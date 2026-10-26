<?php

use App\Http\Controllers\Admin\ArticuloController as AdminArticuloController;
use App\Http\Controllers\Admin\FloraFaunaController as AdminFloraFaunaController;
use App\Http\Controllers\Admin\LugarController as AdminLugarController;
use App\Http\Controllers\Api\ArticuloController;
use App\Http\Controllers\Api\FloraFaunaController;
use App\Http\Controllers\Api\LugarController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\MapApiController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VoluntariadoController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {

    try {
        DB::connection('mongodb')->command(['ping' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'MongoDB está conectado correctamente.',
        ]);

    } catch (Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'No se pudo conectar con MongoDB.',
            'error' => $e->getMessage(),
        ], 500);
    }
});

Route::post('/auth/register', [AuthController::class, 'register']);

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/auth/me', [AuthController::class, 'me']);

Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

Route::prefix('v1/mapa')->group(function () {
    Route::get('/voluntariados', [MapApiController::class, 'voluntariados']);
    Route::get('/lugares', [MapApiController::class, 'lugares']);
    Route::get('/flora-fauna', [MapApiController::class, 'floraFauna']);
});

Route::get('/usuarios', [UserController::class, 'index']);
Route::post('/usuarios', [UserController::class, 'store']);
Route::get('/usuarios/{id}', [UserController::class, 'show']);
Route::put('/usuarios/{id}', [UserController::class, 'update']);
Route::patch('/usuarios/{id}', [UserController::class, 'update']);
Route::delete('/usuarios/{id}', [UserController::class, 'destroy']);

Route::get('/lugares', [LugarController::class, 'index'])->middleware('auth.optional');
Route::post('/lugares', [LugarController::class, 'store'])->middleware('auth:sanctum');
Route::get('/lugares/{id}', [LugarController::class, 'show'])->middleware('auth.optional');
Route::put('/lugares/{id}', [LugarController::class, 'update'])->middleware('auth:sanctum');

Route::get('/voluntariados', [VoluntariadoController::class, 'index']);
Route::post('/voluntariados', [VoluntariadoController::class, 'store']);
Route::get('/voluntariados/{id}', [VoluntariadoController::class, 'show']);
Route::put('/voluntariados/{id}', [VoluntariadoController::class, 'update']);
Route::patch('/voluntariados/{id}', [VoluntariadoController::class, 'update']);
Route::delete('/voluntariados/{id}', [VoluntariadoController::class, 'destroy']);

Route::get('/inscripciones', [InscripcionController::class, 'index']);
Route::post('/inscripciones', [InscripcionController::class, 'store'])->middleware('auth:sanctum');
Route::get('/inscripciones/{id}', [InscripcionController::class, 'show']);
Route::put('/inscripciones/{id}', [InscripcionController::class, 'update']);
Route::patch('/inscripciones/{id}', [InscripcionController::class, 'update']);
Route::delete('/inscripciones/{id}', [InscripcionController::class, 'destroy']);

Route::get('/flora-fauna', [FloraFaunaController::class, 'index'])->middleware('auth.optional');
Route::post('/flora-fauna', [FloraFaunaController::class, 'store'])->middleware('auth:sanctum');
Route::get('/flora-fauna/{id}', [FloraFaunaController::class, 'show'])->middleware('auth.optional');
Route::put('/flora-fauna/{id}', [FloraFaunaController::class, 'update'])->middleware('auth:sanctum');

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'role:admin'])
    ->group(function () {
        Route::get('/lugares', [AdminLugarController::class, 'index']);
        Route::patch('/lugares/{id}/archivar', [AdminLugarController::class, 'archivar']);
        Route::get('/flora-fauna', [AdminFloraFaunaController::class, 'index']);
        Route::patch('/flora-fauna/{id}/archivar', [AdminFloraFaunaController::class, 'archivar']);
        Route::get('/articulos/pendientes', [AdminArticuloController::class, 'pendientes']);
        Route::patch('/articulos/{id}/aprobar', [AdminArticuloController::class, 'aprobar']);
        Route::patch('/articulos/{id}/rechazar', [AdminArticuloController::class, 'rechazar']);
        Route::patch('/articulos/{id}/despublicar', [AdminArticuloController::class, 'despublicar']);
        Route::delete('/articulos/{id}', [AdminArticuloController::class, 'destroy']);
        Route::get('/auditoria', [AuditoriaController::class, 'index']);
        Route::get('/auditoria/{id}', [AuditoriaController::class, 'show']);
    });

Route::get('/articulos', [ArticuloController::class, 'index'])->middleware('auth.optional');
Route::post('/articulos', [ArticuloController::class, 'store'])->middleware('auth:sanctum');
Route::get('/articulos/{id}', [ArticuloController::class, 'show'])->middleware('auth.optional');
Route::put('/articulos/{id}', [ArticuloController::class, 'update'])->middleware('auth:sanctum');
Route::patch('/articulos/{id}/enviar-revision', [ArticuloController::class, 'enviarRevision'])->middleware('auth:sanctum');

Route::get('/asistencias', [AsistenciaController::class, 'index']);
Route::post('/asistencias', [AsistenciaController::class, 'store']);
Route::get('/asistencias/{id}', [AsistenciaController::class, 'show']);
Route::delete('/asistencias/{id}', [AsistenciaController::class, 'destroy']);

Route::middleware('auth:sanctum')->prefix('notificaciones')->group(function () {
    Route::get('/', [NotificacionController::class, 'index']);
    Route::get('/no-leidas', [NotificacionController::class, 'noLeidas']);
    Route::patch('/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas']);
    Route::patch('/{id}/leida', [NotificacionController::class, 'marcarLeida']);
    Route::delete('/{id}', [NotificacionController::class, 'destroy']);
});
