<?php

namespace App\Models;

use App\Helpers\LogHelper;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class ReportLog extends Model
{
    use HasFactory;

    protected static ?array $tableColumns = null;

    protected $fillable = [
        'report_id',
        'user_id',
        'message',
        'remarks',
        'status',
        'parent_id',
        'is_child',
    ];

    /**
     * Get the report that this log belongs to.
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * Get the user who created this log.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the parent log if applicable.
     */
    public function parentLog(): BelongsTo
    {
        return $this->belongsTo(ReportLog::class, 'parent_id');
    }

    /**
     * Get child logs if any.
     */
    public function childLogs(): HasMany
    {
        return $this->hasMany(ReportLog::class, 'parent_id');
    }


     /**
     * Boot method for logging report creation.
     */
    protected static function boot()
    {
        parent::boot();

        // Keep legacy camelCase columns in sync for older database schemas.
        static::creating(function (ReportLog $reportLog) {
            if ($reportLog->hasColumn('reportId') && isset($reportLog->report_id) && !isset($reportLog->reportId)) {
                $reportLog->reportId = $reportLog->report_id;
            }
            if ($reportLog->hasColumn('userId') && isset($reportLog->user_id) && !isset($reportLog->userId)) {
                $reportLog->userId = $reportLog->user_id;
            }
            if ($reportLog->hasColumn('parentId') && isset($reportLog->parent_id) && !isset($reportLog->parentId)) {
                $reportLog->parentId = $reportLog->parent_id;
            }
            if ($reportLog->hasColumn('isChild') && isset($reportLog->is_child) && !isset($reportLog->isChild)) {
                $reportLog->isChild = $reportLog->is_child;
            }
        });

        static::updating(function (ReportLog $reportLog) {
            if ($reportLog->hasColumn('reportId') && isset($reportLog->report_id)) {
                $reportLog->reportId = $reportLog->report_id;
            }
            if ($reportLog->hasColumn('userId') && isset($reportLog->user_id)) {
                $reportLog->userId = $reportLog->user_id;
            }
            if ($reportLog->hasColumn('parentId') && isset($reportLog->parent_id)) {
                $reportLog->parentId = $reportLog->parent_id;
            }
            if ($reportLog->hasColumn('isChild') && isset($reportLog->is_child)) {
                $reportLog->isChild = $reportLog->is_child;
            }
        });

        static::created(function ($reportLog) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('Report Log Created', $reportLog, 'A new report has been submitted by: ' . $actor);
        });

        static::updated(function ($reportLog) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('Report Log Updated', $reportLog, 'A report has been modified by: '. $actor);
        });

        // static::deleted(function ($report) {
        //     LogHelper::log('Report Deleted', $report, 'A report has been removed.');
        // });
    }

    protected function hasColumn(string $column): bool
    {
        if (self::$tableColumns === null) {
            self::$tableColumns = Schema::getColumnListing($this->getTable());
        }

        return in_array($column, self::$tableColumns, true);
    }

}
