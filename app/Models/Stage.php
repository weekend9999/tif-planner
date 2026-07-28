<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stage extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'area',
        'map_x',
        'map_y',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'map_x' => 'decimal:2',
            'map_y' => 'decimal:2',
            'meta' => 'array',
        ];
    }

    public function performances(): HasMany
    {
        return $this->hasMany(Performance::class);
    }

    public function travelRules(): HasMany
    {
        return $this->hasMany(TravelRule::class);
    }
}
