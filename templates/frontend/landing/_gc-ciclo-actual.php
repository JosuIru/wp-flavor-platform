<?php
/**
 * Template parcial: Ciclo de Pedidos Actual
 *
 * Muestra información del ciclo de pedidos activo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$id_seccion = $id_seccion ?? 'ciclo';

// Obtener ciclo activo
$ciclos = get_posts([
    'post_type' => 'gc_ciclo',
    'post_status' => 'publish',
    'posts_per_page' => 1,
    'meta_query' => [
        [
            'key' => '_gc_estado',
            'value' => 'abierto',
        ],
    ],
    'orderby' => 'meta_value',
    'meta_key' => '_gc_fecha_cierre',
    'order' => 'ASC',
]);

$ciclo = !empty($ciclos) ? $ciclos[0] : null;

if ($ciclo):
    $fecha_cierre = get_post_meta($ciclo->ID, '_gc_fecha_cierre', true);
    $fecha_entrega = get_post_meta($ciclo->ID, '_gc_fecha_entrega', true);
    $hora_entrega = get_post_meta($ciclo->ID, '_gc_hora_entrega', true);
    $lugar_entrega = get_post_meta($ciclo->ID, '_gc_lugar_entrega', true);
    $notas = get_post_meta($ciclo->ID, '_gc_notas', true);

    // Calcular tiempo restante
    $ahora = current_time('timestamp');
    $cierre_timestamp = strtotime($fecha_cierre);
    $diferencia = $cierre_timestamp - $ahora;
    $dias_restantes = floor($diferencia / (60 * 60 * 24));
    $horas_restantes = floor(($diferencia % (60 * 60 * 24)) / (60 * 60));
?>

<section id="<?php echo esc_attr($id_seccion); ?>" class="flavor-landing__section flavor-gc-ciclo-section">
    <div class="flavor-container">
        <div class="flavor-gc-ciclo-card">
            <div class="flavor-gc-ciclo-estado">
                <span class="flavor-gc-estado-badge flavor-gc-estado--abierto">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Ciclo de pedidos abierto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
            </div>

            <div class="flavor-gc-ciclo-content">
                <div class="flavor-gc-ciclo-principal">
                    <h2 class="flavor-gc-ciclo-titulo"><?php echo esc_html($ciclo->post_title); ?></h2>

                    <div class="flavor-gc-ciclo-countdown">
                        <span class="flavor-gc-countdown-label"><?php _e('Tiempo para hacer tu pedido:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <div class="flavor-gc-countdown-timer" data-cierre="<?php echo esc_attr($fecha_cierre); ?>">
                            <?php if ($dias_restantes > 0): ?>
                                <div class="flavor-gc-countdown-item">
                                    <span class="flavor-gc-countdown-valor"><?php echo $dias_restantes; ?></span>
                                    <span class="flavor-gc-countdown-unidad"><?php echo _n('día', 'días', $dias_restantes, FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="flavor-gc-countdown-item">
                                <span class="flavor-gc-countdown-valor"><?php echo $horas_restantes; ?></span>
                                <span class="flavor-gc-countdown-unidad"><?php echo _n('hora', 'horas', $horas_restantes, FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-gc-ciclo-cta">
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="flavor-btn flavor-btn--primary flavor-btn--lg">
                            <span class="dashicons dashicons-cart"></span>
                            <?php _e('Hacer mi pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>

                <div class="flavor-gc-ciclo-detalles">
                    <div class="flavor-gc-detalle-item">
                        <div class="flavor-gc-detalle-icono">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="flavor-gc-detalle-info">
                            <span class="flavor-gc-detalle-label"><?php _e('Cierre de pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <strong class="flavor-gc-detalle-valor">
                                <?php echo date_i18n('l j \d\e F, H:i', strtotime($fecha_cierre)); ?>
                            </strong>
                        </div>
                    </div>

                    <div class="flavor-gc-detalle-item">
                        <div class="flavor-gc-detalle-icono">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="flavor-gc-detalle-info">
                            <span class="flavor-gc-detalle-label"><?php _e('Fecha de entrega', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <strong class="flavor-gc-detalle-valor">
                                <?php echo date_i18n('l j \d\e F', strtotime($fecha_entrega)); ?>
                                <?php if ($hora_entrega): ?>
                                    - <?php echo esc_html($hora_entrega); ?>
                                <?php endif; ?>
                            </strong>
                        </div>
                    </div>

                    <?php if ($lugar_entrega): ?>
                    <div class="flavor-gc-detalle-item">
                        <div class="flavor-gc-detalle-icono">
                            <span class="dashicons dashicons-location"></span>
                        </div>
                        <div class="flavor-gc-detalle-info">
                            <span class="flavor-gc-detalle-label"><?php _e('Lugar de recogida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <strong class="flavor-gc-detalle-valor"><?php echo esc_html($lugar_entrega); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($notas): ?>
                    <div class="flavor-gc-detalle-item flavor-gc-detalle--notas">
                        <div class="flavor-gc-detalle-icono">
                            <span class="dashicons dashicons-info"></span>
                        </div>
                        <div class="flavor-gc-detalle-info">
                            <span class="flavor-gc-detalle-label"><?php _e('Información adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <p class="flavor-gc-detalle-texto"><?php echo esc_html($notas); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php else: ?>

<section id="<?php echo esc_attr($id_seccion); ?>" class="flavor-landing__section flavor-gc-ciclo-section flavor-gc-ciclo--cerrado">
    <div class="flavor-container">
        <div class="flavor-gc-ciclo-card flavor-gc-ciclo-card--cerrado">
            <div class="flavor-gc-ciclo-estado">
                <span class="flavor-gc-estado-badge flavor-gc-estado--cerrado">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Próximo ciclo de pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
            </div>

            <div class="flavor-gc-ciclo-content flavor-gc-ciclo-content--cerrado">
                <span class="dashicons dashicons-calendar-alt flavor-gc-ciclo-icono-grande"></span>
                <h3><?php _e('El ciclo de pedidos está cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Te avisaremos cuando abra el próximo ciclo de pedidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('grupos_consumo', 'unirme')); ?>" class="flavor-btn flavor-btn--outline">
                    <?php _e('Unirme al grupo para recibir avisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
    </div>
</section>

<?php endif; ?>

<style>
.flavor-gc-ciclo-section {
    padding: 3rem 0;
    background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);
}
.flavor-gc-ciclo-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
}
.flavor-gc-ciclo-estado {
    padding: 1rem 1.5rem;
    background: #f0fdf4;
    border-bottom: 1px solid #dcfce7;
}
.flavor-gc-estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
}
.flavor-gc-estado--abierto {
    color: #16a34a;
}
.flavor-gc-estado--cerrado {
    color: #94a3b8;
}
.flavor-gc-ciclo-content {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    padding: 2rem;
}
@media (max-width: 768px) {
    .flavor-gc-ciclo-content {
        grid-template-columns: 1fr;
    }
}
.flavor-gc-ciclo-principal {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
}
.flavor-gc-ciclo-titulo {
    margin: 0 0 1.5rem;
    font-size: 1.5rem;
    color: #1e293b;
}
.flavor-gc-ciclo-countdown {
    margin-bottom: 1.5rem;
}
.flavor-gc-countdown-label {
    display: block;
    font-size: 0.875rem;
    color: #64748b;
    margin-bottom: 0.5rem;
}
.flavor-gc-countdown-timer {
    display: flex;
    gap: 1rem;
}
.flavor-gc-countdown-item {
    text-align: center;
    background: #f8fafc;
    padding: 0.75rem 1.25rem;
    border-radius: 8px;
}
.flavor-gc-countdown-valor {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    color: #84cc16;
    line-height: 1;
}
.flavor-gc-countdown-unidad {
    font-size: 0.75rem;
    color: #64748b;
    text-transform: uppercase;
}
.flavor-gc-ciclo-cta .flavor-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}
.flavor-gc-ciclo-detalles {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.flavor-gc-detalle-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}
.flavor-gc-detalle-icono {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0fdf4;
    border-radius: 10px;
    color: #84cc16;
    flex-shrink: 0;
}
.flavor-gc-detalle-label {
    display: block;
    font-size: 0.75rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 0.25rem;
}
.flavor-gc-detalle-valor {
    font-size: 1rem;
    color: #1e293b;
}
.flavor-gc-detalle-texto {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    color: #64748b;
    line-height: 1.5;
}
/* Estado cerrado */
.flavor-gc-ciclo--cerrado {
    background: #f1f5f9;
}
.flavor-gc-ciclo-card--cerrado {
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}
.flavor-gc-ciclo-content--cerrado {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 3rem;
}
.flavor-gc-ciclo-icono-grande {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #cbd5e1;
    margin-bottom: 1rem;
}
.flavor-gc-ciclo-content--cerrado h3 {
    margin: 0 0 0.5rem;
    color: #475569;
}
.flavor-gc-ciclo-content--cerrado p {
    margin: 0 0 1.5rem;
    color: #94a3b8;
}
</style>
