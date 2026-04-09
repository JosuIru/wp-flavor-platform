<?php
/**
 * Vista Gestion de Hilos
 *
 * Listado y administracion de hilos/temas del foro
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
$tabla_foros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'");

// Parametros de filtrado
$filtro_foro = isset($_GET['foro_id']) ? absint($_GET['foro_id']) : 0;
$filtro_estado = isset($_GET['estado']) ? sanitize_key($_GET['estado']) : '';
$filtro_busqueda = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
$por_pagina = 20;
$offset = ($pagina_actual - 1) * $por_pagina;

// Construir consulta
$clausulas_where = ["h.estado != 'eliminado'"];
$valores_preparados = [];

if ($filtro_foro > 0) {
    $clausulas_where[] = 'h.foro_id = %d';
    $valores_preparados[] = $filtro_foro;
}

if ($filtro_estado && in_array($filtro_estado, ['abierto', 'cerrado', 'fijado'], true)) {
    $clausulas_where[] = 'h.estado = %s';
    $valores_preparados[] = $filtro_estado;
}

if (!empty($filtro_busqueda)) {
    $clausulas_where[] = '(h.titulo LIKE %s OR h.contenido LIKE %s)';
    $patron_busqueda = '%' . $wpdb->esc_like($filtro_busqueda) . '%';
    $valores_preparados[] = $patron_busqueda;
    $valores_preparados[] = $patron_busqueda;
}

$sql_where = implode(' AND ', $clausulas_where);

// Obtener total de hilos
$total_hilos = 0;
if ($tabla_hilos_existe) {
    $sql_count = "SELECT COUNT(*) FROM $tabla_hilos h WHERE $sql_where";
    if (!empty($valores_preparados)) {
        $total_hilos = $wpdb->get_var($wpdb->prepare($sql_count, $valores_preparados));
    } else {
        $total_hilos = $wpdb->get_var($sql_count);
    }
}

$total_paginas = ceil($total_hilos / $por_pagina);

// Obtener hilos
$hilos = [];
if ($tabla_hilos_existe && $tabla_foros_existe) {
    $sql_hilos = "SELECT h.*, f.nombre AS nombre_foro, u.display_name AS nombre_autor
                  FROM $tabla_hilos h
                  LEFT JOIN $tabla_foros f ON f.id = h.foro_id
                  LEFT JOIN {$wpdb->users} u ON u.ID = h.autor_id
                  WHERE $sql_where
                  ORDER BY h.es_fijado DESC, h.ultima_actividad DESC
                  LIMIT %d OFFSET %d";

    $valores_paginacion = array_merge($valores_preparados, [$por_pagina, $offset]);

    if (!empty($valores_paginacion)) {
        $hilos = $wpdb->get_results($wpdb->prepare($sql_hilos, $valores_paginacion));
    }
}

// Obtener lista de foros para filtro
$foros_disponibles = [];
if ($tabla_foros_existe) {
    $foros_disponibles = $wpdb->get_results("SELECT id, nombre FROM $tabla_foros ORDER BY orden ASC, nombre ASC");
}

$nonce = wp_create_nonce('flavor_foros_admin');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-comments"></span>
        <?php echo esc_html__('Gestion de Hilos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=foros'); ?>" class="page-title-action">
        <span class="dashicons dashicons-arrow-left-alt"></span>
        <?php echo esc_html__('Volver al Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <!-- Filtros -->
    <div class="tablenav top" style="margin: 20px 0;">
        <form method="get" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="foros-hilos">

            <!-- Filtro por foro -->
            <select name="foro_id" style="min-width: 150px;">
                <option value=""><?php echo esc_html__('Todos los foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($foros_disponibles as $foro) : ?>
                    <option value="<?php echo esc_attr($foro->id); ?>" <?php selected($filtro_foro, $foro->id); ?>>
                        <?php echo esc_html($foro->nombre); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Filtro por estado -->
            <select name="estado" style="min-width: 120px;">
                <option value=""><?php echo esc_html__('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="abierto" <?php selected($filtro_estado, 'abierto'); ?>><?php echo esc_html__('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="cerrado" <?php selected($filtro_estado, 'cerrado'); ?>><?php echo esc_html__('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="fijado" <?php selected($filtro_estado, 'fijado'); ?>><?php echo esc_html__('Fijado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>

            <!-- Busqueda -->
            <input type="search"
                   name="s"
                   value="<?php echo esc_attr($filtro_busqueda); ?>"
                   placeholder="<?php echo esc_attr__('Buscar hilos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                   style="min-width: 200px;">

            <button type="submit" class="button"><?php echo esc_html__('Filtrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

            <?php if ($filtro_foro || $filtro_estado || $filtro_busqueda) : ?>
                <a href="<?php echo admin_url('admin.php?page=foros-hilos'); ?>" class="button">
                    <?php echo esc_html__('Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </form>

        <div class="tablenav-pages" style="float: right; margin-top: -30px;">
            <span class="displaying-num">
                <?php printf(
                    _n('%s hilo', '%s hilos', $total_hilos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                    number_format_i18n($total_hilos)
                ); ?>
            </span>
        </div>
    </div>

    <!-- Tabla de Hilos -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width: 30px;"><input type="checkbox" id="select-all-hilos"></th>
                <th style="width: 50px;"><?php echo esc_html__('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th><?php echo esc_html__('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Foro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 80px;"><?php echo esc_html__('Respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 80px;"><?php echo esc_html__('Vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 120px;"><?php echo esc_html__('Ultima Act.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 100px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <th style="width: 150px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($hilos)) : ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 40px;">
                        <span class="dashicons dashicons-admin-comments" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></span>
                        <?php if ($filtro_foro || $filtro_estado || $filtro_busqueda) : ?>
                            <p><?php echo esc_html__('No se encontraron hilos con los filtros seleccionados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php else : ?>
                            <p><?php echo esc_html__('No hay hilos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else : ?>
                <?php foreach ($hilos as $hilo) : ?>
                    <?php
                    $clases_estado = [
                        'abierto' => ['bg' => '#d4edda', 'color' => '#155724', 'icono' => 'dashicons-yes'],
                        'cerrado' => ['bg' => '#f8d7da', 'color' => '#721c24', 'icono' => 'dashicons-lock'],
                        'fijado' => ['bg' => '#cce5ff', 'color' => '#004085', 'icono' => 'dashicons-admin-post'],
                    ];
                    $info_estado = $clases_estado[$hilo->estado] ?? ['bg' => '#e9ecef', 'color' => '#495057', 'icono' => 'dashicons-marker'];
                    ?>
                    <tr data-id="<?php echo esc_attr($hilo->id); ?>">
                        <td><input type="checkbox" class="hilo-checkbox" value="<?php echo esc_attr($hilo->id); ?>"></td>
                        <td><strong>#<?php echo intval($hilo->id); ?></strong></td>
                        <td>
                            <?php if ($hilo->es_fijado) : ?>
                                <span class="dashicons dashicons-admin-post" style="color: #2271b1;" title="<?php echo esc_attr__('Fijado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                            <?php endif; ?>
                            <?php if ($hilo->es_destacado) : ?>
                                <span class="dashicons dashicons-star-filled" style="color: #dba617;" title="<?php echo esc_attr__('Destacado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                            <?php endif; ?>
                            <strong><?php echo esc_html($hilo->titulo); ?></strong>
                            <br>
                            <small style="color: #646970;"><?php echo esc_html(wp_trim_words($hilo->contenido, 15, '...')); ?></small>
                        </td>
                        <td>
                            <a href="<?php echo admin_url('admin.php?page=foros-hilos&foro_id=' . $hilo->foro_id); ?>">
                                <?php echo esc_html($hilo->nombre_foro ?: '-'); ?>
                            </a>
                        </td>
                        <td>
                            <?php
                            if ($hilo->autor_id) {
                                echo get_avatar($hilo->autor_id, 24, '', '', ['style' => 'vertical-align: middle; margin-right: 5px; border-radius: 50%;']);
                            }
                            echo esc_html($hilo->nombre_autor ?: __('Usuario eliminado', FLAVOR_PLATFORM_TEXT_DOMAIN));
                            ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="dashicons dashicons-format-chat" style="color: #00a32a;"></span>
                            <?php echo intval($hilo->respuestas_count); ?>
                        </td>
                        <td style="text-align: center;">
                            <span class="dashicons dashicons-visibility" style="color: #646970;"></span>
                            <?php echo intval($hilo->vistas); ?>
                        </td>
                        <td>
                            <?php
                            if ($hilo->ultima_actividad) {
                                $diferencia_tiempo = human_time_diff(strtotime($hilo->ultima_actividad), current_time('timestamp'));
                                echo sprintf(__('hace %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $diferencia_tiempo);
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                        <td>
                            <span style="padding: 4px 8px; border-radius: 3px; font-size: 11px; background: <?php echo $info_estado['bg']; ?>; color: <?php echo $info_estado['color']; ?>;">
                                <span class="dashicons <?php echo $info_estado['icono']; ?>" style="font-size: 14px; vertical-align: text-bottom;"></span>
                                <?php echo esc_html(ucfirst($hilo->estado)); ?>
                            </span>
                        </td>
                        <td>
                            <div class="row-actions visible">
                                <span class="edit">
                                    <a href="#" class="hilo-accion" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="ver">
                                        <?php echo esc_html__('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a> |
                                </span>
                                <?php if ($hilo->estado === 'abierto') : ?>
                                    <span class="close">
                                        <a href="#" class="hilo-accion" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="cerrar">
                                            <?php echo esc_html__('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a> |
                                    </span>
                                <?php else : ?>
                                    <span class="open">
                                        <a href="#" class="hilo-accion" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="abrir">
                                            <?php echo esc_html__('Abrir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a> |
                                    </span>
                                <?php endif; ?>
                                <?php if (!$hilo->es_fijado) : ?>
                                    <span class="pin">
                                        <a href="#" class="hilo-accion" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="fijar">
                                            <?php echo esc_html__('Fijar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a> |
                                    </span>
                                <?php else : ?>
                                    <span class="unpin">
                                        <a href="#" class="hilo-accion" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="desfijar">
                                            <?php echo esc_html__('Desfijar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </a> |
                                    </span>
                                <?php endif; ?>
                                <span class="trash">
                                    <a href="#" class="hilo-accion" data-id="<?php echo esc_attr($hilo->id); ?>" data-accion="eliminar" style="color: #b32d2e;">
                                        <?php echo esc_html__('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginacion -->
    <?php if ($total_paginas > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(
                        _n('%s hilo', '%s hilos', $total_hilos, FLAVOR_PLATFORM_TEXT_DOMAIN),
                        number_format_i18n($total_hilos)
                    ); ?>
                </span>
                <span class="pagination-links">
                    <?php
                    $url_base = admin_url('admin.php?page=foros-hilos');
                    if ($filtro_foro) {
                        $url_base .= '&foro_id=' . $filtro_foro;
                    }
                    if ($filtro_estado) {
                        $url_base .= '&estado=' . $filtro_estado;
                    }
                    if ($filtro_busqueda) {
                        $url_base .= '&s=' . urlencode($filtro_busqueda);
                    }
                    ?>

                    <?php if ($pagina_actual > 1) : ?>
                        <a class="first-page button" href="<?php echo esc_url($url_base . '&paged=1'); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Primera pagina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span>&laquo;</span>
                        </a>
                        <a class="prev-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual - 1)); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Pagina anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span>&lsaquo;</span>
                        </a>
                    <?php endif; ?>

                    <span class="paging-input">
                        <?php echo $pagina_actual; ?> <?php echo esc_html__('de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo $total_paginas; ?>
                    </span>

                    <?php if ($pagina_actual < $total_paginas) : ?>
                        <a class="next-page button" href="<?php echo esc_url($url_base . '&paged=' . ($pagina_actual + 1)); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Pagina siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span>&rsaquo;</span>
                        </a>
                        <a class="last-page button" href="<?php echo esc_url($url_base . '&paged=' . $total_paginas); ?>">
                            <span class="screen-reader-text"><?php echo esc_html__('Ultima pagina', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <span>&raquo;</span>
                        </a>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
jQuery(document).ready(function($) {
    var nonce = '<?php echo esc_js($nonce); ?>';

    // Seleccionar todos
    $('#select-all-hilos').on('change', function() {
        $('.hilo-checkbox').prop('checked', $(this).is(':checked'));
    });

    // Acciones sobre hilos
    $('.hilo-accion').on('click', function(e) {
        e.preventDefault();

        var id = $(this).data('id');
        var accion = $(this).data('accion');

        if (accion === 'ver') {
            // Abrir en nueva ventana (frontend)
            window.open('<?php echo home_url('/foro/hilo/'); ?>' + id, '_blank');
            return;
        }

        if (accion === 'eliminar') {
            if (!confirm('<?php echo esc_js(__('Eliminar este hilo y todas sus respuestas?', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>')) {
                return;
            }
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_foros_moderar_hilo',
                nonce: nonce,
                hilo_id: id,
                accion_moderacion: accion
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error al procesar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            }
        });
    });
});
</script>
