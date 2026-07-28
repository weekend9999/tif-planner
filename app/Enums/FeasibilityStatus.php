<?php

namespace App\Enums;

enum FeasibilityStatus: string
{
    case Ok = 'ok';
    case Tight = 'tight';
    case Impossible = 'impossible';
    case LeaveNow = 'leave_now';

    public function label(): string
    {
        return match ($this) {
            self::Ok => '余裕あり',
            self::Tight => 'ギリギリ',
            self::Impossible => '間に合わない',
            self::LeaveNow => '今すぐ移動',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ok => '✅',
            self::Tight => '⚠️',
            self::Impossible => '❌',
            self::LeaveNow => '🚨',
        };
    }

    public function colorClass(): string
    {
        return match ($this) {
            self::Ok => 'text-emerald-400 border-emerald-500/40 bg-emerald-500/10',
            self::Tight => 'text-amber-400 border-amber-500/40 bg-amber-500/10',
            self::Impossible => 'text-red-400 border-red-500/40 bg-red-500/10',
            self::LeaveNow => 'text-red-300 border-red-400/60 bg-red-500/20 animate-pulse',
        };
    }

    public function blockBorderClass(): string
    {
        return match ($this) {
            self::Ok => 'ring-[3px] ring-emerald-500 ring-offset-1 ring-offset-white border-2 border-emerald-600 shadow-md z-30',
            self::Tight, self::LeaveNow => 'ring-[3px] ring-amber-400 ring-offset-1 ring-offset-white border-2 border-amber-500 shadow-md z-30',
            self::Impossible => 'ring-[3px] ring-red-500 ring-offset-1 ring-offset-white border-2 border-red-600 shadow-md z-30',
        };
    }
}
