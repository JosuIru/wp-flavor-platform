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
    "SELECT * FROM $tabla_espacios WHERE id = %d AND estado = 'disponible'",
    $espacio_id
));

if (!$espacio) {
    echo '<div class="espacios-empty"><span class="dashicons dashicons-warning"></span><h3>' . __('Espacio no encontrado', 'flavor-platform') . '</h3></div>';
    return;
}

$usuario_id = get_current_user_id();

// Obtener equipamiento
$equipamiento = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_equipamiento WHERE espacio_id = %d ORDER BY nombre",
    $espacio->id
));

// Obtener imágenes desde el campo fotos (JSON array)
$imagenes = [];
if (!empty($espacio->fotos)) {
    $fotos_array = json_decode($espacio->fotos, true);
    if (is_array($fotos_array)) {
        $imagenes = $fotos_array;
    }
}

// Verificar si el usuario tiene reserva activa
$tiene_reserva = false;
if ($usuario_id) {
    $tiene_reserva = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_reservas
         WHERE espacio_id = %d AND usuario_id = %d AND estado IN ('solicitada', 'confirmada')
         AND DATE(fecha_inicio) >= CURDATE()",
        $espacio->id,
        $usuario_id
    ));
}

// Horarios del espacio - usar campos de la tabla
$horario_apertura = $espacio->horario_apertura ?? '08:00:00';
$horario_cierre = $espacio->horario_cierre ?? '22:00:00';
$dias_disponibles_str = $espacio->dias_disponibles ?? 'L,M,X,J,V,S,D';
$dias_disponibles_arr = explode(',', $dias_disponibles_str);

$dias_semana_map = [
    'L' => __('Lunes', 'flavor-platform'),
    'M' => __('Martes', 'flavor-platform'),
    'X' => __('Miércoles', 'flavor-platform'),
    'J' => __('Jueves', 'flavor-platform'),
    'V' => __('Viernes', 'flavor-platform'),
    'S' => __('Sábado', 'flavor-platform'),
    'D' => __('Domingo', 'flavor-platform'),
];
?>

<div class="espacio-detalle-wrapper">
    <a href="<?php echo remove_query_arg('espacio_id'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 1rem;">
        <span class="dashicons dashicons-arrow-left-alt2"></span>
        <?php _e('Volver a espacios', 'flavor-platform'); ?>
    </a>

    <div class="espacio-detalle">
        <div class="espacio-detalle-galeria">
            <div class="espacio-detalle-imagen-principal">
                <?php if (!empty($imagenes)): ?>
                    <img src="<?php echo esc_url($imagenes[0]); ?>" alt="<?php echo esc_attr($espacio->nombre); ?>">
                <?php else: ?>
                    <div class="placeholder">
                        <span class="dashicons dashicons-building"></span>
                        <span><?php _e('Sin imagen', 'flavor-platform'); ?></span>
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
                        <?php echo number_format((float) $espacio->valoracion_media, 1); ?>
                        (<?php echo (int) $espacio->numero_valoraciones; ?> <?php _e('valoraciones', 'flavor-platform'); ?>)
                    </span>
                </div>
            <?php endif; ?>

            <div class="espacio-detalle-meta">
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Tipo', 'flavor-platform'); ?></label>
                    <span><?php echo esc_html(ucfirst($espacio->tipo)); ?></span>
                </div>
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Capacidad', 'flavor-platform'); ?></label>
                    <span><?php printf(__('Hasta %d personas', 'flavor-platform'), $espacio->capacidad_personas); ?></span>
                </div>
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Precio', 'flavor-platform'); ?></label>
                    <span style="color: <?php echo $espacio->precio_hora > 0 ? '#6366f1' : '#10b981'; ?>; font-weight: 600;">
                        <?php
                        if ($espacio->precio_hora > 0) {
                            echo number_format($espacio->precio_hora, 2) . '€/hora';
                        } else {
                            _e('Gratuito', 'flavor-platform');
                        }
                        ?>
                    </span>
                </div>
                <div class="espacio-detalle-meta-item">
                    <label><?php _e('Reserva mínima', 'flavor-platform'); ?></label>
                    <span><?php printf(__('%d hora(s)', 'flavor-platform'), $espacio->tiempo_minimo ?? 1); ?></span>
                </div>
            </div>

            <?php if ($espacio->descripcion): ?>
                <div class="espacio-detalle-descripcion">
                    <?php echo wp_kses_post(wpautop($espacio->descripcion)); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($espacio->requiere_fianza) && !empty($espacio->importe_fianza) && $espacio->importe_fianza > 0): ?>
                <div class="fianza-info">
                    <span class="dashicons dashicons-money-alt"></span>
                    <div class="fianza-info-texto">
                        <strong><?php printf(__('Fianza requerida: %s€', 'flavor-platform'), number_format((float) $espacio->importe_fianza, 2)); ?></strong>
                        <span><?php _e('Se devolverá al entregar el espacio en buenas condiciones', 'flavor-platform'); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="espacio-detalle-acciones">
                <?php if (is_user_logged_in()): ?>
                    <?php if ($tiene_reserva): ?>
                        <button class="btn btn-outline" disabled>
                            <?php _e('Ya tienes una reserva', 'flavor-platform'); ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary btn-reservar-espacio" data-espacio-id="<?php echo $espacio->id; ?>">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php _e('Reservar espacio', 'flavor-platform'); ?>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-outline btn-reportar-incidencia" data-espacio-id="<?php echo $espacio->id; ?>">
                        <span class="dashicons dashicons-flag"></span>
                        <?php _e('Reportar incidencia', 'flavor-platform'); ?>
                    </button>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(flavor_current_request_url()); ?>" class="btn btn-primary">
                        <?php _e('Inicia sesión para reservar', 'flavor-platform'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Equipamiento -->
    <?php if ($equipamiento): ?>
        <div class="espacio-equipamiento">
            <h3><?php _e('Equipamiento disponible', 'flavor-platform'); ?></h3>
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
    <?php if (!empty($dias_disponibles_arr)): ?>
        <div class="espacio-equipamiento">
            <h3><?php _e('Horarios de disponibilidad', 'flavor-platform'); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.75rem;">
                <?php foreach ($dias_disponibles_arr as $dia_codigo): ?>
                    <?php $dia_codigo = trim($dia_codigo); ?>
                    <?php if (isset($dias_semana_map[$dia_codigo])): ?>
                        <div style="padding: 0.75rem; background: #f9fafb; border-radius: 8px;">
                            <strong style="display: block; margin-bottom: 0.25rem;"><?php echo esc_html($dias_semana_map[$dia_codigo]); ?></strong>
                            <span style="color: #6b7280; font-size: 0.875rem;">
                                <?php echo esc_html(substr($horario_apertura, 0, 5)); ?> - <?php echo esc_html(substr($horario_cierre, 0, 5)); ?>
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
            <h3><?php _e('Disponibilidad', 'flavor-platform'); ?></h3>
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
            <h4 style="margin-bottom: 0.75rem;"><?php _e('Horarios disponibles', 'flavor-platform'); ?></h4>
            <p style="color: #6b7280; font-size: 0.875rem;"><?php _e('Selecciona una fecha en el calendario para ver los horarios disponibles.', 'flavor-platform'); ?></p>
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
             WHERE espacio_id = %d AND usuario_id = %d AND estado = 'finalizada'",
            $espacio->id,
            $usuario_id
        ));

        if ($ha_usado && !$ya_valoro):
        ?>
            <div class="espacio-equipamiento">
                <h3><?php _e('Valora este espacio', 'flavor-platform'); ?></h3>
                <form class="form-valoracion" data-espacio-id="<?php echo $espacio->id; ?>" style="max-width: 500px;">
                    <div class="valoracion-estrellas" data-valoracion="0" style="margin-bottom: 1rem;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star dashicons dashicons-star-empty" data-valor="<?php echo $i; ?>" style="font-size: 28px; width: 28px; height: 28px; cursor: pointer; color: #fbbf24;"></span>
                        <?php endfor; ?>
                    </div>
                    <div class="form-grupo">
                        <textarea name="comentario" rows="3" placeholder="<?php esc_attr_e('Escribe un comentario (opcional)', 'flavor-platform'); ?>"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm"><?php _e('Enviar valoración', 'flavor-platform'); ?></button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Normas de uso -->
    <?php if ($espacio->normas_uso): ?>
        <div class="espacio-equipamiento">
            <h3><?php _e('Normas de uso', 'flavor-platform'); ?></h3>
            <div style="background: #fffbeb; border: 1px solid #fbbf24; border-radius: 8px; padding: 1rem;">
                <?php echo wp_kses_post(wpautop($espacio->normas_uso)); ?>
            </div>
        </div>
    <?php endif; ?>
</div>
