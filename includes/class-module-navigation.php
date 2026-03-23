<?php
/**
 * Sistema de Navegación Contextual para Módulos
 *
 * Proporciona navegación consistente dentro de cada módulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Navigation {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_shortcode('flavor_module_nav', [$this, 'render_module_nav']);
        add_shortcode('flavor_page_header', [$this, 'render_page_header']);
    }

    /**
     * Renderiza navegación de módulo
     *
     * [flavor_module_nav module="eventos" current="crear"]
     */
    public function render_module_nav($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'current' => '',
            'items' => '', // Opcional: override de items
        ], $atts);

        if (empty($atts['module'])) {
            return '';
        }

        $module_id = sanitize_key($atts['module']);
        $current = sanitize_key($atts['current']);

        // Obtener items de navegación
        $nav_items = $this->get_module_nav_items($module_id, $atts['items']);

        if (empty($nav_items)) {
            return '';
        }

        ob_start();
        ?>
        <nav class="flavor-module-nav" aria-label="<?php esc_attr_e('Navegación del módulo', 'flavor-chat-ia'); ?>">
            <ul class="flavor-module-nav__list">
                <?php foreach ($nav_items as $item) : ?>
                    <li class="flavor-module-nav__item">
                        <a href="<?php echo esc_url($item['url']); ?>"
                           class="flavor-module-nav__link <?php echo ($current === $item['slug']) ? 'flavor-module-nav__link--active' : ''; ?>">
                            <?php if (!empty($item['icon'])) : ?>
                                <span class="flavor-module-nav__icon"><?php echo $item['icon']; ?></span>
                            <?php endif; ?>
                            <span class="flavor-module-nav__text"><?php echo esc_html($item['label']); ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <style>
        .flavor-module-nav {
            margin-bottom: 32px;
            border-bottom: 2px solid #e5e7eb;
        }
        .flavor-module-nav__list {
            display: flex;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: wrap;
        }
        .flavor-module-nav__item {
            margin: 0;
        }
        .flavor-module-nav__link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            color: #6b7280;
            text-decoration: none;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            transition: all 0.2s ease;
        }
        .flavor-module-nav__link:hover {
            color: var(--flavor-primary, #3b82f6);
            background: #f9fafb;
        }
        .flavor-module-nav__link--active {
            color: var(--flavor-primary, #3b82f6);
            border-bottom-color: var(--flavor-primary, #3b82f6);
            background: #eff6ff;
        }
        .flavor-module-nav__icon {
            font-size: 18px;
        }
        @media (max-width: 640px) {
            .flavor-module-nav__list {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .flavor-module-nav__link {
                white-space: nowrap;
                padding: 10px 16px;
                font-size: 14px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene items de navegación para un módulo
     */
    private function get_module_nav_items($module_id, $override = '') {
        // Si hay override manual, usarlo
        if (!empty($override)) {
            $items_str = explode(',', $override);
            $items = [];
            foreach ($items_str as $item_str) {
                $item_str = trim($item_str);
                $items[] = [
                    'slug' => $item_str,
                    'label' => ucfirst(str_replace('-', ' ', $item_str)),
                    'url' => home_url("/{$module_id}/{$item_str}/"),
                    'icon' => '',
                ];
            }
            return $items;
        }

        // Items por defecto según módulo
        $default_items = [
            'eventos' => [
                ['slug' => 'listado', 'label' => __('Todos los Eventos', 'flavor-chat-ia'), 'url' => home_url('/eventos/'), 'icon' => '📅'],
                ['slug' => 'mis-eventos', 'label' => __('Mis Eventos', 'flavor-chat-ia'), 'url' => home_url('/eventos/mis-eventos/'), 'icon' => '⭐'],
                ['slug' => 'crear', 'label' => __('Crear Evento', 'flavor-chat-ia'), 'url' => home_url('/eventos/crear/'), 'icon' => '➕'],
            ],
            'talleres' => [
                ['slug' => 'listado', 'label' => __('Todos los Talleres', 'flavor-chat-ia'), 'url' => home_url('/talleres/'), 'icon' => '🎨'],
                ['slug' => 'mis-talleres', 'label' => __('Mis Talleres', 'flavor-chat-ia'), 'url' => home_url('/talleres/mis-talleres/'), 'icon' => '⭐'],
                ['slug' => 'crear', 'label' => __('Proponer Taller', 'flavor-chat-ia'), 'url' => home_url('/talleres/crear/'), 'icon' => '➕'],
            ],
            'grupos-consumo' => [
                ['slug' => 'listado', 'label' => __('Todos los Grupos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('grupos_consumo', ''), 'icon' => '🌱'],
                ['slug' => 'mi-grupo', 'label' => __('Mi Grupo', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-grupo'), 'icon' => '⭐'],
                ['slug' => 'pedidos', 'label' => __('Pedidos', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'pedidos'), 'icon' => '🛒'],
            ],
            'incidencias' => [
                ['slug' => 'listado', 'label' => __('Todas las Incidencias', 'flavor-chat-ia'), 'url' => home_url('/incidencias/'), 'icon' => '🔧'],
                ['slug' => 'mis-incidencias', 'label' => __('Mis Incidencias', 'flavor-chat-ia'), 'url' => home_url('/incidencias/mis-incidencias/'), 'icon' => '⭐'],
                ['slug' => 'crear', 'label' => __('Reportar Incidencia', 'flavor-chat-ia'), 'url' => home_url('/incidencias/crear/'), 'icon' => '➕'],
            ],
            'espacios-comunes' => [
                ['slug' => 'listado', 'label' => __('Espacios Disponibles', 'flavor-chat-ia'), 'url' => home_url('/espacios-comunes/'), 'icon' => '🏛️'],
                ['slug' => 'mis-reservas', 'label' => __('Mis Reservas', 'flavor-chat-ia'), 'url' => home_url('/espacios-comunes/mis-reservas/'), 'icon' => '⭐'],
                ['slug' => 'reservar', 'label' => __('Reservar Espacio', 'flavor-chat-ia'), 'url' => home_url('/espacios-comunes/reservar/'), 'icon' => '➕'],
            ],
            'mi-red' => [
                ['slug' => 'feed', 'label' => __('Feed', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('mi_red', ''), 'icon' => '🏠'],
                ['slug' => 'explorar', 'label' => __('Explorar', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('mi_red', 'explorar'), 'icon' => '🔍'],
                ['slug' => 'mensajes', 'label' => __('Mensajes', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('mi_red', 'mensajes'), 'icon' => '💬'],
                ['slug' => 'notificaciones', 'label' => __('Notificaciones', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('mi_red', 'notificaciones'), 'icon' => '🔔'],
                ['slug' => 'perfil', 'label' => __('Mi Perfil', 'flavor-chat-ia'), 'url' => Flavor_Chat_Helpers::get_action_url('mi_red', 'perfil'), 'icon' => '👤'],
            ],
        ];

        return $default_items[$module_id] ?? [];
    }

    /**
     * Renderiza header de página con breadcrumbs y navegación
     *
     * [flavor_page_header
     *     title="Mi Portal"
     *     subtitle="Tu centro de control"
     *     breadcrumbs="yes"
     *     background="gradient"
     *     module="eventos"
     *     current="crear"]
     */
    public function render_page_header($atts) {
        $atts = shortcode_atts([
            'title' => '',
            'subtitle' => '',
            'breadcrumbs' => 'yes',
            'background' => 'white', // white, gradient, primary
            'module' => '', // Si está dentro de un módulo
            'current' => '', // Página actual dentro del módulo
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-page-header flavor-page-header--<?php echo esc_attr($atts['background']); ?>">
            <?php if ($atts['breadcrumbs'] === 'yes' && class_exists('Flavor_Breadcrumbs')) : ?>
                <div class="flavor-page-header__breadcrumbs">
                    <?php echo Flavor_Breadcrumbs::render(); ?>
                </div>
            <?php endif; ?>

            <div class="flavor-page-header__content">
                <?php if (!empty($atts['title'])) : ?>
                    <h1 class="flavor-page-header__title"><?php echo esc_html($atts['title']); ?></h1>
                <?php endif; ?>

                <?php if (!empty($atts['subtitle'])) : ?>
                    <p class="flavor-page-header__subtitle"><?php echo esc_html($atts['subtitle']); ?></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($atts['module'])) : ?>
                <div class="flavor-page-header__nav">
                    <?php echo $this->render_module_nav([
                        'module' => $atts['module'],
                        'current' => $atts['current']
                    ]); ?>
                </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-page-header {
            margin-bottom: 32px;
            padding: 32px 24px;
            border-radius: 12px;
        }
        .flavor-page-header--white {
            background: white;
            border: 1px solid #e5e7eb;
        }
        .flavor-page-header--gradient {
            background: linear-gradient(135deg, var(--flavor-primary, #3b82f6) 0%, var(--flavor-secondary, #8b5cf6) 100%);
            color: white;
        }
        .flavor-page-header--gradient .flavor-page-header__title,
        .flavor-page-header--gradient .flavor-page-header__subtitle {
            color: white;
        }
        .flavor-page-header--primary {
            background: var(--flavor-primary, #3b82f6);
            color: white;
        }
        .flavor-page-header__breadcrumbs {
            margin-bottom: 20px;
        }
        .flavor-page-header__content {
            text-align: center;
        }
        .flavor-page-header__title {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 8px;
            color: #111827;
        }
        .flavor-page-header__subtitle {
            font-size: 18px;
            color: #6b7280;
            margin: 0;
        }
        .flavor-page-header__nav {
            margin-top: 24px;
        }
        /* Cuando hay módulo nav, ajustar el breadcrumbs al borde del nav */
        .flavor-page-header__nav .flavor-module-nav {
            margin-bottom: 0;
        }
        @media (max-width: 768px) {
            .flavor-page-header {
                padding: 24px 16px;
            }
            .flavor-page-header__title {
                font-size: 28px;
            }
            .flavor-page-header__subtitle {
                font-size: 16px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

// Inicializar
Flavor_Module_Navigation::get_instance();
