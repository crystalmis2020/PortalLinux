# Trip Ticket Mobile API

This guide covers the backend contract for Flutter trip ticket approval.

## Authentication

Use Bearer tokens from Laravel Sanctum.

### Login

```http
POST /api/login
Content-Type: application/json
```

```json
{
  "username": "trip_approver",
  "password": "<configured-test-password>",
  "device_name": "flutter-phone"
}
```

Success:

```json
{
  "token_type": "Bearer",
  "access_token": "TOKEN",
  "user": {
    "id": 1,
    "full_name": "Trip Ticket Approver",
    "username": "trip_approver",
    "department": "Administration",
    "section": "Management Information System",
    "permissions": {
      "can_encode_trip_tickets": false,
      "can_approve_trip_tickets": true,
      "can_manage_trip_tickets": false,
      "can_print_trip_tickets": false
    }
  }
}
```

Use the token on protected requests:

```http
Authorization: Bearer TOKEN
```

### Current User

```http
GET /api/me
Authorization: Bearer TOKEN
```

### Logout

```http
POST /api/logout
Authorization: Bearer TOKEN
```

## Approval Endpoints

Only users with `can_approve_trip_tickets` or manager/admin permissions can use these endpoints.

### List Tickets For Approval

```http
GET /api/trip-tickets/for-approval
Authorization: Bearer TOKEN
```

Success:

```json
{
  "data": [
    {
      "id": 15,
      "ticket_number": "TT-2026-00015",
      "status": "for_approval",
      "destination": "Main Office",
      "purpose": "Official business meeting",
      "passengers": "Requester One",
      "contact_number": "09170000000",
      "remarks": "Encoded notes",
      "vehicle_details": "ABC-123 - Van",
      "driver_name": "Juan Dela Cruz",
      "requested_start_datetime": "2026-06-06T08:00:00+00:00",
      "requested_end_datetime": "2026-06-06T10:00:00+00:00",
      "actual_departure_datetime": "2026-06-06T08:00:00+00:00",
      "actual_return_datetime": "2026-06-06T10:00:00+00:00",
      "requester": {
        "id": 2,
        "full_name": "Requester One"
      },
      "department": {
        "id": 1,
        "name": "Administration"
      },
      "section": {
        "id": 1,
        "name": "Management Information System"
      },
      "encoder": {
        "id": 3,
        "full_name": "Trip Ticket Encoder"
      },
      "approver": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 1
  }
}
```

### Show Ticket

```http
GET /api/trip-tickets/{id}
Authorization: Bearer TOKEN
```

Success:

```json
{
  "ticket": {
    "id": 15,
    "ticket_number": "TT-2026-00015",
    "status": "for_approval",
    "destination": "Main Office",
    "purpose": "Official business meeting",
    "vehicle_details": "ABC-123 - Van",
    "driver_name": "Juan Dela Cruz",
    "logs": []
  },
  "can_approve": true
}
```

### Approve

```http
POST /api/trip-tickets/{id}/approve
Authorization: Bearer TOKEN
Content-Type: application/json
```

```json
{
  "approval_remarks": "Approved."
}
```

### Reject

```http
POST /api/trip-tickets/{id}/reject
Authorization: Bearer TOKEN
Content-Type: application/json
```

```json
{
  "approval_remarks": "Rejected due to schedule conflict."
}
```

### Return For Correction

```http
POST /api/trip-tickets/{id}/return
Authorization: Bearer TOKEN
Content-Type: application/json
```

```json
{
  "approval_remarks": "Please update the driver details."
}
```

Approval action success:

```json
{
  "message": "Trip ticket approved successfully.",
  "ticket": {
    "id": 15,
    "status": "approved",
    "approved_at": "2026-06-05T10:00:00+00:00",
    "approval_remarks": "Approved."
  }
}
```

## Common Errors

Unauthenticated:

```json
{
  "message": "Unauthenticated."
}
```

Forbidden:

```json
{
  "message": "This action is unauthorized."
}
```

Ticket is not ready for approval:

```json
{
  "message": "Only tickets submitted for approval can be updated.",
  "status": "pending_details"
}
```

Validation error:

```json
{
  "message": "The username field is required.",
  "errors": {
    "username": ["The username field is required."]
  }
}
```
