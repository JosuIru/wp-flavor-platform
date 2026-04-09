<?php
/**
 * Vista Admin: Editar Comunidad
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

// Obtener ID de la comunidad
$comunidad_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

if (!$comunidad_id) {
    echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Comunidad no especificada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div></div>';
    return;
}

// Obtener datos de la comunidad
$comunidad = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$tabla_comunidades} WHERE id = %d",
    $comunidad_id
));

if (!$comunidad) {
    echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div></div>';
    return;
}

// Contar miembros
$total_miembros = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$tabla_miembros} WHERE comunidad_id = %d AND estado = 'activo'",
    $comunidad_id
));

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if (isset($_POST['comunidades_editar']) && wp_verify_nonce($_POST['comunidades_nonce'], 'comunidades_editar_comunidad')) {
    $nombre = sanitize_text_field($_POST['nombre'] ?? '');
    $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
    $categoria = sanitize_text_field($_POST['categoria'] ?? 'otra');
    $privacidad = sanitize_text_field($_POST['privacidad'] ?? 'publica');
    $estado = sanitize_text_field($_POST['estado'] ?? 'activa');
    $reglas = sanitize_textarea_field($_POST['reglas'] ?? '');

    if (empty($nombre)) {
        $mensaje = __('El nombre de la comunidad es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $tipo_mensaje = 'error';
    } else {
        $resultado = $wpdb->update(
            $tabla_comunidades,
            [
                'nombre'      => $nombre,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'privacidad'  => $privacidad,
                'estado'      => $estado,
                'reglas'      => $reglas,
                'updated_at'  => current_time('mysql'),
            ],
            ['id' => $comunidad_id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado !== false) {
            $mensaje = __('Comunidad actualizada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $tipo_mensaje = 'success';

            // Recargar datos
            $comunidad = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_comunidades} WHERE id = %d",
                $comunidad_id
            ));
        } else {
            $mensaje = __('Error al actualizar la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $tipo_mensaje = 'error';
        }
    }
}

// Categorías disponibles
$categorias = [
    'vecinal'     => __('Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'deportiva'   => __('Deportiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cultural'    => __('Cultural', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'educativa'   => __('Educativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'social'      => __('Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'profesional' => __('Profesional', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otra'        => __('Otra', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estados = [
    'activa'    => __('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'inactiva'  => __('Inactiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'archivada' => __('Archivada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$privacidades = [
    'publica' => __('Pública', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'privada' => __('Privada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'secreta' => __('Secreta', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=comunidades-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-admin-multisite" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <a href="<?php echo admin_url('admin.php?page=comunidades-listado'); ?>" style="color: #2271b1; text-decoration: none;">
            <?php _e('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php echo esc_html($comunidad->nombre); ?></span>
    </nav>

    <h1>
        <span class="dashicons dashicons-edit"></span>
        <?php printf(__('Editar: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($comunidad->nombre)); ?>
    </h1>

    <?php if ($mensaje): ?>
    <div class="notice notice-<?php echo esc_attr($tipo_mensaje); ?> is-dismissible">
        <p><?php echo esc_html($mensaje); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('comunidades_editar_comunidad', 'comunidades_nonce'); ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Columna principal -->
                <div id="post-body-content">
                    <div class="postbox">
                        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                            <span class="dashicons dashicons-edit"></span>
                            <?php _e('Información básica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h2>
                        <div class="inside">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nombre"><?php _e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" id="nombre" name="nombre" class="regular-text" required
                                               value="<?php echo esc_attr($comunidad->nombre); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="descripcion"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="descripcion" name="descripcion" rows="4" class="large-text"><?php echo esc_textarea($comunidad->descripcion ?? ''); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="reglas"><?php _e('Reglas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="reglas" name="reglas" rows="3" class="large-text"><?php echo esc_textarea($comunidad->reglas ?? ''); ?></textarea>
                                        <p class="description"><?php _e('Normas de convivencia para los miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="postbox">
                        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php _e('Acciones rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h2>
                        <div class="inside">
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <a href="<?php echo admin_url('admin.php?page=comunidades-miembros&comunidad=' . $comunidad_id); ?>" class="button">
                                    <span class="dashicons dashicons-groups" style="margin-top: 4px;"></span>
                                    <?php printf(__('Ver %d miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_miembros); ?>
                                </a>
                                <a href="<?php echo admin_url('admin.php?page=comunidades-publicaciones&comunidad=' . $comunidad_id); ?>" class="button">
                                    <span class="dashicons dashicons-format-chat" style="margin-top: 4px;"></span>
                                    <?php _e('Ver publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                                <a href="<?php echo home_url('/comunidad/?comunidad=' . $comunidad_id); ?>" class="button" target="_blank">
                                    <span class="dashicons dashicons-external" style="margin-top: 4px;"></span>
                                    <?php _e('Ver en frontend', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <div class="postbox">
                        <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php _e('Opciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h2>
                        <div class="inside">
                            <p>
                                <label for="estado"><strong><?php _e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                                <select id="estado" name="estado" style="width: 100%; margin-top: 5px;">
                                    <?php foreach ($estados as $slug => $label): ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($comunidad->estado, $slug); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <p>
                                <label for="categoria"><strong><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                                <select id="categoria" name="categoria" style="width: 100%; margin-top: 5px;">
                                    <?php foreach ($categorias as $slug => $label): ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($comunidad->categoria, $slug); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <p>
                                <label for="privacidad"><strong><?php _e('Privacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                                <select id="privacidad" name="privacidad" style="width: 100%; margin-top: 5px;">
                                    <?php foreach ($privacidades as $slug => $label): ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($comunidad->privacidad, $slug); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <hr>

                            <p style="color: #646970; font-size: 12px;">
                                <strong><?php _e('Creada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong><br>
                                <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($comunidad->created_at))); ?>
                            </p>

                            <p style="color: #646970; font-size: 12px;">
                                <strong><?php _e('Miembros:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong> <?php echo number_format($total_miembros); ?>
                            </p>

                            <hr>

                            <p>
                                <button type="submit" name="comunidades_editar" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                                    <?php _e('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </p>

                            <p style="text-align: center;">
                                <a href="<?php echo admin_url('admin.php?page=comunidades-listado'); ?>" class="button">
                                    <?php _e('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.required { color: #d63638; }
#post-body-content .postbox { margin-bottom: 20px; }
</style>
