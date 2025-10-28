<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ArticuloTienda; // <-- ¡Importa el modelo!
use Illuminate\Support\Facades\Validator;

class ArticuloTiendaController extends Controller
{

    /**
     * Almacena (crea) un nuevo artículo en la tienda.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'nombre'           => 'required|string|max:255',
            'descripcion'      => 'required|string',
            'stock'            => 'required|integer|min:0',
            'imagen_url'       => 'required|string|url', // 'url' valida que sea una URL válida
            'puntos_requeridos' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Crear el artículo
        $articulo = ArticuloTienda::create([
            'nombre'           => $request->nombre,
            'descripcion'      => $request->descripcion,
            'stock'            => $request->stock,
            'imagen_url'       => $request->imagen_url,
            'puntos_requeridos' => $request->puntos_requeridos,
        ]);

        // 3. Devolver respuesta
        return response()->json([
            'message' => 'Artículo creado exitosamente.',
            'data'    => $articulo
        ], 201); // 201 = Created
    }


    public function index(Request $request)
    {
        // 1. Busca todos los artículos
        $articulos = ArticuloTienda::all();

        // 2. Devuelve la lista en formato JSON
        return response()->json($articulos, 200);
    }


    /**
     * Actualizar un artículo existente (ADMIN).
     */
    public function update(Request $request, $id)
    {
        $articulo = ArticuloTienda::find($id);

        if (!$articulo) {
            return response()->json(['message' => 'Artículo no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'descripcion' => 'sometimes|string',
            'stock' => 'sometimes|integer|min:0',
            'imagen_url' => 'sometimes|string|url',
            'puntos_requeridos' => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $articulo->update($request->only(['nombre', 'descripcion', 'stock', 'imagen_url', 'puntos_requeridos']));

        return response()->json([
            'message' => 'Artículo actualizado exitosamente.',
            'data' => $articulo
        ], 200);
    }


    /**
     * Eliminar un artículo (ADMIN).
     */
    public function destroy($id)
    {
        $articulo = ArticuloTienda::find($id);

        if (!$articulo) {
            return response()->json(['message' => 'Artículo no encontrado.'], 404);
        }

        $articulo->delete();

        return response()->json([
            'message' => 'Artículo eliminado exitosamente.'
        ], 200);
    }

}