@if (auth()->check() && portalMessengerEnabled())
@php
    $messengerCurrentUser = auth()->user();
    $messengerUnreadCounts = collect();

    if (portalMessengerEnabled()) {
        $messengerConversations = \App\Models\MessengerConversation::query()
            ->where('user_one_id', $messengerCurrentUser->id)
            ->orWhere('user_two_id', $messengerCurrentUser->id)
            ->get();

        $messengerConversationContactIds = $messengerConversations
            ->mapWithKeys(function ($conversation) use ($messengerCurrentUser) {
                return [$conversation->id => $conversation->otherParticipantIdFor($messengerCurrentUser->id)];
            });

        $messengerUnreadCounts = \App\Models\MessengerMessage::query()
            ->selectRaw('conversation_id, COUNT(*) as aggregate')
            ->whereIn('conversation_id', $messengerConversations->pluck('id'))
            ->where('recipient_id', $messengerCurrentUser->id)
            ->whereNull('read_at')
            ->groupBy('conversation_id')
            ->pluck('aggregate', 'conversation_id')
            ->mapWithKeys(function ($count, $conversationId) use ($messengerConversationContactIds) {
                return [(int) $messengerConversationContactIds->get($conversationId) => (int) $count];
            });
    }

    $messengerContacts = \App\Models\User::query()
        ->where('id', '!=', $messengerCurrentUser->id)
        ->where('is_active', true)
        ->with(['department', 'section'])
        ->orderBy('full_name')
        ->get()
        ->map(function ($user) use ($messengerUnreadCounts) {
            return [
                'id' => $user->id,
                'full_name' => $user->full_name ?: $user->username,
                'username' => $user->username,
                'department' => $user->department?->name,
                'section' => $user->section?->name,
                'avatar_url' => $user->profile_photo_url,
                'is_online' => $user->isCurrentlyOnline(),
                'last_seen_at' => optional($user->last_seen_at)?->toIso8601String(),
                'unread_count' => (int) $messengerUnreadCounts->get($user->id, 0),
            ];
        })
        ->sort(function ($a, $b) {
            if ($a['is_online'] !== $b['is_online']) {
                return $a['is_online'] ? -1 : 1;
            }

            return strcasecmp($a['full_name'], $b['full_name']);
        })
        ->values();

    $messengerReportIssues = $messengerCurrentUser->isAdmin()
        ? \App\Models\Issue::query()->orderBy('name')->get(['id', 'name'])
        : collect();

    $messengerReportSections = $messengerCurrentUser->isAdmin()
        ? \App\Models\Section::query()->with('department')->orderBy('name')->get()
        : collect();
@endphp

<style>
    .portal-messenger {
        --pm-primary: #0b6b37;
        --pm-primary-dark: #084726;
        --pm-accent: #d9f3df;
        --pm-border: rgba(11, 107, 55, 0.16);
        --pm-shadow: 0 24px 60px rgba(5, 32, 17, 0.24);
        --pm-alert: #ffb703;
        --pm-alert-strong: #fb8500;
        position: fixed;
        right: 24px;
        bottom: 24px;
        z-index: 1080;
        font-family: "Plus Jakarta Sans", sans-serif;
    }

    .portal-messenger__launcher {
        width: 64px;
        height: 64px;
        border: 0;
        border-radius: 20px;
        background: linear-gradient(145deg, var(--pm-primary), var(--pm-primary-dark));
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: var(--pm-shadow);
        position: relative;
        transition: transform 0.25s ease, box-shadow 0.25s ease, background 0.25s ease;
    }

    .portal-messenger__launcher.is-alerting {
        animation: portalMessengerBlink 1.2s ease-in-out infinite;
        background: linear-gradient(145deg, var(--pm-alert), var(--pm-alert-strong));
    }

    .portal-messenger__launcher i {
        font-size: 28px;
        transition: color 0.25s ease, transform 0.25s ease, filter 0.25s ease;
    }

    .portal-messenger__launcher.is-alerting i {
        animation: portalMessengerIconBlink 0.9s ease-in-out infinite;
    }

    @keyframes portalMessengerBlink {
        0%, 100% {
            transform: scale(1);
            box-shadow: 0 24px 60px rgba(5, 32, 17, 0.24);
            filter: brightness(1);
        }

        50% {
            transform: scale(1.08);
            box-shadow: 0 28px 78px rgba(251, 133, 0, 0.42);
            filter: brightness(1.2);
        }
    }

    @keyframes portalMessengerIconBlink {
        0%, 100% {
            color: #ffffff;
            transform: scale(1);
            filter: drop-shadow(0 0 0 rgba(255, 255, 255, 0));
        }

        50% {
            color: #4a1800;
            transform: scale(1.14);
            filter: drop-shadow(0 0 14px rgba(255, 255, 255, 0.5));
        }
    }

    .portal-messenger__badge {
        min-width: 22px;
        height: 22px;
        padding: 0 6px;
        border-radius: 999px;
        position: absolute;
        top: -6px;
        right: -6px;
        background: #e53935;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 800;
        display: none;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 18px rgba(229, 57, 53, 0.28);
    }

    .portal-messenger__badge.is-visible {
        display: inline-flex;
    }

    .portal-messenger__panel {
        position: absolute;
        right: 0;
        bottom: 82px;
        width: min(920px, calc(100vw - 32px));
        height: min(680px, calc(100vh - 120px));
        border-radius: 24px;
        overflow: hidden;
        background: #f6faf7;
        box-shadow: var(--pm-shadow);
        border: 1px solid rgba(255, 255, 255, 0.4);
        display: none;
    }

    .portal-messenger__panel.is-open {
        display: grid;
        grid-template-columns: 320px minmax(0, 1fr);
    }

    .portal-messenger__sidebar {
        background:
            radial-gradient(circle at top left, rgba(217, 243, 223, 0.95), rgba(246, 250, 247, 0.92) 58%),
            #f6faf7;
        border-right: 1px solid var(--pm-border);
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .portal-messenger__main {
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(244, 248, 245, 0.98));
        display: flex;
        flex-direction: column;
        min-height: 0;
    }

    .portal-messenger__sidebar-head,
    .portal-messenger__thread-head {
        padding: 18px 20px;
        border-bottom: 1px solid var(--pm-border);
    }

    .portal-messenger__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 999px;
        background: rgba(11, 107, 55, 0.08);
        color: var(--pm-primary-dark);
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .portal-messenger__title {
        margin: 12px 0 4px;
        color: #13331f;
        font-size: 1.1rem;
        font-weight: 800;
    }

    .portal-messenger__subtitle {
        margin: 0;
        color: #5f7167;
        font-size: 0.88rem;
    }

    .portal-messenger__search {
        padding: 16px 20px 12px;
    }

    .portal-messenger__modebar {
        display: flex;
        gap: 8px;
        padding: 0 20px 12px;
    }

    .portal-messenger__modebtn {
        border: 1px solid var(--pm-border);
        background: rgba(255, 255, 255, 0.82);
        color: #20452f;
        border-radius: 999px;
        padding: 9px 12px;
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1;
    }

    .portal-messenger__modebtn.is-active {
        background: linear-gradient(145deg, var(--pm-primary), var(--pm-primary-dark));
        color: #fff;
        border-color: transparent;
        box-shadow: 0 14px 28px rgba(11, 107, 55, 0.16);
    }

    .portal-messenger__search input {
        width: 100%;
        border: 1px solid var(--pm-border);
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.92);
        padding: 12px 14px;
        font-size: 0.92rem;
        color: #13331f;
    }

    .portal-messenger__search input:focus,
    .portal-messenger__composer textarea:focus {
        outline: none;
        border-color: rgba(11, 107, 55, 0.45);
        box-shadow: 0 0 0 0.2rem rgba(11, 107, 55, 0.12);
    }

    .portal-messenger__contacts {
        overflow: auto;
        padding: 0 12px 12px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .portal-messenger__contact {
        width: 100%;
        border: 1px solid transparent;
        background: rgba(255, 255, 255, 0.72);
        border-radius: 18px;
        padding: 14px;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        text-align: left;
        transition: 0.2s ease;
    }

    .portal-messenger__contact.is-selecting {
        padding-left: 12px;
    }

    .portal-messenger__contact:hover,
    .portal-messenger__contact.is-active {
        border-color: rgba(11, 107, 55, 0.18);
        background: #fff;
        transform: translateY(-1px);
    }

    .portal-messenger__contact.is-selected {
        border-color: rgba(11, 107, 55, 0.36);
        background: rgba(217, 243, 223, 0.72);
    }

    .portal-messenger__contact.is-unread {
        border-color: rgba(11, 107, 55, 0.22);
        background: linear-gradient(90deg, rgba(217, 243, 223, 0.85), rgba(255, 255, 255, 0.92));
        box-shadow: inset 4px 0 0 var(--pm-primary);
    }

    .portal-messenger__contact.is-unread .portal-messenger__contact-name {
        color: var(--pm-primary-dark);
        font-weight: 900;
    }

    .portal-messenger__selector {
        width: 18px;
        height: 18px;
        margin-top: 12px;
        accent-color: var(--pm-primary);
        flex: 0 0 auto;
    }

    .portal-messenger__avatar,
    .portal-messenger__thread-avatar {
        width: 44px;
        height: 44px;
        border-radius: 16px;
        object-fit: cover;
        flex: 0 0 auto;
    }

    .portal-messenger__avatar-shell,
    .portal-messenger__thread-avatar-shell {
        position: relative;
        flex: 0 0 auto;
    }

    .portal-messenger__status-dot {
        position: absolute;
        right: -2px;
        bottom: -2px;
        width: 12px;
        height: 12px;
        border-radius: 999px;
        border: 2px solid #f6faf7;
        background: #98a7a0;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.12);
    }

    .portal-messenger__status-dot.is-online {
        background: #2bbf6a;
    }

    .portal-messenger__contact-body,
    .portal-messenger__thread-user {
        min-width: 0;
        flex: 1 1 auto;
    }

    .portal-messenger__contact-row,
    .portal-messenger__thread-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .portal-messenger__contact-name,
    .portal-messenger__thread-name {
        margin: 0;
        color: #13331f;
        font-size: 0.95rem;
        font-weight: 800;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .portal-messenger__contact-meta,
    .portal-messenger__thread-meta,
    .portal-messenger__time {
        margin: 0;
        color: #6d7f74;
        font-size: 0.78rem;
    }

    .portal-messenger__seen {
        margin: 2px 0 0;
        color: var(--pm-primary-dark);
        font-size: 0.72rem;
        font-weight: 800;
        text-align: right;
    }

    .portal-messenger__message.is-mine .portal-messenger__time {
        display: none;
    }

    .portal-messenger__message-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 6px;
    }

    .portal-messenger__report-action {
        border: 0;
        border-radius: 999px;
        background: rgba(11, 107, 55, 0.08);
        color: var(--pm-primary-dark);
        font-size: 0.72rem;
        font-weight: 800;
        padding: 6px 10px;
    }

    .portal-messenger__message.is-mine .portal-messenger__report-action {
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
    }

    .portal-messenger__contact-unread {
        min-width: 24px;
        height: 24px;
        padding: 0 7px;
        border-radius: 999px;
        background: #e53935;
        color: #fff;
        font-size: 0.72rem;
        font-weight: 800;
        display: none;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        line-height: 1;
        box-shadow: 0 8px 16px rgba(229, 57, 53, 0.26);
    }

    .portal-messenger__contact-unread.is-visible {
        display: inline-flex;
    }

    .portal-messenger__thread-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        background: rgba(255, 255, 255, 0.86);
        backdrop-filter: blur(12px);
    }

    .portal-messenger__thread-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .portal-messenger__call-action {
        background: linear-gradient(145deg, rgba(11, 107, 55, 0.14), rgba(217, 243, 223, 0.92));
        color: var(--pm-primary-dark);
    }

    .portal-messenger__call-action.is-live {
        background: linear-gradient(145deg, #d62839, #b81f30);
        color: #fff;
        box-shadow: 0 16px 28px rgba(214, 40, 57, 0.24);
    }

    .portal-messenger__call-modal {
        --pm-primary: #0b6b37;
        --pm-primary-dark: #084726;
        position: fixed;
        inset: 0;
        z-index: 2050;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(8, 16, 12, 0.58);
        backdrop-filter: blur(10px);
    }

    .portal-messenger__call-modal.is-open {
        display: flex;
    }

    .portal-messenger__call-card {
        width: min(460px, calc(100vw - 32px));
        padding: 24px;
        border-radius: 28px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(241, 248, 244, 0.96));
        box-shadow: 0 28px 80px rgba(5, 25, 15, 0.32);
        text-align: center;
    }

    .portal-messenger__call-avatar {
        width: 88px;
        height: 88px;
        margin: 0 auto 18px;
        border-radius: 28px;
        object-fit: cover;
        box-shadow: 0 18px 40px rgba(11, 107, 55, 0.2);
    }

    .portal-messenger__call-status {
        margin: 0 0 8px;
        font-size: 0.86rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #5f7167;
    }

    .portal-messenger__call-name {
        margin: 0;
        font-size: 1.45rem;
        font-weight: 800;
        color: #183121;
    }

    .portal-messenger__call-meta {
        margin: 8px 0 0;
        color: #5f7167;
        font-size: 0.92rem;
    }

    .portal-messenger__call-actions {
        margin-top: 24px;
        display: flex;
        justify-content: center;
        gap: 12px;
        flex-wrap: wrap;
    }

    .portal-messenger__call-btn {
        min-width: 120px;
        border: 0;
        border-radius: 999px;
        padding: 12px 18px;
        font-weight: 800;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .portal-messenger__call-btn.is-hidden {
        display: none !important;
    }

    .portal-messenger__call-btn--accept {
        background: linear-gradient(145deg, #0b6b37, #084726) !important;
        color: #fff !important;
        border: 1px solid #0b6b37 !important;
        box-shadow: 0 16px 28px rgba(11, 107, 55, 0.24);
    }

    .portal-messenger__call-btn--decline {
        background: linear-gradient(145deg, #d62839, #b81f30) !important;
        color: #fff !important;
        border: 1px solid #d62839 !important;
        box-shadow: 0 16px 28px rgba(214, 40, 57, 0.22);
    }

    .portal-messenger__call-btn--secondary {
        background: rgba(32, 69, 47, 0.08) !important;
        color: #20452f !important;
        border: 1px solid rgba(32, 69, 47, 0.18) !important;
    }

    .portal-messenger__call-btn--accept i,
    .portal-messenger__call-btn--accept span,
    .portal-messenger__call-btn--decline i,
    .portal-messenger__call-btn--decline span {
        color: #fff !important;
    }

    .portal-messenger__call-modal.is-incoming .portal-messenger__call-btn--accept,
    .portal-messenger__call-modal.is-incoming .portal-messenger__call-btn--decline {
        display: inline-flex !important;
    }

    .portal-messenger__call-modal.is-incoming .portal-messenger__call-btn--secondary {
        display: none !important;
    }

    .portal-messenger__presence {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-top: 4px;
        color: #6d7f74;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .portal-messenger__presence-bullet {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #98a7a0;
        flex: 0 0 auto;
    }

    .portal-messenger__presence-bullet.is-online {
        background: #2bbf6a;
    }

    .portal-messenger__selection-bar {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 18px 0;
    }

    .portal-messenger__selection-bar.is-visible {
        display: flex;
    }

    .portal-messenger__selection-note {
        margin: 0;
        color: #4d6255;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .portal-messenger__selection-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .portal-messenger__select-all {
        width: 100%;
        border: 1px solid rgba(11, 107, 55, 0.18);
        background: rgba(217, 243, 223, 0.42);
        color: #20452f;
        border-radius: 16px;
        padding: 12px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.84rem;
        font-weight: 800;
        text-align: left;
    }

    .portal-messenger__select-all-label {
        flex: 1 1 auto;
    }

    .portal-messenger__action {
        border: 1px solid var(--pm-border);
        background: #fff;
        color: #20452f;
        border-radius: 12px;
        width: 40px;
        height: 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .portal-messenger__mobile-back {
        display: none;
    }

    .portal-messenger__messages {
        flex: 1 1 auto;
        min-height: 0;
        overflow: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        background-image:
            radial-gradient(circle at 20% 20%, rgba(217, 243, 223, 0.72), transparent 26%),
            radial-gradient(circle at 80% 0%, rgba(11, 107, 55, 0.06), transparent 20%);
    }

    .portal-messenger__message {
        max-width: min(78%, 540px);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .portal-messenger__message.is-mine {
        align-self: flex-end;
    }

    .portal-messenger__message.is-report-selectable {
        cursor: pointer;
    }

    .portal-messenger__message.is-report-selected .portal-messenger__bubble {
        box-shadow: 0 0 0 3px rgba(251, 133, 0, 0.32);
    }

    .portal-messenger__report-range {
        display: none;
        border: 0;
        background: rgba(11, 107, 55, 0.08);
        color: var(--pm-primary-dark);
        font-size: 0.78rem;
        font-weight: 800;
        padding: 11px 20px;
        text-align: left;
    }

    .portal-messenger__report-range.is-visible {
        display: block;
    }

    .portal-messenger__report-attachments {
        border: 1px solid var(--pm-border);
        border-radius: 14px;
        background: rgba(11, 107, 55, 0.05);
        padding: 12px;
    }

    .portal-messenger__report-attachment-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 10px;
    }

    .portal-messenger__report-attachment-item {
        display: flex;
        align-items: center;
        gap: 10px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.92);
        color: #193121;
        padding: 10px 12px;
    }

    .portal-messenger__report-attachment-item > div {
        min-width: 0;
    }

    .portal-messenger__report-attachment-name {
        color: inherit;
        font-weight: 700;
        overflow-wrap: anywhere;
    }

    .portal-messenger__report-attachment-item i {
        color: var(--pm-primary);
        font-size: 1.1rem;
    }

    .portal-messenger__bubble {
        padding: 12px 14px;
        border-radius: 18px;
        background: #fff;
        color: #193121;
        box-shadow: 0 8px 24px rgba(17, 43, 27, 0.08);
        display: flex;
        flex-direction: column;
        gap: 10px;
        overflow: hidden;
    }

    .portal-messenger__message.is-mine .portal-messenger__bubble {
        background: linear-gradient(140deg, var(--pm-primary), #127d43);
        color: #fff;
    }

    .portal-messenger__body {
        white-space: pre-wrap;
        word-break: break-word;
    }

    .portal-messenger__attachment {
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(11, 107, 55, 0.08);
        color: inherit;
    }

    .portal-messenger__message.is-mine .portal-messenger__attachment {
        background: rgba(255, 255, 255, 0.16);
    }

    .portal-messenger__attachment-name {
        font-weight: 700;
        line-height: 1.35;
    }

    .portal-messenger__attachment-meta {
        margin-top: 4px;
        font-size: 0.78rem;
        opacity: 0.82;
    }

    .portal-messenger__attachment-actions {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .portal-messenger__attachment-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.92);
        color: var(--pm-primary-dark);
        font-size: 0.8rem;
        font-weight: 700;
        text-decoration: none;
    }

    .portal-messenger__message.is-mine .portal-messenger__attachment-link {
        background: rgba(255, 255, 255, 0.96);
        color: var(--pm-primary-dark);
    }

    .portal-messenger__attachment-link--button {
        border: 0;
        cursor: pointer;
    }

    .portal-messenger__composer {
        border-top: 1px solid var(--pm-border);
        padding: 16px 18px 18px;
        background: rgba(255, 255, 255, 0.92);
    }

    .portal-messenger__preview-modal {
        position: fixed;
        inset: 0;
        z-index: 2000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(9, 19, 13, 0.6);
        backdrop-filter: blur(6px);
    }

    .portal-messenger__preview-modal.is-open {
        display: flex;
    }

    .portal-messenger__preview-dialog {
        width: min(1040px, calc(100vw - 32px));
        max-height: calc(100vh - 32px);
        display: flex;
        flex-direction: column;
        border-radius: 24px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 32px 80px rgba(10, 26, 17, 0.3);
    }

    .portal-messenger__preview-dialog > form {
        display: flex;
        flex: 1 1 auto;
        min-height: 0;
        flex-direction: column;
    }

    .portal-messenger__preview-header,
    .portal-messenger__preview-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 16px 20px;
        border-color: rgba(11, 107, 55, 0.12);
        border-style: solid;
    }

    .portal-messenger__preview-header {
        border-width: 0 0 1px;
    }

    .portal-messenger__preview-footer {
        border-width: 1px 0 0;
    }

    .portal-messenger__preview-body {
        flex: 1 1 auto;
        min-height: 0;
        padding: 20px;
        overflow: auto;
    }

    .portal-messenger__preview-frame {
        width: 100%;
        min-height: 70vh;
        border: 0;
        background: #f5f7f6;
    }

    .portal-messenger__preview-image {
        display: block;
        max-width: 100%;
        max-height: 70vh;
        margin: 0 auto;
        border-radius: 18px;
    }

    .portal-messenger__preview-text {
        min-height: 320px;
        max-height: 70vh;
        overflow: auto;
        margin: 0;
        padding: 18px;
        border-radius: 18px;
        background: #f5f7f6;
        color: #193121;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .portal-messenger__preview-empty {
        min-height: 280px;
        display: grid;
        place-items: center;
        text-align: center;
        color: #587061;
    }

    .portal-messenger__composer form {
        display: flex;
        gap: 12px;
        align-items: flex-end;
    }

    .portal-messenger__composer-main {
        flex: 1 1 auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .portal-messenger__composer textarea {
        min-height: 52px;
        max-height: 132px;
        border: 1px solid var(--pm-border);
        border-radius: 18px;
        resize: none;
        padding: 14px 16px;
        background: #fff;
        color: #193121;
    }

    .portal-messenger__composer-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .portal-messenger__attach {
        border: 1px dashed rgba(11, 107, 55, 0.3);
        border-radius: 999px;
        min-width: 52px;
        height: 52px;
        background: rgba(217, 243, 223, 0.66);
        color: var(--pm-primary-dark);
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .portal-messenger__pending {
        display: none;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 10px 12px;
        border-radius: 16px;
        border: 1px solid rgba(11, 107, 55, 0.12);
        background: rgba(217, 243, 223, 0.56);
    }

    .portal-messenger__pending.is-visible {
        display: flex;
    }

    .portal-messenger__pending-file {
        min-width: 0;
    }

    .portal-messenger__pending-name {
        font-weight: 700;
        color: #193121;
        word-break: break-word;
    }

    .portal-messenger__pending-meta {
        margin-top: 2px;
        font-size: 0.78rem;
        color: #587061;
    }

    .portal-messenger__send {
        border: 0;
        border-radius: 16px;
        min-width: 56px;
        height: 52px;
        background: linear-gradient(145deg, var(--pm-primary), var(--pm-primary-dark));
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 18px 36px rgba(11, 107, 55, 0.2);
    }

    .portal-messenger__placeholder,
    .portal-messenger__empty {
        flex: 1 1 auto;
        min-height: 0;
        display: grid;
        place-items: center;
        padding: 24px;
        text-align: center;
        color: #587061;
    }

    .portal-messenger__placeholder-card {
        max-width: 420px;
    }

    .portal-messenger__placeholder-icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 16px;
        border-radius: 24px;
        display: grid;
        place-items: center;
        background: linear-gradient(145deg, rgba(11, 107, 55, 0.12), rgba(217, 243, 223, 0.92));
        color: var(--pm-primary-dark);
        font-size: 2rem;
    }

    .portal-messenger__empty {
        display: none;
    }

    .portal-messenger__empty.is-visible {
        display: grid;
    }

    .portal-messenger__loading {
        display: none;
        padding: 12px 20px 0;
        color: #5f7167;
        font-size: 0.82rem;
    }

    .portal-messenger__loading.is-visible {
        display: block;
    }

    html.dark-theme .portal-messenger__panel {
        background: #152118;
        border-color: rgba(255, 255, 255, 0.06);
    }

    html.dark-theme .portal-messenger__sidebar,
    html.dark-theme .portal-messenger__main,
    html.dark-theme .portal-messenger__thread-head,
    html.dark-theme .portal-messenger__composer {
        background: rgba(19, 31, 23, 0.96);
    }

    html.dark-theme .portal-messenger__messages {
        background-image: none;
        background-color: rgba(19, 31, 23, 0.96);
    }

    html.dark-theme .portal-messenger__contact,
    html.dark-theme .portal-messenger__search input,
    html.dark-theme .portal-messenger__composer textarea,
    html.dark-theme .portal-messenger__action,
    html.dark-theme .portal-messenger__bubble,
    html.dark-theme .portal-messenger__modebtn,
    html.dark-theme .portal-messenger__pending,
    html.dark-theme .portal-messenger__attach {
        background: rgba(255, 255, 255, 0.04);
        color: #edf4ef;
        border-color: rgba(255, 255, 255, 0.08);
    }

    html.dark-theme .portal-messenger__call-card {
        background: linear-gradient(180deg, rgba(20, 33, 24, 0.98), rgba(15, 24, 18, 0.98));
    }

    html.dark-theme .portal-messenger__call-name {
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__call-meta,
    html.dark-theme .portal-messenger__call-status {
        color: rgba(237, 244, 239, 0.72);
    }

    html.dark-theme .portal-messenger__call-btn--secondary {
        background: rgba(255, 255, 255, 0.08);
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__attachment {
        background: rgba(255, 255, 255, 0.08);
    }

    html.dark-theme .portal-messenger__attachment-link {
        background: rgba(255, 255, 255, 0.12);
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__preview-dialog {
        background: #152118;
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__report-attachments {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
    }

    html.dark-theme .portal-messenger__report-attachment-item {
        background: rgba(255, 255, 255, 0.08);
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__preview-frame,
    html.dark-theme .portal-messenger__preview-text {
        background: rgba(255, 255, 255, 0.04);
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__contact:hover,
    html.dark-theme .portal-messenger__contact.is-active {
        background: rgba(255, 255, 255, 0.08);
    }

    html.dark-theme .portal-messenger__contact.is-selected {
        background: rgba(36, 94, 61, 0.5);
    }

    html.dark-theme .portal-messenger__contact.is-unread {
        background: linear-gradient(90deg, rgba(36, 94, 61, 0.62), rgba(255, 255, 255, 0.06));
    }

    html.dark-theme .portal-messenger__title,
    html.dark-theme .portal-messenger__contact-name,
    html.dark-theme .portal-messenger__thread-name {
        color: #edf4ef;
    }

    html.dark-theme .portal-messenger__subtitle,
    html.dark-theme .portal-messenger__contact-meta,
    html.dark-theme .portal-messenger__thread-meta,
    html.dark-theme .portal-messenger__time,
    html.dark-theme .portal-messenger__seen,
    html.dark-theme .portal-messenger__empty,
    html.dark-theme .portal-messenger__placeholder,
    html.dark-theme .portal-messenger__pending-meta,
    html.dark-theme .portal-messenger__attachment-meta {
        color: rgba(237, 244, 239, 0.74);
    }

    html.dark-theme .portal-messenger__select-all {
        background: rgba(36, 94, 61, 0.38);
        color: #edf4ef;
        border-color: rgba(255, 255, 255, 0.08);
    }

    html.dark-theme .portal-messenger__status-dot {
        border-color: #152118;
    }

    @media (max-width: 991.98px) {
        .portal-messenger {
            right: 16px;
            bottom: 16px;
        }

        .portal-messenger__panel.is-open {
            grid-template-columns: 1fr;
            grid-template-rows: minmax(280px, 44%) minmax(0, 1fr);
        }

        .portal-messenger__sidebar {
            max-height: none;
            border-right: 0;
            border-bottom: 1px solid var(--pm-border);
            overflow: hidden;
        }

        .portal-messenger__contacts {
            flex: 1 1 auto;
            min-height: 0;
        }

        .portal-messenger__main {
            min-height: 0;
        }

        .portal-messenger__mobile-back {
            display: inline-flex;
        }

        .portal-messenger__panel.is-open.is-mobile-list {
            grid-template-rows: 1fr;
        }

        .portal-messenger__panel.is-open.is-mobile-list .portal-messenger__sidebar {
            border-bottom: 0;
        }

        .portal-messenger__panel.is-open.is-mobile-list .portal-messenger__main {
            display: none;
        }

        .portal-messenger__panel.is-open.is-mobile-thread {
            grid-template-rows: 1fr;
        }

        .portal-messenger__panel.is-open.is-mobile-thread .portal-messenger__sidebar {
            display: none;
        }

        .portal-messenger__panel.is-open.is-mobile-thread .portal-messenger__main {
            display: flex;
        }
    }

    @media (max-width: 575.98px) {
        .portal-messenger__panel {
            width: calc(100vw - 16px);
            height: calc(100vh - 92px);
            right: -8px;
        }

        .portal-messenger__launcher {
            width: 58px;
            height: 58px;
            border-radius: 18px;
        }
    }
</style>

<div
    class="portal-messenger"
    id="portalMessenger"
    data-contacts-url="{{ route('messenger.contacts') }}"
    data-conversation-url-template="{{ url('/messenger/conversation/__USER__') }}"
    data-store-url-template="{{ url('/messenger/conversation/__USER__') }}"
    data-store-many-url="{{ route('messenger.store-many') }}"
    data-create-report-url-template="{{ url('/messenger/conversations/__CONVERSATION__/report') }}"
    data-call-signal-url-template="{{ url('/messenger/call/__USER__/signal') }}"
    data-initial-contacts='@json($messengerContacts)'
    data-current-user-id="{{ $messengerCurrentUser->id }}"
    data-current-user-name="{{ $messengerCurrentUser->full_name ?: $messengerCurrentUser->username }}"
    data-is-admin="{{ $messengerCurrentUser->isAdmin() ? '1' : '0' }}"
    data-initial-unread="{{ getPortalMessengerUnreadCount() }}"
>
    <div class="portal-messenger__panel" id="portalMessengerPanel">
        <aside class="portal-messenger__sidebar">
            <div class="portal-messenger__sidebar-head">
                <span class="portal-messenger__eyebrow"><i class='bx bx-message-dots'></i> MIS-senger</span>
            </div>
            <div class="portal-messenger__search">
                <input type="text" id="portalMessengerSearch" placeholder="Search by name, section, or username">
            </div>
            <div class="portal-messenger__modebar">
                <button type="button" class="portal-messenger__modebtn is-active" id="portalMessengerDirectMode">Direct</button>
                <button type="button" class="portal-messenger__modebtn" id="portalMessengerMultiMode">Send to Many</button>
            </div>
            <div class="portal-messenger__contacts" id="portalMessengerContacts"></div>
        </aside>

        <section class="portal-messenger__main">
            <div class="portal-messenger__thread-head" id="portalMessengerThreadHead">
                <div class="portal-messenger__thread-row">
                    <div class="portal-messenger__thread-avatar-shell">
                        <img src="{{ $messengerCurrentUser->profile_photo_url }}" alt="Current user" class="portal-messenger__thread-avatar" id="portalMessengerThreadAvatar">
                        <span class="portal-messenger__status-dot" id="portalMessengerThreadStatus"></span>
                    </div>
                    <div class="portal-messenger__thread-user">
                        <p class="portal-messenger__thread-name" id="portalMessengerThreadName">Select a contact</p>
                        <p class="portal-messenger__thread-meta" id="portalMessengerThreadMeta">Choose a person from the list to start chatting.</p>
                    </div>
                </div>
                <div class="portal-messenger__thread-actions">
                    <button type="button" class="portal-messenger__action portal-messenger__mobile-back" id="portalMessengerBack" title="Back to contacts">
                        <i class='bx bx-chevron-left'></i>
                    </button>
                    <button type="button" class="portal-messenger__action portal-messenger__call-action" id="portalMessengerCallButton" title="Start audio call" disabled>
                        <i class='bx bx-phone-call'></i>
                    </button>
                    @if ($messengerCurrentUser->isAdmin())
                    <button type="button" class="portal-messenger__action" id="portalMessengerReportRangeButton" title="Create report from conversation" disabled>
                        <i class='bx bx-file'></i>
                    </button>
                    @endif
                    <button type="button" class="portal-messenger__action" id="portalMessengerRefresh" title="Refresh">
                        <i class='bx bx-refresh'></i>
                    </button>
                    <button type="button" class="portal-messenger__action" id="portalMessengerClose" title="Close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
            </div>

            <div class="portal-messenger__loading" id="portalMessengerLoading">Syncing conversation...</div>
            @if ($messengerCurrentUser->isAdmin())
            <button type="button" class="portal-messenger__report-range" id="portalMessengerReportRangeHint">Select the first message for the report.</button>
            @endif
            <div class="portal-messenger__selection-bar" id="portalMessengerSelectionBar">
                <p class="portal-messenger__selection-note" id="portalMessengerSelectionNote">Select teammates to receive the same message.</p>
                <div class="portal-messenger__selection-actions">
                    <button type="button" class="portal-messenger__action" id="portalMessengerClearSelection" title="Clear selection">
                        <i class='bx bx-reset'></i>
                    </button>
                </div>
            </div>
            <div class="portal-messenger__placeholder" id="portalMessengerPlaceholder">
                <div class="portal-messenger__placeholder-card">
                    <div class="portal-messenger__placeholder-icon"><i class='bx bx-chat'></i></div>
                    <h4 class="portal-messenger__title mb-2">MISsenger is ready</h4>
                    <p class="portal-messenger__subtitle">Pick a teammate for a direct conversation, or switch to Send to Many to deliver the same message to multiple users.</p>
                </div>
            </div>
            <div class="portal-messenger__messages" id="portalMessengerMessages" style="display:none;"></div>
            <div class="portal-messenger__empty" id="portalMessengerEmpty">
                <div class="portal-messenger__placeholder-card">
                    <div class="portal-messenger__placeholder-icon"><i class='bx bx-message-square-detail'></i></div>
                    <h4 class="portal-messenger__title mb-2">No messages yet</h4>
                    <p class="portal-messenger__subtitle">Send the first message to start this conversation.</p>
                </div>
            </div>

            <div class="portal-messenger__composer">
                <form id="portalMessengerForm">
                    <div class="portal-messenger__composer-main">
                        <div class="portal-messenger__pending" id="portalMessengerPendingAttachment">
                            <div class="portal-messenger__pending-file">
                                <div class="portal-messenger__pending-name" id="portalMessengerPendingAttachmentName"></div>
                                <div class="portal-messenger__pending-meta" id="portalMessengerPendingAttachmentMeta"></div>
                            </div>
                            <button type="button" class="portal-messenger__action" id="portalMessengerClearAttachment" title="Remove attachment">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <textarea id="portalMessengerInput" placeholder="Write a message..." disabled></textarea>
                    </div>
                    <div class="portal-messenger__composer-actions">
                        <input type="file" id="portalMessengerAttachment" class="d-none">
                        <button type="button" class="portal-messenger__attach" id="portalMessengerAttach" title="Attach file" disabled>
                            <i class='bx bx-paperclip'></i>
                        </button>
                    </div>
                    <button type="submit" class="portal-messenger__send" id="portalMessengerSend" disabled>
                        <i class='bx bx-send'></i>
                    </button>
                </form>
            </div>
        </section>
    </div>

    <button type="button" class="portal-messenger__launcher" id="portalMessengerLauncher" aria-label="Open MISsenger">
        <i class='bx bxs-message-rounded-dots'></i>
        <span class="portal-messenger__badge {{ getPortalMessengerUnreadCount() > 0 ? 'is-visible' : '' }}" id="portalMessengerBadge">{{ getPortalMessengerUnreadCount() }}</span>
    </button>
</div>

@if ($messengerCurrentUser->isAdmin())
<div class="portal-messenger__preview-modal" id="portalMessengerReportModal" aria-hidden="true">
    <div class="portal-messenger__preview-dialog" role="dialog" aria-modal="true" aria-labelledby="portalMessengerReportModalTitle">
        <div class="portal-messenger__preview-header">
            <div>
                <h5 class="mb-1" id="portalMessengerReportModalTitle">Create Report from Chat</h5>
                <p class="mb-0 text-muted small" id="portalMessengerReportReporter"></p>
            </div>
            <button type="button" class="portal-messenger__action" id="portalMessengerReportModalClose" aria-label="Close report form">
                <i class='bx bx-x'></i>
            </button>
        </div>
        <form id="portalMessengerReportForm">
            <div class="portal-messenger__preview-body p-3">
                <div class="mb-3">
                    <label for="portalMessengerReportSection" class="form-label">Section</label>
                    <select class="form-select" id="portalMessengerReportSection" name="section_id" required>
                        @foreach ($messengerReportSections as $section)
                            <option value="{{ $section->id }}">
                                {{ $section->name }}{{ $section->department ? ' - ' . $section->department->name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="portalMessengerReportIssueCategory" class="form-label">Issue Category</label>
                    <select class="form-select" id="portalMessengerReportIssueCategory" name="issue_id" required>
                        <option value="" selected disabled>Select Issue Category</option>
                        @foreach ($messengerReportIssues as $issue)
                            <option value="{{ $issue->id }}">{{ $issue->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="portalMessengerReportIssue" class="form-label">Brief Description</label>
                    <textarea class="form-control" id="portalMessengerReportIssue" name="issue" rows="5" maxlength="2000" required></textarea>
                </div>
                <div class="mb-3 portal-messenger__report-attachments" id="portalMessengerReportAttachments">
                    <div class="fw-bold">Attachments included in this report</div>
                    <div class="small text-muted" id="portalMessengerReportAttachmentSummary">No attachments in the selected conversation range.</div>
                    <div class="portal-messenger__report-attachment-list" id="portalMessengerReportAttachmentList"></div>
                </div>
                <div class="mb-0">
                    <label for="portalMessengerReportContact" class="form-label">Contact</label>
                    <input type="text" class="form-control" id="portalMessengerReportContact" name="contact_number">
                </div>
            </div>
            <div class="portal-messenger__preview-footer">
                <button type="button" class="btn btn-outline-secondary" id="portalMessengerReportModalCancel">Cancel</button>
                <button type="submit" class="btn btn-primary" id="portalMessengerReportSubmit">Create Report</button>
            </div>
        </form>
    </div>
</div>
@endif

<div class="portal-messenger__call-modal" id="portalMessengerCallModal" aria-hidden="true">
    <div class="portal-messenger__call-card" role="dialog" aria-modal="true" aria-labelledby="portalMessengerCallName">
        <img src="{{ $messengerCurrentUser->profile_photo_url }}" alt="Call contact" class="portal-messenger__call-avatar" id="portalMessengerCallAvatar">
        <p class="portal-messenger__call-status" id="portalMessengerCallStatus">Audio call</p>
        <h4 class="portal-messenger__call-name" id="portalMessengerCallName">Select a contact</h4>
        <p class="portal-messenger__call-meta" id="portalMessengerCallMeta">Calls work only while the portal is open.</p>
        <div class="portal-messenger__call-actions">
            <button type="button" class="portal-messenger__call-btn portal-messenger__call-btn--accept" id="portalMessengerCallAccept">
                <i class='bx bx-phone-call'></i>
                <span>Answer</span>
            </button>
            <button type="button" class="portal-messenger__call-btn portal-messenger__call-btn--secondary" id="portalMessengerCallDismiss">
                <i class='bx bx-x'></i>
                <span>Close</span>
            </button>
            <button type="button" class="portal-messenger__call-btn portal-messenger__call-btn--decline" id="portalMessengerCallDecline">
                <i class='bx bx-phone-off'></i>
                <span>Decline</span>
            </button>
        </div>
        <audio id="portalMessengerRemoteAudio" autoplay playsinline></audio>
        <audio id="portalMessengerLocalAudio" autoplay playsinline muted></audio>
        <audio id="portalMessengerCallRingtone" src="{{ asset('assets/plugins/notifications/sounds/sound1.ogg') }}" preload="auto" loop></audio>
    </div>
</div>

<div class="portal-messenger__preview-modal" id="portalMessengerAttachmentModal" aria-hidden="true">
    <div class="portal-messenger__preview-dialog" role="dialog" aria-modal="true" aria-labelledby="portalMessengerAttachmentModalTitle">
        <div class="portal-messenger__preview-header">
            <div>
                <h5 class="mb-1" id="portalMessengerAttachmentModalTitle">Attachment preview</h5>
                <p class="mb-0 text-muted small" id="portalMessengerAttachmentModalMeta"></p>
            </div>
            <button type="button" class="portal-messenger__action" id="portalMessengerAttachmentModalClose" aria-label="Close preview">
                <i class='bx bx-x'></i>
            </button>
        </div>
        <div class="portal-messenger__preview-body" id="portalMessengerAttachmentModalBody">
            <div class="portal-messenger__preview-empty">Select a file to preview.</div>
        </div>
        <div class="portal-messenger__preview-footer">
            <a href="#" class="btn btn-outline-secondary" id="portalMessengerAttachmentModalDownload" download>
                <i class='bx bx-download me-1'></i>Download
            </a>
            <button type="button" class="btn btn-primary" id="portalMessengerAttachmentModalCloseFooter">Close</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const root = document.getElementById('portalMessenger');
        if (!root) {
            return;
        }

        const maxAttachmentBytes = 20 * 1024 * 1024;

        const state = {
            contacts: JSON.parse(root.dataset.initialContacts || '[]'),
            filteredContacts: [],
            selectedUserId: null,
            conversationId: null,
            selectedRecipientIds: [],
            messages: [],
            isOpen: false,
            isLoadingConversation: false,
            isSyncingConversation: false,
            isLoadingOlderMessages: false,
            hasMoreOlderMessages: false,
            hasMoreNewerMessages: false,
            conversationRequestId: 0,
            search: '',
            totalUnread: Number(root.dataset.initialUnread || 0),
            refreshTimer: null,
            composeMode: 'direct',
            pendingAttachment: null,
            reportDraft: {
                messageIds: [],
            },
            reportSelection: {
                active: false,
                startId: null,
                endId: null,
            },
            call: {
                currentCallId: null,
                partnerId: null,
                partnerName: '',
                partnerMeta: '',
                partnerAvatar: '',
                mode: 'idle',
                modalHidden: false,
                isTerminating: false,
                incomingTimeoutId: null,
                outgoingTimeoutId: null,
                offer: null,
                pendingCandidates: [],
                peerConnection: null,
                localStream: null,
                remoteStream: null,
                ringtoneUnlocked: false,
                ringtoneAudioContext: null,
                ringtoneToneNodes: [],
                ringtoneToneTimer: null,
            },
        };

        const elements = {
            panel: document.getElementById('portalMessengerPanel'),
            launcher: document.getElementById('portalMessengerLauncher'),
            badge: document.getElementById('portalMessengerBadge'),
            contacts: document.getElementById('portalMessengerContacts'),
            search: document.getElementById('portalMessengerSearch'),
            directMode: document.getElementById('portalMessengerDirectMode'),
            multiMode: document.getElementById('portalMessengerMultiMode'),
            placeholder: document.getElementById('portalMessengerPlaceholder'),
            messages: document.getElementById('portalMessengerMessages'),
            empty: document.getElementById('portalMessengerEmpty'),
            composer: root.querySelector('.portal-messenger__composer'),
            form: document.getElementById('portalMessengerForm'),
            input: document.getElementById('portalMessengerInput'),
            send: document.getElementById('portalMessengerSend'),
            loading: document.getElementById('portalMessengerLoading'),
            threadName: document.getElementById('portalMessengerThreadName'),
            threadMeta: document.getElementById('portalMessengerThreadMeta'),
            threadAvatar: document.getElementById('portalMessengerThreadAvatar'),
            threadStatus: document.getElementById('portalMessengerThreadStatus'),
            refresh: document.getElementById('portalMessengerRefresh'),
            close: document.getElementById('portalMessengerClose'),
            back: document.getElementById('portalMessengerBack'),
            callButton: document.getElementById('portalMessengerCallButton'),
            reportRangeButton: document.getElementById('portalMessengerReportRangeButton'),
            reportRangeHint: document.getElementById('portalMessengerReportRangeHint'),
            selectionBar: document.getElementById('portalMessengerSelectionBar'),
            selectionNote: document.getElementById('portalMessengerSelectionNote'),
            clearSelection: document.getElementById('portalMessengerClearSelection'),
            attachmentInput: document.getElementById('portalMessengerAttachment'),
            attachButton: document.getElementById('portalMessengerAttach'),
            pendingAttachment: document.getElementById('portalMessengerPendingAttachment'),
            pendingAttachmentName: document.getElementById('portalMessengerPendingAttachmentName'),
            pendingAttachmentMeta: document.getElementById('portalMessengerPendingAttachmentMeta'),
            clearAttachment: document.getElementById('portalMessengerClearAttachment'),
            attachmentModal: document.getElementById('portalMessengerAttachmentModal'),
            attachmentModalTitle: document.getElementById('portalMessengerAttachmentModalTitle'),
            attachmentModalMeta: document.getElementById('portalMessengerAttachmentModalMeta'),
            attachmentModalBody: document.getElementById('portalMessengerAttachmentModalBody'),
            attachmentModalDownload: document.getElementById('portalMessengerAttachmentModalDownload'),
            attachmentModalClose: document.getElementById('portalMessengerAttachmentModalClose'),
            attachmentModalCloseFooter: document.getElementById('portalMessengerAttachmentModalCloseFooter'),
            callModal: document.getElementById('portalMessengerCallModal'),
            callAvatar: document.getElementById('portalMessengerCallAvatar'),
            callStatus: document.getElementById('portalMessengerCallStatus'),
            callName: document.getElementById('portalMessengerCallName'),
            callMeta: document.getElementById('portalMessengerCallMeta'),
            callAccept: document.getElementById('portalMessengerCallAccept'),
            callDismiss: document.getElementById('portalMessengerCallDismiss'),
            callDecline: document.getElementById('portalMessengerCallDecline'),
            remoteAudio: document.getElementById('portalMessengerRemoteAudio'),
            localAudio: document.getElementById('portalMessengerLocalAudio'),
            callRingtone: document.getElementById('portalMessengerCallRingtone'),
            reportModal: document.getElementById('portalMessengerReportModal'),
            reportForm: document.getElementById('portalMessengerReportForm'),
            reportReporter: document.getElementById('portalMessengerReportReporter'),
            reportSection: document.getElementById('portalMessengerReportSection'),
            reportIssueCategory: document.getElementById('portalMessengerReportIssueCategory'),
            reportIssue: document.getElementById('portalMessengerReportIssue'),
            reportContact: document.getElementById('portalMessengerReportContact'),
            reportAttachmentSummary: document.getElementById('portalMessengerReportAttachmentSummary'),
            reportAttachmentList: document.getElementById('portalMessengerReportAttachmentList'),
            reportModalClose: document.getElementById('portalMessengerReportModalClose'),
            reportModalCancel: document.getElementById('portalMessengerReportModalCancel'),
            reportSubmit: document.getElementById('portalMessengerReportSubmit'),
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function getCallRingtoneAudioContext() {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;

            if (!AudioContextClass) {
                return null;
            }

            if (!state.call.ringtoneAudioContext) {
                state.call.ringtoneAudioContext = new AudioContextClass();
            }

            return state.call.ringtoneAudioContext;
        }

        function resumeCallRingtoneAudioContext() {
            const context = getCallRingtoneAudioContext();

            if (!context) {
                return Promise.resolve(null);
            }

            if (context.state !== 'suspended') {
                return Promise.resolve(context);
            }

            return context.resume()
                .then(() => context)
                .catch(() => null);
        }

        function clearCallRingtoneToneNodes() {
            state.call.ringtoneToneNodes.forEach(({ oscillator, gain }) => {
                try {
                    oscillator.stop();
                } catch (error) {
                    // The tone may already be stopped.
                }

                try {
                    oscillator.disconnect();
                    gain.disconnect();
                } catch (error) {
                    // Detached nodes do not need more cleanup.
                }
            });

            state.call.ringtoneToneNodes = [];
        }

        function playCallRingtoneTonePulse() {
            const context = getCallRingtoneAudioContext();

            if (!context || context.state !== 'running' || state.call.mode !== 'incoming') {
                return;
            }

            clearCallRingtoneToneNodes();

            const startAt = context.currentTime;
            const stopAt = startAt + 0.85;

            [440, 554].forEach((frequency) => {
                const oscillator = context.createOscillator();
                const gain = context.createGain();

                oscillator.type = 'sine';
                oscillator.frequency.value = frequency;
                gain.gain.setValueAtTime(0.0001, startAt);
                gain.gain.exponentialRampToValueAtTime(0.16, startAt + 0.04);
                gain.gain.exponentialRampToValueAtTime(0.0001, stopAt);

                oscillator.connect(gain);
                gain.connect(context.destination);
                oscillator.start(startAt);
                oscillator.stop(stopAt);

                state.call.ringtoneToneNodes.push({ oscillator, gain });
            });
        }

        function playCallRingtoneTone() {
            resumeCallRingtoneAudioContext().then((context) => {
                if (!context || state.call.mode !== 'incoming') {
                    return;
                }

                playCallRingtoneTonePulse();

                if (!state.call.ringtoneToneTimer) {
                    state.call.ringtoneToneTimer = window.setInterval(playCallRingtoneTonePulse, 1600);
                }
            });
        }

        function stopCallRingtoneTone() {
            if (state.call.ringtoneToneTimer) {
                window.clearInterval(state.call.ringtoneToneTimer);
                state.call.ringtoneToneTimer = null;
            }

            clearCallRingtoneToneNodes();
        }

        function resetCallRingtone() {
            if (!elements.callRingtone) {
                return;
            }

            try {
                elements.callRingtone.currentTime = 0;
            } catch (error) {
                // Audio metadata may not be ready yet.
            }
        }

        function unlockCallRingtone() {
            if (!elements.callRingtone || state.call.ringtoneUnlocked) {
                return;
            }

            const originalVolume = elements.callRingtone.volume;
            elements.callRingtone.volume = 0;
            resumeCallRingtoneAudioContext();

            const playPromise = elements.callRingtone.play();

            if (!playPromise?.then) {
                elements.callRingtone.pause();
                elements.callRingtone.volume = originalVolume;
                resetCallRingtone();
                state.call.ringtoneUnlocked = true;
                return;
            }

            playPromise
                .then(() => {
                    elements.callRingtone.pause();
                    elements.callRingtone.volume = originalVolume;
                    resetCallRingtone();
                    state.call.ringtoneUnlocked = true;
                })
                .catch(() => {
                    elements.callRingtone.volume = originalVolume;
                });

            if (state.call.mode === 'incoming') {
                playCallRingtone();
            }
        }

        function playCallRingtone() {
            if (!elements.callRingtone) {
                playCallRingtoneTone();
                return;
            }

            elements.callRingtone.pause();
            elements.callRingtone.volume = 1;
            resetCallRingtone();

            const playPromise = elements.callRingtone.play();

            if (playPromise?.then) {
                playPromise
                    .then(stopCallRingtoneTone)
                    .catch((error) => {
                        playCallRingtoneTone();
                        console.info('Incoming call ringtone could not play until the portal receives a click.', error);
                    });
            } else {
                stopCallRingtoneTone();
            }
        }

        function stopCallRingtone() {
            stopCallRingtoneTone();

            if (!elements.callRingtone) {
                return;
            }

            elements.callRingtone.pause();
            resetCallRingtone();
        }

        ['click', 'keydown', 'touchstart'].forEach((eventName) => {
            window.addEventListener(eventName, unlockCallRingtone);
        });

        function showAttachmentModal() {
            if (!elements.attachmentModal) {
                return false;
            }

            elements.attachmentModal.classList.add('is-open');
            elements.attachmentModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            return true;
        }

        function hideAttachmentModal() {
            if (!elements.attachmentModal) {
                return;
            }

            elements.attachmentModal.classList.remove('is-open');
            elements.attachmentModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        function isAudioCallSupported() {
            return window.isSecureContext
                && typeof window.RTCPeerConnection !== 'undefined'
                && Boolean(navigator.mediaDevices?.getUserMedia);
        }

        function generateCallId() {
            if (window.crypto?.randomUUID) {
                return window.crypto.randomUUID();
            }

            return `call-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
        }

        function getContactById(userId) {
            return state.contacts.find((contact) => Number(contact.id) === Number(userId)) || null;
        }

        function isCompactMessengerViewport() {
            return window.matchMedia('(max-width: 991.98px)').matches;
        }

        function syncCompactMessengerLayout() {
            elements.panel.classList.remove('is-mobile-list', 'is-mobile-thread');

            if (!state.isOpen || !isCompactMessengerViewport() || isMultiMode()) {
                return;
            }

            if (state.selectedUserId) {
                elements.panel.classList.add('is-mobile-thread');
                return;
            }

            elements.panel.classList.add('is-mobile-list');
        }

        function getCallPartnerMeta(contact) {
            return [contact?.department, contact?.section].filter(Boolean).join(' • ') || `@${contact?.username || ''}`;
        }

        function updateCallButtonState() {
            const contact = state.selectedUserId ? getContactById(state.selectedUserId) : null;
            const liveCall = ['calling', 'ringing', 'connecting', 'connected', 'incoming'].includes(state.call.mode);
            const available = liveCall || (!isMultiMode() && Boolean(state.selectedUserId) && Boolean(contact?.is_online));
            elements.callButton.disabled = !available;
            elements.callButton.title = liveCall
                ? 'Show audio call'
                : contact && !contact.is_online
                    ? 'Audio call unavailable while this contact is offline'
                    : 'Start audio call';
            elements.callButton.classList.toggle('is-live', liveCall);

            if (elements.reportRangeButton) {
                elements.reportRangeButton.disabled = isMultiMode() || !state.selectedUserId || !state.messages.length;
            }
        }

        function updateCallModalUI() {
            const isOpen = state.call.mode !== 'idle' && !state.call.modalHidden;
            elements.callModal.classList.toggle('is-open', isOpen);
            elements.callModal.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
            elements.callAvatar.src = state.call.partnerAvatar || '{{ $messengerCurrentUser->profile_photo_url }}';
            elements.callName.textContent = state.call.partnerName || 'Audio call';
            elements.callMeta.textContent = state.call.partnerMeta || 'Calls work only while the portal is open.';

            const incoming = state.call.mode === 'incoming';
            const active = ['calling', 'ringing', 'connecting', 'connected'].includes(state.call.mode);

            elements.callModal.classList.toggle('is-incoming', incoming);
            elements.callAccept.style.display = '';
            elements.callDismiss.style.display = '';
            elements.callDecline.style.display = '';
            elements.callAccept.classList.toggle('is-hidden', !incoming);
            elements.callDismiss.classList.toggle('is-hidden', incoming);
            elements.callDecline.innerHTML = active
                ? "<i class='bx bx-phone-off'></i><span>Hang up</span>"
                : "<i class='bx bx-phone-off'></i><span>Decline</span>";

            if (!isOpen) {
                document.body.style.overflow = '';
            }
        }

        function setCallPresentation(contact, status, mode) {
            state.call.mode = mode;
            state.call.modalHidden = false;
            state.call.partnerName = contact?.full_name || 'MISsenger';
            state.call.partnerMeta = getCallPartnerMeta(contact);
            state.call.partnerAvatar = contact?.avatar_url || '{{ $messengerCurrentUser->profile_photo_url }}';
            elements.callStatus.textContent = status;
            if (mode === 'incoming') {
                playCallRingtone();
            } else {
                stopCallRingtone();
            }
            updateCallButtonState();
            updateCallModalUI();
        }

        function resetCallState() {
            stopCallRingtone();

            if (state.call.incomingTimeoutId) {
                window.clearTimeout(state.call.incomingTimeoutId);
                state.call.incomingTimeoutId = null;
            }

            if (state.call.outgoingTimeoutId) {
                window.clearTimeout(state.call.outgoingTimeoutId);
                state.call.outgoingTimeoutId = null;
            }

            state.call.currentCallId = null;
            state.call.partnerId = null;
            state.call.partnerName = '';
            state.call.partnerMeta = '';
            state.call.partnerAvatar = '';
            state.call.mode = 'idle';
            state.call.modalHidden = false;
            state.call.isTerminating = false;
            state.call.offer = null;
            state.call.pendingCandidates = [];
        }

        function scheduleIncomingCallTimeout(contact, senderId, callId) {
            if (state.call.incomingTimeoutId) {
                window.clearTimeout(state.call.incomingTimeoutId);
            }

            state.call.incomingTimeoutId = window.setTimeout(async function () {
                if (
                    state.call.mode !== 'incoming'
                    || state.call.currentCallId !== callId
                    || state.call.partnerId !== senderId
                ) {
                    return;
                }

                await sendCallSignal(senderId, {
                    call_id: callId,
                    signal_type: 'reject',
                }).catch(() => {});

                await finishCallSession({
                    remote: false,
                    toast: `Missed call from ${contact.full_name}.`,
                });
            }, 30000);
        }

        function scheduleOutgoingCallTimeout(contact, recipientId, callId) {
            if (state.call.outgoingTimeoutId) {
                window.clearTimeout(state.call.outgoingTimeoutId);
            }

            state.call.outgoingTimeoutId = window.setTimeout(async function () {
                if (
                    !['calling', 'ringing'].includes(state.call.mode)
                    || state.call.currentCallId !== callId
                    || state.call.partnerId !== recipientId
                ) {
                    return;
                }

                await finishCallSession({
                    remote: false,
                    sendHangup: true,
                    toast: `${contact.full_name || 'This contact'} did not answer.`,
                });
            }, 30000);
        }

        function clearOutgoingCallTimeout() {
            if (!state.call.outgoingTimeoutId) {
                return;
            }

            window.clearTimeout(state.call.outgoingTimeoutId);
            state.call.outgoingTimeoutId = null;
        }

        function dismissCallModal() {
            if (state.call.mode === 'idle') {
                return;
            }

            state.call.modalHidden = true;
            updateCallModalUI();
        }

        async function request(url, options = {}) {
            const isFormData = options.body instanceof FormData;
            const response = await fetch(url, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    ...(!isFormData && options.body ? { 'Content-Type': 'application/json' } : {}),
                    ...(options.headers || {}),
                },
                ...options,
            });

            const contentType = response.headers.get('content-type') || '';
            const data = contentType.includes('application/json')
                ? await response.json()
                : { message: await response.text() };

            if (!response.ok) {
                const error = new Error(data?.message || 'Request failed.');
                error.response = { data, status: response.status };
                throw error;
            }

            return data;
        }

        function routeFor(template, userId) {
            return template.replace('__USER__', String(userId));
        }

        function conversationRouteFor(template, conversationId) {
            return template.replace('__CONVERSATION__', String(conversationId));
        }

        function conversationUrlFor(userId, params = {}) {
            const query = new URLSearchParams();
            Object.entries(params).forEach(([key, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    query.set(key, String(value));
                }
            });

            const route = routeFor(root.dataset.conversationUrlTemplate, userId);
            const queryString = query.toString();

            return queryString ? `${route}?${queryString}` : route;
        }

        async function sendCallSignal(userId, payload) {
            return request(routeFor(root.dataset.callSignalUrlTemplate, userId), {
                method: 'POST',
                body: JSON.stringify(payload),
            });
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatSeenDate(value) {
            if (!value) {
                return '';
            }

            const date = new Date(value);

            if (Number.isNaN(date.getTime())) {
                return '';
            }

            return date.toLocaleString([], {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
            });
        }

        function formatFileSize(bytes) {
            const size = Number(bytes || 0);

            if (!size) {
                return 'Unknown size';
            }

            if (size < 1024) {
                return `${size} B`;
            }

            if (size < 1024 * 1024) {
                return `${(size / 1024).toFixed(1)} KB`;
            }

            return `${(size / (1024 * 1024)).toFixed(1)} MB`;
        }

        function hasPendingAttachment() {
            return Boolean(state.pendingAttachment);
        }

        function canComposeMessage() {
            return Boolean(elements.input.value.trim() || hasPendingAttachment());
        }

        function updateComposerState() {
            const hasSelectedUser = Boolean(state.selectedUserId) && !isMultiMode();
            const canTargetRecipients = isMultiMode()
                ? state.selectedRecipientIds.length > 0
                : hasSelectedUser;

            elements.composer.style.display = canTargetRecipients ? '' : 'none';
            elements.input.disabled = !canTargetRecipients;
            elements.attachButton.disabled = !canTargetRecipients || state.isLoadingConversation;
            elements.send.disabled = state.isLoadingConversation || !canTargetRecipients || !canComposeMessage();
            elements.pendingAttachment.classList.toggle('is-visible', hasPendingAttachment());
            updateCallButtonState();

            if (!hasPendingAttachment()) {
                elements.pendingAttachmentName.textContent = '';
                elements.pendingAttachmentMeta.textContent = '';
                return;
            }

            elements.pendingAttachmentName.textContent = state.pendingAttachment.name;
            elements.pendingAttachmentMeta.textContent = formatFileSize(state.pendingAttachment.size);
        }

        async function ensureLocalAudioStream() {
            if (!window.isSecureContext) {
                throw new Error('Audio calling requires HTTPS or localhost so the browser can access the microphone.');
            }

            if (!navigator.mediaDevices?.getUserMedia) {
                throw new Error('This browser does not support microphone calling.');
            }

            if (state.call.localStream) {
                return state.call.localStream;
            }

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            state.call.localStream = stream;
            elements.localAudio.srcObject = stream;
            return stream;
        }

        async function addQueuedIceCandidates() {
            if (!state.call.peerConnection || !state.call.pendingCandidates.length) {
                return;
            }

            const candidates = state.call.pendingCandidates.slice();
            state.call.pendingCandidates = [];

            for (const candidate of candidates) {
                try {
                    await state.call.peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
                } catch (error) {
                    console.error('Unable to add queued ICE candidate.', error);
                }
            }
        }

        function normalizeSessionDescription(payload, fallbackType = '') {
            let description = payload;

            if (!description) {
                return null;
            }

            if (typeof description === 'string') {
                try {
                    description = JSON.parse(description);
                } catch (error) {
                    return {
                        type: fallbackType,
                        sdp: description.replace(/\r?\n/g, '\r\n').trim(),
                    };
                }
            }

            if (typeof description !== 'object') {
                return null;
            }

            if (description.sdp && typeof description.sdp === 'object') {
                description = description.sdp;
            }

            const type = typeof description.type === 'string' ? description.type.trim() : '';
            let sdp = typeof description.sdp === 'string' ? description.sdp : '';
            const encoding = typeof description.encoding === 'string' ? description.encoding.trim().toLowerCase() : '';

            if (encoding === 'base64' && sdp) {
                try {
                    sdp = atob(sdp);
                } catch (error) {
                    console.error('Unable to decode base64 session description.', error);
                    return null;
                }
            }

            if (sdp.startsWith('{')) {
                try {
                    const nestedDescription = JSON.parse(sdp);

                    if (nestedDescription && typeof nestedDescription === 'object') {
                        return normalizeSessionDescription({
                            type: nestedDescription.type || type || fallbackType,
                            sdp: nestedDescription.sdp || '',
                        }, fallbackType);
                    }
                } catch (error) {
                    // Keep the original SDP string and continue normalizing below.
                }
            }

            sdp = sdp
                .replace(/\\r\\n/g, '\r\n')
                .replace(/\\n/g, '\n')
                .replace(/\\r/g, '\r')
                .replace(/\r\n|\r|\n/g, '\r\n');

            const resolvedType = type || fallbackType;

            if (!resolvedType || !sdp) {
                return null;
            }

            return { type: resolvedType, sdp };
        }

        function encodeSessionDescription(description) {
            const normalized = normalizeSessionDescription(description);

            if (!normalized) {
                return null;
            }

            return {
                type: normalized.type,
                sdp: btoa(normalized.sdp),
                encoding: 'base64',
            };
        }

        function createPeerConnection() {
            const connection = new RTCPeerConnection({
                iceServers: [
                    { urls: 'stun:stun.l.google.com:19302' },
                    { urls: 'stun:stun1.l.google.com:19302' },
                ],
            });

            const remoteStream = new MediaStream();
            state.call.remoteStream = remoteStream;
            elements.remoteAudio.srcObject = remoteStream;

            connection.ontrack = (event) => {
                event.streams[0]?.getTracks().forEach((track) => {
                    remoteStream.addTrack(track);
                });
            };

            connection.onicecandidate = (event) => {
                if (!event.candidate || !state.call.partnerId || !state.call.currentCallId) {
                    return;
                }

                sendCallSignal(state.call.partnerId, {
                    call_id: state.call.currentCallId,
                    signal_type: 'ice-candidate',
                    candidate: event.candidate.toJSON ? event.candidate.toJSON() : {
                        candidate: event.candidate.candidate,
                        sdpMid: event.candidate.sdpMid,
                        sdpMLineIndex: event.candidate.sdpMLineIndex,
                    },
                }).catch(() => {});
            };

            connection.onconnectionstatechange = () => {
                const nextState = connection.connectionState;

                if (state.call.isTerminating) {
                    return;
                }

                if (nextState === 'connected') {
                    clearOutgoingCallTimeout();
                    const contact = getContactById(state.call.partnerId);
                    setCallPresentation(contact, 'Connected', 'connected');
                    return;
                }

                if (['failed', 'closed', 'disconnected'].includes(nextState)) {
                    finishCallSession({
                        remote: false,
                        toast: nextState === 'failed' ? 'Audio call failed.' : 'Audio call ended.',
                        sendHangup: nextState !== 'closed',
                    });
                }
            };

            state.call.peerConnection = connection;
            return connection;
        }

        async function preparePeerConnection() {
            const stream = await ensureLocalAudioStream();
            const connection = createPeerConnection();

            stream.getTracks().forEach((track) => {
                connection.addTrack(track, stream);
            });

            return connection;
        }

        function stopStream(stream) {
            if (!stream) {
                return;
            }

            stream.getTracks().forEach((track) => track.stop());
        }

        async function finishCallSession({ remote = false, sendHangup = false, toast = '' } = {}) {
            state.call.isTerminating = true;

            if (sendHangup && state.call.partnerId && state.call.currentCallId) {
                sendCallSignal(state.call.partnerId, {
                    call_id: state.call.currentCallId,
                    signal_type: 'hangup',
                }).catch(() => {});
            }

            try {
                state.call.peerConnection?.close();
            } catch (error) {
                console.error('Unable to close peer connection.', error);
            }

            stopStream(state.call.localStream);
            state.call.localStream = null;
            state.call.remoteStream = null;
            state.call.peerConnection = null;
            elements.localAudio.srcObject = null;
            elements.remoteAudio.srcObject = null;

            const shouldKeepName = remote && state.call.partnerName;
            const contact = shouldKeepName
                ? {
                    full_name: state.call.partnerName,
                    avatar_url: state.call.partnerAvatar,
                    department: '',
                    section: '',
                    username: '',
                }
                : null;

            resetCallState();
            updateCallButtonState();
            updateCallModalUI();

            if (toast && window.Lobibox) {
                Lobibox.notify(remote ? 'info' : 'warning', {
                    size: 'mini',
                    rounded: true,
                    delayIndicator: true,
                    sound: false,
                    position: 'top right',
                    icon: remote ? 'bx bx-phone-call' : 'bx bx-phone-off',
                    msg: toast,
                });
            }

            if (contact) {
                setCallPresentation(contact, 'Call ended', 'idle');
            }
        }

        async function startOutgoingCall() {
            if (!state.selectedUserId || isMultiMode()) {
                return;
            }

            const contact = getContactById(state.selectedUserId);

            if (!contact?.is_online) {
                if (window.Lobibox) {
                    Lobibox.notify('warning', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-phone-off',
                        msg: `${contact?.full_name || 'This contact'} is offline and cannot receive calls right now.`,
                    });
                }
                return;
            }

            if (!isAudioCallSupported()) {
                if (window.Lobibox) {
                    Lobibox.notify('warning', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-lock-alt',
                        msg: 'Audio calls require HTTPS or localhost with microphone access.',
                    });
                }
                return;
            }

            if (state.call.mode !== 'idle') {
                return;
            }

            state.call.partnerId = Number(state.selectedUserId);
            state.call.currentCallId = generateCallId();

            setCallPresentation(contact, 'Calling...', 'calling');

            try {
                const connection = await preparePeerConnection();
                const offer = await connection.createOffer({
                    offerToReceiveAudio: true,
                });

                await connection.setLocalDescription(offer);

                const localDescription = connection.localDescription?.toJSON
                    ? connection.localDescription.toJSON()
                    : {
                        type: offer.type,
                        sdp: offer.sdp,
                    };

                const encodedDescription = encodeSessionDescription(localDescription);

                if (!encodedDescription) {
                    throw new Error('Unable to prepare the outgoing call offer.');
                }

                await sendCallSignal(state.call.partnerId, {
                    call_id: state.call.currentCallId,
                    signal_type: 'offer',
                    sdp: encodedDescription,
                });

                setCallPresentation(contact, 'Ringing...', 'ringing');
                scheduleOutgoingCallTimeout(contact, state.call.partnerId, state.call.currentCallId);
            } catch (error) {
                console.error('Unable to start audio call.', error);
                await finishCallSession();

                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: error?.message || 'Unable to start the audio call.',
                    });
                }
            }
        }

        async function answerIncomingCall() {
            if (state.call.mode !== 'incoming' || !state.call.partnerId || !state.call.offer) {
                return;
            }

            const contact = getContactById(state.call.partnerId) || {
                id: state.call.partnerId,
                full_name: state.call.partnerName,
                avatar_url: state.call.partnerAvatar,
                department: '',
                section: '',
                username: '',
            };

            window.portalMessenger.openForUser(state.call.partnerId);
            setCallPresentation(contact, 'Connecting...', 'connecting');

            try {
                const connection = await preparePeerConnection();
                const remoteOffer = normalizeSessionDescription(state.call.offer, 'offer');

                if (!remoteOffer) {
                    throw new Error('Incoming call offer is incomplete.');
                }

                await connection.setRemoteDescription(remoteOffer);
                await addQueuedIceCandidates();

                const answer = await connection.createAnswer();
                await connection.setLocalDescription(answer);

                const localDescription = connection.localDescription?.toJSON
                    ? connection.localDescription.toJSON()
                    : {
                        type: answer.type,
                        sdp: answer.sdp,
                    };

                const encodedDescription = encodeSessionDescription(localDescription);

                if (!encodedDescription) {
                    throw new Error('Unable to prepare the call answer.');
                }

                await sendCallSignal(state.call.partnerId, {
                    call_id: state.call.currentCallId,
                    signal_type: 'answer',
                    sdp: encodedDescription,
                });
            } catch (error) {
                console.error('Unable to answer call.', error);
                if (state.call.partnerId && state.call.currentCallId) {
                    await sendCallSignal(state.call.partnerId, {
                        call_id: state.call.currentCallId,
                        signal_type: 'reject',
                    }).catch(() => {});
                }

                await finishCallSession();

                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: error?.message || 'Unable to answer the audio call.',
                    });
                }
            }
        }

        async function rejectIncomingCall() {
            if (state.call.partnerId && state.call.currentCallId) {
                await sendCallSignal(state.call.partnerId, {
                    call_id: state.call.currentCallId,
                    signal_type: 'reject',
                }).catch(() => {});
            }

            await finishCallSession();
        }

        async function handleCallSignal(payload) {
            const signal = payload?.signal || {};
            const sender = payload?.sender || {};
            const senderId = Number(sender.id || 0);

            if (!senderId || !signal.call_id || !signal.signal_type) {
                return;
            }

            const contact = getContactById(senderId) || {
                id: senderId,
                full_name: sender.full_name || sender.username || 'MISsenger',
                avatar_url: sender.avatar_url || '{{ $messengerCurrentUser->profile_photo_url }}',
                department: sender.department || '',
                section: sender.section || '',
                username: sender.username || '',
            };

            if (signal.signal_type === 'offer') {
                if (
                    state.call.mode === 'incoming'
                    && state.call.currentCallId === signal.call_id
                    && state.call.partnerId === senderId
                ) {
                    scheduleIncomingCallTimeout(contact, senderId, signal.call_id);
                    return;
                }

                if (state.call.mode !== 'idle') {
                    await sendCallSignal(senderId, {
                        call_id: signal.call_id,
                        signal_type: 'busy',
                    }).catch(() => {});
                    return;
                }

                state.call.currentCallId = signal.call_id;
                state.call.partnerId = senderId;
                state.call.partnerName = contact.full_name;
                state.call.partnerMeta = getCallPartnerMeta(contact);
                state.call.partnerAvatar = contact.avatar_url;
                state.call.offer = normalizeSessionDescription(signal.sdp, 'offer') || signal.sdp || null;
                state.call.pendingCandidates = [];
                setCallPresentation(contact, 'Incoming call', 'incoming');
                scheduleIncomingCallTimeout(contact, senderId, signal.call_id);
                togglePanel(true);
                setComposeMode('direct');
                openConversation(senderId, true, { preserveScroll: true }).catch(function () {});
                return;
            }

            if (senderId !== state.call.partnerId || signal.call_id !== state.call.currentCallId) {
                return;
            }

            if (signal.signal_type === 'answer' && state.call.peerConnection && signal.sdp) {
                clearOutgoingCallTimeout();
                const remoteAnswer = normalizeSessionDescription(signal.sdp, 'answer');

                if (!remoteAnswer) {
                    throw new Error('Incoming call answer is incomplete.');
                }

                await state.call.peerConnection.setRemoteDescription(remoteAnswer);
                await addQueuedIceCandidates();
                setCallPresentation(contact, 'Connecting...', 'connecting');
                return;
            }

            if (signal.signal_type === 'ice-candidate' && signal.candidate) {
                if (!state.call.peerConnection || !state.call.peerConnection.remoteDescription) {
                    state.call.pendingCandidates.push(signal.candidate);
                    return;
                }

                try {
                    await state.call.peerConnection.addIceCandidate(new RTCIceCandidate(signal.candidate));
                } catch (error) {
                    console.error('Unable to add ICE candidate.', error);
                }
                return;
            }

            if (signal.signal_type === 'hangup') {
                await finishCallSession({
                    remote: true,
                    toast: `${contact.full_name} ended the call.`,
                });
                return;
            }

            if (signal.signal_type === 'reject') {
                await finishCallSession({
                    remote: true,
                    toast: `${contact.full_name} declined the call.`,
                });
                return;
            }

            if (signal.signal_type === 'busy') {
                await finishCallSession({
                    remote: true,
                    toast: `${contact.full_name} is already in another call.`,
                });
            }
        }

        function clearPendingAttachment() {
            state.pendingAttachment = null;
            elements.attachmentInput.value = '';
            updateComposerState();
        }

        function renderAttachment(attachment) {
            if (!attachment) {
                return '';
            }

            return `
                <div class="portal-messenger__attachment">
                    <div class="portal-messenger__attachment-name">${escapeHtml(attachment.original_name)}</div>
                    <div class="portal-messenger__attachment-meta">${escapeHtml(attachment.mime_type || 'File')} • ${escapeHtml(formatFileSize(attachment.size_bytes))}</div>
                    <div class="portal-messenger__attachment-actions">
                        <button
                            type="button"
                            class="portal-messenger__attachment-link portal-messenger__attachment-link--button"
                            data-attachment-view="${escapeHtml(attachment.view_url)}"
                            data-attachment-download="${escapeHtml(attachment.download_url)}"
                            data-attachment-name="${escapeHtml(attachment.original_name)}"
                            data-attachment-mime="${escapeHtml(attachment.mime_type || '')}"
                            data-attachment-size="${escapeHtml(attachment.size_bytes)}"
                        >
                            <i class='bx bx-show'></i>
                            <span>View</span>
                        </button>
                        <a href="${escapeHtml(attachment.download_url)}" class="portal-messenger__attachment-link">
                            <i class='bx bx-download'></i>
                            <span>Download</span>
                        </a>
                    </div>
                </div>
            `;
        }

        function renderAttachmentPreviewFallback(message) {
            return `
                <div class="portal-messenger__preview-empty">
                    <div>
                        <div class="portal-messenger__placeholder-icon mb-3"><i class='bx bx-file'></i></div>
                        <p class="mb-2">${escapeHtml(message)}</p>
                        <p class="mb-0 small">Use Download if preview is not available for this file type.</p>
                    </div>
                </div>
            `;
        }

        function renderAttachmentFrame(viewUrl, originalName) {
            return `<iframe src="${escapeHtml(viewUrl)}" class="portal-messenger__preview-frame" title="${escapeHtml(originalName || 'Attachment preview')}"></iframe>`;
        }

        async function openAttachmentPreview({ viewUrl, downloadUrl, originalName, mimeType, sizeBytes }) {
            if (!showAttachmentModal()) {
                if (window.Lobibox) {
                    Lobibox.notify('warning', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: 'Attachment preview modal is not available right now.',
                    });
                }

                return;
            }

            elements.attachmentModalTitle.textContent = originalName || 'Attachment preview';
            elements.attachmentModalMeta.textContent = `${mimeType || 'File'} • ${formatFileSize(sizeBytes)}`;
            elements.attachmentModalDownload.href = downloadUrl || '#';
            elements.attachmentModalDownload.setAttribute('download', originalName || 'attachment');

            const lowerMimeType = String(mimeType || '').toLowerCase();

            if (lowerMimeType.startsWith('image/')) {
                elements.attachmentModalBody.innerHTML = `<img src="${escapeHtml(viewUrl)}" alt="${escapeHtml(originalName || 'Attachment')}" class="portal-messenger__preview-image">`;
                return;
            }

            if (lowerMimeType === 'application/pdf') {
                elements.attachmentModalBody.innerHTML = renderAttachmentFrame(viewUrl, originalName);
                return;
            }

            if (lowerMimeType.startsWith('text/')) {
                elements.attachmentModalBody.innerHTML = `<div class="portal-messenger__preview-empty">Loading preview...</div>`;

                try {
                    const response = await fetch(viewUrl, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const text = await response.text();
                    elements.attachmentModalBody.innerHTML = `<pre class="portal-messenger__preview-text">${escapeHtml(text)}</pre>`;
                } catch (error) {
                    elements.attachmentModalBody.innerHTML = renderAttachmentPreviewFallback('This text file could not be previewed right now.');
                }

                return;
            }

            elements.attachmentModalBody.innerHTML = renderAttachmentFrame(viewUrl, originalName);
        }

        function updateBadge() {
            elements.badge.textContent = state.totalUnread;
            elements.badge.classList.toggle('is-visible', state.totalUnread > 0);
            elements.launcher.classList.toggle('is-alerting', state.totalUnread > 0 && !state.isOpen);
        }

        function isMultiMode() {
            return state.composeMode === 'multi';
        }

        function getFilteredContactIds() {
            return state.filteredContacts.map((contact) => Number(contact.id));
        }

        function areAllFilteredRecipientsSelected() {
            const filteredIds = getFilteredContactIds();

            if (!filteredIds.length) {
                return false;
            }

            return filteredIds.every((id) => state.selectedRecipientIds.includes(id));
        }

        function getPresenceLabel(contact) {
            return contact?.is_online ? 'Online' : 'Offline';
        }

        function formatUnreadBadge(count) {
            const unread = Number(count || 0);

            return unread > 99 ? '99+' : String(unread);
        }

        function applyContactFilter() {
            const needle = state.search.trim().toLowerCase();

            if (!needle) {
                state.filteredContacts = state.contacts.slice();
                return;
            }

            state.filteredContacts = state.contacts.filter((contact) => {
                const haystack = [
                    contact.full_name,
                    contact.username,
                    contact.department,
                    contact.section,
                    contact.latest_message,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                return haystack.includes(needle);
            });
        }

        function renderContacts() {
            applyContactFilter();

            if (!state.filteredContacts.length) {
                elements.contacts.innerHTML = '<div class="portal-messenger__empty is-visible"><div class="portal-messenger__placeholder-card"><div class="portal-messenger__placeholder-icon"><i class="bx bx-search-alt"></i></div><h4 class="portal-messenger__title mb-2">No matches</h4><p class="portal-messenger__subtitle">Try a different name or section.</p></div></div>';
                return;
            }

            const selectAllRow = isMultiMode()
                ? `
                    <button type="button" class="portal-messenger__select-all" id="portalMessengerSelectAllRow">
                        <input type="checkbox" class="portal-messenger__selector" tabindex="-1" ${areAllFilteredRecipientsSelected() ? 'checked' : ''} aria-hidden="true">
                        <span class="portal-messenger__select-all-label">Select all</span>
                    </button>
                `
                : '';

            elements.contacts.innerHTML = selectAllRow + state.filteredContacts.map((contact) => {
                const unread = Number(contact.unread_count || 0);
                const isSelected = state.selectedRecipientIds.includes(Number(contact.id));
                const classes = [
                    'portal-messenger__contact',
                    !isMultiMode() && state.selectedUserId === contact.id ? 'is-active' : '',
                    isMultiMode() ? 'is-selecting' : '',
                    isSelected ? 'is-selected' : '',
                    unread > 0 ? 'is-unread' : '',
                ].filter(Boolean).join(' ');

                return `
                    <button type="button" class="${classes}" data-contact-id="${contact.id}">
                        ${isMultiMode() ? `<input type="checkbox" class="portal-messenger__selector" tabindex="-1" ${isSelected ? 'checked' : ''} aria-hidden="true">` : ''}
                        <div class="portal-messenger__avatar-shell">
                            <img src="${escapeHtml(contact.avatar_url)}" alt="${escapeHtml(contact.full_name)}" class="portal-messenger__avatar">
                            <span class="portal-messenger__status-dot ${contact.is_online ? 'is-online' : ''}"></span>
                        </div>
                        <div class="portal-messenger__contact-body">
                            <div class="portal-messenger__contact-row">
                                <p class="portal-messenger__contact-name">${escapeHtml(contact.full_name)}</p>
                                <span class="portal-messenger__contact-unread ${unread > 0 ? 'is-visible' : ''}" aria-label="${unread} unread message${unread === 1 ? '' : 's'}">${escapeHtml(formatUnreadBadge(unread))}</span>
                            </div>
                            <p class="portal-messenger__contact-meta">${escapeHtml(contact.section || ('@' + contact.username))}</p>
                            <p class="portal-messenger__presence">
                                <span class="portal-messenger__presence-bullet ${contact.is_online ? 'is-online' : ''}"></span>
                                <span>${escapeHtml(getPresenceLabel(contact))}</span>
                            </p>
                        </div>
                    </button>
                `;
            }).join('');
        }

        function getOldestLoadedMessageId() {
            return state.messages.reduce((oldest, message) => {
                const id = Number(message.id || 0);
                return id && (!oldest || id < oldest) ? id : oldest;
            }, null);
        }

        function getNewestLoadedMessageId() {
            return state.messages.reduce((newest, message) => {
                const id = Number(message.id || 0);
                return id && id > newest ? id : newest;
            }, 0);
        }

        function mergeMessages(messages, placement = 'append') {
            if (!Array.isArray(messages) || !messages.length) {
                return false;
            }

            const knownIds = new Set(state.messages.map((message) => Number(message.id)));
            const freshMessages = messages.filter((message) => {
                const id = Number(message.id || 0);
                return id && !knownIds.has(id);
            });

            if (!freshMessages.length) {
                return false;
            }

            state.messages = placement === 'prepend'
                ? freshMessages.concat(state.messages)
                : state.messages.concat(freshMessages);

            state.messages.sort((a, b) => Number(a.id || 0) - Number(b.id || 0));

            return true;
        }

        function applyConversationPayload(data, requestedUserId, { replace = false, placement = 'append', updateOlder = true } = {}) {
            let changed = false;

            if (replace) {
                state.messages = data.messages || [];
                changed = true;
            } else {
                changed = mergeMessages(data.messages || [], placement);
            }

            state.conversationId = data.conversation_id || state.conversationId || null;
            state.totalUnread = Number(data.total_unread || 0);
            if (updateOlder) {
                state.hasMoreOlderMessages = Boolean(data.has_more_older);
            }
            state.hasMoreNewerMessages = Boolean(data.has_more_newer);
            updateBadge();

            if (data.contact) {
                const idx = state.contacts.findIndex((item) => Number(item.id) === requestedUserId);
                if (idx !== -1) {
                    state.contacts[idx] = {
                        ...state.contacts[idx],
                        ...data.contact,
                        unread_count: 0,
                    };
                }
            }

            upsertContacts(state.contacts);
            renderContacts();

            return changed;
        }

        function resetConversationWindow() {
            state.conversationId = null;
            state.messages = [];
            state.hasMoreOlderMessages = false;
            state.hasMoreNewerMessages = false;
            state.isLoadingOlderMessages = false;
            state.isSyncingConversation = false;
        }

        function renderMessages(options = {}) {
            const hasSelectedUser = Boolean(state.selectedUserId) && !isMultiMode();
            const hasMessages = state.messages.length > 0;
            const previousScrollHeight = elements.messages.scrollHeight;
            const previousScrollTop = elements.messages.scrollTop;
            const previousDistanceFromBottom = previousScrollHeight - previousScrollTop - elements.messages.clientHeight;
            const wasNearBottom = previousDistanceFromBottom <= 80;

            elements.placeholder.style.display = hasSelectedUser ? 'none' : 'grid';
            elements.messages.style.display = hasSelectedUser && hasMessages ? 'flex' : 'none';
            elements.empty.classList.toggle('is-visible', hasSelectedUser && !hasMessages);
            elements.selectionBar.classList.toggle('is-visible', isMultiMode());
            updateComposerState();

            if (!hasSelectedUser || !hasMessages) {
                if (isMultiMode()) {
                    const selectedContacts = state.contacts.filter((contact) => state.selectedRecipientIds.includes(Number(contact.id)));
                    elements.threadStatus.classList.remove('is-online');
                    elements.threadName.textContent = state.selectedRecipientIds.length
                        ? `${state.selectedRecipientIds.length} recipients selected`
                        : 'Select recipients';
                    elements.threadMeta.textContent = state.selectedRecipientIds.length
                        ? selectedContacts.map((contact) => contact.full_name).slice(0, 3).join(', ') + (selectedContacts.length > 3 ? ` +${selectedContacts.length - 3} more` : '')
                        : 'Choose one or more users from the list to send the same message.';
                    elements.selectionNote.textContent = state.selectedRecipientIds.length
                        ? `Sending to ${state.selectedRecipientIds.length} selected ${state.selectedRecipientIds.length === 1 ? 'user' : 'users'}.`
                        : 'Select teammates to receive the same message.';
                } else if (!hasSelectedUser) {
                    elements.threadStatus.classList.remove('is-online');
                    elements.threadName.textContent = 'Select a contact';
                    elements.threadMeta.textContent = 'Choose a person from the list to start chatting.';
                }

                syncCompactMessengerLayout();
                return;
            }

            const selectedReportMessageIds = getSelectedReportMessageIds();
            const latestSeenOutgoingMessageId = state.messages.reduce((latestId, message) => {
                if (!message.is_mine || !message.read_at) {
                    return latestId;
                }

                return Math.max(latestId, Number(message.id) || 0);
            }, 0);

            elements.messages.innerHTML = state.messages.map((message) => {
                const isLatestSeenOutgoing = Number(message.id) === latestSeenOutgoingMessageId;
                const seenDate = isLatestSeenOutgoing ? formatSeenDate(message.read_at) : '';

                return `
                    <div class="portal-messenger__message ${message.is_mine ? 'is-mine' : ''} ${state.reportSelection.active ? 'is-report-selectable' : ''} ${selectedReportMessageIds.includes(Number(message.id)) ? 'is-report-selected' : ''}" data-message-id="${message.id}">
                        <div class="portal-messenger__bubble">
                            ${message.body ? `<div class="portal-messenger__body">${escapeHtml(message.body)}</div>` : ''}
                            ${renderAttachment(message.attachment)}
                        </div>
                        ${message.is_mine ? '' : `<p class="portal-messenger__time">${escapeHtml(message.time_label || message.created_at_human || '')}</p>`}
                        ${seenDate ? `<p class="portal-messenger__seen">Seen (${escapeHtml(seenDate)})</p>` : ''}
                    </div>
                `;
            }).join('');

            if (options.preserveTop && previousScrollHeight > 0) {
                elements.messages.scrollTop = previousScrollTop + (elements.messages.scrollHeight - previousScrollHeight);
            } else if (options.preserveScroll && previousScrollHeight > 0 && !wasNearBottom) {
                elements.messages.scrollTop = Math.max(
                    0,
                    elements.messages.scrollHeight - elements.messages.clientHeight - previousDistanceFromBottom
                );
            } else {
                elements.messages.scrollTop = elements.messages.scrollHeight;
            }

            syncCompactMessengerLayout();
        }

        function setLoading(isLoading) {
            state.isLoadingConversation = isLoading;
            if (isLoading) {
                elements.loading.textContent = 'Syncing conversation...';
            }
            elements.loading.classList.toggle('is-visible', isLoading);
            updateComposerState();
        }

        function getSelectedReportMessageIds() {
            if (!state.reportSelection.startId) {
                return [];
            }

            const ids = state.messages.map((message) => Number(message.id));
            const startIndex = ids.indexOf(Number(state.reportSelection.startId));
            const endIndex = state.reportSelection.endId
                ? ids.indexOf(Number(state.reportSelection.endId))
                : startIndex;

            if (startIndex === -1 || endIndex === -1) {
                return [];
            }

            const [from, to] = startIndex <= endIndex
                ? [startIndex, endIndex]
                : [endIndex, startIndex];

            return ids.slice(from, to + 1);
        }

        function buildReportDescriptionFromSelection(messageIds) {
            const contact = getContactById(state.selectedUserId);
            const currentUserName = root.dataset.currentUserName || 'Admin';

            return state.messages
                .filter((message) => messageIds.includes(Number(message.id)))
                .map((message) => {
                    const speaker = message.is_mine ? currentUserName : (contact?.full_name || 'User');
                    const body = message.body || '';
                    const attachment = message.attachment?.original_name
                        ? ` [Attachment: ${message.attachment.original_name}]`
                        : '';

                    return `${speaker}: ${body}${attachment}`.trim();
                })
                .join('\n');
        }

        function getSelectedReportAttachments(messageIds) {
            return state.messages
                .filter((message) => messageIds.includes(Number(message.id)) && message.attachment)
                .map((message) => message.attachment);
        }

        function renderReportAttachments(messageIds) {
            if (!elements.reportAttachmentSummary || !elements.reportAttachmentList) {
                return;
            }

            const attachments = getSelectedReportAttachments(messageIds);

            elements.reportAttachmentSummary.textContent = attachments.length
                ? `${attachments.length} file${attachments.length === 1 ? '' : 's'} will be uploaded with this report.`
                : 'No attachments in the selected conversation range.';

            elements.reportAttachmentList.innerHTML = attachments.map((attachment) => `
                <div class="portal-messenger__report-attachment-item">
                    <i class='bx bx-paperclip'></i>
                    <div>
                        <div class="portal-messenger__report-attachment-name">${escapeHtml(attachment.original_name || 'Attachment')}</div>
                        <div class="small text-muted">${escapeHtml(attachment.mime_type || 'File')} • ${escapeHtml(formatFileSize(attachment.size_bytes || 0))}</div>
                    </div>
                </div>
            `).join('');
        }

        function beginReportRangeSelection() {
            if (!state.selectedUserId || !state.messages.length || !state.conversationId) {
                return;
            }

            state.reportSelection = { active: true, startId: null, endId: null };
            elements.reportRangeHint.textContent = 'Select the first message for the report.';
            elements.reportRangeHint.classList.add('is-visible');
            renderMessages({ preserveScroll: true });
        }

        function cancelReportRangeSelection() {
            state.reportSelection = { active: false, startId: null, endId: null };
            elements.reportRangeHint?.classList.remove('is-visible');
        }

        function selectReportRangeMessage(messageId) {
            if (!state.reportSelection.active) {
                return;
            }

            if (!state.reportSelection.startId) {
                state.reportSelection.startId = Number(messageId);
                elements.reportRangeHint.textContent = 'Now select the last message for the report.';
                renderMessages({ preserveScroll: true });
                return;
            }

            state.reportSelection.endId = Number(messageId);
            openReportModal(getSelectedReportMessageIds());
        }

        function openReportModal(messageIds) {
            if (!elements.reportModal || !elements.reportForm) {
                return;
            }

            const contact = getContactById(state.selectedUserId);

            if (!messageIds.length || !contact) {
                return;
            }

            state.reportDraft.messageIds = messageIds;
            elements.reportForm.reset();
            elements.reportIssue.value = buildReportDescriptionFromSelection(messageIds);
            renderReportAttachments(messageIds);
            elements.reportReporter.textContent = `Report will be created for ${contact.full_name}.`;
            elements.reportModal.classList.add('is-open');
            elements.reportModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            elements.reportIssue.focus();
        }

        function closeReportModal() {
            if (!elements.reportModal) {
                return;
            }

            state.reportDraft.messageIds = [];
            cancelReportRangeSelection();
            elements.reportModal.classList.remove('is-open');
            elements.reportModal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        async function submitReportFromMessage() {
            if (!state.reportDraft.messageIds.length || !state.conversationId || !elements.reportForm) {
                return;
            }

            const payload = new FormData(elements.reportForm);
            state.reportDraft.messageIds.forEach((messageId) => payload.append('message_ids[]', String(messageId)));
            elements.reportSubmit.disabled = true;

            try {
                const data = await request(
                    conversationRouteFor(root.dataset.createReportUrlTemplate, state.conversationId),
                    {
                        method: 'POST',
                        body: payload,
                    }
                );

                closeReportModal();

                if (window.Lobibox) {
                    Lobibox.notify('success', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-check-circle',
                        msg: `Report #${data.report_id} created successfully.`,
                    });
                }

            } catch (error) {
                const validationMessage = error?.response?.data?.errors?.issue_id?.[0]
                    || error?.response?.data?.errors?.issue?.[0]
                    || error?.response?.data?.errors?.section_id?.[0];
                const message = validationMessage || error?.response?.data?.message || 'Unable to create the report.';

                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: message,
                    });
                }
            } finally {
                elements.reportSubmit.disabled = false;
            }
        }

        function updateSelectedContactHeader(contact) {
            if (!contact) {
                return;
            }

            elements.threadAvatar.src = contact.avatar_url;
            elements.threadAvatar.alt = contact.full_name;
            elements.threadStatus.classList.toggle('is-online', Boolean(contact.is_online));
            elements.threadName.textContent = contact.full_name;

            const descriptor = [contact.department, contact.section].filter(Boolean).join(' • ');
            const presence = getPresenceLabel(contact);
            elements.threadMeta.textContent = descriptor
                ? `${descriptor} • ${presence}`
                : presence || ('@' + contact.username);
        }

        function upsertContacts(contacts) {
            if (!Array.isArray(contacts)) {
                return;
            }

            state.contacts = contacts.slice().sort((a, b) => {
                const aOnline = Boolean(a.is_online);
                const bOnline = Boolean(b.is_online);

                if (aOnline !== bOnline) {
                    return aOnline ? -1 : 1;
                }

                const aTime = a.latest_message_at ? Date.parse(a.latest_message_at) : 0;
                const bTime = b.latest_message_at ? Date.parse(b.latest_message_at) : 0;

                if (bTime !== aTime) {
                    return bTime - aTime;
                }

                return String(a.full_name).localeCompare(String(b.full_name));
            });
        }

        async function fetchContacts() {
            const data = await request(root.dataset.contactsUrl);
            upsertContacts(data.contacts || []);
            state.totalUnread = Number(data.total_unread || 0);
            updateBadge();
            if (state.selectedUserId && !isMultiMode()) {
                updateSelectedContactHeader(getContactById(state.selectedUserId));
            }
            renderContacts();
            updateCallButtonState();
        }

        async function openConversation(userId, silent = false, options = {}) {
            if (isMultiMode()) {
                return;
            }

            const requestedUserId = Number(userId);
            const requestId = ++state.conversationRequestId;

            if (state.selectedUserId !== requestedUserId) {
                cancelReportRangeSelection();
                resetConversationWindow();
            }

            state.selectedUserId = requestedUserId;
            const contact = state.contacts.find((item) => Number(item.id) === requestedUserId);
            updateSelectedContactHeader(contact);
            renderContacts();

            if (!silent) {
                setLoading(true);
            }

            try {
                const data = await request(conversationUrlFor(requestedUserId, { limit: 50 }));

                if (requestId !== state.conversationRequestId || state.selectedUserId !== requestedUserId) {
                    return;
                }

                applyConversationPayload(data, requestedUserId, { replace: true });
                renderMessages({
                    preserveScroll: Boolean(options.preserveScroll),
                });
            } catch (error) {
                if (!silent && window.Lobibox) {
                    const message = error?.response?.data?.message || 'Unable to load this conversation right now.';
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: message,
                    });
                }
            } finally {
                if (!silent && requestId === state.conversationRequestId) {
                    setLoading(false);
                }
            }
        }

        async function syncSelectedConversation(options = {}) {
            if (!state.selectedUserId || isMultiMode() || state.isLoadingConversation || state.isSyncingConversation) {
                return;
            }

            const requestedUserId = Number(state.selectedUserId);
            const newestMessageId = getNewestLoadedMessageId();

            if (!newestMessageId) {
                return openConversation(requestedUserId, true, { preserveScroll: true });
            }

            state.isSyncingConversation = true;

            try {
                const data = await request(conversationUrlFor(requestedUserId, {
                    after_id: newestMessageId,
                    limit: 100,
                }));

                if (state.selectedUserId !== requestedUserId || isMultiMode()) {
                    return;
                }

                const changed = applyConversationPayload(data, requestedUserId, { updateOlder: false });

                if (changed || options.forceRender) {
                    renderMessages({ preserveScroll: true });
                }
            } finally {
                if (state.selectedUserId === requestedUserId) {
                    state.isSyncingConversation = false;
                }
            }
        }

        async function loadOlderMessages() {
            if (
                !state.selectedUserId
                || isMultiMode()
                || state.isLoadingConversation
                || state.isLoadingOlderMessages
                || !state.hasMoreOlderMessages
            ) {
                return;
            }

            const requestedUserId = Number(state.selectedUserId);
            const oldestMessageId = getOldestLoadedMessageId();

            if (!oldestMessageId) {
                return;
            }

            state.isLoadingOlderMessages = true;
            elements.loading.textContent = 'Loading older messages...';
            elements.loading.classList.add('is-visible');

            try {
                const data = await request(conversationUrlFor(requestedUserId, {
                    before_id: oldestMessageId,
                    limit: 50,
                }));

                if (state.selectedUserId !== requestedUserId || isMultiMode()) {
                    return;
                }

                const changed = applyConversationPayload(data, requestedUserId, { placement: 'prepend' });

                if (changed) {
                    renderMessages({ preserveTop: true });
                }
            } finally {
                if (state.selectedUserId === requestedUserId) {
                    state.isLoadingOlderMessages = false;
                    elements.loading.textContent = 'Syncing conversation...';
                    elements.loading.classList.remove('is-visible');
                }
            }
        }

        async function sendMessage(body) {
            if (!state.selectedUserId || isMultiMode()) {
                return;
            }

            const recipientId = Number(state.selectedUserId);

            setLoading(true);

            try {
                const payload = new FormData();
                payload.append('body', body);

                if (state.pendingAttachment) {
                    payload.append('attachment', state.pendingAttachment);
                }

                const data = await request(
                    routeFor(root.dataset.storeUrlTemplate, recipientId),
                    {
                        method: 'POST',
                        body: payload,
                    }
                );

                if (state.selectedUserId !== recipientId || isMultiMode()) {
                    upsertContacts(data.contacts || state.contacts);
                    state.totalUnread = Number(data.total_unread || 0);
                    updateBadge();
                    renderContacts();
                    return;
                }

                if (data.message) {
                    state.messages.push(data.message);
                }

                upsertContacts(data.contacts || state.contacts);
                state.totalUnread = Number(data.total_unread || 0);
                updateBadge();
                renderContacts();
                renderMessages();
                elements.input.value = '';
                clearPendingAttachment();
                updateComposerState();
            } catch (error) {
                const validationMessage = error?.response?.data?.errors?.body?.[0]
                    || error?.response?.data?.errors?.attachment?.[0];
                const message = validationMessage || error?.response?.data?.message || 'Unable to send your message.';

                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: message,
                    });
                }
            } finally {
                if (state.selectedUserId === recipientId && !isMultiMode()) {
                    setLoading(false);
                }
            }
        }

        async function sendMessageToMany(body) {
            if (!state.selectedRecipientIds.length) {
                return;
            }

            setLoading(true);

            try {
                const payload = new FormData();
                payload.append('body', body);
                state.selectedRecipientIds.forEach((recipientId) => {
                    payload.append('recipient_ids[]', String(recipientId));
                });

                if (state.pendingAttachment) {
                    payload.append('attachment', state.pendingAttachment);
                }

                const data = await request(root.dataset.storeManyUrl, {
                    method: 'POST',
                    body: payload,
                });

                upsertContacts(data.contacts || state.contacts);
                state.totalUnread = Number(data.total_unread || 0);
                updateBadge();
                renderContacts();
                renderMessages();
                elements.input.value = '';
                clearPendingAttachment();
                updateComposerState();

            } catch (error) {
                const validationMessage = error?.response?.data?.errors?.recipient_ids?.[0]
                    || error?.response?.data?.errors?.body?.[0]
                    || error?.response?.data?.errors?.attachment?.[0];
                const message = validationMessage || error?.response?.data?.message || 'Unable to send your message.';

                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: message,
                    });
                }
            } finally {
                setLoading(false);
            }
        }

        function setComposeMode(mode) {
            state.composeMode = mode === 'multi' ? 'multi' : 'direct';
            elements.directMode.classList.toggle('is-active', state.composeMode === 'direct');
            elements.multiMode.classList.toggle('is-active', state.composeMode === 'multi');

            if (isMultiMode()) {
                resetConversationWindow();
                state.selectedUserId = null;
            } else {
                state.selectedRecipientIds = [];
            }

            renderContacts();
            renderMessages();
            updateComposerState();
            syncCompactMessengerLayout();
        }

        function toggleRecipient(userId) {
            const numericId = Number(userId);

            if (state.selectedRecipientIds.includes(numericId)) {
                state.selectedRecipientIds = state.selectedRecipientIds.filter((id) => id !== numericId);
            } else {
                state.selectedRecipientIds = [...state.selectedRecipientIds, numericId];
            }

            renderContacts();
            renderMessages();
        }

        function selectAllFilteredRecipients() {
            const filteredIds = getFilteredContactIds();
            state.selectedRecipientIds = Array.from(new Set([...state.selectedRecipientIds, ...filteredIds]));
            renderContacts();
            renderMessages();
        }

        function toggleSelectAllFilteredRecipients() {
            const filteredIds = getFilteredContactIds();

            if (!filteredIds.length) {
                return;
            }

            if (areAllFilteredRecipientsSelected()) {
                state.selectedRecipientIds = state.selectedRecipientIds.filter((id) => !filteredIds.includes(id));
            } else {
                selectAllFilteredRecipients();
                return;
            }

            renderContacts();
            renderMessages();
        }

        function togglePanel(forceOpen = null) {
            state.isOpen = forceOpen === null ? !state.isOpen : Boolean(forceOpen);
            elements.panel.classList.toggle('is-open', state.isOpen);
            elements.launcher.classList.toggle('is-alerting', state.totalUnread > 0 && !state.isOpen);
            syncCompactMessengerLayout();

            if (state.isOpen) {
                fetchContacts().catch(() => {});
            }
        }

        function startAutoRefresh() {
            stopAutoRefresh();
            state.refreshTimer = window.setInterval(async () => {
                try {
                    await fetchContacts();

                    if (state.selectedUserId && !isMultiMode()) {
                        await syncSelectedConversation();
                    }
                } catch (error) {
                    // Keep the widget quiet during background polling.
                }
            }, 8000);
        }

        function stopAutoRefresh() {
            if (state.refreshTimer) {
                window.clearInterval(state.refreshTimer);
                state.refreshTimer = null;
            }
        }

        window.portalMessenger = {
            refresh() {
                return fetchContacts().then(function () {
                    if (state.selectedUserId && !isMultiMode()) {
                        return syncSelectedConversation({ forceRender: true });
                    }
                });
            },
            openForUser(userId) {
                const numericId = Number(userId);

                if (!numericId) {
                    return;
                }

                togglePanel(true);
                setComposeMode('direct');
                openConversation(numericId);
            }
        };

        elements.launcher.addEventListener('click', function () {
            togglePanel();
        });

        elements.close.addEventListener('click', function () {
            togglePanel(false);
        });

        elements.back.addEventListener('click', function () {
            if (!isCompactMessengerViewport() || isMultiMode()) {
                return;
            }

            state.selectedUserId = null;
            resetConversationWindow();
            renderContacts();
            renderMessages();
        });

        elements.callButton.addEventListener('click', function () {
            if (state.call.mode !== 'idle') {
                state.call.modalHidden = false;
                updateCallModalUI();
                return;
            }

            startOutgoingCall();
        });

        elements.reportRangeButton?.addEventListener('click', function () {
            beginReportRangeSelection();
        });

        elements.refresh.addEventListener('click', function () {
            fetchContacts()
                .then(function () {
                    if (state.selectedUserId && !isMultiMode()) {
                        return syncSelectedConversation({ forceRender: true });
                    }
                })
                .catch(function () {});
        });

        elements.directMode.addEventListener('click', function () {
            cancelReportRangeSelection();
            setComposeMode('direct');
        });

        elements.multiMode.addEventListener('click', function () {
            cancelReportRangeSelection();
            setComposeMode('multi');
        });

        elements.clearSelection.addEventListener('click', function () {
            state.selectedRecipientIds = [];
            renderContacts();
            renderMessages();
        });

        elements.search.addEventListener('input', function (event) {
            state.search = event.target.value || '';
            renderContacts();
        });

        elements.attachButton.addEventListener('click', function () {
            if (!elements.attachButton.disabled) {
                elements.attachmentInput.click();
            }
        });

        elements.attachmentInput.addEventListener('change', function (event) {
            const file = event.target.files?.[0] || null;

            if (file && file.size > maxAttachmentBytes) {
                event.target.value = '';
                state.pendingAttachment = null;
                updateComposerState();

                if (window.Lobibox) {
                    Lobibox.notify('error', {
                        size: 'mini',
                        rounded: true,
                        delayIndicator: true,
                        sound: false,
                        position: 'top right',
                        icon: 'bx bx-error',
                        msg: 'Attachment is too large. Please choose a file up to 20MB.',
                    });
                }

                return;
            }

            state.pendingAttachment = file;
            updateComposerState();
        });

        elements.clearAttachment.addEventListener('click', function () {
            clearPendingAttachment();
        });

        elements.callAccept.addEventListener('click', function () {
            answerIncomingCall();
        });

        elements.callDecline.addEventListener('click', function () {
            if (state.call.mode === 'incoming') {
                rejectIncomingCall();
                return;
            }

            finishCallSession({
                sendHangup: true,
            });
        });

        elements.callDismiss.addEventListener('click', function () {
            dismissCallModal();
        });

        elements.contacts.addEventListener('click', function (event) {
            const selectAllButton = event.target.closest('#portalMessengerSelectAllRow');
            if (selectAllButton) {
                toggleSelectAllFilteredRecipients();
                return;
            }

            const button = event.target.closest('[data-contact-id]');
            if (!button) {
                return;
            }

            if (isMultiMode()) {
                toggleRecipient(Number(button.dataset.contactId));
                return;
            }

            openConversation(Number(button.dataset.contactId));
        });

        elements.messages.addEventListener('click', function (event) {
            if (state.reportSelection.active) {
                const selectedMessage = event.target.closest('[data-message-id]');
                if (selectedMessage) {
                    selectReportRangeMessage(Number(selectedMessage.dataset.messageId));
                }
                return;
            }

            const previewButton = event.target.closest('[data-attachment-view]');

            if (!previewButton) {
                return;
            }

            event.preventDefault();

            openAttachmentPreview({
                viewUrl: previewButton.dataset.attachmentView,
                downloadUrl: previewButton.dataset.attachmentDownload,
                originalName: previewButton.dataset.attachmentName,
                mimeType: previewButton.dataset.attachmentMime,
                sizeBytes: previewButton.dataset.attachmentSize,
            });
        });

        elements.messages.addEventListener('scroll', function () {
            if (elements.messages.scrollTop <= 80) {
                loadOlderMessages().catch(function () {});
            }
        });

        elements.attachmentModalClose.addEventListener('click', hideAttachmentModal);
        elements.attachmentModalCloseFooter.addEventListener('click', hideAttachmentModal);
        elements.attachmentModal.addEventListener('click', function (event) {
            if (event.target === elements.attachmentModal) {
                hideAttachmentModal();
            }
        });

        elements.callModal.addEventListener('click', function (event) {
            if (event.target === elements.callModal && state.call.mode !== 'incoming') {
                dismissCallModal();
            }
        });

        if (elements.reportModal) {
            elements.reportModalClose.addEventListener('click', closeReportModal);
            elements.reportModalCancel.addEventListener('click', closeReportModal);
            elements.reportModal.addEventListener('click', function (event) {
                if (event.target === elements.reportModal) {
                    closeReportModal();
                }
            });
            elements.reportForm.addEventListener('submit', function (event) {
                event.preventDefault();
                submitReportFromMessage();
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && elements.attachmentModal.classList.contains('is-open')) {
                hideAttachmentModal();
                return;
            }

            if (event.key === 'Escape' && elements.reportModal?.classList.contains('is-open')) {
                closeReportModal();
                return;
            }

            if (event.key === 'Escape' && elements.callModal.classList.contains('is-open') && state.call.mode !== 'incoming') {
                dismissCallModal();
            }
        });

        elements.input.addEventListener('input', function () {
            updateComposerState();
        });

        elements.form.addEventListener('submit', function (event) {
            event.preventDefault();

            const body = elements.input.value.trim();
            if (!body && !hasPendingAttachment()) {
                return;
            }

            if (isMultiMode()) {
                sendMessageToMany(body);
                return;
            }

            sendMessage(body);
        });

        root.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        elements.input.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                elements.form.requestSubmit();
            }
        });

        document.addEventListener('click', function (event) {
            if (!state.isOpen) {
                return;
            }

            if (
                root.contains(event.target)
                || elements.attachmentModal.contains(event.target)
                || elements.callModal.contains(event.target)
                || elements.reportModal?.contains(event.target)
            ) {
                return;
            }

            togglePanel(false);
        });

        window.addEventListener('portal-messenger-message-received', function (event) {
            const payload = event.detail || {};
            const senderId = Number(payload?.sender?.id || 0);

            fetchContacts()
                .then(function () {
                    if (state.selectedUserId === senderId && !isMultiMode()) {
                        return syncSelectedConversation({ forceRender: true });
                    }
                })
                .catch(function () {});
        });

        window.addEventListener('portal-messenger-messages-read', function (event) {
            const payload = event.detail || {};
            const conversationId = Number(payload.conversation_id || 0);
            const readAt = payload.read_at || new Date().toISOString();
            const readMessageIds = Array.isArray(payload.message_ids)
                ? payload.message_ids.map((id) => Number(id)).filter(Boolean)
                : [];

            if (!conversationId || conversationId !== Number(state.conversationId || 0) || !readMessageIds.length) {
                return;
            }

            let changed = false;
            state.messages = state.messages.map((message) => {
                if (!message.is_mine || !readMessageIds.includes(Number(message.id)) || message.read_at) {
                    return message;
                }

                changed = true;
                return {
                    ...message,
                    read_at: readAt,
                };
            });

            if (changed) {
                renderMessages({ preserveScroll: true });
            }
        });

        window.addEventListener('portal-messenger-open-user', function (event) {
            const userId = Number(event.detail?.userId || 0);

            if (!userId) {
                return;
            }

            window.portalMessenger.openForUser(userId);
        });

        window.addEventListener('portal-messenger-call-signal-received', function (event) {
            handleCallSignal(event.detail || {}).catch(function (error) {
                console.error('Unable to handle call signal.', error);
            });
        });

        window.addEventListener('resize', function () {
            syncCompactMessengerLayout();
        });

        upsertContacts(state.contacts);
        renderContacts();
        renderMessages();
        updateBadge();
        updateComposerState();
        fetchContacts().catch(function () {});
        startAutoRefresh();
        window.addEventListener('beforeunload', function () {
            stopAutoRefresh();

            if (state.call.mode !== 'idle' && state.call.partnerId && state.call.currentCallId) {
                sendCallSignal(state.call.partnerId, {
                    call_id: state.call.currentCallId,
                    signal_type: 'hangup',
                }).catch(function () {});
            }
        });
    })();
</script>
@endif
