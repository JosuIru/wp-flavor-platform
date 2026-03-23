<?php
/**
 * Vista Dashboard del módulo Radio
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_programas = $wpdb->prefix . 'flavor_radio_programas';
$tabla_emisiones = $wpdb->prefix . 'flavor_radio_emisiones';
$tabla_locutores = $wpdb->prefix . 'flavor_radio_locutores';

// Verificar existencia de tablas
$tabla_programas_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_programas)) === $tabla_programas;
$tabla_emisiones_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_emisiones)) === $tabla_emisiones;
$tabla_locutores_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_locutores)) === $tabla_locutores;

// Inicializar variables
$total_programas = 0;
$total_emisiones = 0;
$total_locutores = 0;
$total_oyentes = 0;
$programas_populares = [];
$emisiones_recientes = [];
$audiencia_por_dia = [];
$emision_actual = null;
$tablas_disponibles = ($tabla_programas_existe && $tabla_emisiones_existe && $tabla_locutores_existe);

if ($tabla_programas_existe && $tabla_emisiones_existe && $tabla_locutores_existe) {
    // Obtener estadísticas generales
    $total_programas = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_programas WHERE estado = 'activo'");
    $total_emisiones = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_emisiones");
    $total_locutores = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_locutores WHERE estado = 'activo'");
    $total_oyentes = (int) $wpdb->get_var("SELECT SUM(oyentes_pico) FROM $tabla_emisiones WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)");

    // Programas más populares
    $programas_populares = $wpdb->get_results("
        SELECT p.*, COUNT(e.id) as total_emisiones, SUM(e.oyentes_pico) as total_oyentes
        FROM $tabla_programas p
        LEFT JOIN $tabla_emisiones e ON p.id = e.programa_id
        WHERE p.estado = 'activo'
        GROUP BY p.id
        ORDER BY total_oyentes DESC
        LIMIT 10
    ");

    // Emisiones recientes
    $emisiones_recientes = $wpdb->get_results("
        SELECT e.*, p.nombre as programa_nombre
        FROM $tabla_emisiones e
        INNER JOIN $tabla_programas p ON e.programa_id = p.id
        ORDER BY e.fecha_emision DESC
        LIMIT 10
    ");

    // Estadísticas de audiencia por día de la semana
    $audiencia_por_dia = $wpdb->get_results("
        SELECT DAYNAME(fecha_emision) as dia, AVG(oyentes_pico) as promedio_oyentes
        FROM $tabla_emisiones
        WHERE fecha_emision >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DAYOFWEEK(fecha_emision), DAYNAME(fecha_emision)
        ORDER BY DAYOFWEEK(fecha_emision)
    ");

    // Estado actual de la emisión
    $emision_actual = $wpdb->get_row("
        SELECT e.*, p.nombre as programa_nombre, p.descripcion as programa_descripcion
        FROM $tabla_emisiones e
        INNER JOIN $tabla_programas p ON e.programa_id = p.id
        WHERE e.estado = 'en_vivo'
        ORDER BY e.fecha_emision DESC
        LIMIT 1
    ");
}

// Mapeo de estados a clases de badge
$estado_badge_classes = [
    'en_vivo' => 'dm-badge--success',
    'finalizada' => 'dm-badge--info',
    'programada' => 'dm-badge--warning',
    'cancelada' => 'dm-badge--error',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('radio');
    }
    ?>

    <?php if (!$tablas_disponibles): ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('Faltan tablas del módulo Radio o aún no hay emisiones registradas.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php endif; ?>

    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-controls-volumeon"></span>
            <?php esc_html_e('Dashboard de Radio', 'flavor-chat-ia'); ?>
        </h1>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-action-grid dm-action-grid--3">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-radio-programas')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-microphone dm-text-primary"></span>
            <span><?php esc_html_e('Programas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-radio-emisiones')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-controls-volumeon dm-text-success"></span>
            <span><?php esc_html_e('Emisiones', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/radio/')); ?>" class="dm-action-card">
            <span class="dashicons dashicons-external dm-text-purple"></span>
            <span><?php esc_html_e('Portal', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Estado de emisión en vivo -->
    <?php if ($emision_actual): ?>
        <div class="dm-alert dm-alert--success dm-live-broadcast">
            <span class="dashicons dashicons-controls-play dm-live-broadcast__icon"></span>
            <div class="dm-live-broadcast__content">
                <h2 class="dm-text-success"><?php esc_html_e('EN VIVO AHORA', 'flavor-chat-ia'); ?></h2>
                <h3><?php echo esc_html($emision_actual->programa_nombre); ?></h3>
                <p class="dm-text-muted"><?php echo esc_html($emision_actual->programa_descripcion); ?></p>
                <p class="dm-text-sm dm-text-muted">
                    <strong><?php echo number_format_i18n($emision_actual->oyentes_actual ?? 0); ?></strong>
                    <?php esc_html_e('oyentes conectados', 'flavor-chat-ia'); ?>
                </p>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-radio-emisiones&ver=' . $emision_actual->id)); ?>" class="dm-btn dm-btn--primary dm-btn--lg">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Gestionar Emisión', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <div class="dm-alert dm-alert--info">
            <span class="dashicons dashicons-info"></span>
            <div>
                <strong><?php esc_html_e('No hay emisiones en vivo en este momento', 'flavor-chat-ia'); ?></strong>
                <p class="dm-text-muted dm-text-sm"><?php esc_html_e('La próxima emisión programada comenzará pronto.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_programas); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Programas Activos', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-microphone dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_emisiones); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Total Emisiones', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-album dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_locutores); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Locutores', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-admin-users dm-stat-card__icon"></span>
        </div>

        <div class="dm-stat-card dm-stat-card--error">
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_oyentes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Oyentes (30d)', 'flavor-chat-ia'); ?></div>
            <span class="dashicons dashicons-groups dm-stat-card__icon"></span>
        </div>
    </div>

    <div class="dm-grid dm-grid--2-1">
        <!-- Gráfico de audiencia por día -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php esc_html_e('Audiencia por Día de la Semana', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-audiencia-dia"></canvas>
            </div>
        </div>

        <!-- Programas más populares -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Top Programas', 'flavor-chat-ia'); ?>
                </h3>
            </div>
            <?php if (empty($programas_populares)): ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-microphone"></span>
                    <p><?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?></p>
                </div>
            <?php else: ?>
                <div class="dm-ranking">
                    <?php foreach (array_slice($programas_populares, 0, 5) as $indice => $programa): ?>
                        <div class="dm-ranking__item">
                            <span class="dm-ranking__number"><?php echo $indice + 1; ?></span>
                            <div class="dm-ranking__content">
                                <span class="dm-ranking__label"><?php echo esc_html($programa->nombre); ?></span>
                                <span class="dm-ranking__value"><?php echo number_format_i18n($programa->total_oyentes ?? 0); ?> oyentes</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Emisiones recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h3>
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Emisiones Recientes', 'flavor-chat-ia'); ?>
            </h3>
        </div>

        <table class="dm-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Programa', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Duración', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Oyentes Pico', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($emisiones_recientes)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="dm-empty">
                                <span class="dashicons dashicons-album"></span>
                                <p><?php esc_html_e('No hay emisiones registradas', 'flavor-chat-ia'); ?></p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($emisiones_recientes as $emision): ?>
                        <tr>
                            <td><strong>#<?php echo esc_html($emision->id); ?></strong></td>
                            <td><strong><?php echo esc_html($emision->programa_nombre); ?></strong></td>
                            <td>
                                <?php
                                if (!empty($emision->duracion_minutos)) {
                                    $horas = floor($emision->duracion_minutos / 60);
                                    $minutos = $emision->duracion_minutos % 60;
                                    echo esc_html($horas . 'h ' . $minutos . 'min');
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="dashicons dashicons-groups dm-text-primary"></span>
                                <?php echo number_format_i18n($emision->oyentes_pico ?? 0); ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = $estado_badge_classes[$emision->estado] ?? 'dm-badge--secondary';
                                $estado_label = ucfirst(str_replace('_', ' ', $emision->estado));
                                ?>
                                <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                    <?php echo esc_html($estado_label); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($emision->fecha_emision))); ?></td>
                            <td>
                                <button class="dm-btn dm-btn--ghost dm-btn--sm" onclick="verEmision(<?php echo esc_attr($emision->id); ?>)">
                                    <span class="dashicons dashicons-visibility"></span>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
jQuery(document).ready(function($) {
    // Obtener colores del tema
    var rootStyles = getComputedStyle(document.documentElement);
    var primaryColor = rootStyles.getPropertyValue('--dm-primary').trim() || '#3b82f6';

    // Gráfico de audiencia por día
    var ctxAudienciaDia = document.getElementById('grafico-audiencia-dia');
    if (ctxAudienciaDia) {
        new Chart(ctxAudienciaDia.getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo wp_json_encode(array_map(function($item) {
                    return $item->dia;
                }, $audiencia_por_dia)); ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Oyentes Promedio', 'flavor-chat-ia'); ?>',
                    data: <?php echo wp_json_encode(array_map(function($item) {
                        return round($item->promedio_oyentes);
                    }, $audiencia_por_dia)); ?>,
                    backgroundColor: primaryColor,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
});

function verEmision(emisionId) {
    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=flavor-radio-emisiones&ver=')); ?>' + emisionId;
}
</script>
