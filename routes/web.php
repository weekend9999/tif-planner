<?php

use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TimetableController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');
Route::get('/live', [LiveController::class, 'index'])->name('live.index');
Route::get('/map', [MapController::class, 'index'])->name('map.index');

Route::post('/favorites', [FavoriteController::class, 'store'])->name('favorites.store');
Route::delete('/favorites', [FavoriteController::class, 'destroy'])->name('favorites.destroy');

Route::get('/plans', [PlanController::class, 'show'])->name('plans.show');
Route::get('/plans/edit', [PlanController::class, 'edit'])->name('plans.edit');
Route::patch('/plans/settings', [PlanController::class, 'updateSettings'])->name('plans.settings');
Route::post('/plans/performances/{performance}', [PlanController::class, 'addPerformance'])->name('plans.add');
Route::delete('/plans/performances/{performance}', [PlanController::class, 'removePerformance'])->name('plans.remove');

Route::get('/dashboard', function () {
    return redirect()->route('plans.show');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
