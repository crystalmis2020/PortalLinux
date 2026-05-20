<div class="table-responsive">
    <table class="table table-hover align-middle" id="inventoryReleasesTable" style="width:100%">
        <thead>
            <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Department</th>
                <th>Location</th>
                <th>Purpose</th>
                <th>Released By</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($releases as $release)
                <tr>
                    <td data-order="{{ optional($release->released_at)->timestamp ?: 0 }}">
                        {{ optional($release->released_at)->format('M d, Y h:i A') ?: '—' }}
                    </td>
                    <td>
                        <div class="inventory-code">{{ $release->inventoryItem?->item_code ?: '—' }}</div>
                        <div class="text-muted small">{{ $release->inventoryItem?->item_name ?: 'Deleted item' }}</div>
                    </td>
                    <td class="fw-semibold">{{ $release->quantity }}</td>
                    <td>{{ $release->department ?: '—' }}</td>
                    <td>{{ $release->location ?: '—' }}</td>
                    <td>
                        <div>{{ $release->purpose ?: '—' }}</div>
                        @if ($release->remarks)
                            <div class="text-muted small mt-1">{{ $release->remarks }}</div>
                        @endif
                    </td>
                    <td>{{ $release->releasedBy?->full_name ?: 'System' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
