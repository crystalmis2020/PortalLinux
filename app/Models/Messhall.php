<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;


class Messhall extends Model
{
    use HasFactory;

    protected $fillable = [
        'department_id',
        'user_id',
        'guest_name',
        'company_name',
        'date_stay',
        'start_date_stay',
        'end_date_stay',
        'with_meal',
        'status',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

