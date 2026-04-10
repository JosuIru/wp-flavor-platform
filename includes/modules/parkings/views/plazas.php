<?php
/**
 * Vista de Gestión de Plazas - Parkings
 *
 * @package FlavorPlatform
 * @subpackage Parkings
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
}

global $wpdb;
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_propietarios = $wpdb->prefix . 'flavor_parkings_propietarios';

// Filtros
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'todos';
$filtro_zona = isset($_GET['zona']) ? sanitize_text_field($_GET['zona']) : '';
$filtro_tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Paginación
$elementos_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $elementos_por_pagina;

// Construir consulta
$where = "WHERE 1=1";

if ($filtro_estado !== 'todos') {
    $where .= $wpdb->prepare(" AND p.estado = %s", $filtro_estado);
}

if (!empty($filtro_zona)) {
    $where .= $wpdb->prepare(" AND p.zona = %s", $filtro_zona);
}

if (!empty($filtro_tipo)) {
    $where .= $wpdb->prepare(" AND p.tipo_vehiculo = %s", $filtro_tipo);
}

if (!empty($filtro_busqueda)) {
    $where .= $wpdb->prepare(" AND (p.numero_plaza LIKE %s OR p.direccion LIKE %s)",
        '%' . $wpdb->esc_like($filtro_busqueda) . '%',
        '%' . $wpdb->esc_like($filtro_busqueda) . '%'
    );
}

$total_plazas = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$tabla_plazas} p {$where}"
);

$plazas = $wpdb->get_results(
    "SELECT
        p.*,
        u.display_name as nombre_propietario,
        (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_parkings_reservas WHERE plaza_id = p.id) as total_reservas
    FROM {$tabla_plazas} p
    LEFT JOIN {$tabla_propietarios} pr ON p.propietario_id = pr.id
    LEFT JOIN {$wpdb->users} u ON pr.usuario_id = u.ID
    {$where}
    ORDER BY p.numero_plaza ASC
    LIMIT {$elementos_por_pagina} OFFSET {$offset}"
);

$total_paginas = ceil($total_plazas / $elementos_por_pagina);

// Obtener zonas únicas
$zonas = $wpdb->get_col("SELECT DISTINCT zona FROM {$tabla_plazas} ORDER BY zona");

// Estadísticas
$stats = $wpdb->get_row(
    "SELECT
        SUM(CASE WHEN estado = 'disponible' THEN 1 ELSE 0 END) as disponibles,
        SUM(CASE WHEN estado = 'ocupada' THEN 1 ELSE 0 END) as ocupadas,
        SUM(CASE WHEN estado = 'mantenimiento' THEN 1 ELSE 0 END) as mantenimiento
    FROM {$tabla_plazas}"
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&action=nueva')); ?>" class="page-title-action">
        <?php esc_html_e('Añadir Nueva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-mini" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                <?php echo esc_html(number_format($stats->disponibles, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                <?php echo esc_html(number_format($stats->ocupadas, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
        <div class="card" style="padding: 15px; text-align: center; min-width: 150px;">
            <div style="font-size: 24px; font-weight: bold; color: #dba617;">
                <?php echo esc_html(number_format($stats->mantenimiento, 0, ',', '.')); ?>
            </div>
            <div style="color: #666; font-size: 12px; text-transform: uppercase;">
                <?php esc_html_e('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card" style="padding: 15px; margin: 20px 0;">
        <form method="get" action="">
            <input type="hidden" name="page" value="flavor-parkings-plazas">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">

                <div>
                    <label for="estado"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="estado" id="estado" class="regular-text">
                        <option value="todos" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="disponible" <?php selected($filtro_estado, 'disponible'); ?>><?php esc_html_e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="ocupada" <?php selected($filtro_estado, 'ocupada'); ?>><?php esc_html_e('Ocupada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="mantenimiento" <?php selected($filtro_estado, 'mantenimiento'); ?>><?php esc_html_e('Mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div>
                    <label for="zona"><?php esc_html_e('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="zona" id="zona" class="regular-text">
                        <option value=""><?php esc_html_e('Todas las zonas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($zonas as $zona) : ?>
                            <option value="<?php echo esc_attr($zona); ?>" <?php selected($filtro_zona, $zona); ?>>
                                <?php echo esc_html($zona); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="tipo"><?php esc_html_e('Tipo Vehículo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select name="tipo" id="tipo" class="regular-text">
                        <option value=""><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="coche" <?php selected($filtro_tipo, 'coche'); ?>><?php esc_html_e('Coche', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="moto" <?php selected($filtro_tipo, 'moto'); ?>><?php esc_html_e('Moto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="bicicleta" <?php selected($filtro_tipo, 'bicicleta'); ?>><?php esc_html_e('Bicicleta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="furgoneta" <?php selected($filtro_tipo, 'furgoneta'); ?>><?php esc_html_e('Furgoneta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <div>
                    <label for="busqueda"><?php esc_html_e('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="s" id="busqueda" class="regular-text" placeholder="<?php esc_attr_e('Número o dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" value="<?php echo esc_attr($filtro_busqueda); ?>">
                </div>

                <div>
                    <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas')); ?>" class="button"><?php esc_html_e('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                </div>

            </div>
        </form>
    </div>

    <!-- Lista de plazas -->
    <div class="card" style="padding: 0; margin: 20px 0;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 80px;"><?php esc_html_e('Número', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Zona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Propietario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Precio/hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($plazas)) : ?>
                    <?php foreach ($plazas as $plaza) : ?>
                        <tr>
                            <td><strong><?php echo esc_html($plaza->numero_plaza); ?></strong></td>
                            <td>
                                <?php echo esc_html($plaza->direccion); ?>
                                <?php if ($plaza->coordenadas_lat && $plaza->coordenadas_lng) : ?>
                                    <br><small style="color: #666;">
                                        📍 <a href="https://www.google.com/maps?q=<?php echo esc_attr($plaza->coordenadas_lat); ?>,<?php echo esc_attr($plaza->coordenadas_lng); ?>" target="_blank">
                                            <?php esc_html_e('Ver en mapa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($plaza->zona); ?></td>
                            <td>
                                <?php
                                $iconos_tipo = [
                                    'coche' => '🚗',
                                    'moto' => '🏍️',
                                    'bicicleta' => '🚲',
                                    'furgoneta' => '🚐'
                                ];
                                echo esc_html($iconos_tipo[$plaza->tipo_vehiculo] ?? '🚗') . ' ' . esc_html(ucfirst($plaza->tipo_vehiculo));
                                ?>
                            </td>
                            <td><?php echo esc_html($plaza->nombre_propietario ?? __('Sin propietario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                            <td><strong>€<?php echo esc_html(number_format($plaza->precio_por_hora, 2, ',', '.')); ?></strong></td>
                            <td>
                                <?php
                                $color_estado = [
                                    'disponible' => '#00a32a',
                                    'ocupada' => '#d63638',
                                    'mantenimiento' => '#dba617'
                                ];
                                ?>
                                <span class="badge" style="background: <?php echo esc_attr($color_estado[$plaza->estado] ?? '#666'); ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 11px;">
                                    <?php echo esc_html(ucfirst($plaza->estado)); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-reservas&plaza_id=' . $plaza->id)); ?>">
                                    <?php echo esc_html($plaza->total_reservas); ?>
                                </a>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&action=ver&plaza_id=' . $plaza->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-parkings-plazas&action=editar&plaza_id=' . $plaza->id)); ?>" class="button button-small">
                                    <?php esc_html_e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <div style="color: #666;">
                                <span class="dashicons dashicons-location" style="font-size: 48px; opacity: 0.3;"></span>
                                <p><?php esc_html_e('No se encontraron plazas con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(esc_html__('%s plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($total_plazas)); ?>
                </span>
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_paginas,
                    'current' => $pagina_actual
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 782px) {
    .flavor-stats-mini { flex-direction: column; }
    .wp-list-table td { font-size: 12px; }
}
</style>
