<?php
/**
 * Template: Detalle de Libro
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
$tabla_resenas = $wpdb->prefix . 'flavor_biblioteca_resenas';
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

$libro = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_libros WHERE id = %d",
    $libro_id
));

if (!$libro) {
    echo '<div class="biblioteca-empty"><span class="dashicons dashicons-warning"></span><h3>' . __('Libro no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3></div>';
    return;
}

$propietario = get_userdata($libro->propietario_id);
$usuario_id = get_current_user_id();
$es_propietario = $usuario_id == $libro->propietario_id;

// Obtener reseñas
$resenas = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, u.display_name as usuario_nombre
     FROM $tabla_resenas r
     LEFT JOIN {$wpdb->users} u ON r.usuario_id = u.ID
     WHERE r.libro_id = %d
     ORDER BY r.fecha_creacion DESC
     LIMIT 10",
    $libro->id
));

// Verificar si el usuario ya tiene un préstamo activo
$tiene_prestamo = false;
if ($usuario_id) {
    $tiene_prestamo = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_prestamos WHERE libro_id = %d AND prestatario_id = %d AND estado IN ('pendiente', 'activo')",
        $libro->id,
        $usuario_id
    ));
}

// Verificar si el usuario ya valoró
$ya_valoro = false;
if ($usuario_id) {
    $ya_valoro = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $tabla_resenas WHERE libro_id = %d AND usuario_id = %d",
        $libro->id,
        $usuario_id
    ));
}
?>

<div class="libro-detalle-wrapper">
    <a href="<?php echo remove_query_arg('libro_id'); ?>" class="btn btn-outline btn-sm" style="margin-bottom: 1rem;">
        <span class="dashicons dashicons-arrow-left-alt2"></span>
        <?php _e('Volver al catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <div class="libro-detalle">
        <div class="libro-detalle-portada">
            <?php if ($libro->portada_url): ?>
                <img src="<?php echo esc_url($libro->portada_url); ?>" alt="<?php echo esc_attr($libro->titulo); ?>">
            <?php endif; ?>
        </div>

        <div class="libro-detalle-info">
            <h1><?php echo esc_html($libro->titulo); ?></h1>
            <p class="libro-detalle-autor"><?php echo esc_html($libro->autor); ?></p>

            <?php if ($libro->valoracion_media > 0): ?>
                <div class="libro-valoracion-display">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="dashicons dashicons-star-<?php echo $i <= round($libro->valoracion_media) ? 'filled' : 'empty'; ?>" style="color: #fbbf24;"></span>
                    <?php endfor; ?>
                    <span style="margin-left: 0.5rem; color: #6b7280;"><?php echo number_format($libro->valoracion_media, 1); ?> (<?php echo count($resenas); ?> <?php _e('reseñas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>)</span>
                </div>
            <?php endif; ?>

            <div class="libro-detalle-meta">
                <?php if ($libro->editorial): ?>
                    <div class="libro-detalle-meta-item">
                        <label><?php _e('Editorial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <span><?php echo esc_html($libro->editorial); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($libro->ano_publicacion): ?>
                    <div class="libro-detalle-meta-item">
                        <label><?php _e('Año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <span><?php echo esc_html($libro->ano_publicacion); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($libro->genero): ?>
                    <div class="libro-detalle-meta-item">
                        <label><?php _e('Género', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <span><?php echo esc_html($libro->genero); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($libro->num_paginas): ?>
                    <div class="libro-detalle-meta-item">
                        <label><?php _e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <span><?php echo esc_html($libro->num_paginas); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($libro->idioma): ?>
                    <div class="libro-detalle-meta-item">
                        <label><?php _e('Idioma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <span><?php echo esc_html($libro->idioma); ?></span>
                    </div>
                <?php endif; ?>

                <div class="libro-detalle-meta-item">
                    <label><?php _e('Estado físico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <span><?php echo esc_html(ucfirst($libro->estado_fisico)); ?></span>
                </div>
            </div>

            <div class="libro-detalle-propietario">
                <div class="libro-detalle-propietario-avatar">
                    <?php echo strtoupper(substr($propietario ? $propietario->display_name : 'V', 0, 1)); ?>
                </div>
                <div class="libro-detalle-propietario-info">
                    <strong><?php echo esc_html($propietario ? $propietario->display_name : __('Vecino', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></strong>
                    <span>
                        <?php
                        $tipo_texto = [
                            'donado' => __('Donado a la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'prestamo' => __('Disponible para préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'intercambio' => __('Disponible para intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ];
                        echo $tipo_texto[$libro->tipo] ?? '';
                        ?>
                    </span>
                </div>
            </div>

            <?php if ($libro->descripcion): ?>
                <div class="libro-detalle-descripcion">
                    <?php echo wp_kses_post(wpautop($libro->descripcion)); ?>
                </div>
            <?php endif; ?>

            <div class="libro-detalle-acciones">
                <?php if (!$es_propietario): ?>
                    <?php if ($libro->disponibilidad === 'disponible'): ?>
                        <?php if ($tiene_prestamo): ?>
                            <button class="btn btn-outline" disabled><?php _e('Solicitud enviada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <?php else: ?>
                            <button class="btn btn-primary btn-solicitar-prestamo" data-libro-id="<?php echo $libro->id; ?>">
                                <span class="dashicons dashicons-book"></span>
                                <?php _e('Solicitar préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php endif; ?>
                    <?php elseif ($libro->disponibilidad === 'prestado'): ?>
                        <button class="btn btn-warning btn-reservar" data-libro-id="<?php echo $libro->id; ?>">
                            <span class="dashicons dashicons-bell"></span>
                            <?php _e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php else: ?>
                        <button class="btn btn-outline" disabled><?php _e('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?php echo add_query_arg('editar', $libro->id, get_permalink()); ?>" class="btn btn-outline">
                        <span class="dashicons dashicons-edit"></span>
                        <?php _e('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reseñas -->
    <div class="libro-resenas">
        <h3><?php _e('Reseñas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <?php if (is_user_logged_in() && !$ya_valoro && !$es_propietario): ?>
            <form class="form-resena" data-libro-id="<?php echo $libro->id; ?>" style="background: #f9fafb; padding: 1.5rem; border-radius: 12px; margin-bottom: 1.5rem;">
                <div class="libro-valoracion-estrellas" data-valoracion="0" style="margin-bottom: 1rem;">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <span class="star dashicons dashicons-star-empty" data-valor="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                </div>
                <div class="form-grupo" style="margin-bottom: 1rem;">
                    <textarea name="resena" placeholder="<?php esc_attr_e('Escribe tu reseña (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" rows="3"></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><?php _e('Enviar reseña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </form>
        <?php endif; ?>

        <?php if ($resenas): ?>
            <?php foreach ($resenas as $resena): ?>
                <div class="resena-item">
                    <div class="resena-header">
                        <span class="resena-usuario"><?php echo esc_html($resena->usuario_nombre); ?></span>
                        <span class="resena-fecha"><?php echo date_i18n(get_option('date_format'), strtotime($resena->fecha_creacion)); ?></span>
                    </div>
                    <div class="resena-valoracion">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="dashicons dashicons-star-<?php echo $i <= $resena->valoracion ? 'filled' : 'empty'; ?>"></span>
                        <?php endfor; ?>
                    </div>
                    <?php if ($resena->resena): ?>
                        <div class="resena-texto"><?php echo esc_html($resena->resena); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="color: #6b7280;"><?php _e('Aún no hay reseñas para este libro.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        <?php endif; ?>
    </div>
</div>
