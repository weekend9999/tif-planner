<?php

namespace Tests\Feature;

use App\Models\Performance;
use App\Models\User;
use App\Models\WatchPlan;
use Database\Seeders\TifSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestDataMergeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TifSeeder::class);
    }

    public function test_login_merges_guest_watch_plan_into_user(): void
    {
        $user = User::factory()->create([
            'email' => 'merge-test@example.com',
            'password' => bcrypt('password'),
        ]);

        $performance = Performance::query()->where('artist_name', '=LOVE')->firstOrFail();

        $this->post(route('plans.add', $performance));
        $this->post(route('favorites.store'), ['artist_name' => '=LOVE']);

        $this->post('/login', [
            'email' => 'merge-test@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        $plan = WatchPlan::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($plan);
        $this->assertTrue($plan->items()->where('performance_id', $performance->id)->exists());
        $this->assertDatabaseHas('favorite_artists', [
            'user_id' => $user->id,
            'artist_name' => '=LOVE',
        ]);
        $this->assertNull(session('guest_watch_plan'));
    }
}
