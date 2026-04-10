<?php
/**
 * Template: Ciclo Actual - Grupos de Consumo
 *
 * Muestra información detallada del ciclo de pedidos activo.
 *
 * @package FlavorPlatform
 * @subpackage Modules\GruposConsumo\Templates
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Obtener ciclo activo
$args_ciclo = [
    'post_type'      => 'gc_ciclo',
    'post_status'    => ['publish', 'gc_abierto'],
    'posts_per_page' => 1,
    'meta_query'     => [
        'relation' => 'OR',
        ['key' => '_gc_estado', 'value' => 'abierto'],
        ['key' => '_gc_estado', 'value' => 'activo'],
    ],
    'orderby'        => 'meta_value',
    'meta_key'       => '_gc_fecha_cierre',
    'order'          => 'ASC',
];
$query_ciclo = new WP_Query($args_ciclo);
$ciclo_activo = $query_ciclo->have_posts() ? $query_ciclo->posts[0] : null;

// Si no hay ciclo activo, mostrar el siguiente programado
if (!$ciclo_activo) {
    $args_proximo = [
        'post_type'      => 'gc_ciclo',
        'post_status'    => ['publish', 'gc_programado', 'future'],
        'posts_per_page' => 1,
        'meta_query'     => [
            [
                'key'     => '_gc_fecha_inicio',
                'value'   => current_time('mysql'),
                'compare' => '>',
                'type'    => 'DATETIME',
            ],
        ],
        'orderby'        => 'meta_value',
        'meta_key'       => '_gc_fecha_inicio',
        'order'          => 'ASC',
    ];
    $query_proximo = new WP_Query($args_proximo);
    $ciclo_proximo = $query_proximo->have_posts() ? $query_proximo->posts[0] : null;
}

// Obtener estadísticas si hay ciclo activo
$estadisticas_ciclo = [];
if ($ciclo_activo) {
    $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
    $tabla_entregas = $wpdb->prefix . 'flavor_gc_entregas';

    // Total de pedidos
    $estadisticas_ciclo['total_pedidos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT usuario_id) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
        $ciclo_activo->ID
    ));

    // Total de productos pedidos
    $estadisticas_ciclo['total_productos'] = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT producto_id) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
        $ciclo_activo->ID
    ));

    // Importe total
    $estadisticas_ciclo['importe_total'] = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(cantidad * precio_unitario) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
        $ciclo_activo->ID
    ));

    // Productos más pedidos
    $estadisticas_ciclo['productos_populares'] = $wpdb->get_results($wpdb->prepare(
        "SELECT p.producto_id, SUM(p.cantidad) as cantidad_total, posts.post_title as nombre
         FROM {$tabla_pedidos} p
         LEFT JOIN {$wpdb->posts} posts ON p.producto_id = posts.ID
         WHERE p.ciclo_id = %d
         GROUP BY p.producto_id
         ORDER BY cantidad_total DESC
         LIMIT 5",
        $ciclo_activo->ID
    ));
}

// Obtener meta del ciclo
$meta_ciclo = [];
if ($ciclo_activo) {
    $meta_ciclo = [
        'fecha_inicio'   => get_post_meta($ciclo_activo->ID, '_gc_fecha_inicio', true),
        'fecha_cierre'   => get_post_meta($ciclo_activo->ID, '_gc_fecha_cierre', true),
        'fecha_entrega'  => get_post_meta($ciclo_activo->ID, '_gc_fecha_entrega', true),
        'hora_entrega'   => get_post_meta($ciclo_activo->ID, '_gc_hora_entrega', true),
        'lugar_entrega'  => get_post_meta($ciclo_activo->ID, '_gc_lugar_entrega', true),
        'notas'          => get_post_meta($ciclo_activo->ID, '_gc_notas', true),
        'gastos_gestion' => get_post_meta($ciclo_activo->ID, '_gc_gastos_gestion', true),
    ];
}
?>

<div class="gc-ciclo-actual-container">
    <?php if ($ciclo_activo) : ?>
        <!-- Ciclo Activo -->
        <div class="gc-ciclo-card gc-ciclo-activo">
            <div class="gc-ciclo-header">
                <div class="gc-ciclo-titulo">
                    <span class="gc-status-badge gc-status-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Ciclo Abierto', 'flavor-platform'); ?>
                    </span>
                    <h2><?php echo esc_html($ciclo_activo->post_title); ?></h2>
                </div>
                <div class="gc-ciclo-countdown" id="gc-countdown-container">
                    <span class="gc-countdown-label"><?php esc_html_e('Cierra en:', 'flavor-platform'); ?></span>
                    <span class="gc-countdown-value" id="gc-countdown" data-cierre="<?php echo esc_attr($meta_ciclo['fecha_cierre']); ?>">
                        --
                    </span>
                </div>
            </div>

            <div class="gc-ciclo-body">
                <!-- Fechas importantes -->
                <div class="gc-ciclo-fechas">
                    <div class="gc-fecha-item">
                        <span class="dashicons dashicons-calendar"></span>
                        <div class="gc-fecha-content">
                            <span class="gc-fecha-label"><?php esc_html_e('Inicio de pedidos', 'flavor-platform'); ?></span>
                            <strong>
                                <?php
                                if ($meta_ciclo['fecha_inicio']) {
                                    echo esc_html(date_i18n('l j F, H:i', strtotime($meta_ciclo['fecha_inicio'])));
                                }
                                ?>
                            </strong>
                        </div>
                    </div>

                    <div class="gc-fecha-item gc-fecha-destacada">
                        <span class="dashicons dashicons-clock"></span>
                        <div class="gc-fecha-content">
                            <span class="gc-fecha-label"><?php esc_html_e('Cierre de pedidos', 'flavor-platform'); ?></span>
                            <strong>
                                <?php
                                if ($meta_ciclo['fecha_cierre']) {
                                    echo esc_html(date_i18n('l j F, H:i', strtotime($meta_ciclo['fecha_cierre'])));
                                }
                                ?>
                            </strong>
                        </div>
                    </div>

                    <div class="gc-fecha-item">
                        <span class="dashicons dashicons-location"></span>
                        <div class="gc-fecha-content">
                            <span class="gc-fecha-label"><?php esc_html_e('Entrega', 'flavor-platform'); ?></span>
                            <strong>
                                <?php
                                if ($meta_ciclo['fecha_entrega']) {
                                    echo esc_html(date_i18n('l j F', strtotime($meta_ciclo['fecha_entrega'])));
                                    if ($meta_ciclo['hora_entrega']) {
                                        echo ' - ' . esc_html($meta_ciclo['hora_entrega']);
                                    }
                                }
                                ?>
                            </strong>
                        </div>
                    </div>

                    <?php if ($meta_ciclo['lugar_entrega']) : ?>
                    <div class="gc-fecha-item">
                        <span class="dashicons dashicons-admin-home"></span>
                        <div class="gc-fecha-content">
                            <span class="gc-fecha-label"><?php esc_html_e('Lugar de recogida', 'flavor-platform'); ?></span>
                            <strong><?php echo esc_html($meta_ciclo['lugar_entrega']); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Estadisticas del ciclo -->
                <?php if (!empty($estadisticas_ciclo)) : ?>
                <div class="gc-ciclo-stats">
                    <h3><?php esc_html_e('Estado del ciclo', 'flavor-platform'); ?></h3>
                    <div class="gc-stats-grid">
                        <div class="gc-stat-item">
                            <span class="gc-stat-value"><?php echo esc_html($estadisticas_ciclo['total_pedidos']); ?></span>
                            <span class="gc-stat-label"><?php esc_html_e('Participantes', 'flavor-platform'); ?></span>
                        </div>
                        <div class="gc-stat-item">
                            <span class="gc-stat-value"><?php echo esc_html($estadisticas_ciclo['total_productos']); ?></span>
                            <span class="gc-stat-label"><?php esc_html_e('Productos', 'flavor-platform'); ?></span>
                        </div>
                        <div class="gc-stat-item">
                            <span class="gc-stat-value"><?php echo number_format($estadisticas_ciclo['importe_total'], 2, ',', '.'); ?> &euro;</span>
                            <span class="gc-stat-label"><?php esc_html_e('Total acumulado', 'flavor-platform'); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($estadisticas_ciclo['productos_populares'])) : ?>
                    <div class="gc-productos-populares">
                        <h4><?php esc_html_e('Productos mas pedidos', 'flavor-platform'); ?></h4>
                        <ul class="gc-populares-lista">
                            <?php foreach ($estadisticas_ciclo['productos_populares'] as $producto) : ?>
                            <li>
                                <span class="gc-pop-nombre"><?php echo esc_html($producto->nombre); ?></span>
                                <span class="gc-pop-cantidad"><?php echo esc_html($producto->cantidad_total); ?> uds.</span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Notas del ciclo -->
                <?php if ($meta_ciclo['notas']) : ?>
                <div class="gc-ciclo-notas">
                    <span class="dashicons dashicons-info"></span>
                    <p><?php echo wp_kses_post(nl2br($meta_ciclo['notas'])); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <div class="gc-ciclo-footer">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="gc-btn gc-btn-primary">
                    <span class="dashicons dashicons-products"></span>
                    <?php esc_html_e('Ver catalogo', 'flavor-platform'); ?>
                </a>
                <?php if (is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'mi-pedido')); ?>" class="gc-btn gc-btn-secondary">
                    <span class="dashicons dashicons-cart"></span>
                    <?php esc_html_e('Pedido actual', 'flavor-platform'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif (isset($ciclo_proximo) && $ciclo_proximo) : ?>
        <!-- Proximo Ciclo -->
        <?php
        $meta_proximo = [
            'fecha_inicio'  => get_post_meta($ciclo_proximo->ID, '_gc_fecha_inicio', true),
            'fecha_cierre'  => get_post_meta($ciclo_proximo->ID, '_gc_fecha_cierre', true),
            'fecha_entrega' => get_post_meta($ciclo_proximo->ID, '_gc_fecha_entrega', true),
            'hora_entrega'  => get_post_meta($ciclo_proximo->ID, '_gc_hora_entrega', true),
            'lugar_entrega' => get_post_meta($ciclo_proximo->ID, '_gc_lugar_entrega', true),
        ];
        ?>
        <div class="gc-ciclo-card gc-ciclo-proximo">
            <div class="gc-ciclo-header">
                <div class="gc-ciclo-titulo">
                    <span class="gc-status-badge gc-status-info">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php esc_html_e('Proximo Ciclo', 'flavor-platform'); ?>
                    </span>
                    <h2><?php echo esc_html($ciclo_proximo->post_title); ?></h2>
                </div>
            </div>

            <div class="gc-ciclo-body">
                <div class="gc-ciclo-mensaje">
                    <span class="dashicons dashicons-clock"></span>
                    <p>
                        <?php
                        printf(
                            esc_html__('El proximo ciclo de pedidos comenzara el %s.', 'flavor-platform'),
                            '<strong>' . esc_html(date_i18n('l j \d\e F', strtotime($meta_proximo['fecha_inicio']))) . '</strong>'
                        );
                        ?>
                    </p>
                </div>

                <div class="gc-ciclo-fechas gc-fechas-compactas">
                    <div class="gc-fecha-item">
                        <span class="gc-fecha-label"><?php esc_html_e('Apertura:', 'flavor-platform'); ?></span>
                        <strong><?php echo esc_html(date_i18n('j M, H:i', strtotime($meta_proximo['fecha_inicio']))); ?></strong>
                    </div>
                    <div class="gc-fecha-item">
                        <span class="gc-fecha-label"><?php esc_html_e('Cierre:', 'flavor-platform'); ?></span>
                        <strong><?php echo esc_html(date_i18n('j M, H:i', strtotime($meta_proximo['fecha_cierre']))); ?></strong>
                    </div>
                    <div class="gc-fecha-item">
                        <span class="gc-fecha-label"><?php esc_html_e('Entrega:', 'flavor-platform'); ?></span>
                        <strong><?php echo esc_html(date_i18n('j M', strtotime($meta_proximo['fecha_entrega']))); ?></strong>
                    </div>
                </div>
            </div>

            <div class="gc-ciclo-footer">
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', 'productos')); ?>" class="gc-btn gc-btn-secondary">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Ver productos disponibles', 'flavor-platform'); ?>
                </a>
            </div>
        </div>

    <?php else : ?>
        <!-- Sin ciclos -->
        <div class="gc-ciclo-card gc-ciclo-vacio">
            <div class="gc-ciclo-empty">
                <span class="dashicons dashicons-calendar-alt"></span>
                <h3><?php esc_html_e('No hay ciclos programados', 'flavor-platform'); ?></h3>
                <p><?php esc_html_e('Actualmente no hay ciclos de pedidos activos ni programados. Vuelve pronto para ver las novedades.', 'flavor-platform'); ?></p>
                <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('grupos_consumo', '')); ?>" class="gc-btn gc-btn-secondary">
                    <?php esc_html_e('Volver al inicio', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function() {
    'use strict';

    var countdownElement = document.getElementById('gc-countdown');
    if (!countdownElement) return;

    var fechaCierre = countdownElement.getAttribute('data-cierre');
    if (!fechaCierre) return;

    var fechaCierreTimestamp = new Date(fechaCierre).getTime();

    function actualizarCountdown() {
        var ahora = new Date().getTime();
        var diferencia = fechaCierreTimestamp - ahora;

        if (diferencia <= 0) {
            countdownElement.textContent = '<?php echo esc_js(__('Ciclo cerrado', 'flavor-platform')); ?>';
            countdownElement.classList.add('gc-countdown-cerrado');
            return;
        }

        var dias = Math.floor(diferencia / (1000 * 60 * 60 * 24));
        var horas = Math.floor((diferencia % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutos = Math.floor((diferencia % (1000 * 60 * 60)) / (1000 * 60));
        var segundos = Math.floor((diferencia % (1000 * 60)) / 1000);

        var texto = '';
        if (dias > 0) {
            texto = dias + 'd ' + horas + 'h ' + minutos + 'm';
        } else if (horas > 0) {
            texto = horas + 'h ' + minutos + 'm ' + segundos + 's';
        } else {
            texto = minutos + 'm ' + segundos + 's';
        }

        countdownElement.textContent = texto;
    }

    actualizarCountdown();
    setInterval(actualizarCountdown, 1000);
})();
</script>

<style>
.gc-ciclo-actual-container {
    max-width: 800px;
}
.gc-ciclo-card {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow: hidden;
}
.gc-ciclo-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 20px;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    border-bottom: 1px solid #a5d6a7;
}
.gc-ciclo-proximo .gc-ciclo-header {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-bottom-color: #90caf9;
}
.gc-ciclo-vacio .gc-ciclo-header {
    background: #f5f5f5;
    border-bottom-color: #e0e0e0;
}
.gc-ciclo-titulo h2 {
    margin: 10px 0 0 0;
    font-size: 1.5rem;
}
.gc-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
}
.gc-status-success {
    background: #4caf50;
    color: #fff;
}
.gc-status-info {
    background: #2196f3;
    color: #fff;
}
.gc-ciclo-countdown {
    text-align: right;
}
.gc-countdown-label {
    display: block;
    font-size: 12px;
    color: #666;
}
.gc-countdown-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}
.gc-countdown-cerrado {
    color: #d32f2f;
}
.gc-ciclo-body {
    padding: 20px;
}
.gc-ciclo-fechas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.gc-fecha-item {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 12px;
    background: #fafafa;
    border-radius: 8px;
}
.gc-fecha-item .dashicons {
    color: #757575;
    margin-top: 2px;
}
.gc-fecha-destacada {
    background: #fff3e0;
    border: 1px solid #ffcc80;
}
.gc-fecha-destacada .dashicons {
    color: #f57c00;
}
.gc-fecha-content {
    flex: 1;
}
.gc-fecha-label {
    display: block;
    font-size: 12px;
    color: #757575;
    margin-bottom: 2px;
}
.gc-fecha-content strong {
    font-size: 14px;
}
.gc-fechas-compactas {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.gc-fechas-compactas .gc-fecha-item {
    background: none;
    padding: 0;
    flex-direction: row;
    align-items: center;
}
.gc-ciclo-stats {
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    margin-bottom: 20px;
}
.gc-ciclo-stats h3 {
    margin: 0 0 15px 0;
    font-size: 14px;
    color: #666;
    text-transform: uppercase;
}
.gc-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}
.gc-stat-item {
    text-align: center;
    padding: 15px;
    background: #fff;
    border-radius: 6px;
}
.gc-stat-value {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e7d32;
}
.gc-stat-label {
    font-size: 12px;
    color: #757575;
}
.gc-productos-populares h4 {
    margin: 15px 0 10px 0;
    font-size: 13px;
    color: #666;
}
.gc-populares-lista {
    list-style: none;
    margin: 0;
    padding: 0;
}
.gc-populares-lista li {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}
.gc-populares-lista li:last-child {
    border-bottom: none;
}
.gc-pop-cantidad {
    color: #757575;
    font-weight: 600;
}
.gc-ciclo-notas {
    display: flex;
    gap: 10px;
    padding: 15px;
    background: #e8f5e9;
    border-radius: 8px;
    border-left: 4px solid #4caf50;
}
.gc-ciclo-notas .dashicons {
    color: #4caf50;
    flex-shrink: 0;
}
.gc-ciclo-notas p {
    margin: 0;
    font-size: 14px;
}
.gc-ciclo-mensaje {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #e3f2fd;
    border-radius: 8px;
    margin-bottom: 20px;
}
.gc-ciclo-mensaje .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #1976d2;
}
.gc-ciclo-mensaje p {
    margin: 0;
    font-size: 15px;
}
.gc-ciclo-footer {
    display: flex;
    gap: 10px;
    padding: 20px;
    background: #fafafa;
    border-top: 1px solid #eee;
}
.gc-ciclo-empty {
    text-align: center;
    padding: 60px 20px;
}
.gc-ciclo-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #bdbdbd;
    margin-bottom: 15px;
}
.gc-ciclo-empty h3 {
    margin: 0 0 10px 0;
    color: #666;
}
.gc-ciclo-empty p {
    color: #999;
    margin-bottom: 20px;
}
.gc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 20px;
    border-radius: 6px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s;
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
    background: #e0e0e0;
    color: #333;
}
.gc-btn-secondary:hover {
    background: #bdbdbd;
}
@media (max-width: 600px) {
    .gc-ciclo-header {
        flex-direction: column;
        gap: 15px;
    }
    .gc-ciclo-countdown {
        text-align: left;
    }
    .gc-stats-grid {
        grid-template-columns: 1fr;
    }
    .gc-ciclo-footer {
        flex-direction: column;
    }
    .gc-ciclo-footer .gc-btn {
        justify-content: center;
    }
}
</style>
