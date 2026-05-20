<div class="table-responsive">
    <table class="table table-hover align-middle" id="inventoryHistoryTable" style="width:100%">
        <thead>
            <tr>
                <th>Event</th>
                <th>Component</th>
                <th>Change</th>
                <th>Notes</th>
                <th>Performed By</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($historyEntries as $history)
                <tr>
                    <td data-order="{{ optional($history->action_date)->timestamp ?: 0 }}">
                        <div>@include('inventory-items.partials.status-badge', ['status' => $history->action_type])</div>
                        <div class="text-muted small mt-1">{{ optional($history->action_date)->format('M d, Y h:i A') ?: '—' }}</div>
                    </td>
                    <td>{{ $history->part_name }}</td>
                    <td>
                        <div class="small">Old: {{ $history->oldPart?->serial_number ?: $history->oldPart?->part_name ?: '—' }}</div>
                        <div class="text-muted small">New: {{ $history->newPart?->serial_number ?: $history->newPart?->part_name ?: '—' }}</div>
                    </td>
                    <td>
                        <div>{{ $history->reason ?: '—' }}</div>
                        @if ($history->remarks)
                            <div class="text-muted small mt-1">{{ $history->remarks }}</div>
                        @endif
                    </td>
                    <td>{{ $history->performedBy?->full_name ?: 'System' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
