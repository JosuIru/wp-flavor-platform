<?php
/**
 * Template: Estadísticas de Compostaje
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
$tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';
$tabla_estadisticas = $wpdb->prefix . 'flavor_estadisticas_compost';

// Estadísticas globales
$stats_globales = $wpdb->get_row(
    "SELECT
        COUNT(DISTINCT usuario_id) as total_usuarios,
        COUNT(*) as total_aportaciones,
        COALESCE(SUM(cantidad_kg), 0) as total_kg,
        COALESCE(SUM(co2_evitado_kg), 0) as co2_evitado
     FROM $tabla_aportaciones
     WHERE validado = 1"
);

// Puntos de compostaje activos
$puntos_activos = $wpdb->get_var(
    "SELECT COUNT(*) FROM $tabla_puntos WHERE estado = 'activo'"
);

// Estadísticas por mes (últimos 6 meses)
$stats_mensuales = $wpdb->get_results(
    "SELECT
        DATE_FORMAT(fecha_aportacion, '%Y-%m') as mes,
        COUNT(*) as aportaciones,
        COALESCE(SUM(cantidad_kg), 0) as kg_total,
        COUNT(DISTINCT usuario_id) as usuarios_activos
     FROM $tabla_aportaciones
     WHERE validado = 1
       AND fecha_aportacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(fecha_aportacion, '%Y-%m')
     ORDER BY mes DESC"
);

// Top materiales compostados
$top_materiales = $wpdb->get_results(
    "SELECT
        tipo_material,
        categoria_material,
        COUNT(*) as veces,
        COALESCE(SUM(cantidad_kg), 0) as kg_total
     FROM $tabla_aportaciones
     WHERE validado = 1
     GROUP BY tipo_material, categoria_material
     ORDER BY kg_total DESC
     LIMIT 5"
);

// Materiales labels
$materiales_labels = [
    'frutas_verduras' => __('Frutas y verduras', 'flavor-platform'),
    'posos_cafe' => __('Posos de café', 'flavor-platform'),
    'cesped_fresco' => __('Césped fresco', 'flavor-platform'),
    'restos_cocina' => __('Restos de cocina', 'flavor-platform'),
    'plantas_verdes' => __('Plantas verdes', 'flavor-platform'),
    'hojas_secas' => __('Hojas secas', 'flavor-platform'),
    'papel_carton' => __('Papel y cartón', 'flavor-platform'),
    'ramas_poda' => __('Ramas y poda', 'flavor-platform'),
    'serrin' => __('Serrín', 'flavor-platform'),
    'paja' => __('Paja', 'flavor-platform'),
    'cascaras_huevo' => __('Cáscaras de huevo', 'flavor-platform'),
    'bolsas_te' => __('Bolsas de té', 'flavor-platform'),
    'otro' => __('Otro', 'flavor-platform'),
];

$categoria_colors = [
    'verde' => '#10b981',
    'marron' => '#92400e',
    'especial' => '#6366f1',
];

// Calcular equivalencias medioambientales
$arboles_equivalentes = ($stats_globales->co2_evitado ?? 0) / 21; // Un árbol absorbe ~21kg CO2/año
$km_coche = ($stats_globales->co2_evitado ?? 0) / 0.12; // ~120g CO2 por km
?>

<div class="compostaje-estadisticas-wrapper">
    <div class="estadisticas-header">
        <h2><?php esc_html_e('Impacto de nuestra comunidad', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Juntos estamos haciendo la diferencia por el medio ambiente', 'flavor-platform'); ?></p>
    </div>

    <!-- Estadísticas principales -->
    <div class="stats-hero-grid">
        <div class="stat-hero-card">
            <div class="stat-hero-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="stat-hero-value"><?php echo esc_html(number_format($stats_globales->total_usuarios ?? 0)); ?></div>
            <div class="stat-hero-label"><?php esc_html_e('Participantes activos', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-hero-card">
            <div class="stat-hero-icon eco">
                <span class="dashicons dashicons-image-rotate"></span>
            </div>
            <div class="stat-hero-value"><?php echo esc_html(number_format($stats_globales->total_kg ?? 0, 0)); ?> <small>kg</small></div>
            <div class="stat-hero-label"><?php esc_html_e('Material compostado', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-hero-card">
            <div class="stat-hero-icon success">
                <span class="dashicons dashicons-cloud"></span>
            </div>
            <div class="stat-hero-value"><?php echo esc_html(number_format($stats_globales->co2_evitado ?? 0, 0)); ?> <small>kg</small></div>
            <div class="stat-hero-label"><?php esc_html_e('CO₂ evitado', 'flavor-platform'); ?></div>
        </div>
        <div class="stat-hero-card">
            <div class="stat-hero-icon">
                <span class="dashicons dashicons-location-alt"></span>
            </div>
            <div class="stat-hero-value"><?php echo esc_html($puntos_activos ?? 0); ?></div>
            <div class="stat-hero-label"><?php esc_html_e('Puntos de compostaje', 'flavor-platform'); ?></div>
        </div>
    </div>

    <!-- Equivalencias medioambientales -->
    <div class="stats-equivalencias">
        <h3><?php esc_html_e('Esto equivale a...', 'flavor-platform'); ?></h3>
        <div class="equivalencias-grid">
            <div class="equivalencia-item">
                <span class="equiv-icon">🌳</span>
                <span class="equiv-value"><?php echo esc_html(number_format($arboles_equivalentes, 0)); ?></span>
                <span class="equiv-label"><?php esc_html_e('árboles plantados (CO₂ absorbido en 1 año)', 'flavor-platform'); ?></span>
            </div>
            <div class="equivalencia-item">
                <span class="equiv-icon">🚗</span>
                <span class="equiv-value"><?php echo esc_html(number_format($km_coche, 0)); ?></span>
                <span class="equiv-label"><?php esc_html_e('km en coche evitados', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <div class="stats-columns">
        <!-- Evolución mensual -->
        <div class="stats-section">
            <h3><?php esc_html_e('Evolución mensual', 'flavor-platform'); ?></h3>
            <?php if ($stats_mensuales): ?>
                <div class="stats-timeline">
                    <?php foreach ($stats_mensuales as $mes): ?>
                        <?php
                        $fecha_mes = DateTime::createFromFormat('Y-m', $mes->mes);
                        $nombre_mes = $fecha_mes ? date_i18n('F Y', $fecha_mes->getTimestamp()) : $mes->mes;
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-mes"><?php echo esc_html(ucfirst($nombre_mes)); ?></div>
                            <div class="timeline-stats">
                                <span class="timeline-kg"><?php echo esc_html(number_format($mes->kg_total, 1)); ?> kg</span>
                                <span class="timeline-meta">
                                    <?php echo esc_html($mes->aportaciones); ?> <?php esc_html_e('aportaciones', 'flavor-platform'); ?> •
                                    <?php echo esc_html($mes->usuarios_activos); ?> <?php esc_html_e('usuarios', 'flavor-platform'); ?>
                                </span>
                            </div>
                            <div class="timeline-bar">
                                <?php
                                $max_kg = !empty($stats_mensuales) ? max(array_column($stats_mensuales, 'kg_total')) : 1;
                                $porcentaje = $max_kg > 0 ? ($mes->kg_total / $max_kg) * 100 : 0;
                                ?>
                                <div class="timeline-bar-fill" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stats-empty"><?php esc_html_e('Aún no hay datos mensuales disponibles.', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Top materiales -->
        <div class="stats-section">
            <h3><?php esc_html_e('Materiales más compostados', 'flavor-platform'); ?></h3>
            <?php if ($top_materiales): ?>
                <div class="materiales-ranking">
                    <?php foreach ($top_materiales as $index => $material): ?>
                        <div class="material-rank-item">
                            <span class="rank-number"><?php echo esc_html($index + 1); ?></span>
                            <div class="rank-info">
                                <span class="rank-nombre"><?php echo esc_html($materiales_labels[$material->tipo_material] ?? $material->tipo_material); ?></span>
                                <span class="rank-categoria" style="color: <?php echo esc_attr($categoria_colors[$material->categoria_material] ?? '#6b7280'); ?>">
                                    <?php echo esc_html(ucfirst($material->categoria_material)); ?>
                                </span>
                            </div>
                            <div class="rank-kg"><?php echo esc_html(number_format($material->kg_total, 1)); ?> kg</div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stats-empty"><?php esc_html_e('Aún no hay datos de materiales disponibles.', 'flavor-platform'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Call to action -->
    <div class="stats-cta">
        <h3><?php esc_html_e('¡Únete al movimiento!', 'flavor-platform'); ?></h3>
        <p><?php esc_html_e('Cada aportación cuenta. Empieza a compostar hoy y ayuda a reducir residuos.', 'flavor-platform'); ?></p>
        <div class="cta-buttons">
            <a href="<?php echo esc_url(add_query_arg('vista', 'registrar', get_permalink())); ?>" class="btn btn-primary">
                <?php esc_html_e('Registrar aportación', 'flavor-platform'); ?>
            </a>
            <a href="<?php echo esc_url(add_query_arg('vista', 'mapa', get_permalink())); ?>" class="btn btn-outline">
                <?php esc_html_e('Ver puntos de compostaje', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
</div>

<style>
.compostaje-estadisticas-wrapper { max-width: 1000px; margin: 0 auto; }

.estadisticas-header { text-align: center; margin-bottom: 2rem; }
.estadisticas-header h2 { margin: 0 0 0.5rem; font-size: 1.75rem; color: #1f2937; }
.estadisticas-header p { margin: 0; color: #6b7280; }

.stats-hero-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
.stat-hero-card { background: white; border-radius: 16px; padding: 1.5rem; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
.stat-hero-icon { width: 56px; height: 56px; border-radius: 50%; background: #eff6ff; display: flex; align-items: center; justify-content: center; margin: 0 auto 1rem; }
.stat-hero-icon .dashicons { font-size: 28px; width: 28px; height: 28px; color: #4f46e5; }
.stat-hero-icon.eco { background: #ecfdf5; }
.stat-hero-icon.eco .dashicons { color: #10b981; }
.stat-hero-icon.success { background: #f0fdf4; }
.stat-hero-icon.success .dashicons { color: #22c55e; }
.stat-hero-value { font-size: 2rem; font-weight: 700; color: #1f2937; }
.stat-hero-value small { font-size: 1rem; font-weight: 400; color: #6b7280; }
.stat-hero-label { font-size: 0.9rem; color: #6b7280; margin-top: 0.25rem; }

.stats-equivalencias { background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%); border-radius: 16px; padding: 1.5rem; margin-bottom: 2rem; }
.stats-equivalencias h3 { margin: 0 0 1rem; font-size: 1.1rem; color: #065f46; text-align: center; }
.equivalencias-grid { display: flex; gap: 2rem; justify-content: center; flex-wrap: wrap; }
.equivalencia-item { display: flex; align-items: center; gap: 0.75rem; background: white; padding: 1rem 1.5rem; border-radius: 12px; }
.equiv-icon { font-size: 2rem; }
.equiv-value { font-size: 1.5rem; font-weight: 700; color: #065f46; }
.equiv-label { font-size: 0.85rem; color: #047857; max-width: 180px; }

.stats-columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
.stats-section { background: white; border-radius: 16px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.stats-section h3 { margin: 0 0 1rem; font-size: 1.1rem; color: #1f2937; }

.stats-timeline { display: flex; flex-direction: column; gap: 1rem; }
.timeline-item { display: flex; flex-direction: column; gap: 0.25rem; }
.timeline-mes { font-weight: 500; font-size: 0.9rem; color: #374151; }
.timeline-stats { display: flex; gap: 1rem; font-size: 0.85rem; }
.timeline-kg { font-weight: 600; color: #10b981; }
.timeline-meta { color: #9ca3af; }
.timeline-bar { height: 8px; background: #f3f4f6; border-radius: 4px; overflow: hidden; }
.timeline-bar-fill { height: 100%; background: linear-gradient(90deg, #10b981, #34d399); border-radius: 4px; transition: width 0.5s ease; }

.materiales-ranking { display: flex; flex-direction: column; gap: 0.75rem; }
.material-rank-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; background: #f9fafb; border-radius: 8px; }
.rank-number { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: #4f46e5; color: white; border-radius: 50%; font-size: 0.85rem; font-weight: 600; }
.rank-info { flex: 1; }
.rank-nombre { display: block; font-weight: 500; color: #1f2937; font-size: 0.9rem; }
.rank-categoria { font-size: 0.75rem; text-transform: uppercase; }
.rank-kg { font-weight: 600; color: #374151; }

.stats-empty { color: #9ca3af; font-style: italic; text-align: center; padding: 1rem; }

.stats-cta { background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%); border-radius: 16px; padding: 2rem; text-align: center; color: white; }
.stats-cta h3 { margin: 0 0 0.5rem; font-size: 1.25rem; }
.stats-cta p { margin: 0 0 1.5rem; opacity: 0.9; }
.cta-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: white; color: #4f46e5; }
.btn-primary:hover { background: #f3f4f6; }
.btn-outline { background: transparent; border: 2px solid white; color: white; }
.btn-outline:hover { background: rgba(255,255,255,0.1); }
</style>
