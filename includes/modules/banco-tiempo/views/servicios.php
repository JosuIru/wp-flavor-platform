<?php
/**
 * Vista Servicios - Banco de Tiempo
 *
 * Gestión completa de servicios ofrecidos en el banco de tiempo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

// Procesar acciones
$mensaje_exito = '';
$mensaje_error = '';

if (isset($_POST['accion']) && check_admin_referer('banco_tiempo_servicios')) {
    $accion = sanitize_text_field($_POST['accion']);

    switch ($accion) {
        case 'crear_servicio':
            $datos_servicio = [
                'usuario_id' => absint($_POST['usuario_id']),
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descripcion' => sanitize_textarea_field($_POST['descripcion']),
                'categoria' => sanitize_text_field($_POST['categoria']),
                'horas_estimadas' => floatval($_POST['horas_estimadas']),
                'estado' => 'activo',
                'fecha_publicacion' => current_time('mysql')
            ];

            if ($wpdb->insert($tabla_servicios, $datos_servicio)) {
                $mensaje_exito = 'Servicio creado correctamente.';
            } else {
                $mensaje_error = 'Error al crear el servicio.';
            }
            break;

        case 'actualizar_estado':
            $servicio_id = absint($_POST['servicio_id']);
            $nuevo_estado = sanitize_text_field($_POST['estado']);

            if ($wpdb->update($tabla_servicios, ['estado' => $nuevo_estado], ['id' => $servicio_id])) {
                $mensaje_exito = 'Estado actualizado correctamente.';
            } else {
                $mensaje_error = 'Error al actualizar el estado.';
            }
            break;

        case 'eliminar_servicio':
            $servicio_id = absint($_POST['servicio_id']);

            if ($wpdb->delete($tabla_servicios, ['id' => $servicio_id])) {
                $mensaje_exito = 'Servicio eliminado correctamente.';
            } else {
                $mensaje_error = 'Error al eliminar el servicio.';
            }
            break;
    }
}

// Obtener filtros
$filtro_categoria = isset($_GET['categoria']) ? sanitize_text_field($_GET['categoria']) : '';
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$paginacion_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$items_por_pagina = 20;
$offset = ($paginacion_actual - 1) * $items_por_pagina;

// Construir query
$where = ['1=1'];
$preparar = [];

if ($filtro_categoria) {
    $where[] = "categoria = %s";
    $preparar[] = $filtro_categoria;
}

if ($filtro_estado) {
    $where[] = "estado = %s";
    $preparar[] = $filtro_estado;
}

if ($filtro_busqueda) {
    $where[] = "(titulo LIKE %s OR descripcion LIKE %s)";
    $like_busqueda = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $preparar[] = $like_busqueda;
    $preparar[] = $like_busqueda;
}

$where_sql = implode(' AND ', $where);

// Obtener total de servicios
if (!empty($preparar)) {
    $total_servicios = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_servicios WHERE $where_sql",
        ...$preparar
    ));
} else {
    $total_servicios = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios WHERE $where_sql");
}

// Obtener servicios con paginación
$preparar_paginado = $preparar;
$preparar_paginado[] = $items_por_pagina;
$preparar_paginado[] = $offset;

if (count($preparar_paginado) > 2) {
    $servicios = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_servicios WHERE $where_sql ORDER BY fecha_publicacion DESC LIMIT %d OFFSET %d",
        ...$preparar_paginado
    ));
} else {
    $servicios = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_servicios WHERE $where_sql ORDER BY fecha_publicacion DESC LIMIT %d OFFSET %d",
        $items_por_pagina,
        $offset
    ));
}

$total_paginas = ceil($total_servicios / $items_por_pagina);

// Categorías disponibles
$categorias = [
    'cuidados' => 'Cuidados',
    'educacion' => 'Educación',
    'bricolaje' => 'Bricolaje',
    'tecnologia' => 'Tecnología',
    'transporte' => 'Transporte',
    'otros' => 'Otros'
];

?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-tools"></span>
        <?php echo esc_html__('Gestión de Servicios', 'flavor-chat-ia'); ?>
    </h1>

    <a href="#" class="page-title-action" id="btn-nuevo-servicio"><?php echo esc_html__('Añadir Nuevo', 'flavor-chat-ia'); ?></a>

    <hr class="wp-header-end">

    <?php if ($mensaje_exito): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_exito); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html($mensaje_error); ?></p>
        </div>
    <?php endif; ?>

    <!-- Filtros -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" style="display: inline-flex; gap: 8px; align-items: center;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">

                <select name="categoria" id="filter-categoria">
                    <option value=""><?php echo esc_html__('Todas las categorías', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($categorias as $key => $label): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($filtro_categoria, $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="estado" id="filter-estado">
                    <option value=""><?php echo esc_html__('Todos los estados', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('activo', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'activo'); ?>><?php echo esc_html__('Activo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('inactivo', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'inactivo'); ?>><?php echo esc_html__('Inactivo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('completado', 'flavor-chat-ia'); ?>" <?php selected($filtro_estado, 'completado'); ?>><?php echo esc_html__('Completado', 'flavor-chat-ia'); ?></option>
                </select>

                <button type="submit" class="button"><?php echo esc_html__('Filtrar', 'flavor-chat-ia'); ?></button>

                <?php if ($filtro_categoria || $filtro_estado || $filtro_busqueda): ?>
                    <a href="?page=<?php echo esc_attr($_GET['page']); ?>" class="button"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
                <?php endif; ?>
            </form>
        </div>

        <div class="alignright actions">
            <form method="get" style="display: inline-flex; gap: 8px;">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                <input type="search" name="s" value="<?php echo esc_attr($filtro_busqueda); ?>"
                       placeholder="<?php echo esc_attr__('Buscar servicios...', 'flavor-chat-ia'); ?>">
                <button type="submit" class="button"><?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?></button>
            </form>
        </div>
    </div>

    <!-- Tabla de Servicios -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 50px;"><?php echo esc_html__('ID', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Título', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Usuario', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Categoría', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Horas Est.', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                <th><?php echo esc_html__('Fecha Publicación', 'flavor-chat-ia'); ?></th>
                <th style="width: 150px;"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($servicios)): ?>
                <?php foreach ($servicios as $servicio):
                    $usuario = get_userdata($servicio->usuario_id);
                ?>
                <tr>
                    <td><strong><?php echo $servicio->id; ?></strong></td>
                    <td>
                        <strong>
                            <a href="#" class="ver-servicio-detalle" data-id="<?php echo $servicio->id; ?>">
                                <?php echo esc_html($servicio->titulo); ?>
                            </a>
                        </strong>
                    </td>
                    <td>
                        <?php if ($usuario): ?>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $servicio->usuario_id); ?>">
                                <?php echo esc_html($usuario->display_name); ?>
                            </a>
                        <?php else: ?>
                            Usuario desconocido
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="categoria-badge" style="padding: 4px 8px; background: #f0f0f1; border-radius: 3px;">
                            <?php echo esc_html($categorias[$servicio->categoria] ?? $servicio->categoria); ?>
                        </span>
                    </td>
                    <td><?php echo number_format($servicio->horas_estimadas, 1); ?> h</td>
                    <td>
                        <?php
                        $estado_class = $servicio->estado === 'activo' ? 'success' : 'default';
                        ?>
                        <span class="estado-badge badge-<?php echo $estado_class; ?>"
                              style="padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600;">
                            <?php echo ucfirst($servicio->estado); ?>
                        </span>
                    </td>
                    <td><?php echo date_i18n('d/m/Y H:i', strtotime($servicio->fecha_publicacion)); ?></td>
                    <td>
                        <a href="#" class="button button-small editar-servicio"
                           data-id="<?php echo $servicio->id; ?>"><?php echo esc_html__('Editar', 'flavor-chat-ia'); ?></a>
                        <a href="#" class="button button-small cambiar-estado"
                           data-id="<?php echo $servicio->id; ?>"
                           data-estado="<?php echo $servicio->estado; ?>">
                            <?php echo $servicio->estado === 'activo' ? 'Desactivar' : 'Activar'; ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #646970;">
                        <span class="dashicons dashicons-info" style="font-size: 48px;"></span>
                        <p><?php echo esc_html__('No se encontraron servicios con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php echo number_format($total_servicios); ?> servicios
                </span>
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_paginas,
                    'current' => $paginacion_actual
                ]);
                ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Modal: Nuevo/Editar Servicio -->
<div id="modal-servicio" style="display:none;">
    <div class="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div class="modal-content" style="position: relative; max-width: 600px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 4px;">
            <h2 id="modal-titulo"><?php echo esc_html__('Nuevo Servicio', 'flavor-chat-ia'); ?></h2>

            <form method="post" id="form-servicio">
                <?php wp_nonce_field('banco_tiempo_servicios'); ?>
                <input type="hidden" name="accion" value="<?php echo esc_attr__('crear_servicio', 'flavor-chat-ia'); ?>">
                <input type="hidden" name="servicio_id" id="servicio_id">

                <table class="form-table">
                    <tr>
                        <th><label for="usuario_id"><?php echo esc_html__('Usuario *', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_users([
                                'name' => 'usuario_id',
                                'id' => 'usuario_id',
                                'show_option_none' => 'Seleccionar usuario',
                                'option_none_value' => ''
                            ]);
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="titulo"><?php echo esc_html__('Título *', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="text" name="titulo" id="titulo" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="descripcion"><?php echo esc_html__('Descripción *', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <textarea name="descripcion" id="descripcion" rows="4" class="large-text" required></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="categoria"><?php echo esc_html__('Categoría *', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select name="categoria" id="categoria" required>
                                <option value=""><?php echo esc_html__('Seleccionar...', 'flavor-chat-ia'); ?></option>
                                <?php foreach ($categorias as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>">
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="horas_estimadas"><?php echo esc_html__('Horas Estimadas *', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="number" name="horas_estimadas" id="horas_estimadas"
                                   step="0.5" min="0.5" value="1" required>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php echo esc_html__('Guardar Servicio', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="btn-cerrar-modal"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Detalles del Servicio -->
<div id="modal-detalle" style="display:none;">
    <div class="modal-overlay" style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
        <div class="modal-content" style="position: relative; max-width: 700px; margin: 50px auto; background: #fff; padding: 20px; border-radius: 4px;">
            <h2><?php echo esc_html__('Detalles del Servicio', 'flavor-chat-ia'); ?></h2>
            <div id="contenido-detalle"></div>
            <p>
                <button type="button" class="button" id="btn-cerrar-detalle"><?php echo esc_html__('Cerrar', 'flavor-chat-ia'); ?></button>
            </p>
        </div>
    </div>
</div>

<style>
.badge-success {
    background-color: #00a32a;
    color: #fff;
}
.badge-default {
    background-color: #646970;
    color: #fff;
}
</style>

<script>
jQuery(document).ready(function($) {

    // Abrir modal nuevo servicio
    $('#btn-nuevo-servicio').click(function(e) {
        e.preventDefault();
        $('#modal-titulo').text('Nuevo Servicio');
        $('#form-servicio')[0].reset();
        $('#servicio_id').val('');
        $('input[name="accion"]').val('crear_servicio');
        $('#modal-servicio').fadeIn();
    });

    // Cerrar modal
    $('#btn-cerrar-modal, .modal-overlay').click(function(e) {
        if (e.target === this) {
            $('#modal-servicio').fadeOut();
        }
    });

    $('#btn-cerrar-detalle').click(function() {
        $('#modal-detalle').fadeOut();
    });

    // Ver detalle
    $('.ver-servicio-detalle').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        // Aquí se cargaría vía AJAX el detalle
        $('#modal-detalle').fadeIn();
    });

    // Cambiar estado
    $('.cambiar-estado').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        var estadoActual = $(this).data('estado');
        var nuevoEstado = estadoActual === 'activo' ? 'inactivo' : 'activo';

        if (confirm('¿Confirmar cambio de estado?')) {
            $('<form method="post">' +
                '<input type="hidden" name="accion" value="<?php echo esc_attr__('actualizar_estado', 'flavor-chat-ia'); ?>">' +
                '<input type="hidden" name="servicio_id" value="<?php echo esc_attr__('\' + id + \'', 'flavor-chat-ia'); ?>">' +
                '<input type="hidden" name="estado" value="<?php echo esc_attr__('\' + nuevoEstado + \'', 'flavor-chat-ia'); ?>">' +
                '<?php echo wp_nonce_field("banco_tiempo_servicios", "_wpnonce", true, false); ?>' +
                '</form>').appendTo('body').submit();
        }
    });
});
</script>
