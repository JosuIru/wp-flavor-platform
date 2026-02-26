<?php
/**
 * Shortcodes del sistema de reputación
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reputation_Shortcodes {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->register_shortcodes();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Registrar shortcodes
     */
    private function register_shortcodes() {
        add_shortcode('flavor_leaderboard', [$this, 'shortcode_leaderboard']);
        add_shortcode('flavor_mi_reputacion', [$this, 'shortcode_mi_reputacion']);
        add_shortcode('flavor_mis_badges', [$this, 'shortcode_mis_badges']);
        add_shortcode('flavor_badges_disponibles', [$this, 'shortcode_badges_disponibles']);
        add_shortcode('flavor_historial_puntos', [$this, 'shortcode_historial_puntos']);
        add_shortcode('flavor_nivel_usuario', [$this, 'shortcode_nivel_usuario']);
    }

    /**
     * Cargar assets
     */
    public function enqueue_assets() {
        if (!is_singular() && !is_page()) {
            return;
        }

        wp_enqueue_style(
            'flavor-reputation',
            FLAVOR_CHAT_IA_URL . 'assets/css/reputation.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );
    }

    /**
     * Shortcode: Leaderboard
     * [flavor_leaderboard periodo="total" limite="10"]
     */
    public function shortcode_leaderboard($atts) {
        $atts = shortcode_atts([
            'periodo' => 'total',
            'limite' => 10,
            'mostrar_avatar' => 'true',
            'mostrar_nivel' => 'true'
        ], $atts);

        if (!function_exists('flavor_reputation')) {
            return '<p class="flavor-error">Sistema de reputación no disponible.</p>';
        }

        $leaderboard = flavor_reputation()->get_leaderboard($atts['periodo'], (int) $atts['limite']);

        if (empty($leaderboard)) {
            return '<p class="flavor-empty">No hay datos de clasificación disponibles.</p>';
        }

        $periodo_label = [
            'total' => __('Todos los tiempos', 'flavor-chat-ia'),
            'mes' => __('Este mes', 'flavor-chat-ia'),
            'semana' => __('Esta semana', 'flavor-chat-ia')
        ];

        ob_start();
        ?>
        <div class="flavor-leaderboard">
            <div class="leaderboard-header">
                <h3><?php _e('Clasificación', 'flavor-chat-ia'); ?></h3>
                <span class="leaderboard-periodo"><?php echo esc_html($periodo_label[$atts['periodo']] ?? $atts['periodo']); ?></span>
            </div>
            <div class="leaderboard-list">
                <?php foreach ($leaderboard as $usuario): ?>
                <div class="leaderboard-item <?php echo $usuario->posicion <= 3 ? 'top-' . $usuario->posicion : ''; ?>">
                    <div class="leaderboard-posicion">
                        <?php if ($usuario->posicion === 1): ?>
                            <span class="medal gold">🥇</span>
                        <?php elseif ($usuario->posicion === 2): ?>
                            <span class="medal silver">🥈</span>
                        <?php elseif ($usuario->posicion === 3): ?>
                            <span class="medal bronze">🥉</span>
                        <?php else: ?>
                            <span class="numero"><?php echo $usuario->posicion; ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($atts['mostrar_avatar'] === 'true'): ?>
                    <div class="leaderboard-avatar">
                        <img src="<?php echo esc_url($usuario->avatar_url); ?>" alt="">
                    </div>
                    <?php endif; ?>
                    <div class="leaderboard-info">
                        <div class="leaderboard-nombre"><?php echo esc_html($usuario->display_name); ?></div>
                        <?php if ($atts['mostrar_nivel'] === 'true'): ?>
                        <div class="leaderboard-nivel"><?php echo esc_html($usuario->nivel_nombre); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="leaderboard-puntos">
                        <span class="puntos-valor"><?php echo number_format($usuario->puntos); ?></span>
                        <span class="puntos-label"><?php _e('pts', 'flavor-chat-ia'); ?></span>
                    </div>
                    <?php if ($usuario->racha_dias > 0): ?>
                    <div class="leaderboard-racha" title="<?php esc_attr_e('Racha de días', 'flavor-chat-ia'); ?>">
                        🔥 <?php echo $usuario->racha_dias; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi reputación
     * [flavor_mi_reputacion mostrar_progreso="true"]
     */
    public function shortcode_mi_reputacion($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tu reputación.', 'flavor-chat-ia') . '</p>';
        }

        $atts = shortcode_atts([
            'mostrar_progreso' => 'true',
            'mostrar_badges' => 'true',
            'limite_badges' => 5
        ], $atts);

        if (!function_exists('flavor_reputation')) {
            return '<p class="flavor-error">Sistema de reputación no disponible.</p>';
        }

        $usuario_id = get_current_user_id();
        $reputacion = flavor_reputation()->get_reputacion_usuario($usuario_id);

        ob_start();
        ?>
        <div class="flavor-mi-reputacion">
            <div class="reputacion-header">
                <div class="reputacion-nivel">
                    <span class="nivel-badge nivel-<?php echo esc_attr($reputacion['nivel']); ?>">
                        <?php echo esc_html($reputacion['nivel_nombre']); ?>
                    </span>
                </div>
                <div class="reputacion-puntos">
                    <span class="puntos-total"><?php echo number_format($reputacion['puntos_totales']); ?></span>
                    <span class="puntos-label"><?php _e('puntos', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if ($atts['mostrar_progreso'] === 'true' && $reputacion['siguiente_nivel']): ?>
            <div class="reputacion-progreso">
                <div class="progreso-info">
                    <span><?php _e('Progreso al siguiente nivel', 'flavor-chat-ia'); ?></span>
                    <span class="progreso-siguiente"><?php echo esc_html($reputacion['siguiente_nivel']['nombre']); ?></span>
                </div>
                <div class="progreso-barra">
                    <div class="progreso-fill" style="width: <?php echo $reputacion['progreso_nivel']; ?>%"></div>
                </div>
                <div class="progreso-porcentaje"><?php echo $reputacion['progreso_nivel']; ?>%</div>
            </div>
            <?php endif; ?>

            <div class="reputacion-stats">
                <div class="stat-item">
                    <span class="stat-valor"><?php echo number_format($reputacion['puntos_mes']); ?></span>
                    <span class="stat-label"><?php _e('Este mes', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-valor"><?php echo number_format($reputacion['puntos_semana']); ?></span>
                    <span class="stat-label"><?php _e('Esta semana', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-valor"><?php echo $reputacion['racha_dias']; ?> 🔥</span>
                    <span class="stat-label"><?php _e('Racha', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <?php if ($atts['mostrar_badges'] === 'true' && !empty($reputacion['badges'])): ?>
            <div class="reputacion-badges">
                <h4><?php _e('Mis Badges', 'flavor-chat-ia'); ?></h4>
                <div class="badges-grid">
                    <?php 
                    $badges_mostrar = array_slice($reputacion['badges'], 0, (int) $atts['limite_badges']);
                    foreach ($badges_mostrar as $badge): 
                    ?>
                    <div class="badge-item" style="--badge-color: <?php echo esc_attr($badge->color); ?>">
                        <span class="badge-icono"><?php echo esc_html($badge->icono); ?></span>
                        <span class="badge-nombre"><?php echo esc_html($badge->nombre); ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($reputacion['badges']) > (int) $atts['limite_badges']): ?>
                    <div class="badge-item badge-more">
                        <span>+<?php echo count($reputacion['badges']) - (int) $atts['limite_badges']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis badges
     * [flavor_mis_badges]
     */
    public function shortcode_mis_badges($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tus badges.', 'flavor-chat-ia') . '</p>';
        }

        if (!function_exists('flavor_reputation')) {
            return '<p class="flavor-error">Sistema de reputación no disponible.</p>';
        }

        $usuario_id = get_current_user_id();
        $badges = flavor_reputation()->get_badges_usuario($usuario_id);

        if (empty($badges)) {
            return '<p class="flavor-empty">' . __('Aún no has obtenido ningún badge. ¡Sigue participando!', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-mis-badges">
            <div class="badges-grid-full">
                <?php foreach ($badges as $badge): ?>
                <div class="badge-card" style="--badge-color: <?php echo esc_attr($badge->color); ?>">
                    <div class="badge-icono"><?php echo esc_html($badge->icono); ?></div>
                    <div class="badge-info">
                        <h4><?php echo esc_html($badge->nombre); ?></h4>
                        <p><?php echo esc_html($badge->descripcion); ?></p>
                        <span class="badge-fecha">
                            <?php echo sprintf(__('Obtenido el %s', 'flavor-chat-ia'), date_i18n('j M Y', strtotime($badge->fecha_obtenido))); ?>
                        </span>
                    </div>
                    <?php if ($badge->destacado): ?>
                    <span class="badge-destacado">⭐</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Badges disponibles
     * [flavor_badges_disponibles]
     */
    public function shortcode_badges_disponibles($atts) {
        if (!function_exists('flavor_reputation')) {
            return '<p class="flavor-error">Sistema de reputación no disponible.</p>';
        }

        $badges = flavor_reputation()->get_badges_disponibles();
        $usuario_id = get_current_user_id();
        $badges_usuario = [];

        if ($usuario_id) {
            $mis_badges = flavor_reputation()->get_badges_usuario($usuario_id);
            foreach ($mis_badges as $b) {
                $badges_usuario[$b->id] = true;
            }
        }

        if (empty($badges)) {
            return '<p class="flavor-empty">' . __('No hay badges disponibles.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-badges-disponibles">
            <div class="badges-grid-full">
                <?php foreach ($badges as $badge): 
                    $obtenido = isset($badges_usuario[$badge->id]);
                ?>
                <div class="badge-card <?php echo $obtenido ? 'obtenido' : 'bloqueado'; ?>" style="--badge-color: <?php echo esc_attr($badge->color); ?>">
                    <div class="badge-icono"><?php echo esc_html($badge->icono); ?></div>
                    <div class="badge-info">
                        <h4><?php echo esc_html($badge->nombre); ?></h4>
                        <p><?php echo esc_html($badge->descripcion); ?></p>
                        <?php if ($badge->puntos_requeridos > 0): ?>
                        <span class="badge-requisito">
                            <?php echo sprintf(__('%s puntos requeridos', 'flavor-chat-ia'), number_format($badge->puntos_requeridos)); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="badge-estado">
                        <?php if ($obtenido): ?>
                            <span class="estado-obtenido">✓</span>
                        <?php else: ?>
                            <span class="estado-bloqueado">🔒</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Historial de puntos
     * [flavor_historial_puntos limite="10"]
     */
    public function shortcode_historial_puntos($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Inicia sesión para ver tu historial.', 'flavor-chat-ia') . '</p>';
        }

        $atts = shortcode_atts([
            'limite' => 10
        ], $atts);

        if (!function_exists('flavor_reputation')) {
            return '<p class="flavor-error">Sistema de reputación no disponible.</p>';
        }

        $usuario_id = get_current_user_id();
        $historial = flavor_reputation()->get_historial_puntos($usuario_id, (int) $atts['limite']);

        if (empty($historial)) {
            return '<p class="flavor-empty">' . __('No hay actividad registrada aún.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-historial-puntos">
            <div class="historial-lista">
                <?php foreach ($historial as $item): ?>
                <div class="historial-item">
                    <div class="historial-puntos <?php echo $item->puntos > 0 ? 'positivo' : 'negativo'; ?>">
                        <?php echo ($item->puntos > 0 ? '+' : '') . $item->puntos; ?>
                    </div>
                    <div class="historial-info">
                        <div class="historial-descripcion"><?php echo esc_html($item->descripcion); ?></div>
                        <div class="historial-fecha">
                            <?php echo human_time_diff(strtotime($item->fecha_creacion), current_time('timestamp')); ?> 
                            <?php _e('atrás', 'flavor-chat-ia'); ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Nivel de usuario (inline)
     * [flavor_nivel_usuario id="123"]
     */
    public function shortcode_nivel_usuario($atts) {
        $atts = shortcode_atts([
            'id' => get_current_user_id()
        ], $atts);

        $usuario_id = (int) $atts['id'];
        if (!$usuario_id) {
            return '';
        }

        if (!function_exists('flavor_reputation')) {
            return '';
        }

        $reputacion = flavor_reputation()->get_reputacion_usuario($usuario_id);

        return sprintf(
            '<span class="flavor-nivel-inline nivel-%s">%s</span>',
            esc_attr($reputacion['nivel']),
            esc_html($reputacion['nivel_nombre'])
        );
    }
}

// Inicializar
add_action('init', function() {
    if (function_exists('flavor_reputation')) {
        Flavor_Reputation_Shortcodes::get_instance();
    }
});
