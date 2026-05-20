<?php

namespace App\Http\Controllers;

use App\Models\InternetAccessRequest;
use App\Services\Mikrotik\RouterOsClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class InternetAccessRequestController extends Controller
{
    public function index(Request $request): View
    {
        $activeRequest = InternetAccessRequest::where('user_id', $request->user()->id)
            ->whereIn('status', [InternetAccessRequest::STATUS_READY, InternetAccessRequest::STATUS_ACTIVE])
            ->latest()
            ->first();

        $requests = InternetAccessRequest::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('internet-access.index', compact('activeRequest', 'requests'));
    }

    public function store(Request $request, RouterOsClient $mikrotik): RedirectResponse
    {
        $validated = $request->validate([
            'requested_hours' => ['required', 'in:1h,2h,3h,8h'],
            'purpose' => ['required', 'string', 'max:1000'],
        ]);

        $existingRequest = InternetAccessRequest::where('user_id', $request->user()->id)
            ->whereIn('status', [InternetAccessRequest::STATUS_READY, InternetAccessRequest::STATUS_ACTIVE])
            ->latest()
            ->first();

        if ($existingRequest) {
            return redirect()
                ->route('internet-access.index')
                ->with('error', 'You already have an internet access request that is still open.');
        }

        $username = $this->generateUsername($validated['requested_hours']);
        $profile = config("mikrotik.profiles.{$validated['requested_hours']}");
        $duration = $this->durationMinutes($validated['requested_hours']);

        $internetRequest = InternetAccessRequest::create([
            'user_id' => $request->user()->id,
            'requester_ip' => $request->ip(),
            'purpose' => $validated['purpose'],
            'requested_hours' => $validated['requested_hours'],
            'duration_minutes' => $duration,
            'username' => $username,
            'password' => $username,
            'mikrotik_profile' => $profile,
            'status' => InternetAccessRequest::STATUS_READY,
        ]);

        try {
            $referenceId = $mikrotik->createTemporaryAccess(
                $username,
                $username,
                $profile,
                $this->buildComment($request, $validated['purpose'])
            );

            $internetRequest->update(['mikrotik_reference_id' => $referenceId]);

            sendIpMsgNotification(
                "Your internet access is ready. Username: {$username} Password: {$username}",
                $request->ip() ?: $request->user()->ip_address
            );

            return redirect()
                ->route('internet-access.index')
                ->with('success', 'Internet access created. Use the generated username and password to connect.');
        } catch (Throwable $exception) {
            report($exception);

            $internetRequest->update([
                'status' => InternetAccessRequest::STATUS_FAILED,
                'failure_reason' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('internet-access.index')
                ->with('error', 'Internet access was not created: '.$exception->getMessage());
        }
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
            '2h' => 120,
            '3h' => 180,
            '8h' => 480,
        };
    }

    protected function buildComment(Request $request, string $purpose): string
    {
        $user = $request->user();
        $name = $user->full_name ?: $user->username;

        return "{$name} ({$request->ip()}) purpose: {$purpose}";
    }
}
