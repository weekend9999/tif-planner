<?php

namespace App\Http\Controllers;

use App\Services\FavoriteArtistService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(
        private readonly FavoriteArtistService $favoriteArtistService,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'artist_name' => ['required', 'string', 'max:100'],
        ]);

        $this->favoriteArtistService->add($request->user(), $validated['artist_name']);

        return redirect()->back()->with('status', "{$validated['artist_name']} を推しアイドルに登録しました");
    }

    public function destroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'artist_name' => ['required', 'string', 'max:100'],
        ]);

        $this->favoriteArtistService->remove($request->user(), $validated['artist_name']);

        return redirect()->back()->with('status', "{$validated['artist_name']} を推しアイドルから解除しました");
    }
}
