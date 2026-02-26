<?php
/**
 * Template: Panel Principal del Consumidor - Grupos de Consumo
 *
 * Dashboard principal con resumen de actividad, ciclo actual y accesos rapidos.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="gc-panel-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Acceso restringido', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('Inicia sesion para acceder a tu panel de consumidor.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Iniciar sesion', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

global $wpdb;
$user_id = get_current_user_id();
$user = wp_get_current_user();

$tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
$tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
$tabla_lista = $wpdb->prefix . 'flavor_gc_lista_compra';

// Obtener ciclo activo
$ciclo_activo = null;
$args_ciclo = [
    'post_type'      => 'gc_ciclo',
    'post_status'    => ['publish', 'gc_abierto'],
    'posts_per_page' => 1,
    'meta_query'     => [
        ['key' => '_gc_estado', 'value' => 'abierto'],
    ],
];
$query_ciclo = new WP_Query($args_ciclo);
if ($query_ciclo->have_posts()) {
    $ciclo_activo = $query_ciclo->posts[0];
}

// Datos del ciclo activo
$meta_ciclo = [];
if ($ciclo_activo) {
    $meta_ciclo = [
        'fecha_cierre'  => get_post_meta($ciclo_activo->ID, '_gc_fecha_cierre', true),
        'fecha_entrega' => get_post_meta($ciclo_activo->ID, '_gc_fecha_entrega', true),
        'hora_entrega'  => get_post_meta($ciclo_activo->ID, '_gc_hora_entrega', true),
        'lugar_entrega' => get_post_meta($ciclo_activo->ID, '_gc_lugar_entrega', true),
    ];
}

// Estadisticas del usuario
$estadisticas = [
    'total_pedidos' => (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT ciclo_id) FROM {$tabla_entregas} WHERE usuario_id = %d",
        $user_id
    )),
    'gasto_total' => (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(total_final) FROM {$tabla_entregas} WHERE usuario_id = %d AND estado_pago = 'completado'",
        $user_id
    )),
    'pedidos_pendientes' => (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_entregas} WHERE usuario_id = %d AND estado_pago IN ('pendiente', 'pendiente_recogida')",
        $user_id
    )),
    'items_cesta' => (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$tabla_lista} WHERE usuario_id = %d",
        $user_id
    )),
];

// Pedidos recientes
$pedidos_recientes = $wpdb->get_results($wpdb->prepare(
    "SELECT e.*, c.post_title as ciclo_nombre
     FROM {$tabla_entregas} e
     LEFT JOIN {$wpdb->posts} c ON e.ciclo_id = c.ID
     WHERE e.usuario_id = %d
     ORDER BY e.fecha_creacion DESC
     LIMIT 3",
    $user_id
));

// Grupos del usuario
$mis_grupos = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, g.post_title as grupo_nombre
     FROM {$tabla_consumidores} c
     LEFT JOIN {$wpdb->posts} g ON c.grupo_id = g.ID
     WHERE c.usuario_id = %d AND c.estado = 'activo'",
    $user_id
));

// Notificaciones pendientes
$tabla_notificaciones = $wpdb->prefix . 'flavor_gc_notificaciones';
$notificaciones_count = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_notificaciones} WHERE usuario_id = %d AND leida = 0",
    $user_id
));

// Accesos rapidos
$accesos_rapidos = [
    [
        'titulo' => __('Catalogo', 'flavor-chat-ia'),
        'desc'   => __('Ver productos disponibles', 'flavor-chat-ia'),
        'url'    => home_url('/mi-portal/grupos-consumo/catalogo/'),
        'icon'   => 'products',
        'color'  => '#4caf50',
    ],
    [
        'titulo' => __('Mi Cesta', 'flavor-chat-ia'),
        'desc'   => sprintf(_n('%d producto', '%d productos', $estadisticas['items_cesta'], 'flavor-chat-ia'), $estadisticas['items_cesta']),
        'url'    => home_url('/mi-portal/grupos-consumo/mi-cesta/'),
        'icon'   => 'cart',
        'color'  => '#2196f3',
        'badge'  => $estadisticas['items_cesta'],
    ],
    [
        'titulo' => __('Mis Pedidos', 'flavor-chat-ia'),
        'desc'   => __('Historial de pedidos', 'flavor-chat-ia'),
        'url'    => home_url('/mi-portal/grupos-consumo/mis-pedidos/'),
        'icon'   => 'clipboard',
        'color'  => '#ff9800',
        'badge'  => $estadisticas['pedidos_pendientes'],
    ],
    [
        'titulo' => __('Productores', 'flavor-chat-ia'),
        'desc'   => __('Conoce a los productores', 'flavor-chat-ia'),
        'url'    => home_url('/mi-portal/grupos-consumo/productores/'),
        'icon'   => 'store',
        'color'  => '#9c27b0',
    ],
];
?>

<div class="gc-panel-container">
    <!-- Bienvenida -->
    <header class="gc-panel-header">
        <div class="gc-bienvenida">
            <h2>
                <?php
                $hora = (int) date('H');
                if ($hora < 12) {
                    $saludo = __('Buenos dias', 'flavor-chat-ia');
                } elseif ($hora < 20) {
                    $saludo = __('Buenas tardes', 'flavor-chat-ia');
                } else {
                    $saludo = __('Buenas noches', 'flavor-chat-ia');
                }
                echo esc_html($saludo . ', ' . $user->display_name);
                ?>
            </h2>
            <p class="gc-fecha-hoy">
                <?php echo esc_html(date_i18n('l, j \d\e F \d\e Y')); ?>
            </p>
        </div>

        <?php if ($notificaciones_count > 0) : ?>
        <a href="<?php echo esc_url(home_url('/mi-portal/notificaciones/')); ?>" class="gc-notificaciones-btn">
            <span class="dashicons dashicons-bell"></span>
            <span class="gc-notif-count"><?php echo esc_html($notificaciones_count); ?></span>
        </a>
        <?php endif; ?>
    </header>

    <!-- Ciclo activo -->
    <?php if ($ciclo_activo) : ?>
    <section class="gc-panel-ciclo">
        <div class="gc-ciclo-card gc-ciclo-activo">
            <div class="gc-ciclo-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="gc-ciclo-info">
                <span class="gc-ciclo-estado"><?php esc_html_e('Ciclo de pedidos abierto', 'flavor-chat-ia'); ?></span>
                <h3><?php echo esc_html($ciclo_activo->post_title); ?></h3>
                <div class="gc-ciclo-fechas">
                    <span>
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Cierra:', 'flavor-chat-ia'); ?>
                        <?php echo esc_html(date_i18n('j M, H:i', strtotime($meta_ciclo['fecha_cierre']))); ?>
                    </span>
                    <span>
                        <span class="dashicons dashicons-location"></span>
                        <?php esc_html_e('Entrega:', 'flavor-chat-ia'); ?>
                        <?php echo esc_html(date_i18n('j M', strtotime($meta_ciclo['fecha_entrega']))); ?>
                    </span>
                </div>
            </div>
            <div class="gc-ciclo-acciones">
                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/catalogo/')); ?>" class="gc-btn gc-btn-primary">
                    <?php esc_html_e('Hacer pedido', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </section>
    <?php else : ?>
    <section class="gc-panel-ciclo">
        <div class="gc-ciclo-card gc-ciclo-cerrado">
            <div class="gc-ciclo-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="gc-ciclo-info">
                <span class="gc-ciclo-estado"><?php esc_html_e('Sin ciclo activo', 'flavor-chat-ia'); ?></span>
                <p><?php esc_html_e('No hay ciclos de pedidos abiertos actualmente. Te notificaremos cuando se abra el proximo.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Accesos rapidos -->
    <section class="gc-panel-accesos">
        <h3 class="gc-section-title"><?php esc_html_e('Acceso rapido', 'flavor-chat-ia'); ?></h3>
        <div class="gc-accesos-grid">
            <?php foreach ($accesos_rapidos as $acceso) : ?>
            <a href="<?php echo esc_url($acceso['url']); ?>" class="gc-acceso-card">
                <div class="gc-acceso-icon" style="background: <?php echo esc_attr($acceso['color']); ?>20; color: <?php echo esc_attr($acceso['color']); ?>;">
                    <span class="dashicons dashicons-<?php echo esc_attr($acceso['icon']); ?>"></span>
                    <?php if (!empty($acceso['badge']) && $acceso['badge'] > 0) : ?>
                    <span class="gc-acceso-badge"><?php echo esc_html($acceso['badge']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="gc-acceso-info">
                    <strong><?php echo esc_html($acceso['titulo']); ?></strong>
                    <span><?php echo esc_html($acceso['desc']); ?></span>
                </div>
                <span class="gc-acceso-arrow">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="gc-panel-grid">
        <!-- Resumen de actividad -->
        <section class="gc-panel-resumen">
            <h3 class="gc-section-title"><?php esc_html_e('Tu actividad', 'flavor-chat-ia'); ?></h3>
            <div class="gc-stats-grid">
                <div class="gc-stat-card">
                    <span class="gc-stat-icon" style="background: #e8f5e9; color: #4caf50;">
                        <span class="dashicons dashicons-clipboard"></span>
                    </span>
                    <div class="gc-stat-info">
                        <span class="gc-stat-value"><?php echo esc_html($estadisticas['total_pedidos']); ?></span>
                        <span class="gc-stat-label"><?php esc_html_e('Pedidos realizados', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <div class="gc-stat-card">
                    <span class="gc-stat-icon" style="background: #e3f2fd; color: #2196f3;">
                        <span class="dashicons dashicons-money-alt"></span>
                    </span>
                    <div class="gc-stat-info">
                        <span class="gc-stat-value"><?php echo number_format($estadisticas['gasto_total'], 0, ',', '.'); ?> &euro;</span>
                        <span class="gc-stat-label"><?php esc_html_e('Total consumido', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>

                <?php if ($estadisticas['pedidos_pendientes'] > 0) : ?>
                <div class="gc-stat-card gc-stat-alerta">
                    <span class="gc-stat-icon" style="background: #fff3e0; color: #ff9800;">
                        <span class="dashicons dashicons-warning"></span>
                    </span>
                    <div class="gc-stat-info">
                        <span class="gc-stat-value"><?php echo esc_html($estadisticas['pedidos_pendientes']); ?></span>
                        <span class="gc-stat-label"><?php esc_html_e('Pedidos pendientes', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Pedidos recientes -->
        <section class="gc-panel-pedidos">
            <div class="gc-section-header">
                <h3 class="gc-section-title"><?php esc_html_e('Pedidos recientes', 'flavor-chat-ia'); ?></h3>
                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/mis-pedidos/')); ?>" class="gc-link-ver-todos">
                    <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php if (empty($pedidos_recientes)) : ?>
            <div class="gc-pedidos-empty">
                <span class="dashicons dashicons-clipboard"></span>
                <p><?php esc_html_e('Aun no has realizado ningun pedido.', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(home_url('/mi-portal/grupos-consumo/catalogo/')); ?>" class="gc-btn gc-btn-sm gc-btn-primary">
                    <?php esc_html_e('Ver catalogo', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php else : ?>
            <div class="gc-pedidos-lista">
                <?php foreach ($pedidos_recientes as $pedido) :
                    $estado_class = '';
                    $estado_label = '';
                    switch ($pedido->estado_pago) {
                        case 'completado':
                            $estado_class = 'gc-estado-pagado';
                            $estado_label = __('Pagado', 'flavor-chat-ia');
                            break;
                        case 'pendiente':
                        case 'pendiente_recogida':
                            $estado_class = 'gc-estado-pendiente';
                            $estado_label = __('Pendiente', 'flavor-chat-ia');
                            break;
                        default:
                            $estado_class = 'gc-estado-otro';
                            $estado_label = ucfirst($pedido->estado_pago);
                    }
                ?>
                <a href="<?php echo esc_url(add_query_arg('entrega_id', $pedido->id, home_url('/mi-portal/grupos-consumo/mi-pedido/'))); ?>" class="gc-pedido-item">
                    <div class="gc-pedido-info">
                        <strong>#<?php echo esc_html($pedido->id); ?> - <?php echo esc_html($pedido->ciclo_nombre); ?></strong>
                        <span class="gc-pedido-fecha">
                            <?php echo esc_html(date_i18n('j M Y', strtotime($pedido->fecha_creacion))); ?>
                        </span>
                    </div>
                    <div class="gc-pedido-derecha">
                        <span class="gc-pedido-total"><?php echo number_format($pedido->total_final, 2, ',', '.'); ?> &euro;</span>
                        <span class="gc-pedido-estado <?php echo esc_attr($estado_class); ?>">
                            <?php echo esc_html($estado_label); ?>
                        </span>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- Mis grupos -->
    <?php if (!empty($mis_grupos)) : ?>
    <section class="gc-panel-grupos">
        <h3 class="gc-section-title"><?php esc_html_e('Mis grupos', 'flavor-chat-ia'); ?></h3>
        <div class="gc-grupos-lista">
            <?php foreach ($mis_grupos as $grupo) : ?>
            <a href="<?php echo esc_url(get_permalink($grupo->grupo_id)); ?>" class="gc-grupo-item">
                <span class="gc-grupo-icon">
                    <span class="dashicons dashicons-groups"></span>
                </span>
                <div class="gc-grupo-info">
                    <strong><?php echo esc_html($grupo->grupo_nombre); ?></strong>
                    <span class="gc-grupo-rol">
                        <?php
                        $roles = [
                            'consumidor'  => __('Consumidor', 'flavor-chat-ia'),
                            'coordinador' => __('Coordinador', 'flavor-chat-ia'),
                            'productor'   => __('Productor', 'flavor-chat-ia'),
                        ];
                        echo esc_html($roles[$grupo->rol] ?? $grupo->rol);
                        ?>
                    </span>
                </div>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Ayuda -->
    <section class="gc-panel-ayuda">
        <div class="gc-ayuda-card">
            <span class="gc-ayuda-icon">
                <span class="dashicons dashicons-editor-help"></span>
            </span>
            <div class="gc-ayuda-content">
                <strong><?php esc_html_e('Necesitas ayuda?', 'flavor-chat-ia'); ?></strong>
                <p><?php esc_html_e('Consulta nuestras guias o contacta con los coordinadores del grupo.', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="<?php echo esc_url(home_url('/mi-portal/ayuda/')); ?>" class="gc-btn gc-btn-sm gc-btn-secondary">
                <?php esc_html_e('Ver ayuda', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </section>
</div>

<style>
.gc-panel-container {
    max-width: 900px;
}
.gc-panel-login-required {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 10px;
}
.gc-panel-login-required .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
    margin-bottom: 15px;
}
.gc-panel-login-required h3 {
    margin: 0 0 10px 0;
    color: #666;
}
.gc-panel-login-required p {
    color: #999;
    margin-bottom: 20px;
}
.gc-panel-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 25px;
}
.gc-bienvenida h2 {
    margin: 0 0 5px 0;
    font-size: 1.5rem;
}
.gc-fecha-hoy {
    margin: 0;
    color: #757575;
    font-size: 14px;
}
.gc-notificaciones-btn {
    position: relative;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 50%;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
}
.gc-notificaciones-btn:hover {
    background: #e0e0e0;
}
.gc-notif-count {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 20px;
    height: 20px;
    background: #f44336;
    color: #fff;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 5px;
}
.gc-panel-ciclo {
    margin-bottom: 25px;
}
.gc-ciclo-card {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.gc-ciclo-activo {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border: 1px solid #a5d6a7;
}
.gc-ciclo-cerrado {
    background: #f5f5f5;
    border: 1px solid #e0e0e0;
}
.gc-ciclo-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.gc-ciclo-activo .gc-ciclo-icon {
    background: #4caf50;
    color: #fff;
}
.gc-ciclo-cerrado .gc-ciclo-icon {
    background: #9e9e9e;
    color: #fff;
}
.gc-ciclo-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}
.gc-ciclo-info {
    flex: 1;
}
.gc-ciclo-estado {
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #666;
    margin-bottom: 5px;
}
.gc-ciclo-info h3 {
    margin: 0 0 8px 0;
    font-size: 1.1rem;
}
.gc-ciclo-info p {
    margin: 0;
    color: #666;
    font-size: 14px;
}
.gc-ciclo-fechas {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: #555;
}
.gc-ciclo-fechas span {
    display: flex;
    align-items: center;
    gap: 5px;
}
.gc-ciclo-fechas .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    color: #757575;
}
.gc-section-title {
    margin: 0 0 15px 0;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #666;
}
.gc-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}
.gc-link-ver-todos {
    font-size: 13px;
    color: #4caf50;
    text-decoration: none;
}
.gc-link-ver-todos:hover {
    text-decoration: underline;
}
.gc-panel-accesos {
    margin-bottom: 25px;
}
.gc-accesos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}
.gc-acceso-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    text-decoration: none;
    transition: all 0.2s;
}
.gc-acceso-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.12);
}
.gc-acceso-icon {
    position: relative;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.gc-acceso-icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}
.gc-acceso-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 18px;
    height: 18px;
    background: #f44336;
    color: #fff;
    border-radius: 9px;
    font-size: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-acceso-info {
    flex: 1;
    min-width: 0;
}
.gc-acceso-info strong {
    display: block;
    color: #333;
    margin-bottom: 2px;
}
.gc-acceso-info span {
    font-size: 12px;
    color: #9e9e9e;
}
.gc-acceso-arrow {
    color: #bdbdbd;
}
.gc-panel-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 25px;
}
.gc-panel-resumen,
.gc-panel-pedidos {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.gc-stats-grid {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.gc-stat-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #fafafa;
    border-radius: 8px;
}
.gc-stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-stat-info {
    flex: 1;
}
.gc-stat-value {
    display: block;
    font-size: 1.2rem;
    font-weight: 700;
    color: #333;
}
.gc-stat-label {
    font-size: 12px;
    color: #9e9e9e;
}
.gc-pedidos-empty {
    text-align: center;
    padding: 30px 15px;
}
.gc-pedidos-empty .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #ccc;
}
.gc-pedidos-empty p {
    color: #999;
    margin: 10px 0 15px;
    font-size: 14px;
}
.gc-pedidos-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.gc-pedido-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #fafafa;
    border-radius: 8px;
    text-decoration: none;
    transition: background 0.2s;
}
.gc-pedido-item:hover {
    background: #f0f0f0;
}
.gc-pedido-info strong {
    display: block;
    color: #333;
    font-size: 14px;
}
.gc-pedido-fecha {
    font-size: 12px;
    color: #9e9e9e;
}
.gc-pedido-derecha {
    text-align: right;
}
.gc-pedido-total {
    display: block;
    font-weight: 600;
    color: #333;
}
.gc-pedido-estado {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}
.gc-estado-pagado {
    background: #e8f5e9;
    color: #2e7d32;
}
.gc-estado-pendiente {
    background: #fff3e0;
    color: #ef6c00;
}
.gc-estado-otro {
    background: #f5f5f5;
    color: #757575;
}
.gc-panel-grupos {
    margin-bottom: 25px;
}
.gc-grupos-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.gc-grupo-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    text-decoration: none;
    transition: all 0.2s;
}
.gc-grupo-item:hover {
    transform: translateX(5px);
}
.gc-grupo-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #e8f5e9;
    color: #4caf50;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-grupo-info {
    flex: 1;
}
.gc-grupo-info strong {
    display: block;
    color: #333;
}
.gc-grupo-rol {
    font-size: 12px;
    color: #9e9e9e;
}
.gc-grupo-item > .dashicons {
    color: #bdbdbd;
}
.gc-panel-ayuda {
    margin-bottom: 20px;
}
.gc-ayuda-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #e3f2fd;
    border-radius: 10px;
    border: 1px solid #90caf9;
}
.gc-ayuda-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #2196f3;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.gc-ayuda-content {
    flex: 1;
}
.gc-ayuda-content strong {
    display: block;
    color: #333;
    margin-bottom: 3px;
}
.gc-ayuda-content p {
    margin: 0;
    font-size: 13px;
    color: #666;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
}
.gc-btn-sm {
    padding: 8px 14px;
    font-size: 13px;
}
.gc-btn-primary {
    background: #4caf50;
    color: #fff;
}
.gc-btn-primary:hover {
    background: #388e3c;
    color: #fff;
}
.gc-btn-secondary {
    background: #fff;
    color: #333;
    border: 1px solid #ddd;
}
.gc-btn-secondary:hover {
    background: #f5f5f5;
}
@media (max-width: 768px) {
    .gc-panel-grid {
        grid-template-columns: 1fr;
    }
    .gc-ciclo-card {
        flex-direction: column;
        text-align: center;
    }
    .gc-ciclo-fechas {
        justify-content: center;
        flex-wrap: wrap;
    }
    .gc-ayuda-card {
        flex-direction: column;
        text-align: center;
    }
}
@media (max-width: 500px) {
    .gc-accesos-grid {
        grid-template-columns: 1fr;
    }
}
</style>
