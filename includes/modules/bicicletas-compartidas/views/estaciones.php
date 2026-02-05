<?php
/**
 * Vista de Gestión de Estaciones - Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('No tienes permisos suficientes.', 'flavor-chat-ia'));

global $wpdb;
$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_compartidas_estaciones';
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_compartidas_bicicletas';

$estaciones = $wpdb->get_results(
    "SELECT
        e.*,
        COUNT(b.id) as bicicletas_actuales,
        e.capacidad_maxima - COUNT(b.id) as espacios_libres
    FROM {$tabla_estaciones} e
    LEFT JOIN {$tabla_bicicletas} b ON e.id = b.estacion_actual_id
    GROUP BY e.id
    ORDER BY e.nombre ASC"
);

$stats = $wpdb->get_row("SELECT COUNT(*) as total, SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activas FROM {$tabla_estaciones}");
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Gestión de Estaciones', 'flavor-chat-ia'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=nueva')); ?>" class="page-title-action">
        <?php esc_html_e('Añadir Nueva', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html($stats->total); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Total Estaciones', 'flavor-chat-ia'); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html($stats->activas); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Activas', 'flavor-chat-ia'); ?>
            </div>
        </div>
    </div>

    <!-- Mapa de estaciones -->
    <div class="card" style="padding: 20px; margin: 20px 0;">
        <h2><?php esc_html_e('Mapa de Estaciones', 'flavor-chat-ia'); ?></h2>
        <div id="mapa-estaciones" style="height: 400px; background: #e0e0e0; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #666;">
            <?php esc_html_e('Integración de Google Maps aquí', 'flavor-chat-ia'); ?>
        </div>
    </div>

    <!-- Lista de estaciones -->
    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Capacidad', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Bicicletas', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ocupación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($estaciones)) : ?>
                    <?php foreach ($estaciones as $estacion) : ?>
                        <?php $ocupacion = $estacion->capacidad_maxima > 0 ? ($estacion->bicicletas_actuales / $estacion->capacidad_maxima) * 100 : 0; ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($estacion->id); ?></strong></td>
                            <td><strong><?php echo esc_html($estacion->nombre); ?></strong></td>
                            <td>
                                <?php echo esc_html($estacion->direccion); ?>
                                <?php if ($estacion->coordenadas_lat && $estacion->coordenadas_lng) : ?>
                                    <br><small style="color: #666;">📍 <a href="https://www.google.com/maps?q=<?php echo esc_attr($estacion->coordenadas_lat); ?>,<?php echo esc_attr($estacion->coordenadas_lng); ?>" target="_blank">Ver en mapa</a></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($estacion->capacidad_maxima); ?> plazas</td>
                            <td>
                                <strong style="color: <?php echo $estacion->bicicletas_actuales > 0 ? '#00a32a' : '#d63638'; ?>;">
                                    <?php echo esc_html($estacion->bicicletas_actuales); ?>
                                </strong>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="flex: 1; background: #e0e0e0; border-radius: 10px; height: 20px; overflow: hidden;">
                                        <div style="background: <?php echo $ocupacion > 80 ? '#d63638' : ($ocupacion > 50 ? '#dba617' : '#00a32a'); ?>; height: 100%; width: <?php echo esc_attr($ocupacion); ?>%;"></div>
                                    </div>
                                    <span style="min-width: 50px;"><?php echo number_format($ocupacion, 0); ?>%</span>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: <?php echo $estacion->estado === 'activa' ? '#00a32a' : '#d63638'; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($estacion->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=ver&estacion_id=' . $estacion->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-estaciones&action=editar&estacion_id=' . $estacion->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <p><?php esc_html_e('No hay estaciones registradas.', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
