<div class="table-responsive">
    <table class="table table-hover align-middle" id="inventoryItemsTable" style="width:100%">
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Stored Qty</th>
                <th>Assigned To</th>
                <th>Remarks</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td>{{ $item->item_name }}</td>
                    <td>
                        <span class="fw-semibold {{ ($item->stock_quantity ?? 0) > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $supportsStockQuantity ? ($item->stock_quantity ?? 0) : '—' }}
                        </span>
                    </td>
                    <td>{{ $item->assigned_to ?: '—' }}</td>
                    <td>{{ $item->remarks ?: '—' }}</td>
                    <td>
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary edit-inventory-item"
                                data-id="{{ $item->id }}"
                                data-update-url="{{ route('inventory-items.update', $item) }}"
                                data-item='@json($item)'
                                data-bs-toggle="tooltip"
                                data-bs-title="Edit"
                                aria-label="Edit inventory item">
                                <i class="bx bx-edit-alt"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-success release-inventory-item"
                                data-release-url="{{ route('inventory-items.release', $item) }}"
                                data-item='@json($item)'
                                data-bs-toggle="tooltip"
                                data-bs-title="Release"
                                aria-label="Release inventory item"
                                {{ (!$supportsStockQuantity || ($item->stock_quantity ?? 0) < 1) ? 'disabled' : '' }}>
                                <i class="bx bx-log-out-circle"></i>
                            </button>
                            <a
                                href="{{ route('inventory-items.show', $item) }}"
                                class="btn btn-sm btn-outline-secondary"
                                data-bs-toggle="tooltip"
                                data-bs-title="View components"
                                aria-label="View components">
                                <i class="bx bx-show"></i>
                            </a>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-info view-inventory-item-history"
                                data-item='@json($item)'
                                data-bs-toggle="tooltip"
                                data-bs-title="History"
                                aria-label="View inventory item history">
                                <i class="bx bx-history"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger delete-inventory-item"
                                data-delete-url="{{ route('inventory-items.destroy', $item) }}"
                                data-item-name="{{ $item->item_code }} - {{ $item->item_name }}"
                                data-bs-toggle="tooltip"
                                data-bs-title="Delete"
                                aria-label="Delete inventory item">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
