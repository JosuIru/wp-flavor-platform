/**
 * App Analytics Dashboard JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function($) {
    'use strict';

    // Charts instances
    let charts = {
        usersSessions: null,
        modules: null,
        platform: null,
        events: null,
        versions: null
    };

    // Realtime interval
    let realtimeInterval = null;

    /**
     * Initialize dashboard
     */
    function init() {
        bindEvents();
        loadAnalytics();
        startRealtimeUpdates();
    }

    /**
     * Bind events
     */
    function bindEvents() {
        $('#refresh-analytics').on('click', loadAnalytics);
        $('#export-analytics').on('click', exportAnalytics);
        $('#analytics-period, #analytics-app, #analytics-platform').on('change', loadAnalytics);
    }

    /**
     * Load analytics data
     */
    function loadAnalytics() {
        const period = $('#analytics-period').val();
        const appType = $('#analytics-app').val();
        const platform = $('#analytics-platform').val();

        // Show loading state
        $('.card-value').text('-');
        $('.card-trend').text('').removeClass('positive negative neutral');

        $.ajax({
            url: flavorAppAnalytics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_get_app_analytics',
                nonce: flavorAppAnalytics.nonce,
                period: period,
                app_type: appType,
                platform: platform
            },
            success: function(response) {
                if (response.success) {
                    updateDashboard(response.data);
                } else {
                    showError(response.data || flavorAppAnalytics.i18n.error);
                }
            },
            error: function() {
                showError(flavorAppAnalytics.i18n.error);
            }
        });
    }

    /**
     * Update dashboard with data
     */
    function updateDashboard(data) {
        // Summary cards
        updateSummaryCards(data.summary);

        // Charts
        updateUsersSessionsChart(data.timeline);
        updateModulesChart(data.modules);
        updatePlatformChart(data.platforms);
        updateEventsChart(data.events);
        updateVersionsChart(data.versions);

        // Tables
        updateTopScreensTable(data.screens);
        updateCrashesTable(data.crashes);
        updateDevicesTable(data.devices);

        // Retention
        updateRetentionHeatmap(data.retention);

        // Realtime
        updateRealtimeData(data.realtime);
    }

    /**
     * Update summary cards
     */
    function updateSummaryCards(summary) {
        // Total users
        $('#total-users').text(formatNumber(summary.total_users));
        updateTrend('#total-users-trend', summary.total_users_trend);

        // Active users
        $('#active-users').text(formatNumber(summary.active_users));
        updateTrend('#active-users-trend', summary.active_users_trend);

        // Sessions
        $('#total-sessions').text(formatNumber(summary.total_sessions));
        updateTrend('#sessions-trend', summary.sessions_trend);

        // Duration
        $('#avg-duration').text(summary.avg_duration + ' min');
        updateTrend('#duration-trend', summary.duration_trend);

        // Events
        $('#total-events').text(formatNumber(summary.total_events));
        updateTrend('#events-trend', summary.events_trend);

        // Crashes
        $('#total-crashes').text(formatNumber(summary.total_crashes));
        updateTrend('#crashes-trend', summary.crashes_trend, true);
    }

    /**
     * Update trend indicator
     */
    function updateTrend(selector, value, inverse) {
        const $trend = $(selector);
        $trend.removeClass('positive negative neutral');

        if (value === 0 || value === null) {
            $trend.addClass('neutral').text('Sin cambios');
        } else if (value > 0) {
            $trend.addClass(inverse ? 'negative' : 'positive').text(value + '%');
        } else {
            $trend.addClass(inverse ? 'positive' : 'negative').text(value + '%');
        }
    }

    /**
     * Update users/sessions chart
     */
    function updateUsersSessionsChart(data) {
        const ctx = document.getElementById('users-sessions-chart');
        if (!ctx) return;

        if (charts.usersSessions) {
            charts.usersSessions.destroy();
        }

        charts.usersSessions = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: flavorAppAnalytics.i18n.users,
                        data: data.users,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: flavorAppAnalytics.i18n.sessions,
                        data: data.sessions,
                        borderColor: '#2193b0',
                        backgroundColor: 'rgba(33, 147, 176, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Update modules chart
     */
    function updateModulesChart(data) {
        const ctx = document.getElementById('modules-chart');
        if (!ctx) return;

        if (charts.modules) {
            charts.modules.destroy();
        }

        const colors = [
            '#667eea', '#764ba2', '#f093fb', '#f5576c',
            '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
            '#fa709a', '#fee140'
        ];

        charts.modules = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: colors.slice(0, data.labels.length),
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    /**
     * Update platform chart
     */
    function updatePlatformChart(data) {
        const ctx = document.getElementById('platform-chart');
        if (!ctx) return;

        if (charts.platform) {
            charts.platform.destroy();
        }

        charts.platform = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: ['#3DDC84', '#007AFF'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '60%'
            }
        });
    }

    /**
     * Update events chart
     */
    function updateEventsChart(data) {
        const ctx = document.getElementById('events-chart');
        if (!ctx) return;

        if (charts.events) {
            charts.events.destroy();
        }

        const colors = [
            '#4facfe', '#00f2fe', '#43e97b', '#38f9d7',
            '#fa709a', '#fee140', '#667eea', '#764ba2'
        ];

        charts.events = new Chart(ctx, {
            type: 'polarArea',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: colors.slice(0, data.labels.length).map(c => c + 'cc')
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    }

    /**
     * Update versions chart
     */
    function updateVersionsChart(data) {
        const ctx = document.getElementById('versions-chart');
        if (!ctx) return;

        if (charts.versions) {
            charts.versions.destroy();
        }

        charts.versions = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        '#2271b1', '#72aee6', '#a7aaad', '#c3c4c7', '#dcdcde'
                    ],
                    borderWidth: 0
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

    /**
     * Update top screens table
     */
    function updateTopScreensTable(data) {
        const $tbody = $('#top-screens-table tbody');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html('<tr><td colspan="4" class="loading">' + flavorAppAnalytics.i18n.noData + '</td></tr>');
            return;
        }

        data.forEach(function(row) {
            const avgTime = Math.round(row.avg_time / 60);
            $tbody.append(`
                <tr>
                    <td><strong>${escapeHtml(row.screen)}</strong></td>
                    <td>${formatNumber(row.visits)}</td>
                    <td>${formatNumber(row.users)}</td>
                    <td>${avgTime} min</td>
                </tr>
            `);
        });
    }

    /**
     * Update crashes table
     */
    function updateCrashesTable(data) {
        const $tbody = $('#recent-crashes-table tbody');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html('<tr><td colspan="4" class="loading">' + flavorAppAnalytics.i18n.noData + '</td></tr>');
            return;
        }

        data.forEach(function(row) {
            const date = new Date(row.date);
            const dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
            $tbody.append(`
                <tr>
                    <td><code>${escapeHtml(row.error?.substring(0, 50) || 'Unknown')}</code></td>
                    <td>v${escapeHtml(row.version || '-')}</td>
                    <td>${escapeHtml(row.device || '-')}</td>
                    <td>${dateStr}</td>
                </tr>
            `);
        });
    }

    /**
     * Update devices table
     */
    function updateDevicesTable(data) {
        const $tbody = $('#top-devices-table tbody');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html('<tr><td colspan="4" class="loading">' + flavorAppAnalytics.i18n.noData + '</td></tr>');
            return;
        }

        data.forEach(function(row) {
            $tbody.append(`
                <tr>
                    <td><strong>${escapeHtml(row.model || 'Unknown')}</strong></td>
                    <td>${escapeHtml(row.os_version || '-')}</td>
                    <td>${formatNumber(row.users)}</td>
                    <td>${row.percentage}%</td>
                </tr>
            `);
        });
    }

    /**
     * Update retention heatmap
     */
    function updateRetentionHeatmap(data) {
        const $container = $('#retention-heatmap');
        $container.empty();

        if (!data || data.length === 0) {
            $container.html('<div class="loading">' + flavorAppAnalytics.i18n.noData + '</div>');
            return;
        }

        // Header row
        let headerHtml = '<div class="retention-row"><div class="retention-label">Cohort</div>';
        for (let i = 0; i <= 6; i++) {
            headerHtml += `<div class="retention-cell" style="background:transparent;color:#646970;">D${i}</div>`;
        }
        headerHtml += '</div>';
        $container.append(headerHtml);

        // Data rows
        data.forEach(function(row) {
            let rowHtml = `<div class="retention-row"><div class="retention-label">${row.cohort}</div>`;
            row.days.forEach(function(value, index) {
                const level = getRetentionLevel(value);
                rowHtml += `<div class="retention-cell level-${level}">${value}%</div>`;
            });
            // Fill empty cells
            for (let i = row.days.length; i <= 6; i++) {
                rowHtml += '<div class="retention-cell level-0">-</div>';
            }
            rowHtml += '</div>';
            $container.append(rowHtml);
        });
    }

    /**
     * Get retention level for color coding
     */
    function getRetentionLevel(value) {
        if (value >= 80) return 5;
        if (value >= 60) return 4;
        if (value >= 40) return 3;
        if (value >= 20) return 2;
        if (value > 0) return 1;
        return 0;
    }

    /**
     * Update realtime data
     */
    function updateRealtimeData(data) {
        if (!data) return;

        $('#realtime-users').text(data.active_users || 0);
        $('#realtime-events').text(data.events_per_min || 0);

        // Update feed
        const $feed = $('#realtime-feed');
        $feed.empty();

        if (data.recent_events && data.recent_events.length > 0) {
            data.recent_events.forEach(function(event) {
                const time = new Date(event.created_at);
                const timeStr = time.toLocaleTimeString();
                $feed.append(`
                    <div class="event-item">
                        <span class="event-time">${timeStr}</span>
                        <span class="event-type">[${event.event_type}]</span>
                        <span class="event-name">${escapeHtml(event.event_name || event.screen_name || '-')}</span>
                    </div>
                `);
            });
        } else {
            $feed.append('<div class="event-item">Sin actividad reciente</div>');
        }
    }

    /**
     * Start realtime updates
     */
    function startRealtimeUpdates() {
        // Update every 30 seconds
        realtimeInterval = setInterval(function() {
            $.ajax({
                url: flavorAppAnalytics.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_get_app_analytics',
                    nonce: flavorAppAnalytics.nonce,
                    period: '7d',
                    app_type: 'all',
                    platform: 'all'
                },
                success: function(response) {
                    if (response.success && response.data.realtime) {
                        updateRealtimeData(response.data.realtime);
                    }
                }
            });
        }, 30000);
    }

    /**
     * Export analytics
     */
    function exportAnalytics() {
        const period = $('#analytics-period').val();

        $.ajax({
            url: flavorAppAnalytics.ajaxUrl,
            type: 'POST',
            data: {
                action: 'flavor_export_app_analytics',
                nonce: flavorAppAnalytics.nonce,
                period: period
            },
            success: function(response) {
                if (response.success) {
                    // Create download
                    const blob = new Blob([response.data.content], { type: 'application/json' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = response.data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    alert(flavorAppAnalytics.i18n.error);
                }
            },
            error: function() {
                alert(flavorAppAnalytics.i18n.error);
            }
        });
    }

    /**
     * Format number with thousands separator
     */
    function formatNumber(num) {
        if (num === null || num === undefined) return '0';
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Show error message
     */
    function showError(message) {
        console.error('Analytics Error:', message);
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);
