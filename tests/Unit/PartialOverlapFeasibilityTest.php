<?php

namespace Tests\Unit;

use App\Enums\FeasibilityStatus;
use App\Models\Performance;
use App\Services\FeasibilityService;
use App\Enums\BufferMode;
use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartialOverlapFeasibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
    }

    public function test_cynhn_and_nijikon_partial_overlap_is_resolvable_with_early_exit(): void
    {
        $cynhn = Performance::query()->where('artist_name', 'CYNHN')->firstOrFail();
        $niji = Performance::query()->where('artist_name', '虹のコンキスタドール')->firstOrFail();

        $service = app(FeasibilityService::class);
        $annotations = $service->buildBlockAnnotations(
            collect([$cynhn, $niji]),
            BufferMode::Normal,
            5,
            5,
        );

        $this->assertNotSame(
            FeasibilityStatus::Impossible->value,
            $annotations[$cynhn->id]['status'],
        );
        $lines = implode("\n", $annotations[$cynhn->id]['lines']);
        $this->assertStringContainsString('【公演時間かぶり】', $lines);
        $this->assertStringContainsString('5 分かぶり', $lines);
        $this->assertStringContainsString('【見方の選択肢】', $lines);
        $this->assertStringContainsString('① 最後まで観て移動', $lines);
        $this->assertStringContainsString('② 途中退場して移動', $lines);
        $this->assertStringContainsString('虹のコンキスタドール', $lines);

        $nijiLines = implode("\n", $annotations[$niji->id]['lines']);
        $this->assertStringContainsString('【見方の選択肢】', $nijiLines);
        $this->assertStringContainsString('① CYNHN を最後まで観る', $nijiLines);
        $this->assertStringContainsString('② CYNHN 途中退場', $nijiLines);

        $analysis = $service->analyze(collect([$cynhn, $niji]), BufferMode::Normal, 5, 5);
        $this->assertSame('partial_resolvable', $analysis['overlapDetails'][0]['type']);
    }

    public function test_ukishima_rough_luck_to_doll_white_scorpion_has_comfortable_slack(): void
    {
        $rough = Performance::query()
            ->where('artist_name', 'ラフ×ラフ')
            ->whereHas('stage', fn ($q) => $q->where('slug', 'ukishima-stage'))
            ->firstOrFail();

        $whiteScorpion = Performance::query()
            ->where('artist_name', 'WHITE SCORPION')
            ->whereHas('stage', fn ($q) => $q->where('slug', 'doll-factory'))
            ->firstOrFail();

        $leg = app(FeasibilityService::class)->analyzeLeg(
            $rough,
            $whiteScorpion,
            BufferMode::Normal,
            5,
            5,
        );

        $this->assertSame(FeasibilityStatus::Ok, $leg->status);
        $this->assertTrue($leg->recommendedDeparture->gt($rough->ends_at));
    }

    public function test_cynhn_shows_incoming_leg_from_doll_batten_when_overlapping_nijikon(): void
    {
        $batten = Performance::query()
            ->where('artist_name', 'ばってん少女隊')
            ->whereHas('stage', fn ($q) => $q->where('slug', 'doll-factory'))
            ->firstOrFail();
        $cynhn = Performance::query()->where('artist_name', 'CYNHN')->firstOrFail();
        $niji = Performance::query()->where('artist_name', '虹のコンキスタドール')->firstOrFail();

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$batten, $cynhn, $niji]),
            BufferMode::Normal,
            5,
            5,
        );

        $lines = implode("\n", $annotations[$cynhn->id]['lines']);

        $this->assertStringContainsString('【移動】', $lines);
        $this->assertStringContainsString('ばってん少女隊', $lines);
        $this->assertStringContainsString('DOLL FACTORY', $lines);
        $this->assertStringContainsString('前の公演終了', $lines);
        $this->assertStringContainsString('【公演時間かぶり】', $lines);
        $this->assertStringContainsString('【見方の選択肢】', $lines);
        $this->assertStringContainsString('虹のコンキスタドール', $lines);
    }

    public function test_appare_to_tif_asia_live_stage_is_tight_not_ok(): void
    {
        $appare = Performance::query()
            ->where('artist_name', 'Appare!')
            ->whereHas('stage', fn ($q) => $q->where('slug', 'hot-stage'))
            ->firstOrFail();
        $tifAsia = Performance::query()
            ->where('artist_name', 'like', 'TIF ASIA TOUR%')
            ->whereHas('stage', fn ($q) => $q->where('slug', 'heat-garage'))
            ->firstOrFail();

        $leg = app(FeasibilityService::class)->analyzeLeg(
            $appare,
            $tifAsia,
            BufferMode::Normal,
            5,
            5,
        );

        $this->assertSame(FeasibilityStatus::Tight, $leg->status);
        $this->assertSame('13:52', $leg->recommendedDeparture->format('H:i'));

        $annotations = app(FeasibilityService::class)->buildBlockAnnotations(
            collect([$appare, $tifAsia]),
            BufferMode::Normal,
            5,
            5,
        );

        $lines = implode("\n", $annotations[$tifAsia->id]['lines']);
        $this->assertStringContainsString('【移動】', $lines);
        $this->assertStringContainsString('途中退場', $lines);
        $this->assertSame(FeasibilityStatus::Tight->value, $annotations[$tifAsia->id]['status']);
    }
}
