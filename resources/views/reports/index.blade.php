@extends('layout.app')
@section('css-custom')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2-bootstrap5.css') }}" />
<style>
    .report-table-wrap {
        width: 100%;
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch;
    }

    .report-table {
        width: max-content;
        min-width: 100%;
    }

    .report-table th,
    .report-table td {
        vertical-align: middle;
    }

    .report-table th:last-child,
    .report-table td:last-child {
        min-width: 88px;
        white-space: nowrap;
        position: sticky;
        right: 0;
        z-index: 2;
        background: rgba(221, 231, 221, 0.98);
        box-shadow: -10px 0 18px rgba(15, 23, 42, 0.08);
    }

    .report-table thead th:last-child {
        z-index: 3;
    }

    html.dark-theme .report-table th:last-child,
    html.dark-theme .report-table td:last-child {
        background: rgba(42, 42, 42, 0.98);
        box-shadow: -10px 0 18px rgba(0, 0, 0, 0.28);
    }

    .report-issue-col {
        min-width: 220px;
        max-width: 260px;
    }

    .report-issue-preview {
        display: inline-block;
        max-width: 100%;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: middle;
        cursor: help;
    }

    .report-issue-tooltip-content {
        white-space: pre-line;
    }

    .report-issue-tooltip .tooltip-inner {
        max-width: 360px;
        padding: 10px 12px;
        background: #1f2937;
        color: #fff;
        font-size: 0.85rem;
        line-height: 1.45;
        text-align: left;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(15, 23, 42, 0.18);
    }

    .report-issue-tooltip {
        opacity: 1 !important;
    }

    .report-issue-tooltip.bs-tooltip-top .tooltip-arrow::before,
    .report-issue-tooltip.bs-tooltip-auto[data-popper-placement^=top] .tooltip-arrow::before {
        border-top-color: #1f2937;
    }

    .report-issue-tooltip.bs-tooltip-bottom .tooltip-arrow::before,
    .report-issue-tooltip.bs-tooltip-auto[data-popper-placement^=bottom] .tooltip-arrow::before {
        border-bottom-color: #1f2937;
    }

    .report-issue-tooltip.bs-tooltip-start .tooltip-arrow::before,
    .report-issue-tooltip.bs-tooltip-auto[data-popper-placement^=left] .tooltip-arrow::before {
        border-left-color: #1f2937;
    }

    .report-issue-tooltip.bs-tooltip-end .tooltip-arrow::before,
    .report-issue-tooltip.bs-tooltip-auto[data-popper-placement^=right] .tooltip-arrow::before {
        border-right-color: #1f2937;
    }
</style>
@endsection
@section('content')

<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Reports</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">List</li>
            </ol>
        </nav>
    </div>
    @if(auth()->user()->user_type == 'admin')
    <div class="ms-auto">
        <div class="d-flex gap-2">
            <a
                href="{{ route('reports.export', ['user' => $userId, 'status' => $status]) }}"
                class="btn btn-success"
            >
                <i class="bx bx-spreadsheet me-1"></i>Export Excel
            </a>
            <button type="button" class="btn btn-success" id="toggle-selected-export" data-export-ready="0">
                <i class="bx bx-list-check me-1"></i>Export Selected
            </button>
            <div class="btn-group">
            <button type="button" class="btn btn-primary  dropdown-toggle-split" data-bs-toggle="dropdown">View By</button>
            <button type="button" class="btn btn-primary split-bg-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">	<span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <div class="dropdown-menu dropdown-menu-right dropdown-menu-lg-end">
                <a class="dropdown-item" href="{{ route('reports.filter', ['user' => 'all', 'status' => $status]) }}">All</a>
                @foreach ($sectionUsers as $sectionUser )
                <a class="dropdown-item" href="{{ route('reports.filter', ['user' => $sectionUser->id, 'status' => $status]) }}">{{ $sectionUser->full_name }}</a>
                <div class="dropdown-divider"></div>
                @endforeach
            </div>
            </div>
        </div>
    </div>
    @endif
</div>
<!--end breadcrumb-->
<div class="row">
    <div class="col-12 col-lg-12 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">
                            Report List  for
                            @if ($userId == $user->id)
                                {{ $user->full_name }}
                            @elseif($userId == 'all')
                                All
                            @else
                                {{ $selectedUser->full_name }}
                            @endif

                        </h6>
                    </div>
                    <div class="dropdown ms-auto">
                          <a href="{{ route('reports.filter', ['user' => $userId]) }}" class="btn badge bg-warning">ALL</a>
                        | <a href="{{ route('reports.filter', ['user' => $userId, 'status' => 'new']) }}" class="btn badge bg-primary">NEW</a>
                        | <a href="{{ route('reports.filter', ['user' => $userId, 'status' => 'in progress']) }}" class="btn badge bg-warning">IN PROGRESS</a>
                        | <a href="{{ route('reports.filter', ['user' => $userId, 'status' => 'resolved']) }}" class="btn badge bg-success">RESOLVED</a>
                        | <a href="{{ route('reports.filter', ['user' => $userId, 'status' => 'closed']) }}" class="btn badge bg-secondary">CLOSED</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @error('report_ids')
                <div class="alert alert-danger" role="alert">{{ $message }}</div>
                @enderror

                <form method="POST" action="{{ route('reports.export-selected', ['user' => $userId, 'status' => $status]) }}" id="selectedReportsExportForm">
                    @csrf
                <div class="table-responsive report-table-wrap">
                    <table class="table mb-0 table-hover table-bordered report-table">
                        <thead>
                            <tr>
                                @if(auth()->user()->user_type == 'admin')
                                <th class="text-center selected-export-control d-none" style="width: 48px;">
                                    <input class="form-check-input" type="checkbox" id="select-all-reports" aria-label="Select all reports">
                                </th>
                                @endif
                                <th>From Department</th>
                                <th class="report-issue-col">Reported Issue</th>
                                <th scope="col">Reported by</th>
                                <th scope="col">Assigned To</th>
                                <th>Status</th>
                                <th class="text-center"> </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($reports as $report)
                            <tr>
                                @if(auth()->user()->user_type == 'admin')
                                <td class="text-center selected-export-control d-none">
                                    <input class="form-check-input report-select-checkbox" type="checkbox" name="report_ids[]" value="{{ $report->id }}" aria-label="Select report {{ $report->id }}">
                                </td>
                                @endif
                                <td>{{ $report->departmentAddressFrom->name ?? 'N/A' }} - {{ $report->sectionAddressFrom->name ?? 'N/A' }}</td>
                                <td class="report-issue-col">
                                    @php
                                        $issueTooltip = 'Category: ' . ($report->issueCategory->name ?? 'N/A') . "\n\n" . 'Issue: ' . ($report->issue ?? 'N/A');
                                    @endphp
                                    <span
                                        class="report-issue-preview"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-custom-class="report-issue-tooltip"
                                        data-bs-html="true"
                                        title="<div class='report-issue-tooltip-content'>{{ e($issueTooltip) }}</div>"
                                    >
                                        {{ \Illuminate\Support\Str::limit($report->issue ?? 'N/A', 30) }}
                                    </span>
                                </td>
                                <td>{{ $report->reportedBy->full_name ?? 'N/A' }}</td>
                                <td class="td-{{$report->id}}">
                                    @if ($report->assigned_users)
                                    {!! getAssignedUserNames($report->assigned_users) !!}
                                    @else
                                        @if(auth()->user()->user_type == 'admin')
                                        <button type="button" class="btn btn-sm btn-primary assign-btn" data-bs-toggle="modal" data-bs-target="#assignToModal2" id="{{$report->id}}"><i class="bx bx-user-plus mr-1"></i>Assign</button>
                                        @endif
                                    @endif
                                </td>
                                <td>
                                    <span class="btn badge {{ getReportStatusClass($report->status) }} report-status-{{$report->id}}" >
                                        {{ strtoupper($report->status) }}
                                    </span>
                                </td>
                                <td class="text-center font-22">
                                    @if(auth()->user()->isAdmin() || (int) $report->reported_by === (int) auth()->id())
                                        <a href="{{ route('reports.details', $report->id) }}?edit=1" class="me-1" title="Edit report" aria-label="Edit report">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('reports.details', $report->id) }}"><i class="bx bx-dots-horizontal-rounded"></i></a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Assign Modal -->
<div class="modal fade" id="assignToModal2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fadeIn animated bx bx-user-plus"></i> Assign to</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="assignToSubmitForm2">
            @csrf
                <div class="modal-body">
                    <div class="mb-4">
                        <select class="form-select selected-user" id="multiple-select-clear-field" name="assigned_users[]" data-placeholder="Select person" multiple>
                            @forelse ($sectionUsers as $usr)
                            <option value="{{ $usr->id }}">{{ $usr->full_name }}</option>
                            @empty
                            <option>No Persons found</option>
                            @endforelse
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="report_id" value="">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>
@section('js-custom')
<script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
<script src="{{ asset('assets/plugins/select2/js/select2-custom.js') }}"></script>

<script>
        const REPORT_ASSIGN_URL = "{{ route('reports.assign2', ':id') }}";
</script>

<script>
    $(function () {
        if (window.innerWidth >= 1024) {
            const $wrapper = $('.wrapper');
            const $sidebar = $('.sidebar-wrapper');

            $wrapper.addClass('toggled').removeClass('sidebar-hovered');

            $sidebar.off('mouseenter.reports mouseleave.reports');
            $sidebar.on('mouseenter.reports', function () {
                $wrapper.addClass('sidebar-hovered');
            }).on('mouseleave.reports', function () {
                $wrapper.removeClass('sidebar-hovered');
            });
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            new bootstrap.Tooltip(element);
        });

        const selectedExportButton = document.getElementById('toggle-selected-export');
        const selectedExportForm = document.getElementById('selectedReportsExportForm');
        const selectAllReports = document.getElementById('select-all-reports');
        const reportCheckboxes = document.querySelectorAll('.report-select-checkbox');
        const selectedExportControls = document.querySelectorAll('.selected-export-control');

        if (selectedExportButton) {
            selectedExportButton.addEventListener('click', function () {
                if (selectedExportButton.dataset.exportReady !== '1') {
                    selectedExportControls.forEach(function (control) {
                        control.classList.remove('d-none');
                    });
                    selectedExportButton.dataset.exportReady = '1';
                    selectedExportButton.innerHTML = '<i class="bx bx-download me-1"></i>Download Selected';
                    return;
                }

                const hasSelectedReport = Array.from(reportCheckboxes).some(function (checkbox) {
                    return checkbox.checked;
                });

                if (!hasSelectedReport) {
                    alert('Please select at least one report to export.');
                    return;
                }

                selectedExportForm.submit();
            });
        }

        if (selectAllReports) {
            selectAllReports.addEventListener('change', function () {
                reportCheckboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAllReports.checked;
                });
            });
        }

        reportCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', function () {
                if (!selectAllReports) {
                    return;
                }

                selectAllReports.checked = Array.from(reportCheckboxes).every(function (item) {
                    return item.checked;
                });
            });
        });
    });
</script>

<script src="{{ asset('assets/js/custom/reports.js') }}"></script>
@endsection
@endsection
