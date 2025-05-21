<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PlatformController;
use App\Http\Controllers\API\PostController;
use Illuminate\Support\Facades\Route;

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

// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    
    // Posts
    Route::apiResource('posts', PostController::class);
    
    // Platforms
    Route::apiResource('platforms', PlatformController::class);
    Route::post('/platforms/toggle', [PlatformController::class, 'toggleUserPlatforms']);
}); 