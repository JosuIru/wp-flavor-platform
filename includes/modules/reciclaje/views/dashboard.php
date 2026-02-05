<?php
/**
 * Vista Dashboard - Módulo Reciclaje
 * Panel principal con estadísticas de reciclaje
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

// Obtener estadísticas generales
$total_puntos_reciclaje = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_puntos_reciclaje WHERE estado = 'activo'");
$total_depositos_mes = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_depositos WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE()) AND YEAR(fecha_deposito) = YEAR(CURRENT_DATE())");
$total_kg_mes = $wpdb->get_var("SELECT SUM(cantidad_kg) FROM $tabla_depositos WHERE MONTH(fecha_deposito) = MONTH(CURRENT_DATE()) AND YEAR(fecha_deposito) = YEAR(CURRENT_DATE())");
$contenedores_llenos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contenedores WHERE necesita_vaciado = 1");

// Estadísticas por material
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

// Usuarios más activos
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

// Datos para gráfica de evolución mensual (últimos 6 meses)
$evolucion_mensual = $wpdb->get_results("
    SELECT DATE_FORMAT(fecha_deposito, '%Y-%m') as mes,
           SUM(cantidad_kg) as total_kg,
           COUNT(*) as total_depositos
    FROM $tabla_depositos
    WHERE fecha_deposito >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");

// Puntos que necesitan atención
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
?>

<div class="wrap flavor-reciclaje-dashboard">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-site"></span>
        <?php echo esc_html__('Dashboard de Reciclaje', 'flavor-chat-ia'); ?>
    </h1>

    <hr class="wp-header-end">

    <!-- Tarjetas de estadísticas principales -->
    <div class="flavor-stats-cards">
        <div class="flavor-stat-card flavor-stat-primary">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-location"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($total_puntos_reciclaje); ?></h3>
                <p><?php echo esc_html__('Puntos de Reciclaje Activos', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-success">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($total_kg_mes, 2); ?> kg</h3>
                <p><?php echo esc_html__('Reciclado este Mes', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-info">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($total_depositos_mes); ?></h3>
                <p><?php echo esc_html__('Depósitos este Mes', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-stat-card flavor-stat-warning">
            <div class="flavor-stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="flavor-stat-content">
                <h3><?php echo number_format($contenedores_llenos); ?></h3>
                <p><?php echo esc_html__('Contenedores Llenos', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <div class="flavor-dashboard-grid">
        <!-- Gráfica de evolución mensual -->
        <div class="flavor-dashboard-widget flavor-widget-large">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Evolución de Reciclaje', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Últimos 6 meses', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <canvas id="grafica-evolucion-reciclaje" height="80"></canvas>
            </div>
        </div>

        <!-- Estadísticas por material -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Reciclaje por Material', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <canvas id="grafica-materiales" height="200"></canvas>
            </div>
        </div>

        <!-- Usuarios más activos -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Usuarios Más Activos', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <?php if (!empty($usuarios_activos)) : ?>
                    <div class="flavor-ranking-list">
                        <?php foreach ($usuarios_activos as $index => $usuario) : ?>
                            <div class="flavor-ranking-item">
                                <span class="flavor-ranking-position"><?php echo $index + 1; ?></span>
                                <div class="flavor-ranking-user">
                                    <?php echo get_avatar($usuario->ID, 32); ?>
                                    <div class="flavor-ranking-info">
                                        <strong><?php echo esc_html($usuario->display_name); ?></strong>
                                        <span><?php echo sprintf(__('%s kg • %s puntos', 'flavor-chat-ia'), number_format($usuario->total_kg, 2), number_format($usuario->total_puntos)); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p class="flavor-no-data"><?php echo esc_html__('No hay datos de usuarios activos este mes.', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Puntos que necesitan atención -->
        <div class="flavor-dashboard-widget">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Puntos que Necesitan Atención', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-widget-body">
                <?php if (!empty($puntos_atencion)) : ?>
                    <div class="flavor-alert-list">
                        <?php foreach ($puntos_atencion as $punto) : ?>
                            <div class="flavor-alert-item flavor-alert-<?php echo esc_attr($punto->estado); ?>">
                                <span class="dashicons dashicons-location-alt"></span>
                                <div class="flavor-alert-content">
                                    <strong><?php echo esc_html($punto->nombre); ?></strong>
                                    <span>
                                        <?php
                                        if ($punto->estado == 'lleno') {
                                            echo esc_html__('Punto lleno', 'flavor-chat-ia');
                                        } elseif ($punto->estado == 'mantenimiento') {
                                            echo esc_html__('En mantenimiento', 'flavor-chat-ia');
                                        }
                                        if ($punto->contenedores_problema > 0) {
                                            echo ' • ' . sprintf(__('%d contenedores necesitan vaciado', 'flavor-chat-ia'), $punto->contenedores_problema);
                                        }
                                        ?>
                                    </span>
                                </div>
                                <a href="<?php echo admin_url('admin.php?page=flavor-reciclaje-puntos&action=edit&id=' . $punto->id); ?>" class="button button-small">
                                    <?php echo esc_html__('Ver', 'flavor-chat-ia'); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="flavor-success-message">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p><?php echo esc_html__('Todos los puntos están operativos.', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Impacto ambiental -->
        <div class="flavor-dashboard-widget flavor-widget-highlight">
            <div class="flavor-widget-header">
                <h2><?php echo esc_html__('Impacto Ambiental', 'flavor-chat-ia'); ?></h2>
                <span class="flavor-widget-subtitle"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flavor-widget-body">
                <div class="flavor-impact-stats">
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-admin-site"></span>
                        <div>
                            <strong><?php echo number_format($total_kg_mes * 0.75, 2); ?> kg</strong>
                            <p><?php echo esc_html__('CO₂ evitado', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-palmtree"></span>
                        <div>
                            <strong><?php echo number_format($total_kg_mes / 17, 0); ?></strong>
                            <p><?php echo esc_html__('Árboles equivalentes', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <div class="flavor-impact-item">
                        <span class="dashicons dashicons-tide"></span>
                        <div>
                            <strong><?php echo number_format($total_kg_mes * 5, 0); ?> L</strong>
                            <p><?php echo esc_html__('Agua ahorrada', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Datos para las gráficas
    const datosEvolucion = <?php echo json_encode($evolucion_mensual); ?>;
    const datosMateriales = <?php echo json_encode($stats_materiales); ?>;

    // Gráfica de evolución mensual
    const ctxEvolucion = document.getElementById('grafica-evolucion-reciclaje');
    if (ctxEvolucion) {
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: datosEvolucion.map(d => {
                    const [year, month] = d.mes.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('es-ES', { month: 'short', year: 'numeric' });
                }),
                datasets: [{
                    label: '<?php echo esc_js(__('Kg reciclados', 'flavor-chat-ia')); ?>',
                    data: datosEvolucion.map(d => parseFloat(d.total_kg)),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
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
                    y: {
                        beginAtZero: true,
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
            'papel': '#6c757d',
            'plastico': '#ffc107',
            'vidrio': '#17a2b8',
            'organico': '#28a745',
            'electronico': '#dc3545',
            'ropa': '#6f42c1',
            'aceite': '#fd7e14',
            'pilas': '#20c997'
        };

        new Chart(ctxMateriales, {
            type: 'doughnut',
            data: {
                labels: datosMateriales.map(m => m.tipo_material.charAt(0).toUpperCase() + m.tipo_material.slice(1)),
                datasets: [{
                    data: datosMateriales.map(m => parseFloat(m.total_kg)),
                    backgroundColor: datosMateriales.map(m => coloresMateriales[m.tipo_material] || '#6c757d')
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'right'
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

<style>
.flavor-reciclaje-dashboard {
    padding: 20px;
}

.flavor-stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-stat-card {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid;
}

.flavor-stat-primary { border-left-color: #0073aa; }
.flavor-stat-success { border-left-color: #28a745; }
.flavor-stat-info { border-left-color: #17a2b8; }
.flavor-stat-warning { border-left-color: #ffc107; }

.flavor-stat-icon {
    font-size: 40px;
    opacity: 0.8;
}

.flavor-stat-content h3 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.flavor-stat-content p {
    margin: 5px 0 0;
    color: #666;
    font-size: 14px;
}

.flavor-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
}

.flavor-widget-large {
    grid-column: span 2;
}

.flavor-dashboard-widget {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.flavor-widget-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.flavor-widget-header h2 {
    margin: 0;
    font-size: 18px;
}

.flavor-widget-subtitle {
    color: #666;
    font-size: 13px;
}

.flavor-widget-body {
    padding: 20px;
}

.flavor-ranking-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-ranking-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 6px;
}

.flavor-ranking-position {
    font-size: 20px;
    font-weight: 700;
    color: #0073aa;
    min-width: 30px;
    text-align: center;
}

.flavor-ranking-user {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.flavor-ranking-info {
    display: flex;
    flex-direction: column;
}

.flavor-ranking-info strong {
    font-size: 14px;
}

.flavor-ranking-info span {
    font-size: 12px;
    color: #666;
}

.flavor-alert-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.flavor-alert-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    border-radius: 6px;
    border-left: 4px solid;
}

.flavor-alert-item.flavor-alert-lleno {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.flavor-alert-item.flavor-alert-mantenimiento {
    background: #f8d7da;
    border-left-color: #dc3545;
}

.flavor-alert-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.flavor-alert-content strong {
    font-size: 14px;
}

.flavor-alert-content span {
    font-size: 12px;
    color: #666;
}

.flavor-success-message {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #d4edda;
    border-radius: 6px;
    color: #155724;
}

.flavor-widget-highlight {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #fff;
}

.flavor-widget-highlight .flavor-widget-header {
    border-bottom-color: rgba(255,255,255,0.2);
}

.flavor-widget-highlight h2,
.flavor-widget-highlight .flavor-widget-subtitle {
    color: #fff;
}

.flavor-impact-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
}

.flavor-impact-item {
    display: flex;
    align-items: center;
    gap: 10px;
}

.flavor-impact-item .dashicons {
    font-size: 40px;
    opacity: 0.9;
}

.flavor-impact-item strong {
    display: block;
    font-size: 24px;
    margin-bottom: 5px;
}

.flavor-impact-item p {
    margin: 0;
    font-size: 13px;
    opacity: 0.9;
}

.flavor-no-data {
    text-align: center;
    color: #666;
    padding: 40px 20px;
}

@media (max-width: 1200px) {
    .flavor-widget-large {
        grid-column: span 1;
    }
}

@media (max-width: 768px) {
    .flavor-dashboard-grid {
        grid-template-columns: 1fr;
    }

    .flavor-stats-cards {
        grid-template-columns: 1fr;
    }
}
</style>
