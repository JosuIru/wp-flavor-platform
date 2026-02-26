# Funcionalidades Compartidas entre Módulos

## Descripción General

El sistema de funcionalidades compartidas proporciona características comunes que pueden aplicarse a cualquier entidad de cualquier módulo: valoraciones, favoritos, comentarios, seguimiento, etc.

Esto evita duplicar código y garantiza una experiencia consistente.

---

## Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│              FLAVOR_SHARED_FEATURES                     │
│                   (Registro Central)                    │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Features Disponibles:                                  │
│  ┌─────────┬─────────┬─────────┬─────────┬─────────┐   │
│  │ ratings │favorites│comments │ follow  │  share  │   │
│  ├─────────┼─────────┼─────────┼─────────┼─────────┤   │
│  │ report  │  tags   │  views  │reactions│bookmarks│   │
│  ├─────────┼─────────┼─────────┼─────────┼─────────┤   │
│  │versions │ qrcode  │ export  │         │         │   │
│  └─────────┴─────────┴─────────┴─────────┴─────────┘   │
│                         │                               │
│                         ▼                               │
│  ┌──────────────────────────────────────────────────┐  │
│  │              ENTIDADES                           │  │
│  │  recetas, productos, eventos, comunidades, ...   │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

---

## Features Disponibles

| ID | Nombre | Descripción | Default |
|----|--------|-------------|---------|
| `ratings` | Valoraciones | Sistema de estrellas (1-5) | ✓ Activo |
| `favorites` | Favoritos | Marcar como favorito | ✓ Activo |
| `comments` | Comentarios | Sistema de comentarios | ✓ Activo |
| `follow` | Seguir | Seguir entidad para updates | Inactivo |
| `share` | Compartir | Botones redes sociales | ✓ Activo |
| `report` | Reportar | Reportar contenido | Inactivo |
| `tags` | Etiquetas | Sistema de tags | Inactivo |
| `views` | Vistas | Contador de vistas | ✓ Activo |
| `reactions` | Reacciones | Emojis (like, love, etc) | Inactivo |
| `bookmarks` | Guardar | Guardar para después | Inactivo |
| `versions` | Versiones | Historial de cambios | Inactivo |
| `qrcode` | Código QR | Generar QR | Inactivo |
| `export` | Exportar | Exportar PDF/JSON | Inactivo |

---

## Uso Básico

### Habilitar Feature para un Módulo

```php
// En el init() de tu módulo o en functions.php
flavor_enable_feature('flavor_receta', 'ratings');
flavor_enable_feature('flavor_receta', 'favorites');
flavor_enable_feature('flavor_receta', 'share');

// Con opciones personalizadas
flavor_enable_feature('gc_producto', 'ratings', [
    'max_stars' => 5,
    'allow_half' => false,
]);
```

### Renderizar Features en Frontend

```php
// En tu template o shortcode
flavor_render_features('flavor_receta', $post_id);
```

**Output HTML:**
```html
<div class="flavor-entity-features" data-entity-type="flavor_receta" data-entity-id="123">
    <!-- Botón de favoritos -->
    <button class="flavor-feature-btn flavor-favorite" data-action="favorite">
        <span class="dashicons dashicons-heart"></span>
        <span class="count">24</span>
    </button>

    <!-- Rating -->
    <div class="flavor-rating-container">
        <div class="flavor-stars" data-action="rating">
            <span class="star filled">★</span>
            <span class="star filled">★</span>
            <span class="star filled">★</span>
            <span class="star filled">★</span>
            <span class="star">★</span>
        </div>
        <span class="rating-info">4.2 (156 votos)</span>
    </div>

    <!-- Compartir -->
    <div class="flavor-share-buttons">
        <a href="..." class="share-twitter">...</a>
        <a href="..." class="share-facebook">...</a>
        <a href="..." class="share-whatsapp">...</a>
    </div>
</div>
```

### Obtener Contadores

```php
$counts = flavor_get_entity_counts('flavor_receta', $post_id);

// Resultado:
[
    'favorite' => ['count' => 24, 'sum' => 0, 'avg' => 0],
    'rating'   => ['count' => 156, 'sum' => 655, 'avg' => 4.2],
    'view'     => ['count' => 1250, 'sum' => 0, 'avg' => 0],
]
```

---

## API REST

### Endpoints

```
POST /wp-json/flavor-features/v1/interact
GET  /wp-json/flavor-features/v1/entity/{type}/{id}
GET  /wp-json/flavor-features/v1/user/interactions
```

### Realizar Interacción

```bash
POST /wp-json/flavor-features/v1/interact
Content-Type: application/json
Authorization: Bearer {token}

{
    "entity_type": "flavor_receta",
    "entity_id": 123,
    "action": "favorite",
    "value": null
}
```

**Respuesta:**
```json
{
    "status": "added",
    "counts": {
        "favorite": {"count": 25, "sum": 0, "avg": 0}
    }
}
```

### Valorar con Estrellas

```bash
POST /wp-json/flavor-features/v1/interact

{
    "entity_type": "flavor_receta",
    "entity_id": 123,
    "action": "rating",
    "value": "4"
}
```

### Obtener Interacciones de Entidad

```bash
GET /wp-json/flavor-features/v1/entity/flavor_receta/123
```

**Respuesta:**
```json
{
    "counts": {
        "favorite": {"count": 25, "sum": 0, "avg": 0},
        "rating": {"count": 157, "sum": 659, "avg": 4.2},
        "view": {"count": 1251, "sum": 0, "avg": 0}
    },
    "user_interactions": {
        "favorite": {"value": null, "date": "2024-01-15 10:30:00"},
        "rating": {"value": "4", "date": "2024-01-15 10:31:00"}
    }
}
```

### Obtener Favoritos del Usuario

```bash
GET /wp-json/flavor-features/v1/user/interactions?tipo=favorite&limite=20
```

---

## AJAX (Fallback)

Para casos donde REST no está disponible:

```javascript
jQuery.ajax({
    url: FlavorFeatures.ajaxUrl,
    method: 'POST',
    data: {
        action: 'flavor_feature_action',
        feature_action: 'favorite',
        entity_type: 'flavor_receta',
        entity_id: 123,
        nonce: FlavorFeatures.nonce
    },
    success: function(response) {
        if (response.success) {
            console.log(response.data.status); // 'added' o 'removed'
        }
    }
});
```

---

## Base de Datos

### Tabla: `wp_flavor_interactions`

Almacena todas las interacciones individuales.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | bigint | ID único |
| user_id | bigint | ID del usuario |
| entity_type | varchar(50) | Tipo de entidad |
| entity_id | bigint | ID de la entidad |
| interaction_type | varchar(50) | Tipo (favorite, rating, etc) |
| value | text | Valor (ej: "4" para rating) |
| metadata | longtext | JSON adicional |
| ip_address | varchar(45) | IP del usuario |
| created_at | datetime | Fecha de creación |
| updated_at | datetime | Última actualización |

**Índices:**
- `user_entity`: (user_id, entity_type, entity_id)
- `entity_interaction`: (entity_type, entity_id, interaction_type)

### Tabla: `wp_flavor_interaction_counts`

Contadores agregados para rendimiento.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| entity_type | varchar(50) | Tipo de entidad |
| entity_id | bigint | ID de la entidad |
| interaction_type | varchar(50) | Tipo de interacción |
| count_value | bigint | Número de interacciones |
| sum_value | decimal | Suma de valores |
| avg_value | decimal | Promedio |

---

## Personalización

### Registrar Feature Personalizada

```php
$features = Flavor_Shared_Features::get_instance();

$features->register_feature('applause', [
    'label'       => __('Aplausos', 'mi-plugin'),
    'description' => __('Dar aplausos al contenido', 'mi-plugin'),
    'icon'        => 'dashicons-thumbs-up',
    'handler'     => 'Mi_Feature_Applause', // Clase handler opcional
    'default'     => false,
]);

// Luego habilitarla
flavor_enable_feature('mi_entidad', 'applause');
```

### Personalizar Renderizado

```php
// Filtrar argumentos del renderizado
add_filter('flavor_feature_render_args', function($args, $feature_id, $entity_type) {
    if ($feature_id === 'ratings' && $entity_type === 'gc_producto') {
        $args['max_stars'] = 10;
    }
    return $args;
}, 10, 3);
```

### CSS Personalizado

```css
/* Cambiar color de favoritos */
.flavor-favorite.active {
    background: #ff6b6b;
    border-color: #ff6b6b;
}

/* Estrellas doradas */
.flavor-stars .star.filled {
    color: #ffd700;
}
```

---

## Hooks y Filtros

### Acciones

```php
// Cuando se añade interacción
do_action('flavor_interaction_favorite', $user_id, $entity_type, $entity_id, $value, 'added');
do_action('flavor_interaction_rating', $user_id, $entity_type, $entity_id, $value, 'added');

// Genérico
do_action("flavor_interaction_{$action}", $user_id, $entity_type, $entity_id, $value, $status);
```

### Filtros

```php
// Modificar features disponibles
add_filter('flavor_shared_features', function($features) {
    unset($features['report']); // Quitar reportar
    return $features;
});

// Modificar contadores antes de mostrar
add_filter('flavor_entity_counts', function($counts, $entity_type, $entity_id) {
    // Modificar counts
    return $counts;
}, 10, 3);
```

---

## JavaScript API

### Objeto Global

```javascript
// Disponible en frontend
window.FlavorFeatures = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    restUrl: '/wp-json/flavor-features/v1/',
    nonce: '...',
    restNonce: '...',
    isLoggedIn: true,
    strings: {
        loginRequired: 'Debes iniciar sesión...',
        error: 'Ha ocurrido un error'
    }
};
```

### Handler Externo

```javascript
// Puedes usar el handler directamente
FlavorFeaturesHandler.sendInteraction(
    'flavor_receta',
    123,
    'favorite',
    null,
    function(response) {
        console.log(response);
    }
);
```

---

## Integración con Módulos

### En el Módulo

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    public function init() {
        // Habilitar features para este módulo
        add_action('init', [$this, 'setup_features']);

        // Mostrar features en el contenido
        add_filter('the_content', [$this, 'append_features']);
    }

    public function setup_features() {
        flavor_enable_feature('mi_post_type', 'ratings');
        flavor_enable_feature('mi_post_type', 'favorites');
        flavor_enable_feature('mi_post_type', 'share');
        flavor_enable_feature('mi_post_type', 'views');
    }

    public function append_features($content) {
        if (get_post_type() !== 'mi_post_type') {
            return $content;
        }

        ob_start();
        flavor_render_features('mi_post_type', get_the_ID());
        $features_html = ob_get_clean();

        return $content . $features_html;
    }
}
```

---

## Panel de Administración

Cada módulo puede mostrar configuración de features en su panel de settings:

```php
// El sistema añade automáticamente esto si usas:
do_action('flavor_module_settings_after', $module_id, $settings);
```

Esto muestra checkboxes para activar/desactivar cada feature.

---

## Rendimiento

### Contadores Agregados

El sistema usa una tabla de contadores (`wp_flavor_interaction_counts`) para evitar COUNT() en cada request.

### Actualización Automática

Los contadores se actualizan automáticamente al crear/eliminar interacciones mediante `ON DUPLICATE KEY UPDATE`.

### Caché Recomendada

Para sitios de alto tráfico, considera:

```php
// Cachear contadores
$cache_key = "flavor_counts_{$entity_type}_{$entity_id}";
$counts = wp_cache_get($cache_key);

if ($counts === false) {
    $counts = flavor_get_entity_counts($entity_type, $entity_id);
    wp_cache_set($cache_key, $counts, '', 300); // 5 minutos
}
```

---

## Troubleshooting

### Las interacciones no se guardan

1. Verificar que el usuario está logueado (excepto views)
2. Verificar nonce válido
3. Verificar que las tablas existen

### El botón no responde

1. Verificar que jQuery está cargado
2. Verificar que `FlavorFeatures` está definido
3. Revisar consola del navegador

### Los contadores no coinciden

1. Ejecutar recálculo manual:
```php
$features = Flavor_Shared_Features::get_instance();
$features->recalculate_counts($entity_type, $entity_id);
```
