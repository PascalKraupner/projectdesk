<?php

use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TimeEntryController;
use App\Http\Controllers\Api\V1\TimerController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {
    Route::apiResource('clients', ClientController::class);
    Route::apiResource('projects', ProjectController::class);

    Route::get('timer', [TimerController::class, 'current'])->name('timer.current');
    Route::post('projects/{project}/timer/start', [TimerController::class, 'start'])->name('timer.start');

    Route::get('time-entries', [TimeEntryController::class, 'index'])->name('time-entries.index');
    Route::post('time-entries', [TimeEntryController::class, 'store'])->name('time-entries.store');
    Route::get('time-entries/{timeEntry}', [TimeEntryController::class, 'show'])->name('time-entries.show');
    Route::patch('time-entries/{timeEntry}', [TimeEntryController::class, 'update'])->name('time-entries.update');
    Route::delete('time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])->name('time-entries.destroy');

    Route::patch('time-entries/{timeEntry}/pause', [TimerController::class, 'pause'])->name('timer.pause');
    Route::patch('time-entries/{timeEntry}/resume', [TimerController::class, 'resume'])->name('timer.resume');
    Route::patch('time-entries/{timeEntry}/stop', [TimerController::class, 'stop'])->name('timer.stop');
});
