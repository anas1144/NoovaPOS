<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Landing page — public marketing page
Route::get('/', function () {
    return view('landing');
})->name('landing');

// React SPA catch-all — handles /login, /register, /dashboard, etc.
// The React Router takes over from here.
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '^(?!api).*$')->name('spa');

