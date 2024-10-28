<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::post('/user',[UserController::class,"Register"]);
Route::get('/validate',[UserController::class,"ValidateToken"])->middleware('auth:api');
Route::get('/logout',[UserController::class,"Logout"])->middleware('auth:api');
Route::get('/usuario/actual', [UserController::class, 'obtenerUsuarioActual'])->middleware('auth:api');
Route::post('/usuario/actualizar', [UserController::class, 'actualizar'])->middleware('auth:api');
Route::get('/ciudades', [UserController::class, 'obtenerCiudades']);
Route::get('/ciudad/{id}', [UserController::class, 'show']);