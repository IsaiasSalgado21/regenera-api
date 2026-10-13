<?php


use App\Http\Controllers\AuthController;
use App\Http\Controllers\LugarController;
use App\Http\Controllers\VoluntariadoController;
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

Route::get('/auth/me', [AuthController::class, 'me']);

Route::post('/auth/logout', [AuthController::class, 'logout']);

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