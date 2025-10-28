<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ArticuloTienda;
use App\Models\PuntoAcopio;
use App\Models\TransaccionPuntos;
use App\Models\Residuo;
use App\Models\TipoResiduo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    // ==================== GESTIÓN DE USUARIOS ====================
    
    /**
     * Listar todos los usuarios
     */
    public function indexUsuarios(Request $request)
    {
        $usuarios = User::select('id_usuario', 'nombre', 'apellido', 'dni', 'email', 'rol', 'puntos', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get();
        
        return response()->json($usuarios, 200);
    }

    /**
     * Ver detalles de un usuario específico
     */
    public function showUsuario($id)
    {
        $usuario = User::with(['puntosAcopio', 'transaccionesPuntos', 'transaccionesComoRecolector'])
                       ->find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        return response()->json($usuario, 200);
    }

    /**
     * Actualizar un usuario (cambiar rol, puntos, etc.)
     */
    public function updateUsuario(Request $request, $id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255',
            'apellido' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id . ',id_usuario',
            'rol' => 'sometimes|in:cliente,recolector,admin',
            'puntos' => 'sometimes|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $usuario->update($request->only(['nombre', 'apellido', 'email', 'rol', 'puntos']));

        return response()->json([
            'message' => 'Usuario actualizado exitosamente.',
            'usuario' => $usuario
        ], 200);
    }

    /**
     * Eliminar un usuario
     */
    public function destroyUsuario($id)
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        try {
            // Verificar si tiene relaciones que impidan la eliminación
            $tienePuntosAcopio = $usuario->puntosAcopio()->count() > 0;
            $tieneTransacciones = TransaccionPuntos::where('user_id_cliente', $id)
                                                   ->orWhere('user_id_recolector', $id)
                                                   ->count() > 0;

            if ($tienePuntosAcopio || $tieneTransacciones) {
                return response()->json([
                    'message' => 'No se puede eliminar. El usuario tiene registros asociados (puntos de acopio o transacciones).'
                ], 400);
            }

            $usuario->delete();

            return response()->json([
                'message' => 'Usuario eliminado exitosamente.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    // ==================== GESTIÓN DE PUNTOS DE ACOPIO ====================
    
    /**
     * Listar todos los puntos de acopio
     */
    public function indexAcopios()
    {
        $acopios = PuntoAcopio::with('recolector:id_usuario,nombre,apellido,email')
                              ->get();
        
        return response()->json($acopios, 200);
    }

    /**
     * Actualizar un punto de acopio
     */
    public function updateAcopio(Request $request, $id)
    {
        $acopio = PuntoAcopio::find($id);

        if (!$acopio) {
            return response()->json(['message' => 'Punto de acopio no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre_lugar' => 'sometimes|string|max:255',
            'direccion' => 'sometimes|string|max:255',
            'ubicacion_gps' => 'sometimes|string|max:255',
            'estado' => 'sometimes|in:pendiente,aprobado,rechazado',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $acopio->update($request->only(['nombre_lugar', 'direccion', 'ubicacion_gps', 'estado']));

        return response()->json([
            'message' => 'Punto de acopio actualizado exitosamente.',
            'acopio' => $acopio
        ], 200);
    }

    /**
     * Eliminar un punto de acopio
     */
    public function destroyAcopio($id)
    {
        $acopio = PuntoAcopio::find($id);

        if (!$acopio) {
            return response()->json(['message' => 'Punto de acopio no encontrado.'], 404);
        }

        $acopio->delete();

        return response()->json([
            'message' => 'Punto de acopio eliminado exitosamente.'
        ], 200);
    }

    // ==================== DASHBOARD Y ESTADÍSTICAS ====================
    
    /**
     * Obtener estadísticas generales del sistema
     */
    public function dashboard()
    {
        $stats = [
            'total_usuarios' => User::count(),
            'total_clientes' => User::where('rol', 'cliente')->count(),
            'total_recolectores' => User::where('rol', 'recolector')->count(),
            'total_admins' => User::where('rol', 'admin')->count(),
            
            'total_acopios' => PuntoAcopio::count(),
            'acopios_pendientes' => PuntoAcopio::where('estado', 'pendiente')->count(),
            'acopios_aprobados' => PuntoAcopio::where('estado', 'aprobado')->count(),
            
            'total_articulos' => ArticuloTienda::count(),
            'articulos_sin_stock' => ArticuloTienda::where('stock', 0)->count(),
            
            'total_residuos_registrados' => Residuo::count(),
            'residuos_disponibles' => Residuo::where('estado', 'disponible')->count(),
            'residuos_reclamados' => Residuo::where('estado', 'reclamado')->count(),
            
            'total_transacciones' => TransaccionPuntos::count(),
            'transacciones_completadas' => TransaccionPuntos::where('status', 'completada')->count(),
            'transacciones_pendientes' => TransaccionPuntos::whereIn('status', ['pendiente_recojo'])->count(),
            
            'total_puntos_distribuidos' => Residuo::where('estado', 'reclamado')->sum('puntos_otorgados'),
            'total_puntos_canjeados' => abs(TransaccionPuntos::where('tipo', 'canjeado')->sum('puntos')),
            
            'total_kg_reciclados' => Residuo::sum('cantidad_kg'),
            
            // Estadísticas por tipo de residuo
            'residuos_por_tipo' => Residuo::select('tipo_residuo', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(cantidad_kg) as total_kg'))
                                           ->groupBy('tipo_residuo')
                                           ->get(),
            
            // Estadísticas por punto de acopio (de dónde viene más residuo)
            'residuos_por_acopio' => Residuo::select('punto_acopio_id', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(cantidad_kg) as total_kg'))
                                            ->with('puntoAcopio:id_acopio,nombre_lugar,direccion')
                                            ->groupBy('punto_acopio_id')
                                            ->orderBy('total_kg', 'desc')
                                            ->get(),
        ];

        return response()->json($stats, 200);
    }

    // ==================== GESTIÓN DE TIPOS DE RESIDUO ====================
    
    /**
     * Listar todos los tipos de residuo con sus precios
     */
    public function indexTiposResiduo()
    {
        $tipos = TipoResiduo::orderBy('nombre')->get();
        return response()->json($tipos, 200);
    }

    /**
     * Crear un nuevo tipo de residuo
     */
    public function storeTipoResiduo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255|unique:tipos_residuo,nombre',
            'descripcion' => 'nullable|string',
            'puntos_por_kg' => 'required|numeric|min:0',
            'color_hex' => 'nullable|string|max:7',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $tipo = TipoResiduo::create($request->all());

        return response()->json([
            'message' => 'Tipo de residuo creado exitosamente.',
            'tipo' => $tipo,
        ], 201);
    }

    /**
     * Actualizar un tipo de residuo (cambiar precio por kg)
     */
    public function updateTipoResiduo(Request $request, $id)
    {
        $tipo = TipoResiduo::find($id);

        if (!$tipo) {
            return response()->json(['message' => 'Tipo de residuo no encontrado.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'nombre' => 'sometimes|string|max:255|unique:tipos_residuo,nombre,' . $id . ',id_tipo',
            'descripcion' => 'nullable|string',
            'puntos_por_kg' => 'sometimes|numeric|min:0',
            'color_hex' => 'nullable|string|max:7',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $tipo->update($request->all());

        return response()->json([
            'message' => 'Tipo de residuo actualizado exitosamente.',
            'tipo' => $tipo,
        ], 200);
    }

    /**
     * Eliminar un tipo de residuo
     */
    public function destroyTipoResiduo($id)
    {
        $tipo = TipoResiduo::find($id);

        if (!$tipo) {
            return response()->json(['message' => 'Tipo de residuo no encontrado.'], 404);
        }

        // Verificar si hay residuos con este tipo
        $residuosExistentes = Residuo::where('tipo_residuo', $tipo->nombre)->count();

        if ($residuosExistentes > 0) {
            return response()->json([
                'message' => 'No se puede eliminar. Hay ' . $residuosExistentes . ' residuos con este tipo. Desactívalo en su lugar.'
            ], 400);
        }

        $tipo->delete();

        return response()->json([
            'message' => 'Tipo de residuo eliminado exitosamente.'
        ], 200);
    }

    // ==================== GESTIÓN DE TRANSACCIONES ====================

    /**
     * Listar todas las transacciones
     */
    public function indexTransacciones(Request $request)
    {
        $query = TransaccionPuntos::with(['usuario:id_usuario,nombre,apellido', 'recolector:id_usuario,nombre,apellido', 'articuloTienda:id_articulo,nombre'])
                                  ->orderBy('created_at', 'desc');

        // Filtrar por tipo si se proporciona
        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $transacciones = $query->get();

        return response()->json($transacciones, 200);
    }
}
