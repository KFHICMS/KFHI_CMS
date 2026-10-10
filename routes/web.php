<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('child_officer.childdashboard');
});

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
