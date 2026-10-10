<?php

use App\Http\Controllers\ChildOfficer\ChildController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

Route::prefix('child-officer')->name('child-officer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'childOfficer'])->name('dashboard');

    Route::resource('children', ChildController::class);
});

Route::prefix('field-officer')->name('field-officer.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'fieldOfficer'])->name('dashboard');
});
