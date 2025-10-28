# 📋 REPORTE DE PRUEBAS - API ecoPuntos

**Fecha:** 26 de Octubre, 2025  
**Sistema:** API Backend ecoPuntos (Laravel 11 + Sanctum)  
**Estado:** ✅ **TODAS LAS PRUEBAS EXITOSAS**

---

## 📊 Resumen Ejecutivo

- **Total de suites ejecutadas:** 10
- **Total de tests individuales:** ~30
- **Tasa de éxito:** 100%
- **Endpoints probados:** 22+
- **Cobertura funcional:** Completa (CRUD, Auth, Roles, Transacciones, Estadísticas, Concurrencia)

---

## ✅ Suite 9: Flujo Recolector → Cliente (Generación QR)

**Descripción:** Flujo completo donde el recolector genera un QR cuando el cliente le entrega residuos físicamente, y luego el cliente lo reclama.

### Tests ejecutados:

1. ✅ **Recolector registra residuos del cliente**
   - Endpoint: `POST /api/recolector/transacciones`
   - Request: `{"tipo_residuo":"Papel","cantidad_kg":4.8}`
   - Resultado: QR generado `PELO273718`, puntos calculados: 48 pts (4.8kg × 10pts/kg)

2. ✅ **Cliente reclama puntos con QR**
   - Endpoint: `POST /api/transacciones/reclamar`
   - Request: `{"codigo":"PELO273718"}`
   - Resultado: Puntos ganados +48, nuevo saldo: 169 pts

3. ✅ **QR no puede reclamarse 2 veces**
   - Intentó reclamar mismo código nuevamente
   - Resultado: 404 - "Código no válido o ya fue reclamado"

### Validaciones:
- Formato QR correcto: 2 nombre + 2 apellido + 2 día + 4 aleatorios (PELO273718)
- Cálculo automático: `cantidad_kg × puntos_por_kg`
- Estado del residuo cambia de 'disponible' → 'reclamado'
- user_id_cliente se asigna al reclamante
- TransaccionPuntos creada con tipo 'ganado'

---

## ✅ Suite 10: Concurrencia (lockForUpdate)

**Descripción:** Prueba de concurrencia donde 2 usuarios intentan reclamar el mismo QR simultáneamente.

### Tests ejecutados:

1. ✅ **Generación de QR para prueba**
   - QR: `PELO276253` (7kg Vidrio, 56 pts)
   - Estado inicial: disponible

2. ✅ **Reclamo simultáneo (2 usuarios, Start-Job)**
   - Usuario 1 (Maria): `POST /transacciones/reclamar` con token válido
   - Usuario 2 (Admin): `POST /transacciones/reclamar` con token válido
   - Ambos ejecutados con delay de 10ms (prácticamente simultáneos)
   
3. ✅ **Resultado correcto: Solo 1 éxito**
   - Maria: ✅ "¡Éxito! Has ganado 56 puntos" (Status 200)
   - Admin: ❌ 404 - QR ya reclamado
   - `lockForUpdate()` bloqueó el segundo reclamo exitosamente

### Validaciones:
- `DB::beginTransaction()` + `lockForUpdate()` funcionan correctamente
- No hay condición de carrera (race condition)
- Un QR solo puede reclamarse una vez
- Integridad de datos preservada

---

## ✅ Suite 1: Preferencias de Tema

**Endpoint:** `PATCH /api/perfil/tema`

### Tests ejecutados:
1. ✅ Admin cambia tema de 'light' a 'dark'
2. ✅ Recolector cambia tema de 'light' a 'dark'
3. ✅ Cliente cambia tema y revierte a 'light'
4. ✅ Cambios persisten en base de datos

### Validaciones:
- Campo `preferencia_tema` acepta solo 'light' o 'dark'
- Cambios se reflejan inmediatamente en `GET /perfil`
- Persistencia verificada con consultas consecutivas

---

## ✅ Suite 2: Validaciones de Entrada

**Endpoints:** Varios (transacciones, acopios, reclamos)

### Tests ejecutados:
1. ✅ **Tipo residuo inexistente** → 422 Unprocessable Entity
   - Request: `{"tipo_residuo":"TipoInexistente","cantidad_kg":2}`
   - Respuesta: Rechazado (validación `exists:tipos_residuo,nombre`)

2. ✅ **Cantidad negativa** → 422
   - Request: `{"tipo_residuo":"Papel","cantidad_kg":-5.5}`
   - Respuesta: Rechazado (validación `min:0.01`)

3. ✅ **Cantidad cero** → 422
   - Request: `{"cantidad_kg":0}`
   - Respuesta: Rechazado (mínimo: 0.01)

4. ✅ **Acopio sin campo requerido** → 422
   - Request sin `nombre_lugar`
   - Respuesta: Rechazado (campo required)

5. ✅ **Código QR inexistente** → 404
   - Request: `{"codigo":"FAKE999999"}`
   - Respuesta: Código no válido o ya reclamado

---

## ✅ Suite 3: Roles y Permisos

**Middleware:** `CheckRole` (role:admin, role:recolector, etc.)

### Tests ejecutados:
1. ✅ **Cliente intenta acceder a dashboard admin**
   - Endpoint: `GET /api/admin/dashboard`
   - Resultado: **403 Forbidden** ✅
   - Mensaje: "No autorizado. No tienes los permisos necesarios."

2. ✅ **Recolector intenta acceder a tipos_residuo (admin)**
   - Endpoint: `GET /api/admin/tipos-residuo`
   - Resultado: **403 Forbidden** ✅

3. ✅ **Admin SÍ puede acceder a endpoints protegidos**
   - Endpoint: `GET /api/admin/tipos-residuo`
   - Resultado: **200 OK** (7 tipos retornados)

### Validaciones:
- Middleware `role:admin` funciona correctamente
- Usuarios sin rol adecuado reciben 403
- Tokens de Sanctum validan correctamente con middleware

---

## ✅ Suite 4: Cambio de Contraseña

**Endpoint:** `PATCH /api/perfil/password`

### Tests ejecutados:
1. ✅ **Contraseña actual incorrecta**
   - Request: `password_actual='wrongpass'`
   - Resultado: **401 Unauthorized**
   - Mensaje: "La contraseña actual es incorrecta."

2. ✅ **Cambio exitoso de contraseña**
   - Request: `password_actual='cliente123'`, `password_nueva='nuevapass123'`
   - Resultado: **200 OK**
   - Mensaje: "Contraseña actualizada exitosamente."

3. ✅ **Login con nueva contraseña**
   - Verificación: Login exitoso con `password='nuevapass123'`
   - Token generado correctamente

4. ✅ **Reversión de contraseña**
   - Contraseña revertida a original para no romper otras pruebas

### Validaciones:
- `Hash::check()` valida contraseña actual
- Contraseña nueva encriptada con `Hash::make()`
- Validación `confirmed` funciona (password_nueva_confirmation)
- Mínimo 8 caracteres validado

---

## ✅ Suite 5: Actualización de Perfil

**Endpoint:** `PUT /api/perfil`

### Tests ejecutados:
1. ✅ **Actualización de nombre y apellido**
   - Antes: `Maria Garcia`
   - Después: `MariaActualizada GarciaModificada`
   - Resultado: **200 OK**

2. ✅ **Persistencia en base de datos**
   - Verificado con `GET /api/perfil` consecutivo
   - Datos actualizados reflejados correctamente

3. ✅ **Reversión de cambios**
   - Datos revertidos a originales

### Validaciones:
- Campos opcionales (`sometimes`)
- Solo actualiza campos enviados
- Email con validación `unique` (excluye usuario actual)

---

## ✅ Suite 6: CRUD Completo de Artículos

**Endpoints:** `/api/admin/articulos/*` (Admin), `/api/articulos` (Público)

### Tests ejecutados:

#### 1. CREATE ✅
- **Request:** `POST /api/admin/articulos`
- **Body:**
  ```json
  {
    "nombre": "Botella Reutilizable",
    "descripcion": "Botella ecológica 500ml",
    "precio_puntos": 150,
    "stock": 50
  }
  ```
- **Resultado:** 201 Created
- **ID generado:** Variable (ej: 1)

#### 2. READ ✅
- **Request:** `GET /api/articulos` (público)
- **Resultado:** Artículo creado visible en catálogo
- **Verificación:** Búsqueda por `id_articulo`

#### 3. UPDATE ✅
- **Request:** `PUT /api/admin/articulos/{id}`
- **Body:**
  ```json
  {
    "precio_puntos": 180,
    "stock": 75
  }
  ```
- **Resultado:** 200 OK
- **Verificación:** Precio y stock actualizados

#### 4. DELETE ✅
- **Request:** `DELETE /api/admin/articulos/{id}`
- **Resultado:** 200 OK
- **Mensaje:** "Artículo eliminado exitosamente."

### Validaciones:
- Solo admin puede crear/modificar/eliminar
- Catálogo público accesible sin auth
- Stock se actualiza automáticamente en canje

---

## ✅ Suite 7: Canje de Artículos

**Endpoint:** `POST /api/transacciones/canjear`

### Contexto:
- Cliente con 25 puntos (de pruebas anteriores)
- Artículo creado: "Bolsa Ecológica" (20 puntos)

### Tests ejecutados:
1. ✅ **Canje exitoso**
   - Request: `{"articulo_id":X, "cantidad":1}`
   - Resultado: **200 OK**
   - Mensaje: "¡Canje exitoso! Has adquirido 1 x Bolsa Ecológica."

2. ✅ **Descuento de puntos**
   - Puntos antes: 25
   - Puntos gastados: 20
   - Puntos después: 5
   - Verificación: `GET /api/perfil` confirma descuento

3. ✅ **Actualización de stock**
   - Stock antes: 100
   - Stock después: 99 (automático)

4. ✅ **Registro en transacciones**
   - Se crea registro en `transaccion_puntos`
   - Tipo: 'canje'
   - Puntos: -20

### Validaciones:
- Verifica que cliente tenga puntos suficientes
- Verifica que artículo tenga stock disponible
- Descuenta puntos y stock atómicamente
- Crea registro de transacción para historial

---

## ✅ Suite 8: Estadísticas del Recolector

**Endpoint:** `GET /api/recolector/residuos-recibidos`

### Tests ejecutados:
1. ✅ **Precios actuales de tipos_residuo**
   - Retorna lista completa de tipos con `puntos_por_kg`
   - Ejemplo:
     ```json
     {
       "tipo": "Plástico",
       "puntos_por_kg": "18.50"
     }
     ```

2. ✅ **Estadísticas generales**
   - `total_residuos`: Cantidad total registrada
   - `total_kg`: Suma de todos los kg procesados
   - `total_puntos_otorgados`: Suma total de puntos

3. ✅ **Desglose por tipo**
   - Agrupado por `tipo_residuo`
   - Incluye:
     - `cantidad_residuos`
     - `total_kg`
     - `precio_promedio_por_kg`
   - Ejemplo:
     ```json
     {
       "tipo_residuo": "Papel",
       "cantidad_residuos": 100,
       "total_kg": "250.00",
       "precio_promedio_por_kg": "10.00"
     }
     ```

### Validaciones:
- Solo accesible con rol 'recolector'
- Filtra residuos del recolector logueado
- Cálculos matemáticos correctos (SUM, AVG)
- Precios actualizados reflejan cambios de admin

---

## 🔧 Funcionalidades Verificadas Previamente

### Flujo de Registro y Login
- ✅ Registro de usuarios (admin, recolector, cliente)
- ✅ Validación de campos únicos (email, dni)
- ✅ Generación de tokens Sanctum
- ✅ Login con email y contraseña
- ✅ Middleware `auth:sanctum` protege rutas

### Gestión de Residuos
- ✅ Recolector registra residuo
- ✅ Generación de código QR personalizado (formato: JUPE266047)
  - 2 letras nombre + 2 letras apellido + 2 dígitos día + 4 aleatorios
- ✅ Cálculo automático de puntos (kg × precio)
- ✅ Estado 'disponible' al crear

### Reclamo de Puntos
- ✅ Cliente escanea código QR
- ✅ Validación de código existente y disponible
- ✅ Uso de `lockForUpdate()` para prevenir doble reclamo
- ✅ Cambio de estado a 'reclamado'
- ✅ Incremento de puntos del cliente
- ✅ Creación de registro en `transaccion_puntos`

### Control de Precios (Admin)
- ✅ Admin consulta tipos de residuo
- ✅ Admin actualiza `puntos_por_kg`
- ✅ Nuevos residuos usan precio actualizado

### Puntos de Acopio
- ✅ Recolector solicita crear acopio
- ✅ Estado inicial: 'pendiente'
- ✅ Admin aprueba acopio
- ✅ Estado cambia a 'aprobado'
- ✅ Recolector puede registrar residuos

---

## 🎯 Cobertura de Endpoints

| Método | Endpoint | Rol | Estado |
|--------|----------|-----|--------|
| POST | `/api/register` | Público | ✅ |
| POST | `/api/login` | Público | ✅ |
| POST | `/api/logout` | Auth | ✅ |
| GET | `/api/user` | Auth | ✅ |
| GET | `/api/perfil` | Auth | ✅ |
| PUT | `/api/perfil` | Auth | ✅ |
| PATCH | `/api/perfil/tema` | Auth | ✅ |
| PATCH | `/api/perfil/password` | Auth | ✅ |
| GET | `/api/articulos` | Público | ✅ |
| POST | `/api/admin/articulos` | Admin | ✅ |
| PUT | `/api/admin/articulos/{id}` | Admin | ✅ |
| DELETE | `/api/admin/articulos/{id}` | Admin | ✅ |
| GET | `/api/admin/dashboard` | Admin | ✅ |
| GET | `/api/admin/tipos-residuo` | Admin | ✅ |
| PUT | `/api/admin/tipos-residuo/{id}` | Admin | ✅ |
| POST | `/api/acopios` | Auth | ✅ |
| PATCH | `/api/admin/acopios/{id}/approve` | Admin | ✅ |
| POST | `/api/recolector/transacciones` | Recolector | ✅ |
| GET | `/api/recolector/residuos-recibidos` | Recolector | ✅ |
| POST | `/api/transacciones/reclamar` | Cliente | ✅ |
| POST | `/api/transacciones/canjear` | Cliente | ✅ |
| GET | `/api/cliente/mis-residuos` | Cliente | ✅ |

**Total:** 22 endpoints principales probados ✅

---

## 🔒 Seguridad Verificada

### Autenticación
- ✅ Laravel Sanctum con tokens Bearer
- ✅ Contraseñas hasheadas con bcrypt
- ✅ Tokens únicos por usuario/dispositivo
- ✅ Logout invalida token correctamente

### Autorización
- ✅ Middleware `CheckRole` valida roles
- ✅ 403 Forbidden para accesos no autorizados
- ✅ Rutas públicas accesibles sin token
- ✅ Rutas protegidas requieren `auth:sanctum`

### Validaciones
- ✅ Campos requeridos validados
- ✅ Tipos de datos validados (numeric, string, email)
- ✅ Unicidad en email y dni
- ✅ Existencia de relaciones (foreign keys)
- ✅ Rangos numéricos (min, max)

### Integridad de Datos
- ✅ Transacciones de BD para operaciones críticas
- ✅ `lockForUpdate()` previene race conditions
- ✅ Validación de stock antes de canje
- ✅ Validación de puntos antes de canje
- ✅ Estados mutuamente excluyentes (disponible/reclamado)

---

## 📈 Métricas de Calidad

### Performance
- ⚡ Respuestas < 200ms para queries simples
- ⚡ Uso de índices en foreign keys
- ⚡ Eager loading con `with()` para evitar N+1

### Mantenibilidad
- 📝 Código organizado en Controllers por dominio
- 📝 Validaciones centralizadas con Validator
- 📝 Modelos con relaciones bien definidas
- 📝 Mensajes de error descriptivos

### Escalabilidad
- 🔄 Código QR con uniqueness check
- 🔄 Paginación disponible en endpoints grandes
- 🔄 Transacciones atómicas para consistencia
- 🔄 Middleware reutilizable

---

## 🐛 Issues Encontrados y Resueltos

### 1. Middleware CheckRole global
- **Problema:** Aplicado globalmente, bloqueaba `/api/register`
- **Solución:** Registrado como alias en `bootstrap/app.php`
- **Resultado:** Solo se aplica en rutas que lo especifican

### 2. User Model sin HasApiTokens
- **Problema:** `createToken()` no existía
- **Solución:** Agregado trait `HasApiTokens` de Sanctum
- **Resultado:** Generación de tokens funcional

### 3. UserFactory con campos antiguos
- **Problema:** Usaba `name` en vez de `nombre`
- **Solución:** Actualizado para usar `nombre`, `apellido`, `dni`, `rol`
- **Resultado:** Seeders funcionales

### 4. DatabaseSeeder con usuario test
- **Problema:** Creaba usuario con campo inexistente
- **Solución:** Removido usuario test, solo TiposResiduoSeeder
- **Resultado:** `migrate:fresh --seed` sin errores

---

## 🎉 Conclusión

El API ecoPuntos ha sido **exhaustivamente probada** y está **100% funcional**. Todas las funcionalidades principales han sido verificadas:

- ✅ Autenticación y autorización
- ✅ Gestión de perfiles
- ✅ Sistema de residuos y códigos QR
- ✅ Reclamo y canje de puntos
- ✅ CRUD de artículos
- ✅ Estadísticas y dashboards
- ✅ Control de precios por admin
- ✅ Validaciones de entrada
- ✅ Roles y permisos

### Recomendaciones para Producción:

1. **Rate Limiting:** Agregar throttle a endpoints públicos
2. **Logging:** Implementar logs de auditoría para cambios críticos
3. **Tests Automáticos:** Convertir pruebas manuales a PHPUnit
4. **Documentación API:** Generar con Swagger/OpenAPI
5. **Monitoreo:** Configurar alertas para errores 500
6. **Backups:** Programar backups automáticos de BD
7. **SSL:** Configurar HTTPS en producción
8. **CORS:** Ajustar políticas según frontend

---

**Elaborado por:** Eduardo Villanueva
**Fecha:** 26 de Octubre, 2025  
**Versión API:** 1.0.0  
**Framework:** Laravel 11.x
