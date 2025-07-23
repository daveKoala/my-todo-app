<?php

use DaveKoala\RoutesExplorer\Http\Controllers\RoutesExplorerController;
use DaveKoala\RoutesExplorer\Http\Middleware\RoutesExplorerSecurity;
use Illuminate\Support\Facades\Route;

Route::prefix('dev')
    ->middleware(RoutesExplorerSecurity::class)
    ->group(function () {
        // Simple test route to verify package is loading
        Route::get('routes-explorer-test', function () {
            return response()->json(['status' => 'Routes Explorer package is loaded!']);
        })->name('routes-explorer.test');

        Route::get('routes-explorer', [RoutesExplorerController::class, 'index'])
            ->name('routes-explorer.index');

         Route::get('routes-explorer/explore', [RoutesExplorerController::class, 'getRoute'])
            ->name('routes-explorer.explore');
    });