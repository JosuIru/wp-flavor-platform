/**
 * Analizador de Datos IA - Binding de la página dedicada
 *
 * Conecta el formulario de análisis con su handler AJAX:
 *   - flavor_analyze_data (nonce: flavorAITools.nonces.analyzer)
 *
 * El handler acepta un único `type`, por lo que se envía el primer tipo de
 * análisis marcado. El periodo se calcula en días a partir del rango de
 * fechas. Los botones de exportar PDF / CSV NO tienen handler real y se
 * deshabilitan visualmente.
 *
 * @package FlavorPlatform
 */

(function ($) {
	'use strict';

	function analyzerNonce() {
		return (window.FlavorAITools &&
			FlavorAITools.config &&
			FlavorAITools.config.nonces &&
			FlavorAITools.config.nonces.analyzer) || '';
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

	function extractError(response) {
		if (!response) {
			return 'Error desconocido al procesar la petición.';
		}
		if (typeof response === 'string') {
			return response;
		}
		return response.error || response.message || 'No se pudo completar el análisis.';
	}

	/**
	 * Días entre las dos fechas del formulario (mínimo 1).
	 */
	function computePeriodDays() {
		var from = $('#date-from').val();
		var to = $('#date-to').val();
		if (!from || !to) {
			return 30;
		}
		var diff = (new Date(to) - new Date(from)) / (1000 * 60 * 60 * 24);
		diff = Math.round(diff);
		return diff > 0 ? diff : 1;
	}

	/**
	 * Tipo de análisis principal (primero marcado).
	 */
	function primaryAnalysisType() {
		var type = $('#analyzer-form input[name="analysis_types[]"]:checked').first().val();
		return type || 'general';
	}

	/**
	 * Aplana un objeto/valor a líneas legibles para mostrar como insights.
	 */
	function renderResult(result) {
		var $list = $('#insights-list').empty();

		if (!result || typeof result !== 'object') {
			$list.append(insightItem('Resultado', String(result)));
			$('#analyzer-results').show();
			return;
		}

		var produced = false;

		$.each(result, function (key, value) {
			if (key === 'type') {
				return;
			}
			var label = String(key).replace(/_/g, ' ');
			var text = formatValue(value);
			if (text === '') {
				return;
			}
			produced = true;
			$list.append(insightItem(label, text));
		});

		if (!produced) {
			$list.append(insightItem('Análisis', 'El análisis no devolvió datos para este periodo.'));
		}

		$('#analyzer-results').show();
	}

	function formatValue(value) {
		if (value === null || typeof value === 'undefined') {
			return '';
		}
		if (typeof value === 'object') {
			try {
				return JSON.stringify(value, null, 2);
			} catch (e) {
				return '';
			}
		}
		return String(value);
	}

	function insightItem(title, text) {
		return '<div class="insight-item">' +
			'<div class="insight-icon"><span class="dashicons dashicons-lightbulb"></span></div>' +
			'<div class="insight-content">' +
			'<h4>' + escapeHtml(title) + '</h4>' +
			'<p style="white-space:pre-wrap;">' + escapeHtml(text) + '</p>' +
			'</div></div>';
	}

	$(function () {
		var $form = $('#analyzer-form');
		if (!$form.length) {
			return;
		}

		// Deshabilitar exportaciones sin handler en el backend.
		$('#export-analysis-pdf, #export-analysis-csv')
			.prop('disabled', true)
			.attr('title', 'Funcionalidad no disponible todavía');

		// "Nuevo análisis" solo limpia la vista (no necesita backend).
		$('#new-analysis').on('click', function () {
			$('#analyzer-results').hide();
			$('#insights-list').empty();
		});

		$form.on('submit', function (e) {
			e.preventDefault();

			var module = $form.find('input[name="module"]:checked').val();
			if (!module) {
				window.alert('Selecciona un módulo a analizar.');
				return;
			}

			var $submit = $form.find('button[type="submit"]');
			$submit.prop('disabled', true);
			$('#analyzer-results').hide();
			$('#analysis-status').show();
			$('#analysis-step').text('Recopilando información del módulo...');
			$('#analysis-progress').css('width', '50%');

			$.ajax({
				url: ajaxUrl(),
				type: 'POST',
				data: {
					action: 'flavor_analyze_data',
					nonce: analyzerNonce(),
					type: primaryAnalysisType(),
					module: module,
					period: computePeriodDays()
				}
			}).done(function (response) {
				$('#analysis-progress').css('width', '100%');
				if (response && response.success) {
					renderResult(response.data);
				} else {
					window.alert(extractError(response && response.data));
				}
			}).fail(function () {
				window.alert('Error de conexión al analizar los datos.');
			}).always(function () {
				$('#analysis-status').hide();
				$('#analysis-progress').css('width', '0%');
				$submit.prop('disabled', false);
			});
		});
	});

})(jQuery);
