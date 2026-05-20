<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportsBackTransferController extends Controller
{
    public function index()
    {
        $reports = DB::table('reports_bck_03012025')->get();

        return view('transfer.index', compact('reports'));
    }

    public function transfer(Request $request)
    {
        $report = DB::table('reports_bck_03012025')->where('id', $request->id)->first();

        if (!$report) {
            return response()->json(['error' => 'Report not found.'], 404);
        }

        $issueContent = is_resource($report->issue)
            ? stream_get_contents($report->issue)
            : $report->issue;

        DB::beginTransaction();

        try {
            DB::table('reports')->insert([
                'id'                        => $report->id,
                'department_address_to'     => $report->departmentAddressTo,
                'section_address_to'        => $report->sectionAddressTo,
                'department_address_from'   => $report->departmentAddressFrom,
                'section_address_from'      => $report->sectionAddressFrom,
                'issue_id'                  => $report->issueId,
                'issue_sub_category_id'     => $report->issueSubCategoryId,
                'assigned_by'               => $report->assignedBy,
                'assigned_to'               => $report->assignedTo,
                'reported_by'               => $report->reportedBy,
                'issue'                     => $issueContent,
                'contact_number'            => $report->contactNumber,
                'status'                    => $report->status,
                'parent_report_id'          => $report->parentReportId,
                'child_number'              => $report->childNumber,
                'created_at'                => $report->createdAt,
                'updated_at'                => $report->updatedAt,
                'assigned_users'            => json_encode($report->assignedTo !== null ? [strval($report->assignedTo)] : []),
            ]);

            DB::table('reports_bck_03012025')->where('id', $request->id)->delete();

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transfer failed.'], 500);
        }
    }

    public function index2()
    {
        $logs = DB::table('report_logs_bck_03012025')->get();
        return view('transfer.index2', compact('logs'));
    }

    public function transfer2(Request $request)
    {
        $log = DB::table('report_logs_bck_03012025')->where('id', $request->id)->first();

        if (!$log) {
            return response()->json(['error' => 'Log not found.'], 404);
        }

        $remarks = is_resource($log->remarks)
            ? stream_get_contents($log->remarks)
            : $log->remarks;

        DB::beginTransaction();

        try {
            DB::table('report_logs')->insert([
                'id'   => $log->id,
                'report_id'   => $log->reportId,
                'user_id'     => $log->userId,
                'message'     => $log->message,
                'remarks'     => $remarks,
                'status'      => $log->status,
                'parent_id'   => $log->parentId,
                'is_child'    => in_array($log->isChild, ['1', 1]) ? '1' : '0',
                'created_at'  => $log->createdAt,
                'updated_at'  => $log->updatedAt,
            ]);

            DB::table('report_logs_bck_03012025')->where('id', $request->id)->delete();

            DB::commit();

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Transfer failed.'], 500);
        }
    }
}
