<?php

namespace Tests\Feature;

use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetablePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
    }

    public function test_timetable_page_shows_full_width_grid(): void
    {
        $response = $this->get(route('timetable.index', ['day' => '2026-08-01']));

        $response->assertOk();
        $response->assertSee('tif-timetable', false);
        $response->assertSee('HOT', false);
        $response->assertSee('SMILE', false);
        $response->assertSee('≠ME', false);
        $response->assertDontSee('min-w-[920px]', false);
    }

    public function test_stage_headers_follow_official_order(): void
    {
        $response = $this->get(route('timetable.index', ['day' => '2026-08-01']));

        $content = $response->getContent();
        $hot = strpos($content, 'HOT');
        $heat = strpos($content, 'HEAT');
        $smile = strpos($content, 'SMILE');
        $doll = strpos($content, 'DOLL');
        $sky = strpos($content, 'SKY');
        $torocco = strpos($content, 'TOROCCO');
        $ukishima = strpos($content, '浮島');
        $info = strpos($content, 'INFO');

        $this->assertNotFalse($hot);
        $this->assertTrue($hot < $heat && $heat < $smile && $smile < $doll);
        $this->assertTrue($doll < $sky && $sky < $torocco && $torocco < $ukishima);
        $this->assertTrue($ukishima < $info);
    }
}
