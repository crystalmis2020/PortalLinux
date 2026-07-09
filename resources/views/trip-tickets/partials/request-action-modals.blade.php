@foreach ($tickets as $ticket)
    @php
        $ui = $ticketUi[$ticket->id] ?? [];
        $canEditRequest = (bool) ($ui['can_edit'] ?? false);
        $canEncodeDetails = (bool) ($ui['can_encode'] ?? false);
        $scheduledDrivers = $ui['scheduled_drivers'] ?? collect();
        $scheduledVehicles = $ui['scheduled_vehicles'] ?? collect();
        $availabilityUrl = $ui['availability_url'] ?? null;
    @endphp
    <div class="modal fade" id="tripTicketActionModal{{ $ticket->id }}" tabindex="-1" aria-labelledby="tripTicketActionModalLabel{{ $ticket->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="tripTicketActionModalLabel{{ $ticket->id }}">{{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</h5>
                        <div class="text-muted small">Submitted {{ $ticket->created_at?->format('M d, Y h:i A') }}</div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge {{ $statusClasses[$ticket->status] ?? 'bg-secondary' }}">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-lg-7">
                            <div class="trip-ticket-panel h-100">
                                <h6 class="trip-ticket-section-title mb-3">Request Details</h6>
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
                                        <div class="trip-ticket-detail-value">{{ $ticket->requested_start_datetime?->format('M d, Y') ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="trip-ticket-detail-label">Requested Return</div>
                                        <div class="trip-ticket-detail-value">{{ $ticket->requested_end_datetime?->format('M d, Y') ?? 'N/A' }}</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="trip-ticket-detail-label">Destination</div>
                                        <div class="trip-ticket-detail-value">{{ $ticket->destination ?: 'N/A' }}</div>
                                        @if ($ticket->distance_km !== null && (float) $ticket->distance_km > 0)
                                            <div class="trip-ticket-muted-line">{{ number_format($ticket->distance_km, 2) }} km one-way</div>
                                        @endif
                                    </div>
                                    <div class="col-12">
                                        <div class="trip-ticket-detail-label">Purpose</div>
                                        <div class="trip-ticket-detail-note">{{ $ticket->purpose ?: 'N/A' }}</div>
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
                        <div class="col-12 col-lg-5">
                            <div class="trip-ticket-panel h-100">
                                <h6 class="trip-ticket-section-title mb-3">Operational Details</h6>
                                @if ($canEncodeDetails)
                                    <form method="POST" action="{{ route('trip-tickets.encode', $ticket) }}" class="trip-ticket-encode-form" data-availability-url="{{ $availabilityUrl }}">
                                        @csrf
                                        <input type="hidden" name="_modal_id" value="tripTicketActionModal{{ $ticket->id }}">
                                        <div class="mb-3">
                                            <label for="vehicle_id_{{ $ticket->id }}" class="form-label">Vehicle</label>
                                            <select class="form-select trip-ticket-resource-select" id="vehicle_id_{{ $ticket->id }}" name="vehicle_id" required>
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
                                            <label for="driver_id_{{ $ticket->id }}" class="form-label">Driver</label>
                                            <select class="form-select trip-ticket-resource-select" id="driver_id_{{ $ticket->id }}" name="driver_id" required>
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
                                            <label for="actual_departure_datetime_{{ $ticket->id }}" class="form-label">Actual Departure</label>
                                            <input type="date" class="form-control trip-ticket-encode-start" id="actual_departure_datetime_{{ $ticket->id }}" name="actual_departure_datetime" value="{{ old('actual_departure_datetime', ($ticket->actual_departure_datetime ?: $ticket->requested_start_datetime)?->format('Y-m-d')) }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="actual_return_datetime_{{ $ticket->id }}" class="form-label">Actual Return</label>
                                            <input type="date" class="form-control trip-ticket-encode-end" id="actual_return_datetime_{{ $ticket->id }}" name="actual_return_datetime" value="{{ old('actual_return_datetime', ($ticket->actual_return_datetime ?: $ticket->requested_end_datetime)?->format('Y-m-d')) }}">
                                        </div>
                                        <div class="mb-3">
                                            <label for="encoder_remarks_{{ $ticket->id }}" class="form-label">Encoder Remarks</label>
                                            <textarea class="form-control" id="encoder_remarks_{{ $ticket->id }}" name="remarks" rows="3">{{ old('remarks', $ticket->remarks) }}</textarea>
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
                        <div class="col-12">
                            <div class="trip-ticket-panel">
                                <h6 class="trip-ticket-section-title mb-3">Activity</h6>
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
                <div class="modal-footer">
                    @if ($canEditRequest)
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#tripTicketEditModal{{ $ticket->id }}">
                            <i class="bx bx-edit me-1"></i>Edit Request
                        </button>
                    @endif
                    @if ($ticket->status === \App\Models\TripTicket::STATUS_APPROVED)
                        <a href="{{ route('trip-tickets.print', $ticket) }}" class="btn btn-outline-primary" target="_blank">
                            <i class="bx bx-printer me-1"></i>Print
                        </a>
                    @endif
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
