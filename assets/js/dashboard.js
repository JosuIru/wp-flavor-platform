/**
 * Dashboard JavaScript
 *
 * @package FlavorChatIA
 */

(function($) {
    'use strict';

    const FlavorDashboard = {
        config: {},
        selectedWidgets: [],

        /**
         * Inicializar
         */
        init() {
            this.config = window.flavorDashboard || {};
            this.selectedWidgets = [...(this.config.userLayout || [])];

            this.loadDashboard();
            this.bindEvents();
        },

        /**
         * Cargar dashboard
         */
        loadDashboard() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_dashboard_data',
                    nonce: this.config.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderWidgets(response.data.widgets);
                        this.initCharts();
                    } else {
                        this.showError('Error al cargar el dashboard');
                    }
                },
                error: () => {
                    this.showError('Error de conexión');
                }
            });
        },

        /**
         * Renderizar widgets
         */
        renderWidgets(widgets) {
            const grid = $('#flavor-dashboard-grid');
            grid.empty();

            Object.values(widgets).forEach(widget => {
                const widgetHtml = this.createWidgetHtml(widget);
                grid.append(widgetHtml);
            });
        },

        /**
         * Crear HTML del widget
         */
        createWidgetHtml(widget) {
            return `
                <div class="flavor-dashboard-widget size-${widget.size}" data-widget-id="${widget.id}">
                    <div class="flavor-widget-header">
                        <div class="flavor-widget-title">
                            <span class="${widget.icon}"></span>
                            ${widget.title}
                        </div>
                        <div class="flavor-widget-actions">
                            <button type="button" class="flavor-widget-action flavor-refresh-widget" title="Actualizar">
                                <span class="dashicons dashicons-update"></span>
                            </button>
                        </div>
                    </div>
                    <div class="flavor-widget-body">
                        ${widget.html}
                    </div>
                </div>
            `;
        },

        /**
         * Inicializar gráficos
         */
        initCharts() {
            const chartCanvas = document.getElementById('flavor-activity-chart');
            if (!chartCanvas) return;

            const chartData = JSON.parse(chartCanvas.dataset.chart || '[]');

            if (typeof Chart === 'undefined') {
                console.warn('Chart.js no está disponible');
                return;
            }

            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: chartData.map(item => item.date),
                    datasets: [{
                        label: 'Conversaciones',
                        data: chartData.map(item => item.count),
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#667eea',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6
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
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        },

        /**
         * Vincular eventos
         */
        bindEvents() {
            // Abrir modal de personalización
            $('#flavor-customize-dashboard').on('click', () => {
                this.openCustomizeModal();
            });

            // Cerrar modal
            $('.flavor-dashboard-modal-close').on('click', () => {
                this.closeCustomizeModal();
            });

            // Click fuera del modal
            $('#flavor-customize-modal').on('click', (e) => {
                if ($(e.target).is('#flavor-customize-modal')) {
                    this.closeCustomizeModal();
                }
            });

            // Filtrar por categoría
            $(document).on('click', '.flavor-widget-categories button', (e) => {
                const button = $(e.currentTarget);
                const category = button.data('category');

                $('.flavor-widget-categories button').removeClass('active');
                button.addClass('active');

                this.filterWidgetsByCategory(category);
            });

            // Seleccionar widget
            $(document).on('click', '.flavor-available-widget', (e) => {
                const widget = $(e.currentTarget);
                const widgetId = widget.data('widget-id');

                widget.toggleClass('selected');

                if (widget.hasClass('selected')) {
                    if (!this.selectedWidgets.includes(widgetId)) {
                        this.selectedWidgets.push(widgetId);
                    }
                } else {
                    this.selectedWidgets = this.selectedWidgets.filter(id => id !== widgetId);
                }
            });

            // Guardar layout
            $('#flavor-save-layout').on('click', () => {
                this.saveLayout();
            });

            // Restablecer layout
            $('#flavor-reset-layout').on('click', () => {
                this.resetLayout();
            });

            // Actualizar widget individual
            $(document).on('click', '.flavor-refresh-widget', (e) => {
                const widget = $(e.currentTarget).closest('.flavor-dashboard-widget');
                const widgetId = widget.data('widget-id');
                this.refreshWidget(widgetId, widget);
            });
        },

        /**
         * Abrir modal de personalización
         */
        openCustomizeModal() {
            this.renderAvailableWidgets();
            $('#flavor-customize-modal').show();
        },

        /**
         * Cerrar modal de personalización
         */
        closeCustomizeModal() {
            $('#flavor-customize-modal').hide();
        },

        /**
         * Renderizar widgets disponibles
         */
        renderAvailableWidgets() {
            const container = $('#flavor-available-widgets');
            container.empty();

            Object.entries(this.config.widgets).forEach(([widgetId, widget]) => {
                const isSelected = this.selectedWidgets.includes(widgetId);
                const html = `
                    <div class="flavor-available-widget ${isSelected ? 'selected' : ''}"
                         data-widget-id="${widgetId}"
                         data-category="${widget.category}">
                        <span class="${widget.icon}"></span>
                        <div class="flavor-available-widget-info">
                            <h4>${widget.title}</h4>
                            <p>${widget.description}</p>
                        </div>
                    </div>
                `;
                container.append(html);
            });
        },

        /**
         * Filtrar widgets por categoría
         */
        filterWidgetsByCategory(category) {
            const widgets = $('.flavor-available-widget');

            if (category === 'all') {
                widgets.show();
            } else {
                widgets.each(function() {
                    const widgetCategory = $(this).data('category');
                    $(this).toggle(widgetCategory === category);
                });
            }
        },

        /**
         * Guardar layout
         */
        saveLayout() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_save_dashboard_layout',
                    nonce: this.config.nonce,
                    layout: this.selectedWidgets
                },
                success: (response) => {
                    if (response.success) {
                        this.closeCustomizeModal();
                        this.loadDashboard();
                        this.showNotice('Layout guardado correctamente');
                    } else {
                        this.showError(response.data?.message || 'Error al guardar');
                    }
                },
                error: () => {
                    this.showError('Error de conexión');
                }
            });
        },

        /**
         * Restablecer layout
         */
        resetLayout() {
            this.selectedWidgets = [
                'chat_stats',
                'activity_chart',
                'recent_conversations',
                'quick_actions',
                'module_status',
                'api_usage'
            ];
            this.renderAvailableWidgets();
        },

        /**
         * Actualizar widget individual
         */
        refreshWidget(widgetId, widgetElement) {
            const body = widgetElement.find('.flavor-widget-body');
            body.css('opacity', '0.5');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_widget_data',
                    nonce: this.config.nonce,
                    widget_id: widgetId
                },
                success: (response) => {
                    body.css('opacity', '1');
                    if (response.success) {
                        body.html(response.data.html);

                        // Reinicializar gráficos si es necesario
                        if (widgetId === 'activity_chart') {
                            this.initCharts();
                        }
                    }
                },
                error: () => {
                    body.css('opacity', '1');
                }
            });
        },

        /**
         * Mostrar error
         */
        showError(message) {
            console.error(message);
            // Implementar notificación visual
        },

        /**
         * Mostrar notificación
         */
        showNotice(message) {
            console.log(message);
            // Implementar notificación visual
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(() => {
        FlavorDashboard.init();
    });

})(jQuery);
