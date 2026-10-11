<?php

use App\Http\Controllers\ChildOfficer\ChildController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'childOfficerDashboard'])->name('child-officer.home');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/field-officer/dashboard', [DashboardController::class, 'fieldOfficerDashboard'])
    ->name('field-officer.dashboard');
Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

Route::prefix('child-officer')->name('child-officer.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'childOfficerDashboard'])->name('dashboard');
    Route::resource('children', ChildController::class);
});
