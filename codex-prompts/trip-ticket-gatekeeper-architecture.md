# Codex Prompt: Trip Ticket Gatekeeper Architecture Discussion

We are discussing a major architecture update for the existing Laravel Support Portal Trip Ticket module. Do not generate code until the design is confirmed.

## Existing Context

- The active project is located at `/mnt/hgfs/Portal/MIS-Portal-main/support-portal-web1`.
- The Trip Ticket module already exists under:
  - `resources/views/trip-tickets/`
  - `app/Http/Controllers/TripTicketController.php`
  - `routes/web.php`
  - `app/Models/TripTicket.php`
- The current module supports trip ticket creation, editing, listing, approval/encoding behavior, printing, destination selection, and distance fields.

## Proposed Major Update

Add a new Gatekeeper role to the Trip Ticket lifecycle.

The Gatekeeper will use a mobile app. The Gatekeeper's job is to record the actual departure and actual return of a trip.

## Recommended Workflow

Existing workflow:

```text
Requester creates trip ticket
-> Dispatcher/Encoder assigns or manages driver and vehicle details
-> Approver approves the trip ticket
-> Trip ticket can be printed
```

New workflow with Gatekeeper:

```text
Requester creates trip ticket
-> Dispatcher/Encoder handles driver and vehicle details
-> Approver approves the trip ticket
-> Gatekeeper records actual departure
-> Gatekeeper records actual return
-> Trip is completed/returned
```

## Gatekeeper Role Rules

The Gatekeeper should be limited to gate actions only:

- Can view approved trips ready for departure.
- Can view departed trips awaiting return.
- Can search trip tickets manually.
- Can scan a QR code to quickly open a trip ticket.
- Can record actual departure date/time.
- Can record actual return date/time.
- Can optionally add departure/return remarks.
- Cannot edit requester, destination, department, driver, vehicle, approval, or dispatcher fields.

## QR Code Design

QR code is supported, but the system will not rely on QR code only.

QR is only a fast lookup method. It should not grant permission.

Recommended QR behavior:

- Laravel generates QR codes for free using a local package/library, not a paid QR service.
- QR should contain only a secure trip ticket token, not full trip details.
- Example QR value:

```text
TT:8f3b1e2d9a4c47b6a92e
```

- The mobile app scans the QR and sends the token to Laravel.
- Laravel checks login, permission, token validity, and trip ticket status.

Security rule:

```text
QR code identifies the trip ticket.
Login and gatekeeper permission authorize the action.
```

## Non-QR Lookup Methods

The mobile app should also support:

- Manual search by ticket number, driver name, vehicle plate number, requester, or destination.
- Daily list of trips ready for departure.
- Daily list of trips awaiting return.

Suggested mobile home screen:

```text
Gatekeeper Home
- Scan QR
- Ready for Departure
- Awaiting Return
- Search Trip Ticket
```

## Suggested Status Flow

The next design topic should confirm the exact status flow.

Possible statuses:

```text
pending_details
waiting_for_approval
approved
departed
returned
declined
cancelled
```

Important new statuses:

- `approved`: ready for Gatekeeper departure recording.
- `departed`: Gatekeeper already recorded departure, waiting for return.
- `returned`: Gatekeeper already recorded return.

## Suggested Database Direction

The existing `trip_tickets` table already appears to have:

- `actual_departure_datetime`
- `actual_return_datetime`

Recommended additional audit fields:

```text
departure_recorded_by
departure_recorded_at
return_recorded_by
return_recorded_at
gatekeeper_departure_remarks
gatekeeper_return_remarks
qr_token
```

Reason:

- `actual_departure_datetime` is the actual trip departure time.
- `departure_recorded_at` is when the gatekeeper submitted the record.
- Same logic applies to return.

## Suggested API Direction

The mobile app should use API routes separate from the web UI.

Suggested API responsibilities:

```text
GET ready-for-departure trips
GET awaiting-return trips
GET/search trip tickets
GET trip ticket by QR token
POST record departure
POST record return
```

Rules enforced by API:

- Only logged-in users with Gatekeeper permission can record departure/return.
- Cannot record departure unless ticket is approved.
- Cannot record return unless ticket is departed.
- Cannot record return before departure.
- Cannot update declined or cancelled tickets.
- Cannot overwrite departure/return timestamps unless manager/admin correction rules are later approved.

## Agreed Discussion State

The user understands the QR flow and has clarified that QR should not be the only method. The architecture should support QR, manual search, and daily lists.

Before coding, continue the discussion by confirming:

1. Exact status flow.
2. Gatekeeper permission name and role model.
3. Whether `returned` is final or whether another `completed` status is needed.
4. Whether gatekeeper can input custom actual time or only use current server time.
5. Whether corrections require manager/admin approval.

## Driver Workload Advisory Feature

The system should help prevent overusing or abusing one driver by using trip ticket records as workload evidence.

This feature should be advisory, not a hard block.

Dispatcher behavior:

- Dispatcher may still select or force a driver.
- If the selected driver appears overloaded, the system should show a warning popup.
- The popup should recommend other available or less-used drivers.
- The dispatcher can continue with the selected driver if operationally necessary.
- A future enhancement may require an override reason for accountability.

## Driver Workload Data Sources

Use Trip Ticket records, especially the Gatekeeper records, to calculate workload:

- `driver_id` or assigned driver record.
- `distance_km`.
- `actual_departure_datetime`.
- `actual_return_datetime`.
- Trip status such as `approved`, `departed`, and `returned`.

Gatekeeper records are important because they provide actual departure and actual return times. These are more reliable than requested schedule times for calculating driver usage.

Suggested workload metrics:

- Total kilometers driven in the last 7 days or 30 days.
- Total actual trip hours in the last 7 days or 30 days.
- Number of trips assigned in a period.
- Whether the driver currently has an active departed trip.
- Rest gap since the driver's last actual return.

Suggested advisory thresholds for discussion only:

```text
Warn if driver has more than 600 km in the last 7 days.
Warn if driver has more than 40 actual trip hours in the last 7 days.
Warn if driver returned less than 8 hours ago.
Warn if driver currently has an active departed trip.
```

These thresholds are not yet final and should be confirmed with operations/management.

## Driver Recommendation Popup

When dispatcher selects an overloaded driver, show a popup similar to:

```text
Driver workload warning

This driver has already covered 760 km and 42 trip hours in the last 7 days.
Last return was 4 hours ago.

For safety and fair workload distribution, consider another driver.

Recommended alternatives:
- Driver A: 180 km, 12 hours, available
- Driver B: 240 km, 18 hours, available
- Driver C: 0 active trips, available

[Choose Recommended Driver] [Continue With Selected Driver] [Cancel]
```

The wording should avoid saying the dispatcher is legally violating a rule unless the rule is confirmed by HR/legal. Use advisory terms such as:

```text
Driver workload advisory
Fatigue risk warning
Fair workload recommendation
```

Avoid wording like:

```text
Legal violation detected
```

## Philippine Legal and Safety Basis Discussion

The feature can be justified as a road safety and fair workload control.

Important distinction:

There may not be a single universal Philippine rule that says all drivers may only drive a fixed number of hours per day in all contexts. Therefore, the system should treat this as an advisory workload/fatigue prevention control unless HR/legal confirms a stricter internal or regulatory rule.

Potential legal/safety basis discussed:

1. Labor Code principles on normal work hours:
   - Normal work hours are generally 8 hours per day.
   - Work beyond normal hours may be overtime.
   - This supports tracking trip duration and warning about repeated long assignments.

2. Labor Code principles on weekly rest:
   - Employees should generally receive a weekly rest period.
   - This supports weekly workload monitoring.

3. RA 4136, Land Transportation and Traffic Code:
   - Drivers are expected to drive carefully and prudently.
   - Reckless or unsafe operation is prohibited.
   - Fatigue prevention supports safe driving and avoidance of road risk.

Source noted during discussion:

- RA 4136, Land Transportation and Traffic Code: https://lawphil.net/statutes/repacts/ra1964/ra_4136_1964.html

Design interpretation:

The system should support safety and fairness by warning dispatchers when a driver has high recent workload, but the dispatcher may override when operationally necessary.

## Suggested Backend Design For Driver Advisory

When implementation begins, consider creating a shared service such as:

```text
DriverWorkloadService
```

Responsibilities:

- Calculate recent driver kilometers.
- Calculate recent actual trip hours.
- Detect active trip conflicts.
- Calculate rest gap since last return.
- Rank recommended alternative drivers.
- Return advisory severity and explanation.

This service can be used by:

- Trip ticket create/edit forms.
- Dispatcher driver selection UI.
- Future API endpoints.
- Reports/dashboard if needed.

## Updated Design Questions Before Coding

Before implementing the Gatekeeper and Driver Workload Advisory features, confirm:

1. Exact status flow.
2. Gatekeeper permission name and role model.
3. Whether `returned` is final or another `completed` status is needed.
4. Whether gatekeeper can input custom actual time or only use current server time.
5. Whether corrections require manager/admin approval.
6. Driver workload warning thresholds.
7. Whether dispatcher override should require a reason.
8. Whether workload calculation should use last 7 days, last 30 days, or both.
9. Whether driver recommendations should exclude drivers with active departed trips.
10. Whether kilometers should count one-way `distance_km` or round-trip distance.

## Implementation Progress

- [x] 2026-07-09: Database foundation added for Gatekeeper records.
  - Added migration `database/migrations/2026_07_09_000001_add_gatekeeper_fields_to_trip_tickets_table.php`.
  - Added `departure_recorded_by`, `departure_recorded_at`, `return_recorded_by`, `return_recorded_at`, `gatekeeper_departure_remarks`, `gatekeeper_return_remarks`, and `qr_token` to the planned schema.
  - Updated `app/Models/TripTicket.php` fillable fields, date casts, and recorder relationships.
  - Verified with PHP syntax checks and a Laravel migration dry run using `--pretend --force`.

- [x] 2026-07-09: Gatekeeper permission foundation added.
  - Added migration `database/migrations/2026_07_09_000002_add_gatekeeper_permission_to_users_table.php` for `users.can_gatekeep_trip_tickets`.
  - Updated `app/Models/User.php` with fillable/cast support, `canGatekeepTripTickets()`, and Gatekeeper recorder relationships.
  - Updated admin user create/edit screens and `AdministrativeToolController` so Gatekeeper access can be assigned.
  - Updated `MobileAuthController` permissions payload with `can_gatekeep_trip_tickets`.
  - Updated `TripTicketSetupSeeder` with an optional `trip_gatekeeper` setup user.
  - Verified with PHP syntax checks and a Laravel migration dry run using `--pretend --force`.

- [x] 2026-07-09: Gatekeeper read API endpoints added.
  - Added `app/Http/Controllers/Api/TripTicketGatekeeperController.php` for daily Gatekeeper lookup flows.
  - Added authenticated API routes under `api/trip-tickets/gatekeeper`: `ready-for-departure`, `awaiting-return`, `search`, and `qr/{token}`.
  - Search supports ticket number, driver name, vehicle details, vehicle plate number, requester, and destination, scoped to the selected day so Gatekeeper can find vehicles scheduled for that day.
  - Added automatic `qr_token` generation for new trip tickets in `app/Models/TripTicket.php`.
  - Verified with PHP syntax checks and `php artisan route:list --path=trip-tickets/gatekeeper`.

- [x] 2026-07-09: Gatekeeper departure and return write API endpoints added.
  - Added `POST api/trip-tickets/gatekeeper/{tripTicket}/departure` to record actual departure, set `departure_recorded_by`, `departure_recorded_at`, optional Gatekeeper remarks, and move the ticket from `approved` to `dispatched`.
  - Added `POST api/trip-tickets/gatekeeper/{tripTicket}/return` to record actual return, set `return_recorded_by`, `return_recorded_at`, optional Gatekeeper remarks, and move the ticket from `dispatched` to `completed`.
  - Both actions require `can_gatekeep_trip_tickets`, lock the trip ticket row during update, prevent duplicate writes, and create `TripTicketLog` entries.
  - Verified with PHP syntax checks and `php artisan route:list --path=trip-tickets/gatekeeper`.

- [x] 2026-07-09: Gatekeeper migrations applied to the configured database.
  - Ran `php artisan migrate --force` successfully.
  - Confirmed migrations `2026_07_09_000001_add_gatekeeper_fields_to_trip_tickets_table` and `2026_07_09_000002_add_gatekeeper_permission_to_users_table` are marked `Ran`.
  - Verified `trip_tickets.departure_recorded_by`, `trip_tickets.return_recorded_by`, `trip_tickets.qr_token`, and `users.can_gatekeep_trip_tickets` exist.

- [x] 2026-07-09: Default Gatekeeper user account enabled.
  - Created or updated user `trip_gatekeeper` as `Trip Ticket Gatekeeper` in the configured database.
  - Enabled `can_gatekeep_trip_tickets` and kept encoder, approver, and manager trip-ticket permissions disabled.
  - Used the existing `trip_encoder` password hash to match the current seeded trip-ticket account convention because `TRIP_TICKET_DEFAULT_PASSWORD` is not currently set.
  - Verified `App\Models\User::canGatekeepTripTickets()` returns true for `trip_gatekeeper`.

- [x] 2026-07-09: Gatekeeper mobile app integration added.
  - Updated the Flutter mobile app session model to read `can_gatekeep_trip_tickets` from the login/me permissions payload.
  - Added Gatekeeper navigation in `mobile/trip_ticket_app/lib/screens/portal_shell_screen.dart`.
  - Added `mobile/trip_ticket_app/lib/screens/gatekeeper_screen.dart` with Ready, Return, and Search tabs.
  - Connected the app to Gatekeeper daily list, plate/ticket search, QR token lookup, record departure, and record return API methods in `ApiService`.
  - Updated the mobile trip ticket model for Gatekeeper audit fields and action flags.
  - Verified with `git diff --check`; Flutter/Dart analyze could not be run because no Flutter or Dart SDK is available in this environment.
  - Note: QR camera scanning is not included in this slice; current QR support is token lookup input. Camera scanning should be added next with a mobile scanner package and Android camera permission.

- [x] 2026-07-09: Gatekeeper mobile QR camera scanning added.
  - Added Flutter dependency `mobile_scanner` for QR camera scanning.
  - Added Android `CAMERA` permission to `mobile/trip_ticket_app/android/app/src/main/AndroidManifest.xml`.
  - Added `mobile/trip_ticket_app/lib/screens/qr_scan_screen.dart` for live QR scanning, flashlight toggle, camera switch, and scan cancellation.
  - Updated Gatekeeper Search tab to support both camera scanning and manual QR token lookup.
  - Verified with `dart format lib`, `flutter analyze`, `flutter test`, and `git diff --check`.
  - APK release/build is intentionally not included in this slice per request.

- [x] 2026-07-09: Printed trip ticket simplified to half-A4 with QR code.
  - Added `bacon/bacon-qr-code` for local SVG QR generation without requiring PHP GD.
  - Updated `TripTicketController::print()` to ensure approved tickets have a `qr_token` and to pass inline SVG QR data to the print view.
  - Reworked `resources/views/trip-tickets/print.blade.php` into a compact half-A4 layout with core trip fields, signatures, and QR value `TT:{qr_token}`.
  - Verified with PHP syntax check, Blade render smoke test, and `git diff --check`.
