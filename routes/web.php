<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SubmissionController;
use Illuminate\Support\Facades\Route;

/*
| Who can open what:
|   guest                 -> home/login, register
|   any signed-in user    -> dashboard, repository, project pages, notifications, account
|   role:student          -> /submissions (My Submissions)
|   role:coordinator,administrator -> /reviews (Review Queue)
|   role:administrator    -> /admin/* (Projects, Users, Activity log)
| The `role:` guard returns 403 even if someone types the URL by hand.
*/

Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'login'])->name('home');
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'authenticate'])->name('login.store');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'store'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/repository', [ProjectController::class, 'index'])->name('repository.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/cover', [ProjectController::class, 'cover'])->name('projects.cover');
    Route::get('/projects/{project}/files/{file}', [ProjectController::class, 'download'])->name('projects.download');
    Route::get('/projects/{project}/files/{file}/preview', [ProjectController::class, 'preview'])->name('projects.preview');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/announcements', [NotificationController::class, 'announce'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [NotificationController::class, 'destroyAnnouncement'])->name('announcements.destroy');

    Route::get('/account', [AccountController::class, 'edit'])->name('account.edit');
    Route::put('/account', [AccountController::class, 'update'])->name('account.update');
    Route::put('/account/password', [AccountController::class, 'password'])->name('account.password');

    Route::middleware('role:student')->prefix('submissions')->name('submissions.')->group(function () {
        Route::get('/', [SubmissionController::class, 'index'])->name('index');
        Route::get('/create', [SubmissionController::class, 'create'])->name('create');
        Route::post('/', [SubmissionController::class, 'store'])->name('store');
        Route::get('/{project}/edit', [SubmissionController::class, 'edit'])->name('edit');
        Route::put('/{project}', [SubmissionController::class, 'update'])->name('update');
        Route::post('/{project}/submit', [SubmissionController::class, 'submit'])->name('submit');
        Route::delete('/{project}', [SubmissionController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('role:coordinator,administrator')->group(function () {
        Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::post('/reviews/{project}', [ReviewController::class, 'store'])->name('reviews.store');
    });

    Route::middleware('role:administrator')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/projects', [Admin\ProjectController::class, 'index'])->name('projects.index');
        Route::get('/projects/export', [Admin\ProjectController::class, 'export'])->name('projects.export');
        Route::get('/projects/create', [Admin\ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects', [Admin\ProjectController::class, 'store'])->name('projects.store');
        Route::get('/projects/{project}/edit', [Admin\ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}', [Admin\ProjectController::class, 'update'])->name('projects.update');
        Route::delete('/projects/{project}', [Admin\ProjectController::class, 'destroy'])->name('projects.destroy');

        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [Admin\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [Admin\UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/reset-password', [Admin\UserController::class, 'resetPassword'])->name('users.reset');

        Route::get('/activity', [Admin\ActivityController::class, 'index'])->name('activity.index');
    });
});
