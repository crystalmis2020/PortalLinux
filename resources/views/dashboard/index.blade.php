@extends('layout.app')

@section('css-custom')
<style>
    .support-dashboard {
        display: grid;
        gap: 18px;
    }

    .support-dashboard .card {
        border-radius: 8px;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: minmax(360px, 0.95fr) minmax(420px, 1.05fr);
        gap: 18px;
        align-items: start;
    }

    .dashboard-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .dashboard-card-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 0;
        color: var(--csci-brand-900);
        font-weight: 800;
    }

    .title-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: grid;
        place-items: center;
        color: #fff;
        background: linear-gradient(135deg, var(--csci-brand-700), var(--csci-brand-900));
        font-size: 20px;
    }

    .fixed-context {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-bottom: 14px;
    }

    .context-field {
        border: 1px solid rgba(0, 73, 30, 0.12);
        border-radius: 8px;
        background: rgba(2, 104, 30, 0.06);
        padding: 12px;
        min-height: 74px;
    }

    .context-label,
    .form-label {
        color: var(--csci-text-muted);
        font-size: 0.78rem;
        font-weight: 800;
        text-transform: uppercase;
    }

    .context-value {
        margin-top: 6px;
        color: var(--csci-brand-900);
        font-weight: 800;
        overflow-wrap: anywhere;
    }

    .form-control,
    .form-select {
        border-radius: 8px;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--csci-brand-700);
        box-shadow: 0 0 0 0.2rem rgba(2, 104, 30, 0.12);
    }

    .file-picker-shell {
        border: 1px dashed rgba(0, 73, 30, 0.28);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.55);
        padding: 12px;
    }

    .attachment-meta {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 10px;
        padding: 10px 12px;
        border-radius: 8px;
        background: rgba(145, 159, 2, 0.1);
        color: var(--csci-brand-900);
        font-weight: 700;
    }

    .attachment-meta.is-visible {
        display: flex;
    }

    .btn-submit-report {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .previous-report-shell {
        max-height: 604px;
        overflow: auto;
    }

    .previous-report-table {
        min-width: 540px;
    }

    .previous-report-table td,
    .previous-report-table th {
        vertical-align: middle;
    }

    .report-link {
        color: inherit;
        text-decoration: none;
    }

    .report-link:hover {
        color: inherit;
        text-decoration: underline;
    }

    .status-badge {
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 94px;
        min-height: 30px;
        padding: 6px 10px;
        font-weight: 800;
    }

    .empty-reports {
        padding: 34px 16px;
        text-align: center;
        color: var(--csci-text-muted);
    }

    .empty-reports i {
        display: block;
        color: var(--csci-brand-700);
        font-size: 34px;
        margin-bottom: 8px;
    }

    html.dark-theme .context-field,
    html.dark-theme .file-picker-shell {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
    }

    html.dark-theme #section_id.form-select,
    html.dark-theme #issue_id.form-select,
    html.dark-theme #contact_number.form-control,
    html.dark-theme #issue.form-control {
        color: #f5f9f2;
        background-color: #1a2326;
        border-color: rgba(255, 255, 255, 0.14);
    }

    html.dark-theme #section_id.form-select option,
    html.dark-theme #issue_id.form-select option {
        color: #f5f9f2;
        background-color: #1a2326;
    }

    html.dark-theme .context-value,
    html.dark-theme .dashboard-card-title,
    html.dark-theme .attachment-meta {
        color: #f5f9f2;
    }

    @media (max-width: 1199.98px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .fixed-context {
            grid-template-columns: 1fr;
        }

        .dashboard-card-header {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endsection

@section('content')
@php
    $totalReports = $previousReports->count();
    $resolvedReportCount = $resolvedNotifications->count();
@endphp

<div class="support-dashboard">
    <div class="dashboard-grid">
        <div class="card w-100">
            <div class="card-header">
                <div class="dashboard-card-header">
                    <h6 class="dashboard-card-title">
                        <span class="title-icon"><i class="bx bx-send"></i></span>
                        Send Report
                    </h6>
                </div>
            </div>
            <div class="card-body">
                <div class="fixed-context">
                    <div class="context-field">
                        <div class="context-label">Department</div>
                        <div class="context-value">{{ $user->department->name ?? 'N/A' }}</div>
                    </div>
                    <div class="context-field">
                        <div class="context-label">Section</div>
                        <div class="context-value">{{ $user->section->name ?? 'N/A' }}</div>
                    </div>
                </div>

                <form id="reportSubmitForm" action="javascript:void(0);" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label for="section_id" class="form-label">Section</label>
                        <select class="form-select" id="section_id" name="section_id" required>
                            @foreach($sections as $section)
                                <option
                                    value="{{ $section->id }}"
                                    {{ (int) $section->id === (int) $user->section_id ? 'selected' : '' }}>
                                    {{ $section->name }}{{ $section->department ? ' - ' . $section->department->name : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="issue_id" class="form-label">Issue Category</label>
                        <select class="form-select" id="issue_id" name="issue_id" required>
                            <option value="" selected disabled>Select Issue Category</option>
                            @foreach($issues as $issue)
                                <option value="{{ $issue->id }}">{{ $issue->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="issue" class="form-label">Brief Description</label>
                        <textarea class="form-control" id="issue" name="issue" rows="5" maxlength="2000" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="contact_number" class="form-label">Contact</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number">
                    </div>

                    <div class="mb-4">
                        <label for="attachment" class="form-label">Attached File</label>
                        <div class="file-picker-shell">
                            <input type="file" class="form-control" id="attachment" name="attachment" onchange="previewAttachment(this)">
                            <small class="text-muted d-block mt-2">jpg, jpeg, png, pdf, doc, docx, xls, xlsx, txt up to 20MB</small>
                            <div class="attachment-meta" id="attachmentMeta">
                                <span id="attachmentName"></span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAttachment" aria-label="Clear attached file">
                                    <i class="bx bx-x"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-submit-report">
                        <i class="bx bx-send"></i>
                        <span>Submit Report</span>
                    </button>
                </form>
            </div>
        </div>

        <div class="card w-100">
            <div class="card-header">
                <div class="dashboard-card-header">
                    <h6 class="dashboard-card-title">
                        <span class="title-icon"><i class="bx bx-history"></i></span>
                        Previous Reports
                    </h6>
                    <span class="badge bg-light text-dark"><span id="previousReportsTotal">{{ $totalReports }}</span> total</span>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive previous-report-shell">
                    <table class="table align-middle mb-0 previous-report-table">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($previousReports as $report)
                                <tr>
                                    <td>
                                        <a class="report-link" href="{{ route('reports.details', $report->id) }}">
                                            {{ $report->created_at->format('M d, Y') }}
                                        </a>
                                    </td>
                                    <td>{{ $report->issueCategory->name ?? 'N/A' }}</td>
                                    <td>
                                        <a class="status-badge {{ getReportStatusClass($report->status) }} report-link" href="{{ route('reports.details', $report->id) }}">
                                            {{ strtoupper($report->status) }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr class="empty-report-row">
                                    <td colspan="3">
                                        <div class="empty-reports">
                                            <i class="bx bx-folder-open"></i>
                                            <span>No reports found</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
               </div>
            </div>
        </div>
    </div>
</div>

@if($resolvedReportCount > 0)
<div class="modal fade" id="resolvedReportsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Resolved Issue{{ $resolvedReportCount > 1 ? 's' : '' }} Ready for Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">
                    {{ $resolvedReportCount > 1 ? 'These reports were marked as resolved.' : 'This report was marked as resolved.' }}
                    Please review {{ $resolvedReportCount > 1 ? 'them' : 'it' }} and close {{ $resolvedReportCount > 1 ? 'them' : 'it' }} if the issue is already fixed.
                </p>
                <div class="list-group" id="resolvedReportsList">
                    @foreach($resolvedNotifications as $notification)
                        @php
                            $resolvedReport = $notification->report;
                        @endphp
                        <div class="list-group-item resolved-report-item" data-report-id="{{ $resolvedReport->id }}" data-notification-id="{{ $notification->id }}">
                            <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
                                <div>
                                    <h6 class="mb-1">Report #{{ $resolvedReport->id }}: {{ $resolvedReport->issueCategory->name ?? 'Issue' }}</h6>
                                    <p class="mb-1 text-muted">{{ \Illuminate\Support\Str::limit($resolvedReport->issue ?? $notification->message, 140) }}</p>
                                    <small class="text-muted">
                                        Resolved by {{ $notification->fromUser?->full_name ?? 'MIS' }}
                                        on {{ optional($notification->created_at)->format('M d, Y h:i A') }}
                                    </small>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <a
                                        href="{{ route('reports.details', ['report' => $resolvedReport->id, 'notification_id' => $notification->id]) }}"
                                        class="btn btn-outline-primary btn-sm">
                                        View Report
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-success btn-sm close-resolved-report-button"
                                        data-report-id="{{ $resolvedReport->id }}">
                                        Close Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Later</button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@section('js-custom')
    <script src="{{ asset('assets/plugins/validation/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/validation/validation-script.js') }}"></script>

    <script>
        function previewAttachment(input) {
            const file = input.files && input.files[0];
            const meta = document.getElementById('attachmentMeta');
            const name = document.getElementById('attachmentName');

            if (!meta || !name) {
                return;
            }

            if (!file) {
                meta.classList.remove('is-visible');
                name.textContent = '';
                return;
            }

            const fileSize = file.size >= 1048576
                ? `${(file.size / 1048576).toFixed(1)} MB`
                : `${Math.max(1, Math.round(file.size / 1024))} KB`;

            name.textContent = `${file.name} (${fileSize})`;
            meta.classList.add('is-visible');
        }

        function notifyUser(type, message) {
            Lobibox.notify(type, {
                size: 'mini',
                rounded: true,
                sound: false,
                delay: 3500,
                position: 'top right',
                msg: message
            });
        }

        function setSubmitLoading(isLoading) {
            const button = $('.btn-submit-report');
            button.prop('disabled', isLoading);
            button.html(isLoading
                ? '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Submitting</span>'
                : '<i class="bx bx-send"></i><span>Submit Report</span>'
            );
        }

        function getReportStatusClass(status) {
            switch (status.toLowerCase()) {
                case 'new': return 'bg-primary';
                case 'in progress': return 'bg-warning';
                case 'in_progress': return 'bg-warning';
                case 'resolved': return 'bg-success';
                case 'assigned': return 'bg-info';
                case 'closed': return 'bg-secondary';
                default: return 'bg-light text-dark';
            }
        }

        function incrementCounter(selector) {
            const counter = $(selector);
            const current = parseInt(counter.text(), 10);
            counter.text(Number.isNaN(current) ? 1 : current + 1);
        }

        $(document).ready(function () {
            const resolvedReportsModalElement = document.getElementById('resolvedReportsModal');
            const resolvedReportsModal = resolvedReportsModalElement ? new bootstrap.Modal(resolvedReportsModalElement) : null;

            $('#clearAttachment').on('click', function () {
                $('#attachment').val('');
                previewAttachment(document.getElementById('attachment'));
            });

            $(document).on('click', '.close-resolved-report-button', function () {
                const button = $(this);
                const reportId = button.data('report-id');
                const row = button.closest('.resolved-report-item');

                button.prop('disabled', true).text('Closing...');

                $.ajax({
                    url: "{{ route('reports.close', ':id') }}".replace(':id', reportId),
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        message: 'Closed from resolved issue prompt.'
                    },
                    success: function (response) {
                        notifyUser('success', response.success || 'Report closed successfully.');
                        row.remove();

                        const matchingStatusLink = $('.previous-report-table a.status-badge[href$="/reports/details/' + reportId + '"]');
                        matchingStatusLink
                            .removeClass('bg-primary bg-warning bg-success bg-info bg-secondary bg-light text-dark')
                            .addClass('bg-secondary')
                            .text('CLOSED');

                        if (!$('#resolvedReportsList .resolved-report-item').length && resolvedReportsModal) {
                            resolvedReportsModal.hide();
                        }
                    },
                    error: function (xhr) {
                        const response = xhr.responseJSON;
                        notifyUser('error', (response && (response.error || response.message)) ? (response.error || response.message) : 'Unable to close the report.');
                        button.prop('disabled', false).text('Close Report');
                    }
                });
            });

            $('#reportSubmitForm').submit(function (e) {
                e.preventDefault();

                if (resolvedReportsModal && $('#resolvedReportsList .resolved-report-item').length > 0) {
                    resolvedReportsModal.show();
                    notifyUser('warning', 'You still have resolved report(s) waiting to be reviewed or closed.');
                    return;
                }

                setSubmitLoading(true);

                const formData = new FormData(this);
                const url = "{{ route('dashboard.reports.save') }}";

                $.ajax({
                    url: url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            notifyUser('success', response.message);

                            const createdAt = new Date(response.report.created_at);
                            const formattedDate = createdAt.toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'short',
                                day: '2-digit'
                            });

                            const issueSelected = $('#issue_id option:selected').text();
                            const statusClass = getReportStatusClass(response.report.status);
                            const statusText = response.report.status.toUpperCase();

                            const newRow = $('<tr>');
                            const dateLink = $('<a>', {
                                class: 'report-link',
                                href: response.details_url,
                                text: formattedDate
                            });
                            const statusLink = $('<a>', {
                                class: `status-badge ${statusClass} report-link`,
                                href: response.details_url,
                                text: statusText
                            });

                            newRow
                                .append($('<td>').append(dateLink))
                                .append($('<td>').text(issueSelected || 'N/A'))
                                .append($('<td>').append(statusLink));

                            $('.previous-report-table tbody .empty-report-row').remove();
                            $('.previous-report-table tbody').prepend(newRow);
                            incrementCounter('#previousReportsTotal');
                            $('#reportSubmitForm')[0].reset();
                            previewAttachment(document.getElementById('attachment'));
                            setSubmitLoading(false);
                            return;
                        }

                        notifyUser('error', response.message || 'Unable to submit report.');
                        setSubmitLoading(false);
                    },
                    error: function (xhr) {
                        const errors = xhr.responseJSON && xhr.responseJSON.errors;

                        if (errors) {
                            $.each(errors, function (key, value) {
                                notifyUser('error', value[0]);
                            });
                        } else {
                            notifyUser('error', 'Something went wrong while submitting the report.');
                        }

                        setSubmitLoading(false);
                    }
                });
            });
        });
    </script>
@endsection
