<?php
/**
 * Template: Mapa de Parkings
 *
 * Muestra un mapa con las ubicaciones de los parkings disponibles.
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
    echo '<div class="parkings-empty"><p>' . esc_html__('El módulo de parkings no está configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Obtener parkings con coordenadas
$parkings = $wpdb->get_results("
    SELECT
        p.id,
        p.nombre,
        p.direccion,
        p.latitud,
        p.longitud,
        p.horario,
        p.telefono,
        p.capacidad_total,
        (SELECT COUNT(*) FROM $tabla_plazas pl WHERE pl.parking_id = p.id AND pl.estado = 'activa') AS total_plazas,
        (SELECT COUNT(*) FROM $tabla_plazas pl
         LEFT JOIN $tabla_reservas r ON pl.id = r.plaza_id AND r.estado = 'activa' AND NOW() BETWEEN r.fecha_inicio AND r.fecha_fin
         WHERE pl.parking_id = p.id AND pl.estado = 'activa' AND r.id IS NULL) AS plazas_libres
    FROM $tabla_parkings p
    WHERE p.estado = 'activo'
    ORDER BY p.nombre
");

// URL base para parkings
$parkings_base_url = home_url('/mi-portal/parkings/');

// Preparar datos para JS
$parkings_json = [];
foreach ($parkings as $parking) {
    // Calcular porcentaje de ocupación
    $total_plazas = max(1, $parking->total_plazas ?: $parking->capacidad_total);
    $plazas_libres = $parking->plazas_libres ?? 0;
    $porcentaje_ocupacion = round((($total_plazas - $plazas_libres) / $total_plazas) * 100);

    // Determinar estado visual
    $estado_visual = 'verde';
    if ($porcentaje_ocupacion >= 90) {
        $estado_visual = 'rojo';
    } elseif ($porcentaje_ocupacion >= 70) {
        $estado_visual = 'amarillo';
    }

    $parkings_json[] = [
        'id' => (int) $parking->id,
        'nombre' => $parking->nombre,
        'direccion' => $parking->direccion,
        'lat' => $parking->latitud ? (float) $parking->latitud : null,
        'lng' => $parking->longitud ? (float) $parking->longitud : null,
        'horario' => $parking->horario,
        'telefono' => $parking->telefono,
        'total_plazas' => (int) $total_plazas,
        'plazas_libres' => (int) $plazas_libres,
        'porcentaje_ocupacion' => $porcentaje_ocupacion,
        'estado_visual' => $estado_visual,
    ];
}

$colores_estado = [
    'verde' => '#10b981',
    'amarillo' => '#f59e0b',
    'rojo' => '#ef4444',
];
?>

<div class="parkings-mapa-wrapper">
    <header class="mapa-header">
        <h2><?php esc_html_e('Mapa de Parkings', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Encuentra el parking más cercano a tu destino', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Leyenda -->
    <div class="mapa-leyenda">
        <span class="leyenda-item">
            <span class="leyenda-color" style="background: <?php echo esc_attr($colores_estado['verde']); ?>"></span>
            <?php esc_html_e('Alta disponibilidad', 'flavor-chat-ia'); ?>
        </span>
        <span class="leyenda-item">
            <span class="leyenda-color" style="background: <?php echo esc_attr($colores_estado['amarillo']); ?>"></span>
            <?php esc_html_e('Disponibilidad media', 'flavor-chat-ia'); ?>
        </span>
        <span class="leyenda-item">
            <span class="leyenda-color" style="background: <?php echo esc_attr($colores_estado['rojo']); ?>"></span>
            <?php esc_html_e('Casi lleno', 'flavor-chat-ia'); ?>
        </span>
    </div>

    <!-- Mapa -->
    <div class="mapa-contenedor">
        <div id="mapa-parkings" class="mapa-canvas">
            <div class="mapa-placeholder">
                <span class="dashicons dashicons-location-alt"></span>
                <p><?php esc_html_e('Mapa de ubicaciones', 'flavor-chat-ia'); ?></p>
                <small><?php esc_html_e('Requiere configurar API de mapas', 'flavor-chat-ia'); ?></small>
            </div>
        </div>
    </div>

    <!-- Contador -->
    <div class="mapa-info">
        <span class="parkings-count">
            <?php printf(
                esc_html(_n('%d parking disponible', '%d parkings disponibles', count($parkings), 'flavor-chat-ia')),
                count($parkings)
            ); ?>
        </span>
    </div>

    <!-- Lista de parkings -->
    <?php if ($parkings): ?>
        <div class="parkings-lista">
            <?php foreach ($parkings_json as $parking_item): ?>
                <div class="parking-mini"
                     onclick="centrarMapa(<?php echo esc_attr($parking_item['lat'] ?? 0); ?>, <?php echo esc_attr($parking_item['lng'] ?? 0); ?>)"
                     data-parking-id="<?php echo esc_attr($parking_item['id']); ?>">
                    <span class="mini-estado" style="background: <?php echo esc_attr($colores_estado[$parking_item['estado_visual']]); ?>"></span>
                    <div class="mini-info">
                        <span class="mini-nombre"><?php echo esc_html($parking_item['nombre']); ?></span>
                        <span class="mini-direccion"><?php echo esc_html($parking_item['direccion']); ?></span>
                    </div>
                    <div class="mini-disponibilidad">
                        <span class="disponibilidad-numero"><?php echo esc_html($parking_item['plazas_libres']); ?></span>
                        <span class="disponibilidad-label"><?php esc_html_e('libres', 'flavor-chat-ia'); ?></span>
                    </div>
                    <a href="<?php echo esc_url(add_query_arg('parking', $parking_item['id'], $parkings_base_url . 'disponibilidad/')); ?>" class="mini-ver-btn">
                        <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="parkings-empty-state">
            <span class="dashicons dashicons-car"></span>
            <p><?php esc_html_e('No hay parkings configurados actualmente.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.parkings-mapa-wrapper { max-width: 1100px; margin: 0 auto; }

.mapa-header { text-align: center; margin-bottom: 1.5rem; }
.mapa-header h2 { margin: 0 0 0.5rem; font-size: 1.75rem; color: #1f2937; }
.mapa-header p { color: #6b7280; margin: 0; }

.mapa-leyenda { display: flex; justify-content: center; gap: 1.5rem; flex-wrap: wrap; margin-bottom: 1rem; padding: 1rem; background: #f9fafb; border-radius: 10px; }
.leyenda-item { display: flex; align-items: center; gap: 0.35rem; font-size: 0.85rem; color: #4b5563; }
.leyenda-color { width: 12px; height: 12px; border-radius: 50%; }

.mapa-contenedor { margin-bottom: 1rem; }
.mapa-canvas { width: 100%; height: 400px; border-radius: 12px; background: #e5e7eb; overflow: hidden; }
.mapa-placeholder { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #6b7280; }
.mapa-placeholder .dashicons { font-size: 3rem; width: auto; height: auto; margin-bottom: 0.5rem; opacity: 0.5; }
.mapa-placeholder p { margin: 0 0 0.25rem; font-size: 1rem; }
.mapa-placeholder small { font-size: 0.8rem; opacity: 0.7; }

.mapa-info { text-align: center; margin-bottom: 1.5rem; }
.parkings-count { font-size: 0.9rem; color: #6b7280; }

.parkings-lista { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }

.parking-mini { display: flex; align-items: center; gap: 0.75rem; padding: 1rem 1.25rem; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background 0.2s; }
.parking-mini:hover { background: #f9fafb; }
.parking-mini:last-child { border-bottom: none; }

.mini-estado { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

.mini-info { flex: 1; min-width: 0; }
.mini-nombre { display: block; font-weight: 600; color: #1f2937; font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.mini-direccion { font-size: 0.8rem; color: #6b7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

.mini-disponibilidad { text-align: center; padding: 0 0.75rem; }
.disponibilidad-numero { display: block; font-size: 1.25rem; font-weight: 700; color: #10b981; line-height: 1; }
.disponibilidad-label { font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }

.mini-ver-btn { padding: 0.375rem 0.75rem; background: #f3f4f6; color: #374151; border-radius: 6px; font-size: 0.8125rem; font-weight: 500; text-decoration: none; transition: all 0.2s; }
.mini-ver-btn:hover { background: #3b82f6; color: white; }

.parkings-empty-state { text-align: center; padding: 3rem 1rem; color: #6b7280; }
.parkings-empty-state .dashicons { font-size: 3rem; width: auto; height: auto; margin-bottom: 1rem; opacity: 0.5; }

@media (max-width: 640px) {
    .mapa-canvas { height: 300px; }
    .parking-mini { flex-wrap: wrap; }
    .mini-disponibilidad { margin-left: auto; }
    .mini-ver-btn { margin-top: 0.5rem; width: 100%; text-align: center; }
}
</style>

<script>
var parkingsData = <?php echo json_encode($parkings_json); ?>;

function centrarMapa(lat, lng) {
    if (!lat || !lng) {
        console.log('Coordenadas no disponibles');
        return;
    }
    // Implementar con API de mapas (Google Maps, Leaflet, etc.)
    console.log('Centrar en:', lat, lng);
}

// Inicializar mapa cuando esté disponible la API
function inicializarMapaParkings() {
    // Placeholder para integración con API de mapas
    console.log('Datos de parkings:', parkingsData);
}

document.addEventListener('DOMContentLoaded', inicializarMapaParkings);
</script>
