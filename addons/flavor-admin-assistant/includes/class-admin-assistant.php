<?php
/**
 * Asistente IA para Administradores
 *
 * Proporciona un chat con IA para ayudar a gestionar el plugin
 * Calendario Experiencias desde el panel de administración
 *
 * @package ChatIAAddon
 */

if (!defined('ABSPATH')) {
    exit;
}

// Evitar redeclaración si ya existe
if (class_exists('Chat_IA_Admin_Assistant')) {
    return;
}

class Chat_IA_Admin_Assistant {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Herramientas disponibles
     */
    private $tools = null;

    /**
     * Sistema de atajos
     */
    private $shortcuts = null;

    /**
     * Cache de analytics
     */
    private $analytics_cache = null;

    /**
     * Control de acceso por roles
     */
    private $role_access = null;

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
        // Cargar dependencias
        require_once dirname(__FILE__) . '/class-admin-backup.php';
        require_once dirname(__FILE__) . '/class-admin-assistant-tools.php';
        require_once dirname(__FILE__) . '/class-admin-shortcuts.php';
        require_once dirname(__FILE__) . '/class-admin-role-access.php';
        require_once dirname(__FILE__) . '/class-analytics-cache.php';

        // Inicializar componentes
        $this->tools = Chat_IA_Admin_Assistant_Tools::get_instance();
        $this->shortcuts = Chat_IA_Admin_Shortcuts::get_instance();
        $this->analytics_cache = Chat_IA_Analytics_Cache::get_instance();
        $this->role_access = Chat_IA_Admin_Role_Access::get_instance();

        // Conectar shortcuts con tools, cache y control de acceso
        $this->shortcuts->set_tools($this->tools);
        $this->shortcuts->set_analytics_cache($this->analytics_cache);
        $this->shortcuts->set_role_access($this->role_access);

        // Conectar tools con control de acceso
        $this->tools->set_role_access($this->role_access);

        add_action('admin_menu', [$this, 'add_admin_menu'], 99);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_chat_ia_admin_assistant_message', [$this, 'handle_message']);
        add_action('wp_ajax_chat_ia_admin_assistant_clear', [$this, 'handle_clear_history']);
    }

    /**
     * Añade menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'calendario_experiencias',
            __('Asistente IA', 'chat-ia-addon'),
            __('🤖 Asistente IA', 'chat-ia-addon'),
            'manage_options',
            'calendario-asistente-ia',
            [$this, 'render_assistant_page']
        );
    }

    /**
     * Carga assets en admin
     */
    public function enqueue_assets($hook) {
        // El hook puede ser: calendario_experiencias_page_calendario-asistente-ia
        if (strpos($hook, 'asistente-ia') === false && strpos($hook, 'asistente_ia') === false) {
            return;
        }

        // CSS principal
        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'chat-ia-admin-assistant',
            CHAT_IA_ADDON_URL . "assets/css/admin-assistant{$sufijo_asset}.css",
            [],
            CHAT_IA_ADDON_VERSION
        );

        // CSS de shortcuts
        wp_enqueue_style(
            'chat-ia-admin-shortcuts',
            CHAT_IA_ADDON_URL . "assets/css/admin-shortcuts{$sufijo_asset}.css",
            ['chat-ia-admin-assistant'],
            CHAT_IA_ADDON_VERSION
        );

        // Flatpickr para datepickers (CDN)
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
            [],
            '4.6.13'
        );
        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            [],
            '4.6.13',
            true
        );
        wp_enqueue_script(
            'flatpickr-es',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js',
            ['flatpickr'],
            '4.6.13',
            true
        );

        // JS principal
        wp_enqueue_script(
            'chat-ia-admin-assistant',
            CHAT_IA_ADDON_URL . "assets/js/admin-assistant{$sufijo_asset}.js",
            ['jquery'],
            CHAT_IA_ADDON_VERSION,
            true
        );

        // JS de shortcuts
        wp_enqueue_script(
            'chat-ia-admin-shortcuts',
            CHAT_IA_ADDON_URL . "assets/js/admin-shortcuts{$sufijo_asset}.js",
            ['jquery', 'chat-ia-admin-assistant', 'flatpickr'],
            CHAT_IA_ADDON_VERSION,
            true
        );

        // Datos para JS
        $estados = get_option('calendario_experiencias_estados', []);
        $estados_js = [];
        foreach ($estados as $slug => $estado) {
            $estados_js[] = [
                'slug' => $slug,
                'nombre' => $estado['nombre'] ?? $estado['title'] ?? $slug,
            ];
        }

        $tipos = get_option('calendario_experiencias_ticket_types', []);
        $tipos_js = [];
        foreach ($tipos as $slug => $tipo) {
            $tipos_js[] = [
                'slug' => $slug,
                'nombre' => $tipo['name'] ?? $slug,
                'precio' => floatval($tipo['precio'] ?? 0),
            ];
        }

        wp_localize_script('chat-ia-admin-assistant', 'chatIAAdminAssistant', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chat_ia_admin_assistant_nonce'),
            'strings' => [
                'sending' => __('Procesando...', 'chat-ia-addon'),
                'error' => __('Error al procesar mensaje', 'chat-ia-addon'),
                'placeholder' => __('Pregunta sobre reservas, disponibilidad, shortcodes...', 'chat-ia-addon'),
            ],
        ]);

        // Datos para shortcuts
        wp_localize_script('chat-ia-admin-shortcuts', 'chatIAEstados', $estados_js);
        wp_localize_script('chat-ia-admin-shortcuts', 'chatIATicketTypes', $tipos_js);
    }

    /**
     * Renderiza la página del asistente
     */
    public function render_assistant_page() {
        // Verificar si hay motor de IA configurado
        $engine_manager = null;
        $engine_configured = false;

        if (class_exists('Chat_IA_Engine_Manager')) {
            $engine_manager = Chat_IA_Engine_Manager::get_instance();
            $active_engine = $engine_manager->get_backend_engine();
            $engine_configured = $active_engine && $active_engine->is_configured();
        }

        ?>
        <div class="wrap chat-ia-admin-assistant-wrap">
            <h1>
                <span class="dashicons dashicons-format-chat"></span>
                <?php esc_html_e('Asistente IA - Calendario Experiencias', 'chat-ia-addon'); ?>
            </h1>

            <?php if (!$engine_configured): ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('Configura un proveedor de IA', 'chat-ia-addon'); ?></strong><br>
                    <?php esc_html_e('Para usar el asistente, configura una API key en', 'chat-ia-addon'); ?>
                    <a href="<?php echo admin_url('admin.php?page=chat-ia-addon-settings&tab=providers'); ?>">
                        <?php esc_html_e('Configuración > Proveedores IA', 'chat-ia-addon'); ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>

            <!-- Panel informativo colapsable -->
            <div class="admin-assistant-info-panel">
                <details>
                    <summary>
                        <span class="dashicons dashicons-info-outline"></span>
                        <?php esc_html_e('¿Qué puedo hacer con este asistente?', 'chat-ia-addon'); ?>
                        <span class="toggle-icon dashicons dashicons-arrow-down-alt2"></span>
                    </summary>
                    <div class="info-content">
                        <div class="capabilities-grid">
                            <div class="capability-group">
                                <h4><span class="dashicons dashicons-search"></span> <?php esc_html_e('Consultar', 'chat-ia-addon'); ?></h4>
                                <ul>
                                    <li><?php esc_html_e('Reservas por fecha o cliente', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Plazas disponibles', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Ingresos y estadísticas', 'chat-ia-addon'); ?></li>
                                </ul>
                            </div>
                            <div class="capability-group">
                                <h4><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Calendario', 'chat-ia-addon'); ?></h4>
                                <ul>
                                    <li><?php esc_html_e('Asignar estados a días/rangos', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Filtrar por día de semana', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Crear/editar estados', 'chat-ia-addon'); ?></li>
                                </ul>
                            </div>
                            <div class="capability-group">
                                <h4><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e('Tickets', 'chat-ia-addon'); ?></h4>
                                <ul>
                                    <li><?php esc_html_e('Crear tipos de ticket', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Modificar precios/plazas', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Gestionar límites por día', 'chat-ia-addon'); ?></li>
                                </ul>
                            </div>
                            <div class="capability-group">
                                <h4><span class="dashicons dashicons-backup"></span> <?php esc_html_e('Seguridad', 'chat-ia-addon'); ?></h4>
                                <ul>
                                    <li><?php esc_html_e('Backups automáticos', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Restaurar configuración', 'chat-ia-addon'); ?></li>
                                    <li><?php esc_html_e('Historial de cambios', 'chat-ia-addon'); ?></li>
                                </ul>
                            </div>
                        </div>
                        <div class="how-it-works">
                            <h4><span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('¿Cómo funciona?', 'chat-ia-addon'); ?></h4>
                            <ol>
                                <li><strong><?php esc_html_e('Pide lo que necesites', 'chat-ia-addon'); ?></strong> - <?php esc_html_e('en lenguaje natural', 'chat-ia-addon'); ?></li>
                                <li><strong><?php esc_html_e('Revisa el resumen', 'chat-ia-addon'); ?></strong> - <?php esc_html_e('el asistente te mostrará qué va a cambiar', 'chat-ia-addon'); ?></li>
                                <li><strong><?php esc_html_e('Confirma con "sí"', 'chat-ia-addon'); ?></strong> - <?php esc_html_e('solo entonces se aplican los cambios', 'chat-ia-addon'); ?></li>
                                <li><strong><?php esc_html_e('Refresca la página', 'chat-ia-addon'); ?></strong> - <?php esc_html_e('para ver los cambios en el admin', 'chat-ia-addon'); ?></li>
                            </ol>
                            <p class="safety-note">
                                <span class="dashicons dashicons-shield"></span>
                                <?php esc_html_e('Se crea un backup automático antes de cada cambio. Siempre puedes restaurar diciendo "restaura el backup [id]".', 'chat-ia-addon'); ?>
                            </p>
                        </div>
                    </div>
                </details>
            </div>

            <div class="admin-assistant-layout">
                <!-- Panel principal del chat -->
                <div class="admin-assistant-chat-panel">
                    <div class="admin-assistant-header">
                        <div class="assistant-info">
                            <div class="assistant-avatar">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="assistant-details">
                                <span class="assistant-name"><?php esc_html_e('Asistente de Calendario', 'chat-ia-addon'); ?></span>
                                <span class="assistant-status"><?php esc_html_e('Listo para ayudar', 'chat-ia-addon'); ?></span>
                            </div>
                        </div>
                        <button type="button" id="clear-chat" class="button button-secondary" title="<?php esc_attr_e('Limpiar conversación', 'chat-ia-addon'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>

                    <div class="admin-assistant-messages" id="assistant-messages">
                        <!-- Mensaje de bienvenida -->
                        <div class="assistant-message assistant-message-bot">
                            <div class="message-avatar">
                                <span class="dashicons dashicons-admin-generic"></span>
                            </div>
                            <div class="message-content">
                                <p><?php esc_html_e('Hola! Soy el asistente del Calendario de Experiencias. Puedo ayudarte con:', 'chat-ia-addon'); ?></p>
                                <ul>
                                    <li><?php esc_html_e('Consultar reservas, disponibilidad e ingresos', 'chat-ia-addon'); ?></li>
                                    <li><strong><?php esc_html_e('Gestionar el calendario', 'chat-ia-addon'); ?></strong><?php esc_html_e(': asignar estados a días o rangos', 'chat-ia-addon'); ?></li>
                                    <li><strong><?php esc_html_e('Crear/editar estados', 'chat-ia-addon'); ?></strong><?php esc_html_e(': añadir nuevos estados con colores', 'chat-ia-addon'); ?></li>
                                    <li><strong><?php esc_html_e('Gestionar tipos de ticket', 'chat-ia-addon'); ?></strong><?php esc_html_e(': crear, editar precios y plazas', 'chat-ia-addon'); ?></li>
                                    <li><strong><?php esc_html_e('Backups', 'chat-ia-addon'); ?></strong><?php esc_html_e(': crear, restaurar y ver historial de cambios', 'chat-ia-addon'); ?></li>
                                </ul>
                                <p><?php esc_html_e('Se crean backups automáticos antes de cada cambio. Pregunta lo que necesites!', 'chat-ia-addon'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="admin-assistant-input">
                        <form id="assistant-form">
                            <textarea
                                id="assistant-input"
                                placeholder="<?php esc_attr_e('Pregunta sobre reservas, disponibilidad, shortcodes...', 'chat-ia-addon'); ?>"
                                rows="1"
                            ></textarea>
                            <button type="submit" id="assistant-send" <?php echo !$engine_configured ? 'disabled' : ''; ?>>
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Panel lateral con acciones rápidas -->
                <div class="admin-assistant-sidebar">
                    <!-- Panel de Atajos Directos (Sin AI) -->
                    <?php $this->render_shortcuts_panel(); ?>

                    <div class="sidebar-section">
                        <h3><?php esc_html_e('Consultas con IA', 'chat-ia-addon'); ?></h3>
                        <div class="quick-actions">
                            <button type="button" class="quick-action" data-prompt="<?php esc_attr_e('Dame un resumen detallado de las reservas de hoy', 'chat-ia-addon'); ?>">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php esc_html_e('Analisis de hoy', 'chat-ia-addon'); ?>
                            </button>
                            <button type="button" class="quick-action" data-prompt="<?php esc_attr_e('Compara los ingresos de esta semana con la anterior', 'chat-ia-addon'); ?>">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php esc_html_e('Comparativa semanal', 'chat-ia-addon'); ?>
                            </button>
                            <button type="button" class="quick-action" data-prompt="<?php esc_attr_e('Hay alguna alerta o problema en el sistema', 'chat-ia-addon'); ?>">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Diagnostico', 'chat-ia-addon'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="sidebar-section">
                        <h3><?php esc_html_e('Ejemplos de acciones', 'chat-ia-addon'); ?></h3>
                        <ul class="examples-list">
                            <li><?php esc_html_e('Asigna el estado "abierto" a todos los sábados de febrero', 'chat-ia-addon'); ?></li>
                            <li><?php esc_html_e('Pon el estado "cerrado" del 24 al 26 de diciembre', 'chat-ia-addon'); ?></li>
                            <li><?php esc_html_e('Crea un nuevo estado "festivo" con color rojo', 'chat-ia-addon'); ?></li>
                            <li><?php esc_html_e('Cambia el precio del ticket "entrada-general" a 25€', 'chat-ia-addon'); ?></li>
                            <li><?php esc_html_e('Restaura el backup más reciente', 'chat-ia-addon'); ?></li>
                        </ul>
                    </div>

                    <!-- Panel de Monitoreo de Tokens -->
                    <?php $this->render_token_stats_panel(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el panel de atajos directos
     */
    private function render_shortcuts_panel() {
        $shortcuts_grouped = $this->shortcuts->get_shortcuts_grouped();

        // Obtener estados y tickets para los selectores
        $estados = get_option('calendario_experiencias_estados', []);
        $tipos = get_option('calendario_experiencias_ticket_types', []);

        // Obtener nivel de acceso del usuario
        $access_level = 'full';
        $access_label = '';
        if ($this->role_access) {
            $access_level = $this->role_access->get_current_access_level();
            $user_permissions = $this->role_access->get_user_permissions(wp_get_current_user());
            $access_label = $user_permissions['label'] ?? '';
        }
        ?>
        <div class="shortcuts-panel">
            <div class="shortcuts-panel-header">
                <h3>
                    <span class="dashicons dashicons-superhero"></span>
                    <?php esc_html_e('Atajos Directos', 'chat-ia-addon'); ?>
                </h3>
                <button type="button" class="shortcuts-panel-toggle" title="<?php esc_attr_e('Colapsar/Expandir', 'chat-ia-addon'); ?>">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
            </div>

            <?php if ($access_level !== 'full' && $access_label): ?>
            <div class="shortcuts-access-notice" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 8px 12px; margin-bottom: 10px; font-size: 12px;">
                <span class="dashicons dashicons-shield-alt" style="font-size: 14px; vertical-align: middle;"></span>
                <?php printf(
                    esc_html__('Acceso: %s - Algunas funciones pueden estar restringidas', 'chat-ia-addon'),
                    '<strong>' . esc_html($access_label) . '</strong>'
                ); ?>
            </div>
            <?php endif; ?>

            <div class="shortcuts-tabs">
                <button type="button" class="shortcuts-tab active" data-group="analytics">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Analytics', 'chat-ia-addon'); ?>
                </button>
                <button type="button" class="shortcuts-tab" data-group="calendario">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Calendario', 'chat-ia-addon'); ?>
                </button>
                <button type="button" class="shortcuts-tab" data-group="tickets">
                    <span class="dashicons dashicons-tickets-alt"></span>
                    <?php esc_html_e('Tickets', 'chat-ia-addon'); ?>
                </button>
                <button type="button" class="shortcuts-tab" data-group="backups">
                    <span class="dashicons dashicons-backup"></span>
                    <?php esc_html_e('Backups', 'chat-ia-addon'); ?>
                </button>
            </div>

            <div class="shortcuts-content">
                <!-- Analytics - Activo por defecto -->
                <div class="shortcut-group active" data-group="analytics">
                    <h4 class="shortcut-group-title">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php esc_html_e('Resumenes rapidos', 'chat-ia-addon'); ?>
                    </h4>

                    <!-- Quick Stats -->
                    <?php $this->render_quick_stats(); ?>

                    <div class="shortcut-buttons">
                        <button type="button" class="shortcut-btn" data-shortcut="summary_today">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php esc_html_e('Resumen hoy', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="summary_week">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php esc_html_e('Resumen semana', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="comparison_yesterday">
                            <span class="dashicons dashicons-chart-pie"></span>
                            <?php esc_html_e('Vs. ayer', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="comparison_week">
                            <span class="dashicons dashicons-chart-area"></span>
                            <?php esc_html_e('Vs. semana ant.', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="alerts">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('Ver alertas', 'chat-ia-addon'); ?>
                        </button>
                    </div>
                </div>

                <!-- Calendario -->
                <div class="shortcut-group" data-group="calendario">
                    <h4 class="shortcut-group-title">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Gestion del calendario', 'chat-ia-addon'); ?>
                    </h4>

                    <div class="shortcut-controls">
                        <input type="text" class="shortcut-datepicker" placeholder="<?php esc_attr_e('Fecha', 'chat-ia-addon'); ?>">
                        <select class="shortcut-state-select">
                            <option value=""><?php esc_html_e('Estado...', 'chat-ia-addon'); ?></option>
                            <?php foreach ($estados as $slug => $estado): ?>
                                <option value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html($estado['nombre'] ?? $estado['title'] ?? $slug); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="date-state-preview"></div>

                    <div class="shortcut-buttons">
                        <button type="button" class="shortcut-btn primary" data-shortcut="set_day_open">
                            <span class="dashicons dashicons-unlock"></span>
                            <?php esc_html_e('Abrir dia', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn warning" data-shortcut="set_day_closed">
                            <span class="dashicons dashicons-lock"></span>
                            <?php esc_html_e('Cerrar dia', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn needs-params" data-shortcut="set_range_state" data-fields="fecha_inicio,fecha_fin,estado">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php esc_html_e('Estado a rango', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn needs-params" data-shortcut="import_states" data-fields="texto">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Importar', 'chat-ia-addon'); ?>
                        </button>
                    </div>
                </div>

                <!-- Tickets -->
                <div class="shortcut-group" data-group="tickets">
                    <h4 class="shortcut-group-title">
                        <span class="dashicons dashicons-tickets-alt"></span>
                        <?php esc_html_e('Gestion de tickets', 'chat-ia-addon'); ?>
                    </h4>

                    <div class="shortcut-controls">
                        <select class="shortcut-ticket-select">
                            <option value=""><?php esc_html_e('Ticket...', 'chat-ia-addon'); ?></option>
                            <?php foreach ($tipos as $slug => $tipo): ?>
                                <option value="<?php echo esc_attr($slug); ?>">
                                    <?php echo esc_html(($tipo['name'] ?? $slug) . ' (' . ($tipo['precio'] ?? 0) . '€)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="number" class="shortcut-input" name="precio" placeholder="<?php esc_attr_e('Precio', 'chat-ia-addon'); ?>" step="0.01" min="0">
                    </div>

                    <div class="shortcut-buttons">
                        <button type="button" class="shortcut-btn primary" data-shortcut="update_price">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Cambiar precio', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn needs-params" data-shortcut="quick_ticket" data-fields="nombre,precio,plazas">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Crear ticket', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="available_today">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Plazas hoy', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="available_tomorrow">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Plazas manana', 'chat-ia-addon'); ?>
                        </button>
                    </div>
                </div>

                <!-- Backups -->
                <div class="shortcut-group" data-group="backups">
                    <h4 class="shortcut-group-title">
                        <span class="dashicons dashicons-backup"></span>
                        <?php esc_html_e('Sistema de backups', 'chat-ia-addon'); ?>
                    </h4>

                    <div class="shortcut-buttons">
                        <button type="button" class="shortcut-btn success" data-shortcut="create_backup">
                            <span class="dashicons dashicons-backup"></span>
                            <?php esc_html_e('Crear backup', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="list_backups">
                            <span class="dashicons dashicons-archive"></span>
                            <?php esc_html_e('Ver backups', 'chat-ia-addon'); ?>
                        </button>
                    </div>

                    <!-- Diagnóstico -->
                    <div class="shortcut-diagnostics" style="margin-top: 15px; padding-top: 10px; border-top: 1px dashed #ddd;">
                        <button type="button" class="shortcut-btn" data-shortcut="test_ping" style="width: 100%; background: #f0f0f1; border-color: #8c8f94;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Test conexión', 'chat-ia-addon'); ?>
                        </button>
                    </div>
                </div>

                <!-- Shortcodes -->
                <div class="shortcut-group" data-group="shortcodes" style="display: none;">
                    <h4 class="shortcut-group-title">
                        <span class="dashicons dashicons-shortcode"></span>
                        <?php esc_html_e('Generador de shortcodes', 'chat-ia-addon'); ?>
                    </h4>

                    <div class="shortcut-buttons">
                        <button type="button" class="shortcut-btn" data-shortcut="gen_shortcode_calendar">
                            <span class="dashicons dashicons-calendar"></span>
                            <?php esc_html_e('Calendario', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="gen_shortcode_tickets">
                            <span class="dashicons dashicons-tickets-alt"></span>
                            <?php esc_html_e('Tickets', 'chat-ia-addon'); ?>
                        </button>
                        <button type="button" class="shortcut-btn" data-shortcut="gen_shortcode_cart">
                            <span class="dashicons dashicons-cart"></span>
                            <?php esc_html_e('Carrito', 'chat-ia-addon'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza mini KPIs rapidos
     */
    private function render_quick_stats() {
        // Obtener datos cacheados
        $dashboard = $this->analytics_cache->get_cached_dashboard();
        $kpis_hoy = $dashboard['kpis']['hoy'] ?? [];
        ?>
        <div class="quick-stats">
            <div class="quick-stat">
                <div class="quick-stat-value"><?php echo intval($kpis_hoy['reservas'] ?? 0); ?></div>
                <div class="quick-stat-label"><?php esc_html_e('Hoy', 'chat-ia-addon'); ?></div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-value"><?php echo intval($kpis_hoy['checkins'] ?? 0); ?></div>
                <div class="quick-stat-label"><?php esc_html_e('Check-ins', 'chat-ia-addon'); ?></div>
            </div>
            <div class="quick-stat">
                <div class="quick-stat-value"><?php echo number_format($kpis_hoy['ingresos'] ?? 0, 0, ',', '.'); ?>€</div>
                <div class="quick-stat-label"><?php esc_html_e('Ingresos', 'chat-ia-addon'); ?></div>
            </div>
            <div class="quick-stat <?php echo ($dashboard['ocupacion_media_hoy'] ?? 0) > 80 ? 'positive' : ''; ?>">
                <div class="quick-stat-value"><?php echo intval($dashboard['ocupacion_media_hoy'] ?? 0); ?>%</div>
                <div class="quick-stat-label"><?php esc_html_e('Ocupacion', 'chat-ia-addon'); ?></div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el panel de estadísticas de tokens
     */
    private function render_token_stats_panel() {
        if (!class_exists('Chat_IA_Token_Monitor')) {
            return;
        }

        echo Chat_IA_Token_Monitor::get_instance()->render_stats_panel();
    }

    /**
     * Maneja mensajes del chat
     */
    public function handle_message() {
        check_ajax_referer('chat_ia_admin_assistant_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        $message = sanitize_textarea_field($_POST['message'] ?? '');

        if (empty($message)) {
            wp_send_json_error(['error' => __('Mensaje vacío', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Obtener motor de IA (contexto backend = admin assistant)
        if (!class_exists('Chat_IA_Engine_Manager')) {
            wp_send_json_error(['error' => __('Motor de IA no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $engine_manager = Chat_IA_Engine_Manager::get_instance();
        $engine = $engine_manager->get_backend_engine();

        if (!$engine || !$engine->is_configured()) {
            wp_send_json_error(['error' => __('Configura un proveedor de IA primero', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Obtener historial de conversación
        $history = $this->get_conversation_history();

        // Procesar mensaje largo (documento) si es necesario
        $message = $this->process_long_message($message);

        // Añadir mensaje del usuario
        $history[] = ['role' => 'user', 'content' => $message];

        // Generar system prompt (compacto si hay mensaje largo)
        $is_long_message = mb_strlen($message) > 2000;
        $system_prompt = $this->generate_system_prompt($is_long_message);

        // Obtener herramientas
        $tools = $this->tools->get_tools_definition();

        // Enviar a la IA
        $response = $engine->send_message($history, $system_prompt, $tools);

        if (!$response['success']) {
            wp_send_json_error(['error' => $response['error'] ?? 'Error al procesar']);
        }

        // Registrar uso de tokens
        $this->log_token_usage($response, 'admin_assistant');

        // Procesar tool calls si existen
        $result = $this->process_response_with_actions($response, $history, $engine, $system_prompt, $tools);
        $final_response = $result['response'];
        $actions = $result['actions'] ?? [];

        // Guardar historial
        $history[] = ['role' => 'assistant', 'content' => $final_response];
        $this->save_conversation_history($history);

        // Tracking de estadísticas del chat admin
        $this->track_admin_usage();

        wp_send_json_success([
            'response' => $final_response,
            'actions' => $actions,
        ]);
    }

    /**
     * Registra uso del chat admin para estadísticas
     */
    private function track_admin_usage() {
        $stats = get_option('chat_ia_admin_stats', [
            'total_messages' => 0,
            'total_sessions' => 0,
            'last_used' => null,
            'daily_stats' => [],
        ]);

        $today = date('Y-m-d');

        // Incrementar mensaje
        $stats['total_messages']++;
        $stats['last_used'] = current_time('mysql');

        // Stats diarias (mantener últimos 30 días)
        if (!isset($stats['daily_stats'][$today])) {
            $stats['daily_stats'][$today] = ['messages' => 0, 'sessions' => 1];
            $stats['total_sessions']++;
        }
        $stats['daily_stats'][$today]['messages']++;

        // Limpiar stats de más de 30 días
        $cutoff = date('Y-m-d', strtotime('-30 days'));
        $stats['daily_stats'] = array_filter(
            $stats['daily_stats'],
            fn($date) => $date >= $cutoff,
            ARRAY_FILTER_USE_KEY
        );

        update_option('chat_ia_admin_stats', $stats);
    }

    /**
     * Registra uso de tokens en el monitor
     *
     * @param array $response Respuesta del motor de IA
     * @param string $context Contexto de uso
     */
    private function log_token_usage($response, $context = 'general') {
        if (!class_exists('Chat_IA_Token_Monitor')) {
            return;
        }

        $usage = $response['usage'] ?? [];
        $input_tokens = $usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0;
        $output_tokens = $usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0;

        if ($input_tokens > 0 || $output_tokens > 0) {
            Chat_IA_Token_Monitor::get_instance()->log_ai_usage(
                $input_tokens,
                $output_tokens,
                $context
            );
        }
    }

    /**
     * Obtiene estadísticas del chat admin
     *
     * @param string $periodo 'day', 'week', 'month'
     * @return array
     */
    public static function get_admin_stats($periodo = 'week') {
        $stats = get_option('chat_ia_admin_stats', [
            'total_messages' => 0,
            'total_sessions' => 0,
            'daily_stats' => [],
        ]);

        $fecha_inicio = match ($periodo) {
            'day' => date('Y-m-d'),
            'week' => date('Y-m-d', strtotime('-7 days')),
            'month' => date('Y-m-d', strtotime('-30 days')),
            default => date('Y-m-d', strtotime('-7 days')),
        };

        // Filtrar stats del periodo
        $periodo_messages = 0;
        $periodo_sessions = 0;

        foreach ($stats['daily_stats'] ?? [] as $date => $day_stats) {
            if ($date >= $fecha_inicio) {
                $periodo_messages += $day_stats['messages'] ?? 0;
                $periodo_sessions += $day_stats['sessions'] ?? 0;
            }
        }

        return [
            'periodo' => $periodo,
            'total_mensajes' => $periodo_messages,
            'total_sesiones' => $periodo_sessions,
            'ultimo_uso' => $stats['last_used'] ?? null,
        ];
    }

    /**
     * Procesa un mensaje desde la API REST (para app mobile)
     *
     * @param string $message El mensaje del usuario
     * @param string|null $session_id ID de sesión opcional
     * @return array Respuesta con 'success', 'response' y opcionalmente 'error'
     */
    public function process_message($message, $session_id = null) {
        if (empty($message)) {
            return ['success' => false, 'error' => __('Mensaje vacío', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Obtener motor de IA (contexto backend = admin assistant)
        if (!class_exists('Chat_IA_Engine_Manager')) {
            return ['success' => false, 'error' => __('Motor de IA no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        $engine_manager = Chat_IA_Engine_Manager::get_instance();
        $engine = $engine_manager->get_backend_engine();

        if (!$engine || !$engine->is_configured()) {
            return ['success' => false, 'error' => __('Configura un proveedor de IA primero', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        }

        // Obtener historial de conversación (usando session_id si se proporciona)
        $history = $this->get_conversation_history($session_id);

        // Añadir mensaje del usuario
        $history[] = ['role' => 'user', 'content' => $message];

        // Generar system prompt
        $system_prompt = $this->generate_system_prompt();

        // Obtener herramientas
        $tools = $this->tools->get_tools_definition();

        // Enviar a la IA
        $response = $engine->send_message($history, $system_prompt, $tools);

        if (!$response['success']) {
            return ['success' => false, 'error' => $response['error'] ?? 'Error al procesar'];
        }

        // Procesar tool calls si existen
        $final_response = $this->process_response($response, $history, $engine, $system_prompt, $tools);

        // Guardar historial
        $history[] = ['role' => 'assistant', 'content' => $final_response];
        $this->save_conversation_history($history, $session_id);

        return [
            'success' => true,
            'response' => $final_response,
        ];
    }

    /**
     * Procesa la respuesta, ejecutando herramientas si es necesario
     */
    private function process_response($response, &$history, $engine, $system_prompt, $tools) {
        $max_iterations = 5;
        $iteration = 0;

        while ($iteration < $max_iterations) {
            $iteration++;

            // Si hay tool_calls, ejecutarlas
            if (!empty($response['tool_calls'])) {
                $tool_results = [];

                foreach ($response['tool_calls'] as $tool_call) {
                    $tool_name = $tool_call['name'];
                    $arguments = $tool_call['arguments'] ?? [];

                    // Ejecutar herramienta
                    $result = $this->tools->execute_tool($tool_name, $arguments);

                    $tool_results[] = [
                        'tool_use_id' => $tool_call['id'] ?? uniqid(),
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ];
                }

                // Añadir respuesta del asistente con tool_use al historial
                $history[] = [
                    'role' => 'assistant',
                    'content' => $response['response'] ?? '',
                    'tool_calls' => $response['tool_calls'],
                ];

                // Añadir resultados de herramientas
                $history[] = [
                    'role' => 'user',
                    'content' => json_encode([
                        'type' => 'tool_result',
                        'tool_results' => $tool_results,
                    ], JSON_UNESCAPED_UNICODE),
                ];

                // Continuar conversación
                $response = $engine->send_message($history, $system_prompt, $tools);

                if (!$response['success']) {
                    return 'Error al procesar la información: ' . ($response['error'] ?? 'desconocido');
                }
            } else {
                // No hay más tool_calls, devolver respuesta final
                return $response['response'] ?? '';
            }
        }

        return $response['response'] ?? 'Procesamiento completado';
    }

    /**
     * Procesa la respuesta con deteccion de acciones contextuales
     */
    private function process_response_with_actions($response, &$history, $engine, $system_prompt, $tools) {
        $max_iterations = 5;
        $iteration = 0;
        $context = [
            'tools_used' => [],
            'backup_ids' => [],
            'data_modified' => false,
        ];

        while ($iteration < $max_iterations) {
            $iteration++;

            // Si hay tool_calls, ejecutarlas
            if (!empty($response['tool_calls'])) {
                $tool_results = [];

                foreach ($response['tool_calls'] as $tool_call) {
                    $tool_name = $tool_call['name'];
                    $arguments = $tool_call['arguments'] ?? [];

                    // Registrar herramienta usada
                    $context['tools_used'][] = $tool_name;

                    // Detectar si es operacion de escritura
                    $write_tools = ['asignar_estado_calendario', 'crear_estado_calendario', 'editar_estado_calendario',
                                   'eliminar_estado_calendario', 'crear_tipo_ticket', 'editar_tipo_ticket',
                                   'eliminar_tipo_ticket', 'modificar_limite_plazas', 'bloquear_ticket',
                                   'restaurar_backup', 'resetear_calendario'];
                    if (in_array($tool_name, $write_tools)) {
                        $context['data_modified'] = true;
                    }

                    // Ejecutar herramienta
                    $result = $this->tools->execute_tool($tool_name, $arguments);

                    // Capturar backup_id si existe
                    if (isset($result['backup_id'])) {
                        $context['backup_ids'][] = $result['backup_id'];
                    }

                    $tool_results[] = [
                        'tool_use_id' => $tool_call['id'] ?? uniqid(),
                        'content' => json_encode($result, JSON_UNESCAPED_UNICODE),
                    ];
                }

                // Añadir respuesta del asistente con tool_use al historial
                $history[] = [
                    'role' => 'assistant',
                    'content' => $response['response'] ?? '',
                    'tool_calls' => $response['tool_calls'],
                ];

                // Añadir resultados de herramientas
                $history[] = [
                    'role' => 'user',
                    'content' => json_encode([
                        'type' => 'tool_result',
                        'tool_results' => $tool_results,
                    ], JSON_UNESCAPED_UNICODE),
                ];

                // Continuar conversación
                $response = $engine->send_message($history, $system_prompt, $tools);

                if (!$response['success']) {
                    return [
                        'response' => 'Error al procesar la información: ' . ($response['error'] ?? 'desconocido'),
                        'actions' => [],
                    ];
                }
            } else {
                // No hay más tool_calls, generar acciones y devolver
                $final_response = $response['response'] ?? '';
                $actions = $this->generate_response_actions($final_response, $context);

                return [
                    'response' => $final_response,
                    'actions' => $actions,
                ];
            }
        }

        return [
            'response' => $response['response'] ?? 'Procesamiento completado',
            'actions' => $this->generate_response_actions($response['response'] ?? '', $context),
        ];
    }

    /**
     * Genera acciones contextuales basadas en la respuesta
     */
    private function generate_response_actions($response, $context) {
        $actions = [];

        // Si se modificaron datos, ofrecer deshacer
        if ($context['data_modified'] && !empty($context['backup_ids'])) {
            $last_backup = end($context['backup_ids']);
            $actions[] = [
                'label' => __('Deshacer cambios', 'chat-ia-addon'),
                'shortcut' => 'restore_backup',
                'params' => ['backup_id' => $last_backup],
                'icon' => 'undo',
            ];
        }

        // Acciones basadas en herramientas usadas
        foreach ($context['tools_used'] as $tool) {
            switch ($tool) {
                case 'asignar_estado_calendario':
                case 'crear_estado_calendario':
                case 'editar_estado_calendario':
                case 'resetear_calendario':
                    $actions[] = [
                        'label' => __('Ver calendario', 'chat-ia-addon'),
                        'url' => admin_url('admin.php?page=calendario_experiencias'),
                        'icon' => 'calendar-alt',
                    ];
                    break;

                case 'crear_tipo_ticket':
                case 'editar_tipo_ticket':
                case 'eliminar_tipo_ticket':
                    $actions[] = [
                        'label' => __('Ver tickets', 'chat-ia-addon'),
                        'url' => admin_url('admin.php?page=calendario-gestion-tickets&tab=tipos'),
                        'icon' => 'tickets-alt',
                    ];
                    break;

                case 'obtener_resumen_periodo':
                case 'obtener_resumen_hoy':
                case 'obtener_estadisticas_ingresos':
                    $actions[] = [
                        'label' => __('Ver dashboard', 'chat-ia-addon'),
                        'url' => admin_url('admin.php?page=calendario-gestion-tickets'),
                        'icon' => 'chart-bar',
                    ];
                    break;

                case 'listar_backups':
                case 'crear_backup':
                    $actions[] = [
                        'label' => __('Crear backup', 'chat-ia-addon'),
                        'shortcut' => 'create_backup',
                        'icon' => 'backup',
                    ];
                    break;

                case 'exportar_datos_csv':
                    // La URL de descarga estara en el response
                    break;

                case 'obtener_proximas_reservas':
                case 'obtener_reservas_dia':
                case 'buscar_reservas':
                    $actions[] = [
                        'label' => __('Ver todas', 'chat-ia-addon'),
                        'url' => admin_url('admin.php?page=calendario-gestion-tickets&tab=reservas'),
                        'icon' => 'list-view',
                    ];
                    break;
            }
        }

        // Acciones basadas en contenido de la respuesta
        if (stripos($response, 'backup') !== false && stripos($response, 'creado') !== false) {
            // Ya se agrego deshacer arriba
        }

        if (stripos($response, 'shortcode') !== false) {
            $actions[] = [
                'label' => __('Copiar', 'chat-ia-addon'),
                'action' => 'copy_code',
                'icon' => 'clipboard',
            ];
        }

        // Sugerencias de seguimiento
        if (stripos($response, 'reserva') !== false) {
            if (!$this->action_exists($actions, 'summary_today')) {
                $actions[] = [
                    'label' => __('Resumen hoy', 'chat-ia-addon'),
                    'shortcut' => 'summary_today',
                    'icon' => 'calendar-alt',
                ];
            }
        }

        // Limitar a 4 acciones max
        return array_slice(array_unique($actions, SORT_REGULAR), 0, 4);
    }

    /**
     * Verifica si una accion ya existe en el array
     */
    private function action_exists($actions, $identifier) {
        foreach ($actions as $action) {
            if (($action['shortcut'] ?? '') === $identifier ||
                ($action['url'] ?? '') === $identifier) {
                return true;
            }
        }
        return false;
    }

    /**
     * Genera el system prompt especializado
     *
     * @param bool $compact Si es true, genera versión reducida para documentos largos
     */
    private function generate_system_prompt($compact = false) {
        $fecha_hoy = date('Y-m-d');
        $dia_semana = date_i18n('l');
        $usuario = wp_get_current_user();

        // Versión compacta para cuando hay documentos largos
        if ($compact) {
            return <<<PROMPT
Eres el asistente de gestión del plugin "Calendario Experiencias". Fecha: {$fecha_hoy}.

CAPACIDADES: Consultar reservas, disponibilidad, estadísticas. Gestionar calendario, estados, tickets, plazas.

MODO DOCUMENTO LARGO - SIGUE ESTE PROTOCOLO:

1. **ANALIZA** la información extraída del documento
2. **GENERA UN PLAN** con las acciones específicas:
   ```
   📋 PLAN DE CONFIGURACIÓN

   1. CREAR TICKETS:
      - [nombre]: [precio]€, [plazas] plazas

   2. CONFIGURAR ESTADOS:
      - [estado]: [descripción]

   3. ASIGNAR CALENDARIO:
      - [fechas]: [estado]
   ```
3. **PIDE CONFIRMACIÓN** antes de ejecutar
4. **EJECUTA PASO A PASO** cuando el usuario confirme

REGLAS CRÍTICAS:
- NUNCA ejecutes acciones sin confirmación explícita
- Presenta todo el plan antes de empezar
- Si hay información ambigua, PREGUNTA primero
- Usa las herramientas una por una, verificando cada resultado
PROMPT;
        }

        // Obtener contexto del sistema
        $tipos_ticket = get_option('calendario_experiencias_ticket_types', []);
        $nombres_tickets = [];
        foreach ($tipos_ticket as $slug => $tipo) {
            $nombres_tickets[] = "- {$tipo['name']} (slug: {$slug}, precio: {$tipo['precio']}€, plazas: {$tipo['plazas']})";
        }

        $prompt = <<<PROMPT
Eres el asistente de gestión del plugin "Calendario Experiencias" de WordPress. Ayudas a administradores a gestionar reservas, consultar disponibilidad, ver estadísticas y configurar el sistema.

INFORMACIÓN DEL CONTEXTO:
- Fecha actual: {$fecha_hoy} ({$dia_semana})
- Usuario: {$usuario->display_name}
- Plugin: Calendario Experiencias con Reservas Addon
- Integración: WooCommerce

TIPOS DE TICKET CONFIGURADOS:
PROMPT;

        if (!empty($nombres_tickets)) {
            $prompt .= "\n" . implode("\n", $nombres_tickets);
        } else {
            $prompt .= "\n(No hay tipos de ticket configurados)";
        }

        // Obtener estados del calendario
        $estados_calendario = get_option('calendario_experiencias_estados', []);
        $nombres_estados = [];
        foreach ($estados_calendario as $slug => $estado) {
            $nombre = $estado['nombre'] ?? $estado['title'] ?? $slug;
            $nombres_estados[] = "- {$nombre} (slug: {$slug})";
        }

        $prompt .= <<<PROMPT


ESTADOS DEL CALENDARIO CONFIGURADOS:
PROMPT;

        if (!empty($nombres_estados)) {
            $prompt .= "\n" . implode("\n", $nombres_estados);
        } else {
            $prompt .= "\n(No hay estados configurados)";
        }

        $prompt .= <<<PROMPT


CAPACIDADES:

CONSULTAS (solo lectura):
1. Consultar reservas por fecha, cliente, código de ticket o período
2. Ver plazas disponibles, vendidas y en carrito para cualquier fecha
3. Calcular ingresos, comparar períodos, ver tickets más vendidos
4. Obtener datos de clientes y su historial
5. Exportar datos a CSV

GESTIÓN DEL CALENDARIO:
6. Asignar estados a días individuales o rangos de fechas
7. Filtrar por días de la semana (ej: solo lunes a viernes)
8. Resetear el calendario completo

GESTIÓN DE ESTADOS:
9. Crear nuevos estados del calendario
10. Editar estados existentes (nombre, color, horario)
11. Eliminar estados

GESTIÓN DE TIPOS DE TICKET:
12. Crear nuevos tipos de ticket con precio, plazas, IVA
13. Editar tipos existentes
14. Eliminar tipos (si no tienen reservas)

GESTIÓN DE PLAZAS Y BLOQUEOS:
15. Modificar límites de plazas para fechas específicas
16. Bloquear/desbloquear tickets individuales

SISTEMA DE BACKUPS:
17. Crear backups manuales de la configuración
18. Listar backups disponibles
19. Restaurar configuración desde un backup anterior
20. Ver historial de cambios

AYUDA:
21. Explicar cómo funcionan las secciones del plugin
22. Generar shortcodes personalizados

PROTOCOLO DE CONFIRMACIÓN (MUY IMPORTANTE):

Cuando el usuario pida una acción que MODIFICA datos, SIEMPRE sigue este protocolo:

1. **PRIMERO**: Muestra un resumen claro de lo que vas a hacer:
   ```
   📋 **Acción solicitada**: [descripción clara]

   **Cambios que se aplicarán:**
   - [cambio 1]
   - [cambio 2]
   ...

   **Datos afectados:**
   - Fechas: X días
   - Estados/Tickets: nombres específicos
   ```

2. **SEGUNDO**: Pide confirmación EXPLÍCITA:
   ```
   ⚠️ ¿Confirmas que quieres aplicar estos cambios? Responde "sí" o "confirmo" para proceder.
   ```

3. **TERCERO**: ESPERA la confirmación antes de ejecutar. NO ejecutes herramientas que modifiquen datos hasta que el usuario confirme.

4. **CUARTO**: Después de ejecutar, muestra:
   ```
   ✅ **Cambios aplicados correctamente**

   - Se modificaron X elementos
   - Backup creado: [backup_id]

   🔄 **Para ver los cambios**, refresca la página del calendario o ve a:
   → [enlace a la página relevante]

   ↩️ Si necesitas deshacer, di: "restaura el backup [backup_id]"
   ```

ENLACES DEL ADMIN (incluir cuando sea relevante):
- Calendario: /wp-admin/admin.php?page=calendario_experiencias
- Estados: /wp-admin/admin.php?page=calendario_experiencias&tab=estados
- Tipos de Ticket: /wp-admin/admin.php?page=calendario-gestion-tickets&tab=tipos
- Dashboard: /wp-admin/admin.php?page=calendario-gestion-tickets
- Asistente IA: /wp-admin/admin.php?page=calendario-asistente-ia

VALIDACIONES ANTES DE EJECUTAR:
- Si el usuario pide asignar un estado, VERIFICA que el slug existe (usa listar_estados_calendario)
- Si el usuario pide crear algo con un nombre ambiguo, PREGUNTA por el slug correcto
- Si el rango de fechas es muy grande (>30 días), ADVIERTE al usuario
- Si la acción afecta muchos registros, MUESTRA el número exacto antes de confirmar

INTERPRETACIÓN DE ÓRDENES:
- "pon abierto los sábados de febrero" → asignar estado "abierto" a días de semana [6] en febrero
- "cierra del 24 al 26" → asignar estado "cerrado" (verificar que existe, si no, sugerir alternativas)
- "crea un estado festivo rojo" → crear_estado_calendario con nombre "Festivo", color "#ff0000"
- "sube el precio de entrada a 30" → buscar ticket que contenga "entrada" y pedir confirmación del slug exacto

SI HAY AMBIGÜEDAD:
- PREGUNTA antes de actuar
- Muestra opciones disponibles
- Ejemplo: "Veo varios tickets que contienen 'entrada': entrada-general, entrada-vip. ¿Cuál quieres modificar?"

INSTRUCCIONES GENERALES:
- Responde siempre en español
- Sé conciso pero informativo
- Para fechas, acepta formatos como "hoy", "mañana", "próxima semana", "15 de marzo"
- Convierte fechas relativas a formato YYYY-MM-DD internamente
- El sistema crea backups automáticos, pero menciona SIEMPRE el backup_id en tu respuesta

FORMATO DE RESPUESTA:
- Usa markdown para formatear
- Usa emojis para hacer el mensaje más visual (📋 ✅ ⚠️ 🔄 ↩️)
- Para tablas de datos, usa formato de tabla markdown
- Destaca información importante con **negrita**
PROMPT;

        return $prompt;
    }

    /**
     * Obtiene el historial de conversación
     *
     * @param string|null $session_id ID de sesión opcional (para API móvil)
     */
    private function get_conversation_history($session_id = null) {
        $key = $session_id ? "chat_ia_admin_history_session_{$session_id}" : "chat_ia_admin_history_" . get_current_user_id();
        $history = get_transient($key);
        return is_array($history) ? $history : [];
    }

    /**
     * Guarda el historial de conversación
     *
     * @param array $history El historial de mensajes
     * @param string|null $session_id ID de sesión opcional (para API móvil)
     */
    private function save_conversation_history($history, $session_id = null) {
        $key = $session_id ? "chat_ia_admin_history_session_{$session_id}" : "chat_ia_admin_history_" . get_current_user_id();

        // Limpiar historial para reducir tokens
        $cleaned_history = [];
        foreach ($history as $msg) {
            if (empty($msg['content'])) {
                continue;
            }

            $content = $msg['content'];

            // Si es un mensaje del usuario muy largo (documento), resumirlo para el historial
            if ($msg['role'] === 'user' && mb_strlen($content) > 1000) {
                // Guardar solo resumen para historial
                $content = mb_substr($content, 0, 500) . "\n[...documento procesado...]";
            }

            $cleaned_history[] = [
                'role' => $msg['role'],
                'content' => $content,
            ];
        }

        // Mantener solo los últimos 8 mensajes
        $cleaned_history = array_slice($cleaned_history, -8);
        set_transient($key, $cleaned_history, HOUR_IN_SECONDS);
    }

    /**
     * Procesa mensajes largos (documentos) de forma inteligente
     * Extrae información clave y genera un plan de tareas ejecutable
     *
     * @param string $message
     * @return string
     */
    private function process_long_message($message) {
        $max_chars = 5000; // ~1250 tokens - aumentado para documentos complejos

        if (mb_strlen($message) <= $max_chars) {
            return $message;
        }

        // Detectar si es un documento estructurado
        $has_structure = preg_match('/^[\-\*•]|\n[\-\*•]|^\d+\.|:\s*\n/m', $message);

        $sections = [];
        $detected_tasks = [];

        // === EXTRACCIÓN DE TICKETS/TARIFAS ===
        // Patrones para detectar tipos de ticket con precios
        $ticket_patterns = [
            '/(?:entrada|ticket|tarifa|pase|bono|adulto|niño|infantil|senior|jubilado|familiar|grupo)[^\n]*?\d+[,.]?\d*\s*€/i',
            '/\d+[,.]?\d*\s*€[^\n]*(?:entrada|ticket|tarifa|persona|adulto|niño)/i',
            '/(?:precio|coste|tarifa):\s*\d+[,.]?\d*\s*€?/i',
        ];

        foreach ($ticket_patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[0] as $match) {
                    $sections['tickets'][] = trim($match);
                }
            }
        }
        if (!empty($sections['tickets'])) {
            $sections['tickets'] = array_slice(array_unique($sections['tickets']), 0, 15);
            $detected_tasks[] = "CREAR TIPOS DE TICKET según los precios detectados";
        }

        // === EXTRACCIÓN DE HORARIOS Y ESTADOS ===
        $horario_patterns = [
            '/(?:horario|hora|apertura|cierre)[^\n]*/i',
            '/(?:lunes|martes|miércoles|jueves|viernes|sábado|domingo)[^\n]*\d+[:\-]\d+[^\n]*/i',
            '/\d{1,2}[:\-]\d{2}\s*(?:a|hasta|-)\s*\d{1,2}[:\-]\d{2}/i',
        ];

        foreach ($horario_patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[0] as $match) {
                    $sections['horarios'][] = trim($match);
                }
            }
        }
        if (!empty($sections['horarios'])) {
            $sections['horarios'] = array_slice(array_unique($sections['horarios']), 0, 10);
            $detected_tasks[] = "CONFIGURAR HORARIOS del calendario";
        }

        // === EXTRACCIÓN DE ESTADOS DEL CALENDARIO ===
        if (preg_match_all('/(?:estado|temporada|período|época)[^\n]*(?:abierto|cerrado|especial|alta|baja|festivo)/i', $message, $matches)) {
            $sections['estados'] = array_slice(array_unique($matches[0]), 0, 5);
            $detected_tasks[] = "CREAR/ASIGNAR ESTADOS del calendario";
        }

        // === EXTRACCIÓN DE PLAZAS/CAPACIDAD ===
        if (preg_match_all('/(?:plazas?|capacidad|aforo|máximo|límite)[^\n]*\d+[^\n]*/i', $message, $matches)) {
            $sections['capacidad'] = array_slice(array_unique($matches[0]), 0, 5);
            $detected_tasks[] = "CONFIGURAR LÍMITES DE PLAZAS";
        }

        // === EXTRACCIÓN DE EXPERIENCIAS/SERVICIOS ===
        $servicios_patterns = [
            '/^[\-\*•]\s*[A-ZÁÉÍÓÚÑ][^\n]{10,100}/m',
            '/(?:experiencia|actividad|servicio|visita|tour|ruta)[^\n]*/i',
        ];

        foreach ($servicios_patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[0] as $match) {
                    $sections['servicios'][] = trim($match);
                }
            }
        }
        if (!empty($sections['servicios'])) {
            $sections['servicios'] = array_slice(array_unique($sections['servicios']), 0, 10);
        }

        // === EXTRACCIÓN DE CONTACTO ===
        if (preg_match_all('/(?:\+\d{1,3}[\s\-]?)?(?:\(?\d{2,4}\)?[\s\-]?)?\d{3}[\s\-]?\d{3,4}[\s\-]?\d{0,4}/', $message, $phones)) {
            $sections['contacto'][] = 'Tel: ' . implode(', ', array_slice(array_unique($phones[0]), 0, 2));
        }
        if (preg_match_all('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message, $emails)) {
            $sections['contacto'][] = 'Email: ' . implode(', ', array_slice(array_unique($emails[0]), 0, 2));
        }

        // === CONSTRUIR DOCUMENTO PROCESADO ===
        $summary = "📋 DOCUMENTO PROCESADO PARA CONFIGURACIÓN\n";
        $summary .= "═══════════════════════════════════════\n\n";

        // Plan de tareas detectadas
        if (!empty($detected_tasks)) {
            $summary .= "🎯 TAREAS A REALIZAR:\n";
            foreach ($detected_tasks as $i => $task) {
                $summary .= ($i + 1) . ". " . $task . "\n";
            }
            $summary .= "\n";
        }

        // Información extraída por sección
        foreach ($sections as $tipo => $items) {
            if (!empty($items)) {
                $titulo = match($tipo) {
                    'tickets' => '🎟️ TICKETS/TARIFAS',
                    'horarios' => '🕐 HORARIOS',
                    'estados' => '📅 ESTADOS CALENDARIO',
                    'capacidad' => '👥 CAPACIDAD/PLAZAS',
                    'servicios' => '⭐ SERVICIOS/EXPERIENCIAS',
                    'contacto' => '📞 CONTACTO',
                    default => strtoupper($tipo),
                };
                $summary .= $titulo . ":\n";
                foreach ($items as $item) {
                    $summary .= "  • " . $item . "\n";
                }
                $summary .= "\n";
            }
        }

        // Contexto adicional si hay espacio
        $remaining_chars = $max_chars - mb_strlen($summary) - 200;
        if ($remaining_chars > 500) {
            $summary .= "📝 CONTEXTO ADICIONAL:\n";
            $summary .= mb_substr($message, 0, $remaining_chars);
        }

        // Instrucción final para el asistente
        $summary .= "\n\n═══════════════════════════════════════\n";
        $summary .= "⚡ INSTRUCCIÓN: Analiza la información extraída y presenta un PLAN DE ACCIÓN.\n";
        $summary .= "Muestra qué tickets crearás, qué estados configurarás, etc.\n";
        $summary .= "Pide confirmación al usuario ANTES de ejecutar cualquier cambio.\n";

        return mb_substr($summary, 0, $max_chars);
    }

    /**
     * Limpia el historial de conversación
     */
    public function handle_clear_history() {
        check_ajax_referer('chat_ia_admin_assistant_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'chat-ia-addon')]);
        }

        $user_id = get_current_user_id();
        delete_transient("chat_ia_admin_history_{$user_id}");

        wp_send_json_success();
    }
}
