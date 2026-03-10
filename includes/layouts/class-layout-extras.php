<?php
/**
 * Layout Extras - Variantes y Funcionalidades Adicionales
 *
 * Menús y footers adicionales, componentes extra como
 * announcement bar, breadcrumbs, dark mode, etc.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Layout_Extras {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Registrar layouts extra
        add_action('flavor_register_layouts', [$this, 'register_extra_layouts']);

        // Hooks para componentes extra
        add_action('wp_head', [$this, 'output_dark_mode_styles'], 1);
        add_action('wp_body_open', [$this, 'render_announcement_bar'], 5);
        add_action('wp_footer', [$this, 'render_back_to_top'], 20);
        add_action('wp_footer', [$this, 'render_sticky_cta'], 25);
        add_action('wp_footer', [$this, 'render_cookie_banner'], 30);

        // Breadcrumbs
        add_action('flavor_before_content', [$this, 'render_breadcrumbs']);

        // Scripts y estilos
        add_action('wp_enqueue_scripts', [$this, 'enqueue_extras_assets']);

        // AJAX
        add_action('wp_ajax_flavor_dismiss_announcement', [$this, 'ajax_dismiss_announcement']);
        add_action('wp_ajax_nopriv_flavor_dismiss_announcement', [$this, 'ajax_dismiss_announcement']);
        add_action('wp_ajax_flavor_accept_cookies', [$this, 'ajax_accept_cookies']);
        add_action('wp_ajax_nopriv_flavor_accept_cookies', [$this, 'ajax_accept_cookies']);
    }

    /**
     * Registrar layouts extra en el registry
     */
    public function register_extra_layouts($registry) {
        // ====== MENÚS EXTRA ======

        // 7. Split Menu - Logo centrado con nav dividida
        $registry->register_menu('split', [
            'name' => __('Split Menu', 'flavor-chat-ia'),
            'description' => __('Logo centrado con navegación dividida a ambos lados', 'flavor-chat-ia'),
            'icon' => 'dashicons-editor-justify',
            'preview_image' => 'menu-split.svg',
            'recommended_for' => ['restaurante', 'coworking', 'comunidad'],
            'supports' => ['logo', 'split_menu', 'social_icons', 'search'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'items_left' => 3,
                'items_right' => 3,
                'sticky' => true,
                'transparent_on_hero' => true,
            ],
        ]);

        // 8. Transparent Hero - Se vuelve sólido al scroll
        $registry->register_menu('transparent-hero', [
            'name' => __('Transparent Hero', 'flavor-chat-ia'),
            'description' => __('Transparente sobre hero, sólido al hacer scroll', 'flavor-chat-ia'),
            'icon' => 'dashicons-visibility',
            'preview_image' => 'menu-transparent.svg',
            'recommended_for' => ['restaurante', 'coworking', 'tienda'],
            'supports' => ['logo', 'menu', 'cta_button', 'dark_mode_toggle'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'initial_bg' => 'transparent',
                'scrolled_bg' => '#ffffff',
                'scroll_threshold' => 100,
                'text_color_initial' => '#ffffff',
                'text_color_scrolled' => '#1f2937',
                'sticky' => true,
            ],
        ]);

        // 9. Tab Navigation - Pestañas horizontales
        $registry->register_menu('tabs', [
            'name' => __('Tab Navigation', 'flavor-chat-ia'),
            'description' => __('Navegación por pestañas estilo aplicación', 'flavor-chat-ia'),
            'icon' => 'dashicons-category',
            'preview_image' => 'menu-tabs.svg',
            'recommended_for' => ['marketplace', 'comunidad', 'ayuntamiento'],
            'supports' => ['logo', 'tabs', 'search', 'user_menu'],
            'mobile_behavior' => 'scrollable-tabs',
            'settings' => [
                'tab_style' => 'underline', // underline, pill, boxed
                'show_icons' => true,
                'sticky' => true,
            ],
        ]);

        // 10. Double Header - Dos barras de navegación
        $registry->register_menu('double-header', [
            'name' => __('Double Header', 'flavor-chat-ia'),
            'description' => __('Barra superior con info + barra principal con navegación', 'flavor-chat-ia'),
            'icon' => 'dashicons-editor-insertmore',
            'preview_image' => 'menu-double.svg',
            'recommended_for' => ['tienda', 'marketplace', 'ayuntamiento'],
            'supports' => ['topbar', 'logo', 'menu', 'search', 'cart', 'user_menu'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'topbar_content' => ['phone', 'email', 'social', 'language'],
                'topbar_bg' => '#1f2937',
                'main_sticky' => true,
            ],
        ]);

        // 11. Search Focused - Barra de búsqueda prominente
        $registry->register_menu('search-focused', [
            'name' => __('Search Focused', 'flavor-chat-ia'),
            'description' => __('Menú con barra de búsqueda central prominente', 'flavor-chat-ia'),
            'icon' => 'dashicons-search',
            'preview_image' => 'menu-search.svg',
            'recommended_for' => ['marketplace', 'tienda', 'biblioteca'],
            'supports' => ['logo', 'search_bar', 'categories_dropdown', 'cart', 'user_menu'],
            'mobile_behavior' => 'hamburger',
            'settings' => [
                'search_placeholder' => __('Buscar productos, servicios...', 'flavor-chat-ia'),
                'show_categories' => true,
                'sticky' => true,
            ],
        ]);

        // ====== FOOTERS EXTRA ======

        // 6. Wave Footer - Con forma decorativa de ola
        $registry->register_footer('wave', [
            'name' => __('Wave Footer', 'flavor-chat-ia'),
            'description' => __('Footer con decoración de ola SVG en la parte superior', 'flavor-chat-ia'),
            'icon' => 'dashicons-chart-area',
            'preview_image' => 'footer-wave.svg',
            'recommended_for' => ['comunidad', 'grupo-consumo', 'barrio'],
            'supports' => ['wave_decoration', 'logo', 'columns', 'social_icons', 'copyright'],
            'settings' => [
                'wave_color' => 'primary',
                'wave_height' => '80px',
                'columns' => 3,
                'background_style' => 'dark',
            ],
        ]);

        // 7. Minimal Dark - Minimalista oscuro
        $registry->register_footer('minimal-dark', [
            'name' => __('Minimal Dark', 'flavor-chat-ia'),
            'description' => __('Footer minimalista con fondo oscuro elegante', 'flavor-chat-ia'),
            'icon' => 'dashicons-editor-contract',
            'preview_image' => 'footer-minimal-dark.svg',
            'recommended_for' => ['coworking', 'restaurante', 'tienda'],
            'supports' => ['logo', 'tagline', 'social_icons', 'copyright'],
            'settings' => [
                'layout' => 'centered',
                'background_style' => 'gradient-dark',
            ],
        ]);

        // 8. Big Footer - Footer extenso con mucha info
        $registry->register_footer('big', [
            'name' => __('Big Footer', 'flavor-chat-ia'),
            'description' => __('Footer grande con múltiples secciones de información', 'flavor-chat-ia'),
            'icon' => 'dashicons-editor-expand',
            'preview_image' => 'footer-big.svg',
            'recommended_for' => ['ayuntamiento', 'marketplace', 'tienda'],
            'supports' => ['logo', 'about', 'columns', 'contact', 'map_mini', 'newsletter', 'social_icons', 'partners', 'copyright'],
            'settings' => [
                'columns' => 5,
                'show_partners' => true,
                'show_certifications' => true,
                'background_style' => 'dark',
            ],
        ]);

        // 9. CTA Footer - Con llamada a la acción prominente
        $registry->register_footer('cta', [
            'name' => __('CTA Footer', 'flavor-chat-ia'),
            'description' => __('Footer con sección de llamada a la acción destacada', 'flavor-chat-ia'),
            'icon' => 'dashicons-megaphone',
            'preview_image' => 'footer-cta.svg',
            'recommended_for' => ['tienda', 'comunidad', 'coworking'],
            'supports' => ['cta_section', 'logo', 'columns', 'social_icons', 'copyright'],
            'settings' => [
                'cta_title' => __('¿Listo para empezar?', 'flavor-chat-ia'),
                'cta_description' => __('Únete a nuestra comunidad hoy mismo', 'flavor-chat-ia'),
                'cta_button_text' => __('Comenzar ahora', 'flavor-chat-ia'),
                'cta_button_url' => '#',
                'cta_bg' => 'gradient',
            ],
        ]);

        // 10. Social Footer - Centrado en redes sociales
        $registry->register_footer('social', [
            'name' => __('Social Footer', 'flavor-chat-ia'),
            'description' => __('Footer minimalista centrado en redes sociales', 'flavor-chat-ia'),
            'icon' => 'dashicons-share',
            'preview_image' => 'footer-social.svg',
            'recommended_for' => ['comunidad', 'barrio', 'grupo-consumo'],
            'supports' => ['large_social_icons', 'tagline', 'copyright'],
            'settings' => [
                'icon_size' => 'large',
                'layout' => 'centered',
                'background_style' => 'transparent',
            ],
        ]);
    }

    /**
     * Encolar assets de extras
     */
    public function enqueue_extras_assets() {
        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-layout-extras',
            FLAVOR_CHAT_IA_URL . "assets/css/layouts/layout-extras{$sufijo_asset}.css",
            ['flavor-layouts'],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-layout-extras',
            FLAVOR_CHAT_IA_URL . "assets/js/layout-extras{$sufijo_asset}.js",
            ['jquery', 'flavor-layouts'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-layout-extras', 'flavorExtrasConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_extras_nonce'),
            'darkMode' => $this->get_dark_mode_settings(),
            'backToTop' => $this->get_back_to_top_settings(),
        ]);
    }

    /**
     * ====== DARK MODE ======
     */

    /**
     * Obtener configuración de dark mode
     */
    private function get_dark_mode_settings() {
        $settings = get_option('flavor_layout_settings', []);
        return [
            'enabled' => $settings['dark_mode_enabled'] ?? false,
            'auto' => $settings['dark_mode_auto'] ?? true,
            'toggle_position' => $settings['dark_mode_toggle_position'] ?? 'header',
        ];
    }

    /**
     * Estilos CSS de dark mode
     */
    public function output_dark_mode_styles() {
        $settings = get_option('flavor_layout_settings', []);
        if (empty($settings['dark_mode_enabled'])) return;

        $design_settings = get_option('flavor_design_settings', []);
        ?>
        <style id="flavor-dark-mode-vars">
            :root {
                --flavor-dark-bg: <?php echo esc_attr($settings['dark_bg_color'] ?? '#0f172a'); ?>;
                --flavor-dark-surface: <?php echo esc_attr($settings['dark_surface_color'] ?? '#1e293b'); ?>;
                --flavor-dark-text: <?php echo esc_attr($settings['dark_text_color'] ?? '#f1f5f9'); ?>;
                --flavor-dark-text-secondary: <?php echo esc_attr($settings['dark_text_secondary'] ?? '#94a3b8'); ?>;
                --flavor-dark-border: <?php echo esc_attr($settings['dark_border_color'] ?? '#334155'); ?>;
            }

            [data-theme="dark"] {
                --flavor-bg: var(--flavor-dark-bg);
                --flavor-text: var(--flavor-dark-text);
                --flavor-text-light: var(--flavor-dark-text-secondary);
                --flavor-border: var(--flavor-dark-border);
                --flavor-bg-alt: var(--flavor-dark-surface);
                color-scheme: dark;
            }

            [data-theme="dark"] body {
                background-color: var(--flavor-dark-bg);
                color: var(--flavor-dark-text);
            }

            [data-theme="dark"] .flavor-header,
            [data-theme="dark"] .flavor-footer {
                background-color: var(--flavor-dark-surface);
            }

            [data-theme="dark"] .flavor-logo__image {
                filter: brightness(0) invert(1);
            }
        </style>
        <?php
    }

    /**
     * ====== ANNOUNCEMENT BAR ======
     */

    /**
     * Renderizar barra de anuncios
     */
    public function render_announcement_bar() {
        $settings = get_option('flavor_layout_settings', []);

        if (empty($settings['announcement_enabled'])) return;
        if (isset($_COOKIE['flavor_announcement_dismissed'])) return;

        $message = $settings['announcement_message'] ?? '';
        $link = $settings['announcement_link'] ?? '';
        $link_text = $settings['announcement_link_text'] ?? __('Saber más', 'flavor-chat-ia');
        $bg_color = $settings['announcement_bg'] ?? 'var(--flavor-primary)';
        $dismissible = $settings['announcement_dismissible'] ?? true;

        if (empty($message)) return;
        ?>
        <div class="flavor-announcement-bar" style="background: <?php echo esc_attr($bg_color); ?>;" data-dismissible="<?php echo $dismissible ? 'true' : 'false'; ?>">
            <div class="flavor-container">
                <div class="flavor-announcement-bar__content">
                    <span class="flavor-announcement-bar__message"><?php echo wp_kses_post($message); ?></span>
                    <?php if (!empty($link)): ?>
                    <a href="<?php echo esc_url($link); ?>" class="flavor-announcement-bar__link">
                        <?php echo esc_html($link_text); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                    <?php endif; ?>
                </div>
                <?php if ($dismissible): ?>
                <button class="flavor-announcement-bar__close" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Descartar announcement
     */
    public function ajax_dismiss_announcement() {
        setcookie('flavor_announcement_dismissed', '1', time() + DAY_IN_SECONDS * 7, '/');
        wp_send_json_success();
    }

    /**
     * ====== BREADCRUMBS ======
     */

    /**
     * Renderizar breadcrumbs
     */
    public function render_breadcrumbs() {
        $settings = get_option('flavor_layout_settings', []);

        if (empty($settings['breadcrumbs_enabled'])) return;
        if (is_front_page()) return;

        $separator = $settings['breadcrumbs_separator'] ?? '›';
        ?>
        <nav class="flavor-breadcrumbs" aria-label="<?php esc_attr_e('Breadcrumb', 'flavor-chat-ia'); ?>">
            <div class="flavor-container">
                <ol class="flavor-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
                    <li class="flavor-breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a href="<?php echo esc_url(home_url('/')); ?>" itemprop="item">
                            <span itemprop="name"><?php esc_html_e('Inicio', 'flavor-chat-ia'); ?></span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>

                    <?php
                    $position = 2;

                    if (is_category() || is_single()) {
                        $categories = get_the_category();
                        if ($categories) {
                            $category = $categories[0];
                            echo '<li class="flavor-breadcrumbs__separator">' . esc_html($separator) . '</li>';
                            echo '<li class="flavor-breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                            echo '<a href="' . esc_url(get_category_link($category->term_id)) . '" itemprop="item">';
                            echo '<span itemprop="name">' . esc_html($category->name) . '</span>';
                            echo '</a>';
                            echo '<meta itemprop="position" content="' . $position . '" />';
                            echo '</li>';
                            $position++;
                        }
                    }

                    if (is_single() || is_page()) {
                        echo '<li class="flavor-breadcrumbs__separator">' . esc_html($separator) . '</li>';
                        echo '<li class="flavor-breadcrumbs__item flavor-breadcrumbs__item--current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                        echo '<span itemprop="name">' . esc_html(get_the_title()) . '</span>';
                        echo '<meta itemprop="position" content="' . $position . '" />';
                        echo '</li>';
                    } elseif (is_archive()) {
                        echo '<li class="flavor-breadcrumbs__separator">' . esc_html($separator) . '</li>';
                        echo '<li class="flavor-breadcrumbs__item flavor-breadcrumbs__item--current">';
                        echo '<span>' . esc_html(get_the_archive_title()) . '</span>';
                        echo '</li>';
                    } elseif (is_search()) {
                        echo '<li class="flavor-breadcrumbs__separator">' . esc_html($separator) . '</li>';
                        echo '<li class="flavor-breadcrumbs__item flavor-breadcrumbs__item--current">';
                        echo '<span>' . sprintf(__('Resultados: %s', 'flavor-chat-ia'), get_search_query()) . '</span>';
                        echo '</li>';
                    }
                    ?>
                </ol>
            </div>
        </nav>
        <?php
    }

    /**
     * ====== BACK TO TOP ======
     */

    /**
     * Obtener configuración de back to top
     */
    private function get_back_to_top_settings() {
        $settings = get_option('flavor_layout_settings', []);
        return [
            'enabled' => $settings['back_to_top_enabled'] ?? true,
            'show_after' => $settings['back_to_top_show_after'] ?? 300,
            'position' => $settings['back_to_top_position'] ?? 'right',
        ];
    }

    /**
     * Renderizar botón back to top
     */
    public function render_back_to_top() {
        $settings = get_option('flavor_layout_settings', []);

        if (empty($settings['back_to_top_enabled']) && !isset($settings['back_to_top_enabled'])) {
            // Enabled by default
        } elseif (empty($settings['back_to_top_enabled'])) {
            return;
        }

        $position = $settings['back_to_top_position'] ?? 'right';
        ?>
        <button class="flavor-back-to-top flavor-back-to-top--<?php echo esc_attr($position); ?>" aria-label="<?php esc_attr_e('Volver arriba', 'flavor-chat-ia'); ?>" style="display: none;">
            <span class="dashicons dashicons-arrow-up-alt2"></span>
        </button>
        <?php
    }

    /**
     * ====== STICKY CTA ======
     */

    /**
     * Renderizar CTA flotante
     */
    public function render_sticky_cta() {
        $settings = get_option('flavor_layout_settings', []);

        if (empty($settings['sticky_cta_enabled'])) return;

        $text = $settings['sticky_cta_text'] ?? '';
        $url = $settings['sticky_cta_url'] ?? '';
        $icon = $settings['sticky_cta_icon'] ?? 'whatsapp';
        $position = $settings['sticky_cta_position'] ?? 'bottom-right';
        $show_on_mobile = $settings['sticky_cta_mobile'] ?? true;

        if (empty($text) && empty($url)) return;

        $icon_map = [
            'whatsapp' => 'dashicons-whatsapp',
            'phone' => 'dashicons-phone',
            'email' => 'dashicons-email',
            'chat' => 'dashicons-format-chat',
            'calendar' => 'dashicons-calendar-alt',
        ];
        $icon_class = $icon_map[$icon] ?? 'dashicons-admin-comments';
        ?>
        <a href="<?php echo esc_url($url); ?>" class="flavor-sticky-cta flavor-sticky-cta--<?php echo esc_attr($position); ?> <?php echo $show_on_mobile ? '' : 'flavor-sticky-cta--hide-mobile'; ?>" target="_blank" rel="noopener">
            <span class="flavor-sticky-cta__icon dashicons <?php echo esc_attr($icon_class); ?>"></span>
            <?php if (!empty($text)): ?>
            <span class="flavor-sticky-cta__text"><?php echo esc_html($text); ?></span>
            <?php endif; ?>
        </a>
        <?php
    }

    /**
     * ====== COOKIE BANNER ======
     */

    /**
     * Renderizar banner de cookies
     */
    public function render_cookie_banner() {
        $settings = get_option('flavor_layout_settings', []);

        if (empty($settings['cookie_banner_enabled'])) return;
        if (isset($_COOKIE['flavor_cookies_accepted'])) return;

        $message = $settings['cookie_banner_message'] ?? __('Utilizamos cookies para mejorar tu experiencia. Al continuar navegando, aceptas nuestra política de cookies.', 'flavor-chat-ia');
        $privacy_url = get_privacy_policy_url() ?: $settings['cookie_privacy_url'] ?? '#';
        $position = $settings['cookie_banner_position'] ?? 'bottom';
        ?>
        <div class="flavor-cookie-banner flavor-cookie-banner--<?php echo esc_attr($position); ?>" role="dialog" aria-label="<?php esc_attr_e('Aviso de cookies', 'flavor-chat-ia'); ?>">
            <div class="flavor-container">
                <div class="flavor-cookie-banner__content">
                    <p class="flavor-cookie-banner__message">
                        <?php echo wp_kses_post($message); ?>
                        <a href="<?php echo esc_url($privacy_url); ?>" class="flavor-cookie-banner__link">
                            <?php esc_html_e('Más información', 'flavor-chat-ia'); ?>
                        </a>
                    </p>
                    <div class="flavor-cookie-banner__actions">
                        <button class="flavor-button flavor-button--secondary flavor-cookie-banner__decline">
                            <?php esc_html_e('Rechazar', 'flavor-chat-ia'); ?>
                        </button>
                        <button class="flavor-button flavor-button--primary flavor-cookie-banner__accept">
                            <?php esc_html_e('Aceptar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Aceptar cookies
     */
    public function ajax_accept_cookies() {
        $accept = isset($_POST['accept']) ? (bool) $_POST['accept'] : true;
        $value = $accept ? 'all' : 'essential';
        setcookie('flavor_cookies_accepted', $value, time() + YEAR_IN_SECONDS, '/');
        wp_send_json_success(['accepted' => $value]);
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Layout_Extras::get_instance();
});
