<?php
/**
 * Vista de Configuración - Módulo Cursos
 *
 * @package FlavorChatIA
 * @subpackage Cursos
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion = get_option('flavor_cursos_settings', []);
$configuracion_default = [
    'nombre_academia' => get_bloginfo('name'),
    'descripcion' => '',
    'email_contacto' => get_option('admin_email'),
    'moneda' => 'EUR',
    'permitir_inscripcion_publica' => true,
    'requiere_aprobacion' => false,
    'enviar_certificados' => true,
    'formato_certificado' => 'pdf',
    'duracion_acceso_dias' => 365,
    'permitir_comentarios' => true,
    'mostrar_progreso' => true,
    'notificar_inscripcion' => true,
    'notificar_completado' => true,
    'habilitar_examenes' => true,
    'puntuacion_minima_aprobar' => 70,
    'intentos_examen' => 3,
    'mostrar_instructores' => true,
    'habilitar_foro_curso' => false,
    'video_provider' => 'youtube',
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

$mensaje_guardado = '';
if (isset($_POST['guardar_config_cursos']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_cursos')) {
    $nueva_config = [
        'nombre_academia' => sanitize_text_field($_POST['nombre_academia'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'email_contacto' => sanitize_email($_POST['email_contacto'] ?? ''),
        'moneda' => sanitize_text_field($_POST['moneda'] ?? 'EUR'),
        'permitir_inscripcion_publica' => isset($_POST['permitir_inscripcion_publica']),
        'requiere_aprobacion' => isset($_POST['requiere_aprobacion']),
        'enviar_certificados' => isset($_POST['enviar_certificados']),
        'formato_certificado' => sanitize_text_field($_POST['formato_certificado'] ?? 'pdf'),
        'duracion_acceso_dias' => absint($_POST['duracion_acceso_dias'] ?? 365),
        'permitir_comentarios' => isset($_POST['permitir_comentarios']),
        'mostrar_progreso' => isset($_POST['mostrar_progreso']),
        'notificar_inscripcion' => isset($_POST['notificar_inscripcion']),
        'notificar_completado' => isset($_POST['notificar_completado']),
        'habilitar_examenes' => isset($_POST['habilitar_examenes']),
        'puntuacion_minima_aprobar' => absint($_POST['puntuacion_minima_aprobar'] ?? 70),
        'intentos_examen' => absint($_POST['intentos_examen'] ?? 3),
        'mostrar_instructores' => isset($_POST['mostrar_instructores']),
        'habilitar_foro_curso' => isset($_POST['habilitar_foro_curso']),
        'video_provider' => sanitize_text_field($_POST['video_provider'] ?? 'youtube'),
    ];

    update_option('flavor_cursos_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', 'flavor-platform');
}
?>

<div class="wrap flavor-cursos-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <?php esc_html_e('Configuración de Cursos', 'flavor-platform'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_cursos'); ?>

        <div class="dm-config-grid">
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-home"></span> <?php esc_html_e('Información General', 'flavor-platform'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="nombre_academia"><?php esc_html_e('Nombre de la Academia', 'flavor-platform'); ?></label>
                        <input type="text" id="nombre_academia" name="nombre_academia" value="<?php echo esc_attr($configuracion['nombre_academia']); ?>">
                    </div>
                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-platform'); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="email_contacto"><?php esc_html_e('Email de Contacto', 'flavor-platform'); ?></label>
                            <input type="email" id="email_contacto" name="email_contacto" value="<?php echo esc_attr($configuracion['email_contacto']); ?>">
                        </div>
                        <div class="dm-form-group">
                            <label for="moneda"><?php esc_html_e('Moneda', 'flavor-platform'); ?></label>
                            <select id="moneda" name="moneda">
                                <option value="EUR" <?php selected($configuracion['moneda'], 'EUR'); ?>>Euro (EUR)</option>
                                <option value="USD" <?php selected($configuracion['moneda'], 'USD'); ?>>Dólar (USD)</option>
                                <option value="GBP" <?php selected($configuracion['moneda'], 'GBP'); ?>>Libra (GBP)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-groups"></span> <?php esc_html_e('Inscripciones', 'flavor-platform'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_inscripcion_publica" value="1" <?php checked($configuracion['permitir_inscripcion_publica']); ?>>
                            <span><?php esc_html_e('Permitir inscripción pública', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="requiere_aprobacion" value="1" <?php checked($configuracion['requiere_aprobacion']); ?>>
                            <span><?php esc_html_e('Requerir aprobación del instructor', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_instructores" value="1" <?php checked($configuracion['mostrar_instructores']); ?>>
                            <span><?php esc_html_e('Mostrar perfil de instructores', 'flavor-platform'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="duracion_acceso_dias"><?php esc_html_e('Duración del acceso (días)', 'flavor-platform'); ?></label>
                        <input type="number" id="duracion_acceso_dias" name="duracion_acceso_dias" value="<?php echo esc_attr($configuracion['duracion_acceso_dias']); ?>" min="1" max="9999">
                        <p class="description"><?php esc_html_e('0 = acceso ilimitado', 'flavor-platform'); ?></p>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-awards"></span> <?php esc_html_e('Certificados y Exámenes', 'flavor-platform'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="enviar_certificados" value="1" <?php checked($configuracion['enviar_certificados']); ?>>
                            <span><?php esc_html_e('Emitir certificados al completar', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_examenes" value="1" <?php checked($configuracion['habilitar_examenes']); ?>>
                            <span><?php esc_html_e('Habilitar exámenes', 'flavor-platform'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-row" style="margin-top: 15px;">
                        <div class="dm-form-group">
                            <label for="puntuacion_minima_aprobar"><?php esc_html_e('Puntuación mínima (%)', 'flavor-platform'); ?></label>
                            <input type="number" id="puntuacion_minima_aprobar" name="puntuacion_minima_aprobar" value="<?php echo esc_attr($configuracion['puntuacion_minima_aprobar']); ?>" min="0" max="100">
                        </div>
                        <div class="dm-form-group">
                            <label for="intentos_examen"><?php esc_html_e('Intentos permitidos', 'flavor-platform'); ?></label>
                            <input type="number" id="intentos_examen" name="intentos_examen" value="<?php echo esc_attr($configuracion['intentos_examen']); ?>" min="1" max="99">
                        </div>
                    </div>
                    <div class="dm-form-group">
                        <label for="formato_certificado"><?php esc_html_e('Formato de Certificado', 'flavor-platform'); ?></label>
                        <select id="formato_certificado" name="formato_certificado">
                            <option value="pdf" <?php selected($configuracion['formato_certificado'], 'pdf'); ?>>PDF</option>
                            <option value="html" <?php selected($configuracion['formato_certificado'], 'html'); ?>>HTML</option>
                            <option value="imagen" <?php selected($configuracion['formato_certificado'], 'imagen'); ?>>Imagen</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-settings"></span> <?php esc_html_e('Opciones del Curso', 'flavor-platform'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_progreso" value="1" <?php checked($configuracion['mostrar_progreso']); ?>>
                            <span><?php esc_html_e('Mostrar barra de progreso', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_comentarios" value="1" <?php checked($configuracion['permitir_comentarios']); ?>>
                            <span><?php esc_html_e('Permitir comentarios en lecciones', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_foro_curso" value="1" <?php checked($configuracion['habilitar_foro_curso']); ?>>
                            <span><?php esc_html_e('Habilitar foro por curso', 'flavor-platform'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="video_provider"><?php esc_html_e('Proveedor de Video', 'flavor-platform'); ?></label>
                        <select id="video_provider" name="video_provider">
                            <option value="youtube" <?php selected($configuracion['video_provider'], 'youtube'); ?>>YouTube</option>
                            <option value="vimeo" <?php selected($configuracion['video_provider'], 'vimeo'); ?>>Vimeo</option>
                            <option value="self" <?php selected($configuracion['video_provider'], 'self'); ?>>Servidor propio</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-email"></span> <?php esc_html_e('Notificaciones', 'flavor-platform'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_inscripcion" value="1" <?php checked($configuracion['notificar_inscripcion']); ?>>
                            <span><?php esc_html_e('Notificar al inscribirse', 'flavor-platform'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_completado" value="1" <?php checked($configuracion['notificar_completado']); ?>>
                            <span><?php esc_html_e('Notificar al completar curso', 'flavor-platform'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_cursos" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', 'flavor-platform'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-cursos-config { max-width: 1200px; }
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
.dm-form-group input[type="text"], .dm-form-group input[type="email"], .dm-form-group input[type="number"], .dm-form-group select, .dm-form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #dcdcde; border-radius: 4px; font-size: 14px; }
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
