# 🎯 API COMPLETA ecoPuntos - CRUD POR ACTOR

## 📋 ÍNDICE DE RUTAS

### 🌐 RUTAS PÚBLICAS (2)
- `POST /api/register`
- `POST /api/login`

### 🔐 RUTAS AUTENTICADAS GENERALES (5)
- `GET /api/user`
- `POST /api/logout`
- `POST /api/acopios` (solicitar ser recolector)
- `GET /api/articulos` (ver catálogo)
- `POST /api/transacciones/reclamar` (reclamar puntos de QR)
- `POST /api/transacciones/canjear` (canjear puntos por artículo)

### 👤 RUTAS DE PERFIL - TODOS LOS USUARIOS (4)
- `GET /api/perfil`
- `PUT /api/perfil`
- `PATCH /api/perfil/tema`
- `PATCH /api/perfil/password`

### 👤 RUTAS DE CLIENTE (4)
- `GET /api/cliente/puntos`
- `GET /api/cliente/historial`
- `GET /api/cliente/canjes-pendientes`
- `GET /api/cliente/puntos-acopio`

### ♻️ RUTAS DE RECOLECTOR (5)
- `GET /api/recolector/puntos`
- `GET /api/recolector/qrs`
- `GET /api/recolector/canjes-pendientes`
- `POST /api/recolector/transacciones`
- `PATCH /api/recolector/transacciones/{id}/entregar`

### 👑 RUTAS DE ADMIN (17)
- `GET /api/admin/dashboard`
- `GET /api/admin/usuarios`
- `GET /api/admin/usuarios/{id}`
- `PUT /api/admin/usuarios/{id}`
- `DELETE /api/admin/usuarios/{id}`
- `GET /api/admin/acopios`
- `GET /api/admin/acopios/pendientes`
- `PATCH /api/admin/acopios/{id}/approve`
- `PUT /api/admin/acopios/{id}`
- `DELETE /api/admin/acopios/{id}`
- `POST /api/admin/articulos`
- `PUT /api/admin/articulos/{id}`
- `DELETE /api/admin/articulos/{id}`

---

## 🔵 RUTAS DE CLIENTE

### 1. Ver Mis Puntos
**GET** `/api/cliente/puntos`
```json
// Response
{
  "puntos_actuales": 450,
  "total_ganados": 500,
  "total_canjeados": 50,
  "total_transacciones": 12
}
```

### 2. Ver Mi Historial
**GET** `/api/cliente/historial?tipo=ganado`
```json
// Query params opcionales:
// - tipo: ganado | canjeado

// Response
[
  {
    "id_transaccion": 15,
    "tipo": "ganado",
    "tipo_residuo": "Plástico PET",
    "cantidad_kg": 2.5,
    "puntos": 25,
    "status": "completada",
    "created_at": "2025-10-26T10:30:00",
    "recolector": {
      "nombre": "Juan",
      "apellido": "Pérez"
    },
    "punto_acopio": {
      "nombre_lugar": "Centro de Acopio Norte",
      "direccion": "Av. Principal 123"
    }
  }
]
```

### 3. Ver Mis Canjes Pendientes
**GET** `/api/cliente/canjes-pendientes`
```json
// Response
[
  {
    "id_transaccion": 20,
    "tipo": "canjeado",
    "puntos": -100,
    "status": "pendiente_recojo",
    "created_at": "2025-10-26T14:00:00",
    "articulo_tienda": {
      "id_articulo": 3,
      "nombre": "Botella Reutilizable",
      "descripcion": "Botella de acero inoxidable 500ml",
      "imagen_url": "https://..."
    },
    "punto_acopio": {
      "nombre_lugar": "Centro de Acopio Sur",
      "direccion": "Calle Los Olivos 456",
      "ubicacion_gps": "-12.0464,-77.0428"
    }
  }
]
```

### 4. Ver Mapa de Puntos de Acopio
**GET** `/api/cliente/puntos-acopio`
```json
// Response
[
  {
    "id_acopio": 5,
    "nombre_lugar": "Centro de Acopio Norte",
    "direccion": "Av. Principal 123",
    "ubicacion_gps": "-12.0464,-77.0428",
    "recolector": {
      "id_usuario": 10,
      "nombre": "Juan",
      "apellido": "Pérez"
    }
  },
  {
    "id_acopio": 8,
    "nombre_lugar": "Centro de Acopio Sur",
    "direccion": "Calle Los Olivos 456",
    "ubicacion_gps": "-12.1234,-77.0567",
    "recolector": {
      "id_usuario": 15,
      "nombre": "María",
      "apellido": "López"
    }
  }
]
```

---

## 👥 RUTAS DE PERFIL (TODOS LOS USUARIOS)

### 1. Ver Mi Perfil
**GET** `/api/perfil`
```json
// Response
{
  "id_usuario": 10,
  "nombre": "María",
  "apellido": "García",
  "dni": "12345678",
  "email": "maria@example.com",
  "rol": "cliente",
  "puntos": 450,
  "preferencia_tema": "dark",
  "created_at": "2025-10-01T10:00:00"
}
```

### 2. Actualizar Perfil
**PUT** `/api/perfil`
```json
// Request (todos opcionales)
{
  "nombre": "María Fernanda",
  "apellido": "García López",
  "email": "mariaf@example.com"
}

// Response
{
  "message": "Perfil actualizado exitosamente.",
  "user": { ... }
}
```

### 3. Cambiar Tema (Modo Oscuro/Claro)
**PATCH** `/api/perfil/tema`
```json
// Request
{
  "preferencia_tema": "dark"  // o "light"
}

// Response
{
  "message": "Tema actualizado exitosamente.",
  "preferencia_tema": "dark"
}
```

### 4. Cambiar Contraseña
**PATCH** `/api/perfil/password`
```json
// Request
{
  "password_actual": "miPasswordVieja123",
  "password_nueva": "miPasswordNueva456",
  "password_nueva_confirmation": "miPasswordNueva456"
}

// Response
{
  "message": "Contraseña actualizada exitosamente."
}
```

---

## 🟢 RUTAS DE RECOLECTOR

### 1. Ver Mis Estadísticas
**GET** `/api/recolector/puntos`
```json
// Response
{
  "punto_acopio": {
    "id": 5,
    "nombre": "Centro de Acopio Norte",
    "direccion": "Av. Principal 123",
    "estado": "aprobado"
  },
  "total_transacciones_generadas": 45,
  "total_puntos_distribuidos": 1250,
  "total_kg_recolectados": 125.5,
  "qrs_pendientes": 3,
  "qrs_completados": 42,
  "articulos_pendientes_entrega": 2
}
```

### 2. Ver Mis QRs Generados
**GET** `/api/recolector/qrs?status=pendiente_puntos`
```json
// Query params opcionales:
// - status: pendiente_puntos | completada

// Response
[
  {
    "id_transaccion": 30,
    "tipo": "ganado",
    "tipo_residuo": "Cartón",
    "cantidad_kg": 5.0,
    "puntos": 50,
    "status": "pendiente_puntos",
    "codigo_reclamacion": "A8F3B2X1",
    "created_at": "2025-10-26T15:00:00",
    "punto_acopio": {
      "id_acopio": 5,
      "nombre_lugar": "Centro de Acopio Norte"
    }
  }
]
```

### 3. Ver Canjes Pendientes de Entrega
**GET** `/api/recolector/canjes-pendientes`
```json
// Response
[
  {
    "id_transaccion": 25,
    "tipo": "canjeado",
    "status": "pendiente_recojo",
    "created_at": "2025-10-26T12:00:00",
    "articulo_tienda": {
      "id_articulo": 2,
      "nombre": "Mochila Ecológica",
      "imagen_url": "https://..."
    },
    "punto_acopio": {
      "nombre_lugar": "Centro de Acopio Norte",
      "direccion": "Av. Principal 123"
    }
  }
]
```

### 4. Generar QR (Registrar Residuo)
**POST** `/api/recolector/transacciones`
```json
// Request
{
  "puntos": 30,
  "tipo_residuo": "Plástico PET",
  "cantidad_kg": 3.0
}

// Response
{
  "message": "Código QR generado. Esperando al cliente.",
  "codigo_reclamacion": "B5K9L2M8",
  "puntos": 30,
  "tipo_residuo": "Plástico PET",
  "cantidad_kg": 3.0
}
```

### 5. Marcar Como Entregado
**PATCH** `/api/recolector/transacciones/{id}/entregar`
```json
// Response
{
  "message": "¡Artículo entregado exitosamente!",
  "transaccion": { ... }
}
```

---

## 🔴 RUTAS DE ADMIN

### 📊 DASHBOARD

**GET** `/api/admin/dashboard`
```json
// Response
{
  "total_usuarios": 150,
  "total_clientes": 120,
  "total_recolectores": 25,
  "total_admins": 5,
  
  "total_acopios": 30,
  "acopios_pendientes": 3,
  "acopios_aprobados": 27,
  
  "total_articulos": 15,
  "articulos_sin_stock": 2,
  
  "total_transacciones": 500,
  "transacciones_completadas": 450,
  "transacciones_pendientes": 50,
  
  "total_puntos_distribuidos": 12500,
  "total_puntos_canjeados": 3500,
  
  "total_kg_reciclados": 1250.5
}
```

---

### 👥 GESTIÓN DE USUARIOS

#### 1. Listar Usuarios
**GET** `/api/admin/usuarios`
```json
// Response
[
  {
    "id_usuario": 1,
    "nombre": "María",
    "apellido": "García",
    "dni": "12345678",
    "email": "maria@example.com",
    "rol": "cliente",
    "puntos": 450,
    "created_at": "2025-10-01T10:00:00"
  }
]
```

#### 2. Ver Detalle de Usuario
**GET** `/api/admin/usuarios/{id}`
```json
// Response
{
  "id_usuario": 1,
  "nombre": "María",
  "apellido": "García",
  "dni": "12345678",
  "email": "maria@example.com",
  "rol": "cliente",
  "puntos": 450,
  "created_at": "2025-10-01T10:00:00",
  "puntos_acopio": [],
  "transacciones_puntos": [...],
  "transacciones_como_recolector": []
}
```

#### 3. Actualizar Usuario
**PUT** `/api/admin/usuarios/{id}`
```json
// Request
{
  "nombre": "María Fernanda",
  "rol": "recolector",
  "puntos": 500
}

// Response
{
  "message": "Usuario actualizado exitosamente.",
  "usuario": { ... }
}
```

#### 4. Eliminar Usuario
**DELETE** `/api/admin/usuarios/{id}`
```json
// Response
{
  "message": "Usuario eliminado exitosamente."
}
```

---

### 📍 GESTIÓN DE PUNTOS DE ACOPIO

#### 1. Listar Todos los Acopios
**GET** `/api/admin/acopios`
```json
// Response
[
  {
    "id_acopio": 5,
    "nombre_lugar": "Centro de Acopio Norte",
    "direccion": "Av. Principal 123",
    "ubicacion_gps": "-12.0464,-77.0428",
    "estado": "aprobado",
    "recolector": {
      "id_usuario": 10,
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@example.com"
    }
  }
]
```

#### 2. Ver Acopios Pendientes
**GET** `/api/admin/acopios/pendientes`
```json
// Response
[
  {
    "id_acopio": 8,
    "nombre_lugar": "Nuevo Punto Centro",
    "direccion": "Jr. Los Pinos 789",
    "estado": "pendiente",
    "recolector": { ... }
  }
]
```

#### 3. Aprobar Acopio
**PATCH** `/api/admin/acopios/{id}/approve`
```json
// Response
{
  "message": "¡Punto de acopio aprobado! El usuario ahora es recolector.",
  "data": { ... }
}
```

#### 4. Actualizar Acopio
**PUT** `/api/admin/acopios/{id}`
```json
// Request
{
  "nombre_lugar": "Centro de Acopio Norte Renovado",
  "direccion": "Av. Principal 123-A",
  "estado": "aprobado"
}

// Response
{
  "message": "Punto de acopio actualizado exitosamente.",
  "acopio": { ... }
}
```

#### 5. Eliminar Acopio
**DELETE** `/api/admin/acopios/{id}`
```json
// Response
{
  "message": "Punto de acopio eliminado exitosamente."
}
```

---

### 🛍️ GESTIÓN DE ARTÍCULOS

#### 1. Crear Artículo
**POST** `/api/admin/articulos`
```json
// Request
{
  "nombre": "Botella Reutilizable Premium",
  "descripcion": "Botella de acero inoxidable 750ml con aislamiento térmico",
  "stock": 50,
  "imagen_url": "https://example.com/botella.jpg",
  "puntos_requeridos": 150
}

// Response
{
  "message": "Artículo creado exitosamente.",
  "data": { ... }
}
```

#### 2. Actualizar Artículo
**PUT** `/api/admin/articulos/{id}`
```json
// Request
{
  "nombre": "Botella Reutilizable Premium XL",
  "stock": 75,
  "puntos_requeridos": 180
}

// Response
{
  "message": "Artículo actualizado exitosamente.",
  "data": { ... }
}
```

#### 3. Eliminar Artículo
**DELETE** `/api/admin/articulos/{id}`
```json
// Response
{
  "message": "Artículo eliminado exitosamente."
}
```

---

## 📋 RESUMEN DE CAMBIOS NUEVOS

### ✅ Nuevos Controladores (3)
1. **AdminController** - 9 métodos
   - indexUsuarios, showUsuario, updateUsuario, destroyUsuario
   - indexAcopios, updateAcopio, destroyAcopio
   - dashboard

2. **RecolectorController** - 3 métodos
   - misPuntos, misQRs, canjesPendientes

3. **ClienteController** - 3 métodos
   - misPuntos, miHistorial, misCanjesPendientes

### ✅ Controladores Actualizados (2)
1. **ArticuloTiendaController** - Agregados:
   - update() - Actualizar artículo
   - destroy() - Eliminar artículo

2. **TransaccionController** - Actualizado:
   - store() - Ahora registra tipo_residuo y cantidad_kg

### ✅ Migración Nueva
- `add_residuos_fields_to_transaccion_puntos_table`
  - Agrega: `tipo_residuo` (string nullable)
  - Agrega: `cantidad_kg` (decimal nullable)

### ✅ Modelo Actualizado
- **TransaccionPuntos** - Agregados a $fillable:
  - tipo_residuo
  - cantidad_kg

### ✅ Nuevas Rutas (25)
- **Cliente**: 3 rutas
- **Recolector**: 2 rutas nuevas (3 ya existían)
- **Admin**: 17 rutas (13 nuevas)

---

## 📊 TOTAL DE FUNCIONALIDADES POR ACTOR

### 👤 CLIENTE (9 funcionalidades)
✅ Registrarse y hacer login
✅ Ver y editar mi perfil
✅ Cambiar tema (modo oscuro/claro)
✅ Cambiar contraseña
✅ Ver catálogo de artículos
✅ Ver mapa de puntos de acopio activos
✅ Reclamar puntos de QR
✅ Canjear puntos por artículos
✅ Ver mis puntos y estadísticas
✅ Ver historial completo
✅ Ver canjes pendientes de recoger
✅ Cerrar sesión

### ♻️ RECOLECTOR (8 funcionalidades)
✅ Solicitar ser recolector
✅ Ver y editar mi perfil
✅ Cambiar tema (modo oscuro/claro)
✅ Cambiar contraseña
✅ Ver mis estadísticas y punto de acopio
✅ Ver historial de QRs generados
✅ Registrar residuo y generar QR
✅ Ver canjes pendientes de entrega
✅ Marcar artículo como entregado
✅ Ver kg recolectados totales
✅ Cerrar sesión

### 👑 ADMIN (20 funcionalidades)
✅ Ver dashboard con estadísticas generales
✅ Ver y editar mi perfil
✅ Cambiar tema (modo oscuro/claro)
✅ Cambiar contraseña
✅ Listar todos los usuarios
✅ Ver detalle de un usuario
✅ Editar usuario (puntos, rol, datos)
✅ Eliminar usuario
✅ Listar todos los puntos de acopio
✅ Ver acopios pendientes
✅ Aprobar/rechazar acopio
✅ Editar punto de acopio
✅ Eliminar punto de acopio
✅ Listar artículos
✅ Crear artículo
✅ Editar artículo (stock, puntos, etc.)
✅ Eliminar artículo
✅ Ver total de usuarios por rol
✅ Ver total de transacciones
✅ Ver total de puntos distribuidos
✅ Ver total de kg reciclados
✅ Ver estadísticas del sistema
✅ Cerrar sesión

---

## 🎯 ESTADO FINAL

**TOTAL DE RUTAS: 37**
- Públicas: 2
- Autenticadas generales: 6
- Perfil (todos): 4
- Cliente: 4
- Recolector: 5
- Admin: 17

**TOTAL DE CONTROLADORES: 8**
- AuthController
- PuntoAcopioController
- ArticuloTiendaController
- TransaccionController
- AdminController ⭐ NUEVO
- RecolectorController ⭐ NUEVO
- ClienteController ⭐ NUEVO (4 métodos)
- PerfilController ⭐ NUEVO (4 métodos)

**TOTAL DE MODELOS: 4**
- User
- PuntoAcopio
- ArticuloTienda
- TransaccionPuntos (actualizado con residuos)

---

## 🔐 SEGURIDAD

✅ Autenticación con Laravel Sanctum
✅ Middleware de roles (admin, recolector)
✅ Validación de entrada en todos los endpoints
✅ Transacciones de BD en operaciones críticas
✅ Bloqueo optimista (lockForUpdate)
✅ Verificación de permisos por rol
✅ Verificación de propiedad de recursos

---

**✅ API 100% COMPLETA PARA TODOS LOS ACTORES**

**Fecha:** 26 de Octubre, 2025
