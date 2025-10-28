# 🗑️ TABLA RESIDUOS - IMPLEMENTACIÓN COMPLETA

## 📊 NUEVA ARQUITECTURA DEL SISTEMA

### **FLUJO ACTUALIZADO:**

1. **Recolector registra residuo** → Crea registro en tabla `residuos` con código QR único
2. **Cliente escanea QR** → Busca en tabla `residuos`, marca como reclamado, crea transacción
3. **Sistema de puntos** → Usa tabla `transaccion_puntos` solo para historial de movimientos
4. **Estadísticas** → Se calculan desde tabla `residuos` (más preciso)

---

## 🗄️ ESTRUCTURA DE LA TABLA `residuos`

```sql
CREATE TABLE residuos (
    id_residuo BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tipo_residuo VARCHAR(255) NOT NULL,          -- Plástico, Papel, Vidrio, Metal, etc.
    cantidad_kg DECIMAL(8,2) NOT NULL,           -- Peso del residuo
    puntos_otorgados INT NOT NULL,               -- Puntos que se darán al cliente
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    user_id_recolector BIGINT UNSIGNED NOT NULL, -- FK al recolector
    punto_acopio_id BIGINT UNSIGNED NOT NULL,    -- FK al punto de acopio
    codigo_qr VARCHAR(255) UNIQUE NOT NULL,      -- Código QR único
    estado VARCHAR(255) DEFAULT 'disponible',    -- disponible | reclamado
    user_id_cliente BIGINT UNSIGNED NULL,        -- FK al cliente (null hasta reclamar)
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id_recolector) REFERENCES users(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (punto_acopio_id) REFERENCES punto_acopios(id_acopio) ON DELETE CASCADE,
    FOREIGN KEY (user_id_cliente) REFERENCES users(id_usuario) ON DELETE SET NULL
);
```

---

## 🔄 CAMBIOS EN LOS ENDPOINTS

### 1️⃣ **POST /api/recolector/transacciones** (Registrar residuo)

**ANTES:**
- Creaba registro en `transaccion_puntos` con estado 'pendiente_puntos'
- Almacenaba `tipo_residuo` y `cantidad_kg` en la transacción

**AHORA:**
- Crea registro en tabla `residuos` con estado 'disponible'
- Genera código QR único
- NO crea transacción hasta que el cliente reclame

**Request:**
```json
{
  "tipo_residuo": "Plástico",
  "cantidad_kg": 5.5,
  "puntos": 100
}
```

**Response:**
```json
{
  "message": "Residuo registrado. Código QR generado.",
  "codigo_qr": "A8F3B2X1",
  "residuo": {
    "id_residuo": 1,
    "tipo_residuo": "Plástico",
    "cantidad_kg": 5.5,
    "puntos_otorgados": 100,
    "estado": "disponible",
    "user_id_recolector": 5,
    "punto_acopio_id": 2,
    "codigo_qr": "A8F3B2X1",
    "fecha_registro": "2025-10-26T14:30:00"
  }
}
```

---

### 2️⃣ **POST /api/transacciones/reclamar** (Cliente reclama QR)

**ANTES:**
- Buscaba en `transaccion_puntos` por `codigo_reclamacion`
- Actualizaba status a 'completada'

**AHORA:**
- Busca en tabla `residuos` por `codigo_qr`
- Marca residuo como 'reclamado'
- **CREA** la transacción en `transaccion_puntos` para historial
- Actualiza puntos del cliente

**Request:**
```json
{
  "codigo_qr": "A8F3B2X1"
}
```

**Response:**
```json
{
  "message": "¡Éxito! Has ganado 100 puntos.",
  "nuevos_puntos_totales": 650,
  "tipo_residuo": "Plástico",
  "cantidad_kg": 5.5
}
```

---

### 3️⃣ **GET /api/recolector/puntos** (Estadísticas del recolector)

**CAMBIOS:**
- `total_residuos_registrados` → Cuenta registros en `residuos`
- `total_puntos_distribuidos` → Suma `puntos_otorgados` de residuos reclamados
- `total_kg_recolectados` → Suma `cantidad_kg` de tabla `residuos`
- `qrs_disponibles` → Cuenta residuos con estado 'disponible'
- `qrs_reclamados` → Cuenta residuos con estado 'reclamado'

**Response:**
```json
{
  "punto_acopio": { ... },
  "total_residuos_registrados": 25,
  "total_puntos_distribuidos": 2500,
  "total_kg_recolectados": 135.5,
  "qrs_disponibles": 5,
  "qrs_reclamados": 20,
  "articulos_pendientes_entrega": 3
}
```

---

### 4️⃣ **GET /api/recolector/qrs** (Historial de QRs)

**CAMBIOS:**
- Consulta tabla `residuos` en lugar de `transaccion_puntos`
- Incluye relaciones con `puntoAcopio` y `cliente`
- Filtro por `estado` (disponible/reclamado)

**Request:**
```
GET /api/recolector/qrs?estado=disponible
```

**Response:**
```json
[
  {
    "id_residuo": 1,
    "tipo_residuo": "Plástico",
    "cantidad_kg": 5.5,
    "puntos_otorgados": 100,
    "codigo_qr": "A8F3B2X1",
    "estado": "disponible",
    "fecha_registro": "2025-10-26T10:00:00",
    "punto_acopio": {
      "id_acopio": 2,
      "nombre_lugar": "Acopio Central"
    },
    "cliente": null
  },
  {
    "id_residuo": 2,
    "tipo_residuo": "Papel",
    "cantidad_kg": 3.2,
    "puntos_otorgados": 60,
    "codigo_qr": "B2X9K1L4",
    "estado": "reclamado",
    "fecha_registro": "2025-10-26T09:30:00",
    "punto_acopio": {
      "id_acopio": 2,
      "nombre_lugar": "Acopio Central"
    },
    "cliente": {
      "id_usuario": 10,
      "nombre": "María",
      "apellido": "García"
    }
  }
]
```

---

### 5️⃣ **GET /api/admin/dashboard** (Dashboard del admin)

**NUEVAS ESTADÍSTICAS:**
```json
{
  "total_usuarios": 150,
  "total_clientes": 120,
  "total_recolectores": 25,
  "total_admins": 5,
  
  "total_acopios": 20,
  "acopios_pendientes": 3,
  "acopios_aprobados": 17,
  
  "total_articulos": 30,
  "articulos_sin_stock": 5,
  
  "total_residuos_registrados": 500,
  "residuos_disponibles": 50,
  "residuos_reclamados": 450,
  
  "total_transacciones": 520,
  "transacciones_completadas": 500,
  "transacciones_pendientes": 20,
  
  "total_puntos_distribuidos": 45000,
  "total_puntos_canjeados": 12000,
  "total_kg_reciclados": 2500.5,
  
  "residuos_por_tipo": [
    {
      "tipo_residuo": "Plástico",
      "cantidad": 200,
      "total_kg": 1000.5
    },
    {
      "tipo_residuo": "Papel",
      "cantidad": 150,
      "total_kg": 800.2
    },
    {
      "tipo_residuo": "Vidrio",
      "cantidad": 100,
      "total_kg": 500.8
    },
    {
      "tipo_residuo": "Metal",
      "cantidad": 50,
      "total_kg": 200.0
    }
  ]
}
```

---

## 🔗 RELACIONES EN LOS MODELOS

### **Model: Residuo.php**
```php
// Recolector que registró el residuo
public function recolector()
{
    return $this->belongsTo(User::class, 'user_id_recolector', 'id_usuario');
}

// Cliente que reclamó los puntos
public function cliente()
{
    return $this->belongsTo(User::class, 'user_id_cliente', 'id_usuario');
}

// Punto de acopio donde se registró
public function puntoAcopio()
{
    return $this->belongsTo(PuntoAcopio::class, 'punto_acopio_id', 'id_acopio');
}
```

### **Model: User.php (NUEVAS RELACIONES)**
```php
// Residuos registrados como recolector
public function residuosRegistrados()
{
    return $this->hasMany(Residuo::class, 'user_id_recolector', 'id_usuario');
}

// Residuos reclamados como cliente
public function residuosReclamados()
{
    return $this->hasMany(Residuo::class, 'user_id_cliente', 'id_usuario');
}
```

---

## ✅ VENTAJAS DE ESTA ARQUITECTURA

1. **Separación de responsabilidades:**
   - `residuos` → Gestión de reciclaje (QRs, kg, tipo)
   - `transaccion_puntos` → Historial de movimientos de puntos

2. **Trazabilidad completa:**
   - Cada residuo tiene su propio registro independiente
   - Se puede consultar qué cliente reclamó qué residuo

3. **Estadísticas precisas:**
   - Calcular kg por tipo de residuo
   - Ver qué recolector registró más residuos
   - Identificar los puntos de acopio más activos

4. **Escalabilidad:**
   - Fácil agregar campos como `foto_residuo`, `calificacion`, etc.
   - Permite implementar gamificación (badges por tipo de residuo)

5. **Integridad de datos:**
   - Los códigos QR son únicos a nivel de tabla
   - No se pueden reclamar dos veces (estado cambia a 'reclamado')
   - Foreign keys aseguran consistencia

---

## 🚀 ENDPOINTS ACTUALIZADOS - RESUMEN

| Método | Endpoint | Cambio | Tabla Principal |
|--------|----------|--------|-----------------|
| POST | `/api/recolector/transacciones` | Crea en `residuos` | `residuos` |
| POST | `/api/transacciones/reclamar` | Busca en `residuos`, crea en `transaccion_puntos` | `residuos` + `transaccion_puntos` |
| GET | `/api/recolector/puntos` | Estadísticas desde `residuos` | `residuos` |
| GET | `/api/recolector/qrs` | Consulta `residuos` | `residuos` |
| GET | `/api/admin/dashboard` | Agrega stats de `residuos` | `residuos` + otras |

---

## 📝 PRÓXIMOS PASOS

1. ✅ Migración ejecutada
2. ✅ Modelo `Residuo` creado
3. ✅ Controladores actualizados
4. ✅ Relaciones configuradas
5. ⏳ Crear seeders con datos de prueba
6. ⏳ Probar endpoints con Postman

---

**TOTAL DE TABLAS: 10**
- ✅ users
- ✅ punto_acopios
- ✅ articulo_tiendas
- ✅ transaccion_puntos
- ✅ **residuos** ⭐ NUEVO
- ✅ cache, cache_locks
- ✅ jobs, job_batches, failed_jobs
- ✅ personal_access_tokens (Sanctum)
