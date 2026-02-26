<?php
/**
 * Dashboard de Espacios Comunes
 * Vista general de uso y estado de espacios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-espacios-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e('Espacios Comunes - Dashboard', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- Accesos Rapidos -->
    <div class="espacios-quick-access" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin: 20px 0;">
        <a href="<?php echo admin_url('admin.php?page=espacios-listado'); ?>" class="espacios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-building" style="font-size: 24px; color: #2271b1;"></span>
            <span><?php echo esc_html__('Espacios', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=espacios-reservas'); ?>" class="espacios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-calendar-alt" style="font-size: 24px; color: #00a32a;"></span>
            <span><?php echo esc_html__('Reservas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=espacios-calendario'); ?>" class="espacios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-calendar" style="font-size: 24px; color: #8c52ff;"></span>
            <span><?php echo esc_html__('Calendario', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=espacios-normas'); ?>" class="espacios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-clipboard" style="font-size: 24px; color: #dba617;"></span>
            <span><?php echo esc_html__('Normas', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo admin_url('admin.php?page=espacios-configuracion'); ?>" class="espacios-quick-link" style="display: flex; align-items: center; gap: 12px; padding: 15px 20px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; text-decoration: none; color: #1d2327; transition: all 0.2s;">
            <span class="dashicons dashicons-admin-settings" style="font-size: 24px; color: #646970;"></span>
            <span><?php echo esc_html__('Configuracion', 'flavor-chat-ia'); ?></span>
        </a>
    </div>

    <!-- KPIs principales -->
    <div class="flavor-kpi-grid">
        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-admin-home"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Total Espacios', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="total-espacios">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('espacios disponibles', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Reservas Activas', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="reservas-activas">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('en curso o próximas', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Usuarios Activos', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="usuarios-activos">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('este mes', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Tasa de Ocupación', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="tasa-ocupacion">0%</div>
                <p class="flavor-kpi-subtitle"><?php _e('promedio semanal', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Gráficos y estadísticas -->
    <div class="flavor-grid-two-columns">
        <!-- Uso por espacio -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Uso por Espacio', 'flavor-chat-ia'); ?></h2>
                <select id="filtro-periodo-uso" class="flavor-select-small">
                    <option value="7"><?php _e('Últimos 7 días', 'flavor-chat-ia'); ?></option>
                    <option value="30" selected><?php _e('Últimos 30 días', 'flavor-chat-ia'); ?></option>
                    <option value="90"><?php _e('Últimos 90 días', 'flavor-chat-ia'); ?></option>
                </select>
            </div>
            <div class="flavor-card-body">
                <canvas id="grafico-uso-espacios" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Reservas por día de la semana -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Reservas por Día', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <canvas id="grafico-dias-semana" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Espacios más populares -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Espacios Más Populares', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <div class="flavor-ranking-list" id="ranking-espacios">
                    <div class="flavor-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>

        <!-- Próximas reservas -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Próximas Reservas', 'flavor-chat-ia'); ?></h2>
                <a href="?page=espacios-comunes-reservas" class="button button-small">
                    <?php _e('Ver todas', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <div class="flavor-card-body">
                <div class="flavor-timeline" id="proximas-reservas">
                    <div class="flavor-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estado de espacios en tiempo real -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php _e('Estado Actual de Espacios', 'flavor-chat-ia'); ?></h2>
            <div class="flavor-header-actions">
                <span class="flavor-live-indicator">
                    <span class="flavor-pulse"></span>
                    <?php _e('En vivo', 'flavor-chat-ia'); ?>
                </span>
            </div>
        </div>
        <div class="flavor-card-body">
            <div class="flavor-espacios-grid" id="estado-espacios-actual">
                <div class="flavor-loading"><?php _e('Cargando estado de espacios...', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Alertas y notificaciones -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php _e('Alertas', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="flavor-card-body">
            <div id="alertas-espacios">
                <!-- Se llenan vía JavaScript -->
            </div>
        </div>
    </div>
</div>

<style>
.flavor-espacios-dashboard {
    margin: 20px;
}

.flavor-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.flavor-kpi-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.flavor-kpi-icon {
    font-size: 48px;
    color: #2271b1;
}

.flavor-kpi-icon .dashicons {
    width: 48px;
    height: 48px;
    font-size: 48px;
}

.flavor-kpi-content h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #666;
    font-weight: 500;
}

.flavor-kpi-value {
    font-size: 32px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1;
}

.flavor-kpi-subtitle {
    margin: 5px 0 0 0;
    font-size: 12px;
    color: #999;
}

.flavor-grid-two-columns {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.flavor-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

.flavor-card-header {
    padding: 15px 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.flavor-card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.flavor-card-body {
    padding: 20px;
}

.flavor-select-small {
    padding: 5px 10px;
    font-size: 13px;
}

.flavor-ranking-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flavor-ranking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.flavor-ranking-item:last-child {
    border-bottom: none;
}

.flavor-ranking-position {
    font-size: 20px;
    font-weight: 700;
    color: #2271b1;
    width: 40px;
}

.flavor-ranking-name {
    flex: 1;
    font-weight: 500;
}

.flavor-ranking-value {
    font-size: 14px;
    color: #666;
}

.flavor-timeline {
    position: relative;
}

.flavor-timeline-item {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.flavor-timeline-item:last-child {
    border-bottom: none;
}

.flavor-timeline-time {
    font-size: 12px;
    color: #666;
    min-width: 80px;
}

.flavor-timeline-content {
    flex: 1;
}

.flavor-timeline-title {
    font-weight: 600;
    margin-bottom: 5px;
}

.flavor-timeline-meta {
    font-size: 12px;
    color: #999;
}

.flavor-live-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #10b981;
}

.flavor-pulse {
    width: 8px;
    height: 8px;
    background: #10b981;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.2);
    }
}

.flavor-espacios-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
}

.flavor-espacio-status-card {
    padding: 15px;
    border: 2px solid #ddd;
    border-radius: 8px;
    text-align: center;
}

.flavor-espacio-status-card.disponible {
    border-color: #10b981;
    background: #ecfdf5;
}

.flavor-espacio-status-card.ocupado {
    border-color: #ef4444;
    background: #fef2f2;
}

.flavor-espacio-status-card h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.flavor-espacio-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.flavor-espacio-status-badge.disponible {
    background: #10b981;
    color: #fff;
}

.flavor-espacio-status-badge.ocupado {
    background: #ef4444;
    color: #fff;
}

.flavor-loading {
    text-align: center;
    padding: 40px;
    color: #999;
}

.flavor-header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

@media (max-width: 782px) {
    .flavor-kpi-grid,
    .flavor-grid-two-columns {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Cargar datos del dashboard
    cargarDatosDashboard();

    // Actualizar cada 30 segundos
    setInterval(cargarEstadoActual, 30000);

    function cargarDatosDashboard() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_get_dashboard_data'
            },
            success: function(response) {
                if (response.success) {
                    actualizarKPIs(response.data.kpis);
                    renderizarGraficoUso(response.data.uso_espacios);
                    renderizarGraficoDias(response.data.dias_semana);
                    renderizarRanking(response.data.ranking);
                    renderizarProximasReservas(response.data.proximas);
                    cargarEstadoActual();
                }
            }
        });
    }

    function actualizarKPIs(kpis) {
        $('#total-espacios').text(kpis.total_espacios);
        $('#reservas-activas').text(kpis.reservas_activas);
        $('#usuarios-activos').text(kpis.usuarios_activos);
        $('#tasa-ocupacion').text(kpis.tasa_ocupacion + '%');
    }

    function renderizarGraficoUso(datos) {
        const ctx = document.getElementById('grafico-uso-espacios').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: datos.labels,
                datasets: [{
                    label: '<?php _e('Horas reservadas', 'flavor-chat-ia'); ?>',
                    data: datos.values,
                    backgroundColor: '#2271b1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function renderizarGraficoDias(datos) {
        const ctx = document.getElementById('grafico-dias-semana').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: '<?php _e('Reservas', 'flavor-chat-ia'); ?>',
                    data: datos,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }

    function renderizarRanking(espacios) {
        let html = '';
        espacios.forEach((espacio, index) => {
            html += `
                <div class="flavor-ranking-item">
                    <span class="flavor-ranking-position">${index + 1}</span>
                    <span class="flavor-ranking-name">${espacio.nombre}</span>
                    <span class="flavor-ranking-value">${espacio.reservas} reservas</span>
                </div>
            `;
        });
        $('#ranking-espacios').html(html);
    }

    function renderizarProximasReservas(reservas) {
        let html = '';
        reservas.forEach(reserva => {
            html += `
                <div class="flavor-timeline-item">
                    <div class="flavor-timeline-time">${reserva.hora}</div>
                    <div class="flavor-timeline-content">
                        <div class="flavor-timeline-title">${reserva.espacio}</div>
                        <div class="flavor-timeline-meta">${reserva.usuario} · ${reserva.duracion}</div>
                    </div>
                </div>
            `;
        });
        $('#proximas-reservas').html(html);
    }

    function cargarEstadoActual() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'espacios_comunes_get_estado_actual'
            },
            success: function(response) {
                if (response.success) {
                    renderizarEstadoEspacios(response.data);
                }
            }
        });
    }

    function renderizarEstadoEspacios(espacios) {
        let html = '';
        espacios.forEach(espacio => {
            const estadoClass = espacio.disponible ? 'disponible' : 'ocupado';
            const estadoTexto = espacio.disponible ? '<?php _e('Disponible', 'flavor-chat-ia'); ?>' : '<?php _e('Ocupado', 'flavor-chat-ia'); ?>';
            html += `
                <div class="flavor-espacio-status-card ${estadoClass}">
                    <h4>${espacio.nombre}</h4>
                    <span class="flavor-espacio-status-badge ${estadoClass}">${estadoTexto}</span>
                    ${!espacio.disponible ? `<p style="margin-top: 10px; font-size: 12px;">Hasta ${espacio.hasta}</p>` : ''}
                </div>
            `;
        });
        $('#estado-espacios-actual').html(html);
    }

    // Filtro de período
    $('#filtro-periodo-uso').on('change', function() {
        cargarDatosDashboard();
    });
});
</script>
