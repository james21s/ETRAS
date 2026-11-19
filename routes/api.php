<?php

use App\Http\Controllers\Api\V1\CommitteeController;
use App\Http\Controllers\Api\V1\PaperController;
use App\Http\Controllers\Api\V1\SectorController;
use Illuminate\Support\Facades\Route;

/*
| Open-access REST feed for external aggregators / academic databases.
| All routes require a Sanctum bearer token (Authorization: Bearer <token>)
| and should be called with "Accept: application/json".
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'throttle:catalog-api'])
    ->group(function () {
        Route::get('/papers', [PaperController::class, 'index'])->name('papers.index');
        Route::get('/papers/{paper}', [PaperController::class, 'show'])->name('papers.show');
        Route::get('/papers/{paper}/download', [PaperController::class, 'download'])->name('papers.download');

        Route::get('/sectors', [SectorController::class, 'index'])->name('sectors.index');
        Route::get('/committees', [CommitteeController::class, 'index'])->name('committees.index');
    });
