<?php

use App\Http\Controllers\API\V1\Admin\UsersController;
use App\Http\Controllers\API\V1\AuthController;
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

Route::get('/', function () {
    return API::response200([
        'message' => 'Welcome to PROYECT_NAME API V1!'
    ]);
});

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/me', [AuthController::class, 'me']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // ONLY ADMIN
    Route::group([], function () {
        Route::resource('users', UsersController::class);
    });
});


Route::fallback(function () {
    return API::response404();
});
