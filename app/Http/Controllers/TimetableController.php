<?php

namespace App\Http\Controllers;

use App\Services\EventClockService;
use App\Services\FavoriteArtistService;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(
        Request $request,
        TimetableGridService $gridService,
        FavoriteArtistService $favoriteArtistService,
        EventClockService $eventClock,
    ): View {
        $day = $request->string('day', '2026-08-01')->toString();
        $now = $eventClock->resolve($request);
        $favoriteArtists = $favoriteArtistService->list($request->user());

        return view('timetable.index', [
            'day' => $day,
            'days' => config('tif.days'),
            'grid' => $gridService->buildForDay($day, $now, $favoriteArtists),
            'now' => $now,
            'showNowLine' => $eventClock->showNowLineForDay($now, $day),
            'wide' => true,
        ]);
    }
}
