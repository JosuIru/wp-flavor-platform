<?php
/**
 * Vista Dashboard - Módulo Compostaje
 * Panel principal con estadísticas de compostaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_composteras = $wpdb->prefix . 'flavor_composteras';
$tabla_depositos_compostaje = $wpdb->prefix . 'flavor_compostaje_depositos';
$tabla_recogidas_compost = $wpdb->prefix . 'flavor_compostaje_recogidas';
$tabla_mantenimiento = $wpdb->prefix . 'flavor_compostaje_mantenimiento';

// Verificar si las tablas existen
$tabla_composteras_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_composteras)) === $tabla_composteras;
$tabla_depositos_existe = false;
$tabla_mantenimiento_existe = false;

$total_composteras = 0;
$total_depositos_mes = 0;
$total_kg_organicos_mes = 0.0;
$compost_listo = 0;
$stats_composteras = [];
$usuarios_activos_compostaje = [];
$evolucion_compostaje = [];
$mantenimiento_pendiente = [];
$composteras_atencion = [];

if ($tabla_composteras_existe) {
    $total_composteras = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_composteras WHERE estado != 'inactiva'");

    $tabla_depositos_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_depositos_compostaje)) === $tabla_depositos_compostaje;

    $total_depositos_mes = $tabla_depositos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_depositos_compostaje WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE())") : 0;
    $total_kg_organicos_mes = $tabla_depositos_existe ? (float) $wpdb->get_var("SELECT COALESCE(SUM(cantidad_kg), 0) FROM $tabla_depositos_compostaje WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE())") : 0;
    $compost_listo = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_composteras WHERE estado = 'listo_recoger'");

    $stats_composteras = $tabla_depositos_existe ? $wpdb->get_results("
        SELECT c.*, COUNT(d.id) as total_depositos, SUM(d.cantidad_kg) as total_kg_depositado
        FROM $tabla_composteras c
        LEFT JOIN $tabla_depositos_compostaje d ON c.id = d.compostera_id
        WHERE MONTH(d.fecha_deposito) = MONTH(CURRENT_DATE())
        GROUP BY c.id ORDER BY total_kg_depositado DESC LIMIT 5
    ") : [];

    $usuarios_activos_compostaje = $tabla_depositos_existe ? $wpdb->get_results("
        SELECT u.ID, u.display_name, COUNT(d.id) as total_depositos, SUM(d.cantidad_kg) as total_kg
        FROM {$wpdb->users} u
        INNER JOIN $tabla_depositos_compostaje d ON u.ID = d.usuario_id
        WHERE MONTH(d.fecha_deposito) = MONTH(CURRENT_DATE())
        GROUP BY u.ID ORDER BY total_kg DESC LIMIT 10
    ") : [];

    $evolucion_compostaje = $tabla_depositos_existe ? $wpdb->get_results("
        SELECT DATE_FORMAT(fecha_deposito, '%Y-%m') as mes, SUM(cantidad_kg) as total_kg, COUNT(*) as total_depositos
        FROM $tabla_depositos_compostaje
        WHERE fecha_deposito >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mes ORDER BY mes ASC
    ") : [];

    $tabla_mantenimiento_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_mantenimiento)) === $tabla_mantenimiento;
    $mantenimiento_pendiente = $tabla_mantenimiento_existe ? $wpdb->get_results("
        SELECT m.*, c.nombre as compostera_nombre
        FROM $tabla_mantenimiento m
        INNER JOIN $tabla_composteras c ON m.compostera_id = c.id
        WHERE m.estado = 'pendiente'
        ORDER BY m.fecha_programada ASC LIMIT 5
    ") : [];

    $composteras_atencion = $wpdb->get_results("
        SELECT * FROM $tabla_composteras
        WHERE estado IN ('llena', 'mantenimiento', 'problema')
        ORDER BY estado DESC LIMIT 5
    ") ?: [];
}

$co2_evitado = $total_kg_organicos_mes * 0.5;
$compost_producido = $total_kg_organicos_mes * 0.3;

$estados_labels = [
    'llena' => __('Compostera llena', 'flavor-chat-ia'),
    'mantenimiento' => __('En mantenimiento', 'flavor-chat-ia'),
    'problema' => __('Problema reportado', 'flavor-chat-ia'),
];

$estado_badge_classes = [
    'llena' => 'dm-badge--warning',
    'mantenimiento' => 'dm-badge--info',
    'problema' => 'dm-badge--error',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('compostaje');
    }
    ?>

    <?php if (!$tabla_composteras_existe): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos disponibles:', 'flavor-chat-ia'); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Compostaje o aún no hay composteras registradas.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-carrot"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Compostaje', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Gestiona el compostaje comunitario', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-compostaje-nueva')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nueva Compostera', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-quick-links">
        <h2 class="dm-quick-links__title">
            <span class="dashicons dashicons-admin-links"></span>
            <?php esc_html_e('Accesos Rápidos', 'flavor-chat-ia'); ?>
        </h2>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-compostaje-composteras')); ?>" class="dm-quick-links__item dm-quick-links__item--success">
                <span class="dashicons dashicons-carrot"></span>
                <span><?php esc_html_e('Composteras', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-compostaje-participantes')); ?>" class="dm-quick-links__item">
                <span class="dashicons dashicons-groups"></span>
                <span><?php esc_html_e('Participantes', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-compostaje-mantenimiento')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-admin-tools"></span>
                <span><?php esc_html_e('Mantenimiento', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('compostaje', '')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-external"></span>
                <span><?php esc_html_e('Portal', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <span class="dashicons dashicons-carrot dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_composteras); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Composteras Activas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--eco">
            <span class="dashicons dashicons-chart-line dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_kg_organicos_mes, 1); ?> kg</div>
                <div class="dm-stat-card__label"><?php esc_html_e('Orgánicos Compostados', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <span class="dashicons dashicons-clipboard dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_depositos_mes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Depósitos', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <span class="dashicons dashicons-yes dm-stat-card__icon"></span>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($compost_listo); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Compost Listo', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráfica de evolución -->
    <div class="dm-card dm-card--chart">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-chart-area"></span>
                <?php esc_html_e('Evolución del Compostaje', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__meta"><?php esc_html_e('Últimos 6 meses', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="dm-card__chart">
            <canvas id="grafica-evolucion-compostaje"></canvas>
        </div>
    </div>

    <!-- Grid de contenido -->
    <div class="dm-grid dm-grid--2">
        <!-- Composteras más activas -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-carrot"></span>
                    <?php esc_html_e('Composteras Más Activas', 'flavor-chat-ia'); ?>
                </h3>
                <span class="dm-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <?php if (!empty($stats_composteras)): ?>
            <div class="dm-item-list">
                <?php foreach ($stats_composteras as $compostera): ?>
                <div class="dm-item-list__item">
                    <div class="dm-item-list__content">
                        <strong><?php echo esc_html($compostera->nombre); ?></strong>
                        <span class="dm-item-list__muted">
                            <?php printf(__('%s kg - %s depósitos', 'flavor-chat-ia'), number_format_i18n($compostera->total_kg_depositado, 1), number_format_i18n($compostera->total_depositos)); ?>
                        </span>
                    </div>
                    <div class="dm-item-list__meta">
                        <div class="dm-progress dm-progress--sm" style="width: 80px;">
                            <div class="dm-progress__fill <?php echo ($compostera->nivel_llenado ?? 0) >= 80 ? 'dm-progress__fill--warning' : 'dm-progress__fill--success'; ?>" style="width: <?php echo min(100, ($compostera->nivel_llenado ?? 0)); ?>%;"></div>
                        </div>
                        <span class="dm-text-sm"><?php echo ($compostera->nivel_llenado ?? 0); ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-carrot"></span>
                <p><?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Usuarios más activos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-awards"></span>
                    <?php esc_html_e('Usuarios Más Activos', 'flavor-chat-ia'); ?>
                </h3>
                <span class="dm-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <?php if (!empty($usuarios_activos_compostaje)): ?>
            <div class="dm-ranking">
                <?php foreach ($usuarios_activos_compostaje as $index => $usuario): ?>
                <div class="dm-ranking__item">
                    <div class="dm-ranking__number"><?php echo $index + 1; ?></div>
                    <div class="dm-ranking__content">
                        <span class="dm-ranking__label"><?php echo esc_html($usuario->display_name); ?></span>
                        <span class="dm-ranking__value"><?php printf(__('%s kg - %s dep.', 'flavor-chat-ia'), number_format_i18n($usuario->total_kg, 1), $usuario->total_depositos); ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-groups"></span>
                <p><?php esc_html_e('No hay usuarios activos este mes', 'flavor-chat-ia'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Mantenimiento pendiente -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php esc_html_e('Mantenimiento Pendiente', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <?php if (!empty($mantenimiento_pendiente)): ?>
            <div class="dm-item-list">
                <?php foreach ($mantenimiento_pendiente as $tarea): ?>
                <div class="dm-item-list__item">
                    <div class="dm-item-list__content">
                        <strong><?php echo esc_html($tarea->compostera_nombre); ?></strong>
                        <span class="dm-item-list__muted"><?php echo esc_html($tarea->tipo_mantenimiento); ?></span>
                    </div>
                    <div class="dm-item-list__meta">
                        <span class="dm-badge dm-badge--sm dm-badge--warning">
                            <?php echo date_i18n('j M', strtotime($tarea->fecha_programada)); ?>
                        </span>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-compostaje-mantenimiento&id=' . $tarea->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                            <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-alert dm-alert--success">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('No hay tareas de mantenimiento pendientes.', 'flavor-chat-ia'); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Composteras que necesitan atención -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-warning"></span>
                    <?php esc_html_e('Requieren Atención', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <?php if (!empty($composteras_atencion)): ?>
            <div class="dm-item-list">
                <?php foreach ($composteras_atencion as $compostera): ?>
                <div class="dm-item-list__item">
                    <div class="dm-item-list__content">
                        <strong><?php echo esc_html($compostera->nombre); ?></strong>
                        <span class="dm-badge dm-badge--sm <?php echo esc_attr($estado_badge_classes[$compostera->estado] ?? 'dm-badge--secondary'); ?>">
                            <?php echo esc_html($estados_labels[$compostera->estado] ?? $compostera->estado); ?>
                        </span>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-compostaje-composteras&action=edit&id=' . $compostera->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                        <?php esc_html_e('Ver', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-alert dm-alert--success">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Todas las composteras están operativas.', 'flavor-chat-ia'); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Impacto ambiental -->
    <div class="dm-card dm-card--eco-highlight">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-palmtree"></span>
                <?php esc_html_e('Impacto Ambiental', 'flavor-chat-ia'); ?>
            </h3>
            <span class="dm-card__meta"><?php esc_html_e('Este mes', 'flavor-chat-ia'); ?></span>
        </div>
        <div class="dm-eco-stats">
            <div class="dm-eco-stat">
                <span class="dashicons dashicons-cloud"></span>
                <div class="dm-eco-stat__content">
                    <strong><?php echo number_format_i18n($co2_evitado, 1); ?> kg</strong>
                    <span><?php esc_html_e('CO₂ evitado', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="dm-eco-stat">
                <span class="dashicons dashicons-carrot"></span>
                <div class="dm-eco-stat__content">
                    <strong><?php echo number_format_i18n($compost_producido, 1); ?> kg</strong>
                    <span><?php esc_html_e('Compost producido', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
            <div class="dm-eco-stat">
                <span class="dashicons dashicons-trash"></span>
                <div class="dm-eco-stat__content">
                    <strong><?php echo number_format_i18n($total_kg_organicos_mes, 1); ?> kg</strong>
                    <span><?php esc_html_e('Residuos evitados', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    var rootStyles = getComputedStyle(document.documentElement);
    var successColor = rootStyles.getPropertyValue('--dm-success').trim() || '#10b981';

    var datosEvolucion = <?php echo wp_json_encode($evolucion_compostaje); ?>;

    var ctxEvolucion = document.getElementById('grafica-evolucion-compostaje');
    if (ctxEvolucion) {
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: datosEvolucion.map(function(d) {
                    var parts = d.mes.split('-');
                    var date = new Date(parts[0], parts[1] - 1);
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: '<?php echo esc_js(__('Kg compostados', 'flavor-chat-ia')); ?>',
                    data: datosEvolucion.map(function(d) { return parseFloat(d.total_kg); }),
                    borderColor: successColor,
                    backgroundColor: successColor + '1A',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return value + ' kg'; }
                        }
                    }
                }
            }
        });
    }
});
</script>
