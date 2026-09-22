@extends('layout.app')

@section('css-custom')
<style>
    .internet-access-shell {
        max-width: 1180px;
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

    .connector-protocol-frame-host {
        position: fixed;
        top: -10000px;
        left: -10000px;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }
</style>
@endsection

@section('content')
<div class="internet-access-shell">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h4 class="mb-1">Internet Access Request</h4>
            <p class="text-muted mb-0">Temporary MikroTik access with immediate automatic approval.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if(auth()->user()->isAdmin())
                <a class="btn btn-sm btn-outline-primary" href="{{ route('internet-access.admin.index') }}">Today's Requests</a>
            @endif
            <button
                id="pwaInstallButton"
                type="button"
                class="btn btn-sm btn-primary d-none"
                data-pwa-install
                aria-hidden="true"
                disabled
            >
                <i class="bx bx-desktop me-1"></i>Install Portal App
            </button>
            @if(config('mikrotik.connector.enabled'))
                <a id="connectorInstallerButton" class="btn btn-sm btn-outline-primary" href="{{ route('internet-access.connector.download') }}">
                    <i class="bx bx-download me-1"></i>Install Windows Connector
                </a>
            @endif
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
                                    <option value="4h" @selected(old('requested_hours') === '4h')>4 hours</option>
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

                            <button type="submit" class="btn btn-primary">Send Request</button>
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
                                'pending' => 'bg-warning text-dark',
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
                        <div
                            id="activeAccessPanel"
                            data-status-url="{{ route('internet-access.status', $activeRequest) }}"
                            data-token-url="{{ route('internet-access.connector.token', $activeRequest) }}"
                            data-connector-enabled="{{ config('mikrotik.connector.enabled') ? 'true' : 'false' }}"
                        >
                            <div id="approvalNotice" class="alert alert-warning d-none" role="status"></div>

                            <div id="connectorActions" class="border rounded p-3 mb-3 d-none">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                                    <div>
                                        <h6 class="mb-1">Your request is approved</h6>
                                        <div class="small text-muted">
                                            Connect using the saved Windows profile <strong>{{ config('mikrotik.connector.connection_name', 'Broadband Connection') }}</strong>.
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button id="connectButton" type="button" class="btn btn-success" disabled aria-disabled="true">
                                            <i class="bx bx-plug me-1"></i><span>Preparing connector...</span>
                                        </button>
                                        <button id="retryConnectorButton" type="button" class="btn btn-outline-success d-none">
                                            Prepare New Link
                                        </button>
                                    </div>
                                </div>
                                <div id="connectorFeedback" class="small text-muted mt-2" role="status" aria-live="polite"></div>
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

                            <div id="accessFailure" class="alert alert-danger mt-3 mb-0 {{ $activeRequest->failure_reason ? '' : 'd-none' }}">
                                {{ $activeRequest->failure_reason }}
                            </div>
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
                                <td>
                                    @php
                                        $rowBadge = match($item->status) {
                                            'pending' => 'bg-warning text-dark',
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
                                <td colspan="6" class="text-muted">No internet access requests yet.</td>
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

    @if(auth()->user()->isAdmin() && $pendingRequests->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h5 class="mb-0">Pending Administrator Approval</h5></div>
            <div class="card-body table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>User</th><th>Time</th><th>Purpose</th><th>Submitted</th><th></th></tr></thead>
                    <tbody>
                    @foreach($pendingRequests as $pending)
                        <tr>
                            <td>{{ $pending->user?->full_name ?: $pending->user?->username }}</td>
                            <td>{{ strtoupper($pending->requested_hours) }}</td>
                            <td>{{ $pending->purpose }}</td>
                            <td>{{ $pending->created_at->diffForHumans() }}</td>
                            <td>
                                <form method="POST" action="{{ route('internet-access.approve', $pending) }}">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

@if($activeRequest)
    <div class="modal fade" id="connectorLaunchModal" tabindex="-1" aria-labelledby="connectorLaunchModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="connectorLaunchModalLabel">Internet Connection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex align-items-start gap-3">
                        <div id="connectorLaunchSpinner" class="spinner-border spinner-border-sm text-primary mt-1" role="status">
                            <span class="visually-hidden">Opening connector...</span>
                        </div>
                        <i id="connectorLaunchSuccessIcon" class="bx bx-check-circle fs-3 text-success d-none" aria-hidden="true"></i>
                        <i id="connectorLaunchWarningIcon" class="bx bx-error-circle fs-3 text-warning d-none" aria-hidden="true"></i>
                        <div>
                            <h6 id="connectorLaunchTitle" class="mb-1">Opening Windows connector</h6>
                            <p id="connectorLaunchMessage" class="text-muted mb-0">
                                Please wait while the saved Broadband Connection is started.
                            </p>
                        </div>
                    </div>

                    <div id="connectorLaunchHelp" class="alert alert-warning mt-3 mb-0 d-none" role="alert">
                        If nothing opened, install the connector for this Windows user and try again. Use the installed Support Portal
                        app or a supported browser so Windows can open <code>supportportal-connect://</code> links.
                    </div>
                </div>
                <div class="modal-footer">
                    <a id="connectorModalInstallerButton" class="btn btn-outline-primary d-none" href="{{ route('internet-access.connector.download') }}">
                        <i class="bx bx-download me-1"></i>Install Connector
                    </a>
                    <button id="connectorModalRetryButton" type="button" class="btn btn-success d-none">
                        <i class="bx bx-refresh me-1"></i>Try Again
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="connectorProtocolFrameHost" class="connector-protocol-frame-host" aria-hidden="true"></div>
@endif

@if(config('mikrotik.connector.enabled'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const installerButton = document.getElementById('connectorInstallerButton');

    try {
        if (window.localStorage.getItem('supportPortalInternetConnectorInstalled') === '1') {
            installerButton.classList.add('d-none');
        }
    } catch (error) {
        // Storage can be disabled by browser policy. Keeping the installer
        // button visible is the safe fallback.
    }
});
</script>
@endif

@if($activeRequest)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const panel = document.getElementById('activeAccessPanel');
    const timerDisplay = document.getElementById('timerDisplay');
    const statusBadge = document.getElementById('currentStatusBadge');
    const connectionDot = document.getElementById('connectionDot');
    const connectionLabel = document.getElementById('connectionLabel');
    const accessMeta = document.getElementById('accessMeta');
    const approvalNotice = document.getElementById('approvalNotice');
    const connectorActions = document.getElementById('connectorActions');
    const connectButton = document.getElementById('connectButton');
    const connectButtonLabel = connectButton.querySelector('span');
    const retryConnectorButton = document.getElementById('retryConnectorButton');
    const connectorFeedback = document.getElementById('connectorFeedback');
    const installerButton = document.getElementById('connectorInstallerButton');
    const connectorLaunchModalElement = document.getElementById('connectorLaunchModal');
    const connectorLaunchSpinner = document.getElementById('connectorLaunchSpinner');
    const connectorLaunchSuccessIcon = document.getElementById('connectorLaunchSuccessIcon');
    const connectorLaunchWarningIcon = document.getElementById('connectorLaunchWarningIcon');
    const connectorLaunchTitle = document.getElementById('connectorLaunchTitle');
    const connectorLaunchMessage = document.getElementById('connectorLaunchMessage');
    const connectorLaunchHelp = document.getElementById('connectorLaunchHelp');
    const connectorModalInstallerButton = document.getElementById('connectorModalInstallerButton');
    const connectorModalRetryButton = document.getElementById('connectorModalRetryButton');
    const connectorProtocolFrameHost = document.getElementById('connectorProtocolFrameHost');
    const accessFailure = document.getElementById('accessFailure');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const connectorEnabled = panel.dataset.connectorEnabled === 'true';

    let expiresAt = @json(optional($activeRequest->expires_at)->toIso8601String());
    let currentStatus = @json($activeRequest->status);
    let launchExpiresAt = 0;
    let launchUri = '';
    let preparingLaunch = false;
    let launchRefreshTimer = null;
    let connectorLaunchFallbackTimer = null;
    let reconnecting = false;
    let launchInProgress = false;
    let fastPollTimer = null;

    const connectorLaunchModal = connectorLaunchModalElement && typeof bootstrap !== 'undefined'
        ? bootstrap.Modal.getOrCreateInstance(connectorLaunchModalElement)
        : null;

    function setConnectorModalState(state, title, message) {
        connectorLaunchSpinner.classList.toggle('d-none', state !== 'opening');
        connectorLaunchSuccessIcon.classList.toggle('d-none', state !== 'success');
        connectorLaunchWarningIcon.classList.toggle('d-none', state !== 'warning');
        connectorLaunchTitle.textContent = title;
        connectorLaunchMessage.textContent = message;
        connectorLaunchHelp.classList.toggle('d-none', state !== 'warning');
        connectorModalInstallerButton.classList.toggle('d-none', state !== 'warning');
        connectorModalRetryButton.classList.toggle('d-none', state !== 'warning');
    }

    function showConnectorLaunchModal() {
        if (connectorLaunchModal) {
            connectorLaunchModal.show();
        }
    }

    function isRunningAsPwa() {
        return Boolean(window.SupportPortalPwa?.isStandalone?.());
    }

    function launchConnectorInBackground() {
        if (!launchUri || launchExpiresAt <= Date.now()) {
            setConnectorModalState(
                'warning',
                'The connection link expired',
                'Close this window, prepare a new link, and click Connect again.'
            );
            retryConnectorButton.classList.remove('d-none');
            showConnectorLaunchModal();
            return;
        }

        setConnectorModalState(
            'opening',
            'Opening Windows connector',
            'Please wait while the saved Broadband Connection is started.'
        );
        reconnecting = currentStatus === 'active';
        launchInProgress = true;
        showConnectorLaunchModal();

        if (isRunningAsPwa()) {
            // A top-level launch preserves the user's click gesture so Edge/Chrome
            // can hand the registered custom protocol directly to Windows.
            window.location.assign(launchUri);
        } else {
            // Keep browser and legacy-launcher pages in place if their embedded
            // Chromium host cannot handle the external protocol.
            const protocolFrame = document.createElement('iframe');
            protocolFrame.setAttribute('title', 'Windows connector launcher');
            protocolFrame.setAttribute('aria-hidden', 'true');
            protocolFrame.src = launchUri;
            while (connectorProtocolFrameHost.firstChild) {
                connectorProtocolFrameHost.removeChild(connectorProtocolFrameHost.firstChild);
            }
            connectorProtocolFrameHost.appendChild(protocolFrame);

            window.setTimeout(function () {
                protocolFrame.remove();
            }, 5000);
        }

        launchUri = null;

        window.clearTimeout(connectorLaunchFallbackTimer);
        connectorLaunchFallbackTimer = window.setTimeout(function () {
            launchInProgress = false;
            if (!['ready', 'active'].includes(currentStatus)) {
                return;
            }

            setConnectorModalState(
                'warning',
                reconnecting ? 'Check your connection' : 'Connector did not respond yet',
                reconnecting ? 'Check the Windows connector for the reconnection result. Your original countdown continues.' : 'Windows has not confirmed the PPPoE connection. Check the connector prompt and try again.'
            );
            setConnectButton(false, 'Prepare a new connection link');
            retryConnectorButton.classList.remove('d-none');
        }, 10000);
    }

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
        } else if (status === 'pending') {
            statusBadge.classList.add('bg-warning', 'text-dark');
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
        approvalNotice.classList.add('d-none');
        connectorActions.classList.add('d-none');
        accessFailure.classList.add('d-none');

        if (currentStatus === 'pending') {
            retryConnectorButton.classList.add('d-none');
            connectionDot.classList.add('bg-warning');
            connectionLabel.textContent = 'Preparing internet access';
            accessMeta.textContent = 'Your request is being prepared automatically.';
            timerDisplay.textContent = '--:--:--';
            approvalNotice.textContent = 'Your request is being prepared automatically. If it remains pending, the system will retry shortly.';
            approvalNotice.classList.remove('d-none');
        } else if (currentStatus === 'active') {
            retryConnectorButton.classList.add('d-none');
            if (connectorLaunchTitle.textContent === 'Opening Windows connector' && !reconnecting) {
                window.clearTimeout(connectorLaunchFallbackTimer);
                launchInProgress = false;
                launchExpiresAt = 0;
                setConnectorModalState('success', 'Internet connected', 'Your internet access countdown has started.');
                rememberSuccessfulConnectorLaunch();
            }
            connectorActions.classList.remove('d-none');
            prepareConnectorLaunch();
            connectionDot.classList.add('bg-success');
            connectionLabel.textContent = 'Access time remaining. If disconnected, click Reconnect.';
            accessMeta.textContent = payload.expires_at ? `Expires at ${new Date(payload.expires_at).toLocaleString()}` : '';
        } else if (currentStatus === 'ready') {
            connectionDot.classList.add('bg-info');
            connectionLabel.textContent = 'Approved. Ready to connect.';
            accessMeta.textContent = 'The countdown starts after MikroTik confirms the PPPoE connection.';
            timerDisplay.textContent = '--:--:--';
            connectorActions.classList.remove('d-none');
            prepareConnectorLaunch();
        } else if (currentStatus === 'failed') {
            retryConnectorButton.classList.add('d-none');
            connectionDot.classList.add('bg-danger');
            connectionLabel.textContent = 'Creation failed';
            accessMeta.textContent = payload.failure_reason || '';
            timerDisplay.textContent = '--:--:--';
            accessFailure.textContent = payload.failure_reason || 'MikroTik could not create this access request.';
            accessFailure.classList.remove('d-none');
        } else {
            retryConnectorButton.classList.add('d-none');
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
            paintStatus({
                status: 'expired',
                expires_at: expiresAt,
                expired_at: new Date().toISOString(),
            });
        }
    }

    function setConnectButton(enabled, label) {
        connectButtonLabel.textContent = label;
        connectButton.disabled = !enabled;
        connectButton.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    }

    async function prepareConnectorLaunch(force) {
        if (!['ready', 'active'].includes(currentStatus) || preparingLaunch || launchInProgress) {
            return;
        }

        if (!connectorEnabled) {
            setConnectButton(false, 'Connector unavailable');
            connectorFeedback.textContent = 'The Windows connector is disabled. Please contact MIS.';
            return;
        }

        if (!force && launchExpiresAt > Date.now()) {
            if (!launchUri) {
                retryConnectorButton.classList.remove('d-none');
            }
            return;
        }

        preparingLaunch = true;
        retryConnectorButton.classList.add('d-none');
        setConnectButton(false, 'Preparing connector...');
        connectorFeedback.textContent = '';

        try {
            const response = await fetch(panel.dataset.tokenUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
            const payload = await response.json().catch(function () { return {}; });

            if (!response.ok) {
                throw new Error(payload.message || 'The connector could not be prepared.');
            }

            launchUri = payload.launch_uri;
            launchExpiresAt = new Date(payload.expires_at).getTime();
            setConnectButton(true, currentStatus === 'active' ? 'Reconnect' : 'Connect');

            window.clearTimeout(launchRefreshTimer);
            launchRefreshTimer = window.setTimeout(function () {
                prepareConnectorLaunch(true);
            }, Math.max(1000, launchExpiresAt - Date.now() + 1000));
        } catch (error) {
            setConnectButton(false, 'Connect unavailable');
            connectorFeedback.textContent = error.message;
        } finally {
            preparingLaunch = false;
        }
    }

    function startFastPolling() {
        window.clearInterval(fastPollTimer);
        fastPollTimer = window.setInterval(pollStatus, 2000);
        window.setTimeout(function () {
            window.clearInterval(fastPollTimer);
        }, 90000);
    }

    function rememberConnectorLaunchAttempt() {
        try {
            window.sessionStorage.setItem('supportPortalInternetConnectorLaunchAttemptedAt', String(Date.now()));
        } catch (error) {
            // The connection flow does not depend on browser storage.
        }
    }

    function rememberSuccessfulConnectorLaunch() {
        try {
            const attemptedAt = Number(window.sessionStorage.getItem('supportPortalInternetConnectorLaunchAttemptedAt'));
            const attemptIsRecent = Number.isFinite(attemptedAt) && attemptedAt > Date.now() - 300000;

            if (!attemptIsRecent) {
                return;
            }

            window.localStorage.setItem('supportPortalInternetConnectorInstalled', '1');
            window.sessionStorage.removeItem('supportPortalInternetConnectorLaunchAttemptedAt');
            installerButton?.classList.add('d-none');
        } catch (error) {
            // The connector is already working; failure to remember this UI
            // preference must not affect the connection.
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

    connectButton.addEventListener('click', function (event) {
        if (connectButton.disabled || !launchUri) {
            event.preventDefault();
            return;
        }

        connectorFeedback.textContent = 'Approve the browser prompt to open Support Portal Internet Connector.';
        rememberConnectorLaunchAttempt();
        window.clearTimeout(launchRefreshTimer);
        setConnectButton(false, 'Opening connector...');
        startFastPolling();
        launchConnectorInBackground();
    });

    connectorModalRetryButton.addEventListener('click', async function () {
        if (!launchUri || launchExpiresAt <= Date.now()) {
            setConnectorModalState(
                'opening',
                'Preparing a new connection link',
                'Please wait, then select Try Again once the link is ready.'
            );
            await prepareConnectorLaunch(true);

            if (!launchUri || launchExpiresAt <= Date.now()) {
                setConnectorModalState(
                    'warning',
                    'A new link could not be prepared',
                    'Close this window and use Prepare New Link before trying again.'
                );
                return;
            }

            setConnectorModalState(
                'warning',
                'New connection link ready',
                'Select Try Again to open the Windows connector.'
            );
            return;
        }

        rememberConnectorLaunchAttempt();
        setConnectButton(false, 'Opening connector...');
        startFastPolling();
        launchConnectorInBackground();
    });

    retryConnectorButton.addEventListener('click', function () {
        retryConnectorButton.classList.add('d-none');
        prepareConnectorLaunch(true);
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            pollStatus();
        }
    });

    paintStatus({
        status: currentStatus,
        expires_at: expiresAt,
        expired_at: @json(optional($activeRequest->expired_at)->toIso8601String()),
        failure_reason: @json($activeRequest->failure_reason),
    });
    pollStatus();
    setInterval(tick, 1000);
    setInterval(pollStatus, 10000);
});
</script>
@endif
@endsection
