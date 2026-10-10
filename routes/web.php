<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin-ui/login.html')->name('home');
Route::view('/child-officer/dashboard', 'child_officer.childdashboard')->name('child-officer.dashboard');

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessageController;

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/users', [DashboardController::class, 'index'])->name('dashboard.users');
Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
Route::delete('/messages/{message}', [MessageController::class, 'destroy'])->name('messages.destroy');

use App\Http\Controllers\ChildOfficer\ChildController;

Route::prefix('child-officer')->name('child-officer.')->group(function () {
    Route::resource('children', ChildController::class);
});
