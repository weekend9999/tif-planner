<?php

namespace App\Services;

use App\Enums\BufferMode;
use App\Models\Performance;
use App\Models\WatchPlan;
use Illuminate\Support\Collection;

class WatchPlanContext
{
    public function __construct(
        public readonly string $name,
        public readonly BufferMode $bufferMode,
        public readonly int $exitBuffer,
        public readonly int $entryBuffer,
        /** @var Collection<int, Performance> */
        public readonly Collection $performances,
        public readonly ?WatchPlan $watchPlan = null,
    ) {}
}

class GuestWatchPlanService
{
    private const SESSION_KEY = 'guest_watch_plan';

    public static function sessionKey(): string
    {
        return self::SESSION_KEY;
    }

    public function get(): WatchPlanContext
    {
        $data = session(self::SESSION_KEY, $this->defaults());

        $bufferMode = BufferMode::tryFrom($data['buffer_mode'] ?? 'normal') ?? BufferMode::Normal;
        $performanceIds = $data['performance_ids'] ?? [];

        $performances = Performance::query()
            ->with('stage')
            ->whereIn('id', $performanceIds)
            ->get()
            ->sortBy(fn (Performance $p) => array_search($p->id, $performanceIds, true))
            ->values();

        return new WatchPlanContext(
            name: $data['name'] ?? 'ゲストプラン',
            bufferMode: $bufferMode,
            exitBuffer: $data['custom_buffers']['exit'] ?? $bufferMode->defaultExitBuffer(),
            entryBuffer: $data['custom_buffers']['entry'] ?? $bufferMode->defaultEntryBuffer(),
            performances: $performances,
        );
    }

    public function update(array $payload): WatchPlanContext
    {
        $current = session(self::SESSION_KEY, $this->defaults());
        session([self::SESSION_KEY => array_merge($current, $payload)]);

        return $this->get();
    }

    public function addPerformance(int $performanceId): WatchPlanContext
    {
        $data = session(self::SESSION_KEY, $this->defaults());
        $ids = $data['performance_ids'] ?? [];

        if (! in_array($performanceId, $ids, true)) {
            $ids[] = $performanceId;
        }

        return $this->update(['performance_ids' => $ids]);
    }

    public function removePerformance(int $performanceId): WatchPlanContext
    {
        $data = session(self::SESSION_KEY, $this->defaults());
        $ids = array_values(array_filter(
            $data['performance_ids'] ?? [],
            fn (int $id) => $id !== $performanceId,
        ));

        return $this->update(['performance_ids' => $ids]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /** @return array<string, mixed> */
    private function defaults(): array
    {
        return [
            'name' => 'ゲストプラン',
            'buffer_mode' => BufferMode::Normal->value,
            'custom_buffers' => [
                'exit' => 5,
                'entry' => 5,
            ],
            'performance_ids' => [],
        ];
    }
}
