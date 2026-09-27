<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\GroupController;
use App\Http\Middleware\EnsureAdminToken;
use Illuminate\Support\Facades\Route;

Route::get('/', [GroupController::class, 'create'])->name('home');
Route::post('/groups', [GroupController::class, 'store'])->middleware('throttle:10,1')->name('groups.store');

Route::prefix('/g/{group}/admin/{token}')
    ->middleware(EnsureAdminToken::class) // also checks that child models belong to the group
    ->name('admin.')
    ->controller(AdminController::class)
    ->group(function () {
        Route::get('/', 'show')->name('show');
        Route::patch('/', 'update')->name('update');
        Route::post('/participants', 'addParticipant')->name('participants.store');
        Route::delete('/participants/{participant}', 'removeParticipant')->name('participants.destroy');
        Route::delete('/participants/{participant}/claim', 'resetClaim')->name('claims.destroy');
        Route::post('/exclusions', 'addExclusion')->name('exclusions.store');
        Route::delete('/exclusions/{exclusion}', 'removeExclusion')->name('exclusions.destroy');
        Route::post('/draw', 'draw')->name('draw');
        Route::delete('/draw', 'undoDraw')->name('draw.undo');
    });

Route::prefix('/g/{group}')
    ->scopeBindings()
    ->controller(ClaimController::class)
    ->group(function () {
        Route::get('/', 'index')->name('group.show');
        Route::post('/claim/{participant}', 'claim')->middleware('throttle:20,1')->name('group.claim');
        Route::get('/me', 'reveal')->name('group.me');
        Route::patch('/me/wishlist', 'updateWishlist')->name('group.wishlist');
    });
