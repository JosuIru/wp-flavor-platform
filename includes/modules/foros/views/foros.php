<?php
/**
 * Vista Gestion de Foros
 *
 * Listado y administracion de categorias de foros
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
$tabla_foros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'");

// Obtener foros con estadisticas
$foros = [];
if ($tabla_foros_existe) {
    $foros = $wpdb->get_results(
        "SELECT f.*,
                COALESCE((SELECT COUNT(*) FROM $tabla_hilos h WHERE h.foro_id = f.id AND h.estado != 'eliminado'), 0) AS total_hilos,
                COALESCE((SELECT SUM(COALESCE(h.respuestas_count, 0)) FROM $tabla_hilos h WHERE h.foro_id = f.id), 0) AS total_respuestas
         FROM $tabla_foros f
         ORDER BY f.orden ASC, f.nombre ASC"
    );
}

// Verificar si estamos editando
$editando = null;
$accion_actual = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
$id_editar = isset($_GET['id']) ? absint($_GET['id']) : 0;

if ($accion_actual === 'editar' && $id_editar > 0 && $tabla_foros_existe) {
    $editando = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_foros WHERE id = %d",
        $id_editar
    ));
}

$nonce = wp_create_nonce('flavor_foros_admin');
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-category"></span>
        <?php echo esc_html__('Gestion de Foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo admin_url('admin.php?page=foros'); ?>" class="page-title-action">
        <span class="dashicons dashicons-arrow-left-alt"></span>
        <?php echo esc_html__('Volver al Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <div id="foros-gestion" style="display: grid; grid-template-columns: 1fr 350px; gap: 20px; margin-top: 20px;">

        <!-- Lista de Foros -->
        <div class="foros-lista-panel">
            <div class="postbox">
                <h2 class="hndle" style="padding: 12px;">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php echo esc_html__('Categorias de Foros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="inside" style="padding: 0;">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;"><?php echo esc_html__('Orden', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th style="width: 50px;"><?php echo esc_html__('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th style="width: 80px;"><?php echo esc_html__('Hilos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th style="width: 100px;"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th style="width: 140px;"><?php echo esc_html__('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody id="foros-lista">
                            <?php if (empty($foros)) : ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <span class="dashicons dashicons-format-chat" style="font-size: 48px; color: #ccc; display: block; margin-bottom: 10px;"></span>
                                        <p><?php echo esc_html__('No hay categorias de foros creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                        <p><?php echo esc_html__('Usa el formulario de la derecha para crear tu primera categoria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($foros as $foro) : ?>
                                    <?php
                                    $clases_estado = [
                                        'activo' => 'background: #d4edda; color: #155724;',
                                        'cerrado' => 'background: #fff3cd; color: #856404;',
                                        'archivado' => 'background: #f8d7da; color: #721c24;',
                                    ];
                                    $estilo_estado = $clases_estado[$foro->estado] ?? '';
                                    ?>
                                    <tr data-id="<?php echo esc_attr($foro->id); ?>">
                                        <td>
                                            <input type="number"
                                                   value="<?php echo esc_attr($foro->orden); ?>"
                                                   min="0"
                                                   style="width: 50px;"
                                                   class="foro-orden-input"
                                                   data-id="<?php echo esc_attr($foro->id); ?>">
                                        </td>
                                        <td style="font-size: 20px; text-align: center;">
                                            <?php echo esc_html($foro->icono ?: '💬'); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($foro->nombre); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo esc_html(wp_trim_words($foro->descripcion, 8, '...')); ?>
                                        </td>
                                        <td style="text-align: center;">
                                            <span class="dashicons dashicons-admin-comments" style="color: #2271b1;"></span>
                                            <?php echo intval($foro->total_hilos); ?>
                                        </td>
                                        <td>
                                            <span style="padding: 4px 8px; border-radius: 3px; font-size: 11px; <?php echo $estilo_estado; ?>">
                                                <?php echo esc_html(ucfirst($foro->estado)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=foros-listado&action=editar&id=' . $foro->id)); ?>"
                                               class="button button-small">
                                                <?php echo esc_html__('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </a>
                                            <button type="button"
                                                    class="button button-small button-link-delete foro-eliminar-btn"
                                                    data-id="<?php echo esc_attr($foro->id); ?>"
                                                    data-nombre="<?php echo esc_attr($foro->nombre); ?>"
                                                    data-hilos="<?php echo intval($foro->total_hilos); ?>">
                                                <?php echo esc_html__('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Formulario Nuevo/Editar -->
        <div class="foros-form-panel">
            <div class="postbox">
                <h2 class="hndle" style="padding: 12px;">
                    <span class="dashicons dashicons-<?php echo $editando ? 'edit' : 'plus-alt'; ?>"></span>
                    <?php echo $editando
                        ? esc_html__('Editar Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN)
                        : esc_html__('Nueva Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="inside">
                    <form id="form-foro-categoria" method="post">
                        <input type="hidden" name="foro_id" value="<?php echo $editando ? esc_attr($editando->id) : ''; ?>">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="foro-nombre"><?php echo esc_html__('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span style="color: red;">*</span></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="foro-nombre"
                                           name="nombre"
                                           class="regular-text"
                                           required
                                           placeholder="<?php echo esc_attr__('Ej: General, Soporte, Ideas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                           value="<?php echo $editando ? esc_attr($editando->nombre) : ''; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="foro-descripcion"><?php echo esc_html__('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <textarea id="foro-descripcion"
                                              name="descripcion"
                                              class="large-text"
                                              rows="3"
                                              placeholder="<?php echo esc_attr__('Describe el proposito de este foro...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php echo $editando ? esc_textarea($editando->descripcion) : ''; ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="foro-icono"><?php echo esc_html__('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="text"
                                           id="foro-icono"
                                           name="icono"
                                           class="small-text"
                                           placeholder="💬"
                                           value="<?php echo $editando ? esc_attr($editando->icono) : '💬'; ?>">
                                    <p class="description"><?php echo esc_html__('Usa un emoji', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="foro-orden"><?php echo esc_html__('Orden', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <input type="number"
                                           id="foro-orden"
                                           name="orden"
                                           class="small-text"
                                           min="0"
                                           value="<?php echo $editando ? esc_attr($editando->orden) : '0'; ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="foro-estado"><?php echo esc_html__('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                </th>
                                <td>
                                    <select id="foro-estado" name="estado" class="regular-text">
                                        <option value="activo" <?php selected($editando->estado ?? 'activo', 'activo'); ?>>
                                            <?php echo esc_html__('Activo - Se pueden crear hilos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="cerrado" <?php selected($editando->estado ?? '', 'cerrado'); ?>>
                                            <?php echo esc_html__('Cerrado - Solo lectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </option>
                                        <option value="archivado" <?php selected($editando->estado ?? '', 'archivado'); ?>>
                                            <?php echo esc_html__('Archivado - Oculto al publico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <p class="submit" style="padding-top: 0;">
                            <button type="submit" class="button button-primary button-large">
                                <?php echo $editando
                                    ? esc_html__('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN)
                                    : esc_html__('Crear Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <?php if ($editando) : ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=foros-listado')); ?>" class="button button-large">
                                    <?php echo esc_html__('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>

            <!-- Ayuda -->
            <div class="postbox" style="margin-top: 15px;">
                <h2 class="hndle" style="padding: 12px;">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php echo esc_html__('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="inside">
                    <p><strong><?php echo esc_html__('Estados disponibles:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><strong><?php echo esc_html__('Activo:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('Los usuarios pueden ver y crear hilos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><strong><?php echo esc_html__('Cerrado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('Visible pero no se pueden crear nuevos hilos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                        <li><strong><?php echo esc_html__('Archivado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo esc_html__('Oculto del listado publico.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var nonce = '<?php echo esc_js($nonce); ?>';

    // Guardar categoria
    $('#form-foro-categoria').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type="submit"]');
        var textoOriginal = $btn.text();

        $btn.prop('disabled', true).text('<?php echo esc_js(__('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_foros_guardar_categoria',
                nonce: nonce,
                id: $form.find('[name="foro_id"]').val(),
                nombre: $form.find('[name="nombre"]').val(),
                descripcion: $form.find('[name="descripcion"]').val(),
                icono: $form.find('[name="icono"]').val(),
                orden: $form.find('[name="orden"]').val(),
                estado: $form.find('[name="estado"]').val()
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_js(admin_url('admin.php?page=foros-listado&saved=1')); ?>';
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    $btn.prop('disabled', false).text(textoOriginal);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                $btn.prop('disabled', false).text(textoOriginal);
            }
        });
    });

    // Eliminar categoria
    $('.foro-eliminar-btn').on('click', function() {
        var id = $(this).data('id');
        var nombre = $(this).data('nombre');
        var hilos = $(this).data('hilos');

        var mensaje = '<?php echo esc_js(__('Eliminar la categoria', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> "' + nombre + '"?';
        if (hilos > 0) {
            mensaje += '\n\n<?php echo esc_js(__('Esta categoria tiene', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> ' + hilos + ' <?php echo esc_js(__('hilos que tambien se eliminaran.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
        }

        if (!confirm(mensaje)) {
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_foros_eliminar_categoria',
                nonce: nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data || '<?php echo esc_js(__('Error al eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                }
            }
        });
    });

    // Cambiar orden en tiempo real
    $('.foro-orden-input').on('change', function() {
        var id = $(this).data('id');
        var orden = $(this).val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'flavor_foros_guardar_categoria',
                nonce: nonce,
                id: id,
                orden: orden,
                solo_orden: 1
            }
        });
    });

    // Mostrar mensaje de guardado exitoso
    <?php if (isset($_GET['saved'])) : ?>
        var $notice = $('<div class="notice notice-success is-dismissible"><p><?php echo esc_js(__('Categoria guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p></div>');
        $('.wrap h1').first().after($notice);
    <?php endif; ?>
});
</script>
