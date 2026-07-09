<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TripTicket;
use App\Models\TripTicketLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripTicketApprovalController extends Controller
{
    public function forApproval(Request $request): JsonResponse
    {
        $this->ensureApprover($request);

        $tickets = TripTicket::query()
            ->with(['requester:id,full_name', 'department:id,name', 'section:id,name', 'vehicle:id,plate_number,description', 'driver:id,name', 'encoder:id,full_name'])
            ->where('status', TripTicket::STATUS_FOR_APPROVAL)
            ->latest('encoded_at')
            ->latest('id')
            ->paginate(20);

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

    public function show(Request $request, TripTicket $tripTicket): JsonResponse
    {
        $this->ensureApprover($request);

        $tripTicket->load([
            'requester:id,full_name',
            'department:id,name',
            'section:id,name',
            'vehicle:id,plate_number,description',
            'driver:id,name',
            'encoder:id,full_name',
            'approver:id,full_name',
            'logs.user:id,full_name',
        ]);

        return response()->json([
            'ticket' => $this->ticketPayload($tripTicket, includeLogs: true),
            'can_approve' => $tripTicket->status === TripTicket::STATUS_FOR_APPROVAL,
        ]);
    }

    public function approve(Request $request, TripTicket $tripTicket): JsonResponse
    {
        $this->ensureApprover($request);
        $payload = $this->approvalPayload($request);

        return $this->transition(
            $request,
            $tripTicket,
            TripTicket::STATUS_APPROVED,
            'approved',
            $payload['approval_remarks'] ?? null
        );
    }

    public function reject(Request $request, TripTicket $tripTicket): JsonResponse
    {
        $this->ensureApprover($request);
        $payload = $this->approvalPayload($request);

        return $this->transition(
            $request,
            $tripTicket,
            TripTicket::STATUS_REJECTED,
            'rejected',
            $payload['approval_remarks'] ?? null
        );
    }

    public function returnForCorrection(Request $request, TripTicket $tripTicket): JsonResponse
    {
        $this->ensureApprover($request);
        $payload = $this->approvalPayload($request);

        return $this->transition(
            $request,
            $tripTicket,
            TripTicket::STATUS_RETURNED,
            'returned',
            $payload['approval_remarks'] ?? null
        );
    }

    protected function transition(
        Request $request,
        TripTicket $tripTicket,
        string $toStatus,
        string $action,
        ?string $remarks
    ): JsonResponse {
        $ticket = DB::transaction(function () use ($request, $tripTicket, $toStatus, $action, $remarks): TripTicket {
            $ticket = TripTicket::query()
                ->whereKey($tripTicket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ticket->status !== TripTicket::STATUS_FOR_APPROVAL) {
                abort(response()->json([
                    'message' => 'Only tickets submitted for approval can be updated.',
                    'status' => $ticket->status,
                ], 422));
            }

            $fromStatus = $ticket->status;
            $ticket->update([
                'status' => $toStatus,
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_remarks' => $remarks,
            ]);

            TripTicketLog::create([
                'trip_ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action' => $action,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'remarks' => $remarks,
            ]);

            return $ticket->fresh(['requester:id,full_name', 'department:id,name', 'section:id,name', 'vehicle:id,plate_number,description', 'driver:id,name', 'encoder:id,full_name', 'approver:id,full_name']);
        });

        return response()->json([
            'message' => 'Trip ticket ' . str_replace('_', ' ', $action) . ' successfully.',
            'ticket' => $this->ticketPayload($ticket),
        ]);
    }

    protected function approvalPayload(Request $request): array
    {
        return $request->validate([
            'approval_remarks' => ['nullable', 'string'],
        ]);
    }

    protected function ensureApprover(Request $request): void
    {
        abort_unless($request->user()?->canApproveTripTickets(), 403);
    }

    protected function ticketPayload(TripTicket $ticket, bool $includeLogs = false): array
    {
        $vehicleDetails = $ticket->vehicle_details
            ?: ($ticket->vehicle ? trim($ticket->vehicle->plate_number . ' - ' . (string) $ticket->vehicle->description) : null);

        $driverName = $ticket->driver_name ?: $ticket->driver?->name;

        $payload = [
            'id' => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'status' => $ticket->status,
            'destination' => $ticket->destination,
            'purpose' => $ticket->purpose,
            'passengers' => $ticket->passengers,
            'contact_number' => $ticket->contact_number,
            'remarks' => $ticket->remarks,
            'vehicle_details' => $vehicleDetails,
            'driver_name' => $driverName,
            'requested_start_datetime' => optional($ticket->requested_start_datetime)?->toDateString(),
            'requested_end_datetime' => optional($ticket->requested_end_datetime)?->toDateString(),
            'actual_departure_datetime' => optional($ticket->actual_departure_datetime)?->toDateString(),
            'actual_return_datetime' => optional($ticket->actual_return_datetime)?->toDateString(),
            'encoded_at' => optional($ticket->encoded_at)?->toIso8601String(),
            'approved_at' => optional($ticket->approved_at)?->toIso8601String(),
            'approval_remarks' => $ticket->approval_remarks,
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
        ];

        if ($includeLogs) {
            $payload['logs'] = $ticket->logs
                ->sortByDesc('created_at')
                ->map(fn (TripTicketLog $log): array => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'from_status' => $log->from_status,
                    'to_status' => $log->to_status,
                    'remarks' => $log->remarks,
                    'created_at' => optional($log->created_at)?->toIso8601String(),
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'full_name' => $log->user->full_name,
                    ] : null,
                ])
                ->values();
        }

        return $payload;
    }
}
