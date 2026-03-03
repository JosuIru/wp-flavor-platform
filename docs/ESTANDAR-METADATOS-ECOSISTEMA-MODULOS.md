# Estándar de Metadatos de Ecosistema para Módulos

Fecha: 2026-03-03

## Objetivo

Definir un contrato técnico simple para describir el papel ecosistémico de cada módulo sin mezclarlo con la lógica funcional del módulo.

Este estándar permite responder desde código:

- qué tipo de módulo es
- de qué depende
- a qué módulos soporta
- qué módulos mide
- qué módulos gobierna
- qué módulos enseña o acompaña culturalmente
- para qué módulos actúa como base

## Dónde vive

El contrato se implementa en la base común:

- `includes/modules/interface-chat-module.php`

Y se expone por el loader:

- `includes/modules/class-module-loader.php`

## Contrato disponible

Todos los módulos que heredan de `Flavor_Chat_Module_Base` pueden declarar:

```php
protected $module_role = 'vertical';
protected $ecosystem_supports_modules = [];
protected $ecosystem_measures_modules = [];
protected $ecosystem_governs_modules = [];
protected $ecosystem_teaches_modules = [];
protected $ecosystem_base_for_modules = [];
```

Además pueden seguir usando:

```php
public function get_dependencies() {
    return [];
}
```

## Método normalizado

La clase base expone:

```php
public function get_ecosystem_metadata()
```

Con salida:

```php
[
    'module_role' => 'base|vertical|transversal',
    'depends_on' => [],
    'supports_modules' => [],
    'measures_modules' => [],
    'governs_modules' => [],
    'teaches_modules' => [],
    'base_for_modules' => [],
]
```

## Metadata adicional para dashboards

La clase base tambien puede exponer:

```php
public function get_dashboard_metadata()
```

Con salida:

```php
[
    'parent_module' => '',
    'satellite_priority' => 50,
    'transversal_priority' => 50,
    'client_contexts' => [],
    'admin_contexts' => [],
]
```

### Significado

#### `parent_module`

Modulo padre preferido para agrupar este modulo en dashboards.

Si no se declara, el sistema puede inferirlo desde `depends_on`, pero eso debe considerarse fallback.

#### `satellite_priority`

Orden relativo del modulo cuando aparece como satelite operativo dentro de un ecosistema.

#### `transversal_priority`

Orden relativo cuando el modulo aparece como capa transversal de soporte, medicion, gobernanza o aprendizaje.

#### `client_contexts`

Contextos de cliente donde este modulo deberia destacarse.

Ejemplos:

- `portal`
- `mi_panel`
- `cuenta`
- `comunidad`

#### `admin_contexts`

Contextos de backend o administracion donde este modulo deberia priorizarse.

Si no se declara, el admin puede reutilizar `client_contexts` como fallback.

### Uso recomendado

- Los modulos `base` deben declarar `parent_module` apuntando a si mismos para evitar agrupaciones ambiguas.
- Los modulos `verticales` deben declarar `parent_module` explicito cuando cuelgan de una base aunque su dependencia tecnica siga siendo opcional.
- Los modulos `transversales` deben usar `transversal_priority` para que el orden del dashboard no dependa del alfabeto.
- Si un modulo no declara esta metadata, el sistema mantiene fallback a `depends_on` y prioridades por defecto.
- Los valores de `client_contexts` deben seguir la guia comun de `GUIA-CONTEXTOS-DASHBOARD-MODULOS.md`.
- Los valores de `admin_contexts` deben mantenerse acotados a vistas de gestion y supervision.

## Semántica

### `module_role`

Valores permitidos:

- `base`
- `vertical`
- `transversal`

### `depends_on`

Dependencias estructurales mínimas para que el módulo tenga sentido dentro del ecosistema.

Ejemplo:

- `energia_comunitaria` depende de `comunidades`

### `supports_modules`

Módulos a los que da soporte operativo o contextual.

Ejemplo:

- `comunidades` soporta `eventos`, `foros`, `ayuda_vecinal`

### `measures_modules`

Módulos cuyos resultados o impacto mide.

Ejemplo:

- `huella_ecologica` mide `energia_comunitaria`, `grupos_consumo`

### `governs_modules`

Módulos sobre los que aporta decisión, supervisión o regla colectiva.

Ejemplo:

- `participacion` gobierna decisiones de inversión o priorización

### `teaches_modules`

Módulos a los que aporta aprendizaje, cambio cultural o transmisión de conocimiento.

Ejemplo:

- `saberes_ancestrales` enseña a `comunidades`, `talleres`, `cursos`

### `base_for_modules`

Módulos para los que actúa como base estructural.

Ejemplo:

- `comunidades` es base de `energia_comunitaria`

## Reglas de uso

### Cuándo usar `base`

Usar `base` si el módulo aporta:

- identidad compartida
- pertenencia
- estructura social u organizativa
- contexto reutilizable para otros módulos

Ejemplos:

- `comunidades`
- `socios`

### Cuándo usar `vertical`

Usar `vertical` si el módulo resuelve una operación concreta con lógica propia.

Ejemplos:

- `grupos_consumo`
- `banco_tiempo`
- `energia_comunitaria`
- `reservas`

### Cuándo usar `transversal`

Usar `transversal` si el módulo:

- mide
- gobierna
- enseña
- acompaña
- da cultura o impacto a otros

Ejemplos:

- `huella_ecologica`
- `economia_suficiencia`
- `saberes_ancestrales`
- `transparencia`

## Ejemplos reales

### `comunidades`

```php
$this->module_role = 'base';
$this->ecosystem_base_for_modules = ['eventos', 'ayuda_vecinal', 'foros', 'energia_comunitaria'];
$this->ecosystem_supports_modules = ['eventos', 'ayuda_vecinal', 'foros', 'presupuestos_participativos', 'energia_comunitaria'];
```

### `energia_comunitaria`

```php
$this->module_role = 'vertical';

public function get_dependencies() {
    return ['comunidades'];
}
```

### `huella_ecologica`

```php
$this->module_role = 'transversal';
$this->ecosystem_measures_modules = ['energia_comunitaria', 'grupos_consumo', 'compostaje', 'reciclaje', 'carpooling'];
```

### `saberes_ancestrales`

```php
$this->module_role = 'transversal';
$this->ecosystem_teaches_modules = ['comunidades', 'talleres', 'cursos'];
$this->ecosystem_supports_modules = ['talleres', 'cursos', 'comunidades'];
```

## Qué no hacer

- no usar este contrato para dependencias PHP duras entre clases
- no mezclarlo con permisos de usuario
- no usar `depends_on` para expresar afinidad conceptual débil
- no declarar como `base` un módulo que realmente es solo un vertical complejo

## Uso recomendado futuro

Este estándar debe alimentar:

- `App Composer`
- vistas de arquitectura
- documentación automática
- sugerencias de combinaciones de módulos
- dashboards de transición del ecosistema
