<?php

namespace Tests\Feature;

use App\Models\Performance;
use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NowLineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
        config(['tif.allow_now_override' => true]);
    }

    public function test_timetable_shows_now_line_with_simulated_time(): void
    {
        $response = $this->get(route('timetable.index', [
            'day' => '2026-08-01',
            'now' => '2026-08-01 14:15',
        ]));

        $response->assertOk();
        $response->assertSee('border-red-600', false);
        $response->assertSee('NOW 赤線表示中', false);
    }

    public function test_plan_result_shows_now_line_with_simulated_time(): void
    {
        $performance = Performance::query()->where('artist_name', '=LOVE')->firstOrFail();
        $this->post(route('plans.add', $performance));

        $response = $this->get(route('plans.show', [
            'day' => '2026-08-01',
            'now' => '2026-08-01 20:30',
        ]));

        $response->assertOk();
        $response->assertSee('border-red-600', false);
        $response->assertSee('NOW 赤線:', false);
    }

    public function test_import_routes_are_removed(): void
    {
        $this->get('/import')->assertNotFound();
    }
}
