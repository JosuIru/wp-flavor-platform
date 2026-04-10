<?php
/**
 * Vista de Configuración - Módulo Radio Comunitaria
 *
 * @package FlavorPlatform
 * @subpackage Radio
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener configuración actual
$configuracion = get_option('flavor_radio_settings', []);
$configuracion_default = [
    'nombre_emisora' => '',
    'frecuencia' => '',
    'stream_url' => '',
    'stream_backup_url' => '',
    'logo_url' => '',
    'descripcion' => '',
    'email_contacto' => get_option('admin_email'),
    'telefono' => '',
    'direccion' => '',
    'redes_sociales' => [
        'facebook' => '',
        'twitter' => '',
        'instagram' => '',
        'youtube' => '',
    ],
    'horario_emision' => [
        'inicio' => '06:00',
        'fin' => '24:00',
    ],
    'permitir_podcast' => true,
    'permitir_comentarios' => true,
    'calidad_stream' => '128',
    'zona_horaria' => wp_timezone_string(),
    'mostrar_programa_actual' => true,
    'mostrar_proximo_programa' => true,
    'widget_chat_activo' => false,
    'notificar_nuevos_programas' => true,
    'email_notificaciones' => get_option('admin_email'),
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

// Procesar formulario
$mensaje_guardado = '';
if (isset($_POST['guardar_config_radio']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_radio')) {
    $nueva_config = [
        'nombre_emisora' => sanitize_text_field($_POST['nombre_emisora'] ?? ''),
        'frecuencia' => sanitize_text_field($_POST['frecuencia'] ?? ''),
        'stream_url' => esc_url_raw($_POST['stream_url'] ?? ''),
        'stream_backup_url' => esc_url_raw($_POST['stream_backup_url'] ?? ''),
        'logo_url' => esc_url_raw($_POST['logo_url'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'email_contacto' => sanitize_email($_POST['email_contacto'] ?? ''),
        'telefono' => sanitize_text_field($_POST['telefono'] ?? ''),
        'direccion' => sanitize_textarea_field($_POST['direccion'] ?? ''),
        'redes_sociales' => [
            'facebook' => esc_url_raw($_POST['redes_facebook'] ?? ''),
            'twitter' => sanitize_text_field($_POST['redes_twitter'] ?? ''),
            'instagram' => sanitize_text_field($_POST['redes_instagram'] ?? ''),
            'youtube' => esc_url_raw($_POST['redes_youtube'] ?? ''),
        ],
        'horario_emision' => [
            'inicio' => sanitize_text_field($_POST['horario_inicio'] ?? '06:00'),
            'fin' => sanitize_text_field($_POST['horario_fin'] ?? '24:00'),
        ],
        'permitir_podcast' => isset($_POST['permitir_podcast']),
        'permitir_comentarios' => isset($_POST['permitir_comentarios']),
        'calidad_stream' => sanitize_text_field($_POST['calidad_stream'] ?? '128'),
        'zona_horaria' => sanitize_text_field($_POST['zona_horaria'] ?? wp_timezone_string()),
        'mostrar_programa_actual' => isset($_POST['mostrar_programa_actual']),
        'mostrar_proximo_programa' => isset($_POST['mostrar_proximo_programa']),
        'widget_chat_activo' => isset($_POST['widget_chat_activo']),
        'notificar_nuevos_programas' => isset($_POST['notificar_nuevos_programas']),
        'email_notificaciones' => sanitize_email($_POST['email_notificaciones'] ?? ''),
    ];

    update_option('flavor_radio_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', 'flavor-platform');
}
?>

<div class="wrap flavor-radio-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Configuración de Radio', 'flavor-platform'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_radio'); ?>

        <div class="dm-config-grid">
            <!-- Información General -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-microphone"></span>
                        <?php esc_html_e('Información de la Emisora', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="nombre_emisora"><?php esc_html_e('Nombre de la Emisora', 'flavor-platform'); ?></label>
                            <input type="text" id="nombre_emisora" name="nombre_emisora"
                                   value="<?php echo esc_attr($configuracion['nombre_emisora']); ?>"
                                   placeholder="<?php esc_attr_e('Ej: Radio Comunitaria Local', 'flavor-platform'); ?>">
                        </div>
                        <div class="dm-form-group">
                            <label for="frecuencia"><?php esc_html_e('Frecuencia FM', 'flavor-platform'); ?></label>
                            <input type="text" id="frecuencia" name="frecuencia"
                                   value="<?php echo esc_attr($configuracion['frecuencia']); ?>"
                                   placeholder="<?php esc_attr_e('Ej: 98.5 FM', 'flavor-platform'); ?>">
                        </div>
                    </div>

                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-platform'); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  placeholder="<?php esc_attr_e('Breve descripción de la emisora...', 'flavor-platform'); ?>"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>

                    <div class="dm-form-group">
                        <label for="logo_url"><?php esc_html_e('Logo de la Emisora', 'flavor-platform'); ?></label>
                        <div class="dm-media-input">
                            <input type="url" id="logo_url" name="logo_url"
                                   value="<?php echo esc_url($configuracion['logo_url']); ?>"
                                   placeholder="https://...">
                            <button type="button" class="button" onclick="seleccionarImagen('logo_url')">
                                <span class="dashicons dashicons-upload"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Streaming -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-controls-volumeon"></span>
                        <?php esc_html_e('Configuración de Streaming', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="stream_url"><?php esc_html_e('URL del Stream Principal', 'flavor-platform'); ?></label>
                        <input type="url" id="stream_url" name="stream_url"
                               value="<?php echo esc_url($configuracion['stream_url']); ?>"
                               placeholder="<?php esc_attr_e('https://stream.ejemplo.com/radio', 'flavor-platform'); ?>">
                        <p class="description"><?php esc_html_e('URL del stream de audio en formato MP3, AAC u OGG.', 'flavor-platform'); ?></p>
                    </div>

                    <div class="dm-form-group">
                        <label for="stream_backup_url"><?php esc_html_e('URL de Respaldo', 'flavor-platform'); ?></label>
                        <input type="url" id="stream_backup_url" name="stream_backup_url"
                               value="<?php echo esc_url($configuracion['stream_backup_url']); ?>"
                               placeholder="<?php esc_attr_e('https://backup.ejemplo.com/radio', 'flavor-platform'); ?>">
                    </div>

                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="calidad_stream"><?php esc_html_e('Calidad de Audio', 'flavor-platform'); ?></label>
                            <select id="calidad_stream" name="calidad_stream">
                                <option value="64" <?php selected($configuracion['calidad_stream'], '64'); ?>>64 kbps</option>
                                <option value="96" <?php selected($configuracion['calidad_stream'], '96'); ?>>96 kbps</option>
                                <option value="128" <?php selected($configuracion['calidad_stream'], '128'); ?>>128 kbps</option>
                                <option value="192" <?php selected($configuracion['calidad_stream'], '192'); ?>>192 kbps</option>
                                <option value="256" <?php selected($configuracion['calidad_stream'], '256'); ?>>256 kbps</option>
                                <option value="320" <?php selected($configuracion['calidad_stream'], '320'); ?>>320 kbps</option>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="zona_horaria"><?php esc_html_e('Zona Horaria', 'flavor-platform'); ?></label>
                            <select id="zona_horaria" name="zona_horaria">
                                <?php
                                $zonas = timezone_identifiers_list();
                                foreach ($zonas as $zona) {
                                    printf(
                                        '<option value="%s" %s>%s</option>',
                                        esc_attr($zona),
                                        selected($configuracion['zona_horaria'], $zona, false),
                                        esc_html($zona)
                                    );
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="horario_inicio"><?php esc_html_e('Hora de Inicio', 'flavor-platform'); ?></label>
                            <input type="time" id="horario_inicio" name="horario_inicio"
                                   value="<?php echo esc_attr($configuracion['horario_emision']['inicio']); ?>">
                        </div>
                        <div class="dm-form-group">
                            <label for="horario_fin"><?php esc_html_e('Hora de Fin', 'flavor-platform'); ?></label>
                            <input type="time" id="horario_fin" name="horario_fin"
                                   value="<?php echo esc_attr($configuracion['horario_emision']['fin']); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contacto -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-phone"></span>
                        <?php esc_html_e('Información de Contacto', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="email_contacto"><?php esc_html_e('Email de Contacto', 'flavor-platform'); ?></label>
                            <input type="email" id="email_contacto" name="email_contacto"
                                   value="<?php echo esc_attr($configuracion['email_contacto']); ?>">
                        </div>
                        <div class="dm-form-group">
                            <label for="telefono"><?php esc_html_e('Teléfono', 'flavor-platform'); ?></label>
                            <input type="tel" id="telefono" name="telefono"
                                   value="<?php echo esc_attr($configuracion['telefono']); ?>">
                        </div>
                    </div>

                    <div class="dm-form-group">
                        <label for="direccion"><?php esc_html_e('Dirección', 'flavor-platform'); ?></label>
                        <textarea id="direccion" name="direccion" rows="2"><?php echo esc_textarea($configuracion['direccion']); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Redes Sociales -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-share"></span>
                        <?php esc_html_e('Redes Sociales', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="redes_facebook">
                                <span class="dashicons dashicons-facebook"></span> Facebook
                            </label>
                            <input type="url" id="redes_facebook" name="redes_facebook"
                                   value="<?php echo esc_url($configuracion['redes_sociales']['facebook']); ?>"
                                   placeholder="https://facebook.com/...">
                        </div>
                        <div class="dm-form-group">
                            <label for="redes_twitter">
                                <span class="dashicons dashicons-twitter"></span> X (Twitter)
                            </label>
                            <input type="text" id="redes_twitter" name="redes_twitter"
                                   value="<?php echo esc_attr($configuracion['redes_sociales']['twitter']); ?>"
                                   placeholder="@usuario">
                        </div>
                    </div>
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="redes_instagram">
                                <span class="dashicons dashicons-instagram"></span> Instagram
                            </label>
                            <input type="text" id="redes_instagram" name="redes_instagram"
                                   value="<?php echo esc_attr($configuracion['redes_sociales']['instagram']); ?>"
                                   placeholder="@usuario">
                        </div>
                        <div class="dm-form-group">
                            <label for="redes_youtube">
                                <span class="dashicons dashicons-youtube"></span> YouTube
                            </label>
                            <input type="url" id="redes_youtube" name="redes_youtube"
                                   value="<?php echo esc_url($configuracion['redes_sociales']['youtube']); ?>"
                                   placeholder="https://youtube.com/...">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Opciones del Widget -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <?php esc_html_e('Opciones de Visualización', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_programa_actual" value="1"
                                   <?php checked($configuracion['mostrar_programa_actual']); ?>>
                            <span><?php esc_html_e('Mostrar programa actual en el reproductor', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_proximo_programa" value="1"
                                   <?php checked($configuracion['mostrar_proximo_programa']); ?>>
                            <span><?php esc_html_e('Mostrar próximo programa', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_podcast" value="1"
                                   <?php checked($configuracion['permitir_podcast']); ?>>
                            <span><?php esc_html_e('Permitir acceso a podcasts de programas pasados', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_comentarios" value="1"
                                   <?php checked($configuracion['permitir_comentarios']); ?>>
                            <span><?php esc_html_e('Permitir comentarios en programas', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="widget_chat_activo" value="1"
                                   <?php checked($configuracion['widget_chat_activo']); ?>>
                            <span><?php esc_html_e('Activar chat en vivo durante emisiones', 'flavor-platform'); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Notificaciones -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-email"></span>
                        <?php esc_html_e('Notificaciones', 'flavor-platform'); ?>
                    </h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_nuevos_programas" value="1"
                                   <?php checked($configuracion['notificar_nuevos_programas']); ?>>
                            <span><?php esc_html_e('Notificar cuando se creen nuevos programas', 'flavor-platform'); ?></span>
                        </label>
                    </div>

                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="email_notificaciones"><?php esc_html_e('Email para Notificaciones', 'flavor-platform'); ?></label>
                        <input type="email" id="email_notificaciones" name="email_notificaciones"
                               value="<?php echo esc_attr($configuracion['email_notificaciones']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_radio" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', 'flavor-platform'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-radio-config {
    max-width: 1200px;
}

.dm-config-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 20px;
}

@media (max-width: 1024px) {
    .dm-config-grid {
        grid-template-columns: 1fr;
    }
}

.dm-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.dm-card__header {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f1;
}

.dm-card__header h3 {
    margin: 0;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.dm-card__header .dashicons {
    color: #2271b1;
}

.dm-card__body {
    padding: 20px;
}

.dm-form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 15px;
}

.dm-form-group {
    margin-bottom: 15px;
}

.dm-form-group:last-child {
    margin-bottom: 0;
}

.dm-form-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 5px;
}

.dm-form-group input[type="text"],
.dm-form-group input[type="email"],
.dm-form-group input[type="url"],
.dm-form-group input[type="tel"],
.dm-form-group input[type="time"],
.dm-form-group select,
.dm-form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    font-size: 14px;
}

.dm-form-group textarea {
    resize: vertical;
}

.dm-form-group .description {
    margin-top: 5px;
    font-size: 12px;
    color: #646970;
}

.dm-media-input {
    display: flex;
    gap: 8px;
}

.dm-media-input input {
    flex: 1;
}

.dm-checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.dm-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    cursor: pointer;
}

.dm-checkbox input {
    margin-top: 3px;
}

.dm-checkbox span {
    font-size: 13px;
}

.dm-form-actions {
    margin-top: 25px;
    padding: 20px;
    background: #f6f7f7;
    border-radius: 8px;
    text-align: center;
}

.dm-form-actions .button-hero {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 30px;
    font-size: 14px;
}

.dm-form-actions .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}
</style>

<script>
function seleccionarImagen(inputId) {
    if (typeof wp !== 'undefined' && wp.media) {
        var frame = wp.media({
            title: '<?php echo esc_js(__('Seleccionar imagen', 'flavor-platform')); ?>',
            button: { text: '<?php echo esc_js(__('Usar imagen', 'flavor-platform')); ?>' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });

        frame.open();
    } else {
        alert('<?php echo esc_js(__('El selector de medios no está disponible.', 'flavor-platform')); ?>');
    }
}
</script>
