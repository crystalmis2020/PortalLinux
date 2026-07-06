@extends('layout.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Trip Tickets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.index') }}">Requests</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.drivers.index') }}">Drivers</a></li>
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
            <h5 class="mb-1">Edit Driver</h5>
            <p class="text-muted small mb-0">Update the driver name.</p>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('trip-tickets.drivers.update', $driver) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-8 col-lg-6">
                    <label for="name" class="form-label">Driver Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $driver->name) }}" required>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('trip-tickets.drivers.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
