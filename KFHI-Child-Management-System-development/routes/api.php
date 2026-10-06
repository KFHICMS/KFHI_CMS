<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ChildController;

// Public
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Protected (requires a valid token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get ('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Children
    Route::get   ('/children',                 [ChildController::class, 'index'])->middleware('permission:view_children');
    Route::post  ('/children',                 [ChildController::class, 'store'])->middleware('permission:create_child');
    Route::get   ('/children/{child}',         [ChildController::class, 'show'])->middleware('permission:view_children');
    Route::put   ('/children/{child}',         [ChildController::class, 'update'])->middleware('permission:edit_child');
    Route::patch ('/children/{child}/archive', [ChildController::class, 'archive'])->middleware('permission:archive_child');

    // QR
    Route::post('/children/{child}/qr', [QrController::class, 'generate'])->middleware('permission:generate_qr');
    Route::post('/qr/resolve',          [QrController::class, 'resolve'])->middleware('permission:scan_qr');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
