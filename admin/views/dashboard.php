<?php
/**
 * Vista del Dashboard de Flavor Platform
 *
 * @package FlavorPlatform
 * @subpackage Admin/Views
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap flavor-dashboard-wrapper">
    <div class="flavor-dashboard-header">
        <h1><?php echo esc_html__('Dashboard de Flavor Platform', 'flavor-chat-ia'); ?></h1>
        <div class="flavor-dashboard-header-actions">
            <span class="flavor-last-update" id="flavor-last-update">
                <span class="dashicons dashicons-update"></span>
                <span class="flavor-update-text"><?php echo esc_html__('Actualizando...', 'flavor-chat-ia'); ?></span>
            </span>
            <button type="button" class="button flavor-refresh-btn" id="flavor-refresh-dashboard">
                <span class="dashicons dashicons-update"></span>
                <?php echo esc_html__('Actualizar', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>

    <!-- Hero del perfil activo -->
    <div class="flavor-dashboard-hero" style="background: linear-gradient(135deg, <?php echo esc_attr($datos_perfil_activo['color']); ?> 0%, <?php echo esc_attr($datos_perfil_activo['color']); ?>cc 100%);">
        <div class="flavor-dashboard-hero-icon">
            <span class="dashicons <?php echo esc_attr($datos_perfil_activo['icono']); ?>"></span>
        </div>
        <div class="flavor-dashboard-hero-info">
            <h2><?php echo esc_html($datos_perfil_activo['nombre']); ?></h2>
            <p><?php echo esc_html($datos_perfil_activo['descripcion']); ?></p>
            <div class="flavor-dashboard-hero-meta">
                <span><span class="dashicons dashicons-admin-plugins"></span> <?php printf(esc_html__('%d/%d modulos activos', 'flavor-chat-ia'), $estadisticas['modulos_activos'], $estadisticas['modulos_totales']); ?></span>
                <span><span class="dashicons dashicons-admin-plugins"></span> <?php printf(esc_html__('%d addons', 'flavor-chat-ia'), $estadisticas['addons_activos']); ?></span>
                <span><span class="dashicons dashicons-admin-users"></span> <?php printf(esc_html__('%d usuarios activos', 'flavor-chat-ia'), $estadisticas['usuarios_activos_30d']); ?></span>
            </div>
        </div>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>" class="button">
            <?php echo esc_html__('Modificar mi app', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Barra de progreso onboarding -->
    <?php if ($progreso_onboarding < 100): ?>
    <div class="flavor-onboarding-bar">
        <h3><?php printf(esc_html__('Configuracion: %d%%', 'flavor-chat-ia'), $progreso_onboarding); ?></h3>
        <div class="flavor-onboarding-steps">
            <?php foreach ($checks_onboarding as $check): ?>
                <span class="flavor-onboarding-step <?php echo $check['completado'] ? 'completado' : 'pendiente'; ?>">
                    <span class="dashicons <?php echo $check['completado'] ? 'dashicons-yes-alt' : 'dashicons-marker'; ?>"></span>
                    <?php echo esc_html($check['etiqueta']); ?>
                </span>
            <?php endforeach; ?>
        </div>
        <div class="flavor-onboarding-progress">
            <div class="flavor-onboarding-progress-fill" style="width: <?php echo intval($progreso_onboarding); ?>%;"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid de widgets sortable -->
    <div class="flavor-widgets-container" id="flavor-widgets-sortable">

        <!-- Fila 1: Metricas principales -->
        <div class="flavor-widgets-row flavor-widgets-row-3">
            <!-- Widget: Metricas en tiempo real -->
            <div class="flavor-dashboard-widget flavor-widget-metrics" data-widget="metrics">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-chart-area"></span> <?php echo esc_html__('Metricas', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-metrics-grid" id="flavor-metrics-grid">
                        <div class="flavor-metric-card" data-metric="usuarios">
                            <div class="flavor-metric-icon"><span class="dashicons dashicons-admin-users"></span></div>
                            <div class="flavor-metric-data">
                                <span class="flavor-metric-value" id="metric-usuarios"><?php echo esc_html($estadisticas['usuarios_activos_30d']); ?></span>
                                <span class="flavor-metric-label"><?php echo esc_html__('Usuarios activos (30d)', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <div class="flavor-metric-card" data-metric="modulos">
                            <div class="flavor-metric-icon"><span class="dashicons dashicons-screenoptions"></span></div>
                            <div class="flavor-metric-data">
                                <span class="flavor-metric-value" id="metric-modulos"><?php echo esc_html($estadisticas['modulos_activos'] . '/' . $estadisticas['modulos_totales']); ?></span>
                                <span class="flavor-metric-label"><?php echo esc_html__('Modulos activos', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <?php if ($estadisticas['eventos_proximos'] > 0): ?>
                        <div class="flavor-metric-card" data-metric="eventos">
                            <div class="flavor-metric-icon"><span class="dashicons dashicons-calendar"></span></div>
                            <div class="flavor-metric-data">
                                <span class="flavor-metric-value" id="metric-eventos"><?php echo esc_html($estadisticas['eventos_proximos']); ?></span>
                                <span class="flavor-metric-label"><?php echo esc_html__('Eventos proximos', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($estadisticas['pedidos_pendientes'] > 0): ?>
                        <div class="flavor-metric-card flavor-metric-warning" data-metric="pedidos">
                            <div class="flavor-metric-icon"><span class="dashicons dashicons-cart"></span></div>
                            <div class="flavor-metric-data">
                                <span class="flavor-metric-value" id="metric-pedidos"><?php echo esc_html($estadisticas['pedidos_pendientes']); ?></span>
                                <span class="flavor-metric-label"><?php echo esc_html__('Pedidos pendientes', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if ($estadisticas['socios_activos'] > 0): ?>
                        <div class="flavor-metric-card" data-metric="socios">
                            <div class="flavor-metric-icon"><span class="dashicons dashicons-groups"></span></div>
                            <div class="flavor-metric-data">
                                <span class="flavor-metric-value" id="metric-socios"><?php echo esc_html($estadisticas['socios_activos']); ?></span>
                                <span class="flavor-metric-label"><?php echo esc_html__('Socios activos', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="flavor-metric-card" data-metric="conversaciones">
                            <div class="flavor-metric-icon"><span class="dashicons dashicons-format-chat"></span></div>
                            <div class="flavor-metric-data">
                                <span class="flavor-metric-value" id="metric-conversaciones"><?php echo esc_html($estadisticas['conversaciones']); ?></span>
                                <span class="flavor-metric-label"><?php echo esc_html__('Conversaciones IA', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widget: Estado del sistema -->
            <div class="flavor-dashboard-widget flavor-widget-system" data-widget="system">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-dashboard"></span> <?php echo esc_html__('Estado del Sistema', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-health-semaphore <?php echo esc_attr($nivel_salud['nivel']); ?>">
                        <span class="dashicons <?php echo esc_attr($nivel_salud['icono']); ?>"></span>
                        <strong><?php echo esc_html($nivel_salud['mensaje']); ?></strong>
                    </div>
                    <div class="flavor-system-info">
                        <div class="flavor-system-row">
                            <span class="flavor-system-label"><?php echo esc_html__('Version Plugin:', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-system-value"><?php echo esc_html($estado_sistema['version_plugin']); ?></span>
                        </div>
                        <div class="flavor-system-row">
                            <span class="flavor-system-label"><?php echo esc_html__('PHP:', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-system-value"><?php echo esc_html($estado_sistema['version_php']); ?></span>
                        </div>
                        <div class="flavor-system-row">
                            <span class="flavor-system-label"><?php echo esc_html__('WordPress:', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-system-value"><?php echo esc_html($estado_sistema['version_wordpress']); ?></span>
                        </div>
                        <div class="flavor-system-row">
                            <span class="flavor-system-label"><?php echo esc_html__('API:', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-system-value flavor-api-status-<?php echo esc_attr($estado_sistema['estado_api']); ?>">
                                <?php
                                $textos_estado_api = [
                                    'sin_configurar' => __('Sin configurar', 'flavor-chat-ia'),
                                    'configurada'    => __('Configurada', 'flavor-chat-ia'),
                                    'activa'         => __('Activa', 'flavor-chat-ia'),
                                ];
                                echo esc_html($textos_estado_api[$estado_sistema['estado_api']] ?? $estado_sistema['estado_api']);
                                ?>
                            </span>
                        </div>
                        <div class="flavor-system-row">
                            <span class="flavor-system-label"><?php echo esc_html__('Espacio Uploads:', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-system-value"><?php echo esc_html($estado_sistema['espacio_uploads']); ?></span>
                        </div>
                        <div class="flavor-system-row">
                            <span class="flavor-system-label"><?php echo esc_html__('Ultima Sync:', 'flavor-chat-ia'); ?></span>
                            <span class="flavor-system-value"><?php echo esc_html($estado_sistema['ultima_sincronizacion']); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-health-check')); ?>" class="button flavor-btn-full">
                        <?php echo esc_html__('Health Check Completo', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>

            <!-- Widget: Alertas -->
            <div class="flavor-dashboard-widget flavor-widget-alerts" data-widget="alerts">
                <div class="flavor-widget-header">
                    <h3>
                        <span class="dashicons dashicons-bell"></span>
                        <?php echo esc_html__('Alertas', 'flavor-chat-ia'); ?>
                        <?php if (!empty($alertas)): ?>
                            <span class="flavor-alert-badge"><?php echo count($alertas); ?></span>
                        <?php endif; ?>
                    </h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-alerts-list" id="flavor-alerts-list">
                        <?php if (!empty($alertas)): ?>
                            <?php foreach ($alertas as $alerta): ?>
                                <a href="<?php echo esc_url($alerta['url']); ?>" class="flavor-alert-item flavor-alert-<?php echo esc_attr($alerta['tipo']); ?>">
                                    <span class="dashicons <?php echo esc_attr($alerta['icono']); ?>"></span>
                                    <span class="flavor-alert-message"><?php echo esc_html($alerta['mensaje']); ?></span>
                                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="flavor-alerts-empty">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <p><?php echo esc_html__('Sin alertas pendientes', 'flavor-chat-ia'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 2: Graficos -->
        <div class="flavor-widgets-row flavor-widgets-row-3">
            <!-- Widget: Grafico de usuarios -->
            <div class="flavor-dashboard-widget flavor-widget-chart" data-widget="chart-users">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-chart-line"></span> <?php echo esc_html__('Usuarios Nuevos', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-chart-container">
                        <canvas id="flavor-chart-users"></canvas>
                    </div>
                </div>
            </div>

            <!-- Widget: Actividad por modulo -->
            <div class="flavor-dashboard-widget flavor-widget-chart" data-widget="chart-modules">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-chart-bar"></span> <?php echo esc_html__('Actividad por Modulo', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-chart-container">
                        <canvas id="flavor-chart-modules"></canvas>
                    </div>
                </div>
            </div>

            <!-- Widget: Distribucion de roles -->
            <div class="flavor-dashboard-widget flavor-widget-chart" data-widget="chart-roles">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-groups"></span> <?php echo esc_html__('Distribucion de Roles', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-chart-container flavor-chart-doughnut">
                        <canvas id="flavor-chart-roles"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 3: Actividad y acciones -->
        <div class="flavor-widgets-row flavor-widgets-row-2">
            <!-- Widget: Actividad reciente -->
            <div class="flavor-dashboard-widget flavor-widget-activity" data-widget="activity">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-backup"></span> <?php echo esc_html__('Actividad Reciente', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <ul class="flavor-activity-list" id="flavor-activity-list">
                        <?php if (!empty($actividad_reciente)): ?>
                            <?php foreach ($actividad_reciente as $actividad_item): ?>
                                <li class="flavor-activity-item flavor-activity-<?php echo esc_attr($actividad_item['tipo']); ?>">
                                    <span class="flavor-activity-icon dashicons <?php echo esc_attr($actividad_item['icono']); ?>"></span>
                                    <div class="flavor-activity-content">
                                        <span class="flavor-activity-title"><?php echo esc_html($actividad_item['titulo']); ?></span>
                                        <?php if (!empty($actividad_item['usuario'])): ?>
                                            <span class="flavor-activity-user">
                                                <?php echo esc_html($actividad_item['usuario']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="flavor-activity-time"><?php echo esc_html($actividad_item['tiempo']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="flavor-activity-empty">
                                <span class="dashicons dashicons-info"></span>
                                <?php echo esc_html__('Sin actividad reciente', 'flavor-chat-ia'); ?>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-activity-log')); ?>" class="flavor-view-all-link">
                        <?php echo esc_html__('Ver toda la actividad', 'flavor-chat-ia'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
            </div>

            <!-- Widget: Acciones rapidas -->
            <div class="flavor-dashboard-widget flavor-widget-actions" data-widget="quick-actions">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-admin-tools"></span> <?php echo esc_html__('Acciones Rapidas', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <!-- Acciones principales -->
                    <div class="flavor-actions-section">
                        <h4><?php echo esc_html__('Principales', 'flavor-chat-ia'); ?></h4>
                        <div class="flavor-quick-actions-grid">
                            <?php foreach ($acciones_rapidas['principales'] as $accion): ?>
                                <a href="<?php echo esc_url($accion['url']); ?>"
                                   class="flavor-quick-action-btn"
                                   style="--action-color: <?php echo esc_attr($accion['color']); ?>"
                                   <?php if (!empty($accion['modal'])): ?>data-modal="<?php echo esc_attr($accion['id']); ?>"<?php endif; ?>>
                                    <span class="dashicons <?php echo esc_attr($accion['icono']); ?>"></span>
                                    <span class="flavor-action-label"><?php echo esc_html($accion['etiqueta']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if (!empty($acciones_rapidas['contextuales'])): ?>
                    <!-- Acciones contextuales -->
                    <div class="flavor-actions-section">
                        <h4><?php echo esc_html__('Segun modulos', 'flavor-chat-ia'); ?></h4>
                        <div class="flavor-quick-actions-grid">
                            <?php foreach ($acciones_rapidas['contextuales'] as $accion): ?>
                                <a href="<?php echo esc_url($accion['url']); ?>"
                                   class="flavor-quick-action-btn"
                                   style="--action-color: <?php echo esc_attr($accion['color']); ?>"
                                   <?php if (!empty($accion['modal'])): ?>data-modal="<?php echo esc_attr($accion['id']); ?>"<?php endif; ?>>
                                    <span class="dashicons <?php echo esc_attr($accion['icono']); ?>"></span>
                                    <span class="flavor-action-label"><?php echo esc_html($accion['etiqueta']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Acciones generales -->
                    <div class="flavor-actions-section">
                        <h4><?php echo esc_html__('Herramientas', 'flavor-chat-ia'); ?></h4>
                        <div class="flavor-quick-actions-grid">
                            <?php foreach ($acciones_rapidas['generales'] as $accion): ?>
                                <a href="<?php echo esc_url($accion['url']); ?>"
                                   class="flavor-quick-action-btn"
                                   style="--action-color: <?php echo esc_attr($accion['color']); ?>"
                                   <?php if (!empty($accion['modal'])): ?>data-modal="<?php echo esc_attr($accion['id']); ?>"<?php endif; ?>
                                   <?php if (!empty($accion['action'])): ?>data-action="<?php echo esc_attr($accion['action']); ?>"<?php endif; ?>>
                                    <span class="dashicons <?php echo esc_attr($accion['icono']); ?>"></span>
                                    <span class="flavor-action-label"><?php echo esc_html($accion['etiqueta']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 4: Addons -->
        <div class="flavor-widgets-row flavor-widgets-row-1">
            <div class="flavor-dashboard-widget flavor-widget-addons" data-widget="addons">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-admin-plugins"></span> <?php echo esc_html__('Addons Instalados', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <?php if (empty($addons_registrados)): ?>
                        <div class="flavor-addons-empty">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <p><?php echo esc_html__('No hay addons instalados', 'flavor-chat-ia'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-addons')); ?>" class="button button-primary">
                                <?php echo esc_html__('Explorar Addons', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="flavor-addons-grid">
                            <?php foreach ($addons_registrados as $slug_addon => $datos_addon): ?>
                                <div class="flavor-addon-card <?php echo in_array($slug_addon, $addons_activos) ? 'active' : 'inactive'; ?>">
                                    <div class="flavor-addon-icon">
                                        <span class="dashicons <?php echo esc_attr($datos_addon['icon'] ?? 'dashicons-admin-plugins'); ?>"></span>
                                    </div>
                                    <div class="flavor-addon-info">
                                        <span class="flavor-addon-name"><?php echo esc_html($datos_addon['name']); ?></span>
                                        <span class="flavor-addon-version"><?php esc_html_e('v', 'flavor-chat-ia'); ?><?php echo esc_html($datos_addon['version']); ?></span>
                                    </div>
                                    <span class="flavor-addon-status <?php echo in_array($slug_addon, $addons_activos) ? 'active' : 'inactive'; ?>">
                                        <?php echo in_array($slug_addon, $addons_activos) ? esc_html__('Activo', 'flavor-chat-ia') : esc_html__('Inactivo', 'flavor-chat-ia'); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-addons')); ?>" class="flavor-view-all-link">
                            <?php echo esc_html__('Gestionar addons', 'flavor-chat-ia'); ?>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fila 5: Red de Comunidades -->
        <div class="flavor-widgets-row flavor-widgets-row-2">
            <!-- Widget: Estado de la Red -->
            <div class="flavor-dashboard-widget flavor-widget-network" data-widget="network-status">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-networking"></span> <?php echo esc_html__('Red de Comunidades', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-widget-actions">
                        <button type="button" class="button button-small" id="flavor-sync-network" title="<?php echo esc_attr__('Sincronizar ahora', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-update"></span>
                        </button>
                        <span class="flavor-widget-handle dashicons dashicons-move"></span>
                    </div>
                </div>
                <div class="flavor-widget-content">
                    <?php if (!empty($estadisticas_red['nodo_local'])): ?>
                        <!-- Nodo local -->
                        <div class="flavor-network-local-node">
                            <div class="flavor-network-local-badge">
                                <span class="dashicons dashicons-admin-home"></span>
                                <strong><?php echo esc_html($estadisticas_red['nodo_local']['nombre']); ?></strong>
                                <?php if ($estadisticas_red['nodo_local']['verificado']): ?>
                                    <span class="flavor-verified-badge" title="<?php echo esc_attr__('Verificado', 'flavor-chat-ia'); ?>">
                                        <span class="dashicons dashicons-yes-alt"></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Estadisticas de red -->
                        <div class="flavor-network-stats-grid">
                            <div class="flavor-network-stat">
                                <span class="flavor-network-stat-value flavor-stat-active"><?php echo esc_html($estadisticas_red['nodos_activos']); ?></span>
                                <span class="flavor-network-stat-label"><?php echo esc_html__('Nodos activos', 'flavor-chat-ia'); ?></span>
                            </div>
                            <div class="flavor-network-stat">
                                <span class="flavor-network-stat-value"><?php echo esc_html($estadisticas_red['conexiones_federadas']); ?></span>
                                <span class="flavor-network-stat-label"><?php echo esc_html__('Federados', 'flavor-chat-ia'); ?></span>
                            </div>
                            <div class="flavor-network-stat">
                                <span class="flavor-network-stat-value"><?php echo esc_html($estadisticas_red['mensajes_sin_leer']); ?></span>
                                <span class="flavor-network-stat-label"><?php echo esc_html__('Mensajes', 'flavor-chat-ia'); ?></span>
                            </div>
                            <div class="flavor-network-stat">
                                <span class="flavor-network-stat-value"><?php echo esc_html($estadisticas_red['contenido_compartido']); ?></span>
                                <span class="flavor-network-stat-label"><?php echo esc_html__('Compartidos', 'flavor-chat-ia'); ?></span>
                            </div>
                        </div>

                        <!-- Alertas de nodos desconectados -->
                        <?php if (!empty($estadisticas_red['alertas_nodos'])): ?>
                        <div class="flavor-network-alerts">
                            <h4><span class="dashicons dashicons-warning"></span> <?php echo esc_html__('Nodos sin conexion reciente', 'flavor-chat-ia'); ?></h4>
                            <ul class="flavor-network-alerts-list">
                                <?php foreach (array_slice($estadisticas_red['alertas_nodos'], 0, 5) as $alerta_nodo): ?>
                                    <li class="flavor-network-alert-item">
                                        <span class="flavor-alert-node-name"><?php echo esc_html($alerta_nodo['nombre']); ?></span>
                                        <span class="flavor-alert-days">
                                            <?php
                                            if ($alerta_nodo['dias_sin_sync'] !== null) {
                                                printf(esc_html__('%d dias', 'flavor-chat-ia'), $alerta_nodo['dias_sin_sync']);
                                            } else {
                                                echo esc_html__('Nunca sincronizado', 'flavor-chat-ia');
                                            }
                                            ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <!-- Ultima sincronizacion -->
                        <div class="flavor-network-sync-info">
                            <span class="dashicons dashicons-clock"></span>
                            <?php echo esc_html__('Ultima sincronizacion:', 'flavor-chat-ia'); ?>
                            <strong id="flavor-last-sync-time">
                                <?php echo $estadisticas_red['ultima_sincronizacion'] ? esc_html(human_time_diff(strtotime($estadisticas_red['ultima_sincronizacion']))) . ' ' . esc_html__('hace', 'flavor-chat-ia') : esc_html__('Nunca', 'flavor-chat-ia'); ?>
                            </strong>
                        </div>
                    <?php else: ?>
                        <div class="flavor-network-empty">
                            <span class="dashicons dashicons-networking"></span>
                            <p><?php echo esc_html__('No hay nodo local configurado', 'flavor-chat-ia'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-network-settings')); ?>" class="button button-primary">
                                <?php echo esc_html__('Configurar Red', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget: Modulos Compartidos -->
            <div class="flavor-dashboard-widget flavor-widget-shared-modules" data-widget="shared-modules">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-share"></span> <?php echo esc_html__('Modulos Compartidos', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <?php if (!empty($modulos_compartidos)): ?>
                        <div class="flavor-shared-modules-list">
                            <?php foreach ($modulos_compartidos as $modulo): ?>
                                <div class="flavor-shared-module-item">
                                    <div class="flavor-shared-module-icon">
                                        <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>"></span>
                                    </div>
                                    <div class="flavor-shared-module-info">
                                        <span class="flavor-shared-module-name"><?php echo esc_html($modulo['etiqueta']); ?></span>
                                        <span class="flavor-shared-module-stats">
                                            <?php printf(esc_html__('%d activos / %d total', 'flavor-chat-ia'), $modulo['activos'], $modulo['total']); ?>
                                        </span>
                                    </div>
                                    <div class="flavor-shared-module-bar">
                                        <div class="flavor-shared-module-bar-fill" style="width: <?php echo $modulo['total'] > 0 ? esc_attr(round(($modulo['activos'] / $modulo['total']) * 100)) : 0; ?>%;"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-network-content')); ?>" class="flavor-view-all-link">
                            <?php echo esc_html__('Gestionar contenido compartido', 'flavor-chat-ia'); ?>
                            <span class="dashicons dashicons-arrow-right-alt2"></span>
                        </a>
                    <?php else: ?>
                        <div class="flavor-shared-modules-empty">
                            <span class="dashicons dashicons-share"></span>
                            <p><?php echo esc_html__('No hay contenido compartido aun', 'flavor-chat-ia'); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-network-content&action=new')); ?>" class="button">
                                <?php echo esc_html__('Compartir contenido', 'flavor-chat-ia'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fila 6: Mapa de Actividad -->
        <div class="flavor-widgets-row flavor-widgets-row-1">
            <div class="flavor-dashboard-widget flavor-widget-map flavor-widget-large" data-widget="activity-map">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-location-alt"></span> <?php echo esc_html__('Mapa de Actividad', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-widget-actions">
                        <div class="flavor-map-filters">
                            <select id="flavor-map-filter-type" class="flavor-map-filter">
                                <option value=""><?php echo esc_html__('Todos los tipos', 'flavor-chat-ia'); ?></option>
                                <option value="comunidad"><?php echo esc_html__('Comunidades', 'flavor-chat-ia'); ?></option>
                                <option value="cooperativa"><?php echo esc_html__('Cooperativas', 'flavor-chat-ia'); ?></option>
                                <option value="asociacion"><?php echo esc_html__('Asociaciones', 'flavor-chat-ia'); ?></option>
                                <option value="empresa"><?php echo esc_html__('Empresas', 'flavor-chat-ia'); ?></option>
                            </select>
                            <button type="button" class="button button-small" id="flavor-map-toggle-heatmap" title="<?php echo esc_attr__('Alternar heatmap', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-chart-area"></span>
                            </button>
                        </div>
                        <span class="flavor-widget-handle dashicons dashicons-move"></span>
                    </div>
                </div>
                <div class="flavor-widget-content">
                    <div id="flavor-activity-map" class="flavor-activity-map" data-center='<?php echo wp_json_encode($datos_mapa_actividad['centro']); ?>' data-zoom="<?php echo esc_attr($datos_mapa_actividad['zoom']); ?>">
                        <div class="flavor-map-loading">
                            <span class="spinner is-active"></span>
                            <p><?php echo esc_html__('Cargando mapa...', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <div class="flavor-map-legend">
                        <div class="flavor-map-legend-item">
                            <span class="flavor-legend-marker flavor-marker-comunidad"></span>
                            <?php echo esc_html__('Comunidades', 'flavor-chat-ia'); ?>
                        </div>
                        <div class="flavor-map-legend-item">
                            <span class="flavor-legend-marker flavor-marker-cooperativa"></span>
                            <?php echo esc_html__('Cooperativas', 'flavor-chat-ia'); ?>
                        </div>
                        <div class="flavor-map-legend-item">
                            <span class="flavor-legend-marker flavor-marker-empresa"></span>
                            <?php echo esc_html__('Empresas', 'flavor-chat-ia'); ?>
                        </div>
                        <div class="flavor-map-legend-item">
                            <span class="flavor-legend-marker flavor-marker-otro"></span>
                            <?php echo esc_html__('Otros', 'flavor-chat-ia'); ?>
                        </div>
                    </div>
                    <!-- Estadisticas por zona -->
                    <?php if (!empty($datos_mapa_actividad['estadisticas_zona'])): ?>
                    <div class="flavor-map-zone-stats">
                        <h4><?php echo esc_html__('Zonas mas activas', 'flavor-chat-ia'); ?></h4>
                        <div class="flavor-zone-stats-list">
                            <?php foreach (array_slice($datos_mapa_actividad['estadisticas_zona'], 0, 5) as $zona): ?>
                                <div class="flavor-zone-stat-item">
                                    <span class="flavor-zone-name"><?php echo esc_html($zona['ciudad'] . ', ' . $zona['pais']); ?></span>
                                    <span class="flavor-zone-count"><?php printf(esc_html__('%d nodos', 'flavor-chat-ia'), $zona['total_nodos']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Fila 7: KPIs con Tendencias y Graficos Avanzados -->
        <div class="flavor-widgets-row flavor-widgets-row-2">
            <!-- Widget: KPIs Principales -->
            <div class="flavor-dashboard-widget flavor-widget-kpis" data-widget="kpis">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-performance"></span> <?php echo esc_html__('KPIs Principales', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-widget-actions">
                        <button type="button" class="button button-small" id="flavor-export-stats" title="<?php echo esc_attr__('Exportar a CSV', 'flavor-chat-ia'); ?>">
                            <span class="dashicons dashicons-download"></span>
                        </button>
                        <span class="flavor-widget-handle dashicons dashicons-move"></span>
                    </div>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-kpis-grid">
                        <?php foreach ($kpis_principales as $clave_kpi => $kpi): ?>
                            <div class="flavor-kpi-card" data-kpi="<?php echo esc_attr($clave_kpi); ?>">
                                <div class="flavor-kpi-icon">
                                    <span class="dashicons <?php echo esc_attr($kpi['icono']); ?>"></span>
                                </div>
                                <div class="flavor-kpi-content">
                                    <span class="flavor-kpi-value"><?php echo esc_html(number_format_i18n($kpi['valor'])); ?></span>
                                    <span class="flavor-kpi-label"><?php echo esc_html($kpi['etiqueta']); ?></span>
                                    <?php if (isset($kpi['subtitulo'])): ?>
                                        <span class="flavor-kpi-subtitle"><?php echo esc_html($kpi['subtitulo']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($kpi['tendencia'] != 0): ?>
                                    <div class="flavor-kpi-trend flavor-trend-<?php echo esc_attr($kpi['tendencia_tipo']); ?>">
                                        <span class="dashicons <?php echo $kpi['tendencia'] > 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'; ?>"></span>
                                        <span class="flavor-trend-value"><?php echo esc_html(abs($kpi['tendencia'])); ?>%</span>
                                        <?php if (isset($kpi['periodo'])): ?>
                                            <span class="flavor-trend-period"><?php echo esc_html($kpi['periodo']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Widget: Comparativas Temporales -->
            <div class="flavor-dashboard-widget flavor-widget-comparatives" data-widget="comparatives">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-chart-area"></span> <?php echo esc_html__('Comparativas', 'flavor-chat-ia'); ?></h3>
                    <div class="flavor-widget-actions">
                        <select id="flavor-chart-period" class="flavor-chart-select">
                            <option value="weekly"><?php echo esc_html__('Semanal', 'flavor-chat-ia'); ?></option>
                            <option value="monthly"><?php echo esc_html__('Mensual', 'flavor-chat-ia'); ?></option>
                            <option value="hourly"><?php echo esc_html__('Por hora', 'flavor-chat-ia'); ?></option>
                        </select>
                        <span class="flavor-widget-handle dashicons dashicons-move"></span>
                    </div>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-comparatives-chart-container">
                        <canvas id="flavor-chart-comparatives"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fila 8: Nodos Recientes -->
        <?php if (!empty($estadisticas_red['nodos_recientes'])): ?>
        <div class="flavor-widgets-row flavor-widgets-row-1">
            <div class="flavor-dashboard-widget flavor-widget-recent-nodes" data-widget="recent-nodes">
                <div class="flavor-widget-header">
                    <h3><span class="dashicons dashicons-welcome-add-page"></span> <?php echo esc_html__('Nodos Conectados Recientemente', 'flavor-chat-ia'); ?></h3>
                    <span class="flavor-widget-handle dashicons dashicons-move"></span>
                </div>
                <div class="flavor-widget-content">
                    <div class="flavor-recent-nodes-grid">
                        <?php foreach ($estadisticas_red['nodos_recientes'] as $nodo_reciente): ?>
                            <div class="flavor-recent-node-card">
                                <div class="flavor-recent-node-logo">
                                    <?php if (!empty($nodo_reciente->logo_url)): ?>
                                        <img src="<?php echo esc_url($nodo_reciente->logo_url); ?>" alt="<?php echo esc_attr($nodo_reciente->nombre); ?>">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="flavor-recent-node-info">
                                    <strong class="flavor-recent-node-name"><?php echo esc_html($nodo_reciente->nombre); ?></strong>
                                    <span class="flavor-recent-node-type"><?php echo esc_html(ucfirst($nodo_reciente->tipo_entidad)); ?></span>
                                    <span class="flavor-recent-node-location">
                                        <span class="dashicons dashicons-location"></span>
                                        <?php echo esc_html($nodo_reciente->ciudad . ', ' . $nodo_reciente->pais); ?>
                                    </span>
                                </div>
                                <div class="flavor-recent-node-date">
                                    <?php echo esc_html(human_time_diff(strtotime($nodo_reciente->fecha_registro))); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-network-directory')); ?>" class="flavor-view-all-link">
                        <?php echo esc_html__('Ver directorio completo', 'flavor-chat-ia'); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.flavor-widgets-container -->

</div><!-- /.flavor-dashboard-wrapper -->

<!-- Modal para enviar notificacion -->
<div id="flavor-modal-notificacion" class="flavor-modal" style="display:none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h3><?php echo esc_html__('Enviar Notificacion', 'flavor-chat-ia'); ?></h3>
            <button type="button" class="flavor-modal-close"><?php esc_html_e('&times;', 'flavor-chat-ia'); ?></button>
        </div>
        <div class="flavor-modal-body">
            <div class="flavor-form-group">
                <label for="notif-titulo"><?php echo esc_html__('Titulo', 'flavor-chat-ia'); ?></label>
                <input type="text" id="notif-titulo" class="regular-text" placeholder="<?php echo esc_attr__('Titulo de la notificacion', 'flavor-chat-ia'); ?>">
            </div>
            <div class="flavor-form-group">
                <label for="notif-mensaje"><?php echo esc_html__('Mensaje', 'flavor-chat-ia'); ?></label>
                <textarea id="notif-mensaje" rows="4" placeholder="<?php echo esc_attr__('Contenido del mensaje', 'flavor-chat-ia'); ?>"></textarea>
            </div>
            <div class="flavor-form-group">
                <label for="notif-destinatarios"><?php echo esc_html__('Destinatarios', 'flavor-chat-ia'); ?></label>
                <select id="notif-destinatarios">
                    <option value="<?php echo esc_attr__('all', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Todos los usuarios', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('socios', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Solo socios', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('admins', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Administradores', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button flavor-modal-cancel"><?php echo esc_html__('Cancelar', 'flavor-chat-ia'); ?></button>
            <button type="button" class="button button-primary" id="flavor-send-notification"><?php echo esc_html__('Enviar', 'flavor-chat-ia'); ?></button>
        </div>
    </div>
</div>
