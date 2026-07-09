<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TripTicket;
use App\Models\TripTicketLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripTicketGatekeeperController extends Controller
{
    public function readyForDeparture(Request $request): JsonResponse
    {
        $this->ensureGatekeeper($request);
        $day = $this->gatekeeperDay($request);

        $tickets = $this->gatekeeperBaseQuery()
            ->where('status', TripTicket::STATUS_APPROVED)
            ->whereNull('actual_departure_datetime')
            ->where(fn ($query) => $this->scheduledOnDay($query, $day))
            ->orderBy('requested_start_datetime')
            ->orderBy('id')
            ->paginate(20);

        return $this->paginatedTickets($tickets);
    }

    public function awaitingReturn(Request $request): JsonResponse
    {
        $this->ensureGatekeeper($request);
        $day = $this->gatekeeperDay($request);

        $tickets = $this->gatekeeperBaseQuery()
            ->where('status', TripTicket::STATUS_DISPATCHED)
            ->whereNotNull('actual_departure_datetime')
            ->whereNull('actual_return_datetime')
            ->where(fn ($query) => $this->scheduledOnDay($query, $day))
            ->orderBy('actual_departure_datetime')
            ->orderBy('id')
            ->paginate(20);

        return $this->paginatedTickets($tickets);
    }

    public function search(Request $request): JsonResponse
    {
        $this->ensureGatekeeper($request);

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'date' => ['nullable', 'date'],
        ]);

        $day = $this->gatekeeperDay($request);
        $term = trim((string) ($validated['q'] ?? ''));

        $tickets = $this->gatekeeperBaseQuery()
            ->whereIn('status', [TripTicket::STATUS_APPROVED, TripTicket::STATUS_DISPATCHED])
            ->where(fn ($query) => $this->scheduledOnDay($query, $day))
            ->when($term !== '', function ($query) use ($term): void {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';

                $query->where(function ($search) use ($like): void {
                    $search->where('ticket_number', 'like', $like)
                        ->orWhere('destination', 'like', $like)
                        ->orWhere('driver_name', 'like', $like)
                        ->orWhere('vehicle_details', 'like', $like)
                        ->orWhereHas('driver', fn ($driver) => $driver->where('name', 'like', $like))
                        ->orWhereHas('vehicle', function ($vehicle) use ($like): void {
                            $vehicle->where('plate_number', 'like', $like)
                                ->orWhere('description', 'like', $like);
                        })
                        ->orWhereHas('requester', fn ($requester) => $requester->where('full_name', 'like', $like));
                });
            })
            ->orderByRaw("CASE status WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END", [TripTicket::STATUS_APPROVED, TripTicket::STATUS_DISPATCHED])
            ->orderBy('requested_start_datetime')
            ->orderBy('id')
            ->paginate(20);

        return $this->paginatedTickets($tickets);
    }

    public function qrLookup(Request $request, string $token): JsonResponse
    {
        $this->ensureGatekeeper($request);

        $normalizedToken = $this->normalizeQrToken($token);

        $ticket = $this->gatekeeperBaseQuery()
            ->where('qr_token', $normalizedToken)
            ->first();

        if (!$ticket) {
            return response()->json([
                'message' => 'Trip ticket QR token was not found.',
            ], 404);
        }

        return response()->json([
            'ticket' => $this->ticketPayload($ticket),
        ]);
    }

    public function recordDeparture(Request $request, TripTicket $tripTicket): JsonResponse
    {
        $this->ensureGatekeeper($request);

        $payload = $request->validate([
            'actual_departure_datetime' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $actualDeparture = isset($payload['actual_departure_datetime'])
            ? Carbon::parse($payload['actual_departure_datetime'])
            : now();

        $ticket = DB::transaction(function () use ($request, $tripTicket, $payload, $actualDeparture): TripTicket {
            $ticket = TripTicket::query()
                ->whereKey($tripTicket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ticket->status !== TripTicket::STATUS_APPROVED || $ticket->actual_departure_datetime) {
                abort(response()->json([
                    'message' => 'Only approved trips without a recorded departure can be dispatched.',
                    'status' => $ticket->status,
                ], 422));
            }

            $fromStatus = $ticket->status;

            $ticket->update([
                'actual_departure_datetime' => $actualDeparture,
                'departure_recorded_by' => $request->user()->id,
                'departure_recorded_at' => now(),
                'gatekeeper_departure_remarks' => $payload['remarks'] ?? null,
                'status' => TripTicket::STATUS_DISPATCHED,
            ]);

            TripTicketLog::create([
                'trip_ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action' => 'departure_recorded',
                'from_status' => $fromStatus,
                'to_status' => TripTicket::STATUS_DISPATCHED,
                'remarks' => $payload['remarks'] ?? 'Gatekeeper recorded actual departure.',
                'metadata' => [
                    'actual_departure_datetime' => $actualDeparture->toIso8601String(),
                ],
            ]);

            return $ticket->fresh($this->gatekeeperRelations());
        });

        return response()->json([
            'message' => 'Actual departure recorded successfully.',
            'ticket' => $this->ticketPayload($ticket),
        ]);
    }

    public function recordReturn(Request $request, TripTicket $tripTicket): JsonResponse
    {
        $this->ensureGatekeeper($request);

        $payload = $request->validate([
            'actual_return_datetime' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $actualReturn = isset($payload['actual_return_datetime'])
            ? Carbon::parse($payload['actual_return_datetime'])
            : now();

        $ticket = DB::transaction(function () use ($request, $tripTicket, $payload, $actualReturn): TripTicket {
            $ticket = TripTicket::query()
                ->whereKey($tripTicket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ticket->status !== TripTicket::STATUS_DISPATCHED || !$ticket->actual_departure_datetime || $ticket->actual_return_datetime) {
                abort(response()->json([
                    'message' => 'Only dispatched trips without a recorded return can be completed.',
                    'status' => $ticket->status,
                ], 422));
            }

            if ($actualReturn->lt($ticket->actual_departure_datetime)) {
                abort(response()->json([
                    'message' => 'Actual return cannot be earlier than actual departure.',
                ], 422));
            }

            $fromStatus = $ticket->status;

            $ticket->update([
                'actual_return_datetime' => $actualReturn,
                'return_recorded_by' => $request->user()->id,
                'return_recorded_at' => now(),
                'gatekeeper_return_remarks' => $payload['remarks'] ?? null,
                'status' => TripTicket::STATUS_COMPLETED,
            ]);

            TripTicketLog::create([
                'trip_ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action' => 'return_recorded',
                'from_status' => $fromStatus,
                'to_status' => TripTicket::STATUS_COMPLETED,
                'remarks' => $payload['remarks'] ?? 'Gatekeeper recorded actual return.',
                'metadata' => [
                    'actual_return_datetime' => $actualReturn->toIso8601String(),
                ],
            ]);

            return $ticket->fresh($this->gatekeeperRelations());
        });

        return response()->json([
            'message' => 'Actual return recorded successfully.',
            'ticket' => $this->ticketPayload($ticket),
        ]);
    }

    protected function ensureGatekeeper(Request $request): void
    {
        abort_unless($request->user()?->canGatekeepTripTickets(), 403);
    }

    protected function gatekeeperDay(Request $request): Carbon
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date'],
        ]);

        return isset($validated['date'])
            ? Carbon::parse($validated['date'])->startOfDay()
            : now()->startOfDay();
    }

    protected function gatekeeperBaseQuery()
    {
        return TripTicket::query()->with($this->gatekeeperRelations());
    }

    protected function gatekeeperRelations(): array
    {
        return [
            'requester:id,full_name',
            'department:id,name',
            'section:id,name',
            'vehicle:id,plate_number,description',
            'driver:id,name',
            'encoder:id,full_name',
            'approver:id,full_name',
            'departureRecorder:id,full_name',
            'returnRecorder:id,full_name',
        ];
    }

    protected function scheduledOnDay($query, Carbon $day): void
    {
        $start = $day->copy()->startOfDay()->format('Y-m-d H:i:s');
        $end = $day->copy()->endOfDay()->format('Y-m-d H:i:s');

        $query->whereRaw('COALESCE(actual_departure_datetime, requested_start_datetime) <= ?', [$end])
            ->whereRaw('COALESCE(actual_return_datetime, requested_end_datetime) >= ?', [$start]);
    }

    protected function paginatedTickets($tickets): JsonResponse
    {
        return response()->json([
            'data' => $tickets->getCollection()
                ->map(fn (TripTicket $ticket): array => $this->ticketPayload($ticket))
                ->values(),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'per_page' => $tickets->perPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    protected function ticketPayload(TripTicket $ticket): array
    {
        $vehicleDetails = $ticket->vehicle_details
            ?: ($ticket->vehicle ? trim($ticket->vehicle->plate_number . ' - ' . (string) $ticket->vehicle->description, ' -') : null);
        $driverName = $ticket->driver_name ?: $ticket->driver?->name;

        return [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'qr_token' => $ticket->qr_token,
            'status' => $ticket->status,
            'destination' => $ticket->destination,
            'purpose' => $ticket->purpose,
            'passengers' => $ticket->passengers,
            'contact_number' => $ticket->contact_number,
            'vehicle_details' => $vehicleDetails,
            'vehicle_plate_number' => $ticket->vehicle?->plate_number,
            'driver_name' => $driverName,
            'requested_start_datetime' => optional($ticket->requested_start_datetime)?->toIso8601String(),
            'requested_end_datetime' => optional($ticket->requested_end_datetime)?->toIso8601String(),
            'actual_departure_datetime' => optional($ticket->actual_departure_datetime)?->toIso8601String(),
            'actual_return_datetime' => optional($ticket->actual_return_datetime)?->toIso8601String(),
            'departure_recorded_at' => optional($ticket->departure_recorded_at)?->toIso8601String(),
            'return_recorded_at' => optional($ticket->return_recorded_at)?->toIso8601String(),
            'gatekeeper_departure_remarks' => $ticket->gatekeeper_departure_remarks,
            'gatekeeper_return_remarks' => $ticket->gatekeeper_return_remarks,
            'can_record_departure' => $ticket->status === TripTicket::STATUS_APPROVED && !$ticket->actual_departure_datetime,
            'can_record_return' => $ticket->status === TripTicket::STATUS_DISPATCHED && $ticket->actual_departure_datetime && !$ticket->actual_return_datetime,
            'requester' => $ticket->requester ? [
                'id' => $ticket->requester->id,
                'full_name' => $ticket->requester->full_name,
            ] : null,
            'department' => $ticket->department ? [
                'id' => $ticket->department->id,
                'name' => $ticket->department->name,
            ] : null,
            'section' => $ticket->section ? [
                'id' => $ticket->section->id,
                'name' => $ticket->section->name,
            ] : null,
            'encoder' => $ticket->encoder ? [
                'id' => $ticket->encoder->id,
                'full_name' => $ticket->encoder->full_name,
            ] : null,
            'approver' => $ticket->approver ? [
                'id' => $ticket->approver->id,
                'full_name' => $ticket->approver->full_name,
            ] : null,
            'departure_recorder' => $ticket->departureRecorder ? [
                'id' => $ticket->departureRecorder->id,
                'full_name' => $ticket->departureRecorder->full_name,
            ] : null,
            'return_recorder' => $ticket->returnRecorder ? [
                'id' => $ticket->returnRecorder->id,
                'full_name' => $ticket->returnRecorder->full_name,
            ] : null,
        ];
    }

    protected function normalizeQrToken(string $token): string
    {
        $token = trim($token);

        if (str_starts_with(strtoupper($token), 'TT:')) {
            $token = substr($token, 3);
        }

        return strtolower(trim($token));
    }
}
