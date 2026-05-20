<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'string'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        if (!$this->passwordMatchesStoredHash((string) $request->user()->password, (string) $validated['current_password'])) {
            $exception = ValidationException::withMessages([
                'current_password' => __('auth.password'),
            ]);
            $exception->errorBag = 'updatePassword';

            throw $exception;
        }

        $request->user()->update([
            'password' => hash('sha256', $validated['password']),
        ]);

        return back()->with('status', 'password-updated');
    }

    protected function passwordMatchesStoredHash(string $storedHash, string $plainPassword): bool
    {
        if (hash_equals($storedHash, hash('sha256', $plainPassword))) {
            return true;
        }

        if (!$this->isLaravelPasswordHash($storedHash)) {
            return false;
        }

        try {
            return Hash::check($plainPassword, $storedHash);
        } catch (\Throwable) {
            return false;
        }
    }

    protected function isLaravelPasswordHash(string $storedHash): bool
    {
        return preg_match('/^\$(2y|2a|2b|argon2i|argon2id)\$/', $storedHash) === 1;
    }
}
