@extends('layout.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Trip Tickets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.index') }}">Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Vehicles</li>
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

    <div class="card" style="border-radius: 8px;">
        <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h5 class="mb-1">Vehicle Management</h5>
                <p class="text-muted small mb-0">Add and maintain vehicle model and plate number for trip tickets.</p>
            </div>
            <a href="{{ route('trip-tickets.index') }}" class="btn btn-outline-secondary btn-sm">Back to Trip Tickets</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('trip-tickets.vehicles.store') }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-12 col-md-5">
                    <label for="description" class="form-label">Vehicle Model</label>
                    <input type="text" class="form-control" id="description" name="description" value="{{ old('description') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label for="plate_number" class="form-label">Plate Number</label>
                    <input type="text" class="form-control" id="plate_number" name="plate_number" value="{{ old('plate_number') }}" required>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Add Vehicle</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Vehicle Model</th>
                            <th>Plate Number</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->description }}</td>
                                <td>{{ $vehicle->plate_number }}</td>
                                <td>
                                    <span class="badge {{ $vehicle->is_available ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $vehicle->is_available ? 'Available' : 'Unavailable' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('trip-tickets.vehicles.edit', $vehicle) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('trip-tickets.vehicles.destroy', $vehicle) }}" class="d-inline" onsubmit="return confirm('Delete this vehicle?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No vehicles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $vehicles->links() }}
        </div>
    </div>
@endsection
