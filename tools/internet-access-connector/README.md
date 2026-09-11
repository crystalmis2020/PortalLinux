# Support Portal Internet Access Connector

This package installs a small, per-user Windows connector for the Support
Portal. It supports Windows 7, Windows 10, and Windows 11 and uses the existing
RAS/PPPoE entry named exactly `Broadband Connection`.

The connector is intentionally separate from the Laravel application. It does
not contain a username, password, portal administrator credential, or MikroTik
credential.

## What it installs

The installer copies the connector to:

```text
%LOCALAPPDATA%\SupportPortalConnector
```

It then registers this URI scheme for the current Windows user under `HKCU`:

```text
supportportal-connect://connect?token=ONE_TIME_TOKEN
```

For the internal portal at `128.0.1.20`, the package also contains the
dedicated **public** Support Portal root certificate. The installer verifies
its pinned thumbprint before adding it to the current user's Windows Trusted
Root store. No CA private key or server private key is included in this package.

Root CA SHA-256 fingerprint:

```text
20:FE:C5:EE:30:4B:6E:19:30:EE:8A:A7:D3:70:F6:E5:44:C3:B7:87:1C:F6:66:26:45:AA:9C:07:04:C8:75:09
```

No administrator permission is required. The browser can ask the user to
confirm that it may open **Support Portal Internet Connector**. Browsers do not
provide a reliable way for the portal to suppress that prompt or to detect the
connector in advance.

## Requirements

- Windows 7 SP1, Windows 10, or Windows 11.
- Windows PowerShell 2.0 or later.
- The built-in Windows RAS service and WinHTTP 5.1 component.
- An existing RAS entry named exactly `Broadband Connection`.
- An HTTPS Support Portal certificate trusted by the client computer.
- TLS 1.2 support enabled for WinHTTP. Windows 7 must have the applicable
  servicing and TLS updates installed. Unpatched Windows 7 is deliberately not
  allowed to fall back to TLS 1.0.

Windows 7 is no longer supported by Microsoft. A company that must retain it
should manage the required Windows updates, trusted root certificates, and
application-control exceptions centrally.

## Install

Download the connector package while authenticated to the portal, extract it,
and double-click `install.cmd`. Accept the Windows certificate trust prompt if
one is shown. A generated package can contain a
`portal-url.txt` file whose first line is the public HTTPS portal base URL. The
installer reads it automatically, so the user normally does not need to enter
anything.

If `portal-url.txt` is absent, the installer prompts for the base URL, for
example:

```text
https://support.example.company
```

Whether supplied in `portal-url.txt`, at the prompt, or with `-PortalUrl`, do
not include `/api/internet-access/connector/exchange`; the installer stores the
base URL and the connector appends the fixed endpoint path. HTTPS validation is
applied equally to all three input methods.

For scripted per-user deployment:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\install.ps1 `
  -PortalUrl "https://support.example.company" -Quiet
```

The install remains per-user even when deployed by a management tool. Run it in
the target user's context, not a system account's context.

## Exact portal contract

The connector accepts only this protocol shape:

```text
supportportal-connect://connect?token=<opaque-token>
```

The decoded token must be exactly 43 characters and contain only ASCII letters,
digits, `_`, or `-` (unpadded base64url for 32 random bytes). The URI cannot
supply or override a server URL, connection name, username, or password.

### 1. Credential exchange

The connector sends:

```http
POST {PORTAL_BASE_URL}/api/internet-access/connector/exchange HTTP/1.1
Authorization: Bearer <opaque-token>
Accept: application/json
Content-Type: application/x-www-form-urlencoded; charset=utf-8
X-Support-Connector-Version: 1.1.0

connector_version=1.1.0&connection_name=Broadband%20Connection
```

Redirects are disabled. The endpoint must return HTTP 200 and UTF-8 JSON:

```json
{
  "username": "temporary-pppoe-user",
  "password": "temporary-pppoe-password",
  "verification_url": "https://support.example.company/api/internet-access/connector/verify"
}
```

`username` and `password` must be non-empty strings no longer than 256
characters. The connection name is not taken from the response. The
`verification_url` must be HTTPS and have the same scheme, host, and port as the
installed portal base URL. Its path must also remain underneath the installed
base path. It may contain a server-generated signed query string.

The exchange response should include `Cache-Control: no-store` and
`Pragma: no-cache`.

### 2. Connection verification

After native `RasDialW` reports success, the connector sends:

```http
POST <verification_url> HTTP/1.1
Authorization: Bearer <same-opaque-token>
Accept: application/json
Content-Type: application/x-www-form-urlencoded; charset=utf-8
X-Support-Connector-Version: 1.1.0

connector_version=1.1.0&connection_name=Broadband%20Connection&ras_result=0
```

The verification endpoint should query MikroTik for the active PPP session and
return HTTP 200, 202, or 204. HTTP 202 means verification was accepted but the
PPP session is not visible yet. The Laravel scheduler remains the fallback for
verification. If this request fails, the connector warns the user but does not
disconnect the successful PPPoE connection.

The server-side token state should allow exactly one credential exchange and
one subsequent verification at the URL returned for that exchange. Both uses
should expire quickly; the current server contract permits a 30-300 second
launch-token lifetime.
Credential exchange must be atomic so parallel or replayed requests cannot
receive the credentials twice. Bind the token to the approved request and the
requesting portal account.

## Security properties and limits

- HTTPS certificate validation is left enabled and TLS 1.2 is required.
- The portal endpoint is fixed at installation; a launched URI cannot redirect
  credential exchange to another server.
- A returned verification URL is restricted to the installed portal origin and
  base path. HTTP redirects are disabled for both requests.
- PPPoE credentials are held only in process memory and passed to native
  `RasDialW`; they are not written to disk, logged, placed in the URI, or exposed
  in a child process command line.
- Managed strings cannot be guaranteed to disappear from memory immediately.
  The connector drops references after dialing and requests garbage collection,
  but this is not equivalent to securely zeroing all managed-memory copies.
- A per-user installation is writable by that Windows user. For production,
  code-sign the scripts/package, publish its hash through a trusted channel, and
  use company application-control policy where available.
- Any web page can attempt to launch a registered custom protocol. Security
  therefore depends on a short-lived, single-use, server-bound token and strict
  authorization at both endpoints.
- The installer and registered handler use `ExecutionPolicy Bypass` for these
  exact local scripts because downloaded scripts can carry Mark-of-the-Web.
  Execution policy is not a security boundary. Code signing is recommended.
- No diagnostic log is written because it could accidentally retain tokens or
  credentials. Windows RAS and portal audit logs should contain only request IDs
  and status information, never passwords or bearer tokens.

## Uninstall

Run either copy of `uninstall.cmd`:

- the one in the extracted package, or
- `%LOCALAPPDATA%\SupportPortalConnector\uninstall.cmd`.

It removes only this user's `supportportal-connect` registration, connector
settings, connector installation directory, and the exact pinned root
certificate when this installer originally added it. A certificate that was
already trusted through Group Policy or another administrator-managed method is
left in place. It does not delete or modify the existing `Broadband Connection`
RAS entry.
