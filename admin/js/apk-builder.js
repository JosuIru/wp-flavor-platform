/**
 * APK Builder JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function ($) {
	'use strict';

	let buildCheckInterval = null;
	let mediaUploader = null;

	/**
     * Initialize
     */
	function init() {
		bindEvents();
		bindPreviewInteractions();
		initColorPickers();
		checkEnvironment();
		loadBuilds();
		updatePreview();
	}

	/**
     * Bind events
     */
	function bindEvents() {
		// Environment check
		$('#check-environment').on('click', checkEnvironment);

		// Icon selector
		$('#select-icon').on('click', openMediaUploader);

		// Generate API key
		$('#generate-api-key').on('click', generateApiKey);

		// Collapsible sections
		$('.collapsible-header').on('click', function () {
			$(this).closest('.collapsible').toggleClass('open');
		});

		// Module selection
		$('.module-item input').on('change', updatePreview);

		// Color changes
		$('.color-picker').on('change', updatePreview);

		// App name change
		$('#app_name').on('input', updatePreview);

		// Save config
		$('#save-config').on('click', saveConfig);

		// Download config
		$('#download-config').on('click', downloadConfig);

		// Start build
		$('#start-build').on('click', startBuild);
	}

	/**
     * Initialize color pickers
     */
	function initColorPickers() {
		$('.color-picker').wpColorPicker({
			change: function () {
				setTimeout(updatePreview, 100);
			}
		});
	}

	/**
     * Check environment
     */
	function checkEnvironment() {
		const $btn = $('#check-environment');
		$btn.prop('disabled', true).find('.dashicons').addClass('spin');

		$('.env-item .status-icon').removeClass('ok warning error').addClass('pending');
		$('.env-item .env-value').text('-');

		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_apk_check_environment',
				nonce: flavorApkBuilder.nonce
			},
			success: function (response) {
				if (response.success) {
					updateEnvironmentStatus(response.data);
				}
			},
			complete: function () {
				$btn.prop('disabled', false).find('.dashicons').removeClass('spin');
			}
		});
	}

	/**
     * Update environment status
     */
	function updateEnvironmentStatus(data) {
		for (const [key, value] of Object.entries(data)) {
			const $item = $(`.env-item[data-check="${key}"]`);
			const $icon = $item.find('.status-icon');
			const $value = $item.find('.env-value');

			$icon.removeClass('pending ok warning error').addClass(value.status);

			if (value.version) {
				$value.text('v' + value.version);
			} else if (value.path) {
				$value.text(value.path.substring(0, 30) + '...');
			} else if (value.message) {
				$value.text(value.message);
			}
		}
	}

	/**
     * Open media uploader
     */
	function openMediaUploader() {
		if (mediaUploader) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media({
			title: flavorApkBuilder.i18n.selectIcon,
			button: { text: flavorApkBuilder.i18n.selectIcon },
			multiple: false,
			library: { type: 'image' }
		});

		mediaUploader.on('select', function () {
			const attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#app_icon').val(attachment.url);
			$('#icon-preview').html('<img src="' + attachment.url + '" alt="Icon">');
		});

		mediaUploader.open();
	}

	/**
     * Generate API key
     */
	function generateApiKey() {
		const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
		let key = 'fai_';
		for (let i = 0; i < 32; i++) {
			key += chars.charAt(Math.floor(Math.random() * chars.length));
		}
		$('#api_key').val(key);
	}

	/**
     * Devuelve los IDs de los módulos seleccionados en el orden del DOM.
     */
	function getSelectedModuleIds() {
		const ids = [];
		$('.module-item input:checked').each(function () {
			ids.push($(this).val());
		});
		return ids;
	}

	/**
     * Construye una Card Material para el módulo dado (estilo home Flutter).
     */
	function buildModuleCard(modId, meta) {
		const color = meta.color || '#3b82f6';
		const icon = meta.material_icon || 'apps';
		const name = meta.name || modId;
		const desc = meta.description || '';

		const $card = $(`
			<div class="preview-module-card" data-module="${modId}">
				<div class="preview-module-card__avatar" style="background:${color};">
					<span class="material-icons-outlined">${icon}</span>
				</div>
				<div class="preview-module-card__body">
					<p class="preview-module-card__title">${name}</p>
					<div class="preview-module-card__desc">${desc}</div>
				</div>
				<span class="material-icons-outlined preview-module-card__chevron">chevron_right</span>
			</div>
		`);

		$card.on('click', function () {
			showModuleScreen(modId, meta);
		});

		return $card;
	}

	/**
     * Cambia la vista visible dentro de la pantalla del teléfono.
     */
	function setActiveView(viewName) {
		$('#preview-screen .screen-view').hide();
		$(`#preview-screen .screen-view[data-view="${viewName}"]`).css('display', 'flex');

		// Actualiza el highlight del bottom nav según la vista (módulo cuenta como hub)
		const navTab = (viewName === 'module') ? 'hub' : viewName;
		$('#preview-navbar .nav-item').removeClass('active');
		$(`#preview-navbar .nav-item[data-tab="${navTab}"]`).addClass('active');
	}

	/**
     * Renderiza la pantalla detalle de un módulo.
     */
	function showModuleScreen(modId, meta) {
		const color = meta.color || '#3b82f6';
		const items = meta.mock_items || [];

		// Header del módulo con su color de marca
		$('#preview-module-header').css('background', color);
		$('#preview-module-title').text(meta.name || modId);

		// Lista de items mock
		const $list = $('#preview-module-list').empty();
		items.forEach(function (item) {
			$list.append(`
				<div class="preview-module-list__item" style="border-left-color:${color};">
					<p class="preview-module-list__title">${item.title}</p>
					<div class="preview-module-list__subtitle">${item.subtitle}</div>
				</div>
			`);
		});
		$list.append(`
			<div class="preview-module-list__fab" style="background:${color};" title="Crear">
				<span class="material-icons-outlined">add</span>
			</div>
		`);

		setActiveView('module');
	}

	/**
     * Renderiza la vista Buscar (sugerencias = primeros módulos seleccionados).
     */
	function renderSearchView(selectedIds, meta) {
		const $list = $('#preview-search-suggestions').empty();
		selectedIds.slice(0, 5).forEach(function (id) {
			const m = meta[id];
			if (!m) return;
			const $card = buildModuleCard(id, m);
			$list.append($card);
		});
	}

	/**
     * Renderiza la vista Avisos (uno por cada módulo, primer mock_item).
     */
	function renderAlertsView(selectedIds, meta) {
		const $list = $('#preview-alerts-list').empty();
		const visible = selectedIds.slice(0, 6);

		if (!visible.length) {
			$list.append(`
				<div class="preview-empty">
					<span class="material-icons-outlined">notifications_off</span>
					<p>${flavorApkBuilder.i18n.noAlerts}</p>
				</div>
			`);
			return;
		}

		visible.forEach(function (id) {
			const m = meta[id];
			if (!m || !m.mock_items || !m.mock_items.length) return;
			const item = m.mock_items[0];
			$list.append(`
				<div class="alert-item">
					<div class="alert-item__icon" style="background:${m.color};">
						<span class="material-icons-outlined">${m.material_icon}</span>
					</div>
					<div>
						<p class="alert-item__title">${item.title}</p>
						<div class="alert-item__time">${m.name} · ${item.subtitle}</div>
					</div>
				</div>
			`);
		});
	}

	/**
     * Update preview — sincroniza header, colores, módulos y vistas.
     */
	function updatePreview() {
		const meta = (window.flavorApkBuilder && flavorApkBuilder.modulesMeta) || {};

		// Nombre + color primario del AppBar
		const appName = $('#app_name').val() || 'Mi App';
		$('#preview-header .app-title').text(appName);

		const primaryColor = $('#color_primary').val() || '#3b82f6';
		document.documentElement.style.setProperty('--preview-primary', primaryColor);
		$('#preview-header').css('background', primaryColor);
		$('#preview-search-header').css('background', primaryColor);
		$('#preview-alerts-header').css('background', primaryColor);
		$('#preview-profile-header').css('background', primaryColor);

		// Lista de módulos en la home
		const selectedIds = getSelectedModuleIds();
		const $modulesPreview = $('#preview-modules').empty();
		const $empty = $('#preview-empty');

		if (!selectedIds.length) {
			$empty.show();
		} else {
			$empty.hide();
			selectedIds.forEach(function (id) {
				const m = meta[id];
				if (!m) return;
				$modulesPreview.append(buildModuleCard(id, m));
			});
		}

		// Refrescar vistas secundarias por si están abiertas
		renderSearchView(selectedIds, meta);
		renderAlertsView(selectedIds, meta);

		// Si la vista actual de detalle hacía referencia a un módulo desmarcado,
		// volver al hub para no quedarse en una pantalla desconectada.
		const $activeModuleView = $('#preview-screen .screen-view[data-view="module"]:visible');
		if ($activeModuleView.length) {
			const currentId = $('#preview-module-title').data('current-module');
			if (currentId && selectedIds.indexOf(currentId) === -1) {
				setActiveView('hub');
			}
		}
	}

	/**
     * Bind events del preview interactivo.
     */
	function bindPreviewInteractions() {
		// Bottom navigation
		$('#preview-navbar').on('click', '.nav-item', function () {
			const tab = $(this).data('tab');
			if (!tab) return;
			setActiveView(tab);
		});

		// Botón back en pantalla de módulo
		$('#preview-back').on('click', function () {
			setActiveView('hub');
		});
	}

	/**
     * Collect form data
     */
	function collectFormData() {
		const modules = [];
		$('.module-item input:checked').each(function () {
			modules.push($(this).val());
		});

		return {
			action: 'flavor_apk_save_config',
			nonce: flavorApkBuilder.nonce,
			app_name: $('#app_name').val(),
			app_id: $('#app_id').val(),
			app_version: $('#app_version').val(),
			app_build: $('#app_build').val(),
			app_icon: $('#app_icon').val(),
			color_primary: $('#color_primary').val(),
			color_secondary: $('#color_secondary').val(),
			color_accent: $('#color_accent').val(),
			site_url: $('#site_url').val(),
			api_key: $('#api_key').val(),
			modules: modules,
			enable_offline: $('#enable_offline').is(':checked') ? 1 : 0,
			enable_push: $('#enable_push').is(':checked') ? 1 : 0,
			enable_biometric: $('#enable_biometric').is(':checked') ? 1 : 0,
			min_android_version: $('#min_android_version').val(),
			build_type: $('#build_type').val(),
			flavor: $('#flavor').val()
		};
	}

	/**
     * Save config
     */
	function saveConfig() {
		const $btn = $('#save-config');
		$btn.prop('disabled', true);

		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: collectFormData(),
			success: function (response) {
				if (response.success) {
					showNotice('Configuración guardada correctamente', 'success');
				} else {
					showNotice(response.data || 'Error al guardar', 'error');
				}
			},
			error: function () {
				showNotice('Error de conexión', 'error');
			},
			complete: function () {
				$btn.prop('disabled', false);
			}
		});
	}

	/**
     * Download config
     */
	function downloadConfig() {
		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_apk_download_config',
				nonce: flavorApkBuilder.nonce
			},
			success: function (response) {
				if (response.success) {
					// Create zip with all configs
					const data = response.data;

					// Download dart config
					downloadFile('generated_config.dart', data.dart_config);

					// Download colors config
					setTimeout(function () {
						downloadFile('generated_colors.dart', data.colors_config);
					}, 500);

					// Download instructions
					setTimeout(function () {
						downloadFile('BUILD_INSTRUCTIONS.md', data.instructions);
					}, 1000);

					// Download JSON config
					setTimeout(function () {
						downloadFile('app_config.json', JSON.stringify(data.config, null, 2));
					}, 1500);

					showNotice('Archivos de configuración descargados', 'success');
				}
			}
		});
	}

	/**
     * Download file helper
     */
	function downloadFile(filename, content) {
		const blob = new Blob([content], { type: 'text/plain' });
		const url = URL.createObjectURL(blob);
		const a = document.createElement('a');
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		document.body.removeChild(a);
		URL.revokeObjectURL(url);
	}

	/**
     * Start build
     */
	function startBuild() {
		if (!confirm(flavorApkBuilder.i18n.confirmBuild)) {
			return;
		}

		// First save config
		const formData = collectFormData();
		formData.action = 'flavor_apk_save_config';

		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: formData,
			success: function (response) {
				if (response.success) {
					// Now start build
					initiateBuilProcess();
				} else {
					showNotice('Error al guardar configuración', 'error');
				}
			}
		});
	}

	/**
     * Initiate build process
     */
	function initiateBuilProcess() {
		showSpinner('Iniciando compilación...');

		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_apk_start_build',
				nonce: flavorApkBuilder.nonce
			},
			success: function (response) {
				hideSpinner();

				if (response.success) {
					// Show build log section
					$('#build-log-section').show();
					$('#build-status').text('En proceso...').attr('class', 'build-status running');
					$('#build-log').text('Iniciando compilación...\n');
					$('#build-progress-bar').css('width', '5%');

					// Start checking build status
					buildCheckInterval = setInterval(checkBuildStatus, 3000);
				} else {
					// Show manual instructions
					if (response.data && response.data.instructions) {
						showManualInstructions(response.data.instructions);
					} else {
						showNotice(response.data?.message || 'Error al iniciar build', 'error');
					}
				}
			},
			error: function () {
				hideSpinner();
				showNotice('Error de conexión', 'error');
			}
		});
	}

	/**
     * Check build status
     */
	function checkBuildStatus() {
		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_apk_check_build_status',
				nonce: flavorApkBuilder.nonce
			},
			success: function (response) {
				if (response.success) {
					const data = response.data;

					// Update progress
					$('#build-progress-bar').css('width', data.progress + '%');

					// Update log
					const $log = $('#build-log');
					$log.text(data.log || '');
					$log.scrollTop($log[0].scrollHeight);

					// Check if complete
					if (data.status === 'success') {
						clearInterval(buildCheckInterval);
						$('#build-status').text('Completado').attr('class', 'build-status success');
						showNotice('APK compilada correctamente', 'success');

						if (data.apk_path) {
							$log.append('\n\n✅ APK generada: ' + data.apk_path);
						}

						loadBuilds();
					} else if (data.status === 'error') {
						clearInterval(buildCheckInterval);
						$('#build-status').text('Error').attr('class', 'build-status error');
						showNotice('Error en la compilación', 'error');
						loadBuilds();
					}
				}
			}
		});
	}

	/**
     * Load builds history
     */
	function loadBuilds() {
		$.ajax({
			url: flavorApkBuilder.ajaxUrl,
			type: 'POST',
			data: {
				action: 'flavor_apk_list_builds',
				nonce: flavorApkBuilder.nonce
			},
			success: function (response) {
				if (response.success) {
					renderBuilds(response.data);
				}
			}
		});
	}

	/**
     * Render builds list
     */
	function renderBuilds(data) {
		const $list = $('#builds-list');
		$list.empty();

		// Current build
		if (data.current && data.current.status === 'running') {
			$list.append(renderBuildItem(data.current, true));
		}

		// History
		if (data.history && data.history.length > 0) {
			data.history.forEach(function (build) {
				$list.append(renderBuildItem(build, false));
			});
		} else if (!data.current) {
			$list.html('<div class="loading">No hay builds anteriores</div>');
		}
	}

	/**
     * Render single build item
     */
	function renderBuildItem(build, isCurrent) {
		const statusClass = build.status === 'success' ? 'success' :
			build.status === 'error' ? 'error' : 'running';
		const statusIcon = build.status === 'success' ? 'yes' :
			build.status === 'error' ? 'no' : 'update';
		const date = new Date(build.started_at || build.completed_at);
		const dateStr = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();

		let actions = '';
		if (build.status === 'success' && build.apk_path) {
			actions = '<button class="button button-small">Descargar</button>';
		}

		return `
            <div class="build-item">
                <div class="build-status-icon ${statusClass}">
                    <span class="dashicons dashicons-${statusIcon}"></span>
                </div>
                <div class="build-info">
                    <div class="build-name">${build.config?.app_name || 'App'} v${build.config?.app_version || '1.0.0'}</div>
                    <div class="build-date">${dateStr}</div>
                </div>
                <div class="build-actions">${actions}</div>
            </div>
        `;
	}

	/**
     * Show manual instructions modal
     */
	function showManualInstructions(instructions) {
		const $modal = $('<div class="spinner-overlay"><div class="manual-instructions">' +
            '<h3>Compilación Manual Requerida</h3>' +
            '<p>Flutter no está disponible en el servidor. Sigue estas instrucciones para compilar localmente:</p>' +
            '<pre>' + escapeHtml(instructions) + '</pre>' +
            '<button class="button button-primary close-modal">Cerrar</button>' +
            '</div></div>');

		$modal.find('.close-modal').on('click', function () {
			$modal.remove();
		});

		$('body').append($modal);
	}

	/**
     * Show spinner
     */
	function showSpinner(message) {
		const $spinner = $('<div class="spinner-overlay"><div class="spinner-content">' +
            '<div class="spinner"></div>' +
            '<p>' + message + '</p>' +
            '</div></div>');
		$('body').append($spinner);
	}

	/**
     * Hide spinner
     */
	function hideSpinner() {
		$('.spinner-overlay').remove();
	}

	/**
     * Show notice
     */
	function showNotice(message, type) {
		const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
		$('.flavor-apk-builder-wrap h1').after($notice);

		setTimeout(function () {
			$notice.fadeOut(function () {
				$(this).remove();
			});
		}, 5000);
	}

	/**
     * Escape HTML
     */
	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Add spin animation for dashicons
	$('<style>.dashicons.spin { animation: spin 1s linear infinite; }</style>').appendTo('head');

	// Initialize on document ready
	$(document).ready(init);

})(jQuery);
