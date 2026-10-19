<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ArticuloController extends Controller
{
    public function index(Request $request)
    {
        $query = Articulo::query();

        if ($request->has('estado')) {
            $query->where('estado', $request->estado);
        } else {
            $query->where('estado', 'publicado');
        }

        $articulos = $query->orderBy('fechaPublicacion', 'desc')->get();

        return response()->json([
            'success' => true,
            'articulos' => $articulos,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'autorId' => 'required|string|max:100',
            'titulo' => 'required|string|max:200',
            'contenido' => 'required|string',
            'imagen' => 'nullable|string|max:500',
            'categoria' => 'required|string|max:100',
        ]);

        $autor = User::find($validated['autorId']);

        if (!$autor) {
            return response()->json([
                'success' => false,
                'message' => 'El autor indicado no existe.',
            ], 404);
        }

        $articulo = Articulo::create([
            'autorId' => $validated['autorId'],
            'titulo' => $validated['titulo'],
            'contenido' => $validated['contenido'],
            'imagen' => $validated['imagen'] ?? null,
            'categoria' => $validated['categoria'],
            'estado' => 'borrador', // Estado inicial por defecto
            'fechaPublicacion' => null,
            'fechaActualizacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Artículo creado como borrador correctamente.',
            'articulo' => $articulo,
        ], 201);
    }

    public function show(string $id)
    {
        $articulo = Articulo::find($id);

        if (!$articulo) {
            return response()->json([
                'success' => false,
                'message' => 'Artículo no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'articulo' => $articulo,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $articulo = Articulo::find($id);

        if (!$articulo) {
            return response()->json([
                'success' => false,
                'message' => 'Artículo no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:200',
            'contenido' => 'sometimes|required|string',
            'imagen' => 'sometimes|nullable|string|max:500',
            'categoria' => 'sometimes|required|string|max:100',
            'estado' => [
                'sometimes',
                'required',
                Rule::in(['borrador', 'pendiente', 'publicado', 'rechazado']),
            ],
        ]);

        // Si cambia a publicado y no tenía fecha, asignarla
        if (isset($validated['estado']) && $validated['estado'] === 'publicado' && !$articulo->fechaPublicacion) {
            $validated['fechaPublicacion'] = now();
        }

        $validated['fechaActualizacion'] = now();

        $articulo->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Artículo actualizado correctamente.',
            'articulo' => $articulo->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $articulo = Articulo::find($id);

        if (!$articulo) {
            return response()->json([
                'success' => false,
                'message' => 'Artículo no encontrado.',
            ], 404);
        }

        $articulo->delete();

        return response()->json([
            'success' => true,
            'message' => 'Artículo eliminado correctamente.',
        ]);
    }
}