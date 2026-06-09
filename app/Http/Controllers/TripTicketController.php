<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTripTicketRequest;
use App\Http\Requests\EncodeTripTicketRequest;
use App\Models\Department;
use App\Models\Section;
use App\Models\TripTicket;
use App\Models\TripTicketLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $ticketsQuery = TripTicket::query()
            ->with(['requester:id,full_name', 'department:id,name', 'section:id,name', 'vehicle:id,plate_number,description', 'driver:id,name'])
            ->latest()
            ->latest('id');

        if ($user->canManageTripTickets()) {
            // Managers can view the full trip ticket registry.
        } elseif ($user->canEncodeTripTickets()) {
            $ticketsQuery->where(function ($query) use ($user) {
                $query->where('requested_by', $user->id)
                    ->orWhereIn('status', [
                        TripTicket::STATUS_PENDING_DETAILS,
                        TripTicket::STATUS_RETURNED,
                    ]);
            });
        } else {
            $ticketsQuery->where('requested_by', $user->id);
        }

        if ($request->filled('status') && in_array($request->string('status')->toString(), TripTicket::statuses(), true)) {
            $ticketsQuery->where('status', $request->string('status')->toString());
        }

        $tickets = $ticketsQuery->paginate(15)->withQueryString();

        return view('trip-tickets.index', [
            'tickets' => $tickets,
            'statuses' => TripTicket::statuses(),
            'selectedStatus' => $request->string('status')->toString(),
        ]);
    }

    public function create(Request $request)
    {
        return view('trip-tickets.create', [
            'departments' => Department::query()->orderBy('name')->get(),
            'sections' => Section::query()->with('department')->orderBy('name')->get(),
            'requester' => $request->user()->loadMissing(['department', 'section']),
        ]);
    }

    public function store(StoreTripTicketRequest $request)
    {
        $user = $request->user();
        $payload = $request->validated();

        $ticket = DB::transaction(function () use ($payload, $user): TripTicket {
            $ticket = TripTicket::create([
                'requested_by' => $user->id,
                'department_id' => $user->department_id ?: ($payload['department_id'] ?? null),
                'section_id' => $user->section_id ?: ($payload['section_id'] ?? null),
                'purpose' => $payload['purpose'],
                'destination' => $payload['destination'],
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
            ->route('trip-tickets.show', $ticket)
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

        return view('trip-tickets.show', [
            'ticket' => $tripTicket,
            'canEncodeDetails' => $this->canEncodeDetails($request, $tripTicket),
        ]);
    }

    public function print(Request $request, TripTicket $tripTicket)
    {
        $this->ensureCanView($request, $tripTicket);

        abort_unless($tripTicket->status === TripTicket::STATUS_APPROVED, 403);

        $tripTicket->load([
            'requester:id,full_name',
            'department:id,name',
            'section:id,name',
            'vehicle:id,plate_number,description',
            'driver:id,name',
            'encoder:id,full_name',
            'approver:id,full_name',
        ]);

        return view('trip-tickets.print', [
            'ticket' => $tripTicket,
        ]);
    }

    public function encode(EncodeTripTicketRequest $request, TripTicket $tripTicket)
    {
        abort_unless($this->canEncodeDetails($request, $tripTicket), 403);

        $payload = $request->validated();
        $user = $request->user();

        DB::transaction(function () use ($tripTicket, $payload, $user): void {
            $ticket = TripTicket::query()
                ->whereKey($tripTicket->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $ticket->status;
            $ticketNumber = $payload['ticket_number'] ?? null;

            if (!$ticketNumber && !$ticket->ticket_number) {
                $ticketNumber = $this->nextTicketNumber($ticket);
            }

            $ticket->update([
                'ticket_number' => $ticketNumber ?: $ticket->ticket_number,
                'vehicle_id' => null,
                'vehicle_details' => $payload['vehicle_details'],
                'driver_id' => null,
                'driver_name' => $payload['driver_name'],
                'actual_departure_datetime' => $payload['actual_departure_datetime'] ?? null,
                'actual_return_datetime' => $payload['actual_return_datetime'] ?? null,
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
                    'vehicle_details' => $payload['vehicle_details'],
                    'driver_name' => $payload['driver_name'],
                ],
            ]);
        });

        return redirect()
            ->route('trip-tickets.show', $tripTicket)
            ->with('success', 'Trip ticket details encoded and submitted for approval.');
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
