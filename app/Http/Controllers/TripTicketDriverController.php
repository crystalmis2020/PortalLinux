<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TripTicketDriverController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeDispatcher($request);

        return view('trip-tickets.drivers.index', [
            'drivers' => Driver::query()->orderBy('name')->paginate(15),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeDispatcher($request);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Driver::create([
            'name' => $payload['name'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Driver created successfully.');
    }

    public function edit(Request $request, Driver $driver): View
    {
        $this->authorizeDispatcher($request);

        return view('trip-tickets.drivers.edit', [
            'driver' => $driver,
        ]);
    }

    public function update(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorizeDispatcher($request);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $driver->update([
            'name' => $payload['name'],
            'is_active' => true,
        ]);

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Driver updated successfully.');
    }

    public function destroy(Request $request, Driver $driver): RedirectResponse
    {
        $this->authorizeDispatcher($request);

        if ($driver->tripTickets()->exists()) {
            $driver->update(['is_active' => false]);

            return redirect()
                ->route('trip-tickets.index')
                ->with('success', 'Driver has trip ticket records, so it was marked inactive.');
        }

        $driver->delete();

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Driver deleted successfully.');
    }

    protected function authorizeDispatcher(Request $request): void
    {
        abort_unless($request->user()?->canEncodeTripTickets(), 403);
    }
}
