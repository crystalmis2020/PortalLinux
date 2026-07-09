<?php

namespace App\Http\Controllers;

use App\Models\ReportAttachment;
use App\Models\IssueCategory;
use App\Models\Notification;
use App\Models\Department;
use App\Models\ReportLog;
use App\Models\Report;
use App\Models\Section;
use App\Models\Issue;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display a list of reports.
     *
     * This view changes based on user type.
     *
     * @return \Illuminate\View\View
     */
    public function index($userId = null, $status = null)
    {
        $user = auth()->user();
        $sectionUsers = User::where('section_id', $user->section_id)
            ->orderBy('full_name')
            ->get();

        $userId = ($userId == null) ? $user->id : $userId;
        $selectedUser = User::find($userId);
        $query = $this->buildReportListQuery($user, $userId, $status);

        $reports = $query->latest()->with('attachment')->limit(50)->get();

        return view('reports.index', compact('reports', 'sectionUsers', 'userId', 'status', 'user', 'selectedUser'));
    }

    public function export($userId = null, $status = null): StreamedResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $userId = ($userId == null) ? $user->id : $userId;

        $reports = $this->buildReportListQuery($user, $userId, $status)
            ->with([
                'departmentAddressFrom',
                'sectionAddressFrom',
                'issueCategory',
                'reportedBy',
            ])
            ->latest()
            ->get();

        $viewLabel = $userId === 'all'
            ? 'all'
            : str($this->exportUserLabel($userId))->slug('-')->value();
        $statusLabel = filled($status) && $status !== 'all'
            ? str($status)->slug('-')->value()
            : 'all';
        $filename = 'reports_' . $viewLabel . '_' . $statusLabel . '_' . now()->format('Y-m-d') . '.xls';

        return $this->downloadReportWorkbook($reports, $filename, $userId, $status, 'Full Report Export');
    }

    public function exportSelected(Request $request, $userId = null, $status = null): StreamedResponse
    {
        $user = auth()->user();

        if (!$user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'report_ids' => 'required|array|min:1',
            'report_ids.*' => 'integer|exists:reports,id',
        ], [
            'report_ids.required' => 'Please select at least one report to export.',
            'report_ids.min' => 'Please select at least one report to export.',
        ]);

        $userId = ($userId == null) ? $user->id : $userId;

        $reports = $this->buildReportListQuery($user, $userId, $status)
            ->whereIn('id', $validated['report_ids'])
            ->with([
                'departmentAddressFrom',
                'sectionAddressFrom',
                'issueCategory',
                'reportedBy',
            ])
            ->latest()
            ->get();

        if ($reports->isEmpty()) {
            return back()->withErrors([
                'report_ids' => 'No selected reports are available for export.',
            ]);
        }

        $filename = 'selected_reports_' . now()->format('Y-m-d') . '.xls';

        return $this->downloadReportWorkbook($reports, $filename, $userId, $status, 'Selected Reports Export');
    }

    protected function downloadReportWorkbook($reports, string $filename, $userId, $status, string $exportType): StreamedResponse
    {
        $assignedUserIds = $reports
            ->flatMap(fn (Report $report) => $report->assigned_users ?? [])
            ->filter()
            ->unique()
            ->values();

        $assignedUsers = User::whereIn('id', $assignedUserIds)->pluck('full_name', 'id');
        $statusCounts = $reports
            ->groupBy(fn (Report $report) => strtolower((string) $report->status))
            ->map->count();
        $scopeLabel = $userId === 'all' ? 'All Users' : $this->exportUserLabel($userId);
        $statusLabel = filled($status) && $status !== 'all' ? strtoupper((string) $status) : 'ALL STATUSES';

        return response()->streamDownload(function () use ($reports, $assignedUsers, $statusCounts, $scopeLabel, $statusLabel, $exportType) {
            echo '<?xml version="1.0" encoding="UTF-8"?>';
            echo '<?mso-application progid="Excel.Sheet"?>';
            echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" ';
            echo 'xmlns:o="urn:schemas-microsoft-com:office:office" ';
            echo 'xmlns:x="urn:schemas-microsoft-com:office:excel" ';
            echo 'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';
            $this->writeExcelStyles();
            echo '<Worksheet ss:Name="Reports"><Table>';
            $this->writeExcelColumns([90, 135, 230, 180, 170, 340, 210, 95, 125, 135]);

            $this->writeExcelRow(['MIS Support Reports'], 'Title', 10);
            $this->writeExcelRow([$exportType], 'Subtitle', 10);
            $this->writeExcelRow([
                'Report ID',
                'Date Reported',
                'From Department / Section',
                'Reported By',
                'Issue Category',
                'Issue Description',
                'Assigned To',
                'Status',
                'Contact Number',
                'Last Updated',
            ], 'Header');

            foreach ($reports as $report) {
                $assignedTo = collect($report->assigned_users ?? [])
                    ->map(fn ($id) => $assignedUsers->get($id))
                    ->filter()
                    ->join(', ');

                $this->writeExcelRow([
                    $report->id,
                    optional($report->created_at)?->format('Y-m-d H:i:s'),
                    trim(($report->departmentAddressFrom?->name ?? 'N/A') . ' - ' . ($report->sectionAddressFrom?->name ?? 'N/A')),
                    $report->reportedBy?->full_name ?? 'N/A',
                    $report->issueCategory?->name ?? 'N/A',
                    $report->issue,
                    $assignedTo ?: 'Unassigned',
                    strtoupper((string) $report->status),
                    $report->contact_number ?: 'N/A',
                    optional($report->updated_at)?->format('Y-m-d H:i:s'),
                ], 'Data');
            }

            echo '</Table></Worksheet></Workbook>';
        }, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    protected function buildReportListQuery(User $user, $userId, $status)
    {
        $query = Report::with(['departmentAddressFrom', 'sectionAddressFrom', 'issueCategory', 'reportedBy']);

        if ($user->user_type == 'admin') {
            switch (true) {
                case $userId === 'all':
                    if ($status && $status !== 'all') {
                        if ($status === 'new') {
                            $query->where(function ($q) {
                                $q->whereJsonLength('assigned_users', 0)->orWhere('status', 'new');
                            });
                        } else {
                            $query->where('status', $status);
                        }
                    }
                    break;

                case !is_null($userId) && is_null($status):
                    $query->where(function ($q) use ($userId) {
                        $q->whereJsonContains('assigned_users', (string) $userId)
                            ->orWhere(function ($subQuery) {
                                $subQuery->where('status', 'new');
                            });
                    });
                    break;

                case !is_null($userId) && !is_null($status):
                    if ($status === 'new') {
                        $query->where(function ($q) use ($userId) {
                            $q->whereJsonContains('assigned_users', (string) $userId)
                                ->orWhere('assigned_users', null);
                        })->where('status', 'new');
                    } else {
                        $query->whereJsonContains('assigned_users', (string) $userId)->where('status', $status);
                    }
                    break;
            }
        } else {
            switch (true) {
                case (is_null($userId) && is_null($status)) || $status == 'all':
                    $query->where('reported_by', $user->id);
                    break;

                case !is_null($userId) && is_null($status):
                    $query->where('reported_by', $userId);
                    break;

                case !is_null($userId) && !is_null($status):
                    if ($status === 'new') {
                        $query->where(function ($q) use ($userId) {
                            $q->where('reported_by', $userId)->orWhereNull('reported_by');
                        })->where('status', 'new');
                    } else {
                        $query->where('reported_by', $userId)->where('status', $status);
                    }
                    break;
            }
        }

        return $query;
    }

    protected function exportUserLabel($userId): string
    {
        if ($userId === 'all') {
            return 'all';
        }

        return User::find($userId)?->full_name ?: 'user-' . $userId;
    }

    protected function writeExcelStyles(): void
    {
        echo '<Styles>';
        echo '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Top" ss:WrapText="1"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>';
        echo '<Style ss:ID="Title"><Font ss:FontName="Calibri" ss:Size="18" ss:Bold="1" ss:Color="#1F4E78"/><Alignment ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="Subtitle"><Font ss:FontName="Calibri" ss:Size="12" ss:Bold="1" ss:Color="#44546A"/><Alignment ss:Vertical="Center"/></Style>';
        echo '<Style ss:ID="Meta"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#404040"/><Interior ss:Color="#F2F6FA" ss:Pattern="Solid"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#D9E2F3"/></Borders></Style>';
        echo '<Style ss:ID="Header"><Font ss:FontName="Calibri" ss:Size="11" ss:Bold="1" ss:Color="#FFFFFF"/><Interior ss:Color="#1F4E78" ss:Pattern="Solid"/><Alignment ss:Horizontal="Center" ss:Vertical="Center" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="2" ss:Color="#17365D"/></Borders></Style>';
        echo '<Style ss:ID="Data"><Font ss:FontName="Calibri" ss:Size="10" ss:Color="#1F1F1F"/><Alignment ss:Vertical="Top" ss:WrapText="1"/><Borders><Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1" ss:Color="#E7E6E6"/></Borders></Style>';
        echo '<Style ss:ID="Spacer"><Font ss:Size="6"/></Style>';
        echo '</Styles>';
    }

    protected function writeExcelColumns(array $widths): void
    {
        foreach ($widths as $width) {
            echo '<Column ss:AutoFitWidth="0" ss:Width="' . e((string) $width) . '"/>';
        }
    }

    protected function writeExcelRow(array $cells, string $style = 'Data', ?int $mergeAcross = null): void
    {
        echo '<Row>';

        if (empty($cells)) {
            echo '<Cell ss:StyleID="' . e($style) . '"><Data ss:Type="String"></Data></Cell>';
            echo '</Row>';
            return;
        }

        foreach ($cells as $index => $cell) {
            $merge = $index === 0 && $mergeAcross !== null ? ' ss:MergeAcross="' . e((string) $mergeAcross) . '"' : '';
            echo '<Cell ss:StyleID="' . e($style) . '"' . $merge . '><Data ss:Type="String">' . e((string) ($cell ?? '')) . '</Data></Cell>';
        }

        echo '</Row>';
    }

    public function details($id, $notification_id = null){

        $user = auth()->user();
        $issues = Issue::all();
        $sections = Section::query()
            ->with('department')
            ->orderBy('name')
            ->get();

        $report = Report::with(
                            ['departmentAddressFrom',
                            'sectionAddressFrom',
                            'issueCategory',
                            'assignedTo',
                            'reportLogs' => function ($query) {
                                $query->orderBy('id', 'desc'); // Order logs by ID descending
                            }])->findOrFail($id);

        $sectionUsers = User::where('section_id', $user->section_id)
            ->orderBy('full_name')
            ->get();

        if ($notification_id) {
            $notification = Notification::find($notification_id);

            if ($notification && $notification->is_read === 'No') {
                $notification->update(['is_read' => 'Yes']);
            }
        }


        return view('reports.details', compact('report', 'sectionUsers', 'user', 'issues', 'sections'));
    }

    public function update(Request $request, Report $report)
    {
        $user = auth()->user();

        if (!$user->isAdmin() && (int) $report->reported_by !== (int) $user->id) {
            abort(403, 'You are not allowed to edit this report.');
        }

        $validated = $request->validate(
            [
                'section_id' => 'required|exists:sections,id',
                'issue_id' => 'required|exists:issues,id',
                'issue' => 'required|string|max:2000',
                'contact_number' => 'nullable|string|max:255',
            ],
            [
                'section_id.required' => 'Please select a section.',
                'section_id.exists' => 'Please select a valid section.',
                'issue_id.required' => 'Please select an issue category.',
                'issue_id.exists' => 'Please select a valid issue category.',
            ]
        );

        $selectedSection = Section::findOrFail($validated['section_id']);

        $report->update([
            'department_address_to' => $selectedSection->department_id,
            'section_address_to' => $selectedSection->id,
            'issue_id' => $validated['issue_id'],
            'issue' => $validated['issue'],
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        ReportLog::create([
            'report_id' => $report->id,
            'user_id' => $user->id,
            'message' => 'Report details were updated by: ' . $user->full_name,
            'status' => $report->status,
            'remarks' => null,
        ]);

        return redirect()
            ->route('reports.details', $report)
            ->with('success', 'Report details updated successfully.');
    }

    public function assign(Request $request, $reportId){

        $userLogin = auth()->user();

        $report = Report::findOrFail($reportId);

        // Validate that user_ids is an array of existing users
        $request->validate([
            'assigned_users' => 'required|array',
            'assigned_users.*' => 'exists:users,id'
        ]);

        // Convert user IDs array to JSON and store
        $report->update([
            'assigned_users' => $request->assigned_users,
            'status' => 'in progress',
            'assigned_by' =>  $userLogin->id,
        ]);

        $assignedUsers = User::whereIn('id', $request->assigned_users)->get();

        // Generate a formatted user names string
        $assignUserNames = $assignedUsers->pluck('full_name')->join(', ', ' and ');

        $count = 0;
        $assignUserNames = "";

        foreach ($assignedUsers as $user) {
            $noti = Notification::create([
                'from_user_id' => $userLogin->id,
                'to_user_id' => $user->id,
                'section_to' => $user->section_id,
                'report_id' => $reportId,
                'title' => 'New report assignment',
                'message' => 'Assigned by: ' .$userLogin->full_name,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                'A report was assigned to you by: '.$userLogin->full_name,
                $user->ip_address
            );

            if(count($assignedUsers) == 1){
                $assignUserNames = $user->full_name;
            }else{
                $count++;
                if($count != count($assignedUsers)){
                    $assignUserNames .= $user->full_name;
                    if($count != (count($assignedUsers) - 1)){
                        $assignUserNames .= ', ';
                    }
                }else{
                    $assignUserNames .= ' and '. $user->full_name;
                }
            }
        }

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $userLogin->id,
            "message" => 'Report was assigned to: '.$assignUserNames.' by: '.$userLogin->full_name,
            "status" => 'assigned',
            "remarks" => null,
        ]);

        $verb = (count($assignedUsers) > 1) ? ' are' : ' is';

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $userLogin->id,
            "message" => $assignUserNames. $verb .' now resolving the reported issue',
            "status" => 'in progress',
            "remarks" => null,
        ]);

        sendIpMsgNotification(
            $assignUserNames. $verb .' now resolving the reported issue',
            $report->reportedBy->ip_address
        );

        $reportLogs = ReportLog::where('report_id', $reportId)->with('user')->latest()->get();

        return response()->json(['success' => 'Users successfully assigned to the report.']);

    }

    public function downloadFile($attachmentId)
    {
        $attachment = ReportAttachment::findOrFail($attachmentId);
        [$filePath] = $this->resolveReportAttachmentPath($attachment);

        return response()->download($filePath, $attachment->original_name);
    }

    public function viewAttachment($attachmentId)
    {
        $attachment = ReportAttachment::findOrFail($attachmentId);
        [$filePath, $mimeType] = $this->resolveReportAttachmentPath($attachment);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($attachment->original_name) . '"',
        ]);
    }

    protected function resolveReportAttachmentPath(ReportAttachment $attachment): array
    {
        $filePath = public_path($attachment->file_path);

        if (!file_exists($filePath)) {
            $legacyPath = storage_path('app/public/' . $attachment->file_path);

            if (file_exists($legacyPath)) {
                $filePath = $legacyPath;
            }
        }

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return [
            $filePath,
            mime_content_type($filePath) ?: 'application/octet-stream',
        ];
    }

    public function message(Request $request, $reportId)
    {
        //get reports where id = $reportId
         // determine if the current loggin user = reporter
           // if yes, insert report_log then notify all reports.assigned_users
           // if no, insert report_log then notify reports.reported_by
        // return success with json

        // Validate the request
        $request->validate([
            'message' => 'required',
        ]);

        $user = Auth::user();


        $report = Report::findOrFail($reportId);

        if($user->id == $report->reported_by){
            ReportLog::create([
                "report_id" => $reportId,
                "user_id" => $user->id,
                "message" => 'A message was sent by: '.$user->full_name,
                "status" => $report->status,
                "remarks" => $request->message,
            ]);
            $assignedUsers = User::whereIn('id', $report->assigned_users ?? [])->get();

            foreach ($assignedUsers as $usr) {
                $noti = Notification::create([
                     'from_user_id' => $user->id,
                     'to_user_id' => $usr->id,
                     'section_to' => $usr->section_id,
                     'report_id' => $report->id, // Store report ID in notification
                     'title' => 'A message was sent for your report #'.$reportId,
                     'message' =>  $request->message,
                     'is_read' => 'No',
                 ]);

                 sendIpMsgNotification(
                    'A message was sent for your report #'.$reportId,
                    $usr->ip_address
                );

            }

            $reportLogs = ReportLog::where('report_id', $reportId)->with('user')->orderBy('id', 'DESC')->get();

            return response()->json([ 'success' => 'Message Sent successfully.', 'report_logs' => $reportLogs ]);

        }else{
            ReportLog::create([
                "report_id" => $reportId,
                "user_id" => $user->id,
                "message" => 'A message was sent by: '.$user->full_name,
                "status" => $report->status,
                "remarks" => $request->message,
            ]);

            $noti = Notification::create([
                'from_user_id' => $user->id,
                'to_user_id' => $report->reported_by,
                'section_to' => $report->section_address_to,
                'report_id' => $report->id, // Store report ID in notification
                'title' => 'A message was sent for your report #'.$reportId,
                'message' => $request->message,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                'A message was sent for your report #'.$reportId,
                $report->reportedBy->ip_address
            );

            $reportLogs = ReportLog::where('report_id', $reportId)->with('user')->orderBy('id', 'DESC')->get();

            return response()->json([ 'success' => 'Message Sent successfully.', 'report_logs' => $reportLogs ]);

        }

    }

    public function uploadAttachment(Request $request, $reportId)
    {
        $request->validate(
            [
                'attachment' => attachmentValidationRule(true),
            ],
            [
                'attachment.max' => 'Attachment is too large. Please choose a file up to ' . attachmentMaxUploadLabel() . '.',
                'attachment.extensions' => 'Unsupported file type. Please upload jpg, jpeg, png, pdf, doc, docx, xls, xlsx, or txt.',
            ]
        );

        $report = Report::findOrFail($reportId);

        try {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getClientMimeType() ?: $file->getMimeType();
            $sizeBytes = $file->getSize();
            $filename = uniqid('report_', true) . ($extension ? '.' . $extension : '');
            $uploadDir = public_path('uploads/reports');

            if (!File::exists($uploadDir)) {
                File::makeDirectory($uploadDir, 0755, true);
            }

            $file->move($uploadDir, $filename);
            $filePath = 'uploads/reports/' . $filename;

            // Save file details in the database
            $attachment = ReportAttachment::create([
                'report_id' => $report->id,
                'file_path' => $filePath,
                'original_name' => $originalName,
            ]);

            return response()->json([
                'success' => 'Attachment uploaded successfully!',
                'attachment' => $attachment,
                'view_url' => route('reports.attachments.view', $attachment),
                'download_url' => route('reports.download', $attachment),
                'extension' => $extension,
                'mime_type' => $mimeType,
                'size_bytes' => $sizeBytes,
                'icon_url' => asset('assets/images/icons/attachment.png') // Update icon path if needed
            ]);

        } catch (\Exception $e) {
            Log::error('File upload failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to upload attachment.'], 500);
        }
    }

    public function reassign(Request $request, $reportId){
        $userLogin = auth()->user();

        $report = Report::findOrFail($reportId);

        // Validate that user_ids is an array of existing users
        $request->validate([
            'assigned_users' => 'required|array',
            'assigned_users.*' => 'exists:users,id',
            'message' => 'required'
        ]);

        $report->update([
            'assigned_users' => $request->assigned_users,
            'assigned_by' => $userLogin->id,
            'status' => 'in progress',
        ]);

        $firstAssignedUserId = collect($request->assigned_users)->first();

        $assignedUsers = User::whereIn('id', $request->assigned_users)->get();

        // Generate a formatted user names string
        $assignUserNames = $assignedUsers->pluck('full_name')->join(', ', ' and ');

        $count = 0;
        $assignUserNames = "";

        foreach ($assignedUsers as $user) {

            $noti = Notification::create([
                'from_user_id' => $userLogin->id,
                'to_user_id' => $user->id,
                'section_to' => $user->section_id,
                'report_id' => $reportId,
                'title' => 'A report was re-assign to you',
                'message' => 'Assigned by: ' .$userLogin->full_name,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                'A report was assigned to you by: '.$userLogin->full_name,
                $user->ip_address
            );

            if(count($assignedUsers) == 1){
                $assignUserNames = $user->full_name;
            }else{
                $count++;
                if($count != count($assignedUsers)){
                    $assignUserNames .= $user->full_name;
                    if($count != (count($assignedUsers) - 1)){
                        $assignUserNames .= ', ';
                    }
                }else{
                    $assignUserNames .= ' and '. $user->full_name;
                }
            }
        }

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $userLogin->id,
            "message" => $userLogin->full_name. ' has resolve a report: '.$request->message,
            "status" => 'resolved',
            "remarks" => $userLogin->full_name.' Report referred to: '.$assignUserNames,
        ]);

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $userLogin->id,
            "message" => 'Report was re-assigned to: '.$assignUserNames.' by: '.$userLogin->full_name,
            "status" => 'assigned',
            "remarks" => null,
        ]);

        $verb = (count($assignedUsers) > 1) ? ' are' : ' is';

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $firstAssignedUserId,
            "message" => $assignUserNames. $verb .' now resolving the reported issue',
            "status" => 'in progress',
            "remarks" => null,
        ]);

        sendIpMsgNotification(
            $assignUserNames. $verb .' is now resolving the reported issue',
            $report->reportedBy->ip_address
        );

        $reportLogs = ReportLog::where('report_id', $reportId)->with('user')->orderBy('id', 'DESC')->get();


        return response()->json(['success' => 'Users successfully assigned to the report.', 'report_logs' => $reportLogs, 'assigned_users' => $assignedUsers]);

    }

    public function resolve(Request $request, $reportId)
    {

        $request->validate([
            'message' => 'required',
        ]);

        $userLogin = auth()->user();

        $report = Report::findOrFail($reportId);

        $report->update([
            'status' => 'resolved',
        ]);

        ReportLog::create([
            "report_id" => $reportId,
            "user_id" => $userLogin->id,
            "message" => $userLogin->full_name.' has resolved the report',
            "status" => 'resolved',
            "remarks" => $request->message,
        ]);

        $noti = Notification::create([
            'from_user_id' => $userLogin->id,
            'to_user_id' => $report->reported_by,
            'section_to' => $report->section_address_from,
            'report_id' => $report->id, // Store report ID in notification
            'title' => 'Report #'.$reportId.' has been resolve by: '.$userLogin->full_name,
            'message' => $request->message,
            'is_read' => 'No',
        ]);

        sendIpMsgNotification(
            $userLogin->fullname.' has resolve a report (ID# '.$report->id.'). \n Please have sometime to review it and close the report.',
            $report->reportedBy->ip_address
        );


        $reportLogs = ReportLog::where('report_id', $reportId)->with('user')->latest()->get();
        return response()->json(['success' => 'Report successfully resolve.', 'report_logs' => $reportLogs]);
    }

    public function reopen(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string'
        ]);

        $userLogin = auth()->user();

        $report = Report::with('reportedBy')->findOrFail($id);

        if ($report->status !== 'resolved') {
            return response()->json([
                'error' => 'Only resolved reports can be re-opened.',
            ], 422);
        }

        $report->update([
            'status' => 'in progress',
        ]);

        ReportLog::create([
            'report_id' => $report->id,
            'user_id' => $userLogin->id,
            'message' => 'Report was re-opened',
            'status' => 'in progress',
            'remarks' => $request->message,
        ]);

        $assignedUserIds = collect($report->assigned_users ?? [])
            ->filter()
            ->values()
            ->all();

        $assignedUsers = User::whereIn('id', $assignedUserIds)->get();

        foreach ($assignedUsers as $user) {

            Notification::create([
                'from_user_id' => $userLogin->id,
                'to_user_id' => $user->id,
                'section_to' => $user->section_id,
                'report_id' => $report->id,
                'title' => 'Report #'.$report->id.' was re-opened by: ' .$userLogin->full_name,
                'message' => $request->message,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                $userLogin->full_name.' has re-opened report #'.$report->id.'.',
                $user->ip_address
            );
        }

        $reportLogs = ReportLog::where('report_id', $id)->with('user')->latest()->get();

        return response()->json([
            'success' => 'Report successfully re-opened.',
            'report_logs' => $reportLogs
        ]);
    }


    public function close(Request $request, $id)
    {
        $request->validate([
            'message' => 'nullable|string'
        ]);

        $userLogin = auth()->user();

        $report = Report::findOrFail($id);

        $canCloseReport = $userLogin->isAdmin()
            || (int) $report->reported_by === (int) $userLogin->id
            || (int) $report->section_address_from === (int) $userLogin->section_id;

        if (!$canCloseReport) {
            return response()->json([
                'error' => 'You are not allowed to close this report.',
            ], 403);
        }

        if ($report->status !== 'resolved') {
            return response()->json([
                'error' => 'Only resolved reports can be closed.',
            ], 422);
        }

        $report->status = 'closed';
        $report->save();

        Notification::where('report_id', $report->id)
            ->where('to_user_id', $userLogin->id)
            ->where('is_read', 'No')
            ->update([
                'is_read' => 'Yes',
                'updated_at' => now(),
            ]);

        ReportLog::create([
            'report_id' => $report->id,
            'user_id' => $userLogin->id,
            'message' => 'Report was closed.',
            'status' => 'closed',
            'remarks' => $request->message,
        ]);

        $assignedUserIds = collect($report->assigned_users ?? [])
            ->filter()
            ->values()
            ->all();

        $assignedUsers = empty($assignedUserIds)
            ? collect()
            : User::whereIn('id', $assignedUserIds)->get();

        foreach ($assignedUsers as $user) {

            $noti = Notification::create([
                'from_user_id' => $userLogin->id,
                'to_user_id' => $user->id,
                'section_to' => $user->section_id,
                'report_id' => $report->id,
                'title' => 'A report has been close by: ' .$userLogin->full_name,
                'message' => ($request->message == null) ? ' ' :  $request->message,
                'is_read' => 'No',
            ]);

            // sendIpMsgNotification(
            //     'A report has been close by: ' .$userLogin->full_name,
            //     $user->ip_address
            // );
        }

        $reportLogs = ReportLog::where('report_id', $id)->with('user')->latest()->get();

        return response()->json([
            'success' => 'Report successfully closed.',
            'report_logs' => $reportLogs
        ]);
    }


    public function assign2(Request $request, $reportId){

        $userLogin = auth()->user();

        $report = Report::findOrFail($reportId);

        // Validate that user_ids is an array of existing users
        $request->validate([
            'assigned_users' => 'required|array',
            'assigned_users.*' => 'exists:users,id'
        ]);

        // Convert user IDs array to JSON and store
        $report->update([
            'assigned_users' => $request->assigned_users,
            'status' => 'in progress',
            'assigned_by' =>  $userLogin->id,
        ]);

        $assignedUsers = User::whereIn('id', $request->assigned_users)->get();

        // Generate a formatted user names string
        $assignUserNames = $assignedUsers->pluck('full_name')->join(', ', ' and ');

        $count = 0;
        $assignUserNames = "";

        foreach ($assignedUsers as $user) {

            $noti = Notification::create([
                'from_user_id' => $userLogin->id,
                'to_user_id' => $user->id,
                'section_to' => $user->section_id,
                'report_id' => $reportId,
                'title' => 'New report assignment',
                'message' => 'Assigned by: ' .$userLogin->full_name,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                'A report was assigned to you by: '.$userLogin->full_name,
                $user->ip_address
            );

            if(count($assignedUsers) == 1){
                $assignUserNames = $user->full_name;
            }else{
                $count++;
                if($count != count($assignedUsers)){
                    $assignUserNames .= $user->full_name;
                    if($count != (count($assignedUsers) - 1)){
                        $assignUserNames .= ', ';
                    }
                }else{
                    $assignUserNames .= ' and '. $user->full_name;
                }
            }


        }

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $userLogin->id,
            "message" => 'Report was assigned to: '.$assignUserNames.' by: '.$userLogin->full_name,
            "status" => 'assigned',
            "remarks" => null,
        ]);

        $verb = (count($assignedUsers) > 1) ? ' are' : ' is';

        ReportLog::create([
            "report_id" => $report->id,
            "user_id" => $userLogin->id,
            "message" => $assignUserNames. $verb .' now resolving the reported issue',
            "status" => 'in progress',
            "remarks" => null,
        ]);

        sendIpMsgNotification(
            $assignUserNames. $verb .' now resolving the reported issue',
            $report->reportedBy->ip_address
        );

        $reportLogs = ReportLog::where('report_id', $reportId)->with('user')->latest()->get();

        return response()->json(['success' => 'Users successfully assigned to the report.', 'assigned_users' => $assignedUsers]);

    }

}
