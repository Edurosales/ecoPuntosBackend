<?php
// Script para importar backup a Railway MySQL

$host = "caboose.proxy.rlwy.net";
$port = "32702";
$user = "root";
$password = "jhSisDSGDPOxzWGBJTjDYDGozqcvFSWU";
$database = "railway";
$backupFile = "ecopuntos_backup.sql";

echo "Conectando a Railway MySQL...\n";

try {
    // Crear conexión
    $mysqli = new mysqli($host, $user, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        die("✗ Error de conexión: " . $mysqli->connect_error . "\n");
    }
    
    echo "✓ Conectado exitosamente\n";
    echo "Importando $backupFile...\n";
    
    // Leer archivo SQL
    $sql = file_get_contents($backupFile);
    
    if ($sql === false) {
        die("✗ Error al leer el archivo de backup\n");
    }
    
    // Ejecutar SQL (dividir por statements)
    $mysqli->multi_query($sql);
    
    // Consumir todos los resultados
    do {
        if ($result = $mysqli->store_result()) {
            $result->free();
        }
    } while ($mysqli->more_results() && $mysqli->next_result());
    
    if ($mysqli->error) {
        echo "✗ Error durante la importación: " . $mysqli->error . "\n";
    } else {
        echo "✓ ¡Importación completada exitosamente!\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "✗ Excepción: " . $e->getMessage() . "\n";
}
?>
