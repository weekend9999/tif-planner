<?php

namespace Tests\Unit;

use App\Services\EventClockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventClockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['tif.allow_now_override' => true]);
    }

    public function test_now_query_sets_preview_time_in_session(): void
    {
        $service = app(EventClockService::class);
        $request = Request::create('/timetable', 'GET', [
            'day' => '2026-08-01',
            'now' => '2026-08-01 14:15',
        ]);

        $resolved = $service->resolve($request);

        $this->assertSame('2026-08-01 14:15', $resolved->format('Y-m-d H:i'));
        $this->assertTrue($service->isPreviewing());
    }

    public function test_reset_clears_preview_time(): void
    {
        $service = app(EventClockService::class);

        $service->resolve(Request::create('/timetable', 'GET', [
            'day' => '2026-08-01',
            'now' => '2026-08-01 14:15',
        ]));

        $service->resolve(Request::create('/timetable', 'GET', ['now' => 'reset']));

        $this->assertFalse($service->isPreviewing());
    }

    public function test_time_only_uses_selected_day(): void
    {
        $service = app(EventClockService::class);
        $resolved = $service->resolve(Request::create('/timetable', 'GET', [
            'day' => '2026-08-01',
            'now' => '20:25',
        ]));

        $this->assertSame('2026-08-01 20:25', $resolved->format('Y-m-d H:i'));
    }
}
