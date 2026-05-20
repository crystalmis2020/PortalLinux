<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class IssueSubCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'issue_id',
    ];

    /**
     * Get the issue that owns the sub-category.
     */
    public function issue(): BelongsTo
    {
        return $this->belongsTo(Issue::class, 'issue_id');
    }
}

