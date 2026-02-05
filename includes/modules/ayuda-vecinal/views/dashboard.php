<?php
/**
 * Dashboard de Ayuda Vecinal
 * Vista general de solicitudes y voluntarios
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap flavor-ayuda-dashboard">
    <h1 class="wp-heading-inline">
        <?php _e('Ayuda Vecinal - Dashboard', 'flavor-chat-ia'); ?>
    </h1>
    <hr class="wp-header-end">

    <!-- KPIs principales -->
    <div class="flavor-kpi-grid">
        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-sos"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Solicitudes Activas', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="solicitudes-activas">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('pendientes de asignación', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Voluntarios Activos', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="voluntarios-activos">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('disponibles este mes', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Ayudas Completadas', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="ayudas-completadas">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('este mes', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <div class="flavor-kpi-card">
            <div class="flavor-kpi-icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="flavor-kpi-content">
                <h3><?php _e('Impacto Social', 'flavor-chat-ia'); ?></h3>
                <div class="flavor-kpi-value" id="horas-voluntariado">0</div>
                <p class="flavor-kpi-subtitle"><?php _e('horas de voluntariado', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Gráficos y estadísticas -->
    <div class="flavor-grid-two-columns">
        <!-- Solicitudes por categoría -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Solicitudes por Categoría', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <canvas id="grafico-categorias" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Tendencia de solicitudes -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Tendencia de Solicitudes', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <canvas id="grafico-tendencia" width="400" height="300"></canvas>
            </div>
        </div>

        <!-- Solicitudes urgentes -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Solicitudes Urgentes', 'flavor-chat-ia'); ?></h2>
                <a href="?page=ayuda-vecinal-solicitudes&urgente=1" class="button button-small">
                    <?php _e('Ver todas', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <div class="flavor-card-body">
                <div id="solicitudes-urgentes-list">
                    <div class="flavor-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>

        <!-- Voluntarios destacados -->
        <div class="flavor-card">
            <div class="flavor-card-header">
                <h2><?php _e('Voluntarios Destacados', 'flavor-chat-ia'); ?></h2>
            </div>
            <div class="flavor-card-body">
                <div id="voluntarios-destacados-list">
                    <div class="flavor-loading"><?php _e('Cargando...', 'flavor-chat-ia'); ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actividad reciente -->
    <div class="flavor-card">
        <div class="flavor-card-header">
            <h2><?php _e('Actividad Reciente', 'flavor-chat-ia'); ?></h2>
        </div>
        <div class="flavor-card-body">
            <div class="flavor-timeline" id="actividad-reciente">
                <div class="flavor-loading"><?php _e('Cargando actividad...', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</div>

<style>
.flavor-ayuda-dashboard {
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

.flavor-loading {
    text-align: center;
    padding: 40px;
    color: #999;
}

.flavor-solicitud-urgente {
    padding: 12px;
    border-left: 4px solid #ef4444;
    background: #fef2f2;
    border-radius: 4px;
    margin-bottom: 10px;
}

.flavor-solicitud-urgente h4 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.flavor-solicitud-urgente p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.flavor-voluntario-destacado {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.flavor-voluntario-destacado:last-child {
    border-bottom: none;
}

.flavor-voluntario-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: #6b7280;
}

.flavor-voluntario-info {
    flex: 1;
}

.flavor-voluntario-nombre {
    font-weight: 600;
    font-size: 14px;
}

.flavor-voluntario-stats {
    font-size: 12px;
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

.flavor-timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-timeline-icon.nueva {
    background: #dbeafe;
    color: #1e40af;
}

.flavor-timeline-icon.asignada {
    background: #fef3c7;
    color: #92400e;
}

.flavor-timeline-icon.completada {
    background: #d1fae5;
    color: #065f46;
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

    // Actualizar cada 60 segundos
    setInterval(cargarDatosDashboard, 60000);

    function cargarDatosDashboard() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'ayuda_vecinal_get_dashboard_data'
            },
            success: function(response) {
                if (response.success) {
                    actualizarKPIs(response.data.kpis);
                    renderizarGraficoCategorias(response.data.categorias);
                    renderizarGraficoTendencia(response.data.tendencia);
                    renderizarSolicitudesUrgentes(response.data.urgentes);
                    renderizarVoluntariosDestacados(response.data.destacados);
                    renderizarActividadReciente(response.data.actividad);
                }
            }
        });
    }

    function actualizarKPIs(kpis) {
        $('#solicitudes-activas').text(kpis.solicitudes_activas);
        $('#voluntarios-activos').text(kpis.voluntarios_activos);
        $('#ayudas-completadas').text(kpis.ayudas_completadas);
        $('#horas-voluntariado').text(kpis.horas_voluntariado);
    }

    function renderizarGraficoCategorias(datos) {
        const ctx = document.getElementById('grafico-categorias').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: datos.labels,
                datasets: [{
                    data: datos.values,
                    backgroundColor: [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#ec4899'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function renderizarGraficoTendencia(datos) {
        const ctx = document.getElementById('grafico-tendencia').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: datos.labels,
                datasets: [{
                    label: '<?php _e('Solicitudes', 'flavor-chat-ia'); ?>',
                    data: datos.values,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    tension: 0.4,
                    fill: true
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

    function renderizarSolicitudesUrgentes(solicitudes) {
        if (solicitudes.length === 0) {
            $('#solicitudes-urgentes-list').html('<p style="text-align: center; color: #999;"><?php _e('No hay solicitudes urgentes', 'flavor-chat-ia'); ?></p>');
            return;
        }

        let html = '';
        solicitudes.forEach(solicitud => {
            html += `
                <div class="flavor-solicitud-urgente">
                    <h4>${solicitud.titulo}</h4>
                    <p>${solicitud.solicitante} · ${solicitud.categoria}</p>
                    <p style="margin-top: 5px;"><strong><?php _e('Hace', 'flavor-chat-ia'); ?> ${solicitud.tiempo}</strong></p>
                </div>
            `;
        });
        $('#solicitudes-urgentes-list').html(html);
    }

    function renderizarVoluntariosDestacados(voluntarios) {
        let html = '';
        voluntarios.forEach(voluntario => {
            html += `
                <div class="flavor-voluntario-destacado">
                    <div class="flavor-voluntario-avatar">${voluntario.iniciales}</div>
                    <div class="flavor-voluntario-info">
                        <div class="flavor-voluntario-nombre">${voluntario.nombre}</div>
                        <div class="flavor-voluntario-stats">${voluntario.ayudas_completadas} ayudas · ${voluntario.valoracion} ⭐</div>
                    </div>
                </div>
            `;
        });
        $('#voluntarios-destacados-list').html(html);
    }

    function renderizarActividadReciente(actividad) {
        let html = '';
        actividad.forEach(item => {
            const iconClass = item.tipo === 'nueva' ? 'nueva' : (item.tipo === 'asignada' ? 'asignada' : 'completada');
            html += `
                <div class="flavor-timeline-item">
                    <div class="flavor-timeline-icon ${iconClass}">
                        <span class="dashicons dashicons-${item.tipo === 'nueva' ? 'plus' : (item.tipo === 'asignada' ? 'admin-users' : 'yes')}"></span>
                    </div>
                    <div class="flavor-timeline-content">
                        <div class="flavor-timeline-title">${item.titulo}</div>
                        <div class="flavor-timeline-meta">${item.descripcion} · ${item.tiempo}</div>
                    </div>
                </div>
            `;
        });
        $('#actividad-reciente').html(html);
    }
});
</script>
