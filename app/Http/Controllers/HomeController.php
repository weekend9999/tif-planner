<?php

namespace App\Http\Controllers;

use App\Enums\BufferMode;
use App\Models\Performance;
use App\Models\Stage;
use App\Models\WatchPlan;
use App\Services\FeasibilityService;
use App\Services\GuestWatchPlanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('home', [
            'days' => [
                '2026-07-31' => 'DAY1 (7/31 金)',
                '2026-08-01' => 'DAY2 (8/1 土)',
                '2026-08-02' => 'DAY3 (8/2 日)',
            ],
        ]);
    }
}
