<?php

use App\Http\Controllers\Api\NoteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Notes Management System
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:60,1'])->group(function () {
    // Semantic Search (Placed above resource routes to prevent matching '{id}' = 'search')
    Route::get('/notes/search', [NoteController::class, 'search'])->name('notes.search.get');
    Route::post('/notes/search', [NoteController::class, 'search'])->name('notes.search.post');

    // AI Summary Endpoint
    Route::post('/notes/{id}/summary', [NoteController::class, 'summary'])->name('notes.summary');

    // Notes CRUD APIs
    Route::get('/notes', [NoteController::class, 'index'])->name('notes.index');
    Route::post('/notes', [NoteController::class, 'store'])->name('notes.store');
    Route::get('/notes/{id}', [NoteController::class, 'show'])->name('notes.show');
    Route::put('/notes/{id}', [NoteController::class, 'update'])->name('notes.update');
    Route::patch('/notes/{id}', [NoteController::class, 'update'])->name('notes.patch');
    Route::delete('/notes/{id}', [NoteController::class, 'destroy'])->name('notes.destroy');
});
