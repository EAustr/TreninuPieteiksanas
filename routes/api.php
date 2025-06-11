<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrainingSessionController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\Api\UserController;

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

Route::middleware(['web', 'auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Training Sessions
    Route::get('/training-sessions', [TrainingSessionController::class, 'index']);
    Route::post('/training-sessions', [TrainingSessionController::class, 'store']);
    Route::put('/training-sessions/{trainingSession}', [TrainingSessionController::class, 'update']);
    Route::delete('/training-sessions/{trainingSession}', [TrainingSessionController::class, 'destroy']);
    Route::post('/training-sessions/{trainingSession}/register', [TrainingSessionController::class, 'register']);
    Route::delete('/training-sessions/{trainingSession}/register', [TrainingSessionController::class, 'unregister']);

    // Attendance Records
    Route::put('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
    Route::get('/training/attendance/heatmap', [AttendanceRecordController::class, 'heatmap']);

    // User Management
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
    });

    // Get all trainers (accessible by admin and trainer roles)
    Route::middleware(['role:admin|trainer'])->group(function () {
        Route::get('/trainers', [UserController::class, 'trainers']);
    });

    Route::get('/settings/attended-training', [AttendanceRecordController::class, 'attendedTrainings']);
});
