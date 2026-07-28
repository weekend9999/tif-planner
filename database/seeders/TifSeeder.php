<?php

namespace Database\Seeders;

use App\Models\Performance;
use App\Models\Stage;
use App\Models\StageTravelTime;
use App\Models\TravelRule;
use App\Services\TimetableImportService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class TifSeeder extends Seeder
{
    public function run(): void
    {
        $stages = collect([
            ['name' => 'HOT STAGE', 'slug' => 'hot-stage', 'area' => 'divercity', 'map_x' => 20, 'map_y' => 50],
            ['name' => 'HEAT GARAGE', 'slug' => 'heat-garage', 'area' => 'divercity', 'map_x' => 35, 'map_y' => 45],
            ['name' => 'SMILE GARDEN', 'slug' => 'smile-garden', 'area' => 'fuji-bay', 'map_x' => 70, 'map_y' => 40],
            ['name' => 'DOLL FACTORY', 'slug' => 'doll-factory', 'area' => 'fuji-bay', 'map_x' => 75, 'map_y' => 55],
            ['name' => 'SKY STAGE', 'slug' => 'sky-stage', 'area' => 'fuji-bay', 'map_x' => 72, 'map_y' => 30, 'meta' => ['entry_via' => 'smile-garden']],
            ['name' => 'INFO CENTRE', 'slug' => 'info-centre', 'area' => 'fuji-bay', 'map_x' => 68, 'map_y' => 60, 'meta' => ['entry_via' => 'smile-garden']],
            ['name' => 'TOROCCO PARK', 'slug' => 'torocco-park', 'area' => 'aomi', 'map_x' => 25, 'map_y' => 75],
            ['name' => '浮島 STAGE', 'slug' => 'ukishima-stage', 'area' => 'aomi', 'map_x' => 40, 'map_y' => 80],
        ])->mapWithKeys(function (array $stage) {
            $model = Stage::query()->updateOrCreate(
                ['slug' => $stage['slug']],
                $stage,
            );

            return [$stage['slug'] => $model];
        });

        $this->seedTravelTimes($stages);
        $this->seedTravelRules($stages);
        $this->seedPerformances($stages);
    }

    /** @param  \Illuminate\Support\Collection<string, Stage>  $stages */
    private function seedTravelTimes($stages): void
    {
        $matrix = [
            'smile-garden' => [
                'hot-stage' => 10,
                'heat-garage' => 13,
                'doll-factory' => 3,
                'sky-stage' => 3,
                'info-centre' => 3,
                'torocco-park' => 8,
                'ukishima-stage' => 8,
            ],
            'hot-stage' => [
                'smile-garden' => 10,
                'heat-garage' => 3,
                'doll-factory' => 12,
                'sky-stage' => 12,
                'info-centre' => 12,
                'torocco-park' => 7,
                'ukishima-stage' => 7,
            ],
            'heat-garage' => [
                'hot-stage' => 3,
                'smile-garden' => 13,
                'doll-factory' => 14,
                'sky-stage' => 14,
                'info-centre' => 14,
                'torocco-park' => 10,
                'ukishima-stage' => 10,
            ],
            'doll-factory' => [
                'smile-garden' => 2,
                'hot-stage' => 12,
                'heat-garage' => 14,
                'sky-stage' => 3,
                'info-centre' => 2,
                'torocco-park' => 9,
                'ukishima-stage' => 9,
            ],
            'sky-stage' => [
                'smile-garden' => 3,
                'hot-stage' => 12,
                'heat-garage' => 14,
                'doll-factory' => 3,
                'info-centre' => 3,
                'torocco-park' => 9,
                'ukishima-stage' => 9,
            ],
            'info-centre' => [
                'smile-garden' => 3,
                'hot-stage' => 10,
                'heat-garage' => 13,
                'doll-factory' => 2,
                'sky-stage' => 3,
                'torocco-park' => 8,
                'ukishima-stage' => 8,
            ],
            'torocco-park' => [
                'hot-stage' => 7,
                'smile-garden' => 8,
                'heat-garage' => 10,
                'doll-factory' => 9,
                'sky-stage' => 9,
                'info-centre' => 8,
                'ukishima-stage' => 3,
            ],
            'ukishima-stage' => [
                'hot-stage' => 7,
                'smile-garden' => 8,
                'heat-garage' => 10,
                'doll-factory' => 9,
                'sky-stage' => 9,
                'info-centre' => 8,
                'torocco-park' => 3,
            ],
        ];

        foreach ($matrix as $fromSlug => $destinations) {
            foreach ($destinations as $toSlug => $minutes) {
                StageTravelTime::query()->updateOrCreate(
                    [
                        'from_stage_id' => $stages[$fromSlug]->id,
                        'to_stage_id' => $stages[$toSlug]->id,
                    ],
                    ['walk_minutes' => $minutes],
                );
            }
        }
    }

    /** @param  \Illuminate\Support\Collection<string, Stage>  $stages */
    private function seedTravelRules($stages): void
    {
        TravelRule::query()->updateOrCreate(
            ['stage_id' => $stages['sky-stage']->id, 'rule_type' => 'elevator_wait'],
            ['extra_minutes' => 10, 'description' => 'SKY STAGE エレベーター待ち'],
        );

        foreach (['torocco-park', 'ukishima-stage'] as $slug) {
            TravelRule::query()->updateOrCreate(
                ['stage_id' => $stages[$slug]->id, 'rule_type' => 'baggage_check'],
                ['extra_minutes' => 10, 'description' => '手荷物検査'],
            );
        }
    }

    /** @param  \Illuminate\Support\Collection<string, Stage>  $stages */
    private function seedPerformances($stages): void
    {
        Performance::query()->delete();

        $importService = app(TimetableImportService::class);
        $dataPath = database_path('data');

        foreach (File::glob("{$dataPath}/*.csv") as $csvFile) {
            $importService->importFromCsv($csvFile);
        }

        // DAY1 / DAY3 サンプル（CSV未整備分）
        $extras = [
            ['day' => '2026-07-31', 'stage' => 'smile-garden', 'artist' => '佐々木彩夏', 'start' => '10:00', 'end' => '10:20'],
            ['day' => '2026-07-31', 'stage' => 'hot-stage', 'artist' => 'OCHA NORMA', 'start' => '10:00', 'end' => '10:20'],
            ['day' => '2026-07-31', 'stage' => 'hot-stage', 'artist' => 'AKB48', 'start' => '19:40', 'end' => '20:00'],
            ['day' => '2026-08-02', 'stage' => 'sky-stage', 'artist' => 'SUPER☆GiRLS', 'start' => '12:30', 'end' => '12:45'],
            ['day' => '2026-08-02', 'stage' => 'smile-garden', 'artist' => '乃木坂46', 'start' => '20:00', 'end' => '20:30'],
        ];

        foreach ($extras as $row) {
            Performance::query()->create([
                'day' => $row['day'],
                'stage_id' => $stages[$row['stage']]->id,
                'artist_name' => $row['artist'],
                'starts_at' => $row['start'],
                'ends_at' => $row['end'],
            ]);
        }
    }
}
