# Módulo: Energía Comunitaria

> Gestión comunitaria de instalaciones y soberanía energética local

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `energia_comunitaria` |
| **Versión** | 1.0.0+ |
| **Categoría** | Sostenibilidad |
| **Rol** | Vertical |
| **Dependencias** | comunidades |
| **Disponible en App** | Cliente |

### Principios Gailu

- `regeneracion` - Energía renovable y sostenible
- `gobernanza` - Gestión democrática de recursos
- `autonomia` - Soberanía energética local
- `impacto` - Reducción huella de carbono

---

## Descripción

Gestión comunitaria de instalaciones, producción, consumo y soberanía energética local. Permite crear comunidades energéticas, gestionar instalaciones fotovoltaicas u otras, registrar lecturas, calcular repartos y generar liquidaciones entre participantes.

### Características Principales

- **Comunidades Energéticas**: Crear y gestionar comunidades
- **Instalaciones**: Fotovoltaica, eólica, biomasa, etc.
- **Lecturas**: Registro de producción y consumo
- **Reparto**: Algoritmos de distribución de energía
- **Liquidaciones**: Cálculo y exportación de pagos
- **Participantes**: Gestión de miembros y cuotas
- **Incidencias**: Reporte de problemas técnicos
- **Mantenimiento**: Planificación de mantenimientos
- **Métricas CO2**: Cálculo de impacto ambiental

---

## Tablas de Base de Datos

### `{prefix}_flavor_energia_comunidades`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `nombre` | varchar(255) | Nombre de la comunidad |
| `descripcion` | text | Descripción |
| `tipo_instalacion_principal` | varchar(100) | Tipo principal |
| `modelo_reparto` | varchar(50) | Modelo de distribución |
| `estado` | enum | `activa`, `inactiva`, `en_constitucion` |
| `created_at` | datetime | Fecha de creación |

### `{prefix}_flavor_energia_instalaciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `comunidad_id` | bigint | Comunidad asociada |
| `nombre` | varchar(255) | Nombre instalación |
| `tipo` | varchar(50) | Fotovoltaica, eólica, etc. |
| `potencia_kwp` | decimal | Potencia instalada |
| `ubicacion` | text | Dirección |
| `estado` | enum | `activa`, `mantenimiento`, `inactiva` |

### `{prefix}_flavor_energia_lecturas`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `instalacion_id` | bigint | Instalación |
| `participante_id` | bigint | Participante (si aplica) |
| `fecha_lectura` | date | Fecha de la lectura |
| `kwh_producidos` | decimal | kWh generados |
| `kwh_consumidos` | decimal | kWh consumidos |
| `kwh_vertidos` | decimal | kWh vertidos a red |
| `validada` | tinyint | Lectura validada |
| `registrado_por` | bigint | Usuario que registra |

### `{prefix}_flavor_energia_participantes`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `comunidad_id` | bigint | Comunidad |
| `usuario_id` | bigint | Usuario WordPress |
| `coeficiente` | decimal | % de participación |
| `fecha_alta` | date | Fecha de alta |
| `estado` | enum | `activo`, `baja` |

### `{prefix}_flavor_energia_repartos_cierre`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `comunidad_id` | bigint | Comunidad |
| `periodo` | varchar(7) | Mes (YYYY-MM) |
| `kwh_total_producido` | decimal | Total producido |
| `kwh_total_consumido` | decimal | Total consumido |
| `kwh_excedentes` | decimal | Excedentes |
| `estado` | enum | `abierto`, `cerrado` |
| `cerrado_por` | bigint | Usuario que cierra |
| `fecha_cierre` | datetime | Fecha de cierre |

### `{prefix}_flavor_energia_liquidaciones`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `cierre_id` | bigint | Cierre de reparto |
| `participante_id` | bigint | Participante |
| `kwh_asignados` | decimal | kWh asignados |
| `importe_eur` | decimal | Importe a pagar/cobrar |
| `estado` | enum | `pendiente`, `notificada`, `aceptada`, `pagada` |
| `fecha_notificacion` | datetime | Fecha de notificación |
| `fecha_aceptacion` | datetime | Fecha de aceptación |

### `{prefix}_flavor_energia_incidencias`

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | bigint | ID único |
| `instalacion_id` | bigint | Instalación afectada |
| `tipo` | varchar(50) | Tipo de incidencia |
| `descripcion` | text | Descripción |
| `estado` | enum | `abierta`, `en_progreso`, `resuelta` |
| `reportado_por` | bigint | Usuario que reporta |

---

## Configuración

| Opción | Tipo | Default | Descripción |
|--------|------|---------|-------------|
| `disponible_app` | string | `cliente` | Disponibilidad en app |
| `permite_reparto_excedentes` | bool | true | Permitir reparto |
| `permite_compra_colectiva` | bool | true | Compra colectiva |
| `permite_autolecuras` | bool | true | Auto-lecturas usuarios |
| `factor_co2_kwh` | float | 0.23 | kg CO2 por kWh |
| `precio_referencia_kwh` | float | 0.18 | Precio referencia € |
| `moneda` | string | `EUR` | Moneda |
| `requiere_validacion_lecturas` | bool | false | Validar lecturas |
| `capacidad_gestion` | string | `edit_posts` | Cap. para gestionar |
| `capacidad_reportes` | string | `read` | Cap. para reportes |
| `capacidad_lecturas` | string | `read` | Cap. para lecturas |

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[flavor_energia_dashboard]` | Dashboard principal |
| `[flavor_energia_instalaciones]` | Lista de instalaciones |
| `[flavor_energia_balance]` | Balance de reparto |
| `[flavor_energia_cierres]` | Histórico de cierres |
| `[flavor_energia_liquidaciones]` | Liquidaciones |
| `[flavor_energia_participantes]` | Lista de participantes |
| `[flavor_energia_mantenimiento]` | Planificación mantenimiento |
| `[flavor_energia_form_comunidad]` | Formulario nueva comunidad |
| `[flavor_energia_form_instalacion]` | Formulario nueva instalación |
| `[flavor_energia_form_lectura]` | Formulario registrar lectura |
| `[flavor_energia_form_participante]` | Formulario nuevo participante |
| `[flavor_energia_form_cierre]` | Formulario cerrar reparto |
| `[flavor_energia_proyectos]` | Proyectos energéticos |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `energia_comunitaria_crear_instalacion` | Crear instalación | Usuario |
| `energia_comunitaria_crear_comunidad` | Crear comunidad | Usuario |
| `energia_comunitaria_crear_participante` | Añadir participante | Usuario |
| `energia_comunitaria_cerrar_reparto` | Cerrar período | Usuario |
| `energia_comunitaria_registrar_lectura` | Registrar lectura | Usuario |
| `energia_comunitaria_reportar_incidencia` | Reportar incidencia | Usuario |
| `energia_comunitaria_actualizar_liquidacion` | Actualizar liquidación | Usuario |

---

## Admin Post Actions

| Action | Descripción |
|--------|-------------|
| `energia_comunitaria_exportar_liquidaciones` | Exportar todas liquidaciones CSV |
| `energia_comunitaria_exportar_liquidacion` | Exportar una liquidación CSV |

---

## Dashboard Tabs

| Tab | Descripción | Requiere Login |
|-----|-------------|----------------|
| Panel | Dashboard general | No |
| Instalaciones | Lista de instalaciones | No |
| Reparto | Balance de distribución | Sí |
| Cierres | Histórico de cierres | Sí |
| Liquidaciones | Liquidaciones pendientes | Sí |
| Participantes | Gestión de miembros | Sí |
| Mantenimiento | Planificación | Sí |
| Nueva comunidad | Formulario creación | Sí |
| Nueva instalación | Formulario creación | Sí |
| Registrar lectura | Formulario lectura | Sí |
| Nuevo participante | Formulario alta | Sí |
| Cerrar reparto | Formulario cierre | Sí |
| Proyectos | Proyectos futuros | No |
| Comunidad | Comunidades relacionadas | No |

---

## Estadísticas del Dashboard

| Métrica | Descripción |
|---------|-------------|
| Comunidades energéticas | Comunidades activas |
| Instalaciones activas | Instalaciones en funcionamiento |
| Lecturas este mes | Lecturas registradas |
| Incidencias abiertas | Incidencias sin resolver |

---

## Integraciones

### Acepta integraciones de:

- `comunidades` - Comunidades base
- `eventos` - Eventos relacionados
- `biblioteca` - Documentación
- `multimedia` - Fotos de instalaciones
- `presupuestos_participativos` - Proyectos de inversión
- `huella_ecologica` - Métricas de impacto

---

## Páginas de Administración

| Menú | Slug | Descripción |
|------|------|-------------|
| Dashboard | `flavor-energia-dashboard` | Panel principal |
| Instalaciones | `flavor-energia-instalaciones` | Gestión instalaciones |
| Comunidad | `flavor-energia-comunidad` | Detalle comunidad |

---

## Cálculo de Impacto Ambiental

```php
// kg CO2 evitados = kWh producidos × factor
$co2_evitado = $kwh_producidos * 0.23; // 0.23 kg/kWh default

// Equivalencia en árboles
$arboles_equivalentes = $co2_evitado / 21; // ~21kg CO2/árbol/año
```

---

## Notas de Implementación

- Cada comunidad tiene su propio modelo de reparto (coeficientes)
- Las lecturas pueden ser auto-registradas o requerir validación
- Los cierres bloquean el período para modificaciones
- Las liquidaciones pueden exportarse a CSV para contabilidad
- El sistema calcula automáticamente el CO2 evitado
- Integración con módulo de comunidades para estructura base
