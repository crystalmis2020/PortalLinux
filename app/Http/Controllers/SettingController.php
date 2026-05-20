<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Class SettingController
 *
 * Handles account-related settings such as password update for authenticated users.
 */
class SettingController extends Controller
{
    /**
     * Show the password change form.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('settings.index');
    }

    /**
     * Update the logged-in user's password.
     *
     * **Flow:**
     * 1. Validate the incoming password fields with custom rules.
     * 2. Verify the current password matches the user's password.
     * 3. Hash and update the new password.
     * 4. Return success response.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws ValidationException
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',           // at least one uppercase letter
                'regex:/[0-9]/',           // at least one number
                'regex:/[^A-Za-z0-9]/',    // at least one special character
            ],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        $user = Auth::user();

        if (!$this->passwordMatchesStoredHash((string) $user->password, (string) $request->current_password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $newPassword = (string) $request->new_password;

        $user->password = hash('sha256', $newPassword);
        $user->save();
        $user->refresh();

        return response()->json([
            'success' => 'Password updated successfully.',
            'case_check' => [
                'exact_saved' => hash_equals((string) $user->password, hash('sha256', $newPassword)),
                'lowercase_saved' => $newPassword !== strtolower($newPassword)
                    && hash_equals((string) $user->password, hash('sha256', strtolower($newPassword))),
            ],
        ]);
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
}
