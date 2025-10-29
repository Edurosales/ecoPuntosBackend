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

// ⚠️ RUTA TEMPORAL PARA SETUP - ELIMINAR DESPUÉS
Route::get('/setup-migrations-temp-123456', function () {
    try {
        $results = [];
        
        // Paso 1: Verificar conexión a BD
        $results['db_connected'] = DB::connection()->getPdo() ? true : false;
        
        // Paso 2: Ejecutar migraciones
        try {
            Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $output = Illuminate\Support\Facades\Artisan::output();
            $results['migrations'] = 'Ejecutadas exitosamente';
            $results['migration_output'] = $output;
        } catch (\Exception $e) {
            $results['migrations_error'] = $e->getMessage();
        }
        
        // Paso 3: Ejecutar seeder de tipos de residuo
        try {
            Illuminate\Support\Facades\Artisan::call('db:seed', [
                '--class' => 'TiposResiduoSeeder',
                '--force' => true
            ]);
            $results['seeders'] = 'Ejecutados exitosamente';
        } catch (\Exception $e) {
            $results['seeders_error'] = $e->getMessage();
        }
        
        // Paso 4: Verificar tablas creadas
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        $results['tables_created'] = array_map(fn($t) => $t->table_name, $tables);
        
        // Paso 5: Verificar si ya existe el admin
        $existingAdmin = DB::table('users')
            ->where('email', 'admin@ecopuntos.com')
            ->first();
        
        if ($existingAdmin) {
            $results['admin_status'] = 'Ya existe';
            $results['admin'] = [
                'id' => $existingAdmin->id_usuario,
                'nombre' => $existingAdmin->nombre,
                'email' => $existingAdmin->email,
                'rol' => $existingAdmin->rol
            ];
        } else {
            // Crear usuario admin
            try {
                $adminId = DB::table('users')->insertGetId([
                    'nombre' => 'Admin',
                    'apellido' => 'System',
                    'dni' => '00000000',
                    'email' => 'admin@ecopuntos.com',
                    'password' => Hash::make('admin123'),
                    'puntos' => 0,
                    'preferencia_tema' => 'light',
                    'rol' => 'admin',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $results['admin_created'] = true;
                $results['admin_id'] = $adminId;
                $results['credentials'] = [
                    'email' => 'admin@ecopuntos.com',
                    'password' => 'admin123'
                ];
            } catch (\Exception $e) {
                $results['admin_error'] = $e->getMessage();
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => '✅ Setup completado',
            'results' => $results,
            'warning' => '⚠️ Elimina esta ruta de routes/api.php después del setup'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// --- RUTAS PÚBLICAS ---
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Catálogos públicos
Route::get('tipos-residuos', function () {
    return response()->json(\App\Models\TipoResiduo::where('activo', true)->orderBy('nombre')->get());
});

// Endpoint público: TODOS los puntos de acopio aprobados SIN relación (más simple)
Route::get('public/puntos-acopio', function () {
    $puntos = \App\Models\PuntoAcopio::where('estado', 'aprobado')
        ->orderBy('nombre_lugar')
        ->get();
    
    // Cargar recolector manualmente para evitar errores
    foreach ($puntos as $punto) {
        $punto->recolector = \App\Models\User::find($punto->user_id_recolector);
    }
    
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