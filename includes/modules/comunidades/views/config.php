<?php
/**
 * Vista Admin: Configuración del Módulo de Comunidades
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Guardar configuración
if (isset($_POST['comunidades_guardar_config']) && wp_verify_nonce($_POST['comunidades_config_nonce'], 'comunidades_guardar_configuracion')) {
    $opciones = [
        'max_miembros_comunidad'     => absint($_POST['max_miembros_comunidad'] ?? 0),
        'requiere_aprobacion'        => isset($_POST['requiere_aprobacion']) ? 1 : 0,
        'permitir_comunidades_privadas' => isset($_POST['permitir_comunidades_privadas']) ? 1 : 0,
        'permitir_comunidades_secretas' => isset($_POST['permitir_comunidades_secretas']) ? 1 : 0,
        'moderar_publicaciones'      => isset($_POST['moderar_publicaciones']) ? 1 : 0,
        'notificar_nuevos_miembros'  => isset($_POST['notificar_nuevos_miembros']) ? 1 : 0,
        'notificar_publicaciones'    => isset($_POST['notificar_publicaciones']) ? 1 : 0,
        'permitir_eventos'           => isset($_POST['permitir_eventos']) ? 1 : 0,
        'permitir_encuestas'         => isset($_POST['permitir_encuestas']) ? 1 : 0,
        'permitir_archivos'          => isset($_POST['permitir_archivos']) ? 1 : 0,
        'max_archivos_mb'            => absint($_POST['max_archivos_mb'] ?? 10),
        'categorias_habilitadas'     => isset($_POST['categorias_habilitadas']) ? array_map('sanitize_text_field', $_POST['categorias_habilitadas']) : [],
        'pagina_comunidades'         => sanitize_text_field($_POST['pagina_comunidades'] ?? ''),
        'email_admin'                => sanitize_email($_POST['email_admin'] ?? ''),
    ];

    update_option('flavor_comunidades_settings', $opciones);

    echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada correctamente.', 'flavor-chat-ia') . '</p></div>';
}

// Obtener configuración actual
$config = get_option('flavor_comunidades_settings', [
    'max_miembros_comunidad'        => 0,
    'requiere_aprobacion'           => 1,
    'permitir_comunidades_privadas' => 1,
    'permitir_comunidades_secretas' => 0,
    'moderar_publicaciones'         => 0,
    'notificar_nuevos_miembros'     => 1,
    'notificar_publicaciones'       => 0,
    'permitir_eventos'              => 1,
    'permitir_encuestas'            => 1,
    'permitir_archivos'             => 1,
    'max_archivos_mb'               => 10,
    'categorias_habilitadas'        => ['vecinal', 'deportiva', 'cultural', 'educativa', 'social', 'profesional', 'otra'],
    'pagina_comunidades'            => '',
    'email_admin'                   => get_option('admin_email'),
]);

// Categorías disponibles
$categorias_disponibles = [
    'vecinal'     => __('Vecinal', 'flavor-chat-ia'),
    'deportiva'   => __('Deportiva', 'flavor-chat-ia'),
    'cultural'    => __('Cultural', 'flavor-chat-ia'),
    'educativa'   => __('Educativa', 'flavor-chat-ia'),
    'social'      => __('Social', 'flavor-chat-ia'),
    'profesional' => __('Profesional', 'flavor-chat-ia'),
    'otra'        => __('Otra', 'flavor-chat-ia'),
];
?>

<div class="wrap flavor-admin-config">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=comunidades-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-admin-multisite" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Comunidades', 'flavor-chat-ia'); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Configuración', 'flavor-chat-ia'); ?></span>
    </nav>

    <h1>
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('Configuración de Comunidades', 'flavor-chat-ia'); ?>
    </h1>

    <form method="post" action="">
        <?php wp_nonce_field('comunidades_guardar_configuracion', 'comunidades_config_nonce'); ?>

        <!-- Configuración General -->
        <div class="postbox" style="margin-top: 20px;">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Configuración General', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_miembros_comunidad"><?php _e('Máximo de miembros por comunidad', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_miembros_comunidad" name="max_miembros_comunidad"
                                   value="<?php echo esc_attr($config['max_miembros_comunidad']); ?>" min="0" class="small-text">
                            <p class="description"><?php _e('0 = sin límite', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Aprobación de miembros', 'flavor-chat-ia'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="requiere_aprobacion" value="1"
                                       <?php checked($config['requiere_aprobacion'], 1); ?>>
                                <?php _e('Requerir aprobación del administrador para nuevos miembros', 'flavor-chat-ia'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Moderación de publicaciones', 'flavor-chat-ia'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="moderar_publicaciones" value="1"
                                       <?php checked($config['moderar_publicaciones'], 1); ?>>
                                <?php _e('Moderar publicaciones antes de publicarlas', 'flavor-chat-ia'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tipos de Comunidades -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-privacy"></span>
                <?php _e('Tipos de Comunidades', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Privacidad permitida', 'flavor-chat-ia'); ?></th>
                        <td>
                            <fieldset>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="permitir_comunidades_privadas" value="1"
                                           <?php checked($config['permitir_comunidades_privadas'], 1); ?>>
                                    <?php _e('Permitir comunidades privadas (visibles pero requieren solicitud)', 'flavor-chat-ia'); ?>
                                </label>
                                <label style="display: block;">
                                    <input type="checkbox" name="permitir_comunidades_secretas" value="1"
                                           <?php checked($config['permitir_comunidades_secretas'], 1); ?>>
                                    <?php _e('Permitir comunidades secretas (solo visibles para miembros)', 'flavor-chat-ia'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Categorías habilitadas', 'flavor-chat-ia'); ?></th>
                        <td>
                            <fieldset>
                                <?php foreach ($categorias_disponibles as $slug => $label): ?>
                                <label style="display: inline-block; margin-right: 20px; margin-bottom: 8px;">
                                    <input type="checkbox" name="categorias_habilitadas[]" value="<?php echo esc_attr($slug); ?>"
                                           <?php checked(in_array($slug, $config['categorias_habilitadas'])); ?>>
                                    <?php echo esc_html($label); ?>
                                </label>
                                <?php endforeach; ?>
                            </fieldset>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Funcionalidades -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-admin-plugins"></span>
                <?php _e('Funcionalidades', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Características adicionales', 'flavor-chat-ia'); ?></th>
                        <td>
                            <fieldset>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="permitir_eventos" value="1"
                                           <?php checked($config['permitir_eventos'], 1); ?>>
                                    <?php _e('Permitir crear eventos dentro de las comunidades', 'flavor-chat-ia'); ?>
                                </label>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="permitir_encuestas" value="1"
                                           <?php checked($config['permitir_encuestas'], 1); ?>>
                                    <?php _e('Permitir crear encuestas dentro de las comunidades', 'flavor-chat-ia'); ?>
                                </label>
                                <label style="display: block;">
                                    <input type="checkbox" name="permitir_archivos" value="1"
                                           <?php checked($config['permitir_archivos'], 1); ?>>
                                    <?php _e('Permitir compartir archivos', 'flavor-chat-ia'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="max_archivos_mb"><?php _e('Tamaño máximo de archivos', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_archivos_mb" name="max_archivos_mb"
                                   value="<?php echo esc_attr($config['max_archivos_mb']); ?>" min="1" max="100" class="small-text">
                            MB
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Notificaciones -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-email"></span>
                <?php _e('Notificaciones', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Notificaciones automáticas', 'flavor-chat-ia'); ?></th>
                        <td>
                            <fieldset>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="notificar_nuevos_miembros" value="1"
                                           <?php checked($config['notificar_nuevos_miembros'], 1); ?>>
                                    <?php _e('Notificar a administradores cuando hay nuevos miembros', 'flavor-chat-ia'); ?>
                                </label>
                                <label style="display: block;">
                                    <input type="checkbox" name="notificar_publicaciones" value="1"
                                           <?php checked($config['notificar_publicaciones'], 1); ?>>
                                    <?php _e('Notificar a miembros sobre nuevas publicaciones', 'flavor-chat-ia'); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email_admin"><?php _e('Email de administración', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="email_admin" name="email_admin"
                                   value="<?php echo esc_attr($config['email_admin']); ?>" class="regular-text">
                            <p class="description"><?php _e('Email para recibir notificaciones del módulo', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Integración -->
        <div class="postbox">
            <h2 class="hndle" style="padding: 12px; margin: 0; border-bottom: 1px solid #c3c4c7;">
                <span class="dashicons dashicons-admin-links"></span>
                <?php _e('Integración', 'flavor-chat-ia'); ?>
            </h2>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="pagina_comunidades"><?php _e('Página de comunidades', 'flavor-chat-ia'); ?></label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name'              => 'pagina_comunidades',
                                'id'                => 'pagina_comunidades',
                                'selected'          => $config['pagina_comunidades'],
                                'show_option_none'  => __('— Seleccionar página —', 'flavor-chat-ia'),
                                'option_none_value' => '',
                            ]);
                            ?>
                            <p class="description"><?php _e('Página donde se mostrarán las comunidades en el frontend', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="submit">
            <button type="submit" name="comunidades_guardar_config" class="button button-primary">
                <span class="dashicons dashicons-saved" style="margin-top: 4px;"></span>
                <?php _e('Guardar Configuración', 'flavor-chat-ia'); ?>
            </button>
        </p>
    </form>
</div>

<style>
.flavor-admin-config .postbox {
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    margin-bottom: 20px;
}
.flavor-admin-config .postbox .hndle {
    background: #f6f7f7;
    font-size: 14px;
    font-weight: 600;
}
.flavor-admin-config .postbox .hndle .dashicons {
    margin-right: 8px;
    color: #646970;
}
.flavor-admin-config .postbox .inside {
    padding: 0 12px 12px;
}
.flavor-admin-config .form-table th {
    padding-left: 10px;
}
</style>
