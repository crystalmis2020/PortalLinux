<?php

namespace App\Http\Controllers;

use App\Models\ReportAttachment;
use App\Models\Notification;
use App\Models\ReportLog;
use App\Models\Report;
use App\Models\Section;
use App\Models\Issue;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;


class DashboardController extends Controller
{
    /**
     * Display the dashboard index page.
     *
     * @return \Illuminate\View\View
     */
    public function index(){

        $issues = Issue::paginate(50);
        $user = Auth::user()->loadMissing(['department', 'section']);
        $sections = Section::query()
            ->with('department')
            ->orderBy('name')
            ->get();

        $previousReports = Report::where('reported_by', $user->id)
            ->with([
                'departmentAddressFrom',
                'sectionAddressFrom',
                'reportedBy',
                'issueCategory'
            ]) // Lazy load relationships
            ->orderBy('created_at', 'desc')
            ->get();

        $resolvedNotifications = Notification::with([
                'report.issueCategory',
                'fromUser:id,full_name',
            ])
            ->where('to_user_id', $user->id)
            ->where('is_read', 'No')
            ->whereHas('report', function ($query) use ($user) {
                $query->where('reported_by', $user->id)
                    ->where('status', 'resolved');
            })
            ->latest()
            ->get()
            ->unique('report_id')
            ->values();

        return view('dashboard.index', compact('issues', 'previousReports', 'user', 'sections', 'resolvedNotifications'));
    }

    /**
     * Store a new report in the database and notify relevant users.
     *
     * This method validates the incoming request, saves the report data to the database,
     * and sends notifications to all users within the assigned section.
     *
     * **Flow:**
     * 1. Validate the request to ensure all required fields are present and valid.
     * 2. Retrieve the authenticated user.
     * 3. Create a new report using the provided request data.
     * 4. Identify all users assigned to the reporter's section
     *    by querying the `users` table where `section_id` matches.
     * 5. Iterate through the retrieved users and generate a new notification entry
     *    for each one, linking it to the newly created report.
     * 6. Return a JSON response with a success message and the saved report details.
     *
     * @param Request $request The incoming request containing the following parameters:
     * - **issue_id** (`int`, required) - The ID of the issue category.
     * - **issue_sub_category_id** (`int`, nullable) - The ID of the issue sub-category (if applicable).
     * - **issue** (`string`, required) - A brief description of the issue.
     * - **contact_number** (`string`, nullable) - Contact number of the reporting user.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating success or failure.
     *
     * @throws \Illuminate\Validation\ValidationException If the request validation fails.
     */
    public function saveReport(Request $request){

        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }


        $user = Auth::user()->loadMissing(['department', 'section']);

        if (!$user->department_id || !$user->section_id) {
            return response()->json([
                'success' => false,
                'message' => 'Your account needs a department and section before you can send a report.',
            ]);
        }

        // Resolved reports must be reviewed and closed before users can submit
        // another report.
        $openStatuses = ['resolved'];

        $openReports = Report::where('reported_by', $user->id)
            ->whereIn('status', $openStatuses)
            ->count();

        if ($openReports > 0) {
            return response()->json([
                'success' => false,
                'message' => 'You still have ' . $openReports . ' resolved report(s). Please review and close them before sending a new one.',
            ]);
        }

        // Validate the request
        $request->validate(
            [
                'issue_id' => 'required|exists:issues,id',
                'issue_sub_category_id' => 'nullable|exists:issue_sub_categories,id',
                'issue' => 'required|string',
                'contact_number' => 'nullable|string',
                'section_id' => 'required|exists:sections,id',
                'attachment' => attachmentValidationRule(),
            ],
            [
                'issue_id.required' => 'Please select an issue category.',
                'issue_id.exists' => 'Please select a valid issue category.',
                'section_id.required' => 'Please select a section.',
                'section_id.exists' => 'Please select a valid section.',
                'attachment.max' => 'Attachment is too large. Please choose a file up to ' . attachmentMaxUploadLabel() . '.',
                'attachment.extensions' => 'Unsupported file type. Please upload jpg, jpeg, png, pdf, doc, docx, xls, xlsx, or txt.',
            ]
        );

        $selectedSection = Section::with('department')->findOrFail($request->integer('section_id'));

        // Save the report
        $report = Report::create([
            'department_address_to' => $selectedSection->department_id,
            'section_address_to' => $selectedSection->id,
            'department_address_from' => $user->department_id,
            'section_address_from' => $user->section_id,
            'issue_id' => $request->issue_id,
            'issue_sub_category_id' => '0',
            'assigned_by' => null,
            'assigned_to' => null,
            'reported_by' => $user->id,
            'issue' => $request->issue,
            'contact_number' => $request->contact_number,
            'status' => 'new',
            'parent_report_id' => null,
            'child_number' => null,
        ]);

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $user->id,
            "message" => 'A new report was submitted',
            "status" => 'new',
            "remarks" => null,
        ]);

        // Handle single file upload
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $extension = strtolower($file->getClientOriginalExtension());
            $filename = uniqid('report_', true) . ($extension ? '.' . $extension : '');
            $uploadDir = public_path('uploads/reports');

            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $filename);
            $filePath = 'uploads/reports/' . $filename;

            // Save file details in the database
            ReportAttachment::create([
                'report_id' => $report->id,
                'file_path' => $filePath,
                'original_name' => $file->getClientOriginalName(),
            ]);
        }

        // New reports should alert the admin team. Regular users receive
        // follow-up alerts only through actions tied to their own report.
        $departmentName = $user->department?->name ?: 'Unknown department';

        $adminUsers = User::query()
            ->whereRaw('LOWER(user_type) = ?', ['admin'])
            ->where('is_active', true)
            ->get();

        foreach ($adminUsers as $usr) {
            Notification::create([
                'from_user_id' => $user->id,
                'to_user_id' => $usr->id,
                'section_to' => $usr->section_id,
                'report_id' => $report->id, // Store report ID in notification
                'title' => 'New Report Submitted',
                'message' => 'Submitted by: ' .$user->full_name,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                'A new report was sent from '.$departmentName.' by '.$user->full_name,
                $usr->ip_address
            );
        }

        $report->load([
            'issueCategory',
            'reportedBy',
            'reportLogs.user',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully!',
            'report' => $report,
            'report_logs' => $report->reportLogs,
            'details_url' => route('reports.details', $report->id),
        ]);
    }
}
