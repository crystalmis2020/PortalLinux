<?php

namespace App\Http\Controllers;

use App\Models\InternetAccessRequest;
use App\Services\Mikrotik\RouterOsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InternetAccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $activeRequest = InternetAccessRequest::where('user_id', $request->user()->id)
            ->whereIn('status', [InternetAccessRequest::STATUS_PENDING, InternetAccessRequest::STATUS_READY, InternetAccessRequest::STATUS_ACTIVE])
            ->latest()
            ->first();

        $requests = InternetAccessRequest::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        $pendingRequests = $request->user()->isAdmin()
            ? InternetAccessRequest::with('user')->where('status', InternetAccessRequest::STATUS_PENDING)->oldest()->get()
            : collect();

        return view('internet-access.index', compact('activeRequest', 'requests', 'pendingRequests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'requested_hours' => ['required', 'in:1h,4h'],
            'purpose' => ['required', 'string', 'max:1000'],
        ]);

        $existingRequest = InternetAccessRequest::where('user_id', $request->user()->id)
            ->whereIn('status', [InternetAccessRequest::STATUS_PENDING, InternetAccessRequest::STATUS_READY, InternetAccessRequest::STATUS_ACTIVE])
            ->latest()
            ->first();

        if ($existingRequest) {
            return redirect()
                ->route('internet-access.index')
                ->with('error', 'You already have an internet access request that is still open.');
        }

        $username = $this->generateUsername($validated['requested_hours']);
        $password = Str::random(24);
        $profile = config("mikrotik.profiles.{$validated['requested_hours']}");
        $duration = $this->durationMinutes($validated['requested_hours']);

        InternetAccessRequest::create([
            'user_id' => $request->user()->id,
            'requester_ip' => $request->ip(),
            'purpose' => $validated['purpose'],
            'requested_hours' => $validated['requested_hours'],
            'duration_minutes' => $duration,
            'username' => $username,
            'password' => $password,
            'mikrotik_profile' => $profile,
            'status' => InternetAccessRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route('internet-access.index')
            ->with('success', 'Request submitted for approval. It will be approved automatically after one minute if an administrator does not act.');
    }

    public function status(Request $request, InternetAccessRequest $internetAccessRequest): JsonResponse
    {
        abort_unless($internetAccessRequest->user_id === $request->user()->id, 403);

        $internetAccessRequest->refresh();

        return response()->json([
            'status' => $internetAccessRequest->status,
            'connected_at' => optional($internetAccessRequest->connected_at)->toIso8601String(),
            'expires_at' => optional($internetAccessRequest->expires_at)->toIso8601String(),
            'expired_at' => optional($internetAccessRequest->expired_at)->toIso8601String(),
            'remaining_seconds' => $internetAccessRequest->remaining_seconds,
            'last_seen_online_at' => optional($internetAccessRequest->last_seen_online_at)->toIso8601String(),
            'failure_reason' => $internetAccessRequest->failure_reason,
        ]);
    }

    public function approve(Request $request, InternetAccessRequest $internetAccessRequest, RouterOsClient $mikrotik): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $this->provision($internetAccessRequest, $mikrotik);

        return redirect()->route('internet-access.index')->with('success', 'Internet access request approved.');
    }

    public function provision(InternetAccessRequest $internetRequest, RouterOsClient $mikrotik): void
    {
        $provisioningException = null;

        DB::transaction(function () use ($internetRequest, $mikrotik, &$provisioningException): void {
            $lockedRequest = InternetAccessRequest::query()
                ->lockForUpdate()
                ->find($internetRequest->id);

            if (! $lockedRequest || $lockedRequest->status !== InternetAccessRequest::STATUS_PENDING) {
                return;
            }

            try {
                $referenceId = $mikrotik->createTemporaryAccess(
                    $lockedRequest->username,
                    $lockedRequest->password,
                    $lockedRequest->mikrotik_profile,
                    $this->buildCommentFor($lockedRequest)
                );

                $lockedRequest->update([
                    'status' => InternetAccessRequest::STATUS_READY,
                    'mikrotik_reference_id' => $referenceId,
                    'failure_reason' => null,
                ]);
            } catch (\Throwable $exception) {
                $lockedRequest->update([
                    'status' => InternetAccessRequest::STATUS_FAILED,
                    'failure_reason' => $exception->getMessage(),
                ]);
                $provisioningException = $exception;
            }
        }, 3);

        if ($provisioningException) {
            report($provisioningException);
            throw $provisioningException;
        }
    }

    protected function generateUsername(string $requestedHours): string
    {
        do {
            $username = $requestedHours.Str::random(7);
        } while (InternetAccessRequest::where('username', $username)->exists());

        return $username;
    }

    protected function durationMinutes(string $requestedHours): int
    {
        return match ($requestedHours) {
            '1h' => 60,
            '4h' => 240,
        };
    }

    protected function buildCommentFor(InternetAccessRequest $internetRequest): string
    {
        $user = $internetRequest->user;
        $name = $user?->full_name ?: $user?->username ?: 'Unknown user';

        return "{$name} ({$internetRequest->requester_ip}) purpose: {$internetRequest->purpose}";
    }
}
