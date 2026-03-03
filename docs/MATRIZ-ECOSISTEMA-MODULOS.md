# Matriz de Ecosistema de Módulos

Fecha: 2026-03-03

## Objetivo

Disponer de una matriz práctica para responder:

- qué rol tiene cada módulo dentro del ecosistema
- de qué módulo base cuelga o con qué contexto se relaciona
- qué módulo mide, gobierna o complementa a otro
- dónde hay huecos, solapamientos o relaciones todavía implícitas

Esta matriz no sustituye al catálogo técnico. Su propósito es arquitectónico y de producto.

## Roles usados

- `base`: módulo estructural o de identidad compartida
- `vertical`: módulo operativo de dominio
- `transversal`: módulo que mide, guía, enseña o gobierna a varios

## Matriz principal

| Módulo | Rol | Cuelga de / contexto | Mide / gobierna / soporta | Juicio |
|--------|-----|-----------------------|---------------------------|--------|
| `comunidades` | base | usuarios, territorio, grupos | soporta eventos, energía, ayuda, foros, recursos | muy buen base social |
| `socios` | base | membresía formal | soporta cuotas, derechos, gobernanza, acceso | buen base organizativo |
| `colectivos` | base | agrupaciones y asociaciones | soporta capas organizativas intermedias | correcto, con riesgo de solape con comunidades |
| `grupos_consumo` | vertical | comunidades, socios | puede ser medido por huella; soportado por transparencia y eventos | muy buen vertical |
| `banco_tiempo` | vertical | comunidades, socios | soportado por ayuda/cultura; medible por impacto social | muy buen vertical |
| `energia_comunitaria` | vertical | comunidades | medido por huella; gobernable por participación y presupuestos | buen vertical |
| `ayuda_vecinal` | vertical | comunidades | soportado por banco de tiempo, cuidados y eventos | buen vertical |
| `reservas` | vertical | socios, espacios, organizaciones | soportado por facturas y eventos | buen vertical operativo |
| `espacios_comunes` | vertical | comunidades, socios | soportado por reservas, incidencias, eventos | buen vertical operativo |
| `incidencias` | vertical | territorio, espacios, comunidad | soportado por gobernanza y trazabilidad | buen vertical operativo |
| `tramites` | vertical | organización, ciudadanía | soportado por transparencia y avisos | buen vertical administrativo |
| `marketplace` | vertical | comunidad, comercio, usuarios | soportado por pagos, mensajería y reputación | correcto |
| `huella_ecologica` | transversal | datos de energía, consumo, movilidad, residuos | mide verticales sostenibles y proyectos colectivos | muy buen transversal |
| `economia_suficiencia` | transversal | hábitos y compromisos | guía módulos económicos y de consumo | buen transversal cultural |
| `saberes_ancestrales` | transversal | comunidad, memoria, talleres | enseña y transmite a comunidad, cursos y eventos | buen transversal cultural |
| `participacion` | transversal | comunidades, socios, colectivos | gobierna decisiones de verticales y nodos | buen transversal de gobernanza |
| `presupuestos_participativos` | transversal | comunidad, organización | gobierna inversiones colectivas | buen transversal de gobernanza |
| `transparencia` | transversal | organización, comunidad | audita y hace visible gestión y decisiones | buen transversal de gobernanza |
| `eventos` | transversal | comunidades, socios, colectivos | soporta activación social de casi todos los verticales | muy buen transversal operativo |
| `talleres` | transversal | comunidades, cursos, saberes | soporta transmisión, aprendizaje y cultura | muy buen transversal operativo |
| `biblioteca` | transversal | comunidad, formación | soporta saberes, suficiencia y cultura compartida | correcto |
| `radio` / `podcast` / `multimedia` | transversal | comunidad, contenidos | amplifican cultura, comunicación e identidad | correctos |

## Relaciones recomendadas como contrato explícito

## Base -> Vertical

- `comunidades base_for energia_comunitaria`
- `comunidades base_for ayuda_vecinal`
- `comunidades base_for eventos`
- `comunidades base_for foros`
- `socios base_for participacion`
- `socios base_for transparencia`
- `socios base_for reservas`

## Vertical -> Transversal de medición

- `huella_ecologica measures grupos_consumo`
- `huella_ecologica measures energia_comunitaria`
- `huella_ecologica measures compostaje`
- `huella_ecologica measures reciclaje`
- `economia_suficiencia guides grupos_consumo`
- `economia_suficiencia guides marketplace`

## Vertical -> Transversal de cultura

- `saberes_ancestrales teaches comunidades`
- `saberes_ancestrales teaches talleres`
- `saberes_ancestrales teaches cursos`
- `eventos activates comunidades`
- `talleres activates saberes_ancestrales`

## Vertical -> Transversal de gobernanza

- `participacion governs energia_comunitaria`
- `presupuestos_participativos governs inversiones_colectivas`
- `transparencia audits grupos_consumo`
- `transparencia audits energia_comunitaria`

## Lo que hoy está mejor resuelto

### `comunidades -> energia_comunitaria`

Patrón bueno:

- base social separada
- vertical operativo con tablas propias
- vínculo claro por `comunidad_id`

### `comunidades -> ayuda_vecinal / eventos / foros`

Patrón bueno:

- la comunidad organiza el contexto
- la lógica del dominio no se mete dentro del módulo base

### `grupos_consumo` y `banco_tiempo`

Patrón bueno:

- verticales con modelo de datos propio
- suficiente autonomía
- integración razonable con capas sociales

## Lo que conviene formalizar

### 1. Distinguir `comunidades`, `socios` y `colectivos`

Regla propuesta:

- `comunidades`: tejido social y actividad compartida
- `socios`: membresía formal, roles, cuotas, derechos
- `colectivos`: agrupación política/asociativa o entidad de segundo nivel

### 2. Dar a `huella_ecologica` un contrato de datos claro

Debe recibir, como mínimo:

- `kwh_locales`, `co2_evitable`, `ahorro`
- `km_evitar`, `producto_local`, `circuito_corto`
- `residuos_valorizados`, `plástico_evitable`

### 3. Evitar que `economia_suficiencia` haga de vertical

Debe seguir siendo:

- hábitos
- cultura
- compromisos
- biblioteca

No:

- mercado
- pagos
- inventario
- liquidaciones

### 4. Elevar `nodo` como futura pieza central

La matriz pide una capa explícita de `nodo` para unificar:

- perfil
- comunidad
- red federada
- métricas
- principios

## Propuesta de metadatos por módulo

Para registrar explícitamente esta arquitectura, cada módulo debería poder declarar algo así:

```php
[
    'module_role' => 'vertical',
    'depends_on' => ['comunidades'],
    'supports_modules' => ['participacion', 'huella_ecologica'],
    'measures_modules' => [],
    'governs_modules' => [],
    'teaches_modules' => [],
]
```

Ejemplos:

### `energia_comunitaria`

```php
[
    'module_role' => 'vertical',
    'depends_on' => ['comunidades'],
    'supports_modules' => [],
    'measures_modules' => [],
    'governs_modules' => [],
    'teaches_modules' => [],
]
```

### `huella_ecologica`

```php
[
    'module_role' => 'transversal',
    'depends_on' => [],
    'supports_modules' => [],
    'measures_modules' => ['energia_comunitaria', 'grupos_consumo', 'compostaje', 'reciclaje'],
    'governs_modules' => [],
    'teaches_modules' => [],
]
```

### `saberes_ancestrales`

```php
[
    'module_role' => 'transversal',
    'depends_on' => ['comunidades'],
    'supports_modules' => ['talleres', 'cursos'],
    'measures_modules' => [],
    'governs_modules' => [],
    'teaches_modules' => ['comunidades', 'talleres', 'cursos'],
]
```

## Conclusión operativa

La matriz confirma tres cosas:

1. los módulos principales están globalmente bien separados
2. ya existe una arquitectura usable de base, verticales y transversales
3. falta hacer explícitas las relaciones para evitar ambigüedad, solapamiento y deuda conceptual

## Siguiente uso recomendado

Esta matriz debería servir para:

- rediseñar el compositor
- definir dependencias reales entre módulos
- preparar la futura entidad `nodo`
- construir dashboards de transición y regeneración
