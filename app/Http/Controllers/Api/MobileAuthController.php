<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MobileAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()
            ->where('username', $credentials['username'])
            ->first();

        if (!$user || !$this->passwordMatchesStoredHash((string) $user->password, $credentials['password'])) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'This account is inactive.',
            ], 403);
        }

        $token = $user->createToken($credentials['device_name'] ?? 'flutter-mobile')->plainTextToken;

        User::withoutEvents(function () use ($user): void {
            $user->forceFill([
                'is_login' => 'Yes',
                'last_login' => now(),
                'last_seen_at' => now(),
            ])->save();
        });

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    protected function userPayload(User $user): array
    {
        $user->loadMissing(['department:id,name', 'section:id,name']);

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'username' => $user->username,
            'department' => $user->department?->name,
            'section' => $user->section?->name,
            'permissions' => [
                'can_encode_trip_tickets' => $user->canEncodeTripTickets(),
                'can_approve_trip_tickets' => $user->canApproveTripTickets(),
                'can_manage_trip_tickets' => $user->canManageTripTickets(),
                'can_gatekeep_trip_tickets' => $user->canGatekeepTripTickets(),
                'can_print_trip_tickets' => $user->canPrintTripTickets(),
            ],
        ];
    }

    protected function passwordMatchesStoredHash(string $storedHash, string $plainPassword): bool
    {
        if (hash_equals($storedHash, hash('sha256', $plainPassword))) {
            return true;
        }

        if (!preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $storedHash)) {
            return false;
        }

        return Hash::check($plainPassword, $storedHash);
    }
}
