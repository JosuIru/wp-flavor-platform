<?php
/**
 * Template: Listado de Espacios
 */

if (!defined('ABSPATH')) {
    exit;
}

// Funcion helper para obtener la primera imagen de fotos JSON
if (!function_exists('espacios_get_primera_imagen')) {
    function espacios_get_primera_imagen($fotos_json) {
        if (empty($fotos_json)) return '';
        $fotos = json_decode($fotos_json, true);
        if (is_array($fotos) && !empty($fotos)) {
            return $fotos[0];
        }
        return '';
    }
}

global $wpdb;
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';

// Filtros
$tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$capacidad_min = isset($_GET['capacidad']) ? intval($_GET['capacidad']) : 0;
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Query base
$where = "WHERE estado = 'disponible'";
$params = [];

if ($tipo) {
    $where .= " AND tipo = %s";
    $params[] = $tipo;
}

if ($capacidad_min > 0) {
    $where .= " AND capacidad_personas >= %d";
    $params[] = $capacidad_min;
}

if ($buscar) {
    $where .= " AND (nombre LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s)";
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

$query = "SELECT * FROM $tabla_espacios $where ORDER BY nombre ASC";

if (!empty($params)) {
    $espacios = $wpdb->get_results($wpdb->prepare($query, $params));
} else {
    $espacios = $wpdb->get_results($query);
}

// Tipos únicos para filtro
$tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_espacios WHERE estado = 'disponible' ORDER BY tipo");
?>

<div class="espacios-wrapper">
    <div class="espacios-header">
        <h2 class="espacios-titulo"><?php _e('Espacios Comunes', 'flavor-chat-ia'); ?></h2>
        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo add_query_arg('vista', 'mis-reservas', get_permalink()); ?>" class="btn btn-outline">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php _e('Mis Reservas', 'flavor-chat-ia'); ?>
            </a>
        <?php endif; ?>
    </div>

    <div class="espacios-filtros">
        <div class="espacios-filtro-grupo">
            <label><?php _e('Tipo', 'flavor-chat-ia'); ?></label>
            <select name="tipo" onchange="this.form.submit()">
                <option value=""><?php _e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                <?php foreach ($tipos_disponibles as $tipo_opcion): ?>
                    <option value="<?php echo esc_attr($tipo_opcion); ?>" <?php selected($tipo, $tipo_opcion); ?>>
                        <?php echo esc_html(ucfirst($tipo_opcion)); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="espacios-filtro-grupo">
            <label><?php _e('Capacidad mínima', 'flavor-chat-ia'); ?></label>
            <select name="capacidad" onchange="this.form.submit()">
                <option value=""><?php _e('Cualquiera', 'flavor-chat-ia'); ?></option>
                <option value="5" <?php selected($capacidad_min, 5); ?>>5+ personas</option>
                <option value="10" <?php selected($capacidad_min, 10); ?>>10+ personas</option>
                <option value="20" <?php selected($capacidad_min, 20); ?>>20+ personas</option>
                <option value="50" <?php selected($capacidad_min, 50); ?>>50+ personas</option>
            </select>
        </div>

        <div class="espacios-filtro-grupo espacios-buscar">
            <label><?php _e('Buscar', 'flavor-chat-ia'); ?></label>
            <input type="text" name="buscar" value="<?php echo esc_attr($buscar); ?>"
                   placeholder="<?php esc_attr_e('Nombre, ubicación...', 'flavor-chat-ia'); ?>">
        </div>
    </div>

    <?php if ($espacios): ?>
        <div class="espacios-grid">
            <?php foreach ($espacios as $espacio): ?>
                <?php
                // Determinar disponibilidad actual
                $ahora = current_time('mysql');
                $reserva_activa = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}flavor_espacios_reservas
                     WHERE espacio_id = %d
                     AND estado IN ('confirmada', 'en_curso')
                     AND fecha_inicio <= %s
                     AND fecha_fin > %s",
                    $espacio->id,
                    $ahora,
                    $ahora
                ));
                $disponibilidad = $reserva_activa ? 'ocupado' : 'disponible';

                // Obtener equipamiento
                $equipamiento = $wpdb->get_results($wpdb->prepare(
                    "SELECT nombre FROM {$wpdb->prefix}flavor_espacios_equipamiento WHERE espacio_id = %d LIMIT 3",
                    $espacio->id
                ));
                ?>
                <div class="espacio-card">
                    <div class="espacio-card-imagen">
                        <?php $imagen_espacio = espacios_get_primera_imagen($espacio->fotos ?? ''); ?>
                        <?php if ($imagen_espacio): ?>
                            <img src="<?php echo esc_url($imagen_espacio); ?>" alt="<?php echo esc_attr($espacio->nombre); ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <span class="dashicons dashicons-building"></span>
                            </div>
                        <?php endif; ?>
                        <span class="espacio-card-estado <?php echo esc_attr($disponibilidad); ?>">
                            <?php echo $disponibilidad === 'disponible' ? __('Disponible', 'flavor-chat-ia') : __('Ocupado', 'flavor-chat-ia'); ?>
                        </span>
                    </div>

                    <div class="espacio-card-body">
                        <h3 class="espacio-card-titulo"><?php echo esc_html($espacio->nombre); ?></h3>
                        <div class="espacio-card-ubicacion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($espacio->ubicacion); ?>
                        </div>

                        <div class="espacio-card-meta">
                            <div class="espacio-card-meta-item">
                                <span class="dashicons dashicons-groups"></span>
                                <?php printf(__('%d personas', 'flavor-chat-ia'), $espacio->capacidad_personas); ?>
                            </div>
                            <?php if ($espacio->precio_hora > 0): ?>
                                <div class="espacio-card-meta-item espacio-card-precio">
                                    <?php echo number_format($espacio->precio_hora, 2); ?>€/h
                                </div>
                            <?php else: ?>
                                <div class="espacio-card-meta-item" style="color: #10b981;">
                                    <?php _e('Gratuito', 'flavor-chat-ia'); ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($equipamiento): ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 0.25rem; margin-top: 0.5rem;">
                                <?php foreach ($equipamiento as $equipo): ?>
                                    <span style="font-size: 0.75rem; padding: 2px 8px; background: #f3f4f6; border-radius: 4px;">
                                        <?php echo esc_html($equipo->nombre); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="espacio-card-footer">
                        <a href="<?php echo add_query_arg('espacio_id', $espacio->id, get_permalink()); ?>" class="btn btn-primary btn-sm" style="flex: 1;">
                            <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                        </a>
                        <?php if (is_user_logged_in() && $disponibilidad === 'disponible'): ?>
                            <button class="btn btn-outline btn-sm btn-reservar-espacio" data-espacio-id="<?php echo $espacio->id; ?>">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="espacios-empty">
            <span class="dashicons dashicons-building"></span>
            <h3><?php _e('No se encontraron espacios', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('No hay espacios disponibles con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
            <?php if ($tipo || $capacidad_min || $buscar): ?>
                <a href="<?php echo remove_query_arg(['tipo', 'capacidad', 'buscar']); ?>" class="btn btn-primary">
                    <?php _e('Ver todos los espacios', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
