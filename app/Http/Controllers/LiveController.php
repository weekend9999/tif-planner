<?php

namespace App\Http\Controllers;

use App\Models\Performance;
use App\Models\Stage;
use App\Services\EventClockService;
use App\Services\FeasibilityService;
use App\Services\GuestWatchPlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LiveController extends Controller
{
    public function __construct(
        private readonly GuestWatchPlanService $guestWatchPlanService,
        private readonly FeasibilityService $feasibilityService,
    ) {}

    public function index(Request $request, EventClockService $eventClock): View
    {
        $day = $request->string('day', '2026-08-01')->toString();
        $now = $eventClock->resolve($request);

        $performances = Performance::query()
            ->with('stage')
            ->whereDate('day', $day)
            ->orderBy('starts_at')
            ->get();

        $nowPlaying = $performances->filter(function (Performance $p) use ($now, $day) {
            if ($p->day->format('Y-m-d') !== $day) {
                return false;
            }
            $start = Carbon::parse("{$day} {$p->starts_at}");
            $end = Carbon::parse("{$day} {$p->ends_at}");

            return $now->between($start, $end);
        });

        $context = $request->user()
            ? $this->userContext($request)
            : $this->guestWatchPlanService->get();

        $upcoming = $context->performances
            ->filter(function (Performance $p) use ($now, $day) {
                return $p->day->format('Y-m-d') === $day
                    && Carbon::parse("{$day} {$p->starts_at}")->gt($now);
            })
            ->sortBy('starts_at')
            ->values();

        $analysis = $this->feasibilityService->analyze(
            performances: $context->performances->filter(fn (Performance $p) => $p->day->format('Y-m-d') === $day),
            bufferMode: $context->bufferMode,
            exitBuffer: $context->exitBuffer,
            entryBuffer: $context->entryBuffer,
            now: $now,
        );

        return view('live.index', [
            'day' => $day,
            'days' => [
                '2026-07-31' => 'DAY1 (7/31)',
                '2026-08-01' => 'DAY2 (8/1)',
                '2026-08-02' => 'DAY3 (8/2)',
            ],
            'now' => $now,
            'nowPlaying' => $nowPlaying,
            'upcoming' => $upcoming,
            'analysis' => $analysis,
            'context' => $context,
            'stages' => Stage::all(),
        ]);
    }

    private function userContext(Request $request): \App\Services\WatchPlanContext
    {
        $plan = \App\Models\WatchPlan::query()->firstOrCreate(
            ['user_id' => $request->user()->id, 'name' => 'マイプラン'],
            ['buffer_mode' => \App\Enums\BufferMode::Normal, 'custom_buffers' => ['exit' => 5, 'entry' => 5]],
        );

        return new \App\Services\WatchPlanContext(
            name: $plan->name,
            bufferMode: $plan->buffer_mode,
            exitBuffer: $plan->exitBuffer(),
            entryBuffer: $plan->entryBuffer(),
            performances: $plan->items()->with('performance.stage')->get()->map->performance,
            watchPlan: $plan,
        );
    }
}
