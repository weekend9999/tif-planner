<?php

namespace Tests\Feature;

use App\Models\Performance;
use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
    }

    public function test_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('推し巡りプランナー');
    }

    public function test_edit_page_shows_timetable_grid(): void
    {
        $response = $this->get(route('plans.edit', ['day' => '2026-08-01']));

        $response->assertOk();
        $response->assertSee('tif-timetable', false);
        $response->assertSee('HOT', false);
        $response->assertSee('≠ME');
    }

    public function test_guest_can_add_performance_to_plan(): void
    {
        $performance = Performance::query()->where('artist_name', '=LOVE')->firstOrFail();

        $response = $this->post(route('plans.add', $performance));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        $this->get(route('plans.show', ['day' => '2026-08-01']))
            ->assertOk()
            ->assertSee('tif-timetable', false)
            ->assertSee('=LOVE', false);
    }

    public function test_guest_can_toggle_performance_via_json(): void
    {
        $performance = Performance::query()->where('artist_name', '=LOVE')->firstOrFail();

        $this->postJson(route('plans.add', $performance))
            ->assertOk()
            ->assertJson([
                'performance_id' => $performance->id,
                'count' => 1,
            ]);

        $this->deleteJson(route('plans.remove', $performance))
            ->assertOk()
            ->assertJson([
                'performance_id' => $performance->id,
                'count' => 0,
            ]);
    }

    public function test_edit_page_does_not_show_registered_performance_list(): void
    {
        $performance = Performance::query()->where('artist_name', '=LOVE')->firstOrFail();
        $this->post(route('plans.add', $performance));

        $this->get(route('plans.edit', ['day' => '2026-08-01']))
            ->assertOk()
            ->assertDontSee('登録中の推し', false);
    }

    public function test_plan_result_shows_timetable_grid_with_feasibility(): void
    {
        $first = Performance::query()->where('artist_name', 'i☆Ris')->firstOrFail();
        $second = Performance::query()->where('artist_name', 'fishbowl')->firstOrFail();

        $this->post(route('plans.add', $first));
        $this->post(route('plans.add', $second));

        $response = $this->get(route('plans.show', ['day' => '2026-08-01']));

        $response->assertOk();
        $response->assertSee('tif-timetable', false);
        $response->assertSee('時間が重なる推しがあります', false);
        $response->assertSee('分かぶり', false);
        $response->assertSee('詳細はタイムテーブルのブロックをタップ', false);
        $response->assertSee('data-feasibility="impossible"', false);
        $response->assertDontSee('登録中の推し', false);
    }

    public function test_plan_result_shows_impossible_route_for_overlapping_artists(): void
    {
        $first = Performance::query()->where('artist_name', 'i☆Ris')->firstOrFail();
        $second = Performance::query()->where('artist_name', 'fishbowl')->firstOrFail();

        $this->post(route('plans.add', $first));
        $this->post(route('plans.add', $second));

        $response = $this->get(route('plans.show', ['day' => '2026-08-01']));

        $response->assertOk();
        $response->assertSee('間に合わない');
    }

    public function test_same_stage_neme_love_shows_ok(): void
    {
        $neme = Performance::query()->where('artist_name', '≠ME')->firstOrFail();
        $love = Performance::query()->where('artist_name', '=LOVE')->firstOrFail();

        $this->post(route('plans.add', $neme));
        $this->post(route('plans.add', $love));

        $response = $this->get(route('plans.show', ['day' => '2026-08-01']));

        $response->assertOk();
        $response->assertSee('tif-timetable', false);
        $response->assertSee('data-feasibility="ok"', false);
        $response->assertSee('余裕あり', false);
        $response->assertSee('すべて間に合う', false);
    }
}
