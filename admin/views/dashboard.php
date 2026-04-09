<?php
/**
 * Vista del Dashboard de Flavor Platform - V2 Mejorado
 *
 * Dashboard práctico y orientado a acciones para administradores y gestores de grupos.
 *
 * @package FlavorPlatform
 * @subpackage Admin/Views
 * @since 3.3.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

$es_vista_gestor_grupos = !empty($es_vista_gestor_grupos);
$datos_perfil_activo = is_array($datos_perfil_activo ?? null) ? $datos_perfil_activo : [];
$datos_gestor = is_array($datos_gestor ?? null) ? $datos_gestor : [];
$estadisticas = is_array($estadisticas ?? null) ? $estadisticas : [];
$progreso_onboarding = isset($progreso_onboarding) ? (int) $progreso_onboarding : 0;
$checks_onboarding = is_array($checks_onboarding ?? null) ? $checks_onboarding : [];
$nivel_salud = is_array($nivel_salud ?? null) ? $nivel_salud : ['clase' => 'is-neutral', 'icono' => 'dashicons-admin-tools', 'etiqueta' => __('Sin datos', FLAVOR_PLATFORM_TEXT_DOMAIN)];
$estado_sistema = is_array($estado_sistema ?? null) ? $estado_sistema : ['estado' => __('Sin datos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'detalle' => ''];
$acciones_rapidas = is_array($acciones_rapidas ?? null) ? $acciones_rapidas : ['principal' => [], 'secundarias' => []];

$datos_gestor = wp_parse_args($datos_gestor, [
    'estadisticas' => ['total_grupos' => 0, 'total_miembros' => 0],
    'solicitudes_pendientes' => [],
    'contenido_pendiente' => [],
    'mis_grupos' => [],
]);

$estadisticas = wp_parse_args($estadisticas, [
    'total_usuarios' => 0,
    'modulos_activos' => 0,
    'paginas_totales' => 0,
    'modulos_instalados' => 0,
    'modulos_con_dashboard' => 0,
    'porcentaje_configurado' => 0,
]);
?>
<div class="wrap flavor-dashboard-v2">
    <div class="flavor-dashboard-header">
        <h1>
            <span class="dashicons dashicons-dashboard"></span>
            <?php
            echo esc_html(
                $es_vista_gestor_grupos
                    ? __('Panel de Gestión de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN)
                    : __('Panel de Administración', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
            ?>
        </h1>
        <span class="flavor-dashboard-greeting">
            <?php
            $hora = (int) current_time('G');
            $saludo = $hora < 12 ? __('Buenos días', FLAVOR_PLATFORM_TEXT_DOMAIN) : ($hora < 20 ? __('Buenas tardes', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Buenas noches', FLAVOR_PLATFORM_TEXT_DOMAIN));
            $usuario = wp_get_current_user();
            printf('%s, <strong>%s</strong>', esc_html($saludo), esc_html($usuario->display_name));
            ?>
        </span>
    </div>

    <?php if ($es_vista_gestor_grupos): ?>
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- VISTA: GESTOR DE GRUPOS -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->

        <!-- Estadísticas de mis grupos -->
        <div class="flavor-stats-bar flavor-stats-gestor">
            <div class="flavor-stat-card">
                <span class="flavor-stat-icon dashicons dashicons-groups"></span>
                <div class="flavor-stat-data">
                    <span class="flavor-stat-value"><?php echo esc_html($datos_gestor['estadisticas']['total_grupos']); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Mis grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="flavor-stat-card">
                <span class="flavor-stat-icon dashicons dashicons-admin-users"></span>
                <div class="flavor-stat-data">
                    <span class="flavor-stat-value"><?php echo esc_html($datos_gestor['estadisticas']['total_miembros']); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Total miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="flavor-stat-card <?php echo !empty($datos_gestor['solicitudes_pendientes']) ? 'flavor-stat-alert' : ''; ?>">
                <span class="flavor-stat-icon dashicons dashicons-clock"></span>
                <div class="flavor-stat-data">
                    <span class="flavor-stat-value"><?php echo esc_html(array_sum(array_column($datos_gestor['solicitudes_pendientes'], 'cantidad'))); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Solicitudes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
            <div class="flavor-stat-card <?php echo !empty($datos_gestor['contenido_pendiente']) ? 'flavor-stat-alert' : ''; ?>">
                <span class="flavor-stat-icon dashicons dashicons-visibility"></span>
                <div class="flavor-stat-data">
                    <span class="flavor-stat-value"><?php echo esc_html(count($datos_gestor['contenido_pendiente'])); ?></span>
                    <span class="flavor-stat-label"><?php esc_html_e('Por moderar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
            </div>
        </div>

        <div class="flavor-dashboard-grid flavor-grid-2">
            <!-- Mis Grupos -->
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-networking"></span> <?php esc_html_e('Mis Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($datos_gestor['mis_grupos'])): ?>
                        <ul class="flavor-grupos-list">
                            <?php foreach ($datos_gestor['mis_grupos'] as $grupo): ?>
                                <li class="flavor-grupo-item">
                                    <a href="<?php echo esc_url($grupo['url']); ?>" class="flavor-grupo-link">
                                        <span class="flavor-grupo-nombre"><?php echo esc_html($grupo['nombre']); ?></span>
                                        <span class="flavor-grupo-meta">
                                            <span class="flavor-grupo-miembros">
                                                <span class="dashicons dashicons-admin-users"></span>
                                                <?php echo esc_html($grupo['miembros']); ?>
                                            </span>
                                            <?php if ($grupo['pendientes'] > 0): ?>
                                                <span class="flavor-grupo-pendientes">
                                                    <?php echo esc_html($grupo['pendientes']); ?> <?php esc_html_e('pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-groups"></span>
                            <p><?php esc_html_e('No gestionas ningún grupo todavía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Solicitudes Pendientes -->
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-clock"></span> <?php esc_html_e('Solicitudes de Membresía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($datos_gestor['solicitudes_pendientes'])): ?>
                        <ul class="flavor-solicitudes-list">
                            <?php foreach ($datos_gestor['solicitudes_pendientes'] as $solicitud): ?>
                                <li class="flavor-solicitud-item">
                                    <a href="<?php echo esc_url($solicitud['url']); ?>">
                                        <span class="flavor-solicitud-grupo"><?php echo esc_html($solicitud['grupo_nombre']); ?></span>
                                        <span class="flavor-solicitud-badge"><?php echo esc_html($solicitud['cantidad']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flavor-empty-state flavor-empty-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php esc_html_e('No hay solicitudes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Miembros Recientes -->
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-admin-users"></span> <?php esc_html_e('Miembros Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($datos_gestor['miembros_recientes'])): ?>
                        <ul class="flavor-miembros-list">
                            <?php foreach ($datos_gestor['miembros_recientes'] as $miembro): ?>
                                <li class="flavor-miembro-item">
                                    <?php echo get_avatar($miembro['user_id'], 32); ?>
                                    <div class="flavor-miembro-info">
                                        <span class="flavor-miembro-nombre"><?php echo esc_html($miembro['nombre']); ?></span>
                                        <span class="flavor-miembro-meta">
                                            <?php echo esc_html($miembro['grupo']); ?> · <?php echo esc_html($miembro['fecha']); ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-admin-users"></span>
                            <p><?php esc_html_e('No hay nuevos miembros esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Contenido por Moderar -->
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-visibility"></span> <?php esc_html_e('Contenido por Moderar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($datos_gestor['contenido_pendiente'])): ?>
                        <ul class="flavor-moderacion-list">
                            <?php foreach ($datos_gestor['contenido_pendiente'] as $contenido): ?>
                                <li class="flavor-moderacion-item">
                                    <a href="<?php echo esc_url($contenido['url']); ?>">
                                        <span class="flavor-moderacion-titulo"><?php echo esc_html($contenido['titulo']); ?></span>
                                        <span class="flavor-moderacion-meta">
                                            <?php echo esc_html($contenido['autor']); ?> · <?php echo esc_html($contenido['fecha']); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flavor-empty-state flavor-empty-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php esc_html_e('Todo el contenido está revisado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- VISTA: ADMINISTRADOR -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->

        <!-- Alertas de configuración -->
        <?php if (!empty($alertas_config)): ?>
            <div class="flavor-alertas-config">
                <?php foreach ($alertas_config as $alerta): ?>
                    <div class="flavor-alerta flavor-alerta-<?php echo esc_attr($alerta['tipo']); ?>">
                        <span class="dashicons <?php echo esc_attr($alerta['icono']); ?>"></span>
                        <span class="flavor-alerta-mensaje"><?php echo esc_html($alerta['mensaje']); ?></span>
                        <a href="<?php echo esc_url($alerta['url']); ?>" class="button button-small">
                            <?php echo esc_html($alerta['accion']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas principales -->
        <div class="flavor-stats-bar">
            <?php foreach ($resumen_stats as $stat): ?>
                <div class="flavor-stat-card" style="--stat-color: <?php echo esc_attr($stat['color']); ?>">
                    <span class="flavor-stat-icon dashicons <?php echo esc_attr($stat['icono']); ?>"></span>
                    <div class="flavor-stat-data">
                        <span class="flavor-stat-value"><?php echo esc_html($stat['valor']); ?></span>
                        <span class="flavor-stat-label"><?php echo esc_html($stat['etiqueta']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="flavor-dashboard-grid flavor-grid-3">
            <!-- Tareas Pendientes -->
            <div class="flavor-dashboard-card flavor-card-primary">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-list-view"></span> <?php esc_html_e('Tareas Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <?php if (!empty($tareas_pendientes)): ?>
                        <span class="flavor-badge-count"><?php echo count($tareas_pendientes); ?></span>
                    <?php endif; ?>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($tareas_pendientes)): ?>
                        <ul class="flavor-tareas-list">
                            <?php foreach ($tareas_pendientes as $tarea): ?>
                                <li class="flavor-tarea-item flavor-tarea-<?php echo esc_attr($tarea['prioridad']); ?>">
                                    <a href="<?php echo esc_url($tarea['url']); ?>">
                                        <span class="flavor-tarea-icon dashicons <?php echo esc_attr($tarea['icono']); ?>"></span>
                                        <span class="flavor-tarea-texto"><?php echo esc_html($tarea['titulo']); ?></span>
                                        <span class="flavor-tarea-badge"><?php echo esc_html($tarea['cantidad']); ?></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flavor-empty-state flavor-empty-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p><?php esc_html_e('¡Todo al día! No hay tareas pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Módulos Activos -->
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-screenoptions"></span> <?php esc_html_e('Módulos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>" class="flavor-card-action">
                        <?php esc_html_e('Ver todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($modulos_usados)): ?>
                        <div class="flavor-modulos-grid">
                            <?php foreach ($modulos_usados as $modulo): ?>
                                <a href="<?php echo esc_url($modulo['url']); ?>"
                                   class="flavor-modulo-card"
                                   style="--modulo-color: <?php echo esc_attr($modulo['color']); ?>"
                                   title="<?php echo esc_attr($modulo['descripcion']); ?>">
                                    <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>"></span>
                                    <span class="flavor-modulo-nombre"><?php echo esc_html($modulo['nombre']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-screenoptions"></span>
                            <p><?php esc_html_e('Activa módulos desde el Compositor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>" class="button button-primary">
                                <?php esc_html_e('Activar módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Accesos Rápidos -->
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-admin-tools"></span> <?php esc_html_e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($accesos_rapidos)): ?>
                        <div class="flavor-accesos-grid">
                            <?php foreach ($accesos_rapidos as $acceso): ?>
                                <a href="<?php echo esc_url($acceso['url']); ?>"
                                   class="flavor-acceso-btn"
                                   style="--acceso-color: <?php echo esc_attr($acceso['color']); ?>">
                                    <span class="dashicons <?php echo esc_attr($acceso['icono']); ?>"></span>
                                    <span><?php echo esc_html($acceso['etiqueta']); ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="flavor-accesos-default">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-settings')); ?>" class="flavor-acceso-btn">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <span><?php esc_html_e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>" class="flavor-acceso-btn">
                                <span class="dashicons dashicons-screenoptions"></span>
                                <span><?php esc_html_e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-create-pages')); ?>" class="flavor-acceso-btn">
                                <span class="dashicons dashicons-admin-page"></span>
                                <span><?php esc_html_e('Crear páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-design-settings')); ?>" class="flavor-acceso-btn">
                                <span class="dashicons dashicons-art"></span>
                                <span><?php esc_html_e('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Segunda fila: Actividad -->
        <div class="flavor-dashboard-grid flavor-grid-1">
            <div class="flavor-dashboard-card">
                <div class="flavor-card-header">
                    <h2><span class="dashicons dashicons-backup"></span> <?php esc_html_e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-activity-log')); ?>" class="flavor-card-action">
                        <?php esc_html_e('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
                <div class="flavor-card-content">
                    <?php if (!empty($actividad_reciente)): ?>
                        <ul class="flavor-actividad-list">
                            <?php foreach (array_slice($actividad_reciente, 0, 8) as $actividad): ?>
                                <li class="flavor-actividad-item">
                                    <span class="flavor-actividad-icon dashicons <?php echo esc_attr($actividad['icono']); ?>"></span>
                                    <div class="flavor-actividad-info">
                                        <span class="flavor-actividad-titulo"><?php echo esc_html($actividad['titulo']); ?></span>
                                        <?php if (!empty($actividad['usuario'])): ?>
                                            <span class="flavor-actividad-usuario"><?php echo esc_html($actividad['usuario']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="flavor-actividad-tiempo"><?php echo esc_html($actividad['tiempo']); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-backup"></span>
                            <p><?php esc_html_e('No hay actividad reciente registrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════════════════════ -->
        <!-- PANELES AVANZADOS (Colapsables) -->
        <!-- ═══════════════════════════════════════════════════════════════════════ -->

        <div class="flavor-advanced-panels">
            <!-- Panel: Gráficos y Analítica -->
            <details class="flavor-collapsible-panel" <?php echo ($paneles_estado['graficos'] ?? false) ? 'open' : ''; ?> data-panel="graficos">
                <summary class="flavor-panel-header">
                    <span class="flavor-panel-icon dashicons dashicons-chart-area"></span>
                    <span class="flavor-panel-title"><?php esc_html_e('Gráficos y Analítica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-desc"><?php esc_html_e('Usuarios, actividad por módulo, distribución de roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-toggle dashicons dashicons-arrow-down-alt2"></span>
                </summary>
                <div class="flavor-panel-content">
                    <div class="flavor-dashboard-grid flavor-grid-3">
                        <!-- Gráfico: Usuarios nuevos -->
                        <div class="flavor-dashboard-card">
                            <div class="flavor-card-header">
                                <h2><span class="dashicons dashicons-chart-line"></span> <?php esc_html_e('Usuarios Nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                            </div>
                            <div class="flavor-card-content">
                                <div class="flavor-chart-container">
                                    <canvas id="flavor-chart-users"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico: Actividad por módulo -->
                        <div class="flavor-dashboard-card">
                            <div class="flavor-card-header">
                                <h2><span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Actividad por Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                            </div>
                            <div class="flavor-card-content">
                                <div class="flavor-chart-container">
                                    <canvas id="flavor-chart-modules"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Gráfico: Distribución de roles -->
                        <div class="flavor-dashboard-card">
                            <div class="flavor-card-header">
                                <h2><span class="dashicons dashicons-groups"></span> <?php esc_html_e('Distribución de Roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
                            </div>
                            <div class="flavor-card-content">
                                <div class="flavor-chart-container flavor-chart-doughnut">
                                    <canvas id="flavor-chart-roles"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPIs principales -->
                    <?php if (!empty($kpis_principales)): ?>
                    <div class="flavor-kpis-section">
                        <h3><span class="dashicons dashicons-performance"></span> <?php esc_html_e('KPIs Principales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="flavor-kpis-grid">
                            <?php foreach ($kpis_principales as $kpi): ?>
                                <div class="flavor-kpi-card">
                                    <span class="flavor-kpi-value"><?php echo esc_html($kpi['valor'] ?? 0); ?></span>
                                    <span class="flavor-kpi-label"><?php echo esc_html($kpi['etiqueta'] ?? ''); ?></span>
                                    <?php if (!empty($kpi['tendencia'])): ?>
                                        <span class="flavor-kpi-trend flavor-trend-<?php echo esc_attr($kpi['tendencia']); ?>">
                                            <span class="dashicons dashicons-arrow-<?php echo $kpi['tendencia'] === 'up' ? 'up' : 'down'; ?>-alt"></span>
                                            <?php echo esc_html($kpi['cambio'] ?? ''); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </details>

            <!-- Panel: Red de Comunidades -->
            <details class="flavor-collapsible-panel" <?php echo ($paneles_estado['red'] ?? false) ? 'open' : ''; ?> data-panel="red">
                <summary class="flavor-panel-header">
                    <span class="flavor-panel-icon dashicons dashicons-networking"></span>
                    <span class="flavor-panel-title"><?php esc_html_e('Red de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-desc"><?php esc_html_e('Tu nodo, conexiones, mapa de actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-toggle dashicons dashicons-arrow-down-alt2"></span>
                </summary>
                <div class="flavor-panel-content">
                    <!-- Banner del Nodo -->
                    <div class="flavor-node-banner <?php echo !empty($estadisticas_red['nodo_local']) ? 'flavor-node-configured' : 'flavor-node-pending'; ?>">
                        <div class="flavor-node-banner-icon">
                            <span class="dashicons dashicons-networking"></span>
                        </div>
                        <div class="flavor-node-banner-content">
                            <?php if (!empty($estadisticas_red['nodo_local'])): ?>
                                <div class="flavor-node-info">
                                    <h3><?php echo esc_html($estadisticas_red['nodo_local']['nombre'] ?? __('Mi Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></h3>
                                    <div class="flavor-node-meta">
                                        <span class="flavor-node-status <?php echo esc_attr($estadisticas_red['nodo_local']['estado'] ?? 'activo'); ?>">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            <?php echo esc_html(ucfirst($estadisticas_red['nodo_local']['estado'] ?? 'activo')); ?>
                                        </span>
                                        <?php if (($estadisticas_red['nodos_conectados'] ?? 0) > 0): ?>
                                        <span class="flavor-node-connections">
                                            <span class="dashicons dashicons-groups"></span>
                                            <?php printf(esc_html__('%d nodos conectados', FLAVOR_PLATFORM_TEXT_DOMAIN), $estadisticas_red['nodos_conectados']); ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (($estadisticas_red['mensajes_sin_leer'] ?? 0) > 0): ?>
                                        <span class="flavor-node-messages">
                                            <span class="dashicons dashicons-email-alt"></span>
                                            <?php printf(esc_html__('%d mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN), $estadisticas_red['mensajes_sin_leer']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="flavor-node-setup">
                                    <h3><?php echo esc_html__('Configura tu Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                                    <p><?php echo esc_html__('Este sitio representa un nodo en la red. Configura tu identidad para conectar con otros nodos y compartir recursos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-node-banner-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-network&tab=mi-nodo')); ?>" class="button button-primary">
                                <?php echo !empty($estadisticas_red['nodo_local']) ? esc_html__('Gestionar Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Configurar Nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <?php if (!empty($estadisticas_red['nodo_local'])): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-network&tab=directorio')); ?>" class="button">
                                <?php echo esc_html__('Directorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Estadísticas de la red -->
                    <?php if (!empty($estadisticas_red)): ?>
                    <div class="flavor-red-stats">
                        <div class="flavor-red-stat">
                            <span class="flavor-red-stat-value"><?php echo esc_html($estadisticas_red['nodos_conectados'] ?? 0); ?></span>
                            <span class="flavor-red-stat-label"><?php esc_html_e('Nodos conectados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-red-stat">
                            <span class="flavor-red-stat-value"><?php echo esc_html($estadisticas_red['recursos_compartidos'] ?? 0); ?></span>
                            <span class="flavor-red-stat-label"><?php esc_html_e('Recursos compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-red-stat">
                            <span class="flavor-red-stat-value"><?php echo esc_html($estadisticas_red['usuarios_federados'] ?? 0); ?></span>
                            <span class="flavor-red-stat-label"><?php esc_html_e('Usuarios en la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Mapa de actividad (placeholder) -->
                    <div class="flavor-map-container">
                        <div id="flavor-activity-map" class="flavor-activity-map">
                            <div class="flavor-map-placeholder">
                                <span class="dashicons dashicons-location-alt"></span>
                                <p><?php esc_html_e('Mapa de actividad de la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <small><?php esc_html_e('Requiere configuración de ubicación del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </details>

            <!-- Panel: Transición Regenerativa (Gailu) -->
            <details class="flavor-collapsible-panel" <?php echo ($paneles_estado['gailu'] ?? false) ? 'open' : ''; ?> data-panel="gailu">
                <summary class="flavor-panel-header">
                    <span class="flavor-panel-icon dashicons dashicons-superhero"></span>
                    <span class="flavor-panel-title"><?php esc_html_e('Transición Regenerativa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-desc"><?php esc_html_e('Principios transformadores y capacidades regenerativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-toggle dashicons dashicons-arrow-down-alt2"></span>
                </summary>
                <div class="flavor-panel-content">
                    <?php
                    $principios_activos = $gailu_metricas['principios'] ?? [];
                    $contribuciones_activas = $gailu_metricas['contribuciones'] ?? [];
                    $principios_cubiertos = $gailu_metricas['cubiertos']['principios'] ?? 0;
                    $total_principios = $gailu_metricas['totales']['principios'] ?? 5;
                    $porcentaje_cobertura = $total_principios > 0 ? round(($principios_cubiertos / $total_principios) * 100) : 0;

                    $etiquetas_principios = [
                        'economia_local' => ['nombre' => __('Economía Local', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-store', 'color' => '#10b981'],
                        'cuidados' => ['nombre' => __('Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-heart', 'color' => '#ec4899'],
                        'gobernanza' => ['nombre' => __('Gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-groups', 'color' => '#8b5cf6'],
                        'regeneracion' => ['nombre' => __('Regeneración', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-palmtree', 'color' => '#22c55e'],
                        'aprendizaje' => ['nombre' => __('Aprendizaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-book', 'color' => '#f59e0b'],
                    ];

                    $etiquetas_contribuciones = [
                        'autonomia' => ['nombre' => __('Autonomía', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-flag', 'color' => '#3b82f6'],
                        'resiliencia' => ['nombre' => __('Resiliencia', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-shield', 'color' => '#06b6d4'],
                        'cohesion' => ['nombre' => __('Cohesión', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-networking', 'color' => '#a855f7'],
                        'impacto' => ['nombre' => __('Impacto', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => 'dashicons-chart-line', 'color' => '#ef4444'],
                    ];
                    ?>

                    <div class="flavor-regenerative-header">
                        <div class="flavor-regenerative-score-circle" style="--score: <?php echo esc_attr($porcentaje_cobertura); ?>">
                            <span class="flavor-score-value"><?php echo esc_html($porcentaje_cobertura); ?>%</span>
                            <span class="flavor-score-label"><?php esc_html_e('cobertura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="flavor-regenerative-intro">
                            <h3><?php esc_html_e('¿Cómo contribuye tu plataforma a la transición?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                            <p><?php esc_html_e('Los módulos activos cubren diferentes principios de economía social y solidaria.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        </div>
                    </div>

                    <div class="flavor-regenerative-grid">
                        <!-- Principios transformadores -->
                        <div class="flavor-regenerative-section">
                            <h4><span class="dashicons dashicons-star-filled"></span> <?php esc_html_e('Principios Transformadores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <div class="flavor-principios-grid">
                                <?php foreach ($etiquetas_principios as $clave => $datos): ?>
                                <?php
                                $modulos_principio = $principios_activos[$clave] ?? [];
                                $tiene_modulos = !empty($modulos_principio);
                                ?>
                                <div class="flavor-principio-card <?php echo $tiene_modulos ? 'activo' : 'inactivo'; ?>"
                                     style="--principio-color: <?php echo esc_attr($datos['color']); ?>"
                                     title="<?php echo $tiene_modulos ? implode(', ', $modulos_principio) : __('Sin módulos activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons <?php echo esc_attr($datos['icono']); ?>"></span>
                                    <span class="flavor-principio-nombre"><?php echo esc_html($datos['nombre']); ?></span>
                                    <?php if ($tiene_modulos): ?>
                                    <span class="flavor-principio-count"><?php echo esc_html(count($modulos_principio)); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Capacidades regenerativas -->
                        <div class="flavor-regenerative-section">
                            <h4><span class="dashicons dashicons-awards"></span> <?php esc_html_e('Capacidades Regenerativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <div class="flavor-contribuciones-list">
                                <?php
                                $configuracion = flavor_get_main_settings();
                                $modulos_activos_ids = $configuracion['active_modules'] ?? [];
                                $total_modulos_activos = max(1, count($modulos_activos_ids));
                                ?>
                                <?php foreach ($etiquetas_contribuciones as $clave => $datos): ?>
                                <?php
                                $modulos_contribucion = $contribuciones_activas[$clave] ?? [];
                                $tiene_contribucion = !empty($modulos_contribucion);
                                $porcentaje_contribucion = $tiene_contribucion ? round((count($modulos_contribucion) / $total_modulos_activos) * 100) : 0;
                                ?>
                                <div class="flavor-contribucion-bar <?php echo $tiene_contribucion ? 'activo' : 'inactivo'; ?>">
                                    <div class="flavor-contribucion-info">
                                        <span class="dashicons <?php echo esc_attr($datos['icono']); ?>" style="color: <?php echo esc_attr($datos['color']); ?>"></span>
                                        <span class="flavor-contribucion-nombre"><?php echo esc_html($datos['nombre']); ?></span>
                                    </div>
                                    <div class="flavor-contribucion-progress">
                                        <div class="flavor-contribucion-fill" style="width: <?php echo esc_attr($porcentaje_contribucion); ?>%; background: <?php echo esc_attr($datos['color']); ?>"></div>
                                    </div>
                                    <span class="flavor-contribucion-valor"><?php echo esc_html(count($modulos_contribucion)); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-regenerative-footer">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>" class="button">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php esc_html_e('Activar más capacidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    </div>
                </div>
            </details>

            <!-- Panel: Addons -->
            <?php if (!empty($addons_registrados)): ?>
            <details class="flavor-collapsible-panel" data-panel="addons">
                <summary class="flavor-panel-header">
                    <span class="flavor-panel-icon dashicons dashicons-admin-plugins"></span>
                    <span class="flavor-panel-title"><?php esc_html_e('Extensiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-panel-desc"><?php printf(esc_html__('%d addons instalados', FLAVOR_PLATFORM_TEXT_DOMAIN), count($addons_registrados)); ?></span>
                    <span class="flavor-panel-toggle dashicons dashicons-arrow-down-alt2"></span>
                </summary>
                <div class="flavor-panel-content">
                    <div class="flavor-addons-grid">
                        <?php foreach ($addons_registrados as $slug_addon => $datos_addon): ?>
                            <div class="flavor-addon-card <?php echo in_array($slug_addon, $addons_activos) ? 'active' : 'inactive'; ?>">
                                <div class="flavor-addon-icon">
                                    <span class="dashicons <?php echo esc_attr($datos_addon['icon'] ?? 'dashicons-admin-plugins'); ?>"></span>
                                </div>
                                <div class="flavor-addon-info">
                                    <span class="flavor-addon-name"><?php echo esc_html($datos_addon['name']); ?></span>
                                    <span class="flavor-addon-version">v<?php echo esc_html($datos_addon['version']); ?></span>
                                </div>
                                <span class="flavor-addon-status <?php echo in_array($slug_addon, $addons_activos) ? 'active' : 'inactive'; ?>">
                                    <?php echo in_array($slug_addon, $addons_activos) ? esc_html__('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Inactivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-addons')); ?>" class="flavor-view-all-link">
                        <?php esc_html_e('Gestionar extensiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                </div>
            </details>
            <?php endif; ?>
        </div>

    <?php endif; ?>
</div>

<style>
/* ═══════════════════════════════════════════════════════════════════════════
   DASHBOARD V2 - Estilos
   ═══════════════════════════════════════════════════════════════════════════ */

.flavor-dashboard-v2 {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.flavor-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e0e0e0;
}

.flavor-dashboard-header h1 {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    font-size: 24px;
    font-weight: 600;
}

.flavor-dashboard-greeting {
    color: #666;
    font-size: 14px;
}

/* Alertas de configuración */
.flavor-alertas-config {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-bottom: 20px;
}

.flavor-alerta {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    background: #fff;
    border-left: 4px solid;
}

.flavor-alerta-warning {
    border-color: #f59e0b;
    background: #fffbeb;
}

.flavor-alerta-info {
    border-color: #3b82f6;
    background: #eff6ff;
}

.flavor-alerta-error {
    border-color: #ef4444;
    background: #fef2f2;
}

.flavor-alerta .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.flavor-alerta-mensaje {
    flex: 1;
    font-weight: 500;
}

/* Barra de estadísticas */
.flavor-stats-bar {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.flavor-stat-card {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 20px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.flavor-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.flavor-stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--stat-color, #3b82f6);
    color: #fff;
    font-size: 24px;
}

.flavor-stat-value {
    display: block;
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.flavor-stat-label {
    display: block;
    font-size: 13px;
    color: #64748b;
}

.flavor-stat-alert {
    border: 2px solid #f59e0b;
}

.flavor-stat-alert .flavor-stat-icon {
    background: #f59e0b;
}

/* Grid del dashboard */
.flavor-dashboard-grid {
    display: grid;
    gap: 20px;
    margin-bottom: 20px;
}

.flavor-grid-1 {
    grid-template-columns: 1fr;
}

.flavor-grid-2 {
    grid-template-columns: repeat(2, 1fr);
}

.flavor-grid-3 {
    grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 1200px) {
    .flavor-grid-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 782px) {
    .flavor-grid-2,
    .flavor-grid-3 {
        grid-template-columns: 1fr;
    }
}

/* Cards */
.flavor-dashboard-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.flavor-card-primary {
    border: 2px solid #3b82f6;
}

.flavor-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
}

.flavor-card-header h2 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: #334155;
}

.flavor-card-header h2 .dashicons {
    color: #64748b;
}

.flavor-card-action {
    font-size: 13px;
    color: #3b82f6;
    text-decoration: none;
}

.flavor-card-action:hover {
    text-decoration: underline;
}

.flavor-badge-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    height: 24px;
    padding: 0 8px;
    border-radius: 12px;
    background: #ef4444;
    color: #fff;
    font-size: 12px;
    font-weight: 600;
}

.flavor-card-content {
    padding: 16px 20px;
}

/* Listas */
.flavor-tareas-list,
.flavor-grupos-list,
.flavor-solicitudes-list,
.flavor-miembros-list,
.flavor-moderacion-list,
.flavor-actividad-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.flavor-tareas-list li,
.flavor-grupos-list li,
.flavor-solicitudes-list li,
.flavor-actividad-list li {
    border-bottom: 1px solid #f1f5f9;
}

.flavor-tareas-list li:last-child,
.flavor-grupos-list li:last-child,
.flavor-solicitudes-list li:last-child,
.flavor-actividad-list li:last-child {
    border-bottom: none;
}

/* Tareas */
.flavor-tarea-item a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    color: #334155;
    text-decoration: none;
}

.flavor-tarea-item a:hover {
    color: #3b82f6;
}

.flavor-tarea-icon {
    color: #64748b;
}

.flavor-tarea-texto {
    flex: 1;
}

.flavor-tarea-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 10px;
    border-radius: 14px;
    background: #e2e8f0;
    color: #475569;
    font-size: 13px;
    font-weight: 600;
}

.flavor-tarea-alta .flavor-tarea-badge {
    background: #fef2f2;
    color: #dc2626;
}

.flavor-tarea-media .flavor-tarea-badge {
    background: #fffbeb;
    color: #d97706;
}

/* Módulos grid */
.flavor-modulos-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.flavor-modulo-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 16px 12px;
    border-radius: 10px;
    background: #f8fafc;
    color: #334155;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s;
}

.flavor-modulo-card:hover {
    background: var(--modulo-color, #3b82f6);
    color: #fff;
    transform: translateY(-2px);
}

.flavor-modulo-card .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.flavor-modulo-nombre {
    font-size: 12px;
    font-weight: 500;
    line-height: 1.3;
}

/* Accesos rápidos */
.flavor-accesos-grid,
.flavor-accesos-default {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.flavor-acceso-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 14px;
    border-radius: 8px;
    background: #f8fafc;
    color: #334155;
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s;
}

.flavor-acceso-btn:hover {
    background: var(--acceso-color, #3b82f6);
    color: #fff;
}

.flavor-acceso-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Actividad */
.flavor-actividad-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
}

.flavor-actividad-icon {
    color: #64748b;
    flex-shrink: 0;
}

.flavor-actividad-info {
    flex: 1;
    min-width: 0;
}

.flavor-actividad-titulo {
    display: block;
    font-size: 13px;
    color: #334155;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-actividad-usuario {
    display: block;
    font-size: 12px;
    color: #64748b;
}

.flavor-actividad-tiempo {
    font-size: 12px;
    color: #94a3b8;
    white-space: nowrap;
}

/* Grupos y miembros */
.flavor-grupo-link {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    color: #334155;
    text-decoration: none;
}

.flavor-grupo-link:hover {
    color: #3b82f6;
}

.flavor-grupo-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-grupo-miembros {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #64748b;
    font-size: 13px;
}

.flavor-grupo-pendientes {
    padding: 4px 10px;
    border-radius: 12px;
    background: #fef2f2;
    color: #dc2626;
    font-size: 12px;
    font-weight: 500;
}

.flavor-miembro-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
}

.flavor-miembro-item:last-child {
    border-bottom: none;
}

.flavor-miembro-item img {
    border-radius: 50%;
}

.flavor-miembro-nombre {
    display: block;
    font-weight: 500;
    color: #334155;
}

.flavor-miembro-meta {
    display: block;
    font-size: 12px;
    color: #64748b;
}

/* Solicitudes */
.flavor-solicitud-item a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    color: #334155;
    text-decoration: none;
}

.flavor-solicitud-item a:hover {
    color: #3b82f6;
}

.flavor-solicitud-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 10px;
    border-radius: 14px;
    background: #fef2f2;
    color: #dc2626;
    font-size: 13px;
    font-weight: 600;
}

/* Moderación */
.flavor-moderacion-item a {
    display: block;
    padding: 12px 0;
    color: #334155;
    text-decoration: none;
}

.flavor-moderacion-item a:hover {
    color: #3b82f6;
}

.flavor-moderacion-titulo {
    display: block;
    font-weight: 500;
    margin-bottom: 4px;
}

.flavor-moderacion-meta {
    display: block;
    font-size: 12px;
    color: #64748b;
}

/* Estado vacío */
.flavor-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 32px 20px;
    text-align: center;
    color: #64748b;
}

.flavor-empty-state .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #cbd5e1;
}

.flavor-empty-state p {
    margin: 0;
    font-size: 14px;
}

.flavor-empty-success .dashicons {
    color: #10b981;
}

.flavor-empty-success p {
    color: #059669;
}

/* ═══════════════════════════════════════════════════════════════════════════
   PANELES COLAPSABLES (Secciones Avanzadas)
   ═══════════════════════════════════════════════════════════════════════════ */

.flavor-advanced-panels {
    margin-top: 32px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-collapsible-panel {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.flavor-collapsible-panel[open] {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.flavor-panel-header {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px 20px;
    cursor: pointer;
    list-style: none;
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    border-bottom: 1px solid transparent;
    transition: all 0.2s;
}

.flavor-panel-header::-webkit-details-marker {
    display: none;
}

.flavor-panel-header:hover {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
}

.flavor-collapsible-panel[open] .flavor-panel-header {
    border-bottom-color: #e2e8f0;
}

.flavor-panel-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: #3b82f6;
    color: #fff;
    font-size: 20px;
}

.flavor-panel-title {
    font-size: 15px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-panel-desc {
    flex: 1;
    font-size: 13px;
    color: #64748b;
}

.flavor-panel-toggle {
    color: #94a3b8;
    transition: transform 0.3s;
}

.flavor-collapsible-panel[open] .flavor-panel-toggle {
    transform: rotate(180deg);
}

.flavor-panel-content {
    padding: 20px;
    background: #fff;
}

/* Iconos personalizados para cada panel */
.flavor-collapsible-panel[data-panel="graficos"] .flavor-panel-icon {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
}

.flavor-collapsible-panel[data-panel="red"] .flavor-panel-icon {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
}

.flavor-collapsible-panel[data-panel="gailu"] .flavor-panel-icon {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.flavor-collapsible-panel[data-panel="addons"] .flavor-panel-icon {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

/* ═══════════════════════════════════════════════════════════════════════════
   GRÁFICOS Y CHARTS
   ═══════════════════════════════════════════════════════════════════════════ */

.flavor-chart-container {
    position: relative;
    height: 200px;
    padding: 10px;
}

.flavor-chart-doughnut {
    height: 180px;
    max-width: 180px;
    margin: 0 auto;
}

/* KPIs */
.flavor-kpis-section {
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #e2e8f0;
}

.flavor-kpis-section h3 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px 0;
    font-size: 14px;
    font-weight: 600;
    color: #475569;
}

.flavor-kpis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 16px;
}

.flavor-kpi-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 10px;
    text-align: center;
}

.flavor-kpi-value {
    font-size: 28px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.flavor-kpi-label {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.flavor-kpi-trend {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 8px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.flavor-trend-up {
    background: #dcfce7;
    color: #16a34a;
}

.flavor-trend-down {
    background: #fef2f2;
    color: #dc2626;
}

/* ═══════════════════════════════════════════════════════════════════════════
   RED DE COMUNIDADES
   ═══════════════════════════════════════════════════════════════════════════ */

.flavor-node-banner {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.flavor-node-configured {
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border: 1px solid #a7f3d0;
}

.flavor-node-pending {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border: 1px solid #fcd34d;
}

.flavor-node-banner-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    border-radius: 16px;
    flex-shrink: 0;
}

.flavor-node-configured .flavor-node-banner-icon {
    background: #10b981;
    color: #fff;
}

.flavor-node-configured .flavor-node-banner-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.flavor-node-pending .flavor-node-banner-icon {
    background: #f59e0b;
    color: #fff;
}

.flavor-node-pending .flavor-node-banner-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.flavor-node-banner-content {
    flex: 1;
}

.flavor-node-banner-content h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-node-banner-content p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.flavor-node-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 4px;
}

.flavor-node-meta span {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #475569;
}

.flavor-node-status.activo {
    color: #059669;
}

.flavor-node-banner-actions {
    display: flex;
    gap: 10px;
    flex-shrink: 0;
}

/* Estadísticas de red */
.flavor-red-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 20px;
}

.flavor-red-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background: #f8fafc;
    border-radius: 10px;
    text-align: center;
}

.flavor-red-stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #8b5cf6;
    line-height: 1.2;
}

.flavor-red-stat-label {
    font-size: 13px;
    color: #64748b;
    margin-top: 4px;
}

/* Mapa de actividad */
.flavor-map-container {
    margin-top: 16px;
}

.flavor-activity-map {
    height: 300px;
    border-radius: 12px;
    overflow: hidden;
    background: #f1f5f9;
}

.flavor-map-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #64748b;
    text-align: center;
}

.flavor-map-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #cbd5e1;
    margin-bottom: 12px;
}

.flavor-map-placeholder p {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 500;
}

.flavor-map-placeholder small {
    font-size: 12px;
    color: #94a3b8;
}

/* ═══════════════════════════════════════════════════════════════════════════
   TRANSICIÓN REGENERATIVA (GAILU)
   ═══════════════════════════════════════════════════════════════════════════ */

.flavor-regenerative-header {
    display: flex;
    align-items: center;
    gap: 24px;
    margin-bottom: 24px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e2e8f0;
}

.flavor-regenerative-score-circle {
    position: relative;
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: conic-gradient(
        #10b981 calc(var(--score, 0) * 3.6deg),
        #e2e8f0 0
    );
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-regenerative-score-circle::before {
    content: '';
    position: absolute;
    inset: 8px;
    border-radius: 50%;
    background: #fff;
}

.flavor-score-value,
.flavor-score-label {
    position: relative;
    z-index: 1;
}

.flavor-score-value {
    font-size: 24px;
    font-weight: 700;
    color: #10b981;
    line-height: 1;
}

.flavor-score-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.flavor-regenerative-intro h3 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.flavor-regenerative-intro p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.flavor-regenerative-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 900px) {
    .flavor-regenerative-grid {
        grid-template-columns: 1fr;
    }
}

.flavor-regenerative-section h4 {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 16px 0;
    font-size: 14px;
    font-weight: 600;
    color: #475569;
}

/* Principios */
.flavor-principios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
    gap: 10px;
}

.flavor-principio-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 14px 10px;
    border-radius: 10px;
    text-align: center;
    transition: all 0.2s;
}

.flavor-principio-card.activo {
    background: var(--principio-color, #10b981);
    color: #fff;
}

.flavor-principio-card.inactivo {
    background: #f1f5f9;
    color: #94a3b8;
}

.flavor-principio-card .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.flavor-principio-nombre {
    font-size: 11px;
    font-weight: 500;
    line-height: 1.3;
}

.flavor-principio-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    background: rgba(255,255,255,0.3);
    font-size: 11px;
    font-weight: 600;
}

/* Contribuciones */
.flavor-contribuciones-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.flavor-contribucion-bar {
    display: flex;
    align-items: center;
    gap: 12px;
}

.flavor-contribucion-info {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 110px;
    flex-shrink: 0;
}

.flavor-contribucion-info .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-contribucion-nombre {
    font-size: 13px;
    color: #475569;
}

.flavor-contribucion-progress {
    flex: 1;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
}

.flavor-contribucion-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.flavor-contribucion-valor {
    width: 28px;
    text-align: right;
    font-size: 13px;
    font-weight: 600;
    color: #475569;
}

.flavor-regenerative-footer {
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    text-align: center;
}

.flavor-regenerative-footer .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* ═══════════════════════════════════════════════════════════════════════════
   ADDONS / EXTENSIONES
   ═══════════════════════════════════════════════════════════════════════════ */

.flavor-addons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 16px;
}

.flavor-addon-card {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 16px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    transition: all 0.2s;
}

.flavor-addon-card.active {
    background: #ecfdf5;
    border-color: #a7f3d0;
}

.flavor-addon-card.inactive {
    opacity: 0.7;
}

.flavor-addon-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: #e2e8f0;
    color: #64748b;
    flex-shrink: 0;
}

.flavor-addon-card.active .flavor-addon-icon {
    background: #10b981;
    color: #fff;
}

.flavor-addon-icon .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.flavor-addon-info {
    flex: 1;
    min-width: 0;
}

.flavor-addon-name {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #1e293b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-addon-version {
    display: block;
    font-size: 12px;
    color: #64748b;
}

.flavor-addon-status {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    flex-shrink: 0;
}

.flavor-addon-status.active {
    background: #dcfce7;
    color: #16a34a;
}

.flavor-addon-status.inactive {
    background: #f1f5f9;
    color: #64748b;
}

.flavor-view-all-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    margin-top: 16px;
    padding-top: 16px;
    border-top: 1px solid #e2e8f0;
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.flavor-view-all-link:hover {
    color: #1d4ed8;
}

.flavor-view-all-link .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<script>
(function() {
    'use strict';

    // ═══════════════════════════════════════════════════════════════════════
    // PANELES COLAPSABLES - Guardar estado
    // ═══════════════════════════════════════════════════════════════════════

    document.querySelectorAll('.flavor-collapsible-panel').forEach(function(panel) {
        panel.addEventListener('toggle', function() {
            const panelId = this.dataset.panel;
            const isOpen = this.open;

            // Guardar estado via AJAX
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'flavor_save_panel_state',
                    panel: panelId,
                    state: isOpen ? '1' : '0',
                    _wpnonce: '<?php echo wp_create_nonce('flavor_panel_state'); ?>'
                })
            }).catch(function(error) {
                console.warn('Error guardando estado del panel:', error);
            });
        });
    });

    // ═══════════════════════════════════════════════════════════════════════
    // GRÁFICOS - Chart.js (si está disponible)
    // ═══════════════════════════════════════════════════════════════════════

    function initCharts() {
        if (typeof Chart === 'undefined') {
            console.info('Chart.js no disponible - gráficos no inicializados');
            return;
        }

        // Datos de gráficos desde PHP
        // Estructura: {usuarios_por_semana: {etiquetas, datos}, actividad_por_modulo: {etiquetas, datos}, distribucion_roles: {etiquetas, datos}}
        const datosGraficos = <?php echo json_encode($datos_graficos ?? []); ?>;

        // Colores del tema
        const colores = {
            primario: '#3b82f6',
            secundario: '#8b5cf6',
            exito: '#10b981',
            advertencia: '#f59e0b',
            error: '#ef4444',
            gris: '#94a3b8'
        };

        // Gráfico: Usuarios nuevos (línea)
        const ctxUsers = document.getElementById('flavor-chart-users');
        const datosUsuarios = datosGraficos.usuarios_por_semana || {};
        if (ctxUsers && datosUsuarios.etiquetas) {
            new Chart(ctxUsers, {
                type: 'line',
                data: {
                    labels: datosUsuarios.etiquetas || [],
                    datasets: [{
                        label: '<?php echo esc_js(__('Usuarios nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        data: datosUsuarios.datos || [],
                        borderColor: colores.primario,
                        backgroundColor: colores.primario + '20',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Gráfico: Actividad por módulo (barras)
        const ctxModules = document.getElementById('flavor-chart-modules');
        const datosModulos = datosGraficos.actividad_por_modulo || {};
        if (ctxModules && datosModulos.etiquetas) {
            new Chart(ctxModules, {
                type: 'bar',
                data: {
                    labels: datosModulos.etiquetas || [],
                    datasets: [{
                        label: '<?php echo esc_js(__('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        data: datosModulos.datos || [],
                        backgroundColor: datosModulos.colores || [
                            colores.primario,
                            colores.secundario,
                            colores.exito,
                            colores.advertencia,
                            colores.error,
                            colores.gris
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }

        // Gráfico: Distribución de roles (doughnut)
        const ctxRoles = document.getElementById('flavor-chart-roles');
        const datosRoles = datosGraficos.distribucion_roles || {};
        if (ctxRoles && datosRoles.etiquetas) {
            new Chart(ctxRoles, {
                type: 'doughnut',
                data: {
                    labels: datosRoles.etiquetas || [],
                    datasets: [{
                        data: datosRoles.datos || [],
                        backgroundColor: datosRoles.colores || [
                            colores.primario,
                            colores.secundario,
                            colores.exito,
                            colores.advertencia,
                            colores.gris
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, padding: 8 }
                        }
                    },
                    cutout: '60%'
                }
            });
        }
    }

    // Inicializar gráficos cuando el panel de gráficos se abra
    const panelGraficos = document.querySelector('.flavor-collapsible-panel[data-panel="graficos"]');
    if (panelGraficos) {
        if (panelGraficos.open) {
            // Ya está abierto, inicializar ahora
            setTimeout(initCharts, 100);
        } else {
            // Esperar a que se abra
            panelGraficos.addEventListener('toggle', function() {
                if (this.open) {
                    setTimeout(initCharts, 100);
                }
            }, { once: true });
        }
    }

})();
</script>
