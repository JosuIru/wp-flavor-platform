<?php
/**
 * Vista Moderacion de Foros
 *
 * Panel de moderacion para hilos y respuestas reportados
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_foros = $wpdb->prefix . 'flavor_foros';
$tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
$tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

// Verificar si las tablas existen
$tabla_hilos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_hilos'");
$tabla_respuestas_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_respuestas'");
$tabla_foros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'");

// Tab actual
$tab_actual = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'pendientes';

// Contar elementos pendientes de moderacion
$total_hilos_pendientes = 0;
$total_respuestas_reportadas = 0;

if ($tabla_hilos_existe) {
    $total_hilos_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_hilos WHERE estado = 'pendiente'");
}

if ($tabla_respuestas_existe) {
    $total_respuestas_reportadas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'reportado'");
}

// Obtener hilos pendientes de moderacion
$hilos_pendientes = [];
if ($tabla_hilos_existe && $tabla_foros_existe) {
    $hilos_pendientes = $wpdb->get_results(
        "SELECT h.*, f.nombre AS nombre_foro, u.display_name AS nombre_autor
         FROM $tabla_hilos h
         LEFT JOIN $tabla_foros f ON f.id = h.foro_id
         LEFT JOIN {$wpdb->users} u ON u.ID = h.autor_id
         WHERE h.estado = 'pendiente'
         ORDER BY h.created_at DESC
         LIMIT 50"
    );
}

// Obtener respuestas reportadas
$respuestas_reportadas = [];
if ($tabla_respuestas_existe && $tabla_hilos_existe) {
    $respuestas_reportadas = $wpdb->get_results(
        "SELECT r.*, h.titulo AS titulo_hilo, u.display_name AS nombre_autor
         FROM $tabla_respuestas r
         LEFT JOIN $tabla_hilos h ON h.id = r.hilo_id
         LEFT JOIN {$wpdb->users} u ON u.ID = r.autor_id
         WHERE r.estado = 'reportado'
         ORDER BY r.created_at DESC
         LIMIT 50"
    );
}

// Obtener actividad reciente de moderacion (ultimos 50 elementos moderados)
$historial_moderacion = [];
if ($tabla_hilos_existe && $tabla_respuestas_existe) {
    // Hilos eliminados/cerrados recientemente
    $hilos_moderados = $wpdb->get_results(
        "SELECT h.id, h.titulo AS titulo, h.estado, h.updated_at, 'hilo' AS tipo, u.display_name AS autor
         FROM $tabla_hilos h
         LEFT JOIN {$wpdb->users} u ON u.ID = h.autor_id
         WHERE h.estado IN ('eliminado', 'cerrado')
         ORDER BY h.updated_at DESC
         LIMIT 25"
    );

    // Respuestas ocultadas/eliminadas recientemente
    $respuestas_moderadas = $wpdb->get_results(
        "SELECT r.id, CONCAT('Respuesta en: ', h.titulo) AS titulo, r.estado, r.updated_at, 'respuesta' AS tipo, u.display_name AS autor
         FROM $tabla_respuestas r
         LEFT JOIN $tabla_hilos h ON h.id = r.hilo_id
         LEFT JOIN {$wpdb->users} u ON u.ID = r.autor_id
         WHERE r.estado IN ('eliminado', 'oculto')
         ORDER BY r.updated_at DESC
         LIMIT 25"
    );

    // Combinar y ordenar
    $historial_moderacion = array_merge($hilos_moderados, $respuestas_moderadas);
    usort($historial_moderacion, function($a, $b) {
        return strtotime($b->updated_at) - strtotime($a->updated_at);
    });
    $historial_moderacion = array_slice($historial_moderacion, 0, 20);
}

$nonce = wp_create_nonce('flavor_foros_admin');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-shield"></span>
        <?php echo esc_html__('Moderacion de Foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=foros'); ?>" class="page-title-action">
        <span class="dashicons dashicons-arrow-left-alt"></span>
        <?php echo esc_html__('Volver al Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <!-- Estadisticas de Moderacion -->
    <div class="moderacion-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
        <div class="stat-card" style="background: #fff; border-left: 4px solid <?php echo $total_hilos_pendientes > 0 ? '#dba617' : '#00a32a'; ?>; padding: 15px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-format-chat" style="font-size: 28px; color: <?php echo $total_hilos_pendientes > 0 ? '#dba617' : '#00a32a'; ?>;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo intval($total_hilos_pendientes); ?></div>
                    <div style="color: #646970; font-size: 13px;"><?php echo esc_html__('Hilos pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; border-left: 4px solid <?php echo $total_respuestas_reportadas > 0 ? '#d63638' : '#00a32a'; ?>; padding: 15px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-warning" style="font-size: 28px; color: <?php echo $total_respuestas_reportadas > 0 ? '#d63638' : '#00a32a'; ?>;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo intval($total_respuestas_reportadas); ?></div>
                    <div style="color: #646970; font-size: 13px;"><?php echo esc_html__('Respuestas reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>

        <div class="stat-card" style="background: #fff; border-left: 4px solid #2271b1; padding: 15px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-users" style="font-size: 28px; color: #2271b1;"></span>
                <div>
                    <div style="font-size: 24px; font-weight: bold;"><?php echo count($historial_moderacion); ?></div>
                    <div style="color: #646970; font-size: 13px;"><?php echo esc_html__('Acciones recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Navegacion -->
    <nav class="nav-tab-wrapper" style="margin-bottom: 0;">
        <a href="<?php echo admin_url('admin.php?page=foros-moderacion&tab=pendientes'); ?>"
           class="nav-tab <?php echo $tab_actual === 'pendientes' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-clock"></span>
            <?php echo esc_html__('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($total_hilos_pendientes > 0) : ?>
                <span class="badge" style="background: #dba617; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                    <?php echo intval($total_hilos_pendientes); ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-moderacion&tab=reportados'); ?>"
           class="nav-tab <?php echo $tab_actual === 'reportados' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-flag"></span>
            <?php echo esc_html__('Reportados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($total_respuestas_reportadas > 0) : ?>
                <span class="badge" style="background: #d63638; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                    <?php echo intval($total_respuestas_reportadas); ?>
                </span>
            <?php endif; ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=foros-moderacion&tab=historial'); ?>"
           class="nav-tab <?php echo $tab_actual === 'historial' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-backup"></span>
            <?php echo esc_html__('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </nav>

    <!-- Contenido de Tabs -->
    <div class="tab-content" style="background: #fff; border: 1px solid #c3c4c7; border-top: none; padding: 20px;">

        <?php if ($tab_actual === 'pendientes') : ?>
            <!-- Tab: Hilos Pendientes -->
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-clock"></span>
                <?php echo esc_html__('Hilos Pendientes de Aprobacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>

            <?php if (empty($hilos_pendientes)) : ?>
                <div style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 64px; color: #00a32a; display: block; margin-bottom: 10px;"></span>
                    <p style="font-size: 16px; color: #1d2327;"><?php echo esc_html__('No hay hilos pendientes de moderacion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p style="color: #646970;"><?php echo esc_html__('Todos los hilos han sido revisados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php echo esc_html__('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 120px;"><?php echo esc_html__('Foro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 120px;"><?php echo esc_html__('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 140px;"><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 180px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hilos_pendientes as $hilo) : ?>
                            <tr data-id="<?php echo esc_attr($hilo->id); ?>">
                                <td><strong>#<?php echo intval($hilo->id); ?></strong></td>
                                <td>
                                    <strong><?php echo esc_html($hilo->titulo); ?></strong>
                                    <br>
                                    <small style="color: #646970;"><?php echo esc_html(wp_trim_words($hilo->contenido, 20, '...')); ?></small>
                                </td>
                                <td><?php echo esc_html($hilo->nombre_foro ?: '-'); ?></td>
                                <td>
                                    <?php
                                    if ($hilo->autor_id) {
                                        echo get_avatar($hilo->autor_id, 24, '', '', ['style' => 'vertical-align: middle; margin-right: 5px; border-radius: 50%;']);
                                    }
                                    echo esc_html($hilo->nombre_autor ?: __('Anonimo', FLAVOR_PLATFORM_TEXT_DOMAIN));
                                    ?>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($hilo->created_at))); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-primary button-small moderar-hilo" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="aprobar">
                                        <span class="dashicons dashicons-yes" style="vertical-align: text-bottom;"></span>
                                        <?php echo esc_html__('Aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <button type="button" class="button button-small moderar-hilo" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="rechazar" style="color: #b32d2e;">
                                        <span class="dashicons dashicons-no" style="vertical-align: text-bottom;"></span>
                                        <?php echo esc_html__('Rechazar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php elseif ($tab_actual === 'reportados') : ?>
            <!-- Tab: Respuestas Reportadas -->
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-flag"></span>
                <?php echo esc_html__('Respuestas Reportadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>

            <?php if (empty($respuestas_reportadas)) : ?>
                <div style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 64px; color: #00a32a; display: block; margin-bottom: 10px;"></span>
                    <p style="font-size: 16px; color: #1d2327;"><?php echo esc_html__('No hay respuestas reportadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <p style="color: #646970;"><?php echo esc_html__('Todos los reportes han sido procesados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php echo esc_html__('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 200px;"><?php echo esc_html__('Hilo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 120px;"><?php echo esc_html__('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 140px;"><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 200px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($respuestas_reportadas as $respuesta) : ?>
                            <tr data-id="<?php echo esc_attr($respuesta->id); ?>">
                                <td><strong>#<?php echo intval($respuesta->id); ?></strong></td>
                                <td>
                                    <div style="max-height: 80px; overflow: hidden; padding: 10px; background: #f9f9f9; border-radius: 4px; border-left: 3px solid #d63638;">
                                        <?php echo esc_html(wp_trim_words($respuesta->contenido, 30, '...')); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="#" title="<?php echo esc_attr($respuesta->titulo_hilo); ?>">
                                        <?php echo esc_html(wp_trim_words($respuesta->titulo_hilo, 5, '...')); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php
                                    if ($respuesta->autor_id) {
                                        echo get_avatar($respuesta->autor_id, 24, '', '', ['style' => 'vertical-align: middle; margin-right: 5px; border-radius: 50%;']);
                                    }
                                    echo esc_html($respuesta->nombre_autor ?: __('Anonimo', FLAVOR_PLATFORM_TEXT_DOMAIN));
                                    ?>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($respuesta->created_at))); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small moderar-respuesta" data-id="<?php echo esc_attr($respuesta->id); ?>" data-accion="aprobar">
                                        <span class="dashicons dashicons-yes" style="vertical-align: text-bottom;"></span>
                                        <?php echo esc_html__('Aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <button type="button" class="button button-small moderar-respuesta" data-id="<?php echo esc_attr($respuesta->id); ?>" data-accion="ocultar" style="color: #dba617;">
                                        <span class="dashicons dashicons-hidden" style="vertical-align: text-bottom;"></span>
                                        <?php echo esc_html__('Ocultar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                    <button type="button" class="button button-small moderar-respuesta" data-id="<?php echo esc_attr($respuesta->id); ?>" data-accion="eliminar" style="color: #b32d2e;">
                                        <span class="dashicons dashicons-trash" style="vertical-align: text-bottom;"></span>
                                        <?php echo esc_html__('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php elseif ($tab_actual === 'historial') : ?>
            <!-- Tab: Historial de Moderacion -->
            <h2 style="margin-top: 0;">
                <span class="dashicons dashicons-backup"></span>
                <?php echo esc_html__('Historial de Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>

            <?php if (empty($historial_moderacion)) : ?>
                <div style="text-align: center; padding: 40px;">
                    <span class="dashicons dashicons-archive" style="font-size: 64px; color: #ccc; display: block; margin-bottom: 10px;"></span>
                    <p style="font-size: 16px; color: #1d2327;"><?php echo esc_html__('No hay historial de moderacion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php echo esc_html__('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php echo esc_html__('Elemento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 120px;"><?php echo esc_html__('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 140px;"><?php echo esc_html__('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th style="width: 100px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_moderacion as $item) : ?>
                            <?php
                            $icono_tipo = $item->tipo === 'hilo' ? 'dashicons-format-chat' : 'dashicons-admin-comments';
                            $color_tipo = $item->tipo === 'hilo' ? '#2271b1' : '#8c52ff';
                            $clases_estado = [
                                'eliminado' => ['bg' => '#f8d7da', 'color' => '#721c24'],
                                'cerrado' => ['bg' => '#fff3cd', 'color' => '#856404'],
                                'oculto' => ['bg' => '#e9ecef', 'color' => '#495057'],
                            ];
                            $info_estado = $clases_estado[$item->estado] ?? ['bg' => '#e9ecef', 'color' => '#495057'];
                            ?>
                            <tr>
                                <td>
                                    <span class="dashicons <?php echo $icono_tipo; ?>" style="color: <?php echo $color_tipo; ?>;"></span>
                                    <?php echo esc_html(ucfirst($item->tipo)); ?>
                                </td>
                                <td><?php echo esc_html(wp_trim_words($item->titulo, 10, '...')); ?></td>
                                <td><?php echo esc_html($item->autor ?: __('Desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                                <td>
                                    <span style="padding: 4px 8px; border-radius: 3px; font-size: 11px; background: <?php echo $info_estado['bg']; ?>; color: <?php echo $info_estado['color']; ?>;">
                                        <?php echo esc_html(ucfirst($item->estado)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $diferencia = human_time_diff(strtotime($item->updated_at), current_time('timestamp'));
                                    echo sprintf(__('hace %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $diferencia);
                                    ?>
                                </td>
                                <td>
                                    <?php if ($item->estado !== 'eliminado') : ?>
                                        <button type="button" class="button button-small restaurar-item" data-id="<?php echo esc_attr($item->id); ?>" data-tipo="<?php echo esc_attr($item->tipo); ?>">
                                            <span class="dashicons dashicons-undo" style="vertical-align: text-bottom;"></span>
                                            <?php echo esc_html__('Restaurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php else : ?>
                                        <span style="color: #646970;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var nonce = '<?php echo esc_js($nonce); ?>';

    // Moderar hilo
    $('.moderar-hilo').on('click', function() {
        var id = $(this).data('id');
        var accion = $(this).data('accion');
        var $row = $(this).closest('tr');

        if (accion === 'rechazar') {
            if (!confirm('<?php echo esc_js(__('Rechazar este hilo?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                return;
            }
        }

        var accionBackend = accion === 'aprobar' ? 'abrir' : 'eliminar';

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_foros_moderar_hilo',
                nonce: nonce,
                hilo_id: id,
                accion_moderacion: accionBackend
            },
            beforeSend: function() {
                $row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error al procesar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    $row.css('opacity', '1');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                $row.css('opacity', '1');
            }
        });
    });

    // Moderar respuesta
    $('.moderar-respuesta').on('click', function() {
        var id = $(this).data('id');
        var accion = $(this).data('accion');
        var $row = $(this).closest('tr');

        if (accion === 'eliminar') {
            if (!confirm('<?php echo esc_js(__('Eliminar esta respuesta?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                return;
            }
        }

        var accionBackend = accion === 'aprobar' ? 'mostrar' : accion;

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_foros_moderar_respuesta',
                nonce: nonce,
                respuesta_id: id,
                accion_moderacion: accionBackend
            },
            beforeSend: function() {
                $row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error al procesar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    $row.css('opacity', '1');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                $row.css('opacity', '1');
            }
        });
    });

    // Restaurar elemento
    $('.restaurar-item').on('click', function() {
        var id = $(this).data('id');
        var tipo = $(this).data('tipo');
        var $row = $(this).closest('tr');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: tipo === 'hilo' ? 'flavor_foros_moderar_hilo' : 'flavor_foros_moderar_respuesta',
                nonce: nonce,
                [tipo === 'hilo' ? 'hilo_id' : 'respuesta_id']: id,
                accion_moderacion: tipo === 'hilo' ? 'abrir' : 'mostrar'
            },
            beforeSend: function() {
                $row.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    $row.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error al procesar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    $row.css('opacity', '1');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                $row.css('opacity', '1');
            }
        });
    });
});
</script>
