/**
 * Reportes Semanales IA - Binding de la página dedicada
 *
 * Conecta el formulario de generación de reportes y la vista previa de
 * métricas con sus handlers AJAX:
 *   - flavor_generate_weekly_report (nonce: flavorAITools.nonces.report)
 *   - flavor_get_report_preview     (nonce: flavorAITools.nonces.report)
 *
 * Los botones de exportar PDF / enviar email / guardar NO tienen handler
 * real en el backend, por lo que se deshabilitan visualmente.
 *
 * @package FlavorPlatform
 */

(function ($) {
	'use strict';

	function reportNonce() {
		return (window.FlavorAITools &&
			FlavorAITools.config &&
			FlavorAITools.config.nonces &&
			FlavorAITools.config.nonces.report) || '';
	}

	function ajaxUrl() {
		return (window.FlavorAITools &&
			FlavorAITools.config &&
			FlavorAITools.config.ajaxUrl) ||
			(window.flavorAITools && flavorAITools.ajaxUrl) ||
			window.ajaxurl;
	}

	function escapeHtml(text) {
		var div = document.createElement('div');
		div.textContent = text == null ? '' : String(text);
		return div.innerHTML;
	}

	/**
	 * Extrae un mensaje de error legible de cualquier respuesta del backend.
	 */
	function extractError(response) {
		if (!response) {
			return 'Error desconocido al procesar la petición.';
		}
		if (typeof response === 'string') {
			return response;
		}
		if (response.error) {
			return response.error;
		}
		if (response.message) {
			return response.message;
		}
		return 'No se pudo completar la operación.';
	}

	/**
	 * Recoge los módulos seleccionados en el formulario.
	 */
	function selectedModules() {
		var modules = [];
		$('#generate-report-form input[name="modules[]"]:checked').each(function () {
			modules.push($(this).val());
		});
		return modules;
	}

	/**
	 * Pinta la rejilla de métricas de la vista previa.
	 */
	function renderMetricsPreview(metrics) {
		var $grid = $('#metrics-grid').empty();

		if (!metrics || typeof metrics !== 'object') {
			$grid.html('<p>' + escapeHtml('No hay métricas disponibles para el periodo.') + '</p>');
			$('#metrics-preview').show();
			return;
		}

		var hasContent = false;

		$.each(metrics, function (group, values) {
			if (!values || typeof values !== 'object' || $.isEmptyObject(values)) {
				return;
			}
			$.each(values, function (label, value) {
				if (value === null || typeof value === 'object') {
					return;
				}
				hasContent = true;
				$grid.append(
					'<div class="metric-card">' +
					'<div class="metric-value">' + escapeHtml(value) + '</div>' +
					'<div class="metric-label">' +
					escapeHtml(group + ' · ' + label.replace(/_/g, ' ')) +
					'</div>' +
					'</div>'
				);
			});
		});

		if (!hasContent) {
			$grid.html('<p>' + escapeHtml('No hay métricas disponibles para el periodo.') + '</p>');
		}

		$('#metrics-preview').show();
	}

	/**
	 * Pinta el reporte completo generado.
	 */
	function renderReport(report) {
		$('#report-title').text('Reporte Semanal');

		if (report.period) {
			$('#report-date').text(
				(report.period.start || '') + ' — ' + (report.period.end || '')
			);
		}

		// Resumen ejecutivo / análisis IA
		var analysis = report.analysis || {};
		var summaryText = analysis.summary || analysis.executive_summary || '';
		$('#executive-summary').text(summaryText || '—');

		var analysisText = analysis.text || analysis.analysis || '';
		if (!analysisText && typeof analysis === 'string') {
			analysisText = analysis;
		}
		$('#ai-analysis').text(analysisText || summaryText || '—');

		// Métricas clave
		var $keyMetrics = $('#key-metrics').empty();
		var variations = report.variations || {};
		var metrics = report.metrics || {};
		var renderedKey = false;
		$.each(metrics, function (group, values) {
			if (!values || typeof values !== 'object' || $.isEmptyObject(values)) {
				return;
			}
			$.each(values, function (label, value) {
				if (value === null || typeof value === 'object') {
					return;
				}
				renderedKey = true;
				var changeHtml = '';
				if (variations[group] && typeof variations[group][label] !== 'undefined') {
					var change = variations[group][label];
					var cls = (parseFloat(change) >= 0) ? 'positive' : 'negative';
					changeHtml = '<div class="metric-change ' + cls + '">' +
						escapeHtml(change) + '%</div>';
				}
				$keyMetrics.append(
					'<div class="metric-card">' +
					'<div class="metric-value">' + escapeHtml(value) + '</div>' +
					'<div class="metric-label">' +
					escapeHtml(group + ' · ' + label.replace(/_/g, ' ')) +
					'</div>' + changeHtml +
					'</div>'
				);
			});
		});
		if (!renderedKey) {
			$keyMetrics.html('<p>' + escapeHtml('Sin métricas clave.') + '</p>');
		}

		// Recomendaciones
		var $rec = $('#recommendations').empty();
		var recs = report.recommendations || [];
		if (recs.length) {
			var $ul = $('<ul>');
			$.each(recs, function (i, rec) {
				var text = (rec && typeof rec === 'object')
					? (rec.text || rec.description || rec.title || JSON.stringify(rec))
					: rec;
				$ul.append($('<li>').text(text));
			});
			$rec.append($ul);
		} else {
			$rec.html('<p>' + escapeHtml('Sin recomendaciones.') + '</p>');
		}

		$('#report-result').show();
	}

	$(function () {
		var $form = $('#generate-report-form');
		if (!$form.length) {
			return;
		}

		// Deshabilitar acciones sin handler en el backend.
		$('#export-pdf, #send-email, #save-report')
			.prop('disabled', true)
			.attr('title', 'Funcionalidad no disponible todavía');

		// Vista previa de métricas
		$('#preview-metrics').on('click', function () {
			var $btn = $(this);
			$btn.prop('disabled', true);

			$.ajax({
				url: ajaxUrl(),
				type: 'POST',
				data: {
					action: 'flavor_get_report_preview',
					nonce: reportNonce()
				}
			}).done(function (response) {
				if (response && response.success && response.data) {
					renderMetricsPreview(response.data.metrics);
				} else {
					window.alert(extractError(response && response.data));
				}
			}).fail(function () {
				window.alert('Error de conexión al obtener las métricas.');
			}).always(function () {
				$btn.prop('disabled', false);
			});
		});

		// Generar reporte
		$form.on('submit', function (e) {
			e.preventDefault();

			var $submit = $form.find('button[type="submit"]');
			$submit.prop('disabled', true);
			$('#report-result').hide();
			$('#report-status').show();
			$('#status-step').text('Recopilando métricas...');
			$('#progress-fill').css('width', '40%');

			$.ajax({
				url: ajaxUrl(),
				type: 'POST',
				data: {
					action: 'flavor_generate_weekly_report',
					nonce: reportNonce(),
					week_start: $('#report-week-start').val(),
					modules: selectedModules()
				}
			}).done(function (response) {
				$('#progress-fill').css('width', '100%');
				if (response && response.success && response.data) {
					renderReport(response.data);
				} else {
					window.alert(extractError(response && response.data));
				}
			}).fail(function () {
				window.alert('Error de conexión al generar el reporte.');
			}).always(function () {
				$('#report-status').hide();
				$('#progress-fill').css('width', '0%');
				$submit.prop('disabled', false);
			});
		});
	});

})(jQuery);
