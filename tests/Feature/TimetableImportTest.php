<?php

namespace Tests\Feature;

use App\Models\Stage;
use App\Services\TimetableImportService;
use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class TimetableImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
    }

    public function test_csv_import_adds_performances(): void
    {
        $csv = storage_path('framework/testing/import.csv');
        File::ensureDirectoryExists(dirname($csv));
        File::put($csv, "day,stage_slug,artist_name,starts_at,ends_at\n2026-08-02,hot-stage,テストグループ,12:00,12:30\n");

        $result = app(TimetableImportService::class)->importFromCsv($csv);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('performances', [
            'artist_name' => 'テストグループ',
        ]);
        $this->assertTrue(
            \App\Models\Performance::query()->where('artist_name', 'テストグループ')->whereDate('day', '2026-08-02')->exists()
        );
    }

    public function test_seeder_loads_day2_hot_stage_artists(): void
    {
        $this->assertDatabaseHas('performances', [
            'artist_name' => '=LOVE',
            'starts_at' => '20:25:00',
        ]);
        $this->assertDatabaseHas('performances', [
            'artist_name' => '≠ME',
            'starts_at' => '19:45:00',
        ]);
    }
}
