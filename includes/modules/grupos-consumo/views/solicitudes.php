<?php
/**
 * Vista Admin: Gestion de Solicitudes de Union
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options') && !current_user_can('gc_gestionar_consumidores')) {
    wp_die(__('No tienes permisos para acceder a esta pagina.', 'flavor-platform'));
}

$membership_manager = Flavor_GC_Membership::get_instance();

// Obtener grupo actual (por defecto primer grupo)
$grupo_id = isset($_GET['grupo_id']) ? absint($_GET['grupo_id']) : 0;

if (!$grupo_id) {
    $grupos = get_posts([
        'post_type' => 'gc_grupo',
        'posts_per_page' => 1,
        'post_status' => 'publish',
    ]);
    if (!empty($grupos)) {
        $grupo_id = $grupos[0]->ID;
    }
}

// Filtro de estado
$filtro_estado = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : 'pendiente';

// Paginacion
$pagina_actual = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
$por_pagina = 20;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener solicitudes
$resultado = $grupo_id
    ? $membership_manager->obtener_solicitudes($grupo_id, $filtro_estado, $por_pagina, $offset)
    : ['solicitudes' => [], 'total' => 0, 'paginas' => 0];

$solicitudes = $resultado['solicitudes'];
$total_solicitudes = $resultado['total'];
$total_paginas = $resultado['paginas'];

// Contar por estado
$total_pendientes = $grupo_id ? $membership_manager->contar_solicitudes_pendientes($grupo_id) : 0;

// Obtener grupos para selector
$todos_los_grupos = get_posts([
    'post_type' => 'gc_grupo',
    'posts_per_page' => -1,
    'post_status' => 'publish',
]);

// Etiquetas de estado
$etiquetas_estado = [
    'pendiente' => __('Pendiente', 'flavor-platform'),
    'aprobada' => __('Aprobada', 'flavor-platform'),
    'rechazada' => __('Rechazada', 'flavor-platform'),
];

// Clases CSS por estado
$clases_estado = [
    'pendiente' => 'gc-estado-pendiente',
    'aprobada' => 'gc-estado-activo',
    'rechazada' => 'gc-estado-baja',
];
?>

<div class="wrap gc-admin-solicitudes">
    <h1 class="wp-heading-inline">
        <?php _e('Solicitudes de Union', 'flavor-platform'); ?>
        <?php if ($total_pendientes > 0): ?>
            <span class="gc-badge-pendientes"><?php echo esc_html($total_pendientes); ?></span>
        <?php endif; ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Selector de Grupo -->
    <?php if (count($todos_los_grupos) > 1): ?>
        <div class="gc-grupo-selector">
            <label for="gc-grupo-select"><?php _e('Grupo:', 'flavor-platform'); ?></label>
            <select id="gc-grupo-select" onchange="window.location.href='<?php echo admin_url('admin.php?page=gc-solicitudes&grupo_id='); ?>'+this.value">
                <?php foreach ($todos_los_grupos as $grupo): ?>
                    <option value="<?php echo $grupo->ID; ?>" <?php selected($grupo_id, $grupo->ID); ?>>
                        <?php echo esc_html($grupo->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <?php if (!$grupo_id): ?>
        <div class="notice notice-warning">
            <p><?php _e('No hay ningun grupo de consumo creado. Crea uno primero.', 'flavor-platform'); ?></p>
        </div>
    <?php else: ?>

    <!-- Resumen rapido -->
    <div class="gc-stats-mini">
        <a href="<?php echo esc_url(add_query_arg('estado', 'pendiente')); ?>" class="gc-stat-mini <?php echo $filtro_estado === 'pendiente' ? 'activo' : ''; ?>">
            <span class="gc-stat-numero"><?php echo esc_html($total_pendientes); ?></span>
            <span class="gc-stat-label"><?php _e('Pendientes', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('estado', 'aprobada')); ?>" class="gc-stat-mini <?php echo $filtro_estado === 'aprobada' ? 'activo' : ''; ?>">
            <span class="gc-stat-label"><?php _e('Aprobadas', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(add_query_arg('estado', 'rechazada')); ?>" class="gc-stat-mini <?php echo $filtro_estado === 'rechazada' ? 'activo' : ''; ?>">
            <span class="gc-stat-label"><?php _e('Rechazadas', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(remove_query_arg('estado')); ?>" class="gc-stat-mini <?php echo empty($filtro_estado) || $filtro_estado === '' ? 'activo' : ''; ?>">
            <span class="gc-stat-label"><?php _e('Todas', 'flavor-platform'); ?></span>
        </a>
    </div>

    <!-- Tabla de Solicitudes -->
    <table class="wp-list-table widefat fixed striped gc-tabla-solicitudes">
        <thead>
            <tr>
                <th scope="col" class="column-usuario"><?php _e('Solicitante', 'flavor-platform'); ?></th>
                <th scope="col" class="column-motivacion"><?php _e('Motivacion', 'flavor-platform'); ?></th>
                <th scope="col" class="column-preferencias"><?php _e('Preferencias', 'flavor-platform'); ?></th>
                <th scope="col" class="column-fecha"><?php _e('Fecha', 'flavor-platform'); ?></th>
                <th scope="col" class="column-estado"><?php _e('Estado', 'flavor-platform'); ?></th>
                <th scope="col" class="column-acciones"><?php _e('Acciones', 'flavor-platform'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($solicitudes)): ?>
                <tr>
                    <td colspan="6">
                        <?php
                        if ($filtro_estado === 'pendiente') {
                            _e('No hay solicitudes pendientes. Todas las solicitudes han sido procesadas.', 'flavor-platform');
                        } else {
                            _e('No se encontraron solicitudes.', 'flavor-platform');
                        }
                        ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($solicitudes as $solicitud):
                    $preferencias = [];
                    if ($solicitud->preferencias_alimentarias) {
                        $prefs_array = json_decode($solicitud->preferencias_alimentarias, true);
                        if (is_array($prefs_array)) {
                            foreach ($prefs_array as $pref) {
                                $preferencias[] = Flavor_GC_Membership::PREFERENCIAS_ALIMENTARIAS[$pref] ?? $pref;
                            }
                        }
                    }
                ?>
                    <tr data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                        <td class="column-usuario">
                            <div class="gc-usuario-info">
                                <?php echo get_avatar($solicitud->usuario_id, 40); ?>
                                <div class="gc-usuario-datos">
                                    <strong><?php echo esc_html($solicitud->display_name); ?></strong>
                                    <span class="gc-email">
                                        <a href="mailto:<?php echo esc_attr($solicitud->user_email); ?>">
                                            <?php echo esc_html($solicitud->user_email); ?>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td class="column-motivacion">
                            <div class="gc-motivacion-preview">
                                <?php echo esc_html(wp_trim_words($solicitud->motivacion, 15, '...')); ?>
                            </div>
                            <?php if ($solicitud->alergias): ?>
                                <span class="gc-tag gc-tag-alergias" title="<?php esc_attr_e('Tiene alergias', 'flavor-platform'); ?>">
                                    <span class="dashicons dashicons-warning"></span>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="column-preferencias">
                            <?php if (!empty($preferencias)): ?>
                                <div class="gc-preferencias-tags">
                                    <?php foreach (array_slice($preferencias, 0, 3) as $pref): ?>
                                        <span class="gc-tag"><?php echo esc_html($pref); ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($preferencias) > 3): ?>
                                        <span class="gc-tag gc-tag-mas">+<?php echo count($preferencias) - 3; ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="gc-sin-datos">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="column-fecha">
                            <span title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($solicitud->fecha_solicitud))); ?>">
                                <?php echo esc_html(human_time_diff(strtotime($solicitud->fecha_solicitud), current_time('timestamp'))); ?>
                            </span>
                        </td>
                        <td class="column-estado">
                            <span class="gc-estado-badge <?php echo esc_attr($clases_estado[$solicitud->estado] ?? ''); ?>">
                                <?php echo esc_html($etiquetas_estado[$solicitud->estado] ?? $solicitud->estado); ?>
                            </span>
                            <?php if ($solicitud->estado === 'rechazada' && $solicitud->resuelto_por_nombre): ?>
                                <small class="gc-resuelto-por">
                                    <?php printf(__('por %s', 'flavor-platform'), esc_html($solicitud->resuelto_por_nombre)); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="column-acciones">
                            <?php if ($solicitud->estado === 'pendiente'): ?>
                                <div class="gc-acciones-botones">
                                    <button type="button" class="button button-primary gc-btn-aprobar" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                                        <span class="dashicons dashicons-yes"></span>
                                        <?php _e('Aprobar', 'flavor-platform'); ?>
                                    </button>
                                    <button type="button" class="button gc-btn-rechazar" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                                        <span class="dashicons dashicons-no"></span>
                                        <?php _e('Rechazar', 'flavor-platform'); ?>
                                    </button>
                                </div>
                            <?php else: ?>
                                <button type="button" class="button gc-btn-ver-detalles" data-solicitud-id="<?php echo esc_attr($solicitud->id); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php _e('Ver', 'flavor-platform'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s solicitud', '%s solicitudes', $total_solicitudes, 'flavor-platform'), number_format_i18n($total_solicitudes)); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $paginate_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_paginas,
                        'current' => $pagina_actual,
                    ]);
                    echo $paginate_links;
                    ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

    <?php endif; // fin if grupo_id ?>
</div>

<!-- Modal: Ver Detalles Solicitud -->
<div id="modal-detalles-solicitud" class="gc-modal" style="display:none;">
    <div class="gc-modal-content gc-modal-lg">
        <div class="gc-modal-header">
            <h2><?php _e('Detalles de la Solicitud', 'flavor-platform'); ?></h2>
            <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', 'flavor-platform'); ?></button>
        </div>
        <div class="gc-modal-body">
            <div id="gc-detalles-contenido" class="gc-detalles-grid">
                <!-- Se carga via AJAX -->
            </div>
        </div>
        <div class="gc-modal-footer gc-modal-footer-acciones" id="gc-modal-acciones" style="display: none;">
            <button type="button" class="button button-primary gc-btn-aprobar-modal">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Aprobar Solicitud', 'flavor-platform'); ?>
            </button>
            <button type="button" class="button gc-btn-rechazar-modal">
                <span class="dashicons dashicons-no"></span>
                <?php _e('Rechazar Solicitud', 'flavor-platform'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Modal: Rechazar Solicitud -->
<div id="modal-rechazar" class="gc-modal" style="display:none;">
    <div class="gc-modal-content">
        <div class="gc-modal-header">
            <h2><?php _e('Rechazar Solicitud', 'flavor-platform'); ?></h2>
            <button type="button" class="gc-modal-close"><?php echo esc_html__('&times;', 'flavor-platform'); ?></button>
        </div>
        <div class="gc-modal-body">
            <form id="form-rechazar-solicitud">
                <input type="hidden" id="rechazar-solicitud-id" name="solicitud_id" value="">

                <div class="gc-form-field">
                    <label for="motivo-rechazo"><?php _e('Motivo del rechazo', 'flavor-platform'); ?></label>
                    <p class="description"><?php _e('Este mensaje se enviara al solicitante por email.', 'flavor-platform'); ?></p>
                    <textarea
                        id="motivo-rechazo"
                        name="motivo"
                        rows="4"
                        placeholder="<?php esc_attr_e('Explica brevemente el motivo del rechazo (opcional pero recomendado)...', 'flavor-platform'); ?>"
                    ></textarea>
                </div>
            </form>
        </div>
        <div class="gc-modal-footer">
            <button type="button" class="button gc-modal-cancel"><?php _e('Cancelar', 'flavor-platform'); ?></button>
            <button type="button" class="button button-primary gc-confirmar-rechazo">
                <?php _e('Confirmar Rechazo', 'flavor-platform'); ?>
            </button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var gcNonce = '<?php echo wp_create_nonce('gc_admin_nonce'); ?>';
    var solicitudActual = null;
    var $notice = $('<div class="gc-inline-notice"></div>').insertBefore('.wrap h1').hide();

    function gcAviso(mensaje, tipo) {
        $notice.removeClass('success error').addClass(tipo || 'error').text(mensaje).show();
    }

    function gcConfirmar(mensaje, onConfirm) {
        $('.gc-inline-confirm').remove();
        var $confirm = $('<div class="gc-inline-confirm"><p></p><div class="gc-inline-confirm-actions"><button type="button" class="button button-primary gc-inline-confirm-ok"><?php echo esc_js(__('Confirmar', 'flavor-platform')); ?></button><button type="button" class="button gc-inline-confirm-cancel"><?php echo esc_js(__('Cancelar', 'flavor-platform')); ?></button></div></div>').insertBefore('.wrap h1').hide();
        $confirm.find('p').text(mensaje);
        $confirm.fadeIn(150);

        $confirm.on('click', '.gc-inline-confirm-ok', function() {
            $confirm.remove();
            if (typeof onConfirm === 'function') {
                onConfirm();
            }
        });

        $confirm.on('click', '.gc-inline-confirm-cancel', function() {
            $confirm.remove();
        });
    }

    // Aprobar solicitud
    function aprobarSolicitud(solicitudId) {
        gcConfirmar('<?php echo esc_js(__('Aprobar esta solicitud? El usuario sera notificado por email.', 'flavor-platform')); ?>', function() {
            $.post(ajaxurl, {
                action: 'gc_aprobar_solicitud',
                solicitud_id: solicitudId,
                nonce: gcNonce
            }, function(response) {
                if (response.success) {
                    gcAviso(response.data.mensaje || '<?php _e('Solicitud aprobada correctamente.', 'flavor-platform'); ?>', 'success');
                    location.reload();
                } else {
                    gcAviso(response.data.error || '<?php _e('Error al aprobar la solicitud.', 'flavor-platform'); ?>', 'error');
                }
            });
        });
    }

    // Click en boton aprobar
    $(document).on('click', '.gc-btn-aprobar', function(e) {
        e.preventDefault();
        var solicitudId = $(this).data('solicitud-id');
        aprobarSolicitud(solicitudId);
    });

    // Click en boton rechazar - abrir modal
    $(document).on('click', '.gc-btn-rechazar', function(e) {
        e.preventDefault();
        var solicitudId = $(this).data('solicitud-id');
        solicitudActual = solicitudId;
        $('#rechazar-solicitud-id').val(solicitudId);
        $('#motivo-rechazo').val('');
        $('#modal-rechazar').fadeIn(200);
    });

    // Confirmar rechazo
    $('.gc-confirmar-rechazo').on('click', function() {
        var solicitudId = $('#rechazar-solicitud-id').val();
        var motivo = $('#motivo-rechazo').val();

        $.post(ajaxurl, {
            action: 'gc_rechazar_solicitud',
            solicitud_id: solicitudId,
            motivo: motivo,
            nonce: gcNonce
        }, function(response) {
            if (response.success) {
                gcAviso(response.data.mensaje || '<?php _e('Solicitud rechazada correctamente.', 'flavor-platform'); ?>', 'success');
                location.reload();
            } else {
                gcAviso(response.data.error || '<?php _e('Error al rechazar la solicitud.', 'flavor-platform'); ?>', 'error');
            }
        });
    });

    // Ver detalles
    $(document).on('click', '.gc-btn-ver-detalles, .gc-motivacion-preview', function(e) {
        e.preventDefault();
        var $row = $(this).closest('tr');
        var solicitudId = $row.data('solicitud-id') || $(this).data('solicitud-id');
        solicitudActual = solicitudId;

        $('#gc-detalles-contenido').html('<p class="gc-cargando"><span class="spinner is-active"></span> <?php _e('Cargando...', 'flavor-platform'); ?></p>');
        $('#gc-modal-acciones').hide();
        $('#modal-detalles-solicitud').fadeIn(200);

        $.post(ajaxurl, {
            action: 'gc_obtener_solicitud',
            solicitud_id: solicitudId,
            nonce: gcNonce
        }, function(response) {
            if (response.success) {
                var s = response.data.solicitud;
                var html = '<div class="gc-detalle-seccion">' +
                    '<h4><?php _e('Informacion del Solicitante', 'flavor-platform'); ?></h4>' +
                    '<p><strong><?php _e('Nombre:', 'flavor-platform'); ?></strong> ' + s.usuario_nombre + '</p>' +
                    '<p><strong><?php _e('Email:', 'flavor-platform'); ?></strong> <a href="mailto:' + s.usuario_email + '">' + s.usuario_email + '</a></p>' +
                    '<p><strong><?php _e('Fecha solicitud:', 'flavor-platform'); ?></strong> ' + s.fecha_solicitud + '</p>' +
                '</div>';

                html += '<div class="gc-detalle-seccion">' +
                    '<h4><?php _e('Motivacion', 'flavor-platform'); ?></h4>' +
                    '<p>' + (s.motivacion || '<em><?php _e('No especificada', 'flavor-platform'); ?></em>') + '</p>' +
                '</div>';

                if (s.preferencias && s.preferencias.length > 0) {
                    html += '<div class="gc-detalle-seccion">' +
                        '<h4><?php _e('Preferencias Alimentarias', 'flavor-platform'); ?></h4>' +
                        '<div class="gc-preferencias-tags">';
                    s.preferencias.forEach(function(pref) {
                        html += '<span class="gc-tag">' + pref + '</span>';
                    });
                    html += '</div></div>';
                }

                if (s.alergias) {
                    html += '<div class="gc-detalle-seccion gc-seccion-alergias">' +
                        '<h4><span class="dashicons dashicons-warning"></span> <?php _e('Alergias', 'flavor-platform'); ?></h4>' +
                        '<p>' + s.alergias + '</p>' +
                    '</div>';
                }

                if (s.como_nos_conocio) {
                    html += '<div class="gc-detalle-seccion">' +
                        '<h4><?php _e('Como nos conocio', 'flavor-platform'); ?></h4>' +
                        '<p>' + s.como_nos_conocio + '</p>' +
                    '</div>';
                }

                if (s.estado !== 'pendiente') {
                    html += '<div class="gc-detalle-seccion gc-seccion-resolucion">' +
                        '<h4><?php _e('Resolucion', 'flavor-platform'); ?></h4>' +
                        '<p><strong><?php _e('Estado:', 'flavor-platform'); ?></strong> ' + s.estado + '</p>';
                    if (s.resuelto_por_nombre) {
                        html += '<p><strong><?php _e('Resuelta por:', 'flavor-platform'); ?></strong> ' + s.resuelto_por_nombre + '</p>';
                    }
                    if (s.fecha_resolucion) {
                        html += '<p><strong><?php _e('Fecha:', 'flavor-platform'); ?></strong> ' + s.fecha_resolucion + '</p>';
                    }
                    if (s.motivo_rechazo) {
                        html += '<p><strong><?php _e('Motivo rechazo:', 'flavor-platform'); ?></strong> ' + s.motivo_rechazo + '</p>';
                    }
                    html += '</div>';
                }

                $('#gc-detalles-contenido').html(html);

                // Mostrar acciones si pendiente
                if (s.estado === 'pendiente') {
                    $('#gc-modal-acciones').show();
                }
            } else {
                $('#gc-detalles-contenido').html('<p class="gc-error">' + (response.data.error || '<?php _e('Error al cargar los detalles.', 'flavor-platform'); ?>') + '</p>');
            }
        });
    });

    // Acciones desde modal de detalles
    $('.gc-btn-aprobar-modal').on('click', function() {
        $('#modal-detalles-solicitud').fadeOut(200);
        aprobarSolicitud(solicitudActual);
    });

    $('.gc-btn-rechazar-modal').on('click', function() {
        $('#modal-detalles-solicitud').fadeOut(200);
        $('#rechazar-solicitud-id').val(solicitudActual);
        $('#motivo-rechazo').val('');
        $('#modal-rechazar').fadeIn(200);
    });

    // Cerrar modales
    $('.gc-modal-close, .gc-modal-cancel').on('click', function() {
        $(this).closest('.gc-modal').fadeOut(200);
    });

    $('.gc-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).fadeOut(200);
        }
    });
});
</script>

<style>
.gc-inline-notice {
    margin: 12px 0 16px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 14px;
}
.gc-inline-notice.error { background: #fee2e2; color: #991b1b; }
.gc-inline-notice.success { background: #dcfce7; color: #166534; }
</style>

<style>
/* Badge de solicitudes pendientes */
.gc-badge-pendientes {
    display: inline-block;
    padding: 2px 10px;
    background: #d63638;
    color: #fff;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    margin-left: 10px;
    vertical-align: middle;
}

/* Estadisticas mini */
.gc-stats-mini {
    display: flex;
    gap: 15px;
    margin: 20px 0;
}
.gc-stat-mini {
    background: #fff;
    padding: 12px 20px;
    border-radius: 6px;
    text-decoration: none;
    color: #1d2327;
    border: 2px solid transparent;
    transition: all 0.2s;
}
.gc-stat-mini:hover {
    border-color: #2271b1;
}
.gc-stat-mini.activo {
    border-color: #2271b1;
    background: #f0f7fc;
}
.gc-stat-mini .gc-stat-numero {
    font-size: 20px;
    font-weight: 600;
    margin-right: 8px;
}
.gc-stat-mini .gc-stat-label {
    color: #646970;
}

/* Selector de grupo */
.gc-grupo-selector {
    background: #fff;
    padding: 12px 15px;
    margin-bottom: 15px;
    border-radius: 4px;
    display: inline-block;
}
.gc-grupo-selector label {
    font-weight: 500;
    margin-right: 10px;
}
.gc-grupo-selector select {
    min-width: 200px;
}

/* Tabla */
.gc-tabla-solicitudes .column-usuario { width: 25%; }
.gc-tabla-solicitudes .column-motivacion { width: 25%; }
.gc-tabla-solicitudes .column-preferencias { width: 15%; }
.gc-tabla-solicitudes .column-fecha { width: 10%; }
.gc-tabla-solicitudes .column-estado { width: 10%; }
.gc-tabla-solicitudes .column-acciones { width: 15%; }

.gc-usuario-info {
    display: flex;
    align-items: center;
    gap: 12px;
}
.gc-usuario-info img {
    border-radius: 50%;
}
.gc-usuario-datos {
    display: flex;
    flex-direction: column;
}
.gc-email {
    font-size: 12px;
    color: #646970;
}

.gc-motivacion-preview {
    cursor: pointer;
    padding: 5px;
    border-radius: 3px;
    transition: background 0.2s;
}
.gc-motivacion-preview:hover {
    background: #f0f0f1;
}

/* Tags */
.gc-tag {
    display: inline-block;
    padding: 3px 8px;
    background: #e0e0e0;
    border-radius: 12px;
    font-size: 11px;
    margin: 2px;
}
.gc-tag-alergias {
    background: #fff3cd;
    color: #856404;
}
.gc-tag-mas {
    background: #2271b1;
    color: #fff;
}
.gc-preferencias-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 3px;
}

/* Estados */
.gc-estado-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}
.gc-estado-pendiente { background: #fcf0c3; color: #8a6d06; }
.gc-estado-activo { background: #d4edda; color: #155724; }
.gc-estado-baja { background: #f8d7da; color: #721c24; }

.gc-resuelto-por {
    display: block;
    font-size: 11px;
    color: #666;
    margin-top: 3px;
}

/* Botones de acciones */
.gc-acciones-botones {
    display: flex;
    gap: 8px;
}
.gc-acciones-botones .button {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
}
.gc-acciones-botones .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

/* Modal */
.gc-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 100000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.gc-modal-content {
    background: #fff;
    border-radius: 8px;
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow: auto;
    box-shadow: 0 5px 30px rgba(0,0,0,0.3);
}
.gc-modal-lg {
    max-width: 700px;
}
.gc-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    background: #f9f9f9;
}
.gc-modal-header h2 { margin: 0; font-size: 18px; }
.gc-modal-close {
    background: none;
    border: none;
    font-size: 28px;
    cursor: pointer;
    color: #666;
    line-height: 1;
}
.gc-modal-close:hover { color: #d63638; }
.gc-modal-body { padding: 20px; }
.gc-modal-footer {
    padding: 15px 20px;
    border-top: 1px solid #ddd;
    text-align: right;
    background: #f9f9f9;
}
.gc-modal-footer .button { margin-left: 10px; }

.gc-modal-footer-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.gc-modal-footer-acciones .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Detalles de solicitud */
.gc-detalles-grid {
    display: flex;
    flex-direction: column;
    gap: 20px;
}
.gc-detalle-seccion {
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}
.gc-detalle-seccion:last-child {
    border-bottom: none;
    padding-bottom: 0;
}
.gc-detalle-seccion h4 {
    margin: 0 0 10px;
    color: #1d2327;
    font-size: 14px;
}
.gc-detalle-seccion p {
    margin: 5px 0;
}
.gc-seccion-alergias {
    background: #fff3cd;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ffc107;
}
.gc-seccion-alergias h4 {
    color: #856404;
}
.gc-seccion-resolucion {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
}

.gc-cargando {
    text-align: center;
    padding: 30px;
}
.gc-cargando .spinner {
    float: none;
    margin-right: 10px;
}
.gc-error {
    color: #d63638;
    text-align: center;
}

/* Form fields */
.gc-form-field {
    margin-bottom: 15px;
}
.gc-form-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
}
.gc-form-field .description {
    font-size: 12px;
    color: #646970;
    margin: 0 0 8px;
}
.gc-form-field textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.gc-sin-datos {
    color: #999;
}
</style>
