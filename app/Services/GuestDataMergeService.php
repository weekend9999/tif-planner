<?php

namespace App\Services;

use App\Enums\BufferMode;
use App\Models\FavoriteArtist;
use App\Models\User;
use App\Models\WatchPlan;

class GuestDataMergeService
{
    public function __construct(
        private readonly GuestWatchPlanService $guestWatchPlanService,
    ) {}

    public function mergeIntoUser(User $user): void
    {
        $this->mergeWatchPlan($user);
        $this->mergeFavoriteArtists($user);
    }

    private function mergeWatchPlan(User $user): void
    {
        $guest = session(GuestWatchPlanService::sessionKey(), null);
        if ($guest === null) {
            return;
        }

        $plan = WatchPlan::query()->firstOrCreate(
            ['user_id' => $user->id, 'name' => 'マイプラン'],
            ['buffer_mode' => BufferMode::Normal, 'custom_buffers' => ['exit' => 5, 'entry' => 5]],
        );

        foreach ($guest['performance_ids'] ?? [] as $performanceId) {
            $plan->items()->firstOrCreate(['performance_id' => $performanceId]);
        }

        if (! empty($guest['buffer_mode']) || ! empty($guest['custom_buffers'])) {
            $bufferMode = BufferMode::tryFrom($guest['buffer_mode'] ?? '') ?? $plan->buffer_mode;

            $plan->update([
                'buffer_mode' => $bufferMode,
                'custom_buffers' => array_merge(
                    $plan->custom_buffers ?? ['exit' => 5, 'entry' => 5],
                    $guest['custom_buffers'] ?? [],
                ),
            ]);
        }

        session()->forget(GuestWatchPlanService::sessionKey());
    }

    private function mergeFavoriteArtists(User $user): void
    {
        $artists = session('favorite_artists', []);
        if ($artists === []) {
            return;
        }

        foreach ($artists as $artistName) {
            FavoriteArtist::query()->firstOrCreate([
                'user_id' => $user->id,
                'artist_name' => $artistName,
            ]);
        }

        session()->forget('favorite_artists');
    }
}
