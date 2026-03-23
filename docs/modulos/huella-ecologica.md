# Módulo: Huella Ecológica Comunitaria

> Sistema de medición y reducción del impacto ambiental colectivo

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `huella_ecologica` |
| **Versión** | 1.0.0+ |
| **Categoría** | Sostenibilidad / Impacto |
| **Rol** | Transversal |
| **Prioridad Dashboard** | 5 |
| **Icono** | dashicons-palmtree |
| **Color** | #27ae60 |

### Principios Gailu

- `regeneracion` - Impacto ambiental positivo

### Contribuye a

- `impacto` - Medir y reducir impacto
- `autonomia` - Soberanía energética

### Mide impacto de módulos

- `energia_comunitaria`
- `grupos_consumo`
- `compostaje`
- `reciclaje`
- `carpooling`

---

## Descripción

Sistema integral de medición y reducción del impacto ambiental colectivo. Permite calcular la huella de carbono individual, registrar acciones reductoras, participar en proyectos de compensación comunitarios y ganar logros por acciones sostenibles.

### Características Principales

- **Calculadora de Huella**: Estima CO2 diario/mensual/anual
- **Registro de Huella**: Seguimiento por categorías
- **Acciones Reductoras**: Registrar acciones positivas
- **Proyectos de Compensación**: Iniciativas comunitarias
- **Sistema de Logros**: Gamificación con badges
- **Estadísticas Comunidad**: Impacto colectivo

---

## Categorías de Huella

| Categoría | Nombre | Icono | Color | Unidad |
|-----------|--------|-------|-------|--------|
| `transporte` | Transporte | car | #e74c3c | kg CO2 |
| `energia` | Energía | lightbulb | #f39c12 | kg CO2 |
| `alimentacion` | Alimentación | carrot | #27ae60 | kg CO2 |
| `consumo` | Consumo | cart | #3498db | kg CO2 |
| `residuos` | Residuos | trash | #9b59b6 | kg CO2 |
| `agua` | Agua | admin-site-alt3 | #1abc9c | litros |

---

## Acciones Reductoras

| Acción | Nombre | Categoría | Reducción (kg CO2) |
|--------|--------|-----------|-------------------|
| `movilidad_sostenible` | Movilidad sostenible | transporte | 2.5 |
| `energia_renovable` | Energía renovable | energia | 5.0 |
| `dieta_local` | Alimentación local | alimentacion | 1.5 |
| `reducir_carne` | Reducir carne | alimentacion | 3.0 |
| `reparar_reutilizar` | Reparar y reutilizar | consumo | 2.0 |
| `compostaje` | Compostaje | residuos | 1.0 |
| `ahorro_agua` | Ahorro de agua | agua | 0.5 |
| `autoconsumo` | Autoconsumo | alimentacion | 2.0 |

---

## Sistema de Logros (Badges)

| Logro | Nombre | Descripción | Icono | Puntos |
|-------|--------|-------------|-------|--------|
| `primera_medicion` | Primera medición | Calculaste tu huella por primera vez | 🌱 | 10 |
| `semana_verde` | Semana verde | 7 días registrando acciones | 🌿 | 25 |
| `huella_cero` | Día huella cero | Un día con huella neta cero | 🌍 | 50 |
| `embajador` | Embajador verde | Propusiste un proyecto comunitario | 🌳 | 100 |
| `compensador` | Compensador | Participaste en proyecto de compensación | 💚 | 75 |
| `mentor_eco` | Mentor ecológico | Ayudaste a 5 personas a reducir su huella | 🎓 | 150 |

---

## Estados de Proyecto

| Estado | Nombre | Color |
|--------|--------|-------|
| `propuesto` | Propuesto | #f39c12 |
| `aprobado` | Aprobado | #3498db |
| `en_curso` | En curso | #27ae60 |
| `completado` | Completado | #2ecc71 |
| `cancelado` | Cancelado | #95a5a6 |

---

## Custom Post Types

### `he_registro` - Registros de Huella

| Meta | Descripción |
|------|-------------|
| `_he_fecha` | Fecha del registro |
| `_he_categoria` | Categoría de huella |
| `_he_valor` | Valor en kg CO2 o litros |
| `_he_descripcion` | Descripción opcional |

### `he_accion` - Acciones Reductoras

| Meta | Descripción |
|------|-------------|
| `_he_tipo` | Tipo de acción |
| `_he_fecha` | Fecha de la acción |
| `_he_cantidad` | Cantidad de veces |
| `_he_reduccion` | Reducción total CO2 |
| `_he_notas` | Notas opcionales |

### `he_proyecto` - Proyectos de Compensación

| Meta | Descripción |
|------|-------------|
| `_he_estado` | Estado del proyecto |
| `_he_meta_co2` | Meta de CO2 a compensar |
| `_he_co2_actual` | CO2 compensado actual |
| `_he_ubicacion` | Ubicación del proyecto |
| `_he_tipo_proyecto` | Tipo (reforestación, etc.) |
| `_he_participantes` | Array de user IDs |
| `_he_fecha_propuesta` | Fecha de propuesta |

### `he_logro` - Logros Obtenidos

Registro de logros conseguidos por usuarios.

---

## Endpoints API REST

Base: `/wp-json/flavor/v1/`

| Endpoint | Método | Descripción | Auth |
|----------|--------|-------------|------|
| `/huella-ecologica/estadisticas` | GET | Estadísticas del usuario | Usuario |
| `/huella-ecologica/comunidad` | GET | Estadísticas comunidad | Público |
| `/huella-ecologica/proyectos` | GET | Listar proyectos | Público |
| `/huella-ecologica/logros` | GET | Logros del usuario | Usuario |
| `/huella-ecologica/calcular` | POST | Calcular huella | Público |

### GET `/huella-ecologica/estadisticas`

**Parámetros:**
- `periodo` - mes, semana, anio

**Respuesta:**
```json
{
  "huella_total": 125.5,
  "reduccion_total": 45.0,
  "huella_neta": 80.5,
  "por_categoria": {
    "transporte": 45.2,
    "energia": 30.1,
    "alimentacion": 35.0
  },
  "acciones_realizadas": 12,
  "logros_obtenidos": 3
}
```

### POST `/huella-ecologica/calcular`

**Body:**
```json
{
  "km_coche": 100,
  "km_avion": 500,
  "kwh_mes": 250,
  "tipo_dieta": "flexitariana",
  "compras_nuevas": 5
}
```

**Respuesta:**
```json
{
  "huella_diaria": 15.5,
  "huella_mensual": 465.0,
  "huella_anual": 5657.5,
  "desglose": {
    "transporte_coche": 21.0,
    "transporte_avion": 127.5,
    "energia": 3.17,
    "alimentacion": 4.7
  }
}
```

### GET `/huella-ecologica/proyectos`

**Respuesta:**
```json
{
  "proyectos": [
    {
      "id": 123,
      "titulo": "Reforestación Monte Local",
      "descripcion": "Plantación de 500 árboles...",
      "meta_co2": 5000,
      "co2_actual": 2500,
      "participantes": 25,
      "estado": "en_curso"
    }
  ],
  "total": 8
}
```

---

## Shortcodes

| Shortcode | Alias | Descripción |
|-----------|-------|-------------|
| `[huella_ecologica_calculadora]` | `[flavor_huella_calculadora]` | Calculadora de huella |
| `[huella_ecologica_mis_registros]` | `[flavor_huella_mis_registros]` | Mis registros |
| `[huella_ecologica_comunidad]` | `[flavor_huella_comunidad]` | Estadísticas comunidad |
| `[huella_ecologica_proyectos]` | `[flavor_huella_proyectos]` | Lista de proyectos |
| `[huella_ecologica_logros]` | `[flavor_huella_logros]` | Mis logros |

---

## Configuración

El módulo opera en los siguientes contextos de dashboard:

| Contexto | Dashboard |
|----------|-----------|
| `impacto` | Cliente y Admin |
| `sostenibilidad` | Cliente y Admin |
| `energia` | Cliente |
| `consumo` | Cliente |

---

## AJAX Actions

| Action | Descripción | Auth |
|--------|-------------|------|
| `he_registrar_huella` | Registrar huella diaria | Usuario |
| `he_registrar_accion` | Registrar acción reductora | Usuario |
| `he_proponer_proyecto` | Proponer proyecto | Usuario |
| `he_unirse_proyecto` | Unirse a proyecto | Usuario |
| `he_obtener_estadisticas` | Obtener estadísticas | Usuario |
| `he_calcular_huella` | Calcular huella estimada | Público |

---

## Cálculo de Huella

### Factores de Emisión (kg CO2)

| Actividad | Factor |
|-----------|--------|
| Km en coche | 0.21 |
| Km en avión | 0.255 |
| kWh electricidad | 0.38/30 (diario) |

### CO2 por Dieta (kg/día)

| Dieta | CO2 |
|-------|-----|
| Omnívora | 7.2 |
| Flexitariana | 4.7 |
| Vegetariana | 3.8 |
| Vegana | 2.9 |

### Comparativas

| Referencia | kg CO2/día |
|------------|------------|
| Media España | 7.5 |
| Objetivo 2030 | 4.0 |
| Objetivo 2050 | 2.0 |

---

## Dashboard Tabs

| Tab | Descripción | Requiere Login |
|-----|-------------|----------------|
| Mi huella | Estadísticas personales | Sí |
| Calculadora | Estimar huella | No |
| Acciones | Registrar acciones | Sí |
| Proyectos | Proyectos comunitarios | No |
| Logros | Mis badges | Sí |
| Comunidad | Estadísticas colectivas | No |

---

## Integraciones

### Mide impacto de módulos

El módulo puede calcular el impacto positivo de otros módulos:

- **energia_comunitaria**: kWh renovables → CO2 evitado
- **grupos_consumo**: Productos locales → Reducción transporte
- **compostaje**: kg compostados → CO2 evitado
- **reciclaje**: kg reciclados → CO2 evitado
- **carpooling**: km compartidos → CO2 evitado

---

## Páginas de Administración

| Página | Slug | Descripción |
|--------|------|-------------|
| Dashboard | `huella-dashboard` | Panel principal |
| Registros | `huella-registros` | Listado de registros |
| Proyectos | `huella-proyectos` | Gestión de proyectos |
| Configuración | `huella-config` | Ajustes del módulo |

---

## Gamificación

El sistema de logros incentiva comportamientos sostenibles:

1. **Logros automáticos**: Se verifican tras cada acción
2. **Puntos acumulativos**: Cada logro suma puntos
3. **Visibilidad**: Badges en perfil de usuario
4. **Ranking**: Opcional por comunidad

### Condiciones de Logros

| Logro | Condición |
|-------|-----------|
| primera_medicion | Primer registro de huella |
| semana_verde | 7 días consecutivos con acciones |
| huella_cero | Día con reducción ≥ huella |
| embajador | Proponer proyecto aprobado |
| compensador | Unirse a proyecto |
| mentor_eco | Ayudar a 5 usuarios |

---

## Notas de Implementación

- Los registros son privados por usuario
- Los proyectos requieren aprobación admin
- Los logros se verifican automáticamente tras cada acción
- Los factores de emisión son configurables
- Soporta múltiples unidades (CO2, agua)
- Integración con dashboard unificado
- Assets CSS/JS propios se cargan solo en páginas del módulo
- Rol transversal con alta prioridad en dashboard

