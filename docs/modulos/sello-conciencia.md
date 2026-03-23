# Módulo: Sello de Conciencia

> Evaluación automática del nivel de conciencia de la aplicación basado en las 5 premisas fundamentales

## Información General

| Campo | Valor |
|-------|-------|
| **ID** | `sello_conciencia` |
| **Versión** | 4.2.0+ |
| **Categoría** | Meta / Evaluación |

---

## Descripción

Evalúa automáticamente el nivel de conciencia de la aplicación basándose en los módulos activos y su alineación con las 5 premisas fundamentales de una economía consciente. Calcula una puntuación global y asigna un nivel de conciencia.

### Características Principales

- **Evaluación Automática**: Calcula puntuación según módulos activos
- **5 Premisas Fundamentales**: Marco ético y filosófico
- **Niveles de Conciencia**: Básico → Transición → Consciente → Referente
- **Valoración por Módulo**: Cada módulo contribuye a premisas específicas
- **Badge Visual**: Certificación visible en la aplicación

---

## Las 5 Premisas Fundamentales

| Premisa | Nombre | Peso | Color |
|---------|--------|------|-------|
| `conciencia_fundamental` | La conciencia es fundamental | 0.20 | #9b59b6 |
| `abundancia_organizable` | La abundancia es organizable | 0.20 | #27ae60 |
| `interdependencia_radical` | La interdependencia es radical | 0.20 | #3498db |
| `madurez_ciclica` | La madurez es cíclica | 0.20 | #e67e22 |
| `valor_intrinseco` | El valor es intrínseco | 0.20 | #f39c12 |

### Detalle de Premisas

#### 1. Conciencia Fundamental
- **Principio**: La materia no es lo único real; la conciencia es tan fundamental como ella
- **Consecuencia**: Módulos que reconocen la dignidad de las personas y respetan la autonomía

#### 2. Abundancia Organizable
- **Principio**: No hay escasez de recursos; hay escasez de distribución equitativa
- **Consecuencia**: Módulos que facilitan el acceso equitativo y la economía colaborativa

#### 3. Interdependencia Radical
- **Principio**: La separación es abstracción útil pero no realidad ontológica
- **Consecuencia**: Módulos que fomentan la cooperación y crean redes de apoyo mutuo

#### 4. Madurez Cíclica
- **Principio**: Los sistemas sanos crecen, maduran y se renuevan cíclicamente
- **Consecuencia**: Módulos que respetan límites y promueven la sostenibilidad

#### 5. Valor Intrínseco
- **Principio**: Las cosas valen por lo que son, no por lo que puede extraerse de ellas
- **Consecuencia**: Módulos que permiten intercambios no monetarios

---

## Niveles de Conciencia

| Nivel | Nombre | Puntuación | Color | Icono |
|-------|--------|------------|-------|-------|
| `ninguno` | Sin evaluar | 0 | #95a5a6 | minus |
| `basico` | Básico | 1-25 | #e74c3c | marker |
| `transicion` | En Transición | 26-50 | #f39c12 | arrow-up-alt |
| `consciente` | Consciente | 51-75 | #27ae60 | yes-alt |
| `referente` | Referente | 76-100 | #9b59b6 | star-filled |

---

## Valoración de Módulos

### Módulos de Economía Colaborativa

| Módulo | Puntuación | Premisas Principales |
|--------|------------|---------------------|
| `banco_tiempo` | 95 | valor_intrinseco (0.35), abundancia (0.25) |
| `grupos_consumo` | 85 | abundancia (0.35), interdependencia (0.25) |
| `moneda_local` | 80 | abundancia (0.30), interdependencia (0.30) |
| `mercado_social` | 75 | abundancia (0.30), interdependencia (0.25) |
| `marketplace` | 60 | abundancia (0.40), interdependencia (0.30) |

### Módulos de Recursos Compartidos

| Módulo | Puntuación | Premisas Principales |
|--------|------------|---------------------|
| `espacios_comunes` | 90 | abundancia (0.35), interdependencia (0.30) |
| `biblioteca` | 90 | abundancia (0.35), madurez (0.25) |
| `huertos_urbanos` | 88 | madurez (0.30), abundancia (0.25) |
| `bicicletas_compartidas` | 85 | abundancia (0.30), madurez (0.30) |
| `carpooling` | 78 | abundancia (0.35), madurez (0.25) |
| `parkings` | 65 | abundancia (0.50), interdependencia (0.30) |

### Módulos de Sostenibilidad Ambiental

| Módulo | Puntuación | Premisas Principales |
|--------|------------|---------------------|
| `energia_comunitaria` | 92 | abundancia (0.30), interdependencia (0.30) |
| `compostaje` | 88 | madurez (0.45), interdependencia (0.25) |
| `reciclaje` | 75 | madurez (0.40), interdependencia (0.25) |

### Módulos de Cuidados

| Módulo | Puntuación | Premisas Principales |
|--------|------------|---------------------|
| `cuidados` | 95 | conciencia (0.35), interdependencia (0.30) |
| `ayuda_vecinal` | 88 | interdependencia (0.35), conciencia (0.30) |
| `salud_comunitaria` | 85 | conciencia (0.35), interdependencia (0.30) |

### Módulos de Participación

| Módulo | Puntuación | Premisas Principales |
|--------|------------|---------------------|
| `asambleas` | 90 | conciencia (0.35), interdependencia (0.35) |
| `presupuestos_participativos` | 85 | abundancia (0.35), interdependencia (0.35) |
| `participacion` | 82 | conciencia (0.35), interdependencia (0.35) |
| `transparencia` | 78 | conciencia (0.40), interdependencia (0.35) |
| `votaciones` | 70 | conciencia (0.40), interdependencia (0.30) |
| `incidencias` | 65 | interdependencia (0.40), conciencia (0.30) |

### Módulos de Cultura

| Módulo | Puntuación | Premisas Principales |
|--------|------------|---------------------|
| `eventos` | 70 | interdependencia (0.35), conciencia (0.25) |
| `avisos_municipales` | 55 | interdependencia (0.45), abundancia (0.35) |

---

## Cálculo de Puntuación

```
Puntuación Global = Σ (puntuación_módulo × peso_premisa × factor_premisa)
```

Para cada módulo activo:
1. Se obtiene su puntuación base (0-100)
2. Se multiplica por el peso de cada premisa que contribuye
3. Se suma la contribución ponderada de todas las premisas
4. El resultado final determina el nivel de conciencia

---

## API y Métodos

### Obtener Evaluación

```php
$sello = Flavor_Chat_Module_Loader::get_instance()->get_module('sello_conciencia');
$evaluacion = $sello->evaluar_aplicacion();
```

### Respuesta de Evaluación

```json
{
  "puntuacion_global": 72,
  "nivel": "consciente",
  "nivel_info": {
    "nombre": "Consciente",
    "color": "#27ae60",
    "icono": "dashicons-yes-alt"
  },
  "por_premisa": {
    "conciencia_fundamental": 68,
    "abundancia_organizable": 75,
    "interdependencia_radical": 72,
    "madurez_ciclica": 70,
    "valor_intrinseco": 74
  },
  "modulos_evaluados": 15
}
```

---

## Dashboard Widget

El módulo puede mostrar un widget en el dashboard de administración con:

- Puntuación global
- Gráfico de radar con las 5 premisas
- Nivel actual de conciencia
- Módulos que más contribuyen
- Sugerencias para mejorar la puntuación

---

## Shortcodes

| Shortcode | Descripción |
|-----------|-------------|
| `[sello_conciencia]` | Muestra el badge del sello |
| `[sello_conciencia_detalle]` | Muestra evaluación detallada |
| `[sello_conciencia_premisas]` | Lista las 5 premisas |

---

## Notas de Implementación

- La evaluación se recalcula cuando cambian los módulos activos
- Los pesos de las premisas suman 1.0 (100%)
- Cada módulo puede contribuir a múltiples premisas
- Los módulos no listados en MODULOS_VALORACION no contribuyen
- El sistema es extensible para añadir nuevos módulos
- Se puede personalizar la valoración de módulos por configuración

