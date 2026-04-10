<?php
/**
 * Vista: Configuración de Email Marketing
 *
 * @package FlavorPlatform
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

$modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');
$settings = $modulo->get_settings();

// Guardar configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['em_settings_nonce'])) {
    if (wp_verify_nonce($_POST['em_settings_nonce'], 'em_save_settings')) {
        $new_settings = [
            'remitente_nombre' => sanitize_text_field($_POST['remitente_nombre']),
            'remitente_email' => sanitize_email($_POST['remitente_email']),
            'responder_a' => sanitize_email($_POST['responder_a']),
            'emails_por_hora' => absint($_POST['emails_por_hora']),
            'doble_optin_global' => isset($_POST['doble_optin_global']),
            'tracking_aperturas' => isset($_POST['tracking_aperturas']),
            'tracking_clicks' => isset($_POST['tracking_clicks']),
            'pie_email_global' => wp_kses_post($_POST['pie_email_global']),
            'color_primario' => sanitize_hex_color($_POST['color_primario']),
            'color_secundario' => sanitize_hex_color($_POST['color_secundario']),
            'proveedor_smtp' => sanitize_key($_POST['proveedor_smtp']),
            'smtp_host' => sanitize_text_field($_POST['smtp_host']),
            'smtp_puerto' => absint($_POST['smtp_puerto']),
            'smtp_usuario' => sanitize_text_field($_POST['smtp_usuario']),
            'smtp_password' => $_POST['smtp_password'], // No sanitize para permitir caracteres especiales
            'smtp_encriptacion' => sanitize_key($_POST['smtp_encriptacion']),
        ];

        update_option('flavor_chat_ia_module_email_marketing', $new_settings);
        $settings = $new_settings;

        echo '<div class="notice notice-success is-dismissible"><p>' . __('Configuración guardada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    }
}
?>

<div class="wrap em-configuracion">
    <!-- Migas de pan -->
    <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
        <a href="<?php echo admin_url('admin.php?page=flavor-email-marketing'); ?>" style="color: #2271b1; text-decoration: none;">
            <span class="dashicons dashicons-email-alt" style="font-size: 14px; vertical-align: middle;"></span>
            <?php _e('Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <span style="color: #646970; margin: 0 5px;">›</span>
        <span style="color: #1d2327;"><?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </nav>

    <h1><?php _e('Configuración de Email Marketing', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

    <form method="post">
        <?php wp_nonce_field('em_save_settings', 'em_settings_nonce'); ?>

        <div class="em-config-tabs">
            <button type="button" class="em-config-tab active" data-tab="general"><?php _e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="em-config-tab" data-tab="smtp"><?php _e('SMTP', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="em-config-tab" data-tab="apariencia"><?php _e('Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <button type="button" class="em-config-tab" data-tab="paginas"><?php _e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>

        <!-- Tab General -->
        <div class="em-config-panel active" data-tab="general">
            <table class="form-table">
                <tr>
                    <th><label for="remitente_nombre"><?php _e('Nombre del remitente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="text" name="remitente_nombre" id="remitente_nombre" class="regular-text"
                               value="<?php echo esc_attr($settings['remitente_nombre']); ?>">
                        <p class="description"><?php _e('Nombre que aparece como remitente en los emails.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="remitente_email"><?php _e('Email del remitente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="email" name="remitente_email" id="remitente_email" class="regular-text"
                               value="<?php echo esc_attr($settings['remitente_email']); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="responder_a"><?php _e('Responder a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="email" name="responder_a" id="responder_a" class="regular-text"
                               value="<?php echo esc_attr($settings['responder_a']); ?>">
                        <p class="description"><?php _e('Email donde se recibirán las respuestas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="emails_por_hora"><?php _e('Límite de envíos por hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="number" name="emails_por_hora" id="emails_por_hora" class="small-text"
                               value="<?php echo esc_attr($settings['emails_por_hora']); ?>" min="1" max="1000">
                        <p class="description"><?php _e('Máximo de emails a enviar por hora (depende de tu proveedor).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Opciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="doble_optin_global" value="1"
                                <?php checked($settings['doble_optin_global']); ?>>
                            <?php _e('Requerir doble opt-in por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <br><br>
                        <label>
                            <input type="checkbox" name="tracking_aperturas" value="1"
                                <?php checked($settings['tracking_aperturas']); ?>>
                            <?php _e('Registrar aperturas de email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <br><br>
                        <label>
                            <input type="checkbox" name="tracking_clicks" value="1"
                                <?php checked($settings['tracking_clicks']); ?>>
                            <?php _e('Registrar clicks en enlaces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tab SMTP -->
        <div class="em-config-panel" data-tab="smtp" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="proveedor_smtp"><?php _e('Proveedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <select name="proveedor_smtp" id="proveedor_smtp">
                            <option value="<?php echo esc_attr__('wp_mail', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($settings['proveedor_smtp'], 'wp_mail'); ?>>
                                <?php _e('WordPress (wp_mail)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </option>
                            <option value="<?php echo esc_attr__('smtp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($settings['proveedor_smtp'], 'smtp'); ?>>
                                <?php _e('SMTP personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>

            <div class="em-smtp-config" style="<?php echo $settings['proveedor_smtp'] !== 'smtp' ? 'display:none;' : ''; ?>">
                <table class="form-table">
                    <tr>
                        <th><label for="smtp_host"><?php _e('Host SMTP', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="text" name="smtp_host" id="smtp_host" class="regular-text"
                                   value="<?php echo esc_attr($settings['smtp_host']); ?>"
                                   placeholder="<?php echo esc_attr__('smtp.ejemplo.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_puerto"><?php _e('Puerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="number" name="smtp_puerto" id="smtp_puerto" class="small-text"
                                   value="<?php echo esc_attr($settings['smtp_puerto']); ?>">
                            <p class="description"><?php _e('Común: 587 (TLS), 465 (SSL), 25 (sin encriptación)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_usuario"><?php _e('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="text" name="smtp_usuario" id="smtp_usuario" class="regular-text"
                                   value="<?php echo esc_attr($settings['smtp_usuario']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_password"><?php _e('Contraseña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="password" name="smtp_password" id="smtp_password" class="regular-text"
                                   value="<?php echo esc_attr($settings['smtp_password']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_encriptacion"><?php _e('Encriptación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <select name="smtp_encriptacion" id="smtp_encriptacion">
                                <option value="" <?php selected($settings['smtp_encriptacion'], ''); ?>><?php _e('Ninguna', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('tls', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($settings['smtp_encriptacion'], 'tls'); ?>><?php echo esc_html__('TLS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                <option value="<?php echo esc_attr__('ssl', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" <?php selected($settings['smtp_encriptacion'], 'ssl'); ?>><?php echo esc_html__('SSL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th></th>
                        <td>
                            <button type="button" class="button em-btn-test-smtp">
                                <?php _e('Probar conexión SMTP', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <span class="em-smtp-test-result"></span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Tab Apariencia -->
        <div class="em-config-panel" data-tab="apariencia" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><label for="color_primario"><?php _e('Color primario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="color" name="color_primario" id="color_primario"
                               value="<?php echo esc_attr($settings['color_primario']); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="color_secundario"><?php _e('Color secundario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <input type="color" name="color_secundario" id="color_secundario"
                               value="<?php echo esc_attr($settings['color_secundario']); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="pie_email_global"><?php _e('Pie de email global', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                    <td>
                        <textarea name="pie_email_global" id="pie_email_global" rows="5" class="large-text"><?php echo esc_textarea($settings['pie_email_global']); ?></textarea>
                        <p class="description"><?php _e('Texto que aparece al final de todos los emails. Puedes incluir HTML.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Tab Páginas -->
        <div class="em-config-panel" data-tab="paginas" style="display:none;">
            <p class="description">
                <?php _e('Selecciona o crea las páginas que contendrán los shortcodes necesarios.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th><?php _e('Página de confirmación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'pagina_confirmacion',
                            'show_option_none' => __('Seleccionar página', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'selected' => get_option('flavor_em_pagina_confirmacion'),
                        ]);
                        ?>
                        <p class="description">
                            <?php _e('Shortcode:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <code><?php echo esc_html__('[em_confirmar_suscripcion]', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Página de preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'pagina_preferencias',
                            'show_option_none' => __('Seleccionar página', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'selected' => get_option('flavor_em_pagina_preferencias'),
                        ]);
                        ?>
                        <p class="description">
                            <?php _e('Shortcode:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <code><?php echo esc_html__('[em_preferencias]', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Página de baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php
                        wp_dropdown_pages([
                            'name' => 'pagina_baja',
                            'show_option_none' => __('Seleccionar página', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'selected' => get_option('flavor_em_pagina_baja'),
                        ]);
                        ?>
                        <p class="description">
                            <?php _e('Shortcode:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <code><?php echo esc_html__('[em_darse_baja]', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(__('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tabs
    $('.em-config-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.em-config-tab').removeClass('active');
        $(this).addClass('active');
        $('.em-config-panel').hide();
        $('.em-config-panel[data-tab="' + tab + '"]').show();
    });

    // Mostrar/ocultar config SMTP
    $('#proveedor_smtp').on('change', function() {
        if ($(this).val() === 'smtp') {
            $('.em-smtp-config').show();
        } else {
            $('.em-smtp-config').hide();
        }
    });
});
</script>
