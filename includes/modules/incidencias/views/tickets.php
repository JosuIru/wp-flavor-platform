<?php
/**
 * Vista Tickets - Módulo Incidencias
 *
 * Gestión completa de incidencias: listado, filtros, detalles y asignación
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
$tabla_seguimiento = $wpdb->prefix . 'flavor_incidencias_seguimiento';

// Obtener ID de incidencia para vista detalle
$incidencia_id_detalle = isset($_GET['id']) ? absint($_GET['id']) : 0;

// Procesar acciones
if (isset($_POST['action'])) {
    check_admin_referer('flavor_incidencias_action');

    switch ($_POST['action']) {
        case 'cambiar_estado':
            $incidencia_id = absint($_POST['incidencia_id']);
            $nuevo_estado = sanitize_text_field($_POST['nuevo_estado']);
            $comentario = sanitize_textarea_field($_POST['comentario']);

            $incidencia_actual = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_incidencias WHERE id = %d",
                $incidencia_id
            ));

            $wpdb->update(
                $tabla_incidencias,
                [
                    'estado' => $nuevo_estado,
                    'fecha_resolucion' => ($nuevo_estado === 'resuelta') ? current_time('mysql') : null
                ],
                ['id' => $incidencia_id],
                ['%s', '%s'],
                ['%d']
            );

            // Registrar seguimiento
            $wpdb->insert(
                $tabla_seguimiento,
                [
                    'incidencia_id' => $incidencia_id,
                    'usuario_id' => get_current_user_id(),
                    'tipo' => 'cambio_estado',
                    'contenido' => $comentario,
                    'estado_anterior' => $incidencia_actual->estado,
                    'estado_nuevo' => $nuevo_estado,
                    'es_publico' => 1
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
            );

            echo '<div class="notice notice-success"><p>Estado actualizado correctamente</p></div>';
            break;

        case 'asignar_personal':
            $incidencia_id = absint($_POST['incidencia_id']);
            $asignado_a = absint($_POST['asignado_a']);
            $departamento = sanitize_text_field($_POST['departamento']);

            $wpdb->update(
                $tabla_incidencias,
                [
                    'asignado_a' => $asignado_a,
                    'departamento' => $departamento,
                    'fecha_asignacion' => current_time('mysql'),
                    'estado' => 'en_proceso'
                ],
                ['id' => $incidencia_id],
                ['%d', '%s', '%s', '%s'],
                ['%d']
            );

            // Registrar seguimiento
            $usuario_asignado = get_user_by('id', $asignado_a);
            $wpdb->insert(
                $tabla_seguimiento,
                [
                    'incidencia_id' => $incidencia_id,
                    'usuario_id' => get_current_user_id(),
                    'tipo' => 'asignacion',
                    'contenido' => sprintf('Asignado a %s (%s)', $usuario_asignado->display_name, $departamento),
                    'es_publico' => 1
                ],
                ['%d', '%d', '%s', '%s', '%d']
            );

            echo '<div class="notice notice-success"><p>Incidencia asignada correctamente</p></div>';
            break;

        case 'agregar_comentario':
            $incidencia_id = absint($_POST['incidencia_id']);
            $comentario = sanitize_textarea_field($_POST['comentario']);
            $es_publico = isset($_POST['es_publico']) ? 1 : 0;

            $wpdb->insert(
                $tabla_seguimiento,
                [
                    'incidencia_id' => $incidencia_id,
                    'usuario_id' => get_current_user_id(),
                    'tipo' => 'comentario',
                    'contenido' => $comentario,
                    'es_publico' => $es_publico
                ],
                ['%d', '%d', '%s', '%s', '%d']
            );

            echo '<div class="notice notice-success"><p>Comentario agregado correctamente</p></div>';
            break;
    }
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$categoria_filtro = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$prioridad_filtro = isset($_GET['prioridad']) ? sanitize_text_field($_GET['prioridad']) : '';
$buscar = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

// Construir query
$where_condiciones = ['1=1'];
$prepare_valores = [];

if ($estado_filtro) {
    $where_condiciones[] = 'estado = %s';
    $prepare_valores[] = $estado_filtro;
}
if ($categoria_filtro) {
    $where_condiciones[] = 'categoria = %s';
    $prepare_valores[] = $categoria_filtro;
}
if ($prioridad_filtro) {
    $where_condiciones[] = 'prioridad = %s';
    $prepare_valores[] = $prioridad_filtro;
}
if ($buscar) {
    $where_condiciones[] = '(titulo LIKE %s OR numero_incidencia LIKE %s OR descripcion LIKE %s)';
    $busqueda_like = '%' . $wpdb->esc_like($buscar) . '%';
    $prepare_valores[] = $busqueda_like;
    $prepare_valores[] = $busqueda_like;
    $prepare_valores[] = $busqueda_like;
}

$where_sql = implode(' AND ', $where_condiciones);

// Paginación
$items_por_pagina = 20;
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$offset = ($pagina_actual - 1) * $items_por_pagina;

// Obtener total
if (!empty($prepare_valores)) {
    $total_items = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_incidencias WHERE $where_sql",
        ...$prepare_valores
    ));
} else {
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE $where_sql");
}

$total_paginas = ceil($total_items / $items_por_pagina);

// Obtener incidencias
$prepare_valores[] = $offset;
$prepare_valores[] = $items_por_pagina;

$incidencias = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_incidencias WHERE $where_sql ORDER BY fecha_reporte DESC LIMIT %d, %d",
    ...$prepare_valores
));

// Si hay ID de detalle, obtener incidencia específica
$incidencia_detalle = null;
$seguimiento = [];
if ($incidencia_id_detalle) {
    $incidencia_detalle = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_incidencias WHERE id = %d",
        $incidencia_id_detalle
    ));

    if ($incidencia_detalle) {
        $seguimiento = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_seguimiento WHERE incidencia_id = %d ORDER BY fecha_creacion DESC",
            $incidencia_id_detalle
        ));
    }
}

// Obtener usuarios para asignación
$usuarios_staff = get_users(['role__in' => ['administrator', 'editor']]);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-tickets-alt"></span>
        <?php echo esc_html__('Gestión de Incidencias', 'flavor-chat-ia'); ?>
    </h1>

    <?php if (!$incidencia_id_detalle): ?>
        <a href="#" class="page-title-action" onclick="document.getElementById('filtros').style.display='block'; return false;">
            <span class="dashicons dashicons-filter"></span> <?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?>
        </a>
    <?php else: ?>
        <a href="<?php echo admin_url('admin.php?page=incidencias-todas'); ?>" class="page-title-action">
            <span class="dashicons dashicons-arrow-left-alt"></span> <?php echo esc_html__('Volver al listado', 'flavor-chat-ia'); ?>
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if (!$incidencia_id_detalle): ?>

        <!-- Filtros -->
        <div id="filtros" class="postbox" style="margin: 20px 0; display: none;">
            <div class="inside">
                <form method="get" action="">
                    <input type="hidden" name="page" value="<?php echo esc_attr__('incidencias-todas', 'flavor-chat-ia'); ?>">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">

                        <div>
                            <label><strong><?php echo esc_html__('Estado:', 'flavor-chat-ia'); ?></strong></label>
                            <select name="estado" class="regular-text">
                                <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('pendiente', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'pendiente'); ?>><?php echo esc_html__('Pendiente', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('en_proceso', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'en_proceso'); ?>><?php echo esc_html__('En Proceso', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('resuelta', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'resuelta'); ?>><?php echo esc_html__('Resuelta', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('cerrada', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'cerrada'); ?>><?php echo esc_html__('Cerrada', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('rechazada', 'flavor-chat-ia'); ?>" <?php selected($estado_filtro, 'rechazada'); ?>><?php echo esc_html__('Rechazada', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div>
                            <label><strong><?php echo esc_html__('Categoría:', 'flavor-chat-ia'); ?></strong></label>
                            <select name="categoria" class="regular-text">
                                <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('alumbrado', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'alumbrado'); ?>><?php echo esc_html__('Alumbrado', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('limpieza', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'limpieza'); ?>><?php echo esc_html__('Limpieza', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('via_publica', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'via_publica'); ?>><?php echo esc_html__('Vía Pública', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('mobiliario', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'mobiliario'); ?>><?php echo esc_html__('Mobiliario', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('parques', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'parques'); ?>><?php echo esc_html__('Parques', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('ruido', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'ruido'); ?>><?php echo esc_html__('Ruido', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('agua', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'agua'); ?>><?php echo esc_html__('Agua', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('señalizacion', 'flavor-chat-ia'); ?>" <?php selected($categoria_filtro, 'señalizacion'); ?>><?php echo esc_html__('Señalización', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div>
                            <label><strong><?php echo esc_html__('Prioridad:', 'flavor-chat-ia'); ?></strong></label>
                            <select name="prioridad" class="regular-text">
                                <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('baja', 'flavor-chat-ia'); ?>" <?php selected($prioridad_filtro, 'baja'); ?>><?php echo esc_html__('Baja', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('media', 'flavor-chat-ia'); ?>" <?php selected($prioridad_filtro, 'media'); ?>><?php echo esc_html__('Media', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('alta', 'flavor-chat-ia'); ?>" <?php selected($prioridad_filtro, 'alta'); ?>><?php echo esc_html__('Alta', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('urgente', 'flavor-chat-ia'); ?>" <?php selected($prioridad_filtro, 'urgente'); ?>><?php echo esc_html__('Urgente', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>

                        <div>
                            <label><strong><?php echo esc_html__('Buscar:', 'flavor-chat-ia'); ?></strong></label>
                            <input type="text" name="s" value="<?php echo esc_attr($buscar); ?>" class="regular-text" placeholder="<?php echo esc_attr__('Número, título...', 'flavor-chat-ia'); ?>">
                        </div>

                    </div>

                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search"></span> <?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=incidencias-todas'); ?>" class="button">
                        <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
                    </a>
                </form>
            </div>
        </div>

        <!-- Listado de incidencias -->
        <div class="postbox" style="margin-top: 20px;">
            <div class="inside" style="margin: 0; padding: 0;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 100px;"><?php echo esc_html__('Número', 'flavor-chat-ia'); ?></th>
                            <th><?php echo esc_html__('Título', 'flavor-chat-ia'); ?></th>
                            <th style="width: 120px;"><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Prioridad', 'flavor-chat-ia'); ?></th>
                            <th style="width: 150px;"><?php echo esc_html__('Fecha Reporte', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php echo esc_html__('Votos', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($incidencias)): ?>
                            <?php foreach ($incidencias as $incidencia): ?>
                                <tr>
                                    <td><strong><?php echo esc_html($incidencia->numero_incidencia); ?></strong></td>
                                    <td>
                                        <strong><?php echo esc_html($incidencia->titulo); ?></strong>
                                        <?php if ($incidencia->asignado_a): ?>
                                            <br><small style="color: #2271b1;">
                                                <span class="dashicons dashicons-admin-users" style="font-size: 14px;"></span>
                                                <?php
                                                $asignado = get_user_by('id', $incidencia->asignado_a);
                                                echo $asignado ? esc_html($asignado->display_name) : 'Usuario eliminado';
                                                ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $incidencia->categoria))); ?></td>
                                    <td>
                                        <?php
                                        $estado_colores = [
                                            'pendiente' => '#f0b849',
                                            'en_proceso' => '#2271b1',
                                            'resuelta' => '#00a32a',
                                            'cerrada' => '#646970',
                                            'rechazada' => '#d63638'
                                        ];
                                        $color_estado = $estado_colores[$incidencia->estado] ?? '#646970';
                                        ?>
                                        <span class="flavor-badge" style="background: <?php echo $color_estado; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $incidencia->estado))); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $prioridad_colores = [
                                            'baja' => '#00a32a',
                                            'media' => '#f0b849',
                                            'alta' => '#ff8c00',
                                            'urgente' => '#d63638'
                                        ];
                                        $color_prioridad = $prioridad_colores[$incidencia->prioridad] ?? '#646970';
                                        ?>
                                        <span class="flavor-badge" style="background: <?php echo $color_prioridad; ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                            <?php echo esc_html(ucfirst($incidencia->prioridad)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($incidencia->fecha_reporte)); ?></td>
                                    <td style="text-align: center;">
                                        <span class="dashicons dashicons-thumbs-up" style="color: #2271b1;"></span>
                                        <?php echo $incidencia->votos_ciudadanos; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('admin.php?page=incidencias-todas&id=' . $incidencia->id); ?>" class="button button-small">
                                            <?php echo esc_html__('Ver detalles', 'flavor-chat-ia'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px 0; color: #646970;">
                                    <?php echo esc_html__('No se encontraron incidencias', 'flavor-chat-ia'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php echo number_format($total_items); ?> elementos</span>
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => $pagina_actual,
                        'total' => $total_paginas,
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;'
                    ]);
                    ?>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>

        <!-- Vista detalle de incidencia -->
        <?php if ($incidencia_detalle): ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 20px;">

                <!-- Columna principal -->
                <div>

                    <!-- Información principal -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2>
                                <?php echo esc_html($incidencia_detalle->titulo); ?>
                                <span style="color: #646970; font-size: 14px; font-weight: normal; margin-left: 10px;">
                                    #<?php echo esc_html($incidencia_detalle->numero_incidencia); ?>
                                </span>
                            </h2>
                        </div>
                        <div class="inside">
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 20px;">
                                <div>
                                    <strong><?php echo esc_html__('Categoría:', 'flavor-chat-ia'); ?></strong><br>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $incidencia_detalle->categoria))); ?>
                                </div>
                                <div>
                                    <strong><?php echo esc_html__('Estado:', 'flavor-chat-ia'); ?></strong><br>
                                    <?php
                                    $estado_colores = [
                                        'pendiente' => '#f0b849',
                                        'en_proceso' => '#2271b1',
                                        'resuelta' => '#00a32a',
                                        'cerrada' => '#646970',
                                        'rechazada' => '#d63638'
                                    ];
                                    $color_estado = $estado_colores[$incidencia_detalle->estado] ?? '#646970';
                                    ?>
                                    <span class="flavor-badge" style="background: <?php echo $color_estado; ?>; color: white; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $incidencia_detalle->estado))); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong><?php echo esc_html__('Prioridad:', 'flavor-chat-ia'); ?></strong><br>
                                    <?php
                                    $prioridad_colores = [
                                        'baja' => '#00a32a',
                                        'media' => '#f0b849',
                                        'alta' => '#ff8c00',
                                        'urgente' => '#d63638'
                                    ];
                                    $color_prioridad = $prioridad_colores[$incidencia_detalle->prioridad] ?? '#646970';
                                    ?>
                                    <span class="flavor-badge" style="background: <?php echo $color_prioridad; ?>; color: white; padding: 6px 12px; border-radius: 4px; font-size: 13px; font-weight: 600;">
                                        <?php echo esc_html(ucfirst($incidencia_detalle->prioridad)); ?>
                                    </span>
                                </div>
                                <div>
                                    <strong><?php echo esc_html__('Fecha Reporte:', 'flavor-chat-ia'); ?></strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($incidencia_detalle->fecha_reporte)); ?>
                                </div>
                                <div>
                                    <strong><?php echo esc_html__('Ubicación:', 'flavor-chat-ia'); ?></strong><br>
                                    <?php echo $incidencia_detalle->direccion ? esc_html($incidencia_detalle->direccion) : '<em>No especificada</em>'; ?>
                                </div>
                                <div>
                                    <strong><?php echo esc_html__('Votos Ciudadanos:', 'flavor-chat-ia'); ?></strong><br>
                                    <span class="dashicons dashicons-thumbs-up" style="color: #2271b1;"></span>
                                    <?php echo $incidencia_detalle->votos_ciudadanos; ?>
                                </div>
                            </div>

                            <hr>

                            <div style="margin-top: 15px;">
                                <strong><?php echo esc_html__('Descripción:', 'flavor-chat-ia'); ?></strong>
                                <p><?php echo nl2br(esc_html($incidencia_detalle->descripcion)); ?></p>
                            </div>

                            <?php if ($incidencia_detalle->notas_internas): ?>
                                <hr>
                                <div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 15px; margin-top: 15px;">
                                    <strong><?php echo esc_html__('Notas Internas:', 'flavor-chat-ia'); ?></strong>
                                    <p style="margin: 5px 0 0 0;"><?php echo nl2br(esc_html($incidencia_detalle->notas_internas)); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Historial de seguimiento -->
                    <div class="postbox" style="margin-top: 20px;">
                        <div class="postbox-header">
                            <h2><?php echo esc_html__('Historial de Seguimiento', 'flavor-chat-ia'); ?></h2>
                        </div>
                        <div class="inside">
                            <div class="flavor-timeline">
                                <?php if (!empty($seguimiento)): ?>
                                    <?php foreach ($seguimiento as $item): ?>
                                        <?php
                                        $autor = get_user_by('id', $item->usuario_id);
                                        $tipo_iconos = [
                                            'comentario' => 'dashicons-admin-comments',
                                            'cambio_estado' => 'dashicons-update',
                                            'asignacion' => 'dashicons-admin-users',
                                            'resolucion' => 'dashicons-yes-alt'
                                        ];
                                        $icono = $tipo_iconos[$item->tipo] ?? 'dashicons-marker';
                                        ?>
                                        <div class="flavor-timeline-item" style="display: flex; gap: 15px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #dcdcde;">
                                            <div>
                                                <span class="dashicons <?php echo $icono; ?>" style="font-size: 24px; color: #2271b1;"></span>
                                            </div>
                                            <div style="flex: 1;">
                                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
                                                    <strong><?php echo $autor ? esc_html($autor->display_name) : 'Sistema'; ?></strong>
                                                    <small style="color: #646970;">
                                                        <?php echo date('d/m/Y H:i', strtotime($item->fecha_creacion)); ?>
                                                    </small>
                                                </div>
                                                <div style="margin-bottom: 5px;">
                                                    <span class="flavor-badge" style="background: #f0f0f1; color: #2c3338; padding: 3px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">
                                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $item->tipo))); ?>
                                                    </span>
                                                    <?php if (!$item->es_publico): ?>
                                                        <span class="flavor-badge" style="background: #ff8c00; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; text-transform: uppercase;">
                                                            <?php echo esc_html__('Privado', 'flavor-chat-ia'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <p style="margin: 8px 0 0 0;"><?php echo nl2br(esc_html($item->contenido)); ?></p>
                                                <?php if ($item->estado_anterior && $item->estado_nuevo): ?>
                                                    <small style="color: #646970;">
                                                        <strong><?php echo esc_html(ucfirst($item->estado_anterior)); ?></strong>
                                                        <span class="dashicons dashicons-arrow-right-alt" style="font-size: 14px;"></span>
                                                        <strong><?php echo esc_html(ucfirst($item->estado_nuevo)); ?></strong>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p style="color: #646970; text-align: center; padding: 20px 0;"><?php echo esc_html__('No hay seguimiento registrado', 'flavor-chat-ia'); ?></p>
                                <?php endif; ?>
                            </div>

                            <!-- Formulario para agregar comentario -->
                            <hr style="margin: 20px 0;">
                            <form method="post" action="" id="form-agregar-comentario">
                                <?php wp_nonce_field('flavor_incidencias_action'); ?>
                                <input type="hidden" name="action" value="<?php echo esc_attr__('agregar_comentario', 'flavor-chat-ia'); ?>">
                                <input type="hidden" name="incidencia_id" value="<?php echo $incidencia_detalle->id; ?>">

                                <p>
                                    <label><strong><?php echo esc_html__('Agregar Comentario:', 'flavor-chat-ia'); ?></strong></label>
                                    <!-- Panel de sugerencias IA se inyecta aquí vía JS -->
                                    <textarea name="comentario" id="incidencia-respuesta" rows="4" class="large-text ticket-reply-textarea" required
                                        data-incidencia-id="<?php echo esc_attr($incidencia_detalle->id); ?>"
                                        data-titulo="<?php echo esc_attr($incidencia_detalle->titulo); ?>"
                                        data-categoria="<?php echo esc_attr($incidencia_detalle->categoria); ?>"
                                        data-prioridad="<?php echo esc_attr($incidencia_detalle->prioridad); ?>"></textarea>
                                </p>

                                <p>
                                    <label>
                                        <input type="checkbox" name="es_publico" value="1" checked>
                                        <?php echo esc_html__('Visible para el ciudadano', 'flavor-chat-ia'); ?>
                                    </label>
                                </p>

                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-plus"></span> <?php echo esc_html__('Agregar Comentario', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>

                <!-- Columna lateral - Acciones -->
                <div>

                    <!-- Cambiar estado -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2><?php echo esc_html__('Cambiar Estado', 'flavor-chat-ia'); ?></h2>
                        </div>
                        <div class="inside">
                            <form method="post" action="">
                                <?php wp_nonce_field('flavor_incidencias_action'); ?>
                                <input type="hidden" name="action" value="<?php echo esc_attr__('cambiar_estado', 'flavor-chat-ia'); ?>">
                                <input type="hidden" name="incidencia_id" value="<?php echo $incidencia_detalle->id; ?>">

                                <p>
                                    <label><strong><?php echo esc_html__('Nuevo Estado:', 'flavor-chat-ia'); ?></strong></label>
                                    <select name="nuevo_estado" class="regular-text" required>
                                        <option value=""><?php echo esc_html__('Seleccionar...', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('pendiente', 'flavor-chat-ia'); ?>" <?php selected($incidencia_detalle->estado, 'pendiente'); ?>><?php echo esc_html__('Pendiente', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('en_proceso', 'flavor-chat-ia'); ?>" <?php selected($incidencia_detalle->estado, 'en_proceso'); ?>><?php echo esc_html__('En Proceso', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('resuelta', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Resuelta', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('cerrada', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Cerrada', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('rechazada', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Rechazada', 'flavor-chat-ia'); ?></option>
                                    </select>
                                </p>

                                <p>
                                    <label><strong><?php echo esc_html__('Comentario:', 'flavor-chat-ia'); ?></strong></label>
                                    <textarea name="comentario" rows="3" class="regular-text" required></textarea>
                                </p>

                                <button type="submit" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-update"></span> <?php echo esc_html__('Actualizar Estado', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Asignar personal -->
                    <div class="postbox" style="margin-top: 20px;">
                        <div class="postbox-header">
                            <h2><?php echo esc_html__('Asignar Personal', 'flavor-chat-ia'); ?></h2>
                        </div>
                        <div class="inside">
                            <form method="post" action="">
                                <?php wp_nonce_field('flavor_incidencias_action'); ?>
                                <input type="hidden" name="action" value="<?php echo esc_attr__('asignar_personal', 'flavor-chat-ia'); ?>">
                                <input type="hidden" name="incidencia_id" value="<?php echo $incidencia_detalle->id; ?>">

                                <p>
                                    <label><strong><?php echo esc_html__('Asignar a:', 'flavor-chat-ia'); ?></strong></label>
                                    <select name="asignado_a" class="regular-text" required>
                                        <option value=""><?php echo esc_html__('Seleccionar...', 'flavor-chat-ia'); ?></option>
                                        <?php foreach ($usuarios_staff as $usuario): ?>
                                            <option value="<?php echo $usuario->ID; ?>" <?php selected($incidencia_detalle->asignado_a, $usuario->ID); ?>>
                                                <?php echo esc_html($usuario->display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>

                                <p>
                                    <label><strong><?php echo esc_html__('Departamento:', 'flavor-chat-ia'); ?></strong></label>
                                    <select name="departamento" class="regular-text" required>
                                        <option value=""><?php echo esc_html__('Seleccionar...', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('Mantenimiento', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Mantenimiento', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('Limpieza', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Limpieza', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('Obras Públicas', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Obras Públicas', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('Parques y Jardines', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Parques y Jardines', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('Servicios Urbanos', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Servicios Urbanos', 'flavor-chat-ia'); ?></option>
                                        <option value="<?php echo esc_attr__('Medio Ambiente', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Medio Ambiente', 'flavor-chat-ia'); ?></option>
                                    </select>
                                </p>

                                <button type="submit" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-admin-users"></span> <?php echo esc_html__('Asignar', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Información ciudadano -->
                    <?php if ($incidencia_detalle->usuario_id): ?>
                        <?php $ciudadano = get_user_by('id', $incidencia_detalle->usuario_id); ?>
                        <?php if ($ciudadano): ?>
                            <div class="postbox" style="margin-top: 20px;">
                                <div class="postbox-header">
                                    <h2><?php echo esc_html__('Información del Ciudadano', 'flavor-chat-ia'); ?></h2>
                                </div>
                                <div class="inside">
                                    <p>
                                        <strong><?php echo esc_html__('Nombre:', 'flavor-chat-ia'); ?></strong><br>
                                        <?php echo esc_html($ciudadano->display_name); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo esc_html__('Email:', 'flavor-chat-ia'); ?></strong><br>
                                        <a href="mailto:<?php echo esc_attr($ciudadano->user_email); ?>">
                                            <?php echo esc_html($ciudadano->user_email); ?>
                                        </a>
                                    </p>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="postbox" style="margin-top: 20px;">
                            <div class="postbox-header">
                                <h2><?php echo esc_html__('Información del Ciudadano', 'flavor-chat-ia'); ?></h2>
                            </div>
                            <div class="inside">
                                <p style="color: #646970;"><em><?php echo esc_html__('Reporte anónimo', 'flavor-chat-ia'); ?></em></p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        <?php else: ?>
            <div class="notice notice-error">
                <p><?php echo esc_html__('La incidencia solicitada no existe.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<style>
.flavor-badge {
    display: inline-block;
    white-space: nowrap;
}

.flavor-timeline-item {
    animation: fadeInLeft 0.3s ease-out;
}

@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
</style>
