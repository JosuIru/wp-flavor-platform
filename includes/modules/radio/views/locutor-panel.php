<?php
/**
 * Vista: Panel del Locutor
 *
 * Panel para locutores con instrucciones de conexión,
 * estado de emisión y herramientas.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$user = wp_get_current_user();

// Obtener configuración del módulo radio
$radio_settings = flavor_get_main_settings();
$radio_config = $radio_settings['radio'] ?? [];

// Verificar si tiene permisos de locutor
$es_locutor = current_user_can('edit_posts') || in_array('flavor_radio_locutor', $user->roles);

// Obtener programas del locutor
global $wpdb;
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
$mis_programas = [];

if (Flavor_Platform_Helpers::tabla_existe($tabla_programas)) {
    $mis_programas = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_programas WHERE locutor_id = %d AND estado = 'activo'",
        $user_id
    ));
}

// Generar credenciales de streaming (si hay servidor configurado)
$stream_server = $radio_config['stream_server'] ?? '';
$stream_port = $radio_config['stream_port'] ?? '8000';
$stream_password = $radio_config['stream_password'] ?? '';
$stream_mount = $radio_config['stream_mount'] ?? '/live';

// Generar token único para el locutor
$locutor_token = get_user_meta($user_id, 'flavor_radio_stream_token', true);
if (empty($locutor_token)) {
    $locutor_token = wp_generate_password(24, false);
    update_user_meta($user_id, 'flavor_radio_stream_token', $locutor_token);
}
?>

<div class="wrap locutor-panel">
    <h1>
        <span class="dashicons dashicons-microphone"></span>
        <?php _e('Panel del Locutor', 'flavor-platform'); ?>
    </h1>

    <?php if (!$es_locutor): ?>
    <div class="notice notice-warning">
        <p><?php _e('No tienes permisos de locutor. Contacta con el administrador para obtener acceso.', 'flavor-platform'); ?></p>
    </div>
    <?php return; endif; ?>

    <!-- Estado actual -->
    <div class="locutor-status-panel" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin: 0 0 10px 0; color: #fff;">
                    <?php printf(__('¡Hola, %s!', 'flavor-platform'), esc_html($user->display_name)); ?>
                </h2>
                <p style="margin: 0; opacity: 0.9;">
                    <?php echo count($mis_programas); ?> <?php _e('programa(s) activo(s)', 'flavor-platform'); ?>
                </p>
            </div>
            <div id="status-indicator" style="text-align: center;">
                <div style="width: 20px; height: 20px; background: #ff4444; border-radius: 50%; margin: 0 auto 5px;"></div>
                <span style="font-size: 12px;"><?php _e('Desconectado', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Columna principal -->
        <div>
            <!-- Instrucciones de conexión -->
            <div class="card" style="background: #fff; padding: 25px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <?php _e('Cómo conectarte para emitir en vivo', 'flavor-platform'); ?>
                </h2>

                <!-- Tabs de software -->
                <div class="software-tabs" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <button class="button software-tab active" data-software="butt">BUTT</button>
                    <button class="button software-tab" data-software="obs">OBS Studio</button>
                    <button class="button software-tab" data-software="mixxx">Mixxx</button>
                    <button class="button software-tab" data-software="otro">Otro software</button>
                </div>

                <!-- Instrucciones BUTT -->
                <div id="instructions-butt" class="software-instructions">
                    <h3>BUTT (Broadcast Using This Tool)</h3>
                    <p><?php _e('BUTT es un software gratuito y simple para transmitir audio.', 'flavor-platform'); ?></p>

                    <ol style="line-height: 2;">
                        <li>
                            <strong><?php _e('Descargar BUTT:', 'flavor-platform'); ?></strong>
                            <a href="https://danielnoethen.de/butt/" target="_blank" class="button button-small">
                                <?php _e('Descargar BUTT', 'flavor-platform'); ?> <span class="dashicons dashicons-external"></span>
                            </a>
                        </li>
                        <li>
                            <strong><?php _e('Instalar y abrir BUTT', 'flavor-platform'); ?></strong>
                        </li>
                        <li>
                            <strong><?php _e('Ir a Settings > Main:', 'flavor-platform'); ?></strong>
                            <ul style="margin-top: 10px;">
                                <li>Server Type: <code>Icecast</code></li>
                                <li>Address: <code id="copy-server"><?php echo esc_html($stream_server ?: 'tu-servidor.com'); ?></code>
                                    <button class="button button-small copy-btn" data-target="copy-server"><?php _e('Copiar', 'flavor-platform'); ?></button>
                                </li>
                                <li>Port: <code id="copy-port"><?php echo esc_html($stream_port); ?></code></li>
                                <li>Password: <code id="copy-password"><?php echo esc_html($stream_password ?: $locutor_token); ?></code>
                                    <button class="button button-small copy-btn" data-target="copy-password"><?php _e('Copiar', 'flavor-platform'); ?></button>
                                </li>
                                <li>Mount: <code id="copy-mount"><?php echo esc_html($stream_mount); ?></code></li>
                            </ul>
                        </li>
                        <li>
                            <strong><?php _e('En Audio Settings:', 'flavor-platform'); ?></strong>
                            <ul style="margin-top: 10px;">
                                <li><?php _e('Selecciona tu micrófono como dispositivo de entrada', 'flavor-platform'); ?></li>
                                <li>Codec: <code>MP3</code></li>
                                <li>Bitrate: <code>128 kbps</code> <?php _e('(recomendado)', 'flavor-platform'); ?></li>
                            </ul>
                        </li>
                        <li>
                            <strong><?php _e('¡Pulsa el botón Play para empezar a emitir!', 'flavor-platform'); ?></strong>
                        </li>
                    </ol>

                    <?php if (!empty($stream_server)): ?>
                    <div style="margin-top: 20px;">
                        <a href="#" class="button button-primary" id="download-butt-config">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Descargar archivo de configuración', 'flavor-platform'); ?>
                        </a>
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">
                            <?php _e('Descarga este archivo e impórtalo en BUTT para configurar automáticamente.', 'flavor-platform'); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Instrucciones OBS -->
                <div id="instructions-obs" class="software-instructions" style="display: none;">
                    <h3>OBS Studio</h3>
                    <p><?php _e('OBS es ideal si quieres transmitir video junto con audio.', 'flavor-platform'); ?></p>

                    <ol style="line-height: 2;">
                        <li>
                            <strong><?php _e('Descargar OBS:', 'flavor-platform'); ?></strong>
                            <a href="https://obsproject.com/" target="_blank" class="button button-small">
                                <?php _e('Descargar OBS', 'flavor-platform'); ?> <span class="dashicons dashicons-external"></span>
                            </a>
                        </li>
                        <li>
                            <strong><?php _e('Ir a Ajustes > Emisión:', 'flavor-platform'); ?></strong>
                            <ul style="margin-top: 10px;">
                                <li>Servicio: <code>Personalizado</code></li>
                                <li>Servidor: <code>icecast://<?php echo esc_html($stream_server ?: 'tu-servidor.com'); ?>:<?php echo esc_html($stream_port); ?><?php echo esc_html($stream_mount); ?></code></li>
                                <li>Clave de retransmisión: <code><?php echo esc_html($stream_password ?: $locutor_token); ?></code></li>
                            </ul>
                        </li>
                        <li>
                            <strong><?php _e('Ajustes > Salida:', 'flavor-platform'); ?></strong>
                            <ul style="margin-top: 10px;">
                                <li>Modo de salida: <code>Avanzado</code></li>
                                <li>Codificador de audio: <code>AAC</code> o <code>MP3</code></li>
                                <li>Bitrate de audio: <code>128 kbps</code></li>
                            </ul>
                        </li>
                        <li>
                            <strong><?php _e('¡Pulsa "Iniciar transmisión"!', 'flavor-platform'); ?></strong>
                        </li>
                    </ol>
                </div>

                <!-- Instrucciones Mixxx -->
                <div id="instructions-mixxx" class="software-instructions" style="display: none;">
                    <h3>Mixxx</h3>
                    <p><?php _e('Mixxx es un software DJ gratuito con soporte de streaming integrado.', 'flavor-platform'); ?></p>

                    <ol style="line-height: 2;">
                        <li>
                            <strong><?php _e('Descargar Mixxx:', 'flavor-platform'); ?></strong>
                            <a href="https://mixxx.org/" target="_blank" class="button button-small">
                                <?php _e('Descargar Mixxx', 'flavor-platform'); ?> <span class="dashicons dashicons-external"></span>
                            </a>
                        </li>
                        <li>
                            <strong><?php _e('Ir a Preferencias > Emisión en directo:', 'flavor-platform'); ?></strong>
                            <ul style="margin-top: 10px;">
                                <li>Tipo: <code>Icecast 2</code></li>
                                <li>Servidor: <code><?php echo esc_html($stream_server ?: 'tu-servidor.com'); ?></code></li>
                                <li>Puerto: <code><?php echo esc_html($stream_port); ?></code></li>
                                <li>Punto de montaje: <code><?php echo esc_html($stream_mount); ?></code></li>
                                <li>Contraseña: <code><?php echo esc_html($stream_password ?: $locutor_token); ?></code></li>
                            </ul>
                        </li>
                        <li>
                            <strong><?php _e('Activa "Emisión en directo" desde el menú o el panel', 'flavor-platform'); ?></strong>
                        </li>
                    </ol>
                </div>

                <!-- Instrucciones Otro -->
                <div id="instructions-otro" class="software-instructions" style="display: none;">
                    <h3><?php _e('Configuración genérica', 'flavor-platform'); ?></h3>
                    <p><?php _e('Usa estos datos para configurar cualquier software de streaming:', 'flavor-platform'); ?></p>

                    <table class="wp-list-table widefat" style="margin-top: 15px;">
                        <tr>
                            <th style="width: 150px;"><?php _e('Servidor', 'flavor-platform'); ?></th>
                            <td><code><?php echo esc_html($stream_server ?: 'No configurado'); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Puerto', 'flavor-platform'); ?></th>
                            <td><code><?php echo esc_html($stream_port); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Mount Point', 'flavor-platform'); ?></th>
                            <td><code><?php echo esc_html($stream_mount); ?></code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Usuario', 'flavor-platform'); ?></th>
                            <td><code>source</code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Contraseña', 'flavor-platform'); ?></th>
                            <td>
                                <code id="generic-password"><?php echo esc_html($stream_password ?: $locutor_token); ?></code>
                                <button class="button button-small copy-btn" data-target="generic-password"><?php _e('Copiar', 'flavor-platform'); ?></button>
                            </td>
                        </tr>
                        <tr>
                            <th><?php _e('Tipo', 'flavor-platform'); ?></th>
                            <td><code>Icecast 2</code></td>
                        </tr>
                        <tr>
                            <th><?php _e('Formato', 'flavor-platform'); ?></th>
                            <td><code>MP3 128kbps</code> <?php _e('(recomendado)', 'flavor-platform'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Sin servidor configurado -->
            <?php if (empty($stream_server)): ?>
            <div class="notice notice-warning" style="margin: 0 0 20px;">
                <h3 style="margin-top: 0;">
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Servidor de streaming no configurado', 'flavor-platform'); ?>
                </h3>
                <p><?php _e('El administrador aún no ha configurado un servidor de streaming. Mientras tanto, puedes:', 'flavor-platform'); ?></p>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><?php _e('Subir programas grabados a la biblioteca de audio', 'flavor-platform'); ?></li>
                    <li><?php _e('Preparar tus playlists para cuando el streaming esté disponible', 'flavor-platform'); ?></li>
                    <li><?php _e('Usar servicios gratuitos como Listen2MyRadio o Azuracast', 'flavor-platform'); ?></li>
                </ul>

                <h4><?php _e('Opciones de servidor de streaming gratuito/económico:', 'flavor-platform'); ?></h4>
                <ul style="list-style: disc; margin-left: 20px;">
                    <li><a href="https://www.azuracast.com/" target="_blank">Azuracast</a> - <?php _e('Gratuito, auto-hospedado (necesitas VPS)', 'flavor-platform'); ?></li>
                    <li><a href="https://www.listen2myradio.com/" target="_blank">Listen2MyRadio</a> - <?php _e('Gratuito con anuncios', 'flavor-platform'); ?></li>
                    <li><a href="https://www.shoutcast.com/" target="_blank">Shoutcast Hosting</a> - <?php _e('Desde $4.95/mes', 'flavor-platform'); ?></li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Mis programas -->
            <div class="card" style="background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h2 style="margin-top: 0;">
                    <span class="dashicons dashicons-playlist-audio"></span>
                    <?php _e('Mis Programas', 'flavor-platform'); ?>
                </h2>

                <?php if (empty($mis_programas)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    <span class="dashicons dashicons-microphone" style="font-size: 48px; display: block; margin-bottom: 10px; opacity: 0.3;"></span>
                    <?php _e('No tienes programas asignados', 'flavor-platform'); ?>
                </p>
                <?php else: ?>
                <div style="display: grid; gap: 15px;">
                    <?php foreach ($mis_programas as $programa): ?>
                    <div style="display: flex; gap: 15px; padding: 15px; background: #f9f9f9; border-radius: 8px; align-items: center;">
                        <?php if (!empty($programa->imagen_url)): ?>
                        <img src="<?php echo esc_url($programa->imagen_url); ?>" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <h4 style="margin: 0 0 5px 0;"><?php echo esc_html($programa->nombre); ?></h4>
                            <p style="margin: 0; font-size: 13px; color: #666;">
                                <?php echo esc_html($programa->categoria); ?>
                                <?php if (!empty($programa->hora_inicio)): ?>
                                    · <?php echo esc_html(substr($programa->hora_inicio, 0, 5)); ?>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <span style="padding: 5px 10px; background: #e8f5e9; color: #2e7d32; border-radius: 4px; font-size: 12px;">
                                <?php echo ucfirst($programa->estado); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Columna lateral -->
        <div>
            <!-- Herramientas rápidas -->
            <div class="card" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0;"><?php _e('Herramientas', 'flavor-platform'); ?></h3>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <a href="<?php echo admin_url('admin.php?page=flavor-radio-media'); ?>" class="button">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Subir Audio', 'flavor-platform'); ?>
                    </a>
                    <a href="<?php echo admin_url('admin.php?page=flavor-radio-programacion'); ?>" class="button">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <?php _e('Ver Programación', 'flavor-platform'); ?>
                    </a>
                    <a href="<?php echo Flavor_Platform_Helpers::get_action_url('radio', ''); ?>" class="button" target="_blank">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Ver Portal Público', 'flavor-platform'); ?>
                    </a>
                </div>
            </div>

            <!-- Test de audio -->
            <div class="card" style="background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0;"><?php _e('Test de Micrófono', 'flavor-platform'); ?></h3>
                <p style="font-size: 13px; color: #666;"><?php _e('Comprueba que tu micrófono funciona correctamente', 'flavor-platform'); ?></p>

                <div id="mic-test-container" style="text-align: center; padding: 20px;">
                    <button id="start-mic-test" class="button button-primary">
                        <span class="dashicons dashicons-microphone"></span>
                        <?php _e('Probar Micrófono', 'flavor-platform'); ?>
                    </button>
                    <div id="mic-visualizer" style="height: 50px; background: #f0f0f0; border-radius: 4px; margin-top: 15px; display: none;">
                        <canvas id="mic-canvas" style="width: 100%; height: 100%;"></canvas>
                    </div>
                    <p id="mic-status" style="font-size: 12px; color: #666; margin-top: 10px;"></p>
                </div>
            </div>

            <!-- Soporte -->
            <div class="card" style="background: #f0f7ff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin-top: 0;">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php _e('¿Necesitas ayuda?', 'flavor-platform'); ?>
                </h3>
                <p style="font-size: 13px;"><?php _e('Si tienes problemas para conectarte, contacta con el equipo técnico.', 'flavor-platform'); ?></p>
                <a href="mailto:<?php echo get_option('admin_email'); ?>" class="button">
                    <?php _e('Contactar Soporte', 'flavor-platform'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.software-tab.active {
    background: #2271b1;
    color: #fff;
    border-color: #2271b1;
}

.copy-btn {
    margin-left: 5px !important;
}

.copy-btn.copied {
    background: #00a32a;
    color: #fff;
    border-color: #00a32a;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tabs de software
    $('.software-tab').on('click', function() {
        $('.software-tab').removeClass('active');
        $(this).addClass('active');

        const software = $(this).data('software');
        $('.software-instructions').hide();
        $('#instructions-' + software).show();
    });

    // Copiar al portapapeles
    $('.copy-btn').on('click', function() {
        const targetId = $(this).data('target');
        const text = $('#' + targetId).text();

        navigator.clipboard.writeText(text).then(() => {
            const btn = $(this);
            btn.addClass('copied').text('<?php echo esc_js(__('¡Copiado!', 'flavor-platform')); ?>');
            setTimeout(() => {
                btn.removeClass('copied').text('<?php echo esc_js(__('Copiar', 'flavor-platform')); ?>');
            }, 2000);
        });
    });

    // Test de micrófono
    let audioContext, analyser, microphone, animationId;

    $('#start-mic-test').on('click', async function() {
        const btn = $(this);
        const status = $('#mic-status');
        const visualizer = $('#mic-visualizer');
        const canvas = document.getElementById('mic-canvas');
        const ctx = canvas.getContext('2d');

        if (audioContext) {
            // Detener
            audioContext.close();
            audioContext = null;
            cancelAnimationFrame(animationId);
            visualizer.hide();
            btn.html('<span class="dashicons dashicons-microphone"></span> <?php echo esc_js(__('Probar Micrófono', 'flavor-platform')); ?>');
            status.text('');
            return;
        }

        try {
            status.text('<?php echo esc_js(__('Solicitando acceso al micrófono...', 'flavor-platform')); ?>');

            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });

            audioContext = new (window.AudioContext || window.webkitAudioContext)();
            analyser = audioContext.createAnalyser();
            microphone = audioContext.createMediaStreamSource(stream);

            analyser.fftSize = 256;
            microphone.connect(analyser);

            visualizer.show();
            canvas.width = canvas.offsetWidth;
            canvas.height = canvas.offsetHeight;

            btn.html('<span class="dashicons dashicons-no"></span> <?php echo esc_js(__('Detener Test', 'flavor-platform')); ?>');
            status.text('<?php echo esc_js(__('¡Micrófono funcionando! Habla para ver la visualización.', 'flavor-platform')); ?>');
            status.css('color', '#00a32a');

            function draw() {
                animationId = requestAnimationFrame(draw);

                const bufferLength = analyser.frequencyBinCount;
                const dataArray = new Uint8Array(bufferLength);
                analyser.getByteFrequencyData(dataArray);

                ctx.fillStyle = '#f0f0f0';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                const barWidth = (canvas.width / bufferLength) * 2.5;
                let x = 0;

                for (let i = 0; i < bufferLength; i++) {
                    const barHeight = (dataArray[i] / 255) * canvas.height;

                    ctx.fillStyle = `rgb(34, 113, 177)`;
                    ctx.fillRect(x, canvas.height - barHeight, barWidth, barHeight);

                    x += barWidth + 1;
                }
            }

            draw();

        } catch (err) {
            status.text('<?php echo esc_js(__('Error: No se pudo acceder al micrófono', 'flavor-platform')); ?>');
            status.css('color', '#d63638');
            console.error(err);
        }
    });

    // Descargar config de BUTT
    $('#download-butt-config').on('click', function(e) {
        e.preventDefault();

        const config = `[main]
server_type=0
server_ip=<?php echo esc_js($stream_server); ?>
server_port=<?php echo esc_js($stream_port); ?>
icecast_user=source
icecast_pass=<?php echo esc_js($stream_password ?: $locutor_token); ?>
icecast_mountpoint=<?php echo esc_js($stream_mount); ?>
song_title=1
song_file=0
song_path=

[audio]
codec=mp3
bitrate=128
samplerate=44100
stereo=1

[stream]
connect_at_startup=0`;

        const blob = new Blob([config], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'radio-config.butt';
        a.click();
        URL.revokeObjectURL(url);
    });
});
</script>
