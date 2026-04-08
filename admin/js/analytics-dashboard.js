/**
 * Analytics Dashboard JavaScript
 */
(function ($) {
	'use strict';

	const FlavorAnalytics = {
		charts: {},

		init: function () {
			this.bindEvents();
			this.loadCharts();
		},

		bindEvents: function () {
			$('#btn-refresh-analytics').on('click', () => this.refreshData());
			$('#btn-export-analytics').on('click', () => this.exportCSV());
			$('#analytics-period').on('change', () => this.refreshData());
		},

		loadCharts: function () {
			this.loadActivityChart();
			this.loadModulesChart();
			this.loadInteractionsChart();
		},

		loadActivityChart: function () {
			const ctx = document.getElementById('chart-actividad');
			if (!ctx) {return;}

			// Obtener datos via AJAX
			$.post(flavorAnalytics.ajaxUrl, {
				action: 'flavor_analytics_data',
				nonce: flavorAnalytics.nonce,
				dias: $('#analytics-period').val()
			}, (response) => {
				if (!response.success) {return;}

				const datos = response.data.actividad;
				const labels = datos.map(d => this.formatDate(d.fecha));

				if (this.charts.actividad) {
					this.charts.actividad.destroy();
				}

				this.charts.actividad = new Chart(ctx, {
					type: 'line',
					data: {
						labels: labels,
						datasets: [
							{
								label: flavorAnalytics.i18n.usuarios,
								data: datos.map(d => d.usuarios),
								borderColor: '#3b82f6',
								backgroundColor: 'rgba(59, 130, 246, 0.1)',
								fill: true,
								tension: 0.4
							},
							{
								label: flavorAnalytics.i18n.engagement,
								data: datos.map(d => d.interacciones),
								borderColor: '#f59e0b',
								backgroundColor: 'rgba(245, 158, 11, 0.1)',
								fill: true,
								tension: 0.4
							}
						]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								position: 'bottom'
							}
						},
						scales: {
							y: {
								beginAtZero: true,
								grid: {
									color: 'rgba(0, 0, 0, 0.05)'
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
			});
		},

		loadModulesChart: function () {
			const ctx = document.getElementById('chart-modulos');
			if (!ctx) {return;}

			$.post(flavorAnalytics.ajaxUrl, {
				action: 'flavor_analytics_data',
				nonce: flavorAnalytics.nonce,
				dias: $('#analytics-period').val()
			}, (response) => {
				if (!response.success) {return;}

				const datos = response.data.modulos;

				if (this.charts.modulos) {
					this.charts.modulos.destroy();
				}

				this.charts.modulos = new Chart(ctx, {
					type: 'doughnut',
					data: {
						labels: datos.map(d => d.nombre),
						datasets: [{
							data: datos.map(d => d.valor),
							backgroundColor: [
								'#3b82f6',
								'#10b981',
								'#f59e0b',
								'#8b5cf6',
								'#ef4444'
							],
							borderWidth: 0
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								position: 'bottom',
								labels: {
									boxWidth: 12,
									padding: 10
								}
							}
						},
						cutout: '65%'
					}
				});
			});
		},

		loadInteractionsChart: function () {
			const ctx = document.getElementById('chart-interacciones');
			if (!ctx) {return;}

			// Datos de ejemplo (se pueden cargar via AJAX)
			const datos = [
				{ tipo: 'Likes', valor: 45 },
				{ tipo: 'Comentarios', valor: 30 },
				{ tipo: 'Compartidos', valor: 15 },
				{ tipo: 'Guardados', valor: 10 }
			];

			if (this.charts.interacciones) {
				this.charts.interacciones.destroy();
			}

			this.charts.interacciones = new Chart(ctx, {
				type: 'bar',
				data: {
					labels: datos.map(d => d.tipo),
					datasets: [{
						data: datos.map(d => d.valor),
						backgroundColor: [
							'#ef4444',
							'#3b82f6',
							'#10b981',
							'#f59e0b'
						],
						borderRadius: 6
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
						y: {
							beginAtZero: true,
							grid: {
								color: 'rgba(0, 0, 0, 0.05)'
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
		},

		refreshData: function () {
			const dias = $('#analytics-period').val();

			// Actualizar KPIs
			$.post(flavorAnalytics.ajaxUrl, {
				action: 'flavor_analytics_data',
				nonce: flavorAnalytics.nonce,
				dias: dias
			}, (response) => {
				if (!response.success) {return;}

				const kpis = response.data.kpis;
				$('#kpi-usuarios').text(this.formatNumber(kpis.usuarios_activos));
				$('#kpi-contenido').text(this.formatNumber(kpis.contenido_creado));
				$('#kpi-engagement').text(this.formatNumber(kpis.interacciones));
				$('#kpi-eventos').text(this.formatNumber(kpis.eventos_activos));
			});

			// Recargar gráficos
			this.loadCharts();
		},

		exportCSV: function () {
			const dias = $('#analytics-period').val();

			$.post(flavorAnalytics.ajaxUrl, {
				action: 'flavor_export_analytics',
				nonce: flavorAnalytics.nonce,
				dias: dias
			}, (response) => {
				if (!response.success) {
					alert(flavorAnalytics.i18n.error);
					return;
				}

				// Descargar CSV
				const blob = new Blob([response.data.content], { type: 'text/csv' });
				const url = window.URL.createObjectURL(blob);
				const a = document.createElement('a');
				a.href = url;
				a.download = response.data.filename;
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
				window.URL.revokeObjectURL(url);
			});
		},

		formatDate: function (dateStr) {
			const date = new Date(dateStr);
			return date.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' });
		},

		formatNumber: function (num) {
			return new Intl.NumberFormat('es-ES').format(num);
		}
	};

	$(document).ready(function () {
		FlavorAnalytics.init();
	});

})(jQuery);
