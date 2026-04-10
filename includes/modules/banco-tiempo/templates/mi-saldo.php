<?php
/**
 * Banco de Tiempo - Mi Saldo (Frontend Dashboard)
 *
 * Template para mostrar el saldo del usuario en el dashboard
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
if (!$usuario_id) {
    echo '<div class="fl-login-required"><p>' . esc_html__('Debes iniciar sesión para ver tu saldo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

global $wpdb;
$tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';

// Calcular saldo: horas recibidas - horas dadas
$horas_recibidas = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
     WHERE usuario_solicitante_id = %d AND estado = 'completado'",
    $usuario_id
));

$horas_dadas = $wpdb->get_var($wpdb->prepare(
    "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
     WHERE usuario_receptor_id = %d AND estado = 'completado'",
    $usuario_id
));

$saldo_actual = floatval($horas_dadas) - floatval($horas_recibidas);

// Estadísticas adicionales
$servicios_activos = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_servicios WHERE usuario_id = %d AND estado = 'activo'",
    $usuario_id
));

$intercambios_pendientes = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_transacciones
     WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d) AND estado = 'pendiente'",
    $usuario_id, $usuario_id
));

$intercambios_completados = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $tabla_transacciones
     WHERE (usuario_solicitante_id = %d OR usuario_receptor_id = %d) AND estado = 'completado'",
    $usuario_id, $usuario_id
));

// Últimos movimientos
$ultimos_movimientos = $wpdb->get_results($wpdb->prepare(
    "SELECT t.*, s.titulo as servicio_titulo,
            CASE WHEN t.usuario_solicitante_id = %d THEN 'recibido' ELSE 'dado' END as tipo
     FROM $tabla_transacciones t
     LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
     WHERE (t.usuario_solicitante_id = %d OR t.usuario_receptor_id = %d)
           AND t.estado = 'completado'
     ORDER BY t.fecha_completado DESC
     LIMIT 5",
    $usuario_id, $usuario_id, $usuario_id
));
?>

<div class="fl-banco-tiempo-saldo">
    <!-- Tarjeta de Saldo Principal -->
    <div class="fl-saldo-card">
        <div class="fl-saldo-header">
            <span class="fl-saldo-icon">
                <span class="dashicons dashicons-clock"></span>
            </span>
            <h3><?php esc_html_e('Mi Saldo de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        </div>
        <div class="fl-saldo-value <?php echo $saldo_actual >= 0 ? 'fl-saldo-positive' : 'fl-saldo-negative'; ?>">
            <?php echo number_format(abs($saldo_actual), 1); ?>h
            <span class="fl-saldo-label">
                <?php echo $saldo_actual >= 0 ? esc_html__('a favor', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('por devolver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        </div>
        <div class="fl-saldo-breakdown">
            <div class="fl-saldo-item fl-saldo-given">
                <span class="dashicons dashicons-arrow-up-alt"></span>
                <span class="fl-saldo-item-value"><?php echo number_format($horas_dadas, 1); ?>h</span>
                <span class="fl-saldo-item-label"><?php esc_html_e('Horas dadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <div class="fl-saldo-item fl-saldo-received">
                <span class="dashicons dashicons-arrow-down-alt"></span>
                <span class="fl-saldo-item-value"><?php echo number_format($horas_recibidas, 1); ?>h</span>
                <span class="fl-saldo-item-label"><?php esc_html_e('Horas recibidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Estadísticas Rápidas -->
    <div class="fl-stats-grid">
        <div class="fl-stat-card">
            <div class="fl-stat-icon" style="background: #e0f2fe; color: #0284c7;">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="fl-stat-content">
                <span class="fl-stat-value"><?php echo intval($servicios_activos); ?></span>
                <span class="fl-stat-label"><?php esc_html_e('Servicios activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <div class="fl-stat-card">
            <div class="fl-stat-icon" style="background: #fef3c7; color: #d97706;">
                <span class="dashicons dashicons-hourglass"></span>
            </div>
            <div class="fl-stat-content">
                <span class="fl-stat-value"><?php echo intval($intercambios_pendientes); ?></span>
                <span class="fl-stat-label"><?php esc_html_e('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
        <div class="fl-stat-card">
            <div class="fl-stat-icon" style="background: #d1fae5; color: #059669;">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="fl-stat-content">
                <span class="fl-stat-value"><?php echo intval($intercambios_completados); ?></span>
                <span class="fl-stat-label"><?php esc_html_e('Completados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Últimos Movimientos -->
    <?php if (!empty($ultimos_movimientos)) : ?>
    <div class="fl-movements-section">
        <h4 class="fl-section-title"><?php esc_html_e('Últimos movimientos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <div class="fl-movements-list">
            <?php foreach ($ultimos_movimientos as $mov) : ?>
            <div class="fl-movement-item">
                <div class="fl-movement-icon <?php echo $mov->tipo === 'dado' ? 'fl-movement-out' : 'fl-movement-in'; ?>">
                    <span class="dashicons dashicons-<?php echo $mov->tipo === 'dado' ? 'arrow-up-alt' : 'arrow-down-alt'; ?>"></span>
                </div>
                <div class="fl-movement-content">
                    <span class="fl-movement-title"><?php echo esc_html($mov->servicio_titulo ?: __('Servicio', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>
                    <span class="fl-movement-date"><?php echo esc_html(date_i18n('j M Y', strtotime($mov->fecha_completado))); ?></span>
                </div>
                <div class="fl-movement-value <?php echo $mov->tipo === 'dado' ? 'fl-value-positive' : 'fl-value-negative'; ?>">
                    <?php echo $mov->tipo === 'dado' ? '+' : '-'; ?><?php echo number_format($mov->horas, 1); ?>h
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else : ?>
    <div class="fl-empty-state">
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php esc_html_e('Aún no tienes movimientos. ¡Empieza a intercambiar servicios!', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.fl-banco-tiempo-saldo {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.fl-saldo-card {
    background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
    border-radius: 16px;
    padding: 1.5rem;
    color: white;
}

.fl-saldo-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.fl-saldo-icon {
    width: 40px;
    height: 40px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.fl-saldo-icon .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.fl-saldo-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
    opacity: 0.9;
}

.fl-saldo-value {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.fl-saldo-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 400;
    opacity: 0.8;
    margin-top: 0.25rem;
}

.fl-saldo-breakdown {
    display: flex;
    gap: 1.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255,255,255,0.2);
}

.fl-saldo-item {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.25rem;
}

.fl-saldo-item .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
    opacity: 0.8;
}

.fl-saldo-item-value {
    font-size: 1.25rem;
    font-weight: 600;
}

.fl-saldo-item-label {
    font-size: 0.75rem;
    opacity: 0.7;
}

.fl-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.fl-stat-card {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.fl-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fl-stat-icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.fl-stat-content {
    display: flex;
    flex-direction: column;
}

.fl-stat-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.fl-stat-label {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.fl-movements-section {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.fl-section-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
    margin: 0 0 1rem;
}

.fl-movements-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.fl-movement-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f3f4f6;
}

.fl-movement-item:last-child {
    border-bottom: none;
}

.fl-movement-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.fl-movement-out {
    background: #d1fae5;
    color: #059669;
}

.fl-movement-in {
    background: #fee2e2;
    color: #dc2626;
}

.fl-movement-icon .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.fl-movement-content {
    flex: 1;
    min-width: 0;
}

.fl-movement-title {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.fl-movement-date {
    font-size: 0.75rem;
    color: #9ca3af;
}

.fl-movement-value {
    font-size: 0.875rem;
    font-weight: 600;
}

.fl-value-positive {
    color: #059669;
}

.fl-value-negative {
    color: #dc2626;
}

.fl-empty-state {
    text-align: center;
    padding: 2rem;
    background: #f9fafb;
    border-radius: 12px;
}

.fl-empty-state .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #9ca3af;
    margin-bottom: 0.5rem;
}

.fl-empty-state p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

@media (max-width: 640px) {
    .fl-stats-grid {
        grid-template-columns: 1fr;
    }

    .fl-saldo-value {
        font-size: 2.5rem;
    }

    .fl-saldo-breakdown {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>
