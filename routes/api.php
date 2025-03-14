<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrainingSessionController;
use App\Http\Controllers\AttendanceRecordController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Training Sessions
    Route::get('/training-sessions', [TrainingSessionController::class, 'index']);
    Route::post('/training-sessions', [TrainingSessionController::class, 'store']);
    Route::put('/training-sessions/{trainingSession}', [TrainingSessionController::class, 'update']);
    Route::delete('/training-sessions/{trainingSession}', [TrainingSessionController::class, 'destroy']);
    Route::post('/training-sessions/{trainingSession}/register', [TrainingSessionController::class, 'register']);
    Route::delete('/training-sessions/{trainingSession}/register', [TrainingSessionController::class, 'cancelRegistration']);

    // Attendance Records
    Route::put('/attendance-records/{attendanceRecord}', [AttendanceRecordController::class, 'update']);
}); 