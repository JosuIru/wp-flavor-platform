<?php
/**
 * Vista de Configuración - Módulo Podcast
 *
 * @package FlavorChatIA
 * @subpackage Podcast
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion = get_option('flavor_podcast_settings', []);
$configuracion_default = [
    'nombre_podcast' => get_bloginfo('name') . ' Podcast',
    'descripcion' => '',
    'autor' => get_bloginfo('name'),
    'email_contacto' => get_option('admin_email'),
    'imagen_cover' => '',
    'idioma' => 'es',
    'categorias_itunes' => [],
    'explicito' => false,
    'permitir_comentarios' => true,
    'permitir_descargas' => true,
    'notificar_nuevos_episodios' => true,
    'formato_audio_preferido' => 'mp3',
    'calidad_audio' => '192',
    'mostrar_transcripciones' => true,
    'integracion_spotify' => '',
    'integracion_apple' => '',
    'integracion_google' => '',
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

$mensaje_guardado = '';
if (isset($_POST['guardar_config_podcast']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_podcast')) {
    $nueva_config = [
        'nombre_podcast' => sanitize_text_field($_POST['nombre_podcast'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'autor' => sanitize_text_field($_POST['autor'] ?? ''),
        'email_contacto' => sanitize_email($_POST['email_contacto'] ?? ''),
        'imagen_cover' => esc_url_raw($_POST['imagen_cover'] ?? ''),
        'idioma' => sanitize_text_field($_POST['idioma'] ?? 'es'),
        'explicito' => isset($_POST['explicito']),
        'permitir_comentarios' => isset($_POST['permitir_comentarios']),
        'permitir_descargas' => isset($_POST['permitir_descargas']),
        'notificar_nuevos_episodios' => isset($_POST['notificar_nuevos_episodios']),
        'formato_audio_preferido' => sanitize_text_field($_POST['formato_audio_preferido'] ?? 'mp3'),
        'calidad_audio' => sanitize_text_field($_POST['calidad_audio'] ?? '192'),
        'mostrar_transcripciones' => isset($_POST['mostrar_transcripciones']),
        'integracion_spotify' => esc_url_raw($_POST['integracion_spotify'] ?? ''),
        'integracion_apple' => esc_url_raw($_POST['integracion_apple'] ?? ''),
        'integracion_google' => esc_url_raw($_POST['integracion_google'] ?? ''),
    ];

    update_option('flavor_podcast_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', 'flavor-chat-ia');
}
?>

<div class="wrap flavor-podcast-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php esc_html_e('Configuración de Podcast', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_podcast'); ?>

        <div class="dm-config-grid">
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-microphone"></span> <?php esc_html_e('Información General', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="nombre_podcast"><?php esc_html_e('Nombre del Podcast', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="nombre_podcast" name="nombre_podcast" value="<?php echo esc_attr($configuracion['nombre_podcast']); ?>">
                    </div>
                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="autor"><?php esc_html_e('Autor', 'flavor-chat-ia'); ?></label>
                            <input type="text" id="autor" name="autor" value="<?php echo esc_attr($configuracion['autor']); ?>">
                        </div>
                        <div class="dm-form-group">
                            <label for="idioma"><?php esc_html_e('Idioma', 'flavor-chat-ia'); ?></label>
                            <select id="idioma" name="idioma">
                                <option value="es" <?php selected($configuracion['idioma'], 'es'); ?>>Español</option>
                                <option value="en" <?php selected($configuracion['idioma'], 'en'); ?>>English</option>
                                <option value="eu" <?php selected($configuracion['idioma'], 'eu'); ?>>Euskara</option>
                                <option value="ca" <?php selected($configuracion['idioma'], 'ca'); ?>>Català</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Opciones de Audio', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="formato_audio_preferido"><?php esc_html_e('Formato de Audio', 'flavor-chat-ia'); ?></label>
                            <select id="formato_audio_preferido" name="formato_audio_preferido">
                                <option value="mp3" <?php selected($configuracion['formato_audio_preferido'], 'mp3'); ?>>MP3</option>
                                <option value="aac" <?php selected($configuracion['formato_audio_preferido'], 'aac'); ?>>AAC</option>
                                <option value="ogg" <?php selected($configuracion['formato_audio_preferido'], 'ogg'); ?>>OGG</option>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="calidad_audio"><?php esc_html_e('Calidad de Audio', 'flavor-chat-ia'); ?></label>
                            <select id="calidad_audio" name="calidad_audio">
                                <option value="128" <?php selected($configuracion['calidad_audio'], '128'); ?>>128 kbps</option>
                                <option value="192" <?php selected($configuracion['calidad_audio'], '192'); ?>>192 kbps</option>
                                <option value="256" <?php selected($configuracion['calidad_audio'], '256'); ?>>256 kbps</option>
                                <option value="320" <?php selected($configuracion['calidad_audio'], '320'); ?>>320 kbps</option>
                            </select>
                        </div>
                    </div>
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_descargas" value="1" <?php checked($configuracion['permitir_descargas']); ?>>
                            <span><?php esc_html_e('Permitir descarga de episodios', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_transcripciones" value="1" <?php checked($configuracion['mostrar_transcripciones']); ?>>
                            <span><?php esc_html_e('Mostrar transcripciones de episodios', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-share"></span> <?php esc_html_e('Plataformas de Distribución', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="integracion_spotify"><span class="dashicons dashicons-spotify"></span> Spotify</label>
                        <input type="url" id="integracion_spotify" name="integracion_spotify" value="<?php echo esc_url($configuracion['integracion_spotify']); ?>" placeholder="https://open.spotify.com/show/...">
                    </div>
                    <div class="dm-form-group">
                        <label for="integracion_apple"><span class="dashicons dashicons-apple"></span> Apple Podcasts</label>
                        <input type="url" id="integracion_apple" name="integracion_apple" value="<?php echo esc_url($configuracion['integracion_apple']); ?>" placeholder="https://podcasts.apple.com/...">
                    </div>
                    <div class="dm-form-group">
                        <label for="integracion_google"><span class="dashicons dashicons-google"></span> Google Podcasts</label>
                        <input type="url" id="integracion_google" name="integracion_google" value="<?php echo esc_url($configuracion['integracion_google']); ?>" placeholder="https://podcasts.google.com/...">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Opciones de Visualización', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_comentarios" value="1" <?php checked($configuracion['permitir_comentarios']); ?>>
                            <span><?php esc_html_e('Permitir comentarios en episodios', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_nuevos_episodios" value="1" <?php checked($configuracion['notificar_nuevos_episodios']); ?>>
                            <span><?php esc_html_e('Notificar a suscriptores de nuevos episodios', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="explicito" value="1" <?php checked($configuracion['explicito']); ?>>
                            <span><?php esc_html_e('Contenido explícito (para iTunes)', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_podcast" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-podcast-config { max-width: 1200px; }
.dm-config-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
@media (max-width: 1024px) { .dm-config-grid { grid-template-columns: 1fr; } }
.dm-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.dm-card__header { padding: 15px 20px; border-bottom: 1px solid #f0f0f1; }
.dm-card__header h3 { margin: 0; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.dm-card__header .dashicons { color: #2271b1; }
.dm-card__body { padding: 20px; }
.dm-form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 15px; }
.dm-form-group { margin-bottom: 15px; }
.dm-form-group:last-child { margin-bottom: 0; }
.dm-form-group label { display: flex; align-items: center; gap: 5px; font-weight: 600; font-size: 13px; margin-bottom: 5px; }
.dm-form-group input[type="text"], .dm-form-group input[type="url"], .dm-form-group select, .dm-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 14px; }
.dm-form-group textarea { resize: vertical; }
.dm-checkbox-group { display: flex; flex-direction: column; gap: 12px; }
.dm-checkbox { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; }
.dm-checkbox input { margin-top: 3px; }
.dm-checkbox span { font-size: 13px; }
.dm-form-actions { margin-top: 25px; padding: 20px; background: #f6f7f7; border-radius: 8px; text-align: center; }
.dm-form-actions .button-hero { display: inline-flex; align-items: center; gap: 8px; padding: 10px 30px; font-size: 14px; }
.dm-form-actions .dashicons { font-size: 18px; width: 18px; height: 18px; }
</style>
