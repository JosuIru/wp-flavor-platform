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
        Gestión de Incidencias
    </h1>

    <?php if (!$incidencia_id_detalle): ?>
        <a href="#" class="page-title-action" onclick="document.getElementById('filtros').style.display='block'; return false;">
            <span class="dashicons dashicons-filter"></span> Filtros
        </a>
    <?php else: ?>
        <a href="<?php echo admin_url('admin.php?page=flavor-incidencias-tickets'); ?>" class="page-title-action">
            <span class="dashicons dashicons-arrow-left-alt"></span> Volver al listado
        </a>
    <?php endif; ?>

    <hr class="wp-header-end">

    <?php if (!$incidencia_id_detalle): ?>

        <!-- Filtros -->
        <div id="filtros" class="postbox" style="margin: 20px 0; display: none;">
            <div class="inside">
                <form method="get" action="">
                    <input type="hidden" name="page" value="flavor-incidencias-tickets">

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">

                        <div>
                            <label><strong>Estado:</strong></label>
                            <select name="estado" class="regular-text">
                                <option value="">Todos</option>
                                <option value="pendiente" <?php selected($estado_filtro, 'pendiente'); ?>>Pendiente</option>
                                <option value="en_proceso" <?php selected($estado_filtro, 'en_proceso'); ?>>En Proceso</option>
                                <option value="resuelta" <?php selected($estado_filtro, 'resuelta'); ?>>Resuelta</option>
                                <option value="cerrada" <?php selected($estado_filtro, 'cerrada'); ?>>Cerrada</option>
                                <option value="rechazada" <?php selected($estado_filtro, 'rechazada'); ?>>Rechazada</option>
                            </select>
                        </div>

                        <div>
                            <label><strong>Categoría:</strong></label>
                            <select name="categoria" class="regular-text">
                                <option value="">Todas</option>
                                <option value="alumbrado" <?php selected($categoria_filtro, 'alumbrado'); ?>>Alumbrado</option>
                                <option value="limpieza" <?php selected($categoria_filtro, 'limpieza'); ?>>Limpieza</option>
                                <option value="via_publica" <?php selected($categoria_filtro, 'via_publica'); ?>>Vía Pública</option>
                                <option value="mobiliario" <?php selected($categoria_filtro, 'mobiliario'); ?>>Mobiliario</option>
                                <option value="parques" <?php selected($categoria_filtro, 'parques'); ?>>Parques</option>
                                <option value="ruido" <?php selected($categoria_filtro, 'ruido'); ?>>Ruido</option>
                                <option value="agua" <?php selected($categoria_filtro, 'agua'); ?>>Agua</option>
                                <option value="señalizacion" <?php selected($categoria_filtro, 'señalizacion'); ?>>Señalización</option>
                            </select>
                        </div>

                        <div>
                            <label><strong>Prioridad:</strong></label>
                            <select name="prioridad" class="regular-text">
                                <option value="">Todas</option>
                                <option value="baja" <?php selected($prioridad_filtro, 'baja'); ?>>Baja</option>
                                <option value="media" <?php selected($prioridad_filtro, 'media'); ?>>Media</option>
                                <option value="alta" <?php selected($prioridad_filtro, 'alta'); ?>>Alta</option>
                                <option value="urgente" <?php selected($prioridad_filtro, 'urgente'); ?>>Urgente</option>
                            </select>
                        </div>

                        <div>
                            <label><strong>Buscar:</strong></label>
                            <input type="text" name="s" value="<?php echo esc_attr($buscar); ?>" class="regular-text" placeholder="Número, título...">
                        </div>

                    </div>

                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-search"></span> Filtrar
                    </button>
                    <a href="<?php echo admin_url('admin.php?page=flavor-incidencias-tickets'); ?>" class="button">
                        Limpiar filtros
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
                            <th style="width: 100px;">Número</th>
                            <th>Título</th>
                            <th style="width: 120px;">Categoría</th>
                            <th style="width: 100px;">Estado</th>
                            <th style="width: 100px;">Prioridad</th>
                            <th style="width: 150px;">Fecha Reporte</th>
                            <th style="width: 80px;">Votos</th>
                            <th style="width: 100px;">Acciones</th>
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
                                        <a href="<?php echo admin_url('admin.php?page=flavor-incidencias-tickets&id=' . $incidencia->id); ?>" class="button button-small">
                                            Ver detalles
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px 0; color: #646970;">
                                    No se encontraron incidencias
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
                                    <strong>Categoría:</strong><br>
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $incidencia_detalle->categoria))); ?>
                                </div>
                                <div>
                                    <strong>Estado:</strong><br>
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
                                    <strong>Prioridad:</strong><br>
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
                                    <strong>Fecha Reporte:</strong><br>
                                    <?php echo date('d/m/Y H:i', strtotime($incidencia_detalle->fecha_reporte)); ?>
                                </div>
                                <div>
                                    <strong>Ubicación:</strong><br>
                                    <?php echo $incidencia_detalle->direccion ? esc_html($incidencia_detalle->direccion) : '<em>No especificada</em>'; ?>
                                </div>
                                <div>
                                    <strong>Votos Ciudadanos:</strong><br>
                                    <span class="dashicons dashicons-thumbs-up" style="color: #2271b1;"></span>
                                    <?php echo $incidencia_detalle->votos_ciudadanos; ?>
                                </div>
                            </div>

                            <hr>

                            <div style="margin-top: 15px;">
                                <strong>Descripción:</strong>
                                <p><?php echo nl2br(esc_html($incidencia_detalle->descripcion)); ?></p>
                            </div>

                            <?php if ($incidencia_detalle->notas_internas): ?>
                                <hr>
                                <div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 15px; margin-top: 15px;">
                                    <strong>Notas Internas:</strong>
                                    <p style="margin: 5px 0 0 0;"><?php echo nl2br(esc_html($incidencia_detalle->notas_internas)); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Historial de seguimiento -->
                    <div class="postbox" style="margin-top: 20px;">
                        <div class="postbox-header">
                            <h2>Historial de Seguimiento</h2>
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
                                                            Privado
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
                                    <p style="color: #646970; text-align: center; padding: 20px 0;">No hay seguimiento registrado</p>
                                <?php endif; ?>
                            </div>

                            <!-- Formulario para agregar comentario -->
                            <hr style="margin: 20px 0;">
                            <form method="post" action="">
                                <?php wp_nonce_field('flavor_incidencias_action'); ?>
                                <input type="hidden" name="action" value="agregar_comentario">
                                <input type="hidden" name="incidencia_id" value="<?php echo $incidencia_detalle->id; ?>">

                                <p>
                                    <label><strong>Agregar Comentario:</strong></label>
                                    <textarea name="comentario" rows="4" class="large-text" required></textarea>
                                </p>

                                <p>
                                    <label>
                                        <input type="checkbox" name="es_publico" value="1" checked>
                                        Visible para el ciudadano
                                    </label>
                                </p>

                                <button type="submit" class="button button-primary">
                                    <span class="dashicons dashicons-plus"></span> Agregar Comentario
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
                            <h2>Cambiar Estado</h2>
                        </div>
                        <div class="inside">
                            <form method="post" action="">
                                <?php wp_nonce_field('flavor_incidencias_action'); ?>
                                <input type="hidden" name="action" value="cambiar_estado">
                                <input type="hidden" name="incidencia_id" value="<?php echo $incidencia_detalle->id; ?>">

                                <p>
                                    <label><strong>Nuevo Estado:</strong></label>
                                    <select name="nuevo_estado" class="regular-text" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="pendiente" <?php selected($incidencia_detalle->estado, 'pendiente'); ?>>Pendiente</option>
                                        <option value="en_proceso" <?php selected($incidencia_detalle->estado, 'en_proceso'); ?>>En Proceso</option>
                                        <option value="resuelta">Resuelta</option>
                                        <option value="cerrada">Cerrada</option>
                                        <option value="rechazada">Rechazada</option>
                                    </select>
                                </p>

                                <p>
                                    <label><strong>Comentario:</strong></label>
                                    <textarea name="comentario" rows="3" class="regular-text" required></textarea>
                                </p>

                                <button type="submit" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-update"></span> Actualizar Estado
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Asignar personal -->
                    <div class="postbox" style="margin-top: 20px;">
                        <div class="postbox-header">
                            <h2>Asignar Personal</h2>
                        </div>
                        <div class="inside">
                            <form method="post" action="">
                                <?php wp_nonce_field('flavor_incidencias_action'); ?>
                                <input type="hidden" name="action" value="asignar_personal">
                                <input type="hidden" name="incidencia_id" value="<?php echo $incidencia_detalle->id; ?>">

                                <p>
                                    <label><strong>Asignar a:</strong></label>
                                    <select name="asignado_a" class="regular-text" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($usuarios_staff as $usuario): ?>
                                            <option value="<?php echo $usuario->ID; ?>" <?php selected($incidencia_detalle->asignado_a, $usuario->ID); ?>>
                                                <?php echo esc_html($usuario->display_name); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </p>

                                <p>
                                    <label><strong>Departamento:</strong></label>
                                    <select name="departamento" class="regular-text" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Mantenimiento">Mantenimiento</option>
                                        <option value="Limpieza">Limpieza</option>
                                        <option value="Obras Públicas">Obras Públicas</option>
                                        <option value="Parques y Jardines">Parques y Jardines</option>
                                        <option value="Servicios Urbanos">Servicios Urbanos</option>
                                        <option value="Medio Ambiente">Medio Ambiente</option>
                                    </select>
                                </p>

                                <button type="submit" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-admin-users"></span> Asignar
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
                                    <h2>Información del Ciudadano</h2>
                                </div>
                                <div class="inside">
                                    <p>
                                        <strong>Nombre:</strong><br>
                                        <?php echo esc_html($ciudadano->display_name); ?>
                                    </p>
                                    <p>
                                        <strong>Email:</strong><br>
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
                                <h2>Información del Ciudadano</h2>
                            </div>
                            <div class="inside">
                                <p style="color: #646970;"><em>Reporte anónimo</em></p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

            </div>

        <?php else: ?>
            <div class="notice notice-error">
                <p>La incidencia solicitada no existe.</p>
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
