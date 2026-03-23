# Módulo: Kulturaka

> Red Cultural Descentralizada

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `kulturaka` |
| **Versión** | 3.3.0+ |
| **Categoría** | Cultura / Economía Solidaria |
| **Rol** | Ecosystem (orquestador) |
| **Requiere** | eventos, espacios-comunes, socios |
| **Soporta** | crowdfunding, banco-tiempo, comunidades, colectivos |

### Principios Gailu

- `economia_solidaria` - Distribución justa de ingresos
- `cooperacion` - Colaboración entre artistas y espacios
- `cultura_accesible` - Cultura para todos

---

## Descripción

Kulturaka es un módulo ecosistema que conecta **artistas**, **espacios culturales** y **comunidades** en una red cultural descentralizada. Orquesta 3 vistas principales integrando módulos existentes del ecosistema Flavor.

### Características Principales

- **Vista Espacio**: Gestión de espacios culturales, calendario, propuestas recibidas
- **Vista Artista**: Perfil de artista, gira, propuestas enviadas, crowdfunding
- **Vista Comunidad**: Eventos cercanos, proyectos activos, muro de agradecimientos
- **Sistema de Propuestas**: Artistas proponen eventos a espacios con negociación
- **Distribución de Ingresos**: Reparto justo (artista 70%, espacio 10%, comunidad 10%, etc.)
- **Métricas de Impacto**: CO2 evitado, índice de cooperación
- **Red de Nodos**: Conexión entre diferentes localidades

---

## Tablas de Base de Datos

### `{prefix}_flavor_kulturaka_agradecimientos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `de_usuario_id` | bigint | Usuario que envía |
| `para_usuario_id` | bigint | Usuario que recibe |
| `evento_id` | bigint | Evento relacionado (opcional) |
| `tipo` | enum | `gracias`, `apoyo`, `colaboracion` |
| `mensaje` | text | Mensaje del agradecimiento |
| `publico` | tinyint | Visible públicamente |
| `comunidad_id` | bigint | Comunidad asociada |
| `created_at` | datetime | Fecha de creación |

### `{prefix}_flavor_kulturaka_nodos`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(200) | Nombre del nodo |
| `lat` | decimal | Latitud |
| `lng` | decimal | Longitud |
| `activo` | tinyint | Estado activo |
| `espacios_count` | int | Contador de espacios |
| `artistas_count` | int | Contador de artistas |
| `eventos_realizados` | int | Total eventos |
| `artistas_apoyados` | int | Artistas únicos |
| `fondos_recaudados` | decimal | Total recaudado |

---

## Endpoints API REST

Base: `/wp-json/flavor/v1/kulturaka/`

| Endpoint | Método | Descripción | Autenticación |
|----------|--------|-------------|---------------|
| `/nodos` | GET | Listar nodos de la red | Público |
| `/propuestas` | POST | Crear propuesta de evento | Usuario logueado |
| `/agradecimientos` | POST | Enviar agradecimiento | Usuario logueado |

### POST `/propuestas`

```json
{
  "espacio_id": 123,
  "titulo": "Concierto de jazz",
  "descripcion": "Descripción del evento",
  "tipo": "concierto",
  "fecha_propuesta": "2026-04-15",
  "cache": 500,
  "acepta_semilla": true,
  "acepta_hours": false,
  "necesidades_tecnicas": "PA system, 2 micros"
}
```

### POST `/agradecimientos`

```json
{
  "para_usuario_id": 456,
  "evento_id": 789,
  "tipo": "gracias",
  "mensaje": "Gracias por el increíble concierto",
  "publico": true
}
```

---

## Shortcodes

| Shortcode | Descripción | Atributos |
|-----------|-------------|-----------|
| `[kulturaka_dashboard]` | Dashboard con 3 vistas | `vista_inicial` |
| `[kulturaka_vista_espacio]` | Vista de espacio cultural | - |
| `[kulturaka_vista_artista]` | Vista de artista | - |
| `[kulturaka_vista_comunidad]` | Vista de comunidad | - |
| `[kulturaka_mapa_nodos]` | Mapa de la red | - |
| `[kulturaka_muro_agradecimientos]` | Muro de agradecimientos | - |

### Ejemplo Dashboard

```
[kulturaka_dashboard vista_inicial="comunidad"]
```

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `distribucion_ingresos` | array | Ver abajo | Reparto de ingresos |
| `tipos_espacio` | array | Ver abajo | Tipos de espacio cultural |
| `tipos_evento` | array | Ver abajo | Tipos de evento cultural |
| `nodos_default` | array | Euskal Herria | Nodos geográficos iniciales |
| `co2_por_km` | float | 0.12 | kg CO2 evitado por km |

### Distribución de Ingresos por Defecto

```php
[
    'artista' => 70,      // 70% para el artista
    'espacio' => 10,      // 10% para el espacio
    'comunidad' => 10,    // 10% para la comunidad
    'plataforma' => 5,    // 5% para la plataforma
    'emergencia' => 5,    // 5% fondo de emergencia
]
```

### Tipos de Espacio

- `gaztetxe` - Gaztetxe
- `sala_conciertos` - Sala de conciertos
- `teatro` - Teatro
- `galeria` - Galería
- `centro_cultural` - Centro cultural
- `espacio_publico` - Espacio público
- `online` - Online

### Tipos de Evento

- `concierto`, `teatro`, `danza`, `exposicion`
- `cine`, `poesia`, `taller`, `festival`

---

## Estados de Propuesta

| Estado | Descripción |
|--------|-------------|
| `pendiente` | Propuesta enviada, esperando respuesta |
| `aceptada` | Espacio ha aceptado |
| `en_negociacion` | En proceso de negociación |
| `rechazada` | Propuesta rechazada |

---

## Hooks y Acciones

### Acciones

```php
// Cuando se crea una propuesta
do_action('flavor_kulturaka_propuesta_creada', $propuesta_id, $espacio_id, $artista_id);

// Cuando se acepta
do_action('flavor_kulturaka_propuesta_aceptada', $propuesta_id);

// Cuando entra en negociación
do_action('flavor_kulturaka_propuesta_negociacion', $propuesta_id);

// Cuando se rechaza
do_action('flavor_kulturaka_propuesta_rechazada', $propuesta_id);

// Cuando se envía agradecimiento
do_action('flavor_kulturaka_agradecimiento_enviado', $agradecimiento_id, $datos);
```

### Filtros de Integración

```php
// Procesar evento cultural
add_action('flavor_evento_creado', [$this, 'procesar_evento_cultural'], 10, 2);

// Calcular impacto al finalizar evento
add_action('flavor_evento_finalizado', [$this, 'calcular_impacto_evento'], 10, 2);
```

---

## Permisos

| Capability | Descripción |
|------------|-------------|
| `manage_options` | Acceso a administración de Kulturaka |
| Usuario logueado | Crear propuestas, enviar agradecimientos |
| Público | Ver nodos, eventos |

---

## Páginas de Administración

| Menú | Slug | Descripción |
|------|------|-------------|
| Kulturaka | `flavor-kulturaka` | Dashboard principal |
| Nodos | `flavor-kulturaka-nodos` | Gestión de nodos |
| Propuestas | `flavor-kulturaka-propuestas` | Listado de propuestas |
| Métricas | `flavor-kulturaka-metricas` | Estadísticas de la red |

---

## Integraciones

### Con Módulo Eventos

- Los eventos culturales se crean a través del sistema de eventos
- Las propuestas se convierten en eventos al ser aceptadas

### Con Módulo Socios

- Perfiles de artista gestionados por socios
- Sistema de membresía integrado

### Con Módulo Crowdfunding

- Proyectos de financiación de artistas
- Vinculación con vista de artista

### Con Módulo Banco de Tiempo

- Opción de aceptar "hours" como pago
- Economía alternativa para artistas

---

## Métricas de Impacto

### CO2 Evitado

```php
// Cálculo simplificado
$asistentes = $datos_asistencia['confirmados'];
$km_ahorrados = $asistentes * 10; // 10km media
$co2_evitado = $km_ahorrados * 0.12; // kg CO2
```

### Índice de Cooperación

Calculado sobre 100 puntos:
- **Diversidad** (0-50): Variedad de artistas únicos
- **Solidaridad** (0-50): % eventos gratuitos o con economía alternativa

---

## Notas de Implementación

- Kulturaka actúa como **módulo orquestador** que une otros módulos
- Requiere que eventos, espacios-comunes y socios estén activos
- Las vistas se adaptan según el perfil del usuario (artista/espacio/comunidad)
- El sistema de agradecimientos crea una "micorriza cultural" de reconocimiento mutuo
