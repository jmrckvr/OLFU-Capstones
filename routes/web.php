<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModerationController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->name('register.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/repository', [ProjectController::class, 'index'])->name('repository.index');
    Route::get('/repository/{project}/files/{file}', [ProjectController::class, 'download'])->name('repository.download');
    Route::middleware('role:student')->group(function () {
        Route::get('/upload', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/upload', [ProjectController::class, 'store'])->name('projects.store');
    });
    Route::middleware('role:coordinator,administrator')->group(function () {
        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::patch('/moderation/{project}', [ModerationController::class, 'update'])->name('moderation.update');
    });
});
