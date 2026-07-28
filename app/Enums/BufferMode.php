<?php

namespace App\Enums;

enum BufferMode: string
{
    case Conservative = 'conservative';
    case Normal = 'normal';
    case Aggressive = 'aggressive';

    public function label(): string
    {
        return match ($this) {
            self::Conservative => '余裕（+20%）',
            self::Normal => '標準',
            self::Aggressive => 'ギリギリ',
        };
    }

    public function travelMultiplier(): float
    {
        return match ($this) {
            self::Conservative => 1.2,
            self::Normal => 1.0,
            self::Aggressive => 1.0,
        };
    }

    public function defaultExitBuffer(): int
    {
        return match ($this) {
            self::Conservative => 7,
            self::Normal => 5,
            self::Aggressive => 3,
        };
    }

    public function defaultEntryBuffer(): int
    {
        return match ($this) {
            self::Conservative => 7,
            self::Normal => 5,
            self::Aggressive => 3,
        };
    }
}
