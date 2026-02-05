# Cómo Crear un Addon para Flavor Platform

## Estructura Básica de un Addon

Un addon es un plugin de WordPress independiente que se conecta a Flavor Platform. Aquí está la estructura mínima:

```
flavor-addon-ejemplo/
├── flavor-addon-ejemplo.php          # Archivo principal
├── includes/
│   └── class-ejemplo-main.php        # Lógica principal
├── admin/
│   └── class-ejemplo-admin.php       # Panel admin
├── assets/
│   ├── css/
│   └── js/
└── readme.txt
```

## Ejemplo Completo: Addon "Hello World"

### 1. Archivo Principal (`flavor-addon-ejemplo.php`)

```php
<?php
/**
 * Plugin Name: Flavor Addon - Ejemplo
 * Plugin URI: https://gailu.net/addons/ejemplo
 * Description: Addon de ejemplo que muestra cómo crear extensiones para Flavor Platform
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tunombre.com
 * License: GPL v2 or later
 * Requires Plugins: flavor-chat-ia
 * Text Domain: flavor-addon-ejemplo
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Constantes
define('FLAVOR_ADDON_EJEMPLO_VERSION', '1.0.0');
define('FLAVOR_ADDON_EJEMPLO_PATH', plugin_dir_path(__FILE__));
define('FLAVOR_ADDON_EJEMPLO_URL', plugin_dir_url(__FILE__));

/**
 * Registrar el addon con Flavor Platform
 *
 * IMPORTANTE: Este hook se dispara DESPUÉS de que Flavor Platform se haya cargado
 */
add_action('flavor_register_addons', 'flavor_addon_ejemplo_register');

function flavor_addon_ejemplo_register() {
    // Verificar que Flavor Platform esté disponible
    if (!class_exists('Flavor_Addon_Manager')) {
        return;
    }

    // Registrar el addon
    Flavor_Addon_Manager::register_addon('addon-ejemplo', [
        // CAMPOS REQUERIDOS
        'name' => __('Addon de Ejemplo', 'flavor-addon-ejemplo'),
        'version' => FLAVOR_ADDON_EJEMPLO_VERSION,

        // CAMPOS OPCIONALES
        'description' => __('Un addon de ejemplo que muestra cómo extender Flavor Platform.', 'flavor-addon-ejemplo'),
        'author' => 'Tu Nombre',
        'author_uri' => 'https://tunombre.com',
        'icon' => 'dashicons-star-filled',  // Dashicon para el admin
        'file' => __FILE__,

        // REQUISITOS
        'requires_core' => '3.0.0',  // Versión mínima de Flavor Platform
        'requires' => [
            'required' => [
                // Requiere PHP 7.4+
                'php' => '7.4',
                // Requiere WordPress 5.8+
                'wordpress' => '5.8',
            ],
            'optional' => [
                // WooCommerce es opcional (añade funcionalidad extra)
                'plugin:woocommerce' => [
                    'name' => 'WooCommerce',
                    'version' => '5.0',
                    'feature' => 'Integración con tienda online'
                ],
            ]
        ],

        // CALLBACK DE INICIALIZACIÓN
        // Esta función se llama cuando el addon se carga
        'init_callback' => 'flavor_addon_ejemplo_init',

        // CONFIGURACIÓN
        'settings_page' => 'admin.php?page=flavor-addon-ejemplo',
        'documentation_url' => 'https://gailu.net/docs/addons/ejemplo',
        'is_premium' => false,
    ]);
}

/**
 * Función de inicialización del addon
 * Se llama cuando el addon está activo y se carga
 */
function flavor_addon_ejemplo_init() {
    // Cargar clases del addon
    require_once FLAVOR_ADDON_EJEMPLO_PATH . 'includes/class-ejemplo-main.php';

    if (is_admin()) {
        require_once FLAVOR_ADDON_EJEMPLO_PATH . 'admin/class-ejemplo-admin.php';
    }

    // Inicializar
    Flavor_Addon_Ejemplo_Main::get_instance();

    if (is_admin()) {
        Flavor_Addon_Ejemplo_Admin::get_instance();
    }
}
```

### 2. Clase Principal (`includes/class-ejemplo-main.php`)

```php
<?php
/**
 * Clase principal del addon de ejemplo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Addon_Ejemplo_Main {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Agregar un shortcode de ejemplo
        add_shortcode('flavor_ejemplo', [$this, 'render_shortcode']);

        // Hook después de cargar el addon
        add_action('flavor_addon_loaded', [$this, 'on_addon_loaded'], 10, 2);
    }

    /**
     * Callback cuando el addon se carga
     */
    public function on_addon_loaded($slug, $config) {
        if ($slug === 'addon-ejemplo') {
            // Tu lógica aquí
            error_log('Addon de ejemplo cargado correctamente!');
        }
    }

    /**
     * Renderiza el shortcode [flavor_ejemplo]
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'mensaje' => 'Hola desde el addon de ejemplo!',
        ], $atts);

        return '<div class="flavor-addon-ejemplo">' . esc_html($atts['mensaje']) . '</div>';
    }
}
```

### 3. Panel Admin (`admin/class-ejemplo-admin.php`)

```php
<?php
/**
 * Panel de administración del addon de ejemplo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Addon_Ejemplo_Admin {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu_page']);
    }

    public function add_menu_page() {
        add_submenu_page(
            'flavor-chat-ia',  // Parent: Flavor Platform
            __('Addon Ejemplo', 'flavor-addon-ejemplo'),
            __('Addon Ejemplo', 'flavor-addon-ejemplo'),
            'manage_options',
            'flavor-addon-ejemplo',
            [$this, 'render_admin_page']
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Addon de Ejemplo', 'flavor-addon-ejemplo'); ?></h1>
            <p><?php echo esc_html__('¡Bienvenido al addon de ejemplo!', 'flavor-addon-ejemplo'); ?></p>

            <div class="card">
                <h2><?php echo esc_html__('Información del Addon', 'flavor-addon-ejemplo'); ?></h2>
                <p>
                    <strong><?php echo esc_html__('Versión:', 'flavor-addon-ejemplo'); ?></strong>
                    <?php echo esc_html(FLAVOR_ADDON_EJEMPLO_VERSION); ?>
                </p>
                <p>
                    <strong><?php echo esc_html__('Estado:', 'flavor-addon-ejemplo'); ?></strong>
                    <span style="color: green;">✓ <?php echo esc_html__('Activo', 'flavor-addon-ejemplo'); ?></span>
                </p>
            </div>

            <div class="card">
                <h2><?php echo esc_html__('Uso del Shortcode', 'flavor-addon-ejemplo'); ?></h2>
                <p><?php echo esc_html__('Usa este shortcode en cualquier página:', 'flavor-addon-ejemplo'); ?></p>
                <code>[flavor_ejemplo mensaje="Tu mensaje personalizado"]</code>
            </div>
        </div>
        <?php
    }
}
```

## Hooks Disponibles para Addons

### Hooks de Registro

```php
// Cuando se registra el addon (antes de activarlo)
add_action('flavor_addon_registered', function($slug, $config) {
    // Tu código
}, 10, 2);
```

### Hooks de Activación

```php
// Cuando se activa el addon
add_action('flavor_addon_activated', function($slug) {
    // Tu código de activación
}, 10, 1);

// Cuando se carga el addon (en cada request si está activo)
add_action('flavor_addon_loaded', function($slug, $config) {
    // Tu código de inicialización
}, 10, 2);
```

### Hooks de Desactivación

```php
// Cuando se desactiva el addon
add_action('flavor_addon_deactivated', function($slug) {
    // Tu código de limpieza
}, 10, 1);
```

## Verificar si un Addon está Activo

```php
// Desde cualquier parte de tu código
if (Flavor_Addon_Manager::is_addon_active('addon-ejemplo')) {
    // El addon está activo
}

// Obtener información del addon
$info = Flavor_Addon_Manager::get_addon_info('addon-ejemplo');
if ($info) {
    echo 'Versión: ' . $info['version'];
}
```

## Dependencias entre Addons

Si tu addon depende de otro addon:

```php
Flavor_Addon_Manager::register_addon('mi-addon', [
    'name' => 'Mi Addon',
    'version' => '1.0.0',
    'requires' => [
        'required' => [
            // Requiere el addon de Web Builder
            'addon:web-builder-pro' => [
                'name' => 'Web Builder Pro',
                'version' => '1.0.0'
            ],
        ]
    ],
    'init_callback' => 'mi_addon_init',
]);
```

## Mejores Prácticas

1. **Prefijos únicos**: Usa un prefijo único para todas tus funciones y clases (ej: `mi_addon_`)

2. **Verificar dependencias**: Siempre verifica que Flavor Platform esté disponible antes de registrar

3. **Documentación**: Proporciona documentación clara de qué hace tu addon

4. **Logging**: Usa `flavor_chat_ia_log()` para debugging en modo desarrollo

5. **Internacionalización**: Prepara tu addon para traducción con `__()` y `_e()`

6. **Limpieza**: Limpia datos temporales cuando el addon se desactiva

7. **Seguridad**: Sanitiza entradas y verifica permisos (`current_user_can()`)

## Distribución

Una vez creado tu addon:

1. Comprime la carpeta del addon en un `.zip`
2. Los usuarios pueden instalarlo como cualquier plugin de WordPress
3. Una vez instalado, aparecerá automáticamente en la página de Addons de Flavor Platform
4. Los usuarios pueden activarlo/desactivarlo desde ahí

## Recursos Adicionales

- [Documentación de WordPress Plugin Development](https://developer.wordpress.org/plugins/)
- [Flavor Platform Hooks Reference](https://gailu.net/docs/hooks)
- [Ejemplos de Addons](https://gailu.net/addons)
