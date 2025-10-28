# =============================================================================
# GUÍA DE PRUEBAS MANUALES - ecoPuntos API
# Copia y pega cada comando en PowerShell (con el servidor corriendo)
# =============================================================================

# IMPORTANTE: Primero ejecuta en OTRO terminal:
# cd C:\Users\eduv0\OneDrive\Desktop\cursoUWeb\ecoPuntosBackend
# php artisan serve

# Luego ejecuta estos comandos UNO POR UNO:

$base = "http://127.0.0.1:8000/api"

# =============================================================================
# 1. REGISTRAR ADMIN
# =============================================================================
Invoke-RestMethod -Uri "$base/register" -Method POST -Body (@{
    nombre = "Carlos"
    apellido = "Rodriguez"
    dni = "11111111"
    email = "admin@ecopuntos.com"
    password = "admin123"
    password_confirmation = "admin123"
    rol = "admin"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"} | ConvertTo-Json -Depth 10

# =============================================================================
# 2. REGISTRAR RECOLECTOR
# =============================================================================
Invoke-RestMethod -Uri "$base/register" -Method POST -Body (@{
    nombre = "Juan"
    apellido = "Perez"
    dni = "22222222"
    email = "recolector@ecopuntos.com"
    password = "reco123"
    password_confirmation = "reco123"
    rol = "recolector"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"} | ConvertTo-Json -Depth 10

# =============================================================================
# 3. REGISTRAR CLIENTE
# =============================================================================
Invoke-RestMethod -Uri "$base/register" -Method POST -Body (@{
    nombre = "Maria"
    apellido = "Garcia"
    dni = "33333333"
    email = "cliente@ecopuntos.com"
    password = "cliente123"
    password_confirmation = "cliente123"
    rol = "cliente"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"} | ConvertTo-Json -Depth 10

# =============================================================================
# 4. LOGIN ADMIN
# =============================================================================
$adminToken = (Invoke-RestMethod -Uri "$base/login" -Method POST -Body (@{
    email = "admin@ecopuntos.com"
    password = "admin123"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"}).token

Write-Host "Token Admin: $adminToken" -ForegroundColor Green

# =============================================================================
# 5. LOGIN RECOLECTOR
# =============================================================================
$recoToken = (Invoke-RestMethod -Uri "$base/login" -Method POST -Body (@{
    email = "recolector@ecopuntos.com"
    password = "reco123"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"}).token

Write-Host "Token Recolector: $recoToken" -ForegroundColor Green

# =============================================================================
# 6. LOGIN CLIENTE
# =============================================================================
$clienteToken = (Invoke-RestMethod -Uri "$base/login" -Method POST -Body (@{
    email = "cliente@ecopuntos.com"
    password = "cliente123"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"}).token

Write-Host "Token Cliente: $clienteToken" -ForegroundColor Green

# =============================================================================
# 7. RECOLECTOR: SOLICITAR PUNTO DE ACOPIO
# =============================================================================
$acopio = Invoke-RestMethod -Uri "$base/acopios" -Method POST -Body (@{
    nombre_lugar = "Acopio Central Lima"
    direccion = "Av. Principal 123, Lima"
    ubicacion_gps = "-12.0464,-77.0428"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"; "Authorization"="Bearer $recoToken"}

Write-Host "ID Acopio creado: $($acopio.punto_acopio.id_acopio)" -ForegroundColor Yellow
$acopioId = $acopio.punto_acopio.id_acopio

# =============================================================================
# 8. ADMIN: APROBAR PUNTO DE ACOPIO
# =============================================================================
Invoke-RestMethod -Uri "$base/admin/acopios/$acopioId/approve" -Method PATCH -Body (@{
    estado = "aprobado"
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"; "Authorization"="Bearer $adminToken"} | ConvertTo-Json -Depth 10

# =============================================================================
# 9. ADMIN: VER TIPOS DE RESIDUO
# =============================================================================
$tipos = Invoke-RestMethod -Uri "$base/admin/tipos-residuo" -Method GET -Headers @{"Authorization"="Bearer $adminToken"}
$tipos | Select-Object nombre, puntos_por_kg, activo | Format-Table

# =============================================================================
# 10. ADMIN: ACTUALIZAR PRECIO DEL PLÁSTICO
# =============================================================================
$plasticoId = ($tipos | Where-Object { $_.nombre -eq "Plástico" }).id_tipo
Invoke-RestMethod -Uri "$base/admin/tipos-residuo/$plasticoId" -Method PUT -Body (@{
    puntos_por_kg = 18.50
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"; "Authorization"="Bearer $adminToken"} | ConvertTo-Json -Depth 10

# =============================================================================
# 11. RECOLECTOR: REGISTRAR RESIDUO (CON PRECIO ACTUALIZADO)
# =============================================================================
$residuo = Invoke-RestMethod -Uri "$base/recolector/transacciones" -Method POST -Body (@{
    tipo_residuo = "Plástico"
    cantidad_kg = 2.5
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"; "Authorization"="Bearer $recoToken"}

Write-Host "`n=== RESIDUO REGISTRADO ===" -ForegroundColor Cyan
Write-Host "Código QR: $($residuo.codigo)" -ForegroundColor Yellow
Write-Host "Tipo: $($residuo.residuo.tipo)" -ForegroundColor White
Write-Host "Cantidad: $($residuo.residuo.cantidad_kg) kg" -ForegroundColor White
Write-Host "Puntos calculados: $($residuo.residuo.puntos)" -ForegroundColor White
Write-Host "Precio por kg: $($residuo.residuo.precio_por_kg)" -ForegroundColor White

$codigoQR = $residuo.codigo

# =============================================================================
# 12. CLIENTE: ESCANEAR QR Y RECLAMAR PUNTOS
# =============================================================================
$reclamo = Invoke-RestMethod -Uri "$base/transacciones/reclamar" -Method POST -Body (@{
    codigo = $codigoQR
} | ConvertTo-Json) -Headers @{"Content-Type"="application/json"; "Authorization"="Bearer $clienteToken"}

Write-Host "`n=== PUNTOS RECLAMADOS ===" -ForegroundColor Green
Write-Host $reclamo.message -ForegroundColor White
Write-Host "Puntos totales: $($reclamo.nuevos_puntos_totales)" -ForegroundColor Yellow
Write-Host "Tipo residuo: $($reclamo.tipo_residuo)" -ForegroundColor White
Write-Host "Cantidad: $($reclamo.cantidad_kg) kg" -ForegroundColor White

# =============================================================================
# 13. INTENTAR ESCANEAR EL MISMO CÓDIGO (DEBE FALLAR)
# =============================================================================
Write-Host "`nIntentando escanear el mismo código de nuevo..." -ForegroundColor Yellow
try {
    Invoke-RestMethod -Uri "$base/transacciones/reclamar" -Method POST -Body (@{
        codigo = $codigoQR
    } | ConvertTo-Json) -Headers @{"Content-Type"="application/json"; "Authorization"="Bearer $clienteToken"}
} catch {
    Write-Host "ERROR (esperado): Código no válido o ya fue reclamado" -ForegroundColor Red
}

# =============================================================================
# 14. CLIENTE: VER MIS RESIDUOS
# =============================================================================
$misResiduos = Invoke-RestMethod -Uri "$base/cliente/mis-residuos" -Method GET -Headers @{"Authorization"="Bearer $clienteToken"}
Write-Host "`n=== MIS RESIDUOS (CLIENTE) ===" -ForegroundColor Cyan
Write-Host "Total residuos: $($misResiduos.estadisticas.total_residuos_reclamados)" -ForegroundColor White
Write-Host "Total puntos: $($misResiduos.estadisticas.total_puntos_ganados)" -ForegroundColor White
Write-Host "Total kg: $($misResiduos.estadisticas.total_kg_reciclados)" -ForegroundColor White

# =============================================================================
# 15. RECOLECTOR: VER RESIDUOS RECIBIDOS
# =============================================================================
$residuosReco = Invoke-RestMethod -Uri "$base/recolector/residuos-recibidos" -Method GET -Headers @{"Authorization"="Bearer $recoToken"}
Write-Host "`n=== RESIDUOS RECIBIDOS (RECOLECTOR) ===" -ForegroundColor Cyan
$residuosReco.precios_actuales | Select-Object nombre, puntos_por_kg | Format-Table

# =============================================================================
# 16. ADMIN: VER DASHBOARD
# =============================================================================
$dashboard = Invoke-RestMethod -Uri "$base/admin/dashboard" -Method GET -Headers @{"Authorization"="Bearer $adminToken"}
Write-Host "`n=== DASHBOARD (ADMIN) ===" -ForegroundColor Cyan
Write-Host "Total usuarios: $($dashboard.total_usuarios)" -ForegroundColor White
Write-Host "Total residuos: $($dashboard.total_residuos_registrados)" -ForegroundColor White
Write-Host "Total kg reciclados: $($dashboard.total_kg_reciclados)" -ForegroundColor White
Write-Host "Total puntos distribuidos: $($dashboard.total_puntos_distribuidos)" -ForegroundColor White

Write-Host "`n=== ✓ PRUEBAS COMPLETADAS ===" -ForegroundColor Green
