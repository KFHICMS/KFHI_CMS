<?php

use App\Http\Controllers\ChildOfficer\ChildController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'childOfficerDashboard'])->name('child-officer.dashboard');
Route::get('/child-officer/dashboard', [DashboardController::class, 'childOfficerDashboard'])->name('child-officer.dashboard.page');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/field-officer/dashboard', [DashboardController::class, 'fieldOfficerDashboard'])->name('field-officer.dashboard');
Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

Route::prefix('child-officer')->name('child-officer.')->group(function () {
    Route::resource('children', ChildController::class);
});
