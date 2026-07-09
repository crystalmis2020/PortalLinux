@foreach ($tickets as $ticket)
    @php
        $ui = $ticketUi[$ticket->id] ?? [];
        $canEditRequest = (bool) ($ui['can_edit'] ?? false);
        $isLocalDestination = ! $ticket->trip_ticket_location_id;
        $localDestination = $isLocalDestination ? preg_replace('/,\s*Maramag,\s*Bukidnon,\s*Philippines$/i', '', $ticket->destination ?? '') : '';
    @endphp
    @if ($canEditRequest)
        <div class="modal fade" id="tripTicketEditModal{{ $ticket->id }}" tabindex="-1" aria-labelledby="tripTicketEditModalLabel{{ $ticket->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST" action="{{ route('trip-tickets.update', $ticket) }}" class="trip-ticket-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_modal_id" value="tripTicketEditModal{{ $ticket->id }}">
                        <input type="hidden" name="destination_mode" value="{{ $isLocalDestination ? 'local_maramag' : 'mindanao' }}">
                        <input type="hidden" name="local_destination" value="{{ $localDestination }}">
                        <input type="hidden" name="destination_region" value="{{ $ticket->location?->region_name }}">
                        <input type="hidden" name="destination_province" value="{{ $ticket->location?->province_name }}">
                        <input type="hidden" name="destination_city" value="{{ $ticket->location?->city_municipality_name }}">
                        <input type="hidden" name="trip_ticket_location_id" value="{{ $ticket->trip_ticket_location_id }}">
                        <input type="hidden" name="destination" value="{{ $ticket->destination }}">
                        <input type="hidden" name="distance_km" value="{{ $ticket->distance_km ?? 0 }}">

                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title" id="tripTicketEditModalLabel{{ $ticket->id }}">Edit {{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</h5>
                                <div class="text-muted small">Quick edit keeps the selected destination unchanged.</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="trip-ticket-section-title mb-0">Schedule</h6>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="edit_requested_start_datetime_{{ $ticket->id }}" class="form-label">Requested Departure</label>
                                    <input type="date" class="form-control" id="edit_requested_start_datetime_{{ $ticket->id }}" name="requested_start_datetime" value="{{ old('requested_start_datetime', $ticket->requested_start_datetime?->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="edit_requested_end_datetime_{{ $ticket->id }}" class="form-label">Requested Return</label>
                                    <input type="date" class="form-control" id="edit_requested_end_datetime_{{ $ticket->id }}" name="requested_end_datetime" value="{{ old('requested_end_datetime', $ticket->requested_end_datetime?->format('Y-m-d')) }}" required>
                                </div>
                                <div class="col-12">
                                    <div class="trip-ticket-detail-label">Destination</div>
                                    <div class="trip-ticket-detail-value">{{ $ticket->destination }}</div>
                                    <div class="trip-ticket-muted-line">Use the full edit page only if the destination needs to change.</div>
                                </div>
                                <div class="col-12">
                                    <div class="border-top pt-3 mt-1">
                                        <h6 class="trip-ticket-section-title mb-0">Trip Details</h6>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label for="edit_purpose_{{ $ticket->id }}" class="form-label">Purpose</label>
                                    <textarea class="form-control" id="edit_purpose_{{ $ticket->id }}" name="purpose" rows="3" required>{{ old('purpose', $ticket->purpose) }}</textarea>
                                </div>
                                <div class="col-12">
                                    <label for="edit_passengers_{{ $ticket->id }}" class="form-label">Passengers / Personnel</label>
                                    <textarea class="form-control" id="edit_passengers_{{ $ticket->id }}" name="passengers" rows="3">{{ old('passengers', $ticket->passengers) }}</textarea>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="edit_contact_number_{{ $ticket->id }}" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="edit_contact_number_{{ $ticket->id }}" name="contact_number" value="{{ old('contact_number', $ticket->contact_number) }}">
                                </div>
                                <div class="col-12">
                                    <label for="edit_remarks_{{ $ticket->id }}" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="edit_remarks_{{ $ticket->id }}" name="remarks" rows="3">{{ old('remarks', $ticket->remarks) }}</textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="{{ route('trip-tickets.edit', $ticket) }}" class="btn btn-outline-secondary">
                                <i class="bx bx-map me-1"></i>Change Destination
                            </a>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endforeach
