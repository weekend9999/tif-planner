<?php

namespace Tests\Unit;

use App\Enums\BufferMode;
use App\Enums\FeasibilityStatus;
use App\Models\Performance;
use App\Models\Stage;
use App\Models\StageTravelTime;
use App\Models\TravelRule;
use App\Services\FeasibilityService;
use App\Services\TravelTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeasibilityServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeasibilityService $service;

    private Stage $hotStage;

    private Stage $smileStage;

    private Stage $skyStage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FeasibilityService(new TravelTimeService());

        $this->hotStage = Stage::query()->create([
            'name' => 'HOT STAGE',
            'slug' => 'hot-stage',
            'area' => 'divercity',
            'map_x' => 20,
            'map_y' => 50,
        ]);

        $this->smileStage = Stage::query()->create([
            'name' => 'SMILE GARDEN',
            'slug' => 'smile-garden',
            'area' => 'fuji-bay',
            'map_x' => 70,
            'map_y' => 40,
        ]);

        $this->skyStage = Stage::query()->create([
            'name' => 'SKY STAGE',
            'slug' => 'sky-stage',
            'area' => 'fuji-bay',
            'map_x' => 72,
            'map_y' => 30,
        ]);

        StageTravelTime::query()->create([
            'from_stage_id' => $this->hotStage->id,
            'to_stage_id' => $this->smileStage->id,
            'walk_minutes' => 10,
        ]);

        StageTravelTime::query()->create([
            'from_stage_id' => $this->smileStage->id,
            'to_stage_id' => $this->skyStage->id,
            'walk_minutes' => 3,
        ]);

        TravelRule::query()->create([
            'stage_id' => $this->skyStage->id,
            'rule_type' => 'elevator_wait',
            'extra_minutes' => 10,
        ]);
    }

    public function test_same_stage_has_zero_travel_and_is_feasible(): void
    {
        $first = $this->makePerformance($this->hotStage, '10:00', '10:20');
        $second = $this->makePerformance($this->hotStage, '11:00', '11:20');

        $leg = $this->service->analyzeLeg($first, $second, BufferMode::Normal, 5, 5);

        $this->assertSame(FeasibilityStatus::Ok, $leg->status);
        $this->assertSame(0, $leg->travelMinutes);
    }

    public function test_hot_to_smile_1450_to_1505_requires_early_exit(): void
    {
        $first = $this->makePerformance($this->hotStage, '14:30', '14:50');
        $second = $this->makePerformance($this->smileStage, '15:05', '15:25');

        $leg = $this->service->analyzeLeg($first, $second, BufferMode::Normal, 5, 5);

        $this->assertContains($leg->status, [FeasibilityStatus::Ok, FeasibilityStatus::Tight]);
        $this->assertSame('14:45', $leg->recommendedDeparture->format('H:i'));
    }

    public function test_hot_to_smile_1450_to_1520_can_be_tight_or_ok(): void
    {
        $first = $this->makePerformance($this->hotStage, '14:30', '14:50');
        $second = $this->makePerformance($this->smileStage, '15:20', '15:40');

        $leg = $this->service->analyzeLeg($first, $second, BufferMode::Normal, 5, 5);

        $this->assertContains($leg->status, [FeasibilityStatus::Ok, FeasibilityStatus::Tight]);
        $this->assertSame('15:00', $leg->recommendedDeparture->format('H:i'));
    }

    public function test_smile_to_sky_requires_elevator_buffer(): void
    {
        $first = $this->makePerformance($this->smileStage, '10:00', '10:20');
        $second = $this->makePerformance($this->skyStage, '11:30', '11:45');

        $leg = $this->service->analyzeLeg($first, $second, BufferMode::Normal, 5, 5);

        $this->assertSame(18, $leg->travelMinutes);
    }

    public function test_movement_annotation_includes_congestion_advisory_for_sky_stage(): void
    {
        $this->seed(\Database\Seeders\TifSeeder::class);

        $smile = Performance::query()
            ->whereHas('stage', fn ($q) => $q->where('slug', 'smile-garden'))
            ->firstOrFail();
        $sky = Performance::query()
            ->whereHas('stage', fn ($q) => $q->where('slug', 'sky-stage'))
            ->firstOrFail();

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$smile, $sky]),
            BufferMode::Normal,
            5,
            5,
        );

        $lines = implode("\n", $annotations[$sky->id]['lines']);
        $this->assertStringContainsString('エレベーター待ち', $lines);
        $this->assertStringContainsString('＋15分程度', $lines);
        $this->assertStringNotContainsString('手荷物検査', $lines);
    }

    public function test_torocco_to_ukishima_has_no_baggage_check(): void
    {
        $this->seed(\Database\Seeders\TifSeeder::class);

        $torocco = Performance::query()
            ->whereHas('stage', fn ($q) => $q->where('slug', 'torocco-park'))
            ->firstOrFail();
        $ukishima = Performance::query()
            ->whereHas('stage', fn ($q) => $q->where('slug', 'ukishima-stage'))
            ->firstOrFail();

        $leg = app(FeasibilityService::class)->analyzeLeg($torocco, $ukishima, BufferMode::Normal, 5, 5);

        $this->assertSame(8, $leg->travelMinutes);

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$torocco, $ukishima]),
            BufferMode::Normal,
            5,
            5,
        );

        $lines = implode("\n", $annotations[$ukishima->id]['lines']);
        $this->assertStringNotContainsString('手荷物検査', $lines);
    }

    public function test_hot_to_ukishima_shows_baggage_check_only_message(): void
    {
        $this->seed(\Database\Seeders\TifSeeder::class);

        $hot = Performance::query()
            ->whereHas('stage', fn ($q) => $q->where('slug', 'hot-stage'))
            ->firstOrFail();
        $ukishima = Performance::query()
            ->whereHas('stage', fn ($q) => $q->where('slug', 'ukishima-stage'))
            ->firstOrFail();

        $leg = app(FeasibilityService::class)->analyzeLeg($hot, $ukishima, BufferMode::Normal, 5, 5);

        $this->assertSame(22, $leg->travelMinutes);

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$hot, $ukishima]),
            BufferMode::Normal,
            5,
            5,
        );

        $lines = implode("\n", $annotations[$ukishima->id]['lines']);
        $this->assertStringContainsString('手荷物検査あり', $lines);
        $this->assertStringNotContainsString('＋15分程度', $lines);
    }

    public function test_overlapping_performances_are_detected(): void
    {
        $first = $this->makePerformance($this->hotStage, '15:00', '15:20');
        $second = $this->makePerformance($this->smileStage, '15:10', '15:30');

        $overlaps = $this->service->detectOverlaps(collect([$first, $second]));

        $this->assertCount(1, $overlaps);
    }

    public function test_conservative_mode_requires_earlier_departure_than_normal(): void
    {
        $first = $this->makePerformance($this->hotStage, '14:30', '14:50');
        $second = $this->makePerformance($this->smileStage, '15:20', '15:40');

        $normal = $this->service->analyzeLeg($first, $second, BufferMode::Normal, 5, 5);
        $conservative = $this->service->analyzeLeg($first, $second, BufferMode::Conservative, 7, 7);

        $this->assertTrue($conservative->recommendedDeparture->lt($normal->recommendedDeparture));
        $this->assertGreaterThan($normal->travelMinutes, $conservative->travelMinutes);
    }

    private function makePerformance(Stage $stage, string $startsAt, string $endsAt): Performance
    {
        return Performance::query()->create([
            'day' => '2026-08-01',
            'stage_id' => $stage->id,
            'artist_name' => 'Test Artist',
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);
    }
}
