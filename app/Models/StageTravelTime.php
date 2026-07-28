<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StageTravelTime extends Model
{
    protected $fillable = [
        'from_stage_id',
        'to_stage_id',
        'walk_minutes',
        'notes',
    ];

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(Stage::class, 'to_stage_id');
    }
}
