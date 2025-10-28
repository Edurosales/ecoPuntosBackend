<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return response()->json([
        'message' => 'EcoPuntos API - Backend Laravel',
        'status' => 'online',
        'version' => '1.0.0',
        'database' => DB::connection()->getDatabaseName(),
        'tables_count' => count(DB::select('SELECT table_name FROM information_schema.tables WHERE table_schema = \'public\''))
    ]);
});

// Ruta de diagnóstico
Route::get('/diagnostico', function () {
    try {
        $dbConnection = DB::connection()->getPdo();
        $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'");
        
        return response()->json([
            'success' => true,
            'database_connected' => true,
            'database_name' => DB::connection()->getDatabaseName(),
            'tables' => array_map(fn($t) => $t->table_name, $tables),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => config('app.debug') ? $e->getTraceAsString() : 'Debug disabled'
        ], 500);
    }
});

// ⚠️ RUTA TEMPORAL PARA SETUP - ELIMINAR DESPUÉS
Route::get('/setup-admin-temp-987654321', function () {
    try {
        $results = [];
        
        // Paso 1: Verificar conexión a BD
        $results['db_connected'] = DB::connection()->getPdo() ? true : false;
        
        // Paso 2: Ejecutar migraciones
        try {
            Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $results['migrations'] = 'Ejecutadas exitosamente';
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
        
        // Verificar si ya existe el admin
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
                    'email' => 'admin@ecopuntos.com',
                    'password' => Hash::make('admin123'),
                    'rol' => 'admin',
                    'direccion' => 'Sistema',
                    'telefono' => '999999999',
                    'puntos_acumulados' => 0,
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
            'message' => '✅ Setup ejecutado',
            'results' => $results,
            'warning' => '⚠️ Elimina esta ruta después del setup'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
});
