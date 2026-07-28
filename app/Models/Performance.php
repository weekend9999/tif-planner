<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Performance extends Model
{
    protected $fillable = [
        'day',
        'stage_id',
        'artist_name',
        'starts_at',
        'ends_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'day' => 'date',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function watchPlanItems(): HasMany
    {
        return $this->hasMany(WatchPlanItem::class);
    }

    public function dayLabel(): string
    {
        return match ($this->day->format('Y-m-d')) {
            '2026-07-31' => 'DAY1 (7/31)',
            '2026-08-01' => 'DAY2 (8/1)',
            '2026-08-02' => 'DAY3 (8/2)',
            default => $this->day->format('n/j'),
        };
    }

    public function startsAtFormatted(): string
    {
        return substr((string) $this->starts_at, 0, 5);
    }

    public function endsAtFormatted(): string
    {
        return substr((string) $this->ends_at, 0, 5);
    }
}
