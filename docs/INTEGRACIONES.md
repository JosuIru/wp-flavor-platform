# Sistema de Integraciones Dinámicas entre Módulos

## Descripción General

El sistema de integraciones permite que los módulos "polivalentes" (providers) ofrezcan contenido que puede ser vinculado por módulos "base" (consumers). Esto crea relaciones dinámicas entre entidades de diferentes módulos.

**Ejemplo práctico:** Un producto de grupos de consumo puede tener vinculadas recetas, videos y episodios de podcast relacionados.

---

## Arquitectura

```
┌─────────────────┐     ┌─────────────────┐
│    PROVIDERS    │     │    CONSUMERS    │
│  (Ofrecen)      │────▶│  (Aceptan)      │
├─────────────────┤     ├─────────────────┤
│ - recetas       │     │ - productos     │
│ - multimedia    │     │ - eventos       │
│ - podcast       │     │ - talleres      │
│ - biblioteca    │     │ - comunidades   │
│ - videos        │     │ - etc...        │
└─────────────────┘     └─────────────────┘
```

### Archivos del Sistema

| Archivo | Descripción |
|---------|-------------|
| `trait-module-integrations.php` | Traits Provider y Consumer |
| `class-integration-registry.php` | Registro central singleton |
| `config-integrations.php` | Matriz de configuración |

---

## Implementación

### 1. Convertir un Módulo en Provider

Los providers son módulos que **ofrecen** contenido para ser vinculado.

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    // 1. Usar el trait
    use Flavor_Module_Integration_Provider;

    // 2. Definir el tipo de contenido
    protected function get_integration_content_type() {
        return [
            'id'         => 'mi_contenido',
            'label'      => __('Mi Contenido', 'flavor-chat-ia'),
            'icon'       => 'dashicons-admin-post',
            'post_type'  => 'mi_post_type', // O 'table' => 'mi_tabla'
            'capability' => 'edit_posts',
        ];
    }

    // 3. Registrar en init()
    public function init() {
        $this->register_as_integration_provider();
        // ... resto del código
    }
}
```

### 2. Convertir un Módulo en Consumer

Los consumers son módulos que **aceptan** contenido de providers.

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    // 1. Usar el trait
    use Flavor_Module_Integration_Consumer;

    // 2. Definir qué providers acepta
    protected function get_accepted_integrations() {
        return ['recetas', 'multimedia', 'podcast'];
    }

    // 3. Definir dónde mostrar metaboxes
    protected function get_integration_targets() {
        return [
            [
                'type'      => 'post',
                'post_type' => 'mi_post_type',
                'context'   => 'side', // 'normal', 'side', 'advanced'
            ],
        ];
    }

    // 4. Registrar en init()
    public function init() {
        $this->register_as_integration_consumer();
        // ... resto del código
    }
}
```

### 3. Target con Tabla Custom

Si el módulo usa tablas en lugar de CPT:

```php
protected function get_integration_targets() {
    global $wpdb;
    return [
        [
            'type'    => 'table',
            'table'   => $wpdb->prefix . 'mi_tabla',
            'context' => 'normal',
        ],
    ];
}
```

---

## Configuración Centralizada

El archivo `config-integrations.php` permite configurar integraciones sin modificar los módulos:

```php
$integration_matrix = [
    'grupos_consumo' => [
        'targets' => [
            ['type' => 'post', 'post_type' => 'gc_producto', 'context' => 'side'],
        ],
        'accepts' => ['recetas', 'multimedia', 'podcast'],
    ],
    // ... más módulos
];
```

**Nota:** Los módulos con traits implementados tienen prioridad sobre esta configuración.

---

## API REST

### Endpoints Disponibles

```
GET  /wp-json/flavor-integration/v1/providers
GET  /wp-json/flavor-integration/v1/consumers
GET  /wp-json/flavor-integration/v1/content/{type}/{id}
POST /wp-json/flavor-integration/v1/relation
DELETE /wp-json/flavor-integration/v1/relation/{id}
```

### Ejemplo: Obtener contenido relacionado

```javascript
fetch('/wp-json/flavor-integration/v1/content/gc_producto/123')
  .then(response => response.json())
  .then(data => {
    console.log(data.recetas);     // Recetas vinculadas
    console.log(data.multimedia);  // Multimedia vinculado
  });
```

---

## Funciones Helper

### PHP

```php
// Verificar si un módulo está activo
flavor_is_module_active('recetas');

// Obtener providers activos para un consumer
$providers = flavor_get_active_providers_for('grupos_consumo');

// Verificar si un consumer acepta un provider
$acepta = flavor_consumer_accepts('grupos_consumo', 'recetas');

// Obtener contenido relacionado
$recetas = Flavor_Integration_Registry::get_instance()
    ->get_related_content('gc_producto', $producto_id, 'recetas');
```

### JavaScript (Admin)

```javascript
// El sistema expone flavorIntegrations en admin
if (typeof flavorIntegrations !== 'undefined') {
    console.log(flavorIntegrations.providers);
    console.log(flavorIntegrations.ajaxUrl);
}
```

---

## Relación Bidireccional

El sistema mantiene sincronización bidireccional automática:

1. Al vincular una receta a un producto, el producto aparece en la receta
2. Al desvincular, se actualiza en ambos lados
3. Meta keys utilizadas: `_flavor_integrated_{provider_id}`

---

## Matriz de Integraciones Actual

### Providers Registrados

| ID | Label | Tipo | Módulo |
|----|-------|------|--------|
| recetas | Recetas | CPT | recetas |
| multimedia | Multimedia | Tabla | multimedia |
| podcast | Episodios | Tabla | podcast |
| biblioteca | Recursos | Tabla | biblioteca |
| videos | Videos | CPT | multimedia |
| publicaciones | Publicaciones | Tabla | red_social |
| eventos | Eventos | CPT | eventos |
| cursos | Cursos | CPT | cursos |
| talleres | Talleres | CPT | talleres |

### Consumers y sus Aceptaciones

| Consumer | Acepta |
|----------|--------|
| grupos_consumo | recetas, multimedia, podcast, biblioteca |
| eventos | multimedia, podcast, recetas, publicaciones |
| talleres | multimedia, recetas, biblioteca |
| cursos | multimedia, videos, biblioteca, podcast |
| comunidades | multimedia, podcast, publicaciones, biblioteca, recetas |
| saberes_ancestrales | recetas, biblioteca, multimedia, podcast, videos |
| biodiversidad_local | multimedia, recetas, biblioteca |
| circulos_cuidados | multimedia, recetas, biblioteca |
| compostaje | multimedia, recetas, biblioteca |
| ... | (ver config-integrations.php para lista completa) |

---

## Hooks y Filtros

### Acciones

```php
// Cuando se registra un provider
do_action('flavor_integration_provider_registered', $provider_id, $config);

// Cuando se crea una relación
do_action('flavor_integration_relation_created', $consumer_type, $consumer_id, $provider_id, $content_id);

// Cuando se elimina una relación
do_action('flavor_integration_relation_deleted', $consumer_type, $consumer_id, $provider_id, $content_id);
```

### Filtros

```php
// Modificar providers disponibles
add_filter('flavor_integration_providers', function($providers) {
    // Agregar o modificar providers
    return $providers;
});

// Modificar consumers
add_filter('flavor_integration_consumers', function($consumers) {
    return $consumers;
});

// Personalizar metabox
add_filter('flavor_integration_metabox_args', function($args, $provider_id) {
    return $args;
}, 10, 2);
```

---

## Buenas Prácticas

1. **Usar traits siempre que sea posible** - Ofrecen más control que la configuración centralizada

2. **Definir capabilities apropiados** - Controla quién puede crear relaciones

3. **Usar context apropiado** - `side` para metaboxes pequeños, `normal` para grandes

4. **Cachear resultados** - El sistema usa transients, pero considera cachear en tu código

5. **Limpiar al desactivar** - Implementa limpieza de meta al desactivar módulos

---

## Troubleshooting

### El metabox no aparece

1. Verificar que ambos módulos están activos
2. Verificar que el consumer acepta el provider
3. Limpiar caché de transients: `delete_transient('flavor_integration_...')`

### Las relaciones no se guardan

1. Verificar nonce y capabilities
2. Revisar que el hook `save_post` se ejecuta
3. Verificar meta keys en la base de datos

### Error en REST API

1. Verificar que el usuario tiene permisos
2. Revisar logs de PHP
3. Verificar que el endpoint está registrado: `rest_api_init`
