<?php

namespace App\Http\Controllers\Api;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TransaccionPuntos;
use App\Models\ArticuloTienda;
use App\Models\User;
use App\Models\Residuo;
use App\Models\TipoResiduo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TransaccionController extends Controller
{

    /**
     * RECOLECTOR: Registra un residuo y genera un código único.
     * Formato: 2 iniciales nombre + 2 apellido + 2 dígitos fecha + 4 aleatorios
     * Ejemplo: JUPE261234 (Juan Pérez, día 26, números aleatorios 1234)
     */
    public function store(Request $request)
    {
        // 1. Validar la entrada
        $validator = Validator::make($request->all(), [
            'tipo_residuo' => 'required|string|exists:tipos_residuo,nombre',
            'cantidad_kg' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // 2. Obtener al recolector logueado y su punto de acopio
        /** @var User $recolector */
        $recolector = Auth::user();
        $puntoAcopio = $recolector->puntoAcopio; 

        if (!$puntoAcopio) {
            return response()->json(['message' => 'No tienes un punto de acopio asignado.'], 403);
        }

        // 3. Obtener el tipo de residuo y calcular puntos automáticamente
        $tipoResiduo = TipoResiduo::where('nombre', $request->tipo_residuo)
                                  ->where('activo', true)
                                  ->first();

        if (!$tipoResiduo) {
            return response()->json(['message' => 'Tipo de residuo no válido o inactivo.'], 400);
        }

        // Calcular puntos: cantidad_kg * puntos_por_kg
        $puntosCalculados = round($request->cantidad_kg * $tipoResiduo->puntos_por_kg);

        // 4. Generar código único personalizado
        // Formato: 2 letras nombre + 2 letras apellido + 2 dígitos día + 4 números aleatorios
        $nombre = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $recolector->nombre), 0, 2));
        $apellido = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $recolector->apellido), 0, 2));
        $dia = date('d'); // Día actual (01-31)
        $aleatorio = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT); // 4 dígitos aleatorios
        
        $codigoQR = $nombre . $apellido . $dia . $aleatorio;

        // Verificar que sea único (por si acaso)
        while (Residuo::where('codigo_qr', $codigoQR)->exists()) {
            $aleatorio = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $codigoQR = $nombre . $apellido . $dia . $aleatorio;
        }

        // 5. Crear el registro de residuo
        $residuo = Residuo::create([
            'tipo_residuo'       => $request->tipo_residuo,
            'cantidad_kg'        => $request->cantidad_kg,
            'puntos_otorgados'   => $puntosCalculados,
            'fecha_registro'     => now(),
            'user_id_recolector' => $recolector->id_usuario,
            'punto_acopio_id'    => $puntoAcopio->id_acopio,
            'codigo_qr'          => $codigoQR,
            'estado'             => 'disponible',
            'user_id_cliente'    => null,
        ]);

        // 6. Devolver el CÓDIGO (no QR, el front lo convierte)
        return response()->json([
            'message'      => 'Residuo registrado. Código generado.',
            'codigo'       => $codigoQR,
            'residuo'      => [
                'id' => $residuo->id_residuo,
                'tipo' => $residuo->tipo_residuo,
                'cantidad_kg' => $residuo->cantidad_kg,
                'puntos' => $residuo->puntos_otorgados,
                'precio_por_kg' => $tipoResiduo->puntos_por_kg,
            ],
        ], 201);
    }


    public function reclamar(Request $request)
    {
        // 1. Validar que nos envíen el CÓDIGO (el front escanea QR y extrae el código)
        $validator = Validator::make($request->all(), [
            'codigo' => 'required|string|max:12',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $codigo = $request->codigo;
        /** @var User $cliente */
        $cliente = Auth::user();

        // 2. Usar transacción de BD para seguridad
        try {
            DB::beginTransaction();

            // 3. Buscar el residuo disponible (con bloqueo)
            $residuo = Residuo::where('codigo_qr', $codigo)
                              ->where('estado', 'disponible')
                              ->lockForUpdate()
                              ->first();

            // 4. Verificar si el código es válido
            if (!$residuo) {
                DB::rollBack();
                return response()->json(['message' => 'Código no válido o ya fue reclamado.'], 404);
            }

            // 5. Marcar el residuo como reclamado
            $residuo->estado = 'reclamado';
            $residuo->user_id_cliente = $cliente->id_usuario;
            $residuo->save();

            // 6. Actualizar los puntos del cliente
            $cliente->puntos += $residuo->puntos_otorgados;
            $cliente->save();

            // 7. Crear transacción de ganancia de puntos
            TransaccionPuntos::create([
                'tipo'               => 'ganado',
                'puntos'             => $residuo->puntos_otorgados,
                'status'             => 'completada',
                'codigo_reclamacion' => $codigo,
                'user_id_recolector' => $residuo->user_id_recolector,
                'user_id_cliente'    => $cliente->id_usuario,
                'punto_acopio_id'    => $residuo->punto_acopio_id,
                'articulo_id'        => null,
            ]);

            // 8. Confirmar todos los cambios
            DB::commit();

            return response()->json([
                'message' => '¡Éxito! Has ganado ' . $residuo->puntos_otorgados . ' puntos.',
                'puntos_ganados' => $residuo->puntos_otorgados,
                'nuevos_puntos_totales' => $cliente->puntos,
                'tipo_residuo' => $residuo->tipo_residuo,
                'cantidad_kg' => $residuo->cantidad_kg,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ocurrió un error al procesar la reclamación.'], 500);
        }
    }


    /**
     * CLIENTE: Canjea puntos por un artículo de la tienda.
     */
    public function canjear(Request $request)
    {
        // 1. Validar que nos envíen el artículo y el acopio donde recogerá
        $validator = Validator::make($request->all(), [
            'articulo_id' => 'required|integer|exists:articulo_tiendas,id_articulo',
            'punto_acopio_id' => 'required|integer|exists:punto_acopios,id_acopio',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        /** @var User $cliente */
        $cliente = Auth::user();

        // 2. Usar transacción de BD para seguridad
        try {
            DB::beginTransaction();

            // 3. Obtener el artículo con bloqueo
            $articulo = ArticuloTienda::where('id_articulo', $request->articulo_id)
                                      ->lockForUpdate()
                                      ->first();

            if (!$articulo) {
                DB::rollBack();
                return response()->json(['message' => 'Artículo no encontrado.'], 404);
            }

            // 4. Validar: ¿El cliente tiene suficientes puntos?
            if ($cliente->puntos < $articulo->puntos_requeridos) {
                DB::rollBack();
                return response()->json([
                    'message' => 'No tienes suficientes puntos para canjear este artículo.',
                    'puntos_actuales' => $cliente->puntos,
                    'puntos_requeridos' => $articulo->puntos_requeridos
                ], 400);
            }

            // 5. Validar: ¿Hay stock disponible?
            if ($articulo->stock <= 0) {
                DB::rollBack();
                return response()->json(['message' => 'Artículo sin stock disponible.'], 400);
            }

            // 6. Restar puntos al cliente
            $cliente->puntos -= $articulo->puntos_requeridos;
            $cliente->save();

            // 7. Restar stock del artículo
            $articulo->stock -= 1;
            $articulo->save();

            // 8. Crear la transacción de canje
            $transaccion = TransaccionPuntos::create([
                'tipo'               => 'canjeado',
                'puntos'             => -$articulo->puntos_requeridos, // Valor negativo
                'status'             => 'pendiente_recojo',
                'codigo_reclamacion' => null,
                'user_id_recolector' => null,
                'user_id_cliente'    => $cliente->id_usuario,
                'punto_acopio_id'    => $request->punto_acopio_id,
                'articulo_id'        => $articulo->id_articulo,
            ]);

            // 9. Confirmar todos los cambios
            DB::commit();

            return response()->json([
                'message' => '¡Canje exitoso! Puedes recoger tu artículo en el punto de acopio.',
                'codigo_recojo' => $transaccion->id_transaccion,
                'puntos_restantes' => $cliente->puntos,
                'articulo' => $articulo->nombre
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Ocurrió un error al procesar el canje.'], 500);
        }
    }


    /**
     * RECOLECTOR: Marca un canje como entregado.
     */
    public function entregar(Request $request, $id)
    {
        // 1. Buscar la transacción por ID
        $transaccion = TransaccionPuntos::find($id);

        if (!$transaccion) {
            return response()->json(['message' => 'Transacción no encontrada.'], 404);
        }

        // 2. Validar: ¿Es una transacción de canje pendiente de recojo?
        if ($transaccion->status !== 'pendiente_recojo') {
            return response()->json([
                'message' => 'Esta transacción no está pendiente de recojo.',
                'status_actual' => $transaccion->status
            ], 400);
        }

        // 3. Obtener el recolector logueado y su punto de acopio
        $recolector = Auth::user();
        $puntoAcopio = $recolector->puntoAcopio;

        if (!$puntoAcopio) {
            return response()->json(['message' => 'No tienes un punto de acopio asignado.'], 403);
        }

        // 4. Validar: ¿El acopio de la transacción coincide con el del recolector?
        if ($transaccion->punto_acopio_id !== $puntoAcopio->id_acopio) {
            return response()->json([
                'message' => 'Este canje no pertenece a tu punto de acopio.',
            ], 403);
        }

        // 5. Marcar como completada
        $transaccion->status = 'completada';
        $transaccion->save();

        return response()->json([
            'message' => '¡Artículo entregado exitosamente!',
            'transaccion' => $transaccion
        ], 200);
    }
}