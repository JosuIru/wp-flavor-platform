/**
 * App Dashboard Widget JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function($) {
    'use strict';

    let miniChart = null;
    let refreshInterval = null;

    /**
     * Initialize widget
     */
    function init() {
        initMiniChart();
        bindEvents();

        // Auto-refresh si está habilitado
        if (flavorAppWidget.refreshInterval) {
            startAutoRefresh();
        }
    }

    /**
     * Initialize mini chart
     */
    function initMiniChart() {
        const canvas = document.getElementById('app-widget-mini-chart');
        if (!canvas || typeof Chart === 'undefined') {
            // Si Chart.js no está cargado, cargarlo dinámicamente
            loadChartJS().then(() => {
                createChart(canvas);
            });
            return;
        }

        createChart(canvas);
    }

    /**
     * Load Chart.js dynamically
     */
    function loadChartJS() {
        return new Promise((resolve, reject) => {
            if (typeof Chart !== 'undefined') {
                resolve();
                return;
            }

            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    /**
     * Create the mini chart
     */
    function createChart(canvas) {
        if (!canvas) return;

        const ctx = canvas.getContext('2d');

        // Datos de ejemplo (se actualizarán con AJAX)
        const labels = getLast7Days();
        const data = [0, 0, 0, 0, 0, 0, 0];

        miniChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 0,
                    pointHoverRadius: 4,
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
                        mode: 'index',
                        intersect: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' usuarios';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: false
                    },
                    y: {
                        display: false,
                        beginAtZero: true
                    }
                },
                interaction: {
                    mode: 'nearest',
                    axis: 'x',
                    intersect: false
                }
            }
        });
    }

    /**
     * Get last 7 days labels
     */
    function getLast7Days() {
        const days = [];
        const dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            days.push(dayNames[date.getDay()]);
        }

        return days;
    }

    /**
     * Bind events
     */
    function bindEvents() {
        // Refresh button
        $('#refresh-app-widget').on('click', function() {
            refreshStats();
        });
    }

    /**
     * Start auto-refresh
     */
    function startAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }

        refreshInterval = setInterval(refreshStats, flavorAppWidget.refreshInterval);
    }

    /**
     * Refresh stats via AJAX
     */
    function refreshStats() {
        const $btn = $('#refresh-app-widget');
        const $widget = $('#flavor-app-widget');

        $btn.addClass('spinning');

        $.ajax({
            url: flavorAppWidget.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_app_widget_refresh',
                nonce: flavorAppWidget.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateWidget(response.data);
                }
            },
            error: function() {
                console.error('Error refreshing app widget');
            },
            complete: function() {
                $btn.removeClass('spinning');
            }
        });
    }

    /**
     * Update widget with new data
     */
    function updateWidget(data) {
        const stats = data.stats;

        // Update metric values with animation
        animateValue('[data-metric="active_users"]', stats.active_users);
        animateValue('[data-metric="sessions_today"]', stats.sessions_today);
        animateValue('[data-metric="total_downloads"]', stats.total_downloads);

        // Update trend
        const $trend = $('.metric-trend');
        $trend.removeClass('up down')
              .addClass(stats.users_trend >= 0 ? 'up' : 'down')
              .text((stats.users_trend >= 0 ? '+' : '') + stats.users_trend + '%');

        // Update chart
        if (miniChart && stats.chart_data) {
            updateChart(stats.chart_data);
        }

        // Update timestamp
        $('#widget-timestamp').text(data.timestamp);
    }

    /**
     * Animate value change
     */
    function animateValue(selector, newValue) {
        const $el = $(selector);
        const currentValue = parseInt($el.text().replace(/,/g, '')) || 0;

        if (currentValue === newValue) return;

        $({ value: currentValue }).animate({ value: newValue }, {
            duration: 500,
            step: function() {
                $el.text(Math.floor(this.value).toLocaleString());
            },
            complete: function() {
                $el.text(newValue.toLocaleString());
            }
        });
    }

    /**
     * Update chart with new data
     */
    function updateChart(chartData) {
        if (!miniChart) return;

        const labels = chartData.map(item => {
            const date = new Date(item.date);
            return ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'][date.getDay()];
        });

        const data = chartData.map(item => item.users);

        miniChart.data.labels = labels;
        miniChart.data.datasets[0].data = data;
        miniChart.update('active');
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
