<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MarkInventoryItemPartDamagedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageInventory();
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
