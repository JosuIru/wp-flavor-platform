# Módulo: Publicidad Ética

> Sistema de anuncios éticos con reparto de beneficios a la comunidad

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `advertising` |
| **Versión** | 1.0.0+ |
| **Categoría** | Economía / Marketing |
| **Disponible en App** | Ambas (cliente y admin) |

### Traits Utilizados

- `Flavor_Module_Admin_Pages_Trait`
- `Flavor_Module_Notifications_Trait`

---

## Descripción

Sistema de publicidad ética donde parte de los ingresos se reparten con la comunidad. Permite a anunciantes crear campañas publicitarias con un modelo de negocio más justo y transparente.

### Características Principales

- **Reparto Comunitario**: Porcentaje de ingresos va a la comunidad
- **Tipos de Pago**: CPC (coste por clic) y CPM (coste por mil impresiones)
- **Aprobación de Anuncios**: Sistema de revisión antes de publicar
- **Estadísticas Detalladas**: Impresiones, clics, CTR, gasto
- **Campañas**: Agrupar anuncios en campañas
- **Pool Comunitario**: Acumulación para distribución colectiva

---

## Custom Post Types

| CPT | Descripción |
|-----|-------------|
| `flavor_ad` | Anuncios individuales |
| `flavor_ad_campaign` | Campañas publicitarias |

---

## Tablas de Base de Datos

### `{prefix}_flavor_ads_stats`

Estadísticas diarias de anuncios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `ad_id` | bigint | ID del anuncio |
| `fecha` | date | Fecha de las estadísticas |
| `impresiones` | int | Número de impresiones |
| `clics` | int | Número de clics |
| `gasto` | decimal(10,4) | Gasto generado |

**Índices:** `ad_id`, `fecha`, UNIQUE(`ad_fecha`)

### `{prefix}_flavor_ads_ingresos`

Ingresos acumulados para usuarios (reparto comunitario).

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `usuario_id` | bigint | Usuario beneficiario |
| `cantidad` | decimal(10,4) | Cantidad acumulada |
| `concepto` | varchar(255) | Concepto del ingreso |
| `fecha` | datetime | Fecha de registro |
| `estado` | varchar(20) | `pendiente`, `pagado` |
| `fecha_pago` | datetime | Fecha de pago |
| `metodo_pago` | varchar(50) | Método de pago usado |
| `referencia` | varchar(100) | Referencia del pago |

### `{prefix}_flavor_ads_transactions`

Transacciones de anunciantes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `anunciante_id` | bigint | Usuario anunciante |
| `ad_id` | bigint | Anuncio relacionado |
| `tipo` | varchar(20) | Tipo de transacción |
| `cantidad` | decimal(10,2) | Cantidad |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `reparto_comunidad_default` | int | 30 | % de ingresos para comunidad |
| `precio_clic_default` | decimal | 0.10 | Precio por clic (€) |
| `precio_cpm_default` | decimal | 1.00 | Precio CPM (€) |
| `minimo_pago` | int | 10 | Mínimo para procesar pagos (€) |
| `aprobacion_automatica` | bool | false | Publicar sin revisión |
| `mostrar_etiqueta` | bool | true | Mostrar etiqueta "Anuncio" |

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[flavor_ad]` | Mostrar un anuncio |
| `[flavor_ads_dashboard]` | Dashboard de anunciante |
| `[flavor_ads_crear]` | Formulario para crear anuncio |
| `[flavor_ads_ingresos]` | Ver ingresos del reparto |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `flavor_ads_track_impression` | Registrar impresión | Público |
| `flavor_ads_track_click` | Registrar clic | Público |
| `flavor_ads_crear_campana` | Crear campaña | Usuario |
| `flavor_ads_pausar_campana` | Pausar campaña | Usuario |
| `flavor_ads_stats` | Obtener estadísticas | Usuario |
| `flavor_ads_aprobar` | Aprobar anuncio | Admin |
| `flavor_ads_rechazar` | Rechazar anuncio | Admin |
| `flavor_ads_procesar_pago` | Procesar pago comunidad | Admin |

---

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `flavor_ads_procesar_pagos` | Diario | Procesar pagos acumulados |
| `flavor_ads_actualizar_estadisticas` | Cada hora | Actualizar stats agregadas |

---

## Dashboard Admin

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `advertising-dashboard` | Panel principal con stats |
| Anuncios | `advertising-anuncios` | Gestión de anuncios |
| Campañas | `advertising-campanas` | Gestión de campañas |
| Configuración | `advertising-config` | Configuración del módulo |

---

## Flujo de Trabajo

```
1. CREAR ANUNCIO
   Anunciante crea anuncio
   Estado: pending (borrador)
   ↓
2. REVISIÓN
   Admin revisa y aprueba
   Estado: publish
   ↓
3. MOSTRAR
   Anuncio se muestra en el sitio
   Se registran impresiones
   ↓
4. INTERACCIÓN
   Usuario hace clic
   Se registra clic y gasto
   ↓
5. REPARTO
   Parte del gasto va al pool comunitario
   ↓
6. PAGO
   Cron diario procesa pagos cuando superan mínimo
```

---

## Pool Comunitario

El sistema mantiene un pool de fondos acumulados para la comunidad:

- `flavor_ads_pool_comunidad`: Option con el saldo actual
- Se distribuye según reglas configurables
- Mínimo de pago configurable para evitar micropagos

---

## Notas de Implementación

- Los anuncios usan CPT de WordPress para fácil gestión
- Meta fields: `_ad_tipo`, `_ad_anunciante_id`, `_ad_presupuesto`
- Estadísticas agregadas por día para eficiencia
- El reparto comunitario fomenta participación
- Compatible con el sistema de notificaciones del plugin
