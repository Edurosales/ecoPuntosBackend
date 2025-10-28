<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

Route::get('/', function () {
    return response()->json([
        'message' => 'EcoPuntos API - Backend Laravel',
        'status' => 'online',
        'version' => '1.0.0'
    ]);
});

// ⚠️ RUTA TEMPORAL PARA SETUP - ELIMINAR DESPUÉS
Route::get('/setup-admin-temp-987654321', function () {
    try {
        // Ejecutar migraciones primero
        \Artisan::call('migrate', ['--force' => true]);
        
        // Ejecutar seeder de tipos de residuo
        \Artisan::call('db:seed', [
            '--class' => 'TiposResiduoSeeder',
            '--force' => true
        ]);
        
        // Verificar si ya existe el admin
        $existingAdmin = DB::table('users')
            ->where('email', 'admin@ecopuntos.com')
            ->first();
        
        if ($existingAdmin) {
            return response()->json([
                'message' => 'Setup completado - Admin ya existe',
                'admin' => [
                    'id' => $existingAdmin->id_usuario,
                    'nombre' => $existingAdmin->nombre,
                    'email' => $existingAdmin->email,
                    'rol' => $existingAdmin->rol
                ]
            ]);
        }
        
        // Crear usuario admin
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
        
        return response()->json([
            'success' => true,
            'message' => '✅ Setup completado exitosamente',
            'migrations' => 'Ejecutadas',
            'seeders' => 'Ejecutados',
            'admin_created' => true,
            'credentials' => [
                'email' => 'admin@ecopuntos.com',
                'password' => 'admin123',
                'id' => $adminId
            ],
            'warning' => '⚠️ IMPORTANTE: Elimina esta ruta de routes/web.php después del setup'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});
