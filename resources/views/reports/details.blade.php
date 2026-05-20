@extends('layout.app')
@section('css-custom')
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2-bootstrap5.css') }}" />
<style>
    .report-attachment {
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(11, 107, 55, 0.08);
        color: #193121;
        display: flex;
        flex-direction: column;
        gap: 10px;
        min-width: 220px;
    }

    .report-attachment__name {
        font-weight: 700;
        line-height: 1.35;
        word-break: break-word;
    }

    .report-attachment__meta {
        margin-top: 4px;
        font-size: 0.78rem;
        color: #64766a;
    }

    .report-attachment__actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .report-attachment__action {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: #084726;
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
        border: 0;
    }

    .report-attachment-preview__body {
        min-height: 320px;
        max-height: 70vh;
        overflow: auto;
        background: #f5f7f6;
    }

    .report-attachment-preview__frame {
        width: 100%;
        min-height: 70vh;
        border: 0;
        background: #f5f7f6;
    }

    .report-attachment-preview__image {
        display: block;
        max-width: 100%;
        max-height: 70vh;
        margin: 0 auto;
        border-radius: 12px;
    }

    .report-attachment-preview__text,
    .report-attachment-preview__empty {
        min-height: 320px;
        margin: 0;
        padding: 18px;
        color: #193121;
        white-space: pre-wrap;
        word-break: break-word;
    }

    html.dark-theme .report-attachment {
        background: rgba(255, 255, 255, 0.08);
        color: #f4f7f5;
    }

    html.dark-theme .report-attachment__meta {
        color: #b9c7bf;
    }

    html.dark-theme .report-attachment__action {
        background: rgba(255, 255, 255, 0.12);
        color: #e6f5ea;
    }

    html.dark-theme .report-attachment-preview__body,
    html.dark-theme .report-attachment-preview__frame,
    html.dark-theme .report-attachment-preview__text {
        background: #18211c;
        color: #f4f7f5;
    }

    html.dark-theme .report-attachment-preview__empty {
        color: #c9d6ce;
    }
</style>
@endsection
@section('content')


<!--breadcrumb-->
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">Reports: # {{$report->id}} from: {{ @$report->departmentAddressFrom->name}} - {{$report->sectionAddressFrom->name}}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">List</a></li>
                <li class="breadcrumb-item active" aria-current="page">Details</li>
            </ol>
        </nav>
    </div>

</div>
<!--end breadcrumb-->

{{-- Detail --}}
<div class="card h-100">
    @php
        $canManageTargetReport = $user->isAdmin() || (int) $report->section_address_to === (int) $user->section_id;
        $canManageResolvedReport = $user->isAdmin()
            || (int) $report->reported_by === (int) $user->id
            || (int) $report->section_address_from === (int) $user->section_id;
        $canEditReport = $user->isAdmin() || (int) $report->reported_by === (int) $user->id;
    @endphp
    <div class="row g-0 d-flex align-items-stretch">
        <!-- Left Column (Image) -->
        <div class="col-md-3 border-end justify-content-center align-items-center mb-3 ml-3">
            <img src="{{ asset('assets/images/default_issue_img.png') }}" class="mt-3 rounded" style="width:80%; margin-left: 2rem !important;" alt="{{$report->issueCategory->name}}">
        </div>

        <!-- Right Column (Text Content) -->
        <div class="col-md-9 d-flex flex-column justify-content-end">
            <div class="card-body d-flex flex-column">
                <div class="row">
                    <h4 class="card-title d-flex align-items-center gap-2">
                        <span class="issue-category-name">{{$report->issueCategory->name}}</span>
                        @if ($canEditReport)
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#editReportModal"
                            >
                                <i class="bx bx-edit me-1"></i>Edit
                            </button>
                        @endif
                    </h4>
                    <p class="card-text fs-6"> {!! nl2br(e($report->issue)) !!}</p>
                    <p class="card-text fs-6 mt-4">Contact: {{ $report->contact_number }}</p>
                </div>
                <div class="row mt-auto">
                    <div class="row row-cols-auto row-cols-1 row-cols-md-4 align-items-center">

                        <div class="d-flex align-items-center theme-icons shadow-sm p-2 cursor-pointer rounded" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Status">
							<div class="font-22 text-primary">	<i class="fadeIn animated bx bx-bolt-circle"></i>
							</div>
							<div class="ms-2">
                                <span class="btn badge {{ getReportStatusClass($report->status) }} report-status">
                                    {{ strtoupper($report->status) }}
                                </span>
                            </div>
						</div>

                        <div class="d-flex align-items-center theme-icons shadow-sm p-2 cursor-pointer rounded" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Assigned To">
							<div class="font-22 text-primary">
                                <i class="fadeIn animated bx bx-user-circle"></i>
							</div>

                                @if (is_null($report->assigned_users) && $canManageTargetReport)
                                <div class="ms-2" data-bs-toggle="modal" data-bs-target="#assignToModal">
                                    assign to
                                </div>
                                @else
                                <div class="ms-2 assigned-users">
                                    {!! getAssignedUserNames($report->assigned_users) !!}
                                </div>
                                @endif

						</div>

                        <div class="d-flex align-items-center theme-icons shadow-sm p-2 cursor-pointer rounded" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Date Sent">
							<div class="font-22 text-primary">	<i class="fadeIn animated bx bx-calendar-alt"></i>
							</div>
							<div class="ms-2">{{ $report->created_at->format('M d, Y h:i:s a') }}</div>
						</div>

                        <div class="d-flex align-items-center theme-icons shadow-sm p-2 cursor-pointer rounded" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="Date Last Update">
							<div class="font-22 text-primary">	<i class="fadeIn animated bx bx-calendar-star"></i>
							</div>
							<div class="ms-2">{{ $report->updated_at->format('M d, Y h:i:s a') }}</div>
						</div>
                    </div>
                    <div class="row row-cols-auto row-cols-1 row-cols-md-4 align-items-center attachment-row">
                        @foreach ($report->attachment as $attachment)
                        @php

                            $filename = pathinfo($attachment->original_name, PATHINFO_FILENAME);
                            $extension = pathinfo($attachment->original_name, PATHINFO_EXTENSION);
                            $shortFilename = Str::limit($filename, 10, '...') . '.' . $extension;
                        @endphp
                        <div class="report-attachment">
                            <div>
                                <div class="report-attachment__name">{{ $attachment->original_name }}</div>
                                <div class="report-attachment__meta">{{ strtoupper($extension ?: 'FILE') }}</div>
                            </div>
                            <div class="report-attachment__actions">
                                <button
                                    type="button"
                                    class="report-attachment__action report-attachment-view"
                                    data-attachment-view="{{ route('reports.attachments.view', $attachment->id) }}"
                                    data-attachment-download="{{ route('reports.download', $attachment->id) }}"
                                    data-attachment-name="{{ $attachment->original_name }}"
                                    data-attachment-extension="{{ strtolower($extension) }}"
                                >
                                    <i class='bx bx-show'></i>
                                    <span>View</span>
                                </button>
                                <a href="{{ route('reports.download', $attachment->id) }}" class="report-attachment__action">
                                    <i class='bx bx-download'></i>
                                    <span>Download</span>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if ($canEditReport)
<!-- Edit Report Modal -->
<div class="modal fade" id="editReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bx bx-edit"></i> Edit Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('reports.update', $report) }}">
                @csrf
                <div class="modal-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="edit_report_section_id" class="form-label">Section</label>
                        <select class="form-select" id="edit_report_section_id" name="section_id" required>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" {{ (int) old('section_id', $report->section_address_to) === (int) $section->id ? 'selected' : '' }}>
                                    {{ $section->name }}{{ $section->department ? ' - ' . $section->department->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_report_issue_id" class="form-label">Issue Category</label>
                        <select class="form-select" id="edit_report_issue_id" name="issue_id" required>
                            @foreach($issues as $issue)
                                <option value="{{ $issue->id }}" {{ (int) old('issue_id', $report->issue_id) === (int) $issue->id ? 'selected' : '' }}>
                                    {{ $issue->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="edit_report_issue" class="form-label">Brief Description</label>
                        <textarea class="form-control" id="edit_report_issue" name="issue" rows="5" maxlength="2000" required>{{ old('issue', $report->issue) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="edit_report_contact_number" class="form-label">Contact</label>
                        <input type="text" class="form-control" id="edit_report_contact_number" name="contact_number" value="{{ old('contact_number', $report->contact_number) }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Assign Modal -->
<div class="modal fade" id="assignToModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fadeIn animated bx bx-user-plus"></i> Assign to</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="assignToSubmitForm">
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
                    <input type="hidden" id="report_id" value="{{$report->id}}">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Thread and Actions --}}
<div class="row">
    {{-- Thread --}}
    <div class="col-12 col-lg-7 col-xl-8 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Thread</h6>
                    </div>
                    <div class="dropdown ms-auto">
                        <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class='font-22 text-option'>&NonBreakingSpace;</i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    @foreach ($report->reportLogs as $log)
                    <li class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">{{ $log->user->full_name }}</h5>
                            <small class="text-muted">{{ $log->created_at->format('M d, Y h:i:s a') }}</small>
                        </div>
                        <span class="btn badge {{ getReportStatusClass($log->status) }}" style="float:right;">
                            {{ strtoupper($log->status) }}
                        </span>
                        <div class="mb-1" style="width: 80% !important;">{!! nl2br(e($log->message)) !!}</div>
                        <small class="text-muted" >Remarks: {!! nl2br(e($log->remarks)) !!}</small>
                    </li>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    {{-- Action --}}
    <div class="col-12 col-lg-7 col-xl-4 d-flex">
        <div class="card radius-10 w-100">
            <div class="card-header">
                <div class="d-flex align-items-center">
                    <div>
                        <h6 class="mb-0">Action</h6>
                    </div>
                    <div class="dropdown ms-auto">
                        <a class="dropdown-toggle dropdown-toggle-nocaret" href="#" data-bs-toggle="dropdown"><i class='font-22 text-option'>&NonBreakingSpace;</i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body d-flex flex-column align-items-center action-div">


                {{--
                    if report.department_address_to == user.department_id && report.status == in progress
                     show
                       - message box
                       - send button
                       - Resolve button
                       - Resolve & Re-assign button
                    if report.department_address_from == user.department_id && report.status == resolved
                     show
                       - message box
                       - send button
                       - Close Report button
                       - Re-open button
                --}}
                <div class="col-sm-12 text-center">
                @if ($report->status == 'in progress'  || $report->status == 'assigned') {{-- if report is still new --}}

                    <textarea class="form-control" id="message" name="message" rows="3" placeholder="Say something"></textarea>
                    <div class="row row-cols-auto g-3 mt-1 justify-content-center">
                        @if ($canManageTargetReport) {{-- if the report is address to the current login user's section or the user is admin --}}
                            <div class="col">
                                <button type="button" class="btn btn-primary send-message"><i class="bx bx-send mr-1"></i>Send</button>
                            </div>
                            <div class="col">
                                <button type="button" class="btn btn-success resolve-report btn-resolve-report"><i class="bx bx-check mr-1"></i>Resolve</button>
                            </div>
                            <div class="col">
                                <button type="button" class="btn btn-danger " data-bs-toggle="modal" data-bs-target="#resolveAndReAssignToModal"><i class="bx bx-user-check mr-1"></i>Resolve and Reassign</button>
                            </div>
                            <div class="col">
                                <button type="button" class="btn btn-info " data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal"><i class="bx bx-paperclip mr-1"></i>Attach file</button>
                            </div>
                        @else
                            {{-- if the report report is resolved and current login user section is equal to report  --}}
                            @if ($report->status == 'resolved' && $canManageResolvedReport)
                                    <div class="col close-reopen">
                                        <button type="button" class="btn btn-primary close-report btn.close-report"><i class="bx bx-check-square mr-1"></i>Close</button>
                                    </div>
                                    <div class="col close-reopen" >
                                        <button type="button" class="btn btn-secondary reopen-report"><i class="bx bx-folder-open mr-1"></i>Re-open</button>
                                    </div>

                                    <div class="col send-attached" style="display: none;">
                                        <button type="button" class="btn btn-primary send-message"><i class="bx bx-send mr-1"></i>Send</button>
                                    </div>
                                    <div class="col send-attached" style="display: none;">
                                        <button type="button" class="btn btn-info " data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal"><i class="bx bx-paperclip mr-1"></i>Attach file</button>
                                    </div>

                            @else
                                <div class="col">
                                    <button type="button" class="btn btn-primary send-message"><i class="bx bx-send mr-1"></i>Send</button>
                                </div>
                                <div class="col">
                                    <button type="button" class="btn btn-info " data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal"><i class="bx bx-paperclip mr-1"></i>Attach file</button>
                                </div>
                            @endif
                        @endif
                    </div>

                @endif

                {{-- if the report report is resolved and current login user section is equal to report  --}}
                @if ($report->status == 'resolved' && $canManageResolvedReport)
                <textarea class="form-control mb-2" id="message" name="message" rows="3" placeholder="Say something"></textarea>
                    <div class="col close-reopen mb-2" style="float: left;">
                        <button type="button" class="btn btn-primary close-report btn-close-report"><i class="bx bx-check-square mr-1 "></i>Close</button>
                    </div>
                    <div class="col close-reopen" style="float: right;">
                        <button type="button" class="btn btn-secondary reopen-report"><i class="bx bx-folder-open mr-1"></i>Re-open</button>
                    </div>

                    <div class="col send-attached mb-2" style="display: none;">
                        <button type="button" class="btn btn-primary send-message"><i class="bx bx-send mr-1"></i>Send</button>
                    </div>
                    <div class="col send-attached mb-2" style="display: none;">
                        <button type="button" class="btn btn-info " data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal"><i class="bx bx-paperclip mr-1"></i>Attach file</button>
                    </div>

                @else
                    {{-- <div class="col">
                        <button type="button" class="btn btn-primary send-message"><i class="bx bx-send mr-1"></i>Send</button>
                    </div>
                    <div class="col">
                        <button type="button" class="btn btn-info " data-bs-toggle="modal" data-bs-target="#uploadAttachmentModal"><i class="bx bx-paperclip mr-1"></i>Attach file</button>
                    </div> --}}
                @endif

                </div>
            </div>
        </div>
    </div>
</div>

{{-- Upload Attachment Modal --}}
<div class="modal fade" id="uploadAttachmentModal" tabindex="-1" aria-labelledby="uploadAttachmentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadAttachmentModalLabel">Upload Attachment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="uploadAttachmentForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="report_id" value="{{ $report->id }}">

                    <div class="mb-3">
                        <label for="attachment" class="form-label">Choose a file</label>
                        <input type="file" class="form-control" name="attachment" id="attachment" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Upload</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Attachment Preview Modal --}}
<div class="modal fade" id="reportAttachmentPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1" id="reportAttachmentPreviewTitle">Attachment preview</h5>
                    <p class="mb-0 text-muted small" id="reportAttachmentPreviewMeta"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body report-attachment-preview__body" id="reportAttachmentPreviewBody">
                <div class="report-attachment-preview__empty">Select a file to preview.</div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-outline-secondary" id="reportAttachmentPreviewDownload" download>
                    <i class='bx bx-download me-1'></i>Download
                </a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Resolve and Assign Modal -->
<div class="modal fade" id="resolveAndReAssignToModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fadeIn animated bx bx-user-plus"></i> Assign to</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="" id="resolveAndReAssignToSubmitForm">
            @csrf
                <div class="modal-body">
                    <div class="mb-4">
                        <select class="form-select selected-user-reassign" id="resolveAndReAssignTo-multiple-select" name="assigned_users[]" data-placeholder="Select person" multiple>
                            @forelse ($sectionUsers as $usr)
                            <option value="{{ $usr->id }}">{{ $usr->full_name }}</option>
                            @empty
                            <option>No Persons found</option>
                            @endforelse
                        </select>
                    </div>
                </div>
                {{-- test --}}
                <div class="modal-footer">
                    <input type="hidden" id="report_id" name="report_id" value="{{$report->id}}">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Re-Assign</button>
                </div>
            </form>
        </div>
    </div>
</div>



    @section('js-custom')
    <script>
        $(function () {
            $('[data-bs-toggle="popover"]').popover();
            $('[data-bs-toggle="tooltip"]').tooltip();

            @if ($canEditReport && (request()->boolean('edit') || $errors->any()))
                new bootstrap.Modal(document.getElementById('editReportModal')).show();
            @endif
        })
    </script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2-custom.js') }}"></script>

    <script>
        const REPORT_ASSIGN_URL = "{{ route('reports.assign', ':id') }}";
        const REPORT_MESSAGE_URL = "{{ route('reports.message', ':id') }}";
        const REPORT_UPLOAD_URL = "{{ route('reports.upload', ':id') }}";
        const REPORT_REASSIGN_URL = "{{ route('reports.reassign', ':id') }}";
        const REPORT_RESOLVE_URL = "{{ route('reports.resolve', ':id') }}";
        const REPORT_REOPEN_URL = "{{ route('reports.reopen', ':id') }}";
        const REPORT_CLOSE_URL = "{{ route('reports.close', ':id') }}";
        window.CURRENT_REPORT_ID = "{{ $report->id }}";


        var statusClasses = {
        'new': '{{ getReportStatusClass('new') }}',
        'in progress': '{{ getReportStatusClass('in progress') }}',
        'assigned': '{{ getReportStatusClass('assigned') }}',
        'resolved': '{{ getReportStatusClass('resolved') }}',
        'closed': '{{ getReportStatusClass('closed') }}'
    };

    </script>

    <script src="{{ asset('assets/js/custom/reports.js') }}"></script>
    @endsection
@endsection
