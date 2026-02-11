/**
 * Flavor Platform Dashboard Charts & Real-time Updates
 *
 * Maneja los graficos con Chart.js y la actualizacion en tiempo real
 * de las estadisticas del dashboard.
 *
 * @package FlavorPlatform
 * @since 3.0.0
 */

(function($) {
    'use strict';

    /**
     * Controlador principal del Dashboard
     */
    const FlavorDashboardController = {

        // Instancias de graficos
        charts: {
            users: null,
            modules: null,
            roles: null
        },

        // Intervalo de actualizacion
        updateInterval: null,

        // Ultima actualizacion
        lastUpdate: null,

        // Configuracion de colores
        colors: {
            primary: '#2271b1',
            success: '#00a32a',
            warning: '#dba617',
            error: '#d63638',
            info: '#3498db',
            purple: '#9b59b6',
            orange: '#f39c12',
            teal: '#1abc9c',
            gray: '#7f8c8d'
        },

        /**
         * Inicializa el dashboard
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.loadCharts();
            this.startAutoUpdate();
            this.updateLastUpdateTime();
        },

        /**
         * Vincula eventos
         */
        bindEvents: function() {
            const self = this;

            // Boton de actualizar
            $('#flavor-refresh-dashboard').on('click', function() {
                self.refreshDashboard();
            });

            // Modal de notificacion
            $('[data-modal="enviar_notificacion"]').on('click', function(e) {
                e.preventDefault();
                $('#flavor-modal-notificacion').fadeIn(200);
            });

            // Cerrar modal
            $('.flavor-modal-close, .flavor-modal-cancel, .flavor-modal-overlay').on('click', function() {
                $(this).closest('.flavor-modal').fadeOut(200);
            });

            // Enviar notificacion
            $('#flavor-send-notification').on('click', function() {
                self.sendNotification();
            });

            // Acciones rapidas con modal
            $('[data-action]').on('click', function(e) {
                const actionType = $(this).data('action');
                if (actionType === 'send_notification') {
                    e.preventDefault();
                    $('#flavor-modal-notificacion').fadeIn(200);
                }
            });
        },

        /**
         * Inicializa el sortable para los widgets
         */
        initSortable: function() {
            const self = this;

            if ($.fn.sortable) {
                $('.flavor-widgets-row').sortable({
                    handle: '.flavor-widget-handle',
                    connectWith: '.flavor-widgets-row',
                    placeholder: 'flavor-widget-placeholder',
                    tolerance: 'pointer',
                    cursor: 'move',
                    opacity: 0.7,
                    revert: 150,
                    start: function(event, ui) {
                        ui.placeholder.height(ui.item.height());
                        ui.placeholder.width(ui.item.width());
                    },
                    stop: function(event, ui) {
                        self.saveWidgetOrder();
                    }
                });
            }
        },

        /**
         * Guarda el orden de los widgets
         */
        saveWidgetOrder: function() {
            const widgetOrder = [];

            $('.flavor-widgets-row').each(function(rowIndex) {
                const rowWidgets = [];
                $(this).find('.flavor-dashboard-widget').each(function() {
                    rowWidgets.push($(this).data('widget'));
                });
                widgetOrder.push(rowWidgets);
            });

            // Guardar en localStorage por ahora
            localStorage.setItem('flavorDashboardWidgetOrder', JSON.stringify(widgetOrder));
        },

        /**
         * Carga todos los graficos
         */
        loadCharts: function() {
            this.fetchChartData().then(data => {
                if (data.success) {
                    this.renderUsersChart(data.data.usuarios_por_semana);
                    this.renderModulesChart(data.data.actividad_por_modulo);
                    this.renderRolesChart(data.data.distribucion_roles);
                }
            }).catch(error => {
                console.error('Error cargando graficos:', error);
            });
        },

        /**
         * Obtiene datos de graficos via API
         */
        fetchChartData: function() {
            return $.ajax({
                url: flavorDashboard.restUrl + 'dashboard-charts',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                }
            });
        },

        /**
         * Renderiza grafico de usuarios nuevos por semana
         */
        renderUsersChart: function(data) {
            const ctx = document.getElementById('flavor-chart-users');
            if (!ctx) return;

            // Destruir grafico existente si hay
            if (this.charts.users) {
                this.charts.users.destroy();
            }

            const gradientFill = ctx.getContext('2d').createLinearGradient(0, 0, 0, 200);
            gradientFill.addColorStop(0, 'rgba(34, 113, 177, 0.3)');
            gradientFill.addColorStop(1, 'rgba(34, 113, 177, 0.0)');

            this.charts.users = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.etiquetas || [],
                    datasets: [{
                        label: flavorDashboard.textos.usuariosNuevos,
                        data: data.datos || [],
                        borderColor: this.colors.primary,
                        backgroundColor: gradientFill,
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: this.colors.primary,
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: { size: 13 },
                            bodyFont: { size: 12 },
                            padding: 12,
                            cornerRadius: 6
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: { size: 11 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: { size: 11 },
                                stepSize: 1
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        },

        /**
         * Renderiza grafico de actividad por modulo
         */
        renderModulesChart: function(data) {
            const ctx = document.getElementById('flavor-chart-modules');
            if (!ctx) return;

            if (this.charts.modules) {
                this.charts.modules.destroy();
            }

            // Si no hay datos, mostrar mensaje
            if (!data.etiquetas || data.etiquetas.length === 0) {
                this.showEmptyChart(ctx, flavorDashboard.textos.actividadModulo);
                return;
            }

            this.charts.modules = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.etiquetas,
                    datasets: [{
                        label: flavorDashboard.textos.actividadModulo,
                        data: data.datos,
                        backgroundColor: data.colores || [
                            this.colors.primary,
                            this.colors.success,
                            this.colors.warning,
                            this.colors.info,
                            this.colors.purple,
                            this.colors.orange
                        ],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 6
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: { size: 10 }
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: { size: 11 },
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        /**
         * Renderiza grafico de distribucion de roles
         */
        renderRolesChart: function(data) {
            const ctx = document.getElementById('flavor-chart-roles');
            if (!ctx) return;

            if (this.charts.roles) {
                this.charts.roles.destroy();
            }

            this.charts.roles = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.etiquetas || [],
                    datasets: [{
                        data: data.datos || [],
                        backgroundColor: data.colores || [
                            this.colors.error,
                            this.colors.info,
                            this.colors.success,
                            this.colors.orange,
                            this.colors.purple,
                            this.colors.teal
                        ],
                        borderWidth: 2,
                        borderColor: '#fff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                usePointStyle: true,
                                pointStyle: 'circle',
                                font: { size: 11 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 6,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const porcentaje = ((context.raw / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.raw} (${porcentaje}%)`;
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Muestra un grafico vacio con mensaje
         */
        showEmptyChart: function(ctx, label) {
            const container = ctx.parentElement;
            container.innerHTML = `
                <div class="flavor-chart-empty">
                    <span class="dashicons dashicons-chart-bar"></span>
                    <p>${label}</p>
                    <small>Sin datos disponibles</small>
                </div>
            `;
        },

        /**
         * Inicia la actualizacion automatica
         */
        startAutoUpdate: function() {
            const self = this;

            if (this.updateInterval) {
                clearInterval(this.updateInterval);
            }

            this.updateInterval = setInterval(function() {
                self.refreshDashboard(true);
            }, flavorDashboard.intervaloActualizacion);
        },

        /**
         * Actualiza el dashboard
         */
        refreshDashboard: function(silent = false) {
            const self = this;
            const $refreshBtn = $('#flavor-refresh-dashboard');
            const $updateText = $('.flavor-update-text');

            if (!silent) {
                $refreshBtn.addClass('flavor-loading');
                $updateText.text(flavorDashboard.textos.cargando);
            }

            $.ajax({
                url: flavorDashboard.restUrl + 'dashboard-stats',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateMetrics(response.data.estadisticas);
                        self.updateAlerts(response.data.alertas);
                        self.updateActivity(response.data.actividad);
                        self.lastUpdate = new Date();
                        self.updateLastUpdateTime();
                    }
                },
                error: function() {
                    if (!silent) {
                        $updateText.text(flavorDashboard.textos.error);
                    }
                },
                complete: function() {
                    $refreshBtn.removeClass('flavor-loading');
                }
            });

            // Actualizar graficos tambien
            this.loadCharts();
        },

        /**
         * Actualiza las metricas en la UI
         */
        updateMetrics: function(stats) {
            this.animateValue('#metric-usuarios', stats.usuarios_activos_30d);
            this.animateValue('#metric-modulos', stats.modulos_activos + '/' + stats.modulos_totales);
            this.animateValue('#metric-eventos', stats.eventos_proximos);
            this.animateValue('#metric-pedidos', stats.pedidos_pendientes);
            this.animateValue('#metric-socios', stats.socios_activos);
            this.animateValue('#metric-conversaciones', stats.conversaciones);
        },

        /**
         * Anima el cambio de valor
         */
        animateValue: function(selector, newValue) {
            const $element = $(selector);
            if ($element.length === 0) return;

            const currentValue = $element.text();
            if (currentValue !== String(newValue)) {
                $element.addClass('flavor-value-updated');
                $element.text(newValue);
                setTimeout(function() {
                    $element.removeClass('flavor-value-updated');
                }, 1000);
            }
        },

        /**
         * Actualiza la lista de alertas
         */
        updateAlerts: function(alerts) {
            const $container = $('#flavor-alerts-list');
            if (!$container.length) return;

            if (!alerts || alerts.length === 0) {
                $container.html(`
                    <div class="flavor-alerts-empty">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p>Sin alertas pendientes</p>
                    </div>
                `);
                $('.flavor-alert-badge').remove();
                return;
            }

            let html = '';
            alerts.forEach(function(alert) {
                html += `
                    <a href="${alert.url}" class="flavor-alert-item flavor-alert-${alert.tipo}">
                        <span class="dashicons ${alert.icono}"></span>
                        <span class="flavor-alert-message">${alert.mensaje}</span>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </a>
                `;
            });

            $container.html(html);

            // Actualizar badge
            const $badge = $('.flavor-widget-alerts .flavor-alert-badge');
            if ($badge.length) {
                $badge.text(alerts.length);
            } else {
                $('.flavor-widget-alerts h3').append(`<span class="flavor-alert-badge">${alerts.length}</span>`);
            }
        },

        /**
         * Actualiza la lista de actividad
         */
        updateActivity: function(activity) {
            const $container = $('#flavor-activity-list');
            if (!$container.length) return;

            if (!activity || activity.length === 0) {
                $container.html(`
                    <li class="flavor-activity-empty">
                        <span class="dashicons dashicons-info"></span>
                        Sin actividad reciente
                    </li>
                `);
                return;
            }

            let html = '';
            activity.forEach(function(item) {
                const userHtml = item.usuario ? `<span class="flavor-activity-user">${item.usuario}</span>` : '';
                html += `
                    <li class="flavor-activity-item flavor-activity-${item.tipo}">
                        <span class="flavor-activity-icon dashicons ${item.icono}"></span>
                        <div class="flavor-activity-content">
                            <span class="flavor-activity-title">${item.titulo}</span>
                            ${userHtml}
                        </div>
                        <span class="flavor-activity-time">${item.tiempo}</span>
                    </li>
                `;
            });

            $container.html(html);
        },

        /**
         * Actualiza el texto de ultima actualizacion
         */
        updateLastUpdateTime: function() {
            const $text = $('.flavor-update-text');

            if (!this.lastUpdate) {
                this.lastUpdate = new Date();
            }

            const now = new Date();
            const diffMs = now - this.lastUpdate;
            const diffMins = Math.floor(diffMs / 60000);

            let text = '';
            if (diffMins < 1) {
                text = flavorDashboard.textos.ahora;
            } else if (diffMins === 1) {
                text = flavorDashboard.textos.haceMenos1Min;
            } else {
                text = flavorDashboard.textos.haceMinutos.replace('%d', diffMins);
            }

            $text.text(text);
        },

        /**
         * Envia una notificacion
         */
        sendNotification: function() {
            const titulo = $('#notif-titulo').val();
            const mensaje = $('#notif-mensaje').val();
            const destinatarios = $('#notif-destinatarios').val();

            if (!titulo || !mensaje) {
                alert('Por favor completa todos los campos');
                return;
            }

            const $btn = $('#flavor-send-notification');
            $btn.prop('disabled', true).text('Enviando...');

            $.ajax({
                url: flavorDashboard.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'flavor_dashboard_quick_action',
                    action_id: 'send_notification',
                    nonce: flavorDashboard.ajaxNonce,
                    titulo: titulo,
                    mensaje: mensaje,
                    destinatarios: destinatarios
                },
                success: function(response) {
                    if (response.success) {
                        $('#flavor-modal-notificacion').fadeOut(200);
                        $('#notif-titulo, #notif-mensaje').val('');
                        // Mostrar mensaje de exito
                        alert('Notificacion enviada correctamente');
                    } else {
                        alert('Error: ' + (response.data.message || 'Error desconocido'));
                    }
                },
                error: function() {
                    alert('Error de conexion');
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Enviar');
                }
            });
        }
    };

    // Inicializar cuando el documento este listo
    $(document).ready(function() {
        // Solo inicializar si estamos en la pagina del dashboard
        if ($('.flavor-dashboard-wrapper').length) {
            FlavorDashboardController.init();
        }
    });

    // Exponer para debug
    window.FlavorDashboard = FlavorDashboardController;

})(jQuery);
