<?php
/**
 * Template: Widget Compacto de Ocupación Actual
 *
 * Muestra un resumen compacto de la ocupación de parkings para widgets y sidebars.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_parkings = $wpdb->prefix . 'flavor_parkings';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_parkings)) {
    return;
}

// Parámetros del widget
$limite_parkings = isset($atts['limite']) ? absint($atts['limite']) : 3;
$mostrar_titulo = isset($atts['titulo']) ? $atts['titulo'] === 'true' : true;

// Obtener estadísticas de ocupación
$parkings = $wpdb->get_results($wpdb->prepare("
    SELECT
        p.id,
        p.nombre,
        p.capacidad_total,
        (SELECT COUNT(*) FROM $tabla_plazas pl WHERE pl.parking_id = p.id AND pl.estado = 'activa') AS total_plazas,
        (SELECT COUNT(*) FROM $tabla_plazas pl
         INNER JOIN $tabla_reservas r ON pl.id = r.plaza_id
         WHERE pl.parking_id = p.id
           AND pl.estado = 'activa'
           AND r.estado IN ('activa', 'confirmada')
           AND NOW() BETWEEN r.fecha_inicio AND r.fecha_fin) AS plazas_ocupadas
    FROM $tabla_parkings p
    WHERE p.estado = 'activo'
    ORDER BY p.nombre
    LIMIT %d
", $limite_parkings));

if (empty($parkings)) {
    return;
}

// Calcular totales
$total_plazas_general = 0;
$total_ocupadas_general = 0;

foreach ($parkings as $parking) {
    $total = $parking->total_plazas ?: $parking->capacidad_total;
    $total_plazas_general += $total;
    $total_ocupadas_general += $parking->plazas_ocupadas;
}

$plazas_libres_general = $total_plazas_general - $total_ocupadas_general;
$porcentaje_general = $total_plazas_general > 0
    ? round(($total_ocupadas_general / $total_plazas_general) * 100)
    : 0;

// Determinar color según ocupación
$color_estado = '#10b981'; // Verde
if ($porcentaje_general >= 90) {
    $color_estado = '#ef4444'; // Rojo
} elseif ($porcentaje_general >= 70) {
    $color_estado = '#f59e0b'; // Amarillo
}

$parkings_url = Flavor_Chat_Helpers::get_action_url('parkings', '');
?>

<div class="widget-ocupacion-actual">
    <?php if ($mostrar_titulo): ?>
        <header class="widget-header">
            <h3 class="widget-titulo">
                <span class="dashicons dashicons-car"></span>
                <?php esc_html_e('Parkings', 'flavor-chat-ia'); ?>
            </h3>
        </header>
    <?php endif; ?>

    <!-- Resumen general compacto -->
    <div class="ocupacion-resumen-compacto">
        <div class="resumen-circulo" style="--porcentaje: <?php echo esc_attr($porcentaje_general); ?>; --color: <?php echo esc_attr($color_estado); ?>">
            <span class="circulo-valor"><?php echo esc_html($plazas_libres_general); ?></span>
            <span class="circulo-label"><?php esc_html_e('libres', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="resumen-texto">
            <span class="texto-principal">
                <?php printf(
                    esc_html__('%d de %d plazas ocupadas', 'flavor-chat-ia'),
                    $total_ocupadas_general,
                    $total_plazas_general
                ); ?>
            </span>
            <span class="texto-secundario" style="color: <?php echo esc_attr($color_estado); ?>">
                <?php echo esc_html($porcentaje_general); ?>% <?php esc_html_e('ocupación', 'flavor-chat-ia'); ?>
            </span>
        </div>
    </div>

    <!-- Lista compacta de parkings -->
    <ul class="ocupacion-lista-compacta">
        <?php foreach ($parkings as $parking):
            $total = $parking->total_plazas ?: $parking->capacidad_total;
            $libres = max(0, $total - $parking->plazas_ocupadas);
            $porcentaje = $total > 0 ? round(($parking->plazas_ocupadas / $total) * 100) : 0;

            $color_parking = '#10b981';
            if ($porcentaje >= 90) $color_parking = '#ef4444';
            elseif ($porcentaje >= 70) $color_parking = '#f59e0b';
        ?>
            <li class="ocupacion-item">
                <span class="item-indicador" style="background: <?php echo esc_attr($color_parking); ?>"></span>
                <span class="item-nombre"><?php echo esc_html($parking->nombre); ?></span>
                <span class="item-libres" style="color: <?php echo esc_attr($color_parking); ?>">
                    <?php echo esc_html($libres); ?>
                </span>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- Enlace a ver más -->
    <a href="<?php echo esc_url($parkings_url . 'ocupacion/'); ?>" class="widget-ver-mas">
        <?php esc_html_e('Ver todos los parkings', 'flavor-chat-ia'); ?>
        <span class="dashicons dashicons-arrow-right-alt2"></span>
    </a>
</div>

<style>
.widget-ocupacion-actual { background: white; border-radius: 12px; padding: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }

.widget-header { margin-bottom: 1rem; }
.widget-titulo { margin: 0; font-size: 1rem; color: #1f2937; display: flex; align-items: center; gap: 0.5rem; }
.widget-titulo .dashicons { color: #6b7280; font-size: 1.125rem; }

.ocupacion-resumen-compacto { display: flex; align-items: center; gap: 1rem; padding: 1rem; background: #f9fafb; border-radius: 10px; margin-bottom: 1rem; }

.resumen-circulo { position: relative; width: 60px; height: 60px; border-radius: 50%; background: conic-gradient(var(--color) calc(var(--porcentaje) * 1%), #e5e7eb 0); display: flex; flex-direction: column; align-items: center; justify-content: center; }
.resumen-circulo::before { content: ''; position: absolute; width: 48px; height: 48px; background: white; border-radius: 50%; }
.circulo-valor { position: relative; font-size: 1.125rem; font-weight: 700; color: #1f2937; line-height: 1; }
.circulo-label { position: relative; font-size: 0.6rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }

.resumen-texto { flex: 1; }
.texto-principal { display: block; font-size: 0.85rem; color: #4b5563; margin-bottom: 0.125rem; }
.texto-secundario { font-size: 0.8rem; font-weight: 600; }

.ocupacion-lista-compacta { list-style: none; margin: 0; padding: 0; }
.ocupacion-item { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0; border-bottom: 1px solid #f3f4f6; }
.ocupacion-item:last-child { border-bottom: none; }
.item-indicador { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.item-nombre { flex: 1; font-size: 0.85rem; color: #374151; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.item-libres { font-size: 0.9rem; font-weight: 600; min-width: 24px; text-align: right; }

.widget-ver-mas { display: flex; align-items: center; justify-content: center; gap: 0.25rem; margin-top: 1rem; padding: 0.5rem; color: #3b82f6; font-size: 0.8125rem; font-weight: 500; text-decoration: none; border-radius: 6px; transition: background 0.2s; }
.widget-ver-mas:hover { background: #eff6ff; }
.widget-ver-mas .dashicons { font-size: 1rem; }
</style>
