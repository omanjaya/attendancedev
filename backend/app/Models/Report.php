<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Report extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'type',
        'format',
        'filename',
        'file_path',
        'status',
        'filters',
        'generated_by',
        'expires_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $appends = ['report_type'];

    public function getReportTypeAttribute()
    {
        return $this->type;
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
