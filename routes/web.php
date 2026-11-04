<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ProfileController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');
Route::inertia('/education', 'Education')->name('education');
Route::inertia('/experience', 'Experience')->name('experience');
Route::inertia('/projects', 'Projects')->name('projects');
Route::inertia('/publications', 'Publications')->name('publications');
Route::inertia('/student-corner', 'StudentCorner')->name('student-corner');
Route::inertia('/hobbies', 'Hobbies')->name('hobbies');
Route::inertia('/contact', 'Contact')->name('contact');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'create'])->name('login');
        Route::post('/login', [AuthController::class, 'store']);
    });

    Route::middleware('auth')->group(function () {
        Route::inertia('/', 'admin/Dashboard')->name('dashboard');
        Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    });
});

// Keep links to the old static .html pages working
foreach ([
    '/index.html' => 'home',
    '/Education.html' => 'education',
    '/Experience.html' => 'experience',
    '/Projects.html' => 'projects',
    '/Publications.html' => 'publications',
    '/StudentCorner.html' => 'student-corner',
    '/Hobbies.html' => 'hobbies',
    '/Contact.html' => 'contact',
] as $oldPath => $routeName) {
    Route::get($oldPath, fn () => to_route($routeName, status: 301));
}
