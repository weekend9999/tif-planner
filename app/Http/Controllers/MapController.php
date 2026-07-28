<?php

namespace App\Http\Controllers;

use App\Services\GuestWatchPlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MapController extends Controller
{
    public function __construct(
        private readonly GuestWatchPlanService $guestWatchPlanService,
    ) {}

    public function index(Request $request): View
    {
        $context = $request->user()
            ? $this->userContext($request)
            : $this->guestWatchPlanService->get();

        return view('map.index', [
            'performances' => $context->performances->load('stage'),
            'wide' => true,
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
