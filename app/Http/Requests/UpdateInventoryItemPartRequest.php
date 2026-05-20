<?php

namespace App\Http\Requests;

use App\Models\InventoryItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemPartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->canManageInventory();
    }

    public function rules(): array
    {
        /** @var \App\Models\InventoryItemPart $inventoryItemPart */
        $inventoryItemPart = $this->route('inventoryItemPart');

        return [
            'part_name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', Rule::unique('inventory_item_parts', 'serial_number')->ignore($inventoryItemPart->id)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string'],
            'status' => ['nullable', Rule::in(InventoryItem::statuses())],
        ];
    }
}
