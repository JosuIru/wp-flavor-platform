<?php
/**
 * Shortcodes del Portal del Cliente
 *
 * Proporciona shortcodes para el portal de servicios y dashboard personalizado
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para shortcodes del portal del cliente
 */
class Flavor_Portal_Shortcodes {

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
        $this->register_shortcodes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
    }

    /**
     * Encola estilos del portal
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'flavor-portal',
            FLAVOR_CHAT_IA_URL . 'assets/css/portal.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('flavor_servicios', [$this, 'render_servicios']);
        add_shortcode('flavor_mi_portal', [$this, 'render_mi_portal']);
        add_shortcode('flavor_modulos_grid', [$this, 'render_modulos_grid']);
    }

    /**
     * Renderiza la landing de servicios
     */
    public function render_servicios($atts) {
        $atts = shortcode_atts([
            'titulo' => __('Servicios de la Comunidad', 'flavor-chat-ia'),
            'subtitulo' => __('Descubre todo lo que tu comunidad tiene para ofrecer', 'flavor-chat-ia'),
            'mostrar_stats' => 'yes',
            'columnas' => '3',
        ], $atts);

        ob_start();
        ?>
        <div class="flavor-servicios-landing">
            <!-- Hero Section -->
            <div class="flavor-hero flavor-hero--servicios">
                <div class="flavor-hero__content">
                    <h1 class="flavor-hero__title"><?php echo esc_html($atts['titulo']); ?></h1>
                    <p class="flavor-hero__subtitle"><?php echo esc_html($atts['subtitulo']); ?></p>

                    <?php if ($atts['mostrar_stats'] === 'yes') : ?>
                        <?php echo $this->render_stats(); ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grid de Módulos -->
            <div class="flavor-servicios__grid">
                <?php echo $this->render_modulos_grid(['tipo' => 'servicios', 'columnas' => $atts['columnas']]); ?>
            </div>

            <?php if (!is_user_logged_in()) : ?>
                <!-- CTA de Registro -->
                <div class="flavor-servicios__cta">
                    <div class="flavor-cta-box">
                        <h2><?php _e('¿Quieres acceder a más servicios?', 'flavor-chat-ia'); ?></h2>
                        <p><?php _e('Regístrate para participar activamente en tu comunidad', 'flavor-chat-ia'); ?></p>
                        <a href="<?php echo wp_registration_url(); ?>" class="flavor-button flavor-button--primary">
                            <?php _e('Crear Cuenta', 'flavor-chat-ia'); ?>
                        </a>
                        <a href="<?php echo wp_login_url(); ?>" class="flavor-button flavor-button--secondary">
                            <?php _e('Iniciar Sesión', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el dashboard personalizado
     */
    public function render_mi_portal($atts) {
        // Requerir login
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required">
                <p>' . __('Debes iniciar sesión para acceder a tu portal.', 'flavor-chat-ia') . '</p>
                <a href="' . wp_login_url(get_permalink()) . '" class="flavor-button flavor-button--primary">' . __('Iniciar Sesión', 'flavor-chat-ia') . '</a>
            </div>';
        }

        $atts = shortcode_atts([
            'mostrar_actividad' => 'yes',
            'mostrar_notificaciones' => 'yes',
            'columnas' => '3',
        ], $atts);

        $current_user = wp_get_current_user();

        ob_start();
        ?>
        <div class="flavor-mi-portal">
            <!-- Header del Portal -->
            <div class="flavor-portal__header">
                <h1 class="flavor-portal__title">
                    <?php printf(__('Bienvenido/a, %s', 'flavor-chat-ia'), esc_html($current_user->display_name)); ?>
                </h1>
                <p class="flavor-portal__subtitle"><?php _e('Tu centro de control comunitario', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-portal__layout">
                <!-- Columna Principal -->
                <div class="flavor-portal__main">
                    <?php if ($atts['mostrar_notificaciones'] === 'yes') : ?>
                        <!-- Notificaciones -->
                        <div class="flavor-portal__section">
                            <h2 class="flavor-portal__section-title"><?php _e('Notificaciones', 'flavor-chat-ia'); ?></h2>
                            <?php echo $this->render_notificaciones(); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Accesos Rápidos -->
                    <div class="flavor-portal__section">
                        <h2 class="flavor-portal__section-title"><?php _e('Accesos Rápidos', 'flavor-chat-ia'); ?></h2>
                        <?php echo $this->render_accesos_rapidos(); ?>
                    </div>

                    <!-- Mis Módulos -->
                    <div class="flavor-portal__section">
                        <h2 class="flavor-portal__section-title"><?php _e('Mis Servicios', 'flavor-chat-ia'); ?></h2>
                        <?php echo $this->render_modulos_grid(['tipo' => 'portal', 'columnas' => $atts['columnas']]); ?>
                    </div>
                </div>

                <?php if ($atts['mostrar_actividad'] === 'yes') : ?>
                    <!-- Sidebar con Actividad -->
                    <aside class="flavor-portal__sidebar">
                        <div class="flavor-portal__widget">
                            <h3 class="flavor-portal__widget-title"><?php _e('Actividad Reciente', 'flavor-chat-ia'); ?></h3>
                            <?php echo $this->render_actividad_reciente(); ?>
                        </div>

                        <div class="flavor-portal__widget">
                            <h3 class="flavor-portal__widget-title"><?php _e('Mis Estadísticas', 'flavor-chat-ia'); ?></h3>
                            <?php echo $this->render_mis_stats(); ?>
                        </div>
                    </aside>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza grid de módulos
     */
    public function render_modulos_grid($atts = []) {
        $atts = wp_parse_args($atts, [
            'tipo' => 'servicios', // 'servicios' o 'portal'
            'columnas' => '3',
        ]);

        $modulos = $this->get_modulos_disponibles($atts['tipo']);

        if (empty($modulos)) {
            return '<p class="flavor-no-modulos">' . __('No hay módulos disponibles en este momento.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-modulos-grid flavor-modulos-grid--col-<?php echo esc_attr($atts['columnas']); ?>">
            <?php foreach ($modulos as $modulo) : ?>
                <div class="flavor-modulo-card" data-modulo="<?php echo esc_attr($modulo['id']); ?>">
                    <div class="flavor-modulo-card__icon">
                        <?php echo $modulo['icon']; ?>
                    </div>
                    <div class="flavor-modulo-card__content">
                        <h3 class="flavor-modulo-card__title"><?php echo esc_html($modulo['name']); ?></h3>
                        <p class="flavor-modulo-card__description"><?php echo esc_html($modulo['description']); ?></p>

                        <?php if (!empty($modulo['stats'])) : ?>
                            <div class="flavor-modulo-card__stats">
                                <?php foreach ($modulo['stats'] as $stat) : ?>
                                    <span class="flavor-stat"><?php echo esc_html($stat); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flavor-modulo-card__actions">
                        <?php if ($modulo['tiene_acceso']) : ?>
                            <a href="<?php echo esc_url($modulo['url']); ?>" class="flavor-button flavor-button--primary">
                                <?php echo esc_html($modulo['action_text'] ?? __('Acceder', 'flavor-chat-ia')); ?>
                            </a>
                        <?php else : ?>
                            <span class="flavor-modulo-card__locked">
                                🔒 <?php _e('Requiere registro', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene módulos disponibles según contexto
     */
    private function get_modulos_disponibles($tipo = 'servicios') {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_activos = $loader->get_loaded_modules();
        $modulos_disponibles = [];

        foreach ($modulos_activos as $id => $instance) {
            // Verificar acceso
            $tiene_acceso = true;
            if (class_exists('Flavor_Module_Access_Control')) {
                $control = Flavor_Module_Access_Control::get_instance();
                $tiene_acceso = $control->user_can_access($id);
            }

            // Para landing "servicios", mostrar todos los módulos
            // Para "portal", solo los que el usuario puede acceder
            if ($tipo === 'portal' && !$tiene_acceso) {
                continue;
            }

            $modulos_disponibles[] = [
                'id' => $id,
                'name' => $instance->name ?? ucfirst($id),
                'description' => $instance->description ?? '',
                'icon' => $this->get_modulo_icon($id),
                'url' => home_url('/' . str_replace('_', '-', $id) . '/'),
                'tiene_acceso' => $tiene_acceso,
                'stats' => $this->get_modulo_stats($id, $instance),
                'action_text' => $this->get_modulo_action_text($id),
            ];
        }

        return $modulos_disponibles;
    }

    /**
     * Obtiene el icono de un módulo
     */
    private function get_modulo_icon($modulo_id) {
        $icons = [
            'talleres' => '🎓',
            'ayuda_vecinal' => '🤝',
            'eventos' => '📅',
            'grupos_consumo' => '🥬',
            'banco_tiempo' => '⏰',
            'socios' => '👥',
            'foros' => '💬',
            'incidencias' => '🔧',
            'espacios_comunes' => '🏢',
            'huertos_urbanos' => '🌱',
            'biblioteca' => '📚',
            'bicicletas_compartidas' => '🚲',
            'carpooling' => '🚗',
            'reciclaje' => '♻️',
            'marketplace' => '🛒',
        ];

        return $icons[$modulo_id] ?? '📦';
    }

    /**
     * Obtiene estadísticas del módulo
     */
    private function get_modulo_stats($modulo_id, $instance) {
        // Intentar obtener stats del módulo
        if (method_exists($instance, 'get_public_stats')) {
            return $instance->get_public_stats();
        }

        return [];
    }

    /**
     * Obtiene texto de acción del módulo
     */
    private function get_modulo_action_text($modulo_id) {
        $texts = [
            'talleres' => __('Ver Talleres', 'flavor-chat-ia'),
            'ayuda_vecinal' => __('Pedir/Ofrecer Ayuda', 'flavor-chat-ia'),
            'eventos' => __('Ver Eventos', 'flavor-chat-ia'),
            'grupos_consumo' => __('Ver Grupos', 'flavor-chat-ia'),
            'banco_tiempo' => __('Ver Servicios', 'flavor-chat-ia'),
        ];

        return $texts[$modulo_id] ?? __('Acceder', 'flavor-chat-ia');
    }

    /**
     * Renderiza estadísticas generales
     */
    private function render_stats() {
        $stats = [
            'modulos_activos' => count($this->get_modulos_disponibles('servicios')),
            'usuarios_activos' => $this->get_usuarios_activos(),
            'actividad_reciente' => $this->get_actividad_total(),
        ];

        ob_start();
        ?>
        <div class="flavor-stats-bar">
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['modulos_activos']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Servicios Activos', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['usuarios_activos']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Usuarios Activos', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-stat-item">
                <span class="flavor-stat-item__value"><?php echo esc_html($stats['actividad_reciente']); ?></span>
                <span class="flavor-stat-item__label"><?php _e('Actividades este mes', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza notificaciones del usuario
     */
    private function render_notificaciones() {
        // TODO: Integrar con sistema de notificaciones si existe
        ob_start();
        ?>
        <div class="flavor-notificaciones">
            <div class="flavor-notificacion flavor-notificacion--info">
                <span class="flavor-notificacion__icon">ℹ️</span>
                <div class="flavor-notificacion__content">
                    <p><?php _e('Bienvenido a tu portal. Explora los servicios disponibles.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza accesos rápidos
     */
    private function render_accesos_rapidos() {
        $accesos = $this->get_accesos_rapidos();

        ob_start();
        ?>
        <div class="flavor-accesos-rapidos">
            <?php foreach ($accesos as $acceso) : ?>
                <a href="<?php echo esc_url($acceso['url']); ?>" class="flavor-acceso-rapido">
                    <span class="flavor-acceso-rapido__icon"><?php echo $acceso['icon']; ?></span>
                    <span class="flavor-acceso-rapido__text"><?php echo esc_html($acceso['text']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene accesos rápidos del usuario
     */
    private function get_accesos_rapidos() {
        $accesos = [];

        // Accesos según módulos activos y permisos
        $loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;
        if (!$loader) {
            return $accesos;
        }

        $modulos = $loader->get_loaded_modules();

        if (isset($modulos['talleres'])) {
            $accesos[] = [
                'icon' => '➕',
                'text' => __('Crear Taller', 'flavor-chat-ia'),
                'url' => home_url('/talleres/crear/'),
            ];
        }

        if (isset($modulos['ayuda_vecinal'])) {
            $accesos[] = [
                'icon' => '🆘',
                'text' => __('Solicitar Ayuda', 'flavor-chat-ia'),
                'url' => home_url('/ayuda-vecinal/solicitar/'),
            ];
        }

        if (isset($modulos['eventos'])) {
            $accesos[] = [
                'icon' => '📅',
                'text' => __('Crear Evento', 'flavor-chat-ia'),
                'url' => home_url('/eventos/crear/'),
            ];
        }

        return $accesos;
    }

    /**
     * Renderiza actividad reciente
     */
    private function render_actividad_reciente() {
        ob_start();
        ?>
        <div class="flavor-actividad-reciente">
            <p class="flavor-actividad-reciente__empty"><?php _e('No hay actividad reciente', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza estadísticas personales
     */
    private function render_mis_stats() {
        ob_start();
        ?>
        <div class="flavor-mis-stats">
            <div class="flavor-stat-personal">
                <span class="flavor-stat-personal__label"><?php _e('Participaciones', 'flavor-chat-ia'); ?></span>
                <span class="flavor-stat-personal__value">0</span>
            </div>
            <div class="flavor-stat-personal">
                <span class="flavor-stat-personal__label"><?php _e('Contribuciones', 'flavor-chat-ia'); ?></span>
                <span class="flavor-stat-personal__value">0</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Helpers para stats
     */
    private function get_usuarios_activos() {
        $users = count_users();
        return $users['total_users'] ?? 0;
    }

    private function get_actividad_total() {
        // TODO: Calcular actividad real de los módulos
        return rand(50, 200);
    }
}
