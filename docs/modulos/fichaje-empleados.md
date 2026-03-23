# Módulo: Fichaje de Empleados

> Control de horarios, asistencia y fichaje desde la app móvil

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `fichaje_empleados` |
| **Versión** | 1.0.0+ |
| **Categoría** | Gestión / Personas |
| **Visibilidad** | Privado (solo empleados con permiso) |
| **Capability** | `flavor_fichaje_acceso` |

---

## Descripción

Sistema completo de control de presencia para empleados. Permite fichar entrada, salida y pausas desde la app móvil, con opción de geolocalización. Incluye historial, resúmenes mensuales y gestión de horarios.

### Características Principales

- **Fichaje Móvil**: Entrada, salida, pausas desde la app
- **Geolocalización**: Verificación de ubicación (opcional)
- **Historial**: Registro completo de fichajes
- **Resumen Mensual**: Horas trabajadas por mes
- **Pausas**: Comida, descanso, reuniones
- **Validación**: Admin puede validar/corregir fichajes
- **Multi-empresa**: Soporte para múltiples empresas

---

## Tablas de Base de Datos

### `{prefix}_flavor_fichajes`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `empresa_id` | bigint | Empresa (si aplica) |
| `usuario_id` | bigint | Usuario empleado |
| `tipo` | enum | `entrada`, `salida`, `pausa_inicio`, `pausa_fin` |
| `fecha_hora` | datetime | Momento del fichaje |
| `latitud` | decimal(10,8) | Latitud GPS |
| `longitud` | decimal(11,8) | Longitud GPS |
| `dispositivo` | varchar(100) | Identificador del dispositivo |
| `ip_address` | varchar(45) | Dirección IP |
| `notas` | text | Notas del fichaje |
| `validado` | tinyint | Fichaje validado |
| `validado_por` | bigint | Quién validó |
| `fecha_creacion` | datetime | Fecha de registro |

**Índices:**
- `usuario_id`
- `empresa_id`
- `fecha_hora`
- `tipo`

### `{prefix}_flavor_empleados_horarios`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `empresa_id` | bigint | Empresa |
| `usuario_id` | bigint | Usuario empleado |
| `dia_semana` | enum | `monday` a `sunday` |
| `hora_entrada` | time | Hora de entrada |
| `hora_salida` | time | Hora de salida |
| `es_laboral` | tinyint | Día laboral |
| `activo` | tinyint | Horario activo |

**Unique Key:** `usuario_id`, `dia_semana`, `empresa_id`

---

## Tipos de Fichaje

| Tipo | Descripción |
|------|-------------|
| `entrada` | Inicio de jornada laboral |
| `salida` | Fin de jornada laboral |
| `pausa_inicio` | Comienzo de descanso |
| `pausa_fin` | Fin del descanso |

## Tipos de Pausa

| Tipo | Descripción |
|------|-------------|
| `comida` | Pausa para comer |
| `descanso` | Descanso breve |
| `reunion` | Reunión externa |
| `otros` | Otros motivos |

---

## Endpoints API REST

Base: `/wp-json/flavor/v1/`

| Endpoint | Método | Descripción | Auth |
|----------|--------|-------------|------|
| `/fichaje/entrada` | POST | Registrar entrada | Usuario |
| `/fichaje/salida` | POST | Registrar salida | Usuario |
| `/fichaje/estado` | GET | Estado actual | Usuario |
| `/fichaje/historial` | GET | Historial de fichajes | Usuario |
| `/fichaje/resumen` | GET | Resumen mensual | Usuario |
| `/fichaje/pausa/iniciar` | POST | Iniciar pausa | Usuario |
| `/fichaje/pausa/finalizar` | POST | Finalizar pausa | Usuario |
| `/fichaje/hoy` | GET | Fichajes del día | Usuario |

### POST `/fichaje/entrada`

**Parámetros:**
- `notas` (string) - Notas opcionales
- `latitud` (number) - Latitud GPS
- `longitud` (number) - Longitud GPS
- `dispositivo` (string) - ID del dispositivo

**Respuesta:**
```json
{
  "success": true,
  "fichaje_id": 123,
  "mensaje": "Juan ha fichado entrada correctamente a las 09:05"
}
```

### GET `/fichaje/estado`

**Respuesta:**
```json
{
  "success": true,
  "estado": "trabajando",
  "ultimo_fichaje": {
    "tipo": "entrada",
    "hora": "09:05"
  },
  "mensaje": "Tu último fichaje fue de entrada a las 09:05. Estado actual: trabajando."
}
```

### GET `/fichaje/historial`

**Parámetros:**
- `desde` (date) - Fecha inicio YYYY-MM-DD
- `hasta` (date) - Fecha fin YYYY-MM-DD
- `tipo` (string) - Filtrar por tipo
- `limite` (int) - Máximo resultados (default: 50)

**Respuesta:**
```json
{
  "success": true,
  "total": 25,
  "fichajes": [
    {
      "id": 123,
      "tipo": "entrada",
      "fecha": "2026-03-20",
      "hora": "09:05",
      "fecha_hora": "2026-03-20 09:05:00",
      "notas": "",
      "validado": true,
      "latitud": 43.2627,
      "longitud": -2.9253,
      "dispositivo": "app_movil"
    }
  ]
}
```

### GET `/fichaje/resumen`

**Parámetros:**
- `mes` (int) - Mes 1-12
- `anio` (int) - Año YYYY

**Respuesta:**
```json
{
  "success": true,
  "resumen": {
    "mes": 3,
    "anio": 2026,
    "dias_trabajados": 15,
    "total_horas": 120.50,
    "total_pausas": 15.00,
    "promedio_horas_diarias": 8.03
  },
  "detalle_dias": [
    {
      "fecha": "2026-03-01",
      "horas_trabajadas": 8.25,
      "tiempo_pausas": 1.00,
      "fichajes": 4
    }
  ],
  "mensaje": "En 03/2026 has trabajado 15 dias con un total de 120.50 horas."
}
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `horario_entrada` | time | 09:00 | Hora entrada estándar |
| `horario_salida` | time | 18:00 | Hora salida estándar |
| `tiempo_gracia` | int | 15 | Minutos de gracia |
| `requiere_geolocalizacion` | bool | false | Exigir ubicación |
| `radio_maximo` | int | 100 | Radio máximo en metros |
| `dias_laborables` | array | Lun-Vie | Días de trabajo |
| `permite_fichaje_remoto` | bool | true | Permitir teletrabajo |
| `notificar_retrasos` | bool | true | Alertar retrasos |

---

## AJAX Actions

No implementa AJAX tradicional. Usa exclusivamente REST API para la app móvil.

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[fichaje_panel]` | Panel con estado actual |
| `[fichaje_historial periodo="semana"]` | Historial de fichajes |
| `[fichaje_solicitar_cambio]` | Formulario corrección |
| `[flavor_module_form module="fichaje_empleados" action="X"]` | Formularios dinámicos |

---

## Dashboard Tabs

| Tab | Descripción | Requiere Login | Capability |
|-----|-------------|----------------|------------|
| Estado actual | Panel de fichaje | Sí | flavor_fichaje_acceso |
| Fichar entrada | Formulario entrada | Sí | flavor_fichaje_acceso |
| Fichar salida | Formulario salida | Sí | flavor_fichaje_acceso |
| Mis fichajes | Historial | Sí | flavor_fichaje_acceso |
| Solicitar corrección | Formulario | Sí | flavor_fichaje_acceso |

---

## Estados del Empleado

| Estado | Descripción | Condición |
|--------|-------------|-----------|
| `sin_fichar` | No ha fichado hoy | Sin fichajes del día |
| `trabajando` | Trabajando | Último = entrada o pausa_fin |
| `en_pausa` | En pausa | Último = pausa_inicio |
| `fuera` | Fuera del trabajo | Último = salida |

---

## Páginas de Administración

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `fichaje-dashboard` | Panel principal |
| Registros de hoy | `fichaje-registros-hoy` | Fichajes del día |
| Historial | `fichaje-historial` | Historial completo |
| Empleados | `fichaje-empleados` | Gestión de horarios |
| Configuración | `fichaje-config` | Ajustes del módulo |

---

## Estadísticas Dashboard

```json
[
  {
    "icon": "dashicons-clock",
    "valor": 45,
    "label": "Fichajes hoy",
    "color": "blue"
  },
  {
    "icon": "dashicons-groups",
    "valor": 12,
    "label": "Empleados activos",
    "color": "green"
  },
  {
    "icon": "dashicons-warning",
    "valor": 3,
    "label": "Pendientes validar",
    "color": "orange"
  }
]
```

---

## Cálculo de Horas

El sistema calcula automáticamente las horas trabajadas:

```
Horas = Σ (salida|pausa_inicio - entrada|pausa_fin)
```

- Entrada/pausa_fin inician período de trabajo
- Salida/pausa_inicio cierran período de trabajo
- Las pausas se contabilizan separadamente

---

## Multi-Empresa

El módulo soporta múltiples empresas:

1. Cada fichaje puede tener `empresa_id`
2. Los horarios se definen por empresa
3. Los filtros aplican automáticamente
4. Integración con módulo Empresas si está activo

---

## Formularios Frontend

### Fichar Entrada

- Notas opcionales
- Proyecto/tarea
- Ubicación (si configurado)

### Fichar Salida

- Resumen del día
- Notas opcionales

### Solicitar Corrección

- Fecha del fichaje
- Tipo (entrada/salida)
- Hora correcta
- Motivo de corrección

---

## Componentes Web

| Componente | Descripción |
|------------|-------------|
| `hero` | Hero con botón de fichaje |
| `boton_fichaje` | Botón grande entrada/salida |
| `historial` | Tabla de fichajes |
| `resumen_horas` | Gráfico de horas |

---

## Permisos

| Capability | Descripción |
|------------|-------------|
| `flavor_fichaje_acceso` | Acceso básico al módulo |
| `manage_options` | Gestión completa (admin) |

---

## Notas de Implementación

- El módulo es privado por defecto (visibilidad restringida)
- Usa REST API para integración con app móvil
- La geolocalización es opcional y configurable
- Los fichajes no validados se marcan con `validado = 0`
- Soporta dispositivos múltiples por usuario
- Las correcciones se registran como fichajes pendientes de validación
- Integración con Page Creator V3 para páginas automáticas
- Crea tablas automáticamente si no existen

