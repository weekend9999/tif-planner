<?php

namespace App\Services;

use App\Models\Performance;
use App\Models\Stage;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableGridService
{
    /**
     * @return array{
     *     day: string,
     *     day_label: string,
     *     grid_start: string,
     *     grid_end: string,
     *     total_height_px: float,
     *     time_labels: list<array{time: string, top_px: float}>,
     *     stages: list<array{id: int, slug: string, name: string, header: string, label: string}>,
     *     blocks: list<array{
     *         id: int,
     *         stage_slug: string,
     *         artist_name: string,
     *         starts_at: string,
     *         ends_at: string,
     *         top_px: float,
     *         height_px: float,
     *         is_now: bool,
     *         is_favorite_artist: bool
     *     }>
     * }
     */
    public function buildForDay(string $day, ?Carbon $now = null, array $favoriteArtists = []): array
    {
        $slotMinutes = (int) config('tif.grid.slot_minutes', 10);
        $pxPerMinute = (float) config('tif.grid.px_per_minute', 2.2);
        $paddingMinutes = (int) config('tif.grid.padding_minutes', 15);
        $blockGapPx = (float) config('tif.grid.block_gap_px', 3);
        $favoriteSet = collect($favoriteArtists)->flip();

        $performances = Performance::query()
            ->with('stage')
            ->whereDate('day', $day)
            ->orderBy('starts_at')
            ->get();

        $gridStart = $this->gridBoundary($performances, 'min', $paddingMinutes, $slotMinutes);
        $gridEnd = $this->gridBoundary($performances, 'max', $paddingMinutes, $slotMinutes);

        $startMinutes = $this->timeToMinutes($gridStart);
        $endMinutes = $this->timeToMinutes($gridEnd);
        $totalHeightPx = ($endMinutes - $startMinutes) * $pxPerMinute;

        $stages = $this->orderedStages();
        $themes = config('tif.stage_themes', []);
        $shortNames = config('tif.stage_short_names', []);
        $showNow = $now !== null && $now->format('Y-m-d') === $day;

        $blocks = $performances->map(function (Performance $performance) use (
            $day,
            $startMinutes,
            $pxPerMinute,
            $now,
            $showNow,
            $favoriteSet,
        ) {
            $slug = $performance->stage->slug;

            $blockStart = $this->timeToMinutes(substr((string) $performance->starts_at, 0, 5));
            $blockEnd = $this->timeToMinutes(substr((string) $performance->ends_at, 0, 5));

            $startAt = Carbon::parse($day.' '.substr((string) $performance->starts_at, 0, 5));
            $endAt = Carbon::parse($day.' '.substr((string) $performance->ends_at, 0, 5));

            return [
                'id' => $performance->id,
                'stage_slug' => $slug,
                'artist_name' => $performance->artist_name,
                'starts_at' => $performance->startsAtFormatted(),
                'ends_at' => $performance->endsAtFormatted(),
                'top_px' => ($blockStart - $startMinutes) * $pxPerMinute,
                'height_px' => max(16, ($blockEnd - $blockStart) * $pxPerMinute),
                'is_now' => $showNow && $now->between($startAt, $endAt),
                'is_favorite_artist' => $favoriteSet->has($performance->artist_name),
            ];
        })->values()->all();

        $blocks = $this->applyBlockGaps($blocks, $blockGapPx);

        $nowLinePx = null;
        if ($showNow) {
            $nowMinutes = $this->timeToMinutes($now->format('H:i'));
            if ($nowMinutes >= $startMinutes && $nowMinutes <= $endMinutes) {
                $nowLinePx = ($nowMinutes - $startMinutes) * $pxPerMinute;
            }
        }

        return [
            'day' => $day,
            'day_label' => config('tif.days')[$day] ?? $day,
            'grid_start' => $gridStart,
            'grid_end' => $gridEnd,
            'total_height_px' => $totalHeightPx,
            'now_line_px' => $nowLinePx,
            'time_labels' => $this->timeLabels($gridStart, $gridEnd, $startMinutes, $slotMinutes, $pxPerMinute),
            'stages' => $stages->map(fn (Stage $stage) => [
                'id' => $stage->id,
                'slug' => $stage->slug,
                'name' => $stage->name,
                'short_name' => $shortNames[$stage->slug] ?? $stage->name,
                'header' => $themes[$stage->slug]['header'] ?? '#37474f',
                'column' => $themes[$stage->slug]['column'] ?? $themes[$stage->slug]['header'] ?? '#37474f',
                'label' => $themes[$stage->slug]['label'] ?? '#ffffff',
            ])->values()->all(),
            'blocks' => $blocks,
        ];
    }

    /** @return Collection<int, Stage> */
    private function orderedStages(): Collection
    {
        $order = config('tif.stage_order', []);
        $stages = Stage::query()->get()->keyBy('slug');

        return collect($order)
            ->map(fn (string $slug) => $stages->get($slug))
            ->filter()
            ->values();
    }

    /** @param  Collection<int, Performance>  $performances */
    private function gridBoundary(Collection $performances, string $mode, int $padding, int $slotMinutes): string
    {
        if ($performances->isEmpty()) {
            return $mode === 'min' ? '10:00' : '22:00';
        }

        $times = $performances->flatMap(fn (Performance $p) => [
            substr((string) $p->starts_at, 0, 5),
            substr((string) $p->ends_at, 0, 5),
        ]);

        $minutes = $mode === 'min'
            ? $this->timeToMinutes((string) $times->min()) - $padding
            : $this->timeToMinutes((string) $times->max()) + $padding;

        $minutes = (int) (floor($minutes / $slotMinutes) * $slotMinutes);
        $minutes = max(0, $minutes);

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    /**
     * @return list<array{time: string, top_px: float}>
     */
    private function timeLabels(string $gridStart, string $gridEnd, int $startMinutes, int $slotMinutes, float $pxPerMinute): array
    {
        $labels = [];
        $cursor = Carbon::createFromFormat('H:i', $gridStart);
        $end = Carbon::createFromFormat('H:i', $gridEnd);

        while ($cursor->lte($end)) {
            $time = $cursor->format('H:i');
            $labels[] = [
                'time' => $time,
                'top_px' => ($this->timeToMinutes($time) - $startMinutes) * $pxPerMinute,
            ];
            $cursor->addMinutes($slotMinutes);
        }

        return $labels;
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m] = array_map('intval', explode(':', $time));

        return $h * 60 + $m;
    }

    /**
     * @param  list<array<string, mixed>>  $blocks
     * @return list<array<string, mixed>>
     */
    private function applyBlockGaps(array $blocks, float $gapPx): array
    {
        if ($gapPx <= 0) {
            return $blocks;
        }

        $byStage = collect($blocks)->groupBy('stage_slug');

        return $byStage->flatMap(function (Collection $stageBlocks) use ($gapPx) {
            $sorted = $stageBlocks->sortBy('top_px')->values();

            return $sorted->map(function (array $block, int $index) use ($sorted, $gapPx) {
                if ($index === 0) {
                    return $block;
                }

                $previous = $sorted[$index - 1];
                if ($previous['ends_at'] !== $block['starts_at']) {
                    return $block;
                }

                $block['top_px'] += $gapPx;
                $block['height_px'] = max(16, $block['height_px'] - $gapPx);

                return $block;
            });
        })->values()->all();
    }
}
