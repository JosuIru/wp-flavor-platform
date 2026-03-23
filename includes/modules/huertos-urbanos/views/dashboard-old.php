<?php
/**
 * Dashboard de Huertos Urbanos - Vista Admin
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) exit;

global $wpdb;

// Tablas del módulo
$tabla_huertos = $wpdb->prefix . 'flavor_huertos';
$tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
$tabla_huertanos = $wpdb->prefix . 'flavor_huertos_huertanos';
$tabla_cosechas = $wpdb->prefix . 'flavor_huertos_cosechas';
$tabla_actividades = $wpdb->prefix . 'flavor_huertos_actividades';

// Verificar si las tablas existen
$tabla_huertos_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_huertos}'") === $tabla_huertos;
$tabla_parcelas_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_parcelas}'") === $tabla_parcelas;

// Inicializar variables
$total_huertos = 0;
$huertos_activos = 0;
$total_parcelas = 0;
$parcelas_ocupadas = 0;
$parcelas_disponibles = 0;
$lista_espera = 0;
$total_huertanos = 0;
$cosechas_anio = 0;
$kg_cosechados = 0;
$actividades_proximas = 0;
$solicitudes_pendientes = 0;
$por_cultivo = [];
$huertos_recientes = [];
$lista_actividades = [];
$tablas_disponibles = $tabla_huertos_existe;

if ($tabla_huertos_existe) {
    $total_huertos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_huertos}");
    $huertos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_huertos} WHERE estado = 'activo'");

    if ($tabla_parcelas_existe) {
        $total_parcelas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_parcelas}");
        $parcelas_ocupadas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_parcelas} WHERE estado = 'ocupada'");
        $parcelas_disponibles = $total_parcelas - $parcelas_ocupadas;
        $lista_espera = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_parcelas} WHERE estado = 'lista_espera'");
        $solicitudes_pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_parcelas} WHERE estado = 'pendiente_asignacion'");
    }

    $tabla_huertanos_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_huertanos}'") === $tabla_huertanos;
    $total_huertanos = $tabla_huertanos_existe ? (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_huertanos} WHERE estado = 'activo'") : 0;

    $tabla_cosechas_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_cosechas}'") === $tabla_cosechas;
    if ($tabla_cosechas_existe) {
        $cosechas_anio = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_cosechas} WHERE YEAR(fecha_cosecha) = YEAR(CURDATE())");
        $kg_cosechados = (float) $wpdb->get_var("SELECT COALESCE(SUM(cantidad_kg), 0) FROM {$tabla_cosechas} WHERE YEAR(fecha_cosecha) = YEAR(CURDATE())");
        $por_cultivo = $wpdb->get_results("
            SELECT tipo_cultivo, SUM(cantidad_kg) as total_kg
            FROM {$tabla_cosechas}
            WHERE YEAR(fecha_cosecha) = YEAR(CURDATE())
            GROUP BY tipo_cultivo
            ORDER BY total_kg DESC
            LIMIT 6
        ", ARRAY_A) ?: [];
    }

    $tabla_actividades_existe = $wpdb->get_var("SHOW TABLES LIKE '{$tabla_actividades}'") === $tabla_actividades;
    if ($tabla_actividades_existe) {
        $actividades_proximas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_actividades} WHERE fecha >= CURDATE() AND estado != 'cancelada'");
        $lista_actividades = $wpdb->get_results("
            SELECT a.id, a.titulo, a.tipo, a.fecha, a.hora, h.nombre as huerto_nombre
            FROM {$tabla_actividades} a
            LEFT JOIN {$tabla_huertos} h ON a.huerto_id = h.id
            WHERE a.fecha >= CURDATE() AND a.estado != 'cancelada'
            ORDER BY a.fecha ASC, a.hora ASC
            LIMIT 5
        ", ARRAY_A) ?: [];
    }

    $huertos_recientes = $wpdb->get_results("
        SELECT id, nombre, ubicacion, estado, capacidad_parcelas, created_at
        FROM {$tabla_huertos}
        ORDER BY created_at DESC
        LIMIT 5
    ", ARRAY_A) ?: [];
}

// Estados y tipos
$estados_huerto = [
    'activo' => 'dm-badge--success',
    'inactivo' => 'dm-badge--secondary',
    'en_desarrollo' => 'dm-badge--warning',
];

$tipos_actividad = [
    'taller' => 'dm-badge--primary',
    'jornada' => 'dm-badge--success',
    'asamblea' => 'dm-badge--warning',
    'evento' => 'dm-badge--purple',
    'formacion' => 'dm-badge--pink',
];

$tasa_ocupacion = $total_parcelas > 0 ? round(($parcelas_ocupadas / $total_parcelas) * 100, 1) : 0;
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('huertos_urbanos');
    }
    ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-carrot"></span>
            <h1><?php esc_html_e('Huertos Urbanos', 'flavor-chat-ia'); ?></h1>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas&action=nueva')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Huerto', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <?php if (!$tablas_disponibles) : ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info-outline dm-alert__icon"></span>
        <div class="dm-alert__content">
            <strong><?php esc_html_e('Sin datos disponibles', 'flavor-chat-ia'); ?>:</strong>
            <?php esc_html_e('Faltan tablas del módulo Huertos Urbanos o aún no hay huertos registrados.', 'flavor-chat-ia'); ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($solicitudes_pendientes > 0) : ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-info-outline dm-alert__icon"></span>
        <div class="dm-alert__content">
            <strong><?php esc_html_e('Atención', 'flavor-chat-ia'); ?>:</strong>
            <?php printf(esc_html__('Hay %s solicitud(es) de parcela pendiente(s) de asignar.', 'flavor-chat-ia'), '<strong>' . $solicitudes_pendientes . '</strong>'); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas&estado=pendiente')); ?>">
                <?php esc_html_e('Gestionar solicitudes', 'flavor-chat-ia'); ?> →
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="dm-stats-grid dm-stats-grid--3">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($huertos_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Huertos Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-grid-view"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_parcelas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Total Parcelas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($parcelas_ocupadas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Parcelas Ocupadas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_huertanos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Huertanos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--pink">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-carrot"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($kg_cosechados, 0); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Kg Cosechados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($lista_espera); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Lista de Espera', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-listado')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-home"></span>
            <span><?php esc_html_e('Todos los huertos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-parcelas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-grid-view"></span>
            <span><?php esc_html_e('Gestionar parcelas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-huertanos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-groups"></span>
            <span><?php esc_html_e('Huertanos', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-cosechas')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-carrot"></span>
            <span><?php esc_html_e('Registro de cosechas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-actividades')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-calendar-alt"></span>
            <span><?php esc_html_e('Actividades', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('huertos_urbanos', '')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Ocupación de Parcelas', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-progress-wrapper">
                <div class="dm-progress <?php echo $tasa_ocupacion >= 90 ? 'dm-progress--error' : ($tasa_ocupacion >= 70 ? 'dm-progress--warning' : 'dm-progress--success'); ?>">
                    <div class="dm-progress__bar" style="width: <?php echo min($tasa_ocupacion, 100); ?>%;"></div>
                </div>
                <span class="dm-progress__label"><?php echo $tasa_ocupacion; ?>%</span>
            </div>
            <div class="dm-focus-list" style="margin-top: 16px;">
                <div class="dm-focus-list__item dm-focus-list__item--success">
                    <span class="dm-focus-list__label"><?php esc_html_e('Disponibles', 'flavor-chat-ia'); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($parcelas_disponibles); ?></span>
                </div>
                <div class="dm-focus-list__item dm-focus-list__item--warning">
                    <span class="dm-focus-list__label"><?php esc_html_e('Ocupadas', 'flavor-chat-ia'); ?></span>
                    <span class="dm-focus-list__value"><?php echo number_format_i18n($parcelas_ocupadas); ?></span>
                </div>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-carrot"></span>
                    <?php esc_html_e('Cosechas del Año (por cultivo)', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="chart-cosechas"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-admin-home"></span>
                    <?php esc_html_e('Huertos Registrados', 'flavor-chat-ia'); ?>
                </h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-listado')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php if (!empty($huertos_recientes)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Huerto', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px; text-align: center;"><?php esc_html_e('Parcelas', 'flavor-chat-ia'); ?></th>
                            <th style="width: 100px;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($huertos_recientes as $huerto) :
                            $badge_class = $estados_huerto[$huerto['estado']] ?? 'dm-badge--secondary';
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-editar&id=' . $huerto['id'])); ?>">
                                    <?php echo esc_html($huerto['nombre']); ?>
                                </a>
                                <span class="dm-table__subtitle"><?php echo esc_html($huerto['ubicacion']); ?></span>
                            </td>
                            <td style="text-align: center;">
                                <strong><?php echo esc_html($huerto['capacidad_parcelas'] ?? '—'); ?></strong>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $huerto['estado']))); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-admin-home"></span>
                    <p><?php esc_html_e('No hay huertos registrados', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php esc_html_e('Próximas Actividades', 'flavor-chat-ia'); ?>
                </h2>
                <a href="<?php echo esc_url(admin_url('admin.php?page=huertos-actividades')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver calendario', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php if (!empty($lista_actividades)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Actividad', 'flavor-chat-ia'); ?></th>
                            <th style="width: 90px;"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th style="width: 80px;"><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_actividades as $actividad) :
                            $badge_class = $tipos_actividad[$actividad['tipo']] ?? 'dm-badge--secondary';
                            $dias_hasta = floor((strtotime($actividad['fecha']) - time()) / 86400);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($actividad['titulo']); ?></strong>
                                <span class="dm-table__subtitle">
                                    <?php echo esc_html($actividad['huerto_nombre'] ?? __('General', 'flavor-chat-ia')); ?> • <?php echo esc_html($actividad['hora']); ?>h
                                </span>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo $dias_hasta <= 2 ? 'dm-badge--error' : ($dias_hasta <= 5 ? 'dm-badge--warning' : 'dm-badge--success'); ?>">
                                    <?php echo esc_html(date_i18n('j M', strtotime($actividad['fecha']))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                    <?php echo esc_html(ucfirst($actividad['tipo'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <p><?php esc_html_e('No hay actividades programadas', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cosechasData = <?php echo json_encode(array_map(function($c) {
        return ['label' => $c['tipo_cultivo'], 'value' => (float) $c['total_kg']];
    }, $por_cultivo)); ?>;

    const chartElement = document.getElementById('chart-cosechas');
    if (chartElement && cosechasData.length > 0) {
        new Chart(chartElement, {
            type: 'bar',
            data: {
                labels: cosechasData.map(c => c.label),
                datasets: [{
                    label: '<?php esc_attr_e('Kg cosechados', 'flavor-chat-ia'); ?>',
                    data: cosechasData.map(c => c.value),
                    backgroundColor: [
                        'var(--dm-success, #22c55e)',
                        'var(--dm-primary, #3b82f6)',
                        'var(--dm-warning, #f59e0b)',
                        '#ec4899',
                        '#8b5cf6',
                        '#06b6d4'
                    ],
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Kg' }
                    }
                }
            }
        });
    }
});
</script>
