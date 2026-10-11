<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\UserPhotoController as AdminUserPhotoController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ChildController;

// Public
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Protected (requires a valid token)
Route::middleware(['auth:sanctum', 'password.changed'])->group(function () {
    // Auth
    Route::get ('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [PasswordController::class, 'update'])->middleware('throttle:5,1');

    // Children
    Route::get   ('/children',                 [ChildController::class, 'index'])->middleware('permission:view_children');
    Route::post  ('/children',                 [ChildController::class, 'store'])->middleware('permission:create_child');
    Route::get   ('/children/{child}',         [ChildController::class, 'show'])->middleware('permission:view_children');
    Route::put   ('/children/{child}',         [ChildController::class, 'update'])->middleware('permission:edit_child');
    Route::patch ('/children/{child}/archive', [ChildController::class, 'archive'])->middleware('permission:archive_child');

    // QR
    Route::post('/children/{child}/qr', [QrController::class, 'generate'])->middleware('permission:generate_qr');
    Route::post('/qr/resolve',          [QrController::class, 'resolve'])->middleware('permission:scan_qr');

    Route::prefix('admin')->middleware('permission:manage_users')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::post('/users', [AdminUserController::class, 'store'])->middleware('throttle:20,1');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->middleware('throttle:30,1');
        Route::patch('/users/{user}/deactivate', [AdminUserController::class, 'deactivate'])->middleware('throttle:30,1');
        Route::patch('/users/{user}/activate', [AdminUserController::class, 'activate'])->middleware('throttle:30,1');
        Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->middleware('throttle:10,1');
        Route::get('/users/{user}/photo', [AdminUserPhotoController::class, 'show']);
        Route::post('/users/{user}/photo', [AdminUserPhotoController::class, 'store'])->middleware('throttle:20,1');
        Route::delete('/users/{user}/photo', [AdminUserPhotoController::class, 'destroy'])->middleware('throttle:20,1');
    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'password.changed']);
