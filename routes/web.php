<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FoodController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('layouts.main');
// });

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DeliveryController;

Route::get('/', [AuthController::class, 'loginPage'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');

Route::resource('/category' , CategoryController::class);
Route::resource('/food' , FoodController::class);

Route::get('/delivery', [DeliveryController::class, 'index'])->name('delivery.index');
Route::post('/delivery/add-to-session/{food}', [DeliveryController::class, 'addToSession'])->name('delivery.addToSession');
Route::post('/delivery/update-and-send', [DeliveryController::class, 'updateSessionAndSendToTelegram'])->name('delivery.updateSessionAndSendToTelegram');