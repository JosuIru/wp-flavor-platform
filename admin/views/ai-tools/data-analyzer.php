<?php
/**
 * Página del Analizador de Datos IA
 *
 * Analiza datos de módulos y genera insights con IA.
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

// Módulos disponibles para análisis
$available_modules = [
    'socios' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'eventos' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'reservas' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cursos' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'grupos_consumo' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'incidencias' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'marketplace' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'foros' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'participacion' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'biblioteca' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap flavor-ai-page flavor-data-analyzer">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-chart-pie"></span>
        <?php esc_html_e('Analizador de Datos IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if (!$is_ai_configured): ?>
    <div class="notice notice-warning">
        <p>
            <span class="dashicons dashicons-warning"></span>
            <?php esc_html_e('Configura el motor de IA para obtener análisis inteligentes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-settings')); ?>">
                <?php esc_html_e('Ir a configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>

    <div class="analyzer-layout">
        <!-- Panel de Configuración -->
        <div class="analyzer-config card">
            <h2>
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Configurar Análisis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>

            <form id="analyzer-form" class="analyzer-form">
                <!-- Selección de Módulo -->
                <div class="form-group">
                    <label><?php esc_html_e('Módulo a analizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="modules-grid">
                        <?php foreach ($available_modules as $module_key => $module_name): ?>
                            <label class="module-option">
                                <input type="radio" name="module" value="<?php echo esc_attr($module_key); ?>">
                                <span class="module-card">
                                    <span class="dashicons dashicons-analytics"></span>
                                    <span class="module-name"><?php echo esc_html($module_name); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Rango de Fechas -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="date-from"><?php esc_html_e('Desde', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" id="date-from" value="<?php echo esc_attr(date('Y-m-d', strtotime('-30 days'))); ?>">
                    </div>
                    <div class="form-group">
                        <label for="date-to"><?php esc_html_e('Hasta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="date" id="date-to" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                    </div>
                </div>

                <!-- Tipo de Análisis -->
                <div class="form-group">
                    <label><?php esc_html_e('Tipo de análisis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="analysis-types">
                        <label class="analysis-type">
                            <input type="checkbox" name="analysis_types[]" value="trends" checked>
                            <span class="type-card">
                                <span class="dashicons dashicons-chart-line"></span>
                                <span class="type-name"><?php esc_html_e('Tendencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                        <label class="analysis-type">
                            <input type="checkbox" name="analysis_types[]" value="patterns" checked>
                            <span class="type-card">
                                <span class="dashicons dashicons-visibility"></span>
                                <span class="type-name"><?php esc_html_e('Patrones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                        <label class="analysis-type">
                            <input type="checkbox" name="analysis_types[]" value="anomalies">
                            <span class="type-card">
                                <span class="dashicons dashicons-warning"></span>
                                <span class="type-name"><?php esc_html_e('Anomalías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                        <label class="analysis-type">
                            <input type="checkbox" name="analysis_types[]" value="predictions">
                            <span class="type-card">
                                <span class="dashicons dashicons-pressthis"></span>
                                <span class="type-name"><?php esc_html_e('Predicciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Pregunta personalizada -->
                <div class="form-group">
                    <label for="custom-question"><?php esc_html_e('Pregunta personalizada (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="custom-question" rows="3" placeholder="<?php esc_attr_e('Ejemplo: ¿Cuáles son los eventos más populares y por qué?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary button-hero" <?php echo !$is_ai_configured ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-chart-pie"></span>
                        <?php esc_html_e('Analizar Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Resultados del Análisis -->
        <div class="analyzer-results" id="analyzer-results" style="display: none;">
            <!-- Resumen Rápido -->
            <div class="quick-stats card">
                <h2>
                    <span class="dashicons dashicons-dashboard"></span>
                    <?php esc_html_e('Resumen Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="stats-grid" id="quick-stats-grid">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>

            <!-- Gráficos -->
            <div class="charts-section card">
                <h2>
                    <span class="dashicons dashicons-chart-area"></span>
                    <?php esc_html_e('Visualización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="charts-tabs">
                    <button type="button" class="chart-tab active" data-chart="timeline">
                        <?php esc_html_e('Línea temporal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="chart-tab" data-chart="distribution">
                        <?php esc_html_e('Distribución', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="chart-tab" data-chart="comparison">
                        <?php esc_html_e('Comparativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
                <div class="chart-container">
                    <canvas id="analysis-chart"></canvas>
                </div>
            </div>

            <!-- Insights IA -->
            <div class="insights-section card">
                <h2>
                    <span class="dashicons dashicons-lightbulb"></span>
                    <?php esc_html_e('Insights IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="insights-list" id="insights-list">
                    <!-- Lista de insights generados por IA -->
                </div>
            </div>

            <!-- Respuesta Personalizada -->
            <div class="custom-answer card" id="custom-answer-section" style="display: none;">
                <h2>
                    <span class="dashicons dashicons-format-chat"></span>
                    <?php esc_html_e('Respuesta a tu pregunta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="answer-content" id="custom-answer">
                    <!-- Respuesta del IA -->
                </div>
            </div>

            <!-- Recomendaciones -->
            <div class="recommendations-section card">
                <h2>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Acciones Recomendadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="recommendations-grid" id="recommendations-grid">
                    <!-- Tarjetas de recomendaciones -->
                </div>
            </div>

            <!-- Exportar -->
            <div class="export-section">
                <button type="button" class="button" id="export-analysis-pdf">
                    <span class="dashicons dashicons-pdf"></span>
                    <?php esc_html_e('Exportar PDF', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="export-analysis-csv">
                    <span class="dashicons dashicons-media-spreadsheet"></span>
                    <?php esc_html_e('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="button" id="new-analysis">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Nuevo Análisis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>

        <!-- Estado de análisis -->
        <div class="analysis-status card" id="analysis-status" style="display: none;">
            <div class="status-animation">
                <div class="pulse-ring"></div>
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="status-text">
                <h3><?php esc_html_e('Analizando datos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p id="analysis-step"><?php esc_html_e('Recopilando información del módulo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="analysis-progress" style="width: 0%"></div>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-data-analyzer {
    max-width: 1200px;
}

.flavor-data-analyzer h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 25px;
}

.analyzer-layout {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 25px;
}

.card h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 20px 0;
    font-size: 16px;
}

.card h2 .dashicons {
    color: #667eea;
}

/* Modules Grid */
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 10px;
}

.module-option input {
    display: none;
}

.module-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 15px 10px;
    background: #f5f5f5;
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
    text-align: center;
}

.module-option input:checked + .module-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.module-card:hover {
    border-color: #667eea;
}

.module-card .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    opacity: 0.7;
}

.module-card .module-name {
    font-size: 12px;
    font-weight: 500;
}

/* Form Row */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
}

.form-group input[type="date"],
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
}

.form-group textarea {
    resize: vertical;
    min-height: 80px;
}

/* Analysis Types */
.analysis-types {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.analysis-type input {
    display: none;
}

.type-card {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 15px;
    background: #f5f5f5;
    border: 2px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.analysis-type input:checked + .type-card {
    background: #e8f0fe;
    border-color: #667eea;
    color: #667eea;
}

.type-card .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.type-card .type-name {
    font-size: 12px;
    font-weight: 500;
}

/* Form Actions */
.form-actions {
    text-align: center;
    padding-top: 10px;
}

.button-hero {
    padding: 12px 30px !important;
    height: auto !important;
    font-size: 14px !important;
}

.button-hero .dashicons {
    margin-right: 8px;
}

/* Quick Stats */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-item {
    background: linear-gradient(135deg, #f5f7ff 0%, #ebe8ff 100%);
    border-radius: 10px;
    padding: 20px 15px;
    text-align: center;
}

.stat-item .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
}

.stat-item .stat-label {
    font-size: 12px;
    color: #666;
    margin-top: 5px;
}

.stat-item .stat-trend {
    font-size: 11px;
    margin-top: 8px;
}

.stat-item .stat-trend.up {
    color: #10b981;
}

.stat-item .stat-trend.down {
    color: #ef4444;
}

/* Charts */
.charts-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 15px;
}

.chart-tab {
    padding: 8px 16px;
    background: #f5f5f5;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.2s;
}

.chart-tab.active {
    background: #667eea;
    color: white;
}

.chart-container {
    background: #fafafa;
    border-radius: 8px;
    padding: 20px;
    min-height: 300px;
}

/* Insights */
.insights-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.insight-item {
    display: flex;
    gap: 15px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.insight-item.warning {
    border-left-color: #f59e0b;
    background: #fffbeb;
}

.insight-item.success {
    border-left-color: #10b981;
    background: #ecfdf5;
}

.insight-icon {
    width: 40px;
    height: 40px;
    background: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.insight-icon .dashicons {
    color: #667eea;
}

.insight-content h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.insight-content p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

/* Custom Answer */
.answer-content {
    background: linear-gradient(135deg, #f5f7ff 0%, #ebe8ff 100%);
    padding: 20px;
    border-radius: 10px;
    font-size: 14px;
    line-height: 1.7;
}

/* Recommendations */
.recommendations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.recommendation-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 18px;
    transition: all 0.2s;
}

.recommendation-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.recommendation-card .rec-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.recommendation-card .rec-icon {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.recommendation-card .rec-title {
    font-weight: 600;
    font-size: 13px;
}

.recommendation-card .rec-description {
    font-size: 12px;
    color: #666;
    margin-bottom: 12px;
}

.recommendation-card .rec-action {
    font-size: 12px;
    color: #667eea;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Export Section */
.export-section {
    display: flex;
    gap: 10px;
    justify-content: center;
    padding-top: 20px;
}

/* Analysis Status */
.analysis-status {
    text-align: center;
    padding: 50px 30px;
}

.status-animation {
    position: relative;
    width: 80px;
    height: 80px;
    margin: 0 auto 20px;
}

.pulse-ring {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 3px solid #667eea;
    border-radius: 50%;
    animation: pulse 2s ease infinite;
}

@keyframes pulse {
    0% {
        transform: scale(0.95);
        opacity: 1;
    }
    70% {
        transform: scale(1.3);
        opacity: 0;
    }
    100% {
        transform: scale(0.95);
        opacity: 0;
    }
}

.status-animation .dashicons {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 36px;
    width: 36px;
    height: 36px;
    color: #667eea;
    animation: spin 3s linear infinite;
}

@keyframes spin {
    from { transform: translate(-50%, -50%) rotate(0deg); }
    to { transform: translate(-50%, -50%) rotate(360deg); }
}

.status-text h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
}

.status-text p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.analysis-status .progress-bar {
    max-width: 400px;
    margin: 25px auto 0;
    height: 6px;
    background: #e0e0e0;
    border-radius: 3px;
    overflow: hidden;
}

.analysis-status .progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.5s ease;
}
</style>
