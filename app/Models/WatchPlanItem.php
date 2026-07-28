<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchPlanItem extends Model
{
    protected $fillable = [
        'watch_plan_id',
        'performance_id',
        'priority',
        'notes',
    ];

    public function watchPlan(): BelongsTo
    {
        return $this->belongsTo(WatchPlan::class);
    }

    public function performance(): BelongsTo
    {
        return $this->belongsTo(Performance::class);
    }
}
