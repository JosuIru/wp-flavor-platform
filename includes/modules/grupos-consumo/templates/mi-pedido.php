<?php
/**
 * Template: Mi Pedido - Grupos de Consumo
 *
 * Muestra el detalle completo del pedido actual del usuario.
 *
 * @package FlavorChatIA
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="gc-pedido-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Acceso restringido', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('Inicia sesion para ver los detalles de tu pedido.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mi-pedido'))) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Iniciar sesion', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

global $wpdb;
$user_id = get_current_user_id();

// Obtener ID de entrega desde la URL o el mas reciente
$entrega_id = isset($_GET['entrega_id']) ? absint($_GET['entrega_id']) : 0;

$tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

// Obtener la entrega
if ($entrega_id) {
    $entrega = $wpdb->get_row($wpdb->prepare(
        "SELECT e.*, c.post_title as ciclo_nombre
         FROM {$tabla_entregas} e
         LEFT JOIN {$wpdb->posts} c ON e.ciclo_id = c.ID
         WHERE e.id = %d AND e.usuario_id = %d",
        $entrega_id,
        $user_id
    ));
} else {
    // Obtener el pedido mas reciente
    $entrega = $wpdb->get_row($wpdb->prepare(
        "SELECT e.*, c.post_title as ciclo_nombre
         FROM {$tabla_entregas} e
         LEFT JOIN {$wpdb->posts} c ON e.ciclo_id = c.ID
         WHERE e.usuario_id = %d
         ORDER BY e.fecha_creacion DESC
         LIMIT 1",
        $user_id
    ));
}

if (!$entrega) {
    echo '<div class="gc-pedido-no-encontrado">';
    echo '<span class="dashicons dashicons-clipboard"></span>';
    echo '<h3>' . esc_html__('Pedido no encontrado', 'flavor-chat-ia') . '</h3>';
    echo '<p>' . esc_html__('No se ha encontrado el pedido solicitado o no tienes pedidos aun.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos')) . '" class="gc-btn gc-btn-primary">';
    echo esc_html__('Ver catalogo', 'flavor-chat-ia');
    echo '</a></div>';
    return;
}

// Obtener items del pedido
$items_pedido = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*,
            prod.post_title as producto_nombre,
            pm_unidad.meta_value as unidad,
            pm_imagen.meta_value as imagen_id,
            pm_productor.meta_value as productor_id
     FROM {$tabla_pedidos} p
     LEFT JOIN {$wpdb->posts} prod ON p.producto_id = prod.ID
     LEFT JOIN {$wpdb->postmeta} pm_unidad ON p.producto_id = pm_unidad.post_id AND pm_unidad.meta_key = '_gc_unidad'
     LEFT JOIN {$wpdb->postmeta} pm_imagen ON p.producto_id = pm_imagen.post_id AND pm_imagen.meta_key = '_thumbnail_id'
     LEFT JOIN {$wpdb->postmeta} pm_productor ON p.producto_id = pm_productor.post_id AND pm_productor.meta_key = '_gc_productor_id'
     WHERE p.ciclo_id = %d AND p.usuario_id = %d
     ORDER BY prod.post_title ASC",
    $entrega->ciclo_id,
    $user_id
));

// Obtener datos del ciclo
$ciclo_meta = [
    'fecha_entrega'  => get_post_meta($entrega->ciclo_id, '_gc_fecha_entrega', true),
    'hora_entrega'   => get_post_meta($entrega->ciclo_id, '_gc_hora_entrega', true),
    'lugar_entrega'  => get_post_meta($entrega->ciclo_id, '_gc_lugar_entrega', true),
    'notas'          => get_post_meta($entrega->ciclo_id, '_gc_notas', true),
];

// Estados del pedido
$estados_pago = [
    'pendiente'          => ['label' => __('Pendiente de pago', 'flavor-chat-ia'), 'class' => 'gc-status-warning', 'icon' => 'clock'],
    'pendiente_recogida' => ['label' => __('Pago en recogida', 'flavor-chat-ia'), 'class' => 'gc-status-info', 'icon' => 'location'],
    'procesando'         => ['label' => __('Procesando pago', 'flavor-chat-ia'), 'class' => 'gc-status-info', 'icon' => 'update'],
    'completado'         => ['label' => __('Pagado', 'flavor-chat-ia'), 'class' => 'gc-status-success', 'icon' => 'yes'],
    'fallido'            => ['label' => __('Pago fallido', 'flavor-chat-ia'), 'class' => 'gc-status-error', 'icon' => 'no'],
    'reembolsado'        => ['label' => __('Reembolsado', 'flavor-chat-ia'), 'class' => 'gc-status-secondary', 'icon' => 'undo'],
];

$estados_recogida = [
    'pendiente' => ['label' => __('Pendiente de recoger', 'flavor-chat-ia'), 'class' => 'gc-status-warning', 'icon' => 'clock'],
    'preparado' => ['label' => __('Preparado para recoger', 'flavor-chat-ia'), 'class' => 'gc-status-info', 'icon' => 'yes-alt'],
    'recogido'  => ['label' => __('Recogido', 'flavor-chat-ia'), 'class' => 'gc-status-success', 'icon' => 'yes'],
];

$estado_pago_actual = $estados_pago[$entrega->estado_pago] ?? $estados_pago['pendiente'];
$estado_recogida_actual = $estados_recogida[$entrega->estado_recogida ?? 'pendiente'] ?? $estados_recogida['pendiente'];

// Agrupar items por productor
$items_por_productor = [];
foreach ($items_pedido as $item) {
    $productor_id = $item->productor_id ?: 0;
    if (!isset($items_por_productor[$productor_id])) {
        $productor = $productor_id ? get_post($productor_id) : null;
        $items_por_productor[$productor_id] = [
            'nombre' => $productor ? $productor->post_title : __('Sin productor', 'flavor-chat-ia'),
            'items'  => [],
        ];
    }
    $items_por_productor[$productor_id]['items'][] = $item;
}
?>

<div class="gc-mi-pedido-container">
    <!-- Cabecera del pedido -->
    <header class="gc-pedido-header">
        <div class="gc-pedido-titulo">
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos')); ?>" class="gc-btn-back">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
            </a>
            <div>
                <span class="gc-pedido-numero"><?php printf(__('Pedido #%d', 'flavor-chat-ia'), $entrega->id); ?></span>
                <h2><?php echo esc_html($entrega->ciclo_nombre ?: __('Ciclo de pedidos', 'flavor-chat-ia')); ?></h2>
            </div>
        </div>

        <div class="gc-pedido-estados">
            <span class="gc-status-badge <?php echo esc_attr($estado_pago_actual['class']); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr($estado_pago_actual['icon']); ?>"></span>
                <?php echo esc_html($estado_pago_actual['label']); ?>
            </span>
            <?php if ($entrega->estado_pago === 'completado') : ?>
            <span class="gc-status-badge <?php echo esc_attr($estado_recogida_actual['class']); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr($estado_recogida_actual['icon']); ?>"></span>
                <?php echo esc_html($estado_recogida_actual['label']); ?>
            </span>
            <?php endif; ?>
        </div>
    </header>

    <div class="gc-pedido-content">
        <!-- Panel principal -->
        <div class="gc-pedido-main">
            <!-- Timeline del pedido -->
            <div class="gc-pedido-timeline">
                <h3><?php esc_html_e('Estado del pedido', 'flavor-chat-ia'); ?></h3>
                <div class="gc-timeline">
                    <div class="gc-timeline-item gc-timeline-completado">
                        <span class="gc-timeline-icon">
                            <span class="dashicons dashicons-yes"></span>
                        </span>
                        <div class="gc-timeline-content">
                            <strong><?php esc_html_e('Pedido realizado', 'flavor-chat-ia'); ?></strong>
                            <span class="gc-timeline-fecha">
                                <?php echo esc_html(date_i18n('j M Y, H:i', strtotime($entrega->fecha_creacion))); ?>
                            </span>
                        </div>
                    </div>

                    <div class="gc-timeline-item <?php echo $entrega->estado_pago === 'completado' ? 'gc-timeline-completado' : 'gc-timeline-pendiente'; ?>">
                        <span class="gc-timeline-icon">
                            <?php if ($entrega->estado_pago === 'completado') : ?>
                            <span class="dashicons dashicons-yes"></span>
                            <?php else : ?>
                            <span class="gc-timeline-numero">2</span>
                            <?php endif; ?>
                        </span>
                        <div class="gc-timeline-content">
                            <strong><?php esc_html_e('Pago confirmado', 'flavor-chat-ia'); ?></strong>
                            <?php if ($entrega->fecha_pago) : ?>
                            <span class="gc-timeline-fecha">
                                <?php echo esc_html(date_i18n('j M Y, H:i', strtotime($entrega->fecha_pago))); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="gc-timeline-item <?php echo ($entrega->estado_recogida ?? '') === 'preparado' || ($entrega->estado_recogida ?? '') === 'recogido' ? 'gc-timeline-completado' : 'gc-timeline-pendiente'; ?>">
                        <span class="gc-timeline-icon">
                            <?php if (($entrega->estado_recogida ?? '') === 'preparado' || ($entrega->estado_recogida ?? '') === 'recogido') : ?>
                            <span class="dashicons dashicons-yes"></span>
                            <?php else : ?>
                            <span class="gc-timeline-numero">3</span>
                            <?php endif; ?>
                        </span>
                        <div class="gc-timeline-content">
                            <strong><?php esc_html_e('Pedido preparado', 'flavor-chat-ia'); ?></strong>
                        </div>
                    </div>

                    <div class="gc-timeline-item <?php echo ($entrega->estado_recogida ?? '') === 'recogido' ? 'gc-timeline-completado' : 'gc-timeline-pendiente'; ?>">
                        <span class="gc-timeline-icon">
                            <?php if (($entrega->estado_recogida ?? '') === 'recogido') : ?>
                            <span class="dashicons dashicons-yes"></span>
                            <?php else : ?>
                            <span class="gc-timeline-numero">4</span>
                            <?php endif; ?>
                        </span>
                        <div class="gc-timeline-content">
                            <strong><?php esc_html_e('Pedido recogido', 'flavor-chat-ia'); ?></strong>
                            <?php if ($entrega->fecha_recogida) : ?>
                            <span class="gc-timeline-fecha">
                                <?php echo esc_html(date_i18n('j M Y, H:i', strtotime($entrega->fecha_recogida))); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detalle de productos -->
            <div class="gc-pedido-productos">
                <h3>
                    <?php esc_html_e('Productos del pedido', 'flavor-chat-ia'); ?>
                    <span class="gc-productos-count">(<?php echo count($items_pedido); ?>)</span>
                </h3>

                <?php foreach ($items_por_productor as $productor_data) : ?>
                <div class="gc-productor-grupo">
                    <h4 class="gc-productor-nombre">
                        <span class="dashicons dashicons-store"></span>
                        <?php echo esc_html($productor_data['nombre']); ?>
                    </h4>

                    <div class="gc-items-lista">
                        <?php foreach ($productor_data['items'] as $item) :
                            $item_subtotal = (float) $item->cantidad * (float) $item->precio_unitario;
                            $unidad = $item->unidad ?: 'ud';

                            // Estado del item
                            $estado_item_class = '';
                            $estado_item_label = '';
                            switch ($item->estado) {
                                case 'confirmado':
                                    $estado_item_class = 'gc-item-confirmado';
                                    $estado_item_label = __('Confirmado', 'flavor-chat-ia');
                                    break;
                                case 'completado':
                                    $estado_item_class = 'gc-item-completado';
                                    $estado_item_label = __('Preparado', 'flavor-chat-ia');
                                    break;
                                case 'cancelado':
                                    $estado_item_class = 'gc-item-cancelado';
                                    $estado_item_label = __('Cancelado', 'flavor-chat-ia');
                                    break;
                                case 'sin_stock':
                                    $estado_item_class = 'gc-item-sin-stock';
                                    $estado_item_label = __('Sin stock', 'flavor-chat-ia');
                                    break;
                                default:
                                    $estado_item_class = 'gc-item-pendiente';
                                    $estado_item_label = __('Pendiente', 'flavor-chat-ia');
                            }
                        ?>
                        <div class="gc-pedido-item <?php echo esc_attr($estado_item_class); ?>">
                            <div class="gc-item-imagen">
                                <?php if ($item->imagen_id) : ?>
                                    <?php echo wp_get_attachment_image($item->imagen_id, 'thumbnail', false, ['class' => 'gc-item-thumb']); ?>
                                <?php else : ?>
                                    <div class="gc-item-thumb-placeholder">
                                        <span class="dashicons dashicons-carrot"></span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="gc-item-info">
                                <h5 class="gc-item-nombre"><?php echo esc_html($item->producto_nombre); ?></h5>
                                <span class="gc-item-precio">
                                    <?php echo number_format($item->precio_unitario, 2, ',', '.'); ?> &euro;/<?php echo esc_html($unidad); ?>
                                </span>
                                <?php if ($item->notas) : ?>
                                <p class="gc-item-notas"><?php echo esc_html($item->notas); ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="gc-item-cantidad">
                                <span class="gc-cantidad-valor"><?php echo esc_html($item->cantidad); ?></span>
                                <span class="gc-cantidad-unidad"><?php echo esc_html($unidad); ?></span>
                            </div>

                            <div class="gc-item-subtotal">
                                <?php echo number_format($item_subtotal, 2, ',', '.'); ?> &euro;
                            </div>

                            <div class="gc-item-estado">
                                <span class="gc-estado-mini <?php echo esc_attr($estado_item_class); ?>">
                                    <?php echo esc_html($estado_item_label); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Panel lateral -->
        <aside class="gc-pedido-sidebar">
            <!-- Resumen economico -->
            <div class="gc-sidebar-card gc-resumen-economico">
                <h3><?php esc_html_e('Resumen', 'flavor-chat-ia'); ?></h3>

                <div class="gc-resumen-lineas">
                    <div class="gc-resumen-linea">
                        <span><?php esc_html_e('Subtotal productos', 'flavor-chat-ia'); ?></span>
                        <span><?php echo number_format($entrega->total_pedido, 2, ',', '.'); ?> &euro;</span>
                    </div>

                    <?php if ($entrega->gastos_gestion > 0) : ?>
                    <div class="gc-resumen-linea gc-linea-gestion">
                        <span><?php esc_html_e('Gastos de gestion', 'flavor-chat-ia'); ?></span>
                        <span><?php echo number_format($entrega->gastos_gestion, 2, ',', '.'); ?> &euro;</span>
                    </div>
                    <?php endif; ?>

                    <div class="gc-resumen-linea gc-linea-total">
                        <strong><?php esc_html_e('Total', 'flavor-chat-ia'); ?></strong>
                        <strong><?php echo number_format($entrega->total_final, 2, ',', '.'); ?> &euro;</strong>
                    </div>
                </div>

                <?php if ($entrega->estado_pago === 'pendiente') : ?>
                <a href="<?php echo esc_url(add_query_arg('entrega_id', $entrega->id, Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'checkout'))); ?>"
                   class="gc-btn gc-btn-primary gc-btn-block">
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php esc_html_e('Pagar ahora', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>

            <!-- Informacion de entrega -->
            <div class="gc-sidebar-card gc-info-entrega">
                <h3><?php esc_html_e('Entrega', 'flavor-chat-ia'); ?></h3>

                <?php if ($ciclo_meta['fecha_entrega']) : ?>
                <div class="gc-info-item">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <div>
                        <span class="gc-info-label"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></span>
                        <strong>
                            <?php echo esc_html(date_i18n('l j \d\e F', strtotime($ciclo_meta['fecha_entrega']))); ?>
                            <?php if ($ciclo_meta['hora_entrega']) : ?>
                            - <?php echo esc_html($ciclo_meta['hora_entrega']); ?>
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($ciclo_meta['lugar_entrega']) : ?>
                <div class="gc-info-item">
                    <span class="dashicons dashicons-location"></span>
                    <div>
                        <span class="gc-info-label"><?php esc_html_e('Lugar', 'flavor-chat-ia'); ?></span>
                        <strong><?php echo esc_html($ciclo_meta['lugar_entrega']); ?></strong>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($ciclo_meta['notas']) : ?>
                <div class="gc-info-notas">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php echo wp_kses_post(nl2br($ciclo_meta['notas'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Notas del pedido -->
            <?php if ($entrega->notas) : ?>
            <div class="gc-sidebar-card gc-notas-pedido">
                <h3><?php esc_html_e('Notas del pedido', 'flavor-chat-ia'); ?></h3>
                <p><?php echo wp_kses_post(nl2br($entrega->notas)); ?></p>
            </div>
            <?php endif; ?>

            <!-- Acciones -->
            <div class="gc-sidebar-acciones">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'mis-pedidos')); ?>" class="gc-btn gc-btn-text gc-btn-block">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php esc_html_e('Ver todos mis pedidos', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </aside>
    </div>
</div>

<style>
.gc-mi-pedido-container {
    max-width: 1000px;
}
.gc-pedido-login-required,
.gc-pedido-no-encontrado {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 10px;
}
.gc-pedido-login-required .dashicons,
.gc-pedido-no-encontrado .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #ccc;
    margin-bottom: 15px;
}
.gc-pedido-login-required h3,
.gc-pedido-no-encontrado h3 {
    margin: 0 0 10px 0;
    color: #666;
}
.gc-pedido-login-required p,
.gc-pedido-no-encontrado p {
    color: #999;
    margin-bottom: 20px;
}
.gc-pedido-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e0e0e0;
}
.gc-pedido-titulo {
    display: flex;
    align-items: center;
    gap: 15px;
}
.gc-btn-back {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 50%;
    color: #666;
    text-decoration: none;
    transition: all 0.2s;
}
.gc-btn-back:hover {
    background: #e0e0e0;
    color: #333;
}
.gc-pedido-numero {
    display: block;
    font-size: 12px;
    color: #757575;
    text-transform: uppercase;
    letter-spacing: 1px;
}
.gc-pedido-titulo h2 {
    margin: 5px 0 0 0;
    font-size: 1.3rem;
}
.gc-pedido-estados {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}
.gc-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.gc-status-success {
    background: #e8f5e9;
    color: #2e7d32;
}
.gc-status-warning {
    background: #fff3e0;
    color: #ef6c00;
}
.gc-status-info {
    background: #e3f2fd;
    color: #1565c0;
}
.gc-status-error {
    background: #ffebee;
    color: #c62828;
}
.gc-status-secondary {
    background: #f5f5f5;
    color: #757575;
}
.gc-pedido-content {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 30px;
    align-items: start;
}
.gc-pedido-main {
    display: flex;
    flex-direction: column;
    gap: 25px;
}
.gc-pedido-timeline {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.gc-pedido-timeline h3 {
    margin: 0 0 20px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}
.gc-timeline {
    display: flex;
    flex-direction: column;
    gap: 0;
}
.gc-timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px 0;
    position: relative;
}
.gc-timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 17px;
    top: 50px;
    bottom: -15px;
    width: 2px;
    background: #e0e0e0;
}
.gc-timeline-completado:not(:last-child)::after {
    background: #4caf50;
}
.gc-timeline-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    z-index: 1;
}
.gc-timeline-completado .gc-timeline-icon {
    background: #4caf50;
    color: #fff;
}
.gc-timeline-pendiente .gc-timeline-icon {
    background: #f5f5f5;
    color: #9e9e9e;
    border: 2px solid #e0e0e0;
}
.gc-timeline-numero {
    font-weight: 600;
    font-size: 14px;
}
.gc-timeline-content strong {
    display: block;
    font-size: 14px;
}
.gc-timeline-pendiente .gc-timeline-content strong {
    color: #9e9e9e;
}
.gc-timeline-fecha {
    font-size: 12px;
    color: #757575;
}
.gc-pedido-productos {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.gc-pedido-productos h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.gc-productos-count {
    font-size: 14px;
    font-weight: 400;
    color: #757575;
}
.gc-productor-grupo {
    margin-bottom: 20px;
}
.gc-productor-grupo:last-child {
    margin-bottom: 0;
}
.gc-productor-nombre {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 10px 0;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 6px;
    font-size: 14px;
}
.gc-productor-nombre .dashicons {
    color: #757575;
}
.gc-items-lista {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.gc-pedido-item {
    display: grid;
    grid-template-columns: 50px 1fr auto auto auto;
    gap: 12px;
    align-items: center;
    padding: 12px;
    background: #fafafa;
    border-radius: 8px;
}
.gc-item-cancelado,
.gc-item-sin-stock {
    opacity: 0.6;
    background: #ffebee;
}
.gc-item-imagen {
    width: 50px;
    height: 50px;
    border-radius: 6px;
    overflow: hidden;
}
.gc-item-thumb {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.gc-item-thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e8f5e9;
}
.gc-item-thumb-placeholder .dashicons {
    color: #a5d6a7;
}
.gc-item-nombre {
    margin: 0 0 2px 0;
    font-size: 14px;
}
.gc-item-precio {
    font-size: 12px;
    color: #757575;
}
.gc-item-notas {
    font-size: 12px;
    color: #9e9e9e;
    margin: 5px 0 0 0;
}
.gc-item-cantidad {
    text-align: center;
}
.gc-cantidad-valor {
    display: block;
    font-size: 16px;
    font-weight: 700;
}
.gc-cantidad-unidad {
    font-size: 11px;
    color: #9e9e9e;
}
.gc-item-subtotal {
    font-size: 15px;
    font-weight: 600;
    min-width: 70px;
    text-align: right;
}
.gc-estado-mini {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 600;
}
.gc-item-confirmado .gc-estado-mini {
    background: #e3f2fd;
    color: #1565c0;
}
.gc-item-completado .gc-estado-mini {
    background: #e8f5e9;
    color: #2e7d32;
}
.gc-item-cancelado .gc-estado-mini,
.gc-item-sin-stock .gc-estado-mini {
    background: #ffebee;
    color: #c62828;
}
.gc-item-pendiente .gc-estado-mini {
    background: #fff3e0;
    color: #ef6c00;
}
.gc-sidebar-card {
    background: #fff;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    margin-bottom: 15px;
}
.gc-sidebar-card h3 {
    margin: 0 0 15px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}
.gc-resumen-lineas {
    margin-bottom: 15px;
}
.gc-resumen-linea {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
}
.gc-linea-gestion {
    color: #757575;
    border-bottom: 1px solid #eee;
}
.gc-linea-total {
    font-size: 18px;
    padding-top: 15px;
}
.gc-info-item {
    display: flex;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f5f5f5;
}
.gc-info-item:last-child {
    border-bottom: none;
}
.gc-info-item .dashicons {
    color: #9e9e9e;
    margin-top: 2px;
}
.gc-info-label {
    display: block;
    font-size: 12px;
    color: #9e9e9e;
}
.gc-info-notas {
    display: flex;
    gap: 10px;
    padding: 10px;
    background: #fff3e0;
    border-radius: 6px;
    margin-top: 10px;
}
.gc-info-notas .dashicons {
    color: #f57c00;
    flex-shrink: 0;
}
.gc-info-notas p {
    margin: 0;
    font-size: 13px;
}
.gc-notas-pedido p {
    margin: 0;
    font-size: 14px;
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
.gc-btn-block {
    width: 100%;
}
.gc-btn-primary {
    background: #4caf50;
    color: #fff;
}
.gc-btn-primary:hover {
    background: #388e3c;
    color: #fff;
}
.gc-btn-text {
    background: none;
    color: #666;
    padding: 10px;
}
.gc-btn-text:hover {
    color: #333;
    background: #f5f5f5;
}
@media (max-width: 900px) {
    .gc-pedido-content {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 600px) {
    .gc-pedido-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .gc-pedido-item {
        grid-template-columns: 40px 1fr auto;
        grid-template-rows: auto auto;
    }
    .gc-item-cantidad,
    .gc-item-subtotal,
    .gc-item-estado {
        grid-column: 2 / -1;
    }
    .gc-item-imagen {
        width: 40px;
        height: 40px;
    }
    .gc-timeline-item {
        padding: 10px 0;
    }
}
</style>
