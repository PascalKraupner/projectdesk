<?php

use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientShareController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceItemController;
use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TimeLogController;
use App\Http\Controllers\TimesheetController;
use Illuminate\Support\Facades\Route;

Route::get('/share/clients/{client}', [ClientShareController::class, 'show'])
    ->name('clients.share');

Route::middleware('auth')->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('clients', ClientController::class);
    Route::post('/clients/{client}/share', [ClientShareController::class, 'store'])->name('clients.share.store');
    Route::delete('/clients/{client}/share', [ClientShareController::class, 'destroy'])->name('clients.share.destroy');
    Route::get('/clients/{client}/timesheet.pdf', [TimesheetController::class, 'client'])->name('clients.timesheet');

    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::patch('/invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
    Route::patch('/invoices/{invoice}/pay', [InvoiceController::class, 'pay'])->name('invoices.pay');
    Route::patch('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel');
    Route::get('/invoices/{invoice}/download.pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');

    Route::post('/invoices/{invoice}/items', [InvoiceItemController::class, 'store'])->name('invoice-items.store');
    Route::patch('/invoice-items/{invoiceItem}', [InvoiceItemController::class, 'update'])->name('invoice-items.update');
    Route::delete('/invoice-items/{invoiceItem}', [InvoiceItemController::class, 'destroy'])->name('invoice-items.destroy');

    Route::resource('projects', ProjectController::class);
    Route::get('/projects/{project}/timesheet.pdf', [TimesheetController::class, 'project'])->name('projects.timesheet');

    Route::get('/projects/{project}/time-logs/export.csv', [TimeLogController::class, 'export'])->name('time-logs.export');
    Route::post('/projects/{project}/time-logs', [TimeLogController::class, 'store'])->name('time-logs.store');
    Route::post('/projects/{project}/time-logs/manual', [TimeLogController::class, 'storeManual'])->name('time-logs.store-manual');
    Route::patch('/time-logs/{timeLog}', [TimeLogController::class, 'update'])->name('time-logs.update');
    Route::patch('/time-logs/{timeLog}/pause', [TimeLogController::class, 'pause'])->name('time-logs.pause');
    Route::patch('/time-logs/{timeLog}/resume', [TimeLogController::class, 'resume'])->name('time-logs.resume');
    Route::patch('/time-logs/{timeLog}/manual', [TimeLogController::class, 'updateManual'])->name('time-logs.update-manual');
    Route::patch('/time-logs/{timeLog}/note', [TimeLogController::class, 'updateNote'])->name('time-logs.update-note');
    Route::delete('/time-logs/{timeLog}', [TimeLogController::class, 'destroy'])->name('time-logs.destroy');

    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    Route::get('/openapi.yaml', OpenApiController::class)->name('openapi');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
