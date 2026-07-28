<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteArtistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
    }

    public function test_guest_can_register_favorite_artist(): void
    {
        $response = $this->post(route('favorites.store'), [
            'artist_name' => '=LOVE',
        ]);

        $response->assertRedirect();
        $this->assertSame(['=LOVE'], session('favorite_artists'));
    }

    public function test_edit_page_shows_favorite_artist_pink_blocks(): void
    {
        $this->post(route('favorites.store'), ['artist_name' => '=LOVE']);

        $response = $this->get(route('plans.edit', ['day' => '2026-08-01']));

        $response->assertOk();
        $response->assertSee('推しアイドル登録', false);
        $response->assertSee('=LOVE', false);
        $response->assertSee('bg-pink-100', false);
    }

    public function test_guest_can_remove_favorite_artist(): void
    {
        $this->post(route('favorites.store'), ['artist_name' => '=LOVE']);

        $response = $this->delete(route('favorites.destroy'), [
            'artist_name' => '=LOVE',
        ]);

        $response->assertRedirect();
        $this->assertSame([], session('favorite_artists'));
    }

    public function test_authenticated_user_persists_favorite_artist(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('favorites.store'), [
            'artist_name' => 'わーすた',
        ]);

        $this->assertDatabaseHas('favorite_artists', [
            'user_id' => $user->id,
            'artist_name' => 'わーすた',
        ]);
    }
}
