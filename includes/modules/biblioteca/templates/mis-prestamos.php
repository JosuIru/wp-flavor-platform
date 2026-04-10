<?php
/**
 * Template: Mis Préstamos
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_prestamos = $wpdb->prefix . 'flavor_biblioteca_prestamos';
$tabla_libros = $wpdb->prefix . 'flavor_biblioteca_libros';
$tabla_reservas = $wpdb->prefix . 'flavor_biblioteca_reservas';

$usuario_id = get_current_user_id();

// Libros que tengo prestados
$prestamos_activos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, l.titulo, l.autor, l.portada_url, l.propietario_id,
            u.display_name as propietario_nombre
     FROM $tabla_prestamos p
     INNER JOIN $tabla_libros l ON p.libro_id = l.id
     LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
     WHERE p.prestatario_id = %d AND p.estado IN ('pendiente', 'activo', 'retrasado')
     ORDER BY p.fecha_solicitud DESC",
    $usuario_id
));

// Historial de préstamos
$historial = $wpdb->get_results($wpdb->prepare(
    "SELECT p.*, l.titulo, l.autor, l.portada_url,
            u.display_name as propietario_nombre
     FROM $tabla_prestamos p
     INNER JOIN $tabla_libros l ON p.libro_id = l.id
     LEFT JOIN {$wpdb->users} u ON l.propietario_id = u.ID
     WHERE p.prestatario_id = %d AND p.estado IN ('devuelto', 'rechazado')
     ORDER BY p.fecha_devolucion_real DESC
     LIMIT 20",
    $usuario_id
));

// Mis reservas
$reservas = $wpdb->get_results($wpdb->prepare(
    "SELECT r.*, l.titulo, l.autor, l.portada_url
     FROM $tabla_reservas r
     INNER JOIN $tabla_libros l ON r.libro_id = l.id
     WHERE r.usuario_id = %d AND r.estado IN ('pendiente', 'confirmada')
     ORDER BY r.fecha_solicitud DESC",
    $usuario_id
));

$biblioteca_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Biblioteca_Module')
    : 'Flavor_Chat_Biblioteca_Module';
$settings_module = new $biblioteca_module_class();
$settings = $settings_module->get_settings();
$max_renovaciones = $settings['renovaciones_maximas'] ?? 2;
?>

<div class="biblioteca-wrapper">
    <h2 class="biblioteca-titulo"><?php _e('Mis Préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <div class="mis-prestamos-tabs">
        <button class="mis-prestamos-tab active" data-tab="activos">
            <?php _e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($prestamos_activos): ?>
                <span style="background: #6366f1; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 0.75rem; margin-left: 0.5rem;">
                    <?php echo count($prestamos_activos); ?>
                </span>
            <?php endif; ?>
        </button>
        <button class="mis-prestamos-tab" data-tab="reservas">
            <?php _e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <?php if ($reservas): ?>
                <span style="background: #f59e0b; color: #fff; border-radius: 10px; padding: 2px 8px; font-size: 0.75rem; margin-left: 0.5rem;">
                    <?php echo count($reservas); ?>
                </span>
            <?php endif; ?>
        </button>
        <button class="mis-prestamos-tab" data-tab="historial"><?php _e('Historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </div>

    <!-- Panel: Activos -->
    <div id="activos" class="mis-prestamos-panel">
        <?php if ($prestamos_activos): ?>
            <?php foreach ($prestamos_activos as $prestamo): ?>
                <div class="prestamo-card">
                    <div class="prestamo-card-header">
                        <div class="prestamo-card-portada">
                            <?php if ($prestamo->portada_url): ?>
                                <img src="<?php echo esc_url($prestamo->portada_url); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="prestamo-card-info">
                            <h4><?php echo esc_html($prestamo->titulo); ?></h4>
                            <p><?php echo esc_html($prestamo->autor); ?></p>
                            <span class="prestamo-card-estado <?php echo esc_attr($prestamo->estado); ?>">
                                <?php
                                $estados = [
                                    'pendiente' => __('Pendiente de aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'activo' => __('En préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    'retrasado' => __('Retrasado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                ];
                                echo $estados[$prestamo->estado] ?? $prestamo->estado;
                                ?>
                            </span>
                        </div>
                    </div>

                    <div class="prestamo-card-detalles">
                        <div class="prestamo-card-detalle">
                            <label><?php _e('Propietario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <span><?php echo esc_html($prestamo->propietario_nombre); ?></span>
                        </div>

                        <?php if ($prestamo->estado === 'activo' || $prestamo->estado === 'retrasado'): ?>
                            <div class="prestamo-card-detalle">
                                <label><?php _e('Fecha préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <span><?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_prestamo)); ?></span>
                            </div>
                            <div class="prestamo-card-detalle">
                                <label><?php _e('Devolver antes de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <span style="<?php echo $prestamo->estado === 'retrasado' ? 'color: #ef4444; font-weight: 600;' : ''; ?>">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($prestamo->fecha_devolucion_prevista)); ?>
                                </span>
                            </div>
                            <div class="prestamo-card-detalle">
                                <label><?php _e('Renovaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <span><?php echo $prestamo->renovaciones; ?>/<?php echo $max_renovaciones; ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($prestamo->punto_entrega): ?>
                            <div class="prestamo-card-detalle">
                                <label><?php _e('Punto de entrega', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <span><?php echo esc_html($prestamo->punto_entrega); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($prestamo->estado === 'activo' || $prestamo->estado === 'retrasado'): ?>
                        <div class="prestamo-card-acciones">
                            <button class="btn btn-success btn-devolver" data-prestamo-id="<?php echo $prestamo->id; ?>">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Marcar como devuelto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                            <?php if ($prestamo->renovaciones < $max_renovaciones): ?>
                                <button class="btn btn-outline btn-renovar" data-prestamo-id="<?php echo $prestamo->id; ?>">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php _e('Renovar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="biblioteca-empty">
                <span class="dashicons dashicons-book"></span>
                <h3><?php _e('No tienes préstamos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Explora el catálogo y solicita tu primer libro.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo remove_query_arg('vista'); ?>" class="btn btn-primary">
                    <?php _e('Ver catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel: Reservas -->
    <div id="reservas" class="mis-prestamos-panel" style="display: none;">
        <?php if ($reservas): ?>
            <?php foreach ($reservas as $reserva): ?>
                <div class="prestamo-card">
                    <div class="prestamo-card-header">
                        <div class="prestamo-card-portada">
                            <?php if ($reserva->portada_url): ?>
                                <img src="<?php echo esc_url($reserva->portada_url); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="prestamo-card-info">
                            <h4><?php echo esc_html($reserva->titulo); ?></h4>
                            <p><?php echo esc_html($reserva->autor); ?></p>
                            <span class="prestamo-card-estado <?php echo $reserva->estado === 'confirmada' ? 'activo' : 'pendiente'; ?>">
                                <?php echo $reserva->estado === 'confirmada' ? __('¡Disponible para recoger!', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('En espera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        </div>
                    </div>

                    <div class="prestamo-card-detalles">
                        <div class="prestamo-card-detalle">
                            <label><?php _e('Fecha reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <span><?php echo date_i18n(get_option('date_format'), strtotime($reserva->fecha_solicitud)); ?></span>
                        </div>
                        <?php if ($reserva->estado === 'confirmada'): ?>
                            <div class="prestamo-card-detalle">
                                <label><?php _e('Recoger antes de', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                                <span style="color: #f59e0b; font-weight: 600;">
                                    <?php echo date_i18n(get_option('date_format') . ' H:i', strtotime($reserva->fecha_expiracion)); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="prestamo-card-acciones">
                        <button class="btn btn-outline btn-sm btn-cancelar-reserva" data-reserva-id="<?php echo $reserva->id; ?>">
                            <?php _e('Cancelar reserva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="biblioteca-empty">
                <span class="dashicons dashicons-bell"></span>
                <h3><?php _e('No tienes reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Puedes reservar libros que estén prestados para recibirlos cuando estén disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Panel: Historial -->
    <div id="historial" class="mis-prestamos-panel" style="display: none;">
        <?php if ($historial): ?>
            <?php foreach ($historial as $prestamo): ?>
                <div class="prestamo-card" style="opacity: 0.8;">
                    <div class="prestamo-card-header">
                        <div class="prestamo-card-portada">
                            <?php if ($prestamo->portada_url): ?>
                                <img src="<?php echo esc_url($prestamo->portada_url); ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="prestamo-card-info">
                            <h4><?php echo esc_html($prestamo->titulo); ?></h4>
                            <p><?php echo esc_html($prestamo->autor); ?></p>
                            <span style="font-size: 0.75rem; color: #6b7280;">
                                <?php
                                if ($prestamo->estado === 'devuelto') {
                                    printf(__('Devuelto el %s', FLAVOR_PLATFORM_TEXT_DOMAIN), date_i18n(get_option('date_format'), strtotime($prestamo->fecha_devolucion_real)));
                                } else {
                                    _e('Rechazado', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="biblioteca-empty">
                <span class="dashicons dashicons-backup"></span>
                <h3><?php _e('Sin historial', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p><?php _e('Aquí aparecerán los libros que hayas devuelto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
