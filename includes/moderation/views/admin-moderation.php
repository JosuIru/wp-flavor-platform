<?php
/**
 * Panel de administración de moderación
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

$manager = Flavor_Moderation_Manager::get_instance();
$current_tab = $tab;
$tabs = [
    'cola' => ['label' => 'Cola de moderación', 'icon' => 'list-view'],
    'reportes' => ['label' => 'Todos los reportes', 'icon' => 'flag'],
    'usuarios' => ['label' => 'Usuarios sancionados', 'icon' => 'admin-users'],
    'historial' => ['label' => 'Historial de acciones', 'icon' => 'backup'],
    'estadisticas' => ['label' => 'Estadísticas', 'icon' => 'chart-bar']
];
?>

<div class="wrap flavor-moderation-admin">
    <h1>
        <span class="dashicons dashicons-shield"></span>
        Panel de Moderación
    </h1>

    <!-- Tabs de navegación -->
    <nav class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_id => $tab_info): ?>
            <a href="<?php echo admin_url('admin.php?page=flavor-moderation&tab=' . $tab_id); ?>"
               class="nav-tab <?php echo $current_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                <span class="dashicons dashicons-<?php echo $tab_info['icon']; ?>"></span>
                <?php echo esc_html($tab_info['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="flavor-mod-content">
        <?php if ($current_tab === 'cola'): ?>
            <!-- Cola de moderación -->
            <div class="flavor-mod-section">
                <div class="flavor-mod-header">
                    <h2>Reportes pendientes</h2>
                    <div class="flavor-mod-actions">
                        <select id="filter-tipo">
                            <option value="">Todos los tipos</option>
                            <?php foreach (Flavor_Moderation_Manager::CONTENT_TYPES as $tipo => $info): ?>
                                <option value="<?php echo esc_attr($tipo); ?>"><?php echo esc_html($info['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-severidad">
                            <option value="">Todas las severidades</option>
                            <option value="critica">Crítica</option>
                            <option value="alta">Alta</option>
                            <option value="media">Media</option>
                            <option value="baja">Baja</option>
                        </select>
                        <button type="button" class="button" id="btn-refresh">
                            <span class="dashicons dashicons-update"></span>
                            Actualizar
                        </button>
                    </div>
                </div>

                <!-- Acciones masivas -->
                <div class="flavor-bulk-actions" style="display: none;">
                    <span class="selected-count">0 seleccionados</span>
                    <select id="bulk-action">
                        <option value="">Acción masiva...</option>
                        <option value="aprobar">Aprobar contenido</option>
                        <option value="ocultar">Ocultar contenido</option>
                        <option value="rechazar">Rechazar (eliminar)</option>
                    </select>
                    <button type="button" class="button" id="btn-bulk-apply">Aplicar</button>
                </div>

                <div id="reports-queue" class="flavor-reports-container">
                    <div class="flavor-loading">
                        <span class="spinner is-active"></span>
                        Cargando reportes...
                    </div>
                </div>

                <div id="pagination" class="flavor-pagination"></div>
            </div>

        <?php elseif ($current_tab === 'reportes'): ?>
            <!-- Todos los reportes -->
            <div class="flavor-mod-section">
                <div class="flavor-mod-header">
                    <h2>Historial de reportes</h2>
                    <div class="flavor-mod-filters">
                        <select id="filter-estado">
                            <option value="">Todos los estados</option>
                            <?php foreach (Flavor_Moderation_Manager::REPORT_STATUS as $estado => $label): ?>
                                <option value="<?php echo esc_attr($estado); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-motivo">
                            <option value="">Todos los motivos</option>
                            <?php foreach (Flavor_Moderation_Manager::REPORT_REASONS as $motivo => $info): ?>
                                <option value="<?php echo esc_attr($motivo); ?>"><?php echo esc_html($info['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="date" id="filter-fecha-desde" placeholder="Desde">
                        <input type="date" id="filter-fecha-hasta" placeholder="Hasta">
                        <button type="button" class="button" id="btn-filter">Filtrar</button>
                    </div>
                </div>

                <div id="all-reports" class="flavor-reports-container">
                    <div class="flavor-loading">
                        <span class="spinner is-active"></span>
                        Cargando...
                    </div>
                </div>
            </div>

        <?php elseif ($current_tab === 'usuarios'): ?>
            <!-- Usuarios sancionados -->
            <div class="flavor-mod-section">
                <div class="flavor-mod-header">
                    <h2>Usuarios sancionados</h2>
                    <div class="flavor-mod-actions">
                        <input type="text" id="search-user" placeholder="Buscar usuario...">
                        <button type="button" class="button button-primary" id="btn-add-sanction">
                            <span class="dashicons dashicons-plus"></span>
                            Nueva sanción
                        </button>
                    </div>
                </div>

                <div id="sanctioned-users" class="flavor-users-container">
                    <?php
                    global $wpdb;
                    $tabla_sanciones = $wpdb->prefix . 'flavor_moderation_sanctions';

                    $usuarios_sancionados = $wpdb->get_results(
                        "SELECT s.*, u.display_name, u.user_email
                         FROM {$tabla_sanciones} s
                         LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
                         WHERE s.estado = 'activa'
                         ORDER BY s.fecha_creacion DESC"
                    );

                    if (empty($usuarios_sancionados)):
                    ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p>No hay usuarios sancionados actualmente.</p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Tipo de sanción</th>
                                    <th>Motivo</th>
                                    <th>Expira</th>
                                    <th>Aplicada por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios_sancionados as $sancion): ?>
                                    <tr>
                                        <td>
                                            <div class="flavor-user-info">
                                                <?php echo get_avatar($sancion->usuario_id, 32); ?>
                                                <div>
                                                    <strong><?php echo esc_html($sancion->display_name); ?></strong>
                                                    <br>
                                                    <small><?php echo esc_html($sancion->user_email); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="flavor-badge <?php echo $sancion->tipo === 'ban_permanente' ? 'danger' : 'warning'; ?>">
                                                <?php
                                                $tipos = [
                                                    'ban_temporal' => 'Suspensión temporal',
                                                    'ban_permanente' => 'Suspensión permanente',
                                                    'silenciado' => 'Silenciado'
                                                ];
                                                echo esc_html($tipos[$sancion->tipo] ?? $sancion->tipo);
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html(wp_trim_words($sancion->motivo, 10)); ?></td>
                                        <td>
                                            <?php if ($sancion->fecha_expiracion): ?>
                                                <?php echo date_i18n('d/m/Y H:i', strtotime($sancion->fecha_expiracion)); ?>
                                            <?php else: ?>
                                                <span class="flavor-text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $mod = get_userdata($sancion->aplicada_por);
                                            echo $mod ? esc_html($mod->display_name) : 'Sistema';
                                            ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button button-small btn-view-history"
                                                    data-usuario-id="<?php echo esc_attr($sancion->usuario_id); ?>">
                                                Ver historial
                                            </button>
                                            <button type="button" class="button button-small btn-remove-sanction"
                                                    data-usuario-id="<?php echo esc_attr($sancion->usuario_id); ?>">
                                                Levantar
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($current_tab === 'historial'): ?>
            <!-- Historial de acciones -->
            <div class="flavor-mod-section">
                <div class="flavor-mod-header">
                    <h2>Historial de acciones de moderación</h2>
                    <div class="flavor-mod-filters">
                        <select id="filter-moderador">
                            <option value="">Todos los moderadores</option>
                            <?php
                            $moderadores = get_users(['role__in' => ['administrator', 'editor']]);
                            foreach ($moderadores as $mod):
                            ?>
                                <option value="<?php echo esc_attr($mod->ID); ?>">
                                    <?php echo esc_html($mod->display_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select id="filter-tipo-accion">
                            <option value="">Todas las acciones</option>
                            <?php foreach (Flavor_Moderation_Manager::ACTION_TYPES as $tipo => $info): ?>
                                <option value="<?php echo esc_attr($tipo); ?>"><?php echo esc_html($info['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" class="button" id="btn-filter-history">Filtrar</button>
                    </div>
                </div>

                <div id="action-history" class="flavor-history-container">
                    <?php
                    global $wpdb;
                    $tabla_acciones = $wpdb->prefix . 'flavor_moderation_actions';

                    $acciones = $wpdb->get_results(
                        "SELECT a.*, u_mod.display_name as moderador_nombre, u_afec.display_name as afectado_nombre
                         FROM {$tabla_acciones} a
                         LEFT JOIN {$wpdb->users} u_mod ON a.moderador_id = u_mod.ID
                         LEFT JOIN {$wpdb->users} u_afec ON a.usuario_afectado = u_afec.ID
                         ORDER BY a.fecha_creacion DESC
                         LIMIT 100"
                    );

                    if (empty($acciones)):
                    ?>
                        <div class="flavor-empty-state">
                            <span class="dashicons dashicons-backup"></span>
                            <p>No hay acciones de moderación registradas.</p>
                        </div>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Fecha</th>
                                    <th style="width: 120px;">Acción</th>
                                    <th>Contenido</th>
                                    <th>Usuario afectado</th>
                                    <th>Moderador</th>
                                    <th>Notas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($acciones as $accion): ?>
                                    <?php
                                    $accion_info = Flavor_Moderation_Manager::ACTION_TYPES[$accion->tipo_accion] ?? null;
                                    $tipo_info = Flavor_Moderation_Manager::CONTENT_TYPES[$accion->tipo_contenido] ?? null;
                                    ?>
                                    <tr>
                                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($accion->fecha_creacion)); ?></td>
                                        <td>
                                            <?php if ($accion_info): ?>
                                                <span class="flavor-badge <?php echo $accion_info['color']; ?>">
                                                    <span class="dashicons dashicons-<?php echo $accion_info['icon']; ?>"></span>
                                                    <?php echo esc_html($accion_info['label']); ?>
                                                </span>
                                            <?php else: ?>
                                                <?php echo esc_html($accion->tipo_accion); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($tipo_info): ?>
                                                <span class="dashicons dashicons-<?php echo $tipo_info['icon']; ?>"></span>
                                            <?php endif; ?>
                                            <?php echo esc_html($tipo_info['label'] ?? $accion->tipo_contenido); ?>
                                            #<?php echo esc_html($accion->contenido_id); ?>
                                        </td>
                                        <td><?php echo esc_html($accion->afectado_nombre ?? 'N/A'); ?></td>
                                        <td><?php echo esc_html($accion->moderador_nombre ?? 'Sistema'); ?></td>
                                        <td><?php echo esc_html(wp_trim_words($accion->notas, 10)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ($current_tab === 'estadisticas'): ?>
            <!-- Estadísticas -->
            <div class="flavor-mod-section">
                <div class="flavor-mod-header">
                    <h2>Estadísticas de moderación</h2>
                    <div class="flavor-mod-actions">
                        <select id="stats-periodo">
                            <option value="dia">Hoy</option>
                            <option value="semana" selected>Última semana</option>
                            <option value="mes">Último mes</option>
                            <option value="ano">Último año</option>
                        </select>
                        <button type="button" class="button" id="btn-refresh-stats">
                            <span class="dashicons dashicons-update"></span>
                            Actualizar
                        </button>
                    </div>
                </div>

                <!-- Resumen de métricas -->
                <div class="flavor-stats-grid" id="stats-summary">
                    <div class="flavor-stat-card">
                        <span class="dashicons dashicons-flag"></span>
                        <div class="stat-content">
                            <span class="stat-value" id="stat-total">-</span>
                            <span class="stat-label">Total reportes</span>
                        </div>
                    </div>
                    <div class="flavor-stat-card warning">
                        <span class="dashicons dashicons-clock"></span>
                        <div class="stat-content">
                            <span class="stat-value" id="stat-pendientes">-</span>
                            <span class="stat-label">Pendientes</span>
                        </div>
                    </div>
                    <div class="flavor-stat-card success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <div class="stat-content">
                            <span class="stat-value" id="stat-resueltos">-</span>
                            <span class="stat-label">Resueltos</span>
                        </div>
                    </div>
                    <div class="flavor-stat-card danger">
                        <span class="dashicons dashicons-warning"></span>
                        <div class="stat-content">
                            <span class="stat-value" id="stat-criticos">-</span>
                            <span class="stat-label">Críticos</span>
                        </div>
                    </div>
                    <div class="flavor-stat-card info">
                        <span class="dashicons dashicons-dashboard"></span>
                        <div class="stat-content">
                            <span class="stat-value" id="stat-tiempo">-</span>
                            <span class="stat-label">Tiempo promedio (h)</span>
                        </div>
                    </div>
                    <div class="flavor-stat-card">
                        <span class="dashicons dashicons-admin-users"></span>
                        <div class="stat-content">
                            <span class="stat-value" id="stat-sanciones">-</span>
                            <span class="stat-label">Sanciones activas</span>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="flavor-charts-grid">
                    <div class="flavor-chart-card">
                        <h3>Tendencia de reportes</h3>
                        <canvas id="chart-tendencia"></canvas>
                    </div>
                    <div class="flavor-chart-card">
                        <h3>Reportes por tipo</h3>
                        <canvas id="chart-tipos"></canvas>
                    </div>
                    <div class="flavor-chart-card">
                        <h3>Reportes por motivo</h3>
                        <canvas id="chart-motivos"></canvas>
                    </div>
                    <div class="flavor-chart-card">
                        <h3>Actividad de moderadores</h3>
                        <canvas id="chart-moderadores"></canvas>
                    </div>
                </div>

                <!-- Usuarios más reportados -->
                <div class="flavor-table-card">
                    <h3>Usuarios más reportados</h3>
                    <table class="wp-list-table widefat" id="table-usuarios-reportados">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Total reportes</th>
                                <th>Confirmados</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="flavor-loading">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal: Ver historial de usuario -->
<div id="modal-user-history" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content flavor-modal-large">
        <div class="flavor-modal-header">
            <h3>Historial de moderación</h3>
            <button type="button" class="flavor-modal-close">&times;</button>
        </div>
        <div class="flavor-modal-body" id="user-history-content">
            <div class="flavor-loading">
                <span class="spinner is-active"></span>
                Cargando historial...
            </div>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-close-history">Cerrar</button>
        </div>
    </div>
</div>

<!-- Modal: Procesar reporte -->
<div id="modal-process-report" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h3>Procesar reporte</h3>
            <button type="button" class="flavor-modal-close">&times;</button>
        </div>
        <div class="flavor-modal-body">
            <input type="hidden" id="process-report-id">
            <input type="hidden" id="process-report-autor">

            <div id="report-preview" class="flavor-report-preview"></div>

            <div class="flavor-form-group">
                <label>Acción a tomar:</label>
                <div class="flavor-action-buttons">
                    <button type="button" class="button flavor-action-btn" data-action="aprobar">
                        <span class="dashicons dashicons-yes-alt"></span> Aprobar
                    </button>
                    <button type="button" class="button flavor-action-btn" data-action="ocultar">
                        <span class="dashicons dashicons-hidden"></span> Ocultar
                    </button>
                    <button type="button" class="button flavor-action-btn danger" data-action="rechazar">
                        <span class="dashicons dashicons-no-alt"></span> Eliminar
                    </button>
                </div>
            </div>

            <div class="flavor-form-group">
                <label>Acción sobre el usuario:</label>
                <div class="flavor-action-buttons">
                    <button type="button" class="button flavor-action-btn" data-action="warning">
                        <span class="dashicons dashicons-warning"></span> Advertencia
                    </button>
                    <button type="button" class="button flavor-action-btn" data-action="silenciar">
                        <span class="dashicons dashicons-controls-volumeoff"></span> Silenciar
                    </button>
                    <button type="button" class="button flavor-action-btn danger" data-action="ban_temporal">
                        <span class="dashicons dashicons-clock"></span> Suspender
                    </button>
                </div>
            </div>

            <div id="action-options" style="display: none;">
                <div class="flavor-form-group" id="duration-group" style="display: none;">
                    <label for="action-duration">Duración (días):</label>
                    <input type="number" id="action-duration" value="7" min="1" max="365">
                </div>

                <div class="flavor-form-group" id="message-group" style="display: none;">
                    <label for="action-message">Mensaje al usuario:</label>
                    <textarea id="action-message" rows="3" placeholder="Escribe un mensaje para el usuario..."></textarea>
                </div>
            </div>

            <div class="flavor-form-group">
                <label for="mod-notes">Notas del moderador (internas):</label>
                <textarea id="mod-notes" rows="2" placeholder="Notas internas..."></textarea>
            </div>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancel-process">Cancelar</button>
            <button type="button" class="button button-primary" id="btn-confirm-process" disabled>
                Aplicar acción
            </button>
        </div>
    </div>
</div>

<!-- Modal: Nueva sanción -->
<div id="modal-new-sanction" class="flavor-modal" style="display: none;">
    <div class="flavor-modal-overlay"></div>
    <div class="flavor-modal-content">
        <div class="flavor-modal-header">
            <h3>Nueva sanción</h3>
            <button type="button" class="flavor-modal-close">&times;</button>
        </div>
        <div class="flavor-modal-body">
            <div class="flavor-form-group">
                <label for="sanction-user">Usuario:</label>
                <input type="text" id="sanction-user-search" placeholder="Buscar usuario por nombre o email...">
                <input type="hidden" id="sanction-user-id">
                <div id="user-search-results"></div>
            </div>

            <div class="flavor-form-group">
                <label for="sanction-type">Tipo de sanción:</label>
                <select id="sanction-type">
                    <option value="warning">Advertencia</option>
                    <option value="silenciado">Silenciar</option>
                    <option value="ban_temporal">Suspensión temporal</option>
                    <option value="ban_permanente">Suspensión permanente</option>
                </select>
            </div>

            <div class="flavor-form-group" id="sanction-duration-group">
                <label for="sanction-duration">Duración (días):</label>
                <input type="number" id="sanction-duration" value="7" min="1" max="365">
            </div>

            <div class="flavor-form-group">
                <label for="sanction-reason">Motivo:</label>
                <textarea id="sanction-reason" rows="3" placeholder="Motivo de la sanción..."></textarea>
            </div>
        </div>
        <div class="flavor-modal-footer">
            <button type="button" class="button" id="btn-cancel-sanction">Cancelar</button>
            <button type="button" class="button button-primary" id="btn-apply-sanction">
                Aplicar sanción
            </button>
        </div>
    </div>
</div>

<!-- Template: Reporte individual -->
<script type="text/template" id="tmpl-report-item">
    <div class="flavor-report-item {{ data.severidad }}" data-report-id="{{ data.id }}">
        <div class="report-checkbox">
            <input type="checkbox" name="report_ids[]" value="{{ data.id }}">
        </div>
        <div class="report-content">
            <div class="report-header">
                <span class="report-type">
                    <span class="dashicons dashicons-{{ data.tipo_info.icon }}"></span>
                    {{ data.tipo_info.label }}
                </span>
                <span class="report-severity severity-{{ data.severidad }}">{{ data.severidad }}</span>
                <span class="report-date">{{ data.fecha_creacion }}</span>
            </div>
            <div class="report-preview">
                <# if (data.contenido_preview.existe) { #>
                    <p>{{ data.contenido_preview.preview }}</p>
                <# } else { #>
                    <p class="flavor-text-muted"><em>{{ data.contenido_preview.preview }}</em></p>
                <# } #>
            </div>
            <div class="report-meta">
                <span class="report-reason">
                    <strong>Motivo:</strong> {{ data.motivo_info.label }}
                </span>
                <span class="report-author">
                    <strong>Autor:</strong> {{ data.autor_nombre || 'Desconocido' }}
                </span>
                <span class="report-reporter">
                    <strong>Reportado por:</strong> {{ data.reportado_por_nombre }}
                </span>
            </div>
            <# if (data.descripcion) { #>
                <div class="report-description">
                    <strong>Descripción:</strong> {{ data.descripcion }}
                </div>
            <# } #>
        </div>
        <div class="report-actions">
            <button type="button" class="button button-small btn-process-report"
                    data-report-id="{{ data.id }}"
                    data-autor-id="{{ data.autor_contenido_id }}">
                Procesar
            </button>
            <button type="button" class="button button-small btn-quick-approve" data-report-id="{{ data.id }}">
                <span class="dashicons dashicons-yes"></span>
            </button>
            <button type="button" class="button button-small btn-quick-hide" data-report-id="{{ data.id }}">
                <span class="dashicons dashicons-hidden"></span>
            </button>
            <button type="button" class="button button-small btn-quick-reject" data-report-id="{{ data.id }}">
                <span class="dashicons dashicons-no"></span>
            </button>
        </div>
    </div>
</script>
