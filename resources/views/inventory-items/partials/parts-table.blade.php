<div
    class="table-responsive inventory-parts-table"
    data-total-count="{{ $parts->count() }}"
    data-active-count="{{ $activePartsCount ?? $parts->count() }}"
    data-damaged-count="{{ $damagedPartsCount ?? 0 }}"
>
    <table class="table table-hover align-middle" id="inventoryPartsTable" style="width:100%">
        <thead>
            <tr>
                <th>Component</th>
                <th>Asset Info</th>
                <th>Status</th>
                <th>Activity</th>
                <th>Remarks</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($parts as $part)
                @php
                    $canMarkDamaged = $part->status === \App\Models\InventoryItem::STATUS_ACTIVE;
                    $canReplace = !in_array($part->status, [
                        \App\Models\InventoryItem::STATUS_INACTIVE,
                        \App\Models\InventoryItem::STATUS_REPLACED,
                    ], true);
                @endphp
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $part->part_name }}</div>
                        <div class="text-muted small">Installed {{ optional($part->installed_at)->format('M d, Y') ?: '—' }}</div>
                    </td>
                    <td>
                        <div>{{ $part->serial_number ?: 'No serial number' }}</div>
                        <div class="text-muted small">{{ collect([$part->brand, $part->model])->filter()->join(' · ') ?: 'No brand/model' }}</div>
                    </td>
                    <td>@include('inventory-items.partials.status-badge', ['status' => $part->status])</td>
                    <td>
                        <div class="small">{{ $damageCounts[$part->part_name] ?? 0 }} damage records</div>
                        <div class="text-muted small">{{ $replacementCounts[$part->part_name] ?? 0 }} replacements</div>
                    </td>
                    <td>{{ $part->remarks ?: '—' }}</td>
                    <td>
                        <div class="d-flex flex-wrap gap-1">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary edit-inventory-part"
                                data-update-url="{{ route('inventory-items.parts.update', [$inventoryItem, $part]) }}"
                                data-part='@json($part)'
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Edit component">
                                <i class="bx bx-edit-alt"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger mark-damaged-part"
                                data-damage-url="{{ route('inventory-items.parts.damage', [$inventoryItem, $part]) }}"
                                data-part-name="{{ $part->part_name }}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Mark damaged"
                                @disabled(! $canMarkDamaged)>
                                <i class="bx bx-error-circle"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary replace-inventory-part"
                                data-replace-url="{{ route('inventory-items.parts.replace', [$inventoryItem, $part]) }}"
                                data-part='@json($part)'
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Replace component"
                                @disabled(! $canReplace)>
                                <i class="bx bx-transfer-alt"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-dark view-part-history"
                                data-history-url="{{ route('inventory-items.history', $inventoryItem) }}?part_name={{ urlencode($part->part_name) }}"
                                data-part-name="{{ $part->part_name }}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="View history">
                                <i class="bx bx-history"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger delete-inventory-part"
                                data-delete-url="{{ route('inventory-items.parts.destroy', [$inventoryItem, $part]) }}"
                                data-part-name="{{ $part->part_name }}"
                                data-bs-toggle="tooltip"
                                data-bs-placement="top"
                                title="Delete component">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
