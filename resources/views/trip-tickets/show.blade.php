@extends('layout.app')

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

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <div class="card" style="border-radius: 8px;">
                <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div>
                        <h5 class="mb-1">{{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</h5>
                        <p class="text-muted small mb-0">Submitted {{ $ticket->created_at?->format('M d, Y h:i A') }}</p>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($ticket->status === \App\Models\TripTicket::STATUS_APPROVED)
                            <a href="{{ route('trip-tickets.print', $ticket) }}" class="btn btn-outline-primary btn-sm" target="_blank">
                                <i class="bx bx-printer me-1"></i>Print
                            </a>
                        @endif
                        <span class="badge bg-secondary fs-6">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Requester</div>
                            <div class="fw-semibold">{{ $ticket->requester?->full_name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Department / Section</div>
                            <div class="fw-semibold">{{ $ticket->department?->name ?? 'N/A' }} / {{ $ticket->section?->name ?? 'N/A' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Requested Departure</div>
                            <div class="fw-semibold">{{ $ticket->requested_start_datetime?->format('M d, Y h:i A') }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Requested Return</div>
                            <div class="fw-semibold">{{ $ticket->requested_end_datetime?->format('M d, Y h:i A') }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Destination</div>
                            <div class="fw-semibold">{{ $ticket->destination }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Purpose</div>
                            <div>{{ $ticket->purpose }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Passengers / Personnel</div>
                            <div>{{ $ticket->passengers ?: 'N/A' }}</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="text-muted small">Contact Number</div>
                            <div>{{ $ticket->contact_number ?: 'N/A' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-muted small">Remarks</div>
                            <div>{{ $ticket->remarks ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card" style="border-radius: 8px;">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Operational Details</h6>
                </div>
                <div class="card-body">
                    @if ($canEncodeDetails)
                        <form method="POST" action="{{ route('trip-tickets.encode', $ticket) }}">
                            @csrf

                            <div class="mb-3">
                                <label for="ticket_number" class="form-label">Ticket Number</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="ticket_number"
                                    name="ticket_number"
                                    value="{{ old('ticket_number', $ticket->ticket_number) }}"
                                    placeholder="Auto-generated if blank">
                            </div>
                            <div class="mb-3">
                                <label for="vehicle_details" class="form-label">Vehicle</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="vehicle_details"
                                    name="vehicle_details"
                                    value="{{ old('vehicle_details', $ticket->vehicle_details ?: ($ticket->vehicle ? ($ticket->vehicle->plate_number . ' - ' . $ticket->vehicle->description) : '')) }}"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="driver_name" class="form-label">Driver</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="driver_name"
                                    name="driver_name"
                                    value="{{ old('driver_name', $ticket->driver_name ?: $ticket->driver?->name) }}"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label for="actual_departure_datetime" class="form-label">Actual Departure</label>
                                <input
                                    type="datetime-local"
                                    class="form-control"
                                    id="actual_departure_datetime"
                                    name="actual_departure_datetime"
                                    value="{{ old('actual_departure_datetime', $ticket->actual_departure_datetime?->format('Y-m-d\TH:i')) }}">
                            </div>
                            <div class="mb-3">
                                <label for="actual_return_datetime" class="form-label">Actual Return</label>
                                <input
                                    type="datetime-local"
                                    class="form-control"
                                    id="actual_return_datetime"
                                    name="actual_return_datetime"
                                    value="{{ old('actual_return_datetime', $ticket->actual_return_datetime?->format('Y-m-d\TH:i')) }}">
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
                            <div class="text-muted small">Vehicle</div>
                            <div class="fw-semibold">{{ $ticket->vehicle_details ?: ($ticket->vehicle ? ($ticket->vehicle->plate_number . ' - ' . $ticket->vehicle->description) : 'Pending encoder') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Driver</div>
                            <div class="fw-semibold">{{ $ticket->driver_name ?: ($ticket->driver?->name ?? 'Pending encoder') }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Actual Departure</div>
                            <div class="fw-semibold">{{ $ticket->actual_departure_datetime?->format('M d, Y h:i A') ?? 'Pending' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Actual Return</div>
                            <div class="fw-semibold">{{ $ticket->actual_return_datetime?->format('M d, Y h:i A') ?? 'Pending' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Encoded By</div>
                            <div class="fw-semibold">{{ $ticket->encoder?->full_name ?? 'Pending' }}</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Approved By</div>
                            <div class="fw-semibold">{{ $ticket->approver?->full_name ?? 'Pending' }}</div>
                        </div>
                        <div>
                            <div class="text-muted small">Approval Remarks</div>
                            <div>{{ $ticket->approval_remarks ?: 'N/A' }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="card mt-3" style="border-radius: 8px;">
                <div class="card-header bg-transparent">
                    <h6 class="mb-0">Activity</h6>
                </div>
                <div class="card-body">
                    @forelse ($ticket->logs->sortByDesc('created_at') as $log)
                        <div class="border-bottom pb-2 mb-2">
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
