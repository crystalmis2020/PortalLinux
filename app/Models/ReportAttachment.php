<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportAttachment extends Model
{
    use HasFactory;

    protected $fillable = ['report_id', 'file_path', 'original_name'];

    public function report()
    {
        return $this->belongsTo(Report::class);
    }
}
