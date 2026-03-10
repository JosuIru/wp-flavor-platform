<?php
/**
 * Layout Renderer - Renderizador de Menús y Footers
 *
 * Genera el HTML de los layouts predefinidos.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Layout_Renderer {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Registry de layouts
     */
    private $registry;

    /**
     * Obtiene la URL actual para redirects de login en layouts dinámicos.
     */
    private function get_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }

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
     * Flag para evitar renderizar header/footer dos veces
     */
    private $header_rendered = false;
    private $footer_rendered = false;

    /**
     * Constructor
     */
    private function __construct() {
        $this->registry = flavor_layout_registry();

        // Hooks para reemplazar header/footer del tema (flavor-starter y otros)
        add_action('flavor_header', [$this, 'render_header']);
        add_action('flavor_footer', [$this, 'render_footer']);

        // Fallback: si el tema no usa flavor_header/flavor_footer,
        // intentar inyectar el layout en hooks estándar de WordPress
        add_action('wp_body_open', [$this, 'maybe_render_header_fallback'], 5);
        add_action('wp_footer', [$this, 'maybe_render_footer_fallback'], 5);

        // Shortcodes
        add_shortcode('flavor_menu', [$this, 'shortcode_menu']);
        add_shortcode('flavor_footer', [$this, 'shortcode_footer']);
    }

    /**
     * Renderiza el header como fallback si el tema no usa flavor_header
     * Solo se renderiza si está habilitado el modo fallback en settings
     */
    public function maybe_render_header_fallback() {
        // Si ya se renderizó via flavor_header, no duplicar
        if ($this->header_rendered) {
            return;
        }

        // Verificar si el fallback está habilitado
        $settings = get_option('flavor_layout_settings', []);
        $use_fallback = !empty($settings['inject_in_any_theme']);

        if ($use_fallback) {
            $this->render_header();
        }
    }

    /**
     * Renderiza el footer como fallback si el tema no usa flavor_footer
     * Solo se renderiza si está habilitado el modo fallback en settings
     */
    public function maybe_render_footer_fallback() {
        // Si ya se renderizó via flavor_footer, no duplicar
        if ($this->footer_rendered) {
            return;
        }

        // Verificar si el fallback está habilitado
        $settings = get_option('flavor_layout_settings', []);
        $use_fallback = !empty($settings['inject_in_any_theme']);

        if ($use_fallback) {
            $this->render_footer();
        }
    }

    /**
     * Renderizar header/menú
     */
    public function render_header() {
        // Marcar como renderizado para evitar duplicados
        $this->header_rendered = true;

        $active_layout = $this->registry->get_active_layout();
        $menu_type = $active_layout['menu'];
        $settings = $this->registry->get_menu_settings($menu_type);

        $template_file = FLAVOR_CHAT_IA_PATH . "templates/layouts/menus/{$menu_type}.php";

        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_menu_fallback($menu_type, $settings);
        }
    }

    /**
     * Renderizar footer
     */
    public function render_footer() {
        // Marcar como renderizado para evitar duplicados
        $this->footer_rendered = true;

        $active_layout = $this->registry->get_active_layout();
        $footer_type = $active_layout['footer'];
        $settings = $this->registry->get_footer_settings($footer_type);

        $template_file = FLAVOR_CHAT_IA_PATH . "templates/layouts/footers/{$footer_type}.php";

        if (file_exists($template_file)) {
            include $template_file;
        } else {
            $this->render_footer_fallback($footer_type, $settings);
        }
    }

    /**
     * Renderizar menú Classic
     */
    public function render_menu_classic($settings = []) {
        $defaults = [
            'logo_position' => 'left',
            'menu_position' => 'center',
            'cta_position' => 'right',
            'sticky' => true,
            'transparent_on_hero' => false,
            'menu_state' => 'expanded',
            'style_normal' => 'light',
            'style_sticky' => 'light',
            'actions_order' => ['search', 'user', 'cta'],
        ];
        $settings = array_merge($defaults, $settings);

        if ($settings['style_normal'] === 'transparent') {
            $settings['transparent_on_hero'] = true;
        }

        $sticky_class = $settings['sticky'] ? 'flavor-menu--sticky' : '';
        $transparent_class = $settings['transparent_on_hero'] ? 'flavor-menu--transparent' : '';
        $style_class = 'flavor-menu--style-' . $settings['style_normal'];
        $sticky_style_class = 'flavor-menu--sticky-style-' . $settings['style_sticky'];
        $state_class = $settings['menu_state'] === 'collapsed' ? 'flavor-menu--collapsed' : '';
        ?>
        <header class="flavor-header flavor-menu flavor-menu--classic <?php echo esc_attr($sticky_class . ' ' . $transparent_class . ' ' . $style_class . ' ' . $sticky_style_class . ' ' . $state_class); ?>" data-menu-state="<?php echo esc_attr($settings['menu_state']); ?>">
            <div class="flavor-container">
                <div class="flavor-menu__inner">
                    <!-- Logo -->
                    <div class="flavor-menu__logo">
                        <?php $this->render_logo(); ?>
                    </div>

                    <!-- Navigation -->
                    <nav class="flavor-menu__nav" role="navigation" aria-label="<?php esc_attr_e('Navegación principal', 'flavor-chat-ia'); ?>">
                        <?php $this->render_navigation(); ?>
                    </nav>

                    <!-- Actions -->
                    <div class="flavor-menu__actions">
                        <?php $this->render_menu_actions($settings, ['search', 'user', 'cta']); ?>
                    </div>

                    <!-- Mobile Toggle -->
                    <button class="flavor-menu__toggle" aria-label="<?php esc_attr_e('Abrir menú', 'flavor-chat-ia'); ?>" aria-expanded="false">
                        <span class="flavor-menu__toggle-bar"></span>
                        <span class="flavor-menu__toggle-bar"></span>
                        <span class="flavor-menu__toggle-bar"></span>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div class="flavor-menu__mobile" aria-hidden="true">
                <nav class="flavor-menu__mobile-nav">
                    <?php $this->render_navigation('mobile'); ?>
                </nav>
            </div>
        </header>
        <?php
    }

    /**
     * Renderizar menú Centered
     */
    public function render_menu_centered($settings = []) {
        $defaults = [
            'logo_position' => 'center',
            'menu_position' => 'center',
            'show_topbar' => true,
            'sticky' => true,
            'transparent_on_hero' => true,
            'menu_state' => 'expanded',
            'style_normal' => 'light',
            'style_sticky' => 'light',
        ];
        $settings = array_merge($defaults, $settings);

        if ($settings['style_normal'] === 'transparent') {
            $settings['transparent_on_hero'] = true;
        }

        $sticky_class = $settings['sticky'] ? 'flavor-menu--sticky' : '';
        $transparent_class = $settings['transparent_on_hero'] ? 'flavor-menu--transparent' : '';
        $style_class = 'flavor-menu--style-' . $settings['style_normal'];
        $sticky_style_class = 'flavor-menu--sticky-style-' . $settings['style_sticky'];
        $state_class = $settings['menu_state'] === 'collapsed' ? 'flavor-menu--collapsed' : '';
        ?>
        <?php if ($settings['show_topbar']): ?>
        <div class="flavor-topbar">
            <div class="flavor-container">
                <div class="flavor-topbar__inner">
                    <div class="flavor-topbar__contact">
                        <?php $this->render_contact_info(); ?>
                    </div>
                    <div class="flavor-topbar__social">
                        <?php $this->render_social_icons(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <header class="flavor-header flavor-menu flavor-menu--centered <?php echo esc_attr($sticky_class . ' ' . $transparent_class . ' ' . $style_class . ' ' . $sticky_style_class . ' ' . $state_class); ?>" data-menu-state="<?php echo esc_attr($settings['menu_state']); ?>">
            <div class="flavor-container">
                <!-- Logo centrado -->
                <div class="flavor-menu__logo-wrapper">
                    <?php $this->render_logo('large'); ?>
                </div>

                <!-- Navigation centrada -->
                <nav class="flavor-menu__nav flavor-menu__nav--centered" role="navigation">
                    <?php $this->render_navigation(); ?>
                </nav>

                <!-- Mobile Toggle -->
                <button class="flavor-menu__toggle" aria-label="<?php esc_attr_e('Abrir menú', 'flavor-chat-ia'); ?>">
                    <span class="flavor-menu__toggle-bar"></span>
                    <span class="flavor-menu__toggle-bar"></span>
                    <span class="flavor-menu__toggle-bar"></span>
                </button>
            </div>
        </header>
        <?php
    }

    /**
     * Renderizar menú Sidebar
     */
    public function render_menu_sidebar($settings = []) {
        $defaults = [
            'sidebar_position' => 'left',
            'overlay_color' => 'rgba(0,0,0,0.5)',
            'sidebar_width' => '280px',
            'show_user_section' => true,
            'sticky' => true,
            'menu_state' => 'expanded',
            'style_normal' => 'light',
            'style_sticky' => 'light',
        ];
        $settings = array_merge($defaults, $settings);
        $style_class = 'flavor-menu--style-' . $settings['style_normal'];
        $sticky_style_class = 'flavor-menu--sticky-style-' . $settings['style_sticky'];
        $state_class = $settings['menu_state'] === 'collapsed' ? 'flavor-menu--collapsed' : '';
        ?>
        <header class="flavor-header flavor-menu flavor-menu--sidebar <?php echo esc_attr(($settings['sticky'] ? 'flavor-menu--sticky' : '') . ' ' . $style_class . ' ' . $sticky_style_class . ' ' . $state_class); ?>" data-menu-state="<?php echo esc_attr($settings['menu_state']); ?>">
            <div class="flavor-container">
                <div class="flavor-menu__inner">
                    <!-- Toggle Sidebar -->
                    <button class="flavor-menu__sidebar-toggle" aria-label="<?php esc_attr_e('Abrir menú', 'flavor-chat-ia'); ?>" data-position="<?php echo esc_attr($settings['sidebar_position']); ?>">
                        <span class="dashicons dashicons-menu"></span>
                    </button>

                    <!-- Logo -->
                    <div class="flavor-menu__logo">
                        <?php $this->render_logo(); ?>
                    </div>

                    <!-- Actions -->
                    <div class="flavor-menu__actions">
                        <?php $this->render_notifications_button(); ?>
                        <?php $this->render_search_button(); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar Drawer -->
        <aside class="flavor-sidebar" data-position="<?php echo esc_attr($settings['sidebar_position']); ?>" style="--sidebar-width: <?php echo esc_attr($settings['sidebar_width']); ?>;" aria-hidden="true">
            <div class="flavor-sidebar__header">
                <button class="flavor-sidebar__close" aria-label="<?php esc_attr_e('Cerrar menú', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>

            <?php if ($settings['show_user_section']): ?>
            <div class="flavor-sidebar__user">
                <?php $this->render_user_section(); ?>
            </div>
            <?php endif; ?>

            <nav class="flavor-sidebar__nav">
                <?php $this->render_navigation('sidebar'); ?>
            </nav>

            <div class="flavor-sidebar__footer">
                <?php $this->render_sidebar_footer(); ?>
            </div>
        </aside>

        <!-- Overlay -->
        <div class="flavor-sidebar__overlay" style="--overlay-color: <?php echo esc_attr($settings['overlay_color']); ?>;"></div>
        <?php
    }

    /**
     * Renderizar Bottom Navigation
     */
    public function render_menu_bottom_nav($settings = []) {
        $defaults = [
            'max_items' => 5,
            'show_labels' => true,
            'icon_style' => 'outlined',
            'active_indicator' => 'dot',
            'hide_on_scroll' => false,
        ];
        $settings = array_merge($defaults, $settings);

        // Obtener items del menú
        $navigation_items = $this->get_bottom_nav_items($settings['max_items']);
        ?>
        <!-- Header mínimo para móvil con bottom nav -->
        <header class="flavor-header flavor-menu flavor-menu--with-bottom-nav">
            <div class="flavor-container">
                <div class="flavor-menu__inner">
                    <div class="flavor-menu__logo">
                        <?php $this->render_logo(); ?>
                    </div>
                    <div class="flavor-menu__actions">
                        <?php $this->render_notifications_button(); ?>
                        <?php $this->render_user_menu(); ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Bottom Navigation -->
        <nav class="flavor-bottom-nav <?php echo $settings['hide_on_scroll'] ? 'flavor-bottom-nav--hide-on-scroll' : ''; ?>" role="navigation" aria-label="<?php esc_attr_e('Navegación principal', 'flavor-chat-ia'); ?>">
            <?php foreach ($navigation_items as $item): ?>
            <a href="<?php echo esc_url($item['url']); ?>" class="flavor-bottom-nav__item <?php echo $item['active'] ? 'is-active' : ''; ?>">
                <span class="flavor-bottom-nav__icon">
                    <span class="dashicons dashicons-<?php echo esc_attr($item['icon']); ?>"></span>
                    <?php if ($item['badge'] > 0): ?>
                    <span class="flavor-bottom-nav__badge"><?php echo intval($item['badge']); ?></span>
                    <?php endif; ?>
                </span>
                <?php if ($settings['show_labels']): ?>
                <span class="flavor-bottom-nav__label"><?php echo esc_html($item['label']); ?></span>
                <?php endif; ?>
                <?php if ($item['active'] && $settings['active_indicator'] === 'dot'): ?>
                <span class="flavor-bottom-nav__indicator"></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    /**
     * Renderizar Mega Menu
     */
    public function render_menu_mega($settings = []) {
        $defaults = [
            'columns_per_dropdown' => 4,
            'show_featured_image' => true,
            'dropdown_animation' => 'fade',
            'sticky' => true,
            'menu_state' => 'expanded',
            'style_normal' => 'light',
            'style_sticky' => 'light',
            'actions_order' => ['search', 'user', 'cta'],
        ];
        $settings = array_merge($defaults, $settings);
        $style_class = 'flavor-menu--style-' . $settings['style_normal'];
        $sticky_style_class = 'flavor-menu--sticky-style-' . $settings['style_sticky'];
        $state_class = $settings['menu_state'] === 'collapsed' ? 'flavor-menu--collapsed' : '';
        ?>
        <header class="flavor-header flavor-menu flavor-menu--mega <?php echo esc_attr(($settings['sticky'] ? 'flavor-menu--sticky' : '') . ' ' . $style_class . ' ' . $sticky_style_class . ' ' . $state_class); ?>" data-menu-state="<?php echo esc_attr($settings['menu_state']); ?>">
            <div class="flavor-container">
                <div class="flavor-menu__inner">
                    <!-- Logo -->
                    <div class="flavor-menu__logo">
                        <?php $this->render_logo(); ?>
                    </div>

                    <!-- Mega Navigation -->
                    <nav class="flavor-menu__nav flavor-mega-nav" role="navigation" data-animation="<?php echo esc_attr($settings['dropdown_animation']); ?>">
                        <?php $this->render_mega_navigation($settings); ?>
                    </nav>

                    <!-- Actions -->
                    <div class="flavor-menu__actions">
                        <?php $this->render_menu_actions($settings, ['search', 'user', 'cta']); ?>
                    </div>

                    <!-- Mobile Toggle -->
                    <button class="flavor-menu__toggle" aria-label="<?php esc_attr_e('Abrir menú', 'flavor-chat-ia'); ?>">
                        <span class="flavor-menu__toggle-bar"></span>
                        <span class="flavor-menu__toggle-bar"></span>
                        <span class="flavor-menu__toggle-bar"></span>
                    </button>
                </div>
            </div>
        </header>
        <?php
    }

    /**
     * Renderizar Menú Minimal
     */
    public function render_menu_minimal($settings = []) {
        $defaults = [
            'hamburger_style' => 'animated',
            'fullscreen_menu' => true,
            'transparent_on_hero' => true,
            'sticky' => false,
            'menu_state' => 'expanded',
            'style_normal' => 'light',
            'style_sticky' => 'light',
        ];
        $settings = array_merge($defaults, $settings);

        if ($settings['style_normal'] === 'transparent') {
            $settings['transparent_on_hero'] = true;
        }

        $classes = [
            'flavor-header',
            'flavor-menu',
            'flavor-menu--minimal',
        ];
        if ($settings['sticky']) $classes[] = 'flavor-menu--sticky';
        if ($settings['transparent_on_hero']) $classes[] = 'flavor-menu--transparent';
        $classes[] = 'flavor-menu--style-' . $settings['style_normal'];
        $classes[] = 'flavor-menu--sticky-style-' . $settings['style_sticky'];
        if ($settings['menu_state'] === 'collapsed') $classes[] = 'flavor-menu--collapsed';
        ?>
        <header class="<?php echo esc_attr(implode(' ', $classes)); ?>" data-menu-state="<?php echo esc_attr($settings['menu_state']); ?>">
            <div class="flavor-container">
                <div class="flavor-menu__inner">
                    <!-- Logo -->
                    <div class="flavor-menu__logo">
                        <?php $this->render_logo(); ?>
                    </div>

                    <!-- Hamburger Toggle -->
                    <button class="flavor-menu__hamburger flavor-menu__hamburger--<?php echo esc_attr($settings['hamburger_style']); ?>" aria-label="<?php esc_attr_e('Abrir menú', 'flavor-chat-ia'); ?>" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Fullscreen Menu -->
        <?php if ($settings['fullscreen_menu']): ?>
        <div class="flavor-fullscreen-menu" aria-hidden="true">
            <div class="flavor-fullscreen-menu__content">
                <nav class="flavor-fullscreen-menu__nav">
                    <?php $this->render_navigation('fullscreen'); ?>
                </nav>
                <div class="flavor-fullscreen-menu__footer">
                    <?php $this->render_social_icons('large'); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }

    /**
     * Renderizar Footer Multi-Column
     */
    public function render_footer_multi_column($settings = []) {
        $defaults = [
            'columns' => 4,
            'show_logo' => true,
            'show_social' => true,
            'show_newsletter' => false,
            'background_style' => 'dark',
        ];
        $settings = array_merge($defaults, $settings);
        ?>
        <footer class="flavor-footer flavor-footer--multi-column flavor-footer--<?php echo esc_attr($settings['background_style']); ?>">
            <div class="flavor-container">
                <div class="flavor-footer__main">
                    <!-- Logo Column -->
                    <?php if ($settings['show_logo']): ?>
                    <div class="flavor-footer__brand">
                        <?php $this->render_logo('footer'); ?>
                        <p class="flavor-footer__tagline"><?php echo esc_html(get_bloginfo('description')); ?></p>
                        <?php if ($settings['show_social']): ?>
                        <div class="flavor-footer__social">
                            <?php $this->render_social_icons(); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Menu Columns -->
                    <div class="flavor-footer__columns" data-columns="<?php echo intval($settings['columns']); ?>">
                        <?php $this->render_footer_columns($settings['columns']); ?>
                    </div>
                </div>

                <?php $this->render_footer_sponsors($settings); ?>

                <!-- Copyright -->
                <div class="flavor-footer__bottom">
                    <div class="flavor-footer__copyright">
                        <?php $this->render_copyright(); ?>
                    </div>
                    <div class="flavor-footer__legal">
                        <?php $this->render_legal_links(); ?>
                    </div>
                </div>
            </div>
        </footer>
        <?php
    }

    /**
     * Renderizar Footer Compact
     */
    public function render_footer_compact($settings = []) {
        $defaults = [
            'layout' => 'inline',
            'show_logo' => false,
            'background_style' => 'transparent',
        ];
        $settings = array_merge($defaults, $settings);
        ?>
        <footer class="flavor-footer flavor-footer--compact flavor-footer--<?php echo esc_attr($settings['background_style']); ?>">
            <div class="flavor-container">
                <div class="flavor-footer__inner">
                    <?php if ($settings['show_logo']): ?>
                    <div class="flavor-footer__logo">
                        <?php $this->render_logo('small'); ?>
                    </div>
                    <?php endif; ?>

                    <div class="flavor-footer__copyright">
                        <?php $this->render_copyright(); ?>
                    </div>

                    <?php $this->render_footer_sponsors($settings); ?>

                    <nav class="flavor-footer__nav">
                        <?php $this->render_legal_links(); ?>
                    </nav>

                    <div class="flavor-footer__social">
                        <?php $this->render_social_icons('small'); ?>
                    </div>
                </div>
            </div>
        </footer>
        <?php
    }

    /**
     * Renderizar Footer Newsletter
     */
    public function render_footer_newsletter($settings = []) {
        $defaults = [
            'newsletter_position' => 'top',
            'show_benefits' => true,
            'background_style' => 'gradient',
        ];
        $settings = array_merge($defaults, $settings);
        ?>
        <footer class="flavor-footer flavor-footer--newsletter flavor-footer--<?php echo esc_attr($settings['background_style']); ?>">
            <!-- Newsletter Section -->
            <div class="flavor-footer__newsletter">
                <div class="flavor-container">
                    <div class="flavor-newsletter">
                        <div class="flavor-newsletter__content">
                            <h3 class="flavor-newsletter__title"><?php esc_html_e('Suscríbete a nuestra newsletter', 'flavor-chat-ia'); ?></h3>
                            <p class="flavor-newsletter__description"><?php esc_html_e('Recibe las últimas novedades y ofertas exclusivas.', 'flavor-chat-ia'); ?></p>
                        </div>
                        <form class="flavor-newsletter__form flavor-newsletter-form" action="" method="post" data-source="footer">
                            <input type="email" name="email" class="flavor-newsletter__input" placeholder="<?php esc_attr_e('Tu email', 'flavor-chat-ia'); ?>" required>
                            <button type="submit" class="flavor-newsletter__submit flavor-button flavor-button--primary">
                                <span class="flavor-button__text"><?php esc_html_e('Suscribirse', 'flavor-chat-ia'); ?></span>
                                <span class="flavor-button__loading" style="display:none;">
                                    <span class="spinner"></span>
                                </span>
                            </button>
                            <div class="flavor-form-message" style="display:none;"></div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Footer -->
            <div class="flavor-container">
                <div class="flavor-footer__main">
                    <div class="flavor-footer__brand">
                        <?php $this->render_logo('footer'); ?>
                        <div class="flavor-footer__social">
                            <?php $this->render_social_icons(); ?>
                        </div>
                    </div>

                    <div class="flavor-footer__columns" data-columns="3">
                        <?php $this->render_footer_columns(3); ?>
                    </div>
                </div>

                <div class="flavor-footer__bottom">
                    <?php $this->render_footer_sponsors($settings); ?>
                    <?php $this->render_copyright(); ?>
                </div>
            </div>
        </footer>
        <?php
    }

    /**
     * Renderizar Footer Contact
     */
    public function render_footer_contact($settings = []) {
        $defaults = [
            'show_map' => true,
            'map_height' => '200px',
            'contact_layout' => 'cards',
            'background_style' => 'light',
        ];
        $settings = array_merge($defaults, $settings);
        ?>
        <footer class="flavor-footer flavor-footer--contact flavor-footer--<?php echo esc_attr($settings['background_style']); ?>">
            <?php if ($settings['show_map']): ?>
            <div class="flavor-footer__map" style="height: <?php echo esc_attr($settings['map_height']); ?>;">
                <?php $this->render_map(); ?>
            </div>
            <?php endif; ?>

            <div class="flavor-container">
                <div class="flavor-footer__contact-cards">
                    <!-- Address Card -->
                    <div class="flavor-contact-card">
                        <span class="flavor-contact-card__icon dashicons dashicons-location"></span>
                        <h4 class="flavor-contact-card__title"><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></h4>
                        <p class="flavor-contact-card__content"><?php echo esc_html($this->get_contact_address()); ?></p>
                    </div>

                    <!-- Phone Card -->
                    <div class="flavor-contact-card">
                        <span class="flavor-contact-card__icon dashicons dashicons-phone"></span>
                        <h4 class="flavor-contact-card__title"><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></h4>
                        <p class="flavor-contact-card__content">
                            <a href="tel:<?php echo esc_attr($this->get_contact_phone()); ?>"><?php echo esc_html($this->get_contact_phone()); ?></a>
                        </p>
                    </div>

                    <!-- Email Card -->
                    <div class="flavor-contact-card">
                        <span class="flavor-contact-card__icon dashicons dashicons-email-alt"></span>
                        <h4 class="flavor-contact-card__title"><?php esc_html_e('Email', 'flavor-chat-ia'); ?></h4>
                        <p class="flavor-contact-card__content">
                            <a href="mailto:<?php echo esc_attr($this->get_contact_email()); ?>"><?php echo esc_html($this->get_contact_email()); ?></a>
                        </p>
                    </div>

                    <!-- Hours Card -->
                    <div class="flavor-contact-card">
                        <span class="flavor-contact-card__icon dashicons dashicons-clock"></span>
                        <h4 class="flavor-contact-card__title"><?php esc_html_e('Horario', 'flavor-chat-ia'); ?></h4>
                        <p class="flavor-contact-card__content"><?php echo esc_html($this->get_business_hours()); ?></p>
                    </div>
                </div>

                <div class="flavor-footer__bottom">
                    <div class="flavor-footer__social">
                        <?php $this->render_social_icons(); ?>
                    </div>
                    <?php $this->render_footer_sponsors($settings); ?>
                    <?php $this->render_copyright(); ?>
                </div>
            </div>
        </footer>
        <?php
    }

    /**
     * Renderizar Footer App Download
     */
    public function render_footer_app_download($settings = []) {
        $defaults = [
            'show_qr' => true,
            'show_features' => true,
            'app_store_url' => '',
            'play_store_url' => '',
            'background_style' => 'dark',
        ];
        $settings = array_merge($defaults, $settings);

        $layout_settings = get_option('flavor_layout_settings', []);
        $app_store_url = !empty($settings['app_store_url']) ? $settings['app_store_url'] : ($layout_settings['app_store_url'] ?? '');
        $play_store_url = !empty($settings['play_store_url']) ? $settings['play_store_url'] : ($layout_settings['play_store_url'] ?? '');
        ?>
        <footer class="flavor-footer flavor-footer--app-download flavor-footer--<?php echo esc_attr($settings['background_style']); ?>">
            <div class="flavor-container">
                <!-- App Promo Section -->
                <div class="flavor-footer__app-promo">
                    <div class="flavor-app-promo">
                        <div class="flavor-app-promo__content">
                            <h3 class="flavor-app-promo__title"><?php esc_html_e('Descarga nuestra App', 'flavor-chat-ia'); ?></h3>
                            <p class="flavor-app-promo__description"><?php esc_html_e('Lleva todo en tu bolsillo. Disponible para iOS y Android.', 'flavor-chat-ia'); ?></p>

                            <?php if ($settings['show_features']): ?>
                            <ul class="flavor-app-promo__features">
                                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Notificaciones en tiempo real', 'flavor-chat-ia'); ?></li>
                                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Acceso sin conexión', 'flavor-chat-ia'); ?></li>
                                <li><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Interfaz optimizada', 'flavor-chat-ia'); ?></li>
                            </ul>
                            <?php endif; ?>

                            <div class="flavor-app-promo__buttons">
                                <?php if (!empty($app_store_url)): ?>
                                <a href="<?php echo esc_url($app_store_url); ?>" class="flavor-app-button flavor-app-button--ios" target="_blank" rel="noopener">
                                    <span class="flavor-app-button__icon"></span>
                                    <span class="flavor-app-button__text">
                                        <small><?php esc_html_e('Descargar en', 'flavor-chat-ia'); ?></small>
                                        App Store
                                    </span>
                                </a>
                                <?php endif; ?>

                                <?php if (!empty($play_store_url)): ?>
                                <a href="<?php echo esc_url($play_store_url); ?>" class="flavor-app-button flavor-app-button--android" target="_blank" rel="noopener">
                                    <span class="flavor-app-button__icon"></span>
                                    <span class="flavor-app-button__text">
                                        <small><?php esc_html_e('Disponible en', 'flavor-chat-ia'); ?></small>
                                        Google Play
                                    </span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($settings['show_qr']): ?>
                        <div class="flavor-app-promo__qr">
                            <div class="flavor-qr-code" id="flavor-app-qr"></div>
                            <p class="flavor-qr-code__label"><?php esc_html_e('Escanea para descargar', 'flavor-chat-ia'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bottom Section -->
                <div class="flavor-footer__bottom">
                    <div class="flavor-footer__brand">
                        <?php $this->render_logo('footer'); ?>
                    </div>
                    <?php $this->render_footer_sponsors($settings); ?>
                    <div class="flavor-footer__social">
                        <?php $this->render_social_icons(); ?>
                    </div>
                    <?php $this->render_copyright(); ?>
                </div>
            </div>
        </footer>
        <?php
    }

    // ====== HELPER METHODS ======

    /**
     * Renderizar logos de patrocinadores en footer
     */
    private function render_footer_sponsors($settings = []) {
        $sponsors = $settings['sponsors'] ?? [];
        if (empty($sponsors) || !is_array($sponsors)) {
            return;
        }
        ?>
        <div class="flavor-footer__sponsors">
            <span class="flavor-footer__sponsors-label"><?php esc_html_e('Patrocinan', 'flavor-chat-ia'); ?></span>
            <div class="flavor-footer__sponsors-logos">
                <?php foreach ($sponsors as $sponsor): ?>
                    <?php
                    $name = $sponsor['name'] ?? '';
                    $url = $sponsor['url'] ?? '';
                    $logo = $sponsor['logo'] ?? '';
                    ?>
                    <div class="flavor-footer__sponsor">
                        <?php if (!empty($url)): ?>
                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener">
                                <?php if (!empty($logo)): ?>
                                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name ?: 'Sponsor'); ?>">
                                <?php else: ?>
                                    <span><?php echo esc_html($name); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <?php if (!empty($logo)): ?>
                                <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($name ?: 'Sponsor'); ?>">
                            <?php else: ?>
                                <span><?php echo esc_html($name); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar logo
     *
     * Prioriza el logo configurado en Flavor Platform (flavor_logo_url),
     * luego el custom logo del tema de WordPress, y finalmente muestra el nombre del sitio.
     */
    private function render_logo($size = 'default') {
        // Usar función helper centralizada que prioriza flavor_logo_url
        $logo_url = '';
        if (class_exists('Flavor_Chat_Helpers')) {
            $logo_url = Flavor_Chat_Helpers::get_site_logo();
        }

        // Fallback al custom logo del tema si no hay logo de Flavor
        if (empty($logo_url)) {
            $custom_logo_id = get_theme_mod('custom_logo');
            $logo_url = $custom_logo_id ? wp_get_attachment_image_url($custom_logo_id, 'full') : '';
        }

        $size_class = 'flavor-logo--' . $size;
        ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="flavor-logo <?php echo esc_attr($size_class); ?>" rel="home">
            <?php if ($logo_url): ?>
                <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" class="flavor-logo__image">
            <?php else: ?>
                <span class="flavor-logo__text"><?php echo esc_html(get_bloginfo('name')); ?></span>
            <?php endif; ?>
        </a>
        <?php
    }

    /**
     * Renderizar navegación
     */
    private function render_navigation($context = 'desktop') {
        $menu_class = 'flavor-nav';
        if ($context !== 'desktop') {
            $menu_class .= ' flavor-nav--' . $context;
        }

        wp_nav_menu([
            'theme_location' => 'primary',
            'container' => false,
            'menu_class' => $menu_class,
            'fallback_cb' => [$this, 'fallback_navigation'],
            'depth' => $context === 'mobile' ? 2 : 3,
        ]);
    }

    /**
     * Navegación de respaldo
     */
    public function fallback_navigation() {
        ?>
        <ul class="flavor-nav">
            <li><a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Inicio', 'flavor-chat-ia'); ?></a></li>
            <?php wp_list_pages(['title_li' => '', 'depth' => 1]); ?>
        </ul>
        <?php
    }

    /**
     * Renderizar botón de búsqueda
     */
    private function render_search_button() {
        ?>
        <button class="flavor-search-toggle" aria-label="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-search"></span>
        </button>
        <?php
    }

    /**
     * Renderizar acciones del menú con orden configurable
     */
    private function render_menu_actions($settings, $available_actions) {
        $order = $settings['actions_order'] ?? [];
        $order = array_values(array_filter($order, function($action) use ($available_actions) {
            return in_array($action, $available_actions, true);
        }));

        if (empty($order)) {
            $order = $available_actions;
        }

        foreach ($order as $action) {
            if ($action === 'search') {
                $this->render_search_button();
            } elseif ($action === 'user') {
                $this->render_user_menu();
            } elseif ($action === 'cta') {
                $this->render_cta_button();
            }
        }
    }

    /**
     * Renderizar menú de usuario
     */
    private function render_user_menu() {
        if (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            $settings = get_option('flavor_chat_ia_settings', []);
            $active_modules = $settings['active_modules'] ?? [];

            // Fusionar con opción legacy para compatibilidad
            $modulos_legacy = get_option('flavor_active_modules', []);
            if (!empty($modulos_legacy)) {
                $active_modules = array_unique(array_merge($active_modules, $modulos_legacy));
            }

            // Obtener enlaces del menú de usuario
            $menu_links = $this->get_user_menu_links($current_user, $active_modules);
            ?>
            <div class="flavor-user-menu">
                <button class="flavor-user-menu__toggle" aria-expanded="false" aria-haspopup="true">
                    <?php echo get_avatar($current_user->ID, 32, '', '', ['class' => 'flavor-user-menu__avatar']); ?>
                </button>
                <div class="flavor-user-menu__dropdown">
                    <div class="flavor-user-menu__header">
                        <span class="flavor-user-menu__name"><?php echo esc_html($current_user->display_name); ?></span>
                        <span class="flavor-user-menu__email"><?php echo esc_html($current_user->user_email); ?></span>
                    </div>
                    <ul class="flavor-user-menu__links">
                        <?php foreach ($menu_links as $link): ?>
                        <li<?php echo !empty($link['separator']) ? ' class="flavor-user-menu__separator"' : ''; ?>>
                            <a href="<?php echo esc_url($link['url']); ?>"<?php echo !empty($link['icon']) ? ' class="has-icon"' : ''; ?>>
                                <?php if (!empty($link['icon'])): ?>
                                <span class="dashicons <?php echo esc_attr($link['icon']); ?>"></span>
                                <?php endif; ?>
                                <?php echo esc_html($link['label']); ?>
                                <?php if (!empty($link['badge'])): ?>
                                <span class="flavor-user-menu__badge"><?php echo esc_html($link['badge']); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php
        } else {
            ?>
            <a href="<?php echo esc_url(wp_login_url($this->get_current_request_url())); ?>" class="flavor-login-link">
                <span class="dashicons dashicons-admin-users"></span>
                <span class="flavor-login-link__text"><?php esc_html_e('Acceder', 'flavor-chat-ia'); ?></span>
            </a>
            <?php
        }
    }

    /**
     * Obtener enlaces del menú de usuario
     *
     * @param WP_User $user           Usuario actual
     * @param array   $active_modules Módulos activos
     * @return array Enlaces del menú
     */
    private function get_user_menu_links($user, $active_modules) {
        $links = [];

        // Mi Portal (siempre visible)
        $links[] = [
            'url'   => home_url('/mi-portal/'),
            'label' => __('Mi Portal', 'flavor-chat-ia'),
            'icon'  => 'dashicons-dashboard',
        ];

        // Mi Red Social (si hay módulos sociales activos)
        $modulos_sociales = ['red_social', 'comunidades', 'foros', 'chat_interno'];
        $tiene_social = !empty(array_intersect($modulos_sociales, $active_modules));
        if ($tiene_social) {
            $links[] = [
                'url'   => home_url('/mi-portal/mi-red/'),
                'label' => __('Mi Red Social', 'flavor-chat-ia'),
                'icon'  => 'dashicons-share',
            ];
        }

        // Mis Mensajes (si chat está activo)
        if (in_array('chat_interno', $active_modules, true) || in_array('chat_grupos', $active_modules, true)) {
            $mensajes_no_leidos = $this->contar_mensajes_no_leidos($user->ID);
            $links[] = [
                'url'   => home_url('/mi-portal/mensajes/'),
                'label' => __('Mis Mensajes', 'flavor-chat-ia'),
                'icon'  => 'dashicons-email',
                'badge' => $mensajes_no_leidos > 0 ? $mensajes_no_leidos : null,
            ];
        }

        // Notificaciones
        $notificaciones_no_leidas = $this->contar_notificaciones_no_leidas($user->ID);
        $links[] = [
            'url'   => home_url('/mi-portal/notificaciones/'),
            'label' => __('Notificaciones', 'flavor-chat-ia'),
            'icon'  => 'dashicons-bell',
            'badge' => $notificaciones_no_leidas > 0 ? $notificaciones_no_leidas : null,
        ];

        // Separador
        $links[] = [
            'url'       => '#',
            'label'     => '',
            'separator' => true,
        ];

        // Enlaces de módulos activos (máximo 4)
        $module_links = $this->get_active_module_links($active_modules);
        $module_count = 0;
        foreach ($module_links as $module_link) {
            if ($module_count >= 4) break;
            $links[] = $module_link;
            $module_count++;
        }

        // WooCommerce - Pedidos
        if (class_exists('WooCommerce') && in_array('woocommerce', $active_modules, true)) {
            $links[] = [
                'url'   => wc_get_account_endpoint_url('orders'),
                'label' => __('Mis Pedidos', 'flavor-chat-ia'),
                'icon'  => 'dashicons-cart',
            ];
        }

        // Separador antes de opciones de cuenta
        $links[] = [
            'url'       => '#',
            'label'     => '',
            'separator' => true,
        ];

        // Mi Perfil
        $links[] = [
            'url'   => home_url('/mi-portal/perfil/'),
            'label' => __('Mi Perfil', 'flavor-chat-ia'),
            'icon'  => 'dashicons-admin-users',
        ];

        // Configuración
        $links[] = [
            'url'   => home_url('/mi-portal/configuracion/'),
            'label' => __('Configuración', 'flavor-chat-ia'),
            'icon'  => 'dashicons-admin-generic',
        ];

        // Administración (solo para admins)
        if (current_user_can('manage_options')) {
            $links[] = [
                'url'   => admin_url(),
                'label' => __('Administración', 'flavor-chat-ia'),
                'icon'  => 'dashicons-wordpress',
            ];
        }

        // Cerrar sesión
        $links[] = [
            'url'   => wp_logout_url(home_url()),
            'label' => __('Cerrar sesión', 'flavor-chat-ia'),
            'icon'  => 'dashicons-exit',
        ];

        return apply_filters('flavor_user_menu_links', $links, $user, $active_modules);
    }

    /**
     * Obtener enlaces de módulos activos
     *
     * @param array $active_modules Módulos activos
     * @return array
     */
    private function get_active_module_links($active_modules) {
        $links = [];

        $module_config = [
            'grupos_consumo' => [
                'url'   => '/mi-portal/grupos-consumo/',
                'label' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'icon'  => 'dashicons-groups',
            ],
            'banco_tiempo' => [
                'url'   => '/mi-portal/banco-tiempo/',
                'label' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'icon'  => 'dashicons-clock',
            ],
            'marketplace' => [
                'url'   => '/mi-portal/marketplace/',
                'label' => __('Marketplace', 'flavor-chat-ia'),
                'icon'  => 'dashicons-store',
            ],
            'eventos' => [
                'url'   => '/mi-portal/eventos/',
                'label' => __('Mis Eventos', 'flavor-chat-ia'),
                'icon'  => 'dashicons-calendar-alt',
            ],
            'reservas' => [
                'url'   => '/mi-portal/reservas/',
                'label' => __('Mis Reservas', 'flavor-chat-ia'),
                'icon'  => 'dashicons-calendar',
            ],
            'ayuda_vecinal' => [
                'url'   => '/mi-portal/ayuda-vecinal/',
                'label' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'icon'  => 'dashicons-heart',
            ],
            'cursos' => [
                'url'   => '/mi-portal/cursos/',
                'label' => __('Mis Cursos', 'flavor-chat-ia'),
                'icon'  => 'dashicons-welcome-learn-more',
            ],
            'tramites' => [
                'url'   => '/mi-portal/tramites/',
                'label' => __('Mis Trámites', 'flavor-chat-ia'),
                'icon'  => 'dashicons-clipboard',
            ],
        ];

        foreach ($module_config as $module_id => $config) {
            if (in_array($module_id, $active_modules, true)) {
                $links[] = [
                    'url'   => home_url($config['url']),
                    'label' => $config['label'],
                    'icon'  => $config['icon'],
                ];
            }
        }

        return $links;
    }

    /**
     * Contar mensajes no leídos
     *
     * @param int $user_id ID del usuario
     * @return int
     */
    private function contar_mensajes_no_leidos($user_id) {
        global $wpdb;

        // La tabla flavor_chat_interno_mensajes usa arquitectura de conversaciones
        // con participantes, no tiene columna destinatario_id directa.
        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_interno_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_interno_participantes';

        // Verificar que existan ambas tablas
        if (Flavor_Chat_Helpers::tabla_existe($tabla_mensajes) &&
            Flavor_Chat_Helpers::tabla_existe($tabla_participantes)) {
            // Contar mensajes no leídos en todas las conversaciones donde participa el usuario
            $total_no_leidos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COALESCE(SUM(no_leidos), 0) FROM (
                    SELECT COUNT(*) as no_leidos
                    FROM {$tabla_mensajes} m
                    INNER JOIN {$tabla_participantes} p ON m.conversacion_id = p.conversacion_id
                    WHERE p.usuario_id = %d
                    AND m.remitente_id != %d
                    AND m.id > p.ultimo_mensaje_leido
                    AND m.eliminado = 0
                    GROUP BY m.conversacion_id
                ) as subquery",
                $user_id,
                $user_id
            ));
            return $total_no_leidos;
        }

        // No usar fallback a tabla genérica - el módulo chat interno tiene su propia estructura
        return 0;
    }

    /**
     * Contar notificaciones no leídas
     *
     * @param int $user_id ID del usuario
     * @return int
     */
    private function contar_notificaciones_no_leidas($user_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_notificaciones';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d AND leida = 0",
            $user_id
        ));
    }

    /**
     * Renderizar botón CTA
     */
    private function render_cta_button() {
        $settings = get_option('flavor_layout_settings', []);
        $cta_text = $settings['cta_text'] ?? __('Empezar', 'flavor-chat-ia');
        $cta_url = $settings['cta_url'] ?? '#';

        if (!empty($cta_text) && !empty($cta_url)) {
            ?>
            <a href="<?php echo esc_url($cta_url); ?>" class="flavor-button flavor-button--primary flavor-menu__cta">
                <?php echo esc_html($cta_text); ?>
            </a>
            <?php
        }
    }

    /**
     * Renderizar iconos sociales
     */
    private function render_social_icons($size = 'default') {
        $settings = get_option('flavor_layout_settings', []);
        $social_links = $settings['social_links'] ?? [];

        $networks = [
            'facebook' => ['icon' => 'facebook', 'label' => 'Facebook'],
            'twitter' => ['icon' => 'twitter', 'label' => 'Twitter/X'],
            'instagram' => ['icon' => 'instagram', 'label' => 'Instagram'],
            'linkedin' => ['icon' => 'linkedin', 'label' => 'LinkedIn'],
            'youtube' => ['icon' => 'youtube', 'label' => 'YouTube'],
            'tiktok' => ['icon' => 'video-alt3', 'label' => 'TikTok'],
        ];
        ?>
        <div class="flavor-social flavor-social--<?php echo esc_attr($size); ?>">
            <?php foreach ($networks as $network => $data): ?>
                <?php if (!empty($social_links[$network])): ?>
                <a href="<?php echo esc_url($social_links[$network]); ?>" class="flavor-social__link flavor-social__link--<?php echo esc_attr($network); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr($data['label']); ?>">
                    <span class="dashicons dashicons-<?php echo esc_attr($data['icon']); ?>"></span>
                </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderizar información de contacto
     */
    private function render_contact_info() {
        $phone = $this->get_contact_phone();
        $email = $this->get_contact_email();
        ?>
        <div class="flavor-contact-info">
            <?php if ($phone): ?>
            <a href="tel:<?php echo esc_attr($phone); ?>" class="flavor-contact-info__item">
                <span class="dashicons dashicons-phone"></span>
                <?php echo esc_html($phone); ?>
            </a>
            <?php endif; ?>
            <?php if ($email): ?>
            <a href="mailto:<?php echo esc_attr($email); ?>" class="flavor-contact-info__item">
                <span class="dashicons dashicons-email"></span>
                <?php echo esc_html($email); ?>
            </a>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderizar botón de notificaciones
     */
    private function render_notifications_button() {
        if (!is_user_logged_in()) return;

        $notifications_count = apply_filters('flavor_notifications_count', 0);
        ?>
        <button class="flavor-notifications-toggle" aria-label="<?php esc_attr_e('Notificaciones', 'flavor-chat-ia'); ?>">
            <span class="dashicons dashicons-bell"></span>
            <?php if ($notifications_count > 0): ?>
            <span class="flavor-notifications-badge"><?php echo intval($notifications_count); ?></span>
            <?php endif; ?>
        </button>
        <?php
    }

    /**
     * Renderizar sección de usuario en sidebar
     */
    private function render_user_section() {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            ?>
            <div class="flavor-user-section">
                <?php echo get_avatar($user->ID, 64, '', '', ['class' => 'flavor-user-section__avatar']); ?>
                <div class="flavor-user-section__info">
                    <span class="flavor-user-section__name"><?php echo esc_html($user->display_name); ?></span>
                    <span class="flavor-user-section__role"><?php echo esc_html(ucfirst($user->roles[0] ?? 'Usuario')); ?></span>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="flavor-user-section flavor-user-section--guest">
                <a href="<?php echo esc_url(wp_login_url()); ?>" class="flavor-button flavor-button--primary"><?php esc_html_e('Iniciar sesión', 'flavor-chat-ia'); ?></a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-button flavor-button--secondary"><?php esc_html_e('Registrarse', 'flavor-chat-ia'); ?></a>
            </div>
            <?php
        }
    }

    /**
     * Renderizar pie del sidebar
     */
    private function render_sidebar_footer() {
        ?>
        <div class="flavor-sidebar-footer">
            <?php $this->render_social_icons('small'); ?>
            <p class="flavor-sidebar-footer__copyright">
                &copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Obtener items para bottom navigation
     */
    private function get_bottom_nav_items($max_items = 5) {
        $items = [];
        $current_url = home_url(add_query_arg([]));

        // Defaults si no hay menú configurado
        $default_items = [
            ['url' => home_url('/'), 'label' => __('Inicio', 'flavor-chat-ia'), 'icon' => 'admin-home', 'badge' => 0],
            ['url' => home_url('/explorar'), 'label' => __('Explorar', 'flavor-chat-ia'), 'icon' => 'search', 'badge' => 0],
            ['url' => home_url('/crear'), 'label' => __('Crear', 'flavor-chat-ia'), 'icon' => 'plus-alt', 'badge' => 0],
            ['url' => home_url('/mensajes'), 'label' => __('Mensajes', 'flavor-chat-ia'), 'icon' => 'email', 'badge' => 0],
            ['url' => home_url('/perfil'), 'label' => __('Perfil', 'flavor-chat-ia'), 'icon' => 'admin-users', 'badge' => 0],
        ];

        // Obtener del menú si existe
        $menu_locations = get_nav_menu_locations();
        if (isset($menu_locations['bottom-nav'])) {
            $menu_items = wp_get_nav_menu_items($menu_locations['bottom-nav']);
            if ($menu_items) {
                foreach (array_slice($menu_items, 0, $max_items) as $item) {
                    $icon = get_post_meta($item->ID, '_menu_item_icon', true) ?: 'marker';
                    $items[] = [
                        'url' => $item->url,
                        'label' => $item->title,
                        'icon' => $icon,
                        'badge' => 0,
                        'active' => $current_url === $item->url,
                    ];
                }
            }
        }

        // Usar defaults si no hay items
        if (empty($items)) {
            foreach (array_slice($default_items, 0, $max_items) as $item) {
                $item['active'] = $current_url === $item['url'];
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Renderizar mega navegación
     */
    private function render_mega_navigation($settings = []) {
        wp_nav_menu([
            'theme_location' => 'primary',
            'container' => false,
            'menu_class' => 'flavor-mega-nav__list',
            'walker' => new Flavor_Mega_Menu_Walker($settings),
            'fallback_cb' => [$this, 'fallback_navigation'],
        ]);
    }

    /**
     * Renderizar columnas del footer
     */
    private function render_footer_columns($num_columns = 4) {
        $footer_menus = ['footer-1', 'footer-2', 'footer-3', 'footer-4'];

        for ($i = 0; $i < $num_columns; $i++) {
            $menu_location = $footer_menus[$i] ?? 'footer-' . ($i + 1);
            ?>
            <div class="flavor-footer__column">
                <?php
                if (has_nav_menu($menu_location)) {
                    wp_nav_menu([
                        'theme_location' => $menu_location,
                        'container' => false,
                        'menu_class' => 'flavor-footer-menu',
                        'depth' => 1,
                    ]);
                } else {
                    // Fallback con widget area
                    if (is_active_sidebar('footer-' . ($i + 1))) {
                        dynamic_sidebar('footer-' . ($i + 1));
                    }
                }
                ?>
            </div>
            <?php
        }
    }

    /**
     * Renderizar copyright
     */
    private function render_copyright() {
        $settings = get_option('flavor_layout_settings', []);
        $copyright_text = $settings['copyright_text'] ?? sprintf(
            __('&copy; %d %s. Todos los derechos reservados.', 'flavor-chat-ia'),
            date('Y'),
            get_bloginfo('name')
        );
        ?>
        <p class="flavor-copyright"><?php echo wp_kses_post($copyright_text); ?></p>
        <?php
    }

    /**
     * Renderizar enlaces legales
     */
    private function render_legal_links() {
        $settings = get_option('flavor_layout_settings', []);
        $privacy_page = get_privacy_policy_url();

        // Buscar páginas legales por slug o crear URLs condicionales
        $terminos_page = get_page_by_path('terminos-de-uso') ?: get_page_by_path('terminos');
        $cookies_page = get_page_by_path('politica-de-cookies') ?: get_page_by_path('cookies');
        ?>
        <nav class="flavor-legal-links">
            <?php if ($privacy_page): ?>
            <a href="<?php echo esc_url($privacy_page); ?>"><?php esc_html_e('Política de privacidad', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
            <?php if ($terminos_page): ?>
            <a href="<?php echo esc_url(get_permalink($terminos_page)); ?>"><?php esc_html_e('Términos de uso', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
            <?php if ($cookies_page): ?>
            <a href="<?php echo esc_url(get_permalink($cookies_page)); ?>"><?php esc_html_e('Política de cookies', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </nav>
        <?php
    }

    /**
     * Renderizar mapa
     */
    private function render_map() {
        $settings = get_option('flavor_layout_settings', []);
        $map_embed = $settings['map_embed_url'] ?? '';

        if (!empty($map_embed)) {
            ?>
            <iframe src="<?php echo esc_url($map_embed); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            <?php
        } else {
            ?>
            <div class="flavor-map-placeholder">
                <span class="dashicons dashicons-location-alt"></span>
                <p><?php esc_html_e('Configura el mapa en los ajustes', 'flavor-chat-ia'); ?></p>
            </div>
            <?php
        }
    }

    // Helpers para datos de contacto
    private function get_contact_phone() {
        $settings = get_option('flavor_layout_settings', []);
        return $settings['contact_phone'] ?? '';
    }

    private function get_contact_email() {
        $settings = get_option('flavor_layout_settings', []);
        return $settings['contact_email'] ?? get_option('admin_email');
    }

    private function get_contact_address() {
        $settings = get_option('flavor_layout_settings', []);
        return $settings['contact_address'] ?? '';
    }

    private function get_business_hours() {
        $settings = get_option('flavor_layout_settings', []);
        return $settings['business_hours'] ?? __('Lun - Vie: 9:00 - 18:00', 'flavor-chat-ia');
    }

    /**
     * Fallback para menú
     */
    private function render_menu_fallback($menu_type, $settings) {
        // Llamar al método correspondiente
        $method = 'render_menu_' . str_replace('-', '_', $menu_type);
        if (method_exists($this, $method)) {
            $this->$method($settings);
        } else {
            $this->render_menu_classic($settings);
        }
    }

    /**
     * Fallback para footer
     */
    private function render_footer_fallback($footer_type, $settings) {
        $method = 'render_footer_' . str_replace('-', '_', $footer_type);
        if (method_exists($this, $method)) {
            $this->$method($settings);
        } else {
            $this->render_footer_multi_column($settings);
        }
    }

    /**
     * Shortcode para menú
     */
    public function shortcode_menu($atts) {
        $atts = shortcode_atts([
            'type' => '',
        ], $atts);

        ob_start();
        if (!empty($atts['type'])) {
            $settings = $this->registry->get_menu_settings($atts['type']);
            $method = 'render_menu_' . str_replace('-', '_', $atts['type']);
            if (method_exists($this, $method)) {
                $this->$method($settings);
            }
        } else {
            $this->render_header();
        }
        return ob_get_clean();
    }

    /**
     * Shortcode para footer
     */
    public function shortcode_footer($atts) {
        $atts = shortcode_atts([
            'type' => '',
        ], $atts);

        ob_start();
        if (!empty($atts['type'])) {
            $settings = $this->registry->get_footer_settings($atts['type']);
            $method = 'render_footer_' . str_replace('-', '_', $atts['type']);
            if (method_exists($this, $method)) {
                $this->$method($settings);
            }
        } else {
            $this->render_footer();
        }
        return ob_get_clean();
    }
}

/**
 * Walker personalizado para Mega Menu
 */
class Flavor_Mega_Menu_Walker extends Walker_Nav_Menu {

    private $settings;
    private $megamenu_items = [];

    public function __construct($settings = []) {
        $this->settings = $settings;
    }

    public function start_lvl(&$output, $depth = 0, $args = null) {
        if ($depth === 0) {
            $output .= '<div class="flavor-mega-dropdown"><div class="flavor-mega-dropdown__inner"><ul class="flavor-mega-dropdown__list">';
        } else {
            $output .= '<ul class="flavor-mega-dropdown__sublist">';
        }
    }

    public function end_lvl(&$output, $depth = 0, $args = null) {
        if ($depth === 0) {
            $output .= '</ul></div></div>';
        } else {
            $output .= '</ul>';
        }
    }

    public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0) {
        $classes = implode(' ', $item->classes);
        $has_children = in_array('menu-item-has-children', $item->classes);

        if ($depth === 0 && $has_children) {
            $output .= '<li class="flavor-mega-nav__item has-dropdown ' . esc_attr($classes) . '">';
            $output .= '<a href="' . esc_url($item->url) . '" class="flavor-mega-nav__link">' . esc_html($item->title);
            $output .= '<span class="flavor-mega-nav__arrow dashicons dashicons-arrow-down-alt2"></span></a>';
        } else {
            $output .= '<li class="' . ($depth === 0 ? 'flavor-mega-nav__item' : 'flavor-mega-dropdown__item') . ' ' . esc_attr($classes) . '">';
            $output .= '<a href="' . esc_url($item->url) . '">' . esc_html($item->title) . '</a>';
        }
    }

    public function end_el(&$output, $item, $depth = 0, $args = null) {
        $output .= '</li>';
    }
}

/**
 * Función helper para obtener la instancia del renderer
 */
function flavor_layout_renderer() {
    return Flavor_Layout_Renderer::get_instance();
}
