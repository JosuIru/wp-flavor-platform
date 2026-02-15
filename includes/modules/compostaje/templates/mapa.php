<?php
/**
 * Template: Mapa de Composteras
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

// Obtener todos los puntos de compostaje activos
$puntos = $wpdb->get_results(
    "SELECT id, nombre, descripcion, direccion, latitud, longitud, tipo,
            capacidad_litros, nivel_llenado_pct, fase_actual, horario_apertura,
            telefono_contacto, email_contacto, foto_url, estado
     FROM $tabla_puntos
     WHERE estado = 'activo'
     ORDER BY nombre ASC"
);

// Filtro por tipo
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Tipos disponibles
$tipos_disponibles = [
    'comunitario' => __('Comunitario', 'flavor-chat-ia'),
    'vecinal' => __('Vecinal', 'flavor-chat-ia'),
    'escolar' => __('Escolar', 'flavor-chat-ia'),
    'municipal' => __('Municipal', 'flavor-chat-ia'),
    'privado' => __('Privado', 'flavor-chat-ia'),
];

// Fases labels
$fases_labels = [
    'recepcion' => __('Recepción', 'flavor-chat-ia'),
    'activo' => __('Activo', 'flavor-chat-ia'),
    'maduracion' => __('Maduración', 'flavor-chat-ia'),
    'listo' => __('Compost listo', 'flavor-chat-ia'),
    'mantenimiento' => __('Mantenimiento', 'flavor-chat-ia'),
];

// Colores por tipo
$tipo_colors = [
    'comunitario' => '#10b981',
    'vecinal' => '#3b82f6',
    'escolar' => '#f59e0b',
    'municipal' => '#8b5cf6',
    'privado' => '#6b7280',
];

// Preparar datos para JS
$puntos_json = [];
foreach ($puntos as $punto) {
    if ($tipo_filtro && $punto->tipo !== $tipo_filtro) {
        continue;
    }
    $puntos_json[] = [
        'id' => (int) $punto->id,
        'nombre' => $punto->nombre,
        'descripcion' => $punto->descripcion,
        'direccion' => $punto->direccion,
        'lat' => (float) $punto->latitud,
        'lng' => (float) $punto->longitud,
        'tipo' => $punto->tipo,
        'tipo_label' => $tipos_disponibles[$punto->tipo] ?? $punto->tipo,
        'capacidad' => (int) $punto->capacidad_litros,
        'llenado' => (int) $punto->nivel_llenado_pct,
        'fase' => $punto->fase_actual,
        'fase_label' => $fases_labels[$punto->fase_actual] ?? $punto->fase_actual,
        'horario' => $punto->horario_apertura,
        'telefono' => $punto->telefono_contacto,
        'email' => $punto->email_contacto,
        'foto' => $punto->foto_url,
        'color' => $tipo_colors[$punto->tipo] ?? '#6b7280',
    ];
}
?>

<div class="compostaje-mapa-wrapper">
    <div class="mapa-header">
        <h2><?php esc_html_e('Puntos de Compostaje', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Encuentra el punto de compostaje más cercano a ti', 'flavor-chat-ia'); ?></p>
    </div>

    <!-- Filtros -->
    <div class="mapa-filtros">
        <div class="filtro-grupo">
            <label><?php esc_html_e('Filtrar por tipo', 'flavor-chat-ia'); ?></label>
            <select id="filtro-tipo" onchange="filtrarPuntos(this.value)">
                <option value=""><?php esc_html_e('Todos los tipos', 'flavor-chat-ia'); ?></option>
                <?php foreach ($tipos_disponibles as $tipo_key => $tipo_label): ?>
                    <option value="<?php echo esc_attr($tipo_key); ?>" <?php selected($tipo_filtro, $tipo_key); ?>>
                        <?php echo esc_html($tipo_label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtro-leyenda">
            <?php foreach ($tipos_disponibles as $tipo_key => $tipo_label): ?>
                <span class="leyenda-item">
                    <span class="leyenda-color" style="background: <?php echo esc_attr($tipo_colors[$tipo_key]); ?>"></span>
                    <?php echo esc_html($tipo_label); ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mapa -->
    <div class="mapa-contenedor">
        <div id="mapa-compostaje" class="mapa-canvas"></div>
    </div>

    <!-- Lista de puntos -->
    <div class="puntos-lista">
        <h3><?php esc_html_e('Puntos disponibles', 'flavor-chat-ia'); ?> <span class="contador">(<?php echo count($puntos_json); ?>)</span></h3>

        <?php if ($puntos_json): ?>
            <div class="puntos-grid" id="puntos-grid">
                <?php foreach ($puntos_json as $punto): ?>
                    <div class="punto-card" data-id="<?php echo esc_attr($punto['id']); ?>" data-tipo="<?php echo esc_attr($punto['tipo']); ?>">
                        <div class="punto-header">
                            <span class="punto-tipo" style="background: <?php echo esc_attr($punto['color']); ?>">
                                <?php echo esc_html($punto['tipo_label']); ?>
                            </span>
                            <span class="punto-fase fase-<?php echo esc_attr($punto['fase']); ?>">
                                <?php echo esc_html($punto['fase_label']); ?>
                            </span>
                        </div>
                        <h4 class="punto-nombre"><?php echo esc_html($punto['nombre']); ?></h4>
                        <div class="punto-direccion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($punto['direccion']); ?>
                        </div>
                        <?php if ($punto['horario']): ?>
                            <div class="punto-horario">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($punto['horario']); ?>
                            </div>
                        <?php endif; ?>
                        <div class="punto-llenado">
                            <span class="llenado-label"><?php esc_html_e('Nivel de llenado', 'flavor-chat-ia'); ?></span>
                            <div class="llenado-barra">
                                <div class="llenado-progreso" style="width: <?php echo esc_attr($punto['llenado']); ?>%"></div>
                            </div>
                            <span class="llenado-pct"><?php echo esc_html($punto['llenado']); ?>%</span>
                        </div>
                        <div class="punto-acciones">
                            <button class="btn btn-sm btn-outline" onclick="centrarMapa(<?php echo esc_attr($punto['lat']); ?>, <?php echo esc_attr($punto['lng']); ?>)">
                                <span class="dashicons dashicons-location-alt"></span>
                                <?php esc_html_e('Ver en mapa', 'flavor-chat-ia'); ?>
                            </button>
                            <?php if (is_user_logged_in()): ?>
                                <a href="<?php echo esc_url(add_query_arg(['vista' => 'registrar', 'punto_id' => $punto['id']], get_permalink())); ?>" class="btn btn-sm btn-primary">
                                    <?php esc_html_e('Aportar', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="puntos-empty">
                <span class="dashicons dashicons-location-alt"></span>
                <h4><?php esc_html_e('No hay puntos disponibles', 'flavor-chat-ia'); ?></h4>
                <p><?php esc_html_e('Aún no hay puntos de compostaje registrados en esta zona.', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.compostaje-mapa-wrapper { max-width: 1200px; margin: 0 auto; }

.mapa-header { text-align: center; margin-bottom: 1.5rem; }
.mapa-header h2 { margin: 0 0 0.5rem; font-size: 1.5rem; color: #1f2937; }
.mapa-header p { margin: 0; color: #6b7280; }

.mapa-filtros { display: flex; flex-wrap: wrap; gap: 1rem; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.filtro-grupo { display: flex; align-items: center; gap: 0.5rem; }
.filtro-grupo label { font-size: 0.85rem; font-weight: 500; color: #374151; }
.filtro-grupo select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
.filtro-leyenda { display: flex; gap: 1rem; flex-wrap: wrap; }
.leyenda-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: #6b7280; }
.leyenda-color { width: 12px; height: 12px; border-radius: 50%; }

.mapa-contenedor { margin-bottom: 2rem; }
.mapa-canvas { width: 100%; height: 400px; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; }
.mapa-canvas::before { content: "🗺️ Mapa (Requiere configurar API de mapas)"; color: #6b7280; font-size: 1rem; }

.puntos-lista h3 { margin: 0 0 1rem; font-size: 1.2rem; color: #1f2937; }
.puntos-lista .contador { font-weight: 400; color: #6b7280; font-size: 0.9rem; }

.puntos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1rem; }
.punto-card { background: white; border-radius: 12px; padding: 1.25rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
.punto-card:hover { box-shadow: 0 8px 20px rgba(0,0,0,0.1); transform: translateY(-2px); }

.punto-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; }
.punto-tipo { padding: 4px 10px; border-radius: 20px; color: white; font-size: 0.75rem; font-weight: 500; }
.punto-fase { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 500; background: #f3f4f6; color: #374151; }
.punto-fase.fase-listo { background: #d1fae5; color: #065f46; }
.punto-fase.fase-activo { background: #dbeafe; color: #1e40af; }
.punto-fase.fase-maduracion { background: #fef3c7; color: #92400e; }

.punto-nombre { margin: 0 0 0.5rem; font-size: 1.1rem; color: #1f2937; }
.punto-direccion, .punto-horario { font-size: 0.85rem; color: #6b7280; margin-bottom: 0.35rem; display: flex; align-items: center; gap: 0.35rem; }
.punto-direccion .dashicons, .punto-horario .dashicons { font-size: 14px; width: 14px; height: 14px; }

.punto-llenado { margin: 1rem 0; }
.llenado-label { font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 0.35rem; }
.llenado-barra { height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; flex: 1; }
.llenado-progreso { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 4px; transition: width 0.3s; }
.llenado-pct { font-size: 0.8rem; font-weight: 600; color: #374151; margin-left: 0.5rem; }
.punto-llenado { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

.punto-acciones { display: flex; gap: 0.5rem; margin-top: 1rem; }

.puntos-empty { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.puntos-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.puntos-empty h4 { margin: 0 0 0.5rem; color: #374151; }
.puntos-empty p { margin: 0; color: #6b7280; }

.btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.85rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-sm { padding: 0.4rem 0.75rem; font-size: 0.8rem; }
.btn-primary { background: #10b981; color: white; }
.btn-primary:hover { background: #059669; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn .dashicons { font-size: 14px; width: 14px; height: 14px; }
</style>

<script>
function filtrarPuntos(tipo) {
    var url = new URL(window.location.href);
    if (tipo) {
        url.searchParams.set('tipo', tipo);
    } else {
        url.searchParams.delete('tipo');
    }
    window.location.href = url.toString();
}

function centrarMapa(lat, lng) {
    // Implementar con API de mapas (Leaflet, Google Maps, etc.)
    alert('Ubicación: ' + lat + ', ' + lng);
}

// Datos de puntos para JS
var puntosCompostaje = <?php echo json_encode($puntos_json); ?>;
</script>
