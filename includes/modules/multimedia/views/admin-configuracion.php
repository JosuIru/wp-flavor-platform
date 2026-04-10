<?php
/**
 * Vista: Configuración del módulo Multimedia
 *
 * @package FlavorPlatform
 * @subpackage Multimedia
 */

if (!defined('ABSPATH')) {
    exit;
}

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['multimedia_config_nonce'])) {
    if (wp_verify_nonce($_POST['multimedia_config_nonce'], 'multimedia_save_config')) {
        $settings = [
            'permitir_subida_usuarios' => isset($_POST['permitir_subida_usuarios']),
            'requiere_moderacion' => isset($_POST['requiere_moderacion']),
            'tipos_permitidos' => isset($_POST['tipos_permitidos']) ? array_map('sanitize_text_field', $_POST['tipos_permitidos']) : ['image/jpeg', 'image/png', 'image/gif'],
            'tamano_maximo_mb' => absint($_POST['tamano_maximo_mb'] ?? 10),
            'ancho_maximo' => absint($_POST['ancho_maximo'] ?? 1920),
            'alto_maximo' => absint($_POST['alto_maximo'] ?? 1080),
            'calidad_compresion' => absint($_POST['calidad_compresion'] ?? 85),
            'marca_agua_enabled' => isset($_POST['marca_agua_enabled']),
            'marca_agua_texto' => sanitize_text_field($_POST['marca_agua_texto'] ?? ''),
        ];

        update_option('flavor_multimedia_settings', $settings);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Configuración guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }
}

// Obtener configuración actual
$settings = get_option('flavor_multimedia_settings', [
    'permitir_subida_usuarios' => true,
    'requiere_moderacion' => true,
    'tipos_permitidos' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'tamano_maximo_mb' => 10,
    'ancho_maximo' => 1920,
    'alto_maximo' => 1080,
    'calidad_compresion' => 85,
    'marca_agua_enabled' => false,
    'marca_agua_texto' => '',
]);
?>

<div class="wrap multimedia-configuracion">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-multimedia'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-format-gallery" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1><?php _e('Configuración de Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('multimedia_save_config', 'multimedia_config_nonce'); ?>

        <!-- Configuración General -->
        <div class="multimedia-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <?php _e('Configuración General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Subida de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="permitir_subida_usuarios" value="1"
                                <?php checked($settings['permitir_subida_usuarios'] ?? false); ?>>
                            <?php _e('Permitir a los usuarios subir contenido multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="requiere_moderacion" value="1"
                                <?php checked($settings['requiere_moderacion'] ?? true); ?>>
                            <?php _e('Requerir aprobación antes de publicar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <p class="description"><?php _e('El contenido subido por usuarios quedará pendiente de revisión.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Restricciones de archivos -->
        <div class="multimedia-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <?php _e('Restricciones de Archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="tamano_maximo_mb"><?php _e('Tamaño máximo (MB)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" name="tamano_maximo_mb" id="tamano_maximo_mb" class="small-text"
                               value="<?php echo esc_attr($settings['tamano_maximo_mb'] ?? 10); ?>" min="1" max="100">
                        <p class="description"><?php _e('Tamaño máximo permitido por archivo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Tipos de archivo permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php
                        $tipos_disponibles = [
                            'image/jpeg' => 'JPEG',
                            'image/png' => 'PNG',
                            'image/gif' => 'GIF',
                            'image/webp' => 'WebP',
                            'video/mp4' => 'MP4',
                            'video/webm' => 'WebM',
                        ];
                        $tipos_seleccionados = $settings['tipos_permitidos'] ?? [];
                        foreach ($tipos_disponibles as $tipo => $label):
                        ?>
                        <label style="display: inline-block; margin-right: 15px; margin-bottom: 5px;">
                            <input type="checkbox" name="tipos_permitidos[]" value="<?php echo esc_attr($tipo); ?>"
                                <?php checked(in_array($tipo, $tipos_seleccionados)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Procesamiento de imágenes -->
        <div class="multimedia-settings-section" style="background: #fff; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee;">
                <?php _e('Procesamiento de Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ancho_maximo"><?php _e('Dimensiones máximas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" name="ancho_maximo" id="ancho_maximo" class="small-text"
                               value="<?php echo esc_attr($settings['ancho_maximo'] ?? 1920); ?>" min="100" max="4096">
                        ×
                        <input type="number" name="alto_maximo" id="alto_maximo" class="small-text"
                               value="<?php echo esc_attr($settings['alto_maximo'] ?? 1080); ?>" min="100" max="4096">
                        px
                        <p class="description"><?php _e('Las imágenes más grandes serán redimensionadas automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="calidad_compresion"><?php _e('Calidad de compresión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="range" name="calidad_compresion" id="calidad_compresion"
                               value="<?php echo esc_attr($settings['calidad_compresion'] ?? 85); ?>" min="50" max="100"
                               style="vertical-align: middle;">
                        <span id="calidad_valor"><?php echo esc_html($settings['calidad_compresion'] ?? 85); ?>%</span>
                        <p class="description"><?php _e('Mayor calidad = archivos más grandes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Marca de agua', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="marca_agua_enabled" value="1"
                                <?php checked($settings['marca_agua_enabled'] ?? false); ?>>
                            <?php _e('Añadir marca de agua a las imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <br><br>
                        <input type="text" name="marca_agua_texto" class="regular-text"
                               value="<?php echo esc_attr($settings['marca_agua_texto'] ?? ''); ?>"
                               placeholder="<?php echo esc_attr__('Texto de la marca de agua', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#calidad_compresion').on('input', function() {
        $('#calidad_valor').text($(this).val() + '%');
    });
});
</script>
