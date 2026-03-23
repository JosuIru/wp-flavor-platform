<?php
/**
 * Vista de Configuración - Módulo Colectivos
 *
 * @package FlavorChatIA
 * @subpackage Colectivos
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion = get_option('flavor_colectivos_settings', []);
$configuracion_default = [
    'nombre_red' => __('Red de Colectivos', 'flavor-chat-ia'),
    'descripcion' => '',
    'permitir_crear_colectivos' => true,
    'requiere_aprobacion' => true,
    'minimo_miembros_asamblea' => 3,
    'permitir_asambleas_virtuales' => true,
    'dias_anticipacion_asamblea' => 7,
    'quorum_porcentaje' => 50,
    'mostrar_directorio_publico' => true,
    'permitir_proyectos' => true,
    'permitir_recursos_compartidos' => true,
    'habilitar_votaciones' => true,
    'tipo_votacion_default' => 'mayoria_simple',
    'notificar_nuevos_miembros' => true,
    'notificar_asambleas' => true,
    'notificar_votaciones' => true,
    'categorias_colectivos' => "Vecinal\nCultural\nMedioambiental\nSocial\nDeportivo",
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

$mensaje_guardado = '';
if (isset($_POST['guardar_config_colectivos']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_colectivos')) {
    $nueva_config = [
        'nombre_red' => sanitize_text_field($_POST['nombre_red'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'permitir_crear_colectivos' => isset($_POST['permitir_crear_colectivos']),
        'requiere_aprobacion' => isset($_POST['requiere_aprobacion']),
        'minimo_miembros_asamblea' => absint($_POST['minimo_miembros_asamblea'] ?? 3),
        'permitir_asambleas_virtuales' => isset($_POST['permitir_asambleas_virtuales']),
        'dias_anticipacion_asamblea' => absint($_POST['dias_anticipacion_asamblea'] ?? 7),
        'quorum_porcentaje' => absint($_POST['quorum_porcentaje'] ?? 50),
        'mostrar_directorio_publico' => isset($_POST['mostrar_directorio_publico']),
        'permitir_proyectos' => isset($_POST['permitir_proyectos']),
        'permitir_recursos_compartidos' => isset($_POST['permitir_recursos_compartidos']),
        'habilitar_votaciones' => isset($_POST['habilitar_votaciones']),
        'tipo_votacion_default' => sanitize_text_field($_POST['tipo_votacion_default'] ?? 'mayoria_simple'),
        'notificar_nuevos_miembros' => isset($_POST['notificar_nuevos_miembros']),
        'notificar_asambleas' => isset($_POST['notificar_asambleas']),
        'notificar_votaciones' => isset($_POST['notificar_votaciones']),
        'categorias_colectivos' => sanitize_textarea_field($_POST['categorias_colectivos'] ?? ''),
    ];

    update_option('flavor_colectivos_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', 'flavor-chat-ia');
}
?>

<div class="wrap flavor-colectivos-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups"></span>
        <?php esc_html_e('Configuración de Colectivos', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_colectivos'); ?>

        <div class="dm-config-grid">
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-networking"></span> <?php esc_html_e('Información de la Red', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="nombre_red"><?php esc_html_e('Nombre de la Red', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="nombre_red" name="nombre_red" value="<?php echo esc_attr($configuracion['nombre_red']); ?>">
                    </div>
                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>
                    <div class="dm-form-group">
                        <label for="categorias_colectivos"><?php esc_html_e('Categorías (una por línea)', 'flavor-chat-ia'); ?></label>
                        <textarea id="categorias_colectivos" name="categorias_colectivos" rows="5"><?php echo esc_textarea($configuracion['categorias_colectivos']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Gestión de Colectivos', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_crear_colectivos" value="1" <?php checked($configuracion['permitir_crear_colectivos']); ?>>
                            <span><?php esc_html_e('Permitir crear nuevos colectivos', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="requiere_aprobacion" value="1" <?php checked($configuracion['requiere_aprobacion']); ?>>
                            <span><?php esc_html_e('Requerir aprobación de administrador', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_directorio_publico" value="1" <?php checked($configuracion['mostrar_directorio_publico']); ?>>
                            <span><?php esc_html_e('Mostrar directorio público de colectivos', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_proyectos" value="1" <?php checked($configuracion['permitir_proyectos']); ?>>
                            <span><?php esc_html_e('Permitir proyectos colaborativos', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_recursos_compartidos" value="1" <?php checked($configuracion['permitir_recursos_compartidos']); ?>>
                            <span><?php esc_html_e('Permitir compartir recursos', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-megaphone"></span> <?php esc_html_e('Asambleas', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_asambleas_virtuales" value="1" <?php checked($configuracion['permitir_asambleas_virtuales']); ?>>
                            <span><?php esc_html_e('Permitir asambleas virtuales', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-row" style="margin-top: 15px;">
                        <div class="dm-form-group">
                            <label for="minimo_miembros_asamblea"><?php esc_html_e('Mínimo miembros para convocar', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="minimo_miembros_asamblea" name="minimo_miembros_asamblea" value="<?php echo esc_attr($configuracion['minimo_miembros_asamblea']); ?>" min="1" max="100">
                        </div>
                        <div class="dm-form-group">
                            <label for="dias_anticipacion_asamblea"><?php esc_html_e('Días de anticipación', 'flavor-chat-ia'); ?></label>
                            <input type="number" id="dias_anticipacion_asamblea" name="dias_anticipacion_asamblea" value="<?php echo esc_attr($configuracion['dias_anticipacion_asamblea']); ?>" min="1" max="60">
                        </div>
                    </div>
                    <div class="dm-form-group">
                        <label for="quorum_porcentaje"><?php esc_html_e('Quórum necesario (%)', 'flavor-chat-ia'); ?></label>
                        <input type="number" id="quorum_porcentaje" name="quorum_porcentaje" value="<?php echo esc_attr($configuracion['quorum_porcentaje']); ?>" min="0" max="100">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-yes"></span> <?php esc_html_e('Votaciones', 'flavor-chat-ia'); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_votaciones" value="1" <?php checked($configuracion['habilitar_votaciones']); ?>>
                            <span><?php esc_html_e('Habilitar sistema de votaciones', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="tipo_votacion_default"><?php esc_html_e('Tipo de votación por defecto', 'flavor-chat-ia'); ?></label>
                        <select id="tipo_votacion_default" name="tipo_votacion_default">
                            <option value="mayoria_simple" <?php selected($configuracion['tipo_votacion_default'], 'mayoria_simple'); ?>><?php esc_html_e('Mayoría simple', 'flavor-chat-ia'); ?></option>
                            <option value="mayoria_absoluta" <?php selected($configuracion['tipo_votacion_default'], 'mayoria_absoluta'); ?>><?php esc_html_e('Mayoría absoluta', 'flavor-chat-ia'); ?></option>
                            <option value="unanimidad" <?php selected($configuracion['tipo_votacion_default'], 'unanimidad'); ?>><?php esc_html_e('Unanimidad', 'flavor-chat-ia'); ?></option>
                            <option value="consenso" <?php selected($configuracion['tipo_votacion_default'], 'consenso'); ?>><?php esc_html_e('Consenso', 'flavor-chat-ia'); ?></option>
                        </select>
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
                            <input type="checkbox" name="notificar_nuevos_miembros" value="1" <?php checked($configuracion['notificar_nuevos_miembros']); ?>>
                            <span><?php esc_html_e('Notificar nuevos miembros', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_asambleas" value="1" <?php checked($configuracion['notificar_asambleas']); ?>>
                            <span><?php esc_html_e('Notificar convocatorias de asambleas', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_votaciones" value="1" <?php checked($configuracion['notificar_votaciones']); ?>>
                            <span><?php esc_html_e('Notificar nuevas votaciones', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_colectivos" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-colectivos-config { max-width: 1200px; }
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
.dm-checkbox-group { display: flex; flex-direction: column; gap: 12px; }
.dm-checkbox { display: flex; align-items: flex-start; gap: 8px; cursor: pointer; }
.dm-checkbox input { margin-top: 3px; }
.dm-checkbox span { font-size: 13px; }
.dm-form-actions { margin-top: 25px; padding: 20px; background: #f6f7f7; border-radius: 8px; text-align: center; }
.dm-form-actions .button-hero { display: inline-flex; align-items: center; gap: 8px; padding: 10px 30px; font-size: 14px; }
.dm-form-actions .dashicons { font-size: 18px; width: 18px; height: 18px; }
</style>
