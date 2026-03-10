<?php
/**
 * Vista: Balance Energético - Lecturas y Producción
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$tabla_lecturas = $wpdb->prefix . 'flavor_energia_lecturas';
$tabla_instalaciones = $wpdb->prefix . 'flavor_energia_instalaciones';
$tabla_comunidades = $wpdb->prefix . 'flavor_energia_comunidades';

// Período seleccionado
$periodo = isset($_GET['periodo']) ? sanitize_text_field($_GET['periodo']) : 'mes';
$fecha_inicio = '';
$fecha_fin = date('Y-m-d');

switch ($periodo) {
    case 'semana':
        $fecha_inicio = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'mes':
        $fecha_inicio = date('Y-m-01');
        break;
    case 'trimestre':
        $fecha_inicio = date('Y-m-d', strtotime('-3 months'));
        break;
    case 'año':
        $fecha_inicio = date('Y-01-01');
        break;
    default:
        $fecha_inicio = date('Y-m-01');
}

// Estadísticas del período
$stats = [
    'total_generado' => 0,
    'total_consumido' => 0,
    'total_vertido' => 0,
    'total_lecturas' => 0,
    'ahorro_total' => 0,
];

if (Flavor_Chat_Helpers::tabla_existe($tabla_lecturas)) {
    $stats['total_generado'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(kwh_generados), 0) FROM $tabla_lecturas WHERE fecha_lectura BETWEEN %s AND %s",
        $fecha_inicio, $fecha_fin
    ));

    $stats['total_consumido'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(kwh_consumidos), 0) FROM $tabla_lecturas WHERE fecha_lectura BETWEEN %s AND %s",
        $fecha_inicio, $fecha_fin
    ));

    $stats['total_vertido'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COALESCE(SUM(kwh_vertidos), 0) FROM $tabla_lecturas WHERE fecha_lectura BETWEEN %s AND %s",
        $fecha_inicio, $fecha_fin
    ));

    $stats['total_lecturas'] = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $tabla_lecturas WHERE fecha_lectura BETWEEN %s AND %s",
        $fecha_inicio, $fecha_fin
    ));

    $stats['ahorro_total'] = $stats['total_generado'] * 0.18; // Precio referencia
}

// Últimas lecturas
$lecturas = $wpdb->get_results($wpdb->prepare(
    "SELECT l.*, i.nombre as instalacion_nombre, i.tipo as instalacion_tipo, c.nombre as comunidad_nombre
     FROM $tabla_lecturas l
     LEFT JOIN $tabla_instalaciones i ON l.instalacion_id = i.id
     LEFT JOIN $tabla_comunidades c ON l.energia_comunidad_id = c.id
     WHERE l.fecha_lectura BETWEEN %s AND %s
     ORDER BY l.fecha_lectura DESC, l.created_at DESC
     LIMIT 50",
    $fecha_inicio, $fecha_fin
));

// Datos para gráfico
$datos_grafico = $wpdb->get_results($wpdb->prepare(
    "SELECT DATE(fecha_lectura) as fecha,
            SUM(kwh_generados) as generado,
            SUM(kwh_consumidos) as consumido,
            SUM(kwh_vertidos) as vertido
     FROM $tabla_lecturas
     WHERE fecha_lectura BETWEEN %s AND %s
     GROUP BY DATE(fecha_lectura)
     ORDER BY fecha ASC",
    $fecha_inicio, $fecha_fin
));
?>

<div class="energia-balance" x-data="energiaBalance()">
    <!-- Selector de período -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div style="display: flex; gap: 8px;">
            <button class="button <?php echo $periodo === 'semana' ? 'button-primary' : ''; ?>"
                    onclick="location.href='?periodo=semana'"><?php esc_html_e('Semana', 'flavor-chat-ia'); ?></button>
            <button class="button <?php echo $periodo === 'mes' ? 'button-primary' : ''; ?>"
                    onclick="location.href='?periodo=mes'"><?php esc_html_e('Mes', 'flavor-chat-ia'); ?></button>
            <button class="button <?php echo $periodo === 'trimestre' ? 'button-primary' : ''; ?>"
                    onclick="location.href='?periodo=trimestre'"><?php esc_html_e('Trimestre', 'flavor-chat-ia'); ?></button>
            <button class="button <?php echo $periodo === 'año' ? 'button-primary' : ''; ?>"
                    onclick="location.href='?periodo=año'"><?php esc_html_e('Año', 'flavor-chat-ia'); ?></button>
        </div>

        <button class="button button-primary" @click="showModalLectura = true">
            <span class="dashicons dashicons-plus-alt2"></span>
            <?php esc_html_e('Nueva Lectura', 'flavor-chat-ia'); ?>
        </button>
    </div>

    <!-- KPIs del período -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Generado', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo number_format($stats['total_generado'], 1); ?> kWh</div>
        </div>

        <div style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Consumido', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo number_format($stats['total_consumido'], 1); ?> kWh</div>
        </div>

        <div style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Vertido a red', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo number_format($stats['total_vertido'], 1); ?> kWh</div>
        </div>

        <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: #fff; padding: 20px; border-radius: 12px;">
            <div style="font-size: 11px; opacity: 0.8; text-transform: uppercase;"><?php esc_html_e('Ahorro estimado', 'flavor-chat-ia'); ?></div>
            <div style="font-size: 28px; font-weight: bold;"><?php echo number_format($stats['ahorro_total'], 2); ?> €</div>
        </div>
    </div>

    <!-- Gráfico -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #e5e7eb;">
        <h3 style="margin: 0 0 16px;"><?php esc_html_e('Evolución del período', 'flavor-chat-ia'); ?></h3>
        <canvas id="graficoBalance" height="100"></canvas>
    </div>

    <!-- Tabla de lecturas -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;">
        <h3 style="margin: 0 0 16px;"><?php esc_html_e('Registro de lecturas', 'flavor-chat-ia'); ?></h3>

        <?php if ($lecturas): ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Instalación', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Comunidad', 'flavor-chat-ia'); ?></th>
                    <th style="text-align: right;"><?php esc_html_e('Generado', 'flavor-chat-ia'); ?></th>
                    <th style="text-align: right;"><?php esc_html_e('Consumido', 'flavor-chat-ia'); ?></th>
                    <th style="text-align: right;"><?php esc_html_e('Vertido', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Validada', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lecturas as $lectura): ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('d/m/Y', strtotime($lectura->fecha_lectura))); ?></td>
                    <td><?php echo esc_html($lectura->instalacion_nombre ?: '-'); ?></td>
                    <td><?php echo esc_html($lectura->comunidad_nombre ?: '-'); ?></td>
                    <td style="text-align: right; color: #10b981; font-weight: 500;">
                        <?php echo number_format($lectura->kwh_generados, 2); ?> kWh
                    </td>
                    <td style="text-align: right; color: #f59e0b; font-weight: 500;">
                        <?php echo number_format($lectura->kwh_consumidos, 2); ?> kWh
                    </td>
                    <td style="text-align: right; color: #3b82f6; font-weight: 500;">
                        <?php echo number_format($lectura->kwh_vertidos, 2); ?> kWh
                    </td>
                    <td>
                        <?php if ($lectura->validada): ?>
                            <span style="color: #10b981;">✓</span>
                        <?php else: ?>
                            <span style="color: #f59e0b;">⏳</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <p style="text-align: center; color: #666; padding: 20px;">
            <?php esc_html_e('No hay lecturas registradas en este período.', 'flavor-chat-ia'); ?>
        </p>
        <?php endif; ?>
    </div>

    <!-- Modal Nueva Lectura -->
    <div x-show="showModalLectura" x-cloak
         style="position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 9999; display: flex; align-items: center; justify-content: center;"
         @click.self="showModalLectura = false">
        <div style="background: #fff; border-radius: 12px; padding: 24px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto;">
            <h2 style="margin: 0 0 20px;"><?php esc_html_e('Registrar Lectura', 'flavor-chat-ia'); ?></h2>
            <?php echo do_shortcode('[flavor_energia_form_lectura]'); ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('graficoBalance');
    if (ctx) {
        const datos = <?php echo json_encode($datos_grafico); ?>;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: datos.map(d => d.fecha),
                datasets: [
                    {
                        label: '<?php echo esc_js(__('Generado', 'flavor-chat-ia')); ?>',
                        data: datos.map(d => parseFloat(d.generado)),
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: '<?php echo esc_js(__('Consumido', 'flavor-chat-ia')); ?>',
                        data: datos.map(d => parseFloat(d.consumido)),
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: '<?php echo esc_js(__('Vertido', 'flavor-chat-ia')); ?>',
                        data: datos.map(d => parseFloat(d.vertido)),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'kWh' }
                    }
                }
            }
        });
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('energiaBalance', () => ({
        showModalLectura: false
    }));
});
</script>

<style>
.energia-balance [x-cloak] { display: none !important; }
</style>
