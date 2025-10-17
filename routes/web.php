<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/posts/{post:slug}', [HomeController::class, 'show'])->name('posts.show');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Library
    Route::get('/library', [LibraryController::class, 'index'])->name('library');

    // Post interactions
    Route::post('/posts/{post:slug}/like', [HomeController::class, 'toggleLike'])->name('posts.like');
    Route::post('/posts/{post:slug}/bookmark', [HomeController::class, 'toggleBookmark'])->name('posts.bookmark');
    Route::post('/posts/{post:slug}/report', [HomeController::class, 'reportPost'])->name('posts.report');
});

require __DIR__.'/auth.php';
