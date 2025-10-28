<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransaccionPuntos;
use App\Models\PuntoAcopio;
use App\Models\Residuo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClienteController extends Controller
{
    /**
     * Ver puntos actuales del cliente
     */
    public function misPuntos()
    {
        /** @var User $cliente */
        $cliente = Auth::user();

        $stats = [
            'puntos_actuales' => $cliente->puntos,
            'total_ganados' => TransaccionPuntos::where('user_id_cliente', $cliente->id_usuario)
                                                ->where('tipo', 'ganado')
                                                ->where('status', 'completada')
                                                ->sum('puntos'),
            'total_canjeados' => abs(TransaccionPuntos::where('user_id_cliente', $cliente->id_usuario)
                                                      ->where('tipo', 'canjeado')
                                                      ->sum('puntos')),
            'total_transacciones' => TransaccionPuntos::where('user_id_cliente', $cliente->id_usuario)->count(),
        ];

        return response()->json($stats, 200);
    }

    /**
     * Ver historial completo de transacciones (ganados y canjeados)
     */
    public function miHistorial(Request $request)
    {
        /** @var User $cliente */
        $cliente = Auth::user();

        $tipo = $request->query('tipo'); // Filtro opcional: ganado, canjeado

        $query = TransaccionPuntos::where('user_id_cliente', $cliente->id_usuario)
                                   ->with([
                                       'recolector:id_usuario,nombre,apellido',
                                       'puntoAcopio:id_acopio,nombre_lugar,direccion',
                                       'articuloTienda:id_articulo,nombre,imagen_url'
                                   ]);

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        $historial = $query->orderBy('created_at', 'desc')->get();

        return response()->json($historial, 200);
    }

    /**
     * Ver TODOS mis artículos canjeados (pendientes y completados)
     */
    public function misCanjes()
    {
        /** @var User $cliente */
        $cliente = Auth::user();

        $canjes = TransaccionPuntos::where('user_id_cliente', $cliente->id_usuario)
                                   ->where('tipo', 'canjeado')
                                   ->with([
                                       'articuloTienda:id_articulo,nombre,descripcion,imagen_url',
                                       'puntoAcopio:id_acopio,nombre_lugar,direccion,ubicacion_gps'
                                   ])
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return response()->json($canjes, 200);
    }

    /**
     * Ver artículos canjeados pendientes de recoger
     */
    public function misCanjesPendientes()
    {
        /** @var User $cliente */
        $cliente = Auth::user();

        $canjes = TransaccionPuntos::where('user_id_cliente', $cliente->id_usuario)
                                   ->where('tipo', 'canjeado')
                                   ->where('status', 'pendiente_recojo')
                                   ->with([
                                       'articuloTienda:id_articulo,nombre,descripcion,imagen_url',
                                       'puntoAcopio:id_acopio,nombre_lugar,direccion,ubicacion_gps'
                                   ])
                                   ->orderBy('created_at', 'desc')
                                   ->get();

        return response()->json($canjes, 200);
    }

    /**
     * Ver mapa de puntos de acopio activos
     */
    public function puntosAcopioMapa()
    {
        $acopios = PuntoAcopio::where('estado', 'aprobado')
                              ->with('recolector:id_usuario,nombre,apellido')
                              ->select('id_acopio', 'nombre_lugar', 'direccion', 'ubicacion_gps', 'user_id_recolector')
                              ->get();

        return response()->json($acopios, 200);
    }

    /**
     * Ver historial de RESIDUOS reclamados por el cliente
     */
    public function misResiduos(Request $request)
    {
        /** @var User $cliente */
        $cliente = Auth::user();

        $tipoFiltro = $request->query('tipo'); // Filtro opcional por tipo

        $query = Residuo::where('user_id_cliente', $cliente->id_usuario)
                        ->where('estado', 'reclamado')
                        ->with([
                            'recolector:id_usuario,nombre,apellido',
                            'puntoAcopio:id_acopio,nombre_lugar,direccion'
                        ]);

        if ($tipoFiltro) {
            $query->where('tipo_residuo', $tipoFiltro);
        }

        $residuos = $query->orderBy('fecha_registro', 'desc')->get();

        // Estadísticas de residuos
        $stats = [
            'total_residuos_reclamados' => $residuos->count(),
            'total_puntos_ganados' => $residuos->sum('puntos_otorgados'),
            'total_kg_reciclados' => round($residuos->sum('cantidad_kg'), 2),
            'por_tipo' => Residuo::where('user_id_cliente', $cliente->id_usuario)
                                 ->where('estado', 'reclamado')
                                 ->select('tipo_residuo', 
                                         DB::raw('COUNT(*) as cantidad'),
                                         DB::raw('SUM(cantidad_kg) as total_kg'),
                                         DB::raw('SUM(puntos_otorgados) as total_puntos'))
                                 ->groupBy('tipo_residuo')
                                 ->get(),
        ];

        return response()->json([
            'estadisticas' => $stats,
            'residuos' => $residuos,
        ], 200);
    }
}
