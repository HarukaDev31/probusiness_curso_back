<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/getPaises', 'App\Http\Controllers\PaisController@getPaises');
    Route::get('/getDepartamentos', 'App\Http\Controllers\PaisController@getDepartamentos');
    Route::post('/getProvincias', 'App\Http\Controllers\PaisController@getProvincias');
    Route::post('/getDistritos', 'App\Http\Controllers\PaisController@getDistritos');
    Route::post('/crearUsuario', 'App\Http\Controllers\UsuarioController@crearUsuario');
});