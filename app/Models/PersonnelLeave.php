<?php

// app/Models/PersonnelLeave.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelLeave extends Model
{
    protected $fillable = [
        'user_id',
        'department_id',
        'section_id',
        'from_date',
        'to_date',
        'reason',
        'leave_address',
        'encode_by',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encode_by');
    }
}
