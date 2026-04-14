/**
 * Flavor Settings Hub - JavaScript
 *
 * Búsqueda y navegación del hub de configuración
 *
 * @package FlavorPlatform
 * @since 3.5.1
 */

(function($) {
	'use strict';

	const SettingsHub = {
		searchInput: null,
		searchResults: null,
		categoriesGrid: null,
		searchTimeout: null,
		lastQuery: '',

		/**
		 * Inicializar
		 */
		init: function() {
			this.searchInput = $('#fsh-search');
			this.searchResults = $('#fsh-search-results');
			this.categoriesGrid = $('#fsh-categories');

			if (!this.searchInput.length) {
				return;
			}

			this.bindEvents();
		},

		/**
		 * Vincular eventos
		 */
		bindEvents: function() {
			const self = this;

			// Búsqueda en tiempo real
			this.searchInput.on('input', function() {
				clearTimeout(self.searchTimeout);
				self.searchTimeout = setTimeout(function() {
					self.performSearch();
				}, 200);
			});

			// Atajo de teclado Ctrl+/ para enfocar búsqueda
			$(document).on('keydown', function(e) {
				if ((e.ctrlKey || e.metaKey) && e.key === '/') {
					e.preventDefault();
					self.searchInput.focus().select();
				}

				// Escape para limpiar búsqueda
				if (e.key === 'Escape' && self.searchInput.is(':focus')) {
					self.searchInput.val('');
					self.clearSearch();
				}
			});

			// Limpiar al hacer clic fuera
			this.searchInput.on('blur', function() {
				// Pequeño delay para permitir click en resultados
				setTimeout(function() {
					if (self.searchInput.val() === '') {
						self.clearSearch();
					}
				}, 200);
			});
		},

		/**
		 * Realizar búsqueda
		 */
		performSearch: function() {
			const query = this.searchInput.val().trim();

			// Si el query está vacío, mostrar categorías
			if (query.length < 2) {
				this.clearSearch();
				return;
			}

			// Evitar búsquedas duplicadas
			if (query === this.lastQuery) {
				return;
			}
			this.lastQuery = query;

			// Búsqueda local primero (más rápida)
			const results = this.localSearch(query);
			this.renderResults(results);
		},

		/**
		 * Búsqueda local en los datos
		 */
		localSearch: function(query) {
			if (!flavorSettingsHub || !flavorSettingsHub.categories) {
				return [];
			}

			const queryLower = query.toLowerCase();
			const results = [];

			Object.keys(flavorSettingsHub.categories).forEach(function(categoryId) {
				const category = flavorSettingsHub.categories[categoryId];

				if (!category.items || !Array.isArray(category.items)) {
					return;
				}

				category.items.forEach(function(item) {
					let match = false;

					// Buscar en título
					if (item.title && item.title.toLowerCase().includes(queryLower)) {
						match = true;
					}

					// Buscar en descripción
					if (!match && item.description && item.description.toLowerCase().includes(queryLower)) {
						match = true;
					}

					// Buscar en keywords
					if (!match && item.keywords && Array.isArray(item.keywords)) {
						for (let i = 0; i < item.keywords.length; i++) {
							if (item.keywords[i].toLowerCase().includes(queryLower)) {
								match = true;
								break;
							}
						}
					}

					if (match) {
						results.push({
							title: item.title,
							description: item.description,
							url: item.url,
							icon: item.icon,
							category: category.title,
							color: category.color
						});
					}
				});
			});

			return results;
		},

		/**
		 * Renderizar resultados
		 */
		renderResults: function(results) {
			const grid = this.searchResults.find('.fsh-search-results__grid');
			grid.empty();

			if (results.length === 0) {
				grid.html(
					'<div class="fsh-search-empty">' +
					'<span class="dashicons dashicons-search"></span>' +
					'<p>' + (flavorSettingsHub.i18n.noResults || 'No se encontraron resultados') + '</p>' +
					'</div>'
				);
			} else {
				results.forEach(function(result) {
					const html =
						'<a href="' + result.url + '" class="fsh-search-result" style="--result-color: ' + result.color + '">' +
						'<div class="fsh-search-result__icon">' +
						'<span class="dashicons ' + result.icon + '"></span>' +
						'</div>' +
						'<div class="fsh-search-result__content">' +
						'<span class="fsh-search-result__title">' + result.title + '</span>' +
						'<span class="fsh-search-result__category">' + result.category + '</span>' +
						'</div>' +
						'</a>';
					grid.append(html);
				});
			}

			// Mostrar resultados, ocultar categorías
			this.searchResults.show();
			this.categoriesGrid.hide();
		},

		/**
		 * Limpiar búsqueda
		 */
		clearSearch: function() {
			this.lastQuery = '';
			this.searchResults.hide();
			this.categoriesGrid.show();
		}
	};

	// Inicializar cuando el DOM esté listo
	$(document).ready(function() {
		SettingsHub.init();
	});

})(jQuery);
