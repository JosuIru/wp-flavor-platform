# Módulo: Transparencia

Portal completo de transparencia y rendición de cuentas.

## Información General

| Campo | Valor |
|-------|-------|
| ID | `transparencia` |
| Versión | 2.0.0 |
| Categoría | Gobernanza |
| Rol | Transversal |
| Principios Gailu | Gobernanza |

## Descripción

El módulo TRANSPARENCIA proporciona un portal de datos públicos con presupuestos, contratos, actas de sesiones, indicadores de gestión y sistema de solicitudes de información pública. Facilita el acceso ciudadano a información oficial y promueve la rendición de cuentas.

## Tablas de Base de Datos

### `wp_flavor_transparencia_documentos_publicos`
Documentos públicos por categoría.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| categoria | varchar | presupuestos, contratos, subvenciones, normativa, actas, personal, indicadores, patrimonio |
| titulo | varchar | Título |
| descripcion | text | Descripción |
| archivo_url | varchar | URL del archivo |
| importe | decimal | Monto económico |
| periodo | varchar | Período/ejercicio |
| estado | enum | borrador, pendiente, publicado, archivado |
| visitas | int | Contador |
| descargas | int | Contador |

### `wp_flavor_transparencia_presupuestos`
Estructura presupuestaria.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| ejercicio | year | Año fiscal |
| tipo | enum | ingresos, gastos |
| capitulo | varchar | Capítulo presupuestario |
| credito_inicial | decimal | Crédito inicial |
| credito_definitivo | decimal | Crédito final |
| obligaciones_reconocidas | decimal | Obligaciones |
| pagos_realizados | decimal | Pagos |
| porcentaje_ejecucion | decimal | % ejecución |

### `wp_flavor_transparencia_gastos`
Registro de operaciones de gasto.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| ejercicio | year | Año |
| fecha_operacion | date | Fecha |
| concepto | text | Descripción |
| proveedor | varchar | Proveedor |
| importe_total | decimal | Total con IVA |
| estado_pago | enum | pendiente, pagado, anulado |

### `wp_flavor_transparencia_solicitudes_info`
Solicitudes de acceso a información.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| numero_expediente | varchar | Expediente |
| titulo | varchar | Asunto |
| descripcion | text | Detalle |
| estado | enum | recibida, admitida, en_tramite, resuelta, denegada |
| fecha_solicitud | datetime | Fecha solicitud |
| fecha_limite | datetime | Plazo respuesta |
| respuesta | longtext | Respuesta |

### `wp_flavor_transparencia_actas`
Actas de sesiones de órganos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| tipo_organo | enum | pleno, junta_gobierno, comision, consejo |
| fecha_sesion | datetime | Fecha |
| orden_del_dia | longtext | Orden del día |
| acuerdos | json | Acuerdos adoptados |
| estado | enum | convocada, celebrada, aprobada, publicada |

## Endpoints API REST

**Namespace:** `flavor-chat/v1`

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/transparencia/documentos` | Listar documentos |
| GET | `/transparencia/documentos/{id}` | Detalle documento |
| GET | `/transparencia/presupuestos` | Presupuestos |
| GET | `/transparencia/presupuestos/resumen` | Resumen ejecutivo |
| GET | `/transparencia/gastos` | Registro de gastos |
| GET | `/transparencia/gastos/estadisticas` | Estadísticas |
| GET | `/transparencia/actas` | Actas de sesiones |
| POST | `/transparencia/solicitudes` | Nueva solicitud |
| GET | `/transparencia/solicitudes/{id}` | Detalle solicitud |
| GET | `/transparencia/estadisticas` | Estadísticas portal |

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[transparencia_portal]` | Portal completo |
| `[transparencia_presupuesto_actual]` | Resumen presupuesto |
| `[transparencia_presupuestos]` | Lista presupuestos |
| `[transparencia_ultimos_gastos]` | Últimos gastos |
| `[transparencia_buscador_docs]` | Buscador documentos |
| `[transparencia_solicitar_info]` | Formulario solicitud |
| `[transparencia_actas]` | Lista de actas |
| `[transparencia_contratos]` | Lista contratos |
| `[transparencia_indicadores]` | Panel KPIs |
| `[transparencia_mis_solicitudes]` | Mis solicitudes |

## Configuración

```php
[
    'permite_solicitudes_anonimas' => false,
    'dias_plazo_respuesta' => 30,
    'publicacion_automatica' => false,
    'requiere_aprobacion_publicacion' => true,
    'notificar_nuevas_solicitudes' => true,
    'categorias_habilitadas' => [
        'presupuestos', 'contratos', 'subvenciones',
        'normativa', 'actas', 'personal', 'indicadores', 'patrimonio'
    ],
    'mostrar_graficos' => true,
    'limite_documentos_por_pagina' => 12,
]
```

## Dashboard Tabs Usuario

- **Mis Solicitudes**: Solicitudes de información
- **Seguimiento**: Timeline de solicitudes en trámite
- **Docs. Guardados**: Documentos favoritos

## Integraciones

El módulo TRANSPARENCIA:
- Provee tabs de foro vía módulo Foros
- Integra chat de discusión vía Chat-Grupos
- Soporta multimedia vía módulo Multimedia
- Publica en red social

## Permisos

| Acción | Requisito |
|--------|-----------|
| Ver documentos | Público |
| Descargar | Público |
| Crear solicitud | Público (configurable) |
| Ver solicitud | Propietario o admin |
| Gestionar | `manage_options` |

## Cron Jobs

| Hook | Frecuencia | Descripción |
|------|------------|-------------|
| `transparencia_check_plazos` | Diaria | Verificar plazos solicitudes |
