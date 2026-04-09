<?php
/**
 * Template: Mi Reputación en Banco de Tiempo
 *
 * @package FlavorChatIA
 * @subpackage BancoTiempo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $reputacion Datos de reputación del usuario
 * @var array $badges_info Información de badges obtenidos
 */

if (!defined('ABSPATH')) {
    exit;
}

$reputacion = is_array($reputacion ?? null) ? $reputacion : [];
$badges_info = is_array($badges_info ?? null) ? $badges_info : [];

$reputacion = array_merge([
    'nombre' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
    'avatar' => is_user_logged_in() ? get_avatar_url(get_current_user_id(), ['size' => 96]) : '',
    'nivel' => 1,
    'estado_verificacion' => 'pendiente',
    'puntos_confianza' => 0,
    'rating_promedio' => 0,
    'total_intercambios_completados' => 0,
    'total_horas_dadas' => 0,
    'total_horas_recibidas' => 0,
    'rating_puntualidad' => 0,
    'rating_calidad' => 0,
    'rating_comunicacion' => 0,
    'fecha_primer_intercambio' => null,
], $reputacion);

$nivel = intval($reputacion['nivel'] ?? 1);
$estado = $reputacion['estado_verificacion'] ?? 'pendiente';
$rating = floatval($reputacion['rating_promedio'] ?? 0);
?>

<div class="bt-reputacion">
    <div class="bt-reputacion__header">
        <div class="bt-reputacion__avatar">
            <img src="<?php echo esc_url($reputacion['avatar']); ?>" alt="<?php echo esc_attr($reputacion['nombre']); ?>">
            <?php if ($estado === 'verificado' || $estado === 'destacado' || $estado === 'mentor'): ?>
                <span class="bt-reputacion__verificado" title="<?php esc_attr_e('Usuario verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-yes-alt"></span>
                </span>
            <?php endif; ?>
        </div>
        <div class="bt-reputacion__info">
            <h3 class="bt-reputacion__nombre"><?php echo esc_html($reputacion['nombre']); ?></h3>
            <div class="bt-reputacion__nivel">
                <span class="bt-reputacion__nivel-badge">
                    <?php printf(esc_html__('Nivel %d', FLAVOR_PLATFORM_TEXT_DOMAIN), $nivel); ?>
                </span>
                <span class="bt-reputacion__puntos">
                    <?php printf(esc_html__('%d pts confianza', FLAVOR_PLATFORM_TEXT_DOMAIN), intval($reputacion['puntos_confianza'])); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Rating general -->
    <div class="bt-reputacion__rating">
        <div class="bt-reputacion__estrellas">
            <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="dashicons dashicons-star-<?php echo $i <= round($rating) ? 'filled' : 'empty'; ?>"></span>
            <?php endfor; ?>
            <span class="bt-reputacion__rating-valor"><?php echo esc_html(number_format($rating, 1)); ?></span>
        </div>
        <span class="bt-reputacion__total-intercambios">
            <?php printf(
                esc_html(_n('%d intercambio', '%d intercambios', intval($reputacion['total_intercambios_completados']), FLAVOR_PLATFORM_TEXT_DOMAIN)),
                intval($reputacion['total_intercambios_completados'])
            ); ?>
        </span>
    </div>

    <!-- Estadísticas -->
    <div class="bt-reputacion__stats">
        <div class="bt-reputacion__stat">
            <span class="bt-reputacion__stat-valor"><?php echo esc_html(number_format($reputacion['total_horas_dadas'], 1)); ?>h</span>
            <span class="bt-reputacion__stat-label"><?php esc_html_e('Horas dadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="bt-reputacion__stat">
            <span class="bt-reputacion__stat-valor"><?php echo esc_html(number_format($reputacion['total_horas_recibidas'], 1)); ?>h</span>
            <span class="bt-reputacion__stat-label"><?php esc_html_e('Horas recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="bt-reputacion__stat">
            <?php
            $saldo = floatval($reputacion['total_horas_dadas']) - floatval($reputacion['total_horas_recibidas']);
            $clase_saldo = $saldo >= 0 ? 'positivo' : 'negativo';
            ?>
            <span class="bt-reputacion__stat-valor bt-reputacion__stat-valor--<?php echo esc_attr($clase_saldo); ?>">
                <?php echo ($saldo >= 0 ? '+' : '') . esc_html(number_format($saldo, 1)); ?>h
            </span>
            <span class="bt-reputacion__stat-label"><?php esc_html_e('Balance', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Ratings detallados -->
    <?php if ($reputacion['total_intercambios_completados'] > 0): ?>
        <div class="bt-reputacion__ratings-detalle">
            <h4><?php esc_html_e('Valoraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="bt-reputacion__rating-item">
                <span class="bt-reputacion__rating-label"><?php esc_html_e('Puntualidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <div class="bt-reputacion__rating-bar">
                    <div class="bt-reputacion__rating-fill" style="width: <?php echo esc_attr(($reputacion['rating_puntualidad'] / 5) * 100); ?>%"></div>
                </div>
                <span class="bt-reputacion__rating-num"><?php echo esc_html(number_format($reputacion['rating_puntualidad'], 1)); ?></span>
            </div>
            <div class="bt-reputacion__rating-item">
                <span class="bt-reputacion__rating-label"><?php esc_html_e('Calidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <div class="bt-reputacion__rating-bar">
                    <div class="bt-reputacion__rating-fill" style="width: <?php echo esc_attr(($reputacion['rating_calidad'] / 5) * 100); ?>%"></div>
                </div>
                <span class="bt-reputacion__rating-num"><?php echo esc_html(number_format($reputacion['rating_calidad'], 1)); ?></span>
            </div>
            <div class="bt-reputacion__rating-item">
                <span class="bt-reputacion__rating-label"><?php esc_html_e('Comunicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <div class="bt-reputacion__rating-bar">
                    <div class="bt-reputacion__rating-fill" style="width: <?php echo esc_attr(($reputacion['rating_comunicacion'] / 5) * 100); ?>%"></div>
                </div>
                <span class="bt-reputacion__rating-num"><?php echo esc_html(number_format($reputacion['rating_comunicacion'], 1)); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <!-- Badges -->
    <?php if (!empty($badges_info)): ?>
        <div class="bt-reputacion__badges">
            <h4><?php esc_html_e('Insignias obtenidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="bt-reputacion__badges-grid">
                <?php foreach ($badges_info as $badge): ?>
                    <div class="bt-reputacion__badge" title="<?php echo esc_attr($badge['descripcion']); ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr($badge['icono']); ?>"></span>
                        <span class="bt-reputacion__badge-nombre"><?php echo esc_html($badge['nombre']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bt-reputacion__badges bt-reputacion__badges--vacio">
            <p><?php esc_html_e('Completa tu primer intercambio para obtener tu primera insignia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php endif; ?>

    <!-- Miembro desde -->
    <?php if (!empty($reputacion['fecha_primer_intercambio'])): ?>
        <div class="bt-reputacion__footer">
            <span class="dashicons dashicons-calendar-alt"></span>
            <?php printf(
                esc_html__('Miembro desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                esc_html(date_i18n(get_option('date_format'), strtotime($reputacion['fecha_primer_intercambio'])))
            ); ?>
        </div>
    <?php endif; ?>
</div>

<style>
.bt-reputacion {
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
    max-width: 400px;
}

.bt-reputacion__header {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.5rem;
}

.bt-reputacion__avatar {
    position: relative;
    width: 64px;
    height: 64px;
    flex-shrink: 0;
}

.bt-reputacion__avatar img {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
}

.bt-reputacion__verificado {
    position: absolute;
    bottom: -2px;
    right: -2px;
    background: var(--bt-success);
    color: #fff;
    border-radius: 50%;
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bt-reputacion__verificado .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.bt-reputacion__nombre {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
}

.bt-reputacion__nivel {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.bt-reputacion__nivel-badge {
    background: linear-gradient(135deg, var(--bt-primary), #1565c0);
    color: #fff;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
}

.bt-reputacion__puntos {
    color: var(--bt-text-light);
    font-size: 0.8rem;
}

.bt-reputacion__rating {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.bt-reputacion__estrellas {
    display: flex;
    align-items: center;
    gap: 0.1rem;
}

.bt-reputacion__estrellas .dashicons-star-filled {
    color: #ffc107;
}

.bt-reputacion__estrellas .dashicons-star-empty {
    color: #ddd;
}

.bt-reputacion__rating-valor {
    margin-left: 0.5rem;
    font-weight: 600;
    font-size: 1.1rem;
}

.bt-reputacion__total-intercambios {
    color: var(--bt-text-light);
    font-size: 0.85rem;
}

.bt-reputacion__stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.bt-reputacion__stat {
    text-align: center;
    padding: 0.75rem;
    background: #fafafa;
    border-radius: 8px;
}

.bt-reputacion__stat-valor {
    display: block;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--bt-text);
}

.bt-reputacion__stat-valor--positivo {
    color: var(--bt-success);
}

.bt-reputacion__stat-valor--negativo {
    color: var(--bt-danger);
}

.bt-reputacion__stat-label {
    font-size: 0.75rem;
    color: var(--bt-text-light);
}

.bt-reputacion__ratings-detalle h4,
.bt-reputacion__badges h4 {
    font-size: 0.9rem;
    color: var(--bt-text-light);
    margin: 0 0 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.bt-reputacion__rating-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.bt-reputacion__rating-label {
    width: 100px;
    font-size: 0.85rem;
    color: var(--bt-text-light);
}

.bt-reputacion__rating-bar {
    flex: 1;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.bt-reputacion__rating-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--bt-primary), #42a5f5);
    border-radius: 4px;
    transition: width 0.5s ease;
}

.bt-reputacion__rating-num {
    width: 30px;
    text-align: right;
    font-size: 0.85rem;
    font-weight: 500;
}

.bt-reputacion__badges {
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--bt-border);
}

.bt-reputacion__badges-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.bt-reputacion__badge {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.4rem 0.75rem;
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    border: 1px solid #ffc107;
    border-radius: 20px;
    font-size: 0.8rem;
    cursor: help;
}

.bt-reputacion__badge .dashicons {
    color: #f57c00;
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.bt-reputacion__badges--vacio {
    text-align: center;
    color: var(--bt-text-light);
    font-style: italic;
}

.bt-reputacion__footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--bt-border);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--bt-text-light);
    font-size: 0.85rem;
}

.bt-reputacion__footer .dashicons {
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}
</style>
