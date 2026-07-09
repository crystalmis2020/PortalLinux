<?php

namespace App\Http\Controllers;

use App\Events\MessengerCallSignalReceived;
use App\Events\MessengerMessagesRead;
use App\Models\MessengerConversation;
use App\Models\MessengerMessage;
use App\Models\Notification;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportLog;
use App\Models\Section;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

class MessengerController extends Controller
{
    public function contacts(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->touchPresence($user);

        return response()->json([
            'contacts' => $this->buildContactsPayload($user->id),
            'total_unread' => $this->getTotalUnreadCount($user->id),
        ]);
    }

    public function conversation(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();
        $this->touchPresence($authUser);
        $this->ensureValidRecipient($authUser->id, $user);

        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:1'],
            'before_id' => ['nullable', 'integer', 'min:1'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $conversation = $this->findOrCreateConversation($authUser->id, $user->id);

        $unreadMessageIds = MessengerMessage::where('conversation_id', $conversation->id)
            ->where('recipient_id', $authUser->id)
            ->whereNull('read_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (!empty($unreadMessageIds)) {
            $readAt = now();

            MessengerMessage::whereIn('id', $unreadMessageIds)
                ->update(['read_at' => $readAt]);

            try {
                event(new MessengerMessagesRead(
                    $conversation->id,
                    $authUser->id,
                    $user->id,
                    $unreadMessageIds,
                    $readAt->toIso8601String(),
                ));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $limit = (int) ($validated['limit'] ?? 50);
        $afterId = isset($validated['after_id']) ? (int) $validated['after_id'] : null;
        $beforeId = isset($validated['before_id']) ? (int) $validated['before_id'] : null;

        $messagesQuery = MessengerMessage::where('conversation_id', $conversation->id);

        if ($afterId) {
            $messages = (clone $messagesQuery)
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit($limit)
                ->get();
        } elseif ($beforeId) {
            $messages = (clone $messagesQuery)
                ->where('id', '<', $beforeId)
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->sortBy('id')
                ->values();
        } else {
            $messages = (clone $messagesQuery)
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->sortBy('id')
                ->values();
        }

        $oldestMessageId = $messages->min('id');
        $newestMessageId = $messages->max('id');
        $hasMoreOlder = $oldestMessageId
            ? (clone $messagesQuery)->where('id', '<', $oldestMessageId)->exists()
            : false;
        $hasMoreNewer = $newestMessageId
            ? (clone $messagesQuery)->where('id', '>', $newestMessageId)->exists()
            : false;

        return response()->json([
            'conversation_id' => $conversation->id,
            'contact' => $this->formatContact($user, $authUser->id, true),
            'messages' => $messages->map(fn (MessengerMessage $message) => $this->formatMessagePayload($message, $authUser->id))->values(),
            'has_more_older' => $hasMoreOlder,
            'has_more_newer' => $hasMoreNewer,
            'oldest_message_id' => $oldestMessageId,
            'newest_message_id' => $newestMessageId,
            'total_unread' => $this->getTotalUnreadCount($authUser->id),
        ]);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();
        $this->touchPresence($authUser);
        $this->ensureValidRecipient($authUser->id, $user);

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => 'required_without:body|' . attachmentValidationRule(),
        ]);

        $conversation = $this->findOrCreateConversation($authUser->id, $user->id);
        $attachment = $request->file('attachment');
        $body = trim((string) ($validated['body'] ?? ''));

        $message = DB::transaction(function () use ($conversation, $authUser, $user, $body, $attachment) {
            $attachmentData = $attachment ? $this->storeAttachment($attachment) : [];

            $message = MessengerMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $authUser->id,
                'recipient_id' => $user->id,
                'body' => $body,
                ...$attachmentData,
            ]);

            $conversation->update([
                'last_message_at' => $message->created_at,
            ]);

            return $message;
        });

        return response()->json([
            'message' => $this->formatMessagePayload($message, $authUser->id),
            'contacts' => $this->buildContactsPayload($authUser->id),
            'total_unread' => $this->getTotalUnreadCount($authUser->id),
        ]);
    }

    public function storeMany(Request $request): JsonResponse
    {
        $authUser = $request->user();
        $this->touchPresence($authUser);

        $validated = $request->validate([
            'recipient_ids' => ['required', 'array', 'min:1'],
            'recipient_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'body' => ['nullable', 'string', 'max:5000', 'required_without:attachment'],
            'attachment' => 'required_without:body|' . attachmentValidationRule(),
        ]);

        $recipientIds = collect($validated['recipient_ids'])
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $authUser->id)
            ->values();

        if ($recipientIds->isEmpty()) {
            throw ValidationException::withMessages([
                'recipient_ids' => 'Please select at least one other user.',
            ]);
        }

        $recipients = User::query()
            ->whereIn('id', $recipientIds->all())
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'recipient_ids' => 'No valid active recipients were selected.',
            ]);
        }

        $body = trim((string) ($validated['body'] ?? ''));
        $attachment = $request->file('attachment');
        $attachmentData = $attachment ? $this->storeAttachment($attachment) : [];

        DB::transaction(function () use ($authUser, $recipients, $body, $attachmentData) {
            foreach ($recipients as $recipient) {
                $conversation = $this->findOrCreateConversation($authUser->id, $recipient->id);

                $message = MessengerMessage::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $authUser->id,
                    'recipient_id' => $recipient->id,
                    'body' => $body,
                    ...$attachmentData,
                ]);

                $conversation->update([
                    'last_message_at' => $message->created_at,
                ]);
            }
        });

        return response()->json([
            'sent_count' => $recipients->count(),
            'recipient_ids' => $recipients->keys()->values()->all(),
            'contacts' => $this->buildContactsPayload($authUser->id),
            'total_unread' => $this->getTotalUnreadCount($authUser->id),
        ]);
    }

    public function createReport(Request $request, MessengerConversation $conversation): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser?->isAdmin()) {
            abort(403);
        }

        if (!in_array((int) $authUser->id, [(int) $conversation->user_one_id, (int) $conversation->user_two_id], true)) {
            abort(403);
        }

        $validated = $request->validate(
            [
                'issue_id' => ['required', 'exists:issues,id'],
                'issue' => ['required', 'string', 'max:2000'],
                'contact_number' => ['nullable', 'string', 'max:255'],
                'section_id' => ['required', 'exists:sections,id'],
                'message_ids' => ['required', 'array', 'min:1'],
                'message_ids.*' => ['integer', 'distinct'],
            ],
            [
                'issue_id.required' => 'Please select an issue category.',
                'issue_id.exists' => 'Please select a valid issue category.',
                'section_id.required' => 'Please select a section.',
                'section_id.exists' => 'Please select a valid section.',
            ]
        );

        $messages = MessengerMessage::query()
            ->where('conversation_id', $conversation->id)
            ->whereIn('id', $validated['message_ids'])
            ->orderBy('created_at')
            ->get();

        if ($messages->count() !== count($validated['message_ids'])) {
            throw ValidationException::withMessages([
                'message_ids' => 'One or more selected messages do not belong to this conversation.',
            ]);
        }

        $reporterId = $conversation->otherParticipantIdFor($authUser->id);

        $reporter = User::with(['department', 'section'])->findOrFail($reporterId);

        if (!$reporter->department_id || !$reporter->section_id) {
            return response()->json([
                'message' => 'This user needs a department and section before a report can be created for them.',
            ], 422);
        }

        $openResolvedReports = Report::where('reported_by', $reporter->id)
            ->where('status', 'resolved')
            ->count();

        if ($openResolvedReports > 0) {
            return response()->json([
                'message' => 'This user still has ' . $openResolvedReports . ' resolved report(s) waiting to be reviewed or closed.',
            ], 422);
        }

        $selectedSection = Section::with('department')->findOrFail((int) $validated['section_id']);

        $report = DB::transaction(function () use ($validated, $reporter, $selectedSection, $messages) {
            $report = Report::create([
                'department_address_to' => $selectedSection->department_id,
                'section_address_to' => $selectedSection->id,
                'department_address_from' => $reporter->department_id,
                'section_address_from' => $reporter->section_id,
                'issue_id' => $validated['issue_id'],
                'issue_sub_category_id' => '0',
                'assigned_by' => null,
                'assigned_to' => null,
                'reported_by' => $reporter->id,
                'issue' => $validated['issue'],
                'contact_number' => $validated['contact_number'] ?? null,
                'status' => 'new',
                'parent_report_id' => null,
                'child_number' => null,
            ]);

            ReportLog::create([
                'report_id' => $report->id,
                'user_id' => $reporter->id,
                'message' => 'A new report was submitted',
                'status' => 'new',
                'remarks' => null,
            ]);

            foreach ($messages as $message) {
                $this->copyMessengerAttachmentToReport($message, $report);
            }

            return $report;
        });

        $departmentName = $reporter->department?->name ?: 'Unknown department';
        $adminUsers = User::query()
            ->whereRaw('LOWER(user_type) = ?', ['admin'])
            ->where('is_active', true)
            ->get();

        foreach ($adminUsers as $adminUser) {
            Notification::create([
                'from_user_id' => $reporter->id,
                'to_user_id' => $adminUser->id,
                'section_to' => $adminUser->section_id,
                'report_id' => $report->id,
                'title' => 'New Report Submitted',
                'message' => 'Submitted by: ' . $reporter->full_name,
                'is_read' => 'No',
            ]);

            sendIpMsgNotification(
                'A new report was sent from ' . $departmentName . ' by ' . $reporter->full_name,
                $adminUser->ip_address
            );
        }

        return response()->json([
            'message' => 'Report created successfully from MISsenger.',
            'report_id' => $report->id,
            'details_url' => route('reports.details', $report->id),
        ]);
    }

    public function signalCall(Request $request, User $user): JsonResponse
    {
        $authUser = $request->user();
        $this->touchPresence($authUser);
        $this->ensureValidRecipient($authUser->id, $user);

        $validated = $request->validate([
            'call_id' => ['required', 'string', 'max:255'],
            'signal_type' => ['required', 'string', 'in:offer,answer,ice-candidate,hangup,reject,busy'],
            'sdp' => ['nullable', 'array'],
            'sdp.type' => ['required_with:sdp', 'string', 'in:offer,answer'],
            'sdp.sdp' => ['required_with:sdp', 'string'],
            'sdp.encoding' => ['nullable', 'string', 'in:base64'],
            'candidate' => ['nullable', 'array'],
            'candidate.candidate' => ['required_with:candidate', 'string'],
            'candidate.sdpMid' => ['nullable', 'string'],
            'candidate.sdpMLineIndex' => ['nullable', 'integer'],
        ]);

        if ($validated['signal_type'] === 'offer' && !$user->isCurrentlyOnline()) {
            throw ValidationException::withMessages([
                'user' => ($user->full_name ?: $user->username) . ' is offline and cannot receive calls right now.',
            ]);
        }

        $signal = [
            'call_id' => $validated['call_id'],
            'signal_type' => $validated['signal_type'],
            'sdp' => $this->normalizeCallSessionDescription($validated['sdp'] ?? null),
            'candidate' => $validated['candidate'] ?? null,
            'sent_at' => now()->toIso8601String(),
        ];

        try {
            event(new MessengerCallSignalReceived(
                $authUser->loadMissing(['department', 'section']),
                $user->loadMissing(['department', 'section']),
                $signal
            ));
        } catch (BroadcastException $exception) {
            report($exception);

            return response()->json([
                'status' => 'failed',
                'message' => 'Call signaling service is unavailable. Please try again in a moment.',
            ], 503);
        }

        return response()->json([
            'status' => 'sent',
        ]);
    }

    public function updatePresenceVisibility(Request $request): JsonResponse
    {
        $authUser = $request->user();

        if (!$authUser?->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:online,offline'],
        ]);

        $authUser->forceFill([
            'messenger_presence_visible' => $validated['status'] === 'online',
            'is_login' => 'Yes',
            'last_seen_at' => now(),
        ])->save();

        return response()->json([
            'status' => $validated['status'],
            'is_online' => $authUser->isCurrentlyOnline(),
            'message' => $validated['status'] === 'online'
                ? 'You are now visible online in MISsenger.'
                : 'You are now hidden offline in MISsenger.',
        ]);
    }

    protected function normalizeCallSessionDescription(?array $description): ?array
    {
        if (!$description) {
            return null;
        }

        $type = isset($description['type']) ? trim((string) $description['type']) : '';
        $sdp = $description['sdp'] ?? '';
        $encoding = isset($description['encoding']) ? strtolower(trim((string) $description['encoding'])) : '';

        if (is_array($sdp)) {
            $nestedType = isset($sdp['type']) ? trim((string) $sdp['type']) : $type;
            $nestedSdp = $sdp['sdp'] ?? '';

            $type = $nestedType;
            $sdp = $nestedSdp;
        }

        if ($encoding === 'base64' && is_string($sdp)) {
            $decodedBase64 = base64_decode($sdp, true);

            if ($decodedBase64 !== false) {
                $sdp = $decodedBase64;
            }
        }

        if (is_string($sdp)) {
            $decoded = json_decode($sdp, true);

            if (
                json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_key_exists('sdp', $decoded)
            ) {
                $type = isset($decoded['type']) ? trim((string) $decoded['type']) : $type;
                $sdp = $decoded['sdp'];
            }
        }

        $sdp = (string) $sdp;
        $sdp = str_replace(["\\r\\n", "\\n", "\\r"], ["\r\n", "\n", "\r"], $sdp);
        $sdp = preg_replace("/\r\n|\r|\n/", "\r\n", $sdp) ?? $sdp;

        return [
            'type' => $type,
            'sdp' => $sdp,
        ];
    }

    public function viewAttachment(Request $request, MessengerMessage $message)
    {
        $this->ensureAttachmentAccess($request->user()->id, $message);
        [$filePath, $mimeType] = $this->resolveAttachmentPath($message);

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . addslashes($message->attachment_original_name) . '"',
        ]);
    }

    public function downloadAttachment(Request $request, MessengerMessage $message)
    {
        $this->ensureAttachmentAccess($request->user()->id, $message);
        [$filePath] = $this->resolveAttachmentPath($message);

        return response()->download($filePath, $message->attachment_original_name);
    }

    protected function ensureValidRecipient(int $authUserId, User $recipient): void
    {
        if ($authUserId === (int) $recipient->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot message yourself.',
            ]);
        }
    }

    protected function ensureAttachmentAccess(int $authUserId, MessengerMessage $message): void
    {
        if (
            !$message->attachment_file_path
            || !in_array($authUserId, [(int) $message->sender_id, (int) $message->recipient_id], true)
        ) {
            abort(403);
        }
    }

    protected function touchPresence(User $user): void
    {
        $user->forceFill([
            'is_login' => 'Yes',
            'last_seen_at' => now(),
        ])->save();
    }

    protected function findOrCreateConversation(int $firstUserId, int $secondUserId): MessengerConversation
    {
        [$userOneId, $userTwoId] = collect([$firstUserId, $secondUserId])->sort()->values()->all();

        return MessengerConversation::firstOrCreate(
            [
                'user_one_id' => $userOneId,
                'user_two_id' => $userTwoId,
            ]
        );
    }

    protected function buildContactsPayload(int $authUserId): array
    {
        $users = User::query()
            ->where('id', '!=', $authUserId)
            ->where('is_active', true)
            ->with(['department', 'section'])
            ->orderBy('full_name')
            ->get();

        $conversationMeta = $this->buildConversationMeta($authUserId, $users);

        return $users->map(function (User $contact) use ($authUserId, $conversationMeta) {
            return $this->formatContact($contact, $authUserId, false, $conversationMeta[$contact->id] ?? []);
        })
        ->sort(function (array $a, array $b) {
            if ($a['is_online'] !== $b['is_online']) {
                return $a['is_online'] ? -1 : 1;
            }

            if ($b['sort_key'] !== $a['sort_key']) {
                return $b['sort_key'] <=> $a['sort_key'];
            }

            return strcasecmp($a['full_name'], $b['full_name']);
        })
        ->values()
        ->map(function (array $contact) {
            unset($contact['sort_key']);

            return $contact;
        })
        ->all();
    }

    protected function buildConversationMeta(int $authUserId, Collection $users): array
    {
        $contactIds = $users->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($contactIds)) {
            return [];
        }

        $conversations = MessengerConversation::query()
            ->where(function ($query) use ($authUserId, $contactIds) {
                $query->where('user_one_id', $authUserId)
                    ->whereIn('user_two_id', $contactIds);
            })
            ->orWhere(function ($query) use ($authUserId, $contactIds) {
                $query->where('user_two_id', $authUserId)
                    ->whereIn('user_one_id', $contactIds);
            })
            ->get();

        if ($conversations->isEmpty()) {
            return [];
        }

        $conversationIds = $conversations->pluck('id')->all();
        $latestMessages = MessengerMessage::query()
            ->whereIn('id', function ($query) use ($conversationIds) {
                $query->from('messenger_messages')
                    ->selectRaw('MAX(id)')
                    ->whereIn('conversation_id', $conversationIds)
                    ->groupBy('conversation_id');
            })
            ->get()
            ->keyBy('conversation_id');

        $unreadCounts = MessengerMessage::query()
            ->selectRaw('conversation_id, COUNT(*) as aggregate')
            ->whereIn('conversation_id', $conversationIds)
            ->where('recipient_id', $authUserId)
            ->whereNull('read_at')
            ->groupBy('conversation_id')
            ->pluck('aggregate', 'conversation_id');

        $meta = [];

        foreach ($conversations as $conversation) {
            $contactId = $conversation->otherParticipantIdFor($authUserId);

            if (!$contactId) {
                continue;
            }

            $latestMessage = $latestMessages->get($conversation->id);

            $meta[$contactId] = [
                'conversation_id' => $conversation->id,
                'latest_message' => $this->messagePreview($latestMessage),
                'latest_message_at' => optional($latestMessage?->created_at)?->toIso8601String(),
                'latest_message_human' => optional($latestMessage?->created_at)?->diffForHumans(),
                'unread_count' => (int) ($unreadCounts[$conversation->id] ?? 0),
                'sort_key' => optional($conversation->last_message_at)?->timestamp ?? 0,
            ];
        }

        return $meta;
    }

    protected function formatContact(User $contact, int $authUserId, bool $includeConversationId = false, array $meta = []): array
    {
        if (empty($meta)) {
            $conversation = MessengerConversation::query()
                ->where('user_one_id', min($authUserId, $contact->id))
                ->where('user_two_id', max($authUserId, $contact->id))
                ->first();

            if ($conversation) {
                $latestMessage = MessengerMessage::where('conversation_id', $conversation->id)
                    ->latest('created_at')
                    ->first();

                $meta = [
                    'conversation_id' => $conversation->id,
                    'latest_message' => $this->messagePreview($latestMessage),
                    'latest_message_at' => optional($latestMessage?->created_at)?->toIso8601String(),
                    'latest_message_human' => optional($latestMessage?->created_at)?->diffForHumans(),
                    'unread_count' => MessengerMessage::where('conversation_id', $conversation->id)
                        ->where('recipient_id', $authUserId)
                        ->whereNull('read_at')
                        ->count(),
                    'sort_key' => optional($conversation->last_message_at)?->timestamp ?? 0,
                ];
            }
        }

        $payload = [
            'id' => $contact->id,
            'full_name' => $contact->full_name ?: $contact->username,
            'username' => $contact->username,
            'department' => $contact->department?->name,
            'section' => $contact->section?->name,
            'avatar_url' => $contact->profile_photo_url,
            'is_online' => $contact->isCurrentlyOnline(),
            'last_seen_at' => optional($contact->last_seen_at)?->toIso8601String(),
            'latest_message' => $meta['latest_message'] ?? null,
            'latest_message_at' => $meta['latest_message_at'] ?? null,
            'latest_message_human' => $meta['latest_message_human'] ?? null,
            'unread_count' => (int) ($meta['unread_count'] ?? 0),
            'sort_key' => (int) ($meta['sort_key'] ?? 0),
        ];

        if ($includeConversationId) {
            $payload['conversation_id'] = $meta['conversation_id'] ?? null;
        }

        return $payload;
    }

    protected function getTotalUnreadCount(int $authUserId): int
    {
        return MessengerMessage::where('recipient_id', $authUserId)
            ->whereNull('read_at')
            ->count();
    }

    protected function formatMessagePayload(MessengerMessage $message, int $authUserId): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'recipient_id' => $message->recipient_id,
            'is_mine' => (int) $message->sender_id === (int) $authUserId,
            'read_at' => optional($message->read_at)?->toIso8601String(),
            'created_at' => optional($message->created_at)?->toIso8601String(),
            'created_at_human' => optional($message->created_at)?->diffForHumans(),
            'time_label' => optional($message->created_at)?->format('M d, Y h:i A'),
            'attachment' => $this->formatAttachmentPayload($message),
        ];
    }

    protected function formatAttachmentPayload(MessengerMessage $message): ?array
    {
        if (!$message->attachment_file_path) {
            return null;
        }

        return [
            'original_name' => $message->attachment_original_name,
            'mime_type' => $message->attachment_mime_type,
            'size_bytes' => (int) ($message->attachment_size_bytes ?? 0),
            'view_url' => route('messenger.attachments.view', $message),
            'download_url' => route('messenger.attachments.download', $message),
        ];
    }

    protected function messagePreview(?MessengerMessage $message): ?string
    {
        if (!$message) {
            return null;
        }

        if (filled($message->body)) {
            return $message->body;
        }

        if ($message->attachment_original_name) {
            return '[Attachment] ' . $message->attachment_original_name;
        }

        return null;
    }

    protected function storeAttachment(UploadedFile $attachment): array
    {
        $uploadDir = public_path('uploads/messenger');
        $originalName = $attachment->getClientOriginalName();
        $mimeType = $attachment->getClientMimeType() ?: $attachment->getMimeType();
        $sizeBytes = $attachment->getSize();

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('messenger_', true) . '.' . $attachment->getClientOriginalExtension();
        $attachment->move($uploadDir, $filename);

        return [
            'attachment_original_name' => $originalName,
            'attachment_file_path' => 'uploads/messenger/' . $filename,
            'attachment_mime_type' => $mimeType,
            'attachment_size_bytes' => $sizeBytes,
        ];
    }

    protected function copyMessengerAttachmentToReport(MessengerMessage $message, Report $report): void
    {
        if (!$message->attachment_file_path) {
            return;
        }

        [$sourcePath] = $this->resolveAttachmentPath($message);
        $uploadDir = public_path('uploads/reports');

        if (!File::exists($uploadDir)) {
            File::makeDirectory($uploadDir, 0755, true);
        }

        $extension = pathinfo($message->attachment_original_name ?: $sourcePath, PATHINFO_EXTENSION);
        $filename = uniqid('messenger_report_', true) . ($extension ? '.' . $extension : '');
        $destinationPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        File::copy($sourcePath, $destinationPath);

        ReportAttachment::create([
            'report_id' => $report->id,
            'file_path' => 'uploads/reports/' . $filename,
            'original_name' => $message->attachment_original_name ?: basename($destinationPath),
        ]);
    }

    protected function resolveAttachmentPath(MessengerMessage $message): array
    {
        $filePath = public_path($message->attachment_file_path);

        if (!file_exists($filePath)) {
            $legacyPath = storage_path('app/public/' . $message->attachment_file_path);

            if (file_exists($legacyPath)) {
                $filePath = $legacyPath;
            }
        }

        if (!file_exists($filePath)) {
            abort(404, 'File not found.');
        }

        return [
            $filePath,
            $message->attachment_mime_type ?: mime_content_type($filePath) ?: 'application/octet-stream',
        ];
    }
}
