<?php
/**
 * Template: Puntos de Reciclaje Cercanos
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';

// Filtros
$tipo_filtro = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Tipos de contenedores
$tipos_contenedores = [
    'papel' => ['nombre' => __('Papel y cartón', 'flavor-platform'), 'color' => '#3b82f6', 'icono' => '📄'],
    'vidrio' => ['nombre' => __('Vidrio', 'flavor-platform'), 'color' => '#22c55e', 'icono' => '🫙'],
    'plastico' => ['nombre' => __('Plástico y envases', 'flavor-platform'), 'color' => '#fbbf24', 'icono' => '🧴'],
    'organico' => ['nombre' => __('Orgánico', 'flavor-platform'), 'color' => '#92400e', 'icono' => '🍃'],
    'textil' => ['nombre' => __('Textil', 'flavor-platform'), 'color' => '#8b5cf6', 'icono' => '👕'],
    'aceite' => ['nombre' => __('Aceite usado', 'flavor-platform'), 'color' => '#f59e0b', 'icono' => '🛢️'],
    'pilas' => ['nombre' => __('Pilas y baterías', 'flavor-platform'), 'color' => '#ef4444', 'icono' => '🔋'],
    'electronico' => ['nombre' => __('Electrónico', 'flavor-platform'), 'color' => '#6366f1', 'icono' => '💻'],
    'punto_limpio' => ['nombre' => __('Punto limpio', 'flavor-platform'), 'color' => '#10b981', 'icono' => '♻️'],
];

// Obtener puntos de reciclaje
$puntos_reciclaje = [];
if (Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
    $where = "estado = 'activo'";
    if ($tipo_filtro) {
        $where .= $wpdb->prepare(" AND tipo = %s", $tipo_filtro);
    }
    $puntos_reciclaje = $wpdb->get_results(
        "SELECT * FROM $tabla_puntos WHERE $where ORDER BY nombre ASC"
    );
}

// Si no hay tabla o datos, mostrar ejemplos
if (empty($puntos_reciclaje)) {
    $puntos_reciclaje = [
        (object) ['id' => 1, 'nombre' => 'Contenedor Azul - Plaza Mayor', 'direccion' => 'Plaza Mayor, 1', 'tipo' => 'papel', 'latitud' => 40.416775, 'longitud' => -3.703790, 'horario' => '24h'],
        (object) ['id' => 2, 'nombre' => 'Contenedor Verde - Calle Principal', 'direccion' => 'Calle Principal, 15', 'tipo' => 'vidrio', 'latitud' => 40.417775, 'longitud' => -3.704790, 'horario' => '24h'],
        (object) ['id' => 3, 'nombre' => 'Punto Limpio Municipal', 'direccion' => 'Polígono Industrial, s/n', 'tipo' => 'punto_limpio', 'latitud' => 40.418775, 'longitud' => -3.705790, 'horario' => 'L-V 8:00-20:00'],
    ];
}
?>

<div class="reciclaje-puntos-wrapper">
    <div class="puntos-header">
        <h2><?php esc_html_e('Puntos de Reciclaje', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Encuentra el contenedor más cercano para reciclar', 'flavor-platform'); ?></p>
    </div>

    <!-- Filtros por tipo -->
    <div class="tipos-filtros">
        <a href="<?php echo esc_url(remove_query_arg('tipo')); ?>"
           class="tipo-btn <?php echo empty($tipo_filtro) ? 'active' : ''; ?>">
            <?php esc_html_e('Todos', 'flavor-platform'); ?>
        </a>
        <?php foreach ($tipos_contenedores as $tipo_key => $tipo): ?>
            <a href="<?php echo esc_url(add_query_arg('tipo', $tipo_key)); ?>"
               class="tipo-btn <?php echo $tipo_filtro === $tipo_key ? 'active' : ''; ?>"
               style="<?php echo $tipo_filtro === $tipo_key ? 'background:' . esc_attr($tipo['color']) : ''; ?>">
                <?php echo $tipo['icono'] . ' ' . esc_html($tipo['nombre']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Mapa -->
    <div class="mapa-contenedor">
        <div id="mapa-reciclaje" class="mapa-canvas"></div>
    </div>

    <!-- Lista de puntos -->
    <div class="puntos-lista">
        <h3><?php esc_html_e('Puntos disponibles', 'flavor-platform'); ?></h3>
        <div class="puntos-grid">
            <?php foreach ($puntos_reciclaje as $punto):
                $tipo_info = $tipos_contenedores[$punto->tipo] ?? ['nombre' => ucfirst($punto->tipo), 'color' => '#6b7280', 'icono' => '♻️'];
            ?>
                <div class="punto-card">
                    <div class="punto-tipo" style="background: <?php echo esc_attr($tipo_info['color']); ?>">
                        <?php echo $tipo_info['icono']; ?>
                    </div>
                    <div class="punto-info">
                        <h4><?php echo esc_html($punto->nombre); ?></h4>
                        <div class="punto-direccion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($punto->direccion); ?>
                        </div>
                        <?php if (!empty($punto->horario)): ?>
                            <div class="punto-horario">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($punto->horario); ?>
                            </div>
                        <?php endif; ?>
                        <span class="punto-categoria"><?php echo esc_html($tipo_info['nombre']); ?></span>
                    </div>
                    <div class="punto-acciones">
                        <button class="btn btn-sm btn-outline" onclick="abrirMapa(<?php echo esc_attr($punto->latitud ?? 0); ?>, <?php echo esc_attr($punto->longitud ?? 0); ?>)">
                            <span class="dashicons dashicons-location-alt"></span>
                            <?php esc_html_e('Cómo llegar', 'flavor-platform'); ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Guía rápida -->
    <div class="reciclaje-guia-rapida">
        <h3><?php esc_html_e('¿Qué va en cada contenedor?', 'flavor-platform'); ?></h3>
        <div class="guia-grid">
            <?php foreach ($tipos_contenedores as $tipo => $info): ?>
                <div class="guia-item" style="border-left-color: <?php echo esc_attr($info['color']); ?>">
                    <span class="guia-icono"><?php echo $info['icono']; ?></span>
                    <span class="guia-nombre"><?php echo esc_html($info['nombre']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo esc_url(add_query_arg('vista', 'guia', get_permalink())); ?>" class="ver-guia-completa">
            <?php esc_html_e('Ver guía completa de reciclaje', 'flavor-platform'); ?> →
        </a>
    </div>
</div>

<style>
.reciclaje-puntos-wrapper { max-width: 1000px; margin: 0 auto; }
.puntos-header { text-align: center; margin-bottom: 1.5rem; }
.puntos-header h2 { margin: 0 0 0.5rem; color: #1f2937; }
.puntos-header p { margin: 0; color: #6b7280; }
.tipos-filtros { display: flex; flex-wrap: wrap; gap: 0.5rem; justify-content: center; margin-bottom: 1.5rem; }
.tipo-btn { padding: 0.5rem 1rem; border-radius: 20px; background: #f3f4f6; color: #374151; text-decoration: none; font-size: 0.85rem; transition: all 0.2s; white-space: nowrap; }
.tipo-btn:hover { background: #e5e7eb; }
.tipo-btn.active { background: #10b981; color: white; }
.mapa-contenedor { margin-bottom: 2rem; }
.mapa-canvas { width: 100%; height: 350px; border-radius: 12px; background: #e5e7eb; display: flex; align-items: center; justify-content: center; }
.mapa-canvas::before { content: "🗺️ Mapa (Requiere configurar API)"; color: #6b7280; }
.puntos-lista h3 { margin: 0 0 1rem; font-size: 1.1rem; color: #1f2937; }
.puntos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.punto-card { display: flex; gap: 1rem; background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.punto-tipo { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
.punto-info { flex: 1; }
.punto-info h4 { margin: 0 0 0.35rem; font-size: 0.95rem; color: #1f2937; }
.punto-direccion, .punto-horario { font-size: 0.8rem; color: #6b7280; display: flex; align-items: center; gap: 0.25rem; margin-bottom: 0.25rem; }
.punto-direccion .dashicons, .punto-horario .dashicons { font-size: 12px; width: 12px; height: 12px; }
.punto-categoria { display: inline-block; padding: 2px 8px; background: #f3f4f6; border-radius: 4px; font-size: 0.75rem; color: #6b7280; margin-top: 0.35rem; }
.punto-acciones { display: flex; align-items: center; }
.reciclaje-guia-rapida { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.reciclaje-guia-rapida h3 { margin: 0 0 1rem; font-size: 1.1rem; color: #1f2937; }
.guia-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 0.75rem; margin-bottom: 1rem; }
.guia-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; border-left: 3px solid; }
.guia-icono { font-size: 1.25rem; }
.guia-nombre { font-size: 0.85rem; color: #374151; }
.ver-guia-completa { display: inline-block; color: #10b981; font-size: 0.9rem; text-decoration: none; }
.btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.75rem; border-radius: 6px; font-size: 0.8rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; }
.btn-sm { padding: 0.35rem 0.6rem; font-size: 0.75rem; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
</style>

<script>
function abrirMapa(lat, lng) {
    if (lat && lng) {
        window.open('https://www.google.com/maps?q=' + lat + ',' + lng, '_blank');
    }
}
</script>
