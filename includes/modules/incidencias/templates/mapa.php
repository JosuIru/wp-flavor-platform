<?php
/**
 * Template: Mapa de Incidencias
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';

// Verificar si existe la tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_incidencias)) {
    echo '<div class="incidencias-empty"><p>' . esc_html__('El módulo de incidencias no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Filtros
$estado_filtro = isset($_GET['estado']) ? sanitize_text_field($_GET['estado']) : '';
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Obtener incidencias con coordenadas
$where = ["estado NOT IN ('eliminada', 'cerrada')"];
$params = [];

if ($estado_filtro) {
    $where[] = "estado = %s";
    $params[] = $estado_filtro;
}

if ($tipo_filtro) {
    $where[] = "tipo = %s";
    $params[] = $tipo_filtro;
}

$where_sql = implode(' AND ', $where);
$incidencias_query = "SELECT id, titulo, descripcion, ubicacion, latitud, longitud, tipo, estado, prioridad, created_at
                      FROM $tabla_incidencias
                      WHERE $where_sql AND latitud IS NOT NULL AND longitud IS NOT NULL
                      ORDER BY created_at DESC
                      LIMIT 100";

if (!empty($params)) {
    $incidencias = $wpdb->get_results($wpdb->prepare($incidencias_query, $params));
} else {
    $incidencias = $wpdb->get_results($incidencias_query);
}

// Obtener tipos para filtros
$tipos_disponibles = $wpdb->get_col("SELECT DISTINCT tipo FROM $tabla_incidencias WHERE tipo IS NOT NULL ORDER BY tipo");

// Labels para estados (español e inglés)
$estados_labels = [
    // Estados en español
    'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'en_proceso' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'resuelta' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cerrada' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
    // Estados en inglés (para datos existentes)
    'pending' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'in_progress' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'resolved' => __('Resuelta', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'closed' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$estados_colors = [
    // Estados en español
    'pendiente' => '#f59e0b',
    'en_proceso' => '#3b82f6',
    'resuelta' => '#10b981',
    'cerrada' => '#6b7280',
    // Estados en inglés
    'pending' => '#f59e0b',
    'in_progress' => '#3b82f6',
    'resolved' => '#10b981',
    'closed' => '#6b7280',
];

// URL base para incidencias
$incidencias_base_url = Flavor_Platform_Helpers::get_action_url('incidencias', '');

// Preparar datos para JS
$incidencias_json = [];
foreach ($incidencias as $incidencia) {
    $incidencias_json[] = [
        'id' => (int) $incidencia->id,
        'titulo' => $incidencia->titulo,
        'descripcion' => wp_trim_words($incidencia->descripcion, 15),
        'ubicacion' => $incidencia->ubicacion,
        'lat' => (float) $incidencia->latitud,
        'lng' => (float) $incidencia->longitud,
        'tipo' => $incidencia->tipo,
        'estado' => $incidencia->estado,
        'estado_label' => $estados_labels[$incidencia->estado] ?? ucfirst($incidencia->estado),
        'color' => $estados_colors[$incidencia->estado] ?? '#6b7280',
        'prioridad' => $incidencia->prioridad ?? 'normal',
        'fecha' => date_i18n(get_option('date_format'), strtotime($incidencia->created_at)),
    ];
}
?>

<div class="incidencias-mapa-wrapper">
    <div class="mapa-header">
        <h2><?php esc_html_e('Mapa de Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <?php if (is_user_logged_in()): ?>
            <a href="<?php echo esc_url($incidencias_base_url . 'reportar/'); ?>" class="btn btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Reportar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div class="mapa-filtros">
        <div class="filtro-grupo">
            <select id="filtro-estado" onchange="filtrarMapa()">
                <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <?php foreach ($estados_labels as $estado => $label): ?>
                    <option value="<?php echo esc_attr($estado); ?>" <?php selected($estado_filtro, $estado); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php if ($tipos_disponibles): ?>
            <div class="filtro-grupo">
                <select id="filtro-tipo" onchange="filtrarMapa()">
                    <option value=""><?php esc_html_e('Todos los tipos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($tipos_disponibles as $tipo): ?>
                        <option value="<?php echo esc_attr($tipo); ?>" <?php selected($tipo_filtro, $tipo); ?>>
                            <?php echo esc_html(ucfirst($tipo)); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="filtro-leyenda">
            <?php foreach ($estados_labels as $estado => $label): ?>
                <span class="leyenda-item">
                    <span class="leyenda-color" style="background: <?php echo esc_attr($estados_colors[$estado]); ?>"></span>
                    <?php echo esc_html($label); ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Mapa -->
    <div class="mapa-contenedor">
        <div id="mapa-incidencias" class="mapa-canvas"></div>
    </div>

    <!-- Contador -->
    <div class="mapa-info">
        <span class="incidencias-count">
            <?php printf(
                esc_html(_n('%d incidencia en el mapa', '%d incidencias en el mapa', count($incidencias), FLAVOR_PLATFORM_TEXT_DOMAIN)),
                count($incidencias)
            ); ?>
        </span>
    </div>

    <!-- Lista resumida -->
    <?php if ($incidencias): ?>
        <div class="incidencias-lista-mini">
            <?php foreach (array_slice($incidencias_json, 0, 5) as $inc): ?>
                <div class="incidencia-mini" onclick="centrarMapa(<?php echo esc_attr($inc['lat']); ?>, <?php echo esc_attr($inc['lng']); ?>)">
                    <span class="mini-estado" style="background: <?php echo esc_attr($inc['color']); ?>"></span>
                    <div class="mini-info">
                        <span class="mini-titulo"><?php echo esc_html($inc['titulo']); ?></span>
                        <span class="mini-ubicacion"><?php echo esc_html($inc['ubicacion']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (count($incidencias) > 5): ?>
                <a href="<?php echo esc_url($incidencias_base_url); ?>" class="ver-todas">
                    <?php esc_html_e('Ver todas las incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.incidencias-mapa-wrapper { max-width: 1100px; margin: 0 auto; }
.mapa-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; flex-wrap: wrap; gap: 1rem; }
.mapa-header h2 { margin: 0; font-size: 1.5rem; color: #1f2937; }
.mapa-filtros { display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.filtro-grupo select { padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; }
.filtro-leyenda { display: flex; gap: 1rem; flex-wrap: wrap; margin-left: auto; }
.leyenda-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.8rem; color: #6b7280; }
.leyenda-color { width: 12px; height: 12px; border-radius: 50%; }
.mapa-contenedor { margin-bottom: 1rem; }
.mapa-canvas { width: 100%; height: 450px; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; }
.mapa-canvas::before { content: "🗺️ Mapa de incidencias (Requiere configurar API)"; color: #6b7280; font-size: 1rem; }
.mapa-info { text-align: center; margin-bottom: 1.5rem; }
.incidencias-count { font-size: 0.9rem; color: #6b7280; }
.incidencias-lista-mini { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.06); }
.incidencia-mini { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1rem; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background 0.2s; }
.incidencia-mini:hover { background: #f9fafb; }
.incidencia-mini:last-of-type { border-bottom: none; }
.mini-estado { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.mini-info { flex: 1; }
.mini-titulo { display: block; font-weight: 500; color: #1f2937; font-size: 0.9rem; }
.mini-ubicacion { font-size: 0.8rem; color: #6b7280; }
.ver-todas { display: block; padding: 0.75rem 1rem; text-align: center; color: #4f46e5; font-size: 0.875rem; border-top: 1px solid #f3f4f6; text-decoration: none; }
.ver-todas:hover { background: #f9fafb; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #ef4444; color: white; }
</style>

<script>
var incidenciasData = <?php echo json_encode($incidencias_json); ?>;

function filtrarMapa() {
    var estado = document.getElementById('filtro-estado').value;
    var tipo = document.getElementById('filtro-tipo')?.value || '';
    var url = new URL(window.location.href);
    if (estado) url.searchParams.set('estado', estado);
    else url.searchParams.delete('estado');
    if (tipo) url.searchParams.set('tipo', tipo);
    else url.searchParams.delete('tipo');
    window.location.href = url.toString();
}

function centrarMapa(lat, lng) {
    // Implementar con API de mapas
    console.log('Centrar en:', lat, lng);
}
</script>
