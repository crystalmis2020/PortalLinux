<?php

namespace App\Http\Controllers;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use App\Http\Requests\StoreTripTicketRequest;
use App\Http\Requests\EncodeTripTicketRequest;
use App\Models\Department;
use App\Models\Driver;
use App\Models\Section;
use App\Models\Vehicle;
use App\Models\TripTicket;
use App\Models\TripTicketLog;
use App\Models\TripTicketLocation;
use App\Services\DistanceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $isDispatcher = (bool) $user->canEncodeTripTickets();

        $ticketsQuery = TripTicket::query()
            ->with([
                'requester:id,full_name,department_id,section_id',
                'department:id,name',
                'section:id,name',
                'vehicle:id,plate_number,description',
                'driver:id,name',
                'encoder:id,full_name',
                'approver:id,full_name',
                'location:id,region_name,province_name,city_municipality_name,destination',
                'logs.user:id,full_name',
            ])
            ->latest()
            ->latest('id');

        if (! $isDispatcher) {
            $ticketsQuery->where('requested_by', $user->id);
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), TripTicket::statuses(), true)) {
            $ticketsQuery->where('status', $request->string('status')->toString());
        }

        $tickets = $ticketsQuery->paginate(15)->withQueryString();
        $dispatcherDrivers = $isDispatcher
            ? Driver::query()->where('is_active', true)->orderBy('name')->get()
            : collect();
        $dispatcherVehicles = $isDispatcher
            ? Vehicle::query()->where('is_available', true)->orderBy('description')->orderBy('plate_number')->get()
            : collect();
        $ticketUi = [];

        foreach ($tickets->getCollection() as $ticket) {
            if (! $isDispatcher) {
                continue;
            }

            $canEncodeDetails = $this->canEncodeDetails($request, $ticket);
            $scheduleWindow = $this->tripTicketScheduleWindow($ticket);
            $resourceConflicts = $canEncodeDetails
                ? $this->resourceConflictLabels($ticket, $scheduleWindow['start'], $scheduleWindow['end'])
                : ['drivers' => collect(), 'vehicles' => collect()];

            $ticketUi[$ticket->id] = [
                'can_edit' => $this->canEditRequest($request, $ticket),
                'can_encode' => $canEncodeDetails,
                'scheduled_drivers' => $resourceConflicts['drivers'],
                'scheduled_vehicles' => $resourceConflicts['vehicles'],
                'availability_url' => $canEncodeDetails ? route('trip-tickets.availability', $ticket) : null,
            ];
        }

        return view('trip-tickets.index', [
            'tickets' => $tickets,
            'statuses' => TripTicket::statuses(),
            'selectedStatus' => $request->string('status')->toString(),
            'dispatcherDrivers' => $dispatcherDrivers,
            'dispatcherVehicles' => $dispatcherVehicles,
            'ticketUi' => $ticketUi,
            'isDispatcher' => $isDispatcher,
        ]);
    }

    public function create(Request $request)
    {
        return view('trip-tickets.create', [
            'departments' => Department::query()->orderBy('name')->get(),
            'sections' => Section::query()->with('department')->orderBy('name')->get(),
            'requester' => $request->user()->loadMissing(['department', 'section']),
            'destinationLocations' => TripTicketLocation::locationTree(),
            'selectedDestinationRegion' => null,
            'selectedDestinationProvince' => null,
            'selectedDestinationCity' => null,
        ]);
    }


    public function edit(Request $request, TripTicket $tripTicket)
    {
        $this->ensureCanEdit($request, $tripTicket);

        $tripTicket->loadMissing(['requester.department', 'requester.section', 'location']);
        $selection = $this->destinationSelection($tripTicket);

        return view('trip-tickets.edit', [
            'ticket' => $tripTicket,
            'departments' => Department::query()->orderBy('name')->get(),
            'sections' => Section::query()->with('department')->orderBy('name')->get(),
            'requester' => $tripTicket->requester?->loadMissing(['department', 'section']) ?: $request->user()->loadMissing(['department', 'section']),
            'destinationLocations' => TripTicketLocation::locationTree(),
            'selectedDestinationRegion' => $selection['region'],
            'selectedDestinationProvince' => $selection['province'],
            'selectedDestinationCity' => $selection['city'],
        ]);
    }

    public function update(StoreTripTicketRequest $request, TripTicket $tripTicket, DistanceCalculationService $distanceCalculator)
    {
        $this->ensureCanEdit($request, $tripTicket);

        $user = $request->user();
        $payload = $request->validated();
        $location = ! empty($payload['trip_ticket_location_id'])
            ? TripTicketLocation::find($payload['trip_ticket_location_id'])
            : null;
        $distanceKm = $location ? $distanceCalculator->distanceForLocation($location) : null;
        $distanceKm ??= (float) $payload['distance_km'];

        DB::transaction(function () use ($payload, $user, $tripTicket, $distanceKm): void {
            $tripTicket->update([
                'department_id' => $tripTicket->department_id ?: ($payload['department_id'] ?? null),
                'section_id' => $tripTicket->section_id ?: ($payload['section_id'] ?? null),
                'purpose' => $payload['purpose'],
                'destination' => $payload['destination'],
                'trip_ticket_location_id' => $payload['trip_ticket_location_id'] ?? null,
                'distance_km' => $distanceKm,
                'requested_start_datetime' => $payload['requested_start_datetime'],
                'requested_end_datetime' => $payload['requested_end_datetime'],
                'passengers' => $payload['passengers'] ?? null,
                'contact_number' => $payload['contact_number'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
            ]);

            TripTicketLog::create([
                'trip_ticket_id' => $tripTicket->id,
                'user_id' => $user->id,
                'action' => 'request_updated',
                'to_status' => $tripTicket->status,
                'remarks' => 'Trip ticket request details updated.',
            ]);
        });

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Trip ticket request updated successfully.');
    }

    public function store(StoreTripTicketRequest $request, DistanceCalculationService $distanceCalculator)
    {
        $user = $request->user();
        $payload = $request->validated();
        $location = ! empty($payload['trip_ticket_location_id'])
            ? TripTicketLocation::find($payload['trip_ticket_location_id'])
            : null;
        $distanceKm = $location ? $distanceCalculator->distanceForLocation($location) : null;
        $distanceKm ??= (float) $payload['distance_km'];

        $ticket = DB::transaction(function () use ($payload, $user, $distanceKm): TripTicket {
            $ticket = TripTicket::create([
                'requested_by' => $user->id,
                'department_id' => $user->department_id ?: ($payload['department_id'] ?? null),
                'section_id' => $user->section_id ?: ($payload['section_id'] ?? null),
                'purpose' => $payload['purpose'],
                'destination' => $payload['destination'],
                'trip_ticket_location_id' => $payload['trip_ticket_location_id'] ?? null,
                'distance_km' => $distanceKm,
                'requested_start_datetime' => $payload['requested_start_datetime'],
                'requested_end_datetime' => $payload['requested_end_datetime'],
                'passengers' => $payload['passengers'] ?? null,
                'contact_number' => $payload['contact_number'] ?? null,
                'remarks' => $payload['remarks'] ?? null,
                'status' => TripTicket::STATUS_PENDING_DETAILS,
            ]);

            TripTicketLog::create([
                'trip_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'requested',
                'to_status' => TripTicket::STATUS_PENDING_DETAILS,
                'remarks' => 'Trip ticket request submitted.',
            ]);

            return $ticket;
        });

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Trip ticket request submitted successfully.');
    }

    public function show(Request $request, TripTicket $tripTicket)
    {
        $this->ensureCanView($request, $tripTicket);

        $tripTicket->load([
            'requester:id,full_name,department_id,section_id',
            'department:id,name',
            'section:id,name',
            'vehicle:id,plate_number,description',
            'driver:id,name',
            'encoder:id,full_name',
            'approver:id,full_name',
            'logs.user:id,full_name',
        ]);

        $canEncodeDetails = $this->canEncodeDetails($request, $tripTicket);
        $scheduleWindow = $this->tripTicketScheduleWindow($tripTicket);
        $resourceConflicts = $canEncodeDetails
            ? $this->resourceConflictLabels($tripTicket, $scheduleWindow['start'], $scheduleWindow['end'])
            : ['drivers' => collect(), 'vehicles' => collect()];

        return view('trip-tickets.show', [
            'ticket' => $tripTicket,
            'canEncodeDetails' => $canEncodeDetails,
            'canEditRequest' => $this->canEditRequest($request, $tripTicket),
            'dispatcherDrivers' => $canEncodeDetails
                ? Driver::query()->where('is_active', true)->orderBy('name')->get()
                : collect(),
            'dispatcherVehicles' => $canEncodeDetails
                ? Vehicle::query()->where('is_available', true)->orderBy('description')->orderBy('plate_number')->get()
                : collect(),
            'scheduledDrivers' => $resourceConflicts['drivers'],
            'scheduledVehicles' => $resourceConflicts['vehicles'],
            'availabilityUrl' => $canEncodeDetails ? route('trip-tickets.availability', $tripTicket) : null,
        ]);
    }

    public function availability(Request $request, TripTicket $tripTicket)
    {
        abort_unless($this->canEncodeDetails($request, $tripTicket), 403);

        $validated = $request->validate([
            'actual_departure_datetime' => ['nullable', 'date'],
            'actual_return_datetime' => ['nullable', 'date', 'after_or_equal:actual_departure_datetime'],
        ]);

        $scheduleWindow = $this->tripTicketScheduleWindow($tripTicket, $validated);
        $resourceConflicts = $this->resourceConflictLabels($tripTicket, $scheduleWindow['start'], $scheduleWindow['end']);

        return response()->json([
            'drivers' => $resourceConflicts['drivers'],
            'vehicles' => $resourceConflicts['vehicles'],
        ]);
    }

    public function print(Request $request, TripTicket $tripTicket)
    {
        $this->ensureCanView($request, $tripTicket);

        abort_unless($tripTicket->status === TripTicket::STATUS_APPROVED, 403);

        if (!$tripTicket->qr_token) {
            $tripTicket->forceFill([
                'qr_token' => TripTicket::generateQrToken(),
            ])->save();
        }

        $tripTicket->load([
            'requester:id,full_name',
            'department:id,name',
            'section:id,name',
            'vehicle:id,plate_number,description',
            'driver:id,name',
            'encoder:id,full_name',
            'approver:id,full_name',
        ]);

        $qrValue = 'TT:' . $tripTicket->qr_token;
        $qrRenderer = new ImageRenderer(
            new RendererStyle(132),
            new SvgImageBackEnd()
        );
        $qrSvg = (new Writer($qrRenderer))->writeString($qrValue);

        return view('trip-tickets.print', [
            'ticket' => $tripTicket,
            'qrValue' => $qrValue,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function encode(EncodeTripTicketRequest $request, TripTicket $tripTicket)
    {
        abort_unless($this->canEncodeDetails($request, $tripTicket), 403);

        $payload = $request->validated();
        $user = $request->user();
        $scheduleWindow = $this->tripTicketScheduleWindow($tripTicket, $payload);
        $conflicts = $this->resourceConflicts($tripTicket, $scheduleWindow['start'], $scheduleWindow['end']);

        if ($conflicts->where('vehicle_id', (int) $payload['vehicle_id'])->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'vehicle_id' => 'The selected vehicle is already scheduled for another trip during this time.',
            ]);
        }

        if ($conflicts->where('driver_id', (int) $payload['driver_id'])->isNotEmpty()) {
            return back()->withInput()->withErrors([
                'driver_id' => 'The selected driver is already scheduled for another trip during this time.',
            ]);
        }

        DB::transaction(function () use ($tripTicket, $payload, $user): void {
            $ticket = TripTicket::query()
                ->whereKey($tripTicket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $vehicle = Vehicle::query()->findOrFail($payload['vehicle_id']);
            $driver = Driver::query()->findOrFail($payload['driver_id']);
            $vehicleDetails = trim($vehicle->plate_number . ' - ' . $vehicle->description, ' -');

            $fromStatus = $ticket->status;
            $ticketNumber = $payload['ticket_number'] ?? null;

            if (!$ticketNumber && !$ticket->ticket_number) {
                $ticketNumber = $this->nextTicketNumber($ticket);
            }

            $ticket->update([
                'ticket_number' => $ticketNumber ?: $ticket->ticket_number,
                'vehicle_id' => $vehicle->id,
                'vehicle_details' => $vehicleDetails,
                'driver_id' => $driver->id,
                'driver_name' => $driver->name,
                'actual_departure_datetime' => null,
                'actual_return_datetime' => null,
                'departure_recorded_by' => null,
                'departure_recorded_at' => null,
                'return_recorded_by' => null,
                'return_recorded_at' => null,
                'gatekeeper_departure_remarks' => null,
                'gatekeeper_return_remarks' => null,
                'encoded_by' => $user->id,
                'encoded_at' => now(),
                'remarks' => $payload['remarks'] ?? $ticket->remarks,
                'status' => TripTicket::STATUS_FOR_APPROVAL,
            ]);

            TripTicketLog::create([
                'trip_ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'details_encoded',
                'from_status' => $fromStatus,
                'to_status' => TripTicket::STATUS_FOR_APPROVAL,
                'remarks' => 'Trip ticket details encoded and submitted for approval.',
                'metadata' => [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_details' => $vehicleDetails,
                    'driver_id' => $driver->id,
                    'driver_name' => $driver->name,
                ],
            ]);
        });

        return redirect()
            ->route('trip-tickets.index')
            ->with('success', 'Trip ticket details encoded and submitted for approval.');
    }


    protected function tripTicketScheduleWindow(TripTicket $tripTicket, array $payload = []): array
    {
        $start = $payload['actual_departure_datetime']
            ?? $tripTicket->actual_departure_datetime
            ?? $tripTicket->requested_start_datetime;
        $end = $payload['actual_return_datetime']
            ?? $tripTicket->actual_return_datetime
            ?? $tripTicket->requested_end_datetime;

        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end->copy() : Carbon::parse($end);

        return [
            'start' => $start->startOfDay(),
            'end' => $end->endOfDay(),
        ];
    }

    protected function resourceConflicts(TripTicket $tripTicket, Carbon $start, Carbon $end)
    {
        return $this->resourceScheduleConflicts($tripTicket, $start, $end)
            ->whereIn('status', [
                TripTicket::STATUS_APPROVED,
                TripTicket::STATUS_DISPATCHED,
            ])
            ->get();
    }

    protected function resourceScheduleConflicts(TripTicket $tripTicket, Carbon $start, Carbon $end)
    {
        $databaseStart = $start->copy()->utc()->format('Y-m-d H:i:s');
        $databaseEnd = $end->copy()->utc()->format('Y-m-d H:i:s');

        return TripTicket::query()
            ->whereKeyNot($tripTicket->id)
            ->whereIn('status', [
                TripTicket::STATUS_FOR_APPROVAL,
                TripTicket::STATUS_APPROVED,
                TripTicket::STATUS_DISPATCHED,
            ])
            ->where(function ($query) {
                $query->whereNotNull('vehicle_id')
                    ->orWhereNotNull('driver_id');
            })
            ->whereRaw('COALESCE(actual_departure_datetime, requested_start_datetime) < ?', [$databaseEnd])
            ->whereRaw('COALESCE(actual_return_datetime, requested_end_datetime) > ?', [$databaseStart])
            ->with(['vehicle:id,plate_number,description', 'driver:id,name']);
    }

    protected function resourceConflictLabels(TripTicket $tripTicket, Carbon $start, Carbon $end): array
    {
        $conflicts = $this->resourceScheduleConflicts($tripTicket, $start, $end)->get();

        return [
            'drivers' => $conflicts
                ->whereNotNull('driver_id')
                ->groupBy('driver_id')
                ->map(fn ($tickets) => $this->resourceConflictMeta($tickets->first())),
            'vehicles' => $conflicts
                ->whereNotNull('vehicle_id')
                ->groupBy('vehicle_id')
                ->map(fn ($tickets) => $this->resourceConflictMeta($tickets->first())),
        ];
    }

    protected function resourceConflictMeta(TripTicket $ticket): array
    {
        $isBlocked = in_array($ticket->status, [
            TripTicket::STATUS_APPROVED,
            TripTicket::STATUS_DISPATCHED,
        ], true);

        return [
            'label' => $isBlocked ? 'scheduled' : 'waiting for approval',
            'blocked' => $isBlocked,
        ];
    }

    protected function ensureCanEdit(Request $request, TripTicket $tripTicket): void
    {
        abort_unless($this->canEditRequest($request, $tripTicket), 403);
    }

    protected function canEditRequest(Request $request, TripTicket $tripTicket): bool
    {
        $user = $request->user();

        return (bool) ($user?->canManageTripTickets()
            || (
                (int) $tripTicket->requested_by === (int) $user?->id
                && in_array($tripTicket->status, [
                    TripTicket::STATUS_PENDING_DETAILS,
                    TripTicket::STATUS_RETURNED,
                ], true)
            ));
    }

    protected function destinationSelection(TripTicket $tripTicket): array
    {
        return [
            'region' => $tripTicket->location?->region_name,
            'province' => $tripTicket->location?->province_name,
            'city' => $tripTicket->location?->city_municipality_name,
        ];
    }

    protected function ensureCanView(Request $request, TripTicket $tripTicket): void
    {
        $user = $request->user();

        abort_unless(
            $user->canManageTripTickets()
                || $this->canEncoderView($request, $tripTicket)
                || (int) $tripTicket->requested_by === (int) $user->id,
            403
        );
    }

    protected function canEncoderView(Request $request, TripTicket $tripTicket): bool
    {
        $user = $request->user();

        return $user?->canEncodeTripTickets()
            && (
                in_array($tripTicket->status, [
                    TripTicket::STATUS_PENDING_DETAILS,
                    TripTicket::STATUS_RETURNED,
                ], true)
                || (int) $tripTicket->encoded_by === (int) $user->id
            );
    }

    protected function canEncodeDetails(Request $request, TripTicket $tripTicket): bool
    {
        return $request->user()?->canEncodeTripTickets()
            && in_array($tripTicket->status, [
                TripTicket::STATUS_PENDING_DETAILS,
                TripTicket::STATUS_RETURNED,
            ], true);
    }

    protected function nextTicketNumber(TripTicket $tripTicket): string
    {
        return 'TT-' . now()->format('Y') . '-' . str_pad((string) $tripTicket->id, 5, '0', STR_PAD_LEFT);
    }
}
