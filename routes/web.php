<?php

use App\Http\Controllers\Admin\DocumentController as AdminDocumentController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\PublicationController as AdminPublicationController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\PublicationController;
use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Home')->name('home');
Route::inertia('/education', 'Education')->name('education');
Route::inertia('/experience', 'Experience')->name('experience');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/publications', [PublicationController::class, 'index'])->name('publications');
Route::inertia('/hobbies', 'Hobbies')->name('hobbies');
Route::inertia('/contact', 'Contact')->name('contact');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::get('/student-corner', [StudentController::class, 'index'])->name('student');
    Route::inertia('/student-corner/profile', 'StudentProfile')->name('student.profile');
    Route::put('/student-corner/profile', [ProfileController::class, 'update']);
    Route::put('/student-corner/profile/password', [ProfileController::class, 'updatePassword']);
    Route::get('/student-corner/documents/{document}', [StudentController::class, 'show'])->name('student.documents.show');
    Route::get('/student-corner/documents/{document}/download', [StudentController::class, 'download'])->name('student.documents.download');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::inertia('/', 'admin/Dashboard')->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::resource('projects', AdminProjectController::class)->except('show');
    Route::resource('documents', AdminDocumentController::class)->except('show');
    Route::resource('publications', AdminPublicationController::class)->except('show');
    Route::resource('users', AdminUserController::class)->only(['index', 'edit', 'update']);
});

// Keep links to the old static .html pages working
foreach ([
    '/index.html' => 'home',
    '/Education.html' => 'education',
    '/Experience.html' => 'experience',
    '/Projects.html' => 'projects',
    '/Publications.html' => 'publications',
    '/StudentCorner.html' => 'student',
    '/student' => 'student',
    '/Hobbies.html' => 'hobbies',
    '/Contact.html' => 'contact',
    '/admin/login' => 'login',
] as $oldPath => $routeName) {
    Route::get($oldPath, fn () => to_route($routeName, status: 301));
}
