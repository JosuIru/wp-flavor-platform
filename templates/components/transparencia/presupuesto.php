<?php
/**
 * Template: Widget visual del presupuesto municipal
 *
 * Variables disponibles en $args:
 * - titulo: Título del widget
 * - año: Año del presupuesto
 * - total: Total del presupuesto
 * - partidas: Array de partidas presupuestarias
 * - mostrar_grafico: Tipo de gráfico ('barras', 'donut', 'ambos')
 * - moneda: Símbolo de moneda
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_presupuesto = isset($args['titulo']) ? $args['titulo'] : __('Presupuesto Municipal', FLAVOR_PLATFORM_TEXT_DOMAIN);
$año_presupuesto = isset($args['año']) ? $args['año'] : date('Y');
$moneda_simbolo = isset($args['moneda']) ? $args['moneda'] : '€';
$tipo_grafico = isset($args['mostrar_grafico']) ? $args['mostrar_grafico'] : 'ambos';

// Datos de demostración de partidas presupuestarias
$partidas_presupuestarias = isset($args['partidas']) ? $args['partidas'] : array(
    array(
        'nombre' => 'Servicios Sociales',
        'importe' => 2500000,
        'porcentaje' => 25,
        'color' => '#4CAF50',
        'icono' => 'dashicons-groups'
    ),
    array(
        'nombre' => 'Infraestructuras',
        'importe' => 2000000,
        'porcentaje' => 20,
        'color' => '#2196F3',
        'icono' => 'dashicons-building'
    ),
    array(
        'nombre' => 'Educación y Cultura',
        'importe' => 1800000,
        'porcentaje' => 18,
        'color' => '#FF9800',
        'icono' => 'dashicons-welcome-learn-more'
    ),
    array(
        'nombre' => 'Seguridad Ciudadana',
        'importe' => 1500000,
        'porcentaje' => 15,
        'color' => '#F44336',
        'icono' => 'dashicons-shield'
    ),
    array(
        'nombre' => 'Medio Ambiente',
        'importe' => 1200000,
        'porcentaje' => 12,
        'color' => '#8BC34A',
        'icono' => 'dashicons-palmtree'
    ),
    array(
        'nombre' => 'Administración General',
        'importe' => 1000000,
        'porcentaje' => 10,
        'color' => '#9C27B0',
        'icono' => 'dashicons-admin-generic'
    )
);

$total_presupuesto = isset($args['total']) ? $args['total'] : array_sum(array_column($partidas_presupuestarias, 'importe'));

/**
 * Formatea un número como moneda
 */
function flavor_formatear_moneda($cantidad, $simbolo) {
    return number_format($cantidad, 0, ',', '.') . ' ' . $simbolo;
}
?>

<div class="flavor-presupuesto-widget">
    <header class="flavor-presupuesto-header">
        <h2 class="flavor-presupuesto-titulo">
            <?php echo esc_html($titulo_presupuesto); ?>
            <span class="flavor-presupuesto-año"><?php echo esc_html($año_presupuesto); ?></span>
        </h2>
        <div class="flavor-presupuesto-total">
            <span class="flavor-presupuesto-total-label"><?php esc_html_e('Presupuesto Total:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <span class="flavor-presupuesto-total-cantidad"><?php echo esc_html(flavor_formatear_moneda($total_presupuesto, $moneda_simbolo)); ?></span>
        </div>
    </header>

    <div class="flavor-presupuesto-contenido">
        <?php if ($tipo_grafico === 'donut' || $tipo_grafico === 'ambos') : ?>
        <!-- Gráfico Donut -->
        <div class="flavor-presupuesto-grafico-donut">
            <div class="flavor-donut-container">
                <svg class="flavor-donut-svg" viewBox="0 0 42 42">
                    <circle class="flavor-donut-hole" cx="21" cy="21" r="15.91549430918954" fill="#fff"></circle>
                    <circle class="flavor-donut-ring" cx="21" cy="21" r="15.91549430918954" fill="transparent" stroke="#d2d3d4" stroke-width="3"></circle>
                    <?php
                    $offset_acumulado = 25;
                    foreach ($partidas_presupuestarias as $partida) :
                        $longitud_segmento = $partida['porcentaje'];
                    ?>
                    <circle
                        class="flavor-donut-segment"
                        cx="21"
                        cy="21"
                        r="15.91549430918954"
                        fill="transparent"
                        stroke="<?php echo esc_attr($partida['color']); ?>"
                        stroke-width="3"
                        stroke-dasharray="<?php echo esc_attr($longitud_segmento); ?> <?php echo esc_attr(100 - $longitud_segmento); ?>"
                        stroke-dashoffset="<?php echo esc_attr($offset_acumulado); ?>"
                        data-partida="<?php echo esc_attr($partida['nombre']); ?>"
                    ></circle>
                    <?php
                        $offset_acumulado -= $longitud_segmento;
                    endforeach;
                    ?>
                </svg>
                <div class="flavor-donut-centro">
                    <span class="flavor-donut-centro-texto"><?php echo esc_html($año_presupuesto); ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($tipo_grafico === 'barras' || $tipo_grafico === 'ambos') : ?>
        <!-- Gráfico de Barras -->
        <div class="flavor-presupuesto-grafico-barras">
            <?php foreach ($partidas_presupuestarias as $partida) : ?>
            <div class="flavor-barra-item">
                <div class="flavor-barra-info">
                    <span class="flavor-barra-icono <?php echo esc_attr($partida['icono']); ?>" style="color: <?php echo esc_attr($partida['color']); ?>"></span>
                    <span class="flavor-barra-nombre"><?php echo esc_html($partida['nombre']); ?></span>
                    <span class="flavor-barra-cantidad"><?php echo esc_html(flavor_formatear_moneda($partida['importe'], $moneda_simbolo)); ?></span>
                </div>
                <div class="flavor-barra-contenedor">
                    <div
                        class="flavor-barra-progreso"
                        style="width: <?php echo esc_attr($partida['porcentaje']); ?>%; background-color: <?php echo esc_attr($partida['color']); ?>;"
                        data-porcentaje="<?php echo esc_attr($partida['porcentaje']); ?>"
                    >
                        <span class="flavor-barra-porcentaje"><?php echo esc_html($partida['porcentaje']); ?>%</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Leyenda -->
    <div class="flavor-presupuesto-leyenda">
        <?php foreach ($partidas_presupuestarias as $partida) : ?>
        <div class="flavor-leyenda-item">
            <span class="flavor-leyenda-color" style="background-color: <?php echo esc_attr($partida['color']); ?>;"></span>
            <span class="flavor-leyenda-texto"><?php echo esc_html($partida['nombre']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <footer class="flavor-presupuesto-footer">
        <p class="flavor-presupuesto-nota">
            <?php esc_html_e('Datos actualizados a fecha:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <time datetime="<?php echo esc_attr(date('Y-m-d')); ?>"><?php echo esc_html(date_i18n(get_option('date_format'))); ?></time>
        </p>
        <a href="#" class="flavor-presupuesto-enlace-detalle">
            <?php esc_html_e('Ver presupuesto completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <span class="dashicons dashicons-arrow-right-alt2"></span>
        </a>
    </footer>
</div>

<style>
.flavor-presupuesto-widget {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    padding: 24px;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.flavor-presupuesto-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.flavor-presupuesto-titulo {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #1f2937;
}

.flavor-presupuesto-año {
    display: inline-block;
    margin-left: 8px;
    padding: 4px 12px;
    background: #e5e7eb;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4b5563;
}

.flavor-presupuesto-total {
    text-align: right;
}

.flavor-presupuesto-total-label {
    display: block;
    font-size: 0.875rem;
    color: #6b7280;
}

.flavor-presupuesto-total-cantidad {
    font-size: 1.5rem;
    font-weight: 700;
    color: #059669;
}

.flavor-presupuesto-contenido {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 32px;
    margin-bottom: 24px;
}

/* Gráfico Donut */
.flavor-presupuesto-grafico-donut {
    display: flex;
    justify-content: center;
    align-items: center;
}

.flavor-donut-container {
    position: relative;
    width: 200px;
    height: 200px;
}

.flavor-donut-svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.flavor-donut-segment {
    transition: stroke-width 0.3s ease;
    cursor: pointer;
}

.flavor-donut-segment:hover {
    stroke-width: 4;
}

.flavor-donut-centro {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.flavor-donut-centro-texto {
    font-size: 1.25rem;
    font-weight: 700;
    color: #374151;
}

/* Gráfico de Barras */
.flavor-presupuesto-grafico-barras {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-barra-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.flavor-barra-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-barra-icono {
    font-size: 1rem;
}

.flavor-barra-nombre {
    flex: 1;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.flavor-barra-cantidad {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
}

.flavor-barra-contenedor {
    height: 24px;
    background: #f3f4f6;
    border-radius: 6px;
    overflow: hidden;
}

.flavor-barra-progreso {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    border-radius: 6px;
    transition: width 0.6s ease;
}

.flavor-barra-porcentaje {
    font-size: 0.75rem;
    font-weight: 600;
    color: #ffffff;
}

/* Leyenda */
.flavor-presupuesto-leyenda {
    display: flex;
    flex-wrap: wrap;
    gap: 12px 24px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    margin-bottom: 16px;
}

.flavor-leyenda-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.flavor-leyenda-color {
    width: 12px;
    height: 12px;
    border-radius: 3px;
}

.flavor-leyenda-texto {
    font-size: 0.8125rem;
    color: #4b5563;
}

/* Footer */
.flavor-presupuesto-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    padding-top: 16px;
    border-top: 1px solid #e5e7eb;
}

.flavor-presupuesto-nota {
    margin: 0;
    font-size: 0.8125rem;
    color: #6b7280;
}

.flavor-presupuesto-enlace-detalle {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.875rem;
    font-weight: 500;
    color: #2563eb;
    text-decoration: none;
    transition: color 0.2s;
}

.flavor-presupuesto-enlace-detalle:hover {
    color: #1d4ed8;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-presupuesto-contenido {
        grid-template-columns: 1fr;
    }

    .flavor-presupuesto-header {
        flex-direction: column;
        text-align: center;
    }

    .flavor-presupuesto-total {
        text-align: center;
    }

    .flavor-donut-container {
        width: 160px;
        height: 160px;
    }

    .flavor-presupuesto-footer {
        flex-direction: column;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .flavor-presupuesto-widget {
        padding: 16px;
    }

    .flavor-presupuesto-titulo {
        font-size: 1.25rem;
    }

    .flavor-presupuesto-total-cantidad {
        font-size: 1.25rem;
    }

    .flavor-barra-info {
        flex-wrap: wrap;
    }

    .flavor-barra-cantidad {
        width: 100%;
        text-align: right;
    }
}
</style>
