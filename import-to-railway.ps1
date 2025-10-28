# Script para importar datos a Railway MySQL
# Usamos mysql nativo con configuración específica

$host = "caboose.proxy.rlwy.net"
$port = "32702"
$user = "root"
$password = "jhSisDSGDPOxzWGBJTjDYDGozqcvFSWU"
$database = "railway"
$backupFile = "ecopuntos_backup.sql"

Write-Host "Importando $backupFile a Railway MySQL..." -ForegroundColor Green

# Método 1: Usar mysql con default-auth
& "C:\xampp\mysql\bin\mysql.exe" `
    --host=$host `
    --port=$port `
    --user=$user `
    --password=$password `
    --database=$database `
    --default-auth=mysql_native_password `
    --execute="source $backupFile"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Importación exitosa!" -ForegroundColor Green
} else {
    Write-Host "✗ Error en la importación. Intentando método alternativo..." -ForegroundColor Yellow
    
    # Método 2: Leer archivo y ejecutar
    Get-Content $backupFile | & "C:\xampp\mysql\bin\mysql.exe" `
        --host=$host `
        --port=$port `
        --user=$user `
        --password=$password `
        --database=$database `
        --default-auth=mysql_native_password
}
