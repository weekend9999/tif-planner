<?php

namespace Tests\Unit;

use App\Enums\BufferMode;
use App\Enums\FeasibilityStatus;
use App\Models\Performance;
use App\Models\Stage;
use App\Models\StageTravelTime;
use App\Services\FeasibilityService;
use App\Services\TravelTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SameStageFeasibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_back_to_back_hot_stage_neme_to_love_is_feasible(): void
    {
        $hot = Stage::query()->create([
            'name' => 'HOT STAGE', 'slug' => 'hot-stage', 'area' => 'divercity', 'map_x' => 0, 'map_y' => 0,
        ]);

        $neme = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $hot->id, 'artist_name' => '≠ME',
            'starts_at' => '19:45', 'ends_at' => '20:15',
        ]);
        $love = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $hot->id, 'artist_name' => '=LOVE',
            'starts_at' => '20:25', 'ends_at' => '20:55',
        ]);

        $service = new FeasibilityService(new TravelTimeService());
        $leg = $service->analyzeLeg($neme, $love, BufferMode::Normal, 5, 5);

        $this->assertSame(FeasibilityStatus::Ok, $leg->status);
        $this->assertSame(0, $leg->travelMinutes);
        $this->assertStringContainsString('同一ステージ', $leg->message);
    }

    public function test_three_consecutive_hot_stage_acts_are_all_feasible(): void
    {
        $hot = Stage::query()->create([
            'name' => 'HOT STAGE', 'slug' => 'hot-stage', 'area' => 'divercity', 'map_x' => 0, 'map_y' => 0,
        ]);

        $joy = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $hot->id, 'artist_name' => '≒JOY',
            'starts_at' => '19:05', 'ends_at' => '19:35',
        ]);
        $neme = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $hot->id, 'artist_name' => '≠ME',
            'starts_at' => '19:45', 'ends_at' => '20:15',
        ]);
        $love = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $hot->id, 'artist_name' => '=LOVE',
            'starts_at' => '20:25', 'ends_at' => '20:55',
        ]);

        $service = new FeasibilityService(new TravelTimeService());
        $analysis = $service->analyze(collect([$joy, $neme, $love]), BufferMode::Normal, 5, 5);

        $this->assertTrue($analysis['allFeasible']);
        $this->assertCount(2, $analysis['legs']);
        foreach ($analysis['legs'] as $leg) {
            $this->assertNotSame(FeasibilityStatus::Impossible, $leg->status);
        }
    }
}
