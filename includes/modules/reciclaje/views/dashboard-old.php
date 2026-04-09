<?php
/**
 * Vista Dashboard - Módulo Reciclaje
 * Panel principal con estadísticas de reciclaje
 * Migrado al sistema dm-* centralizado
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_puntos_reciclaje = $wpdb->prefix . 'flavor_reciclaje_puntos';
$tabla_depositos = $wpdb->prefix . 'flavor_reciclaje_depositos';
$tabla_contenedores = $wpdb->prefix . 'flavor_reciclaje_contenedores';

// Verificar existencia de tablas
$tabla_puntos_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_puntos_reciclaje
)) > 0;

$tabla_depositos_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_depositos
)) > 0;

$tabla_contenedores_existe = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
    DB_NAME,
    $tabla_contenedores
)) > 0;

// Inicializar valores
$total_puntos_reciclaje = 0;
$total_depositos_mes = 0;
$total_kg_mes = 0;
$contenedores_llenos = 0;
$stats_materiales = [];
$usuarios_activos = [];
$evolucion_mensual = [];
$puntos_atencion = [];

// Obtener estadísticas si las tablas existen
if ($tabla_puntos_existe) {
    $total_puntos_reciclaje = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_puntos_reciclaje WHERE estado = 'activo'");
}

if ($tabla_depositos_existe) {
    $total_depositos_mes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_depositos WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE()) AND YEAR(fecha_deposito) = YEAR(CURRENT_DATE())");
    $total_kg_mes = (float) ($wpdb->get_var("SELECT SUM(cantidad_kg) FROM $tabla_depositos WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE()) AND YEAR(fecha_deposito) = YEAR(CURRENT_DATE())") ?: 0);

    $stats_materiales = $wpdb->get_results("
        SELECT tipo_material,
               COUNT(*) as total_depositos,
               SUM(cantidad_kg) as total_kg,
               AVG(cantidad_kg) as promedio_kg
        FROM $tabla_depositos
        WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE())
        AND YEAR(fecha_deposito) = YEAR(CURRENT_DATE())
        GROUP BY tipo_material
        ORDER BY total_kg DESC
    ");

    $usuarios_activos = $wpdb->get_results("
        SELECT u.ID, u.display_name,
               COUNT(d.id) as total_depositos,
               SUM(d.cantidad_kg) as total_kg,
               SUM(d.puntos_ganados) as total_puntos
        FROM {$wpdb->users} u
        INNER JOIN $tabla_depositos d ON u.ID = d.usuario_id
        WHERE MONTH(d.fecha_deposito) = MONTH(CURRENT_DATE())
        AND YEAR(d.fecha_deposito) = YEAR(CURRENT_DATE())
        GROUP BY u.ID
        ORDER BY total_kg DESC
        LIMIT 10
    ");

    $evolucion_mensual = $wpdb->get_results("
        SELECT DATE_FORMAT(fecha_deposito, '%Y-%m') as mes,
               SUM(cantidad_kg) as total_kg,
               COUNT(*) as total_depositos
        FROM $tabla_depositos
        WHERE fecha_deposito >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY mes
        ORDER BY mes ASC
    ");
}

if ($tabla_contenedores_existe) {
    $contenedores_llenos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contenedores WHERE necesita_vaciado = 1");
}

if ($tabla_puntos_existe && $tabla_contenedores_existe) {
    $puntos_atencion = $wpdb->get_results("
        SELECT p.*,
               COUNT(c.id) as contenedores_problema
        FROM $tabla_puntos_reciclaje p
        LEFT JOIN $tabla_contenedores c ON p.id = c.punto_reciclaje_id AND c.necesita_vaciado = 1
        WHERE p.estado IN ('lleno', 'mantenimiento')
        OR c.id IS NOT NULL
        GROUP BY p.id
        HAVING contenedores_problema > 0 OR p.estado != 'activo'
        ORDER BY contenedores_problema DESC
        LIMIT 5
    ");
}

// Calcular impacto ambiental
$co2_evitado = $total_kg_mes * 0.75;
$arboles_equivalentes = $total_kg_mes / 17;
$agua_ahorrada = $total_kg_mes * 5;

$materiales_colores = [
    'papel' => 'secondary',
    'plastico' => 'warning',
    'vidrio' => 'info',
    'organico' => 'success',
    'electronico' => 'error',
    'ropa' => 'purple',
    'aceite' => 'warning',
    'pilas' => 'info',
];
?>

<div class="dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('reciclaje');
    }
    ?>

    <div class="dm-header">
        <h1 class="dm-header__title">
            <span class="dashicons dashicons-admin-site"></span>
            <?php esc_html_e('Dashboard de Reciclaje', 'flavor-platform'); ?>
        </h1>
        <p class="dm-header__description">
            <?php esc_html_e('Gestiona puntos de reciclaje, monitoriza depósitos y mide el impacto ambiental', 'flavor-platform'); ?>
        </p>
    </div>

    <?php if (!$tabla_puntos_existe && !$tabla_depositos_existe && !$tabla_contenedores_existe): ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <strong><?php esc_html_e('Sin datos:', 'flavor-platform'); ?></strong>
        <?php esc_html_e('Faltan tablas del módulo Reciclaje o todavía no hay actividad registrada.', 'flavor-platform'); ?>
    </div>
    <?php endif; ?>

    <!-- Accesos Rápidos -->
    <div class="dm-quick-links">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-reciclaje-puntos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-location"></span>
            <span><?php esc_html_e('Puntos', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-reciclaje-depositos')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-chart-line"></span>
            <span><?php esc_html_e('Depósitos', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-reciclaje-contenedores')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-archive"></span>
            <span><?php esc_html_e('Contenedores', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-reciclaje-config')); ?>" class="dm-quick-links__item">
            <span class="dashicons dashicons-admin-settings"></span>
            <span><?php esc_html_e('Configuración', 'flavor-platform'); ?></span>
        </a>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('reciclaje', '')); ?>" class="dm-quick-links__item" target="_blank">
            <span class="dashicons dashicons-external"></span>
            <span><?php esc_html_e('Portal público', 'flavor-platform'); ?></span>
        </a>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--primary">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-location"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_puntos_reciclaje); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Puntos de Reciclaje', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_kg_mes, 1); ?> kg</div>
            <div class="dm-stat-card__label"><?php esc_html_e('Reciclado este Mes', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($total_depositos_mes); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Depósitos este Mes', 'flavor-platform'); ?></div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="dm-stat-card__value"><?php echo number_format_i18n($contenedores_llenos); ?></div>
            <div class="dm-stat-card__label"><?php esc_html_e('Contenedores Llenos', 'flavor-platform'); ?></div>
        </div>
    </div>

    <div class="dm-grid dm-grid--2">
        <!-- Gráfica de evolución mensual -->
        <div class="dm-card dm-card--chart" style="grid-column: span 2;">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-area"></span> <?php esc_html_e('Evolución de Reciclaje', 'flavor-platform'); ?></h3>
                <span class="dm-card__meta"><?php esc_html_e('Últimos 6 meses', 'flavor-platform'); ?></span>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafica-evolucion-reciclaje"></canvas>
            </div>
        </div>

        <!-- Estadísticas por material -->
        <div class="dm-card dm-card--chart">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-chart-pie"></span> <?php esc_html_e('Reciclaje por Material', 'flavor-platform'); ?></h3>
            </div>
            <div class="dm-card__chart">
                <canvas id="grafica-materiales"></canvas>
            </div>
        </div>

        <!-- Usuarios más activos -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-awards"></span> <?php esc_html_e('Usuarios Más Activos', 'flavor-platform'); ?></h3>
                <span class="dm-card__meta"><?php esc_html_e('Este mes', 'flavor-platform'); ?></span>
            </div>
            <?php if (!empty($usuarios_activos)): ?>
            <div class="dm-ranking">
                <?php foreach ($usuarios_activos as $index => $usuario): ?>
                <div class="dm-ranking__item">
                    <span class="dm-ranking__position"><?php echo $index + 1; ?></span>
                    <div class="dm-ranking__content">
                        <?php echo get_avatar($usuario->ID, 32, '', '', ['class' => 'dm-ranking__avatar']); ?>
                        <div class="dm-ranking__info">
                            <strong><?php echo esc_html($usuario->display_name); ?></strong>
                            <span class="dm-text-muted dm-text-sm">
                                <?php echo sprintf(
                                    esc_html__('%s kg · %s puntos', 'flavor-platform'),
                                    number_format_i18n($usuario->total_kg, 1),
                                    number_format_i18n($usuario->total_puntos)
                                ); ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-empty">
                <span class="dashicons dashicons-groups"></span>
                <p><?php esc_html_e('No hay datos de usuarios activos este mes.', 'flavor-platform'); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Puntos que necesitan atención -->
        <div class="dm-card">
            <div class="dm-card__header">
                <h3><span class="dashicons dashicons-warning"></span> <?php esc_html_e('Puntos que Necesitan Atención', 'flavor-platform'); ?></h3>
            </div>
            <?php if (!empty($puntos_atencion)): ?>
            <div class="dm-focus-list">
                <?php foreach ($puntos_atencion as $punto):
                    $variante = $punto->estado === 'lleno' ? 'warning' : ($punto->estado === 'mantenimiento' ? 'error' : 'info');
                ?>
                <div class="dm-focus-list__item dm-focus-list__item--<?php echo esc_attr($variante); ?>">
                    <span class="dashicons dashicons-location-alt"></span>
                    <div class="dm-focus-list__content">
                        <strong><?php echo esc_html($punto->nombre); ?></strong>
                        <span class="dm-text-sm">
                            <?php
                            if ($punto->estado === 'lleno') {
                                esc_html_e('Punto lleno', 'flavor-platform');
                            } elseif ($punto->estado === 'mantenimiento') {
                                esc_html_e('En mantenimiento', 'flavor-platform');
                            }
                            if ($punto->contenedores_problema > 0) {
                                echo ' · ' . sprintf(
                                    esc_html__('%d contenedores necesitan vaciado', 'flavor-platform'),
                                    $punto->contenedores_problema
                                );
                            }
                            ?>
                        </span>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-reciclaje-puntos&action=edit&id=' . $punto->id)); ?>" class="dm-btn dm-btn--sm dm-btn--ghost">
                        <?php esc_html_e('Ver', 'flavor-platform'); ?>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="dm-alert dm-alert--success" style="margin: 20px;">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Todos los puntos están operativos.', 'flavor-platform'); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Impacto ambiental -->
        <div class="dm-card dm-stat-card--highlight" style="--dm-stat-gradient: linear-gradient(135deg, var(--dm-eco, #10b981) 0%, #059669 100%);">
            <div class="dm-card__header" style="border-bottom-color: rgba(255,255,255,0.2);">
                <h3 style="color: white;"><span class="dashicons dashicons-admin-site"></span> <?php esc_html_e('Impacto Ambiental', 'flavor-platform'); ?></h3>
                <span class="dm-card__meta" style="color: rgba(255,255,255,0.9);"><?php esc_html_e('Este mes', 'flavor-platform'); ?></span>
            </div>
            <div class="dm-impact-grid" style="padding: 20px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; color: white;">
                <div class="dm-impact-item" style="text-align: center;">
                    <span class="dashicons dashicons-cloud" style="font-size: 32px; opacity: 0.9;"></span>
                    <div style="font-size: 24px; font-weight: bold; margin: 8px 0;"><?php echo number_format_i18n($co2_evitado, 0); ?> kg</div>
                    <div style="font-size: 13px; opacity: 0.9;"><?php esc_html_e('CO₂ evitado', 'flavor-platform'); ?></div>
                </div>
                <div class="dm-impact-item" style="text-align: center;">
                    <span class="dashicons dashicons-palmtree" style="font-size: 32px; opacity: 0.9;"></span>
                    <div style="font-size: 24px; font-weight: bold; margin: 8px 0;"><?php echo number_format_i18n($arboles_equivalentes, 0); ?></div>
                    <div style="font-size: 13px; opacity: 0.9;"><?php esc_html_e('Árboles equivalentes', 'flavor-platform'); ?></div>
                </div>
                <div class="dm-impact-item" style="text-align: center;">
                    <span class="dashicons dashicons-tide" style="font-size: 32px; opacity: 0.9;"></span>
                    <div style="font-size: 24px; font-weight: bold; margin: 8px 0;"><?php echo number_format_i18n($agua_ahorrada, 0); ?> L</div>
                    <div style="font-size: 13px; opacity: 0.9;"><?php esc_html_e('Agua ahorrada', 'flavor-platform'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Chart === 'undefined') return;

    const style = getComputedStyle(document.documentElement);
    const success = style.getPropertyValue('--dm-success').trim() || '#10b981';
    const primary = style.getPropertyValue('--dm-primary').trim() || '#3b82f6';
    const warning = style.getPropertyValue('--dm-warning').trim() || '#f59e0b';
    const info = style.getPropertyValue('--dm-info').trim() || '#06b6d4';
    const error = style.getPropertyValue('--dm-error').trim() || '#ef4444';
    const purple = style.getPropertyValue('--dm-purple').trim() || '#8b5cf6';
    const muted = style.getPropertyValue('--dm-text-muted').trim() || '#64748b';

    // Datos para las gráficas
    const datosEvolucion = <?php echo wp_json_encode($evolucion_mensual); ?>;
    const datosMateriales = <?php echo wp_json_encode($stats_materiales); ?>;

    // Gráfica de evolución mensual
    const ctxEvolucion = document.getElementById('grafica-evolucion-reciclaje');
    if (ctxEvolucion && datosEvolucion.length > 0) {
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: datosEvolucion.map(d => {
                    const [year, month] = d.mes.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: '<?php echo esc_js(__('Kg reciclados', 'flavor-platform')); ?>',
                    data: datosEvolucion.map(d => parseFloat(d.total_kg)),
                    borderColor: success,
                    backgroundColor: success + '20',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e5e7eb' },
                        ticks: {
                            callback: function(value) {
                                return value + ' kg';
                            }
                        }
                    }
                }
            }
        });
    }

    // Gráfica de materiales
    const ctxMateriales = document.getElementById('grafica-materiales');
    if (ctxMateriales && datosMateriales.length > 0) {
        const coloresMateriales = {
            'papel': muted,
            'plastico': warning,
            'vidrio': info,
            'organico': success,
            'electronico': error,
            'ropa': purple,
            'aceite': warning,
            'pilas': info
        };

        new Chart(ctxMateriales, {
            type: 'doughnut',
            data: {
                labels: datosMateriales.map(m => m.tipo_material.charAt(0).toUpperCase() + m.tipo_material.slice(1)),
                datasets: [{
                    data: datosMateriales.map(m => parseFloat(m.total_kg)),
                    backgroundColor: datosMateriales.map(m => coloresMateriales[m.tipo_material] || muted)
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' kg';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
