<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TripTicketVehicleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDispatcher($request);

        return view('trip-tickets.vehicles.index', [
            'vehicles' => Vehicle::query()->orderBy('description')->orderBy('plate_number')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDispatcher($request);

        $payload = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'plate_number' => ['required', 'string', 'max:50', 'unique:vehicles,plate_number'],
        ]);

        Vehicle::create([
            'description' => $payload['description'],
            'plate_number' => $payload['plate_number'],
            'is_available' => true,
        ]);

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Vehicle created successfully.');
    }

    public function edit(Request $request, Vehicle $vehicle): View
    {
        $this->authorizeDispatcher($request);

        return view('trip-tickets.vehicles.edit', [
            'vehicle' => $vehicle,
        ]);
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeDispatcher($request);

        $payload = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'plate_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles', 'plate_number')->ignore($vehicle->id),
            ],
        ]);

        $vehicle->update([
            'description' => $payload['description'],
            'plate_number' => $payload['plate_number'],
            'is_available' => true,
        ]);

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Vehicle updated successfully.');
    }

    public function destroy(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $this->authorizeDispatcher($request);

        if ($vehicle->tripTickets()->exists()) {
            return redirect()
                ->route('trip-tickets.index')
                ->with('success', 'Vehicle has trip ticket records and cannot be deleted.');
        }

        $vehicle->delete();

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    protected function authorizeDispatcher(Request $request): void
    {
        abort_unless($request->user()?->canEncodeTripTickets(), 403);
    }
}
