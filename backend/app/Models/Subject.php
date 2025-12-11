<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'category',
        'weekly_hours',
        'max_meetings_per_week',
        'requires_lab',
        'is_active',
        'color',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_lab' => 'boolean',
        'metadata' => 'array',
    ];
}
