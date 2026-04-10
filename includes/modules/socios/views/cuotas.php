<?php
/**
 * Vista: Gestión de Cuotas (Admin)
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_socios = $wpdb->prefix . 'flavor_socios';
$tabla_cuotas = $wpdb->prefix . 'flavor_socios_cuotas';
$tabla_users = $wpdb->users;

// Procesar cambio de estado de cuota
if (!empty($_GET['cuota_action']) && !empty($_GET['cuota_id']) && isset($_GET['_wpnonce'])) {
    $accion_cuota = sanitize_text_field($_GET['cuota_action']);
    $identificador_cuota = absint($_GET['cuota_id']);
    $nonce_verificado = wp_verify_nonce($_GET['_wpnonce'], 'socios_cuota_' . $identificador_cuota);

    if ($nonce_verificado && in_array($accion_cuota, ['pagada', 'vencida', 'condonada'], true)) {
        $datos_actualizacion = ['estado' => $accion_cuota];
        if ($accion_cuota === 'pagada') {
            $datos_actualizacion['fecha_pago'] = date('Y-m-d');
            $datos_actualizacion['metodo_pago'] = 'manual_admin';
        }
        $wpdb->update($tabla_cuotas, $datos_actualizacion, ['id' => $identificador_cuota]);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Estado de la cuota actualizado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }
}

// Generar cuotas del mes si se solicita
if (!empty($_POST['generar_cuotas']) && wp_verify_nonce($_POST['_wpnonce'], 'socios_generar_cuotas')) {
    $periodo_generar = sanitize_text_field($_POST['periodo'] ?? date('Y-m'));
    $socios_activos = $wpdb->get_results("SELECT id, cuota_mensual FROM $tabla_socios WHERE estado = 'activo'");
    $dia_cargo = 1;

    $modulo_socios = Flavor_Platform::get_instance()->get_module('socios');
    if ($modulo_socios) {
        $dia_cargo = $modulo_socios->get_setting('dia_cargo', 1);
    }

    $cuotas_generadas = 0;
    foreach ($socios_activos as $socio) {
        // Verificar si ya existe cuota para este periodo
        $cuota_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_cuotas WHERE socio_id = %d AND periodo = %s",
            $socio->id,
            $periodo_generar
        ));

        if (!$cuota_existente) {
            $fecha_cargo = $periodo_generar . '-' . str_pad($dia_cargo, 2, '0', STR_PAD_LEFT);
            $wpdb->insert($tabla_cuotas, [
                'socio_id' => $socio->id,
                'periodo' => $periodo_generar,
                'importe' => $socio->cuota_mensual,
                'fecha_cargo' => $fecha_cargo,
                'estado' => 'pendiente',
            ]);
            $cuotas_generadas++;
        }
    }

    if ($cuotas_generadas > 0) {
        echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(
            esc_html__('Se generaron %d cuotas para el periodo %s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $cuotas_generadas,
            $periodo_generar
        ) . '</p></div>';
    } else {
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html__('No se generaron cuotas nuevas. Es posible que ya existan para este periodo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$periodo_filtro = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : '';
$busqueda_texto = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$registros_por_pagina = 30;
$offset_consulta = ($pagina_actual - 1) * $registros_por_pagina;

// Construir consulta
$condiciones_where = [];
$parametros_consulta = [];

if ($estado_filtro) {
    $condiciones_where[] = 'c.estado = %s';
    $parametros_consulta[] = $estado_filtro;
}
if ($periodo_filtro) {
    $condiciones_where[] = 'c.periodo = %s';
    $parametros_consulta[] = $periodo_filtro;
}
if ($busqueda_texto) {
    $condiciones_where[] = '(u.display_name LIKE %s OR s.numero_socio LIKE %s)';
    $texto_busqueda_like = '%' . $wpdb->esc_like($busqueda_texto) . '%';
    $parametros_consulta[] = $texto_busqueda_like;
    $parametros_consulta[] = $texto_busqueda_like;
}

$clausula_where = !empty($condiciones_where) ? 'WHERE ' . implode(' AND ', $condiciones_where) : '';

// Contar total
$sql_conteo = "SELECT COUNT(*) FROM $tabla_cuotas c
               INNER JOIN $tabla_socios s ON c.socio_id = s.id
               LEFT JOIN $tabla_users u ON s.usuario_id = u.ID
               $clausula_where";
$total_registros = $parametros_consulta ? $wpdb->get_var($wpdb->prepare($sql_conteo, $parametros_consulta)) : $wpdb->get_var($sql_conteo);
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros
$sql_cuotas = "SELECT c.*, s.numero_socio, u.display_name
               FROM $tabla_cuotas c
               INNER JOIN $tabla_socios s ON c.socio_id = s.id
               LEFT JOIN $tabla_users u ON s.usuario_id = u.ID
               $clausula_where
               ORDER BY c.fecha_cargo DESC
               LIMIT %d OFFSET %d";

$parametros_paginacion = array_merge($parametros_consulta, [$registros_por_pagina, $offset_consulta]);
$lista_cuotas = $wpdb->get_results($wpdb->prepare($sql_cuotas, $parametros_paginacion));

// Estadísticas rápidas
$cuotas_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pendiente'");
$cuotas_pagadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'pagada'");
$cuotas_vencidas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_cuotas WHERE estado = 'vencida'");
$importe_pendiente = $wpdb->get_var("SELECT IFNULL(SUM(importe), 0) FROM $tabla_cuotas WHERE estado = 'pendiente'");
$importe_cobrado_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT IFNULL(SUM(importe), 0) FROM $tabla_cuotas WHERE estado = 'pagada' AND DATE_FORMAT(fecha_pago, '%%Y-%%m') = %s",
    date('Y-m')
));

// Obtener periodos disponibles para filtro
$periodos_disponibles = $wpdb->get_col("SELECT DISTINCT periodo FROM $tabla_cuotas ORDER BY periodo DESC LIMIT 24");
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
    <button type="button" class="page-title-action" id="btn-generar-cuotas">
        <?php echo esc_html__('Generar Cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </button>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-row" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #f59e0b; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #f59e0b;"><?php echo number_format($cuotas_pendientes); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #10b981; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #10b981;"><?php echo number_format($cuotas_pagadas); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Pagadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #ef4444; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #ef4444;"><?php echo number_format($cuotas_vencidas); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Vencidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #8b5cf6; flex: 1; min-width: 180px;">
            <span style="font-size: 24px; font-weight: bold; color: #8b5cf6;"><?php echo number_format($importe_pendiente, 2, ',', '.'); ?> &euro;</span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Importe pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #06b6d4; flex: 1; min-width: 180px;">
            <span style="font-size: 24px; font-weight: bold; color: #06b6d4;"><?php echo number_format($importe_cobrado_mes, 2, ',', '.'); ?> &euro;</span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Cobrado este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Modal para generar cuotas -->
    <div id="modal-generar-cuotas" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 100000; align-items: center; justify-content: center;">
        <div style="background: #fff; padding: 30px; border-radius: 8px; max-width: 400px; width: 90%;">
            <h2 style="margin-top: 0;"><?php echo esc_html__('Generar Cuotas del Mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <form method="post">
                <?php wp_nonce_field('socios_generar_cuotas'); ?>
                <input type="hidden" name="generar_cuotas" value="1">
                <p>
                    <label for="periodo"><strong><?php echo esc_html__('Periodo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                    <input type="month" name="periodo" id="periodo" value="<?php echo esc_attr(date('Y-m')); ?>" style="width: 100%;">
                </p>
                <p class="description">
                    <?php echo esc_html__('Se crearán cuotas para todos los miembros activos que no tengan cuota en este periodo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
                <p style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="button" class="button" onclick="document.getElementById('modal-generar-cuotas').style.display='none'">
                        <?php echo esc_html__('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php echo esc_html__('Generar Cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="cuotas-filtros" style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="page" value="socios-cuotas">

        <select name="estado" style="min-width: 150px;">
            <option value=""><?php echo esc_html__('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="pendiente" <?php selected($estado_filtro, 'pendiente'); ?>><?php echo esc_html__('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="pagada" <?php selected($estado_filtro, 'pagada'); ?>><?php echo esc_html__('Pagada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="vencida" <?php selected($estado_filtro, 'vencida'); ?>><?php echo esc_html__('Vencida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <option value="condonada" <?php selected($estado_filtro, 'condonada'); ?>><?php echo esc_html__('Condonada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
        </select>

        <select name="periodo" style="min-width: 150px;">
            <option value=""><?php echo esc_html__('Todos los periodos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            <?php foreach ($periodos_disponibles as $periodo): ?>
                <option value="<?php echo esc_attr($periodo); ?>" <?php selected($periodo_filtro, $periodo); ?>>
                    <?php echo esc_html(date_i18n('F Y', strtotime($periodo . '-01'))); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="search" name="s" placeholder="<?php esc_attr_e('Buscar por socio...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
               value="<?php echo esc_attr($busqueda_texto); ?>" style="min-width: 200px;">

        <button type="submit" class="button"><?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

        <?php if ($estado_filtro || $periodo_filtro || $busqueda_texto): ?>
            <a href="<?php echo admin_url('admin.php?page=socios-cuotas'); ?>" class="button">
                <?php echo esc_html__('Limpiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </form>

    <!-- Resultados -->
    <p class="cuotas-resultados-info" style="color: #646970; margin-bottom: 10px;">
        <?php printf(
            esc_html__('Mostrando %d de %d cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            count($lista_cuotas),
            $total_registros
        ); ?>
    </p>

    <?php if (empty($lista_cuotas)): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No se encontraron cuotas con los filtros aplicados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Socio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Numero', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Periodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Importe', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Fecha Cargo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Fecha Pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista_cuotas as $cuota): ?>
                    <tr>
                        <td><?php echo esc_html($cuota->id); ?></td>
                        <td><strong><?php echo esc_html($cuota->display_name ?: __('Sin nombre', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong></td>
                        <td><?php echo esc_html($cuota->numero_socio); ?></td>
                        <td><?php echo esc_html(date_i18n('F Y', strtotime($cuota->periodo . '-01'))); ?></td>
                        <td><strong><?php echo esc_html(number_format((float)$cuota->importe, 2, ',', '.')); ?> &euro;</strong></td>
                        <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($cuota->fecha_cargo))); ?></td>
                        <td>
                            <?php if ($cuota->fecha_pago): ?>
                                <?php echo esc_html(date_i18n('d/m/Y', strtotime($cuota->fecha_pago))); ?>
                            <?php else: ?>
                                <span style="color: #94a3b8;">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $colores_estado = [
                                'pendiente' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                'pagada' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                'vencida' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                                'condonada' => ['bg' => '#e0e7ff', 'color' => '#3730a3'],
                            ];
                            $estilo_estado = $colores_estado[$cuota->estado] ?? $colores_estado['pendiente'];
                            ?>
                            <span style="background: <?php echo $estilo_estado['bg']; ?>; color: <?php echo $estilo_estado['color']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                <?php echo esc_html(ucfirst($cuota->estado)); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $acciones_cuota = [];
                            $estados_cuota_disponibles = [
                                'pagada' => __('Marcar pagada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                'vencida' => __('Marcar vencida', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                'condonada' => __('Condonar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            ];
                            foreach ($estados_cuota_disponibles as $estado_clave => $estado_etiqueta) {
                                if ($estado_clave !== $cuota->estado) {
                                    $url_accion = wp_nonce_url(
                                        add_query_arg([
                                            'page' => 'socios-cuotas',
                                            'cuota_action' => $estado_clave,
                                            'cuota_id' => $cuota->id,
                                        ], admin_url('admin.php')),
                                        'socios_cuota_' . $cuota->id
                                    );
                                    $acciones_cuota[] = '<a href="' . esc_url($url_accion) . '">' . esc_html($estado_etiqueta) . '</a>';
                                }
                            }
                            echo implode(' | ', $acciones_cuota);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginacion -->
        <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php
                        $url_base_paginacion = add_query_arg([
                            'page' => 'socios-cuotas',
                            'estado' => $estado_filtro,
                            'periodo' => $periodo_filtro,
                            's' => $busqueda_texto,
                        ], admin_url('admin.php'));

                        if ($pagina_actual > 1):
                            echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $pagina_actual - 1, $url_base_paginacion)) . '">&lsaquo;</a> ';
                        endif;

                        echo '<span class="paging-input">' . $pagina_actual . ' de ' . $total_paginas . '</span>';

                        if ($pagina_actual < $total_paginas):
                            echo ' <a class="next-page button" href="' . esc_url(add_query_arg('paged', $pagina_actual + 1, $url_base_paginacion)) . '">&rsaquo;</a>';
                        endif;
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.getElementById('btn-generar-cuotas').addEventListener('click', function() {
    document.getElementById('modal-generar-cuotas').style.display = 'flex';
});
</script>
