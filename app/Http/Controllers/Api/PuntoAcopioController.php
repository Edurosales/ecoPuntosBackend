<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PuntoAcopio; // <-- ¡Importa el modelo!
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth; // <-- ¡Importa Auth para saber quién está logueado!

class PuntoAcopioController extends Controller
{

    /**
     * Almacena (crea) una nueva solicitud de punto de acopio.
     */
    public function store(Request $request)
    {
        // 1. Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'nombre_lugar'  => 'required|string|max:255',
            'direccion'     => 'required|string|max:255',
            'ubicacion_gps' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Obtener el ID del usuario autenticado
        $userId = Auth::id(); // O $request->user()->id_usuario;

        // 3. Crear el punto de acopio
        $puntoAcopio = PuntoAcopio::create([
            'nombre_lugar'       => $request->nombre_lugar,
            'direccion'          => $request->direccion,
            'ubicacion_gps'      => $request->ubicacion_gps,
            'user_id_recolector' => $userId,
            // 'estado' se pondrá 'pendiente' automáticamente por el default de la migración
        ]);

        // 4. Devolver respuesta
        return response()->json([
            'message' => 'Solicitud de punto de acopio recibida. Está pendiente de aprobación.',
            'data'    => $puntoAcopio
        ], 201); // 201 = Created
    }


    /**
     * Muestra todos los puntos de acopio pendientes de aprobación.
     */
    public function indexPendientes(Request $request)
    {
        // 1. Busca todos los acopios donde 'estado' es 'pendiente'
        //    'with' también trae los datos del recolector (User)
        $pendientes = PuntoAcopio::where('estado', 'pendiente')
                                 ->with('recolector') // Carga la relación
                                 ->get();

        // 2. Devuelve la lista
        return response()->json($pendientes, 200);
    }

    /**
     * Aprueba un punto de acopio y actualiza el rol del usuario.
     */
    public function approve(Request $request, $id)
    {
        // 1. Encontrar el punto de acopio por su ID
        $puntoAcopio = PuntoAcopio::findOrFail($id);

        // 2. Validar el estado que viene en la petición
        $validator = Validator::make($request->all(), [
            'estado' => 'required|in:aprobado,rechazado'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $nuevoEstado = $request->estado;

        // 3. Actualizar el estado del acopio
        $puntoAcopio->estado = $nuevoEstado;
        $puntoAcopio->save();

        // 4. Si se APRUEBA, encontrar al usuario (recolector) y actualizar su rol
        if ($nuevoEstado === 'aprobado') {
            $recolector = $puntoAcopio->recolector;
            
            if ($recolector) {
                $recolector->rol = 'recolector'; // ¡El usuario es promovido!
                $recolector->save();
            }

            return response()->json([
                'message' => '¡Punto de acopio aprobado! El usuario ahora es recolector.',
                'data'    => $puntoAcopio
            ], 200);
        }

        // 5. Si se RECHAZA
        return response()->json([
            'message' => 'Punto de acopio rechazado.',
            'data'    => $puntoAcopio
        ], 200);
    }

    /**
     * Actualiza un punto de acopio existente.
     */
    public function update(Request $request, $id)
    {
        // 1. Encontrar el punto de acopio
        $puntoAcopio = PuntoAcopio::findOrFail($id);

        // 2. Validar los datos de entrada
        $validator = Validator::make($request->all(), [
            'nombre_lugar'  => 'sometimes|string|max:255',
            'direccion'     => 'sometimes|string|max:255',
            'ubicacion_gps' => 'sometimes|string|max:255',
            'estado'        => 'sometimes|in:aprobado,pendiente,rechazado',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 3. Actualizar solo los campos que vienen en el request
        if ($request->has('nombre_lugar')) {
            $puntoAcopio->nombre_lugar = $request->nombre_lugar;
        }
        if ($request->has('direccion')) {
            $puntoAcopio->direccion = $request->direccion;
        }
        if ($request->has('ubicacion_gps')) {
            $puntoAcopio->ubicacion_gps = $request->ubicacion_gps;
        }
        if ($request->has('estado')) {
            $puntoAcopio->estado = $request->estado;
        }

        $puntoAcopio->save();

        // 4. Devolver respuesta
        return response()->json([
            'message' => 'Punto de acopio actualizado correctamente.',
            'data'    => $puntoAcopio
        ], 200);
    }

    /**
     * Elimina un punto de acopio.
     */
    public function destroy($id)
    {
        // 1. Encontrar el punto de acopio
        $puntoAcopio = PuntoAcopio::findOrFail($id);

        // 2. Eliminar
        $puntoAcopio->delete();

        // 3. Devolver respuesta
        return response()->json([
            'message' => 'Punto de acopio eliminado correctamente.'
        ], 200);
    }

}