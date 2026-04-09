<?php
/**
 * Template: Mis Aportaciones de Compostaje
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="compostaje-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesión para ver tus aportaciones', 'flavor-platform') . '</h3>';
    echo '<p>' . esc_html__('Necesitas estar conectado para acceder a tu historial de compostaje.', 'flavor-platform') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesión', 'flavor-platform') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$usuario_id = get_current_user_id();
$tabla_aportaciones = $wpdb->prefix . 'flavor_aportaciones_compost';
$tabla_puntos = $wpdb->prefix . 'flavor_puntos_compostaje';

// Obtener estadísticas del usuario
$estadisticas_usuario = $wpdb->get_row($wpdb->prepare(
    "SELECT
        COUNT(*) as total_aportaciones,
        COALESCE(SUM(cantidad_kg), 0) as total_kg,
        COALESCE(SUM(puntos_obtenidos), 0) as total_puntos,
        COALESCE(SUM(co2_evitado_kg), 0) as co2_evitado
     FROM $tabla_aportaciones
     WHERE usuario_id = %d AND validado = 1",
    $usuario_id
));

// Obtener aportaciones recientes
$aportaciones = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, p.nombre as punto_nombre, p.direccion as punto_direccion
     FROM $tabla_aportaciones a
     LEFT JOIN $tabla_puntos p ON a.punto_id = p.id
     WHERE a.usuario_id = %d
     ORDER BY a.fecha_aportacion DESC
     LIMIT 50",
    $usuario_id
));

// Materiales disponibles para mostrar nombres
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
?>

<div class="compostaje-mis-aportaciones-wrapper">
    <!-- Resumen de estadísticas -->
    <div class="compostaje-stats-grid">
        <div class="compostaje-stat-card">
            <span class="stat-icon dashicons dashicons-archive"></span>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html(number_format($estadisticas_usuario->total_aportaciones ?? 0)); ?></span>
                <span class="stat-label"><?php esc_html_e('Aportaciones', 'flavor-platform'); ?></span>
            </div>
        </div>
        <div class="compostaje-stat-card">
            <span class="stat-icon dashicons dashicons-scale"></span>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html(number_format($estadisticas_usuario->total_kg ?? 0, 1)); ?> kg</span>
                <span class="stat-label"><?php esc_html_e('Total compostado', 'flavor-platform'); ?></span>
            </div>
        </div>
        <div class="compostaje-stat-card">
            <span class="stat-icon dashicons dashicons-star-filled"></span>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html(number_format($estadisticas_usuario->total_puntos ?? 0)); ?></span>
                <span class="stat-label"><?php esc_html_e('Puntos ganados', 'flavor-platform'); ?></span>
            </div>
        </div>
        <div class="compostaje-stat-card eco">
            <span class="stat-icon dashicons dashicons-cloud"></span>
            <div class="stat-content">
                <span class="stat-number"><?php echo esc_html(number_format($estadisticas_usuario->co2_evitado ?? 0, 1)); ?> kg</span>
                <span class="stat-label"><?php esc_html_e('CO₂ evitado', 'flavor-platform'); ?></span>
            </div>
        </div>
    </div>

    <!-- Acciones rápidas -->
    <div class="compostaje-actions">
        <a href="<?php echo esc_url(add_query_arg('vista', 'registrar', get_permalink())); ?>" class="btn btn-primary">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nueva aportación', 'flavor-platform'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('vista', 'mapa', get_permalink())); ?>" class="btn btn-outline">
            <span class="dashicons dashicons-location-alt"></span>
            <?php esc_html_e('Ver mapa', 'flavor-platform'); ?>
        </a>
    </div>

    <!-- Historial de aportaciones -->
    <div class="compostaje-historial">
        <h3><?php esc_html_e('Historial de aportaciones', 'flavor-platform'); ?></h3>

        <?php if ($aportaciones): ?>
            <div class="aportaciones-lista">
                <?php foreach ($aportaciones as $aportacion): ?>
                    <div class="aportacion-item <?php echo $aportacion->validado ? '' : 'pendiente'; ?>">
                        <div class="aportacion-fecha">
                            <span class="dia"><?php echo esc_html(date_i18n('d', strtotime($aportacion->fecha_aportacion))); ?></span>
                            <span class="mes"><?php echo esc_html(date_i18n('M', strtotime($aportacion->fecha_aportacion))); ?></span>
                        </div>
                        <div class="aportacion-info">
                            <div class="aportacion-material">
                                <span class="material-badge" style="background-color: <?php echo esc_attr($categoria_colors[$aportacion->categoria_material] ?? '#6b7280'); ?>">
                                    <?php echo esc_html($materiales_labels[$aportacion->tipo_material] ?? $aportacion->tipo_material); ?>
                                </span>
                            </div>
                            <div class="aportacion-punto">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($aportacion->punto_nombre ?? __('Punto no especificado', 'flavor-platform')); ?>
                            </div>
                            <?php if ($aportacion->notas): ?>
                                <p class="aportacion-notas"><?php echo esc_html($aportacion->notas); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="aportacion-cantidad">
                            <span class="kg"><?php echo esc_html(number_format($aportacion->cantidad_kg, 1)); ?> kg</span>
                            <span class="puntos">+<?php echo esc_html($aportacion->puntos_obtenidos); ?> pts</span>
                        </div>
                        <?php if (!$aportacion->validado): ?>
                            <span class="aportacion-estado pendiente"><?php esc_html_e('Pendiente', 'flavor-platform'); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="compostaje-empty">
                <span class="dashicons dashicons-archive"></span>
                <h4><?php esc_html_e('Sin aportaciones todavía', 'flavor-platform'); ?></h4>
                <p><?php esc_html_e('¡Empieza a compostar y gana puntos mientras ayudas al medio ambiente!', 'flavor-platform'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('vista', 'registrar', get_permalink())); ?>" class="btn btn-primary">
                    <?php esc_html_e('Registrar primera aportación', 'flavor-platform'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.compostaje-mis-aportaciones-wrapper { max-width: 900px; margin: 0 auto; }
.compostaje-login-required { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.compostaje-login-required .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }

.compostaje-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.compostaje-stat-card { display: flex; align-items: center; gap: 1rem; padding: 1.25rem; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.compostaje-stat-card .stat-icon { font-size: 32px; width: 32px; height: 32px; color: #4f46e5; }
.compostaje-stat-card.eco .stat-icon { color: #10b981; }
.compostaje-stat-card .stat-content { display: flex; flex-direction: column; }
.compostaje-stat-card .stat-number { font-size: 1.5rem; font-weight: 700; color: #1f2937; }
.compostaje-stat-card .stat-label { font-size: 0.8rem; color: #6b7280; }

.compostaje-actions { display: flex; gap: 0.75rem; margin-bottom: 2rem; flex-wrap: wrap; }

.compostaje-historial h3 { margin: 0 0 1rem; font-size: 1.25rem; color: #1f2937; }
.aportaciones-lista { display: flex; flex-direction: column; gap: 0.75rem; }
.aportacion-item { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: white; border-radius: 10px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); }
.aportacion-item.pendiente { border-left: 3px solid #fbbf24; }
.aportacion-fecha { display: flex; flex-direction: column; align-items: center; justify-content: center; width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; }
.aportacion-fecha .dia { font-size: 1.25rem; font-weight: 700; color: #1f2937; line-height: 1; }
.aportacion-fecha .mes { font-size: 0.7rem; color: #6b7280; text-transform: uppercase; }
.aportacion-info { flex: 1; }
.aportacion-material { margin-bottom: 0.25rem; }
.material-badge { display: inline-block; padding: 3px 8px; border-radius: 4px; color: white; font-size: 0.75rem; font-weight: 500; }
.aportacion-punto { font-size: 0.85rem; color: #6b7280; }
.aportacion-punto .dashicons { font-size: 14px; width: 14px; height: 14px; vertical-align: middle; }
.aportacion-notas { margin: 0.5rem 0 0; font-size: 0.85rem; color: #9ca3af; font-style: italic; }
.aportacion-cantidad { text-align: right; }
.aportacion-cantidad .kg { display: block; font-size: 1.1rem; font-weight: 600; color: #1f2937; }
.aportacion-cantidad .puntos { display: block; font-size: 0.8rem; color: #10b981; font-weight: 500; }
.aportacion-estado.pendiente { background: #fef3c7; color: #92400e; padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 500; }

.compostaje-empty { text-align: center; padding: 2.5rem; background: #f9fafb; border-radius: 12px; }
.compostaje-empty .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
.compostaje-empty h4 { margin: 0 0 0.5rem; color: #374151; }
.compostaje-empty p { margin: 0 0 1.5rem; color: #6b7280; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.25rem; border-radius: 8px; font-size: 0.9rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #10b981; color: white; }
.btn-primary:hover { background: #059669; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
</style>
