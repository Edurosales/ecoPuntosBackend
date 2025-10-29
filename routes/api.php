<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PuntoAcopioController;
use App\Http\Controllers\Api\ArticuloTiendaController;
use App\Http\Controllers\Api\TransaccionController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\RecolectorController;
use App\Http\Controllers\Api\ClienteController;
use App\Http\Controllers\Api\PerfilController;

// --- RUTAS PÚBLICAS ---
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Catálogos públicos
Route::get('tipos-residuos', function () {
    return response()->json(\App\Models\TipoResiduo::where('activo', true)->orderBy('nombre')->get());
});

// Endpoint público: TODOS los puntos de acopio aprobados CON relación recolector
Route::get('public/puntos-acopio', function () {
    $puntos = \App\Models\PuntoAcopio::where('estado', 'aprobado')
        ->with('recolector:id_usuario,nombre,apellido')
        ->select('id_acopio', 'nombre_lugar', 'direccion', 'departamento', 'provincia', 'distrito', 'ubicacion_gps', 'user_id_recolector', 'estado')
        ->orderBy('nombre_lugar')
        ->get();
    
    return response()->json($puntos, 200);
});

// Endpoint antiguo (mantenerlo por compatibilidad)
Route::get('puntos-acopio', function () {
    return response()->json(\App\Models\PuntoAcopio::where('estado', 'aprobado')->orderBy('nombre_lugar')->get());
});

// --- RUTAS PROTEGIDAS (PARA CUALQUIER USUARIO LOGUEADO) ---
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // RUTA DE DIAGNÓSTICO TEMPORAL
    Route::get('/debug-user', function (Request $request) {
        $user = $request->user();
        return response()->json([
            'authenticated' => !!$user,
            'user' => $user,
            'rol' => $user->rol ?? null,
            'is_admin' => $user && $user->rol === 'admin',
        ]);
    });

    // RUTA TEMPORAL PARA ARREGLAR ROL DE ADMIN
    Route::post('/fix-admin-role-temp-999', function (Request $request) {
        $adminUser = \App\Models\User::where('email', 'admin@ecopuntos.com')->first();
        
        if (!$adminUser) {
            return response()->json(['error' => 'Usuario admin no encontrado'], 404);
        }
        
        $adminUser->rol = 'admin';
        $adminUser->save();
        
        return response()->json([
            'message' => 'Rol actualizado exitosamente',
            'user' => $adminUser,
        ]);
    });

    // Cerrar sesión
    Route::post('logout', [AuthController::class, 'logout']);

    // ==================== RUTAS DE PERFIL (TODOS LOS USUARIOS) ====================
    Route::prefix('perfil')->group(function () {
        Route::get('/', [PerfilController::class, 'show']);
        Route::put('/', [PerfilController::class, 'update']);
        Route::patch('tema', [PerfilController::class, 'cambiarTema']);
        Route::patch('password', [PerfilController::class, 'cambiarPassword']);
    });

    // Solicitar ser recolector (cualquier usuario logueado)
    Route::post('acopios', [PuntoAcopioController::class, 'store']); 

    // Ver catálogo de artículos (todos los usuarios)
    Route::get('articulos', [ArticuloTiendaController::class, 'index']);

    // Rutas para clientes: reclamar puntos y canjear artículos
    Route::post('transacciones/reclamar', [TransaccionController::class, 'reclamar']);
    Route::post('transacciones/canjear', [TransaccionController::class, 'canjear']);


    // ==================== RUTAS DE CLIENTE ====================
    Route::prefix('cliente')->group(function () {
        Route::get('puntos', [ClienteController::class, 'misPuntos']);
        Route::get('historial', [ClienteController::class, 'miHistorial']);
        Route::get('mis-canjes', [ClienteController::class, 'misCanjes']);
        Route::get('canjes-pendientes', [ClienteController::class, 'misCanjesPendientes']);
        Route::get('puntos-acopio', [ClienteController::class, 'puntosAcopioMapa']);
        Route::get('mis-residuos', [ClienteController::class, 'misResiduos']); // NUEVO
    });


    // ==================== RUTAS DE ADMIN ====================
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        
        // Dashboard y estadísticas
        Route::get('dashboard', [AdminController::class, 'dashboard']);
        
        // Gestión de usuarios
        Route::get('usuarios', [AdminController::class, 'indexUsuarios']);
        Route::get('usuarios/{id}', [AdminController::class, 'showUsuario']);
        Route::put('usuarios/{id}', [AdminController::class, 'updateUsuario']);
        Route::delete('usuarios/{id}', [AdminController::class, 'destroyUsuario']);
        
        // Gestión de puntos de acopio
        Route::get('acopios', [AdminController::class, 'indexAcopios']);
        Route::get('acopios/pendientes', [PuntoAcopioController::class, 'indexPendientes']);
        Route::patch('acopios/{id}/approve', [PuntoAcopioController::class, 'approve']);
        Route::put('acopios/{id}', [AdminController::class, 'updateAcopio']);
        Route::delete('acopios/{id}', [AdminController::class, 'destroyAcopio']);
        
        // Gestión de artículos de tienda
        Route::post('articulos', [ArticuloTiendaController::class, 'store']);
        Route::put('articulos/{id}', [ArticuloTiendaController::class, 'update']);
        Route::delete('articulos/{id}', [ArticuloTiendaController::class, 'destroy']);
        
        // Gestión de tipos de residuo (NUEVAS)
        Route::get('tipos-residuo', [AdminController::class, 'indexTiposResiduo']);
        Route::post('tipos-residuo', [AdminController::class, 'storeTipoResiduo']);
        Route::put('tipos-residuo/{id}', [AdminController::class, 'updateTipoResiduo']);
        Route::delete('tipos-residuo/{id}', [AdminController::class, 'destroyTipoResiduo']);
        
        // Gestión de transacciones
        Route::get('transacciones', [AdminController::class, 'indexTransacciones']);
    });


    // ==================== RUTAS DE RECOLECTOR ====================
    Route::middleware('role:recolector')->prefix('recolector')->group(function () {
        
        // Ver mis estadísticas
        Route::get('puntos', [RecolectorController::class, 'misPuntos']);
        Route::get('qrs', [RecolectorController::class, 'misQRs']);
        Route::get('canjes-pendientes', [RecolectorController::class, 'canjesPendientes']);
        Route::get('canjes-completados', [RecolectorController::class, 'canjesCompletados']);
        Route::get('residuos-recibidos', [RecolectorController::class, 'residuosRecibidos']); // NUEVO
        
        // Generar QR (registrar residuo)
        Route::post('transacciones', [TransaccionController::class, 'store']);
        
        // Marcar artículo como entregado
        Route::patch('transacciones/{id}/entregar', [TransaccionController::class, 'entregar']);
    });

});