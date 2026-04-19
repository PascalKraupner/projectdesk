<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectShareController;
use App\Http\Controllers\TimeLogController;
use Illuminate\Support\Facades\Route;

Route::get('/share/projects/{project}', [ProjectShareController::class, 'show'])
    ->name('projects.share');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class)->except(['show']);
    Route::resource('projects', ProjectController::class);
    Route::post('/projects/{project}/share', [ProjectShareController::class, 'store'])->name('projects.share.store');
    Route::delete('/projects/{project}/share', [ProjectShareController::class, 'destroy'])->name('projects.share.destroy');

    Route::get('/projects/{project}/time-logs/export.csv', [TimeLogController::class, 'export'])->name('time-logs.export');
    Route::post('/projects/{project}/time-logs', [TimeLogController::class, 'store'])->name('time-logs.store');
    Route::post('/projects/{project}/time-logs/manual', [TimeLogController::class, 'storeManual'])->name('time-logs.store-manual');
    Route::patch('/time-logs/{timeLog}', [TimeLogController::class, 'update'])->name('time-logs.update');
    Route::patch('/time-logs/{timeLog}/manual', [TimeLogController::class, 'updateManual'])->name('time-logs.update-manual');
    Route::patch('/time-logs/{timeLog}/note', [TimeLogController::class, 'updateNote'])->name('time-logs.update-note');
    Route::delete('/time-logs/{timeLog}', [TimeLogController::class, 'destroy'])->name('time-logs.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
