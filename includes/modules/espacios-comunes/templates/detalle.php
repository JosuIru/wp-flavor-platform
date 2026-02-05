<?php
/**
 * Template: Detalle de Espacio
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
$tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';
$tabla_reservas = $wpdb->prefix . 'flavor_espacios_reservas';

$espacio = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_espacios WHERE id = %d AND estado = 'activo'",
    $espacio_id
));

if (!$espacio) {
    echo '<div class="espacios-empty"><span class="dashicons dashicons-warning"></span><h3>' . __('Espacio no encontrado', 'flavor-chat-ia') . '</h3></div>';
    return;
}

$usuario_id = get_current_user_id();

// Obtener equipamiento
$equipamiento = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_equipamiento WHERE espacio_id = %d ORDER BY nombre",
    $espacio->id
));

// Obtener imágenes adicionales (si las hay en el campo JSON)
$imagenes = [];
if ($espacio->imagen_url) {
    $imagenes[] = $espacio->imagen_url;
}
$imagenes_adicionales = json_decode($espacio->imagenes_adicionales ?? '[]', true);
if ($imagenes_adicionales) {
    $imagenes = array_merge($imagenes, $imagenes_adicionales);
}

// Verificar si el usuario tiene reserva activa
$tiene_reserva = false;
if ($usuario_id) {
    $tiene_reserva = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_reservas
         WHERE espacio_id = %d AND usuario_id = %d AND estado IN ('pendiente', 'confirmada')
         AND fecha >= CURDATE()",
        $espacio->id,
        $usuario_id
    ));
}

// Horarios del espacio
$horarios = json_decode($espacio->horarios ?? '{}', true);
$dias_semana = [
    'lunes' => __('Lunes', 'flavor-chat-ia'),
    'martes' => __('Martes', 'flavor-chat-ia'),
    'miercoles' => __('Miércoles', 'flavor-chat-ia'),
    'jueves' => __('Jueves', 'flavor-chat-ia'),
    'viernes' => __('Viernes', 'flavor-chat-ia'),
    'sabado' => __('Sábado', 'flavor-chat-ia'),
    'domingo' => __('Domingo', 'flavor-chat-ia'),
];
?>

<div class="espacio-detalle-wrapper">
    <a href="<?php echo remove_query_arg('espacio_id'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 1rem;">
        <span class="dashicons dashicons-arrow-left-alt2"></span>
        <?php _e('Volver a espacios', 'flavor-chat-ia'); ?>
    </a>

    <div class="espacio-detalle">
        <div class="espacio-detalle-galeria">
            <div class="espacio-detalle-imagen-principal">
                <?php if (!empty($imagenes)): ?>
                    <img src="<?php echo esc_url($imagenes[0]); ?>" alt="<?php echo esc_attr($espacio->nombre); ?>">
                <?php else: ?>
                    <div class="placeholder">
                        <span class="dashicons dashicons-building"></span>
                        <span><?php _e('Sin imagen', 'flavor-chat-ia'); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (count($imagenes) > 1): ?>
                <div class="espacio-detalle-miniaturas">
                    <?php foreach ($imagenes as $index => $imagen): ?>
                        <div class="espacio-detalle-miniatura <?php echo $index === 0 ? 'active' : ''; ?>">
                            <img src="<?php echo esc_url($imagen); ?>" alt="">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="espacio-detalle-info">
            <h1><?php echo esc_html($espacio->nombre); ?></h1>

            <div class="espacio-detalle-ubicacion">
                <span class="dashicons dashicons-location"></span>
                <?php echo esc_html($espacio->ubicacion); ?>
            </div>

            <?php if ($espacio->valoracion_media > 0): ?>
                <div class="espacio-detalle-valoracion">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="dashicons dashicons-star-<?php echo $i <= round($espacio->valoracion_media) ? 'filled' : 'empty'; ?>"></span>
                    <?php endfor; ?>
                    <span style="margin-left: 0.5rem; color: #6b7280;">
                        <?php echo number_format($espacio->valoracion_media, 1); ?>
                        (<?php echo $espacio->total_valoraciones; ?> <?php _e('valoraciones', 'flavor-chat-ia'); ?>)
                    </span>
                </div>
            <?php endif; ?>

            <div class="espacio-detalle-meta">
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
                    <span><?php echo esc_html(ucfirst($espacio->tipo)); ?></span>
                </div>
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Capacidad', 'flavor-chat-ia'); ?></label>
                    <span><?php printf(__('Hasta %d personas', 'flavor-chat-ia'), $espacio->capacidad_maxima); ?></span>
                </div>
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Precio', 'flavor-chat-ia'); ?></label>
                    <span style="color: <?php echo $espacio->precio_hora > 0 ? '#6366f1' : '#10b981'; ?>; font-weight: 600;">
                        <?php
                        if ($espacio->precio_hora > 0) {
                            echo number_format($espacio->precio_hora, 2) . '€/hora';
                        } else {
                            _e('Gratuito', 'flavor-chat-ia');
                        }
                        ?>
                    </span>
                </div>
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Reserva mínima', 'flavor-chat-ia'); ?></label>
                    <span><?php printf(__('%d hora(s)', 'flavor-chat-ia'), $espacio->tiempo_minimo ?? 1); ?></span>
                </div>
            </div>

            <?php if ($espacio->descripcion): ?>
                <div class="espacio-detalle-descripcion">
                    <?php echo wp_kses_post(wpautop($espacio->descripcion)); ?>
                </div>
            <?php endif; ?>

            <?php if ($espacio->fianza > 0): ?>
                <div class="fianza-info">
                    <span class="dashicons dashicons-money-alt"></span>
                    <div class="fianza-info-texto">
                        <strong><?php printf(__('Fianza requerida: %s€', 'flavor-chat-ia'), number_format($espacio->fianza, 2)); ?></strong>
                        <span><?php _e('Se devolverá al entregar el espacio en buenas condiciones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="espacio-detalle-acciones">
                <?php if (is_user_logged_in()): ?>
                    <?php if ($tiene_reserva): ?>
                        <button class="btn btn-outline" disabled>
                            <?php _e('Ya tienes una reserva', 'flavor-chat-ia'); ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-reservar-espacio" data-espacio-id="<?php echo $espacio->id; ?>">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e('Reservar espacio', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-outline btn-reportar-incidencia" data-espacio-id="<?php echo $espacio->id; ?>">
                        <span class="dashicons dashicons-flag"></span>
                        <?php _e('Reportar incidencia', 'flavor-chat-ia'); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="btn btn-primary">
                        <?php _e('Inicia sesión para reservar', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Equipamiento -->
    <?php if ($equipamiento): ?>
        <div class="espacio-equipamiento">
            <h3><?php _e('Equipamiento disponible', 'flavor-chat-ia'); ?></h3>
            <div class="equipamiento-lista">
                <?php foreach ($equipamiento as $equipo): ?>
                    <div class="equipamiento-item">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php echo esc_html($equipo->nombre); ?>
                        <?php if ($equipo->cantidad > 1): ?>
                            <span style="color: #6b7280;">(<?php echo $equipo->cantidad; ?>)</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Horarios -->
    <?php if (!empty($horarios)): ?>
        <div class="espacio-equipamiento">
            <h3><?php _e('Horarios de disponibilidad', 'flavor-chat-ia'); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">
                <?php foreach ($dias_semana as $dia_key => $dia_label): ?>
                    <?php if (isset($horarios[$dia_key]) && $horarios[$dia_key]['activo']): ?>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                            <strong style="display: block; margin-bottom: 0.25rem;"><?php echo $dia_label; ?></strong>
                            <span style="color: #6b7280; font-size: 0.875rem;">
                                <?php echo esc_html($horarios[$dia_key]['apertura']); ?> - <?php echo esc_html($horarios[$dia_key]['cierre']); ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Calendario de disponibilidad -->
    <div class="calendario-wrapper" data-espacio-id="<?php echo $espacio->id; ?>">
        <div class="calendario-header">
            <h3><?php _e('Disponibilidad', 'flavor-chat-ia'); ?></h3>
            <div class="calendario-nav">
                <button class="calendario-prev">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </button>
                <span class="calendario-mes-actual"></span>
                <button class="calendario-next">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
        <div class="calendario-grid">
            <div class="espacios-loading">
                <div class="espacios-spinner"></div>
            </div>
        </div>

        <div style="margin-top: 1rem;">
            <h4 style="margin-bottom: 0.75rem;"><?php _e('Horarios disponibles', 'flavor-chat-ia'); ?></h4>
            <p style="color: #6b7280; font-size: 0.875rem;"><?php _e('Selecciona una fecha en el calendario para ver los horarios disponibles.', 'flavor-chat-ia'); ?></p>
            <div class="horarios-disponibles"></div>
        </div>
    </div>

    <!-- Valoraciones -->
    <?php if (is_user_logged_in() && !$tiene_reserva): ?>
        <?php
        // Verificar si el usuario ya ha valorado
        $ya_valoro = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_espacios_reservas
             WHERE espacio_id = %d AND usuario_id = %d AND valoracion IS NOT NULL",
            $espacio->id,
            $usuario_id
        ));

        // Verificar si el usuario ha usado el espacio antes
        $ha_usado = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_espacios_reservas
             WHERE espacio_id = %d AND usuario_id = %d AND estado = 'completada'",
            $espacio->id,
            $usuario_id
        ));

        if ($ha_usado && !$ya_valoro):
        ?>
            <div class="espacio-equipamiento">
                <h3><?php _e('Valora este espacio', 'flavor-chat-ia'); ?></h3>
                <form class="form-valoracion" data-espacio-id="<?php echo $espacio->id; ?>" style="max-width: 500px;">
                    <div class="valoracion-estrellas" data-valoracion="0" style="margin-bottom: 1rem;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star dashicons dashicons-star-empty" data-valor="<?php echo $i; ?>" style="font-size: 28px; width: 28px; height: 28px; cursor: pointer; color: #fbbf24;"></span>
                        <?php endfor; ?>
                    </div>
                    <div class="form-grupo">
                        <textarea name="comentario" rows="3" placeholder="<?php esc_attr_e('Escribe un comentario (opcional)', 'flavor-chat-ia'); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><?php _e('Enviar valoración', 'flavor-chat-ia'); ?></button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Normas de uso -->
    <?php if ($espacio->normas_uso): ?>
        <div class="espacio-equipamiento">
            <h3><?php _e('Normas de uso', 'flavor-chat-ia'); ?></h3>
            <div style="background: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 1rem;">
                <?php echo wp_kses_post(wpautop($espacio->normas_uso)); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
