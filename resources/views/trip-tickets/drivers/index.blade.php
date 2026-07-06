@extends('layout.app')

@section('content')
    <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Trip Tickets</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('trip-tickets.index') }}">Requests</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Drivers</li>
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
                <h5 class="mb-1">Driver Management</h5>
                <p class="text-muted small mb-0">Add and maintain driver names for trip tickets.</p>
            </div>
            <a href="{{ route('trip-tickets.index') }}" class="btn btn-outline-secondary btn-sm">Back to Trip Tickets</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('trip-tickets.drivers.store') }}" class="row g-2 align-items-end mb-4">
                @csrf
                <div class="col-12 col-md-8 col-lg-6">
                    <label for="name" class="form-label">Driver Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required>
                </div>
                <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Add Driver</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($drivers as $driver)
                            <tr>
                                <td>{{ $driver->name }}</td>
                                <td>
                                    <span class="badge {{ $driver->is_active ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $driver->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('trip-tickets.drivers.edit', $driver) }}" class="btn btn-outline-primary btn-sm">Edit</a>
                                    <form method="POST" action="{{ route('trip-tickets.drivers.destroy', $driver) }}" class="d-inline" onsubmit="return confirm('Delete this driver?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No drivers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $drivers->links() }}
        </div>
    </div>
@endsection
