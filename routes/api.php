<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\DrugController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

//AUTH ROUTES
Route::controller(AuthController::class)->group(function() {
    Route::post('sign-in', 'login');
    Route::post('sign-up', 'register');
    Route::get('unauthorized', 'unauthorized')->name('unauthorized');
});

//DRUG PUBLIC ROUTES
Route::middleware(['throttle:api'])->controller(DrugController::class)->group(function() {
    Route::get('get-drugs', 'getDrugs');
});

Route::middleware(['auth:api'])->controller(DrugController::class)->group(function() {
    Route::post('add-drug', 'addUserDrug');
    Route::get('delete-drug/{id}', 'deleteUserDrug');
    Route::get('user-drugs-list', 'getUserDrugs');
});