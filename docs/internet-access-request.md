# Internet Access Request Setup

This document explains how the new Internet Access Request module was added to the Support Portal.

It is written for a beginner developer, so the explanations are step by step.

## What This Feature Does

The Internet Access Request module lets a logged-in user create temporary MikroTik internet access.

The new flow is:

1. User visits `/internet-access`.
2. User selects the requested time: `1h`, `2h`, `3h`, or `8h`.
3. User enters a purpose.
4. The system immediately creates a MikroTik account.
5. The username and password are generated using the old project style.
6. Username and password are the same.
7. The page shows the generated username/password.
8. IPMsg sends the username/password to the user's IP address.
9. Countdown starts only after MikroTik detects the first successful connection.
10. Countdown continues even if the user disconnects.
11. When the timer expires, the system removes the MikroTik access.

There is no admin approval in this version.

## Important Route

The page is available here:

```text
/internet-access
```

If the app is deployed under `/support`, use:

```text
/support/internet-access
```

The user must be logged in because the route uses Laravel `auth` middleware.

## Files Added

These are the main files for this feature.

### Controller

```text
app/Http/Controllers/InternetAccessRequestController.php
```

This handles:

- showing the Internet Access page
- creating a request
- generating username/password
- calling MikroTik
- sending IPMsg
- returning AJAX status for the countdown

### Model

```text
app/Models/InternetAccessRequest.php
```

This represents one internet access request in the database.

### Migration

```text
database/migrations/2026_05_19_000001_create_internet_access_requests_table.php
```

This creates the database table:

```text
internet_access_requests
```

### MikroTik Service

```text
app/Services/Mikrotik/RouterOsClient.php
```

This is the class that talks to MikroTik RouterOS.

The controller does not directly send RouterOS commands. Instead, it calls this service.

### Scheduler Command

```text
app/Console/Commands/SyncInternetAccessRequests.php
```

This command checks MikroTik and updates request statuses.

It does two important things:

- checks if a `ready` user has connected for the first time
- expires `active` users when their time is finished

The command name is:

```bash
php artisan internet-access:sync
```

### View

```text
resources/views/internet-access/index.blade.php
```

This is the page the user sees.

It shows:

- request form
- current username/password
- waiting state
- countdown timer
- request history

### Config

```text
config/mikrotik.php
```

This reads MikroTik settings from `.env`.

## Files Updated

### Routes

```text
routes/web.php
```

Added:

```php
Route::middleware(['auth'])->prefix('internet-access')->name('internet-access.')->controller(InternetAccessRequestController::class)->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/status/{internetAccessRequest}', 'status')->name('status');
});
```

### Scheduler

```text
routes/console.php
```

Added:

```php
Schedule::command('internet-access:sync')
    ->everyMinute()
    ->withoutOverlapping();
```

This means Laravel will run the sync command every minute when the scheduler is active.

### Environment

```text
.env
```

Added MikroTik settings:

```env
MIKROTIK_ENABLED=true
MIKROTIK_HOST=128.0.100.1
MIKROTIK_PORT=8728
MIKROTIK_USERNAME=Alkaloid
MIKROTIK_PASSWORD=alkaloid
MIKROTIK_TIMEOUT=10
MIKROTIK_SERVICE=pppoe
MIKROTIK_CUSTOMER=xonivre
MIKROTIK_PROFILE_1H=1MB_Connection
MIKROTIK_PROFILE_2H=5MB_Connection
MIKROTIK_PROFILE_3H=5MB_Connection
MIKROTIK_PROFILE_8H=50MB_Connection
```

These values came from the old integrated internet request project.

## Database Setup

Run this command:

```bash
php artisan migrate
```

This creates the `internet_access_requests` table.

If Laravel says the app is in production and asks for confirmation, type `yes`.

## Clear Config Cache

After changing `.env`, always run:

```bash
php artisan config:clear
```

Laravel sometimes caches `.env` values. Clearing config makes sure the app reads the latest MikroTik settings.

## How Username And Password Are Generated

The new module keeps the old username/password style.

Example:

```text
1hA8kLmP2
```

The format is:

```text
requested_hours + random_7_characters
```

The password is exactly the same as the username.

Example:

```text
Username: 1hA8kLmP2
Password: 1hA8kLmP2
```

## MikroTik Profile Mapping

The requested time maps to a MikroTik profile.

```text
1h -> 1MB_Connection
2h -> 5MB_Connection
3h -> 5MB_Connection
8h -> 50MB_Connection
```

You can change these in `.env`:

```env
MIKROTIK_PROFILE_1H=1MB_Connection
MIKROTIK_PROFILE_2H=5MB_Connection
MIKROTIK_PROFILE_3H=5MB_Connection
MIKROTIK_PROFILE_8H=50MB_Connection
```

## Status Meanings

Each request has a `status`.

### ready

MikroTik account was created, but the user has not connected yet.

The countdown has not started.

### active

MikroTik detected that the user connected successfully.

The countdown is now running.

### expired

The requested time is finished.

The system removes the MikroTik access.

### failed

The system failed to create the MikroTik access.

Common reasons:

- wrong MikroTik username/password
- MikroTik API is disabled
- portal server cannot reach MikroTik IP
- port `8728` is blocked
- MikroTik profile does not exist

## Countdown Rule

The countdown starts only after the first successful MikroTik connection.

Example:

```text
User requests 1 hour at 10:00 AM
User connects at 10:15 AM
Countdown starts at 10:15 AM
Access expires at 11:15 AM
```

If the user disconnects at 10:30 AM, the countdown still continues.

This is intentional.

The rule is:

```text
Disconnecting does not pause the timer.
```

## IPMsg Setup

The module sends an IPMsg message after the MikroTik account is created.

Message format:

```text
Your internet access is ready. Username: {username} Password: {username}
```

The module uses the existing helper:

```php
sendIpMsgNotification()
```

That helper is located in:

```text
app/Helpers/helpers.php
```

Important:

This new setup uses UDP IPMsg packets through PHP. It does not use the old Windows `ipmsg.exe`.

## Scheduler Setup

The countdown and expiry depend on the Laravel scheduler.

For local testing, you can run:

```bash
php artisan schedule:work
```

Keep that command running in a terminal.

For production, the server should run Laravel scheduler every minute using cron:

```cron
* * * * * cd /path/to/support-portal-web1 && php artisan schedule:run >> /dev/null 2>&1
```

Change `/path/to/support-portal-web1` to the real project path.

## Manual Sync Test

You can manually run the sync command:

```bash
php artisan internet-access:sync
```

This checks:

- ready requests that may now be connected
- active requests that may already be expired

## How To Test The Feature

1. Run migration:

```bash
php artisan migrate
```

2. Clear config:

```bash
php artisan config:clear
```

3. Make sure MikroTik API is enabled.

4. Make sure the portal server can reach:

```text
128.0.100.1:8728
```

5. Log in to the support portal.

6. Visit:

```text
/internet-access
```

7. Create a request.

8. The page should show username/password.

9. The user should receive IPMsg.

10. Connect using the generated username/password.

11. Run this or wait for scheduler:

```bash
php artisan internet-access:sync
```

12. Refresh the page.

The status should change from `ready` to `active`, and the countdown should start.

## Common Problems

### The page says MikroTik integration is disabled

Check `.env`:

```env
MIKROTIK_ENABLED=true
```

Then run:

```bash
php artisan config:clear
```

### The request fails to create

Check:

- MikroTik IP is correct
- MikroTik username is correct
- MikroTik password is correct
- MikroTik API service is enabled
- port `8728` is reachable
- selected MikroTik profile exists

### Countdown does not start

The scheduler may not be running.

Run:

```bash
php artisan internet-access:sync
```

If that works, set up the scheduler.

### IPMsg does not arrive

Check:

- user's IP address is correct
- user's IPMsg app is open
- UDP port `2425` is not blocked
- Laravel logs for IPMsg warning messages

## Sidebar Note

This feature was intentionally not added to the user sidebar.

Users can visit it only through the direct route:

```text
/internet-access
```

This matches the requested behavior: build the feature, but do not display it in the users sidebar.

