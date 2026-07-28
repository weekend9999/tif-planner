<?php

namespace Tests\Unit;

use App\Models\Performance;
use App\Models\Stage;
use App\Services\TimetableGridService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableGridServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_grid_with_ordered_stages_and_blocks(): void
    {
        $stage = Stage::query()->create([
            'name' => 'HOT STAGE',
            'slug' => 'hot-stage',
            'area' => 'test',
            'map_x' => 0,
            'map_y' => 0,
        ]);

        Performance::query()->create([
            'day' => '2026-08-01',
            'stage_id' => $stage->id,
            'artist_name' => 'テストアイドル',
            'starts_at' => '10:00',
            'ends_at' => '10:30',
        ]);

        $grid = app(TimetableGridService::class)->buildForDay('2026-08-01');

        $this->assertSame('DAY2 (8/1)', $grid['day_label']);
        $this->assertSame('hot-stage', $grid['stages'][0]['slug']);
        $this->assertCount(1, $grid['blocks']);
        $this->assertSame('テストアイドル', $grid['blocks'][0]['artist_name']);
        $this->assertGreaterThan(0, $grid['blocks'][0]['height_px']);
    }

    public function test_adds_gap_between_back_to_back_blocks_on_same_stage(): void
    {
        $stage = Stage::query()->create([
            'name' => 'HOT STAGE',
            'slug' => 'hot-stage',
            'area' => 'test',
            'map_x' => 0,
            'map_y' => 0,
        ]);

        Performance::query()->create([
            'day' => '2026-08-01',
            'stage_id' => $stage->id,
            'artist_name' => '先攻',
            'starts_at' => '10:00',
            'ends_at' => '10:20',
        ]);

        Performance::query()->create([
            'day' => '2026-08-01',
            'stage_id' => $stage->id,
            'artist_name' => '後攻',
            'starts_at' => '10:20',
            'ends_at' => '10:40',
        ]);

        $grid = app(TimetableGridService::class)->buildForDay('2026-08-01');
        $blocks = collect($grid['blocks'])->sortBy('top_px')->values();
        $gapPx = (float) config('tif.grid.block_gap_px');

        $this->assertCount(2, $blocks);
        $firstBottom = $blocks[0]['top_px'] + $blocks[0]['height_px'];
        $this->assertEqualsWithDelta($firstBottom + $gapPx, $blocks[1]['top_px'], 0.01);
    }

    public function test_marks_favorite_artist_blocks(): void
    {
        $stage = Stage::query()->create([
            'name' => 'HOT STAGE',
            'slug' => 'hot-stage',
            'area' => 'test',
            'map_x' => 0,
            'map_y' => 0,
        ]);

        Performance::query()->create([
            'day' => '2026-08-01',
            'stage_id' => $stage->id,
            'artist_name' => '=LOVE',
            'starts_at' => '10:00',
            'ends_at' => '10:30',
        ]);

        $grid = app(TimetableGridService::class)->buildForDay('2026-08-01', null, ['=LOVE']);

        $this->assertTrue($grid['blocks'][0]['is_favorite_artist']);
    }
}
