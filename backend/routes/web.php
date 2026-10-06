<?php

use Illuminate\Support\Facades\Route;

Route::get('/',      fn () => view('welcome'));   // landing page
Route::get('/home',  fn () => view('home'));      // home page

Route::get('/scan',  fn () => view('scan'));      // your scan page (keep)

