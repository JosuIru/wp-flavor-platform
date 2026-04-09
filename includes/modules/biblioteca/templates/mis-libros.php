<?php
/**
 * Template: Mis Libros
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';

$usuario_id = get_current_user_id();

// Mis libros
$mis_libros = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_libros WHERE propietario_id = %d ORDER BY fecha_agregado DESC",
    $usuario_id
));

// Solicitudes pendientes de mis libros
$solicitudes_pendientes = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, l.titulo, l.autor, l.portada_url,
            u.display_name as prestatario_nombre, u.user_email as prestatario_email
     FROM $tabla_prestamos p
     INNER JOIN $tabla_libros l ON p.libro_id = l.id
     LEFT JOIN {$wpdb->users} u ON p.prestatario_id = u.ID
     WHERE p.prestamista_id = %d AND p.estado = 'pendiente'
     ORDER BY p.fecha_solicitud DESC",
    $usuario_id
));
?>

<div class="biblioteca-wrapper">
    <div class="mis-libros-header">
        <h2 class="biblioteca-titulo"><?php _e('Mis Libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <a href="<?php echo add_query_arg('vista', 'agregar', get_permalink()); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php _e('Agregar libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

    <?php if ($solicitudes_pendientes): ?>
        <div style="margin-bottom: 2rem;">
            <h3 style="font-size: 1.25rem; margin-bottom: 1rem;">
                <span class="dashicons dashicons-bell" style="color: #f59e0b;"></span>
                <?php printf(__('Solicitudes pendientes (%d)', FLAVOR_PLATFORM_TEXT_DOMAIN), count($solicitudes_pendientes)); ?>
            </h3>

            <?php foreach ($solicitudes_pendientes as $solicitud): ?>
                <div class="solicitud-card">
                    <div class="solicitud-card-header">
                        <div class="solicitud-solicitante">
                            <div class="solicitud-avatar">
                                <?php echo strtoupper(substr($solicitud->prestatario_nombre, 0, 1)); ?>
                            </div>
                            <div class="solicitud-info">
                                <strong><?php echo esc_html($solicitud->prestatario_nombre); ?></strong>
                                <span><?php _e('Quiere prestado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <strong><?php echo esc_html($solicitud->titulo); ?></strong></span>
                            </div>
                        </div>
                        <span style="font-size: 0.875rem; color: #6b7280;">
                            <?php echo date_i18n(get_option('date_format'), strtotime($solicitud->fecha_solicitud)); ?>
                        </span>
                    </div>

                    <?php if ($solicitud->notas_prestatario): ?>
                        <div class="solicitud-notas">
                            "<?php echo esc_html($solicitud->notas_prestatario); ?>"
                        </div>
                    <?php endif; ?>

                    <div class="solicitud-acciones">
                        <button class="btn btn-success btn-sm btn-aprobar-prestamo" data-prestamo-id="<?php echo $solicitud->id; ?>">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button class="btn btn-danger btn-sm btn-rechazar-prestamo" data-prestamo-id="<?php echo $solicitud->id; ?>">
                            <span class="dashicons dashicons-no"></span>
                            <?php _e('Rechazar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($mis_libros): ?>
        <div class="mis-libros-grid">
            <?php foreach ($mis_libros as $libro): ?>
                <div class="mi-libro-card">
                    <div class="mi-libro-card-header">
                        <div class="mi-libro-card-portada">
                            <?php if ($libro->portada_url): ?>
                                <img src="<?php echo esc_url($libro->portada_url); ?>" alt="<?php echo esc_attr($libro->titulo); ?>">
                            <?php endif; ?>
                        </div>
                        <div class="mi-libro-card-info">
                            <h4><?php echo esc_html($libro->titulo); ?></h4>
                            <p><?php echo esc_html($libro->autor); ?></p>
                            <span class="mi-libro-card-estado <?php echo esc_attr($libro->disponibilidad); ?>">
                                <?php
                                $estados = [
                                    'disponible' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'prestado' => __('Prestado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'reservado' => __('Reservado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'no_disponible' => __('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                ];
                                echo $estados[$libro->disponibilidad] ?? $libro->disponibilidad;
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="mi-libro-card-footer">
                        <span style="font-size: 0.75rem; color: #6b7280;">
                            <?php printf(__('%d veces prestado', FLAVOR_PLATFORM_TEXT_DOMAIN), $libro->veces_prestado); ?>
                        </span>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-outline btn-sm btn-editar-libro" data-libro-id="<?php echo $libro->id; ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <?php if ($libro->disponibilidad === 'disponible'): ?>
                                <button class="btn btn-danger btn-sm btn-eliminar-libro" data-libro-id="<?php echo $libro->id; ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="biblioteca-empty">
            <span class="dashicons dashicons-book"></span>
            <h3><?php _e('Aún no tienes libros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php _e('Comparte tus libros con la comunidad y empieza a ganar puntos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <a href="<?php echo add_query_arg('vista', 'agregar', get_permalink()); ?>" class="btn btn-primary">
                <?php _e('Agregar mi primer libro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    <?php endif; ?>
</div>
