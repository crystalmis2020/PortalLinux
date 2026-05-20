<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    protected $fillable = [
        'name',
    ];

    public function subCategories(): HasMany{
        return $this->hasMany(IssueSubCategory::class, 'issue_id');
    }
}
