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
