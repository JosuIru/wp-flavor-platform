<?php
/**
 * Vista de Gestión de Bicicletas - Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('No tienes permisos suficientes.', 'flavor-chat-ia'));

global $wpdb;
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_compartidas_bicicletas';
$tabla_estaciones = $wpdb->prefix . 'flavor_bicicletas_compartidas_estaciones';

$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';

$where = "WHERE 1=1";
if ($filtro_estado !== 'todos') {
    $where .= $wpdb->prepare(" AND b.estado = %s", $filtro_estado);
}

$bicicletas = $wpdb->get_results(
    "SELECT
        b.*,
        e.nombre as nombre_estacion
    FROM {$tabla_bicicletas} b
    LEFT JOIN {$tabla_estaciones} e ON b.estacion_actual_id = e.id
    {$where}
    ORDER BY b.codigo ASC"
);

$stats = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
        SUM(CASE WHEN estado = 'en_uso' THEN 1 ELSE 0 END) as en_uso,
        SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento
    FROM {$tabla_bicicletas}"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Gestión de Bicicletas', 'flavor-chat-ia'); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas&action=nueva')); ?>" class="page-title-action">
        <?php esc_html_e('Añadir Nueva', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;"><?php echo esc_html($stats->total); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('Total', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;"><?php echo esc_html($stats->disponibles); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;"><?php echo esc_html($stats->en_uso); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('En Uso', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #dba617;"><?php echo esc_html($stats->mantenimiento); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-bicicletas-bicicletas">
            <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div>
                    <label for="estado"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></label>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="todos" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                        <option value="disponible" <?php selected($filtro_estado, 'disponible'); ?>><?php esc_html_e('Disponible', 'flavor-chat-ia'); ?></option>
                        <option value="en_uso" <?php selected($filtro_estado, 'en_uso'); ?>><?php esc_html_e('En Uso', 'flavor-chat-ia'); ?></option>
                        <option value="mantenimiento" <?php selected($filtro_estado, 'mantenimiento'); ?>><?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?></option>
                        <option value="fuera_servicio" <?php selected($filtro_estado, 'fuera_servicio'); ?>><?php esc_html_e('Fuera de Servicio', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas')); ?>" class="button"><?php esc_html_e('Limpiar', 'flavor-chat-ia'); ?></a>
                </div>
            </div>
        </form>
    </div>

    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Código', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Modelo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estación Actual', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Km Recorridos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Último Mant.', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bicicletas)) : ?>
                    <?php foreach ($bicicletas as $bicicleta) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($bicicleta->id); ?></strong></td>
                            <td><strong>🚲 <?php echo esc_html($bicicleta->codigo); ?></strong></td>
                            <td><?php echo esc_html($bicicleta->modelo ?? '-'); ?></td>
                            <td><?php echo esc_html($bicicleta->nombre_estacion ?? __('Sin estación', 'flavor-chat-ia')); ?></td>
                            <td><?php echo esc_html(number_format($bicicleta->kilometros_totales ?? 0, 1)); ?> km</td>
                            <td>
                                <?php
                                if ($bicicleta->fecha_ultimo_mantenimiento) {
                                    echo date('d/m/Y', strtotime($bicicleta->fecha_ultimo_mantenimiento));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $colores = ['disponible' => '#00a32a', 'en_uso' => '#2271b1', 'mantenimiento' => '#dba617', 'fuera_servicio' => '#d63638'];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($colores[$bicicleta->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $bicicleta->estado))); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-bicicletas&action=ver&bicicleta_id=' . $bicicleta->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                                <?php if ($bicicleta->estado !== 'en_uso') : ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento&bicicleta_id=' . $bicicleta->id)); ?>" class="button button-small">
                                        <?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <p><?php esc_html_e('No se encontraron bicicletas.', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
