/**
 * Flavor WP Module Integrations - Frontend Scripts
 *
 * Extiende el modal de compartir con opciones de integración de módulos.
 * Permite seleccionar módulos y elementos específicos (comunidad, evento, etc.)
 */
(function ($) {
	'use strict';

	var modulosData = {};
	var elementosCache = {};
	var postId = 0;

	$(document).ready(function () {
		if (typeof flavorModuleIntegrationsFront === 'undefined') {
			return;
		}

		modulosData = flavorModuleIntegrationsFront.modulos || {};
		postId = flavorModuleIntegrationsFront.postId || 0;

		if (Object.keys(modulosData).length > 0) {
			extenderModalCompartir();
			initEventHandlers();
		}
	});

	/**
     * Extiende el modal de compartir con opciones de módulos
     */
	function extenderModalCompartir() {
		var $modal = $('#flavor-modal-compartir');
		if ($modal.length === 0) {
			return;
		}

		var $form = $modal.find('form');
		var $federarGroup = $form.find('.flavor-federar-group');

		// Crear HTML de integraciones
		var html = '<div class="flavor-integraciones-adicionales">';
		html += '<h4 class="flavor-integraciones-titulo">';
		html += '<span class="dashicons dashicons-admin-plugins"></span> ';
		html += 'Integrar también con:';
		html += '</h4>';
		html += '<div class="flavor-integraciones-lista">';

		$.each(modulosData, function (key, modulo) {
			html += '<div class="flavor-integracion-row" data-modulo="' + key + '">';
			html += '  <label class="flavor-integracion-option">';
			html += '    <input type="checkbox" name="integraciones[' + key + '][enabled]" value="1" class="flavor-integracion-check" data-modulo="' + key + '">';
			html += '    <span class="dashicons dashicons-' + modulo.icono + '"></span>';
			html += '    <span class="flavor-option-label">' + modulo.nombre + '</span>';
			html += '  </label>';
			html += '  <div class="flavor-integracion-selector" style="display:none;">';
			html += '    <select name="integraciones[' + key + '][elemento_id]" class="flavor-select-elemento" data-modulo="' + key + '">';
			html += '      <option value="">' + getPlaceholder(key) + '</option>';
			html += '    </select>';
			html += '  </div>';
			html += '</div>';
		});

		html += '</div>';
		html += '</div>';

		// Insertar antes del grupo de federación
		if ($federarGroup.length) {
			$federarGroup.before(html);
		} else {
			$form.find('.flavor-modal-actions').before(html);
		}

		addStyles();
	}

	/**
     * Obtiene placeholder según módulo
     */
	function getPlaceholder(modulo) {
		var placeholders = {
			'email_marketing': 'Seleccionar newsletter...',
			'comunidades': 'Seleccionar comunidad...',
			'colectivos': 'Seleccionar colectivo...',
			'eventos': 'Seleccionar evento...',
			'foros': 'Seleccionar foro...',
			'cursos': 'Seleccionar curso...',
			'talleres': 'Seleccionar taller...',
			'campanias': 'Seleccionar campaña...',
			'biblioteca': 'Seleccionar categoría...'
		};
		return placeholders[modulo] || 'Seleccionar...';
	}

	/**
     * Inicializa event handlers
     */
	function initEventHandlers() {
		// Toggle del selector al marcar checkbox
		$(document).on('change', '.flavor-integracion-check', function () {
			var $row = $(this).closest('.flavor-integracion-row');
			var $selector = $row.find('.flavor-integracion-selector');
			var modulo = $(this).data('modulo');

			if ($(this).is(':checked')) {
				$selector.slideDown(200);
				cargarElementos(modulo, $row.find('.flavor-select-elemento'));
			} else {
				$selector.slideUp(200);
			}
		});

		// Interceptar envío para incluir integraciones
		$(document).on('submit', '#flavor-form-compartir', function (e) {
			// Las integraciones se envían automáticamente
			// El backend las procesa desde $_POST['integraciones']
		});
	}

	/**
     * Carga elementos de un módulo vía AJAX
     */
	function cargarElementos(modulo, $select) {
		// Usar cache si existe
		if (elementosCache[modulo]) {
			llenarSelect($select, elementosCache[modulo]);
			return;
		}

		$select.prop('disabled', true);
		var originalOption = $select.find('option:first').text();
		$select.find('option:first').text('Cargando...');

		$.ajax({
			url: flavorModuleIntegrationsFront.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_obtener_elementos_modulo',
				nonce: flavorModuleIntegrationsFront.nonce,
				modulo: modulo
			},
			success: function (response) {
				$select.prop('disabled', false);
				$select.find('option:first').text(originalOption);

				if (response.success && response.data.elementos) {
					elementosCache[modulo] = response.data.elementos;
					llenarSelect($select, response.data.elementos);
				}
			},
			error: function () {
				$select.prop('disabled', false);
				$select.find('option:first').text(originalOption);
			}
		});
	}

	/**
     * Llena un select con elementos
     */
	function llenarSelect($select, elementos) {
		var currentVal = $select.val();
		$select.find('option:not(:first)').remove();

		$.each(elementos, function (i, el) {
			$select.append(
				$('<option></option>')
					.val(el.id)
					.text(el.titulo)
			);
		});

		if (currentVal) {
			$select.val(currentVal);
		}
	}

	/**
     * Añade estilos dinámicos
     */
	function addStyles() {
		if ($('#flavor-integraciones-styles').length > 0) {
			return;
		}

		var styles = `
            <style id="flavor-integraciones-styles">
                .flavor-integraciones-adicionales {
                    margin: 16px 0;
                    padding: 16px;
                    background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f4 100%);
                    border-radius: 10px;
                    border: 1px solid #e9ecef;
                }
                .flavor-integraciones-titulo {
                    font-size: 14px;
                    font-weight: 600;
                    margin: 0 0 12px 0;
                    color: #495057;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }
                .flavor-integraciones-lista {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                .flavor-integracion-row {
                    background: #fff;
                    border: 1px solid #dee2e6;
                    border-radius: 8px;
                    overflow: hidden;
                    transition: all 0.2s;
                }
                .flavor-integracion-row:has(.flavor-integracion-check:checked) {
                    border-color: var(--flavor-primary, #6366f1);
                    background: linear-gradient(135deg, #fafaff 0%, #f5f7ff 100%);
                }
                .flavor-integracion-option {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 12px 14px;
                    cursor: pointer;
                    margin: 0;
                }
                .flavor-integracion-option input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    accent-color: var(--flavor-primary, #6366f1);
                }
                .flavor-integracion-option .dashicons {
                    font-size: 20px;
                    width: 20px;
                    height: 20px;
                    color: #6c757d;
                    transition: color 0.2s;
                }
                .flavor-integracion-row:has(.flavor-integracion-check:checked) .dashicons {
                    color: var(--flavor-primary, #6366f1);
                }
                .flavor-option-label {
                    flex: 1;
                    font-weight: 500;
                    color: #495057;
                }
                .flavor-integracion-selector {
                    padding: 0 14px 14px;
                    border-top: 1px solid #eee;
                    margin-top: -4px;
                    padding-top: 12px;
                }
                .flavor-select-elemento {
                    width: 100%;
                    padding: 10px 12px;
                    border: 1px solid #dee2e6;
                    border-radius: 6px;
                    font-size: 14px;
                    background: #fff;
                    cursor: pointer;
                }
                .flavor-select-elemento:focus {
                    outline: none;
                    border-color: var(--flavor-primary, #6366f1);
                    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
                }
                @media (max-width: 480px) {
                    .flavor-integraciones-adicionales {
                        padding: 12px;
                    }
                    .flavor-integracion-option {
                        padding: 10px 12px;
                    }
                }
            </style>
        `;
		$('head').append(styles);
	}

	/**
     * Expone función para obtener integraciones seleccionadas
     */
	window.flavorGetIntegracionesSeleccionadas = function () {
		var integraciones = {};
		$('.flavor-integracion-check:checked').each(function () {
			var modulo = $(this).data('modulo');
			var $row = $(this).closest('.flavor-integracion-row');
			var elementoId = $row.find('.flavor-select-elemento').val() || '';
			integraciones[modulo] = {
				enabled: true,
				elemento_id: elementoId
			};
		});
		return integraciones;
	};

})(jQuery);
