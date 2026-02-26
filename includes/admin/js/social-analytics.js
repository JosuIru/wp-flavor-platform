/**
 * JavaScript del Dashboard de Analytics Social
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

(function($) {
    'use strict';

    const FlavorAnalytics = {
        chartActividad: null,
        config: null,

        /**
         * Inicializar
         */
        init: function() {
            this.config = window.flavorAnalytics || {};
            this.bindEventos();
            this.inicializarGrafico();
        },

        /**
         * Bind de eventos
         */
        bindEventos: function() {
            const self = this;

            // Cambio de período
            $('#periodo-select').on('change', function() {
                const periodo = $(this).val();
                self.actualizarUrl(periodo);
                self.cargarDatos(periodo);
            });

            // Botón refrescar
            $('#btn-refresh').on('click', function() {
                const periodo = $('#periodo-select').val();
                self.cargarDatos(periodo, true);
            });

            // Exportar CSV
            $('#btn-export-csv').on('click', function() {
                self.exportar('csv');
            });

            // Exportar JSON
            $('#btn-export-json').on('click', function() {
                self.exportar('json');
            });
        },

        /**
         * Actualizar URL sin recargar
         */
        actualizarUrl: function(periodo) {
            const url = new URL(window.location);
            url.searchParams.set('periodo', periodo);
            window.history.replaceState({}, '', url);
        },

        /**
         * Inicializar gráfico con Chart.js
         */
        inicializarGrafico: function() {
            const datosElemento = document.getElementById('chart-data');
            if (!datosElemento) {
                return;
            }

            let datos;
            try {
                datos = JSON.parse(datosElemento.textContent);
            } catch (e) {
                console.error('Error parsing chart data:', e);
                return;
            }

            if (!datos || !datos.labels || datos.labels.length === 0) {
                return;
            }

            const ctx = document.getElementById('chart-actividad');
            if (!ctx) {
                return;
            }

            this.chartActividad = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: datos.labels,
                    datasets: [
                        {
                            label: this.config.strings?.publicaciones || 'Publicaciones',
                            data: datos.publicaciones || [],
                            borderColor: '#9b59b6',
                            backgroundColor: 'rgba(155, 89, 182, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: this.config.strings?.comentarios || 'Comentarios',
                            data: datos.comentarios || [],
                            borderColor: '#f39c12',
                            backgroundColor: 'rgba(243, 156, 18, 0.1)',
                            tension: 0.4,
                            fill: true
                        },
                        {
                            label: this.config.strings?.likes || 'Likes',
                            data: datos.likes || [],
                            borderColor: '#e91e63',
                            backgroundColor: 'rgba(233, 30, 99, 0.1)',
                            tension: 0.4,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 6
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 0
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        },

        /**
         * Cargar datos vía AJAX
         */
        cargarDatos: function(periodo, forzarRefresh) {
            const self = this;
            const $wrap = $('.flavor-analytics-wrap');

            $wrap.addClass('loading');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_analytics_get_data',
                    nonce: this.config.nonce,
                    periodo: periodo,
                    tipo: 'general',
                    refresh: forzarRefresh ? 1 : 0
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.actualizarKPIs(response.data);
                        self.actualizarGrafico(response.data.grafico_actividad);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading analytics:', error);
                    self.mostrarError(self.config.strings?.error || 'Error al cargar datos');
                },
                complete: function() {
                    $wrap.removeClass('loading');
                }
            });
        },

        /**
         * Actualizar KPIs en el DOM
         */
        actualizarKPIs: function(datos) {
            // Usuarios activos
            this.actualizarKPI('usuarios', datos.usuarios_activos, datos.tendencia_usuarios);

            // Usuarios nuevos
            this.actualizarKPI('nuevos', datos.usuarios_nuevos);

            // Publicaciones
            this.actualizarKPI('publicaciones', datos.publicaciones, datos.tendencia_publicaciones);

            // Engagement
            this.actualizarKPI('engagement', datos.engagement_rate + '%');

            // Likes
            this.actualizarKPI('likes', datos.likes);

            // Comentarios
            this.actualizarKPI('comentarios', datos.comentarios);
        },

        /**
         * Actualizar un KPI individual
         */
        actualizarKPI: function(tipo, valor, tendencia) {
            const $card = $(`.kpi-card[data-kpi="${tipo}"]`);
            if (!$card.length) {
                return;
            }

            $card.find('.kpi-value').text(valor || 0);

            if (typeof tendencia !== 'undefined') {
                let $trend = $card.find('.kpi-trend');
                const tendenciaPositiva = tendencia >= 0;
                const iconoClase = tendenciaPositiva ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt';
                const tendenciaClase = tendenciaPositiva ? 'positive' : 'negative';

                if (!$trend.length) {
                    $trend = $('<span class="kpi-trend"><span class="dashicons"></span></span>');
                    $card.find('.kpi-content').append($trend);
                }

                $trend
                    .removeClass('positive negative')
                    .addClass(tendenciaClase)
                    .find('.dashicons')
                    .removeClass('dashicons-arrow-up-alt dashicons-arrow-down-alt')
                    .addClass(iconoClase);

                $trend.contents().filter(function() {
                    return this.nodeType === 3;
                }).remove();

                $trend.append(Math.abs(tendencia) + '%');
            }
        },

        /**
         * Actualizar gráfico con nuevos datos
         */
        actualizarGrafico: function(datos) {
            if (!this.chartActividad || !datos) {
                return;
            }

            this.chartActividad.data.labels = datos.labels || [];
            this.chartActividad.data.datasets[0].data = datos.publicaciones || [];
            this.chartActividad.data.datasets[1].data = datos.comentarios || [];
            this.chartActividad.data.datasets[2].data = datos.likes || [];

            this.chartActividad.update('active');
        },

        /**
         * Exportar reporte
         */
        exportar: function(formato) {
            const self = this;
            const periodo = $('#periodo-select').val();
            const $btn = formato === 'csv' ? $('#btn-export-csv') : $('#btn-export-json');

            $btn.prop('disabled', true);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'flavor_analytics_export',
                    nonce: this.config.nonce,
                    periodo: periodo,
                    formato: formato
                },
                success: function(response) {
                    if (response.success && response.data) {
                        if (formato === 'csv') {
                            self.descargarArchivo(
                                response.data.csv,
                                response.data.filename,
                                'text/csv'
                            );
                        } else {
                            self.descargarArchivo(
                                JSON.stringify(response.data, null, 2),
                                'analytics_' + periodo + '_' + self.fechaActual() + '.json',
                                'application/json'
                            );
                        }
                    }
                },
                error: function() {
                    self.mostrarError('Error al exportar');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        },

        /**
         * Descargar archivo
         */
        descargarArchivo: function(contenido, nombreArchivo, tipoMime) {
            const blob = new Blob([contenido], { type: tipoMime + ';charset=utf-8' });
            const url = URL.createObjectURL(blob);

            const enlace = document.createElement('a');
            enlace.href = url;
            enlace.download = nombreArchivo;
            document.body.appendChild(enlace);
            enlace.click();
            document.body.removeChild(enlace);

            URL.revokeObjectURL(url);
        },

        /**
         * Obtener fecha actual formateada
         */
        fechaActual: function() {
            const fecha = new Date();
            return fecha.toISOString().split('T')[0];
        },

        /**
         * Mostrar error
         */
        mostrarError: function(mensaje) {
            const $notice = $('<div class="notice notice-error is-dismissible"><p>' + mensaje + '</p></div>');
            $('.flavor-analytics-title').after($notice);

            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    };

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        FlavorAnalytics.init();
    });

})(jQuery);
