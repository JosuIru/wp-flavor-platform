<?php
/**
 * Template: Huella Ecológica del Ciclo
 *
 * @package FlavorPlatform
 * @subpackage GruposConsumo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $huella Datos de la huella del ciclo
 * @var array $atts Atributos del shortcode
 * @var int $ciclo_id ID del ciclo
 */

if (!defined('ABSPATH')) {
    exit;
}

$estilo = $atts['estilo'] ?? 'completo';

// Calcular nivel de sostenibilidad
$puntuacion = intval($huella['puntuacion_sostenibilidad'] ?? 0);
$nivel = 'bajo';
$nivel_color = '#f44336';
$nivel_texto = __('Mejorable', 'flavor-platform');

if ($puntuacion >= 80) {
    $nivel = 'excelente';
    $nivel_color = '#2e7d32';
    $nivel_texto = __('Excelente', 'flavor-platform');
} elseif ($puntuacion >= 60) {
    $nivel = 'bueno';
    $nivel_color = '#43a047';
    $nivel_texto = __('Bueno', 'flavor-platform');
} elseif ($puntuacion >= 40) {
    $nivel = 'medio';
    $nivel_color = '#ff9800';
    $nivel_texto = __('Aceptable', 'flavor-platform');
}

$ciclo_titulo = get_the_title($ciclo_id);
?>

<?php if ($estilo === 'badge'): ?>
    <div class="gc-huella-badge gc-huella-badge--<?php echo esc_attr($nivel); ?>">
        <span class="gc-huella-badge__icon">
            <span class="dashicons dashicons-<?php echo $puntuacion >= 60 ? 'yes-alt' : 'warning'; ?>"></span>
        </span>
        <span class="gc-huella-badge__valor"><?php echo esc_html($puntuacion); ?></span>
        <span class="gc-huella-badge__label"><?php esc_html_e('Sello de Conciencia', 'flavor-platform'); ?></span>
    </div>

<?php elseif ($estilo === 'resumen'): ?>
    <div class="gc-huella-resumen">
        <div class="gc-huella-resumen__score" style="--score-color: <?php echo esc_attr($nivel_color); ?>">
            <svg viewBox="0 0 36 36" class="gc-huella-resumen__circulo">
                <path class="gc-huella-resumen__bg"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none" stroke="#eee" stroke-width="3"/>
                <path class="gc-huella-resumen__progress"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none" stroke="var(--score-color)" stroke-width="3"
                    stroke-dasharray="<?php echo esc_attr($puntuacion); ?>, 100"/>
            </svg>
            <span class="gc-huella-resumen__numero"><?php echo esc_html($puntuacion); ?></span>
        </div>
        <div class="gc-huella-resumen__texto">
            <strong><?php echo esc_html($nivel_texto); ?></strong>
            <small><?php printf(esc_html__('%s kg CO₂ evitados', 'flavor-platform'), number_format($huella['co2_evitado_kg'], 1)); ?></small>
        </div>
    </div>

<?php else: ?>
    <div class="gc-huella gc-huella--completo">
        <div class="gc-huella__header">
            <div class="gc-huella__titulo-wrapper">
                <span class="dashicons dashicons-palmtree"></span>
                <h3 class="gc-huella__titulo"><?php esc_html_e('Huella Ecológica', 'flavor-platform'); ?></h3>
            </div>
            <?php if ($ciclo_titulo): ?>
                <span class="gc-huella__ciclo"><?php echo esc_html($ciclo_titulo); ?></span>
            <?php endif; ?>
        </div>

        <div class="gc-huella__puntuacion">
            <div class="gc-huella__score" style="--score-color: <?php echo esc_attr($nivel_color); ?>; --score-percent: <?php echo esc_attr($puntuacion); ?>%">
                <svg viewBox="0 0 36 36" class="gc-huella__circulo">
                    <path class="gc-huella__circulo-bg"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none" stroke="#eee" stroke-width="2.5"/>
                    <path class="gc-huella__circulo-progress"
                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                        fill="none" stroke="var(--score-color)" stroke-width="2.5"
                        stroke-dasharray="<?php echo esc_attr($puntuacion); ?>, 100"
                        stroke-linecap="round"/>
                </svg>
                <div class="gc-huella__score-inner">
                    <span class="gc-huella__score-valor"><?php echo esc_html($puntuacion); ?></span>
                    <span class="gc-huella__score-max">/100</span>
                </div>
            </div>
            <div class="gc-huella__nivel" style="color: <?php echo esc_attr($nivel_color); ?>">
                <?php echo esc_html($nivel_texto); ?>
            </div>
        </div>

        <div class="gc-huella__metricas">
            <div class="gc-huella__metrica">
                <span class="gc-huella__metrica-icon" style="background: #e3f2fd;">
                    <span class="dashicons dashicons-location-alt"></span>
                </span>
                <div class="gc-huella__metrica-datos">
                    <span class="gc-huella__metrica-valor">
                        <?php echo esc_html(number_format($huella['km_evitados'], 0)); ?> km
                    </span>
                    <span class="gc-huella__metrica-label"><?php esc_html_e('Transporte evitado', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="gc-huella__metrica">
                <span class="gc-huella__metrica-icon" style="background: #e8f5e9;">
                    <span class="dashicons dashicons-cloud"></span>
                </span>
                <div class="gc-huella__metrica-datos">
                    <span class="gc-huella__metrica-valor">
                        <?php echo esc_html(number_format($huella['co2_evitado_kg'], 1)); ?> kg
                    </span>
                    <span class="gc-huella__metrica-label"><?php esc_html_e('CO₂ evitado', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="gc-huella__metrica">
                <span class="gc-huella__metrica-icon" style="background: #fff3e0;">
                    <span class="dashicons dashicons-image-filter"></span>
                </span>
                <div class="gc-huella__metrica-datos">
                    <span class="gc-huella__metrica-valor">
                        <?php echo esc_html(number_format($huella['plastico_evitado_kg'] * 1000, 0)); ?> g
                    </span>
                    <span class="gc-huella__metrica-label"><?php esc_html_e('Plástico evitado', 'flavor-platform'); ?></span>
                </div>
            </div>

            <div class="gc-huella__metrica">
                <span class="gc-huella__metrica-icon" style="background: #e1f5fe;">
                    <span class="dashicons dashicons-marker"></span>
                </span>
                <div class="gc-huella__metrica-datos">
                    <span class="gc-huella__metrica-valor">
                        <?php echo esc_html(number_format($huella['agua_ahorrada_litros'], 0)); ?> L
                    </span>
                    <span class="gc-huella__metrica-label"><?php esc_html_e('Agua ahorrada', 'flavor-platform'); ?></span>
                </div>
            </div>
        </div>

        <div class="gc-huella__detalles">
            <div class="gc-huella__detalle">
                <span class="dashicons dashicons-store"></span>
                <strong><?php echo esc_html($huella['productores_locales']); ?></strong>
                <?php esc_html_e('productores locales', 'flavor-platform'); ?>
            </div>
            <div class="gc-huella__detalle">
                <span class="dashicons dashicons-groups"></span>
                <strong><?php echo esc_html($huella['num_participantes']); ?></strong>
                <?php esc_html_e('participantes', 'flavor-platform'); ?>
            </div>
            <div class="gc-huella__detalle">
                <span class="dashicons dashicons-yes-alt"></span>
                <strong><?php echo esc_html(number_format($huella['productos_eco_porcentaje'], 0)); ?>%</strong>
                <?php esc_html_e('productos eco', 'flavor-platform'); ?>
            </div>
            <div class="gc-huella__detalle">
                <span class="dashicons dashicons-location"></span>
                <strong><?php echo esc_html(number_format($huella['km_medio_producto'], 0)); ?> km</strong>
                <?php esc_html_e('distancia media', 'flavor-platform'); ?>
            </div>
        </div>

        <div class="gc-huella__footer">
            <small>
                <?php
                printf(
                    esc_html__('Calculado: %s • Total procesado: %s kg', 'flavor-platform'),
                    esc_html(date_i18n(get_option('date_format'), strtotime($huella['fecha_calculo']))),
                    esc_html(number_format($huella['total_kg_productos'], 1))
                );
                ?>
            </small>
        </div>
    </div>
<?php endif; ?>

<style>
/* Badge compacto */
.gc-huella-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 500;
}

.gc-huella-badge--excelente { background: #e8f5e9; color: #2e7d32; }
.gc-huella-badge--bueno { background: #e8f5e9; color: #43a047; }
.gc-huella-badge--medio { background: #fff3e0; color: #e65100; }
.gc-huella-badge--bajo { background: #ffebee; color: #c62828; }

.gc-huella-badge__valor {
    font-weight: 700;
    font-size: 1.1rem;
}

/* Resumen */
.gc-huella-resumen {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 12px;
}

.gc-huella-resumen__score {
    width: 60px;
    height: 60px;
    position: relative;
}

.gc-huella-resumen__circulo {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.gc-huella-resumen__numero {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--score-color);
}

.gc-huella-resumen__texto {
    display: flex;
    flex-direction: column;
}

.gc-huella-resumen__texto strong {
    font-size: 1rem;
}

.gc-huella-resumen__texto small {
    color: #666;
}

/* Completo */
.gc-huella--completo {
    background: linear-gradient(135deg, #f8fdf8, #e8f5e9);
    border: 1px solid #c8e6c9;
    border-radius: 16px;
    padding: 1.5rem;
    margin: 2rem 0;
}

.gc-huella__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.gc-huella__titulo-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.gc-huella__titulo-wrapper .dashicons {
    color: #2e7d32;
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.gc-huella__titulo {
    margin: 0;
    font-size: 1.25rem;
    color: #2e7d32;
}

.gc-huella__ciclo {
    background: #fff;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.85rem;
    color: #666;
}

.gc-huella__puntuacion {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 2rem;
}

.gc-huella__score {
    width: 140px;
    height: 140px;
    position: relative;
}

.gc-huella__circulo {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.gc-huella__circulo-progress {
    transition: stroke-dasharray 1s ease;
}

.gc-huella__score-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.gc-huella__score-valor {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--score-color);
    line-height: 1;
}

.gc-huella__score-max {
    display: block;
    font-size: 0.9rem;
    color: #999;
}

.gc-huella__nivel {
    margin-top: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.gc-huella__metricas {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.gc-huella__metrica {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #fff;
    padding: 1rem;
    border-radius: 10px;
}

.gc-huella__metrica-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.gc-huella__metrica-icon .dashicons {
    font-size: 1.25rem;
    width: 1.25rem;
    height: 1.25rem;
    color: #555;
}

.gc-huella__metrica-datos {
    display: flex;
    flex-direction: column;
}

.gc-huella__metrica-valor {
    font-size: 1.25rem;
    font-weight: 600;
    color: #333;
}

.gc-huella__metrica-label {
    font-size: 0.8rem;
    color: #666;
}

.gc-huella__detalles {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1.5rem;
    padding: 1rem;
    background: rgba(255,255,255,0.5);
    border-radius: 10px;
    margin-bottom: 1rem;
}

.gc-huella__detalle {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
    color: #555;
}

.gc-huella__detalle .dashicons {
    color: #2e7d32;
}

.gc-huella__footer {
    text-align: center;
    color: #999;
}

@media (max-width: 600px) {
    .gc-huella__metricas {
        grid-template-columns: 1fr;
    }

    .gc-huella__detalles {
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
}
</style>
