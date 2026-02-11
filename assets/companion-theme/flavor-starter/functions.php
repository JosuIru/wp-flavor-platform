<?php
/**
 * Flavor Starter Theme Functions
 *
 * Tema minimalista que delega al plugin Flavor Chat IA
 *
 * @package Flavor_Starter
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Constantes del tema
 */
define('FLAVOR_STARTER_VERSION', '1.0.0');
define('FLAVOR_STARTER_PATH', get_template_directory());
define('FLAVOR_STARTER_URL', get_template_directory_uri());

/**
 * Configuración inicial del tema
 */
function flavor_starter_setup() {
    // Soporte para título del documento
    add_theme_support('title-tag');

    // Soporte para imágenes destacadas
    add_theme_support('post-thumbnails');

    // Soporte para logo personalizado
    add_theme_support('custom-logo', [
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    // Soporte para HTML5
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);

    // Soporte para alineación completa de bloques
    add_theme_support('align-wide');

    // Soporte para estilos del editor
    add_theme_support('editor-styles');

    // Registrar menús de navegación
    register_nav_menus([
        'primary'   => __('Menú Principal', 'flavor-starter'),
        'footer'    => __('Menú Footer', 'flavor-starter'),
        'mobile'    => __('Menú Móvil', 'flavor-starter'),
    ]);

    // Soporte para traducciones
    load_theme_textdomain('flavor-starter', FLAVOR_STARTER_PATH . '/languages');
}
add_action('after_setup_theme', 'flavor_starter_setup');

/**
 * Registrar y encolar scripts y estilos
 */
function flavor_starter_scripts() {
    // Estilos del tema
    wp_enqueue_style(
        'flavor-starter-style',
        get_stylesheet_uri(),
        [],
        FLAVOR_STARTER_VERSION
    );

    // Tailwind CSS (CDN para desarrollo, en producción usar build)
    wp_enqueue_script(
        'tailwindcss',
        'https://cdn.tailwindcss.com',
        [],
        '3.4',
        false
    );

    // Configuración de Tailwind
    wp_add_inline_script('tailwindcss', "
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        }
                    }
                }
            }
        }
    ");

    // Google Fonts - Inter
    wp_enqueue_style(
        'google-fonts-inter',
        'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap',
        [],
        null
    );

    // Scripts del tema
    if (file_exists(FLAVOR_STARTER_PATH . '/assets/js/theme.js')) {
        wp_enqueue_script(
            'flavor-starter-scripts',
            FLAVOR_STARTER_URL . '/assets/js/theme.js',
            [],
            FLAVOR_STARTER_VERSION,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'flavor_starter_scripts');

/**
 * Verificar si el plugin Flavor Chat IA está activo
 */
function flavor_starter_is_plugin_active() {
    return defined('FLAVOR_CHAT_IA_VERSION');
}

/**
 * Renderizar el header usando el plugin o fallback
 */
function flavor_starter_header() {
    if (flavor_starter_is_plugin_active() && has_action('flavor_header')) {
        // Usar el sistema de layouts del plugin
        do_action('flavor_header');
    } else {
        // Fallback: header básico
        flavor_starter_header_fallback();
    }
}

/**
 * Renderizar el footer usando el plugin o fallback
 */
function flavor_starter_footer() {
    if (flavor_starter_is_plugin_active() && has_action('flavor_footer')) {
        // Usar el sistema de layouts del plugin
        do_action('flavor_footer');
    } else {
        // Fallback: footer básico
        flavor_starter_footer_fallback();
    }
}

/**
 * Header fallback cuando el plugin no está activo
 */
function flavor_starter_header_fallback() {
    ?>
    <header class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <?php if (has_custom_logo()): ?>
                        <?php the_custom_logo(); ?>
                    <?php else: ?>
                        <a href="<?php echo esc_url(home_url('/')); ?>" class="text-xl font-bold text-gray-900 hover:text-blue-600 transition-colors">
                            <?php bloginfo('name'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Navegación Desktop -->
                <nav class="hidden md:flex items-center space-x-1">
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'flex items-center space-x-1',
                        'fallback_cb'    => 'flavor_starter_fallback_menu',
                        'items_wrap'     => '<ul class="%2$s">%3$s</ul>',
                        'depth'          => 2,
                        'link_before'    => '<span class="block px-4 py-2 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-colors">',
                        'link_after'     => '</span>',
                    ]);
                    ?>
                </nav>

                <!-- Botón móvil -->
                <button id="mobile-menu-toggle" class="md:hidden p-2 rounded-lg text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors" aria-label="Abrir menú">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Menú móvil -->
        <div id="mobile-menu" class="hidden md:hidden border-t border-gray-100 bg-white">
            <div class="px-4 py-4 space-y-1">
                <?php
                wp_nav_menu([
                    'theme_location' => 'mobile',
                    'container'      => false,
                    'menu_class'     => 'space-y-1',
                    'fallback_cb'    => function() {
                        wp_nav_menu([
                            'theme_location' => 'primary',
                            'container'      => false,
                            'menu_class'     => 'space-y-1',
                        ]);
                    },
                    'link_before'    => '<span class="block px-4 py-3 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-colors">',
                    'link_after'     => '</span>',
                ]);
                ?>
            </div>
        </div>
    </header>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggle = document.getElementById('mobile-menu-toggle');
        var menu = document.getElementById('mobile-menu');
        if (toggle && menu) {
            toggle.addEventListener('click', function() {
                menu.classList.toggle('hidden');
                var expanded = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', !expanded);
            });
        }
    });
    </script>
    <?php
}

/**
 * Menú fallback si no hay menú asignado
 */
function flavor_starter_fallback_menu() {
    ?>
    <ul class="flex items-center space-x-1">
        <li>
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <span class="block px-4 py-2 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <?php esc_html_e('Inicio', 'flavor-starter'); ?>
                </span>
            </a>
        </li>
        <?php if (get_option('page_for_posts')): ?>
        <li>
            <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>">
                <span class="block px-4 py-2 text-gray-600 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-colors">
                    <?php esc_html_e('Blog', 'flavor-starter'); ?>
                </span>
            </a>
        </li>
        <?php endif; ?>
    </ul>
    <?php
}

/**
 * Footer fallback cuando el plugin no está activo
 */
function flavor_starter_footer_fallback() {
    ?>
    <footer class="bg-gray-900 text-white mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Logo y descripción -->
                <div class="md:col-span-2">
                    <h3 class="text-xl font-bold mb-4"><?php bloginfo('name'); ?></h3>
                    <p class="text-gray-400 mb-4 max-w-md"><?php bloginfo('description'); ?></p>
                </div>

                <!-- Enlaces rápidos -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4">
                        <?php esc_html_e('Enlaces', 'flavor-starter'); ?>
                    </h4>
                    <?php
                    wp_nav_menu([
                        'theme_location' => 'footer',
                        'container'      => false,
                        'menu_class'     => 'space-y-3',
                        'fallback_cb'    => false,
                        'link_before'    => '<span class="text-gray-400 hover:text-white transition-colors">',
                        'link_after'     => '</span>',
                    ]);
                    ?>
                </div>

                <!-- Contacto -->
                <div>
                    <h4 class="text-sm font-semibold text-gray-300 uppercase tracking-wider mb-4">
                        <?php esc_html_e('Contacto', 'flavor-starter'); ?>
                    </h4>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <?php echo esc_html(get_option('admin_email')); ?>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Copyright -->
            <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php esc_html_e('Todos los derechos reservados.', 'flavor-starter'); ?>
                </p>
                <p class="text-gray-500 text-sm">
                    <?php esc_html_e('Desarrollado con', 'flavor-starter'); ?>
                    <span class="text-red-500">&#10084;</span>
                    <?php esc_html_e('y Flavor Chat IA', 'flavor-starter'); ?>
                </p>
            </div>
        </div>
    </footer>
    <?php
}

/**
 * Añadir clases al body
 */
function flavor_starter_body_classes($classes) {
    // Añadir clase si el plugin está activo
    if (flavor_starter_is_plugin_active()) {
        $classes[] = 'flavor-plugin-active';
    }

    // Añadir clase para páginas con landing
    if (is_page()) {
        $post = get_post();
        if ($post && has_shortcode($post->post_content, 'flavor_landing')) {
            $classes[] = 'has-flavor-landing';
        }
    }

    return $classes;
}
add_filter('body_class', 'flavor_starter_body_classes');

/**
 * Registrar sidebar
 */
function flavor_starter_widgets_init() {
    register_sidebar([
        'name'          => __('Sidebar Principal', 'flavor-starter'),
        'id'            => 'sidebar-1',
        'description'   => __('Añade widgets aquí.', 'flavor-starter'),
        'before_widget' => '<section id="%1$s" class="widget %2$s mb-6 p-6 bg-white rounded-xl shadow-sm">',
        'after_widget'  => '</section>',
        'before_title'  => '<h3 class="widget-title text-lg font-semibold mb-4 text-gray-900">',
        'after_title'   => '</h3>',
    ]);

    register_sidebar([
        'name'          => __('Footer Widgets', 'flavor-starter'),
        'id'            => 'footer-widgets',
        'description'   => __('Añade widgets para el footer.', 'flavor-starter'),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h4 class="widget-title text-sm font-semibold uppercase tracking-wider mb-4 text-gray-300">',
        'after_title'   => '</h4>',
    ]);
}
add_action('widgets_init', 'flavor_starter_widgets_init');

/**
 * Personalizar excerpt
 */
function flavor_starter_excerpt_length($length) {
    return 25;
}
add_filter('excerpt_length', 'flavor_starter_excerpt_length');

function flavor_starter_excerpt_more($more) {
    return '...';
}
add_filter('excerpt_more', 'flavor_starter_excerpt_more');

/**
 * Deshabilitar emojis de WordPress para mejor rendimiento
 */
function flavor_starter_disable_emojis() {
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'flavor_starter_disable_emojis');

/**
 * Añadir soporte para editor de bloques
 */
function flavor_starter_block_editor_setup() {
    // Paleta de colores personalizada
    add_theme_support('editor-color-palette', [
        [
            'name'  => __('Primario', 'flavor-starter'),
            'slug'  => 'primary',
            'color' => '#3b82f6',
        ],
        [
            'name'  => __('Secundario', 'flavor-starter'),
            'slug'  => 'secondary',
            'color' => '#64748b',
        ],
        [
            'name'  => __('Éxito', 'flavor-starter'),
            'slug'  => 'success',
            'color' => '#22c55e',
        ],
        [
            'name'  => __('Advertencia', 'flavor-starter'),
            'slug'  => 'warning',
            'color' => '#f59e0b',
        ],
        [
            'name'  => __('Peligro', 'flavor-starter'),
            'slug'  => 'danger',
            'color' => '#ef4444',
        ],
        [
            'name'  => __('Oscuro', 'flavor-starter'),
            'slug'  => 'dark',
            'color' => '#1e293b',
        ],
        [
            'name'  => __('Claro', 'flavor-starter'),
            'slug'  => 'light',
            'color' => '#f8fafc',
        ],
    ]);

    // Tamaños de fuente
    add_theme_support('editor-font-sizes', [
        [
            'name' => __('Pequeño', 'flavor-starter'),
            'slug' => 'small',
            'size' => 14,
        ],
        [
            'name' => __('Normal', 'flavor-starter'),
            'slug' => 'normal',
            'size' => 16,
        ],
        [
            'name' => __('Grande', 'flavor-starter'),
            'slug' => 'large',
            'size' => 20,
        ],
        [
            'name' => __('Extra Grande', 'flavor-starter'),
            'slug' => 'extra-large',
            'size' => 24,
        ],
    ]);
}
add_action('after_setup_theme', 'flavor_starter_block_editor_setup');

/**
 * Mensaje de activación si el plugin no está instalado
 */
function flavor_starter_admin_notice() {
    if (!flavor_starter_is_plugin_active()) {
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php esc_html_e('Flavor Starter Theme', 'flavor-starter'); ?></strong>:
                <?php esc_html_e('Para aprovechar todas las funcionalidades, instala y activa el plugin Flavor Chat IA.', 'flavor-starter'); ?>
            </p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'flavor_starter_admin_notice');
