<?php
/**
 * Vista de Configuración - Módulo Foros
 *
 * @package FlavorChatIA
 * @subpackage Foros
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion = get_option('flavor_foros_settings', []);
$configuracion_default = [
    'nombre_comunidad' => __('Foros de la Comunidad', 'flavor-chat-ia'),
    'descripcion' => '',
    'permitir_registro' => true,
    'requiere_aprobacion_post' => false,
    'permitir_anonimos' => false,
    'permitir_adjuntos' => true,
    'max_tamano_adjunto' => 5,
    'tipos_adjuntos' => 'jpg,jpeg,png,gif,pdf,doc,docx',
    'posts_por_pagina' => 20,
    'permitir_editar' => true,
    'minutos_edicion' => 30,
    'habilitar_reputacion' => true,
    'puntos_nuevo_tema' => 5,
    'puntos_respuesta' => 2,
    'puntos_mejor_respuesta' => 10,
    'habilitar_mencion' => true,
    'habilitar_firma' => true,
    'max_caracteres_firma' => 200,
    'notificar_respuestas' => true,
    'notificar_menciones' => true,
    'palabras_prohibidas' => '',
    'habilitar_moderacion' => true,
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

$mensaje_guardado = '';
if (isset($_POST['guardar_config_foros']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_foros')) {
    $nueva_config = [
        'nombre_comunidad' => sanitize_text_field($_POST['nombre_comunidad'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'permitir_registro' => isset($_POST['permitir_registro']),
        'requiere_aprobacion_post' => isset($_POST['requiere_aprobacion_post']),
        'permitir_anonimos' => isset($_POST['permitir_anonimos']),
        'permitir_adjuntos' => isset($_POST['permitir_adjuntos']),
        'max_tamano_adjunto' => absint($_POST['max_tamano_adjunto'] ?? 5),
        'tipos_adjuntos' => sanitize_text_field($_POST['tipos_adjuntos'] ?? ''),
        'posts_por_pagina' => absint($_POST['posts_por_pagina'] ?? 20),
        'permitir_editar' => isset($_POST['permitir_editar']),
        'minutos_edicion' => absint($_POST['minutos_edicion'] ?? 30),
        'habilitar_reputacion' => isset($_POST['habilitar_reputacion']),
        'puntos_nuevo_tema' => absint($_POST['puntos_nuevo_tema'] ?? 5),
        'puntos_respuesta' => absint($_POST['puntos_respuesta'] ?? 2),
        'puntos_mejor_respuesta' => absint($_POST['puntos_mejor_respuesta'] ?? 10),
        'habilitar_mencion' => isset($_POST['habilitar_mencion']),
        'habilitar_firma' => isset($_POST['habilitar_firma']),
        'max_caracteres_firma' => absint($_POST['max_caracteres_firma'] ?? 200),
        'notificar_respuestas' => isset($_POST['notificar_respuestas']),
        'notificar_menciones' => isset($_POST['notificar_menciones']),
        'palabras_prohibidas' => sanitize_textarea_field($_POST['palabras_prohibidas'] ?? ''),
        'habilitar_moderacion' => isset($_POST['habilitar_moderacion']),
    ];

    update_option('flavor_foros_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', 'flavor-chat-ia');
}
?>

<div class="wrap flavor-foros-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-format-chat"></span>
        <?php esc_html_e('Configuración de Foros', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_foros'); ?>

        <div class="dm-config-grid">
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-home"></span> <?php esc_html_e('Información General', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="nombre_comunidad"><?php esc_html_e('Nombre de la Comunidad', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="nombre_comunidad" name="nombre_comunidad" value="<?php echo esc_attr($configuracion['nombre_comunidad']); ?>">
                    </div>
                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>
                    <div class="dm-form-group">
                        <label for="posts_por_pagina"><?php esc_html_e('Posts por página', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="posts_por_pagina" name="posts_por_pagina" value="<?php echo esc_attr($configuracion['posts_por_pagina']); ?>" min="5" max="100">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Permisos de Usuarios', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_registro" value="1" <?php checked($configuracion['permitir_registro']); ?>>
                            <span><?php esc_html_e('Permitir registro público', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="requiere_aprobacion_post" value="1" <?php checked($configuracion['requiere_aprobacion_post']); ?>>
                            <span><?php esc_html_e('Requerir aprobación de publicaciones', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_anonimos" value="1" <?php checked($configuracion['permitir_anonimos']); ?>>
                            <span><?php esc_html_e('Permitir publicar anónimamente', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_editar" value="1" <?php checked($configuracion['permitir_editar']); ?>>
                            <span><?php esc_html_e('Permitir editar publicaciones', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="minutos_edicion"><?php esc_html_e('Minutos para editar', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="minutos_edicion" name="minutos_edicion" value="<?php echo esc_attr($configuracion['minutos_edicion']); ?>" min="0" max="1440">
                        <p class="description"><?php esc_html_e('0 = sin límite', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-paperclip"></span> <?php esc_html_e('Archivos Adjuntos', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_adjuntos" value="1" <?php checked($configuracion['permitir_adjuntos']); ?>>
                            <span><?php esc_html_e('Permitir archivos adjuntos', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-row" style="margin-top: 15px;">
                        <div class="dm-form-group">
                            <label for="max_tamano_adjunto"><?php esc_html_e('Tamaño máximo (MB)', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="max_tamano_adjunto" name="max_tamano_adjunto" value="<?php echo esc_attr($configuracion['max_tamano_adjunto']); ?>" min="1" max="100">
                        </div>
                        <div class="dm-form-group">
                            <label for="tipos_adjuntos"><?php esc_html_e('Extensiones permitidas', 'flavor-chat-ia'); ?></label>
                            <input type="text" id="tipos_adjuntos" name="tipos_adjuntos" value="<?php echo esc_attr($configuracion['tipos_adjuntos']); ?>" placeholder="jpg,png,pdf">
                        </div>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Sistema de Reputación', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_reputacion" value="1" <?php checked($configuracion['habilitar_reputacion']); ?>>
                            <span><?php esc_html_e('Habilitar sistema de puntos', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-row" style="margin-top: 15px;">
                        <div class="dm-form-group">
                            <label for="puntos_nuevo_tema"><?php esc_html_e('Puntos por nuevo tema', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="puntos_nuevo_tema" name="puntos_nuevo_tema" value="<?php echo esc_attr($configuracion['puntos_nuevo_tema']); ?>" min="0">
                        </div>
                        <div class="dm-form-group">
                            <label for="puntos_respuesta"><?php esc_html_e('Puntos por respuesta', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="puntos_respuesta" name="puntos_respuesta" value="<?php echo esc_attr($configuracion['puntos_respuesta']); ?>" min="0">
                        </div>
                    </div>
                    <div class="dm-form-group">
                        <label for="puntos_mejor_respuesta"><?php esc_html_e('Puntos por mejor respuesta', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="puntos_mejor_respuesta" name="puntos_mejor_respuesta" value="<?php echo esc_attr($configuracion['puntos_mejor_respuesta']); ?>" min="0">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Perfil de Usuario', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_mencion" value="1" <?php checked($configuracion['habilitar_mencion']); ?>>
                            <span><?php esc_html_e('Habilitar menciones (@usuario)', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_firma" value="1" <?php checked($configuracion['habilitar_firma']); ?>>
                            <span><?php esc_html_e('Permitir firma personalizada', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="max_caracteres_firma"><?php esc_html_e('Máximo caracteres en firma', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="max_caracteres_firma" name="max_caracteres_firma" value="<?php echo esc_attr($configuracion['max_caracteres_firma']); ?>" min="0" max="1000">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-shield"></span> <?php esc_html_e('Moderación', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_moderacion" value="1" <?php checked($configuracion['habilitar_moderacion']); ?>>
                            <span><?php esc_html_e('Habilitar cola de moderación', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="palabras_prohibidas"><?php esc_html_e('Palabras prohibidas (una por línea)', 'flavor-chat-ia'); ?></label>
                        <textarea id="palabras_prohibidas" name="palabras_prohibidas" rows="4"><?php echo esc_textarea($configuracion['palabras_prohibidas']); ?></textarea>
                        <p class="description"><?php esc_html_e('Los posts con estas palabras irán a moderación', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-email"></span> <?php esc_html_e('Notificaciones', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_respuestas" value="1" <?php checked($configuracion['notificar_respuestas']); ?>>
                            <span><?php esc_html_e('Notificar nuevas respuestas', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_menciones" value="1" <?php checked($configuracion['notificar_menciones']); ?>>
                            <span><?php esc_html_e('Notificar menciones', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_foros" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-foros-config { max-width: 1200px; }
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
.dm-form-group input[type="text"], .dm-form-group input[type="number"], .dm-form-group select, .dm-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 14px; }
.dm-form-group textarea { resize: vertical; }
.dm-form-group .description { margin-top: 5px; font-size: 12px; color: #646970; }
.dm-checkbox-group { display: flex; flex-direction: column; gap: 12px; }
.dm-checkbox { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; }
.dm-checkbox input { margin-top: 3px; }
.dm-checkbox span { font-size: 13px; }
.dm-form-actions { margin-top: 25px; padding: 20px; background: #f6f7f7; border-radius: 8px; text-align: center; }
.dm-form-actions .button-hero { display: inline-flex; align-items: center; gap: 8px; padding: 10px 30px; font-size: 14px; }
.dm-form-actions .dashicons { font-size: 18px; width: 18px; height: 18px; }
</style>
