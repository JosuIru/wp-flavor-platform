<?php
/**
 * Template: Equipamiento de Espacios
 * Vista del equipamiento disponible en los espacios
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
$tabla_equipamiento = $wpdb->prefix . 'flavor_espacios_equipamiento';

// Obtener espacios con su equipamiento
$espacios = $wpdb->get_results(
    "SELECT e.*,
            (SELECT COUNT(*) FROM $tabla_equipamiento eq WHERE eq.espacio_id = e.id) as total_equipamiento
     FROM $tabla_espacios e
     WHERE e.estado = 'disponible'
     ORDER BY e.nombre"
);

// Tipos de equipamiento únicos para filtro
$tipos_equipamiento = $wpdb->get_col(
    "SELECT DISTINCT nombre FROM $tabla_equipamiento ORDER BY nombre"
);

// Filtro
$filtro_equipamiento = isset($_GET['equipo']) ? sanitize_text_field($_GET['equipo']) : '';

// Si hay filtro, obtener solo espacios que tengan ese equipamiento
if ($filtro_equipamiento) {
    $espacios = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT e.*,
                (SELECT COUNT(*) FROM $tabla_equipamiento eq WHERE eq.espacio_id = e.id) as total_equipamiento
         FROM $tabla_espacios e
         INNER JOIN $tabla_equipamiento eq ON e.id = eq.espacio_id
         WHERE e.estado = 'disponible' AND eq.nombre LIKE %s
         ORDER BY e.nombre",
        '%' . $wpdb->esc_like($filtro_equipamiento) . '%'
    ));
}
?>

<div class="espacios-wrapper">
    <div class="espacios-header">
        <h2 class="espacios-titulo"><?php _e('Equipamiento Disponible', 'flavor-platform'); ?></h2>
        <a href="<?php echo remove_query_arg(['vista', 'equipo']); ?>" class="btn btn-outline">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php _e('Ver espacios', 'flavor-platform'); ?>
        </a>
    </div>

    <p style="color: #6b7280; margin-bottom: 1.5rem;">
        <?php _e('Consulta el equipamiento disponible en cada espacio para planificar mejor tu reserva.', 'flavor-platform'); ?>
    </p>

    <!-- Filtro rápido por tipo de equipamiento -->
    <div style="margin-bottom: 2rem;">
        <label style="font-weight: 500; display: block; margin-bottom: 0.5rem;"><?php _e('Buscar espacios con:', 'flavor-platform'); ?></label>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <a href="<?php echo remove_query_arg('equipo'); ?>"
               class="btn btn-sm <?php echo !$filtro_equipamiento ? 'btn-primary' : 'btn-outline'; ?>">
                <?php _e('Todos', 'flavor-platform'); ?>
            </a>
            <?php foreach ($tipos_equipamiento as $tipo): ?>
                <a href="<?php echo add_query_arg('equipo', urlencode($tipo)); ?>"
                   class="btn btn-sm <?php echo $filtro_equipamiento === $tipo ? 'btn-primary' : 'btn-outline'; ?>">
                    <?php echo esc_html($tipo); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($espacios): ?>
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php foreach ($espacios as $espacio): ?>
                <?php
                // Obtener equipamiento del espacio
                $equipamiento = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $tabla_equipamiento WHERE espacio_id = %d ORDER BY nombre",
                    $espacio->id
                ));
                ?>
                <div class="reserva-card">
                    <div class="reserva-card-header">
                        <div class="reserva-card-imagen" style="width: 120px; height: 90px;">
                            <?php
                            $imagen_eq = '';
                            if (!empty($espacio->fotos)) {
                                $fotos_arr = json_decode($espacio->fotos, true);
                                if (is_array($fotos_arr) && !empty($fotos_arr)) {
                                    $imagen_eq = $fotos_arr[0];
                                }
                            }
                            ?>
                            <?php if ($imagen_eq): ?>
                                <img src="<?php echo esc_url($imagen_eq); ?>" alt="">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f3f4f6;">
                                    <span class="dashicons dashicons-building" style="color: #9ca3af; font-size: 32px; width: 32px; height: 32px;"></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="reserva-card-info">
                            <h4 style="margin-bottom: 0.25rem;">
                                <a href="<?php echo add_query_arg('espacio_id', $espacio->id, remove_query_arg(['vista', 'equipo'])); ?>" style="color: inherit; text-decoration: none;">
                                    <?php echo esc_html($espacio->nombre); ?>
                                </a>
                            </h4>
                            <p style="margin: 0;">
                                <span class="dashicons dashicons-location" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                <?php echo esc_html($espacio->ubicacion); ?>
                            </p>
                            <div style="margin-top: 0.5rem; display: flex; gap: 1rem; font-size: 0.875rem; color: #6b7280;">
                                <span>
                                    <span class="dashicons dashicons-groups" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                    <?php printf(__('Hasta %d personas', 'flavor-platform'), $espacio->capacidad_personas); ?>
                                </span>
                                <span>
                                    <?php echo ucfirst($espacio->tipo); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <?php if ($equipamiento): ?>
                        <div style="margin-top: 1rem;">
                            <label style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; display: block; margin-bottom: 0.5rem;">
                                <?php _e('Equipamiento disponible', 'flavor-platform'); ?>
                            </label>
                            <div class="equipamiento-lista">
                                <?php foreach ($equipamiento as $equipo): ?>
                                    <?php
                                    $resaltado = $filtro_equipamiento && stripos($equipo->nombre, $filtro_equipamiento) !== false;
                                    ?>
                                    <div class="equipamiento-item" style="<?php echo $resaltado ? 'background: rgba(99, 102, 241, 0.1); border: 1px solid #6366f1;' : ''; ?>">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                        <?php echo esc_html($equipo->nombre); ?>
                                        <?php if ($equipo->cantidad > 1): ?>
                                            <span style="color: #6b7280; font-size: 0.8rem;">(<?php echo $equipo->cantidad; ?>)</span>
                                        <?php endif; ?>
                                        <?php if ($equipo->descripcion): ?>
                                            <span title="<?php echo esc_attr($equipo->descripcion); ?>" style="cursor: help;">
                                                <span class="dashicons dashicons-info" style="font-size: 14px; width: 14px; height: 14px; color: #9ca3af;"></span>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p style="color: #9ca3af; font-style: italic; margin-top: 1rem;">
                            <?php _e('No hay equipamiento registrado para este espacio.', 'flavor-platform'); ?>
                        </p>
                    <?php endif; ?>

                    <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb;">
                        <a href="<?php echo add_query_arg('espacio_id', $espacio->id, remove_query_arg(['vista', 'equipo'])); ?>" class="btn btn-primary btn-sm">
                            <?php _e('Ver espacio', 'flavor-platform'); ?>
                        </a>
                        <?php if (is_user_logged_in()): ?>
                            <button class="btn btn-outline btn-sm btn-reservar-espacio" data-espacio-id="<?php echo $espacio->id; ?>">
                                <span class="dashicons dashicons-calendar-alt"></span>
                                <?php _e('Reservar', 'flavor-platform'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="espacios-empty">
            <span class="dashicons dashicons-clipboard"></span>
            <h3><?php _e('No se encontraron espacios', 'flavor-platform'); ?></h3>
            <?php if ($filtro_equipamiento): ?>
                <p><?php printf(__('No hay espacios con "%s" disponible.', 'flavor-platform'), esc_html($filtro_equipamiento)); ?></p>
                <a href="<?php echo remove_query_arg('equipo'); ?>" class="btn btn-primary">
                    <?php _e('Ver todos los espacios', 'flavor-platform'); ?>
                </a>
            <?php else: ?>
                <p><?php _e('No hay espacios disponibles en este momento.', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Resumen de equipamiento por tipo -->
    <?php if (!$filtro_equipamiento && $tipos_equipamiento): ?>
        <div style="margin-top: 3rem; padding-top: 2rem; border-top: 1px solid #e5e7eb;">
            <h3 style="margin-bottom: 1rem;"><?php _e('Resumen de equipamiento', 'flavor-platform'); ?></h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach ($tipos_equipamiento as $tipo): ?>
                    <?php
                    $count = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(DISTINCT espacio_id) FROM $tabla_equipamiento WHERE nombre = %s",
                        $tipo
                    ));
                    ?>
                    <a href="<?php echo add_query_arg('equipo', urlencode($tipo)); ?>"
                       style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; background: #f9fafb; border-radius: 8px; text-decoration: none; color: inherit; transition: all 0.2s;">
                        <span class="dashicons dashicons-yes-alt" style="color: #10b981;"></span>
                        <div style="flex: 1;">
                            <strong style="display: block;"><?php echo esc_html($tipo); ?></strong>
                            <span style="font-size: 0.875rem; color: #6b7280;">
                                <?php printf(_n('%d espacio', '%d espacios', $count, 'flavor-platform'), $count); ?>
                            </span>
                        </div>
                        <span class="dashicons dashicons-arrow-right-alt2" style="color: #9ca3af;"></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
