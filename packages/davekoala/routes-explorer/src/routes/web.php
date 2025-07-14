<?php

use Illuminate\Support\Facades\Route;
use DaveKoala\RoutesExplorer\Http\Controllers\RoutesExplorerController;

Route::prefix('dev')->group(function () {
    Route::get('routes-explorer', [RoutesExplorerController::class, 'index'])
        ->name('routes-explorer.index');
});