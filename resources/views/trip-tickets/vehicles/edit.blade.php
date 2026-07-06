@extends('layout.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Trip Tickets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.index') }}">Requests</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.vehicles.index') }}">Vehicles</a></li>
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
            <h5 class="mb-1">Edit Vehicle</h5>
            <p class="text-muted small mb-0">Update the vehicle model and plate number.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('trip-tickets.vehicles.update', $vehicle) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label for="description" class="form-label">Vehicle Model</label>
                    <input type="text" class="form-control" id="description" name="description" value="{{ old('description', $vehicle->description) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="plate_number" class="form-label">Plate Number</label>
                    <input type="text" class="form-control" id="plate_number" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('trip-tickets.vehicles.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
