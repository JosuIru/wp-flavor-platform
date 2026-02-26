<?php
/**
 * Template: Dashboard del Anunciante
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$usuario_id = get_current_user_id();
$modulo = Flavor_Chat_Module_Loader::get_instance()->get_module('advertising');

// Obtener datos
$resultado_anuncios = $modulo->execute_action('mis_anuncios', []);
$anuncios = $resultado_anuncios['data'] ?? [];

$resultado_stats = $modulo->execute_action('ver_estadisticas', ['periodo' => 'month']);
$estadisticas = $resultado_stats['data'] ?? [];

// Calcular totales
$total_impresiones = 0;
$total_clics = 0;
$total_gasto = 0;
$anuncios_activos = 0;
$anuncios_pendientes = 0;

foreach ($anuncios as $anuncio) {
    $total_impresiones += $anuncio['impresiones'];
    $total_clics += $anuncio['clics'];
    $total_gasto += $anuncio['gasto'];

    if ($anuncio['estado'] === 'publish') {
        $anuncios_activos++;
    } elseif ($anuncio['estado'] === 'pending') {
        $anuncios_pendientes++;
    }
}

$ctr_total = $total_impresiones > 0 ? round(($total_clics / $total_impresiones) * 100, 2) : 0;

// Determinar vista actual
$vista_actual = isset($_GET['vista']) ? sanitize_key($_GET['vista']) : 'resumen';
$base_url = remove_query_arg(['vista', 'ad_id']);
?>

<div class="flavor-ads-dashboard">
    <!-- Header -->
    <div class="ads-dashboard-header">
        <div class="ads-header-info">
            <h2><?php esc_html_e('Panel de Anunciante', 'flavor-chat-ia'); ?></h2>
            <p class="ads-header-subtitle"><?php esc_html_e('Gestiona tus campañas publicitarias', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="ads-header-actions">
            <a href="<?php echo esc_url(add_query_arg('vista', 'crear', $base_url)); ?>" class="btn btn-primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Crear anuncio', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Navegación -->
    <div class="ads-nav-tabs">
        <a href="<?php echo esc_url($base_url); ?>" class="ads-nav-tab <?php echo $vista_actual === 'resumen' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-dashboard"></span>
            <?php esc_html_e('Resumen', 'flavor-chat-ia'); ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('vista', 'anuncios', $base_url)); ?>" class="ads-nav-tab <?php echo $vista_actual === 'anuncios' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-megaphone"></span>
            <?php esc_html_e('Mis Anuncios', 'flavor-chat-ia'); ?>
            <?php if (count($anuncios) > 0): ?>
                <span class="ads-badge"><?php echo count($anuncios); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url(add_query_arg('vista', 'estadisticas', $base_url)); ?>" class="ads-nav-tab <?php echo $vista_actual === 'estadisticas' ? 'active' : ''; ?>">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Contenido según vista -->
    <?php if ($vista_actual === 'resumen'): ?>
        <!-- Vista Resumen -->
        <div class="ads-resumen-view">
            <!-- Stats cards -->
            <div class="ads-stats-row">
                <div class="ads-stat-card">
                    <div class="ads-stat-icon" style="background: rgba(16, 185, 129, 0.1);">
                        <span class="dashicons dashicons-megaphone" style="color: #10b981;"></span>
                    </div>
                    <div class="ads-stat-content">
                        <span class="ads-stat-valor"><?php echo esc_html($anuncios_activos); ?></span>
                        <span class="ads-stat-label"><?php esc_html_e('Anuncios Activos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="ads-stat-card">
                    <div class="ads-stat-icon" style="background: rgba(99, 102, 241, 0.1);">
                        <span class="dashicons dashicons-visibility" style="color: #6366f1;"></span>
                    </div>
                    <div class="ads-stat-content">
                        <span class="ads-stat-valor"><?php echo esc_html(number_format($total_impresiones)); ?></span>
                        <span class="ads-stat-label"><?php esc_html_e('Impresiones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="ads-stat-card">
                    <div class="ads-stat-icon" style="background: rgba(59, 130, 246, 0.1);">
                        <span class="dashicons dashicons-admin-links" style="color: #3b82f6;"></span>
                    </div>
                    <div class="ads-stat-content">
                        <span class="ads-stat-valor"><?php echo esc_html(number_format($total_clics)); ?></span>
                        <span class="ads-stat-label"><?php esc_html_e('Clics', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="ads-stat-card">
                    <div class="ads-stat-icon" style="background: rgba(245, 158, 11, 0.1);">
                        <span class="dashicons dashicons-chart-line" style="color: #f59e0b;"></span>
                    </div>
                    <div class="ads-stat-content">
                        <span class="ads-stat-valor"><?php echo esc_html($ctr_total); ?>%</span>
                        <span class="ads-stat-label"><?php esc_html_e('CTR Promedio', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Gasto y anuncios pendientes -->
            <div class="ads-info-row">
                <div class="ads-info-card">
                    <h4><?php esc_html_e('Gasto Total', 'flavor-chat-ia'); ?></h4>
                    <div class="ads-gasto-valor"><?php echo esc_html(number_format($total_gasto, 2)); ?>€</div>
                    <p class="ads-info-desc"><?php esc_html_e('Invertido en todas tus campañas', 'flavor-chat-ia'); ?></p>
                </div>

                <?php if ($anuncios_pendientes > 0): ?>
                <div class="ads-info-card ads-warning">
                    <h4><?php esc_html_e('Pendientes de Aprobación', 'flavor-chat-ia'); ?></h4>
                    <div class="ads-gasto-valor"><?php echo esc_html($anuncios_pendientes); ?></div>
                    <p class="ads-info-desc"><?php esc_html_e('Anuncios esperando revisión', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Últimos anuncios -->
            <?php if (!empty($anuncios)): ?>
            <div class="ads-section">
                <h3><?php esc_html_e('Últimos Anuncios', 'flavor-chat-ia'); ?></h3>
                <div class="ads-grid">
                    <?php foreach (array_slice($anuncios, 0, 3) as $anuncio): ?>
                        <div class="ads-card">
                            <div class="ads-card-header">
                                <h4><?php echo esc_html($anuncio['titulo']); ?></h4>
                                <span class="ads-status <?php echo esc_attr($anuncio['estado']); ?>">
                                    <?php echo esc_html(ucfirst($anuncio['estado'])); ?>
                                </span>
                            </div>
                            <div class="ads-card-meta">
                                <span class="ads-tipo"><?php echo esc_html(str_replace('_', ' ', $anuncio['tipo'])); ?></span>
                            </div>
                            <div class="ads-card-stats">
                                <div>
                                    <strong><?php echo esc_html(number_format($anuncio['impresiones'])); ?></strong>
                                    <span><?php esc_html_e('Imp.', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo esc_html(number_format($anuncio['clics'])); ?></strong>
                                    <span><?php esc_html_e('Clics', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo esc_html(number_format($anuncio['gasto'], 2)); ?>€</strong>
                                    <span><?php esc_html_e('Gasto', 'flavor-chat-ia'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($anuncios) > 3): ?>
                <p class="ads-ver-mas">
                    <a href="<?php echo esc_url(add_query_arg('vista', 'anuncios', $base_url)); ?>">
                        <?php esc_html_e('Ver todos los anuncios', 'flavor-chat-ia'); ?> →
                    </a>
                </p>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="ads-empty">
                <span class="dashicons dashicons-megaphone"></span>
                <p><?php esc_html_e('Aún no tienes anuncios. ¡Crea tu primera campaña!', 'flavor-chat-ia'); ?></p>
                <a href="<?php echo esc_url(add_query_arg('vista', 'crear', $base_url)); ?>" class="btn btn-primary">
                    <?php esc_html_e('Crear mi primer anuncio', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>

    <?php elseif ($vista_actual === 'anuncios'): ?>
        <!-- Vista Todos los Anuncios -->
        <div class="ads-anuncios-view">
            <?php if (!empty($anuncios)): ?>
                <div class="ads-grid">
                    <?php foreach ($anuncios as $anuncio): ?>
                        <div class="ads-card">
                            <div class="ads-card-header">
                                <h4><?php echo esc_html($anuncio['titulo']); ?></h4>
                                <span class="ads-status <?php echo esc_attr($anuncio['estado']); ?>">
                                    <?php echo esc_html(ucfirst($anuncio['estado'])); ?>
                                </span>
                            </div>
                            <div class="ads-card-meta">
                                <span class="ads-tipo"><?php echo esc_html(str_replace('_', ' ', $anuncio['tipo'])); ?></span>
                                <?php if (!empty($anuncio['presupuesto'])): ?>
                                <span class="ads-presupuesto">
                                    <?php printf(esc_html__('Presupuesto: %s€', 'flavor-chat-ia'), number_format($anuncio['presupuesto'], 2)); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="ads-card-stats">
                                <div>
                                    <strong><?php echo esc_html(number_format($anuncio['impresiones'])); ?></strong>
                                    <span><?php esc_html_e('Impresiones', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo esc_html(number_format($anuncio['clics'])); ?></strong>
                                    <span><?php esc_html_e('Clics', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div>
                                    <strong><?php echo esc_html(number_format($anuncio['gasto'], 2)); ?>€</strong>
                                    <span><?php esc_html_e('Gasto', 'flavor-chat-ia'); ?></span>
                                </div>
                            </div>
                            <div class="ads-card-actions">
                                <?php if ($anuncio['estado'] === 'publish'): ?>
                                    <button type="button" class="btn btn-sm btn-outline btn-pausar-anuncio" data-ad-id="<?php echo esc_attr($anuncio['id']); ?>">
                                        <span class="dashicons dashicons-controls-pause"></span>
                                        <?php esc_html_e('Pausar', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php elseif ($anuncio['estado'] === 'draft'): ?>
                                    <button type="button" class="btn btn-sm btn-primary btn-reanudar-anuncio" data-ad-id="<?php echo esc_attr($anuncio['id']); ?>">
                                        <span class="dashicons dashicons-controls-play"></span>
                                        <?php esc_html_e('Reanudar', 'flavor-chat-ia'); ?>
                                    </button>
                                <?php elseif ($anuncio['estado'] === 'pending'): ?>
                                    <span class="ads-status-text"><?php esc_html_e('Pendiente de aprobación', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="ads-empty">
                    <span class="dashicons dashicons-megaphone"></span>
                    <p><?php esc_html_e('No tienes anuncios creados.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(add_query_arg('vista', 'crear', $base_url)); ?>" class="btn btn-primary">
                        <?php esc_html_e('Crear anuncio', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

    <?php elseif ($vista_actual === 'estadisticas'): ?>
        <!-- Vista Estadísticas -->
        <div class="ads-estadisticas-view">
            <div class="ads-periodo-selector-container">
                <label for="ads-periodo"><?php esc_html_e('Periodo:', 'flavor-chat-ia'); ?></label>
                <select id="ads-periodo" class="ads-periodo-selector">
                    <option value="today"><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></option>
                    <option value="week"><?php esc_html_e('Última semana', 'flavor-chat-ia'); ?></option>
                    <option value="month" selected><?php esc_html_e('Último mes', 'flavor-chat-ia'); ?></option>
                    <option value="year"><?php esc_html_e('Último año', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <div class="ads-stats-container">
                <div class="ads-stats-grid">
                    <div class="ads-stat-card-lg">
                        <span class="ads-stat-valor"><?php echo esc_html(number_format($estadisticas['impresiones'] ?? 0)); ?></span>
                        <span class="ads-stat-label"><?php esc_html_e('Impresiones', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="ads-stat-card-lg">
                        <span class="ads-stat-valor"><?php echo esc_html(number_format($estadisticas['clics'] ?? 0)); ?></span>
                        <span class="ads-stat-label"><?php esc_html_e('Clics', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="ads-stat-card-lg">
                        <span class="ads-stat-valor"><?php echo esc_html($estadisticas['ctr'] ?? 0); ?>%</span>
                        <span class="ads-stat-label"><?php esc_html_e('CTR', 'flavor-chat-ia'); ?></span>
                    </div>
                    <div class="ads-stat-card-lg">
                        <span class="ads-stat-valor"><?php echo esc_html(number_format($estadisticas['gasto'] ?? 0, 2)); ?>€</span>
                        <span class="ads-stat-label"><?php esc_html_e('Gasto Total', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Rendimiento por anuncio -->
            <?php if (!empty($anuncios)): ?>
            <div class="ads-section">
                <h3><?php esc_html_e('Rendimiento por Anuncio', 'flavor-chat-ia'); ?></h3>
                <table class="ads-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Anuncio', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Impresiones', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Clics', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('CTR', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Gasto', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anuncios as $anuncio):
                            $ctr_anuncio = $anuncio['impresiones'] > 0
                                ? round(($anuncio['clics'] / $anuncio['impresiones']) * 100, 2)
                                : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($anuncio['titulo']); ?></strong></td>
                            <td><?php echo esc_html(str_replace('_', ' ', $anuncio['tipo'])); ?></td>
                            <td><?php echo esc_html(number_format($anuncio['impresiones'])); ?></td>
                            <td><?php echo esc_html(number_format($anuncio['clics'])); ?></td>
                            <td><?php echo esc_html($ctr_anuncio); ?>%</td>
                            <td><?php echo esc_html(number_format($anuncio['gasto'], 2)); ?>€</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    <?php elseif ($vista_actual === 'crear'): ?>
        <!-- Vista Crear (incluir template separado) -->
        <?php include __DIR__ . '/crear.php'; ?>
    <?php endif; ?>
</div>

<style>
/* Estilos adicionales para el dashboard */
.ads-header-subtitle {
    color: var(--ads-gray-500);
    margin: 0.25rem 0 0 0;
    font-size: 0.95rem;
}

.ads-nav-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    border-bottom: 1px solid var(--ads-gray-200);
    padding-bottom: 0;
}

.ads-nav-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    text-decoration: none;
    color: var(--ads-gray-500);
    font-weight: 500;
    border-bottom: 2px solid transparent;
    margin-bottom: -1px;
    transition: all 0.2s;
}

.ads-nav-tab:hover {
    color: var(--ads-primary);
}

.ads-nav-tab.active {
    color: var(--ads-primary);
    border-bottom-color: var(--ads-primary);
}

.ads-badge {
    background: var(--ads-primary);
    color: #fff;
    padding: 2px 8px;
    border-radius: 20px;
    font-size: 0.75rem;
}

.ads-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
    margin-bottom: 2rem;
}

.ads-stat-card {
    background: #fff;
    border-radius: var(--ads-radius);
    padding: 1.25rem;
    box-shadow: var(--ads-shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.ads-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.ads-stat-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.ads-stat-content {
    flex: 1;
}

.ads-stat-valor {
    display: block;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--ads-gray-800);
}

.ads-stat-label {
    color: var(--ads-gray-500);
    font-size: 0.875rem;
}

.ads-info-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.ads-info-card {
    background: #fff;
    border-radius: var(--ads-radius);
    padding: 1.5rem;
    box-shadow: var(--ads-shadow);
}

.ads-info-card.ads-warning {
    border-left: 4px solid var(--ads-warning);
}

.ads-gasto-valor {
    font-size: 2rem;
    font-weight: 700;
    color: var(--ads-primary);
    margin: 0.5rem 0;
}

.ads-info-desc {
    color: var(--ads-gray-500);
    font-size: 0.875rem;
    margin: 0;
}

.ads-section {
    margin-top: 2rem;
}

.ads-section h3 {
    margin: 0 0 1rem 0;
    font-size: 1.125rem;
}

.ads-ver-mas {
    text-align: center;
    margin-top: 1rem;
}

.ads-ver-mas a {
    color: var(--ads-primary);
    text-decoration: none;
}

.ads-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.ads-card-actions {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--ads-gray-100);
    display: flex;
    gap: 0.5rem;
}

.ads-status-text {
    color: var(--ads-warning);
    font-size: 0.875rem;
    font-style: italic;
}

.ads-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--ads-gray-50);
    border-radius: var(--ads-radius);
}

.ads-empty .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: var(--ads-gray-300);
    margin-bottom: 1rem;
}

.ads-empty p {
    color: var(--ads-gray-500);
    margin-bottom: 1.5rem;
}

.ads-periodo-selector-container {
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ads-periodo-selector {
    padding: 0.5rem 1rem;
    border: 1px solid var(--ads-gray-200);
    border-radius: 8px;
}

.ads-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1rem;
}

.ads-stat-card-lg {
    background: #fff;
    border-radius: var(--ads-radius);
    padding: 2rem;
    box-shadow: var(--ads-shadow);
    text-align: center;
}

.ads-stat-card-lg .ads-stat-valor {
    font-size: 2rem;
}

.ads-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: var(--ads-radius);
    overflow: hidden;
    box-shadow: var(--ads-shadow);
}

.ads-table th,
.ads-table td {
    padding: 1rem;
    text-align: left;
    border-bottom: 1px solid var(--ads-gray-100);
}

.ads-table th {
    background: var(--ads-gray-50);
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--ads-gray-600);
}

.ads-table tbody tr:hover {
    background: var(--ads-gray-50);
}

@media (max-width: 1024px) {
    .ads-stats-row,
    .ads-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .ads-nav-tabs {
        overflow-x: auto;
    }

    .ads-stats-row,
    .ads-stats-grid {
        grid-template-columns: 1fr;
    }

    .ads-table {
        display: block;
        overflow-x: auto;
    }
}
</style>
