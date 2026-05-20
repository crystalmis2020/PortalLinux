<?php

namespace App\Models;


use App\Helpers\LogHelper;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_address_to',
        'section_address_to',
        'department_address_from',
        'section_address_from',
        'issue_id',
        'issue_sub_category_id',
        'assigned_by',
        'assigned_to',
        'assigned_users',
        'reported_by',
        'issue',
        'contact_number',
        'status',
        'parent_report_id',
        'child_number',
    ];

    protected $casts = [
        'assigned_users' => 'array',
    ];

    /**
     * Get the department the report is addressed to.
     */
    public function departmentAddressTo(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_address_to');
    }

    /**
     * Get the section the report is addressed to.
     */
    public function sectionAddressTo(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_address_to');
    }

    /**
     * Get the department the report is from.
     */
    public function departmentAddressFrom(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_address_from');
    }

    /**
     * Get the section the report is from.
     */
    public function sectionAddressFrom(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_address_from');
    }

    /**
     * Get the issue related to the report.
     */
    public function issueCategory(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }

    /**
     * Get the issue sub-category related to the report.
     */
    public function issueSubCategory(): BelongsTo
    {
        return $this->belongsTo(IssueSubCategory::class, 'issue_sub_category_id');
    }

    /**
     * Get the user who assigned the report.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Get the user to whom the report is assigned.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the users to whom the report is assigned.
     */
    public function assignedUserDetails()
    {
        return $this->hasMany(User::class, 'id', 'assigned_users');
    }

    /**
     * Get the parent report if applicable.
     */
    public function parentReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'parent_report_id');
    }

    /**
     * Get child reports if any.
     */
    public function childReports(): HasMany
    {
        return $this->hasMany(Report::class, 'parent_report_id');
    }

    public function reportLogs(): HasMany
    {
        return $this->hasMany(ReportLog::class, 'report_id')->orderBy('created_at', 'desc');
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function attachment(): HasMany
    {
        return $this->hasMany(ReportAttachment::class, 'report_id');
    }

    /**
     * Boot method for logging report creation.
    */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($network) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('IP Created', $network, 'A new IP has been added by: ' . $actor);
        });

        static::updated(function ($network) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('IP Updated', $network, 'A IP has been modified by: '. $actor);
        });

        static::deleted(function ($network) {
            $actor = Auth::user()?->full_name ?? 'system';
            LogHelper::log('IP Deleted', $network, 'A IP has been removed by: '. $actor);
        });
    }

}
