<?php
/**
 * Panel de Administración de Licencias
 *
 * Interfaz para gestionar la licencia de Flavor Platform
 *
 * @package FlavorChatIA
 * @subpackage Admin
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del panel de administración de licencias
 *
 * @since 3.2.0
 */
class Flavor_License_Admin {

    /**
     * Instancia singleton
     *
     * @var Flavor_License_Admin
     */
    private static $instance = null;

    /**
     * Slug de la página
     *
     * @var string
     */
    private $page_slug = 'flavor-license';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_License_Admin
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
        add_action('admin_menu', [$this, 'register_menu'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Registra el menú
     *
     * @return void
     */
    public function register_menu() {
        add_submenu_page(
            'flavor-dashboard',
            __('Licencia', 'flavor-chat-ia'),
            __('Licencia', 'flavor-chat-ia'),
            'manage_options',
            $this->page_slug,
            [$this, 'render_page']
        );
    }

    /**
     * Carga assets
     *
     * @param string $hook Hook actual
     * @return void
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, $this->page_slug) === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-license-admin',
            FLAVOR_CHAT_IA_URL . 'admin/css/license-admin.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-license-admin',
            FLAVOR_CHAT_IA_URL . 'admin/js/license-admin.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-license-admin', 'flavorLicense', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_license_nonce'),
            'i18n'    => [
                'activating'   => __('Activando...', 'flavor-chat-ia'),
                'deactivating' => __('Desactivando...', 'flavor-chat-ia'),
                'verifying'    => __('Verificando...', 'flavor-chat-ia'),
                'error'        => __('Error', 'flavor-chat-ia'),
                'success'      => __('Correcto', 'flavor-chat-ia'),
                'confirmDeactivate' => __('¿Seguro que quieres desactivar la licencia? Perderás acceso a los módulos premium.', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Renderiza la página
     *
     * @return void
     */
    public function render_page() {
        $license_manager = Flavor_License_Manager::get_instance();
        $plans_manager = Flavor_License_Plans::get_instance();

        $has_license = $license_manager->has_license();
        $is_active = $license_manager->is_license_active();
        $license_data = $license_manager->get_license_data();
        $current_plan = $license_manager->get_current_plan();
        $days_remaining = $license_manager->get_days_remaining();
        $plans = $plans_manager->get_purchasable_plans();
        ?>
        <div class="wrap flavor-license-wrap">
            <h1><?php esc_html_e('Licencia de Flavor Platform', 'flavor-chat-ia'); ?></h1>

            <div class="flavor-license-container">
                <!-- Panel principal de licencia -->
                <div class="flavor-license-main">
                    <?php if ($has_license && $is_active): ?>
                        <?php $this->render_active_license($license_data, $current_plan, $days_remaining, $plans_manager); ?>
                    <?php elseif ($has_license && !$is_active): ?>
                        <?php $this->render_expired_license($license_data); ?>
                    <?php else: ?>
                        <?php $this->render_no_license(); ?>
                    <?php endif; ?>
                </div>

                <!-- Sidebar con planes -->
                <div class="flavor-license-sidebar">
                    <h2><?php esc_html_e('Planes Disponibles', 'flavor-chat-ia'); ?></h2>
                    <?php $this->render_plans_list($plans, $current_plan); ?>
                </div>
            </div>

            <!-- Módulos por plan -->
            <div class="flavor-license-modules">
                <h2><?php esc_html_e('Módulos incluidos por plan', 'flavor-chat-ia'); ?></h2>
                <?php $this->render_modules_comparison($plans_manager); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza licencia activa
     *
     * @param array $license_data Datos de licencia
     * @param string $current_plan Plan actual
     * @param int|null $days_remaining Días restantes
     * @param Flavor_License_Plans $plans_manager Gestor de planes
     * @return void
     */
    private function render_active_license($license_data, $current_plan, $days_remaining, $plans_manager) {
        $plan_info = $plans_manager->get_plan($current_plan);
        $plan_color = $plan_info['color'] ?? '#3b82f6';
        ?>
        <div class="flavor-license-card active" style="--plan-color: <?php echo esc_attr($plan_color); ?>">
            <div class="license-status">
                <span class="status-badge active">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 12l2 2 4-4"/>
                        <circle cx="12" cy="12" r="10"/>
                    </svg>
                    <?php esc_html_e('Licencia Activa', 'flavor-chat-ia'); ?>
                </span>
            </div>

            <div class="license-plan">
                <h3><?php echo esc_html($plan_info['name']); ?></h3>
                <p><?php echo esc_html($plan_info['description']); ?></p>
            </div>

            <div class="license-details">
                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Clave de licencia:', 'flavor-chat-ia'); ?></span>
                    <span class="value license-key">
                        <?php echo esc_html($this->mask_license_key($license_data['key'])); ?>
                    </span>
                </div>

                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Sitios permitidos:', 'flavor-chat-ia'); ?></span>
                    <span class="value">
                        <?php
                        if ($license_data['sites_allowed'] === -1) {
                            esc_html_e('Ilimitados', 'flavor-chat-ia');
                        } else {
                            echo esc_html($license_data['sites_active'] . ' / ' . $license_data['sites_allowed']);
                        }
                        ?>
                    </span>
                </div>

                <?php if ($days_remaining !== null): ?>
                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Expira en:', 'flavor-chat-ia'); ?></span>
                    <span class="value <?php echo $days_remaining <= 30 ? 'warning' : ''; ?>">
                        <?php echo sprintf(_n('%d día', '%d días', $days_remaining, 'flavor-chat-ia'), $days_remaining); ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (!empty($license_data['customer_email'])): ?>
                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Email:', 'flavor-chat-ia'); ?></span>
                    <span class="value"><?php echo esc_html($license_data['customer_email']); ?></span>
                </div>
                <?php endif; ?>

                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Última verificación:', 'flavor-chat-ia'); ?></span>
                    <span class="value">
                        <?php echo esc_html(
                            human_time_diff(strtotime($license_data['last_verified']), current_time('timestamp'))
                            . ' ' . __('atrás', 'flavor-chat-ia')
                        ); ?>
                    </span>
                </div>
            </div>

            <div class="license-actions">
                <button type="button" class="button" id="flavor-verify-license">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        <path d="M9 12l2 2 4-4"/>
                    </svg>
                    <?php esc_html_e('Verificar', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button button-link-delete" id="flavor-deactivate-license">
                    <?php esc_html_e('Desactivar licencia', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza licencia expirada
     *
     * @param array $license_data Datos de licencia
     * @return void
     */
    private function render_expired_license($license_data) {
        ?>
        <div class="flavor-license-card expired">
            <div class="license-status">
                <span class="status-badge expired">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4M12 16h.01"/>
                    </svg>
                    <?php esc_html_e('Licencia Expirada', 'flavor-chat-ia'); ?>
                </span>
            </div>

            <div class="license-message">
                <p><?php esc_html_e('Tu licencia ha expirado. Renuévala para seguir utilizando los módulos premium.', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="license-details">
                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Clave de licencia:', 'flavor-chat-ia'); ?></span>
                    <span class="value license-key">
                        <?php echo esc_html($this->mask_license_key($license_data['key'])); ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="label"><?php esc_html_e('Expiró:', 'flavor-chat-ia'); ?></span>
                    <span class="value">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license_data['expires_at']))); ?>
                    </span>
                </div>
            </div>

            <div class="license-actions">
                <a href="https://gailu.net/renovar/?license=<?php echo esc_attr($license_data['key']); ?>"
                   class="button button-primary" target="_blank">
                    <?php esc_html_e('Renovar licencia', 'flavor-chat-ia'); ?>
                </a>
                <button type="button" class="button button-link-delete" id="flavor-deactivate-license">
                    <?php esc_html_e('Eliminar licencia', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza formulario sin licencia
     *
     * @return void
     */
    private function render_no_license() {
        ?>
        <div class="flavor-license-card no-license">
            <div class="license-status">
                <span class="status-badge inactive">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0110 0v4"/>
                    </svg>
                    <?php esc_html_e('Sin Licencia', 'flavor-chat-ia'); ?>
                </span>
            </div>

            <div class="license-message">
                <h3><?php esc_html_e('Activa tu licencia', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Introduce tu clave de licencia para desbloquear todos los módulos premium de Flavor Platform.', 'flavor-chat-ia'); ?></p>
            </div>

            <form id="flavor-activate-license-form" class="license-form">
                <div class="form-field">
                    <label for="license-key"><?php esc_html_e('Clave de licencia', 'flavor-chat-ia'); ?></label>
                    <input type="text"
                           id="license-key"
                           name="license_key"
                           placeholder="XXXX-XXXX-XXXX-XXXX"
                           pattern="[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}"
                           required>
                    <span class="description">
                        <?php esc_html_e('Tu clave tiene el formato XXXX-XXXX-XXXX-XXXX', 'flavor-chat-ia'); ?>
                    </span>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-hero" id="flavor-activate-license">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                            <circle cx="12" cy="16" r="1"/>
                        </svg>
                        <?php esc_html_e('Activar Licencia', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <div id="license-message" class="license-feedback" style="display: none;"></div>
            </form>

            <div class="license-help">
                <p>
                    <?php esc_html_e('¿No tienes licencia?', 'flavor-chat-ia'); ?>
                    <a href="https://gailu.net/planes/" target="_blank">
                        <?php esc_html_e('Adquiere una aquí', 'flavor-chat-ia'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza lista de planes
     *
     * @param array $plans Planes disponibles
     * @param string $current_plan Plan actual
     * @return void
     */
    private function render_plans_list($plans, $current_plan) {
        ?>
        <div class="flavor-plans-list">
            <?php foreach ($plans as $slug => $plan): ?>
                <div class="plan-card <?php echo $slug === $current_plan ? 'current' : ''; ?>"
                     style="--plan-color: <?php echo esc_attr($plan['color']); ?>">

                    <?php if ($slug === $current_plan): ?>
                        <span class="plan-badge"><?php esc_html_e('Tu plan', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>

                    <h3 class="plan-name"><?php echo esc_html($plan['name']); ?></h3>

                    <div class="plan-price">
                        <span class="price"><?php echo esc_html($plan['price']); ?>€</span>
                        <span class="period">/<?php esc_html_e('año', 'flavor-chat-ia'); ?></span>
                    </div>

                    <div class="plan-sites">
                        <?php
                        if ($plan['sites'] === -1) {
                            esc_html_e('Sitios ilimitados', 'flavor-chat-ia');
                        } else {
                            echo sprintf(_n('%d sitio', '%d sitios', $plan['sites'], 'flavor-chat-ia'), $plan['sites']);
                        }
                        ?>
                    </div>

                    <ul class="plan-features">
                        <?php foreach ($plan['features'] as $feature): ?>
                            <li>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M9 12l2 2 4-4"/>
                                </svg>
                                <?php echo esc_html($feature); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($slug !== $current_plan): ?>
                        <a href="https://gailu.net/planes/<?php echo esc_attr($slug); ?>/"
                           class="button button-primary plan-cta"
                           target="_blank">
                            <?php esc_html_e('Obtener plan', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza comparativa de módulos por plan
     *
     * @param Flavor_License_Plans $plans_manager Gestor de planes
     * @return void
     */
    private function render_modules_comparison($plans_manager) {
        $plans = $plans_manager->get_plans();
        $base_modules = $plans_manager->get_base_modules();
        $module_loader = Flavor_Chat_Module_Loader::get_instance();
        $all_modules = $module_loader->get_available_modules();

        // Agrupar módulos por categoría
        $categories = [
            'comunicacion' => __('Comunicación', 'flavor-chat-ia'),
            'organizacion' => __('Organización', 'flavor-chat-ia'),
            'comunidad'    => __('Comunidad', 'flavor-chat-ia'),
            'economia'     => __('Economía', 'flavor-chat-ia'),
            'formacion'    => __('Formación', 'flavor-chat-ia'),
            'otros'        => __('Otros', 'flavor-chat-ia'),
        ];
        ?>
        <div class="modules-comparison">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                        <th class="plan-col"><?php esc_html_e('Free', 'flavor-chat-ia'); ?></th>
                        <th class="plan-col"><?php esc_html_e('Starter', 'flavor-chat-ia'); ?></th>
                        <th class="plan-col"><?php esc_html_e('Professional', 'flavor-chat-ia'); ?></th>
                        <th class="plan-col"><?php esc_html_e('Agency', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $shown_modules = [];
                    foreach (array_keys($plans) as $plan_slug) {
                        $plan_modules = $plans_manager->get_plan_modules($plan_slug);
                        foreach ($plan_modules as $module) {
                            $shown_modules[$module] = true;
                        }
                    }

                    foreach (array_keys($shown_modules) as $module_slug):
                        $module_info = $all_modules[$module_slug] ?? null;
                        $module_name = $module_info['name'] ?? ucfirst(str_replace('_', ' ', $module_slug));
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($module_name); ?></strong>
                            </td>
                            <?php foreach (array_keys($plans) as $plan_slug): ?>
                                <td class="plan-col">
                                    <?php if ($plans_manager->is_module_in_plan($module_slug, $plan_slug)): ?>
                                        <span class="check" title="<?php esc_attr_e('Incluido', 'flavor-chat-ia'); ?>">
                                            <svg viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="3">
                                                <path d="M9 12l2 2 4-4"/>
                                            </svg>
                                        </span>
                                    <?php else: ?>
                                        <span class="no-check" title="<?php esc_attr_e('No incluido', 'flavor-chat-ia'); ?>">—</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Enmascara una clave de licencia para mostrar
     *
     * @param string $key Clave de licencia
     * @return string
     */
    private function mask_license_key($key) {
        $parts = explode('-', $key);
        if (count($parts) !== 4) {
            return $key;
        }

        return $parts[0] . '-****-****-' . $parts[3];
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Flavor_License_Admin::get_instance();
    }
});
