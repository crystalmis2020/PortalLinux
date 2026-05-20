# MISsenger

## Summary

MISsenger adds a floating chat launcher to the lower-right corner of authenticated portal pages. The current release supports one-to-one direct messaging and a send-to-many mode for active users inside the Support Portal.

## What Was Added

- A floating `MISsenger` button in the global portal layout.
- A responsive messenger panel with:
  - searchable contact list
  - one active conversation view
  - send-to-many mode with multi-user selection
  - online/offline presence indicators
  - message composer
  - unread badge on the launcher
- Backend endpoints for:
  - loading contacts and unread counts
  - opening a one-to-one conversation
  - sending a message
  - sending the same message to many selected users
- Database tables for:
  - `messenger_conversations`
  - `messenger_messages`

## Files

- `database/migrations/2026_04_27_000000_create_messenger_conversations_table.php`
- `database/migrations/2026_04_27_000001_create_messenger_messages_table.php`
- `app/Models/MessengerConversation.php`
- `app/Models/MessengerMessage.php`
- `app/Http/Controllers/MessengerController.php`
- `resources/views/layout/portal-messenger.blade.php`
- `resources/views/layout/app.blade.php`
- `routes/web.php`
- `app/Helpers/helpers.php`

## Data Model

### `messenger_conversations`

- `user_one_id`
- `user_two_id`
- `last_message_at`
- unique pair on `user_one_id` + `user_two_id`

User IDs are normalized in ascending order before a conversation is created, so each pair of users has only one conversation record.

### `messenger_messages`

- `conversation_id`
- `sender_id`
- `recipient_id`
- `body`
- `read_at`
- timestamps

## API Endpoints

All messenger routes are authenticated.

- `GET /messenger/contacts`
  - returns contact list ordered by latest activity
  - includes unread counts and latest message preview
- `GET /messenger/conversation/{user}`
  - opens or creates the one-to-one conversation
  - loads the latest 50 messages
  - marks incoming unread messages as read
- `POST /messenger/conversation/{user}`
  - sends a new message to the selected user
- `POST /messenger/broadcast`
  - sends the same message to many selected users
  - stores each delivery in that recipient's own one-to-one conversation

## Frontend Behavior

- The widget is rendered globally from `resources/views/layout/portal-messenger.blade.php`.
- The launcher stays fixed in the lower-right corner.
- The panel uses polling every 8 seconds for the MVP instead of websockets.
- The composer sends on `Enter` and inserts a new line on `Shift + Enter`.
- The contact list can be filtered by name, username, department, section, or message preview.
- Users can switch between `Direct` and `Send to Many` modes inside the sidebar.
- Multi-send creates one message per selected recipient so existing direct threads stay intact.
- Presence uses a lightweight heartbeat via `last_seen_at`, with a short timeout for online status.
- Admins can select a start and end point in a direct conversation, then create a normal report from that selected chat range.

## Create Report from Chat

MISsenger conversations stay normal by default. Nothing is sent into the report system unless an admin deliberately creates a report from a selected conversation range.

The admin flow is:

1. Open a direct conversation.
2. Click the report action in the thread header.
3. Select the first message, then the last message, to define the conversation range.
4. Complete the normal report fields:
   - destination section
   - issue category
   - brief description
   - optional contact number
5. Submit the form.

The created item is a normal report:

- it receives a regular report number
- it appears in the normal report list
- it follows the usual assignment, status, notification, and thread workflow
- it is filed under the other participant in the conversation as the reporter
- the selected conversation range is copied into the report description
- if selected MISsenger messages have attachments, those files are copied into the new report attachments
- before submission, the admin sees which selected chat attachments will be included in the new report

## Back-Read Scroll Fix

### Problem

Users could not comfortably back-read older messages in an open MISsenger conversation. Every polling refresh reloaded the selected conversation and pushed the message panel back down to the newest message.

### Root Cause

The frontend refresh flow was doing two things:

- `startAutoRefresh()` runs every 8 seconds and calls `openConversation(state.selectedUserId, true)` when a direct conversation is selected.
- `renderMessages()` always ran `elements.messages.scrollTop = elements.messages.scrollHeight` after rebuilding the message HTML.

That unconditional scroll-to-bottom behavior is good when first opening a conversation or after sending a message, but it is bad during silent background refreshes because it destroys the reader's current scroll position.

### Fix

The fix is in `resources/views/layout/portal-messenger.blade.php`.

`renderMessages()` now accepts an optional config object:

```js
renderMessages({ preserveScroll: true });
```

Before replacing the message HTML, it records:

- previous scroll height
- previous scroll top
- distance from the bottom
- whether the user was already near the bottom

After the HTML is rebuilt:

- If `preserveScroll` is enabled and the user was not near the bottom, MISsenger restores the same distance from the bottom.
- If the user was near the bottom, MISsenger still scrolls to the newest message so active chatting feels natural.
- Normal conversation opens still scroll to the newest message.

### Refresh Paths Updated

These refresh paths pass `{ preserveScroll: true }`:

- background polling in `startAutoRefresh()`
- `window.portalMessenger.refresh()`
- manual refresh button while a conversation is open
- incoming-message refresh for the currently selected sender
- incoming-call conversation sync

### Expected Behavior

- Back-reading older messages should no longer jump to the newest message during refresh.
- Users near the bottom still follow new messages automatically.
- Selecting a contact from the list still opens at the newest message.
- Sending a message still scrolls to the bottom.

## Online Status Logic

MISsenger does not treat `is_login` by itself as the final source of truth for presence.

Online targeting currently works like this:

- `is_login`
  - set to `true` on successful login
  - set to `false` on logout
- `last_login`
  - records when the user last authenticated into the portal
- `last_seen_at`
  - updated on login
  - updated whenever the authenticated user hits MISsenger endpoints such as:
    - loading contacts
    - opening a conversation
    - sending a direct message
    - sending a multi-recipient message

A user is shown as `Online` only when both conditions are true:

- `is_login = true`
- `last_seen_at` is still within the recent heartbeat window

Right now the heartbeat window is `2 minutes` inside `App\Models\User::isCurrentlyOnline()`.

This means:

- A user who logged in earlier but has been inactive long enough will appear `Offline`.
- A stale database row with `is_login = true` is not enough by itself to keep someone `Online`.
- Presence becomes more accurate as active users continue interacting with MISsenger.

This is still an MVP presence model. It is not a websocket-based live presence system yet, so status updates are refreshed through normal MISsenger polling and requests.

## Current MVP Constraints

- Text messages only
- Latest 50 messages per thread
- Polling-based updates, not true realtime sockets
- No attachments, typing indicators, delivery states, or emoji picker yet
- No IPMsg or external desktop message forwarding

## Deployment Notes

This project currently has legacy migration history drift in MySQL, so the safest way to enable MISsenger is to run only the new messenger migrations.

1. Run the targeted messenger migrations:

```bash
php artisan migrate --force \
  --path=database/migrations/2026_04_27_000000_create_messenger_conversations_table.php \
  --path=database/migrations/2026_04_27_000001_create_messenger_messages_table.php \
  --path=database/migrations/2026_04_27_000002_add_last_seen_at_to_users_table.php
```

2. Clear cached routes/config if needed:

```bash
php artisan optimize:clear
```

## Recommended Next Steps

- Add authorization rules for who can message whom if needed.
- Add attachments and upload validation for richer chat.
- Replace polling with Laravel broadcasting/websockets for realtime delivery.
- Add read receipts and delivered states.
- Add message retention/search controls if chat volume grows.
