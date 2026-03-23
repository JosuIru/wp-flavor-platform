<?php
/**
 * Template: Listado de Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_eventos = $wpdb->prefix . 'flavor_eventos';

// Filtros
$tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';
$buscar = isset($_GET['buscar']) ? sanitize_text_field($_GET['buscar']) : '';

// Obtener próximos eventos
$where = "WHERE estado = 'publicado' AND fecha_inicio >= CURDATE()";
$params = [];

if ($tipo) {
    $where .= " AND tipo = %s";
    $params[] = $tipo;
}

if ($buscar) {
    $where .= " AND (titulo LIKE %s OR descripcion LIKE %s OR ubicacion LIKE %s)";
    $buscar_like = '%' . $wpdb->esc_like($buscar) . '%';
    $params[] = $buscar_like;
    $params[] = $buscar_like;
    $params[] = $buscar_like;
}

$limite = isset($limit) ? intval($limit) : 12;

$query = "SELECT * FROM $tabla_eventos $where ORDER BY fecha_inicio ASC LIMIT %d";
$params[] = $limite;

$eventos = $wpdb->get_results($wpdb->prepare($query, $params));

// Obtener tipos para filtro
$tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_eventos WHERE estado = 'publicado' AND fecha_inicio >= CURDATE() ORDER BY tipo");

// Función auxiliar para extraer hora de datetime
$extraer_hora = function($datetime) {
    if (empty($datetime)) return '';
    return date('H:i', strtotime($datetime));
};

$usuario_id = get_current_user_id();
?>

<div class="eventos-listado-wrapper">
    <div class="eventos-filtros">
        <div class="eventos-filtro-grupo">
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

        <div class="eventos-filtro-grupo eventos-buscar">
            <label><?php _e('Buscar', 'flavor-chat-ia'); ?></label>
            <input type="text" name="buscar" value="<?php echo esc_attr($buscar); ?>"
                   placeholder="<?php esc_attr_e('Nombre, ubicación...', 'flavor-chat-ia'); ?>">
        </div>
    </div>

    <?php if ($eventos): ?>
        <div class="eventos-grid">
            <?php foreach ($eventos as $evento): ?>
                <?php
                // Verificar si el usuario está inscrito
                $inscrito = false;
                if ($usuario_id) {
                    $inscrito = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}flavor_eventos_inscripciones
                         WHERE evento_id = %d AND usuario_id = %d AND estado != 'cancelada'",
                        $evento->id,
                        $usuario_id
                    ));
                }

                // Calcular plazas disponibles
                $inscritos = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}flavor_eventos_inscripciones
                     WHERE evento_id = %d AND estado IN ('confirmada', 'pendiente')",
                    $evento->id
                ));
                $aforo_maximo = isset($evento->aforo_maximo) ? $evento->aforo_maximo : 0;
                $plazas_disponibles = $aforo_maximo > 0 ? $aforo_maximo - $inscritos : null;
                ?>
                <div class="evento-card">
                    <div class="evento-card-imagen">
                        <?php if (!empty($evento->imagen)): ?>
                            <img src="<?php echo esc_url($evento->imagen); ?>" alt="<?php echo esc_attr($evento->titulo); ?>">
                        <?php else: ?>
                            <div class="evento-card-placeholder">
                                <span class="dashicons dashicons-calendar-alt"></span>
                            </div>
                        <?php endif; ?>
                        <?php $tipo_evento = isset($evento->tipo) ? $evento->tipo : ''; ?>
                        <?php if ($tipo_evento): ?>
                            <span class="evento-card-tipo"><?php echo esc_html(ucfirst($tipo_evento)); ?></span>
                        <?php endif; ?>
                        <?php if ($plazas_disponibles !== null && $plazas_disponibles <= 5 && $plazas_disponibles > 0): ?>
                            <span class="evento-card-plazas"><?php printf(__('%d plazas', 'flavor-chat-ia'), $plazas_disponibles); ?></span>
                        <?php elseif ($plazas_disponibles === 0): ?>
                            <span class="evento-card-completo"><?php _e('Completo', 'flavor-chat-ia'); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="evento-card-body">
                        <h3 class="evento-card-titulo"><?php echo esc_html($evento->titulo); ?></h3>

                        <div class="evento-card-meta">
                            <span class="evento-card-fecha">
                                <span class="dashicons dashicons-calendar"></span>
                                <?php echo date_i18n(get_option('date_format'), strtotime($evento->fecha_inicio)); ?>
                            </span>
                            <?php
                            $hora_evento = $extraer_hora($evento->fecha_inicio);
                            if ($hora_evento && $hora_evento !== '00:00'): ?>
                                <span class="evento-card-hora">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php echo esc_html($hora_evento); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <?php if ($evento->ubicacion): ?>
                            <div class="evento-card-ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($evento->ubicacion); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($evento->descripcion): ?>
                            <p class="evento-card-descripcion">
                                <?php echo esc_html(wp_trim_words($evento->descripcion, 15)); ?>
                            </p>
                        <?php endif; ?>

                        <div class="evento-card-precio">
                            <?php if ($evento->precio > 0): ?>
                                <span class="precio"><?php echo number_format($evento->precio, 2); ?>€</span>
                            <?php else: ?>
                                <span class="gratuito"><?php _e('Gratuito', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="evento-card-footer">
                        <a href="<?php echo add_query_arg('evento_id', $evento->id, get_permalink()); ?>" class="btn btn-outline btn-sm">
                            <?php _e('Ver detalles', 'flavor-chat-ia'); ?>
                        </a>
                        <?php if (is_user_logged_in()): ?>
                            <?php if ($inscrito): ?>
                                <span class="btn btn-success btn-sm">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Inscrito', 'flavor-chat-ia'); ?>
                                </span>
                            <?php elseif ($plazas_disponibles !== 0): ?>
                                <button class="btn btn-primary btn-sm btn-inscribirse" data-evento-id="<?php echo $evento->id; ?>">
                                    <?php _e('Inscribirse', 'flavor-chat-ia'); ?>
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="eventos-empty">
            <span class="dashicons dashicons-calendar-alt"></span>
            <h3><?php _e('No hay eventos próximos', 'flavor-chat-ia'); ?></h3>
            <p><?php _e('No se encontraron eventos con los filtros seleccionados.', 'flavor-chat-ia'); ?></p>
            <?php if ($tipo || $buscar): ?>
                <a href="<?php echo remove_query_arg(['tipo', 'buscar']); ?>" class="btn btn-primary">
                    <?php _e('Ver todos los eventos', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.eventos-listado-wrapper { max-width: 1200px; margin: 0 auto; }
.eventos-filtros { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.eventos-filtro-grupo { display: flex; flex-direction: column; gap: 0.25rem; }
.eventos-filtro-grupo label { font-size: 0.75rem; font-weight: 600; color: #6b7280; text-transform: uppercase; }
.eventos-filtro-grupo select, .eventos-filtro-grupo input { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; min-width: 150px; }
.eventos-buscar { flex: 1; min-width: 200px; }
.eventos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
.evento-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; display: flex; flex-direction: column; transition: all 0.2s; }
.evento-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.12); transform: translateY(-4px); }
.evento-card-imagen { position: relative; height: 180px; background: #f3f4f6; }
.evento-card-imagen img { width: 100%; height: 100%; object-fit: cover; }
.evento-card-placeholder { display: flex; align-items: center; justify-content: center; height: 100%; }
.evento-card-placeholder .dashicons { font-size: 48px; color: #9ca3af; }
.evento-card-tipo { position: absolute; top: 10px; left: 10px; background: rgba(0,0,0,0.6); color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; }
.evento-card-plazas { position: absolute; top: 10px; right: 10px; background: #fbbf24; color: #78350f; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
.evento-card-completo { position: absolute; top: 10px; right: 10px; background: #ef4444; color: white; padding: 4px 10px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
.evento-card-body { padding: 1.25rem; flex: 1; }
.evento-card-titulo { margin: 0 0 0.75rem; font-size: 1.125rem; }
.evento-card-meta { display: flex; gap: 1rem; margin-bottom: 0.5rem; font-size: 0.875rem; color: #6b7280; }
.evento-card-meta .dashicons { font-size: 14px; width: 14px; height: 14px; }
.evento-card-ubicacion { font-size: 0.875rem; color: #6b7280; margin-bottom: 0.75rem; }
.evento-card-ubicacion .dashicons { font-size: 14px; width: 14px; height: 14px; }
.evento-card-descripcion { margin: 0 0 1rem; font-size: 0.875rem; color: #6b7280; line-height: 1.5; }
.evento-card-precio .precio { font-size: 1.25rem; font-weight: 700; color: #4f46e5; }
.evento-card-precio .gratuito { font-size: 1rem; font-weight: 600; color: #10b981; }
.evento-card-footer { padding: 1rem 1.25rem; border-top: 1px solid #f3f4f6; display: flex; gap: 0.5rem; justify-content: flex-end; }
.eventos-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.eventos-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.eventos-empty h3 { margin: 0 0 0.5rem; color: #374151; }
.eventos-empty p { margin: 0 0 1.5rem; color: #6b7280; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; }
.btn-primary { background: #4f46e5; color: white; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-success { background: #10b981; color: white; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8rem; }
</style>
