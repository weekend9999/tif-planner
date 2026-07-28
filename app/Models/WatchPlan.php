<?php

namespace App\Models;

use App\Enums\BufferMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WatchPlan extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'buffer_mode',
        'custom_buffers',
    ];

    protected function casts(): array
    {
        return [
            'custom_buffers' => 'array',
            'buffer_mode' => BufferMode::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(WatchPlanItem::class)->orderBy('priority');
    }

    public function exitBuffer(): int
    {
        return $this->custom_buffers['exit'] ?? $this->buffer_mode->defaultExitBuffer();
    }

    public function entryBuffer(): int
    {
        return $this->custom_buffers['entry'] ?? $this->buffer_mode->defaultEntryBuffer();
    }
}
