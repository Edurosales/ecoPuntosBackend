<?php

require __DIR__.'/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'caboose.proxy.rlwy.net',
    'port' => '32702',
    'database' => 'railway',
    'username' => 'root',
    'password' => 'jhSisDSGDPOxzWGBJTjDYDGozqcvFSWU',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Conectando a Railway MySQL...\n";

try {
    $pdo = $capsule->getConnection()->getPdo();
    echo "✓ Conectado exitosamente\n";
    echo "Importando ecopuntos_backup.sql...\n\n";
    
    // Leer el archivo SQL
    $sql = file_get_contents('ecopuntos_backup.sql');
    
    // Dividir por líneas y ejecutar
    $statements = explode(';', $sql);
    $count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $count++;
            
            if ($count % 10 === 0) {
                echo "Procesadas $count sentencias...\n";
            }
        } catch (Exception $e) {
            // Ignorar errores menores como "table already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Advertencia: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n✓ ¡Importación completada! ($count sentencias ejecutadas)\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
