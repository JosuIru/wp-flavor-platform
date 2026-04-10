<?php
/**
 * Template: Mis Puntos de Reciclaje
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="reciclaje-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesión para ver tus puntos', 'flavor-platform') . '</h3>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesión', 'flavor-platform') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$usuario_id = get_current_user_id();
$tabla_puntos = $wpdb->prefix . 'flavor_reciclaje_puntos';
$tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';

// Verificar tablas
if (!Flavor_Platform_Helpers::tabla_existe($tabla_puntos)) {
    // Si no hay tabla específica, usar sistema de puntos genérico
    $tabla_puntos = $wpdb->prefix . 'flavor_puntos_usuario';
}

// Obtener puntos del usuario
$puntos_totales = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(puntos), 0) FROM $tabla_puntos WHERE usuario_id = %d",
    $usuario_id
)) ?: 0;

// Historial de puntos
$historial = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $tabla_puntos WHERE usuario_id = %d ORDER BY fecha DESC LIMIT 20",
    $usuario_id
));

// Estadísticas de reciclaje
$stats = [
    'kg_reciclados' => 0,
    'co2_evitado' => 0,
    'depositos' => 0,
];

if (Flavor_Platform_Helpers::tabla_existe($tabla_depositos)) {
    $stats_row = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) as depositos, COALESCE(SUM(cantidad_kg), 0) as kg_total
         FROM $tabla_depositos WHERE usuario_id = %d AND estado = 'validado'",
        $usuario_id
    ));
    if ($stats_row) {
        $stats['depositos'] = (int) $stats_row->depositos;
        $stats['kg_reciclados'] = (float) $stats_row->kg_total;
        $stats['co2_evitado'] = $stats['kg_reciclados'] * 0.5; // Estimación
    }
}

// Niveles de reciclador
$niveles = [
    ['nombre' => 'Iniciado', 'min' => 0, 'icono' => '♻️'],
    ['nombre' => 'Consciente', 'min' => 100, 'icono' => '🌱'],
    ['nombre' => 'Comprometido', 'min' => 500, 'icono' => '🌿'],
    ['nombre' => 'Experto', 'min' => 1500, 'icono' => '🌳'],
    ['nombre' => 'Héroe Verde', 'min' => 5000, 'icono' => '🌍'],
];

$nivel_actual = $niveles[0];
$nivel_siguiente = isset($niveles[1]) ? $niveles[1] : null;
$progreso = 0;

foreach ($niveles as $i => $nivel) {
    if ($puntos_totales >= $nivel['min']) {
        $nivel_actual = $nivel;
        $nivel_siguiente = isset($niveles[$i + 1]) ? $niveles[$i + 1] : null;
    }
}

if ($nivel_siguiente) {
    $rango = $nivel_siguiente['min'] - $nivel_actual['min'];
    $avance = $puntos_totales - $nivel_actual['min'];
    $progreso = min(100, ($avance / $rango) * 100);
}
?>

<div class="reciclaje-mis-puntos-wrapper">
    <!-- Tarjeta de puntos principal -->
    <div class="puntos-hero-card">
        <div class="puntos-hero-content">
            <div class="nivel-badge">
                <span class="nivel-icono"><?php echo $nivel_actual['icono']; ?></span>
                <span class="nivel-nombre"><?php echo esc_html($nivel_actual['nombre']); ?></span>
            </div>
            <div class="puntos-valor">
                <span class="puntos-numero"><?php echo esc_html(number_format($puntos_totales)); ?></span>
                <span class="puntos-label"><?php esc_html_e('puntos', 'flavor-platform'); ?></span>
            </div>
            <?php if ($nivel_siguiente): ?>
                <div class="nivel-progreso">
                    <div class="progreso-info">
                        <span><?php echo esc_html($nivel_siguiente['icono'] . ' ' . $nivel_siguiente['nombre']); ?></span>
                        <span><?php echo esc_html(number_format($nivel_siguiente['min'] - $puntos_totales)); ?> pts</span>
                    </div>
                    <div class="progreso-barra">
                        <div class="progreso-fill" style="width: <?php echo esc_attr($progreso); ?>%"></div>
                    </div>
                </div>
            <?php else: ?>
                <div class="nivel-max"><?php esc_html_e('¡Nivel máximo alcanzado!', 'flavor-platform'); ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Estadísticas de impacto -->
    <div class="impacto-grid">
        <div class="impacto-card">
            <span class="impacto-icono">♻️</span>
            <span class="impacto-valor"><?php echo esc_html(number_format($stats['kg_reciclados'], 1)); ?> kg</span>
            <span class="impacto-label"><?php esc_html_e('Reciclado', 'flavor-platform'); ?></span>
        </div>
        <div class="impacto-card">
            <span class="impacto-icono">🌿</span>
            <span class="impacto-valor"><?php echo esc_html(number_format($stats['co2_evitado'], 1)); ?> kg</span>
            <span class="impacto-label"><?php esc_html_e('CO₂ evitado', 'flavor-platform'); ?></span>
        </div>
        <div class="impacto-card">
            <span class="impacto-icono">📦</span>
            <span class="impacto-valor"><?php echo esc_html($stats['depositos']); ?></span>
            <span class="impacto-label"><?php esc_html_e('Depósitos', 'flavor-platform'); ?></span>
        </div>
    </div>

    <!-- Acciones -->
    <div class="reciclaje-acciones">
        <a href="<?php echo esc_url(add_query_arg('vista', 'puntos-cercanos', get_permalink())); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-location-alt"></span>
            <?php esc_html_e('Puntos cercanos', 'flavor-platform'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('vista', 'recompensas', get_permalink())); ?>" class="btn btn-outline">
            <span class="dashicons dashicons-gift"></span>
            <?php esc_html_e('Canjear puntos', 'flavor-platform'); ?>
        </a>
    </div>

    <!-- Historial -->
    <div class="historial-section">
        <h3><?php esc_html_e('Historial de puntos', 'flavor-platform'); ?></h3>
        <?php if ($historial): ?>
            <div class="historial-lista">
                <?php foreach ($historial as $item): ?>
                    <div class="historial-item">
                        <div class="historial-info">
                            <span class="historial-concepto"><?php echo esc_html($item->concepto ?? __('Reciclaje', 'flavor-platform')); ?></span>
                            <span class="historial-fecha"><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($item->fecha))); ?></span>
                        </div>
                        <span class="historial-puntos <?php echo $item->puntos >= 0 ? 'positivo' : 'negativo'; ?>">
                            <?php echo $item->puntos >= 0 ? '+' : ''; ?><?php echo esc_html($item->puntos); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="historial-empty">
                <p><?php esc_html_e('Aún no tienes movimientos de puntos.', 'flavor-platform'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.reciclaje-mis-puntos-wrapper { max-width: 700px; margin: 0 auto; }
.reciclaje-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.reciclaje-login-required .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; display: block; margin: 0 auto 1rem; }

.puntos-hero-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 20px; padding: 2rem; color: white; margin-bottom: 1.5rem; }
.puntos-hero-content { text-align: center; }
.nivel-badge { display: inline-flex; align-items: center; gap: 0.5rem; background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 20px; margin-bottom: 1rem; }
.nivel-icono { font-size: 1.25rem; }
.nivel-nombre { font-weight: 500; font-size: 0.9rem; }
.puntos-valor { margin-bottom: 1.5rem; }
.puntos-numero { display: block; font-size: 3rem; font-weight: 700; line-height: 1; }
.puntos-label { font-size: 1rem; opacity: 0.9; }
.nivel-progreso { background: rgba(255,255,255,0.15); border-radius: 12px; padding: 1rem; }
.progreso-info { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 0.5rem; opacity: 0.9; }
.progreso-barra { height: 8px; background: rgba(255,255,255,0.3); border-radius: 4px; overflow: hidden; }
.progreso-fill { height: 100%; background: white; border-radius: 4px; transition: width 0.5s ease; }
.nivel-max { font-size: 0.9rem; opacity: 0.9; margin-top: 0.5rem; }

.impacto-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-bottom: 1.5rem; }
.impacto-card { background: white; border-radius: 12px; padding: 1.25rem; text-align: center; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.impacto-icono { display: block; font-size: 2rem; margin-bottom: 0.5rem; }
.impacto-valor { display: block; font-size: 1.25rem; font-weight: 700; color: #1f2937; }
.impacto-label { font-size: 0.8rem; color: #6b7280; }

.reciclaje-acciones { display: flex; gap: 0.75rem; margin-bottom: 2rem; justify-content: center; flex-wrap: wrap; }

.historial-section { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
.historial-section h3 { margin: 0 0 1rem; font-size: 1.1rem; color: #1f2937; }
.historial-lista { display: flex; flex-direction: column; }
.historial-item { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #f3f4f6; }
.historial-item:last-child { border-bottom: none; }
.historial-concepto { display: block; font-weight: 500; color: #1f2937; }
.historial-fecha { font-size: 0.8rem; color: #9ca3af; }
.historial-puntos { font-weight: 600; font-size: 1rem; }
.historial-puntos.positivo { color: #10b981; }
.historial-puntos.negativo { color: #ef4444; }
.historial-empty { text-align: center; padding: 1.5rem; color: #6b7280; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #10b981; color: white; }
.btn-outline { background: white; border: 1px solid #d1d5db; color: #374151; }

@media (max-width: 480px) {
    .impacto-grid { grid-template-columns: 1fr; }
}
</style>
