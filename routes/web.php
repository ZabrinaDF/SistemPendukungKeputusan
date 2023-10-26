<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/elektre', [ElektreController::class, 'index']);
Route::get('/elektre/result', [ElektreController::class, 'result']);
Route::get('/elektre/alternatives', [ElektreController::class, 'getAlternatives']);
Route::get('/elektre/criterias', [ElektreController::class, 'getCriterias']);