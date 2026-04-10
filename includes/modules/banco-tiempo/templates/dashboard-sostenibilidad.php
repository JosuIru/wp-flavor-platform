<?php
/**
 * Template: Dashboard de Sostenibilidad del Banco de Tiempo
 *
 * @package FlavorPlatform
 * @subpackage BancoTiempo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $metricas Métricas del período
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$puntuacion = intval($metricas['puntuacion_sostenibilidad'] ?? 0);
$nivel_color = '#f44336';
$nivel_texto = __('Necesita atención', FLAVOR_PLATFORM_TEXT_DOMAIN);

if ($puntuacion >= 80) {
    $nivel_color = '#2e7d32';
    $nivel_texto = __('Excelente', FLAVOR_PLATFORM_TEXT_DOMAIN);
} elseif ($puntuacion >= 60) {
    $nivel_color = '#43a047';
    $nivel_texto = __('Bueno', FLAVOR_PLATFORM_TEXT_DOMAIN);
} elseif ($puntuacion >= 40) {
    $nivel_color = '#ff9800';
    $nivel_texto = __('Aceptable', FLAVOR_PLATFORM_TEXT_DOMAIN);
}

$alertas = $metricas['alertas_generadas'] ?? [];
$ratio_categorias = $metricas['ratio_oferta_demanda'] ?? [];
?>

<div class="bt-sostenibilidad">
    <div class="bt-sostenibilidad__header">
        <h3 class="bt-sostenibilidad__titulo">
            <span class="dashicons dashicons-chart-area"></span>
            <?php esc_html_e('Sostenibilidad del Sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>
        <span class="bt-sostenibilidad__periodo">
            <?php printf(
                esc_html__('%s - %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                date_i18n('j M', strtotime($metricas['periodo_inicio'])),
                date_i18n('j M Y', strtotime($metricas['periodo_fin']))
            ); ?>
        </span>
    </div>

    <!-- Puntuación principal -->
    <div class="bt-sostenibilidad__score">
        <div class="bt-sostenibilidad__score-circulo" style="--score-color: <?php echo esc_attr($nivel_color); ?>">
            <svg viewBox="0 0 36 36">
                <path class="bt-sostenibilidad__score-bg"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none" stroke="#eee" stroke-width="2.5"/>
                <path class="bt-sostenibilidad__score-fill"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                    fill="none" stroke="var(--score-color)" stroke-width="2.5"
                    stroke-dasharray="<?php echo esc_attr($puntuacion); ?>, 100"
                    stroke-linecap="round"/>
            </svg>
            <div class="bt-sostenibilidad__score-inner">
                <span class="bt-sostenibilidad__score-valor"><?php echo esc_html($puntuacion); ?></span>
                <span class="bt-sostenibilidad__score-max">/100</span>
            </div>
        </div>
        <div class="bt-sostenibilidad__nivel" style="color: <?php echo esc_attr($nivel_color); ?>">
            <?php echo esc_html($nivel_texto); ?>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($alertas)): ?>
        <div class="bt-sostenibilidad__alertas">
            <h4><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Alertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <?php foreach ($alertas as $alerta): ?>
                    <li><?php echo esc_html($alerta); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Métricas principales -->
    <div class="bt-sostenibilidad__metricas">
        <div class="bt-sostenibilidad__metrica">
            <span class="bt-sostenibilidad__metrica-icono" style="background: #e3f2fd;">
                <span class="dashicons dashicons-groups"></span>
            </span>
            <div class="bt-sostenibilidad__metrica-info">
                <span class="bt-sostenibilidad__metrica-valor"><?php echo esc_html($metricas['total_usuarios_activos']); ?></span>
                <span class="bt-sostenibilidad__metrica-label"><?php esc_html_e('Usuarios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="bt-sostenibilidad__metrica">
            <span class="bt-sostenibilidad__metrica-icono" style="background: #e8f5e9;">
                <span class="dashicons dashicons-update"></span>
            </span>
            <div class="bt-sostenibilidad__metrica-info">
                <span class="bt-sostenibilidad__metrica-valor"><?php echo esc_html($metricas['total_intercambios']); ?></span>
                <span class="bt-sostenibilidad__metrica-label"><?php esc_html_e('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="bt-sostenibilidad__metrica">
            <span class="bt-sostenibilidad__metrica-icono" style="background: #fff3e0;">
                <span class="dashicons dashicons-clock"></span>
            </span>
            <div class="bt-sostenibilidad__metrica-info">
                <span class="bt-sostenibilidad__metrica-valor"><?php echo esc_html(number_format($metricas['total_horas_intercambiadas'], 1)); ?>h</span>
                <span class="bt-sostenibilidad__metrica-label"><?php esc_html_e('Horas intercambiadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="bt-sostenibilidad__metrica">
            <span class="bt-sostenibilidad__metrica-icono" style="background: #fce4ec;">
                <span class="dashicons dashicons-heart"></span>
            </span>
            <div class="bt-sostenibilidad__metrica-info">
                <span class="bt-sostenibilidad__metrica-valor"><?php echo esc_html(number_format($metricas['horas_donadas_periodo'], 1)); ?>h</span>
                <span class="bt-sostenibilidad__metrica-label"><?php esc_html_e('Horas donadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Índice de equidad -->
    <div class="bt-sostenibilidad__equidad">
        <h4><?php esc_html_e('Índice de Equidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="bt-sostenibilidad__equidad-barra">
            <div class="bt-sostenibilidad__equidad-fill" style="width: <?php echo esc_attr($metricas['indice_equidad'] * 100); ?>%"></div>
        </div>
        <div class="bt-sostenibilidad__equidad-info">
            <span><?php echo esc_html(number_format($metricas['indice_equidad'] * 100, 1)); ?>%</span>
            <small><?php esc_html_e('Distribución equitativa de horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
        </div>
    </div>

    <!-- Ratio por categorías -->
    <?php if (!empty($ratio_categorias)): ?>
        <div class="bt-sostenibilidad__categorias">
            <h4><?php esc_html_e('Oferta vs Demanda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="bt-sostenibilidad__categorias-lista">
                <?php foreach ($ratio_categorias as $cat): ?>
                    <?php
                    $ratio = $cat['servicios_ofrecidos'] > 0
                        ? $cat['intercambios_solicitados'] / $cat['servicios_ofrecidos']
                        : 0;
                    $estado_clase = $ratio > 2 ? 'alta' : ($ratio < 0.5 ? 'baja' : 'normal');
                    ?>
                    <div class="bt-sostenibilidad__categoria bt-sostenibilidad__categoria--<?php echo esc_attr($estado_clase); ?>">
                        <span class="bt-sostenibilidad__categoria-nombre"><?php echo esc_html(ucfirst($cat['categoria'])); ?></span>
                        <span class="bt-sostenibilidad__categoria-ratio">
                            <?php echo esc_html($cat['intercambios_solicitados']); ?>/<?php echo esc_html($cat['servicios_ofrecidos']); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Detalles adicionales -->
    <div class="bt-sostenibilidad__detalles">
        <div class="bt-sostenibilidad__detalle">
            <span class="dashicons dashicons-plus-alt2"></span>
            <strong><?php echo esc_html($metricas['nuevos_usuarios']); ?></strong>
            <?php esc_html_e('nuevos miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </div>
        <div class="bt-sostenibilidad__detalle">
            <span class="dashicons dashicons-bank"></span>
            <strong><?php echo esc_html(number_format($metricas['fondo_comunitario_actual'], 1)); ?>h</strong>
            <?php esc_html_e('en fondo solidario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </div>
        <?php if ($metricas['usuarios_con_deuda_alta'] > 0): ?>
            <div class="bt-sostenibilidad__detalle bt-sostenibilidad__detalle--warning">
                <span class="dashicons dashicons-warning"></span>
                <strong><?php echo esc_html($metricas['usuarios_con_deuda_alta']); ?></strong>
                <?php esc_html_e('con deuda alta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="bt-sostenibilidad__footer">
        <small>
            <?php printf(
                esc_html__('Calculado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($metricas['fecha_calculo']))
            ); ?>
        </small>
    </div>
</div>

<style>
.bt-sostenibilidad {
    --bt-primary: #1976d2;
    --bt-success: #2e7d32;
    --bt-warning: #f57c00;
    --bt-danger: #c62828;
    --bt-text: #333;
    --bt-text-light: #666;
    --bt-border: #e0e0e0;
    --bt-radius: 12px;
    background: #fff;
    border: 1px solid var(--bt-border);
    border-radius: var(--bt-radius);
    padding: 1.5rem;
}

.bt-sostenibilidad__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.bt-sostenibilidad__titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    font-size: 1.1rem;
}

.bt-sostenibilidad__titulo .dashicons {
    color: var(--bt-primary);
}

.bt-sostenibilidad__periodo {
    background: #f5f5f5;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    font-size: 0.8rem;
    color: var(--bt-text-light);
}

.bt-sostenibilidad__score {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 1.5rem;
}

.bt-sostenibilidad__score-circulo {
    width: 120px;
    height: 120px;
    position: relative;
}

.bt-sostenibilidad__score-circulo svg {
    width: 100%;
    height: 100%;
    transform: rotate(-90deg);
}

.bt-sostenibilidad__score-inner {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.bt-sostenibilidad__score-valor {
    font-size: 2rem;
    font-weight: 700;
    color: var(--score-color);
}

.bt-sostenibilidad__score-max {
    font-size: 0.85rem;
    color: #999;
}

.bt-sostenibilidad__nivel {
    margin-top: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
}

.bt-sostenibilidad__alertas {
    background: #fff3e0;
    border: 1px solid #ffcc80;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.bt-sostenibilidad__alertas h4 {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.5rem;
    color: var(--bt-warning);
    font-size: 0.9rem;
}

.bt-sostenibilidad__alertas ul {
    margin: 0;
    padding-left: 1.5rem;
    font-size: 0.85rem;
}

.bt-sostenibilidad__metricas {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.bt-sostenibilidad__metrica {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: #fafafa;
    border-radius: 8px;
}

.bt-sostenibilidad__metrica-icono {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.bt-sostenibilidad__metrica-icono .dashicons {
    color: #555;
}

.bt-sostenibilidad__metrica-valor {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
}

.bt-sostenibilidad__metrica-label {
    font-size: 0.75rem;
    color: var(--bt-text-light);
}

.bt-sostenibilidad__equidad {
    margin-bottom: 1.5rem;
}

.bt-sostenibilidad__equidad h4 {
    margin: 0 0 0.5rem;
    font-size: 0.85rem;
    color: var(--bt-text-light);
}

.bt-sostenibilidad__equidad-barra {
    height: 12px;
    background: #e0e0e0;
    border-radius: 6px;
    overflow: hidden;
}

.bt-sostenibilidad__equidad-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bt-warning), var(--bt-success));
    border-radius: 6px;
    transition: width 0.5s ease;
}

.bt-sostenibilidad__equidad-info {
    display: flex;
    justify-content: space-between;
    margin-top: 0.25rem;
    font-size: 0.85rem;
}

.bt-sostenibilidad__equidad-info small {
    color: var(--bt-text-light);
}

.bt-sostenibilidad__categorias h4 {
    margin: 0 0 0.75rem;
    font-size: 0.85rem;
    color: var(--bt-text-light);
}

.bt-sostenibilidad__categorias-lista {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.bt-sostenibilidad__categoria {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
}

.bt-sostenibilidad__categoria--normal {
    background: #e8f5e9;
    color: var(--bt-success);
}

.bt-sostenibilidad__categoria--alta {
    background: #ffebee;
    color: var(--bt-danger);
}

.bt-sostenibilidad__categoria--baja {
    background: #fff3e0;
    color: var(--bt-warning);
}

.bt-sostenibilidad__categoria-ratio {
    font-weight: 600;
}

.bt-sostenibilidad__detalles {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 8px;
    margin-top: 1.5rem;
}

.bt-sostenibilidad__detalle {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
}

.bt-sostenibilidad__detalle .dashicons {
    color: var(--bt-primary);
}

.bt-sostenibilidad__detalle--warning .dashicons {
    color: var(--bt-warning);
}

.bt-sostenibilidad__footer {
    text-align: center;
    margin-top: 1rem;
    color: #999;
}

@media (max-width: 500px) {
    .bt-sostenibilidad__metricas {
        grid-template-columns: 1fr;
    }
}
</style>
