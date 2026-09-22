# Internet Access Request

This module gives authenticated Support Portal users temporary internet access
through the company's existing MikroTik PPPoE setup. It does not require a
change from PPPoE or any automatic firewall/Hotspot configuration.

## User Flow

1. The user selects **Internet Access** in the portal sidebar (route
   `/internet-access`).
2. The user selects one or four hours, enters a purpose, and submits a request.
3. Submission automatically approves and provisions the request immediately.
   The scheduler retries requests left pending for at least one minute.
4. Approval creates a temporary PPPoE secret in MikroTik and changes the
   request from `pending` to `ready`.
5. The page displays **Connect**. It never displays the PPPoE username or
   password.
6. The user accepts the browser prompt to open the installed Support Portal
   Internet Connector.
7. The connector redeems a short-lived token once, receives the temporary
   credentials over HTTPS, and dials the existing Windows RAS entry named
   `Broadband Connection` through the native `RasDialW` API.
8. MikroTik confirms that the PPPoE user is active. Only then does the request
   become `active` and the countdown begin.
9. At expiration, the scheduler removes the PPPoE secret and marks the request
   `expired`.

The Internet Access module no longer sends credentials through IPMsg. IPMsg
usage in other Support Portal modules is unchanged.

## Important Requirements

- MikroTik PPPoE and the profiles configured below must already work.
- The Laravel scheduler must run every minute.
- Each Windows client must already have a RAS/PPPoE entry named exactly
  `Broadband Connection`.
- Users install the connector once in their Windows profile.
- The portal must use HTTPS with a certificate trusted by every client.
- Windows 7 SP1 clients must have TLS 1.2/Schannel updates installed.

The connector supports Windows 7 SP1, Windows 10, and Windows 11. Runtime
validation on representative company PCs is required before broad deployment,
especially for Windows 7, proxy settings, antivirus/application-control rules,
and per-user versus all-user RAS phonebooks.

## Main Files

| Purpose | File |
| --- | --- |
| Request and approval flow | `app/Http/Controllers/InternetAccessRequestController.php` |
| Connector token and download endpoints | `app/Http/Controllers/InternetAccessConnectorController.php` |
| Request model | `app/Models/InternetAccessRequest.php` |
| One-time token model | `app/Models/InternetAccessConnectorToken.php` |
| RouterOS API client | `app/Services/Mikrotik/RouterOsClient.php` |
| Scheduler command | `app/Console/Commands/SyncInternetAccessRequests.php` |
| Browser page | `resources/views/internet-access/index.blade.php` |
| PWA manifest and service worker | `public/manifest.webmanifest`, `public/service-worker.js` |
| Windows connector source | `tools/internet-access-connector/` |
| MikroTik/connector configuration | `config/mikrotik.php` |

## Routes

All browser routes require the normal Laravel session authentication:

```text
GET  /internet-access
POST /internet-access
POST /internet-access/{request}/approve
GET  /internet-access/status/{request}
GET  /internet-access/connector/download
POST /internet-access/connector/{request}/token
```

The installed connector calls two token-authenticated API routes:

```text
POST /api/internet-access/connector/exchange
POST /api/internet-access/connector/verify
```

The API routes do not accept a portal session as connector authorization. They
require a random bearer token issued to the authenticated owner of a `ready`
request (or an active request with time remaining), and they are explicitly rate-limited.

## Environment Configuration

Use deployment-specific values in `.env`:

```env
MIKROTIK_ENABLED=true
MIKROTIK_HOST=
MIKROTIK_PORT=8728
MIKROTIK_USERNAME=
MIKROTIK_PASSWORD=
MIKROTIK_TIMEOUT=10
MIKROTIK_SERVICE=pppoe
MIKROTIK_PROFILE_1H=
MIKROTIK_PROFILE_4H=

MIKROTIK_CONNECTOR_ENABLED=true
MIKROTIK_CONNECTOR_PROTOCOL=supportportal-connect
MIKROTIK_CONNECTOR_CONNECTION_NAME="Broadband Connection"
MIKROTIK_CONNECTOR_TOKEN_TTL=120
MIKROTIK_CONNECTOR_VERIFICATION_WINDOW=300
MIKROTIK_CONNECTOR_BIND_TOKEN_TO_IP=true
MIKROTIK_CONNECTOR_REQUIRE_HTTPS=true
```

After updating `.env`, clear the cached configuration:

```bash
php artisan config:clear
```

`APP_URL` must be the canonical public HTTPS base URL, including `/support` or
any other deployment subdirectory. The generated connector package is bound to
this configured value rather than trusting the incoming HTTP `Host` header.

Keep `MIKROTIK_CONNECTOR_PROTOCOL` and
`MIKROTIK_CONNECTOR_CONNECTION_NAME` at the shown values for connector version
1.1.0. The API continues accepting version 1.0.0 during the certificate rollout.

`MIKROTIK_CONNECTOR_BIND_TOKEN_TO_IP=true` requires the browser request and the
connector's WinHTTP exchange to reach Laravel from the same source IP. If the
two use different proxy paths, the exchange will be rejected. Correct trusted
proxy handling first, or deliberately disable this additional binding after a
security review. Verification is not IP-bound because establishing PPPoE can
legitimately change the source IP; it returns no credentials.

Never disable the HTTPS requirement in production. The connector deliberately
rejects HTTP, redirects, untrusted certificates, and obsolete TLS fallback.

If TLS terminates at a reverse proxy, configure Laravel to trust only that
proxy's exact IP/CIDR and forwarded headers in `bootstrap/app.php`. Otherwise
Laravel will reject connector requests as insecure and source-IP token binding
may see the proxy address instead of the client. Do not trust all proxies.

## Database Setup

Deploy the migrations normally:

```bash
php artisan migrate
```

The connector migration creates `internet_access_connector_tokens`. Only a
SHA-256 hash of each random 256-bit token is stored. Tokens expire quickly and
can deliver credentials only once.

The credential migration changes the password column to `TEXT`, encrypts any
existing plaintext PPPoE passwords using Laravel's `APP_KEY`, and enables the
model's encrypted cast. Back up the database and confirm that the production
`APP_KEY` is stable before migration. Changing or losing `APP_KEY` afterward
will make existing encrypted credentials unreadable.

Deploy this migration in maintenance mode so no web worker tries to read a
legacy plaintext password after code with the encrypted model cast is live.

New requests use separate random usernames and passwords. Neither value is
rendered in the page, request history, custom protocol URL, or connector files.

## Scheduler

`routes/console.php` schedules:

```php
Schedule::command('internet-access:sync')
    ->everyMinute()
    ->withoutOverlapping();
```

The server must run Laravel's scheduler, for example:

```cron
* * * * * cd /path/to/support-portal && php artisan schedule:run >> /dev/null 2>&1
```

The command:

- recovers requests left pending for at least one minute;
- detects connected PPPoE users as a fallback to immediate connector
  verification;
- starts the timer only after router confirmation;
- removes expired PPPoE secrets; and
- deletes old connector-token records.

## Installing the Windows Connector

The Internet Access page creates a deployment-bound ZIP dynamically. The ZIP
contains `portal-url.txt`, which fixes the installed connector to the HTTPS
origin from which it was downloaded.

For each Windows user:

1. Click **Install Windows Connector**.
2. Extract the downloaded ZIP. Do not run `install.cmd` from inside the ZIP.
3. Double-click `install.cmd`.
4. Accept the Windows certificate trust prompt if one is shown, then confirm
   the success message showing the portal URL, `Broadband Connection`, and the
   trusted portal certificate.
5. Return to the portal and click **Connect** after approval.
6. Allow the browser to open **Support Portal Internet Connector**. The browser
   may offer an option to remember the choice.

The Connect action opens a portal modal and sends the custom protocol through
an isolated background frame. This keeps the Internet Access page visible when
an embedded Chromium desktop launcher reports an unknown-protocol error such as
`-302`. If the connector does not respond, the modal provides retry and install
actions instead of navigating the main portal page away.

A desktop launcher must still allow `supportportal-connect://` URLs to be
handed to Windows. If the launcher blocks every external protocol, its host
configuration must allow this scheme before the connector can start.

## Installing the Portal PWA

The PWA replaces a desktop launcher that blocks external protocol handoff. It
does not replace the Windows connector and never handles PPPoE credentials.

On Windows 10 or Windows 11:

1. Open `https://128.0.1.20/support/internet-access` in Microsoft Edge or
   Google Chrome after installing connector version 1.1.0 and trusting the
   Support Portal root certificate.
2. Select **Install Portal App** on the Internet Access page. The browser's
   address-bar install action can be used if the button is not visible.
3. Launch **CSCI Support Portal** from the Start menu or desktop shortcut and
   sign in normally.
4. Submit the request and select **Connect** after approval. In standalone PWA
   mode, the user click launches `supportportal-connect://` directly so Windows
   can hand it to the installed connector.

The PWA is scoped to `/support/` and starts at `/support/internet-access`.
Authenticated pages, API responses, request status, and connector tokens are
network-only. The service worker caches only versioned/static portal assets and
an offline explanation page, so it cannot serve stale approvals or credentials.

Windows 7 cannot be treated as a supported modern PWA target. Those clients
should continue using the normal supported-browser page and installed connector
after completing the required TLS 1.2 updates.

The installer button is always available at the top of the Internet Access
page, so users can complete this one-time setup before submitting a request.
Use a modern browser supported by the portal; Internet Explorer 11 is not
supported by the portal UI.

After the first connector-launched connection is confirmed, the page remembers
that installation in the current browser and hides the installer button. If a
later Connect attempt does not open the connector, the button appears again so
the user can reinstall it. Browser privacy/storage restrictions may keep the
button visible; this does not affect the connection flow.

Installation is per user and normally does not require administrator rights.
It copies the connector to:

```text
%LOCALAPPDATA%\SupportPortalConnector
```

It registers `supportportal-connect` beneath
`HKCU\Software\Classes`, so it does not change the PPPoE profile or system-wide
network configuration.

Version 1.1.0 also verifies and installs the dedicated public Support Portal
root certificate into the current user's Trusted Root store. The downloadable
package never contains the root CA private key or the Apache server private key.
The pinned root CA SHA-256 fingerprint is:

```text
20:FE:C5:EE:30:4B:6E:19:30:EE:8A:A7:D3:70:F6:E5:44:C3:B7:87:1C:F6:66:26:45:AA:9C:07:04:C8:75:09
```

The server certificate contains `IP:128.0.1.20` as a Subject Alternative
Name and is valid through December 14, 2028. The dedicated root CA is valid
through September 8, 2036. Track both dates for renewal.

To uninstall, run:

```text
%LOCALAPPDATA%\SupportPortalConnector\uninstall.cmd
```

The current package consists of PowerShell scripts and should be code-signed or
distributed with an organization-published hash before production rollout.
Some application-control policies or antivirus products may require MIS to
allow the connector. The package uses `ExecutionPolicy Bypass` only for its
fixed local scripts; PowerShell execution policy is not treated as a security
boundary.

## Status Meanings

- `pending`: provisioning has not completed; the scheduler recovers older pending requests.
- `ready`: the PPPoE secret exists and the Connect button is available.
- `active`: MikroTik confirmed the PPPoE session and the timer is running.
- `expired`: the allowed duration ended and the PPPoE secret was removed.
- `failed`: MikroTik provisioning failed.

Clicking Connect by itself never marks a request active. The connector's
verification endpoint queries MikroTik, and the minute scheduler provides a
fallback if immediate verification cannot reach the portal after Windows
changes routes.

## Token Security

The Connect button is prepared with a 43-character, unpadded base64url token
created from 32 random bytes. The custom URL contains only this token:

```text
supportportal-connect://connect?token=ONE_TIME_TOKEN
```

It never contains a request ID, portal URL, username, or password. The
connector accepts only that exact URL shape and sends the token to its fixed
installed portal origin. Credential responses are marked `no-store`.

The exchange is rejected when the token is malformed, unknown, expired,
already used, belongs to a request that is neither ready nor active with time remaining, or fails the
configured IP binding. All these cases return the same generic authentication
failure so the endpoint does not reveal token state.

## Test Checklist

Before production rollout:

1. Back up the database and run `php artisan migrate`.
2. Confirm the portal is HTTPS and trusted on Windows 7, 10, and 11.
3. Confirm the exact RAS entry is `Broadband Connection` on each test PC.
4. Install the connector from the page for a standard, non-admin Windows user.
5. Submit and approve a one-hour test request.
6. Confirm no PPPoE username/password appears in the page or IPMsg.
7. Click Connect and accept the browser protocol prompt.
8. Confirm Windows connects and the page changes from `ready` to `active`.
9. Confirm clicking an old/reused Connect URL cannot return credentials.
10. Confirm expiration disconnects future access by removing the PPPoE secret.
11. Test per-user and all-user phonebooks, the browsers used by the company,
    WinHTTP proxy behavior, antivirus, and Windows 7 TLS 1.2.

## Troubleshooting

### Connect button says HTTPS is required

Serve the portal over HTTPS, install a client-trusted certificate, correct the
public `APP_URL`, and clear Laravel's configuration cache. Do not bypass this in
production because the connector exchange carries temporary PPPoE credentials.

### Browser says no application can open the link

Download the connector ZIP, extract it, and run `install.cmd` as the same
Windows user who uses the portal. Reinstall if the HKCU protocol registration
was removed.

### Connector cannot find Broadband Connection

Open Windows Network Connections and verify the existing entry is named
exactly `Broadband Connection`. The connector checks the default, per-user, and
all-user RAS phonebooks.

### Token is invalid or expired

Return to the page and click Connect again. Each prepared token is short-lived
and returns credentials only once. Check source-IP/proxy differences when this
happens consistently.

### Windows reports RAS error 691

MikroTik rejected the generated username/password. Check that the request is
still `ready`, the PPPoE secret exists, and the profile/service configuration
is correct.

### Windows connects but the page remains ready

The immediate verification request may have lost the route to the portal after
PPPoE connected. Confirm the scheduler is running and manually test:

```bash
php artisan internet-access:sync
```

Also confirm the portal server can reach the MikroTik API and that the user is
visible under `/ppp/active`.

### Windows 7 cannot reach the portal securely

Confirm Windows 7 SP1 has current SHA-2, root certificate, Schannel, and TLS
1.2 updates. The connector will not fall back to TLS 1.0.

## Reconnecting after a disconnect

While an active request has time remaining, the page offers **Reconnect**.
It launches the Windows connector with a new one-time token and the existing
PPPoE credentials. Reconnection keeps the original start and expiration times;
the countdown continues during disconnection. No new request is needed.
The connector checks MikroTik again to confirm reconnection. Reconnect tokens
cannot be issued or exchanged once the access time has elapsed, even before
the scheduler finishes cleanup.

### Admin PPPoE IP address

The admin request table shows the original Request IP and the last recorded
PPPoE IP separately. The PPPoE IP is captured from the MikroTik active session
during connector verification (including reconnects) or scheduler activation.
It remains blank until captured and is retained after expiration. Existing active
records receive an address on their next successful connector verification.
Admin search includes both IP fields. This is a recorded address, not a live
online-status indicator. Reconnecting does not reset the expiration time.

Deploy the new database column before using the updated application:

```bash
php artisan migrate --path=database/migrations/2026_09_22_000001_add_pppoe_ip_to_internet_access_requests_table.php
```
