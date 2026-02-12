/**
 * Flavor Systems Admin Panel JavaScript
 * Version: 3.0.0
 */

(function($) {
    'use strict';

    const FlavorSystemsAdmin = {

        charts: {},

        /**
         * Inicializar
         */
        init: function() {
            this.initTabs();
            this.loadSystemStats();
            this.initCharts();
            this.bindEvents();
            this.loadCategoryModules();
            this.loadDependencies();
        },

        /**
         * Inicializar tabs
         */
        initTabs: function() {
            if (typeof $.fn.tabs !== 'undefined') {
                $('#flavor-tabs').tabs({
                    activate: function(event, ui) {
                        // Refrescar gráficos al cambiar de tab
                        if (ui.newPanel.attr('id') === 'tab-overview') {
                            FlavorSystemsAdmin.refreshCharts();
                        }
                    }
                });
            }
        },

        /**
         * Cargar estadísticas del sistema
         */
        loadSystemStats: function() {
            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_get_system_stats',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorSystemsAdmin.updateStatsDisplay(response.data);
                    }
                }
            });
        },

        /**
         * Actualizar display de estadísticas
         */
        updateStatsDisplay: function(stats) {
            // Ya se muestran en PHP, esto es para actualizaciones en tiempo real
            console.log('Stats updated:', stats);
        },

        /**
         * Inicializar gráficos
         */
        initCharts: function() {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js no está cargado');
                return;
            }

            this.createNotificationsChart();
            this.createModulesChart();
        },

        /**
         * Crear gráfico de notificaciones
         */
        createNotificationsChart: function() {
            const ctx = document.getElementById('notifications-chart');
            if (!ctx) return;

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_get_notifications_stats',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.daily) {
                        const labels = response.data.daily.map(item => item.date);
                        const data = response.data.daily.map(item => parseInt(item.count));

                        FlavorSystemsAdmin.charts.notifications = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Notificaciones por Día',
                                    data: data,
                                    borderColor: '#667eea',
                                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'top'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Notificaciones - Últimos 7 Días'
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            precision: 0
                                        }
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        /**
         * Crear gráfico de módulos
         */
        createModulesChart: function() {
            const ctx = document.getElementById('modules-chart');
            if (!ctx) return;

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_get_modules_status',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const migrated = response.data.migrated_count;
                        const pending = response.data.total_count - migrated;

                        FlavorSystemsAdmin.charts.modules = new Chart(ctx, {
                            type: 'doughnut',
                            data: {
                                labels: ['Migrados a V3', 'Pendientes'],
                                datasets: [{
                                    data: [migrated, pending],
                                    backgroundColor: [
                                        '#46b450',
                                        '#ffb900'
                                    ],
                                    borderWidth: 2,
                                    borderColor: '#fff'
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom'
                                    },
                                    title: {
                                        display: true,
                                        text: 'Estado de Migración a V3'
                                    }
                                }
                            }
                        });
                    }
                }
            });
        },

        /**
         * Refrescar gráficos
         */
        refreshCharts: function() {
            if (this.charts.notifications) {
                this.charts.notifications.update();
            }
            if (this.charts.modules) {
                this.charts.modules.update();
            }
        },

        /**
         * Vincular eventos
         */
        bindEvents: function() {
            // Limpiar notificaciones antiguas
            $('#clear-old-notifications').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.clearOldNotifications();
            });

            // Limpiar caché de notificaciones
            $('#clear-notification-cache').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.clearNotificationCache();
            });

            // Regenerar todas las páginas
            $('#regenerate-all-pages').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.regenerateAllPages();
            });

            // Optimización del sistema
            $('#clear-all-caches').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.optimizeSystem('cache');
            });

            $('#optimize-database').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.optimizeSystem('database');
            });

            $('#cleanup-old-notifications').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.optimizeSystem('notifications');
            });

            $('#regenerate-assets').on('click', function(e) {
                e.preventDefault();
                FlavorSystemsAdmin.optimizeSystem('assets');
            });
        },

        /**
         * Limpiar notificaciones antiguas
         */
        clearOldNotifications: function() {
            if (!confirm('¿Eliminar notificaciones leídas con más de 30 días?')) {
                return;
            }

            const $button = $('#clear-old-notifications');
            $button.prop('disabled', true).text('Limpiando...');

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_clear_old_notifications',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorSystemsAdmin.showNotice('success', 'Notificaciones antiguas eliminadas correctamente');
                        location.reload();
                    } else {
                        FlavorSystemsAdmin.showNotice('error', response.data.message || 'Error al limpiar notificaciones');
                    }
                },
                error: function() {
                    FlavorSystemsAdmin.showNotice('error', 'Error de conexión');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Limpiar Antiguas (>30 días)');
                }
            });
        },

        /**
         * Limpiar caché de notificaciones
         */
        clearNotificationCache: function() {
            const $button = $('#clear-notification-cache');
            $button.prop('disabled', true).text('Limpiando...');

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_clear_notification_cache',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorSystemsAdmin.showNotice('success', 'Caché limpiado correctamente');
                    } else {
                        FlavorSystemsAdmin.showNotice('error', response.data.message || 'Error al limpiar caché');
                    }
                },
                error: function() {
                    FlavorSystemsAdmin.showNotice('error', 'Error de conexión');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Limpiar Caché');
                }
            });
        },

        /**
         * Regenerar todas las páginas
         */
        regenerateAllPages: function() {
            if (!confirm('¿Regenerar todas las páginas? Esto puede tardar unos momentos.')) {
                return;
            }

            const $button = $('#regenerate-all-pages');
            $button.prop('disabled', true).text('Regenerando...');

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_regenerate_all_pages',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        FlavorSystemsAdmin.showNotice('success', 'Páginas regeneradas correctamente');
                        location.reload();
                    } else {
                        FlavorSystemsAdmin.showNotice('error', response.data.message || 'Error al regenerar páginas');
                    }
                },
                error: function() {
                    FlavorSystemsAdmin.showNotice('error', 'Error de conexión');
                },
                complete: function() {
                    $button.prop('disabled', false).text('Regenerar Todas las Páginas');
                }
            });
        },

        /**
         * Optimizar sistema
         */
        optimizeSystem: function(type) {
            if (!confirm(flavorSystemsAdmin.i18n.confirm_optimize)) {
                return;
            }

            const $button = $(`#${type === 'cache' ? 'clear-all-caches' : 'optimize-database'}`);
            const originalText = $button.text();
            $button.prop('disabled', true).text(flavorSystemsAdmin.i18n.loading);

            $('#optimization-results').show();
            $('#optimization-output').html('<p class="flavor-loading">Optimizando...</p>');

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_optimize_system',
                    type: type,
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success) {
                        let output = '<ul>';
                        response.data.results.forEach(function(result) {
                            output += `<li class="success">✓ ${result}</li>`;
                        });
                        output += '</ul>';
                        $('#optimization-output').html(output);
                        FlavorSystemsAdmin.showNotice('success', flavorSystemsAdmin.i18n.success);
                    } else {
                        $('#optimization-output').html(`<p class="error">✗ ${response.data.message || 'Error'}</p>`);
                        FlavorSystemsAdmin.showNotice('error', flavorSystemsAdmin.i18n.error);
                    }
                },
                error: function() {
                    $('#optimization-output').html('<p class="error">✗ Error de conexión</p>');
                    FlavorSystemsAdmin.showNotice('error', flavorSystemsAdmin.i18n.error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Cargar módulos por categoría
         */
        loadCategoryModules: function() {
            $('.category-modules').each(function() {
                const $container = $(this);
                const category = $container.data('category');

                $.ajax({
                    url: flavorSystemsAdmin.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'flavor_get_category_modules',
                        category: category,
                        nonce: flavorSystemsAdmin.nonce
                    },
                    success: function(response) {
                        if (response.success && response.data.modules) {
                            if (response.data.modules.length > 0) {
                                let html = '<ul>';
                                response.data.modules.forEach(function(module) {
                                    html += `<li>${module}</li>`;
                                });
                                html += '</ul>';
                                $container.html(html);
                            } else {
                                $container.html('<p style="color:#999;">Sin módulos</p>');
                            }
                        }
                    },
                    error: function() {
                        $container.html('<p style="color:#dc3232;">Error al cargar</p>');
                    }
                });
            });
        },

        /**
         * Cargar dependencias
         */
        loadDependencies: function() {
            const $container = $('#dependencies-table-container');
            if ($container.length === 0) return;

            $container.html('<p class="flavor-loading">Cargando dependencias...</p>');

            $.ajax({
                url: flavorSystemsAdmin.ajax_url,
                type: 'POST',
                data: {
                    action: 'flavor_get_dependencies',
                    nonce: flavorSystemsAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.dependencies) {
                        let html = '<table class="wp-list-table widefat striped"><thead><tr>';
                        html += '<th>Módulo</th><th>Depende de</th><th>Estado</th>';
                        html += '</tr></thead><tbody>';

                        response.data.dependencies.forEach(function(dep) {
                            html += '<tr>';
                            html += `<td><strong>${dep.module}</strong></td>`;
                            html += `<td>${dep.depends_on.join(', ') || 'Ninguna'}</td>`;
                            html += `<td><span class="status-badge status-${dep.status}">${dep.status_label}</span></td>`;
                            html += '</tr>';
                        });

                        html += '</tbody></table>';
                        $container.html(html);
                    } else {
                        $container.html('<p>No se encontraron dependencias</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="flavor-error">Error al cargar dependencias</p>');
                }
            });
        },

        /**
         * Mostrar notificación
         */
        showNotice: function(type, message) {
            const $notice = $('<div>')
                .addClass(`notice notice-${type} is-dismissible`)
                .html(`<p>${message}</p>`)
                .hide();

            $('.wrap > h1').after($notice);
            $notice.slideDown();

            setTimeout(function() {
                $notice.slideUp(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    /**
     * Función global para regenerar páginas de un módulo
     */
    window.regenerateModulePages = function(button) {
        const $button = $(button);
        const moduleId = $button.data('module');
        const originalText = $button.text();

        $button.prop('disabled', true).text('Regenerando...');

        $.ajax({
            url: flavorSystemsAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'flavor_regenerate_module_pages',
                module_id: moduleId,
                nonce: flavorSystemsAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    FlavorSystemsAdmin.showNotice('success', `Páginas del módulo ${moduleId} regeneradas`);
                } else {
                    FlavorSystemsAdmin.showNotice('error', response.data.message || 'Error al regenerar');
                }
            },
            error: function() {
                FlavorSystemsAdmin.showNotice('error', 'Error de conexión');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    };

    /**
     * Inicializar cuando el DOM esté listo
     */
    $(document).ready(function() {
        FlavorSystemsAdmin.init();
    });

})(jQuery);
