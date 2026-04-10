<?php
/**
 * Hub Central de Herramientas IA
 *
 * @package FlavorPlatform
 * @since 3.3.2
 */

if (!defined('ABSPATH')) {
    exit;
}

$ai_tools_admin = Flavor_AI_Tools_Admin::get_instance();
$tools = $ai_tools_admin->get_available_tools();
$is_ai_configured = false;

if (class_exists('Flavor_Engine_Manager')) {
    $manager = Flavor_Engine_Manager::get_instance();
    $engine = $manager->get_active_engine();
    $is_ai_configured = $engine && $engine->is_configured();
}
?>

<div class="wrap flavor-ai-hub">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-generic"></span>
        <?php esc_html_e('Herramientas IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <?php if (!$is_ai_configured): ?>
    <div class="notice notice-warning flavor-ai-notice">
        <p>
            <span class="dashicons dashicons-warning"></span>
            <strong><?php esc_html_e('Motor de IA no configurado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong><br>
            <?php esc_html_e('Para usar las herramientas de IA, primero configura una API key en', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-settings')); ?>">
                <?php esc_html_e('Configuración de Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
    </div>
    <?php endif; ?>

    <div class="flavor-ai-hub-intro">
        <p>
            <?php esc_html_e('Las herramientas de IA te ayudan a gestionar tu comunidad de forma más eficiente. Desde generar contenido hasta analizar datos y obtener recomendaciones automáticas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <!-- Categorías de herramientas -->
    <div class="flavor-ai-tools-grid">

        <!-- Herramientas Inline -->
        <div class="flavor-ai-category">
            <h2>
                <span class="dashicons dashicons-editor-code"></span>
                <?php esc_html_e('Herramientas Integradas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Se activan automáticamente en los editores y formularios de cada módulo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="flavor-ai-tools-list">
                <?php foreach ($tools as $tool_id => $tool):
                    if ($tool['type'] !== 'inline') continue;
                ?>
                <div class="flavor-ai-tool-card <?php echo $tool['status'] === 'disabled' ? 'disabled' : ''; ?>">
                    <div class="tool-icon">
                        <span class="dashicons <?php echo esc_attr($tool['icon']); ?>"></span>
                    </div>
                    <div class="tool-info">
                        <h3><?php echo esc_html($tool['name']); ?></h3>
                        <p><?php echo esc_html($tool['description']); ?></p>
                    </div>
                    <div class="tool-status">
                        <?php if ($tool['status'] === 'active'): ?>
                            <span class="status-badge active">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge disabled">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Requiere IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Widgets -->
        <div class="flavor-ai-category">
            <h2>
                <span class="dashicons dashicons-screenoptions"></span>
                <?php esc_html_e('Widgets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Aparecen en las páginas del admin para asistirte contextualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="flavor-ai-tools-list">
                <?php foreach ($tools as $tool_id => $tool):
                    if ($tool['type'] !== 'widget') continue;
                ?>
                <div class="flavor-ai-tool-card <?php echo $tool['status'] === 'disabled' ? 'disabled' : ''; ?>">
                    <div class="tool-icon">
                        <span class="dashicons <?php echo esc_attr($tool['icon']); ?>"></span>
                    </div>
                    <div class="tool-info">
                        <h3><?php echo esc_html($tool['name']); ?></h3>
                        <p><?php echo esc_html($tool['description']); ?></p>
                    </div>
                    <div class="tool-status">
                        <?php if ($tool['status'] === 'active'): ?>
                            <span class="status-badge active">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php else: ?>
                            <span class="status-badge disabled">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Requiere IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Páginas Dedicadas -->
        <div class="flavor-ai-category">
            <h2>
                <span class="dashicons dashicons-admin-page"></span>
                <?php esc_html_e('Herramientas Avanzadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <p class="description">
                <?php esc_html_e('Páginas dedicadas para análisis y generación de reportes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="flavor-ai-tools-list">
                <?php foreach ($tools as $tool_id => $tool):
                    if ($tool['type'] !== 'page') continue;
                ?>
                <div class="flavor-ai-tool-card <?php echo $tool['status'] === 'disabled' ? 'disabled' : ''; ?>">
                    <div class="tool-icon">
                        <span class="dashicons <?php echo esc_attr($tool['icon']); ?>"></span>
                    </div>
                    <div class="tool-info">
                        <h3><?php echo esc_html($tool['name']); ?></h3>
                        <p><?php echo esc_html($tool['description']); ?></p>
                    </div>
                    <div class="tool-actions">
                        <?php if ($tool['status'] === 'active' && !empty($tool['url'])): ?>
                            <a href="<?php echo esc_url($tool['url']); ?>" class="button button-primary">
                                <?php esc_html_e('Abrir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                <span class="dashicons dashicons-arrow-right-alt"></span>
                            </a>
                        <?php elseif ($tool['status'] === 'disabled'): ?>
                            <span class="status-badge disabled">
                                <span class="dashicons dashicons-warning"></span>
                                <?php esc_html_e('Requiere IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Estadísticas de uso -->
    <div class="flavor-ai-stats-section">
        <h2>
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e('Uso de IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>

        <div class="flavor-ai-stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="ai-requests-today">-</div>
                <div class="stat-label"><?php esc_html_e('Consultas hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="ai-requests-week">-</div>
                <div class="stat-label"><?php esc_html_e('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="ai-content-generated">-</div>
                <div class="stat-label"><?php esc_html_e('Contenido generado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="ai-time-saved">-</div>
                <div class="stat-label"><?php esc_html_e('Tiempo ahorrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="flavor-ai-quick-actions">
        <h2>
            <span class="dashicons dashicons-performance"></span>
            <?php esc_html_e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h2>

        <div class="quick-actions-grid">
            <button type="button" class="quick-action-btn" id="quick-generate-report" <?php echo !$is_ai_configured ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-media-document"></span>
                <span><?php esc_html_e('Generar Reporte Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>

            <button type="button" class="quick-action-btn" id="quick-analyze-data" <?php echo !$is_ai_configured ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-chart-pie"></span>
                <span><?php esc_html_e('Analizar Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>

            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-settings')); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php esc_html_e('Configurar IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>

            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-docs&section=ai-tools')); ?>" class="quick-action-btn">
                <span class="dashicons dashicons-book"></span>
                <span><?php esc_html_e('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>
</div>

<style>
/* Estilos específicos del Hub - complementan ai-tools.css */
.flavor-ai-hub {
    max-width: 1400px;
}

.flavor-ai-hub h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
}

.flavor-ai-hub h1 .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
}

.flavor-ai-notice {
    padding: 15px 20px;
    border-left-color: #f0b849;
    background: #fff8e5;
}

.flavor-ai-notice .dashicons {
    color: #d69700;
    margin-right: 8px;
}

.flavor-ai-hub-intro {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 12px;
    margin-bottom: 30px;
}

.flavor-ai-hub-intro p {
    margin: 0;
    font-size: 15px;
    line-height: 1.6;
}

.flavor-ai-category {
    margin-bottom: 35px;
}

.flavor-ai-category h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 18px;
    margin-bottom: 8px;
}

.flavor-ai-category h2 .dashicons {
    color: #667eea;
}

.flavor-ai-category > .description {
    color: #666;
    margin-bottom: 15px;
}

.flavor-ai-tools-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 15px;
}

.flavor-ai-tool-card {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 10px;
    padding: 18px;
    transition: all 0.2s ease;
}

.flavor-ai-tool-card:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.flavor-ai-tool-card.disabled {
    opacity: 0.7;
    background: #f9f9f9;
}

.flavor-ai-tool-card .tool-icon {
    flex-shrink: 0;
    width: 44px;
    height: 44px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-ai-tool-card .tool-icon .dashicons {
    color: white;
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.flavor-ai-tool-card.disabled .tool-icon {
    background: #999;
}

.flavor-ai-tool-card .tool-info {
    flex: 1;
    min-width: 0;
}

.flavor-ai-tool-card .tool-info h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    font-weight: 600;
}

.flavor-ai-tool-card .tool-info p {
    margin: 0;
    font-size: 12px;
    color: #666;
    line-height: 1.4;
}

.flavor-ai-tool-card .tool-status,
.flavor-ai-tool-card .tool-actions {
    flex-shrink: 0;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 15px;
    font-size: 11px;
    font-weight: 500;
}

.status-badge .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.status-badge.active {
    background: #e6f4ea;
    color: #1e7e34;
}

.status-badge.disabled {
    background: #fff3cd;
    color: #856404;
}

.flavor-ai-tool-card .tool-actions .button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.flavor-ai-tool-card .tool-actions .button .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

/* Stats Section */
.flavor-ai-stats-section {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
}

.flavor-ai-stats-section h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 20px 0;
    font-size: 16px;
}

.flavor-ai-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-card {
    text-align: center;
    padding: 20px 15px;
    background: linear-gradient(135deg, #f5f7ff 0%, #ebe8ff 100%);
    border-radius: 10px;
}

.stat-card .stat-value {
    font-size: 28px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    font-size: 12px;
    color: #666;
}

/* Quick Actions */
.flavor-ai-quick-actions {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 25px;
}

.flavor-ai-quick-actions h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 20px 0;
    font-size: 16px;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.quick-action-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: #f5f5f5;
    border: 1px solid #ddd;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    color: #333;
    font-size: 13px;
}

.quick-action-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.quick-action-btn:hover .dashicons {
    color: white;
}

.quick-action-btn .dashicons {
    color: #667eea;
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.quick-action-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.quick-action-btn:disabled:hover {
    background: #f5f5f5;
    border-color: #ddd;
    color: #333;
}

.quick-action-btn:disabled:hover .dashicons {
    color: #667eea;
}
</style>
