<?php
/**
 * Template: Ocupación de Parkings en Tiempo Real
 *
 * Muestra la ocupación actual de cada parking con porcentaje y barra de progreso.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_parkings = $wpdb->prefix . 'flavor_parkings';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

// Verificar si existe la tabla
if (!Flavor_Platform_Helpers::tabla_existe($tabla_parkings)) {
    echo '<div class="parkings-empty"><p>' . esc_html__('El módulo de parkings no está configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Obtener estadísticas de ocupación por parking
$parkings = $wpdb->get_results("
    SELECT
        p.id,
        p.nombre,
        p.direccion,
        p.capacidad_total,
        p.horario,
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
");

// Calcular totales generales
$total_plazas_general = 0;
$total_ocupadas_general = 0;

foreach ($parkings as $parking) {
    $total_plazas = $parking->total_plazas ?: $parking->capacidad_total;
    $total_plazas_general += $total_plazas;
    $total_ocupadas_general += $parking->plazas_ocupadas;
}

$porcentaje_general = $total_plazas_general > 0
    ? round(($total_ocupadas_general / $total_plazas_general) * 100)
    : 0;

$parkings_base_url = Flavor_Platform_Helpers::get_action_url('parkings', '');

/**
 * Determina el color según el porcentaje de ocupación
 */
function obtener_color_ocupacion($porcentaje) {
    if ($porcentaje >= 90) return '#ef4444'; // Rojo
    if ($porcentaje >= 70) return '#f59e0b'; // Amarillo
    if ($porcentaje >= 50) return '#3b82f6'; // Azul
    return '#10b981'; // Verde
}

/**
 * Determina el estado textual según el porcentaje
 */
function obtener_estado_ocupacion($porcentaje) {
    if ($porcentaje >= 90) return __('Casi lleno', FLAVOR_PLATFORM_TEXT_DOMAIN);
    if ($porcentaje >= 70) return __('Alta ocupación', FLAVOR_PLATFORM_TEXT_DOMAIN);
    if ($porcentaje >= 50) return __('Ocupación media', FLAVOR_PLATFORM_TEXT_DOMAIN);
    return __('Buena disponibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN);
}
?>

<div class="parkings-ocupacion">
    <header class="ocupacion-header">
        <h2><?php esc_html_e('Ocupación en Tiempo Real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Estado actual de los parkings', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Resumen general -->
    <div class="ocupacion-resumen">
        <div class="resumen-grafico">
            <svg class="ocupacion-donut" viewBox="0 0 36 36">
                <circle class="donut-bg" cx="18" cy="18" r="15.915" fill="transparent" stroke="#e5e7eb" stroke-width="3"></circle>
                <circle class="donut-fill" cx="18" cy="18" r="15.915" fill="transparent"
                        stroke="<?php echo esc_attr(obtener_color_ocupacion($porcentaje_general)); ?>"
                        stroke-width="3"
                        stroke-dasharray="<?php echo esc_attr($porcentaje_general); ?>, 100"
                        stroke-linecap="round"
                        transform="rotate(-90 18 18)"></circle>
            </svg>
            <div class="donut-centro">
                <span class="donut-porcentaje"><?php echo esc_html($porcentaje_general); ?>%</span>
                <span class="donut-label"><?php esc_html_e('ocupado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <div class="resumen-stats">
            <div class="stat-item">
                <span class="stat-numero"><?php echo esc_html($total_plazas_general); ?></span>
                <span class="stat-label"><?php esc_html_e('Plazas totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="stat-item stat-item--ocupadas">
                <span class="stat-numero"><?php echo esc_html($total_ocupadas_general); ?></span>
                <span class="stat-label"><?php esc_html_e('Ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="stat-item stat-item--libres">
                <span class="stat-numero"><?php echo esc_html($total_plazas_general - $total_ocupadas_general); ?></span>
                <span class="stat-label"><?php esc_html_e('Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Última actualización -->
    <div class="ocupacion-timestamp">
        <span class="dashicons dashicons-clock"></span>
        <?php printf(
            esc_html__('Última actualización: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
            date_i18n('H:i:s')
        ); ?>
        <button class="btn-refresh" onclick="location.reload()" title="<?php esc_attr_e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <span class="dashicons dashicons-update"></span>
        </button>
    </div>

    <!-- Lista de parkings -->
    <?php if ($parkings): ?>
        <div class="ocupacion-lista">
            <?php foreach ($parkings as $parking):
                $total_plazas = $parking->total_plazas ?: $parking->capacidad_total;
                $plazas_ocupadas = $parking->plazas_ocupadas;
                $plazas_libres = max(0, $total_plazas - $plazas_ocupadas);
                $porcentaje = $total_plazas > 0 ? round(($plazas_ocupadas / $total_plazas) * 100) : 0;
                $color_ocupacion = obtener_color_ocupacion($porcentaje);
                $estado_texto = obtener_estado_ocupacion($porcentaje);
            ?>
                <div class="parking-ocupacion-card">
                    <div class="parking-info">
                        <h3 class="parking-nombre"><?php echo esc_html($parking->nombre); ?></h3>
                        <p class="parking-direccion">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html($parking->direccion); ?>
                        </p>
                        <?php if ($parking->horario): ?>
                            <p class="parking-horario">
                                <span class="dashicons dashicons-clock"></span>
                                <?php echo esc_html($parking->horario); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="parking-ocupacion-visual">
                        <div class="ocupacion-barra-container">
                            <div class="ocupacion-barra">
                                <div class="ocupacion-barra__fill"
                                     style="width: <?php echo esc_attr($porcentaje); ?>%; background: <?php echo esc_attr($color_ocupacion); ?>">
                                </div>
                            </div>
                            <div class="ocupacion-valores">
                                <span class="valor-ocupadas" style="color: <?php echo esc_attr($color_ocupacion); ?>">
                                    <?php echo esc_html($plazas_ocupadas); ?> <?php esc_html_e('ocupadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                                <span class="valor-libres">
                                    <?php echo esc_html($plazas_libres); ?> <?php esc_html_e('libres', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            </div>
                        </div>

                        <div class="ocupacion-porcentaje">
                            <span class="porcentaje-numero" style="color: <?php echo esc_attr($color_ocupacion); ?>">
                                <?php echo esc_html($porcentaje); ?>%
                            </span>
                            <span class="porcentaje-estado"><?php echo esc_html($estado_texto); ?></span>
                        </div>
                    </div>

                    <div class="parking-acciones">
                        <a href="<?php echo esc_url(add_query_arg('parking', $parking->id, $parkings_base_url . 'disponibilidad/')); ?>"
                           class="btn btn-outline btn-sm">
                            <?php esc_html_e('Ver plazas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                        <?php if ($plazas_libres > 0 && is_user_logged_in()): ?>
                            <a href="<?php echo esc_url(add_query_arg('parking', $parking->id, $parkings_base_url . 'solicitar/')); ?>"
                               class="btn btn-primary btn-sm">
                                <?php esc_html_e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="ocupacion-empty-state">
            <span class="dashicons dashicons-car"></span>
            <p><?php esc_html_e('No hay parkings configurados actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>
</div>

<style>
.parkings-ocupacion { max-width: 1000px; margin: 0 auto; }

.ocupacion-header { text-align: center; margin-bottom: 2rem; }
.ocupacion-header h2 { margin: 0 0 0.5rem; font-size: 1.75rem; color: #1f2937; }
.ocupacion-header p { color: #6b7280; margin: 0; }

.ocupacion-resumen { display: flex; align-items: center; justify-content: center; gap: 3rem; padding: 2rem; background: white; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); margin-bottom: 1.5rem; flex-wrap: wrap; }

.resumen-grafico { position: relative; width: 140px; height: 140px; }
.ocupacion-donut { width: 100%; height: 100%; }
.donut-centro { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
.donut-porcentaje { display: block; font-size: 1.75rem; font-weight: 700; color: #1f2937; line-height: 1; }
.donut-label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }

.resumen-stats { display: flex; gap: 2rem; }
.stat-item { text-align: center; }
.stat-numero { display: block; font-size: 2rem; font-weight: 700; color: #1f2937; line-height: 1; }
.stat-item--ocupadas .stat-numero { color: #ef4444; }
.stat-item--libres .stat-numero { color: #10b981; }
.stat-label { font-size: 0.75rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.25rem; display: block; }

.ocupacion-timestamp { text-align: center; margin-bottom: 1.5rem; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
.btn-refresh { background: none; border: none; cursor: pointer; color: #6b7280; padding: 0.25rem; border-radius: 4px; transition: all 0.2s; }
.btn-refresh:hover { color: #3b82f6; background: #eff6ff; }

.ocupacion-lista { display: flex; flex-direction: column; gap: 1rem; }

.parking-ocupacion-card { background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); padding: 1.5rem; display: grid; grid-template-columns: 1fr 2fr auto; gap: 1.5rem; align-items: center; }

.parking-nombre { margin: 0 0 0.25rem; font-size: 1.125rem; color: #1f2937; }
.parking-direccion, .parking-horario { margin: 0; font-size: 0.85rem; color: #6b7280; display: flex; align-items: center; gap: 0.25rem; }
.parking-horario { margin-top: 0.25rem; }

.ocupacion-barra-container { flex: 1; }
.ocupacion-barra { height: 12px; background: #e5e7eb; border-radius: 6px; overflow: hidden; margin-bottom: 0.5rem; }
.ocupacion-barra__fill { height: 100%; border-radius: 6px; transition: width 0.5s ease; }
.ocupacion-valores { display: flex; justify-content: space-between; font-size: 0.8rem; }
.valor-ocupadas { font-weight: 500; }
.valor-libres { color: #10b981; }

.ocupacion-porcentaje { text-align: center; min-width: 100px; }
.porcentaje-numero { display: block; font-size: 1.75rem; font-weight: 700; line-height: 1; }
.porcentaje-estado { font-size: 0.7rem; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; }

.parking-acciones { display: flex; flex-direction: column; gap: 0.5rem; }

.ocupacion-empty-state { text-align: center; padding: 3rem 1rem; background: white; border-radius: 12px; color: #6b7280; }
.ocupacion-empty-state .dashicons { font-size: 3rem; width: auto; height: auto; margin-bottom: 1rem; opacity: 0.5; }

.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.5rem 1rem; border-radius: 6px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; white-space: nowrap; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-sm { padding: 0.375rem 0.75rem; font-size: 0.8125rem; }

@media (max-width: 768px) {
    .ocupacion-resumen { flex-direction: column; gap: 1.5rem; }
    .resumen-stats { gap: 1.5rem; }
    .parking-ocupacion-card { grid-template-columns: 1fr; text-align: center; }
    .parking-direccion, .parking-horario { justify-content: center; }
    .ocupacion-valores { justify-content: center; gap: 1rem; }
    .parking-acciones { flex-direction: row; justify-content: center; }
}
</style>
