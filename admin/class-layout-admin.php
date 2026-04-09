<?php
/**
 * Layout Admin - Panel de Administración de Layouts
 *
 * Gestiona la interfaz de selección de menús y footers
 * con vista previa visual.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Layout_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Registry de layouts
     */
    private $registry;

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
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_flavor_save_layout', [$this, 'ajax_save_layout']);
        add_action('wp_ajax_flavor_save_layout_settings', [$this, 'ajax_save_layout_settings']);
        add_action('wp_ajax_flavor_export_mobile_config', [$this, 'ajax_export_mobile_config']);
        add_action('wp_ajax_flavor_get_layout_preview', [$this, 'ajax_get_layout_preview']);
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Layouts y Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Layouts', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-layouts',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Encolar assets de administración
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-layouts') === false) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-layout-admin',
            FLAVOR_CHAT_IA_URL . "admin/css/layout-admin{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-layout-admin',
            FLAVOR_CHAT_IA_URL . "admin/js/layout-admin{$sufijo_asset}.js",
            ['jquery', 'wp-color-picker'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_enqueue_style('wp-color-picker');

        wp_localize_script('flavor-layout-admin', 'flavorLayoutAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_layout_nonce'),
            'strings' => [
                'saving' => __('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'saved' => __('Guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirm_export' => __('¿Exportar configuración para apps móviles?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'exported' => __('Configuración exportada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sponsor_name' => __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sponsor_logo' => __('URL del logo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'remove' => __('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'select' => __('Seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'selected' => __('Seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Renderizar página de administración
     */
    public function render_admin_page() {
        $this->registry = flavor_layout_registry();
        $menus = $this->registry->get_menus();
        $footers = $this->registry->get_footers();
        $layouts = $this->registry->get_layouts();
        $active_layout = $this->registry->get_active_layout();
        $settings = get_option('flavor_layout_settings', []);
        ?>
        <div class="wrap flavor-layout-admin">
            <h1 class="flavor-admin-title">
                <span class="dashicons dashicons-layout"></span>
                <?php esc_html_e('Layouts Predefinidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <div class="flavor-admin-header">
                <p class="flavor-admin-description">
                    <?php esc_html_e('Selecciona el diseño de menú y footer para tu sitio web y aplicaciones móviles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <div class="flavor-admin-actions">
                    <button type="button" class="button flavor-export-mobile" id="flavor-export-mobile">
                        <span class="dashicons dashicons-smartphone"></span>
                        <?php esc_html_e('Exportar para APKs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <div class="flavor-layout-tabs">
                <nav class="flavor-tabs-nav">
                    <button type="button" class="flavor-tab-btn active" data-tab="menus">
                        <span class="dashicons dashicons-menu-alt3"></span>
                        <?php esc_html_e('Menús', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-tab-btn" data-tab="footers">
                        <span class="dashicons dashicons-editor-insertmore"></span>
                        <?php esc_html_e('Footers', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-tab-btn" data-tab="presets">
                        <span class="dashicons dashicons-admin-customizer"></span>
                        <?php esc_html_e('Presets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-tab-btn" data-tab="settings">
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="flavor-tab-btn" data-tab="components">
                        <span class="dashicons dashicons-welcome-widgets-menus"></span>
                        <?php esc_html_e('Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </nav>

                <!-- Tab: Menús -->
                <div class="flavor-tab-content active" data-tab="menus">
                    <h2><?php esc_html_e('Selecciona un diseño de menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <div class="flavor-layout-grid">
                        <?php foreach ($menus as $menu_id => $menu): ?>
                        <div class="flavor-layout-card <?php echo $active_layout['menu'] === $menu_id ? 'selected' : ''; ?>" data-type="menu" data-id="<?php echo esc_attr($menu_id); ?>">
                            <div class="flavor-layout-card__preview">
                                <?php $this->render_menu_preview($menu_id); ?>
                            </div>
                            <div class="flavor-layout-card__content">
                                <h3 class="flavor-layout-card__title">
                                    <span class="dashicons <?php echo esc_attr($menu['icon']); ?>"></span>
                                    <?php echo esc_html($menu['name']); ?>
                                </h3>
                                <p class="flavor-layout-card__description"><?php echo esc_html($menu['description']); ?></p>
                                <div class="flavor-layout-card__meta">
                                    <span class="flavor-layout-card__tag"><?php echo esc_html($menu['mobile_behavior']); ?></span>
                                    <?php if (!empty($menu['recommended_for'])): ?>
                                    <span class="flavor-layout-card__recommended">
                                        <?php esc_html_e('Ideal para:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <?php echo esc_html(implode(', ', array_slice($menu['recommended_for'], 0, 2))); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flavor-layout-card__actions">
                                <button type="button" class="button flavor-select-layout <?php echo $active_layout['menu'] === $menu_id ? 'button-primary' : ''; ?>">
                                    <?php echo $active_layout['menu'] === $menu_id ? esc_html__('Seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button flavor-preview-layout" data-preview="menu" data-id="<?php echo esc_attr($menu_id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab: Footers -->
                <div class="flavor-tab-content" data-tab="footers">
                    <h2><?php esc_html_e('Selecciona un diseño de footer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <div class="flavor-layout-grid">
                        <?php foreach ($footers as $footer_id => $footer): ?>
                        <div class="flavor-layout-card <?php echo $active_layout['footer'] === $footer_id ? 'selected' : ''; ?>" data-type="footer" data-id="<?php echo esc_attr($footer_id); ?>">
                            <div class="flavor-layout-card__preview">
                                <?php $this->render_footer_preview($footer_id); ?>
                            </div>
                            <div class="flavor-layout-card__content">
                                <h3 class="flavor-layout-card__title">
                                    <span class="dashicons <?php echo esc_attr($footer['icon']); ?>"></span>
                                    <?php echo esc_html($footer['name']); ?>
                                </h3>
                                <p class="flavor-layout-card__description"><?php echo esc_html($footer['description']); ?></p>
                                <div class="flavor-layout-card__meta">
                                    <?php if (!empty($footer['recommended_for'])): ?>
                                    <span class="flavor-layout-card__recommended">
                                        <?php esc_html_e('Ideal para:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        <?php echo esc_html(implode(', ', array_slice($footer['recommended_for'], 0, 2))); ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flavor-layout-card__actions">
                                <button type="button" class="button flavor-select-layout <?php echo $active_layout['footer'] === $footer_id ? 'button-primary' : ''; ?>">
                                    <?php echo $active_layout['footer'] === $footer_id ? esc_html__('Seleccionado', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                                <button type="button" class="button flavor-preview-layout" data-preview="footer" data-id="<?php echo esc_attr($footer_id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab: Presets -->
                <div class="flavor-tab-content" data-tab="presets">
                    <h2><?php esc_html_e('Presets de Layout por Perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="flavor-preset-description">
                        <?php esc_html_e('Aplica una combinación predefinida de menú y footer optimizada para cada tipo de perfil.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                    <div class="flavor-preset-grid">
                        <?php foreach ($layouts as $layout_id => $layout): ?>
                        <?php
                        $layout_menu = $this->registry->get_menu($layout['menu']);
                        $layout_footer = $this->registry->get_footer($layout['footer']);
                        $is_active = ($active_layout['menu'] === $layout['menu'] && $active_layout['footer'] === $layout['footer']);
                        ?>
                        <div class="flavor-preset-card <?php echo $is_active ? 'active' : ''; ?>" data-preset="<?php echo esc_attr($layout_id); ?>">
                            <div class="flavor-preset-card__header">
                                <h3><?php echo esc_html($layout['name']); ?></h3>
                                <?php if ($is_active): ?>
                                <span class="flavor-preset-card__badge"><?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-preset-card__preview">
                                <div class="flavor-preset-preview__menu">
                                    <?php $this->render_menu_preview($layout['menu'], 'mini'); ?>
                                </div>
                                <div class="flavor-preset-preview__content">
                                    <div class="flavor-preset-preview__placeholder"></div>
                                </div>
                                <div class="flavor-preset-preview__footer">
                                    <?php $this->render_footer_preview($layout['footer'], 'mini'); ?>
                                </div>
                            </div>
                            <div class="flavor-preset-card__info">
                                <div class="flavor-preset-card__detail">
                                    <span class="dashicons dashicons-menu-alt3"></span>
                                    <span><?php echo esc_html($layout_menu['name']); ?></span>
                                </div>
                                <div class="flavor-preset-card__detail">
                                    <span class="dashicons dashicons-editor-insertmore"></span>
                                    <span><?php echo esc_html($layout_footer['name']); ?></span>
                                </div>
                            </div>
                            <button type="button" class="button button-primary flavor-apply-preset" data-menu="<?php echo esc_attr($layout['menu']); ?>" data-footer="<?php echo esc_attr($layout['footer']); ?>">
                                <?php esc_html_e('Aplicar Preset', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab: Ajustes -->
                <div class="flavor-tab-content" data-tab="settings">
                    <h2><?php esc_html_e('Ajustes Generales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

                    <form id="flavor-layout-settings-form" class="flavor-settings-form">
                        <!-- CTA Button -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Botón CTA del Menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <div class="flavor-settings-row">
                                <label for="cta_text"><?php esc_html_e('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="cta_text" name="cta_text" value="<?php echo esc_attr($settings['cta_text'] ?? ''); ?>" placeholder="<?php esc_attr_e('Empezar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="cta_url"><?php esc_html_e('URL del botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="url" id="cta_url" name="cta_url" value="<?php echo esc_url($settings['cta_url'] ?? ''); ?>" placeholder="https://">
                            </div>
                        </div>

                        <!-- Menu Settings -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Ajustes de Menús', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p class="description"><?php esc_html_e('Configura el comportamiento y orden de elementos por cada menú.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                            <div class="flavor-settings-row">
                                <label for="menu_settings_menu"><?php esc_html_e('Menú a configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="menu_settings_menu" class="flavor-menu-settings-select">
                                    <?php foreach ($menus as $menu_id => $menu): ?>
                                        <option value="<?php echo esc_attr($menu_id); ?>" <?php selected($active_layout['menu'] ?? 'classic', $menu_id); ?>>
                                            <?php echo esc_html($menu['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php
                            $menu_settings = $settings['menu_settings'] ?? [];
                            foreach ($menus as $menu_id => $menu):
                                $menu_cfg = $menu_settings[$menu_id] ?? [];
                                $is_active = ($active_layout['menu'] ?? 'classic') === $menu_id;
                                $actions_order = $menu_cfg['actions_order'] ?? ['search', 'user', 'cta'];
                                $actions_order = array_values(array_pad($actions_order, 3, 'cta'));
                            ?>
                            <div class="flavor-menu-settings" data-menu="<?php echo esc_attr($menu_id); ?>" <?php echo $is_active ? '' : 'style="display:none"'; ?>>
                                <div class="flavor-settings-row">
                                    <label><?php esc_html_e('Menú fijo (sticky)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <label class="flavor-toggle">
                                        <input type="checkbox" name="menu_settings[<?php echo esc_attr($menu_id); ?>][sticky]" value="1" <?php checked($menu_cfg['sticky'] ?? true); ?>>
                                        <span class="flavor-toggle__slider"></span>
                                    </label>
                                </div>

                                <div class="flavor-settings-row">
                                    <label><?php esc_html_e('Estado por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <select name="menu_settings[<?php echo esc_attr($menu_id); ?>][menu_state]">
                                        <option value="expanded" <?php selected($menu_cfg['menu_state'] ?? 'expanded', 'expanded'); ?>><?php esc_html_e('Desplegado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="collapsed" <?php selected($menu_cfg['menu_state'] ?? '', 'collapsed'); ?>><?php esc_html_e('Replegado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </div>

                                <div class="flavor-settings-row">
                                    <label><?php esc_html_e('Orden de acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <div class="flavor-action-order">
                                        <?php foreach ([0, 1, 2] as $index): ?>
                                            <select name="menu_settings[<?php echo esc_attr($menu_id); ?>][actions_order][<?php echo intval($index); ?>]">
                                                <option value="search" <?php selected($actions_order[$index] ?? 'search', 'search'); ?>><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                                <option value="user" <?php selected($actions_order[$index] ?? '', 'user'); ?>><?php esc_html_e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                                <option value="cta" <?php selected($actions_order[$index] ?? '', 'cta'); ?>><?php esc_html_e('CTA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                            </select>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <div class="flavor-settings-row">
                                    <label><?php esc_html_e('Estilo vista normal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <select name="menu_settings[<?php echo esc_attr($menu_id); ?>][style_normal]">
                                        <option value="light" <?php selected($menu_cfg['style_normal'] ?? 'light', 'light'); ?>><?php esc_html_e('Claro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="dark" <?php selected($menu_cfg['style_normal'] ?? '', 'dark'); ?>><?php esc_html_e('Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="transparent" <?php selected($menu_cfg['style_normal'] ?? '', 'transparent'); ?>><?php esc_html_e('Transparente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </div>

                                <div class="flavor-settings-row">
                                    <label><?php esc_html_e('Estilo vista fija', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    <select name="menu_settings[<?php echo esc_attr($menu_id); ?>][style_sticky]">
                                        <option value="light" <?php selected($menu_cfg['style_sticky'] ?? 'light', 'light'); ?>><?php esc_html_e('Claro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="dark" <?php selected($menu_cfg['style_sticky'] ?? '', 'dark'); ?>><?php esc_html_e('Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="transparent" <?php selected($menu_cfg['style_sticky'] ?? '', 'transparent'); ?>><?php esc_html_e('Transparente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Contact Info -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Información de Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <div class="flavor-settings-row">
                                <label for="contact_phone"><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo esc_attr($settings['contact_phone'] ?? ''); ?>" placeholder="+34 600 000 000">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="contact_email"><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="email" id="contact_email" name="contact_email" value="<?php echo esc_attr($settings['contact_email'] ?? ''); ?>" placeholder="info@ejemplo.com">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="contact_address"><?php esc_html_e('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <textarea id="contact_address" name="contact_address" rows="2"><?php echo esc_textarea($settings['contact_address'] ?? ''); ?></textarea>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="business_hours"><?php esc_html_e('Horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="business_hours" name="business_hours" value="<?php echo esc_attr($settings['business_hours'] ?? ''); ?>" placeholder="<?php esc_attr_e('Lun - Vie: 9:00 - 18:00', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="map_embed_url"><?php esc_html_e('URL del mapa (embed)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="url" id="map_embed_url" name="map_embed_url" value="<?php echo esc_url($settings['map_embed_url'] ?? ''); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
                            </div>
                        </div>

                        <!-- Social Links -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Redes Sociales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <?php
                            $social_networks = [
                                'facebook' => 'Facebook',
                                'twitter' => 'Twitter / X',
                                'instagram' => 'Instagram',
                                'linkedin' => 'LinkedIn',
                                'youtube' => 'YouTube',
                                'tiktok' => 'TikTok',
                            ];
                            $social_links = $settings['social_links'] ?? [];
                            foreach ($social_networks as $network => $label):
                            ?>
                            <div class="flavor-settings-row">
                                <label for="social_<?php echo esc_attr($network); ?>"><?php echo esc_html($label); ?></label>
                                <input type="url" id="social_<?php echo esc_attr($network); ?>" name="social_links[<?php echo esc_attr($network); ?>]" value="<?php echo esc_url($social_links[$network] ?? ''); ?>" placeholder="https://">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- App Links -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Enlaces de Aplicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <div class="flavor-settings-row">
                                <label for="app_store_url"><?php esc_html_e('App Store (iOS)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="url" id="app_store_url" name="app_store_url" value="<?php echo esc_url($settings['app_store_url'] ?? ''); ?>" placeholder="https://apps.apple.com/...">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="play_store_url"><?php esc_html_e('Google Play (Android)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="url" id="play_store_url" name="play_store_url" value="<?php echo esc_url($settings['play_store_url'] ?? ''); ?>" placeholder="https://play.google.com/store/apps/...">
                            </div>
                        </div>

                        <!-- Copyright -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Copyright', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <div class="flavor-settings-row">
                                <label for="copyright_text"><?php esc_html_e('Texto de copyright', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="copyright_text" name="copyright_text" value="<?php echo esc_attr($settings['copyright_text'] ?? ''); ?>" placeholder="<?php echo esc_attr(sprintf(__('© %d %s. Todos los derechos reservados.', FLAVOR_PLATFORM_TEXT_DOMAIN), date('Y'), get_bloginfo('name'))); ?>">
                            </div>
                        </div>

                        <!-- Sponsors -->
                        <div class="flavor-settings-section">
                            <h3><?php esc_html_e('Patrocinadores en Footer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p class="description"><?php esc_html_e('Añade logos de patrocinadores, sponsors o colaboradores para cada footer.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                            <div class="flavor-settings-row">
                                <label for="footer_sponsors_footer"><?php esc_html_e('Footer a configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="footer_sponsors_footer" class="flavor-footer-sponsors-select">
                                    <?php foreach ($footers as $footer_id => $footer): ?>
                                        <option value="<?php echo esc_attr($footer_id); ?>" <?php selected($active_layout['footer'] ?? 'multi-column', $footer_id); ?>>
                                            <?php echo esc_html($footer['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php
                            $footer_settings = $settings['footer_settings'] ?? [];
                            foreach ($footers as $footer_id => $footer):
                                $sponsors = $footer_settings[$footer_id]['sponsors'] ?? [];
                                $is_active = ($active_layout['footer'] ?? 'multi-column') === $footer_id;
                            ?>
                            <div class="flavor-footer-sponsors" data-footer="<?php echo esc_attr($footer_id); ?>" <?php echo $is_active ? '' : 'style="display:none"'; ?>>
                                <div class="flavor-sponsors-list" data-next-index="<?php echo intval(count($sponsors)); ?>">
                                    <?php if (!empty($sponsors)): ?>
                                        <?php foreach ($sponsors as $index => $sponsor): ?>
                                        <div class="flavor-sponsor-row">
                                            <input type="text" name="footer_sponsors[<?php echo esc_attr($footer_id); ?>][<?php echo intval($index); ?>][name]" value="<?php echo esc_attr($sponsor['name'] ?? ''); ?>" placeholder="<?php esc_attr_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <input type="url" name="footer_sponsors[<?php echo esc_attr($footer_id); ?>][<?php echo intval($index); ?>][url]" value="<?php echo esc_url($sponsor['url'] ?? ''); ?>" placeholder="https://">
                                            <input type="url" name="footer_sponsors[<?php echo esc_attr($footer_id); ?>][<?php echo intval($index); ?>][logo]" value="<?php echo esc_url($sponsor['logo'] ?? ''); ?>" placeholder="<?php esc_attr_e('URL del logo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <button type="button" class="button flavor-remove-sponsor"><?php esc_html_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="button flavor-add-sponsor" data-footer="<?php echo esc_attr($footer_id); ?>">
                                    <?php esc_html_e('Añadir patrocinador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flavor-settings-actions">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-saved"></span>
                                <?php esc_html_e('Guardar Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Componentes -->
                <div class="flavor-tab-content" data-tab="components">
                    <h2><?php esc_html_e('Componentes Adicionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <p class="flavor-admin-description"><?php esc_html_e('Activa y configura componentes extra para mejorar la experiencia de usuario.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                    <form id="flavor-components-settings-form" class="flavor-settings-form">
                        <!-- Announcement Bar -->
                        <div class="flavor-settings-section">
                            <h3>
                                <span class="dashicons dashicons-megaphone"></span>
                                <?php esc_html_e('Barra de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="flavor-settings-row">
                                <label for="announcement_enabled"><?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="announcement_enabled" name="announcement_enabled" value="1" <?php checked($settings['announcement_enabled'] ?? false); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="announcement_message"><?php esc_html_e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="announcement_message" name="announcement_message" value="<?php echo esc_attr($settings['announcement_message'] ?? ''); ?>" placeholder="<?php esc_attr_e('¡Ofertas especiales esta semana!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="announcement_link"><?php esc_html_e('Enlace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="url" id="announcement_link" name="announcement_link" value="<?php echo esc_url($settings['announcement_link'] ?? ''); ?>" placeholder="https://">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="announcement_link_text"><?php esc_html_e('Texto del enlace', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="announcement_link_text" name="announcement_link_text" value="<?php echo esc_attr($settings['announcement_link_text'] ?? ''); ?>" placeholder="<?php esc_attr_e('Saber más', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="announcement_bg"><?php esc_html_e('Color de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="announcement_bg" name="announcement_bg" class="flavor-color-picker" value="<?php echo esc_attr($settings['announcement_bg'] ?? '#3b82f6'); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="announcement_dismissible"><?php esc_html_e('Permitir cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="announcement_dismissible" name="announcement_dismissible" value="1" <?php checked($settings['announcement_dismissible'] ?? true); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Dark Mode -->
                        <div class="flavor-settings-section">
                            <h3>
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <?php esc_html_e('Modo Oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="flavor-settings-row">
                                <label for="dark_mode_enabled"><?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="dark_mode_enabled" name="dark_mode_enabled" value="1" <?php checked($settings['dark_mode_enabled'] ?? false); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="dark_mode_auto"><?php esc_html_e('Detectar preferencia del sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="dark_mode_auto" name="dark_mode_auto" value="1" <?php checked($settings['dark_mode_auto'] ?? true); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="dark_bg_color"><?php esc_html_e('Color de fondo (oscuro)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="dark_bg_color" name="dark_bg_color" class="flavor-color-picker" value="<?php echo esc_attr($settings['dark_bg_color'] ?? '#0f172a'); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="dark_text_color"><?php esc_html_e('Color de texto (oscuro)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="dark_text_color" name="dark_text_color" class="flavor-color-picker" value="<?php echo esc_attr($settings['dark_text_color'] ?? '#f1f5f9'); ?>">
                            </div>
                        </div>

                        <!-- Breadcrumbs -->
                        <div class="flavor-settings-section">
                            <h3>
                                <span class="dashicons dashicons-carrot"></span>
                                <?php esc_html_e('Breadcrumbs (Migas de pan)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="flavor-settings-row">
                                <label for="breadcrumbs_enabled"><?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="breadcrumbs_enabled" name="breadcrumbs_enabled" value="1" <?php checked($settings['breadcrumbs_enabled'] ?? false); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="breadcrumbs_separator"><?php esc_html_e('Separador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="breadcrumbs_separator" name="breadcrumbs_separator">
                                    <option value="›" <?php selected($settings['breadcrumbs_separator'] ?? '›', '›'); ?>>› (chevron)</option>
                                    <option value="/" <?php selected($settings['breadcrumbs_separator'] ?? '', '/'); ?>>/ (slash)</option>
                                    <option value="»" <?php selected($settings['breadcrumbs_separator'] ?? '', '»'); ?>>» (guillemet)</option>
                                    <option value="→" <?php selected($settings['breadcrumbs_separator'] ?? '', '→'); ?>>→ (arrow)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Back to Top -->
                        <div class="flavor-settings-section">
                            <h3>
                                <span class="dashicons dashicons-arrow-up-alt"></span>
                                <?php esc_html_e('Botón Volver Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="flavor-settings-row">
                                <label for="back_to_top_enabled"><?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="back_to_top_enabled" name="back_to_top_enabled" value="1" <?php checked($settings['back_to_top_enabled'] ?? true); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="back_to_top_show_after"><?php esc_html_e('Mostrar después de (px)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="number" id="back_to_top_show_after" name="back_to_top_show_after" value="<?php echo intval($settings['back_to_top_show_after'] ?? 300); ?>" min="100" max="1000" step="50">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="back_to_top_position"><?php esc_html_e('Posición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="back_to_top_position" name="back_to_top_position">
                                    <option value="right" <?php selected($settings['back_to_top_position'] ?? 'right', 'right'); ?>><?php esc_html_e('Derecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="left" <?php selected($settings['back_to_top_position'] ?? '', 'left'); ?>><?php esc_html_e('Izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Sticky CTA -->
                        <div class="flavor-settings-section">
                            <h3>
                                <span class="dashicons dashicons-admin-comments"></span>
                                <?php esc_html_e('Botón CTA Flotante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="flavor-settings-row">
                                <label for="sticky_cta_enabled"><?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="sticky_cta_enabled" name="sticky_cta_enabled" value="1" <?php checked($settings['sticky_cta_enabled'] ?? false); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="sticky_cta_icon"><?php esc_html_e('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="sticky_cta_icon" name="sticky_cta_icon">
                                    <option value="whatsapp" <?php selected($settings['sticky_cta_icon'] ?? 'whatsapp', 'whatsapp'); ?>>WhatsApp</option>
                                    <option value="phone" <?php selected($settings['sticky_cta_icon'] ?? '', 'phone'); ?>><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="email" <?php selected($settings['sticky_cta_icon'] ?? '', 'email'); ?>>Email</option>
                                    <option value="chat" <?php selected($settings['sticky_cta_icon'] ?? '', 'chat'); ?>>Chat</option>
                                    <option value="calendar" <?php selected($settings['sticky_cta_icon'] ?? '', 'calendar'); ?>><?php esc_html_e('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                </select>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="sticky_cta_text"><?php esc_html_e('Texto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="text" id="sticky_cta_text" name="sticky_cta_text" value="<?php echo esc_attr($settings['sticky_cta_text'] ?? ''); ?>" placeholder="<?php esc_attr_e('¿Necesitas ayuda?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="sticky_cta_url"><?php esc_html_e('URL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <input type="url" id="sticky_cta_url" name="sticky_cta_url" value="<?php echo esc_url($settings['sticky_cta_url'] ?? ''); ?>" placeholder="https://wa.me/34600000000">
                            </div>
                            <div class="flavor-settings-row">
                                <label for="sticky_cta_position"><?php esc_html_e('Posición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="sticky_cta_position" name="sticky_cta_position">
                                    <option value="bottom-right" <?php selected($settings['sticky_cta_position'] ?? 'bottom-right', 'bottom-right'); ?>><?php esc_html_e('Abajo derecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="bottom-left" <?php selected($settings['sticky_cta_position'] ?? '', 'bottom-left'); ?>><?php esc_html_e('Abajo izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="bottom-center" <?php selected($settings['sticky_cta_position'] ?? '', 'bottom-center'); ?>><?php esc_html_e('Abajo centro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                </select>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="sticky_cta_mobile"><?php esc_html_e('Mostrar en móvil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="sticky_cta_mobile" name="sticky_cta_mobile" value="1" <?php checked($settings['sticky_cta_mobile'] ?? true); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                        </div>

                        <!-- Cookie Banner -->
                        <div class="flavor-settings-section">
                            <h3>
                                <span class="dashicons dashicons-shield"></span>
                                <?php esc_html_e('Banner de Cookies (GDPR)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </h3>
                            <div class="flavor-settings-row">
                                <label for="cookie_banner_enabled"><?php esc_html_e('Activar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <label class="flavor-toggle">
                                    <input type="checkbox" id="cookie_banner_enabled" name="cookie_banner_enabled" value="1" <?php checked($settings['cookie_banner_enabled'] ?? false); ?>>
                                    <span class="flavor-toggle__slider"></span>
                                </label>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="cookie_banner_message"><?php esc_html_e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <textarea id="cookie_banner_message" name="cookie_banner_message" rows="2"><?php echo esc_textarea($settings['cookie_banner_message'] ?? __('Utilizamos cookies para mejorar tu experiencia. Al continuar navegando, aceptas nuestra política de cookies.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></textarea>
                            </div>
                            <div class="flavor-settings-row">
                                <label for="cookie_banner_position"><?php esc_html_e('Posición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <select id="cookie_banner_position" name="cookie_banner_position">
                                    <option value="bottom" <?php selected($settings['cookie_banner_position'] ?? 'bottom', 'bottom'); ?>><?php esc_html_e('Abajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <option value="top" <?php selected($settings['cookie_banner_position'] ?? '', 'top'); ?>><?php esc_html_e('Arriba', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="flavor-settings-actions">
                            <button type="submit" class="button button-primary button-large">
                                <span class="dashicons dashicons-saved"></span>
                                <?php esc_html_e('Guardar Componentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Preview Modal -->
            <div id="flavor-preview-modal" class="flavor-modal" style="display: none;">
                <div class="flavor-modal__backdrop"></div>
                <div class="flavor-modal__content">
                    <div class="flavor-modal__header">
                        <h3 class="flavor-modal__title"><?php esc_html_e('Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <button type="button" class="flavor-modal__close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                    <div class="flavor-modal__body">
                        <div class="flavor-preview-frame">
                            <div class="flavor-preview-toolbar">
                                <button type="button" class="flavor-preview-device active" data-device="desktop">
                                    <span class="dashicons dashicons-desktop"></span>
                                </button>
                                <button type="button" class="flavor-preview-device" data-device="tablet">
                                    <span class="dashicons dashicons-tablet"></span>
                                </button>
                                <button type="button" class="flavor-preview-device" data-device="mobile">
                                    <span class="dashicons dashicons-smartphone"></span>
                                </button>
                            </div>
                            <div class="flavor-preview-container" data-device="desktop">
                                <iframe id="flavor-preview-iframe" src="about:blank"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Toast Notifications -->
            <div id="flavor-toast-container"></div>
        </div>
        <?php
    }

    /**
     * Renderizar preview de menú
     */
    private function render_menu_preview($menu_id, $size = 'normal') {
        $previews = [
            'classic' => '
                <div class="preview-menu preview-menu--classic">
                    <div class="preview-logo"></div>
                    <div class="preview-nav">
                        <span></span><span></span><span></span><span></span>
                    </div>
                    <div class="preview-cta"></div>
                </div>',
            'centered' => '
                <div class="preview-menu preview-menu--centered">
                    <div class="preview-topbar"><span></span><span></span></div>
                    <div class="preview-logo preview-logo--center"></div>
                    <div class="preview-nav preview-nav--center">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </div>',
            'sidebar' => '
                <div class="preview-menu preview-menu--sidebar">
                    <div class="preview-hamburger"><span></span><span></span><span></span></div>
                    <div class="preview-logo"></div>
                    <div class="preview-icons"><span></span><span></span></div>
                </div>
                <div class="preview-drawer">
                    <div class="preview-user"></div>
                    <div class="preview-nav-vertical">
                        <span></span><span></span><span></span><span></span><span></span>
                    </div>
                </div>',
            'bottom-nav' => '
                <div class="preview-menu preview-menu--minimal-top">
                    <div class="preview-logo"></div>
                    <div class="preview-icons"><span></span></div>
                </div>
                <div class="preview-bottom-nav">
                    <span><i></i><small></small></span>
                    <span><i></i><small></small></span>
                    <span class="active"><i></i><small></small></span>
                    <span><i></i><small></small></span>
                    <span><i></i><small></small></span>
                </div>',
            'mega-menu' => '
                <div class="preview-menu preview-menu--mega">
                    <div class="preview-logo"></div>
                    <div class="preview-nav">
                        <span class="has-dropdown"></span><span class="has-dropdown"></span><span></span><span></span>
                    </div>
                    <div class="preview-cta"></div>
                </div>
                <div class="preview-mega-dropdown">
                    <div class="preview-mega-col"><span></span><span></span><span></span></div>
                    <div class="preview-mega-col"><span></span><span></span><span></span></div>
                    <div class="preview-mega-col"><span></span><span></span></div>
                    <div class="preview-mega-featured"></div>
                </div>',
            'minimal' => '
                <div class="preview-menu preview-menu--minimal">
                    <div class="preview-logo"></div>
                    <div class="preview-hamburger preview-hamburger--animated">
                        <span></span><span></span><span></span>
                    </div>
                </div>',
        ];

        $preview_html = $previews[$menu_id] ?? $previews['classic'];
        echo '<div class="flavor-preview flavor-preview--' . esc_attr($size) . ' flavor-preview--menu">' . $preview_html . '</div>';
    }

    /**
     * Renderizar preview de footer
     */
    private function render_footer_preview($footer_id, $size = 'normal') {
        $previews = [
            'multi-column' => '
                <div class="preview-footer preview-footer--multi">
                    <div class="preview-footer-main">
                        <div class="preview-brand">
                            <div class="preview-logo"></div>
                            <div class="preview-text"></div>
                            <div class="preview-social"><span></span><span></span><span></span></div>
                        </div>
                        <div class="preview-columns">
                            <div class="preview-col"><span></span><span></span><span></span><span></span></div>
                            <div class="preview-col"><span></span><span></span><span></span></div>
                            <div class="preview-col"><span></span><span></span><span></span><span></span></div>
                        </div>
                    </div>
                    <div class="preview-footer-bottom">
                        <span></span><span></span>
                    </div>
                </div>',
            'compact' => '
                <div class="preview-footer preview-footer--compact">
                    <span class="preview-copyright"></span>
                    <span class="preview-links"><span></span><span></span><span></span></span>
                    <span class="preview-social"><span></span><span></span><span></span></span>
                </div>',
            'newsletter' => '
                <div class="preview-footer preview-footer--newsletter">
                    <div class="preview-newsletter">
                        <div class="preview-newsletter-text"></div>
                        <div class="preview-newsletter-form">
                            <span class="preview-input"></span>
                            <span class="preview-button"></span>
                        </div>
                    </div>
                    <div class="preview-footer-main">
                        <div class="preview-brand">
                            <div class="preview-logo"></div>
                        </div>
                        <div class="preview-columns">
                            <div class="preview-col"><span></span><span></span><span></span></div>
                            <div class="preview-col"><span></span><span></span><span></span></div>
                        </div>
                    </div>
                </div>',
            'contact' => '
                <div class="preview-footer preview-footer--contact">
                    <div class="preview-map"></div>
                    <div class="preview-contact-cards">
                        <div class="preview-card"><span class="icon"></span><span></span></div>
                        <div class="preview-card"><span class="icon"></span><span></span></div>
                        <div class="preview-card"><span class="icon"></span><span></span></div>
                        <div class="preview-card"><span class="icon"></span><span></span></div>
                    </div>
                </div>',
            'app-download' => '
                <div class="preview-footer preview-footer--app">
                    <div class="preview-app-promo">
                        <div class="preview-app-content">
                            <div class="preview-text"></div>
                            <div class="preview-features"><span></span><span></span><span></span></div>
                            <div class="preview-app-buttons">
                                <span class="preview-store-btn"></span>
                                <span class="preview-store-btn"></span>
                            </div>
                        </div>
                        <div class="preview-qr"></div>
                    </div>
                    <div class="preview-footer-bottom">
                        <span class="preview-logo"></span>
                        <span class="preview-social"><span></span><span></span><span></span></span>
                    </div>
                </div>',
        ];

        $preview_html = $previews[$footer_id] ?? $previews['multi-column'];
        echo '<div class="flavor-preview flavor-preview--' . esc_attr($size) . ' flavor-preview--footer">' . $preview_html . '</div>';
    }

    /**
     * AJAX: Guardar layout seleccionado
     */
    public function ajax_save_layout() {
        check_ajax_referer('flavor_layout_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $type = sanitize_key($_POST['type'] ?? '');
        $id = sanitize_key($_POST['id'] ?? '');

        if (empty($type) || empty($id)) {
            wp_send_json_error(['message' => __('Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $settings = get_option('flavor_layout_settings', []);

        if ($type === 'menu') {
            $settings['active_menu'] = $id;
        } elseif ($type === 'footer') {
            $settings['active_footer'] = $id;
        }

        update_option('flavor_layout_settings', $settings);

        wp_send_json_success([
            'message' => __('Layout actualizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'active_menu' => $settings['active_menu'] ?? 'classic',
            'active_footer' => $settings['active_footer'] ?? 'multi-column',
        ]);
    }

    /**
     * AJAX: Guardar ajustes
     */
    public function ajax_save_layout_settings() {
        check_ajax_referer('flavor_layout_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $settings = get_option('flavor_layout_settings', []);

        // Campos de texto básicos
        $text_fields = [
            'cta_text', 'cta_url', 'contact_phone', 'contact_email', 'contact_address',
            'business_hours', 'map_embed_url', 'app_store_url', 'play_store_url', 'copyright_text',
            // Componentes - Announcement Bar
            'announcement_message', 'announcement_link', 'announcement_link_text', 'announcement_bg',
            // Componentes - Dark Mode
            'dark_bg_color', 'dark_text_color', 'dark_surface_color', 'dark_text_secondary', 'dark_border_color',
            // Componentes - Breadcrumbs
            'breadcrumbs_separator',
            // Componentes - Back to Top
            'back_to_top_position',
            // Componentes - Sticky CTA
            'sticky_cta_icon', 'sticky_cta_text', 'sticky_cta_url', 'sticky_cta_position',
            // Componentes - Cookie Banner
            'cookie_banner_message', 'cookie_banner_position', 'cookie_privacy_url',
        ];

        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $settings[$field] = sanitize_text_field($_POST[$field]);
            }
        }

        // Campos numéricos
        $numeric_fields = ['back_to_top_show_after'];
        foreach ($numeric_fields as $field) {
            if (isset($_POST[$field])) {
                $settings[$field] = intval($_POST[$field]);
            }
        }

        // Campos booleanos (checkboxes)
        $boolean_fields = [
            'announcement_enabled', 'announcement_dismissible',
            'dark_mode_enabled', 'dark_mode_auto',
            'breadcrumbs_enabled',
            'back_to_top_enabled',
            'sticky_cta_enabled', 'sticky_cta_mobile',
            'cookie_banner_enabled',
        ];

        foreach ($boolean_fields as $field) {
            $settings[$field] = isset($_POST[$field]) && $_POST[$field] === '1';
        }

        // Redes sociales
        if (isset($_POST['social_links']) && is_array($_POST['social_links'])) {
            $settings['social_links'] = array_map('esc_url_raw', $_POST['social_links']);
        }

        // Patrocinadores por footer
        if (isset($_POST['footer_sponsors']) && is_array($_POST['footer_sponsors'])) {
            $settings['footer_settings'] = $settings['footer_settings'] ?? [];
            foreach ($_POST['footer_sponsors'] as $footer_id => $sponsors) {
                $footer_id = sanitize_key($footer_id);
                $sanitized = [];
                if (is_array($sponsors)) {
                    foreach ($sponsors as $sponsor) {
                        if (!is_array($sponsor)) {
                            continue;
                        }
                        $name = sanitize_text_field($sponsor['name'] ?? '');
                        $url = esc_url_raw($sponsor['url'] ?? '');
                        $logo = esc_url_raw($sponsor['logo'] ?? '');
                        if ($name === '' && $url === '' && $logo === '') {
                            continue;
                        }
                        $sanitized[] = [
                            'name' => $name,
                            'url' => $url,
                            'logo' => $logo,
                        ];
                    }
                }
                $settings['footer_settings'][$footer_id]['sponsors'] = $sanitized;
            }
        }

        // Ajustes por menú
        if (isset($_POST['menu_settings']) && is_array($_POST['menu_settings'])) {
            $settings['menu_settings'] = $settings['menu_settings'] ?? [];
            $allowed_styles = ['light', 'dark', 'transparent'];
            $allowed_actions = ['search', 'user', 'cta'];

            foreach ($_POST['menu_settings'] as $menu_id => $menu_cfg) {
                $menu_id = sanitize_key($menu_id);
                if (!is_array($menu_cfg)) {
                    continue;
                }

                $sticky = isset($menu_cfg['sticky']) && $menu_cfg['sticky'] === '1';
                $menu_state = sanitize_key($menu_cfg['menu_state'] ?? 'expanded');
                if (!in_array($menu_state, ['expanded', 'collapsed'], true)) {
                    $menu_state = 'expanded';
                }

                $style_normal = sanitize_key($menu_cfg['style_normal'] ?? 'light');
                $style_sticky = sanitize_key($menu_cfg['style_sticky'] ?? 'light');
                if (!in_array($style_normal, $allowed_styles, true)) {
                    $style_normal = 'light';
                }
                if (!in_array($style_sticky, $allowed_styles, true)) {
                    $style_sticky = 'light';
                }

                $actions_order = [];
                if (isset($menu_cfg['actions_order']) && is_array($menu_cfg['actions_order'])) {
                    foreach ($menu_cfg['actions_order'] as $action) {
                        $action = sanitize_key($action);
                        if (in_array($action, $allowed_actions, true)) {
                            $actions_order[] = $action;
                        }
                    }
                }
                $actions_order = array_values(array_unique($actions_order));

                $settings['menu_settings'][$menu_id] = [
                    'sticky' => $sticky,
                    'menu_state' => $menu_state,
                    'style_normal' => $style_normal,
                    'style_sticky' => $style_sticky,
                    'actions_order' => $actions_order,
                ];
            }
        }

        update_option('flavor_layout_settings', $settings);

        wp_send_json_success(['message' => __('Ajustes guardados', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Exportar configuración para móvil
     */
    public function ajax_export_mobile_config() {
        check_ajax_referer('flavor_layout_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $registry = flavor_layout_registry();
        $config = $registry->export_for_mobile();

        // Guardar en archivo
        $upload_dir = wp_upload_dir();
        $config_dir = $upload_dir['basedir'] . '/flavor-chat-ia/mobile-config';

        if (!file_exists($config_dir)) {
            wp_mkdir_p($config_dir);
        }

        $filename = 'layout-config-' . date('Y-m-d-His') . '.json';
        $filepath = $config_dir . '/' . $filename;

        file_put_contents($filepath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // También guardar como latest
        file_put_contents($config_dir . '/layout-config-latest.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        wp_send_json_success([
            'message' => __('Configuración exportada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'filename' => $filename,
            'url' => $upload_dir['baseurl'] . '/flavor-chat-ia/mobile-config/' . $filename,
            'config' => $config,
        ]);
    }

    /**
     * AJAX: Obtener preview de layout
     */
    public function ajax_get_layout_preview() {
        check_ajax_referer('flavor_layout_nonce', 'nonce');

        $type = sanitize_key($_POST['type'] ?? '');
        $id = sanitize_key($_POST['id'] ?? '');

        if (empty($type) || empty($id)) {
            wp_send_json_error(['message' => __('Datos inválidos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Generar URL de preview
        $preview_url = add_query_arg([
            'flavor_layout_preview' => 1,
            'layout_type' => $type,
            'layout_id' => $id,
        ], home_url('/'));

        wp_send_json_success(['preview_url' => $preview_url]);
    }
}

// Inicializar
Flavor_Layout_Admin::get_instance();
