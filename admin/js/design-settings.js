(function ($) {
	function getSettingsConfig() {
		return window.flavorDesignSettings || {};
	}

	function getStrings() {
		return getSettingsConfig().strings || {};
	}

	function hideElement(element) {
		if (element) {
			element.classList.add('flavor-is-hidden');
		}
	}

	function showElement(element) {
		if (element) {
			element.classList.remove('flavor-is-hidden');
		}
	}

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text || '';
		return div.innerHTML;
	}

	function truncateText(text, maxLength) {
		if (!text || text.length <= maxLength) {
			return text || '';
		}

		return text.substring(0, maxLength) + '...';
	}

	function showFeedback(target, message, type, timeout) {
		if (!target) {
			return;
		}

		target.className = 'flavor-theme-feedback flavor-theme-feedback--' + type;
		target.textContent = message;
		showElement(target);

		window.setTimeout(function () {
			hideElement(target);
		}, timeout || 3000);
	}

	function initColorPickers() {
		$('.flavor-color-picker').each(function () {
			const $input = $(this);

			$input.wpColorPicker({
				change: function (event, ui) {
					if (!$input.hasClass('flavor-module-color-input')) {
						return;
					}

					const color = ui.color.toString();
					const preview = $input.closest('.flavor-module-color-card').find('.flavor-module-color-preview');
					preview.css('--flavor-module-preview-color', color);
				}
			});
		});
	}

	function initLayoutSelector() {
		document.querySelectorAll('.flavor-portal-layout-selector .flavor-layout-option input[type="radio"]').forEach(function (radio) {
			radio.addEventListener('change', function () {
				document.querySelectorAll('.flavor-portal-layout-selector .flavor-layout-option').forEach(function (option) {
					option.classList.remove('flavor-layout-option--active');
				});

				if (this.checked) {
					const selected = this.closest('.flavor-layout-option');
					if (selected) {
						selected.classList.add('flavor-layout-option--active');
					}
				}
			});
		});
	}

	function initThemeManager() {
		const config = getSettingsConfig();
		const strings = getStrings();
		const gridEl = document.getElementById('flavor-themes-grid');
		const feedbackEl = document.getElementById('flavor-theme-feedback');
		const categorySelect = document.getElementById('flavor-category-select');
		const themesCountEl = document.getElementById('flavor-themes-count');
		const starterBtn = document.getElementById('flavor-install-starter-theme');
		const starterStatus = document.getElementById('flavor-starter-theme-status');

		if (!gridEl || !feedbackEl || !categorySelect || !themesCountEl || !config.ajaxUrl || !config.nonce) {
			return;
		}

		let categoriasDisponibles = {};

		function getThemeColors(theme) {
			let colorPrimario = '#3b82f6';
			let colorFondo = '#ffffff';
			let colorTexto = '#1f2937';
			let colorTextoSec = '#6b7280';

			const coloresTema = {
				'default': '#3b82f6', 'modern-purple': '#8b5cf6', 'ocean-blue': '#0891b2',
				'forest-green': '#16a34a', 'sunset-orange': '#ea580c', 'dark-mode': '#60a5fa',
				'minimal': '#171717', 'corporate': '#1e40af', 'themacle': '#5660b9',
				'themacle-dark': '#7b84d1', 'zunbeltz': '#2D5F2E', 'naarq': '#1a1a1a',
				'campi': '#1a1b3a', 'denendako': '#333333', 'escena-familiar': '#7c3aed',
				'grupos-consumo': '#4a7c59', 'comunidad-viva': '#4f46e5', 'jantoki': '#8b5a2b',
				'mercado-espiral': '#2e7d32', 'spiral-bank': '#764ba2', 'red-cuidados': '#ec4899',
				'academia-espiral': '#d97706', 'democracia-universal': '#8b5cf6', 'flujo': '#166534',
				'kulturaka': '#e63946', 'pueblo-vivo': '#c2703a', 'ecos-comunitarios': '#0891b2',
				'salud-vital': '#0d9488', 'academia-moderna': '#7c3aed', 'fitness-energy': '#dc2626',
				'galeria-arte': '#1f2937', 'tech-startup': '#6366f1', 'organic-fresh': '#65a30d',
				'real-estate-pro': '#0369a1', 'corporate-trust': '#1e3a5f', 'gastro-deluxe': '#92400e',
				'kids-fun': '#f97316'
			};

			const fondosTema = {
				'dark-mode': '#111827', 'themacle-dark': '#1a1a2e', 'minimal': '#fafafa',
				'naarq': '#f5f0e8', 'campi': '#0d0e24', 'denendako': '#fafafa',
				'comunidad-viva': '#f8f9ff', 'jantoki': '#fdf8f3', 'mercado-espiral': '#f9fdf9',
				'spiral-bank': '#faf8ff', 'red-cuidados': '#fef7fb', 'academia-espiral': '#fffbf5',
				'democracia-universal': '#faf8ff', 'flujo': '#f7fdf9', 'kulturaka': '#fffaf9',
				'pueblo-vivo': '#fdf9f5', 'ecos-comunitarios': '#f5fcff',
				'salud-vital': '#f0fdfa', 'academia-moderna': '#faf5ff', 'fitness-energy': '#fafafa',
				'galeria-arte': '#fafafa', 'tech-startup': '#f8fafc', 'organic-fresh': '#f7fee7',
				'real-estate-pro': '#f8fafc', 'corporate-trust': '#f8fafc', 'gastro-deluxe': '#fffbeb',
				'kids-fun': '#fffbeb'
			};

			const darkThemes = ['dark-mode', 'themacle-dark', 'campi'];
			colorPrimario = coloresTema[theme.id] || colorPrimario;
			colorFondo = fondosTema[theme.id] || colorFondo;

			const isDark = darkThemes.indexOf(theme.id) !== -1;
			colorTexto = isDark ? '#e5e7eb' : '#1f2937';
			colorTextoSec = isDark ? '#9ca3af' : '#6b7280';

			return {
				primario: colorPrimario,
				fondo: colorFondo,
				texto: colorTexto,
				textoSecundario: colorTextoSec
			};
		}

		function updateCategorySelector() {
			categorySelect.innerHTML = '';

			Object.keys(categoriasDisponibles).forEach(function (catId) {
				const option = document.createElement('option');
				option.value = catId;
				option.textContent = categoriasDisponibles[catId];
				categorySelect.appendChild(option);
			});
		}

		function setGridInteractivity(enabled) {
			gridEl.querySelectorAll('.flavor-theme-card').forEach(function (card) {
				card.style.pointerEvents = enabled ? '' : 'none';
			});
		}

		function renderThemes(themes, activeTheme) {
			let html = '';
			let total = 0;

			Object.keys(themes).forEach(function (id) {
				const theme = themes[id];
				theme.id = id;
				total++;

				const isActive = id === activeTheme;
				const colors = getThemeColors(theme);

				html += '<div class="flavor-theme-card' + (isActive ? ' flavor-theme-card--active' : '') + '" data-theme-id="' + id + '" data-category="' + (theme.category || 'general') + '">';
				html += '<div class="flavor-theme-card__preview" style="background:' + colors.fondo + ';">';
				html += '<div class="flavor-theme-card__preview-header" style="background:' + colors.primario + ';"></div>';
				html += '<div class="flavor-theme-card__preview-body">';
				html += '<div class="flavor-theme-card__preview-title" style="background:' + colors.texto + ';"></div>';
				html += '<div class="flavor-theme-card__preview-text" style="background:' + colors.textoSecundario + ';"></div>';
				html += '<div class="flavor-theme-card__preview-text flavor-theme-card__preview-text--short" style="background:' + colors.textoSecundario + ';"></div>';
				html += '<div class="flavor-theme-card__preview-btn" style="background:' + colors.primario + ';"></div>';
				html += '</div></div>';
				html += '<div class="flavor-theme-card__info">';
				html += '<div class="flavor-theme-card__name">' + escapeHtml(theme.name) + '</div>';
				html += '<div class="flavor-theme-card__desc">' + escapeHtml(theme.description || '') + '</div>';

				if (theme.ideal_for) {
					html += '<div class="flavor-theme-card__ideal-for" title="' + escapeHtml(theme.ideal_for) + '">';
					html += '<span class="dashicons dashicons-lightbulb" style="font-size:12px;width:12px;height:12px;margin-right:4px;color:#f59e0b;"></span>';
					html += '<span style="font-size:10px;color:#6b7280;">' + escapeHtml(truncateText(theme.ideal_for, 40)) + '</span>';
					html += '</div>';
				}

				if (theme.category_label && theme.category !== 'general') {
					html += '<span class="flavor-theme-card__badge flavor-theme-card__badge--category">' + escapeHtml(theme.category_label) + '</span>';
				}

				if (isActive) {
					html += '<span class="flavor-theme-card__badge">' + escapeHtml(strings.active || 'Activo') + '</span>';
				}

				if (theme.is_custom) {
					html += '<span class="flavor-theme-card__badge flavor-theme-card__badge--custom">' + escapeHtml(strings.custom || 'Custom') + '</span>';
				}

				html += '</div></div>';
			});

			gridEl.innerHTML = html;
			themesCountEl.textContent = total + ' ' + (strings.themesAvailable || 'temas disponibles');

			gridEl.querySelectorAll('.flavor-theme-card').forEach(function (card) {
				card.addEventListener('click', function () {
					applyTheme(this.getAttribute('data-theme-id'), this);
				});
			});
		}

		function loadThemes(category) {
			const formData = new FormData();
			formData.append('action', 'flavor_get_themes');
			formData.append('nonce', config.nonce);
			formData.append('category', category || 'all');

			fetch(config.ajaxUrl, { method: 'POST', body: formData })
				.then(function (response) { return response.json(); })
				.then(function (response) {
					if (!response.success) {
						gridEl.innerHTML = '<p>' + escapeHtml(strings.loadThemesError || 'Error al cargar temas.') + '</p>';
						return;
					}

					if (response.data.categories && Object.keys(categoriasDisponibles).length === 0) {
						categoriasDisponibles = response.data.categories;
						updateCategorySelector();
					}

					renderThemes(response.data.themes, response.data.active_theme);
				})
				.catch(function () {
					gridEl.innerHTML = '<p>' + escapeHtml(strings.connectionError || 'Error de conexión.') + '</p>';
				});
		}

		function applyTheme(themeId, cardEl) {
			const formData = new FormData();
			formData.append('action', 'flavor_set_theme');
			formData.append('nonce', config.nonce);
			formData.append('theme_id', themeId);

			setGridInteractivity(false);
			cardEl.classList.add('flavor-theme-card--loading');

			fetch(config.ajaxUrl, { method: 'POST', body: formData })
				.then(function (response) { return response.json(); })
				.then(function (response) {
					cardEl.classList.remove('flavor-theme-card--loading');
					setGridInteractivity(true);

					if (!response.success) {
						showFeedback(feedbackEl, (response.data && response.data.message) || 'Error', 'error');
						return;
					}

					gridEl.querySelectorAll('.flavor-theme-card').forEach(function (card) {
						card.classList.remove('flavor-theme-card--active');
					});
					cardEl.classList.add('flavor-theme-card--active');

					gridEl.querySelectorAll('.flavor-theme-card__badge').forEach(function (badge) {
						if (badge.classList.contains('flavor-theme-card__badge--custom') || badge.classList.contains('flavor-theme-card__badge--category')) {
							return;
						}
						badge.remove();
					});

					const infoEl = cardEl.querySelector('.flavor-theme-card__info');
					if (infoEl) {
						const badge = document.createElement('span');
						badge.className = 'flavor-theme-card__badge';
						badge.textContent = strings.active || 'Activo';
						infoEl.appendChild(badge);
					}

					showFeedback(feedbackEl, strings.themeAppliedReloading || 'Tema aplicado correctamente. Recargando...', 'success');
					window.setTimeout(function () {
						window.location.reload();
					}, 600);
				})
				.catch(function () {
					cardEl.classList.remove('flavor-theme-card--loading');
					setGridInteractivity(true);
					showFeedback(feedbackEl, strings.connectionError || 'Error de conexión.', 'error');
				});
		}

		if (starterBtn) {
			starterBtn.addEventListener('click', function () {
				starterBtn.disabled = true;
				if (starterStatus) {
					starterStatus.textContent = strings.installingStarter || 'Instalando y activando...';
				}
				window.location.href = config.starterThemeUrl;
			});
		}

		categorySelect.addEventListener('change', function () {
			loadThemes(this.value);
		});

		window.flavorDesignLoadThemes = loadThemes;
		loadThemes();
	}

	function initTokenExport() {
		const config = getSettingsConfig();
		const strings = getStrings();
		const tokenFormatSelect = document.getElementById('flavor-token-format');
		const previewTokensBtn = document.getElementById('flavor-preview-tokens');
		const downloadTokensBtn = document.getElementById('flavor-download-tokens');
		const copyTokensBtn = document.getElementById('flavor-copy-tokens');
		const tokensPreview = document.getElementById('flavor-tokens-preview');
		const tokensFeedback = document.getElementById('flavor-tokens-feedback');

		if (!tokenFormatSelect || !previewTokensBtn || !downloadTokensBtn || !copyTokensBtn || !tokensPreview || !tokensFeedback || !config.ajaxUrl || !config.nonce) {
			return;
		}

		const tokensCode = tokensPreview.querySelector('code');
		const tokensFilename = tokensPreview.querySelector('.flavor-tokens-filename');
		const tokenFilenames = {
			w3c: 'design-tokens.json',
			css: 'design-tokens.css',
			js: 'design-tokens.js',
			tailwind: 'tailwind.config.js'
		};

		function showTokenFeedback(message, type) {
			tokensFeedback.className = 'flavor-theme-feedback flavor-theme-feedback--' + type + ' flavor-theme-feedback--spaced';
			tokensFeedback.textContent = message;
			showElement(tokensFeedback);

			window.setTimeout(function () {
				hideElement(tokensFeedback);
			}, 3000);
		}

		function getTokens(format, callback) {
			const formData = new FormData();
			formData.append('action', 'flavor_export_tokens_' + format);
			formData.append('nonce', config.nonce);

			fetch(config.ajaxUrl, { method: 'POST', body: formData })
				.then(function (response) { return response.json(); })
				.then(function (response) {
					if (response.success) {
						callback(response.data.content, response.data.filename);
						return;
					}

					showTokenFeedback((response.data && response.data.message) || 'Error', 'error');
				})
				.catch(function () {
					showTokenFeedback(strings.connectionError || 'Error de conexión.', 'error');
				});
		}

		previewTokensBtn.addEventListener('click', function () {
			const format = tokenFormatSelect.value;
			previewTokensBtn.disabled = true;

			getTokens(format, function (content, filename) {
				previewTokensBtn.disabled = false;
				tokensCode.textContent = content;
				tokensFilename.textContent = filename || tokenFilenames[format];
				showElement(tokensPreview);
			});

			window.setTimeout(function () {
				previewTokensBtn.disabled = false;
			}, 5000);
		});

		downloadTokensBtn.addEventListener('click', function () {
			const format = tokenFormatSelect.value;
			downloadTokensBtn.disabled = true;

			getTokens(format, function (content, filename) {
				downloadTokensBtn.disabled = false;
				const blob = new Blob([content], { type: 'text/plain' });
				const url = URL.createObjectURL(blob);
				const link = document.createElement('a');
				link.href = url;
				link.download = filename || tokenFilenames[format];
				document.body.appendChild(link);
				link.click();
				document.body.removeChild(link);
				URL.revokeObjectURL(url);
				showTokenFeedback(strings.fileDownloaded || 'Archivo descargado', 'success');
			});

			window.setTimeout(function () {
				downloadTokensBtn.disabled = false;
			}, 5000);
		});

		copyTokensBtn.addEventListener('click', function () {
			const text = tokensCode.textContent;

			if (navigator.clipboard) {
				navigator.clipboard.writeText(text).then(function () {
					showTokenFeedback(strings.copiedToClipboard || 'Código copiado al portapapeles', 'success');
				});
				return;
			}

			const textarea = document.createElement('textarea');
			textarea.value = text;
			document.body.appendChild(textarea);
			textarea.select();
			document.execCommand('copy');
			document.body.removeChild(textarea);
			showTokenFeedback(strings.copiedToClipboard || 'Código copiado al portapapeles', 'success');
		});
	}

	function initWebTemplateImport() {
		const config = getSettingsConfig();
		const strings = getStrings();
		const importFeedback = document.getElementById('flavor-import-feedback');
		const categorySelect = document.getElementById('flavor-category-select');

		if (!importFeedback || !config.ajaxUrl || !config.nonce) {
			return;
		}

		function getImportButtonLabel(label) {
			return '<span class="dashicons dashicons-download flavor-web-template-card__button-icon"></span> ' + escapeHtml(label);
		}

		function showImportFeedback(message, type, html) {
			importFeedback.className = 'flavor-theme-feedback flavor-theme-feedback--' + type;
			if (html) {
				importFeedback.innerHTML = html;
			} else {
				importFeedback.textContent = message;
			}
			showElement(importFeedback);

			if (type === 'success') {
				window.setTimeout(function () {
					hideElement(importFeedback);
				}, 8000);
			}
		}

		document.querySelectorAll('.flavor-import-web-templates').forEach(function (button) {
			button.addEventListener('click', function () {
				const sectorId = button.getAttribute('data-sector');

				if (!window.confirm(strings.importConfirm || '¿Importar todas las plantillas de este diseño? Se crearán como Landing Pages en borrador.')) {
					return;
				}

				button.disabled = true;
				button.textContent = strings.importing || 'Importando...';

				const formData = new FormData();
				formData.append('action', 'flavor_import_theme_templates');
				formData.append('nonce', config.nonce);
				formData.append('sector_id', sectorId);

				fetch(config.ajaxUrl, { method: 'POST', body: formData })
					.then(function (response) { return response.json(); })
					.then(function (response) {
						button.disabled = false;
						button.innerHTML = getImportButtonLabel(strings.importFullSite || 'Importar Sitio Completo');

						if (!response.success) {
							showImportFeedback((response.data && response.data.message) || 'Error', 'error');
							return;
						}

						const message = response.data.message || '';
						const links = (response.data.pages || []).map(function (page) {
							return '<a href="' + page.edit + '" target="_blank">' + page.title + '</a>';
						}).join(' | ');

						showImportFeedback(message, 'success', message + '<br><small>' + links + '</small>');

						if (typeof window.flavorDesignLoadThemes === 'function') {
							window.flavorDesignLoadThemes(categorySelect ? categorySelect.value : 'all');
						}
					})
					.catch(function () {
						button.disabled = false;
						button.innerHTML = getImportButtonLabel(strings.importFullSite || 'Importar Sitio Completo');
						showImportFeedback(strings.connectionError || 'Error de conexión.', 'error');
					});
			});
		});
	}

	function initPreviewTabs() {
		const tabs = document.querySelectorAll('.flavor-preview-tab');
		const contents = document.querySelectorAll('.flavor-preview-tab-content');

		if (!tabs.length || !contents.length) {
			return;
		}

		tabs.forEach(function (tab) {
			tab.addEventListener('click', function () {
				const targetTab = this.getAttribute('data-tab');

				tabs.forEach(function (item) {
					item.classList.remove('flavor-preview-tab--active');
				});

				this.classList.add('flavor-preview-tab--active');

				contents.forEach(function (content) {
					const visible = content.getAttribute('data-tab-content') === targetTab;
					content.classList.toggle('flavor-is-hidden', !visible);
				});
			});
		});
	}

	$(document).ready(function () {
		initColorPickers();
		initLayoutSelector();
		initThemeManager();
		initTokenExport();
		initWebTemplateImport();
		initPreviewTabs();
	});
})(jQuery);
