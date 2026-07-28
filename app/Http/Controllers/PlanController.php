<?php

namespace App\Http\Controllers;

use App\Enums\BufferMode;
use App\Models\Performance;
use App\Models\WatchPlan;
use App\Services\EventClockService;
use App\Services\FavoriteArtistService;
use App\Services\FeasibilityService;
use App\Services\GuestWatchPlanService;
use App\Services\TimetableGridService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(
        private readonly GuestWatchPlanService $guestWatchPlanService,
        private readonly FeasibilityService $feasibilityService,
        private readonly FavoriteArtistService $favoriteArtistService,
    ) {}

    public function show(Request $request, TimetableGridService $gridService, EventClockService $eventClock): View
    {
        $context = $this->resolveContext($request);
        $now = $eventClock->resolve($request);

        $analysis = $this->feasibilityService->analyze(
            performances: $context->performances,
            bufferMode: $context->bufferMode,
            exitBuffer: $context->exitBuffer,
            entryBuffer: $context->entryBuffer,
            now: $now,
        );

        $blockAnnotations = $this->feasibilityService->buildBlockAnnotations(
            performances: $context->performances,
            bufferMode: $context->bufferMode,
            exitBuffer: $context->exitBuffer,
            entryBuffer: $context->entryBuffer,
            now: $now,
        );

        $day = $request->string('day')->toString();
        if ($day === '') {
            $day = $context->performances
                ->sortBy(fn (Performance $p) => $p->day->format('Y-m-d').' '.$p->starts_at)
                ->first()?->day->format('Y-m-d') ?? '2026-08-01';
        }

        $favoriteArtists = $this->favoriteArtistService->list($request->user());
        $dayCounts = $context->performances
            ->groupBy(fn (Performance $p) => $p->day->format('Y-m-d'))
            ->map->count()
            ->all();

        $selectedIds = $context->performances
            ->filter(fn (Performance $p) => $p->day->format('Y-m-d') === $day)
            ->pluck('id')
            ->all();

        return view('plans.show', [
            'context' => $context,
            'analysis' => $analysis,
            'blockAnnotations' => $blockAnnotations,
            'grid' => $gridService->buildForDay($day, $now, $favoriteArtists),
            'day' => $day,
            'days' => config('tif.days'),
            'dayCounts' => $dayCounts,
            'selectedIds' => $selectedIds,
            'now' => $now,
            'showNowLine' => $eventClock->showNowLineForDay($now, $day),
            'wide' => true,
        ]);
    }

    public function edit(Request $request, TimetableGridService $gridService): View
    {
        $context = $this->resolveContext($request);
        $query = $request->string('q')->toString();
        $day = $request->string('day', '2026-08-01')->toString();
        $favoriteArtists = $this->favoriteArtistService->list($request->user());

        return view('plans.edit', [
            'context' => $context,
            'grid' => $gridService->buildForDay($day, null, $favoriteArtists),
            'day' => $day,
            'days' => config('tif.days'),
            'selectedIds' => $context->performances->pluck('id')->all(),
            'favoriteArtists' => $favoriteArtists,
            'query' => $query,
            'bufferModes' => BufferMode::cases(),
            'wide' => true,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'buffer_mode' => ['required', 'in:conservative,normal,aggressive'],
            'exit_buffer' => ['nullable', 'integer', 'min:0', 'max:30'],
            'entry_buffer' => ['nullable', 'integer', 'min:0', 'max:30'],
        ]);

        if ($request->user()) {
            $plan = $this->userPlan($request);
            $plan->update([
                'name' => $validated['name'] ?? $plan->name,
                'buffer_mode' => $validated['buffer_mode'],
                'custom_buffers' => [
                    'exit' => $validated['exit_buffer'] ?? BufferMode::from($validated['buffer_mode'])->defaultExitBuffer(),
                    'entry' => $validated['entry_buffer'] ?? BufferMode::from($validated['buffer_mode'])->defaultEntryBuffer(),
                ],
            ]);
        } else {
            $mode = BufferMode::from($validated['buffer_mode']);
            $this->guestWatchPlanService->update([
                'name' => $validated['name'] ?? 'ゲストプラン',
                'buffer_mode' => $validated['buffer_mode'],
                'custom_buffers' => [
                    'exit' => $validated['exit_buffer'] ?? $mode->defaultExitBuffer(),
                    'entry' => $validated['entry_buffer'] ?? $mode->defaultEntryBuffer(),
                ],
            ]);
        }

        return redirect()->route('plans.edit')->with('status', '設定を更新しました');
    }

    public function addPerformance(Request $request, Performance $performance): JsonResponse|RedirectResponse
    {
        if ($request->user()) {
            $plan = $this->userPlan($request);
            $plan->items()->firstOrCreate(['performance_id' => $performance->id]);
        } else {
            $this->guestWatchPlanService->addPerformance($performance->id);
        }

        return $this->performanceToggleResponse(
            $request,
            $performance,
            "{$performance->artist_name} を追加しました",
        );
    }

    public function removePerformance(Request $request, Performance $performance): JsonResponse|RedirectResponse
    {
        if ($request->user()) {
            $plan = $this->userPlan($request);
            $plan->items()->where('performance_id', $performance->id)->delete();
        } else {
            $this->guestWatchPlanService->removePerformance($performance->id);
        }

        return $this->performanceToggleResponse(
            $request,
            $performance,
            "{$performance->artist_name} を削除しました",
        );
    }

    private function performanceToggleResponse(
        Request $request,
        Performance $performance,
        string $message,
    ): JsonResponse|RedirectResponse {
        if ($request->wantsJson()) {
            return response()->json([
                'performance_id' => $performance->id,
                'count' => $this->resolveContext($request)->performances->count(),
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('status', $message);
    }

    private function resolveContext(Request $request): \App\Services\WatchPlanContext
    {
        if ($request->user()) {
            $plan = $this->userPlan($request);
            $performances = $plan->items()->with('performance.stage')->get()
                ->map->performance;

            return new \App\Services\WatchPlanContext(
                name: $plan->name,
                bufferMode: $plan->buffer_mode,
                exitBuffer: $plan->exitBuffer(),
                entryBuffer: $plan->entryBuffer(),
                performances: $performances,
                watchPlan: $plan,
            );
        }

        return $this->guestWatchPlanService->get();
    }

    private function userPlan(Request $request): WatchPlan
    {
        return WatchPlan::query()->firstOrCreate(
            ['user_id' => $request->user()->id, 'name' => 'マイプラン'],
            ['buffer_mode' => BufferMode::Normal, 'custom_buffers' => ['exit' => 5, 'entry' => 5]],
        );
    }
}
