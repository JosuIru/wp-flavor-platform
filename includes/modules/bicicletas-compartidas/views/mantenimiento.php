<?php
/**
 * Vista de Mantenimiento - Bicicletas Compartidas
 *
 * @package FlavorChatIA
 * @subpackage BicicletasCompartidas
 */

if (!defined('ABSPATH')) exit;
if (!current_user_can('manage_options')) wp_die(__('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN));

global $wpdb;
$tabla_mantenimiento = $wpdb->prefix . 'flavor_bicicletas_mantenimiento';
$tabla_bicicletas = $wpdb->prefix . 'flavor_bicicletas_bicicletas';

$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'todos';

$where = "WHERE 1=1";
if ($filtro_tipo !== 'todos') {
    $where .= $wpdb->prepare(" AND m.tipo_mantenimiento = %s", $filtro_tipo);
}

$mantenimientos = $wpdb->get_results(
    "SELECT
        m.*,
        b.codigo as codigo_bicicleta,
        b.modelo
    FROM {$tabla_mantenimiento} m
    INNER JOIN {$tabla_bicicletas} b ON m.bicicleta_id = b.id
    {$where}
    ORDER BY m.fecha_inicio DESC
    LIMIT 50"
);

// Bicicletas que necesitan mantenimiento
$bicicletas_necesitan_mantenimiento = $wpdb->get_results(
    "SELECT
        b.*,
        DATEDIFF(NOW(), b.fecha_ultimo_mantenimiento) as dias_sin_mantenimiento
    FROM {$tabla_bicicletas} b
    WHERE b.estado != 'fuera_servicio'
        AND (
            b.fecha_ultimo_mantenimiento IS NULL
            OR DATEDIFF(NOW(), b.fecha_ultimo_mantenimiento) > 30
            OR b.kilometros_totales - COALESCE(b.kilometros_ultimo_mantenimiento, 0) > 500
        )
    ORDER BY dias_sin_mantenimiento DESC
    LIMIT 20"
);

$stats = $wpdb->get_row(
    "SELECT
        COUNT(*) as total,
        SUM(CASE WHEN estado = 'en_curso' THEN 1 ELSE 0 END) as en_curso,
        SUM(CASE WHEN estado = 'completado' THEN 1 ELSE 0 END) as completados
    FROM {$tabla_mantenimiento}
    WHERE fecha_inicio >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Gestión de Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento&action=nuevo')); ?>" class="page-title-action">
        <?php esc_html_e('Registrar Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
    <hr class="wp-header-end">

    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #2271b1;"><?php echo esc_html($stats->total ?? 0); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('Último mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #dba617;"><?php echo esc_html($stats->en_curso ?? 0); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('En curso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #d63638;"><?php echo esc_html(count($bicicletas_necesitan_mantenimiento)); ?></div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e('Necesitan revisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
        </div>
    </div>

    <!-- Bicicletas que necesitan mantenimiento -->
    <?php if (!empty($bicicletas_necesitan_mantenimiento)) : ?>
        <div class="card" style="padding: 20px; margin: 20px 0; border-left: 4px solid #d63638;">
            <h2><?php esc_html_e('⚠️ Bicicletas que Requieren Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Código', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Km Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Último Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Días sin Revisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bicicletas_necesitan_mantenimiento as $bici) : ?>
                        <tr style="background-color: <?php echo $bici->dias_sin_mantenimiento > 60 ? '#ffebee' : '#fff8e1'; ?>;">
                            <td><strong>🚲 <?php echo esc_html($bici->codigo); ?></strong></td>
                            <td><?php echo esc_html($bici->modelo ?? '-'); ?></td>
                            <td><?php echo number_format($bici->kilometros_totales ?? 0, 1); ?> km</td>
                            <td>
                                <?php
                                if ($bici->fecha_ultimo_mantenimiento) {
                                    echo date('d/m/Y', strtotime($bici->fecha_ultimo_mantenimiento));
                                } else {
                                    echo '<strong style="color: #d63638;">' . esc_html__('Nunca', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</strong>';
                                }
                                ?>
                            </td>
                            <td>
                                <strong style="color: <?php echo $bici->dias_sin_mantenimiento > 60 ? '#d63638' : '#dba617'; ?>;">
                                    <?php echo esc_html($bici->dias_sin_mantenimiento ?? 'N/A'); ?> días
                                </strong>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento&action=nuevo&bicicleta_id=' . $bici->id)); ?>" class="button button-small button-primary">
                                    <?php esc_html_e('Programar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="<?php echo esc_attr__('flavor-bicicletas-mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <div style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                <div>
                    <label for="tipo"><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="tipo" id="tipo" class="regular-text">
                        <option value="<?php echo esc_attr__('todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_tipo, 'todos'); ?>><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('preventivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_tipo, 'preventivo'); ?>><?php esc_html_e('Preventivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('correctivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_tipo, 'correctivo'); ?>><?php esc_html_e('Correctivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="<?php echo esc_attr__('reparacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($filtro_tipo, 'reparacion'); ?>><?php esc_html_e('Reparación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento')); ?>" class="button"><?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Historial de mantenimientos -->
    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Fecha Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Fecha Fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($mantenimientos)) : ?>
                    <?php foreach ($mantenimientos as $mant) : ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($mant->id); ?></strong></td>
                            <td>🚲 <strong><?php echo esc_html($mant->codigo_bicicleta); ?></strong></td>
                            <td>
                                <?php
                                $iconos_tipo = ['preventivo' => '🔧', 'correctivo' => '⚙️', 'reparacion' => '🔨'];
                                echo esc_html($iconos_tipo[$mant->tipo_mantenimiento] ?? '🔧') . ' ' . esc_html(ucfirst($mant->tipo_mantenimiento));
                                ?>
                            </td>
                            <td><?php echo esc_html(wp_trim_words($mant->descripcion ?? '', 10)); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($mant->fecha_inicio)); ?></td>
                            <td>
                                <?php
                                if ($mant->fecha_fin) {
                                    echo date('d/m/Y H:i', strtotime($mant->fecha_fin));
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $colores = ['pendiente' => '#d63638', 'en_curso' => '#dba617', 'completado' => '#00a32a'];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($colores[$mant->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($mant->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-bicicletas-mantenimiento&action=ver&mantenimiento_id=' . $mant->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px;">
                            <p><?php esc_html_e('No hay registros de mantenimiento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
