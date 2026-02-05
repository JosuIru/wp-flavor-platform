# Guía Completa de Módulos - Flavor Chat IA

## Índice

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Módulos Nativos Disponibles](#módulos-nativos-disponibles)
4. [Addons (Extensiones)](#addons-extensiones)
5. [Cómo Activar Módulos](#cómo-activar-módulos)
6. [Crear un Módulo Personalizado](#crear-un-módulo-personalizado)
7. [Sistema de Dependencias](#sistema-de-dependencias)
8. [API y Hooks Disponibles](#api-y-hooks-disponibles)
9. [Configuración de Módulos](#configuración-de-módulos)
10. [Preguntas Frecuentes](#preguntas-frecuentes)

---

## Introducción

Flavor Chat IA utiliza un sistema modular que permite activar solo las funcionalidades que necesitas. Esto mantiene tu sitio ligero y optimizado.

### Tipos de Extensiones

| Tipo | Ubicación | Descripción |
|------|-----------|-------------|
| **Módulos Nativos** | `/includes/modules/` | Funcionalidades integradas en el plugin core |
| **Addons** | `/addons/` | Extensiones independientes con funcionalidades avanzadas |

---

## Arquitectura del Sistema

### Flujo de Carga

```
WordPress inicia
    ↓
Plugin Flavor Chat IA se activa
    ↓
Se cargan las dependencias base
    ↓
Addon Manager escanea /addons/
    ↓
Module Loader carga módulos activos
    ↓
Cada módulo ejecuta su init()
    ↓
Sistema listo para usar
```

### Estructura de Archivos

```
flavor-chat-ia/
├── includes/
│   ├── modules/              ← 45+ módulos nativos
│   │   ├── eventos/
│   │   ├── socios/
│   │   ├── marketplace/
│   │   └── ...
│   ├── class-module-loader.php
│   └── interface-chat-module.php
├── addons/                   ← Extensiones avanzadas
│   ├── flavor-web-builder-pro/
│   ├── flavor-network-communities/
│   └── ...
└── docs/
```

---

## Módulos Nativos Disponibles

### Categoría: Gestión Comunitaria

| Módulo | ID | Descripción |
|--------|----|--------------|
| Socios | `socios` | Gestión de membresías y socios |
| Eventos | `eventos` | Calendario y gestión de eventos |
| Grupos de Consumo | `grupos_consumo` | Grupos de compra colectiva |
| Banco de Tiempo | `banco_tiempo` | Intercambio de servicios |
| Colectivos | `colectivos` | Gestión de colectivos y asociaciones |
| Comunidades | `comunidades` | Comunidades de vecinos |
| Participación | `participacion` | Procesos participativos |
| Presupuestos Participativos | `presupuestos_participativos` | Votación de presupuestos |

### Categoría: Comercio

| Módulo | ID | Descripción |
|--------|----|--------------|
| WooCommerce | `woocommerce` | Integración con WooCommerce |
| Marketplace | `marketplace` | Mercado entre usuarios |
| Facturas | `facturas` | Generación de facturas |
| Publicidad | `advertising` | Sistema de anuncios |
| Clientes | `clientes` | Gestión de clientes |

### Categoría: Movilidad

| Módulo | ID | Descripción |
|--------|----|--------------|
| Carpooling | `carpooling` | Compartir coche |
| Bicicletas Compartidas | `bicicletas_compartidas` | Préstamo de bicis |
| Parkings | `parkings` | Gestión de aparcamientos |

### Categoría: Espacios y Recursos

| Módulo | ID | Descripción |
|--------|----|--------------|
| Espacios Comunes | `espacios_comunes` | Reserva de espacios |
| Huertos Urbanos | `huertos_urbanos` | Gestión de huertos |
| Biblioteca | `biblioteca` | Préstamo de libros/recursos |
| Reservas | `reservas` | Sistema genérico de reservas |

### Categoría: Comunicación

| Módulo | ID | Descripción |
|--------|----|--------------|
| Chat Interno | `chat_interno` | Mensajería privada |
| Chat de Grupos | `chat_grupos` | Salas de chat grupales |
| Foros | `foros` | Foros de discusión |
| Red Social | `red_social` | Funcionalidades sociales |
| Avisos Municipales | `avisos_municipales` | Notificaciones oficiales |

### Categoría: Formación

| Módulo | ID | Descripción |
|--------|----|--------------|
| Cursos | `cursos` | Plataforma de cursos |
| Talleres | `talleres` | Gestión de talleres |

### Categoría: Multimedia

| Módulo | ID | Descripción |
|--------|----|--------------|
| Multimedia | `multimedia` | Gestión de medios |
| Podcast | `podcast` | Publicación de podcasts |
| Radio | `radio` | Radio online |

### Categoría: Administración

| Módulo | ID | Descripción |
|--------|----|--------------|
| Trámites | `tramites` | Gestión de trámites |
| Transparencia | `transparencia` | Portal de transparencia |
| Incidencias | `incidencias` | Reportes de incidencias |
| Fichaje Empleados | `fichaje_empleados` | Control horario |

### Categoría: Sostenibilidad

| Módulo | ID | Descripción |
|--------|----|--------------|
| Reciclaje | `reciclaje` | Gestión de reciclaje |
| Compostaje | `compostaje` | Compostaje comunitario |

### Categoría: Hostelería

| Módulo | ID | Descripción |
|--------|----|--------------|
| Bares | `bares` | Gestión de locales |
| Empresarial | `empresarial` | Herramientas para empresas |

### Categoría: Otros

| Módulo | ID | Descripción |
|--------|----|--------------|
| Ayuda Vecinal | `ayuda_vecinal` | Red de ayuda mutua |
| Themacle | `themacle` | Sistema de temas |
| Trading IA | `trading_ia` | Trading automatizado |
| DEX Solana | `dex_solana` | Exchange descentralizado |

---

## Addons (Extensiones)

Los addons son extensiones más complejas con funcionalidades avanzadas.

### Addons Disponibles

| Addon | Descripción | Requisitos |
|-------|-------------|------------|
| **Web Builder Pro** | Constructor visual drag & drop | Core 3.0.0+ |
| **Network Communities** | Red de sitios conectados | Core 3.0.0+, Multisite |
| **Advertising Pro** | Sistema avanzado de publicidad | Core 3.0.0+ |
| **Admin Assistant** | Asistente IA para administración | Core 3.0.0+ |
| **Restaurant Ordering** | Sistema de pedidos para restaurantes | Core 3.0.0+, WooCommerce |

### Instalar un Addon

1. Subir la carpeta del addon a `/wp-content/plugins/flavor-chat-ia/addons/`
2. Ir a **Flavor Platform → Addons**
3. Activar el addon deseado

---

## Cómo Activar Módulos

### Desde el Panel de Administración

1. Ve a **Flavor Platform → Configuración**
2. Selecciona la pestaña **Módulos**
3. Marca los módulos que deseas activar
4. Guarda los cambios

### Mediante Código (Programáticamente)

```php
// Activar un módulo
$settings = get_option('flavor_chat_ia_settings', []);
$active_modules = $settings['active_modules'] ?? [];

if (!in_array('eventos', $active_modules)) {
    $active_modules[] = 'eventos';
    $settings['active_modules'] = $active_modules;
    update_option('flavor_chat_ia_settings', $settings);
}
```

### Verificar si un Módulo está Activo

```php
// Método 1: Usando el loader
$loader = Flavor_Chat_Module_Loader::get_instance();
if ($loader->is_module_active('eventos')) {
    // El módulo eventos está activo
}

// Método 2: Verificar la clase
if (class_exists('Flavor_Chat_Eventos_Module')) {
    // El módulo está cargado
}
```

---

## Crear un Módulo Personalizado

### Paso 1: Crear la Estructura de Carpetas

```
includes/modules/mi-modulo/
├── class-mi-modulo-module.php    (obligatorio)
├── class-mi-modulo-api.php       (opcional - para REST API)
├── install.php                    (opcional - crear tablas)
├── frontend/                      (opcional - vistas frontend)
├── views/                         (opcional - templates admin)
├── assets/                        (opcional - CSS/JS)
│   ├── css/
│   └── js/
└── README.md                      (opcional - documentación)
```

### Paso 2: Crear la Clase Principal

```php
<?php
/**
 * Módulo: Mi Módulo Personalizado
 *
 * @package Flavor_Chat_IA
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Mi_Modulo_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'mi_modulo';
        $this->name = __('Mi Módulo', 'flavor-chat-ia');
        $this->description = __('Descripción de mi módulo personalizado.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * Verificar si el módulo puede activarse
     *
     * @return bool
     */
    public function can_activate() {
        // Verificar dependencias
        // Ejemplo: requiere WooCommerce
        // return class_exists('WooCommerce');

        return true;
    }

    /**
     * Obtener mensaje de error si no puede activarse
     *
     * @return string
     */
    public function get_activation_error() {
        // Ejemplo con dependencia
        // if (!class_exists('WooCommerce')) {
        //     return __('Requiere WooCommerce instalado.', 'flavor-chat-ia');
        // }

        return '';
    }

    /**
     * Inicialización del módulo
     */
    public function init() {
        // Registrar custom post types
        add_action('init', [$this, 'registrar_post_types']);

        // Menú de administración
        add_action('admin_menu', [$this, 'registrar_menu_admin']);

        // Cargar assets
        add_action('wp_enqueue_scripts', [$this, 'cargar_assets_frontend']);
        add_action('admin_enqueue_scripts', [$this, 'cargar_assets_admin']);

        // Shortcodes
        add_shortcode('mi_modulo', [$this, 'shortcode_mi_modulo']);

        // API REST
        add_action('rest_api_init', [$this, 'registrar_rutas_api']);

        // Crear tablas si es necesario
        $this->verificar_instalacion();
    }

    /**
     * Registrar Custom Post Types
     */
    public function registrar_post_types() {
        register_post_type('mi_cpt', [
            'labels' => [
                'name' => __('Mis Items', 'flavor-chat-ia'),
                'singular_name' => __('Mi Item', 'flavor-chat-ia'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-star-filled',
        ]);
    }

    /**
     * Obtener configuración por defecto
     *
     * @return array
     */
    protected function get_default_settings() {
        return [
            'opcion_1' => true,
            'opcion_2' => 'valor_defecto',
            'limite_items' => 10,
        ];
    }

    /**
     * Obtener acciones disponibles para el Chat IA
     *
     * @return array
     */
    public function get_actions() {
        return [
            'listar_items' => [
                'description' => 'Lista los items del módulo',
                'parameters' => [
                    'limite' => [
                        'type' => 'integer',
                        'description' => 'Número máximo de items',
                        'default' => 10,
                    ],
                ],
            ],
            'crear_item' => [
                'description' => 'Crea un nuevo item',
                'parameters' => [
                    'titulo' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                    'contenido' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ];
    }

    /**
     * Ejecutar una acción
     *
     * @param string $nombre_accion Nombre de la acción
     * @param array $parametros Parámetros de la acción
     * @return mixed
     */
    public function execute_action($nombre_accion, $parametros) {
        switch ($nombre_accion) {
            case 'listar_items':
                return $this->listar_items($parametros['limite'] ?? 10);

            case 'crear_item':
                return $this->crear_item($parametros);

            default:
                return new WP_Error('accion_invalida', 'Acción no reconocida');
        }
    }

    /**
     * Definiciones de herramientas para Claude
     *
     * @return array
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'mi_modulo_listar',
                'description' => 'Lista items del módulo personalizado',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de items a devolver',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Base de conocimiento para el Chat IA
     *
     * @return array
     */
    public function get_knowledge_base() {
        return [
            'contexto' => 'Mi Módulo permite gestionar items personalizados.',
            'funcionalidades' => [
                'Crear items',
                'Listar items',
                'Editar items',
                'Eliminar items',
            ],
        ];
    }

    /**
     * Preguntas frecuentes
     *
     * @return array
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo creo un nuevo item?',
                'respuesta' => 'Puedes crear items desde el panel de administración o usando el shortcode.',
            ],
        ];
    }

    // ========================================
    // Métodos privados del módulo
    // ========================================

    private function listar_items($limite) {
        $items = get_posts([
            'post_type' => 'mi_cpt',
            'posts_per_page' => $limite,
            'post_status' => 'publish',
        ]);

        return array_map(function($item) {
            return [
                'id' => $item->ID,
                'titulo' => $item->post_title,
                'contenido' => $item->post_content,
            ];
        }, $items);
    }

    private function crear_item($parametros) {
        $resultado = wp_insert_post([
            'post_type' => 'mi_cpt',
            'post_title' => sanitize_text_field($parametros['titulo']),
            'post_content' => wp_kses_post($parametros['contenido'] ?? ''),
            'post_status' => 'publish',
        ]);

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return [
            'success' => true,
            'item_id' => $resultado,
            'message' => 'Item creado correctamente',
        ];
    }

    private function verificar_instalacion() {
        $version_instalada = get_option('mi_modulo_db_version', '0');

        if (version_compare($version_instalada, '1.0.0', '<')) {
            $this->crear_tablas();
            update_option('mi_modulo_db_version', '1.0.0');
        }
    }

    private function crear_tablas() {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_mi_modulo';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$tabla} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            datos longtext,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
```

### Paso 3: Registrar el Módulo

El módulo se descubre automáticamente si:
1. Está en `/includes/modules/mi-modulo/`
2. El archivo principal se llama `class-mi-modulo-module.php`
3. La clase extiende `Flavor_Chat_Module_Base`

---

## Sistema de Dependencias

### Tipos de Dependencias

```php
// En el método can_activate()
public function can_activate() {
    // Plugin de WordPress
    if (!class_exists('WooCommerce')) {
        return false;
    }

    // Versión de PHP
    if (version_compare(PHP_VERSION, '7.4', '<')) {
        return false;
    }

    // Extensión PHP
    if (!extension_loaded('curl')) {
        return false;
    }

    // Otro módulo de Flavor
    $loader = Flavor_Chat_Module_Loader::get_instance();
    if (!$loader->is_module_active('marketplace')) {
        return false;
    }

    return true;
}
```

### Mensajes de Error Descriptivos

```php
public function get_activation_error() {
    $errores = [];

    if (!class_exists('WooCommerce')) {
        $errores[] = __('Requiere WooCommerce instalado y activo.', 'flavor-chat-ia');
    }

    if (version_compare(PHP_VERSION, '7.4', '<')) {
        $errores[] = sprintf(
            __('Requiere PHP 7.4 o superior. Versión actual: %s', 'flavor-chat-ia'),
            PHP_VERSION
        );
    }

    return implode(' ', $errores);
}
```

---

## API y Hooks Disponibles

### Hooks de Acción

```php
// Cuando un módulo se activa
do_action('flavor_module_activated', $module_id);

// Cuando un módulo se desactiva
do_action('flavor_module_deactivated', $module_id);

// Antes de cargar los módulos
do_action('flavor_before_load_modules');

// Después de cargar los módulos
do_action('flavor_after_load_modules', $loaded_modules);
```

### Filtros

```php
// Filtrar lista de módulos activos
$active_modules = apply_filters('flavor_active_modules', $active_modules);

// Filtrar configuración de un módulo
$settings = apply_filters("flavor_module_settings_{$module_id}", $settings);

// Filtrar acciones disponibles de un módulo
$actions = apply_filters("flavor_module_actions_{$module_id}", $actions);
```

### Ejemplo de Uso

```php
// Ejecutar código cuando se activa el módulo eventos
add_action('flavor_module_activated', function($module_id) {
    if ($module_id === 'eventos') {
        // Crear página de eventos automáticamente
        wp_insert_post([
            'post_title' => 'Eventos',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[flavor_eventos]',
        ]);
    }
});
```

---

## Configuración de Módulos

### Acceder a la Configuración

```php
// Dentro de tu módulo
$valor = $this->get_setting('mi_opcion', 'valor_defecto');

// Actualizar configuración
$this->update_setting('mi_opcion', 'nuevo_valor');
```

### Crear Página de Configuración

```php
public function registrar_menu_admin() {
    add_submenu_page(
        'flavor-platform',
        __('Mi Módulo', 'flavor-chat-ia'),
        __('Mi Módulo', 'flavor-chat-ia'),
        'manage_options',
        'flavor-mi-modulo',
        [$this, 'render_pagina_admin']
    );
}

public function render_pagina_admin() {
    // Guardar configuración
    if (isset($_POST['guardar_config']) && wp_verify_nonce($_POST['_wpnonce'], 'mi_modulo_config')) {
        $this->update_setting('opcion_1', sanitize_text_field($_POST['opcion_1']));
        echo '<div class="notice notice-success"><p>Configuración guardada.</p></div>';
    }

    // Obtener valores actuales
    $opcion_1 = $this->get_setting('opcion_1', '');

    ?>
    <div class="wrap">
        <h1><?php _e('Configuración de Mi Módulo', 'flavor-chat-ia'); ?></h1>

        <form method="post">
            <?php wp_nonce_field('mi_modulo_config'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="opcion_1"><?php _e('Opción 1', 'flavor-chat-ia'); ?></label></th>
                    <td>
                        <input type="text" name="opcion_1" id="opcion_1"
                               value="<?php echo esc_attr($opcion_1); ?>" class="regular-text">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="guardar_config" class="button button-primary">
                    <?php _e('Guardar Cambios', 'flavor-chat-ia'); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}
```

---

## Preguntas Frecuentes

### ¿Puedo crear módulos sin modificar el plugin principal?

**Sí**, puedes crear un plugin separado que registre módulos adicionales:

```php
<?php
/**
 * Plugin Name: Mi Extensión para Flavor
 */

add_action('plugins_loaded', function() {
    if (!class_exists('Flavor_Chat_Module_Base')) {
        return;
    }

    require_once __DIR__ . '/class-mi-modulo-module.php';

    // Registrar el módulo
    add_filter('flavor_chat_ia_modules', function($modules) {
        $modules['mi_modulo'] = new Flavor_Chat_Mi_Modulo_Module();
        return $modules;
    });
}, 20);
```

### ¿Cómo desactivo un módulo programáticamente?

```php
$settings = get_option('flavor_chat_ia_settings', []);
$active_modules = $settings['active_modules'] ?? [];

$active_modules = array_diff($active_modules, ['modulo_a_desactivar']);
$settings['active_modules'] = array_values($active_modules);

update_option('flavor_chat_ia_settings', $settings);
```

### ¿Cómo añado traducciones a mi módulo?

```php
// En el init() del módulo
load_textdomain(
    'mi-modulo-textdomain',
    plugin_dir_path(__FILE__) . 'languages/mi-modulo-' . get_locale() . '.mo'
);
```

### ¿Cómo integro mi módulo con el Chat IA?

Implementa los métodos `get_actions()`, `execute_action()`, `get_tool_definitions()` y `get_knowledge_base()` en tu clase de módulo. El Chat IA los detectará automáticamente.

### ¿Dónde almaceno datos personalizados?

- **Datos simples**: Usa `wp_options` con prefijo `flavor_mi_modulo_`
- **Datos por usuario**: Usa `user_meta` con prefijo `flavor_`
- **Datos estructurados**: Crea una tabla personalizada con prefijo `wp_flavor_`

---

## Soporte

Si necesitas ayuda adicional:

- **Documentación completa**: `/docs/` en el plugin
- **Soporte**: support@gailu.net
- **GitHub**: [Reportar issues](https://github.com/gailu-labs/flavor-chat-ia)

---

*Documentación actualizada: Febrero 2026 - Flavor Chat IA v3.1.0*
