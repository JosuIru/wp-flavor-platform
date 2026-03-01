<?php
/**
 * Vista: Listado de Socios (Admin)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_socios = $wpdb->prefix . 'flavor_socios';
$tabla_users = $wpdb->users;

// Procesar cambio de estado
if (!empty($_GET['socio_action']) && !empty($_GET['socio_id']) && isset($_GET['_wpnonce'])) {
    $accion_estado = sanitize_text_field($_GET['socio_action']);
    $identificador_socio = absint($_GET['socio_id']);
    $nonce_verificado = wp_verify_nonce($_GET['_wpnonce'], 'socios_estado_' . $identificador_socio);

    if ($nonce_verificado && in_array($accion_estado, ['activo', 'suspendido', 'baja'], true)) {
        $datos_actualizacion = ['estado' => $accion_estado];
        if ($accion_estado === 'baja') {
            $datos_actualizacion['fecha_baja'] = date('Y-m-d');
        }
        $wpdb->update($tabla_socios, $datos_actualizacion, ['id' => $identificador_socio]);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Estado del socio actualizado.', 'flavor-chat-ia') . '</p></div>';
    }
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$busqueda_texto = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$registros_por_pagina = 20;
$offset_consulta = ($pagina_actual - 1) * $registros_por_pagina;

// Tipos de socio configurados
$modulo_socios = Flavor_Chat_IA::get_instance()->get_module('socios');
$tipos_socio = $modulo_socios ? $modulo_socios->get_setting('tipos_socio', []) : [];

// Construir consulta
$condiciones_where = [];
$parametros_consulta = [];

if ($estado_filtro) {
    $condiciones_where[] = 's.estado = %s';
    $parametros_consulta[] = $estado_filtro;
}
if ($tipo_filtro) {
    $condiciones_where[] = 's.tipo_socio = %s';
    $parametros_consulta[] = $tipo_filtro;
}
if ($busqueda_texto) {
    $condiciones_where[] = '(u.display_name LIKE %s OR u.user_email LIKE %s OR s.numero_socio LIKE %s)';
    $texto_busqueda_like = '%' . $wpdb->esc_like($busqueda_texto) . '%';
    $parametros_consulta[] = $texto_busqueda_like;
    $parametros_consulta[] = $texto_busqueda_like;
    $parametros_consulta[] = $texto_busqueda_like;
}

$clausula_where = !empty($condiciones_where) ? 'WHERE ' . implode(' AND ', $condiciones_where) : '';

// Contar total
$sql_conteo = "SELECT COUNT(*) FROM $tabla_socios s LEFT JOIN $tabla_users u ON s.usuario_id = u.ID $clausula_where";
$total_registros = $parametros_consulta ? $wpdb->get_var($wpdb->prepare($sql_conteo, $parametros_consulta)) : $wpdb->get_var($sql_conteo);
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Obtener registros
$sql_socios = "SELECT s.*, u.display_name, u.user_email
               FROM $tabla_socios s
               LEFT JOIN $tabla_users u ON s.usuario_id = u.ID
               $clausula_where
               ORDER BY s.fecha_alta DESC
               LIMIT %d OFFSET %d";

$parametros_paginacion = array_merge($parametros_consulta, [$registros_por_pagina, $offset_consulta]);
$lista_socios = $wpdb->get_results($wpdb->prepare($sql_socios, $parametros_paginacion));

// Estadísticas rápidas
$total_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'activo'");
$total_suspendidos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'suspendido'");
$total_bajas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_socios WHERE estado = 'baja'");

// Altas del mes actual
$mes_actual = date('Y-m');
$altas_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_socios WHERE DATE_FORMAT(fecha_alta, '%%Y-%%m') = %s",
    $mes_actual
));
$bajas_mes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_socios WHERE fecha_baja IS NOT NULL AND DATE_FORMAT(fecha_baja, '%%Y-%%m') = %s",
    $mes_actual
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Gestión de Socios', 'flavor-chat-ia'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=socios-altas-bajas'); ?>" class="page-title-action">
        <?php echo esc_html__('Nuevo Socio', 'flavor-chat-ia'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Estadísticas rápidas -->
    <div class="flavor-stats-row" style="display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap;">
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #10b981; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #10b981;"><?php echo number_format($total_activos); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Activos', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #f59e0b; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #f59e0b;"><?php echo number_format($total_suspendidos); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Suspendidos', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #94a3b8; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #94a3b8;"><?php echo number_format($total_bajas); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Bajas', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #3b82f6; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #3b82f6;">+<?php echo number_format($altas_mes); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Altas este mes', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="flavor-mini-stat" style="background: #fff; padding: 15px 20px; border: 1px solid #c3c4c7; border-left: 4px solid #ef4444; flex: 1; min-width: 150px;">
            <span style="font-size: 24px; font-weight: bold; color: #ef4444;">-<?php echo number_format($bajas_mes); ?></span>
            <span style="display: block; color: #64748b; font-size: 12px;"><?php echo esc_html__('Bajas este mes', 'flavor-chat-ia'); ?></span>
        </div>
    </div>

    <!-- Filtros -->
    <form method="get" class="socios-filtros" style="background: #fff; padding: 15px; border: 1px solid #c3c4c7; margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
        <input type="hidden" name="page" value="socios-listado">

        <select name="estado" style="min-width: 150px;">
            <option value=""><?php echo esc_html__('Todos los estados', 'flavor-chat-ia'); ?></option>
            <option value="activo" <?php selected($estado_filtro, 'activo'); ?>><?php echo esc_html__('Activo', 'flavor-chat-ia'); ?></option>
            <option value="suspendido" <?php selected($estado_filtro, 'suspendido'); ?>><?php echo esc_html__('Suspendido', 'flavor-chat-ia'); ?></option>
            <option value="baja" <?php selected($estado_filtro, 'baja'); ?>><?php echo esc_html__('Baja', 'flavor-chat-ia'); ?></option>
        </select>

        <select name="tipo" style="min-width: 150px;">
            <option value=""><?php echo esc_html__('Todos los tipos', 'flavor-chat-ia'); ?></option>
            <?php foreach ($tipos_socio as $clave_tipo => $etiqueta_tipo): ?>
                <option value="<?php echo esc_attr($clave_tipo); ?>" <?php selected($tipo_filtro, $clave_tipo); ?>>
                    <?php echo esc_html($etiqueta_tipo); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="search" name="s" placeholder="<?php esc_attr_e('Buscar por nombre, email o número...', 'flavor-chat-ia'); ?>"
               value="<?php echo esc_attr($busqueda_texto); ?>" style="min-width: 250px;">

        <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>

        <?php if ($estado_filtro || $tipo_filtro || $busqueda_texto): ?>
            <a href="<?php echo admin_url('admin.php?page=socios-listado'); ?>" class="button">
                <?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </form>

    <!-- Resultados -->
    <p class="socios-resultados-info" style="color: #646970; margin-bottom: 10px;">
        <?php printf(
            esc_html__('Mostrando %d de %d socios', 'flavor-chat-ia'),
            count($lista_socios),
            $total_registros
        ); ?>
    </p>

    <?php if (empty($lista_socios)): ?>
        <div class="notice notice-info">
            <p><?php echo esc_html__('No se encontraron socios con los filtros aplicados.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('Número', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Nombre', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Tipo', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Cuota', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Fecha Alta', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lista_socios as $socio): ?>
                    <tr>
                        <td><strong><?php echo esc_html($socio->numero_socio); ?></strong></td>
                        <td>
                            <?php echo get_avatar($socio->usuario_id, 32, '', '', ['style' => 'vertical-align: middle; margin-right: 8px; border-radius: 50%;']); ?>
                            <?php echo esc_html($socio->display_name ?: __('Sin nombre', 'flavor-chat-ia')); ?>
                        </td>
                        <td><?php echo esc_html($socio->user_email ?: '-'); ?></td>
                        <td>
                            <?php
                            $etiqueta_tipo_socio = $tipos_socio[$socio->tipo_socio] ?? ucfirst($socio->tipo_socio);
                            echo esc_html($etiqueta_tipo_socio);
                            ?>
                        </td>
                        <td>
                            <?php echo esc_html(number_format((float)$socio->cuota_mensual, 2, ',', '.')); ?> €/mes
                            <?php if ($socio->cuota_reducida): ?>
                                <span style="background: #dbeafe; color: #1e40af; padding: 2px 6px; border-radius: 3px; font-size: 10px; margin-left: 5px;">
                                    <?php echo esc_html__('Reducida', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($socio->fecha_alta))); ?></td>
                        <td>
                            <?php
                            $colores_estado = [
                                'activo' => ['bg' => '#d1fae5', 'color' => '#065f46'],
                                'suspendido' => ['bg' => '#fef3c7', 'color' => '#92400e'],
                                'baja' => ['bg' => '#f3f4f6', 'color' => '#374151'],
                            ];
                            $estilo_estado = $colores_estado[$socio->estado] ?? $colores_estado['baja'];
                            ?>
                            <span style="background: <?php echo $estilo_estado['bg']; ?>; color: <?php echo $estilo_estado['color']; ?>; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                <?php echo esc_html(ucfirst($socio->estado)); ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $acciones_estado = [];
                            $estados_disponibles = ['activo' => __('Activar', 'flavor-chat-ia'), 'suspendido' => __('Suspender', 'flavor-chat-ia'), 'baja' => __('Dar baja', 'flavor-chat-ia')];
                            foreach ($estados_disponibles as $estado_clave => $estado_etiqueta) {
                                if ($estado_clave !== $socio->estado) {
                                    $url_accion = wp_nonce_url(
                                        add_query_arg([
                                            'page' => 'socios-listado',
                                            'socio_action' => $estado_clave,
                                            'socio_id' => $socio->id,
                                        ], admin_url('admin.php')),
                                        'socios_estado_' . $socio->id
                                    );
                                    $acciones_estado[] = '<a href="' . esc_url($url_accion) . '">' . esc_html($estado_etiqueta) . '</a>';
                                }
                            }
                            echo implode(' | ', $acciones_estado);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="pagination-links">
                        <?php
                        $url_base_paginacion = add_query_arg([
                            'page' => 'socios-listado',
                            'estado' => $estado_filtro,
                            'tipo' => $tipo_filtro,
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
