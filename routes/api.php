<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\LugarController;
use App\Http\Controllers\MapApiController;
use App\Http\Controllers\VoluntariadoController;
use App\Http\Controllers\InscripcionController;
use App\Http\Controllers\FloraFaunaController;
use App\Http\Controllers\ArticuloController;
use App\Http\Controllers\AsistenciaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/ping', function () {

    try {
        DB::connection('mongodb')->command(['ping' => 1]);

        return response()->json([
            'success' => true,
            'message' => 'MongoDB está conectado correctamente.'
        ]);

    } catch (\Exception $e) {

        return response()->json([
            'success' => false,
            'message' => 'No se pudo conectar con MongoDB.',
            'error' => $e->getMessage()
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

Route::get('/lugares', [LugarController::class, 'index']);
Route::post('/lugares', [LugarController::class, 'store']);
Route::get('/lugares/{id}', [LugarController::class, 'show']);
Route::put('/lugares/{id}', [LugarController::class, 'update']);
Route::patch('/lugares/{id}', [LugarController::class, 'update']);
Route::delete('/lugares/{id}', [LugarController::class, 'destroy']);

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

Route::get('/flora-fauna', [FloraFaunaController::class, 'index']);
Route::post('/flora-fauna', [FloraFaunaController::class, 'store']);
Route::get('/flora-fauna/{id}', [FloraFaunaController::class, 'show']);
Route::put('/flora-fauna/{id}', [FloraFaunaController::class, 'update']);
Route::patch('/flora-fauna/{id}', [FloraFaunaController::class, 'update']);
Route::delete('/flora-fauna/{id}', [FloraFaunaController::class, 'destroy']);

Route::get('/articulos', [ArticuloController::class, 'index']);
Route::post('/articulos', [ArticuloController::class, 'store']);
Route::get('/articulos/{id}', [ArticuloController::class, 'show']);
Route::put('/articulos/{id}', [ArticuloController::class, 'update']);
Route::patch('/articulos/{id}', [ArticuloController::class, 'update']);
Route::delete('/articulos/{id}', [ArticuloController::class, 'destroy']);

Route::get('/asistencias', [AsistenciaController::class, 'index']);
Route::post('/asistencias', [AsistenciaController::class, 'store']);
Route::get('/asistencias/{id}', [AsistenciaController::class, 'show']);
Route::delete('/asistencias/{id}', [AsistenciaController::class, 'destroy']);

Route::get('/auditoria', [AuditoriaController::class, 'index']);
Route::post('/auditoria', [AuditoriaController::class, 'store']);
Route::get('/auditoria/{id}', [AuditoriaController::class, 'show']);

Route::get('/notificaciones', [NotificacionController::class, 'index']);
Route::get('/notificaciones/no-leidas/{usuarioId}', [NotificacionController::class, 'noLeidas']);
Route::post('/notificaciones', [NotificacionController::class, 'store']);
Route::patch('/notificaciones/{id}/leida', [NotificacionController::class, 'marcarLeida']);
Route::patch('/notificaciones/marcar-todas-leidas/{usuarioId}', [NotificacionController::class, 'marcarTodasLeidas']);
Route::delete('/notificaciones/{id}', [NotificacionController::class, 'destroy']);