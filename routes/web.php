<?php

use App\Http\Controllers\ApiTokenController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditorialNoteController;
use App\Http\Controllers\OversightController;
use App\Http\Controllers\PaperClassificationController;
use App\Http\Controllers\PaperController;
use App\Http\Controllers\PaperFileController;
use App\Http\Controllers\PaperReviewerController;
use App\Http\Controllers\PaperWorkflowController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\UserAdminController;
use Illuminate\Support\Facades\Route;

/* Public catalog of published papers */
Route::get('/', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/catalog/{paper:slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /* Papers: authoring, workflow, files */
    Route::get('/papers/{paper}/download', [PaperFileController::class, 'download'])->name('papers.download');
    Route::resource('papers', PaperController::class);
    Route::post('/papers/{paper}/submit', [PaperWorkflowController::class, 'submit'])->name('papers.submit');
    Route::post('/papers/{paper}/transition', [PaperWorkflowController::class, 'transition'])->name('papers.transition');
    Route::put('/papers/{paper}/sectors', [PaperClassificationController::class, 'update'])->name('papers.sectors.update');
    Route::post('/papers/{paper}/reviewers', [PaperReviewerController::class, 'store'])->name('papers.reviewers.store');
    Route::delete('/papers/{paper}/reviewers/{review}', [PaperReviewerController::class, 'destroy'])->name('papers.reviewers.destroy');
    Route::post('/papers/{paper}/notes', [EditorialNoteController::class, 'store'])->name('papers.notes.store');

    /* Reviewer workspace */
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');

    /* Role-gated areas */
    Route::get('/oversight', OversightController::class)->middleware('can:view-oversight')->name('oversight');
    Route::resource('sectors', SectorController::class)->only(['index', 'store', 'destroy'])->middleware('can:manage-sectors');
    Route::get('/admin/users', [UserAdminController::class, 'index'])->middleware('can:manage-users')->name('admin.users.index');
    Route::put('/admin/users/{user}', [UserAdminController::class, 'update'])->middleware('can:manage-users')->name('admin.users.update');

    Route::middleware('can:manage-api-tokens')->group(function () {
        Route::get('/api-tokens', [ApiTokenController::class, 'index'])->name('tokens.index');
        Route::post('/api-tokens', [ApiTokenController::class, 'store'])->name('tokens.store');
        Route::delete('/api-tokens/{token}', [ApiTokenController::class, 'destroy'])->name('tokens.destroy');
    });

    /* Profile (Breeze) */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
