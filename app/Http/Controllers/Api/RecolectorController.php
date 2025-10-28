<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransaccionPuntos;
use App\Models\Residuo;
use App\Models\TipoResiduo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecolectorController extends Controller
{
    /**
     * Ver estadísticas del recolector (puntos dados, kg reciclados, etc.)
     */
    public function misPuntos()
    {
        /** @var User $recolector */
        $recolector = Auth::user();
        $puntoAcopio = $recolector->puntoAcopio;

        if (!$puntoAcopio) {
            return response()->json(['message' => 'No tienes un punto de acopio asignado.'], 403);
        }

        $stats = [
            'punto_acopio' => [
                'id' => $puntoAcopio->id_acopio,
                'nombre' => $puntoAcopio->nombre_lugar,
                'direccion' => $puntoAcopio->direccion,
                'estado' => $puntoAcopio->estado,
            ],
            
            'total_residuos_registrados' => Residuo::where('user_id_recolector', $recolector->id_usuario)->count(),
            
            'total_puntos_distribuidos' => Residuo::where('user_id_recolector', $recolector->id_usuario)
                                                   ->where('estado', 'reclamado')
                                                   ->sum('puntos_otorgados'),
            
            'total_kg_recolectados' => Residuo::where('user_id_recolector', $recolector->id_usuario)
                                               ->sum('cantidad_kg'),
            
            'qrs_disponibles' => Residuo::where('user_id_recolector', $recolector->id_usuario)
                                         ->where('estado', 'disponible')
                                         ->count(),
            
            'qrs_reclamados' => Residuo::where('user_id_recolector', $recolector->id_usuario)
                                        ->where('estado', 'reclamado')
                                        ->count(),
            
            'articulos_pendientes_entrega' => TransaccionPuntos::where('punto_acopio_id', $puntoAcopio->id_acopio)
                                                               ->where('status', 'pendiente_recojo')
                                                               ->count(),
        ];

        return response()->json($stats, 200);
    }

    /**
     * Ver historial de QRs generados (residuos registrados)
     */
    public function misQRs(Request $request)
    {
        /** @var User $recolector */
        $recolector = Auth::user();

        $estado = $request->query('estado'); // Filtro opcional: disponible, reclamado

        $query = Residuo::where('user_id_recolector', $recolector->id_usuario)
                        ->with(['puntoAcopio:id_acopio,nombre_lugar', 'cliente:id_usuario,nombre,apellido']);

        if ($estado) {
            $query->where('estado', $estado);
        }

        $residuos = $query->orderBy('fecha_registro', 'desc')->get();

        return response()->json($residuos, 200);
    }

    /**
     * Ver artículos pendientes de entrega en mi punto de acopio
     */
    public function canjesPendientes()
    {
        /** @var User $recolector */
        $recolector = Auth::user();
        $puntoAcopio = $recolector->puntoAcopio;

        if (!$puntoAcopio) {
            return response()->json(['message' => 'No tienes un punto de acopio asignado.'], 403);
        }

        $canjes = TransaccionPuntos::where('punto_acopio_id', $puntoAcopio->id_acopio)
                                   ->where('status', 'pendiente_recojo')
                                   ->with([
                                       'usuario:id_usuario,nombre,apellido,dni,email',
                                       'articuloTienda:id_articulo,nombre,imagen_url',
                                       'puntoAcopio:id_acopio,nombre_lugar,direccion'
                                   ])
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return response()->json($canjes, 200);
    }

    /**
     * Ver historial de artículos entregados (completados) en mi punto de acopio
     */
    public function canjesCompletados()
    {
        /** @var User $recolector */
        $recolector = Auth::user();
        $puntoAcopio = $recolector->puntoAcopio;

        if (!$puntoAcopio) {
            return response()->json(['message' => 'No tienes un punto de acopio asignado.'], 403);
        }

        $canjes = TransaccionPuntos::where('punto_acopio_id', $puntoAcopio->id_acopio)
                                   ->where('tipo', 'canjeado')
                                   ->where('status', 'completada')
                                   ->with([
                                       'usuario:id_usuario,nombre,apellido,dni,email',
                                       'articuloTienda:id_articulo,nombre,imagen_url',
                                       'puntoAcopio:id_acopio,nombre_lugar,direccion'
                                   ])
                                   ->orderBy('updated_at', 'desc')
                                   ->get();

        return response()->json($canjes, 200);
    }

    /**
     * Ver residuos recibidos con sus puntos y precios por kg
     */
    public function residuosRecibidos(Request $request)
    {
        /** @var User $recolector */
        $recolector = Auth::user();

        $tipo = $request->query('tipo'); // Filtro opcional por tipo

        $query = Residuo::where('user_id_recolector', $recolector->id_usuario)
                        ->with(['cliente:id_usuario,nombre,apellido', 'puntoAcopio:id_acopio,nombre_lugar']);

        if ($tipo) {
            $query->where('tipo_residuo', $tipo);
        }

        $residuos = $query->orderBy('fecha_registro', 'desc')->get();

        // Obtener precios actuales por kg
        $precios = TipoResiduo::where('activo', true)
                              ->select('nombre', 'puntos_por_kg', 'descripcion')
                              ->get();

        // Estadísticas detalladas por tipo
        $estadisticasPorTipo = Residuo::where('user_id_recolector', $recolector->id_usuario)
                                      ->select(
                                          'tipo_residuo',
                                          DB::raw('COUNT(*) as cantidad_registros'),
                                          DB::raw('SUM(cantidad_kg) as total_kg'),
                                          DB::raw('SUM(puntos_otorgados) as total_puntos'),
                                          DB::raw('AVG(puntos_otorgados / cantidad_kg) as precio_promedio_por_kg')
                                      )
                                      ->groupBy('tipo_residuo')
                                      ->get();

        return response()->json([
            'precios_actuales' => $precios,
            'estadisticas_por_tipo' => $estadisticasPorTipo,
            'residuos' => $residuos,
        ], 200);
    }
}

