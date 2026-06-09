<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

if (!function_exists('getReportStatusClass')) {
    /**
     * Get the Bootstrap class for report status.
     *
     * @param string $status
     * @return string
     */
    function getReportStatusClass($status){
        return match (strtolower($status)) {
            'new' => 'bg-primary',
            'in progress' => 'bg-warning',
            'resolved' => 'bg-success',
            'assigned' => 'bg-info',
            'closed' => 'bg-secondary',
            default => 'bg-light',
        };
    }
}

if (!function_exists('getUserNotifications')) {
    /**
     * Retrieve the latest notifications for the authenticated user.
     *
     * This function fetches notifications where:
     * - The user is the direct recipient (`to_user_id`).
     * - The notification is assigned to the user's section (`section_to`).
     * - The results are sorted by the latest notifications.
     *
     * @param int $limit The number of notifications to retrieve. Default is 8.
     * @return \Illuminate\Database\Eloquent\Collection A collection of Notification models.
     */
    function getUserNotifications($limit = 8): \Illuminate\Database\Eloquent\Collection {
        $user = Auth::user();
        if (!$user) {
            return collect(); // Return an empty collection if no authenticated user
        }

        // Avoid breaking layout rendering if migrations are out of sync.
        if (!Schema::hasTable('notifications')) {
            return collect();
        }

        return \App\Models\Notification::where('to_user_id', $user->id)->where('is_read', 'No')
        ->latest()
        //->take($limit)
        ->get();
    }
}

if (!function_exists('portalMessengerEnabled')) {
    function portalMessengerEnabled(): bool
    {
        return Schema::hasTable('messenger_conversations') && Schema::hasTable('messenger_messages');
    }
}

if (!function_exists('getPortalMessengerUnreadCount')) {
    function getPortalMessengerUnreadCount(): int
    {
        $user = Auth::user();

        if (!$user || !portalMessengerEnabled()) {
            return 0;
        }

        return \App\Models\MessengerMessage::where('recipient_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }
}

if (!function_exists('getAssignedUserNames')) {
    function getAssignedUserNames($assignedUsersJson)
    {
        if (empty($assignedUsersJson)) {
            return 'Not Yet Assigned';
        }

        // Decode assigned_users field (JSON to array)
        $assignedUserIds = is_array($assignedUsersJson) ? $assignedUsersJson : json_decode($assignedUsersJson, true);

        // Fetch user full names from User model
        $assignedUsers = \App\Models\User::whereIn('id', $assignedUserIds)->pluck('full_name')->toArray();

        return implode('<br />', $assignedUsers);
    }
}

if (!function_exists('sendIpMsgNotification')) {
    /**
     * Send an IP Messenger notification to a specified IP with a given message.
     *
     * @param string $message The message to be sent.
     * @param string|null $ip The IP address of the recipient.
     * @return void
     */
    function sendIpMsgNotification(string $message, ?string $ip): void
    {
        if ($ip === null || empty(trim($ip))) {
            return;
        }

        // if(env('APP_ENV') == 'local'){
        //     $ip = '128.0.100.14';
        //     $message .= '[ TEST TEST ]';
        // }

        // $message .= ' [THIS IS JUST TEST] ';
        $safeMessage = trim(str_replace(["\r", "\n"], ' ', $message));
        $safeIp = trim($ip);

        if (!filter_var($safeIp, FILTER_VALIDATE_IP)) {
            \Illuminate\Support\Facades\Log::warning('IPMSG notification skipped: invalid recipient IP', [
                'ip' => $safeIp,
            ]);
            return;
        }

        $senderName = preg_replace('/[:\r\n]/', ' ', (string) config('app.name', 'Support Portal'));
        $senderHost = preg_replace('/[:\r\n]/', ' ', gethostname() ?: 'support-portal');
        $packetNo = (string) (time() . random_int(1000, 9999));
        $sendMessageCommand = 0x20;
        $packet = '1:' . $packetNo . ':' . $senderName . ':' . $senderHost . ':' . $sendMessageCommand . ':' . $safeMessage . "\0";
        $targetIp = filter_var($safeIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $safeIp . ']' : $safeIp;
        $socket = @stream_socket_client('udp://' . $targetIp . ':2425', $errno, $errstr, 1, STREAM_CLIENT_CONNECT);

        if ($socket === false) {
            \Illuminate\Support\Facades\Log::warning('IPMSG notification failed: unable to open UDP socket', [
                'ip' => $safeIp,
                'port' => 2425,
                'error_code' => $errno,
                'error' => $errstr,
            ]);
            return;
        }

        $bytesWritten = @fwrite($socket, $packet);
        @fclose($socket);

        if ($bytesWritten === false || $bytesWritten < strlen($packet)) {
            \Illuminate\Support\Facades\Log::warning('IPMSG notification failed: UDP packet was not fully sent', [
                'ip' => $safeIp,
                'port' => 2425,
                'bytes_written' => $bytesWritten,
                'packet_length' => strlen($packet),
            ]);
        }
    }
}

if (!function_exists('attachmentValidationRule')) {
    /**
     * Returns the validation rule for file attachments.
     *
     * @return string
     */
    function attachmentValidationRule(bool $required = false): string
    {
        $presenceRule = $required ? 'required' : 'nullable';

        return $presenceRule . '|file|extensions:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,txt|max:' . attachmentMaxUploadKilobytes();
    }
}

if (!function_exists('iniUploadSizeToBytes')) {
    function iniUploadSizeToBytes(string $value): int
    {
        $value = trim($value);

        if ($value === '') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (float) $value;

        switch ($unit) {
            case 'g':
                $bytes *= 1024;
                // no break
            case 'm':
                $bytes *= 1024;
                // no break
            case 'k':
                $bytes *= 1024;
                break;
        }

        return (int) $bytes;
    }
}

if (!function_exists('attachmentMaxUploadBytes')) {
    function attachmentMaxUploadBytes(): int
    {
        $configuredLimit = 20 * 1024 * 1024;
        $uploadLimit = iniUploadSizeToBytes((string) ini_get('upload_max_filesize'));
        $postLimit = iniUploadSizeToBytes((string) ini_get('post_max_size'));

        $limits = array_filter([$configuredLimit, $uploadLimit, $postLimit]);

        return min($limits);
    }
}

if (!function_exists('attachmentMaxUploadKilobytes')) {
    function attachmentMaxUploadKilobytes(): int
    {
        return max(1, (int) floor(attachmentMaxUploadBytes() / 1024));
    }
}

if (!function_exists('attachmentMaxUploadLabel')) {
    function attachmentMaxUploadLabel(): string
    {
        $bytes = attachmentMaxUploadBytes();

        if ($bytes >= 1024 * 1024) {
            $megabytes = $bytes / (1024 * 1024);

            return rtrim(rtrim(number_format($megabytes, 1), '0'), '.') . 'MB';
        }

        return max(1, (int) floor($bytes / 1024)) . 'KB';
    }
}
