@extends('layout.app')

@section('css-custom')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap5.css') }}" rel="stylesheet" />
    <style>
        .trip-ticket-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            box-shadow: none;
        }

        .trip-ticket-toolbar {
            background: #f8fafc;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            padding: 12px;
        }

        .trip-ticket-table-wrap {
            overflow-x: hidden;
        }

        .trip-ticket-table {
            table-layout: fixed;
            width: 100%;
            margin-bottom: 0;
        }

        .trip-ticket-table thead th {
            color: #64748b;
            font-size: 0.72rem;
            letter-spacing: 0;
            text-transform: uppercase;
            white-space: normal;
        }

        .trip-ticket-table th,
        .trip-ticket-table td {
            vertical-align: middle;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .trip-ticket-table tbody tr:hover {
            background: #f8fafc;
        }

        .trip-ticket-table th:nth-child(1) { width: 17%; }
        .trip-ticket-table th:nth-child(2) { width: 27%; }
        .trip-ticket-table th:nth-child(3) { width: 18%; }
        .trip-ticket-table th:nth-child(4) { width: 20%; }
        .trip-ticket-table th:nth-child(5) { width: 12%; }
        .trip-ticket-table th:nth-child(6) { width: 90px; }
        .trip-ticket-user-table th:nth-child(1) { width: 24%; }
        .trip-ticket-user-table th:nth-child(2) { width: 56%; }
        .trip-ticket-user-table th:nth-child(3) { width: 20%; }

        .trip-ticket-table td:last-child {
            white-space: nowrap;
            overflow-wrap: normal;
            word-break: normal;
        }

        .trip-ticket-primary-text {
            color: #0f172a;
            font-weight: 600;
        }

        .trip-ticket-muted-line {
            color: #64748b;
            font-size: 0.78rem;
        }

        .trip-ticket-empty {
            border: 1px dashed rgba(100, 116, 139, 0.35);
            border-radius: 8px;
            padding: 28px 16px;
            text-align: center;
        }

        .trip-ticket-form .form-label,
        .trip-ticket-panel .form-label {
            color: #334155;
            font-weight: 600;
        }

        .trip-ticket-form .form-control,
        .trip-ticket-form .form-select,
        .trip-ticket-panel .form-control,
        .trip-ticket-panel .form-select {
            border-color: #dbe3ef;
        }

        .trip-ticket-form .input-group-text {
            background-color: #f8fafc;
            border-color: #dbe3ef;
            color: #475569;
            font-weight: 600;
        }

        .trip-ticket-section-title {
            align-items: center;
            color: #64748b;
            display: flex;
            font-size: 0.74rem;
            font-weight: 700;
            gap: 8px;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .trip-ticket-section-title::before {
            background: #0d6efd;
            border-radius: 999px;
            content: '';
            height: 8px;
            width: 8px;
        }

        .trip-ticket-panel {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            padding: 16px;
        }

        .trip-ticket-detail-label {
            color: #64748b;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .trip-ticket-detail-value {
            color: #0f172a;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .trip-ticket-detail-note {
            color: #334155;
            overflow-wrap: anywhere;
            white-space: pre-line;
        }

        .trip-ticket-resource-option {
            align-items: center;
            display: flex;
            gap: 8px;
            justify-content: space-between;
        }

        .trip-ticket-resource-option .badge {
            flex-shrink: 0;
            font-size: 0.72rem;
        }

        .trip-ticket-activity-item {
            border-left: 3px solid #dbe3ef;
            margin-bottom: 14px;
            padding: 0 0 14px 12px;
        }

        .trip-ticket-activity-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        html.dark-theme .trip-ticket-card,
        html.dark-theme .trip-ticket-toolbar,
        html.dark-theme .trip-ticket-panel {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme .trip-ticket-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.04);
        }

        html.dark-theme .trip-ticket-table thead th,
        html.dark-theme .trip-ticket-muted-line,
        html.dark-theme .trip-ticket-detail-label,
        html.dark-theme .trip-ticket-section-title {
            color: rgba(255, 255, 255, 0.68);
        }

        html.dark-theme .trip-ticket-primary-text,
        html.dark-theme .trip-ticket-detail-value,
        html.dark-theme .trip-ticket-detail-note,
        html.dark-theme .trip-ticket-table td {
            color: rgba(255, 255, 255, 0.88);
        }

        html.dark-theme .trip-ticket-form .form-label,
        html.dark-theme .trip-ticket-panel .form-label {
            color: rgba(255, 255, 255, 0.82);
        }

        html.dark-theme .trip-ticket-form .form-control,
        html.dark-theme .trip-ticket-form .form-select,
        html.dark-theme .trip-ticket-panel .form-control,
        html.dark-theme .trip-ticket-panel .form-select {
            background-color: rgba(15, 23, 42, 0.45);
            border-color: rgba(255, 255, 255, 0.14);
            color: rgba(255, 255, 255, 0.88);
        }

        html.dark-theme .trip-ticket-form .input-group-text {
            background-color: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.14);
            color: rgba(255, 255, 255, 0.78);
        }

        html.dark-theme .trip-ticket-panel,
        html.dark-theme .trip-ticket-empty {
            border-color: rgba(255, 255, 255, 0.12);
        }
    </style>
@endsection

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Trip Tickets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Requests</li>
                </ol>
            </nav>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif


    @php
        $isDispatcher = $isDispatcher ?? false;
        $statusClasses = [
            \App\Models\TripTicket::STATUS_PENDING_DETAILS => 'bg-warning text-dark',
            \App\Models\TripTicket::STATUS_FOR_APPROVAL => 'bg-info text-dark',
            \App\Models\TripTicket::STATUS_APPROVED => 'bg-success',
            \App\Models\TripTicket::STATUS_REJECTED => 'bg-danger',
            \App\Models\TripTicket::STATUS_RETURNED => 'bg-primary',
            \App\Models\TripTicket::STATUS_DISPATCHED => 'bg-dark',
            \App\Models\TripTicket::STATUS_COMPLETED => 'bg-success',
            \App\Models\TripTicket::STATUS_CANCELLED => 'bg-secondary',
        ];
    @endphp

    <div class="card trip-ticket-card">
        <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-1">Trip Ticket Requests</h5>
                <p class="text-muted small mb-0">Submit and track travel requests.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @if (auth()->user()?->canEncodeTripTickets())
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#driverCrudModal">
                        <i class="bx bx-user me-1"></i>Manage Drivers
                    </button>
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#vehicleCrudModal">
                        <i class="bx bx-car me-1"></i>Manage Vehicles
                    </button>
                @endif
                <a href="{{ route('trip-tickets.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>New Request
                </a>
            </div>
        </div>
        <div class="card-body">
            <form class="trip-ticket-toolbar row g-2 align-items-end mb-3" method="GET" action="{{ route('trip-tickets.index') }}">
                <div class="col-12 col-md-5 col-lg-4">
                    <label for="status" class="form-label fw-semibold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bx bx-filter-alt me-1"></i>Filter
                    </button>
                    @if ($selectedStatus)
                        <a href="{{ route('trip-tickets.index') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-x me-1"></i>Clear
                        </a>
                    @endif
                </div>
            </form>

            @if ($tickets->count())
                <div class="trip-ticket-table-wrap">
                    <table class="table align-middle trip-ticket-table {{ $isDispatcher ? '' : 'trip-ticket-user-table' }}">
                        <thead class="table-light">
                            <tr>
                                <th>Schedule</th>
                                <th>Destination</th>
                                @if ($isDispatcher)
                                    <th>Requester</th>
                                    <th>Department</th>
                                @endif
                                <th>Status</th>
                                @if ($isDispatcher)
                                    <th></th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td>
                                        <div class="trip-ticket-primary-text">{{ $ticket->requested_start_datetime?->format('M d, Y') ?? 'No date' }}</div>
                                        <div class="trip-ticket-muted-line">Return: {{ $ticket->requested_end_datetime?->format('M d, Y') ?? 'No date' }}</div>
                                    </td>
                                    <td>
                                        <div class="trip-ticket-primary-text">{{ $ticket->destination ?: 'No destination' }}</div>
                                        @if ($ticket->distance_km !== null && (float) $ticket->distance_km > 0)
                                            <div class="trip-ticket-muted-line">{{ number_format($ticket->distance_km, 2) }} km one-way</div>
                                        @endif
                                    </td>
                                    @if ($isDispatcher)
                                        <td>{{ $ticket->requester?->full_name ?? 'N/A' }}</td>
                                        <td>
                                            <div>{{ $ticket->department?->name ?? 'N/A' }}</div>
                                            <div class="trip-ticket-muted-line">{{ $ticket->section?->name ?? 'N/A' }}</div>
                                        </td>
                                    @endif
                                    <td>
                                        <span class="badge {{ $statusClasses[$ticket->status] ?? 'bg-secondary' }}">
                                            {{ str_replace('_', ' ', ucfirst($ticket->status)) }}
                                        </span>
                                    </td>
                                    @if ($isDispatcher)
                                        <td class="text-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#tripTicketActionModal{{ $ticket->id }}" title="View trip ticket">
                                                View
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="trip-ticket-empty">
                    <div class="mb-2"><i class="bx bx-clipboard fs-2 text-muted"></i></div>
                    <div class="fw-semibold">No trip ticket requests found.</div>
                    @if ($selectedStatus)
                        <a href="{{ route('trip-tickets.index') }}" class="btn btn-outline-secondary btn-sm mt-3">Clear Filter</a>
                    @else
                        <a href="{{ route('trip-tickets.create') }}" class="btn btn-primary btn-sm mt-3">New Request</a>
                    @endif
                </div>
            @endif

            {{ $tickets->links() }}
        </div>
    </div>

    @if ($isDispatcher)
        @include('trip-tickets.partials.edit-request-modals')
        @include('trip-tickets.partials.request-action-modals')
    @endif

    @if ($isDispatcher)
        @include('trip-tickets.partials.dispatcher-crud-modals')
    @endif
@endsection

@section('js-custom')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function conflictBadgeClass(conflictLabel, isBlocked) {
                if (!conflictLabel) {
                    return '';
                }

                return isBlocked ? 'bg-secondary' : 'bg-warning text-dark';
            }

            function formatResourceOption(option) {
                if (!option.id) {
                    return option.text;
                }

                const element = option.element;
                const baseLabel = element.dataset.baseLabel || option.text;
                const conflictLabel = element.dataset.conflictLabel;
                const isBlocked = element.dataset.conflictBlocked === '1';

                if (!conflictLabel) {
                    return $('<span></span>').text(baseLabel);
                }

                const $option = $('<span class="trip-ticket-resource-option"></span>');
                $('<span></span>').text(baseLabel).appendTo($option);
                $('<span></span>')
                    .addClass('badge rounded-pill ' + conflictBadgeClass(conflictLabel, isBlocked))
                    .text(conflictLabel)
                    .appendTo($option);

                return $option;
            }

            function initializeResourceSelect(select) {
                if (!select || typeof $ === 'undefined' || !$.fn.select2) {
                    return;
                }

                $(select).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    dropdownParent: $(select).closest('.modal'),
                    templateResult: formatResourceOption,
                    templateSelection: formatResourceOption,
                });
            }

            function applyConflicts(select, conflicts) {
                if (!select) {
                    return;
                }

                Array.from(select.options).forEach(function (option) {
                    if (!option.value) {
                        return;
                    }

                    const baseLabel = option.dataset.baseLabel || option.textContent;
                    const conflict = conflicts[option.value];
                    const conflictLabel = conflict && conflict.label;
                    const isBlocked = Boolean(conflict && conflict.blocked);

                    option.disabled = isBlocked;
                    option.dataset.conflictLabel = conflictLabel || '';
                    option.dataset.conflictBlocked = isBlocked ? '1' : '0';
                    option.textContent = conflictLabel ? baseLabel + ' (' + conflictLabel + ')' : baseLabel;
                });

                if (select.selectedOptions.length && select.selectedOptions[0].disabled) {
                    select.value = '';
                }

                if (typeof $ !== 'undefined' && $.fn.select2) {
                    $(select).trigger('change.select2');
                }
            }

            const modalToReopen = @json($errors->any() ? old('_modal_id') : null);
            if (modalToReopen) {
                const modalElement = document.getElementById(modalToReopen);
                if (modalElement && typeof bootstrap !== 'undefined') {
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            }

            document.querySelectorAll('.trip-ticket-encode-form').forEach(function (form) {
                const availabilityUrl = form.dataset.availabilityUrl;
                const departureInput = form.querySelector('.trip-ticket-encode-start');
                const returnInput = form.querySelector('.trip-ticket-encode-end');
                const selects = form.querySelectorAll('.trip-ticket-resource-select');
                const vehicleSelect = selects[0];
                const driverSelect = selects[1];

                selects.forEach(initializeResourceSelect);

                function refreshAvailability() {
                    if (!availabilityUrl || !departureInput || !returnInput) {
                        return;
                    }

                    const params = new URLSearchParams({
                        actual_departure_datetime: departureInput.value,
                        actual_return_datetime: returnInput.value,
                    });

                    fetch(availabilityUrl + '?' + params.toString(), {
                        headers: {
                            'Accept': 'application/json',
                        },
                    })
                        .then(function (response) {
                            return response.ok ? response.json() : null;
                        })
                        .then(function (data) {
                            if (!data) {
                                return;
                            }

                            applyConflicts(vehicleSelect, data.vehicles || {});
                            applyConflicts(driverSelect, data.drivers || {});
                        });
                }

                [departureInput, returnInput].forEach(function (input) {
                    if (input) {
                        input.addEventListener('change', refreshAvailability);
                    }
                });
            });
        });
    </script>
@endsection
