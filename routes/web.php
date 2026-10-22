<?php

use App\Http\Controllers\ArticlePageController;
use App\Http\Controllers\FloraPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\VoluntariadoPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

// Voluntariados
Route::get('/voluntariados', [VoluntariadoPageController::class, 'index'])->name('voluntariados.index');
Route::get('/voluntariados/{id}', [VoluntariadoPageController::class, 'show'])->name('voluntariados.show');

// Flora y fauna
Route::get('/flora', [FloraPageController::class, 'index'])->name('flora.index');
Route::get('/flora/{id}', [FloraPageController::class, 'show'])->name('flora.show');

// Artículos
Route::get('/articulos', [ArticlePageController::class, 'index'])->name('articulos.index');
Route::get('/articulos/{id}', [ArticlePageController::class, 'show'])->name('articulos.show');
