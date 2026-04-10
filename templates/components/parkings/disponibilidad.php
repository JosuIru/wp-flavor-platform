<?php
/**
 * Template: Widget compacto de disponibilidad de plazas en tiempo real
 *
 * @package Flavor_Platform
 * @subpackage Templates/Components/Parkings
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo = isset($args['titulo']) ? $args['titulo'] : 'Disponibilidad en Tiempo Real';
$mostrar_resumen = isset($args['mostrar_resumen']) ? $args['mostrar_resumen'] : true;
$actualizar_cada = isset($args['actualizar_cada']) ? $args['actualizar_cada'] : 30; // segundos
$estilo = isset($args['estilo']) ? $args['estilo'] : 'compacto'; // compacto, detallado, minimalista

// Datos de demostración
$parkings_demo = isset($args['parkings']) ? $args['parkings'] : array(
    array(
        'id' => 1,
        'nombre' => 'Parking Las Flores',
        'codigo' => 'PLF',
        'plazas_totales' => 50,
        'plazas_disponibles' => 12,
        'plazas_reservadas' => 5,
        'tendencia' => 'bajando', // subiendo, bajando, estable
        'ultima_actualizacion' => '2024-01-15 14:30:00',
        'estado' => 'activo'
    ),
    array(
        'id' => 2,
        'nombre' => 'Parking Sol',
        'codigo' => 'PSL',
        'plazas_totales' => 30,
        'plazas_disponibles' => 5,
        'plazas_reservadas' => 3,
        'tendencia' => 'bajando',
        'ultima_actualizacion' => '2024-01-15 14:29:00',
        'estado' => 'activo'
    ),
    array(
        'id' => 3,
        'nombre' => 'Parking Centro',
        'codigo' => 'PCE',
        'plazas_totales' => 80,
        'plazas_disponibles' => 25,
        'plazas_reservadas' => 8,
        'tendencia' => 'subiendo',
        'ultima_actualizacion' => '2024-01-15 14:30:00',
        'estado' => 'activo'
    ),
    array(
        'id' => 4,
        'nombre' => 'Parking Norte',
        'codigo' => 'PNO',
        'plazas_totales' => 40,
        'plazas_disponibles' => 0,
        'plazas_reservadas' => 2,
        'tendencia' => 'estable',
        'ultima_actualizacion' => '2024-01-15 14:28:00',
        'estado' => 'activo'
    ),
    array(
        'id' => 5,
        'nombre' => 'Parking Verde',
        'codigo' => 'PVE',
        'plazas_totales' => 60,
        'plazas_disponibles' => 18,
        'plazas_reservadas' => 4,
        'tendencia' => 'estable',
        'ultima_actualizacion' => '2024-01-15 14:30:00',
        'estado' => 'activo'
    ),
    array(
        'id' => 6,
        'nombre' => 'Parking Plaza Mayor',
        'codigo' => 'PPM',
        'plazas_totales' => 100,
        'plazas_disponibles' => 35,
        'plazas_reservadas' => 12,
        'tendencia' => 'subiendo',
        'ultima_actualizacion' => '2024-01-15 14:30:00',
        'estado' => 'mantenimiento'
    )
);

// Calcular estadísticas generales
$total_plazas = 0;
$total_disponibles = 0;
$total_reservadas = 0;
$parkings_activos = 0;

foreach ($parkings_demo as $parking) {
    $total_plazas += $parking['plazas_totales'];
    $total_disponibles += $parking['plazas_disponibles'];
    $total_reservadas += $parking['plazas_reservadas'];
    if ($parking['estado'] === 'activo') {
        $parkings_activos++;
    }
}

$porcentaje_ocupacion_global = $total_plazas > 0 ? (($total_plazas - $total_disponibles) / $total_plazas) * 100 : 0;

// Iconos de tendencia
$iconos_tendencia = array(
    'subiendo' => '&#8593;',
    'bajando' => '&#8595;',
    'estable' => '&#8596;'
);

$clases_tendencia = array(
    'subiendo' => 'flavor-tendencia-subiendo',
    'bajando' => 'flavor-tendencia-bajando',
    'estable' => 'flavor-tendencia-estable'
);
?>

<div class="flavor-disponibilidad-widget flavor-disponibilidad-<?php echo esc_attr($estilo); ?>"
     data-actualizar="<?php echo esc_attr($actualizar_cada); ?>">

    <div class="flavor-disponibilidad-header">
        <div class="flavor-disponibilidad-titulo-wrapper">
            <h3 class="flavor-disponibilidad-titulo"><?php echo esc_html($titulo); ?></h3>
            <span class="flavor-disponibilidad-live-badge">
                <span class="flavor-disponibilidad-live-dot"></span>
                EN VIVO
            </span>
        </div>
        <div class="flavor-disponibilidad-ultima-sync">
            <span class="flavor-disponibilidad-icon-sync">&#8635;</span>
            <span class="flavor-disponibilidad-sync-texto">Actualizado hace <strong>5 seg</strong></span>
        </div>
    </div>

    <?php if ($mostrar_resumen) : ?>
        <div class="flavor-disponibilidad-resumen">
            <div class="flavor-disponibilidad-stat flavor-disponibilidad-stat-principal">
                <div class="flavor-disponibilidad-stat-circulo">
                    <svg viewBox="0 0 36 36" class="flavor-disponibilidad-circular-chart">
                        <path class="flavor-disponibilidad-circle-bg"
                            d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                        <path class="flavor-disponibilidad-circle"
                            stroke-dasharray="<?php echo esc_attr($porcentaje_ocupacion_global); ?>, 100"
                            d="M18 2.0845
                            a 15.9155 15.9155 0 0 1 0 31.831
                            a 15.9155 15.9155 0 0 1 0 -31.831"
                        />
                    </svg>
                    <span class="flavor-disponibilidad-stat-porcentaje"><?php echo round($porcentaje_ocupacion_global); ?>%</span>
                </div>
                <span class="flavor-disponibilidad-stat-label">Ocupación global</span>
            </div>

            <div class="flavor-disponibilidad-stats-secundarias">
                <div class="flavor-disponibilidad-stat-item">
                    <span class="flavor-disponibilidad-stat-numero flavor-disponibilidad-color-disponible"><?php echo esc_html($total_disponibles); ?></span>
                    <span class="flavor-disponibilidad-stat-texto">Plazas libres</span>
                </div>
                <div class="flavor-disponibilidad-stat-item">
                    <span class="flavor-disponibilidad-stat-numero flavor-disponibilidad-color-reservado"><?php echo esc_html($total_reservadas); ?></span>
                    <span class="flavor-disponibilidad-stat-texto">Reservadas</span>
                </div>
                <div class="flavor-disponibilidad-stat-item">
                    <span class="flavor-disponibilidad-stat-numero flavor-disponibilidad-color-ocupado"><?php echo esc_html($total_plazas - $total_disponibles - $total_reservadas); ?></span>
                    <span class="flavor-disponibilidad-stat-texto">Ocupadas</span>
                </div>
                <div class="flavor-disponibilidad-stat-item">
                    <span class="flavor-disponibilidad-stat-numero"><?php echo esc_html($parkings_activos); ?>/<?php echo count($parkings_demo); ?></span>
                    <span class="flavor-disponibilidad-stat-texto">Parkings activos</span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="flavor-disponibilidad-lista">
        <?php foreach ($parkings_demo as $parking) :
            $porcentaje_ocupacion = $parking['plazas_totales'] > 0
                ? (($parking['plazas_totales'] - $parking['plazas_disponibles']) / $parking['plazas_totales']) * 100
                : 100;

            $clase_estado = 'activo';
            if ($parking['plazas_disponibles'] === 0) {
                $clase_estado = 'completo';
            } elseif ($parking['plazas_disponibles'] <= 5) {
                $clase_estado = 'critico';
            } elseif ($parking['plazas_disponibles'] <= 15) {
                $clase_estado = 'medio';
            }

            $en_mantenimiento = $parking['estado'] === 'mantenimiento';
        ?>
            <div class="flavor-disponibilidad-item <?php echo $en_mantenimiento ? 'flavor-disponibilidad-item-mantenimiento' : ''; ?>"
                 data-parking-id="<?php echo esc_attr($parking['id']); ?>">

                <div class="flavor-disponibilidad-item-izquierda">
                    <div class="flavor-disponibilidad-item-codigo <?php echo esc_attr('flavor-disponibilidad-estado-' . $clase_estado); ?>">
                        <?php echo esc_html($parking['codigo']); ?>
                    </div>
                    <div class="flavor-disponibilidad-item-info">
                        <span class="flavor-disponibilidad-item-nombre"><?php echo esc_html($parking['nombre']); ?></span>
                        <?php if ($en_mantenimiento) : ?>
                            <span class="flavor-disponibilidad-item-mantenimiento-badge">En mantenimiento</span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flavor-disponibilidad-item-centro">
                    <div class="flavor-disponibilidad-barra-container">
                        <div class="flavor-disponibilidad-barra">
                            <div class="flavor-disponibilidad-barra-ocupada" style="width: <?php echo esc_attr(100 - ($parking['plazas_disponibles'] / $parking['plazas_totales'] * 100) - ($parking['plazas_reservadas'] / $parking['plazas_totales'] * 100)); ?>%;"></div>
                            <div class="flavor-disponibilidad-barra-reservada" style="width: <?php echo esc_attr($parking['plazas_reservadas'] / $parking['plazas_totales'] * 100); ?>%;"></div>
                        </div>
                        <div class="flavor-disponibilidad-barra-labels">
                            <span><?php echo esc_html($parking['plazas_disponibles']); ?> libres</span>
                            <span><?php echo esc_html($parking['plazas_reservadas']); ?> reserv.</span>
                        </div>
                    </div>
                </div>

                <div class="flavor-disponibilidad-item-derecha">
                    <div class="flavor-disponibilidad-item-plazas <?php echo esc_attr('flavor-disponibilidad-estado-' . $clase_estado); ?>">
                        <span class="flavor-disponibilidad-plazas-numero"><?php echo esc_html($parking['plazas_disponibles']); ?></span>
                        <span class="flavor-disponibilidad-plazas-total">/<?php echo esc_html($parking['plazas_totales']); ?></span>
                    </div>
                    <span class="flavor-disponibilidad-tendencia <?php echo esc_attr($clases_tendencia[$parking['tendencia']]); ?>"
                          title="Tendencia: <?php echo esc_attr($parking['tendencia']); ?>">
                        <?php echo $iconos_tendencia[$parking['tendencia']]; ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="flavor-disponibilidad-leyenda">
        <span class="flavor-disponibilidad-leyenda-item">
            <span class="flavor-disponibilidad-leyenda-color flavor-disponibilidad-leyenda-disponible"></span>
            Disponible
        </span>
        <span class="flavor-disponibilidad-leyenda-item">
            <span class="flavor-disponibilidad-leyenda-color flavor-disponibilidad-leyenda-reservado"></span>
            Reservado
        </span>
        <span class="flavor-disponibilidad-leyenda-item">
            <span class="flavor-disponibilidad-leyenda-color flavor-disponibilidad-leyenda-ocupado"></span>
            Ocupado
        </span>
    </div>

    <div class="flavor-disponibilidad-acciones">
        <button type="button" class="flavor-disponibilidad-btn-actualizar">
            <span class="flavor-disponibilidad-icon-refresh">&#8635;</span>
            Actualizar ahora
        </button>
        <button type="button" class="flavor-disponibilidad-btn-ver-mapa">
            <span class="flavor-disponibilidad-icon-mapa">&#128506;</span>
            Ver en mapa
        </button>
    </div>
</div>

<style>
.flavor-disponibilidad-widget {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    padding: 20px;
    max-width: 500px;
}

/* Header */
.flavor-disponibilidad-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 20px;
}

.flavor-disponibilidad-titulo-wrapper {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-disponibilidad-titulo {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a1a2e;
}

.flavor-disponibilidad-live-badge {
    display: flex;
    align-items: center;
    gap: 6px;
    background: #fef2f2;
    color: #dc2626;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.flavor-disponibilidad-live-dot {
    width: 8px;
    height: 8px;
    background: #dc2626;
    border-radius: 50%;
    animation: flavor-pulse 1.5s infinite;
}

@keyframes flavor-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

.flavor-disponibilidad-ultima-sync {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8rem;
    color: #64748b;
}

.flavor-disponibilidad-icon-sync {
    animation: flavor-rotate 2s linear infinite;
}

@keyframes flavor-rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Resumen */
.flavor-disponibilidad-resumen {
    display: flex;
    gap: 20px;
    padding: 15px;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-radius: 12px;
    margin-bottom: 20px;
}

.flavor-disponibilidad-stat-principal {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.flavor-disponibilidad-stat-circulo {
    position: relative;
    width: 80px;
    height: 80px;
}

.flavor-disponibilidad-circular-chart {
    display: block;
    width: 100%;
    height: 100%;
}

.flavor-disponibilidad-circle-bg {
    fill: none;
    stroke: #e2e8f0;
    stroke-width: 3;
}

.flavor-disponibilidad-circle {
    fill: none;
    stroke: #4f46e5;
    stroke-width: 3;
    stroke-linecap: round;
    transform: rotate(-90deg);
    transform-origin: center;
    transition: stroke-dasharray 0.5s;
}

.flavor-disponibilidad-stat-porcentaje {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a1a2e;
}

.flavor-disponibilidad-stat-label {
    font-size: 0.75rem;
    color: #64748b;
    text-align: center;
}

.flavor-disponibilidad-stats-secundarias {
    flex: 1;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
}

.flavor-disponibilidad-stat-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-disponibilidad-stat-numero {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a2e;
}

.flavor-disponibilidad-color-disponible {
    color: #22c55e;
}

.flavor-disponibilidad-color-reservado {
    color: #f59e0b;
}

.flavor-disponibilidad-color-ocupado {
    color: #64748b;
}

.flavor-disponibilidad-stat-texto {
    font-size: 0.75rem;
    color: #64748b;
}

/* Lista de parkings */
.flavor-disponibilidad-lista {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 15px;
}

.flavor-disponibilidad-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px;
    background: #f8fafc;
    border-radius: 10px;
    transition: all 0.2s;
}

.flavor-disponibilidad-item:hover {
    background: #f1f5f9;
    transform: translateX(4px);
}

.flavor-disponibilidad-item-mantenimiento {
    opacity: 0.6;
    background: #fef3c7;
}

.flavor-disponibilidad-item-izquierda {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
}

.flavor-disponibilidad-item-codigo {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 700;
    color: white;
}

.flavor-disponibilidad-estado-activo {
    background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
}

.flavor-disponibilidad-estado-medio {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.flavor-disponibilidad-estado-critico {
    background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
}

.flavor-disponibilidad-estado-completo {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.flavor-disponibilidad-item-info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.flavor-disponibilidad-item-nombre {
    font-size: 0.9rem;
    font-weight: 500;
    color: #1a1a2e;
}

.flavor-disponibilidad-item-mantenimiento-badge {
    font-size: 0.7rem;
    color: #d97706;
    font-weight: 500;
}

.flavor-disponibilidad-item-centro {
    flex: 1.5;
    padding: 0 15px;
}

.flavor-disponibilidad-barra-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-disponibilidad-barra {
    height: 8px;
    background: #22c55e;
    border-radius: 4px;
    display: flex;
    overflow: hidden;
}

.flavor-disponibilidad-barra-ocupada {
    background: #94a3b8;
}

.flavor-disponibilidad-barra-reservada {
    background: #f59e0b;
}

.flavor-disponibilidad-barra-labels {
    display: flex;
    justify-content: space-between;
    font-size: 0.65rem;
    color: #94a3b8;
}

.flavor-disponibilidad-item-derecha {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-disponibilidad-item-plazas {
    text-align: right;
}

.flavor-disponibilidad-plazas-numero {
    font-size: 1.25rem;
    font-weight: 700;
}

.flavor-disponibilidad-plazas-total {
    font-size: 0.8rem;
    color: #94a3b8;
}

.flavor-disponibilidad-tendencia {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 0.9rem;
}

.flavor-tendencia-subiendo {
    background: #dcfce7;
    color: #22c55e;
}

.flavor-tendencia-bajando {
    background: #fef2f2;
    color: #ef4444;
}

.flavor-tendencia-estable {
    background: #f1f5f9;
    color: #64748b;
}

/* Leyenda */
.flavor-disponibilidad-leyenda {
    display: flex;
    justify-content: center;
    gap: 20px;
    padding: 12px 0;
    border-top: 1px solid #f1f5f9;
    margin-bottom: 15px;
}

.flavor-disponibilidad-leyenda-item {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    color: #64748b;
}

.flavor-disponibilidad-leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.flavor-disponibilidad-leyenda-disponible {
    background: #22c55e;
}

.flavor-disponibilidad-leyenda-reservado {
    background: #f59e0b;
}

.flavor-disponibilidad-leyenda-ocupado {
    background: #94a3b8;
}

/* Acciones */
.flavor-disponibilidad-acciones {
    display: flex;
    gap: 10px;
}

.flavor-disponibilidad-btn-actualizar,
.flavor-disponibilidad-btn-ver-mapa {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
}

.flavor-disponibilidad-btn-actualizar {
    background: #f1f5f9;
    color: #475569;
}

.flavor-disponibilidad-btn-actualizar:hover {
    background: #e2e8f0;
}

.flavor-disponibilidad-btn-ver-mapa {
    background: #4f46e5;
    color: white;
}

.flavor-disponibilidad-btn-ver-mapa:hover {
    background: #4338ca;
}

/* Responsive */
@media (max-width: 480px) {
    .flavor-disponibilidad-widget {
        padding: 15px;
    }

    .flavor-disponibilidad-header {
        flex-direction: column;
        gap: 10px;
    }

    .flavor-disponibilidad-resumen {
        flex-direction: column;
        align-items: center;
    }

    .flavor-disponibilidad-stats-secundarias {
        width: 100%;
    }

    .flavor-disponibilidad-item-centro {
        display: none;
    }

    .flavor-disponibilidad-leyenda {
        flex-wrap: wrap;
        gap: 10px;
    }

    .flavor-disponibilidad-acciones {
        flex-direction: column;
    }
}

/* Estilo minimalista */
.flavor-disponibilidad-minimalista {
    padding: 15px;
    border-radius: 12px;
}

.flavor-disponibilidad-minimalista .flavor-disponibilidad-resumen {
    display: none;
}

.flavor-disponibilidad-minimalista .flavor-disponibilidad-item {
    padding: 8px;
}

.flavor-disponibilidad-minimalista .flavor-disponibilidad-item-centro {
    display: none;
}

/* Estilo detallado */
.flavor-disponibilidad-detallado {
    max-width: 600px;
}

.flavor-disponibilidad-detallado .flavor-disponibilidad-item {
    padding: 16px;
}

.flavor-disponibilidad-detallado .flavor-disponibilidad-item-codigo {
    width: 50px;
    height: 50px;
    font-size: 0.85rem;
}
</style>
