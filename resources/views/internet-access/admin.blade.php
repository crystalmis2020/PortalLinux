@extends('layout.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <h4 class="mb-1">Internet Access Requests</h4>
        <p class="text-muted mb-0">Today's requests ({{ $today->format('Y-m-d') }}) · {{ $requests->total() }} records</p>
    </div>
    <a class="btn btn-outline-primary" href="{{ route('internet-access.index') }}">My Requests</a>
</div>

@if(session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="GET" action="{{ route('internet-access.admin.index') }}" class="mb-3">
            <label for="requestSearch" class="form-label">Search today's requests</label>
            <div class="d-flex flex-wrap gap-2">
                <input type="search" id="requestSearch" name="search" value="{{ $search }}"
                       class="form-control flex-grow-1 w-auto" placeholder="Name, IP, or purpose" maxlength="255">
                <button type="submit" class="btn btn-primary">Search</button>
                @if($search !== '')
                    <a href="{{ route('internet-access.admin.index') }}" class="btn btn-outline-secondary">Clear</a>
                @endif
            </div>
            @error('search')
                <div class="text-danger mt-1">{{ $message }}</div>
            @enderror
        </form>
        <div class="table-responsive">
            <table class="table table-bordered align-middle mb-0">
                <thead>
                    <tr><th>Name</th><th>IP</th><th>Purpose</th><th>Time</th><th>Action</th></tr>
                </thead>
                <tbody>
                    @forelse($requests as $item)
                        <tr>
                            <td>{{ $item->user?->full_name ?: $item->user?->username ?: 'Unknown user' }}</td>
                            <td class="text-nowrap">{{ $item->requester_ip ?: '—' }}</td>
                            <td class="text-break" style="white-space: pre-wrap">{{ $item->purpose }}</td>
                            <td class="text-nowrap">
                                <div>{{ $item->duration_minutes / 60 }} {{ \Illuminate\Support\Str::plural('hour', $item->duration_minutes / 60) }}</div>
                                <small class="text-muted">{{ $item->created_at?->format('Y-m-d H:i') }}</small>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('internet-access.admin.destroy', $item) }}"
                                      onsubmit="return confirm('Delete this request permanently? Any remaining router credentials will also be removed.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete record</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">{{ $search !== '' ? 'No matching requests today.' : 'No internet access requests today.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $requests->links('pagination::bootstrap-5') }}</div>
    </div>
</div>
@endsection
