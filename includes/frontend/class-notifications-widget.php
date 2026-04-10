<?php
/**
 * Widget de Notificaciones para el Dashboard
 *
 * Usa el Flavor_Notification_Manager existente para mostrar
 * notificaciones in-app al usuario en el frontend.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase del widget de notificaciones
 */
class Flavor_Notifications_Widget {

    /**
     * Instancia singleton
     *
     * @var Flavor_Notifications_Widget
     */
    private static $instancia = null;

    /**
     * Manager de notificaciones
     *
     * @var Flavor_Notification_Manager
     */
    private $notification_manager;

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_shortcode('flavor_notificaciones', [$this, 'shortcode_notificaciones']);
        add_shortcode('flavor_notificaciones_badge', [$this, 'shortcode_badge']);
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Notifications_Widget
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Obtiene el manager de notificaciones
     *
     * @return Flavor_Notification_Manager|null
     */
    private function get_manager() {
        if (null === $this->notification_manager && class_exists('Flavor_Notification_Manager')) {
            $this->notification_manager = Flavor_Notification_Manager::get_instance();
        }
        return $this->notification_manager;
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        $version = defined('FLAVOR_PLATFORM_VERSION') ? FLAVOR_PLATFORM_VERSION : '1.0.0';
        $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));

        wp_enqueue_style(
            'flavor-notificaciones',
            $plugin_url . 'assets/css/modules/notificaciones.css',
            [],
            $version
        );

        wp_enqueue_script(
            'flavor-notificaciones',
            $plugin_url . 'assets/js/notificaciones.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-notificaciones', 'flavorNotificacionesConfig', [
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'restUrl'  => rest_url('flavor-notifications/v1'),
            'nonce'    => wp_create_nonce('flavor_notifications'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'strings'  => [
                'marcarLeida'       => __('Marcar como leída', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'marcarTodas'       => __('Marcar todas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinNotificaciones' => __('No tienes notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'verTodas'          => __('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'hace'              => __('hace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando'          => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'pollInterval' => 60000, // 60 segundos
        ]);
    }

    /**
     * Shortcode: Lista de notificaciones
     */
    public function shortcode_notificaciones($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $manager = $this->get_manager();
        if (!$manager) {
            return '';
        }

        $atts = shortcode_atts([
            'limite'  => 10,
            'compact' => false,
        ], $atts, 'flavor_notificaciones');

        $usuario_id = get_current_user_id();
        $notificaciones = $manager->get_user_notifications($usuario_id, [
            'limit' => absint($atts['limite']),
        ]);
        $no_leidas = $manager->get_unread_count($usuario_id);

        ob_start();
        ?>
        <div class="flavor-notificaciones-widget" data-limite="<?php echo esc_attr($atts['limite']); ?>">
            <div class="flavor-notificaciones-header">
                <h3>
                    <?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <?php if ($no_leidas > 0): ?>
                        <span class="flavor-badge"><?php echo esc_html($no_leidas); ?></span>
                    <?php endif; ?>
                </h3>
                <?php if ($no_leidas > 0): ?>
                    <button type="button" class="flavor-btn-marcar-todas" title="<?php esc_attr_e('Marcar todas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </button>
                <?php endif; ?>
            </div>

            <div class="flavor-notificaciones-lista">
                <?php if (empty($notificaciones)): ?>
                    <div class="flavor-notificaciones-vacio">
                        <span class="dashicons dashicons-bell"></span>
                        <p><?php esc_html_e('No tienes notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notificaciones as $notificacion): ?>
                        <?php echo $this->render_notificacion($notificacion, $atts['compact']); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (count($notificaciones) >= $atts['limite']): ?>
                <div class="flavor-notificaciones-footer">
                    <a href="<?php echo esc_url(home_url('/mi-cuenta/notificaciones/')); ?>" class="flavor-btn-ver-todas">
                        <?php esc_html_e('Ver todas las notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Badge de notificaciones
     */
    public function shortcode_badge($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $manager = $this->get_manager();
        if (!$manager) {
            return '';
        }

        $usuario_id = get_current_user_id();
        $no_leidas = $manager->get_unread_count($usuario_id);

        ob_start();
        ?>
        <span class="flavor-notificaciones-badge-wrapper">
            <button type="button" class="flavor-notificaciones-trigger" aria-label="<?php esc_attr_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-bell"></span>
                <?php if ($no_leidas > 0): ?>
                    <span class="flavor-notificaciones-count"><?php echo esc_html($no_leidas); ?></span>
                <?php endif; ?>
            </button>
            <div class="flavor-notificaciones-dropdown" style="display: none;">
                <div class="flavor-notificaciones-dropdown-content">
                    <?php echo do_shortcode('[flavor_notificaciones limite="5" compact="true"]'); ?>
                </div>
            </div>
        </span>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una notificación individual
     *
     * @param object $notificacion Datos de la notificación
     * @param bool   $compact      Modo compacto
     * @return string
     */
    public function render_notificacion($notificacion, $compact = false) {
        $clases = ['flavor-notificacion'];
        if (empty($notificacion->is_read)) {
            $clases[] = 'no-leida';
        }
        if ($compact) {
            $clases[] = 'compact';
        }

        $icono = $notificacion->icon ?? 'dashicons-bell';
        $color = $notificacion->color ?? '#3b82f6';
        $enlace = $notificacion->link ?? '';
        $tiempo = $this->tiempo_transcurrido($notificacion->created_at);

        ob_start();
        ?>
        <div class="<?php echo esc_attr(implode(' ', $clases)); ?>"
             data-id="<?php echo esc_attr($notificacion->id); ?>">
            <div class="flavor-notificacion-icono" style="color: <?php echo esc_attr($color); ?>">
                <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
            </div>
            <div class="flavor-notificacion-contenido">
                <?php if ($enlace): ?>
                    <a href="<?php echo esc_url($enlace); ?>" class="flavor-notificacion-titulo">
                        <?php echo esc_html($notificacion->title); ?>
                    </a>
                <?php else: ?>
                    <span class="flavor-notificacion-titulo"><?php echo esc_html($notificacion->title); ?></span>
                <?php endif; ?>

                <?php if (!$compact && !empty($notificacion->message)): ?>
                    <p class="flavor-notificacion-mensaje"><?php echo wp_kses_post($notificacion->message); ?></p>
                <?php endif; ?>

                <span class="flavor-notificacion-tiempo">
                    <?php echo esc_html($tiempo); ?>
                </span>
            </div>
            <?php if (empty($notificacion->is_read)): ?>
                <button type="button" class="flavor-btn-marcar-leida"
                        data-id="<?php echo esc_attr($notificacion->id); ?>"
                        title="<?php esc_attr_e('Marcar como leída', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-dismiss"></span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Calcula el tiempo transcurrido desde una fecha
     *
     * @param string $fecha Fecha en formato MySQL
     * @return string
     */
    private function tiempo_transcurrido($fecha) {
        $timestamp = strtotime($fecha);
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return __('hace un momento', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        $intervalos = [
            31536000 => ['año', 'años'],
            2592000  => ['mes', 'meses'],
            604800   => ['semana', 'semanas'],
            86400    => ['día', 'días'],
            3600     => ['hora', 'horas'],
            60       => ['minuto', 'minutos'],
        ];

        foreach ($intervalos as $segundos => $nombres) {
            $cantidad = floor($diff / $segundos);
            if ($cantidad >= 1) {
                $nombre = $cantidad === 1 ? $nombres[0] : $nombres[1];
                return sprintf(
                    /* translators: %1$d: quantity, %2$s: time unit */
                    __('hace %1$d %2$s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $cantidad,
                    $nombre
                );
            }
        }

        return __('hace un momento', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Renderiza el widget para el dashboard
     *
     * @return string
     */
    public function render_dashboard_widget() {
        return do_shortcode('[flavor_notificaciones limite="5"]');
    }
}

// Inicializar después de que el notification manager esté disponible
add_action('init', function() {
    Flavor_Notifications_Widget::get_instance();
}, 20);
