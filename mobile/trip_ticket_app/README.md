# Support Portal Mobile App

This is the mobile version of the Support Portal. The application shell includes
Trip Ticket and Mess Hall navigation. Trip Ticket approval is the active module;
Mess Hall is reserved for the next development phase.

## Server Requirement

Flutter was tested from the SDK extracted at `/tmp/flutter-sdk/flutter`. Use writable Flutter config/cache paths on this server:

```bash
cd mobile/trip_ticket_app
HOME=/tmp/flutter-home XDG_CONFIG_HOME=/tmp/flutter-home/.config PUB_CACHE=/tmp/flutter-pub-cache /tmp/flutter-sdk/flutter/bin/flutter pub get
HOME=/tmp/flutter-home XDG_CONFIG_HOME=/tmp/flutter-home/.config PUB_CACHE=/tmp/flutter-pub-cache /tmp/flutter-sdk/flutter/bin/flutter analyze
```

For APK generation later, the server still needs an Android SDK and Java/JDK setup.

## API Base URL

The app reads the API base URL from a Dart define:

```bash
flutter run --dart-define=API_BASE_URL=http://128.0.254.20/support
```

For APK:

```bash
flutter build apk --release --dart-define=API_BASE_URL=http://128.0.254.20/support
```

APK output:

```text
build/app/outputs/flutter-apk/app-release.apk
```

## Windows Build

Install Git, Flutter, Android Studio, Android SDK, and JDK 17. Then open
PowerShell in the cloned repository:

```powershell
cd mobile\trip_ticket_app
flutter doctor
flutter pub get
flutter analyze
flutter test
flutter build apk --release --dart-define=API_BASE_URL=http://128.0.254.20/support
```

The current release build is suitable for internal testing and uses Android's
debug signing key. Configure a private release keystore before public
distribution.

The HTTP URL is intended only for the trusted internal network. Before
distribution outside that network, install a publicly trusted TLS certificate
on the server, switch `API_BASE_URL` to HTTPS, and remove
`android:usesCleartextTraffic="true"`.

## Test Account

Create the development approver account with `TripTicketSetupSeeder`. Configure
`TRIP_TICKET_APPROVER_USERNAME` and `TRIP_TICKET_DEFAULT_PASSWORD` in the
Laravel `.env` file before running the seeder. Never commit the real password.

## Backend Endpoints Used

```text
POST /api/login
GET  /api/me
POST /api/logout
GET  /api/trip-tickets/for-approval
GET  /api/trip-tickets/{id}
POST /api/trip-tickets/{id}/approve
POST /api/trip-tickets/{id}/reject
POST /api/trip-tickets/{id}/return
```
