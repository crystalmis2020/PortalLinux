<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInventoryItemPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageInventory();
    }

    public function rules(): array
    {
        return [
            'part_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('inventory_item_parts', 'serial_number')],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(InventoryItem::statuses())],
            'replacement_reason' => ['nullable', 'string', 'max:255'],
        ];
    }
}
