<div class="table-responsive">
    <table class="table table-hover align-middle" id="inventoryReleasesTable" style="width:100%">
        <thead>
            <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Purpose</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($releases as $release)
                @php
                    $releasePayload = [
                        'id' => $release->id,
                        'date' => optional($release->released_at)->format('M d, Y h:i A') ?: '—',
                        'item_name' => $release->inventoryItem?->item_name ?: 'Deleted item',
                        'item_remarks' => $release->inventoryItem?->remarks ?: '—',
                        'quantity' => $release->quantity,
                        'department' => $release->department,
                        'location' => $release->location,
                        'purpose' => $release->purpose,
                        'remarks' => $release->remarks,
                        'released_to' => $release->released_to,
                        'released_by' => $release->releasedBy?->full_name ?: 'System',
                    ];
                @endphp
                <tr>
                    <td data-order="{{ optional($release->released_at)->timestamp ?: 0 }}">
                        {{ optional($release->released_at)->format('M d, Y h:i A') ?: '—' }}
                    </td>
                    <td>
                        <div class="inventory-code">{{ $release->inventoryItem?->item_name ?: 'Deleted item' }}</div>
                        <div class="text-muted small">{{ $release->inventoryItem?->remarks ?: '—' }}</div>
                    </td>
                    <td class="fw-semibold">{{ $release->quantity }}</td>
                    <td>
                        <div>{{ $release->purpose ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="d-flex gap-2">
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-secondary view-release-record"
                                data-release='@json($releasePayload)'
                                data-bs-toggle="tooltip"
                                data-bs-title="View"
                                aria-label="View release record">
                                <i class="bx bx-show"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary edit-release-record"
                                data-update-url="{{ route('inventory-items.releases.update', $release) }}"
                                data-release='@json($releasePayload)'
                                data-bs-toggle="tooltip"
                                data-bs-title="Edit"
                                aria-label="Edit release record">
                                <i class="bx bx-edit-alt"></i>
                            </button>
                            <button
                                type="button"
                                class="btn btn-sm btn-outline-danger delete-release-record"
                                data-delete-url="{{ route('inventory-items.releases.destroy', $release) }}"
                                data-item-name="{{ $release->inventoryItem?->item_name ?: 'this release record' }}"
                                data-quantity="{{ $release->quantity }}"
                                data-bs-toggle="tooltip"
                                data-bs-title="Delete"
                                aria-label="Delete release record">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
