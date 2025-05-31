<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('register-user', function () {
        return Inertia::render('users/RegisterUser');
    })->middleware('role:admin')->name('register-user');

    Route::get('users', function () {
        return Inertia::render('users/UserManagement');
    })->middleware('role:admin')->name('users.index');

    Route::get('users/{user}/edit', function ($userId) {
        return Inertia::render('users/EditUser', ['userId' => $userId]);
    })->middleware('role:admin')->name('users.edit');

    Route::get('training-calendar', function () {
        return Inertia::render('training/TrainingCalendar');
    })->name('training.calendar');

    Route::get('training/attendance', function () {
        return Inertia::render('training/Attendance');
    })->middleware('role:admin|trainer')
        ->name('training.attendance');
    
});

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/settings/attended-training', function () {
        return Inertia::render('settings/AttendedTraining');
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
