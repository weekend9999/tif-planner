<?php

namespace App\Services;

use App\Models\FavoriteArtist;
use App\Models\User;

class FavoriteArtistService
{
    private const SESSION_KEY = 'favorite_artists';

    /** @return list<string> */
    public function list(?User $user): array
    {
        if ($user) {
            return FavoriteArtist::query()
                ->where('user_id', $user->id)
                ->orderBy('artist_name')
                ->pluck('artist_name')
                ->all();
        }

        return session(self::SESSION_KEY, []);
    }

    /** @return list<string> */
    public function add(?User $user, string $artistName): array
    {
        $artistName = trim($artistName);
        if ($artistName === '') {
            return $this->list($user);
        }

        if ($user) {
            FavoriteArtist::query()->firstOrCreate([
                'user_id' => $user->id,
                'artist_name' => $artistName,
            ]);
        } else {
            $artists = session(self::SESSION_KEY, []);
            if (! in_array($artistName, $artists, true)) {
                $artists[] = $artistName;
            }
            session([self::SESSION_KEY => $artists]);
        }

        return $this->list($user);
    }

    /** @return list<string> */
    public function remove(?User $user, string $artistName): array
    {
        if ($user) {
            FavoriteArtist::query()
                ->where('user_id', $user->id)
                ->where('artist_name', $artistName)
                ->delete();
        } else {
            $artists = array_values(array_filter(
                session(self::SESSION_KEY, []),
                fn (string $name) => $name !== $artistName,
            ));
            session([self::SESSION_KEY => $artists]);
        }

        return $this->list($user);
    }
}
