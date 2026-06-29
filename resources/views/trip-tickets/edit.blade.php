@extends('layout.app')

@section('css-custom')
    <link href="{{ asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('assets/plugins/select2/css/select2-bootstrap5.css') }}" rel="stylesheet" />
    <style>
        .trip-ticket-form .form-label {
            font-weight: 600;
        }

        .trip-ticket-form .select2-container--bootstrap-5 .select2-selection {
            min-height: calc(1.5em + .75rem + 2px);
        }

        .trip-ticket-form .input-group-text {
            background-color: #f8f9fa;
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
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.show', $ticket) }}">{{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>
    </div>

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

    <div class="card" style="border-radius: 8px;">
        <div class="card-header bg-transparent">
            <h5 class="mb-1">Edit Trip Ticket Request</h5>
            <p class="text-muted small mb-0">{{ $ticket->ticket_number ?: 'Request #' . $ticket->id }}</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('trip-tickets.update', $ticket) }}" class="trip-ticket-form">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="mb-0 text-uppercase text-muted small fw-semibold">Requester</h6>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Requester</label>
                        <input type="text" class="form-control" value="{{ $requester->full_name }}" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Department / Section</label>
                        <input type="text" class="form-control" value="{{ $requester->department?->name ?? 'No department' }} / {{ $requester->section?->name ?? 'No section' }}" disabled>
                    </div>

                    @if (!$requester->department_id || !$requester->section_id)
                        <div class="col-12 col-md-6">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select trip-ticket-select" id="department_id" name="department_id">
                                <option value="">Select department</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $ticket->department_id) == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="section_id" class="form-label">Section</label>
                            <select class="form-select trip-ticket-select" id="section_id" name="section_id">
                                <option value="">Select section</option>
                                @foreach ($sections as $section)
                                    <option value="{{ $section->id }}" {{ old('section_id', $ticket->section_id) == $section->id ? 'selected' : '' }}>
                                        {{ $section->name }}{{ $section->department?->name ? ' - ' . $section->department->name : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-12">
                        <div class="border-top pt-3 mt-1">
                            <h6 class="mb-0 text-uppercase text-muted small fw-semibold">Schedule</h6>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="requested_start_datetime" class="form-label">Requested Departure</label>
                        <input type="date" class="form-control" id="requested_start_datetime" name="requested_start_datetime" value="{{ old('requested_start_datetime', $ticket->requested_start_datetime?->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="requested_end_datetime" class="form-label">Requested Return</label>
                        <input type="date" class="form-control" id="requested_end_datetime" name="requested_end_datetime" value="{{ old('requested_end_datetime', $ticket->requested_end_datetime?->format('Y-m-d')) }}" required>
                    </div>

                    @include('trip-tickets.partials.destination-fields', ['ticket' => $ticket])

                    <div class="col-12">
                        <div class="border-top pt-3 mt-1">
                            <h6 class="mb-0 text-uppercase text-muted small fw-semibold">Trip Details</h6>
                        </div>
                    </div>
                    <div class="col-12">
                        <label for="purpose" class="form-label">Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" required>{{ old('purpose', $ticket->purpose) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label for="passengers" class="form-label">Passengers / Personnel</label>
                        <textarea class="form-control" id="passengers" name="passengers" rows="3">{{ old('passengers', $ticket->passengers) }}</textarea>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <input type="text" class="form-control" id="contact_number" name="contact_number" value="{{ old('contact_number', $ticket->contact_number) }}">
                    </div>
                    <div class="col-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3">{{ old('remarks', $ticket->remarks) }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('trip-tickets.show', $ticket) }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bx bx-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('js-custom')
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    @include('trip-tickets.partials.destination-scripts')
@endsection
