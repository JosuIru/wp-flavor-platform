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
            roles: null,
            comparatives: null
        },

        // Instancia del mapa
        map: null,
        mapMarkers: [],
        heatmapLayer: null,
        showHeatmap: false,

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

        // Colores por tipo de entidad para el mapa
        entityColors: {
            comunidad: '#2271b1',
            cooperativa: '#00a32a',
            asociacion: '#3498db',
            empresa: '#9b59b6',
            colectivo: '#1abc9c',
            fundacion: '#f39c12',
            otro: '#7f8c8d'
        },

        /**
         * Inicializa el dashboard
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.loadCharts();
            this.loadNetworkStats();
            this.initActivityMap();
            this.loadComparativesChart();
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

            // Sincronizar red
            $('#flavor-sync-network').on('click', function() {
                self.syncNetwork();
            });

            // Exportar estadisticas
            $('#flavor-export-stats').on('click', function() {
                self.exportStats();
            });

            // Filtros del mapa
            $('#flavor-map-filter-type').on('change', function() {
                self.filterMap($(this).val());
            });

            // Toggle heatmap
            $('#flavor-map-toggle-heatmap').on('click', function() {
                self.toggleHeatmap();
            });

            // Selector de periodo para comparativas
            $('#flavor-chart-period').on('change', function() {
                self.loadComparativesChart($(this).val());
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
                if (data.success && data.data) {
                    if (data.data.usuarios_por_semana) {
                        this.renderUsersChart(data.data.usuarios_por_semana);
                    }
                    if (data.data.actividad_por_modulo) {
                        this.renderModulesChart(data.data.actividad_por_modulo);
                    }
                    if (data.data.distribucion_roles) {
                        this.renderRolesChart(data.data.distribucion_roles);
                    }
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
            if (!ctx || !data) return;

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
            if (!ctx || !data) return;

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
            if (!ctx || !data) return;

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

        // =========================================================================
        // RED DE COMUNIDADES
        // =========================================================================

        /**
         * Carga estadisticas de la red
         */
        loadNetworkStats: function() {
            const self = this;

            $.ajax({
                url: flavorDashboard.restUrl + 'network-stats',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.updateNetworkUI(response.data);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Red de comunidades no disponible:', error);
                }
            });
        },

        /**
         * Actualiza la UI de estadisticas de red
         */
        updateNetworkUI: function(data) {
            // Las estadisticas se actualizan via PHP en la carga inicial
            // Este metodo se usa para actualizaciones en tiempo real si es necesario
            if (data.estadisticas) {
                $('.flavor-network-stat-value').each(function() {
                    const $this = $(this);
                    const metricType = $this.closest('.flavor-network-stat').index();
                    // Actualizar valores con animacion si cambian
                });
            }
        },

        /**
         * Sincroniza la red manualmente
         */
        syncNetwork: function() {
            const self = this;
            const $btn = $('#flavor-sync-network');
            const $icon = $btn.find('.dashicons');

            $btn.prop('disabled', true);
            $icon.addClass('flavor-spinning');

            $.ajax({
                url: flavorDashboard.restUrl + 'network-sync',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#flavor-last-sync-time').text(flavorDashboard.textos?.ahora || 'Ahora');
                        self.loadNetworkStats();
                        self.showNotice('success', response.message || 'Sincronizacion completada');
                    } else {
                        self.showNotice('error', response.message || 'Error en la sincronizacion');
                    }
                },
                error: function() {
                    self.showNotice('error', 'Error de conexion');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                    $icon.removeClass('flavor-spinning');
                }
            });
        },

        // =========================================================================
        // MAPA DE ACTIVIDAD
        // =========================================================================

        /**
         * Inicializa el mapa de actividad
         */
        initActivityMap: function() {
            const self = this;
            const $mapContainer = $('#flavor-activity-map');

            if (!$mapContainer.length) return;

            // Verificar si Leaflet esta disponible
            if (typeof L === 'undefined') {
                // Cargar Leaflet dinamicamente
                this.loadLeaflet().then(function() {
                    self.createMap();
                }).catch(function() {
                    $mapContainer.html('<div class="flavor-map-error"><p>No se pudo cargar el mapa</p></div>');
                });
            } else {
                this.createMap();
            }
        },

        /**
         * Carga Leaflet dinamicamente
         */
        loadLeaflet: function() {
            return new Promise(function(resolve, reject) {
                // CSS de Leaflet
                if (!$('link[href*="leaflet"]').length) {
                    $('head').append('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />');
                }

                // JS de Leaflet
                if (typeof L === 'undefined') {
                    $.getScript('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js')
                        .done(function() {
                            // Cargar plugin heatmap
                            $.getScript('https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js')
                                .done(resolve)
                                .fail(resolve); // Continuar aunque no cargue el heatmap
                        })
                        .fail(reject);
                } else {
                    resolve();
                }
            });
        },

        /**
         * Crea el mapa
         */
        createMap: function() {
            const self = this;
            const $mapContainer = $('#flavor-activity-map');
            const centerData = $mapContainer.data('center') || { lat: 40.4168, lng: -3.7038 };
            const zoomLevel = $mapContainer.data('zoom') || 6;

            // Limpiar contenido de carga
            $mapContainer.empty();

            // Crear mapa
            this.map = L.map('flavor-activity-map').setView([centerData.lat, centerData.lng], zoomLevel);

            // Agregar capa de tiles (OpenStreetMap)
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 18
            }).addTo(this.map);

            // Cargar datos del mapa
            this.loadMapData();
        },

        /**
         * Carga datos para el mapa
         */
        loadMapData: function(filters) {
            const self = this;
            let queryParams = '';

            if (filters) {
                const params = new URLSearchParams();
                if (filters.tipo) params.append('tipo', filters.tipo);
                if (filters.pais) params.append('pais', filters.pais);
                queryParams = '?' + params.toString();
            }

            $.ajax({
                url: flavorDashboard.restUrl + 'activity-map' + queryParams,
                method: 'GET',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.renderMapMarkers(response.data.nodos);
                        self.prepareHeatmapData(response.data.heatmap);
                    }
                },
                error: function() {
                    console.log('Error cargando datos del mapa');
                }
            });
        },

        /**
         * Renderiza marcadores en el mapa
         */
        renderMapMarkers: function(nodes) {
            const self = this;

            // Limpiar marcadores existentes
            this.mapMarkers.forEach(function(marker) {
                self.map.removeLayer(marker);
            });
            this.mapMarkers = [];

            if (!nodes || nodes.length === 0) return;

            nodes.forEach(function(node) {
                if (!node.lat || !node.lng) return;

                const color = self.entityColors[node.tipo_entidad] || self.entityColors.otro;

                // Crear icono personalizado
                const iconHtml = `
                    <div class="flavor-map-marker" style="background-color: ${color};">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                    </div>
                `;

                const customIcon = L.divIcon({
                    html: iconHtml,
                    className: 'flavor-marker-container',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                    popupAnchor: [0, -32]
                });

                const marker = L.marker([node.lat, node.lng], { icon: customIcon })
                    .addTo(self.map);

                // Popup con informacion del nodo
                const popupContent = `
                    <div class="flavor-map-popup">
                        ${node.logo_url ? `<img src="${node.logo_url}" alt="${node.nombre}" class="flavor-popup-logo">` : ''}
                        <strong>${node.nombre}</strong>
                        <span class="flavor-popup-type">${node.tipo_entidad}</span>
                        <span class="flavor-popup-location">${node.ciudad}, ${node.pais}</span>
                        ${node.miembros_count > 0 ? `<span class="flavor-popup-members">${node.miembros_count} miembros</span>` : ''}
                    </div>
                `;

                marker.bindPopup(popupContent);
                marker._nodeData = node;

                self.mapMarkers.push(marker);
            });

            // Ajustar vista al mostrar todos los marcadores
            if (this.mapMarkers.length > 1) {
                const group = L.featureGroup(this.mapMarkers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }
        },

        /**
         * Prepara datos para el heatmap
         */
        prepareHeatmapData: function(heatmapData) {
            this.heatmapData = heatmapData || [];
        },

        /**
         * Alterna la visualizacion del heatmap
         */
        toggleHeatmap: function() {
            const self = this;

            if (!this.map || typeof L.heatLayer === 'undefined') {
                console.log('Heatmap no disponible');
                return;
            }

            this.showHeatmap = !this.showHeatmap;
            const $btn = $('#flavor-map-toggle-heatmap');

            if (this.showHeatmap) {
                // Ocultar marcadores
                this.mapMarkers.forEach(function(marker) {
                    marker.setOpacity(0.3);
                });

                // Crear heatmap si no existe
                if (!this.heatmapLayer && this.heatmapData && this.heatmapData.length > 0) {
                    const heatPoints = this.heatmapData.map(function(point) {
                        return [point.lat, point.lng, point.intensity || 1];
                    });

                    this.heatmapLayer = L.heatLayer(heatPoints, {
                        radius: 25,
                        blur: 15,
                        maxZoom: 10,
                        gradient: {
                            0.4: 'blue',
                            0.6: 'cyan',
                            0.7: 'lime',
                            0.8: 'yellow',
                            1.0: 'red'
                        }
                    }).addTo(this.map);
                } else if (this.heatmapLayer) {
                    this.heatmapLayer.addTo(this.map);
                }

                $btn.addClass('active');
            } else {
                // Mostrar marcadores
                this.mapMarkers.forEach(function(marker) {
                    marker.setOpacity(1);
                });

                // Ocultar heatmap
                if (this.heatmapLayer) {
                    this.map.removeLayer(this.heatmapLayer);
                }

                $btn.removeClass('active');
            }
        },

        /**
         * Filtra el mapa por tipo
         */
        filterMap: function(type) {
            this.loadMapData({ tipo: type });
        },

        // =========================================================================
        // GRAFICOS COMPARATIVOS
        // =========================================================================

        /**
         * Carga el grafico de comparativas
         */
        loadComparativesChart: function(period) {
            const self = this;
            period = period || 'weekly';

            $.ajax({
                url: flavorDashboard.restUrl + 'dashboard-charts',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                },
                data: { period: period },
                success: function(response) {
                    if (response.success && response.data.comparativas) {
                        self.renderComparativesChart(response.data.comparativas, period);
                    }
                },
                error: function() {
                    console.log('Error cargando datos comparativos');
                }
            });
        },

        /**
         * Renderiza el grafico de comparativas
         */
        renderComparativesChart: function(data, period) {
            const ctx = document.getElementById('flavor-chart-comparatives');
            if (!ctx) return;

            if (this.charts.comparatives) {
                this.charts.comparatives.destroy();
            }

            let chartData = {};
            let chartLabel = '';

            switch (period) {
                case 'monthly':
                    chartData = data.usuarios_mensual || { etiquetas: [], actual: [] };
                    chartLabel = flavorDashboard.textos?.usuariosMes || 'Usuarios por mes';
                    break;
                case 'hourly':
                    chartData = data.actividad_por_hora || { etiquetas: [], valores: [] };
                    chartLabel = flavorDashboard.textos?.actividadHora || 'Actividad por hora';
                    break;
                default: // weekly
                    chartData = data.conversaciones_semanal || { etiquetas: [], valores: [] };
                    chartLabel = flavorDashboard.textos?.conversacionesSemana || 'Conversaciones semanales';
            }

            const dataValues = chartData.valores || chartData.actual || [];

            const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 200);
            gradient.addColorStop(0, 'rgba(0, 163, 42, 0.3)');
            gradient.addColorStop(1, 'rgba(0, 163, 42, 0.0)');

            this.charts.comparatives = new Chart(ctx, {
                type: period === 'hourly' ? 'bar' : 'line',
                data: {
                    labels: chartData.etiquetas || [],
                    datasets: [{
                        label: chartLabel,
                        data: dataValues,
                        borderColor: this.colors.success,
                        backgroundColor: period === 'hourly' ? this.colors.success : gradient,
                        borderWidth: 2,
                        fill: period !== 'hourly',
                        tension: 0.4,
                        borderRadius: period === 'hourly' ? 4 : 0,
                        pointRadius: period === 'hourly' ? 0 : 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: this.colors.success,
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                font: { size: 11 },
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 6
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 10 } }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0, 0, 0, 0.05)' },
                            ticks: { font: { size: 11 }, stepSize: 1 }
                        }
                    }
                }
            });
        },

        // =========================================================================
        // EXPORTACION DE ESTADISTICAS
        // =========================================================================

        /**
         * Exporta estadisticas a CSV
         */
        exportStats: function() {
            const self = this;
            const exportType = 'general'; // Podria ser configurable

            $.ajax({
                url: flavorDashboard.restUrl + 'export-stats',
                method: 'GET',
                headers: {
                    'X-WP-Nonce': flavorDashboard.nonce
                },
                data: { tipo: exportType },
                success: function(response) {
                    if (response.success && response.data) {
                        self.downloadCSV(response.data, response.filename || 'flavor_stats.csv');
                    }
                },
                error: function() {
                    self.showNotice('error', 'Error al exportar estadisticas');
                }
            });
        },

        /**
         * Descarga datos como CSV
         */
        downloadCSV: function(data, filename) {
            let csvContent = '';

            data.forEach(function(row) {
                csvContent += row.map(function(cell) {
                    // Escapar comillas y envolver en comillas si contiene comas
                    const cellStr = String(cell);
                    if (cellStr.includes(',') || cellStr.includes('"') || cellStr.includes('\n')) {
                        return '"' + cellStr.replace(/"/g, '""') + '"';
                    }
                    return cellStr;
                }).join(',') + '\n';
            });

            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');

            if (navigator.msSaveBlob) {
                navigator.msSaveBlob(blob, filename);
            } else {
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                link.style.display = 'none';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
        },

        /**
         * Muestra una notificacion
         */
        showNotice: function(type, message) {
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.flavor-dashboard-header').after($notice);

            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
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
