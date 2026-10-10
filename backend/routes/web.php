<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index']);   // public home page
Route::redirect('/home', '/');
Route::view('/app',  'app');                         // login + role-based application
Route::view('/scan', 'scan');                        // opened by a child's QR code