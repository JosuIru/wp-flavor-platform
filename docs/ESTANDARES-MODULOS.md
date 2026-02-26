# Estándares de Módulos - Flavor Platform

**Versión:** 1.0.0
**Fecha:** 2026-02-25
**Autor:** Claude Code

Este documento define los estándares, patrones y requisitos que debe cumplir cada módulo del sistema Flavor Platform para garantizar consistencia, interoperabilidad y mantenibilidad.

---

## Índice

1. [Estructura de Archivos](#1-estructura-de-archivos)
2. [Interfaz y Clase Base](#2-interfaz-y-clase-base)
3. [Pantallas Requeridas](#3-pantallas-requeridas)
4. [Variables de Diseño](#4-variables-de-diseño)
5. [Sistema de Integraciones](#5-sistema-de-integraciones)
6. [Dashboard Widgets](#6-dashboard-widgets)
7. [Funcionalidades Compartidas](#7-funcionalidades-compartidas)
8. [Red de Nodos (Multisite)](#8-red-de-nodos-multisite)
9. [REST API](#9-rest-api)
10. [Estándares de Código](#10-estándares-de-código)
11. [Checklist de Módulo Completo](#11-checklist-de-módulo-completo)

---

## 1. Estructura de Archivos

### 1.1 Estructura Mínima

```
includes/modules/mi-modulo/
├── class-mi-modulo-module.php      # Clase principal (OBLIGATORIO)
├── class-mi-modulo-api.php         # API REST (si aplica)
├── assets/
│   ├── css/
│   │   └── mi-modulo.css           # Estilos frontend
│   └── js/
│       └── mi-modulo.js            # JavaScript frontend
├── frontend/
│   └── class-mi-modulo-frontend-controller.php  # Controlador frontend
├── views/
│   ├── dashboard.php               # Vista dashboard del usuario
│   └── [otras-vistas].php
└── templates/                      # Templates para shortcodes
    └── listado.php
```

### 1.2 Estructura Completa

```
includes/modules/mi-modulo/
├── class-mi-modulo-module.php      # Clase principal
├── class-mi-modulo-api.php         # Endpoints REST
├── class-mi-modulo-widget.php      # Widget de dashboard (opcional)
├── assets/
│   ├── css/
│   │   ├── mi-modulo.css           # Estilos frontend
│   │   └── mi-modulo-admin.css     # Estilos admin
│   ├── js/
│   │   ├── mi-modulo.js            # JS frontend
│   │   └── mi-modulo-admin.js      # JS admin
│   └── img/                        # Imágenes del módulo
├── frontend/
│   ├── class-mi-modulo-frontend-controller.php
│   ├── archive.php                 # Template archivo CPT
│   └── single.php                  # Template single CPT
├── views/
│   ├── dashboard.php               # Dashboard del usuario
│   ├── listado.php                 # Vista de listado
│   ├── detalle.php                 # Vista de detalle
│   ├── formulario.php              # Vista de crear/editar
│   └── estadisticas.php            # Vista de estadísticas
├── templates/
│   └── [shortcode-templates].php
└── gateways/                       # Integraciones de pago (si aplica)
    ├── class-mi-modulo-gateway-stripe.php
    └── class-mi-modulo-gateway-woocommerce.php
```

### 1.3 Convención de Nombres

| Tipo | Formato | Ejemplo |
|------|---------|---------|
| Directorio | `kebab-case` | `grupos-consumo` |
| Clase principal | `Flavor_Chat_[PascalCase]_Module` | `Flavor_Chat_Grupos_Consumo_Module` |
| ID del módulo | `snake_case` | `grupos_consumo` |
| Archivo de clase | `class-[kebab-case]-module.php` | `class-grupos-consumo-module.php` |
| Tablas DB | `{$wpdb->prefix}flavor_[snake_case]` | `wp_flavor_gc_productos` |

---

## 2. Interfaz y Clase Base

### 2.1 Interfaz Requerida

Todos los módulos **DEBEN** implementar `Flavor_Chat_Module_Interface`:

```php
interface Flavor_Chat_Module_Interface {
    public function get_id();                    // ID único del módulo
    public function get_name();                  // Nombre visible
    public function get_description();           // Descripción breve
    public function can_activate();              // Verificar dependencias
    public function get_activation_error();      // Mensaje si no puede activarse
    public function init();                      // Inicialización
    public function get_actions();               // Acciones disponibles
    public function execute_action($action, $params);  // Ejecutar acción
    public function get_tool_definitions();      // Definiciones para IA
    public function get_knowledge_base();        // Conocimiento base para IA
    public function get_faqs();                  // FAQs del módulo
    public function get_visibility();            // 'public', 'private', 'members_only'
    public function get_required_capability();   // Capacidad requerida
    public function get_dependencies();          // Módulos requeridos
    public function get_pages_definition();      // Páginas del módulo
}
```

### 2.2 Clase Base

Extender `Flavor_Chat_Module_Base` proporciona implementaciones por defecto:

```php
class Flavor_Chat_MiModulo_Module extends Flavor_Chat_Module_Base {

    public function __construct() {
        // Propiedades obligatorias
        $this->id = 'mi_modulo';
        $this->name = __('Mi Módulo', 'flavor-chat-ia');
        $this->description = __('Descripción del módulo.', 'flavor-chat-ia');

        // Propiedades opcionales con valores por defecto
        $this->module_icon = 'dashicons-admin-plugins';  // Icono
        $this->module_color = '#3b82f6';                 // Color principal
        $this->category = 'general';                     // Categoría
        $this->visibility = 'public';                    // Visibilidad
        $this->required_capability = 'read';             // Capacidad mínima

        parent::__construct();
    }

    public function init() {
        // Hooks de WordPress
        add_action('init', [$this, 'register_post_types']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Shortcodes
        add_shortcode('mi_modulo_listado', [$this, 'shortcode_listado']);

        // AJAX
        add_action('wp_ajax_mi_modulo_accion', [$this, 'ajax_handler']);
        add_action('wp_ajax_nopriv_mi_modulo_accion', [$this, 'ajax_handler_public']);

        // Dashboard widget (si usa el trait)
        $this->register_dashboard_widget();

        // Integraciones (si aplica)
        $this->register_as_integration_provider();
    }
}
```

### 2.3 Propiedades de la Clase Base

| Propiedad | Tipo | Obligatorio | Descripción |
|-----------|------|-------------|-------------|
| `$id` | string | Sí | ID único en snake_case |
| `$name` | string | Sí | Nombre visible |
| `$description` | string | Sí | Descripción breve |
| `$module_icon` | string | No | Clase dashicons |
| `$module_color` | string | No | Color hexadecimal |
| `$category` | string | No | Categoría de agrupación |
| `$visibility` | string | No | public/private/members_only |
| `$required_capability` | string | No | Capacidad WordPress |
| `$version` | string | No | Versión del módulo |

---

## 3. Pantallas Requeridas

### 3.1 Pantallas Obligatorias

Todo módulo con contenido de usuario debe implementar:

| Pantalla | Tipo | Descripción | Shortcode Sugerido |
|----------|------|-------------|-------------------|
| **Dashboard** | Vista | Resumen del módulo para el usuario | `[mi_modulo_dashboard]` |
| **Listado** | Vista | Lista de elementos | `[mi_modulo_listado]` |
| **Detalle** | Vista | Detalle de un elemento | `[mi_modulo_detalle]` |
| **Formulario** | Vista | Crear/Editar elemento | `[mi_modulo_formulario]` |

### 3.2 Estructura de Dashboard de Usuario

El dashboard del módulo debe incluir:

```php
// views/dashboard.php
<div class="flavor-module-dashboard" data-module="<?php echo esc_attr($module_id); ?>">
    <!-- Encabezado con título y acciones -->
    <header class="flavor-module-header">
        <h2><?php echo esc_html($module_name); ?></h2>
        <div class="flavor-module-actions">
            <a href="<?php echo $crear_url; ?>" class="flavor-btn flavor-btn-primary">
                <?php _e('Nuevo', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </header>

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-grid">
        <div class="flavor-stat-card">
            <span class="stat-value"><?php echo $total_items; ?></span>
            <span class="stat-label"><?php _e('Total', 'flavor-chat-ia'); ?></span>
        </div>
        <!-- Más stats... -->
    </div>

    <!-- Contenido principal -->
    <div class="flavor-module-content">
        <!-- Listado, tabla, cards, etc. -->
    </div>

    <!-- Acciones secundarias -->
    <footer class="flavor-module-footer">
        <a href="<?php echo $ver_todos_url; ?>"><?php _e('Ver todos', 'flavor-chat-ia'); ?></a>
    </footer>
</div>
```

### 3.3 Componentes de UI Estándar

#### Cards

```html
<div class="flavor-card">
    <div class="flavor-card-header">
        <h3 class="flavor-card-title">Título</h3>
        <span class="flavor-badge flavor-badge-success">Estado</span>
    </div>
    <div class="flavor-card-body">
        <!-- Contenido -->
    </div>
    <div class="flavor-card-footer">
        <button class="flavor-btn flavor-btn-secondary">Acción</button>
    </div>
</div>
```

#### Tablas

```html
<div class="flavor-table-wrapper">
    <table class="flavor-table">
        <thead>
            <tr>
                <th>Columna 1</th>
                <th>Columna 2</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Dato 1</td>
                <td>Dato 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

#### Estados vacíos

```html
<div class="flavor-empty-state">
    <div class="flavor-empty-icon">📦</div>
    <p class="flavor-empty-text"><?php _e('No hay elementos', 'flavor-chat-ia'); ?></p>
    <a href="<?php echo $crear_url; ?>" class="flavor-btn flavor-btn-primary">
        <?php _e('Crear primero', 'flavor-chat-ia'); ?>
    </a>
</div>
```

---

## 4. Variables de Diseño

### 4.1 Variables CSS Globales

Todo módulo **DEBE** usar las variables CSS del tema:

```css
/* Colores principales */
:root {
    --flavor-primary: #3b82f6;
    --flavor-primary-dark: #2563eb;
    --flavor-primary-light: #60a5fa;
    --flavor-secondary: #8b5cf6;

    /* Estados */
    --flavor-success: #10b981;
    --flavor-warning: #f59e0b;
    --flavor-danger: #ef4444;
    --flavor-info: #06b6d4;

    /* Neutros */
    --flavor-bg: #ffffff;
    --flavor-bg-secondary: #f3f4f6;
    --flavor-bg-tertiary: #e5e7eb;
    --flavor-text: #1f2937;
    --flavor-text-secondary: #6b7280;
    --flavor-text-muted: #9ca3af;
    --flavor-border: #e5e7eb;

    /* Espaciado */
    --flavor-spacing-xs: 0.25rem;
    --flavor-spacing-sm: 0.5rem;
    --flavor-spacing-md: 1rem;
    --flavor-spacing-lg: 1.5rem;
    --flavor-spacing-xl: 2rem;

    /* Bordes */
    --flavor-radius-sm: 0.25rem;
    --flavor-radius-md: 0.5rem;
    --flavor-radius-lg: 0.75rem;
    --flavor-radius-full: 9999px;

    /* Sombras */
    --flavor-shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
    --flavor-shadow-md: 0 4px 6px rgba(0,0,0,0.1);
    --flavor-shadow-lg: 0 10px 15px rgba(0,0,0,0.1);

    /* Transiciones */
    --flavor-transition: 150ms ease;
}
```

### 4.2 Clases CSS Estándar

```css
/* Botones */
.flavor-btn {
    padding: var(--flavor-spacing-sm) var(--flavor-spacing-md);
    border-radius: var(--flavor-radius-md);
    font-weight: 500;
    transition: all var(--flavor-transition);
}

.flavor-btn-primary {
    background: var(--flavor-primary);
    color: white;
}

.flavor-btn-secondary {
    background: var(--flavor-bg-secondary);
    color: var(--flavor-text);
    border: 1px solid var(--flavor-border);
}

.flavor-btn-success { background: var(--flavor-success); color: white; }
.flavor-btn-danger { background: var(--flavor-danger); color: white; }

/* Badges */
.flavor-badge {
    padding: var(--flavor-spacing-xs) var(--flavor-spacing-sm);
    border-radius: var(--flavor-radius-full);
    font-size: 0.75rem;
    font-weight: 600;
}

.flavor-badge-success { background: var(--flavor-success); color: white; }
.flavor-badge-warning { background: var(--flavor-warning); color: white; }
.flavor-badge-danger { background: var(--flavor-danger); color: white; }
.flavor-badge-info { background: var(--flavor-info); color: white; }

/* Formularios */
.flavor-input,
.flavor-select,
.flavor-textarea {
    width: 100%;
    padding: var(--flavor-spacing-sm) var(--flavor-spacing-md);
    border: 1px solid var(--flavor-border);
    border-radius: var(--flavor-radius-md);
    background: var(--flavor-bg);
    transition: border-color var(--flavor-transition);
}

.flavor-input:focus,
.flavor-select:focus,
.flavor-textarea:focus {
    outline: none;
    border-color: var(--flavor-primary);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
```

### 4.3 Responsive

```css
/* Breakpoints */
@media (max-width: 768px) {
    .flavor-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-table-wrapper {
        overflow-x: auto;
    }

    .flavor-card-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .flavor-stats-grid {
        grid-template-columns: 1fr;
    }

    .flavor-btn {
        width: 100%;
    }
}
```

---

## 5. Sistema de Integraciones

### 5.1 Tipos de Módulos

| Tipo | Descripción | Ejemplo |
|------|-------------|---------|
| **Provider** | Ofrece contenido para vincular | Recetas, Multimedia, Podcast |
| **Consumer** | Acepta contenido de providers | Eventos, Talleres, Productos |
| **Híbrido** | Ambos | Cursos (ofrece y acepta) |

### 5.2 Implementar Provider

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    use Flavor_Module_Integration_Provider;

    protected function get_integration_content_type() {
        return [
            'id'         => 'mi_contenido',
            'label'      => __('Mi Contenido', 'flavor-chat-ia'),
            'icon'       => 'dashicons-admin-post',
            'post_type'  => 'mi_cpt',  // O 'table' => 'mi_tabla'
            'capability' => 'edit_posts',
        ];
    }

    public function init() {
        $this->register_as_integration_provider();
        // ...
    }
}
```

### 5.3 Implementar Consumer

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    use Flavor_Module_Integration_Consumer;

    protected function get_accepted_integrations() {
        return ['recetas', 'multimedia', 'podcast'];
    }

    protected function get_integration_targets() {
        return [
            ['type' => 'post', 'post_type' => 'mi_cpt', 'context' => 'side'],
        ];
    }

    public function init() {
        $this->register_as_integration_consumer();
        // ...
    }
}
```

### 5.4 Matriz de Integraciones Actual

Configurado en `config-integrations.php`:

```php
$integration_matrix = [
    'grupos_consumo' => [
        'accepts' => ['recetas', 'multimedia', 'podcast', 'videos'],
    ],
    'eventos' => [
        'accepts' => ['multimedia', 'podcast', 'recetas', 'articulos_social'],
    ],
    'talleres' => [
        'accepts' => ['multimedia', 'recetas', 'biblioteca'],
    ],
    // ... más consumers
];
```

---

## 6. Dashboard Widgets

### 6.1 Usar el Trait

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    use Flavor_Dashboard_Widget_Trait;

    // Configuración del widget
    protected $dashboard_widget_category = 'gestion';  // gestion, comunidad, economico
    protected $dashboard_widget_size = 'medium';       // small, medium, large
    protected $dashboard_widget_priority = 50;         // Menor = primero
    protected $dashboard_widget_refreshable = true;    // Permite actualizar AJAX
    protected $dashboard_widget_cache_time = 300;      // Segundos de caché

    public function init() {
        $this->register_dashboard_widget();
        // ...
    }

    // Datos para el widget
    public function get_dashboard_widget_data(): array {
        return [
            'total' => $this->get_total_items(),
            'recientes' => $this->get_recent_items(5),
            'stats' => $this->get_stats(),
        ];
    }

    // Renderizar el widget
    public function render_dashboard_widget(array $data): void {
        ?>
        <div class="flavor-widget-content">
            <p class="widget-stat"><?php echo $data['total']; ?> elementos</p>
            <ul class="widget-list">
                <?php foreach ($data['recientes'] as $item): ?>
                    <li><?php echo esc_html($item->titulo); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
    }
}
```

### 6.2 Categorías de Widgets

| Categoría | Descripción | Módulos Ejemplo |
|-----------|-------------|-----------------|
| `gestion` | Administración y organización | Socios, Facturas, Fichajes |
| `comunidad` | Interacción social | Red Social, Foros, Chat |
| `economico` | Economía y finanzas | Marketplace, Grupos Consumo |
| `servicios` | Servicios ciudadanos | Incidencias, Trámites, Reservas |
| `cultura` | Cultura y educación | Cursos, Talleres, Biblioteca |
| `ambiente` | Medio ambiente | Huertos, Compostaje, Reciclaje |

---

## 7. Funcionalidades Compartidas

### 7.1 Features Disponibles

| Feature | Descripción | Método de Activación |
|---------|-------------|---------------------|
| `ratings` | Valoraciones 1-5 estrellas | `flavor_enable_feature('mi_cpt', 'ratings')` |
| `favorites` | Marcar como favorito | `flavor_enable_feature('mi_cpt', 'favorites')` |
| `comments` | Sistema de comentarios | `flavor_enable_feature('mi_cpt', 'comments')` |
| `share` | Compartir en redes | `flavor_enable_feature('mi_cpt', 'share')` |
| `views` | Contador de vistas | `flavor_enable_feature('mi_cpt', 'views')` |
| `follow` | Seguir entidad | `flavor_enable_feature('mi_cpt', 'follow')` |
| `report` | Reportar contenido | `flavor_enable_feature('mi_cpt', 'report')` |

### 7.2 Implementación

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    public function init() {
        // Habilitar features
        add_action('init', [$this, 'setup_shared_features']);

        // Mostrar en contenido
        add_filter('the_content', [$this, 'append_features_to_content']);
    }

    public function setup_shared_features() {
        flavor_enable_feature('mi_cpt', 'ratings');
        flavor_enable_feature('mi_cpt', 'favorites');
        flavor_enable_feature('mi_cpt', 'share');
        flavor_enable_feature('mi_cpt', 'views');
    }

    public function append_features_to_content($content) {
        if (get_post_type() !== 'mi_cpt') {
            return $content;
        }

        ob_start();
        flavor_render_features('mi_cpt', get_the_ID());
        return $content . ob_get_clean();
    }
}
```

### 7.3 Obtener Datos

```php
// Obtener contadores de una entidad
$counts = flavor_get_entity_counts('mi_cpt', $post_id);
// Resultado: ['favorite' => ['count' => 24], 'rating' => ['avg' => 4.2], ...]

// Verificar si usuario interactuó
$has_favorited = flavor_user_has_interacted(get_current_user_id(), 'mi_cpt', $post_id, 'favorite');
```

---

## 8. Red de Nodos (Multisite)

### 8.1 Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    NODO PRINCIPAL (HUB)                     │
│                    Coordina la red                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│   ┌──────────┐    ┌──────────┐    ┌──────────┐             │
│   │  Nodo A  │◄──►│  Nodo B  │◄──►│  Nodo C  │             │
│   │ (Barrio) │    │ (Pueblo) │    │ (Ciudad) │             │
│   └──────────┘    └──────────┘    └──────────┘             │
│        │              │               │                     │
│        ▼              ▼               ▼                     │
│   Contenido      Contenido       Contenido                  │
│   Compartido     Compartido      Compartido                 │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 8.2 Funciones de Red

```php
// Verificar si estamos en multisite
if (flavor_is_network()) {
    // Obtener nodos activos
    $nodos = flavor_get_active_nodes();

    // Obtener contenido de otros nodos
    $contenido_red = flavor_get_network_content('eventos', [
        'nodos' => 'all',
        'limite' => 10,
    ]);

    // Publicar contenido a la red
    flavor_broadcast_to_network('mi_contenido', $item_id, [
        'nodos_destino' => [2, 3, 5],
        'tipo' => 'evento',
    ]);
}
```

### 8.3 Sincronización

Los módulos pueden implementar sincronización de contenido:

```php
class Mi_Modulo extends Flavor_Chat_Module_Base {

    use Flavor_Network_Sync_Trait;

    protected function get_syncable_content_types() {
        return ['mi_cpt'];
    }

    protected function should_sync_item($item) {
        // Solo sincronizar si está marcado como público
        return get_post_meta($item->ID, '_compartir_red', true);
    }
}
```

---

## 9. REST API

### 9.1 Registro de Endpoints

```php
public function register_rest_routes() {
    $namespace = 'flavor/v1';

    // Listar
    register_rest_route($namespace, '/mi-modulo', [
        'methods' => 'GET',
        'callback' => [$this, 'rest_listar'],
        'permission_callback' => [$this, 'check_read_permission'],
        'args' => [
            'pagina' => ['default' => 1, 'type' => 'integer'],
            'por_pagina' => ['default' => 20, 'type' => 'integer'],
            'estado' => ['type' => 'string'],
        ],
    ]);

    // Crear
    register_rest_route($namespace, '/mi-modulo', [
        'methods' => 'POST',
        'callback' => [$this, 'rest_crear'],
        'permission_callback' => [$this, 'check_write_permission'],
    ]);

    // Obtener uno
    register_rest_route($namespace, '/mi-modulo/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => [$this, 'rest_obtener'],
        'permission_callback' => [$this, 'check_read_permission'],
    ]);

    // Actualizar
    register_rest_route($namespace, '/mi-modulo/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => [$this, 'rest_actualizar'],
        'permission_callback' => [$this, 'check_write_permission'],
    ]);

    // Eliminar
    register_rest_route($namespace, '/mi-modulo/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => [$this, 'rest_eliminar'],
        'permission_callback' => [$this, 'check_delete_permission'],
    ]);
}
```

### 9.2 Callbacks de Permisos

```php
public function check_read_permission() {
    $visibility = $this->get_visibility();

    switch ($visibility) {
        case 'public':
            return true;
        case 'members_only':
            return is_user_logged_in();
        case 'private':
            return current_user_can($this->get_required_capability());
    }

    return false;
}

public function check_write_permission() {
    if (!is_user_logged_in()) {
        return new WP_Error('rest_forbidden', __('Acceso denegado', 'flavor-chat-ia'), ['status' => 401]);
    }

    return current_user_can('edit_posts');
}
```

### 9.3 Respuestas Estándar

```php
// Éxito con datos
return rest_ensure_response([
    'success' => true,
    'data' => $items,
    'meta' => [
        'total' => $total,
        'pagina' => $pagina,
        'paginas' => ceil($total / $por_pagina),
    ],
]);

// Error
return new WP_REST_Response([
    'success' => false,
    'error' => __('Mensaje de error', 'flavor-chat-ia'),
    'code' => 'error_code',
], 400);
```

---

## 10. Estándares de Código

### 10.1 PHP

- **Versión mínima:** PHP 7.4
- **Convención de nombres:** WordPress Coding Standards
- **Prefijo de funciones:** `flavor_` o métodos de clase
- **Sanitización:** Siempre sanitizar entrada del usuario
- **Escapado:** Siempre escapar salida con `esc_html`, `esc_attr`, `esc_url`
- **Nonces:** Verificar en todas las acciones POST/AJAX
- **Capacidades:** Verificar permisos antes de acciones

```php
// Correcto
public function ajax_guardar() {
    check_ajax_referer('mi_modulo_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => __('Sin permisos', 'flavor-chat-ia')]);
    }

    $titulo = sanitize_text_field($_POST['titulo'] ?? '');
    $contenido = wp_kses_post($_POST['contenido'] ?? '');

    // Procesar...

    wp_send_json_success(['message' => __('Guardado', 'flavor-chat-ia')]);
}
```

### 10.2 JavaScript

- **Módulos:** Usar IIFE o módulos ES6
- **jQuery:** Usar `$` solo dentro del closure
- **AJAX:** Preferir `fetch` o `$.ajax` con manejo de errores
- **Localización:** Usar `wp_localize_script` para strings

```javascript
(function($) {
    'use strict';

    const MiModulo = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            $(document).on('click', '.mi-boton', this.handleClick.bind(this));
        },

        async handleClick(e) {
            e.preventDefault();

            try {
                const response = await fetch(miModuloConfig.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'mi_modulo_accion',
                        nonce: miModuloConfig.nonce,
                        data: this.getData()
                    })
                });

                const result = await response.json();

                if (result.success) {
                    this.showSuccess(result.data.message);
                } else {
                    this.showError(result.data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                this.showError(miModuloConfig.strings.error);
            }
        }
    };

    $(document).ready(() => MiModulo.init());

})(jQuery);
```

### 10.3 CSS

- **BEM:** Usar metodología BEM para clases
- **Variables:** Usar CSS custom properties
- **Mobile-first:** Diseñar para móvil primero
- **Prefijo:** `.flavor-` o `.mi-modulo-`

```css
/* Block */
.flavor-mi-modulo { }

/* Element */
.flavor-mi-modulo__header { }
.flavor-mi-modulo__content { }
.flavor-mi-modulo__footer { }

/* Modifier */
.flavor-mi-modulo--loading { }
.flavor-mi-modulo__item--active { }
```

---

## 11. Checklist de Módulo Completo

### 11.1 Estructura

- [ ] Directorio con nombre en kebab-case
- [ ] Clase principal `class-[nombre]-module.php`
- [ ] Directorio `assets/css/` con estilos
- [ ] Directorio `assets/js/` con scripts
- [ ] Directorio `views/` con vistas
- [ ] Directorio `frontend/` con controller (si aplica)

### 11.2 Funcionalidad

- [ ] Extiende `Flavor_Chat_Module_Base`
- [ ] Define `$id`, `$name`, `$description`
- [ ] Implementa `init()` con hooks necesarios
- [ ] Implementa `can_activate()` si tiene dependencias
- [ ] Define `get_default_settings()` si tiene configuración

### 11.3 Pantallas

- [ ] Dashboard del usuario (`[modulo_dashboard]`)
- [ ] Listado de elementos (`[modulo_listado]`)
- [ ] Detalle de elemento (`[modulo_detalle]`)
- [ ] Formulario crear/editar (`[modulo_formulario]`)

### 11.4 API

- [ ] Endpoints REST registrados
- [ ] Permission callbacks implementados
- [ ] Respuestas siguiendo formato estándar
- [ ] Manejo de errores con `WP_Error`

### 11.5 Diseño

- [ ] Usa variables CSS globales
- [ ] Responsive (mobile-first)
- [ ] Estados vacíos implementados
- [ ] Mensajes de feedback al usuario

### 11.6 Integraciones

- [ ] Dashboard widget (si aplica)
- [ ] Shared features habilitadas (si aplica)
- [ ] Provider/Consumer configurado (si aplica)
- [ ] Sincronización de red (si aplica)

### 11.7 Calidad

- [ ] Sanitización de entrada
- [ ] Escapado de salida
- [ ] Verificación de nonces
- [ ] Verificación de capacidades
- [ ] Sin errores PHP
- [ ] Sin errores JavaScript

---

## Anexo: Comandos Útiles

### Verificar módulo

```bash
# Buscar errores PHP
php -l includes/modules/mi-modulo/class-mi-modulo-module.php

# Verificar que todos los archivos existen
ls -la includes/modules/mi-modulo/
```

### Registrar módulo

En `class-module-loader.php`:

```php
'mi_modulo' => [
    'class' => 'Flavor_Chat_MiModulo_Module',
    'file' => 'mi-modulo/class-mi-modulo-module.php',
],
```

### Añadir a perfil

En `class-app-profiles.php`:

```php
'mi_perfil' => [
    'modules' => [
        'mi_modulo',
        // ...otros módulos
    ],
],
```

---

*Documento generado el 2026-02-25 | Flavor Platform v4.0*
