# Script de prueba de concurrencia para reclamo de QR
# Simula 2 clientes intentando reclamar el mismo código simultáneamente

param(
    [string]$baseUrl = "http://127.0.0.1:8000/api",
    [string]$codigo = ""
)

if ($codigo -eq "") {
    Write-Host "❌ Error: Debes proporcionar un código QR" -ForegroundColor Red
    Write-Host "Uso: .\test-concurrency.ps1 -codigo PELO266047" -ForegroundColor Yellow
    exit 1
}

Write-Host "`n=== PRUEBA DE CONCURRENCIA DE RECLAMO ===" -ForegroundColor Cyan
Write-Host "Código a reclamar: $codigo" -ForegroundColor Yellow

# Registrar 2 clientes
Write-Host "`nRegistrando clientes concurrentes..." -ForegroundColor Gray
$c1Body = @{
    nombre = "Cliente1"
    apellido = "Test"
    dni = "77777777"
    email = "cliente1@test.com"
    password = "test123"
    password_confirmation = "test123"
    rol = "cliente"
} | ConvertTo-Json

$c2Body = @{
    nombre = "Cliente2"
    apellido = "Test"
    dni = "88888888"
    email = "cliente2@test.com"
    password = "test123"
    password_confirmation = "test123"
    rol = "cliente"
} | ConvertTo-Json

try {
    $reg1 = Invoke-RestMethod -Uri "$baseUrl/register" -Method POST -Body $c1Body -ContentType "application/json"
    $reg2 = Invoke-RestMethod -Uri "$baseUrl/register" -Method POST -Body $c2Body -ContentType "application/json"
    Write-Host "✅ Clientes registrados" -ForegroundColor Green
} catch {
    # Si ya existen, hacer login
    $login1 = Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -Body (@{email="cliente1@test.com";password="test123"}|ConvertTo-Json) -ContentType "application/json"
    $login2 = Invoke-RestMethod -Uri "$baseUrl/login" -Method POST -Body (@{email="cliente2@test.com";password="test123"}|ConvertTo-Json) -ContentType "application/json"
    $reg1 = @{ access_token = $login1.access_token }
    $reg2 = @{ access_token = $login2.access_token }
    Write-Host "✅ Clientes ya existían, usando login" -ForegroundColor Green
}

$token1 = $reg1.access_token
$token2 = $reg2.access_token

$headers1 = @{
    "Authorization" = "Bearer $token1"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$headers2 = @{
    "Authorization" = "Bearer $token2"
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

$reclamarBody = @{ codigo = $codigo } | ConvertTo-Json

Write-Host "`nIniciando reclamos simultáneos..." -ForegroundColor Yellow
Write-Host "Cliente 1 y Cliente 2 reclaman al mismo tiempo..." -ForegroundColor Gray

# Crear jobs para ejecutar en paralelo
$job1 = Start-Job -ScriptBlock {
    param($url, $body, $hdrs)
    try {
        $result = Invoke-RestMethod -Uri $url -Method POST -Body $body -Headers $hdrs
        return @{ success = $true; response = $result }
    } catch {
        return @{ success = $false; error = $_.Exception.Message; status = $_.Exception.Response.StatusCode.value__ }
    }
} -ArgumentList "$baseUrl/transacciones/reclamar", $reclamarBody, $headers1

$job2 = Start-Job -ScriptBlock {
    param($url, $body, $hdrs)
    try {
        $result = Invoke-RestMethod -Uri $url -Method POST -Body $body -Headers $hdrs
        return @{ success = $true; response = $result }
    } catch {
        return @{ success = $false; error = $_.Exception.Message; status = $_.Exception.Response.StatusCode.value__ }
    }
} -ArgumentList "$baseUrl/transacciones/reclamar", $reclamarBody, $headers2

# Esperar resultados
Wait-Job $job1, $job2 | Out-Null
$result1 = Receive-Job $job1
$result2 = Receive-Job $job2
Remove-Job $job1, $job2

Write-Host "`nResultados:" -ForegroundColor Cyan
Write-Host "Cliente 1: $(if($result1.success){'✅ Exitoso'}else{'❌ Falló - Status: '+$result1.status})" -ForegroundColor $(if($result1.success){'Green'}else{'Red'})
Write-Host "Cliente 2: $(if($result2.success){'✅ Exitoso'}else{'❌ Falló - Status: '+$result2.status})" -ForegroundColor $(if($result2.success){'Green'}else{'Red'})

$exitos = @($result1.success, $result2.success) | Where-Object { $_ -eq $true }

if ($exitos.Count -eq 1) {
    Write-Host "`n✅ CORRECTO: Solo 1 cliente pudo reclamar (lockForUpdate funcionó)" -ForegroundColor Green
} elseif ($exitos.Count -eq 0) {
    Write-Host "`n⚠️ ADVERTENCIA: Ninguno pudo reclamar (código ya usado?)" -ForegroundColor Yellow
} else {
    Write-Host "`n❌ FALLO CRÍTICO: Ambos clientes reclamaron el mismo código!" -ForegroundColor Red
    Write-Host "   lockForUpdate NO está funcionando correctamente" -ForegroundColor Red
}
