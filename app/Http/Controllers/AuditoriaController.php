<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $query = Auditoria::query();

        if ($request->has('entidad')) {
            $query->where('entidad', $request->entidad);
        }

        if ($request->has('usuarioId')) {
            $query->where('usuarioId', $request->usuarioId);
        }

        $registros = $query->orderBy('fecha', 'desc')->get();

        return response()->json([
            'success' => true,
            'auditoria' => $registros,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuarioId' => 'required|string|max:100',
            'accion' => 'required|string|max:150',
            'entidad' => 'required|string|max:100',
            'entidadId' => 'required|string|max:100',
            'detalles' => 'nullable|array',
        ]);

        $registro = Auditoria::create([
            'usuarioId' => $validated['usuarioId'],
            'accion' => $validated['accion'],
            'entidad' => $validated['entidad'],
            'entidadId' => $validated['entidadId'],
            'fecha' => now(),
            'ip' => $request->ip(),
            'detalles' => $validated['detalles'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Evento de auditoría registrado correctamente.',
            'auditoria' => $registro,
        ], 201);
    }

    public function show(string $id)
    {
        $registro = Auditoria::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de auditoría no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'auditoria' => $registro,
        ]);
    }
}