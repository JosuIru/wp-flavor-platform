# Módulo: Gestión de Clientes (CRM)

> CRM básico para gestionar clientes, notas e interacciones

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `clientes` |
| **Versión** | 1.0.0+ |
| **Categoría** | Gestión / Empresarial |
| **Disponible en App** | Admin |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`

---

## Descripción

CRM básico para pequeñas empresas y organizaciones. Permite gestionar clientes, registrar interacciones, añadir notas y hacer seguimiento de relaciones comerciales.

### Características Principales

- **Gestión de Clientes**: Alta, baja y modificación
- **Notas e Interacciones**: Registro de llamadas, emails, reuniones
- **Etiquetas**: Clasificación por etiquetas
- **Estados**: Flujo de cliente potencial a cliente activo
- **Valor Estimado**: Tracking del valor comercial
- **Asignación**: Asignar clientes a usuarios

---

## Tablas de Base de Datos

### `{prefix}_flavor_clientes`

Tabla principal de clientes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(255) | Nombre del cliente |
| `email` | varchar(100) | Email principal |
| `telefono` | varchar(30) | Teléfono |
| `empresa` | varchar(255) | Nombre de empresa |
| `cargo` | varchar(100) | Cargo/puesto |
| `direccion` | text | Dirección completa |
| `tipo` | enum | Tipo de cliente |
| `estado` | enum | Estado actual |
| `etiquetas` | text | Tags separados por comas |
| `valor_estimado` | decimal(12,2) | Valor comercial |
| `origen` | enum | Cómo llegó el cliente |
| `asignado_a` | bigint | Usuario responsable |
| `notas_count` | int | Contador de notas |
| `ultima_interaccion` | datetime | Fecha última nota |
| `created_at` | datetime | Fecha de creación |
| `updated_at` | datetime | Última modificación |

### `{prefix}_flavor_clientes_notas`

Notas e interacciones con clientes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `cliente_id` | bigint | Cliente relacionado |
| `usuario_id` | bigint | Usuario que registra |
| `contenido` | text | Contenido de la nota |
| `tipo` | enum | Tipo de interacción |
| `estado` | enum | Estado de la nota |
| `fecha_seguimiento` | datetime | Fecha de seguimiento |
| `created_at` | datetime | Fecha de registro |

---

## Tipos de Cliente

| Tipo | Nombre |
|------|--------|
| `particular` | Particular |
| `empresa` | Empresa |
| `autonomo` | Autónomo |
| `administracion` | Administración |

## Estados de Cliente

| Estado | Nombre | Descripción |
|--------|--------|-------------|
| `potencial` | Potencial | Lead o prospecto |
| `activo` | Activo | Cliente actual |
| `inactivo` | Inactivo | Sin actividad reciente |
| `perdido` | Perdido | Cliente perdido |

## Orígenes de Cliente

| Origen | Nombre |
|--------|--------|
| `web` | Web |
| `referido` | Referido |
| `redes` | Redes Sociales |
| `directo` | Directo |
| `otro` | Otro |

## Tipos de Nota/Interacción

| Tipo | Nombre |
|------|--------|
| `nota` | Nota general |
| `llamada` | Llamada telefónica |
| `email` | Email |
| `reunion` | Reunión |
| `tarea` | Tarea |
| `seguimiento` | Seguimiento |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `limite_resultados_por_defecto` | int | 20 | Resultados por página |
| `tipos_cliente` | array | [...] | Tipos disponibles |
| `estados_cliente` | array | [...] | Estados disponibles |
| `origenes_cliente` | array | [...] | Orígenes disponibles |
| `tipos_nota` | array | [...] | Tipos de nota |

---

## REST API Endpoints

### Clientes

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/flavor/v1/clientes` | GET | Listar clientes |
| `/flavor/v1/clientes` | POST | Crear cliente |
| `/flavor/v1/clientes/{id}` | GET | Obtener cliente |
| `/flavor/v1/clientes/{id}` | PUT | Actualizar cliente |
| `/flavor/v1/clientes/buscar` | GET | Buscar clientes |
| `/flavor/v1/clientes/estadisticas` | GET | Estadísticas CRM |

### Notas

| Endpoint | Método | Descripción |
|----------|--------|-------------|
| `/flavor/v1/clientes/{id}/notas` | POST | Agregar nota |

### Parámetros de Listado

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `estado` | string | Filtrar por estado |
| `tipo` | string | Filtrar por tipo |
| `etiquetas` | string | Filtrar por etiquetas |
| `limite` | int | Resultados (default: 20) |
| `pagina` | int | Página actual |

### Parámetros de Nota

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `contenido` | string | Sí | Contenido de la nota |
| `tipo` | string | No | Tipo de interacción |
| `estado` | string | No | Estado de la nota |
| `fecha_seguimiento` | string | No | Fecha de seguimiento |

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[flavor_clientes_dashboard]` | Dashboard del CRM |
| `[flavor_clientes_listado]` | Listado de clientes |
| `[flavor_clientes_crear]` | Formulario alta cliente |
| `[flavor_clientes_buscar]` | Buscador de clientes |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `clientes-dashboard` | Panel con estadísticas |
| Clientes | `clientes-listado` | Gestión de clientes |
| Nuevo | `clientes-nuevo` | Formulario de alta |
| Configuración | `clientes-config` | Ajustes del módulo |

---

## Permisos

| Acción | Capability |
|--------|------------|
| Ver clientes | `manage_options` |
| Crear clientes | `manage_options` |
| Editar clientes | `manage_options` |
| Eliminar clientes | `manage_options` |
| Ver estadísticas | `manage_options` |

---

## Flujo de Trabajo

```
1. CAPTAR
   Cliente entra como "potencial"
   Origen registrado (web, referido, etc.)
   ↓
2. INTERACTUAR
   Se registran notas y llamadas
   Se actualiza última interacción
   ↓
3. CONVERTIR
   Estado cambia a "activo"
   Se asigna valor estimado
   ↓
4. MANTENER
   Seguimiento continuo
   Notas de reuniones y emails
   ↓
5. (OPCIONAL) RECUPERAR
   Si pasa a "inactivo" o "perdido"
   Acciones de reactivación
```

---

## Estadísticas Disponibles

El endpoint de estadísticas devuelve:

- Total de clientes por estado
- Total de clientes por tipo
- Valor total estimado
- Clientes nuevos este mes
- Interacciones recientes
- Seguimientos pendientes

---

## Notas de Implementación

- Solo accesible para administradores
- Las etiquetas se almacenan como texto separado por comas
- La búsqueda es fulltext en nombre, email, empresa
- Compatible con exportación CSV
- Las notas soportan fechas de seguimiento para recordatorios
