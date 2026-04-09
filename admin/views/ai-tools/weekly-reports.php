<?php
/**
 * Página de Reportes Semanales IA
 *
 * Genera resúmenes ejecutivos semanales con métricas, tendencias y recomendaciones.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

$is_ai_configured = false;
if (class_exists('Flavor_Engine_Manager')) {
    $manager = Flavor_Engine_Manager::get_instance();
    $engine = $manager->get_active_engine();
    $is_ai_configured = $engine && $engine->is_configured();
}

// Obtener últimos reportes guardados
$saved_reports = get_option('flavor_ai_weekly_reports', []);
$latest_reports = array_slice($saved_reports, -5, 5, true);
?>

<div class="wrap flavor-ai-page flavor-weekly-reports">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('Reportes Semanales IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if (!$is_ai_configured): ?>
    <div class="notice notice-warning">
        <p>
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('Configura el motor de IA para generar análisis inteligentes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-settings')); ?>">
                <?php esc_html_e('Ir a configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>

    <div class="reports-layout">
        <!-- Panel Principal -->
        <div class="reports-main">
            <!-- Generador de Reporte -->
            <div class="report-generator card">
                <h2>
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Generar Nuevo Reporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>

                <form id="generate-report-form" class="report-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="report-week-start"><?php esc_html_e('Semana a analizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="date" id="report-week-start" value="<?php echo esc_attr(date('Y-m-d', strtotime('last monday'))); ?>">
                        </div>

                        <div class="form-group">
                            <label><?php esc_html_e('Módulos a incluir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <div class="modules-selector">
                                <label class="module-checkbox">
                                    <input type="checkbox" name="modules[]" value="all" checked>
                                    <span><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="module-checkbox">
                                    <input type="checkbox" name="modules[]" value="socios">
                                    <span><?php esc_html_e('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="module-checkbox">
                                    <input type="checkbox" name="modules[]" value="eventos">
                                    <span><?php esc_html_e('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="module-checkbox">
                                    <input type="checkbox" name="modules[]" value="reservas">
                                    <span><?php esc_html_e('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="module-checkbox">
                                    <input type="checkbox" name="modules[]" value="grupos_consumo">
                                    <span><?php esc_html_e('Grupos Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                                <label class="module-checkbox">
                                    <input type="checkbox" name="modules[]" value="incidencias">
                                    <span><?php esc_html_e('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="preview-metrics" class="button">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('Vista previa métricas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="submit" class="button button-primary" <?php echo !$is_ai_configured ? 'disabled' : ''; ?>>
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Generar Reporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Vista Previa de Métricas -->
            <div class="metrics-preview card" id="metrics-preview" style="display: none;">
                <h2>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Métricas de la Semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>

                <div class="metrics-grid" id="metrics-grid">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>

            <!-- Resultado del Reporte -->
            <div class="report-result card" id="report-result" style="display: none;">
                <div class="report-header">
                    <h2>
                        <span class="dashicons dashicons-media-document"></span>
                        <span id="report-title"><?php esc_html_e('Reporte Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </h2>
                    <div class="report-meta">
                        <span class="report-date" id="report-date"></span>
                    </div>
                </div>

                <!-- Resumen Ejecutivo -->
                <div class="report-section">
                    <h3><?php esc_html_e('Resumen Ejecutivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="executive-summary" id="executive-summary">
                        <!-- Generado por IA -->
                    </div>
                </div>

                <!-- Métricas Clave -->
                <div class="report-section">
                    <h3><?php esc_html_e('Métricas Clave', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="key-metrics" id="key-metrics">
                        <!-- Tarjetas de métricas con variación -->
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="report-section">
                    <h3><?php esc_html_e('Tendencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <div class="charts-container">
                        <div class="chart-wrapper">
                            <canvas id="activity-chart"></canvas>
                        </div>
                        <div class="chart-wrapper">
                            <canvas id="modules-chart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Análisis IA -->
                <div class="report-section">
                    <h3>
                        <span class="dashicons dashicons-lightbulb"></span>
                        <?php esc_html_e('Análisis IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="ai-analysis" id="ai-analysis">
                        <!-- Análisis generado por IA -->
                    </div>
                </div>

                <!-- Recomendaciones -->
                <div class="report-section">
                    <h3>
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Recomendaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <div class="recommendations" id="recommendations">
                        <!-- Lista de recomendaciones -->
                    </div>
                </div>

                <!-- Acciones -->
                <div class="report-actions">
                    <button type="button" class="button" id="export-pdf">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php esc_html_e('Exportar PDF', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button" id="send-email">
                        <span class="dashicons dashicons-email"></span>
                        <?php esc_html_e('Enviar por Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="button" id="save-report">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e('Guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <!-- Estado de generación -->
            <div class="generation-status" id="report-status" style="display: none;">
                <div class="status-content">
                    <div class="spinner is-active"></div>
                    <div class="status-text">
                        <strong><?php esc_html_e('Generando reporte...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span id="status-step"><?php esc_html_e('Recopilando métricas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progress-fill" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="reports-sidebar">
            <!-- Reportes Guardados -->
            <div class="card saved-reports">
                <h3>
                    <span class="dashicons dashicons-portfolio"></span>
                    <?php esc_html_e('Reportes Anteriores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <?php if (empty($latest_reports)): ?>
                    <p class="no-reports"><?php esc_html_e('No hay reportes guardados aún.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <ul class="reports-list">
                        <?php foreach (array_reverse($latest_reports, true) as $report_id => $report): ?>
                            <li class="report-item">
                                <a href="#" class="load-report" data-id="<?php echo esc_attr($report_id); ?>">
                                    <span class="report-icon dashicons dashicons-media-document"></span>
                                    <div class="report-info">
                                        <span class="report-name">
                                            <?php printf(
                                                /* translators: %s: date range */
                                                esc_html__('Semana %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                esc_html($report['period']['start'] ?? $report_id)
                                            ); ?>
                                        </span>
                                        <span class="report-timestamp">
                                            <?php echo esc_html(human_time_diff(strtotime($report['generated_at'] ?? 'now'))); ?>
                                        </span>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Configuración Automática -->
            <div class="card auto-reports">
                <h3>
                    <span class="dashicons dashicons-clock"></span>
                    <?php esc_html_e('Reportes Automáticos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <div class="auto-report-setting">
                    <label class="toggle-switch">
                        <input type="checkbox" id="auto-reports-enabled" <?php checked(get_option('flavor_auto_weekly_reports', false)); ?>>
                        <span class="slider"></span>
                    </label>
                    <span><?php esc_html_e('Generar cada lunes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>

                <div class="auto-report-email">
                    <label for="report-email"><?php esc_html_e('Enviar a:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="email" id="report-email" value="<?php echo esc_attr(get_option('admin_email')); ?>" placeholder="email@ejemplo.com">
                </div>
            </div>

            <!-- Ayuda -->
            <div class="card help-card">
                <h3>
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php esc_html_e('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
                <p><?php esc_html_e('Los reportes semanales incluyen:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <ul>
                    <li><?php esc_html_e('Métricas clave de cada módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Comparativa con semana anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Análisis de tendencias por IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                    <li><?php esc_html_e('Recomendaciones automáticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-weekly-reports {
    max-width: 1400px;
}

.flavor-weekly-reports h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
}

.reports-layout {
    display: grid;
    grid-template-columns: 1fr 300px;
    gap: 25px;
}

@media (max-width: 1200px) {
    .reports-layout {
        grid-template-columns: 1fr;
    }
}

.card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.card h2, .card h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 15px 0;
    font-size: 15px;
}

.card h2 .dashicons,
.card h3 .dashicons {
    color: #667eea;
}

/* Form */
.report-form .form-row {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 6px;
}

.modules-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.module-checkbox {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: #f5f5f5;
    border-radius: 20px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.module-checkbox:has(input:checked) {
    background: #667eea;
    color: white;
}

.module-checkbox input {
    display: none;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.form-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Metrics Grid */
.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 15px;
}

.metric-card {
    background: linear-gradient(135deg, #f5f7ff 0%, #ebe8ff 100%);
    border-radius: 10px;
    padding: 15px;
    text-align: center;
}

.metric-card .metric-value {
    font-size: 24px;
    font-weight: 700;
    color: #667eea;
}

.metric-card .metric-label {
    font-size: 11px;
    color: #666;
    margin-top: 4px;
}

.metric-card .metric-change {
    font-size: 11px;
    margin-top: 6px;
}

.metric-card .metric-change.positive {
    color: #10b981;
}

.metric-card .metric-change.negative {
    color: #ef4444;
}

/* Report Result */
.report-result .report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.report-meta {
    color: #666;
    font-size: 13px;
}

.report-section {
    margin-bottom: 25px;
}

.report-section h3 {
    font-size: 14px;
    margin-bottom: 12px;
    color: #333;
}

.executive-summary,
.ai-analysis {
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    font-size: 14px;
    line-height: 1.6;
}

.key-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 12px;
}

.charts-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.chart-wrapper {
    background: #f9f9f9;
    border-radius: 8px;
    padding: 15px;
}

.recommendations ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.recommendations li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding: 10px;
    background: #f0f9f0;
    border-radius: 6px;
    margin-bottom: 8px;
    font-size: 13px;
}

.recommendations li::before {
    content: '\f147';
    font-family: dashicons;
    color: #10b981;
}

.report-actions {
    display: flex;
    gap: 10px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Status */
.generation-status {
    padding: 25px;
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
}

.status-content {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.status-text {
    display: flex;
    flex-direction: column;
}

.status-text strong {
    font-size: 14px;
}

#status-step {
    font-size: 12px;
    color: #666;
}

.progress-bar {
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
}

/* Sidebar */
.reports-sidebar .card {
    margin-bottom: 15px;
}

.saved-reports .reports-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.report-item {
    margin-bottom: 8px;
}

.report-item a {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #f5f5f5;
    border-radius: 6px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.report-item a:hover {
    background: #667eea;
    color: white;
}

.report-item .report-info {
    display: flex;
    flex-direction: column;
}

.report-item .report-name {
    font-weight: 500;
    font-size: 13px;
}

.report-item .report-timestamp {
    font-size: 11px;
    opacity: 0.7;
}

.no-reports {
    text-align: center;
    color: #999;
    font-size: 13px;
    padding: 20px;
}

/* Auto Reports */
.auto-report-setting {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.toggle-switch {
    position: relative;
    width: 44px;
    height: 24px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-switch .slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.toggle-switch .slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

.toggle-switch input:checked + .slider {
    background-color: #667eea;
}

.toggle-switch input:checked + .slider:before {
    transform: translateX(20px);
}

.auto-report-email label {
    display: block;
    font-size: 12px;
    margin-bottom: 5px;
}

.auto-report-email input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
}

/* Help */
.help-card p {
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
}

.help-card ul {
    margin: 0;
    padding-left: 20px;
}

.help-card li {
    font-size: 12px;
    color: #666;
    margin-bottom: 5px;
}
</style>
