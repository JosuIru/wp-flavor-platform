<?php
/**
 * Dashboard de Colectivos - Vista Admin
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Tablas del módulo
$tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
$tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
$tabla_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
$tabla_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

// Verificar si las tablas existen
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_colectivos}'") === $tabla_colectivos;
$tablas_disponibles = $tabla_existe;

$total_colectivos = 0;
$activos = 0;
$total_miembros = 0;
$proyectos_activos = 0;
$total_proyectos = 0;
$asambleas_programadas = 0;
$sin_actividad = 0;
$por_tipo = [];
$colectivos_activos = [];
$proximas_asambleas = [];
$proyectos_recientes = [];

if ($tabla_existe) {
    $total_colectivos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_colectivos}");
    $activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_colectivos} WHERE estado = 'activo'");

    // Miembros
    $tabla_miembros_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_miembros}'") === $tabla_miembros;
    $total_miembros = $tabla_miembros_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_miembros} WHERE estado = 'activo'") : 0;

    // Proyectos
    $tabla_proyectos_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_proyectos}'") === $tabla_proyectos;
    $proyectos_activos = $tabla_proyectos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos} WHERE estado = 'activo'") : 0;
    $total_proyectos = $tabla_proyectos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_proyectos}") : 0;

    // Asambleas
    $tabla_asambleas_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_asambleas}'") === $tabla_asambleas;
    $asambleas_programadas = $tabla_asambleas_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_asambleas} WHERE fecha >= CURDATE() AND estado != 'cancelada'") : 0;

    // Por tipo de colectivo
    $por_tipo = $wpdb->get_results("
        SELECT tipo, COUNT(*) as total
        FROM {$tabla_colectivos}
        GROUP BY tipo
        ORDER BY total DESC
    ", ARRAY_A) ?: [];

    // Colectivos más activos (por número de miembros)
    $colectivos_activos = $tabla_miembros_existe ? $wpdb->get_results("
        SELECT c.id, c.nombre, c.tipo, c.estado, COUNT(m.id) as num_miembros
        FROM {$tabla_colectivos} c
        LEFT JOIN {$tabla_miembros} m ON c.id = m.colectivo_id AND m.estado = 'activo'
        WHERE c.estado = 'activo'
        GROUP BY c.id
        ORDER BY num_miembros DESC
        LIMIT 5
    ", ARRAY_A) : $wpdb->get_results("
        SELECT id, nombre, tipo, estado, 0 as num_miembros
        FROM {$tabla_colectivos}
        WHERE estado = 'activo'
        ORDER BY created_at DESC
        LIMIT 5
    ", ARRAY_A);

    // Próximas asambleas
    $proximas_asambleas = $tabla_asambleas_existe ? $wpdb->get_results("
        SELECT a.id, a.titulo, a.fecha, DATE_FORMAT(a.fecha, '%H:%i') as hora, a.lugar, c.nombre as colectivo_nombre, c.id as colectivo_id
        FROM {$tabla_asambleas} a
        LEFT JOIN {$tabla_colectivos} c ON a.colectivo_id = c.id
        WHERE a.fecha >= CURDATE()
        AND a.estado != 'cancelada'
        ORDER BY a.fecha ASC
        LIMIT 5
    ", ARRAY_A) : [];

    // Proyectos recientes
    $proyectos_recientes = $tabla_proyectos_existe ? $wpdb->get_results("
        SELECT p.id, p.titulo as nombre, p.estado, p.progreso, c.nombre as colectivo_nombre
        FROM {$tabla_proyectos} p
        LEFT JOIN {$tabla_colectivos} c ON p.colectivo_id = c.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ", ARRAY_A) : [];

    // Colectivos sin actividad reciente (sin asambleas en 60 días)
    $sin_actividad = $tabla_asambleas_existe ? (int) $wpdb->get_var("
        SELECT COUNT(*) FROM {$tabla_colectivos} c
        WHERE c.estado = 'activo'
        AND NOT EXISTS (
            SELECT 1 FROM {$tabla_asambleas} a
            WHERE a.colectivo_id = c.id
            AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        )
    ") : 0;

}
// Labels y colores por tipo
$tipos_labels = [
    'vecinal' => 'Vecinal',
    'cultural' => 'Cultural',
    'ecologista' => 'Ecologista',
    'deportivo' => 'Deportivo',
    'social' => 'Social',
    'educativo' => 'Educativo',
    'otro' => 'Otro',
];

$tipos_colores = [
    'vecinal' => '#3b82f6',
    'cultural' => '#8b5cf6',
    'ecologista' => '#10b981',
    'deportivo' => '#f59e0b',
    'social' => '#ec4899',
    'educativo' => '#06b6d4',
    'otro' => '#6b7280',
];

$estados_proyecto = [
    'planificado' => ['label' => 'Planificado', 'class' => 'dm-badge--info'],
    'activo' => ['label' => 'Activo', 'class' => 'dm-badge--success'],
    'pausado' => ['label' => 'Pausado', 'class' => 'dm-badge--warning'],
    'completado' => ['label' => 'Completado', 'class' => 'dm-badge--secondary'],
];
?>

<div class="wrap dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('colectivos');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Colectivos o aún no hay colectivos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-groups"></span> Colectivos
            </h1>
            <p class="dm-header__description">Gestiona colectivos, asociaciones, sus proyectos y asambleas</p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-nuevo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> Nuevo Colectivo
            </a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if ($sin_actividad > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <strong>Atención:</strong> Hay <strong><?php echo $sin_actividad; ?></strong> colectivo(s) sin actividad en los últimos 60 días.
        <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-listado&filtro=inactivos')); ?>">Revisar colectivos →</a>
    </div>
    <?php endif; ?>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format($total_colectivos); ?></div>
                <div class="dm-stat-card__label">Total Colectivos</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format($activos); ?></div>
                <div class="dm-stat-card__label">Activos</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format($total_miembros); ?></div>
                <div class="dm-stat-card__label">Miembros Totales</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format($proyectos_activos); ?></div>
                <div class="dm-stat-card__label">Proyectos Activos</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format($asambleas_programadas); ?></div>
                <div class="dm-stat-card__label">Asambleas Prog.</div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clipboard"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format($total_proyectos); ?></div>
                <div class="dm-stat-card__label">Total Proyectos</div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-card">
        <h2 class="dm-card__title">
            <span class="dashicons dashicons-admin-links"></span> Accesos Rápidos
        </h2>
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-listado')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-groups dm-action-card__icon"></span>
                <span class="dm-action-card__label">Todos los colectivos</span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-nuevo')); ?>" class="dm-action-card dm-action-card--success">
                <span class="dashicons dashicons-plus-alt dm-action-card__icon"></span>
                <span class="dm-action-card__label">Nuevo colectivo</span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-proyectos')); ?>" class="dm-action-card dm-action-card--warning">
                <span class="dashicons dashicons-portfolio dm-action-card__icon"></span>
                <span class="dm-action-card__label">Proyectos</span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-asambleas')); ?>" class="dm-action-card dm-action-card--pink">
                <span class="dashicons dashicons-calendar-alt dm-action-card__icon"></span>
                <span class="dm-action-card__label">Asambleas</span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-miembros')); ?>" class="dm-action-card dm-action-card--purple">
                <span class="dashicons dashicons-admin-users dm-action-card__icon"></span>
                <span class="dm-action-card__label">Miembros</span>
            </a>
            <a href="<?php echo esc_url(home_url('/mi-portal/colectivos/')); ?>" class="dm-action-card" target="_blank">
                <span class="dashicons dashicons-external dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Portal público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-pie"></span> Distribución por Tipo
            </h3>
            <div class="dm-chart-container">
                <canvas id="chart-tipos"></canvas>
            </div>
        </div>

        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span> Colectivos por Miembros
            </h3>
            <div class="dm-chart-container">
                <canvas id="chart-miembros"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Próximas asambleas -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-calendar-alt"></span> Próximas Asambleas
            </h3>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th>Asamblea</th>
                        <th>Fecha</th>
                        <th>Lugar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proximas_asambleas)): ?>
                        <?php foreach ($proximas_asambleas as $asamblea): ?>
                        <?php
                        $dias_hasta = floor((strtotime($asamblea['fecha']) - time()) / 86400);
                        $clase_urgencia = $dias_hasta <= 2 ? 'dm-badge--error' : ($dias_hasta <= 5 ? 'dm-badge--warning' : 'dm-badge--success');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($asamblea['titulo']); ?></strong>
                                <div class="dm-table__subtitle">
                                    <?php echo esc_html($asamblea['colectivo_nombre']); ?> &bull; <?php echo esc_html($asamblea['hora']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo $clase_urgencia; ?>">
                                    <?php echo esc_html(date_i18n('j M', strtotime($asamblea['fecha']))); ?>
                                </span>
                            </td>
                            <td class="dm-table__muted">
                                <?php echo esc_html(wp_trim_words($asamblea['lugar'] ?? 'Por definir', 3)); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="dm-table__empty">
                                No hay asambleas programadas
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-asambleas')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    Ver todas las asambleas
                </a>
            </div>
        </div>

        <!-- Proyectos recientes -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-portfolio"></span> Proyectos Recientes
            </h3>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th>Proyecto</th>
                        <th>Estado</th>
                        <th>Progreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($proyectos_recientes)): ?>
                        <?php foreach ($proyectos_recientes as $proyecto): ?>
                        <?php
                        $estado_info = $estados_proyecto[$proyecto['estado']] ?? ['label' => $proyecto['estado'], 'class' => 'dm-badge--secondary'];
                        $progreso = (int) ($proyecto['progreso'] ?? 0);
                        $clase_progreso = $progreso >= 80 ? 'dm-progress--success' : ($progreso >= 40 ? 'dm-progress--primary' : 'dm-progress--warning');
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html(wp_trim_words($proyecto['nombre'], 4)); ?></strong>
                                <div class="dm-table__subtitle">
                                    <?php echo esc_html($proyecto['colectivo_nombre']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($estado_info['class']); ?>">
                                    <?php echo esc_html($estado_info['label']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="dm-progress-wrapper">
                                    <div class="dm-progress <?php echo $clase_progreso; ?>">
                                        <div class="dm-progress__bar" style="width: <?php echo $progreso; ?>%;"></div>
                                    </div>
                                    <span class="dm-progress__label"><?php echo $progreso; ?>%</span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="dm-table__empty">
                                No hay proyectos registrados
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos-proyectos')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    Ver todos los proyectos
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos
    const tiposData = <?php echo json_encode(array_map(function($t) use ($tipos_labels, $tipos_colores) {
        return [
            'label' => $tipos_labels[$t['tipo']] ?? $t['tipo'],
            'value' => (int) $t['total'],
            'color' => $tipos_colores[$t['tipo']] ?? '#6b7280'
        ];
    }, $por_tipo)); ?>;

    const miembrosData = <?php echo json_encode(array_map(function($c) use ($tipos_colores) {
        return [
            'label' => $c['nombre'],
            'value' => (int) $c['num_miembros'],
            'color' => $tipos_colores[$c['tipo']] ?? '#3b82f6'
        ];
    }, $colectivos_activos)); ?>;

    // Gráfico por tipo
    new Chart(document.getElementById('chart-tipos'), {
        type: 'doughnut',
        data: {
            labels: tiposData.map(t => t.label),
            datasets: [{
                data: tiposData.map(t => t.value),
                backgroundColor: tiposData.map(t => t.color),
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, usePointStyle: true }
                }
            }
        }
    });

    // Gráfico por miembros
    new Chart(document.getElementById('chart-miembros'), {
        type: 'bar',
        data: {
            labels: miembrosData.map(m => m.label.substring(0, 20) + (m.label.length > 20 ? '...' : '')),
            datasets: [{
                data: miembrosData.map(m => m.value),
                backgroundColor: miembrosData.map(m => m.color),
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    title: { display: true, text: 'Miembros' }
                }
            }
        }
    });
});
</script>
