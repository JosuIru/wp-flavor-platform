<?php
/**
 * Dashboard Tab para Marketplace
 *
 * @package FlavorChatIA
 * @subpackage Modules\Marketplace
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Marketplace_Dashboard_Tab {

    private static $instancia = null;

    private function __construct() {
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs']);
    }

    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    public function registrar_tabs($tabs) {
        $tabs['marketplace-resumen'] = [
            'label' => __('Marketplace', 'flavor-chat-ia'),
            'icon' => 'cart',
            'callback' => [$this, 'render_tab_resumen'],
            'orden' => 50,
        ];

        $tabs['marketplace-mis-anuncios'] = [
            'label' => __('Mis Anuncios', 'flavor-chat-ia'),
            'icon' => 'megaphone',
            'callback' => [$this, 'render_tab_mis_anuncios'],
            'orden' => 51,
        ];

        $tabs['marketplace-favoritos'] = [
            'label' => __('Favoritos', 'flavor-chat-ia'),
            'icon' => 'heart',
            'callback' => [$this, 'render_tab_favoritos'],
            'orden' => 52,
        ];

        return $tabs;
    }

    public function render_tab_resumen() {
        $user_id = get_current_user_id();
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
        $tabla_fav = $wpdb->prefix . 'flavor_marketplace_favoritos';

        $total_anuncios = 0;
        $mis_anuncios = 0;
        $mis_favoritos = 0;
        $mis_ventas = 0;

        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $total_anuncios = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla} WHERE estado = 'publicado'"
            );

            if ($user_id) {
                $mis_anuncios = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d",
                    $user_id
                ));

                $mis_ventas = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d AND estado = 'vendido'",
                    $user_id
                ));
            }
        }

        if ($user_id && Flavor_Chat_Helpers::tabla_existe($tabla_fav)) {
            $mis_favoritos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_fav} WHERE usuario_id = %d",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel flavor-marketplace-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-cart"></span> <?php esc_html_e('Marketplace Vecinal', 'flavor-chat-ia'); ?></h2>
                <p class="flavor-panel-subtitle"><?php esc_html_e('Compra, vende e intercambia con tus vecinos', 'flavor-chat-ia'); ?></p>
            </div>

            <div class="flavor-panel-kpis">
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-tag"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($total_anuncios); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Anuncios Activos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <span class="flavor-kpi-icon dashicons dashicons-megaphone"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_anuncios); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Mis Anuncios', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-primary">
                    <span class="flavor-kpi-icon dashicons dashicons-heart"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_favoritos); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Favoritos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card flavor-kpi-success">
                    <span class="flavor-kpi-icon dashicons dashicons-yes-alt"></span>
                    <div class="flavor-kpi-content">
                        <span class="flavor-kpi-value"><?php echo number_format_i18n($mis_ventas); ?></span>
                        <span class="flavor-kpi-label"><?php esc_html_e('Vendidos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel-actions">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', 'publicar')); ?>" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Publicar Anuncio', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', '')); ?>" class="flavor-btn flavor-btn-secondary">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Explorar', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function render_tab_mis_anuncios() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';

        $anuncios = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
            $anuncios = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla} WHERE usuario_id = %d ORDER BY created_at DESC LIMIT 10",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e('Mis Anuncios', 'flavor-chat-ia'); ?></h2>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', 'publicar')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-sm">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Nuevo', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($anuncios)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No has publicado ningún anuncio.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', 'publicar')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Publicar mi primer anuncio', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <div class="flavor-card">
                            <?php if (!empty($anuncio->imagen_principal)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($anuncio->imagen_principal); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <span class="flavor-badge flavor-badge-<?php echo $anuncio->estado === 'publicado' ? 'success' : 'secondary'; ?>">
                                    <?php echo esc_html(ucfirst($anuncio->estado)); ?>
                                </span>
                                <h4><?php echo esc_html($anuncio->titulo); ?></h4>
                                <?php if ($anuncio->precio > 0): ?>
                                    <p class="flavor-precio"><?php echo number_format_i18n($anuncio->precio, 2); ?> €</p>
                                <?php elseif ($anuncio->es_gratuito): ?>
                                    <p class="flavor-precio flavor-gratis"><?php esc_html_e('Gratis', 'flavor-chat-ia'); ?></p>
                                <?php endif; ?>
                                <p class="flavor-text-muted">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php echo number_format_i18n($anuncio->visualizaciones); ?>
                                    <span class="dashicons dashicons-heart" style="margin-left:8px"></span>
                                    <?php echo number_format_i18n($anuncio->favoritos_count); ?>
                                </p>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', 'detalle') . '?anuncio_id=' . $anuncio->id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(add_query_arg(['tab' => 'publicar', 'editar' => $anuncio->id], Flavor_Chat_Helpers::get_action_url('marketplace', ''))); ?>" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                                    <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_tab_favoritos() {
        $user_id = get_current_user_id();
        if (!$user_id) {
            echo '<p>' . esc_html__('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
        $tabla_fav = $wpdb->prefix . 'flavor_marketplace_favoritos';

        $favoritos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla) && Flavor_Chat_Helpers::tabla_existe($tabla_fav)) {
            $favoritos = $wpdb->get_results($wpdb->prepare(
                "SELECT a.* FROM {$tabla} a
                 JOIN {$tabla_fav} f ON a.id = f.anuncio_id
                 WHERE f.usuario_id = %d AND a.estado = 'publicado'
                 ORDER BY f.created_at DESC LIMIT 12",
                $user_id
            ));
        }

        ?>
        <div class="flavor-panel">
            <div class="flavor-panel-header">
                <h2><span class="dashicons dashicons-heart"></span> <?php esc_html_e('Mis Favoritos', 'flavor-chat-ia'); ?></h2>
            </div>

            <?php if (empty($favoritos)): ?>
                <div class="flavor-empty-state">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php esc_html_e('No tienes anuncios favoritos.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', '')); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar marketplace', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-cards-grid flavor-cards-grid-3">
                    <?php foreach ($favoritos as $anuncio): ?>
                        <div class="flavor-card">
                            <?php if (!empty($anuncio->imagen_principal)): ?>
                                <div class="flavor-card-image">
                                    <img src="<?php echo esc_url($anuncio->imagen_principal); ?>" alt="">
                                </div>
                            <?php endif; ?>
                            <div class="flavor-card-body">
                                <h4><?php echo esc_html($anuncio->titulo); ?></h4>
                                <?php if ($anuncio->precio > 0): ?>
                                    <p class="flavor-precio"><?php echo number_format_i18n($anuncio->precio, 2); ?> €</p>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-card-footer">
                                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', 'detalle') . '?anuncio_id=' . $anuncio->id); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
