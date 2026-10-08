<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\QrController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProgramController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\BenefitController;
use App\Http\Controllers\Api\FollowUpController;

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
   
   //event
   Route::get ('/events',          [EventController::class, 'index']);
   Route::get ('/events/{event}',  [EventController::class, 'show']);
   Route::post('/events',          [EventController::class, 'store'])->middleware('permission:manage_programs');
   Route::put ('/events/{event}',  [EventController::class, 'update'])->middleware('permission:manage_programs');
  
   //attendance
  Route::post('/events/{event}/attendance/scan', [AttendanceController::class, 'scan'])
    ->middleware('permission:record_attendance');

  Route::get('/events/{event}/attendance', [AttendanceController::class, 'index']);
  
  //view_reports lets admin and child_officer export
  Route::get('/events/{event}/attendance/export', [AttendanceController::class, 'export'])
    ->middleware('permission:view_reports');

   //benifits
  Route::get ('/benefit-types', [BenefitController::class, 'types']);
  Route::post('/benefit-types', [BenefitController::class, 'storeType'])->middleware('permission:manage_benefits');
  Route::post('/benefits',      [BenefitController::class, 'store'])->middleware('permission:record_benefits');
  Route::get ('/children/{child}/benefits', [BenefitController::class, 'childHistory']);

  
  //follow-ups
  Route::get   ('/follow-ups',                       [FollowUpController::class, 'index'])->middleware('permission:manage_followups');
  Route::post  ('/children/{child}/follow-ups',      [FollowUpController::class, 'store'])->middleware('permission:manage_followups');
  Route::get   ('/children/{child}/follow-ups',      [FollowUpController::class, 'childHistory']);
  Route::patch ('/follow-ups/{followUp}/complete',   [FollowUpController::class, 'complete'])->middleware('permission:manage_followups');
  Route::put   ('/follow-ups/{followUp}',            [FollowUpController::class, 'update'])->middleware('permission:manage_followups');




  // Audit log — admin only
  Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('permission:view_audit_logs');

  // Reports — admin + child officer
  Route::get('/reports/summary',       [ReportController::class, 'summary'])->middleware('permission:view_reports');
  Route::get('/reports/registrations', [ReportController::class, 'registrations'])->middleware('permission:view_reports');
  Route::get('/reports/participation', [ReportController::class, 'participation'])->middleware('permission:view_reports');
  Route::get('/reports/benefits',      [ReportController::class, 'benefits'])->middleware('permission:view_reports');


});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
