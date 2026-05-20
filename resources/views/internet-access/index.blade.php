@extends('layout.app')

@section('css-custom')
<style>
    .internet-access-shell {
        max-width: 1180px;
    }

    .internet-access-code {
        font-size: 1.35rem;
        font-weight: 700;
        letter-spacing: 0;
    }

    .timer-display {
        font-size: 2.4rem;
        font-weight: 700;
        line-height: 1.1;
        letter-spacing: 0;
    }

    .status-dot {
        width: .65rem;
        height: .65rem;
        border-radius: 50%;
        display: inline-block;
    }
</style>
@endsection

@section('content')
<div class="internet-access-shell">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Internet Access Request</h4>
            <p class="text-muted mb-0">Self-service temporary MikroTik access for portal users.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">New Request</h5>
                </div>
                <div class="card-body">
                    @if($activeRequest)
                        <div class="alert alert-info mb-0">
                            You already have an open internet access request. It must expire before creating another one.
                        </div>
                    @else
                        <form method="POST" action="{{ route('internet-access.store') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label">Requested Time</label>
                                <select name="requested_hours" class="form-select @error('requested_hours') is-invalid @enderror" required>
                                    <option value="">Select time</option>
                                    <option value="1h" @selected(old('requested_hours') === '1h')>1 hour</option>
                                    <option value="2h" @selected(old('requested_hours') === '2h')>2 hours</option>
                                    <option value="3h" @selected(old('requested_hours') === '3h')>3 hours</option>
                                    <option value="8h" @selected(old('requested_hours') === '8h')>8 hours</option>
                                </select>
                                @error('requested_hours')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Purpose</label>
                                <textarea name="purpose" rows="4" class="form-control @error('purpose') is-invalid @enderror" required>{{ old('purpose') }}</textarea>
                                @error('purpose')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Create Access</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Current Access</h5>
                    @if($activeRequest)
                        @php
                            $badgeClass = match($activeRequest->status) {
                                'ready' => 'bg-info',
                                'active' => 'bg-success',
                                'failed' => 'bg-danger',
                                default => 'bg-secondary',
                            };
                        @endphp
                        <span id="currentStatusBadge" class="badge {{ $badgeClass }}">{{ strtoupper($activeRequest->status) }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if($activeRequest)
                        <div id="activeAccessPanel" data-status-url="{{ route('internet-access.status', $activeRequest) }}">
                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Username</label>
                                    <div class="form-control internet-access-code">{{ $activeRequest->username }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Password</label>
                                    <div class="form-control internet-access-code">{{ $activeRequest->password }}</div>
                                </div>
                            </div>

                            <div class="row g-3 align-items-center">
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div class="text-muted mb-1">Time Remaining</div>
                                        <div id="timerDisplay" class="timer-display">--:--:--</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="border rounded p-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <span id="connectionDot" class="status-dot bg-secondary"></span>
                                            <strong id="connectionLabel">Waiting for first connection</strong>
                                        </div>
                                        <div class="small text-muted">
                                            Duration: {{ strtoupper($activeRequest->requested_hours) }} · Profile: {{ $activeRequest->mikrotik_profile }}
                                        </div>
                                        <div id="accessMeta" class="small text-muted mt-1"></div>
                                    </div>
                                </div>
                            </div>

                            @if($activeRequest->failure_reason)
                                <div class="alert alert-danger mt-3 mb-0">{{ $activeRequest->failure_reason }}</div>
                            @endif
                        </div>
                    @else
                        <div class="text-muted">No open internet access request.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h5 class="mb-0">Request History</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Created</th>
                            <th>Time</th>
                            <th>Username</th>
                            <th>Status</th>
                            <th>Connected</th>
                            <th>Expires</th>
                            <th>Purpose</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $item)
                            <tr>
                                <td>{{ optional($item->created_at)->format('Y-m-d H:i') }}</td>
                                <td>{{ strtoupper($item->requested_hours) }}</td>
                                <td>{{ $item->username }}</td>
                                <td>
                                    @php
                                        $rowBadge = match($item->status) {
                                            'ready' => 'bg-info',
                                            'active' => 'bg-success',
                                            'failed' => 'bg-danger',
                                            'expired' => 'bg-secondary',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <span class="badge {{ $rowBadge }}">{{ strtoupper($item->status) }}</span>
                                </td>
                                <td>{{ optional($item->connected_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>{{ optional($item->expires_at)->format('Y-m-d H:i') ?: '-' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($item->purpose, 80) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">No internet access requests yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $requests->links() }}
            </div>
        </div>
    </div>
</div>

@if($activeRequest)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('activeAccessPanel');
    const timerDisplay = document.getElementById('timerDisplay');
    const statusBadge = document.getElementById('currentStatusBadge');
    const connectionDot = document.getElementById('connectionDot');
    const connectionLabel = document.getElementById('connectionLabel');
    const accessMeta = document.getElementById('accessMeta');

    let expiresAt = @json(optional($activeRequest->expires_at)->toIso8601String());
    let currentStatus = @json($activeRequest->status);

    function formatSeconds(totalSeconds) {
        const seconds = Math.max(0, Number(totalSeconds || 0));
        const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const remainder = String(seconds % 60).padStart(2, '0');

        return `${hours}:${minutes}:${remainder}`;
    }

    function setBadge(status) {
        statusBadge.className = 'badge';

        if (status === 'active') {
            statusBadge.classList.add('bg-success');
        } else if (status === 'ready') {
            statusBadge.classList.add('bg-info');
        } else if (status === 'failed') {
            statusBadge.classList.add('bg-danger');
        } else {
            statusBadge.classList.add('bg-secondary');
        }

        statusBadge.textContent = status.toUpperCase();
    }

    function paintStatus(payload) {
        currentStatus = payload.status;
        expiresAt = payload.expires_at;
        setBadge(currentStatus);

        connectionDot.className = 'status-dot';

        if (currentStatus === 'active') {
            connectionDot.classList.add('bg-success');
            connectionLabel.textContent = 'Connected. Countdown is running.';
            accessMeta.textContent = payload.expires_at ? `Expires at ${new Date(payload.expires_at).toLocaleString()}` : '';
        } else if (currentStatus === 'ready') {
            connectionDot.classList.add('bg-info');
            connectionLabel.textContent = 'Waiting for first connection';
            accessMeta.textContent = 'The countdown starts after MikroTik detects a successful login.';
            timerDisplay.textContent = '--:--:--';
        } else if (currentStatus === 'failed') {
            connectionDot.classList.add('bg-danger');
            connectionLabel.textContent = 'Creation failed';
            accessMeta.textContent = payload.failure_reason || '';
            timerDisplay.textContent = '--:--:--';
        } else {
            connectionDot.classList.add('bg-secondary');
            connectionLabel.textContent = 'Access expired';
            accessMeta.textContent = payload.expired_at ? `Expired at ${new Date(payload.expired_at).toLocaleString()}` : '';
            timerDisplay.textContent = '00:00:00';
        }
    }

    function tick() {
        if (currentStatus !== 'active' || !expiresAt) {
            return;
        }

        const remaining = Math.floor((new Date(expiresAt).getTime() - Date.now()) / 1000);
        timerDisplay.textContent = formatSeconds(remaining);

        if (remaining <= 0) {
            currentStatus = 'expired';
            connectionLabel.textContent = 'Access expired';
        }
    }

    async function pollStatus() {
        try {
            const response = await fetch(panel.dataset.statusUrl, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            paintStatus(await response.json());
            tick();
        } catch (error) {
            console.error(error);
        }
    }

    pollStatus();
    setInterval(tick, 1000);
    setInterval(pollStatus, 10000);
});
</script>
@endif
@endsection
