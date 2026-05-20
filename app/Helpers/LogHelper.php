<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class LogHelper
{
    public static function log(string $action, ?Model $model = null, ?string $details = null): void
    {

        // Ensure user is authenticated before accessing ID
        $userId = Auth::check() ? Auth::user()->id : null;

        // Capture model attributes (new values)
        $newValues = $model ? json_encode($model->getAttributes()) : null;

        ActivityLog::create([
            'user_id'    => is_numeric($userId) ? (int) $userId : null, // Ensure only an integer is inserted
            'action'     => $action,
            'details'    => $details,
            'new_values' => $newValues, // Store the new/updated values
            'model_type' => $model ? get_class($model) : null,
            'model_id'   => $model ? $model->id : null,
            'ip_address' => request()->header('X-Forwarded-For') ?? request()->ip(),
        ]);
    }
}
