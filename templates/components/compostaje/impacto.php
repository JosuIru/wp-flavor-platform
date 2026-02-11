<?php
/**
 * Template: Impacto ambiental global del compostaje comunitario
 *
 * @package FlavorChatIA
 * @since 1.0.0
 *
 * Variables esperadas en $args:
 * - estadisticas_globales: array con datos globales de impacto
 * - historico_mensual: array con datos históricos por mes
 * - objetivos: array con metas y objetivos
 * - equivalencias: array con equivalencias visuales
 */

if (!defined('ABSPATH')) exit;

// Estadísticas globales de demostración
$estadisticasGlobales = $args['estadisticas_globales'] ?? [
    'toneladas_procesadas' => 12.45,
    'co2_evitado_kg' => 3735,
    'compost_generado_kg' => 4980,
    'participantes_activos' => 156,
    'aportaciones_totales' => 2847,
    'composteras_activas' => 5,
    'arboles_equivalentes' => 170,
    'km_coche_evitados' => 14940,
    'hogares_compost' => 124,
];

// Datos históricos por mes (últimos 6 meses)
$historicoMensual = $args['historico_mensual'] ?? [
    ['mes' => 'Jul', 'kg' => 1850, 'co2' => 555],
    ['mes' => 'Ago', 'kg' => 1720, 'co2' => 516],
    ['mes' => 'Sep', 'kg' => 2100, 'co2' => 630],
    ['mes' => 'Oct', 'kg' => 2340, 'co2' => 702],
    ['mes' => 'Nov', 'kg' => 2150, 'co2' => 645],
    ['mes' => 'Dic', 'kg' => 2290, 'co2' => 687],
];

// Objetivos anuales
$objetivos = $args['objetivos'] ?? [
    'toneladas_objetivo' => 15,
    'participantes_objetivo' => 200,
    'co2_objetivo' => 4500,
];

// Calcular progreso hacia objetivos
$progresoToneladas = min(100, ($estadisticasGlobales['toneladas_procesadas'] / $objetivos['toneladas_objetivo']) * 100);
$progresoParticipantes = min(100, ($estadisticasGlobales['participantes_activos'] / $objetivos['participantes_objetivo']) * 100);
$progresoCO2 = min(100, ($estadisticasGlobales['co2_evitado_kg'] / $objetivos['co2_objetivo']) * 100);

// Encontrar el valor máximo para escalar el gráfico
$maxKgMensual = max(array_column($historicoMensual, 'kg'));

// Composición típica de residuos
$composicionResiduos = [
    ['tipo' => 'Frutas y verduras', 'porcentaje' => 45, 'color' => '#4caf50'],
    ['tipo' => 'Restos de jardín', 'porcentaje' => 25, 'color' => '#8bc34a'],
    ['tipo' => 'Posos de café/té', 'porcentaje' => 15, 'color' => '#795548'],
    ['tipo' => 'Cáscaras de huevo', 'porcentaje' => 8, 'color' => '#ffeb3b'],
    ['tipo' => 'Otros orgánicos', 'porcentaje' => 7, 'color' => '#9e9e9e'],
];
?>

<div class="flavor-impacto-ambiental">
    <!-- Cabecera con mensaje principal -->
    <div class="flavor-impacto-header">
        <div class="flavor-impacto-hero">
            <span class="flavor-hero-icono">🌍</span>
            <div class="flavor-hero-contenido">
                <h2 class="flavor-impacto-titulo">Impacto Ambiental Comunitario</h2>
                <p class="flavor-impacto-subtitulo">
                    Juntos estamos transformando residuos en recursos y cuidando nuestro planeta
                </p>
            </div>
        </div>
    </div>

    <!-- Métricas principales destacadas -->
    <div class="flavor-metricas-destacadas">
        <div class="flavor-metrica-principal flavor-metrica-toneladas">
            <div class="flavor-metrica-icono-grande">🌱</div>
            <div class="flavor-metrica-datos">
                <span class="flavor-metrica-numero"><?php echo esc_html(number_format($estadisticasGlobales['toneladas_procesadas'], 2)); ?></span>
                <span class="flavor-metrica-unidad">toneladas</span>
            </div>
            <span class="flavor-metrica-descripcion">de residuos orgánicos procesados</span>
        </div>

        <div class="flavor-metrica-principal flavor-metrica-co2">
            <div class="flavor-metrica-icono-grande">💨</div>
            <div class="flavor-metrica-datos">
                <span class="flavor-metrica-numero"><?php echo esc_html(number_format($estadisticasGlobales['co2_evitado_kg'] / 1000, 2)); ?></span>
                <span class="flavor-metrica-unidad">toneladas CO₂</span>
            </div>
            <span class="flavor-metrica-descripcion">de emisiones evitadas</span>
        </div>

        <div class="flavor-metrica-principal flavor-metrica-compost">
            <div class="flavor-metrica-icono-grande">🌿</div>
            <div class="flavor-metrica-datos">
                <span class="flavor-metrica-numero"><?php echo esc_html(number_format($estadisticasGlobales['compost_generado_kg'] / 1000, 2)); ?></span>
                <span class="flavor-metrica-unidad">toneladas</span>
            </div>
            <span class="flavor-metrica-descripcion">de compost de calidad generado</span>
        </div>
    </div>

    <!-- Equivalencias visuales -->
    <div class="flavor-equivalencias">
        <h3 class="flavor-seccion-titulo">
            <span>🔄</span> ¿Qué significa este impacto?
        </h3>
        <div class="flavor-equivalencias-grid">
            <div class="flavor-equivalencia-card">
                <span class="flavor-eq-icono">🌳</span>
                <span class="flavor-eq-numero"><?php echo esc_html(number_format($estadisticasGlobales['arboles_equivalentes'])); ?></span>
                <span class="flavor-eq-texto">árboles plantados equivalente en absorción de CO₂ anual</span>
            </div>
            <div class="flavor-equivalencia-card">
                <span class="flavor-eq-icono">🚗</span>
                <span class="flavor-eq-numero"><?php echo esc_html(number_format($estadisticasGlobales['km_coche_evitados'])); ?></span>
                <span class="flavor-eq-texto">km en coche evitados en emisiones</span>
            </div>
            <div class="flavor-equivalencia-card">
                <span class="flavor-eq-icono">🏠</span>
                <span class="flavor-eq-numero"><?php echo esc_html($estadisticasGlobales['hogares_compost']); ?></span>
                <span class="flavor-eq-texto">hogares abastecidos con compost natural</span>
            </div>
            <div class="flavor-equivalencia-card">
                <span class="flavor-eq-icono">⚡</span>
                <span class="flavor-eq-numero"><?php echo esc_html(number_format($estadisticasGlobales['co2_evitado_kg'] * 2.5)); ?></span>
                <span class="flavor-eq-texto">kWh de energía ahorrados en vertederos</span>
            </div>
        </div>
    </div>

    <!-- Estadísticas de participación -->
    <div class="flavor-participacion-stats">
        <div class="flavor-stats-row">
            <div class="flavor-stat-box">
                <span class="flavor-stat-icono">👥</span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-valor"><?php echo esc_html($estadisticasGlobales['participantes_activos']); ?></span>
                    <span class="flavor-stat-label">participantes activos</span>
                </div>
            </div>
            <div class="flavor-stat-box">
                <span class="flavor-stat-icono">📦</span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-valor"><?php echo esc_html(number_format($estadisticasGlobales['aportaciones_totales'])); ?></span>
                    <span class="flavor-stat-label">aportaciones realizadas</span>
                </div>
            </div>
            <div class="flavor-stat-box">
                <span class="flavor-stat-icono">🏛️</span>
                <div class="flavor-stat-content">
                    <span class="flavor-stat-valor"><?php echo esc_html($estadisticasGlobales['composteras_activas']); ?></span>
                    <span class="flavor-stat-label">composteras activas</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Progreso hacia objetivos -->
    <div class="flavor-objetivos-section">
        <h3 class="flavor-seccion-titulo">
            <span>🎯</span> Progreso hacia objetivos anuales
        </h3>
        <div class="flavor-objetivos-grid">
            <div class="flavor-objetivo-item">
                <div class="flavor-objetivo-header">
                    <span class="flavor-objetivo-nombre">Residuos procesados</span>
                    <span class="flavor-objetivo-progreso-texto">
                        <?php echo esc_html(number_format($estadisticasGlobales['toneladas_procesadas'], 1)); ?> / <?php echo esc_html($objetivos['toneladas_objetivo']); ?>t
                    </span>
                </div>
                <div class="flavor-objetivo-barra">
                    <div class="flavor-objetivo-relleno flavor-relleno-verde" style="width: <?php echo esc_attr($progresoToneladas); ?>%">
                        <span class="flavor-objetivo-porcentaje"><?php echo esc_html(round($progresoToneladas)); ?>%</span>
                    </div>
                </div>
            </div>
            <div class="flavor-objetivo-item">
                <div class="flavor-objetivo-header">
                    <span class="flavor-objetivo-nombre">Participantes</span>
                    <span class="flavor-objetivo-progreso-texto">
                        <?php echo esc_html($estadisticasGlobales['participantes_activos']); ?> / <?php echo esc_html($objetivos['participantes_objetivo']); ?>
                    </span>
                </div>
                <div class="flavor-objetivo-barra">
                    <div class="flavor-objetivo-relleno flavor-relleno-azul" style="width: <?php echo esc_attr($progresoParticipantes); ?>%">
                        <span class="flavor-objetivo-porcentaje"><?php echo esc_html(round($progresoParticipantes)); ?>%</span>
                    </div>
                </div>
            </div>
            <div class="flavor-objetivo-item">
                <div class="flavor-objetivo-header">
                    <span class="flavor-objetivo-nombre">CO₂ evitado</span>
                    <span class="flavor-objetivo-progreso-texto">
                        <?php echo esc_html(number_format($estadisticasGlobales['co2_evitado_kg'])); ?> / <?php echo esc_html(number_format($objetivos['co2_objetivo'])); ?> kg
                    </span>
                </div>
                <div class="flavor-objetivo-barra">
                    <div class="flavor-objetivo-relleno flavor-relleno-naranja" style="width: <?php echo esc_attr($progresoCO2); ?>%">
                        <span class="flavor-objetivo-porcentaje"><?php echo esc_html(round($progresoCO2)); ?>%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de evolución mensual -->
    <div class="flavor-evolucion-section">
        <h3 class="flavor-seccion-titulo">
            <span>📈</span> Evolución de los últimos 6 meses
        </h3>
        <div class="flavor-grafico-container">
            <div class="flavor-grafico-barras">
                <?php foreach ($historicoMensual as $indice => $mes) :
                    $alturaBarra = ($mes['kg'] / $maxKgMensual) * 100;
                ?>
                    <div class="flavor-grafico-columna">
                        <div class="flavor-barra-container">
                            <span class="flavor-barra-valor"><?php echo esc_html(number_format($mes['kg'])); ?> kg</span>
                            <div class="flavor-barra" style="height: <?php echo esc_attr($alturaBarra); ?>%">
                                <div class="flavor-barra-co2">
                                    <?php echo esc_html($mes['co2']); ?> kg CO₂
                                </div>
                            </div>
                        </div>
                        <span class="flavor-barra-mes"><?php echo esc_html($mes['mes']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="flavor-grafico-leyenda">
                <span class="flavor-leyenda-item-grafico">
                    <span class="flavor-leyenda-color-grafico flavor-color-verde"></span>
                    Residuos procesados (kg)
                </span>
                <span class="flavor-leyenda-item-grafico">
                    <span class="flavor-leyenda-color-grafico flavor-color-gris"></span>
                    CO₂ evitado (kg)
                </span>
            </div>
        </div>
    </div>

    <!-- Composición de residuos -->
    <div class="flavor-composicion-section">
        <h3 class="flavor-seccion-titulo">
            <span>🥬</span> Composición de residuos procesados
        </h3>
        <div class="flavor-composicion-container">
            <div class="flavor-composicion-donut">
                <div class="flavor-donut">
                    <?php
                    $rotacionAcumulada = 0;
                    foreach ($composicionResiduos as $residuo) :
                        $grados = ($residuo['porcentaje'] / 100) * 360;
                    ?>
                        <div class="flavor-donut-segment"
                             style="--rotation: <?php echo esc_attr($rotacionAcumulada); ?>deg;
                                    --percentage: <?php echo esc_attr($residuo['porcentaje']); ?>;
                                    --color: <?php echo esc_attr($residuo['color']); ?>;">
                        </div>
                    <?php
                        $rotacionAcumulada += $grados;
                    endforeach;
                    ?>
                    <div class="flavor-donut-center">
                        <span class="flavor-donut-total">100%</span>
                        <span class="flavor-donut-label">orgánicos</span>
                    </div>
                </div>
            </div>
            <div class="flavor-composicion-lista">
                <?php foreach ($composicionResiduos as $residuo) : ?>
                    <div class="flavor-composicion-item">
                        <span class="flavor-comp-color" style="background-color: <?php echo esc_attr($residuo['color']); ?>"></span>
                        <span class="flavor-comp-nombre"><?php echo esc_html($residuo['tipo']); ?></span>
                        <span class="flavor-comp-porcentaje"><?php echo esc_html($residuo['porcentaje']); ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Beneficios del compostaje -->
    <div class="flavor-beneficios-section">
        <h3 class="flavor-seccion-titulo">
            <span>✨</span> Beneficios del compostaje comunitario
        </h3>
        <div class="flavor-beneficios-grid">
            <div class="flavor-beneficio-card">
                <span class="flavor-beneficio-icono">🗑️</span>
                <h4>Reducción de residuos</h4>
                <p>Hasta el 40% de los residuos domésticos son orgánicos y pueden compostarse</p>
            </div>
            <div class="flavor-beneficio-card">
                <span class="flavor-beneficio-icono">🌡️</span>
                <h4>Menos gases de efecto invernadero</h4>
                <p>El compostaje evita las emisiones de metano que se producen en vertederos</p>
            </div>
            <div class="flavor-beneficio-card">
                <span class="flavor-beneficio-icono">🌾</span>
                <h4>Mejora del suelo</h4>
                <p>El compost enriquece la tierra con nutrientes naturales y mejora su estructura</p>
            </div>
            <div class="flavor-beneficio-card">
                <span class="flavor-beneficio-icono">💧</span>
                <h4>Conservación del agua</h4>
                <p>Los suelos con compost retienen mejor la humedad, reduciendo el riego necesario</p>
            </div>
            <div class="flavor-beneficio-card">
                <span class="flavor-beneficio-icono">🐛</span>
                <h4>Biodiversidad</h4>
                <p>El compost fomenta la vida microbiana y mejora los ecosistemas del suelo</p>
            </div>
            <div class="flavor-beneficio-card">
                <span class="flavor-beneficio-icono">💰</span>
                <h4>Ahorro económico</h4>
                <p>Menos costes de gestión de residuos y fertilizantes gratuitos para huertos</p>
            </div>
        </div>
    </div>

    <!-- Llamada a la acción -->
    <div class="flavor-cta-section">
        <div class="flavor-cta-contenido">
            <h3>¡Únete al cambio!</h3>
            <p>Cada aportación cuenta. Sé parte de la solución al problema de los residuos.</p>
            <a href="#" class="flavor-cta-boton">
                Comenzar a compostar →
            </a>
        </div>
        <div class="flavor-cta-icono">
            🌱
        </div>
    </div>
</div>

<style>
.flavor-impacto-ambiental {
    max-width: 1000px;
    margin: 0 auto;
    padding: 1.5rem;
    background: linear-gradient(180deg, #f1f8e9 0%, #fff 30%);
    border-radius: 16px;
}

/* Header */
.flavor-impacto-header {
    margin-bottom: 2rem;
}

.flavor-impacto-hero {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 2rem;
    background: linear-gradient(135deg, #2e7d32, #4caf50);
    border-radius: 16px;
    color: #fff;
}

.flavor-hero-icono {
    font-size: 4rem;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.flavor-impacto-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.75rem;
}

.flavor-impacto-subtitulo {
    margin: 0;
    opacity: 0.9;
    font-size: 1.05rem;
}

/* Métricas destacadas */
.flavor-metricas-destacadas {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.flavor-metrica-principal {
    text-align: center;
    padding: 2rem 1.5rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-metrica-principal:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.flavor-metrica-icono-grande {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.flavor-metrica-datos {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.flavor-metrica-numero {
    font-size: 2.5rem;
    font-weight: 700;
}

.flavor-metrica-toneladas .flavor-metrica-numero { color: #2e7d32; }
.flavor-metrica-co2 .flavor-metrica-numero { color: #0277bd; }
.flavor-metrica-compost .flavor-metrica-numero { color: #558b2f; }

.flavor-metrica-unidad {
    font-size: 1.1rem;
    color: #757575;
}

.flavor-metrica-descripcion {
    font-size: 0.95rem;
    color: #666;
}

/* Equivalencias */
.flavor-equivalencias {
    margin-bottom: 2rem;
}

.flavor-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    color: #333;
    margin: 0 0 1.25rem;
}

.flavor-equivalencias-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.flavor-equivalencia-card {
    text-align: center;
    padding: 1.5rem 1rem;
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border-radius: 12px;
    transition: transform 0.2s;
}

.flavor-equivalencia-card:hover {
    transform: scale(1.03);
}

.flavor-eq-icono {
    display: block;
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.flavor-eq-numero {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-eq-texto {
    display: block;
    font-size: 0.85rem;
    color: #555;
    margin-top: 0.25rem;
}

/* Estadísticas de participación */
.flavor-participacion-stats {
    margin-bottom: 2rem;
}

.flavor-stats-row {
    display: flex;
    gap: 1rem;
}

.flavor-stat-box {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem 1.5rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-stat-icono {
    font-size: 2rem;
}

.flavor-stat-content {
    display: flex;
    flex-direction: column;
}

.flavor-stat-valor {
    font-size: 1.5rem;
    font-weight: 700;
    color: #333;
}

.flavor-stat-label {
    font-size: 0.9rem;
    color: #757575;
}

/* Objetivos */
.flavor-objetivos-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-objetivos-grid {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.flavor-objetivo-item {
    width: 100%;
}

.flavor-objetivo-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.flavor-objetivo-nombre {
    font-weight: 600;
    color: #333;
}

.flavor-objetivo-progreso-texto {
    font-size: 0.9rem;
    color: #757575;
}

.flavor-objetivo-barra {
    height: 24px;
    background: #e0e0e0;
    border-radius: 12px;
    overflow: hidden;
}

.flavor-objetivo-relleno {
    height: 100%;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 0.75rem;
    transition: width 0.5s ease;
    min-width: 50px;
}

.flavor-relleno-verde { background: linear-gradient(90deg, #4caf50, #8bc34a); }
.flavor-relleno-azul { background: linear-gradient(90deg, #2196f3, #03a9f4); }
.flavor-relleno-naranja { background: linear-gradient(90deg, #ff9800, #ffc107); }

.flavor-objetivo-porcentaje {
    color: #fff;
    font-weight: 600;
    font-size: 0.85rem;
}

/* Gráfico de evolución */
.flavor-evolucion-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-grafico-container {
    padding-top: 1rem;
}

.flavor-grafico-barras {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    height: 200px;
    gap: 0.5rem;
    padding: 0 1rem;
}

.flavor-grafico-columna {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
}

.flavor-barra-container {
    flex: 1;
    width: 100%;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
}

.flavor-barra-valor {
    font-size: 0.75rem;
    font-weight: 600;
    color: #4caf50;
    margin-bottom: 0.25rem;
}

.flavor-barra {
    width: 70%;
    max-width: 60px;
    background: linear-gradient(180deg, #4caf50, #81c784);
    border-radius: 6px 6px 0 0;
    position: relative;
    transition: height 0.5s ease;
    display: flex;
    align-items: flex-end;
    justify-content: center;
}

.flavor-barra-co2 {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(0, 0, 0, 0.3);
    color: #fff;
    font-size: 0.65rem;
    padding: 0.25rem;
    text-align: center;
    border-radius: 0 0 0 0;
}

.flavor-barra-mes {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    font-weight: 500;
    color: #555;
}

.flavor-grafico-leyenda {
    display: flex;
    justify-content: center;
    gap: 2rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.flavor-leyenda-item-grafico {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #666;
}

.flavor-leyenda-color-grafico {
    width: 16px;
    height: 16px;
    border-radius: 4px;
}

.flavor-color-verde { background: linear-gradient(180deg, #4caf50, #81c784); }
.flavor-color-gris { background: rgba(0, 0, 0, 0.3); }

/* Composición de residuos */
.flavor-composicion-section {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.flavor-composicion-container {
    display: flex;
    gap: 3rem;
    align-items: center;
}

.flavor-composicion-donut {
    flex-shrink: 0;
}

.flavor-donut {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    position: relative;
    background: conic-gradient(
        #4caf50 0% 45%,
        #8bc34a 45% 70%,
        #795548 70% 85%,
        #ffeb3b 85% 93%,
        #9e9e9e 93% 100%
    );
}

.flavor-donut-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100px;
    height: 100px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.flavor-donut-total {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2e7d32;
}

.flavor-donut-label {
    font-size: 0.8rem;
    color: #757575;
}

.flavor-composicion-lista {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-composicion-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.flavor-comp-color {
    width: 16px;
    height: 16px;
    border-radius: 4px;
    flex-shrink: 0;
}

.flavor-comp-nombre {
    flex: 1;
    color: #333;
}

.flavor-comp-porcentaje {
    font-weight: 600;
    color: #555;
}

/* Beneficios */
.flavor-beneficios-section {
    margin-bottom: 2rem;
}

.flavor-beneficios-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.flavor-beneficio-card {
    padding: 1.5rem;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
    text-align: center;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-beneficio-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.flavor-beneficio-icono {
    display: block;
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.flavor-beneficio-card h4 {
    margin: 0 0 0.5rem;
    color: #333;
    font-size: 1rem;
}

.flavor-beneficio-card p {
    margin: 0;
    font-size: 0.9rem;
    color: #666;
    line-height: 1.4;
}

/* CTA */
.flavor-cta-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 2rem;
    background: linear-gradient(135deg, #2e7d32, #4caf50);
    border-radius: 16px;
    color: #fff;
}

.flavor-cta-contenido h3 {
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
}

.flavor-cta-contenido p {
    margin: 0 0 1rem;
    opacity: 0.9;
}

.flavor-cta-boton {
    display: inline-block;
    padding: 0.875rem 2rem;
    background: #fff;
    color: #2e7d32;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-cta-boton:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.flavor-cta-icono {
    font-size: 5rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Responsive */
@media (max-width: 900px) {
    .flavor-metricas-destacadas {
        grid-template-columns: 1fr;
    }

    .flavor-equivalencias-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-stats-row {
        flex-direction: column;
    }

    .flavor-beneficios-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .flavor-composicion-container {
        flex-direction: column;
    }
}

@media (max-width: 600px) {
    .flavor-impacto-ambiental {
        padding: 1rem;
    }

    .flavor-impacto-hero {
        flex-direction: column;
        text-align: center;
        padding: 1.5rem;
    }

    .flavor-hero-icono {
        font-size: 3rem;
    }

    .flavor-impacto-titulo {
        font-size: 1.35rem;
    }

    .flavor-equivalencias-grid {
        grid-template-columns: 1fr;
    }

    .flavor-beneficios-grid {
        grid-template-columns: 1fr;
    }

    .flavor-grafico-barras {
        height: 150px;
    }

    .flavor-barra-valor {
        font-size: 0.65rem;
    }

    .flavor-barra-co2 {
        display: none;
    }

    .flavor-cta-section {
        flex-direction: column;
        text-align: center;
    }

    .flavor-cta-icono {
        order: -1;
        font-size: 3rem;
        margin-bottom: 1rem;
    }
}
</style>
