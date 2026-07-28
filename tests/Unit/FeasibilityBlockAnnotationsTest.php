<?php

namespace Tests\Unit;

use App\Enums\FeasibilityStatus;
use App\Models\Performance;
use App\Models\Stage;
use App\Services\FeasibilityService;
use App\Enums\BufferMode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeasibilityBlockAnnotationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_performance_on_day_is_marked_ok(): void
    {
        $stage = Stage::query()->create([
            'name' => 'HOT STAGE', 'slug' => 'hot-stage', 'area' => 'a', 'map_x' => 0, 'map_y' => 0,
        ]);

        $performance = Performance::query()->create([
            'day' => '2026-08-01',
            'stage_id' => $stage->id,
            'artist_name' => 'テスト',
            'starts_at' => '10:00',
            'ends_at' => '10:30',
        ]);

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$performance]),
            BufferMode::Normal,
            5,
            5,
        );

        $this->assertSame(FeasibilityStatus::Ok->value, $annotations[$performance->id]['status']);
    }

    public function test_overlapping_performances_are_marked_impossible(): void
    {
        $hot = Stage::query()->create([
            'name' => 'HOT STAGE', 'slug' => 'hot-stage', 'area' => 'a', 'map_x' => 0, 'map_y' => 0,
        ]);
        $smile = Stage::query()->create([
            'name' => 'SMILE GARDEN', 'slug' => 'smile-garden', 'area' => 'a', 'map_x' => 0, 'map_y' => 0,
        ]);

        $a = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $hot->id, 'artist_name' => 'A',
            'starts_at' => '14:00', 'ends_at' => '14:30',
        ]);
        $b = Performance::query()->create([
            'day' => '2026-08-01', 'stage_id' => $smile->id, 'artist_name' => 'B',
            'starts_at' => '14:15', 'ends_at' => '14:45',
        ]);

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$a, $b]),
            BufferMode::Normal,
            5,
            5,
        );

        $this->assertSame(FeasibilityStatus::Impossible->value, $annotations[$a->id]['status']);
        $this->assertSame(FeasibilityStatus::Impossible->value, $annotations[$b->id]['status']);
    }
}
