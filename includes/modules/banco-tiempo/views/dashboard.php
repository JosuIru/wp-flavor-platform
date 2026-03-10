<?php
/**
 * Vista Dashboard - Banco de Tiempo
 *
 * Panel principal con estadísticas y resúmenes del banco de tiempo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
$tabla_transacciones = $wpdb->prefix . 'flavor_banco_tiempo_transacciones';

// Verificar tablas
$tabla_servicios_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'");
$tabla_transacciones_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_transacciones'");

// Estadísticas generales
$total_servicios_activos = 0;
$total_servicios_ofrecidos = 0;
$total_intercambios_completados = 0;
$total_intercambios_pendientes = 0;
$total_horas_intercambiadas = 0;

if ($tabla_servicios_existe) {
    $total_servicios_activos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios WHERE estado = 'activo'");
    $total_servicios_ofrecidos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_servicios");
}

if ($tabla_transacciones_existe) {
    $total_intercambios_completados = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado = 'completado'");
    $total_intercambios_pendientes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_transacciones WHERE estado IN ('pendiente', 'aceptado')");
    $total_horas_intercambiadas = $wpdb->get_var("SELECT IFNULL(SUM(horas), 0) FROM $tabla_transacciones WHERE estado = 'completado'");
}

// Servicios por categoría
$servicios_por_categoria = [];
if ($tabla_servicios_existe) {
    $servicios_por_categoria = $wpdb->get_results(
        "SELECT categoria, COUNT(*) as total
         FROM $tabla_servicios
         WHERE estado = 'activo'
         GROUP BY categoria
         ORDER BY total DESC"
    );
}

// Top usuarios por créditos ganados
$top_usuarios_ganados = [];
if ($tabla_transacciones_existe) {
    $top_usuarios_ganados = $wpdb->get_results(
        "SELECT usuario_receptor_id, SUM(horas) as total_horas
         FROM $tabla_transacciones
         WHERE estado = 'completado'
         GROUP BY usuario_receptor_id
         ORDER BY total_horas DESC
         LIMIT 10"
    );
}

// Top usuarios por créditos gastados
$top_usuarios_gastados = [];
if ($tabla_transacciones_existe) {
    $top_usuarios_gastados = $wpdb->get_results(
        "SELECT usuario_solicitante_id, SUM(horas) as total_horas
         FROM $tabla_transacciones
         WHERE estado = 'completado'
         GROUP BY usuario_solicitante_id
         ORDER BY total_horas DESC
         LIMIT 10"
    );
}

// Intercambios recientes
$intercambios_recientes = [];
if ($tabla_transacciones_existe && $tabla_servicios_existe) {
    $intercambios_recientes = $wpdb->get_results(
        "SELECT t.*, s.titulo as servicio_titulo
         FROM $tabla_transacciones t
         LEFT JOIN $tabla_servicios s ON t.servicio_id = s.id
         ORDER BY t.fecha_solicitud DESC
         LIMIT 10"
    );
}

// Actividad por mes (últimos 6 meses)
$actividad_mensual = [];
if ($tabla_transacciones_existe) {
    $actividad_mensual = $wpdb->get_results(
        "SELECT DATE_FORMAT(fecha_solicitud, '%Y-%m') as mes,
                COUNT(*) as total_intercambios,
                SUM(horas) as total_horas
         FROM $tabla_transacciones
         WHERE estado = 'completado'
         AND fecha_solicitud >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
         GROUP BY mes
         ORDER BY mes ASC"
    );
}

// Datos de ejemplo
$usar_datos_ejemplo = ($total_servicios_activos == 0 && $total_intercambios_completados == 0);

if ($usar_datos_ejemplo) {
    $total_servicios_activos = 45;
    $total_intercambios_completados = 128;
    $total_intercambios_pendientes = 12;
    $total_horas_intercambiadas = 342.5;
}

// Mapeo de estados a clases CSS
$estado_badge_classes = [
    'completado' => 'dm-badge--success',
    'aceptado' => 'dm-badge--info',
    'pendiente' => 'dm-badge--warning',
    'cancelado' => 'dm-badge--error',
];

$estado_labels = [
    'completado' => __('Completado', 'flavor-chat-ia'),
    'aceptado' => __('Aceptado', 'flavor-chat-ia'),
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'cancelado' => __('Cancelado', 'flavor-chat-ia'),
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('banco_tiempo');
    }
    ?>

    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-clock"></span>
            <h1><?php esc_html_e('Dashboard - Banco de Tiempo', 'flavor-chat-ia'); ?></h1>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=bt-servicios')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-tools"></span>
            <span><?php esc_html_e('Servicios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bt-intercambios')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-randomize"></span>
            <span><?php esc_html_e('Intercambios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=bt-usuarios')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-groups"></span>
            <span><?php esc_html_e('Usuarios', 'flavor-chat-ia'); ?></span>
        </a>
        <span class="dm-quick-links__item dm-quick-links__item--disabled">
            <span class="dashicons dashicons-category"></span>
            <span><?php esc_html_e('Categorías', 'flavor-chat-ia'); ?></span>
        </span>
        <span class="dm-quick-links__item dm-quick-links__item--disabled">
            <span class="dashicons dashicons-star-filled"></span>
            <span><?php esc_html_e('Valoraciones', 'flavor-chat-ia'); ?></span>
        </span>
        <a href="<?php echo esc_url(admin_url('admin.php?page=banco-tiempo-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url(home_url('/mi-portal/banco-tiempo/')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- Estadísticas Principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-admin-tools"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_servicios_activos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Servicios Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_intercambios_completados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Intercambios Completados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_intercambios_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Intercambios Pendientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_horas_intercambiadas, 1); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Horas Totales Intercambiadas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-pie"></span>
                    <?php esc_html_e('Servicios por Categoría', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-categorias"></canvas>
            </div>
        </div>

        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php esc_html_e('Actividad Últimos 6 Meses', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafico-actividad"></canvas>
            </div>
        </div>
    </div>

    <!-- Tablas de Rankings -->
    <div class="dm-grid dm-grid--2">
        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Top Usuarios - Horas Ganadas', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <?php if (!empty($top_usuarios_ganados)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $posicion = 1;
                        foreach ($top_usuarios_ganados as $usuario) :
                            $user_data = get_userdata($usuario->usuario_receptor_id);
                            if (!$user_data) continue;
                        ?>
                        <tr>
                            <td><strong><?php echo absint($posicion++); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $usuario->usuario_receptor_id)); ?>">
                                    <?php echo esc_html($user_data->display_name); ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <strong class="dm-text-success"><?php echo number_format_i18n($usuario->total_horas, 1); ?> h</strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($usar_datos_ejemplo) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>1</strong></td><td>María García</td><td style="text-align: right;"><strong class="dm-text-success">45.5 h</strong></td></tr>
                        <tr><td><strong>2</strong></td><td>Carlos López</td><td style="text-align: right;"><strong class="dm-text-success">38.0 h</strong></td></tr>
                        <tr><td><strong>3</strong></td><td>Ana Martínez</td><td style="text-align: right;"><strong class="dm-text-success">32.5 h</strong></td></tr>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-star-empty"></span>
                    <p><?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <div class="dm-card">
            <div class="dm-card__header">
                <h2>
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php esc_html_e('Top Usuarios - Horas Gastadas', 'flavor-chat-ia'); ?>
                </h2>
            </div>
            <?php if (!empty($top_usuarios_gastados)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $posicion = 1;
                        foreach ($top_usuarios_gastados as $usuario) :
                            $user_data = get_userdata($usuario->usuario_solicitante_id);
                            if (!$user_data) continue;
                        ?>
                        <tr>
                            <td><strong><?php echo absint($posicion++); ?></strong></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $usuario->usuario_solicitante_id)); ?>">
                                    <?php echo esc_html($user_data->display_name); ?>
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <strong class="dm-text-warning"><?php echo number_format_i18n($usuario->total_horas, 1); ?> h</strong>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($usar_datos_ejemplo) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th><?php esc_html_e('Usuario', 'flavor-chat-ia'); ?></th>
                            <th style="text-align: right;"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td><strong>1</strong></td><td>Pedro Sánchez</td><td style="text-align: right;"><strong class="dm-text-warning">28.5 h</strong></td></tr>
                        <tr><td><strong>2</strong></td><td>Laura Gómez</td><td style="text-align: right;"><strong class="dm-text-warning">24.0 h</strong></td></tr>
                        <tr><td><strong>3</strong></td><td>Roberto Díaz</td><td style="text-align: right;"><strong class="dm-text-warning">19.5 h</strong></td></tr>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-money-alt"></span>
                    <p><?php esc_html_e('No hay datos disponibles', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Intercambios Recientes -->
    <div class="dm-card">
        <div class="dm-card__header">
            <h2>
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Intercambios Recientes', 'flavor-chat-ia'); ?>
            </h2>
            <?php if (!empty($intercambios_recientes) || $usar_datos_ejemplo) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=bt-intercambios')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php if (!empty($intercambios_recientes)) : ?>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Servicio', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Solicitante', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Proveedor', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th style="width: 120px;"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($intercambios_recientes as $intercambio) :
                        $solicitante = get_userdata($intercambio->usuario_solicitante_id);
                        $receptor = get_userdata($intercambio->usuario_receptor_id);
                        $badge_class = $estado_badge_classes[$intercambio->estado] ?? 'dm-badge--secondary';
                        $badge_label = $estado_labels[$intercambio->estado] ?? ucfirst($intercambio->estado);
                    ?>
                    <tr>
                        <td><strong>#<?php echo absint($intercambio->id); ?></strong></td>
                        <td><?php echo esc_html($intercambio->servicio_titulo ?: __('N/A', 'flavor-chat-ia')); ?></td>
                        <td><?php echo $solicitante ? esc_html($solicitante->display_name) : __('Desconocido', 'flavor-chat-ia'); ?></td>
                        <td><?php echo $receptor ? esc_html($receptor->display_name) : __('Desconocido', 'flavor-chat-ia'); ?></td>
                        <td><strong><?php echo number_format_i18n($intercambio->horas, 1); ?> h</strong></td>
                        <td>
                            <span class="dm-badge <?php echo esc_attr($badge_class); ?>">
                                <?php echo esc_html($badge_label); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($intercambio->fecha_solicitud))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php elseif ($usar_datos_ejemplo) : ?>
            <table class="dm-table">
                <thead>
                    <tr>
                        <th style="width: 50px;"><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Servicio', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Solicitante', 'flavor-chat-ia'); ?></th>
                        <th><?php esc_html_e('Proveedor', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php esc_html_e('Horas', 'flavor-chat-ia'); ?></th>
                        <th style="width: 100px;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                        <th style="width: 120px;"><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>#128</strong></td>
                        <td>Clases de idiomas</td>
                        <td>Laura Gómez</td>
                        <td>María García</td>
                        <td><strong>2.0 h</strong></td>
                        <td><span class="dm-badge dm-badge--success">Completado</span></td>
                        <td>05/03/2026 15:30</td>
                    </tr>
                    <tr>
                        <td><strong>#127</strong></td>
                        <td>Reparación de bicicleta</td>
                        <td>Pedro Sánchez</td>
                        <td>Carlos López</td>
                        <td><strong>1.5 h</strong></td>
                        <td><span class="dm-badge dm-badge--info">Aceptado</span></td>
                        <td>05/03/2026 10:00</td>
                    </tr>
                    <tr>
                        <td><strong>#126</strong></td>
                        <td>Cuidado de mascotas</td>
                        <td>Ana Martínez</td>
                        <td>Roberto Díaz</td>
                        <td><strong>3.0 h</strong></td>
                        <td><span class="dm-badge dm-badge--warning">Pendiente</span></td>
                        <td>04/03/2026 18:45</td>
                    </tr>
                </tbody>
            </table>
        <?php else : ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-randomize"></span>
                <p><?php esc_html_e('No hay intercambios registrados', 'flavor-chat-ia'); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctxCategorias = document.getElementById('grafico-categorias');
    const ctxActividad = document.getElementById('grafico-actividad');

    if (ctxCategorias) {
        new Chart(ctxCategorias, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(!empty($servicios_por_categoria) ? array_map(function($c) { return ucfirst($c->categoria); }, $servicios_por_categoria) : ['Educación', 'Hogar', 'Tecnología', 'Cuidados', 'Otros']); ?>,
                datasets: [{
                    data: <?php echo json_encode(!empty($servicios_por_categoria) ? array_column($servicios_por_categoria, 'total') : [15, 12, 10, 8, 5]); ?>,
                    backgroundColor: [
                        'var(--dm-primary, #3b82f6)',
                        'var(--dm-success, #22c55e)',
                        'var(--dm-warning, #f59e0b)',
                        'var(--dm-error, #ef4444)',
                        '#8b5cf6',
                        '#06b6d4',
                        '#f97316'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'right' } }
            }
        });
    }

    if (ctxActividad) {
        new Chart(ctxActividad, {
            type: 'line',
            data: {
                labels: <?php
                    if (!empty($actividad_mensual)) {
                        echo json_encode(array_map(function($m) {
                            $fecha = DateTime::createFromFormat('Y-m', $m->mes);
                            return $fecha ? $fecha->format('M Y') : $m->mes;
                        }, $actividad_mensual));
                    } else {
                        echo "['Oct', 'Nov', 'Dic', 'Ene', 'Feb', 'Mar']";
                    }
                ?>,
                datasets: [{
                    label: '<?php esc_attr_e('Intercambios', 'flavor-chat-ia'); ?>',
                    data: <?php echo json_encode(!empty($actividad_mensual) ? array_column($actividad_mensual, 'total_intercambios') : [18, 22, 15, 28, 35, 32]); ?>,
                    borderColor: 'var(--dm-primary, #3b82f6)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: '<?php esc_attr_e('Horas', 'flavor-chat-ia'); ?>',
                    data: <?php echo json_encode(!empty($actividad_mensual) ? array_column($actividad_mensual, 'total_horas') : [42, 58, 35, 72, 95, 85]); ?>,
                    borderColor: 'var(--dm-success, #22c55e)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'top' } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});
</script>
