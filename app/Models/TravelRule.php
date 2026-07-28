<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TravelRule extends Model
{
    protected $fillable = [
        'stage_id',
        'rule_type',
        'extra_minutes',
        'description',
    ];

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }
}
