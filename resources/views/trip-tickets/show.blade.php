@extends('layout.app')

@section('css-custom')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap5.css') }}" rel="stylesheet" />
    <style>
        .trip-ticket-detail-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            box-shadow: none;
        }

        .trip-ticket-detail-card .card-header {
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
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
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .trip-ticket-resource-option .badge {
            flex-shrink: 0;
            font-size: 0.72rem;
        }

        .trip-ticket-activity-item {
            border-left: 3px solid #dbe3ef;
            padding: 0 0 14px 12px;
            margin-bottom: 14px;
        }

        .trip-ticket-activity-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .trip-ticket-detail-card .form-label {
            color: #334155;
            font-weight: 600;
        }

        .trip-ticket-detail-card .form-control,
        .trip-ticket-detail-card .form-select {
            border-color: #dbe3ef;
        }

        html.dark-theme .trip-ticket-detail-card {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
        }

        html.dark-theme .trip-ticket-detail-value,
        html.dark-theme .trip-ticket-detail-note {
            color: rgba(255, 255, 255, 0.86);
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
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.index') }}">Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">Please fix the following:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    @php
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

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card trip-ticket-detail-card">
                <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">{{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</h5>
                        <p class="text-muted small mb-0">Submitted {{ $ticket->created_at?->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($canEditRequest)
                            <a href="{{ route('trip-tickets.edit', $ticket) }}" class="btn btn-outline-secondary btn-sm">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                        @endif
                        @if ($ticket->status === \App\Models\TripTicket::STATUS_APPROVED)
                            <a href="{{ route('trip-tickets.print', $ticket) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="bx bx-printer me-1"></i>Print
                            </a>
                        @endif
                        <span class="badge {{ $statusClasses[$ticket->status] ?? 'bg-secondary' }} fs-6">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="trip-ticket-detail-label">Requester</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->requester?->full_name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="trip-ticket-detail-label">Department / Section</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->department?->name ?? 'N/A' }} / {{ $ticket->section?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="trip-ticket-detail-label">Requested Departure</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->requested_start_datetime?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="trip-ticket-detail-label">Requested Return</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->requested_end_datetime?->format('M d, Y') }}</div>
                        </div>
                        <div class="col-12 col-md-8">
                            <div class="trip-ticket-detail-label">Destination</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->destination }}</div>
                        </div>
                        @if ($ticket->distance_km !== null && (float) $ticket->distance_km > 0)
                            <div class="col-12 col-md-4">
                                <div class="trip-ticket-detail-label">Road KM from Maramag</div>
                                <div class="trip-ticket-detail-value">{{ number_format($ticket->distance_km, 2) }} km</div>
                            </div>
                        @endif
                        <div class="col-12">
                            <div class="trip-ticket-detail-label">Purpose</div>
                            <div class="trip-ticket-detail-note">{{ $ticket->purpose }}</div>
                        </div>
                        <div class="col-12">
                            <div class="trip-ticket-detail-label">Passengers / Personnel</div>
                            <div class="trip-ticket-detail-note">{{ $ticket->passengers ?: 'N/A' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="trip-ticket-detail-label">Contact Number</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->contact_number ?: 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="trip-ticket-detail-label">Remarks</div>
                            <div class="trip-ticket-detail-note">{{ $ticket->remarks ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card trip-ticket-detail-card">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Operational Details</h6>
                </div>
                <div class="card-body">
                    @if ($canEncodeDetails)
                        <form method="POST" action="{{ route('trip-tickets.encode', $ticket) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="vehicle_id" class="form-label">Vehicle</label>
                                <select class="form-select" id="vehicle_id" name="vehicle_id" required>
                                    <option value="">Select vehicle</option>
                                    @foreach ($dispatcherVehicles as $vehicle)
                                        @php
                                            $vehicleConflict = $scheduledVehicles->get($vehicle->id);
                                            $vehicleLabel = trim($vehicle->plate_number . ' - ' . $vehicle->description, ' -');
                                            $vehicleConflictLabel = data_get($vehicleConflict, 'label');
                                            $vehicleBlocked = (bool) data_get($vehicleConflict, 'blocked');
                                            $selectedVehicle = (string) old('vehicle_id', $ticket->vehicle_id) === (string) $vehicle->id;
                                        @endphp
                                        <option
                                            value="{{ $vehicle->id }}"
                                            data-base-label="{{ $vehicleLabel }}"
                                            data-conflict-label="{{ $vehicleConflictLabel }}"
                                            data-conflict-blocked="{{ $vehicleBlocked ? '1' : '0' }}"
                                            {{ $selectedVehicle ? 'selected' : '' }}
                                            {{ $vehicleBlocked ? 'disabled' : '' }}>
                                            {{ $vehicleConflictLabel ? $vehicleLabel . ' (' . $vehicleConflictLabel . ')' : $vehicleLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="driver_id" class="form-label">Driver</label>
                                <select class="form-select" id="driver_id" name="driver_id" required>
                                    <option value="">Select driver</option>
                                    @foreach ($dispatcherDrivers as $driver)
                                        @php
                                            $driverConflict = $scheduledDrivers->get($driver->id);
                                            $driverConflictLabel = data_get($driverConflict, 'label');
                                            $driverBlocked = (bool) data_get($driverConflict, 'blocked');
                                            $selectedDriver = (string) old('driver_id', $ticket->driver_id) === (string) $driver->id;
                                        @endphp
                                        <option
                                            value="{{ $driver->id }}"
                                            data-base-label="{{ $driver->name }}"
                                            data-conflict-label="{{ $driverConflictLabel }}"
                                            data-conflict-blocked="{{ $driverBlocked ? '1' : '0' }}"
                                            {{ $selectedDriver ? 'selected' : '' }}
                                            {{ $driverBlocked ? 'disabled' : '' }}>
                                            {{ $driverConflictLabel ? $driver->name . ' (' . $driverConflictLabel . ')' : $driver->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="actual_departure_datetime" class="form-label">Actual Departure</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="actual_departure_datetime"
                                    name="actual_departure_datetime"
                                    value="{{ old('actual_departure_datetime', ($ticket->actual_departure_datetime ?: $ticket->requested_start_datetime)?->format('Y-m-d')) }}">
                            </div>
                            <div class="mb-3">
                                <label for="actual_return_datetime" class="form-label">Actual Return</label>
                                <input
                                    type="date"
                                    class="form-control"
                                    id="actual_return_datetime"
                                    name="actual_return_datetime"
                                    value="{{ old('actual_return_datetime', ($ticket->actual_return_datetime ?: $ticket->requested_end_datetime)?->format('Y-m-d')) }}">
                            </div>
                            <div class="mb-3">
                                <label for="encoder_remarks" class="form-label">Encoder Remarks</label>
                                <textarea class="form-control" id="encoder_remarks" name="remarks" rows="3">{{ old('remarks', $ticket->remarks) }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bx bx-send me-1"></i>Submit For Approval
                            </button>
                        </form>
                    @else
                        <div class="mb-3">
                            <div class="trip-ticket-detail-label">Vehicle</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->vehicle_details ?: ($ticket->vehicle ? ($ticket->vehicle->plate_number . ' - ' . $ticket->vehicle->description) : 'Pending encoder') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="trip-ticket-detail-label">Driver</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->driver_name ?: ($ticket->driver?->name ?? 'Pending encoder') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="trip-ticket-detail-label">Actual Departure</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->actual_departure_datetime?->format('M d, Y') ?? 'Pending' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="trip-ticket-detail-label">Actual Return</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->actual_return_datetime?->format('M d, Y') ?? 'Pending' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="trip-ticket-detail-label">Encoded By</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->encoder?->full_name ?? 'Pending' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="trip-ticket-detail-label">Approved By</div>
                            <div class="trip-ticket-detail-value">{{ $ticket->approver?->full_name ?? 'Pending' }}</div>
                        </div>
                        <div>
                            <div class="trip-ticket-detail-label">Approval Remarks</div>
                            <div class="trip-ticket-detail-note">{{ $ticket->approval_remarks ?: 'N/A' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card trip-ticket-detail-card mt-3">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Activity</h6>
                </div>
                <div class="card-body">
                    @forelse ($ticket->logs->sortByDesc('created_at') as $log)
                        <div class="trip-ticket-activity-item">
                            <div class="fw-semibold">{{ str_replace('_', ' ', ucfirst($log->action)) }}</div>
                            <div class="text-muted small">{{ $log->created_at?->format('M d, Y h:i A') }} by {{ $log->user?->full_name ?? 'System' }}</div>
                            @if ($log->remarks)
                                <div class="small mt-1">{{ $log->remarks }}</div>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted mb-0">No activity yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js-custom')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    @if ($canEncodeDetails && $availabilityUrl)
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const availabilityUrl = @json($availabilityUrl);
                const departureInput = document.getElementById('actual_departure_datetime');
                const returnInput = document.getElementById('actual_return_datetime');
                const vehicleSelect = document.getElementById('vehicle_id');
                const driverSelect = document.getElementById('driver_id');

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
                        option.textContent = conflictLabel
                            ? baseLabel + ' (' + conflictLabel + ')'
                            : baseLabel;
                    });

                    if (select.selectedOptions.length && select.selectedOptions[0].disabled) {
                        select.value = '';
                    }

                    if (typeof $ !== 'undefined' && $.fn.select2) {
                        $(select).trigger('change.select2');
                    }
                }

                function refreshAvailability() {
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
                            if (!response.ok) {
                                return null;
                            }

                            return response.json();
                        })
                        .then(function (data) {
                            if (!data) {
                                return;
                            }

                            applyConflicts(vehicleSelect, data.vehicles || {});
                            applyConflicts(driverSelect, data.drivers || {});
                        });
                }

                initializeResourceSelect(vehicleSelect);
                initializeResourceSelect(driverSelect);

                [departureInput, returnInput].forEach(function (input) {
                    if (input) {
                        input.addEventListener('change', refreshAvailability);
                    }
                });
            });
        </script>
    @endif
@endsection
