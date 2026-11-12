<?php

use App\Http\Controllers\ArticlePageController;
use App\Http\Controllers\FloraPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MapPageController;
use App\Http\Controllers\VoluntariadoPageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/mapa', [MapPageController::class, 'index'])->name('mapa');
Route::get('/admin/dashboard', fn () => view('dashboard.index', ['dashboardRole' => 'admin']))
    ->name('dashboard.admin');
Route::get('/admin/cuestionarios', fn () => view('dashboard.cuestionarios'))
    ->name('dashboard.quizzes');
Route::get('/organizador/dashboard', fn () => view('dashboard.index', ['dashboardRole' => 'organizador']))
    ->name('dashboard.organizador');
Route::get('/notificaciones', fn () => view('notifications.index'))->name('notifications.index');
Route::get('/perfil', fn () => view('profile.show'))->name('profile.show');
Route::get('/aprendizaje', fn () => view('learning.index'))->name('learning.index');

// Voluntariados
Route::get('/voluntariados', [VoluntariadoPageController::class, 'index'])->name('voluntariados.index');
Route::get('/voluntariados/crear', [VoluntariadoPageController::class, 'create'])->name('voluntariados.create');
Route::get('/voluntariados/{id}', [VoluntariadoPageController::class, 'show'])->name('voluntariados.show');
Route::view('/asistencia', 'asistencia.scan')->name('asistencia.scan');

// Flora y fauna
Route::get('/flora', [FloraPageController::class, 'index'])->name('flora.index');
Route::get('/flora/{id}', [FloraPageController::class, 'show'])->name('flora.show');

// Artículos
Route::get('/articulos', [ArticlePageController::class, 'index'])->name('articulos.index');
Route::get('/articulos/crear', [ArticlePageController::class, 'create'])->name('articulos.create');
Route::get('/articulos/{id}', [ArticlePageController::class, 'show'])->name('articulos.show');
