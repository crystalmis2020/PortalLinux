<?php

// app/Http/Controllers/LeaveMonitoringController.php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Section;
use App\Models\User;
use App\Models\PersonnelLeave;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveMonitoringController extends Controller
{
    /**
     * Display the Leave Monitoring page.
     *
     * Lists entries where to_date >= today (Asia/Manila) to show
     * those currently on leave or scheduled to be on leave.
     */
    public function index()
    {
        $today        = now('Asia/Manila')->toDateString();
        $currentUser  = auth()->user();
        $deptId       = $currentUser->department_id;
        $sectionId       = $currentUser->section_id;

        // List only leaves within the same department, still active/upcoming
        $leaves = \App\Models\PersonnelLeave::with([
                'user:id,full_name',
                'department:id,name',
                'section:id,name',
                'encoder:id,full_name',
            ])
            ->where('section_id', $sectionId)
            ->whereDate('to_date', '>=', $today)
            ->orderBy('from_date')
            ->get();

        // Form dropdowns: users & sections only from current user's department
        $users       = \App\Models\User::select('id','full_name')
                        ->where('section_id', $sectionId)
                        ->orderBy('full_name')
                        ->get();

        $department  = \App\Models\Department::select('id','name')->find($deptId);

        $sections    = \App\Models\Section::select('id','name','department_id')
                        ->where('department_id', $deptId)
                        ->orderBy('name')
                        ->get();

        return view('leave_monitoring.index', compact('leaves','users','department','sections','today'));
    }

    public function store(Request $request)
    {
        $auth      = auth()->user();
        $deptId    = $auth->department_id;
        $sectionId = $auth->section_id;

        $validated = $request->validate([
            'user_id'       => ['required', 'exists:users,id'],
            'department_id' => ['required', Rule::in([$deptId])],
            'section_id'    => ['required'],
            'from_date'     => ['required', 'date'],
            'to_date'       => ['required', 'date', 'after_or_equal:from_date'],
            'reason'        => ['required', 'string', 'max:255'],
            'leave_address' => ['required', 'string', 'max:255'],
        ]);

        // Ensure selected employee is in the same section
        $targetUser = User::select('id','section_id')->findOrFail($validated['user_id']);
        if ((int) $targetUser->section_id !== (int) $sectionId) {
            return response()->json([
                'message' => 'Selected employee is not in your section.',
                'errors'  => ['user_id' => ['Selected employee is not in your section.']],
            ], 422);
        }

        PersonnelLeave::create([
            'user_id'       => $validated['user_id'],
            'department_id' => $deptId,
            'section_id'    => $sectionId,
            'from_date'     => $validated['from_date'],
            'to_date'       => $validated['to_date'],
            'reason'        => $validated['reason'],
            'leave_address' => $validated['leave_address'],
            'encode_by'     => $auth->id,
        ]);

        // Return ALL current/upcoming leaves in the same section as JSON
        $today = now('Asia/Manila')->toDateString();

        $leaves = PersonnelLeave::with([
                'user:id,full_name',
                'section:id,name',
            ])
            ->where('section_id', $sectionId)
            ->whereDate('to_date', '>=', $today)
            ->orderBy('from_date')
            ->get()
            ->map(fn ($l) => [
                'id'             => $l->id,
                'user_full_name' => $l->user?->full_name,
                'section_name'   => $l->section?->name,
                'from_date'      => $l->from_date->toDateString(), // YYYY-MM-DD
                'to_date'        => $l->to_date->toDateString(),   // YYYY-MM-DD
                'reason'         => $l->reason,
                'leave_address'  => $l->leave_address,
            ]);

        return response()->json([
            'message' => 'Leave saved successfully.',
            'leaves'  => $leaves,
        ]);
    }
}
