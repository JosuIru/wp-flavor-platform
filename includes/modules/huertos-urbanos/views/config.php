<?php
/**
 * Vista de Configuración - Módulo Huertos Urbanos
 *
 * @package FlavorChatIA
 * @subpackage HuertosUrbanos
 */

if (!defined('ABSPATH')) {
    exit;
}

$configuracion = get_option('flavor_huertos_settings', []);
$configuracion_default = [
    'nombre_proyecto' => __('Huertos Comunitarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'descripcion' => '',
    'email_contacto' => get_option('admin_email'),
    'max_parcelas_por_usuario' => 1,
    'tamano_parcela_default' => 25,
    'unidad_medida' => 'm2',
    'duracion_asignacion_meses' => 12,
    'precio_mensual' => 0,
    'permitir_solicitudes' => true,
    'requiere_aprobacion' => true,
    'mostrar_mapa' => true,
    'permitir_intercambio_semillas' => true,
    'permitir_intercambio_cosecha' => true,
    'habilitar_banco_semillas' => true,
    'habilitar_calendario_riego' => true,
    'habilitar_tareas_comunitarias' => true,
    'notificar_nueva_solicitud' => true,
    'notificar_asignacion' => true,
    'notificar_vencimiento' => true,
    'dias_aviso_vencimiento' => 30,
    'normas_huerto' => '',
];

$configuracion = wp_parse_args($configuracion, $configuracion_default);

$mensaje_guardado = '';
if (isset($_POST['guardar_config_huertos']) && wp_verify_nonce($_POST['_wpnonce'], 'guardar_config_huertos')) {
    $nueva_config = [
        'nombre_proyecto' => sanitize_text_field($_POST['nombre_proyecto'] ?? ''),
        'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
        'email_contacto' => sanitize_email($_POST['email_contacto'] ?? ''),
        'max_parcelas_por_usuario' => absint($_POST['max_parcelas_por_usuario'] ?? 1),
        'tamano_parcela_default' => absint($_POST['tamano_parcela_default'] ?? 25),
        'unidad_medida' => sanitize_text_field($_POST['unidad_medida'] ?? 'm2'),
        'duracion_asignacion_meses' => absint($_POST['duracion_asignacion_meses'] ?? 12),
        'precio_mensual' => floatval($_POST['precio_mensual'] ?? 0),
        'permitir_solicitudes' => isset($_POST['permitir_solicitudes']),
        'requiere_aprobacion' => isset($_POST['requiere_aprobacion']),
        'mostrar_mapa' => isset($_POST['mostrar_mapa']),
        'permitir_intercambio_semillas' => isset($_POST['permitir_intercambio_semillas']),
        'permitir_intercambio_cosecha' => isset($_POST['permitir_intercambio_cosecha']),
        'habilitar_banco_semillas' => isset($_POST['habilitar_banco_semillas']),
        'habilitar_calendario_riego' => isset($_POST['habilitar_calendario_riego']),
        'habilitar_tareas_comunitarias' => isset($_POST['habilitar_tareas_comunitarias']),
        'notificar_nueva_solicitud' => isset($_POST['notificar_nueva_solicitud']),
        'notificar_asignacion' => isset($_POST['notificar_asignacion']),
        'notificar_vencimiento' => isset($_POST['notificar_vencimiento']),
        'dias_aviso_vencimiento' => absint($_POST['dias_aviso_vencimiento'] ?? 30),
        'normas_huerto' => wp_kses_post($_POST['normas_huerto'] ?? ''),
    ];

    update_option('flavor_huertos_settings', $nueva_config);
    $configuracion = $nueva_config;
    $mensaje_guardado = __('Configuración guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
}
?>

<div class="wrap flavor-huertos-config">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-carrot"></span>
        <?php esc_html_e('Configuración de Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>
    <hr class="wp-header-end">

    <?php if ($mensaje_guardado): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html($mensaje_guardado); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" class="dm-config-form">
        <?php wp_nonce_field('guardar_config_huertos'); ?>

        <div class="dm-config-grid">
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-home"></span> <?php esc_html_e('Información General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <label for="nombre_proyecto"><?php esc_html_e('Nombre del Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" id="nombre_proyecto" name="nombre_proyecto" value="<?php echo esc_attr($configuracion['nombre_proyecto']); ?>">
                    </div>
                    <div class="dm-form-group">
                        <label for="descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <textarea id="descripcion" name="descripcion" rows="3"><?php echo esc_textarea($configuracion['descripcion']); ?></textarea>
                    </div>
                    <div class="dm-form-group">
                        <label for="email_contacto"><?php esc_html_e('Email de Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="email" id="email_contacto" name="email_contacto" value="<?php echo esc_attr($configuracion['email_contacto']); ?>">
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-layout"></span> <?php esc_html_e('Parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="max_parcelas_por_usuario"><?php esc_html_e('Máx. parcelas por usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" id="max_parcelas_por_usuario" name="max_parcelas_por_usuario" value="<?php echo esc_attr($configuracion['max_parcelas_por_usuario']); ?>" min="1" max="10">
                        </div>
                        <div class="dm-form-group">
                            <label for="tamano_parcela_default"><?php esc_html_e('Tamaño por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" id="tamano_parcela_default" name="tamano_parcela_default" value="<?php echo esc_attr($configuracion['tamano_parcela_default']); ?>" min="1">
                        </div>
                    </div>
                    <div class="dm-form-row">
                        <div class="dm-form-group">
                            <label for="unidad_medida"><?php esc_html_e('Unidad de medida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <select id="unidad_medida" name="unidad_medida">
                                <option value="m2" <?php selected($configuracion['unidad_medida'], 'm2'); ?>>m²</option>
                                <option value="ha" <?php selected($configuracion['unidad_medida'], 'ha'); ?>>Hectáreas</option>
                            </select>
                        </div>
                        <div class="dm-form-group">
                            <label for="duracion_asignacion_meses"><?php esc_html_e('Duración asignación (meses)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="number" id="duracion_asignacion_meses" name="duracion_asignacion_meses" value="<?php echo esc_attr($configuracion['duracion_asignacion_meses']); ?>" min="1" max="120">
                        </div>
                    </div>
                    <div class="dm-form-group">
                        <label for="precio_mensual"><?php esc_html_e('Precio mensual (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="precio_mensual" name="precio_mensual" value="<?php echo esc_attr($configuracion['precio_mensual']); ?>" min="0" step="0.01">
                        <p class="description"><?php esc_html_e('0 = gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_solicitudes" value="1" <?php checked($configuracion['permitir_solicitudes']); ?>>
                            <span><?php esc_html_e('Permitir solicitudes de parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="requiere_aprobacion" value="1" <?php checked($configuracion['requiere_aprobacion']); ?>>
                            <span><?php esc_html_e('Requerir aprobación de solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="mostrar_mapa" value="1" <?php checked($configuracion['mostrar_mapa']); ?>>
                            <span><?php esc_html_e('Mostrar mapa de huertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_intercambio_semillas" value="1" <?php checked($configuracion['permitir_intercambio_semillas']); ?>>
                            <span><?php esc_html_e('Permitir intercambio de semillas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="permitir_intercambio_cosecha" value="1" <?php checked($configuracion['permitir_intercambio_cosecha']); ?>>
                            <span><?php esc_html_e('Permitir intercambio de cosecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_banco_semillas" value="1" <?php checked($configuracion['habilitar_banco_semillas']); ?>>
                            <span><?php esc_html_e('Habilitar banco de semillas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e('Funcionalidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_calendario_riego" value="1" <?php checked($configuracion['habilitar_calendario_riego']); ?>>
                            <span><?php esc_html_e('Habilitar calendario de riego', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="habilitar_tareas_comunitarias" value="1" <?php checked($configuracion['habilitar_tareas_comunitarias']); ?>>
                            <span><?php esc_html_e('Habilitar tareas comunitarias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="dm-card">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-email"></span> <?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-checkbox-group">
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_nueva_solicitud" value="1" <?php checked($configuracion['notificar_nueva_solicitud']); ?>>
                            <span><?php esc_html_e('Notificar nuevas solicitudes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_asignacion" value="1" <?php checked($configuracion['notificar_asignacion']); ?>>
                            <span><?php esc_html_e('Notificar asignaciones de parcela', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label class="dm-checkbox">
                            <input type="checkbox" name="notificar_vencimiento" value="1" <?php checked($configuracion['notificar_vencimiento']); ?>>
                            <span><?php esc_html_e('Notificar próximos vencimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                    <div class="dm-form-group" style="margin-top: 15px;">
                        <label for="dias_aviso_vencimiento"><?php esc_html_e('Días de aviso antes de vencimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="number" id="dias_aviso_vencimiento" name="dias_aviso_vencimiento" value="<?php echo esc_attr($configuracion['dias_aviso_vencimiento']); ?>" min="1" max="90">
                    </div>
                </div>
            </div>

            <div class="dm-card dm-card--full">
                <div class="dm-card__header">
                    <h3><span class="dashicons dashicons-media-document"></span> <?php esc_html_e('Normas del Huerto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>
                <div class="dm-card__body">
                    <div class="dm-form-group">
                        <?php
                        wp_editor(
                            $configuracion['normas_huerto'],
                            'normas_huerto',
                            [
                                'textarea_name' => 'normas_huerto',
                                'textarea_rows' => 8,
                                'media_buttons' => false,
                                'teeny' => true,
                            ]
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="dm-form-actions">
            <button type="submit" name="guardar_config_huertos" class="button button-primary button-hero">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </form>
</div>

<style>
.flavor-huertos-config { max-width: 1200px; }
.dm-config-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px; }
.dm-card--full { grid-column: 1 / -1; }
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
