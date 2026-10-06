<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProgramController;

// Public
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');

// Protected (requires a valid token)
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get ('/me',     [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);


    Route::get   ('/users',                   [UserController::class, 'index'])->middleware('permission:manage_users');
    Route::post  ('/users',                   [UserController::class, 'store'])->middleware('permission:manage_users');
    Route::put   ('/users/{user}',            [UserController::class, 'update'])->middleware('permission:manage_users');
    Route::patch ('/users/{user}/deactivate', [UserController::class, 'deactivate'])->middleware('permission:manage_users');
    Route::patch ('/users/{user}/activate',   [UserController::class, 'activate'])->middleware('permission:manage_users');

    // Children
    Route::get   ('/children',                 [ChildController::class, 'index'])->middleware('permission:view_children');
    Route::post  ('/children',                 [ChildController::class, 'store'])->middleware('permission:create_child');
    Route::get   ('/children/{child}',         [ChildController::class, 'show'])->middleware('permission:view_children');
    Route::put   ('/children/{child}',         [ChildController::class, 'update'])->middleware('permission:edit_child');
    Route::patch ('/children/{child}/archive', [ChildController::class, 'archive'])->middleware('permission:archive_child');

    // QR
    Route::post('/children/{child}/qr', [QrController::class, 'generate'])->middleware('permission:generate_qr');
    Route::post('/qr/resolve',          [QrController::class, 'resolve'])->middleware('permission:scan_qr');

    // Anyone logged in can view programs
    Route::get('/programs',            [ProgramController::class, 'index']);
    Route::get('/programs/{program}',  [ProgramController::class, 'show']);

    // Only admins/managers can modify
   Route::post ('/programs',                   [ProgramController::class, 'store'])->middleware('permission:manage_programs');
   Route::put  ('/programs/{program}',         [ProgramController::class, 'update'])->middleware('permission:manage_programs');
   Route::patch('/programs/{program}/archive', [ProgramController::class, 'archive'])->middleware('permission:manage_programs');


});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
