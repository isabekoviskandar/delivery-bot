<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FoodController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('layouts.main');
// });

use App\Http\Controllers\AuthController;

Route::get('/', [AuthController::class, 'loginPage'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::resource('/category' , CategoryController::class);
Route::resource('/food' , FoodController::class);