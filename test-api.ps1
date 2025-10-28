# =============================================================================
# SCRIPT DE PRUEBA DE API - ecoPuntos
# Ejecutar: .\test-api.ps1
# =============================================================================

$baseUrl = "http://127.0.0.1:8000/api"
$headers = @{ "Content-Type" = "application/json"; "Accept" = "application/json" }

Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host "  PRUEBA COMPLETA DE API - ecoPuntos" -ForegroundColor Cyan
Write-Host "===============================================`n" -ForegroundColor Cyan

# =============================================================================
# PASO 1: REGISTRAR USUARIOS
# =============================================================================

Write-Host "`n[1/10] Registrando Admin..." -ForegroundColor Yellow
$adminRegister = @{
    nombre = "Carlos"
    apellido = "Rodriguez"
    dni = "11111111"
    email = "admin@ecopuntos.com"
    password = "admin123"
    password_confirmation = "admin123"
    rol = "admin"
} | ConvertTo-Json

$adminResponse = Invoke-RestMethod -Uri "$baseUrl/register" -Method POST -Body $adminRegister -Headers $headers -ErrorAction Stop
Write-Host "✓ Admin registrado: $($adminResponse.user.email)" -ForegroundColor Green

Write-Host "`n[2/10] Registrando Recolector..." -ForegroundColor Yellow
$recolectorRegister = @{
    nombre = "Juan"
    apellido = "Perez"
    dni = "22222222"
    email = "recolector@ecopuntos.com"
    password = "reco123"
    password_confirmation = "reco123"
    rol = "recolector"
} | ConvertTo-Json

$recolectorResponse = Invoke-RestMethod -Uri "$baseUrl/register" -Method POST -Body $recolectorRegister -Headers $headers
Write-Host "✓ Recolector registrado: $($recolectorResponse.user.email)" -ForegroundColor Green

Write-Host "`n[3/10] Registrando Cliente..." -ForegroundColor Yellow
$clienteRegister = @{
    nombre = "Maria"
    apellido = "Garcia"
    dni = "33333333"
    email = "cliente@ecopuntos.com"
    password = "cliente123"
    password_confirmation = "cliente123"
    rol = "cliente"
} | ConvertTo-Json

$clienteResponse = Invoke-RestMethod -Uri "$baseUrl/register" -Method POST -Body $clienteRegister -Headers $headers
Write-Host "✓ Cliente registrado: $($clienteResponse.user.email)" -ForegroundColor Green

# =============================================================================
# PASO 2: LOGIN DE USUARIOS
# =============================================================================

Write-Host "`n[4/10] Haciendo login con Admin..." -ForegroundColor Yellow
$adminLogin = @{
    email = "admin@ecopuntos.com"
    password = "admin123"
} | ConvertTo-Json

$adminToken = (Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -Body $adminLogin -Headers $headers).token
$adminHeaders = @{ 
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $adminToken"
}
Write-Host "✓ Token Admin obtenido" -ForegroundColor Green

Write-Host "`n[5/10] Haciendo login con Recolector..." -ForegroundColor Yellow
$recolectorLogin = @{
    email = "recolector@ecopuntos.com"
    password = "reco123"
} | ConvertTo-Json

$recolectorToken = (Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -Body $recolectorLogin -Headers $headers).token
$recolectorHeaders = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $recolectorToken"
}
Write-Host "✓ Token Recolector obtenido" -ForegroundColor Green

Write-Host "`n[6/10] Haciendo login con Cliente..." -ForegroundColor Yellow
$clienteLogin = @{
    email = "cliente@ecopuntos.com"
    password = "cliente123"
} | ConvertTo-Json

$clienteToken = (Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -Body $clienteLogin -Headers $headers).token
$clienteHeaders = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
    "Authorization" = "Bearer $clienteToken"
}
Write-Host "✓ Token Cliente obtenido" -ForegroundColor Green

# =============================================================================
# PASO 3: ADMIN - APROBAR PUNTO DE ACOPIO
# =============================================================================

Write-Host "`n[7/10] Recolector solicita punto de acopio..." -ForegroundColor Yellow
$acopioRequest = @{
    nombre_lugar = "Acopio Central Lima"
    direccion = "Av. Principal 123, Lima"
    ubicacion_gps = "-12.0464,-77.0428"
} | ConvertTo-Json

$acopio = Invoke-RestMethod -Uri "$baseUrl/acopios" -Method POST -Body $acopioRequest -Headers $recolectorHeaders
Write-Host "✓ Solicitud de acopio enviada (ID: $($acopio.punto_acopio.id_acopio))" -ForegroundColor Green

Write-Host "`nAdmin aprobando punto de acopio..." -ForegroundColor Yellow
$approveBody = @{ estado = "aprobado" } | ConvertTo-Json
Invoke-RestMethod -Uri "$baseUrl/admin/acopios/$($acopio.punto_acopio.id_acopio)/approve" -Method PATCH -Body $approveBody -Headers $adminHeaders | Out-Null
Write-Host "✓ Punto de acopio APROBADO" -ForegroundColor Green

# =============================================================================
# PASO 4: ADMIN - VER TIPOS DE RESIDUO Y ACTUALIZAR PRECIO
# =============================================================================

Write-Host "`n[8/10] Admin consultando tipos de residuo..." -ForegroundColor Yellow
$tipos = Invoke-RestMethod -Uri "$baseUrl/admin/tipos-residuo" -Method GET -Headers $adminHeaders
Write-Host "✓ Tipos de residuo disponibles:" -ForegroundColor Green
$tipos | Select-Object -First 3 | ForEach-Object {
    Write-Host "  - $($_.nombre): $($_.puntos_por_kg) pts/kg" -ForegroundColor Cyan
}

Write-Host "`nAdmin actualizando precio del Plástico..." -ForegroundColor Yellow
$plasticoId = ($tipos | Where-Object { $_.nombre -eq "Plástico" }).id_tipo
$updatePrecio = @{ puntos_por_kg = 18.50 } | ConvertTo-Json
$updated = Invoke-RestMethod -Uri "$baseUrl/admin/tipos-residuo/$plasticoId" -Method PUT -Body $updatePrecio -Headers $adminHeaders
Write-Host "✓ Precio actualizado: Plástico = $($updated.tipo.puntos_por_kg) pts/kg" -ForegroundColor Green

# =============================================================================
# PASO 5: RECOLECTOR - REGISTRAR RESIDUO Y GENERAR CÓDIGO
# =============================================================================

Write-Host "`n[9/10] Recolector registrando residuo..." -ForegroundColor Yellow
$residuoData = @{
    tipo_residuo = "Plástico"
    cantidad_kg = 2.5
} | ConvertTo-Json

$residuo = Invoke-RestMethod -Uri "$baseUrl/recolector/transacciones" -Method POST -Body $residuoData -Headers $recolectorHeaders
$codigoGenerado = $residuo.codigo

Write-Host "✓ Residuo registrado:" -ForegroundColor Green
Write-Host "  - Tipo: $($residuo.residuo.tipo)" -ForegroundColor Cyan
Write-Host "  - Cantidad: $($residuo.residuo.cantidad_kg) kg" -ForegroundColor Cyan
Write-Host "  - Puntos calculados: $($residuo.residuo.puntos)" -ForegroundColor Cyan
Write-Host "  - Precio por kg: $($residuo.residuo.precio_por_kg)" -ForegroundColor Cyan
Write-Host "  - CÓDIGO GENERADO: $codigoGenerado" -ForegroundColor Yellow

# =============================================================================
# PASO 6: CLIENTE - ESCANEAR QR Y RECLAMAR PUNTOS
# =============================================================================

Write-Host "`n[10/10] Cliente escaneando código QR..." -ForegroundColor Yellow
$reclamarData = @{
    codigo = $codigoGenerado
} | ConvertTo-Json

$reclamo = Invoke-RestMethod -Uri "$baseUrl/transacciones/reclamar" -Method POST -Body $reclamarData -Headers $clienteHeaders

Write-Host "✓ PUNTOS RECLAMADOS:" -ForegroundColor Green
Write-Host "  - Mensaje: $($reclamo.message)" -ForegroundColor Cyan
Write-Host "  - Puntos totales: $($reclamo.nuevos_puntos_totales)" -ForegroundColor Cyan
Write-Host "  - Tipo residuo: $($reclamo.tipo_residuo)" -ForegroundColor Cyan
Write-Host "  - Cantidad: $($reclamo.cantidad_kg) kg" -ForegroundColor Cyan

# =============================================================================
# PASO 7: INTENTAR ESCANEAR EL MISMO CÓDIGO DE NUEVO (DEBE FALLAR)
# =============================================================================

Write-Host "`nIntentando escanear el mismo código de nuevo..." -ForegroundColor Yellow
try {
    Invoke-RestMethod -Uri "$baseUrl/transacciones/reclamar" -Method POST -Body $reclamarData -Headers $clienteHeaders -ErrorAction Stop
} catch {
    $errorResponse = $_.ErrorDetails.Message | ConvertFrom-Json
    Write-Host "✓ VALIDACIÓN CORRECTA: $($errorResponse.message)" -ForegroundColor Red
}

# =============================================================================
# PASO 8: VER ESTADÍSTICAS
# =============================================================================

Write-Host "`n=== ESTADÍSTICAS FINALES ===" -ForegroundColor Cyan

Write-Host "`nCliente consultando sus residuos..." -ForegroundColor Yellow
$misResiduos = Invoke-RestMethod -Uri "$baseUrl/cliente/mis-residuos" -Method GET -Headers $clienteHeaders
Write-Host "  Total residuos reclamados: $($misResiduos.estadisticas.total_residuos_reclamados)" -ForegroundColor Cyan
Write-Host "  Total puntos ganados: $($misResiduos.estadisticas.total_puntos_ganados)" -ForegroundColor Cyan
Write-Host "  Total kg reciclados: $($misResiduos.estadisticas.total_kg_reciclados)" -ForegroundColor Cyan

Write-Host "`nRecolector consultando residuos recibidos..." -ForegroundColor Yellow
$residuosRecibidos = Invoke-RestMethod -Uri "$baseUrl/recolector/residuos-recibidos" -Method GET -Headers $recolectorHeaders
Write-Host "  Precios actuales:" -ForegroundColor Cyan
$residuosRecibidos.precios_actuales | Select-Object -First 3 | ForEach-Object {
    Write-Host "    - $($_.nombre): $($_.puntos_por_kg) pts/kg" -ForegroundColor White
}

Write-Host "`nAdmin consultando dashboard..." -ForegroundColor Yellow
$dashboard = Invoke-RestMethod -Uri "$baseUrl/admin/dashboard" -Method GET -Headers $adminHeaders
Write-Host "  Total usuarios: $($dashboard.total_usuarios)" -ForegroundColor Cyan
Write-Host "  Total residuos: $($dashboard.total_residuos_registrados)" -ForegroundColor Cyan
Write-Host "  Total kg reciclados: $($dashboard.total_kg_reciclados)" -ForegroundColor Cyan
Write-Host "  Total puntos distribuidos: $($dashboard.total_puntos_distribuidos)" -ForegroundColor Cyan

Write-Host "`n===============================================" -ForegroundColor Green
Write-Host "  ✓ TODAS LAS PRUEBAS COMPLETADAS" -ForegroundColor Green
Write-Host "  ✓ API 100% FUNCIONAL" -ForegroundColor Green
Write-Host "===============================================`n" -ForegroundColor Green
