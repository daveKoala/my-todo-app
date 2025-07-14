<?php

use Illuminate\Support\Facades\Route;
use DaveKoala\RoutesExplorer\Http\Controllers\RoutesExplorerController;

Route::prefix('dev/routes-explorer')->group(function () {
    Route::get('/', [RoutesExplorerController::class, 'index'])->name('routes-explorer.index');
    Route::get('/explore/{route}', [RoutesExplorerController::class, 'explore'])->name('routes-explorer.explore');
    Route::post('/api/explore', [RoutesExplorerController::class, 'apiExplore'])->name('routes-explorer.api.explore');
});