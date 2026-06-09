<?php

namespace App\Http\Controllers;


use App\Models\IssueSubCategory;
use App\Models\Department;
use App\Models\Section;
use App\Models\Report;
use App\Models\Issue;
use App\Models\User;
use App\Models\PersonnelLeave;
use App\Models\Notification;
use App\Models\ActivityLog;
use App\Models\HostCategory;
use App\Models\NetworkHost;
use App\Models\SlotLocator;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class AdministrativeToolController extends Controller
{

    /**
     * Display a listing of users, departments, and issues.
     *
     * @return \Illuminate\View\View The view displaying administrative data.
     */
    public function index(){
        $users = User::orderBy('full_name')->get();
        $departments = Department::all();
        $issues = Issue::all();

        return view('administrative.index', compact('users', 'departments', 'issues'));
    }

    /**
     * Retrieve sections based on the given department ID.
     *
     * @param int $departmentId The ID of the department.
     * @return \Illuminate\Http\JsonResponse JSON response containing sections.
     */
    public function getSections($departmentId){
        $sections = Section::where('department_id', $departmentId)->get();
        return response()->json($sections);
    }

    /**
     * Save a new user to the system.
     *
     * @param \Illuminate\Http\Request $request The request containing user data.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function saveUser(Request $request){
        $request->validate([
            'full_name' => 'required|string|max:255',
            'ip_address' => 'required|string|max:50',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'section_id' => 'required|exists:sections,id',
            'user_type' => 'required|in:User,Admin,user,admin',
            'can_encode_trip_tickets' => 'nullable|boolean',
            'can_approve_trip_tickets' => 'nullable|boolean',
            'can_manage_trip_tickets' => 'nullable|boolean',
        ]);

        //dd(hash('sha256', $request->password));
        $plainPassword = $request->filled('password') ? $request->password : '123456';

        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'password' => hash('sha256', $plainPassword),
            'department_id' => $request->department_id,
            'section_id' => $request->section_id,
            'ip_address' => $request->ip_address,
            'user_type' => strtolower($request->user_type),
            'can_encode_trip_tickets' => $request->boolean('can_encode_trip_tickets'),
            'can_approve_trip_tickets' => $request->boolean('can_approve_trip_tickets'),
            'can_manage_trip_tickets' => $request->boolean('can_manage_trip_tickets'),
        ]);

        return response()->json([
            'success' => 'User added successfully',
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'department' => $user->department->name ?? 'N/A',
                'section' => $user->section->name ?? 'N/A',
                'status' => $user->is_active ? 'Active' : 'Inactive',
                'trip_ticket_access' => $this->tripTicketAccessSummary($user),
            ]
        ]);
    }

    /**
     * Retrieve user details and related reports for editing.
     *
     * @param int $id The ID of the user.
     * @return \Illuminate\View\View The view displaying user edit form.
     */
    public function edit($id){
        $user = User::findOrFail($id);
        $departments = Department::all();
        $sections = Section::where('department_id', $user->department_id)->get();
        $reports = Report::where('reported_by', $id)->with('issueCategory')->orderBy('id', 'desc')->get();

        return view('administrative.edit-user', compact('user', 'departments', 'sections', 'reports'));
    }

    /**
     * Update user details in the system.
     *
     * @param \Illuminate\Http\Request $request The request containing updated user data.
     * @param int $id The ID of the user.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function updateUser(Request $request, $id){
        try {
            $user = User::findOrFail($id);
            $usernameRules = ['required', 'string', 'max:255'];

            if ((string) $request->username !== (string) $user->username) {
                $usernameRules[] = Rule::unique('users', 'username')->ignore($user->id);
            }

            $request->validate([
                'full_name' => 'required|string|max:255',
                'username' => $usernameRules,
                'department_id' => 'required|exists:departments,id',
                'section_id' => 'required|exists:sections,id',
                'ip_address' => 'required|ip',
                'user_type' => 'required|in:User,Admin,user,admin',
                'can_encode_trip_tickets' => 'nullable|boolean',
                'can_approve_trip_tickets' => 'nullable|boolean',
                'can_manage_trip_tickets' => 'nullable|boolean',
            ]);

            $user->update([
                'full_name' => $request->full_name,
                'username' => $request->username,
                'department_id' => $request->department_id,
                'section_id' => $request->section_id,
                'ip_address' => $request->ip_address,
                'user_type' => strtolower($request->user_type),
                'can_encode_trip_tickets' => $request->boolean('can_encode_trip_tickets'),
                'can_approve_trip_tickets' => $request->boolean('can_approve_trip_tickets'),
                'can_manage_trip_tickets' => $request->boolean('can_manage_trip_tickets'),
            ]);

            return response()->json(['success' => 'User updated successfully', 'user' => $user]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating user:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'user_id' => $id,
                'url' => request()->fullUrl(),
            ]);

            return response()->json(['error' => 'Failed to update user. Please try again.'], 500);
        }
    }

    /**
     * Update the password of a specified user.
     *
     * @param \Illuminate\Http\Request $request The request containing current and new password.
     * @param int $id The ID of the user.
     * @return \Illuminate\Http\JsonResponse JSON response indicating success or failure.
     */
    public function updatePassword(Request $request, $id)
    {
        try {
            $messages = [
                'password.required'  => 'Your password is required for verification.',
                'upassword.required' => 'The new password field is required.',
                'upassword.min'      => 'The new password must be at least 6 characters long.',
                'upassword.regex'    => 'The new password must contain at least one letter, one number, and one special character.',
            ];

            $request->validate([
                // password = current logged-in user's password (verification)
                'password' => 'required|string',

                // upassword = new password for the selected user
                'upassword' => [
                    'required',
                    'string',
                    'min:6',
                    'regex:/^(?=.*[A-Za-z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/',
                ],
            ], $messages);

            $loggedInUser = Auth::user();

            // 1) Verify the logged-in user's password first
            if (!$this->passwordMatchesStoredHash((string) $loggedInUser->password, (string) $request->password)) {
                return response()->json(['error' => 'Verification failed. Your password is incorrect.'], 403);
            }

            // 2) Find the selected user whose password will be changed
            $targetUser = User::findOrFail($id);

            // Optional safety: prevent changing own password here (remove if you want to allow it)
            // if ($targetUser->id === $loggedInUser->id) {
            //     return response()->json(['error' => 'Use your own change-password form to update your password.'], 422);
            // }

            // 3) Update the selected user's password
            //dd(hash('sha256', $request->upassword));
            $newPassword = (string) $request->upassword;

            $targetUser->update([
                'password' => hash('sha256', $newPassword),
            ]);
            $targetUser->refresh();

            return response()->json([
                'success' => 'Password updated successfully.',
                'case_check' => [
                    'exact_saved' => hash_equals((string) $targetUser->password, hash('sha256', $newPassword)),
                    'lowercase_saved' => $newPassword !== strtolower($newPassword)
                        && hash_equals((string) $targetUser->password, hash('sha256', strtolower($newPassword))),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating password:', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'target_user_id' => $id,
                'actor_user_id'  => Auth::id(),
                'url'     => request()->fullUrl(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function passwordMatchesStoredHash(string $storedHash, string $plainPassword): bool
    {
        if (hash_equals($storedHash, hash('sha256', $plainPassword))) {
            return true;
        }

        if (!$this->isLaravelPasswordHash($storedHash)) {
            return false;
        }

        try {
            return Hash::check($plainPassword, $storedHash);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isLaravelPasswordHash(string $storedHash): bool
    {
        return preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $storedHash) === 1;
    }

    protected function tripTicketAccessSummary(User $user): string
    {
        if ($user->canManageTripTickets()) {
            return 'Manager';
        }

        $access = array_filter([
            $user->can_encode_trip_tickets ? 'Encoder' : null,
            $user->can_approve_trip_tickets ? 'Approver' : null,
        ]);

        return empty($access) ? 'Requester' : implode(', ', $access);
    }

    public function destroyUser($id)
    {
        try {
            $user = User::findOrFail($id);

            if ((int) $user->id === (int) Auth::id()) {
                return response()->json([
                    'error' => 'You cannot delete your own account while logged in.',
                ], 422);
            }

            $blockingLinks = array_filter([
                Report::where('reported_by', $user->id)->exists() ? 'reports created' : null,
                Report::where('assigned_by', $user->id)->exists() ? 'reports assigned by this user' : null,
                Report::where('assigned_to', $user->id)->exists() ? 'reports assigned to this user' : null,
                PersonnelLeave::where('user_id', $user->id)->exists() ? 'leave monitoring records' : null,
                PersonnelLeave::where('encode_by', $user->id)->exists() ? 'encoded leave records' : null,
                Notification::where('from_user_id', $user->id)->exists() ? 'sent notifications' : null,
                Notification::where('to_user_id', $user->id)->exists() ? 'received notifications' : null,
                ActivityLog::where('user_id', $user->id)->exists() ? 'activity logs' : null,
                HostCategory::where('added_by', $user->id)->exists() ? 'host categories' : null,
                NetworkHost::where('added_by', $user->id)->exists() ? 'network hosts' : null,
                SlotLocator::where('added_by', $user->id)->exists() ? 'slot locator records' : null,
            ]);

            if (!empty($blockingLinks)) {
                return response()->json([
                    'error' => 'User cannot be deleted because it is linked to: ' . implode(', ', $blockingLinks) . '.',
                ], 422);
            }

            $user->delete();

            return response()->json([
                'success' => 'User deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting user:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'target_user_id' => $id,
                'actor_user_id' => Auth::id(),
                'url' => request()->fullUrl(),
            ]);

            return response()->json([
                'error' => 'Failed to delete user. Please try again.',
            ], 500);
        }
    }




}
