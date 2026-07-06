@extends('layout.app')

@section('css-custom')
    <style>
        .trip-ticket-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 8px;
            box-shadow: none;
        }

        .trip-ticket-table thead th {
            color: #64748b;
            font-size: 0.74rem;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .trip-ticket-table td {
            vertical-align: middle;
        }

        html.dark-theme .trip-ticket-card {
            background: rgba(255, 255, 255, 0.03);
            border-color: rgba(255, 255, 255, 0.08);
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
            <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('trip-tickets.index') }}">
                <div class="col-12 col-md-4 col-lg-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status }}" {{ $selectedStatus === $status ? 'selected' : '' }}>
                                {{ str_replace('_', ' ', ucfirst($status)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bx bx-filter-alt me-1"></i>Filter
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle trip-ticket-table">
                    <thead class="table-light">
                        <tr>
                            <th>Schedule</th>
                            <th>Destination</th>
                            <th>Requester</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td>
                                    <div>{{ $ticket->requested_start_datetime?->format('M d, Y h:i A') }}</div>
                                    <div class="text-muted small">{{ $ticket->requested_end_datetime?->format('M d, Y h:i A') }}</div>
                                </td>
                                <td>{{ $ticket->destination }}</td>
                                <td>{{ $ticket->requester?->full_name ?? 'N/A' }}</td>
                                <td>
                                    <div>{{ $ticket->department?->name ?? 'N/A' }}</div>
                                    <div class="text-muted small">{{ $ticket->section?->name ?? 'N/A' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ str_replace('_', ' ', ucfirst($ticket->status)) }}</span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('trip-tickets.show', $ticket) }}" class="btn btn-outline-primary btn-sm">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No trip ticket requests found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $tickets->links() }}
        </div>
    </div>

    @if (auth()->user()?->canEncodeTripTickets())
        @include('trip-tickets.partials.dispatcher-crud-modals')
    @endif
@endsection
