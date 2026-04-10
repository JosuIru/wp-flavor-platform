<?php
/**
 * Vista Admin: Nueva Comunidad
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

// Procesar formulario
$mensaje = '';
$tipo_mensaje = '';

if (isset($_POST['comunidades_crear']) && wp_verify_nonce($_POST['comunidades_nonce'], 'comunidades_crear_comunidad')) {
    $nombre = sanitize_text_field($_POST['nombre'] ?? '');
    $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
    $categoria = sanitize_text_field($_POST['categoria'] ?? 'otros');
    $tipo = sanitize_text_field($_POST['tipo'] ?? 'abierta');
    $reglas = sanitize_textarea_field($_POST['reglas'] ?? '');
    $creador_id = get_current_user_id();
    $slug = sanitize_title($nombre);

    if (empty($nombre)) {
        $mensaje = __('El nombre de la comunidad es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $tipo_mensaje = 'error';
    } else {
        // Insertar comunidad
        $resultado = $wpdb->insert(
            $tabla_comunidades,
            [
                'nombre'      => $nombre,
                'slug'        => $slug,
                'descripcion' => $descripcion,
                'categoria'   => $categoria,
                'tipo'        => $tipo,
                'reglas'      => $reglas,
                'creador_id'  => $creador_id,
                'estado'      => 'activa',
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        if ($resultado) {
            $comunidad_id = $wpdb->insert_id;

            // Agregar creador como admin de la comunidad
            $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
            $wpdb->insert(
                $tabla_miembros,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $creador_id,
                    'rol'          => 'admin',
                    'estado'       => 'activo',
                ],
                ['%d', '%d', '%s', '%s']
            );

            $mensaje = __('Comunidad creada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $tipo_mensaje = 'success';

            // Limpiar campos
            $_POST = [];
        } else {
            $mensaje = __('Error al crear la comunidad. Inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN);
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
    'otros'       => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
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
        <span style="color: #1d2327;"><?php _e('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1>
        <span class="dashicons dashicons-plus-alt2"></span>
        <?php _e('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if ($mensaje): ?>
    <div class="notice notice-<?php echo esc_attr($tipo_mensaje); ?> is-dismissible">
        <p><?php echo esc_html($mensaje); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <?php wp_nonce_field('comunidades_crear_comunidad', 'comunidades_nonce'); ?>

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
                                               value="<?php echo esc_attr($_POST['nombre'] ?? ''); ?>"
                                               placeholder="<?php esc_attr_e('Nombre de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="descripcion"><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="descripcion" name="descripcion" rows="4" class="large-text"
                                                  placeholder="<?php esc_attr_e('Describe el propósito de esta comunidad...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php echo esc_textarea($_POST['descripcion'] ?? ''); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="reglas"><?php _e('Reglas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                    </th>
                                    <td>
                                        <textarea id="reglas" name="reglas" rows="3" class="large-text"
                                                  placeholder="<?php esc_attr_e('Define las normas de convivencia...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"><?php echo esc_textarea($_POST['reglas'] ?? ''); ?></textarea>
                                        <p class="description"><?php _e('Opcional. Normas para los miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                    </td>
                                </tr>
                            </table>
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
                                <label for="categoria"><strong><?php _e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                                <select id="categoria" name="categoria" style="width: 100%; margin-top: 5px;">
                                    <?php foreach ($categorias as $slug => $label): ?>
                                    <option value="<?php echo esc_attr($slug); ?>" <?php selected($_POST['categoria'] ?? '', $slug); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </p>

                            <p>
                                <label for="tipo"><strong><?php _e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                                <select id="tipo" name="tipo" style="width: 100%; margin-top: 5px;">
                                    <option value="abierta" <?php selected($_POST['tipo'] ?? '', 'abierta'); ?>>
                                        <?php _e('Abierta - Cualquiera puede unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </option>
                                    <option value="cerrada" <?php selected($_POST['tipo'] ?? '', 'cerrada'); ?>>
                                        <?php _e('Cerrada - Requiere aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </option>
                                    <option value="secreta" <?php selected($_POST['tipo'] ?? '', 'secreta'); ?>>
                                        <?php _e('Secreta - Solo por invitación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </option>
                                </select>
                            </p>

                            <hr>

                            <p>
                                <button type="submit" name="comunidades_crear" class="button button-primary button-large" style="width: 100%;">
                                    <span class="dashicons dashicons-plus-alt2" style="margin-top: 4px;"></span>
                                    <?php _e('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </p>

                            <p style="text-align: center;">
                                <a href="<?php echo admin_url('admin.php?page=comunidades-listado'); ?>" class="button">
                                    <?php _e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
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
