<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;

class EventClockService
{
    private const SESSION_KEY = 'tif_preview_now';

    public function allowsOverride(): bool
    {
        return (bool) config('tif.allow_now_override', false);
    }

    public function isPreviewing(): bool
    {
        return $this->allowsOverride() && session()->has(self::SESSION_KEY);
    }

    public function resolve(Request $request): Carbon
    {
        if ($this->allowsOverride()) {
            if ($request->has('now')) {
                $param = trim($request->string('now')->toString());
                if ($param === '' || $param === 'reset') {
                    session()->forget(self::SESSION_KEY);
                } else {
                    $parsed = $this->parseNowParam($param, $request->string('day')->toString());
                    session([self::SESSION_KEY => $parsed->toIso8601String()]);
                }
            }

            if ($stored = session(self::SESSION_KEY)) {
                return Carbon::parse($stored);
            }

            if ($fake = config('tif.fake_now')) {
                return Carbon::parse($fake);
            }
        }

        return Carbon::now();
    }

    public function showNowLineForDay(Carbon $now, string $day): bool
    {
        return $now->format('Y-m-d') === $day;
    }

    private function parseNowParam(string $param, string $day): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $param)) {
            return Carbon::parse(str_replace('T', ' ', $param));
        }

        $day = $day !== '' ? $day : '2026-08-01';

        return Carbon::parse("{$day} {$param}");
    }
}
