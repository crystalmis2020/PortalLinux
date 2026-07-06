<div class="modal fade" id="driverCrudModal" tabindex="-1" aria-labelledby="driverCrudModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="driverCrudModalLabel">Manage Drivers</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('trip-tickets.drivers.store') }}" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-12 col-md-8">
                        <label for="modal_driver_name" class="form-label">Driver Name</label>
                        <input type="text" class="form-control" id="modal_driver_name" name="name" required>
                    </div>
                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-plus me-1"></i>Add Driver</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Driver Name</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($dispatcherDrivers as $driver)
                                <tr>
                                    <td style="min-width: 260px;">
                                        <form id="driver-update-{{ $driver->id }}" method="POST" action="{{ route('trip-tickets.drivers.update', $driver) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" class="form-control form-control-sm" name="name" value="{{ $driver->name }}" required>
                                        </form>
                                    </td>
                                    <td>
                                        <span class="badge {{ $driver->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $driver->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="submit" form="driver-update-{{ $driver->id }}" class="btn btn-outline-primary btn-sm">Save</button>
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
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="vehicleCrudModal" tabindex="-1" aria-labelledby="vehicleCrudModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="vehicleCrudModalLabel">Manage Vehicles</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('trip-tickets.vehicles.store') }}" class="row g-2 align-items-end mb-4">
                    @csrf
                    <div class="col-12 col-md-5">
                        <label for="modal_vehicle_description" class="form-label">Vehicle Model</label>
                        <input type="text" class="form-control" id="modal_vehicle_description" name="description" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="modal_vehicle_plate_number" class="form-label">Plate Number</label>
                        <input type="text" class="form-control" id="modal_vehicle_plate_number" name="plate_number" required>
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
                            @forelse ($dispatcherVehicles as $vehicle)
                                <tr>
                                    <td style="min-width: 240px;">
                                        <form id="vehicle-update-{{ $vehicle->id }}" method="POST" action="{{ route('trip-tickets.vehicles.update', $vehicle) }}">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" class="form-control form-control-sm" name="description" value="{{ $vehicle->description }}" required>
                                        </form>
                                    </td>
                                    <td style="min-width: 160px;">
                                        <input type="text" class="form-control form-control-sm" name="plate_number" value="{{ $vehicle->plate_number }}" form="vehicle-update-{{ $vehicle->id }}" required>
                                    </td>
                                    <td>
                                        <span class="badge {{ $vehicle->is_available ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $vehicle->is_available ? 'Available' : 'Unavailable' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <button type="submit" form="vehicle-update-{{ $vehicle->id }}" class="btn btn-outline-primary btn-sm">Save</button>
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
            </div>
        </div>
    </div>
</div>
