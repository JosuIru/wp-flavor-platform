<?php
/**
 * Página del Generador de Datos Demo
 *
 * Genera datos de prueba para módulos. Solo disponible en modo desarrollo.
 *
 * @package FlavorChatIA
 * @since 3.3.2
 */

if (!defined('ABSPATH') || !defined('WP_DEBUG') || !WP_DEBUG) {
    exit;
}

// Módulos disponibles para generación de datos
$available_modules = [
    'socios' => [
        'name' => __('Miembros', 'flavor-chat-ia'),
        'icon' => 'dashicons-id-alt',
        'description' => __('Genera socios con datos realistas: nombres, emails, cuotas, fechas de alta.', 'flavor-chat-ia'),
    ],
    'eventos' => [
        'name' => __('Eventos', 'flavor-chat-ia'),
        'icon' => 'dashicons-calendar',
        'description' => __('Crea eventos pasados y futuros con inscripciones y asistencia.', 'flavor-chat-ia'),
    ],
    'reservas' => [
        'name' => __('Reservas', 'flavor-chat-ia'),
        'icon' => 'dashicons-tickets-alt',
        'description' => __('Genera reservas de espacios con diferentes estados.', 'flavor-chat-ia'),
    ],
    'cursos' => [
        'name' => __('Cursos', 'flavor-chat-ia'),
        'icon' => 'dashicons-welcome-learn-more',
        'description' => __('Crea cursos con módulos, lecciones y matrículas.', 'flavor-chat-ia'),
    ],
    'grupos_consumo' => [
        'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
        'icon' => 'dashicons-cart',
        'description' => __('Genera productores, productos, ciclos y pedidos.', 'flavor-chat-ia'),
    ],
    'incidencias' => [
        'name' => __('Incidencias', 'flavor-chat-ia'),
        'icon' => 'dashicons-warning',
        'description' => __('Crea tickets con diferentes prioridades y estados.', 'flavor-chat-ia'),
    ],
    'marketplace' => [
        'name' => __('Marketplace', 'flavor-chat-ia'),
        'icon' => 'dashicons-store',
        'description' => __('Genera productos, vendedores y transacciones.', 'flavor-chat-ia'),
    ],
    'foros' => [
        'name' => __('Foros', 'flavor-chat-ia'),
        'icon' => 'dashicons-format-chat',
        'description' => __('Crea categorías, temas y respuestas con contenido realista.', 'flavor-chat-ia'),
    ],
];
?>

<div class="wrap flavor-ai-page flavor-demo-generator">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-database-import"></span>
        <?php esc_html_e('Generador de Datos Demo', 'flavor-chat-ia'); ?>
        <span class="debug-badge"><?php esc_html_e('Solo Desarrollo', 'flavor-chat-ia'); ?></span>
    </h1>

    <div class="notice notice-warning">
        <p>
            <span class="dashicons dashicons-warning"></span>
            <strong><?php esc_html_e('Atención:', 'flavor-chat-ia'); ?></strong>
            <?php esc_html_e('Esta herramienta genera datos ficticios. Los datos existentes NO se eliminan, pero asegúrate de estar en un entorno de desarrollo.', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <div class="generator-layout">
        <!-- Panel de Generación -->
        <div class="generator-panel card">
            <h2>
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Generar Datos', 'flavor-chat-ia'); ?>
            </h2>

            <form id="demo-generator-form" class="generator-form">
                <!-- Selección de Módulos -->
                <div class="form-group">
                    <label><?php esc_html_e('Selecciona módulos', 'flavor-chat-ia'); ?></label>
                    <div class="modules-grid">
                        <?php foreach ($available_modules as $module_key => $module): ?>
                            <label class="module-option">
                                <input type="checkbox" name="modules[]" value="<?php echo esc_attr($module_key); ?>">
                                <span class="module-card">
                                    <span class="dashicons <?php echo esc_attr($module['icon']); ?>"></span>
                                    <span class="module-name"><?php echo esc_html($module['name']); ?></span>
                                    <span class="module-desc"><?php echo esc_html($module['description']); ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cantidad de registros -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="records-count"><?php esc_html_e('Cantidad de registros', 'flavor-chat-ia'); ?></label>
                        <select id="records-count" name="count">
                            <option value="10"><?php esc_html_e('Pequeño (10 registros)', 'flavor-chat-ia'); ?></option>
                            <option value="25" selected><?php esc_html_e('Medio (25 registros)', 'flavor-chat-ia'); ?></option>
                            <option value="50"><?php esc_html_e('Grande (50 registros)', 'flavor-chat-ia'); ?></option>
                            <option value="100"><?php esc_html_e('Muy grande (100 registros)', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="date-range"><?php esc_html_e('Rango de fechas', 'flavor-chat-ia'); ?></label>
                        <select id="date-range" name="date_range">
                            <option value="1"><?php esc_html_e('Último mes', 'flavor-chat-ia'); ?></option>
                            <option value="3" selected><?php esc_html_e('Últimos 3 meses', 'flavor-chat-ia'); ?></option>
                            <option value="6"><?php esc_html_e('Últimos 6 meses', 'flavor-chat-ia'); ?></option>
                            <option value="12"><?php esc_html_e('Último año', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Opciones adicionales -->
                <div class="form-group">
                    <label><?php esc_html_e('Opciones', 'flavor-chat-ia'); ?></label>
                    <div class="options-list">
                        <label class="option-checkbox">
                            <input type="checkbox" name="options[]" value="realistic_names" checked>
                            <span><?php esc_html_e('Usar nombres realistas (español)', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="option-checkbox">
                            <input type="checkbox" name="options[]" value="include_images" checked>
                            <span><?php esc_html_e('Incluir imágenes placeholder', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="option-checkbox">
                            <input type="checkbox" name="options[]" value="varied_status" checked>
                            <span><?php esc_html_e('Variar estados (activo, pendiente, etc.)', 'flavor-chat-ia'); ?></span>
                        </label>
                        <label class="option-checkbox">
                            <input type="checkbox" name="options[]" value="create_relations">
                            <span><?php esc_html_e('Crear relaciones entre datos', 'flavor-chat-ia'); ?></span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" id="select-all-modules" class="button">
                        <?php esc_html_e('Seleccionar todos', 'flavor-chat-ia'); ?>
                    </button>
                    <button type="submit" class="button button-primary button-hero">
                        <span class="dashicons dashicons-database-add"></span>
                        <?php esc_html_e('Generar Datos Demo', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Estado de Generación -->
        <div class="generation-status card" id="generation-status" style="display: none;">
            <h2>
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Generando datos...', 'flavor-chat-ia'); ?>
            </h2>

            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill" id="generation-progress"></div>
                </div>
                <div class="progress-text">
                    <span id="progress-current">0</span> / <span id="progress-total">0</span>
                </div>
            </div>

            <div class="generation-log" id="generation-log">
                <!-- Log de generación -->
            </div>
        </div>

        <!-- Resultados -->
        <div class="generation-results card" id="generation-results" style="display: none;">
            <h2>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Datos generados correctamente', 'flavor-chat-ia'); ?>
            </h2>

            <div class="results-summary" id="results-summary">
                <!-- Resumen de datos generados -->
            </div>

            <div class="results-actions">
                <button type="button" class="button" id="view-generated-data">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Ver datos', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button button-link-delete" id="clear-demo-data">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Eliminar datos demo', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button button-primary" id="generate-more">
                    <span class="dashicons dashicons-plus"></span>
                    <?php esc_html_e('Generar más', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>

        <!-- Datos Demo Existentes -->
        <div class="existing-data card">
            <h2>
                <span class="dashicons dashicons-database"></span>
                <?php esc_html_e('Datos Demo Existentes', 'flavor-chat-ia'); ?>
            </h2>

            <div class="existing-data-list" id="existing-data-list">
                <p class="loading">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e('Cargando información...', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <div class="cleanup-actions">
                <button type="button" class="button button-link-delete" id="cleanup-all-demo">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Limpiar todos los datos demo', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-demo-generator {
    max-width: 1100px;
}

.flavor-demo-generator h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.debug-badge {
    background: #f0b849;
    color: #5d4800;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
    margin-left: auto;
}

.generator-layout {
    display: flex;
    flex-direction: column;
    gap: 20px;
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
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 12px;
}

.module-option input {
    display: none;
}

.module-card {
    display: flex;
    flex-direction: column;
    padding: 15px;
    background: #f9f9f9;
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.module-option input:checked + .module-card {
    background: #f0f4ff;
    border-color: #667eea;
}

.module-card:hover {
    border-color: #ccc;
}

.module-card .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #667eea;
    margin-bottom: 8px;
}

.module-card .module-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 5px;
}

.module-card .module-desc {
    font-size: 11px;
    color: #666;
    line-height: 1.4;
}

/* Form */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 20px 0;
}

.form-group label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 8px;
}

.form-group select {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 13px;
    background-color: white;
}

.options-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.option-checkbox {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 15px;
    background: #f9f9f9;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s;
}

.option-checkbox:hover {
    background: #f0f0f0;
}

.option-checkbox input {
    width: 18px;
    height: 18px;
    accent-color: #667eea;
}

.option-checkbox span {
    font-size: 13px;
}

.form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #eee;
    margin-top: 20px;
}

.button-hero {
    padding: 12px 25px !important;
    height: auto !important;
    font-size: 14px !important;
}

.button-hero .dashicons {
    margin-right: 8px;
}

/* Generation Status */
.generation-status h2 .dashicons {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.progress-container {
    margin-bottom: 20px;
}

.progress-bar {
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 8px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    transition: width 0.3s ease;
    width: 0%;
}

.progress-text {
    text-align: center;
    font-size: 13px;
    color: #666;
}

.generation-log {
    background: #1e1e2f;
    border-radius: 8px;
    padding: 15px;
    max-height: 200px;
    overflow-y: auto;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
    font-size: 12px;
}

.generation-log .log-entry {
    color: #a0a0a0;
    margin-bottom: 4px;
}

.generation-log .log-entry.success {
    color: #10b981;
}

.generation-log .log-entry.error {
    color: #ef4444;
}

.generation-log .log-entry.info {
    color: #667eea;
}

/* Results */
.generation-results h2 .dashicons {
    color: #10b981;
}

.results-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 12px;
    margin-bottom: 20px;
}

.result-item {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}

.result-item .result-count {
    font-size: 24px;
    font-weight: 700;
    color: #059669;
}

.result-item .result-label {
    font-size: 11px;
    color: #065f46;
    margin-top: 4px;
}

.results-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
}

/* Existing Data */
.existing-data-list .module-data {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f9f9f9;
    border-radius: 6px;
    margin-bottom: 8px;
}

.existing-data-list .module-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.existing-data-list .module-info .dashicons {
    color: #667eea;
}

.existing-data-list .module-name {
    font-weight: 500;
}

.existing-data-list .module-count {
    color: #666;
    font-size: 12px;
}

.existing-data-list .module-actions .button {
    padding: 4px 10px;
    font-size: 11px;
}

.cleanup-actions {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
    text-align: center;
}

.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 30px;
    color: #666;
}

.loading .spinner {
    margin: 0;
}
</style>
