# ========================================================================
# GUÍA PASO A PASO PARA PROBAR LA API - ecoPuntos
# ========================================================================

## PASO 1: LEVANTAR EL SERVIDOR

Abre una terminal PowerShell en VS Code y ejecuta:

```powershell
cd C:\Users\eduv0\OneDrive\Desktop\cursoUWeb\ecoPuntosBackend
php artisan serve
```

Deberías ver: `Server running on [http://127.0.0.1:8000]`

**DEJA ESA TERMINAL ABIERTA Y CORRIENDO**

---

## PASO 2: ABRIR OTRA TERMINAL

En VS Code:
- Click en el ícono "+" en la pestaña de terminales
- O presiona `Ctrl + Shift + ñ` para abrir una nueva terminal

---

## PASO 3: EJECUTAR PRUEBAS AUTOMÁTICAS

En la NUEVA terminal, ejecuta:

```powershell
cd C:\Users\eduv0\OneDrive\Desktop\cursoUWeb\ecoPuntosBackend

# Opción A: Ejecutar TODO el script de pruebas
.\test-manual.ps1

# Opción B: Ejecutar comando por comando (copiando del archivo)
```

---

## PRUEBA RÁPIDA INDIVIDUAL (Si quieres probar manualmente)

### 1. Registrar Admin
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" -Method POST -Body '{"nombre":"Carlos","apellido":"Rodriguez","dni":"11111111","email":"admin@ecopuntos.com","password":"admin123","password_confirmation":"admin123","rol":"admin"}' -ContentType "application/json"
```

### 2. Registrar Recolector  
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" -Method POST -Body '{"nombre":"Juan","apellido":"Perez","dni":"22222222","email":"reco@eco.com","password":"reco123","password_confirmation":"reco123","rol":"recolector"}' -ContentType "application/json"
```

### 3. Registrar Cliente
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/register" -Method POST -Body '{"nombre":"Maria","apellido":"Garcia","dni":"33333333","email":"cliente@eco.com","password":"cliente123","password_confirmation":"cliente123","rol":"cliente"}' -ContentType "application/json"
```

### 4. Login Admin y guardar token
```powershell
$adminToken = (Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/login" -Method POST -Body '{"email":"admin@ecopuntos.com","password":"admin123"}' -ContentType "application/json").token
Write-Host "Token: $adminToken"
```

### 5. Ver tipos de residuo (como Admin)
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/tipos-residuo" -Headers @{"Authorization"="Bearer $adminToken"} | ConvertTo-Json -Depth 5
```

### 6. Actualizar precio del Plástico (como Admin)
```powershell
Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/admin/tipos-residuo/1" -Method PUT -Body '{"puntos_por_kg":20.00}' -ContentType "application/json" -Headers @{"Authorization"="Bearer $adminToken"} | ConvertTo-Json
```

---

## ALTERNATIVA: USAR POSTMAN

Si prefieres usar Postman, aquí están los endpoints principales:

### BASE URL
```
http://127.0.0.1:8000/api
```

### 1. POST /register
```json
{
  "nombre": "Carlos",
  "apellido": "Rodriguez",
  "dni": "11111111",
  "email": "admin@ecopuntos.com",
  "password": "admin123",
  "password_confirmation": "admin123",
  "rol": "admin"
}
```

### 2. POST /login
```json
{
  "email": "admin@ecopuntos.com",
  "password": "admin123"
}
```
**Copia el `token` de la respuesta**

### 3. GET /admin/tipos-residuo
Headers:
```
Authorization: Bearer {TU_TOKEN_AQUI}
```

### 4. PUT /admin/tipos-residuo/1
Headers:
```
Authorization: Bearer {TU_TOKEN_AQUI}
Content-Type: application/json
```
Body:
```json
{
  "puntos_por_kg": 20.00
}
```

---

## VERIFICAR QUE TODO FUNCIONA

Después de las pruebas, deberías ver:

✅ Admin puede actualizar precios por kg  
✅ Recolector registra residuos con puntos automáticos  
✅ Cliente escanea código y recibe puntos  
✅ Código QR se marca como usado y no se puede reclamar dos veces  
✅ Dashboard del admin muestra estadísticas correctas  

---

## TROUBLESHOOTING

### Error: "No es posible conectar con el servidor remoto"
- Verifica que el servidor esté corriendo en el puerto 8000
- Ejecuta: `php artisan serve` en el directorio del proyecto

### Error: "Could not open input file: artisan"
- Estás en el directorio incorrecto
- Ejecuta: `cd C:\Users\eduv0\OneDrive\Desktop\cursoUWeb\ecoPuntosBackend`

### Error: "Unauthenticated"
- El token expiró o es inválido
- Haz login de nuevo y obtén un nuevo token

---

## RESUMEN DE RUTAS DISPONIBLES

```
PUBLIC:
POST /api/register
POST /api/login

CLIENTE (requiere token):
GET /api/cliente/mis-residuos
GET /api/cliente/puntos
POST /api/transacciones/reclamar

RECOLECTOR (requiere token):
POST /api/recolector/transacciones
GET /api/recolector/residuos-recibidos
GET /api/recolector/qrs

ADMIN (requiere token):
GET /api/admin/tipos-residuo
PUT /api/admin/tipos-residuo/{id}
GET /api/admin/dashboard
```

---

**¡La API está 100% funcional y lista para probar!** 🚀
