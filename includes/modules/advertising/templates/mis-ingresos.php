<?php
/**
 * Template: Mis Ingresos por Publicidad
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();

// Obtener datos de ingresos
global $wpdb;
$tabla_ingresos = $wpdb->prefix . 'flavor_ads_ingresos';

// Verificar si existe la tabla
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_ingresos'") === $tabla_ingresos;

$ingresos_mes = 0;
$ingresos_total = 0;
$pendiente_pago = 0;
$historial = [];

if ($tabla_existe) {
    // Ingresos del mes
    $primer_dia_mes = date('Y-m-01');
    $ingresos_mes = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(cantidad), 0) FROM $tabla_ingresos
         WHERE usuario_id = %d AND fecha >= %s AND estado = 'completado'",
        $usuario_id,
        $primer_dia_mes
    ));

    // Ingresos totales
    $ingresos_total = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(cantidad), 0) FROM $tabla_ingresos
         WHERE usuario_id = %d AND estado = 'completado'",
        $usuario_id
    ));

    // Pendiente de pago
    $pendiente_pago = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(cantidad), 0) FROM $tabla_ingresos
         WHERE usuario_id = %d AND estado = 'pendiente'",
        $usuario_id
    ));

    // Historial de transacciones
    $historial = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_ingresos
         WHERE usuario_id = %d
         ORDER BY fecha DESC
         LIMIT 20",
        $usuario_id
    ));
}

// Pool de la comunidad
$pool_comunidad = get_option('flavor_ads_pool_comunidad', 0);
$configuracion = get_option('flavor_advertising_settings', []);
$minimo_pago = $configuracion['minimo_pago'] ?? 10;
?>

<div class="flavor-ads-ingresos-page">
    <!-- Header -->
    <div class="ads-ingresos-header">
        <h2><?php esc_html_e('Mis Ingresos por Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="ads-ingresos-intro">
            <?php esc_html_e('Como miembro activo de la comunidad, recibes una parte de los ingresos publicitarios generados en la plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="ads-ingresos-cards">
        <div class="ads-ingreso-card">
            <div class="ingreso-icon" style="background: rgba(16, 185, 129, 0.1);">
                <span class="dashicons dashicons-calendar-alt" style="color: #10b981;"></span>
            </div>
            <div class="ingreso-content">
                <span class="ingreso-valor"><?php echo esc_html(number_format((float)$ingresos_mes, 2)); ?>€</span>
                <span class="ingreso-label"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="ads-ingreso-card">
            <div class="ingreso-icon" style="background: rgba(99, 102, 241, 0.1);">
                <span class="dashicons dashicons-chart-bar" style="color: #6366f1;"></span>
            </div>
            <div class="ingreso-content">
                <span class="ingreso-valor"><?php echo esc_html(number_format((float)$ingresos_total, 2)); ?>€</span>
                <span class="ingreso-label"><?php esc_html_e('Total ganado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="ads-ingreso-card <?php echo (float)$pendiente_pago >= $minimo_pago ? 'highlight' : ''; ?>">
            <div class="ingreso-icon" style="background: rgba(245, 158, 11, 0.1);">
                <span class="dashicons dashicons-money-alt" style="color: #f59e0b;"></span>
            </div>
            <div class="ingreso-content">
                <span class="ingreso-valor"><?php echo esc_html(number_format((float)$pendiente_pago, 2)); ?>€</span>
                <span class="ingreso-label"><?php esc_html_e('Pendiente de pago', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
            <?php if ((float)$pendiente_pago >= $minimo_pago): ?>
            <span class="ingreso-badge"><?php esc_html_e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Información del sistema -->
    <div class="ads-info-section">
        <div class="ads-info-box">
            <h4><?php esc_html_e('¿Cómo funciona?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Los anunciantes pagan por mostrar sus anuncios en la plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Un porcentaje de esos ingresos se distribuye entre los miembros activos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </li>
                <li>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php printf(
                        esc_html__('Los pagos se procesan cuando alcanzas el mínimo de %s€.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        number_format($minimo_pago, 2)
                    ); ?>
                </li>
            </ul>
        </div>

        <div class="ads-pool-box">
            <h4><?php esc_html_e('Pool Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="pool-valor"><?php echo esc_html(number_format((float)$pool_comunidad, 2)); ?>€</div>
            <p><?php esc_html_e('Acumulado para distribuir entre la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    </div>

    <!-- Historial de transacciones -->
    <?php if (!empty($historial)): ?>
    <div class="ads-historial-section">
        <h3><?php esc_html_e('Historial de ingresos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

        <div class="ads-historial-table-wrapper">
            <table class="ads-historial-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Concepto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Cantidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historial as $transaccion): ?>
                    <tr>
                        <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($transaccion->fecha))); ?></td>
                        <td><?php echo esc_html($transaccion->concepto ?? __('Reparto publicitario', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></td>
                        <td class="cantidad"><?php echo esc_html(number_format((float)$transaccion->cantidad, 2)); ?>€</td>
                        <td>
                            <span class="estado-badge estado-<?php echo esc_attr($transaccion->estado); ?>">
                                <?php
                                switch ($transaccion->estado) {
                                    case 'completado':
                                        esc_html_e('Pagado', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                        break;
                                    case 'pendiente':
                                        esc_html_e('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN);
                                        break;
                                    default:
                                        echo esc_html(ucfirst($transaccion->estado));
                                }
                                ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="ads-empty-historial">
        <span class="dashicons dashicons-chart-line"></span>
        <p><?php esc_html_e('Aún no tienes ingresos registrados. Participa activamente en la comunidad para empezar a recibir recompensas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.flavor-ads-ingresos-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.ads-ingresos-header {
    margin-bottom: 2rem;
}

.ads-ingresos-header h2 {
    margin: 0 0 0.5rem 0;
}

.ads-ingresos-intro {
    color: var(--ads-gray-500);
    margin: 0;
}

.ads-ingresos-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.ads-ingreso-card {
    background: #fff;
    border-radius: var(--ads-radius);
    padding: 1.5rem;
    box-shadow: var(--ads-shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
    position: relative;
}

.ads-ingreso-card.highlight {
    border: 2px solid var(--ads-success);
}

.ingreso-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.ingreso-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.ingreso-content {
    flex: 1;
}

.ingreso-valor {
    display: block;
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--ads-gray-800);
}

.ingreso-label {
    color: var(--ads-gray-500);
    font-size: 0.875rem;
}

.ingreso-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: var(--ads-success);
    color: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
}

.ads-info-section {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.ads-info-box {
    background: #fff;
    border-radius: var(--ads-radius);
    padding: 1.5rem;
    box-shadow: var(--ads-shadow);
}

.ads-info-box h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
}

.ads-info-box ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.ads-info-box li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.5rem 0;
    color: var(--ads-gray-600);
}

.ads-info-box li .dashicons {
    color: var(--ads-success);
    flex-shrink: 0;
}

.ads-pool-box {
    background: linear-gradient(135deg, var(--ads-primary), #8b5cf6);
    border-radius: var(--ads-radius);
    padding: 1.5rem;
    color: #fff;
    text-align: center;
}

.ads-pool-box h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.875rem;
    opacity: 0.9;
}

.pool-valor {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.ads-pool-box p {
    margin: 0;
    font-size: 0.8rem;
    opacity: 0.8;
}

.ads-historial-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
}

.ads-historial-table-wrapper {
    background: #fff;
    border-radius: var(--ads-radius);
    overflow: hidden;
    box-shadow: var(--ads-shadow);
}

.ads-historial-table {
    width: 100%;
    border-collapse: collapse;
}

.ads-historial-table th,
.ads-historial-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--ads-gray-100);
}

.ads-historial-table th {
    background: var(--ads-gray-50);
    font-weight: 600;
    font-size: 0.8rem;
    color: var(--ads-gray-500);
    text-transform: uppercase;
}

.ads-historial-table .cantidad {
    font-weight: 600;
    color: var(--ads-success);
}

.estado-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.estado-completado {
    background: rgba(16, 185, 129, 0.1);
    color: var(--ads-success);
}

.estado-pendiente {
    background: rgba(245, 158, 11, 0.1);
    color: var(--ads-warning);
}

.ads-empty-historial {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--ads-gray-50);
    border-radius: var(--ads-radius);
}

.ads-empty-historial .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--ads-gray-300);
    margin-bottom: 1rem;
}

.ads-empty-historial p {
    color: var(--ads-gray-500);
    max-width: 400px;
    margin: 0 auto;
}

@media (max-width: 1024px) {
    .ads-info-section {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .ads-ingresos-cards {
        grid-template-columns: 1fr;
    }

    .ads-historial-table-wrapper {
        overflow-x: auto;
    }
}
</style>
