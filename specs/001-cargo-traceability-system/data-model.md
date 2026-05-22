# Data Model: Sistema de Trazabilidad de Carga

**Date**: 2026-03-21
**Spec**: [spec.md](spec.md)
**Storage**: MySQL 8.0+

---

## Entity Relationship Overview

```
User (1)──(N) Solicitud (1)──(1) OrdenServicio (1)──(1) Contenedor
                                                          │
                                          ┌───────────────┼───────────────┐
                                          │               │               │
                                    OrdenVaciado    Referencia(N)    GateEvent(N)
                                          │               │
                                     Novedad(N)     UbicacionPatio
                                                          │
                                                    OrdenCargue(N)
                                                          │
                                                      Tarja(N)
                                                          │
                                                   TarjaDetalle(N)

Contenedor ──(N) Photo (polimórfico)
Novedad ──(N) Photo (polimórfico)
GateEvent ──(N) Photo (polimórfico)
```

---

## Entities

### 1. User

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | |
| email | VARCHAR(255) | NOT NULL, UNIQUE | |
| password | VARCHAR(255) | NOT NULL | Hashed |
| phone | VARCHAR(20) | NULLABLE | WhatsApp del usuario |

| email_verified_at | TIMESTAMP | NULLABLE | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Roles** (via Spatie Permission): `cliente`, `portero`, `operador`, `coordinador`, `supervisor`, `despachador`, `gerente`, `administrador`

**Índices**: `email` (unique)

---

### 2. Solicitud

Petición del cliente para retirar un contenedor del puerto.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| cliente_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Cliente que solicita |
| numero_contenedor | VARCHAR(20) | NOT NULL | Código ISO del contenedor |
| naviera | VARCHAR(100) | NULLABLE | Línea naviera |
| puerto_origen | VARCHAR(100) | NULLABLE | Puerto de retiro |
| descripcion | TEXT | NULLABLE | Notas adicionales |
| estado | ENUM | NOT NULL, DEFAULT 'pendiente' | Ver estados abajo |
| fecha_solicitud | TIMESTAMP | NOT NULL, DEFAULT CURRENT_TIMESTAMP | FR-002 |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Estados**: `pendiente`, `asignada`, `en_proceso`, `completada`, `cancelada`

**Índices**: `cliente_id`, `numero_contenedor`, `estado`

---

### 3. Documento

Documentos adjuntos a la solicitud (BL, factura comercial, packing list, etc.).

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| solicitud_id | BIGINT UNSIGNED | FK → solicitudes.id, NOT NULL | FR-001 |
| nombre | VARCHAR(255) | NOT NULL | Nombre original del archivo |
| ruta | VARCHAR(500) | NOT NULL | Path en Storage |
| tipo_mime | VARCHAR(100) | NOT NULL | |
| tamaño | INT UNSIGNED | NOT NULL | Bytes |
| created_at | TIMESTAMP | | |

**Índices**: `solicitud_id`

---

### 4. OrdenServicio

Generada automáticamente al confirmar asignación de vehículo/conductor.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| solicitud_id | BIGINT UNSIGNED | FK → solicitudes.id, NOT NULL, UNIQUE | 1:1 con solicitud |
| coordinador_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Quien asigna |
| vehiculo | VARCHAR(20) | NOT NULL | Placa del vehículo |
| conductor | VARCHAR(255) | NOT NULL | Nombre del conductor |
| conductor_documento | VARCHAR(20) | NULLABLE | Cédula/ID |
| cita_puerto | DATETIME | NOT NULL | Fecha y hora de cita |
| estado | ENUM | NOT NULL, DEFAULT 'activa' | Ver estados |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Estados**: `activa`, `en_ejecucion`, `completada`, `cancelada`

**Índices**: `solicitud_id` (unique), `coordinador_id`, `estado`

---

### 5. Contenedor

Entidad central del ciclo operativo.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| orden_servicio_id | BIGINT UNSIGNED | FK → ordenes_servicio.id, NOT NULL | |
| numero | VARCHAR(20) | NOT NULL | Código ISO contenedor |
| placa_vehiculo | VARCHAR(20) | NOT NULL | Placa del camión FR-007 |
| tipo | VARCHAR(50) | NULLABLE | 20', 40', 40'HC, etc. |
| estado | ENUM | NOT NULL, DEFAULT 'solicitado' | FR-009, FR-015, FR-031 |
| fecha_ingreso | DATETIME | NULLABLE | Gate In timestamp |
| fecha_salida | DATETIME | NULLABLE | Gate Out timestamp |
| limpieza_registrada | BOOLEAN | DEFAULT FALSE | FR-027 |
| destino_salida | VARCHAR(100) | NULLABLE | Puerto o patio naviera FR-028 |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Estados**: `solicitado`, `en_patio`, `en_vaciado`, `vaciado_completado`, `fuera_de_patio`

**State Transitions**:
```
solicitado → en_patio (Gate In)
en_patio → en_vaciado (inicio vaciado)
en_vaciado → vaciado_completado (fin vaciado)
vaciado_completado → fuera_de_patio (Gate Out)
```

**Índices**: `orden_servicio_id`, `numero`, `estado`, `fecha_ingreso`

---

### 6. GateEvent

Registro de eventos de entrada/salida del patio (Gate In / Gate Out).

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| contenedor_id | BIGINT UNSIGNED | FK → contenedores.id, NOT NULL | |
| tipo | ENUM('gate_in', 'gate_out') | NOT NULL | |
| usuario_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Portero que registra FR-007 |
| hora | DATETIME | NOT NULL | Hora exacta FR-007 |
| estado_fisico | TEXT | NULLABLE | Observaciones del estado |
| notas | TEXT | NULLABLE | |
| created_at | TIMESTAMP | | |

**Índices**: `contenedor_id`, `tipo`, `usuario_id`

---

### 7. Referencia

Ítems de mercancía dentro de un contenedor.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| contenedor_id | BIGINT UNSIGNED | FK → contenedores.id, NOT NULL | FR-010, FR-011 |
| cliente_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | FR-011 |
| codigo | VARCHAR(100) | NOT NULL | Código de referencia |
| descripcion | VARCHAR(255) | NULLABLE | |
| cantidad_inicial | INT UNSIGNED | NOT NULL | Cantidad al ingreso |
| cantidad_actual | INT UNSIGNED | NOT NULL | Cantidad en inventario FR-037 |
| unidad_medida | VARCHAR(50) | DEFAULT 'unidades' | |
| ubicacion_patio_id | BIGINT UNSIGNED | FK → ubicaciones_patio.id, NULLABLE | FR-019 |
| fecha_ingreso | DATETIME | NOT NULL | Para cálculo de días FR-025 |
| fecha_salida | DATETIME | NULLABLE | FR-025 |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Índices**: `contenedor_id`, `cliente_id`, `codigo`, `ubicacion_patio_id`

---

### 8. UbicacionPatio

Posiciones físicas en el patio de almacenamiento.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| modulo | VARCHAR(50) | NOT NULL | Nombre/código del módulo FR-019 |
| posicion | VARCHAR(50) | NOT NULL | Posición exacta dentro del módulo |
| descripcion | VARCHAR(255) | NULLABLE | |
| activa | BOOLEAN | DEFAULT TRUE | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Índices**: `modulo`, (`modulo`, `posicion`) UNIQUE

---

### 9. OrdenVaciado

Programación del descargue de un contenedor.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| contenedor_id | BIGINT UNSIGNED | FK → contenedores.id, NOT NULL | FR-013 |
| supervisor_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Quien crea la orden |
| fecha_programada | DATETIME | NOT NULL | FR-014 |
| fecha_inicio | DATETIME | NULLABLE | Cuando inicia el vaciado |
| fecha_fin | DATETIME | NULLABLE | Cuando termina |
| estado | ENUM | NOT NULL, DEFAULT 'programada' | |
| notas | TEXT | NULLABLE | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Estados**: `programada`, `en_proceso`, `completada`, `cancelada`

**Índices**: `contenedor_id`, `supervisor_id`, `estado`, `fecha_programada`

---

### 10. Novedad

Incidencias durante el vaciado.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| orden_vaciado_id | BIGINT UNSIGNED | FK → ordenes_vaciado.id, NOT NULL | |
| operador_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Quien registra |
| tipo | ENUM('averia', 'faltante', 'dano_visible') | NOT NULL | FR-016 |
| descripcion | TEXT | NOT NULL | |
| referencia_id | BIGINT UNSIGNED | FK → referencias.id, NULLABLE | Referencia afectada |
| created_at | TIMESTAMP | | |

**Índices**: `orden_vaciado_id`, `tipo`, `referencia_id`

---

### 11. OrdenCargue

Solicitud de despacho de mercancía al cliente.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| cliente_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | FR-033 |
| despachador_id | BIGINT UNSIGNED | FK → users.id, NULLABLE | FR-038 |
| fecha_despacho | DATETIME | NOT NULL | FR-034 |
| estado | ENUM | NOT NULL, DEFAULT 'pendiente' | |
| notas | TEXT | NULLABLE | |
| created_at | TIMESTAMP | | |
| updated_at | TIMESTAMP | | |

**Estados**: `pendiente`, `programada`, `en_proceso`, `completada`, `cancelada`

**Índices**: `cliente_id`, `despachador_id`, `estado`, `fecha_despacho`

---

### 12. Tarja

Documento de entrega que registra lo despachado.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| orden_cargue_id | BIGINT UNSIGNED | FK → ordenes_cargue.id, NOT NULL | |
| despachador_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | FR-038 |
| fecha_entrega | DATETIME | NOT NULL | |
| observaciones | TEXT | NULLABLE | |
| created_at | TIMESTAMP | | |

**Índices**: `orden_cargue_id`, `despachador_id`

---

### 13. TarjaDetalle

Líneas de detalle de cada tarja con referencias y cantidades entregadas.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| tarja_id | BIGINT UNSIGNED | FK → tarjas.id, NOT NULL | |
| referencia_id | BIGINT UNSIGNED | FK → referencias.id, NOT NULL | FR-036 |
| cantidad_entregada | INT UNSIGNED | NOT NULL | FR-037 |
| ubicacion_origen_id | BIGINT UNSIGNED | FK → ubicaciones_patio.id, NOT NULL | FR-036 |
| created_at | TIMESTAMP | | |

**Índices**: `tarja_id`, `referencia_id`

**Trigger lógico**: Al crear TarjaDetalle, `Referencia.cantidad_actual -= cantidad_entregada` (FR-037). Implementado en `EntregaService`, no en trigger de BD.

---

### 14. Photo (polimórfico)

Fotos adjuntas a múltiples entidades.

| Campo | Tipo | Restricciones | Notas |
|-------|------|---------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| photoable_type | VARCHAR(255) | NOT NULL | Morph type (GateEvent, Novedad) |
| photoable_id | BIGINT UNSIGNED | NOT NULL | Morph ID |
| ruta | VARCHAR(500) | NOT NULL | Path en Storage |
| nombre | VARCHAR(255) | NULLABLE | Nombre original |
| tamaño | INT UNSIGNED | NULLABLE | Bytes |
| created_at | TIMESTAMP | | |

**Índices**: (`photoable_type`, `photoable_id`)

**Aplica a**: GateEvent (FR-008, FR-029), Novedad (FR-017)

---

## Cálculos derivados

### Días de almacenamiento (FR-025, FR-026)

```
dias_almacenamiento = DATEDIFF(
    COALESCE(referencia.fecha_salida, NOW()),
    referencia.fecha_ingreso
)
```

Calculado on-the-fly en `InventarioService`. No almacenado — siempre derivado. Visible en inventario y reportes (FR-026).

### Inventario en tiempo real (FR-022)

Query base del inventario:
```sql
SELECT r.*, u.modulo, u.posicion, c.numero AS contenedor,
       DATEDIFF(COALESCE(r.fecha_salida, NOW()), r.fecha_ingreso) AS dias_almacenamiento
FROM referencias r
JOIN ubicaciones_patio u ON r.ubicacion_patio_id = u.id
JOIN contenedores c ON r.contenedor_id = c.id
WHERE r.cantidad_actual > 0
```

Filtros: `cliente_id`, `codigo`, `modulo`, rango de fechas (FR-023).

---

## Migration Order

1. `users` (base, existente en Laravel)
2. `ubicaciones_patio` (catálogo, sin FK externa)
3. `solicitudes` (FK → users)
4. `documentos` (FK → solicitudes)
5. `ordenes_servicio` (FK → solicitudes, users)
6. `contenedores` (FK → ordenes_servicio)
7. `gate_events` (FK → contenedores, users)
8. `referencias` (FK → contenedores, users, ubicaciones_patio)
9. `ordenes_vaciado` (FK → contenedores, users)
10. `novedades` (FK → ordenes_vaciado, users, referencias)
11. `ordenes_cargue` (FK → users)
12. `tarjas` (FK → ordenes_cargue, users)
13. `tarja_detalles` (FK → tarjas, referencias, ubicaciones_patio)
14. `photos` (polimórfico, sin FK duras)
15. Spatie Permission tables (`roles`, `permissions`, `model_has_roles`, etc.)