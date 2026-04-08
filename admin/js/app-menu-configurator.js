/**
 * App Menu Configurator JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function ($) {
	'use strict';

	const MenuConfigurator = {
		config: null,
		availableItems: {},
		usedItems: new Set(),

		// Material Design Icons mapping
		icons: [
			'home', 'person', 'settings', 'notifications', 'search', 'info',
			'event', 'forum', 'group', 'people', 'shopping_bag', 'storefront',
			'schedule', 'calendar_today', 'meeting_room', 'directions_bike',
			'local_parking', 'school', 'construction', 'local_library',
			'poll', 'account_balance', 'campaign', 'public', 'chat',
			'directions_car', 'report_problem', 'description', 'visibility',
			'perm_media', 'radio', 'podcasts', 'favorite', 'star', 'bookmark',
			'mail', 'phone', 'location_on', 'work', 'attach_money'
		],

		init: function () {
			this.config = flavorAppMenu.currentConfig;
			this.availableItems = flavorAppMenu.availableItems;

			this.cacheElements();
			this.bindEvents();
			this.renderAvailableItems();
			this.renderConfiguredItems();
			this.initSortable();
			this.updatePreview();
			this.updateUsedItems();
		},

		cacheElements: function () {
			this.$container = $('.flavor-app-menu-configurator');
			this.$bottomTabsZone = $('#bottom-tabs-zone');
			this.$drawerZone = $('#drawer-zone');
			this.$previewTabs = $('#preview-tabs');
			this.$tabCount = $('#tab-count');
			this.$modal = $('#item-modal');
		},

		bindEvents: function () {
			const self = this;

			// Navigation style change
			$('input[name="nav_style"]').on('change', function () {
				self.config.style = $(this).val();
				$('.style-option').removeClass('selected');
				$(this).closest('.style-option').addClass('selected');
				self.updateSectionsVisibility();
			});

			// Show labels toggle
			$('#show-labels').on('change', function () {
				self.config.show_labels = $(this).is(':checked');
				self.updatePreview();
			});

			// Save button
			$('#save-navigation').on('click', function () {
				self.saveNavigation();
			});

			// Reset button
			$('#reset-navigation').on('click', function () {
				if (confirm(flavorAppMenu.i18n.confirm_reset)) {
					self.resetNavigation();
				}
			});

			// Edit item
			this.$container.on('click', '.btn-edit', function () {
				const $item = $(this).closest('.nav-item');
				self.openEditModal($item);
			});

			// Remove item
			this.$container.on('click', '.btn-remove', function () {
				const $item = $(this).closest('.nav-item');
				self.removeItem($item);
			});

			// Category toggle
			$('.category-header').on('click', function () {
				$(this).closest('.category-group').toggleClass('collapsed');
			});

			// Search items
			$('#search-items').on('input', function () {
				const query = $(this).val().toLowerCase();
				self.filterAvailableItems(query);
			});

			// Modal close
			this.$modal.on('click', '.item-modal-close, .btn-cancel', function () {
				self.closeModal();
			});

			this.$modal.on('click', function (e) {
				if ($(e.target).is('.item-modal-overlay')) {
					self.closeModal();
				}
			});

			// Modal save
			$('#save-item-btn').on('click', function () {
				self.saveItemEdit();
			});

			// Icon selection
			this.$modal.on('click', '.icon-option', function () {
				$('.icon-option').removeClass('selected');
				$(this).addClass('selected');
				$('#edit-icon').val($(this).data('icon'));
			});
		},

		initSortable: function () {
			const self = this;

			// Make zones sortable
			this.$bottomTabsZone.sortable({
				items: '.nav-item',
				connectWith: '#drawer-zone, .category-items',
				placeholder: 'nav-item-placeholder',
				receive: function (event, ui) {
					self.handleReceive($(this), ui);
				},
				update: function () {
					self.updateConfigFromUI();
					self.updatePreview();
					self.updateUsedItems();
				}
			});

			this.$drawerZone.sortable({
				items: '.nav-item',
				connectWith: '#bottom-tabs-zone, .category-items',
				placeholder: 'nav-item-placeholder',
				receive: function (event, ui) {
					self.handleReceive($(this), ui);
				},
				update: function () {
					self.updateConfigFromUI();
					self.updateUsedItems();
				}
			});

			// Make available items draggable
			$('.category-items').sortable({
				items: '.available-item:not(.is-used)',
				connectWith: '#bottom-tabs-zone, #drawer-zone',
				helper: 'clone',
				placeholder: 'nav-item-placeholder',
				start: function (event, ui) {
					ui.item.addClass('dragging');
				},
				stop: function (event, ui) {
					ui.item.removeClass('dragging');
				}
			});
		},

		handleReceive: function ($zone, ui) {
			const self = this;
			const zoneId = $zone.attr('id');
			const isBottomTabs = zoneId === 'bottom-tabs-zone';

			// Check max items for bottom tabs
			if (isBottomTabs) {
				const currentCount = $zone.find('.nav-item').length;
				if (currentCount > 5) {
					$(ui.sender).sortable('cancel');
					this.showToast(flavorAppMenu.i18n.max_tabs, 'error');
					return;
				}
			}

			// Convert available-item to nav-item if needed
			if (ui.item.hasClass('available-item')) {
				const itemId = ui.item.data('id');
				const itemData = this.availableItems[itemId];

				if (itemData) {
					const $navItem = this.createNavItem(itemData);
					ui.item.replaceWith($navItem);
				}
			}

			this.updateConfigFromUI();
			this.updatePreview();
			this.updateUsedItems();
			this.updateTabCount();
		},

		renderAvailableItems: function () {
			const self = this;

			Object.keys(flavorAppMenu.categories).forEach(function (categoryId) {
				const $container = $('#category-' + categoryId);
				$container.empty();

				Object.values(self.availableItems).forEach(function (item) {
					if (item.category === categoryId) {
						const $item = $(`
                            <div class="available-item" data-id="${item.id}">
                                <span class="item-icon material-icons">${item.icon}</span>
                                <span class="item-label">${item.label}</span>
                            </div>
                        `);
						$container.append($item);
					}
				});
			});
		},

		renderConfiguredItems: function () {
			const self = this;

			// Bottom tabs
			this.$bottomTabsZone.empty();
			this.config.bottom_tabs.forEach(function (item) {
				const $navItem = self.createNavItem(item);
				self.$bottomTabsZone.append($navItem);
			});

			// Drawer items
			this.$drawerZone.empty();
			this.config.drawer_items.forEach(function (item) {
				const $navItem = self.createNavItem(item);
				self.$drawerZone.append($navItem);
			});

			this.updateTabCount();
			this.updateSectionsVisibility();
		},

		createNavItem: function (item) {
			return $(`
                <div class="nav-item" data-id="${item.id}">
                    <span class="item-drag dashicons dashicons-move"></span>
                    <span class="item-icon material-icons">${item.icon}</span>
                    <div class="item-content">
                        <div class="item-label">${item.label}</div>
                        <div class="item-route">${item.route}</div>
                    </div>
                    <div class="item-actions">
                        <button type="button" class="btn-edit" title="Editar">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                        <button type="button" class="btn-remove" title="Quitar">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
            `);
		},

		updateConfigFromUI: function () {
			const self = this;

			// Bottom tabs
			this.config.bottom_tabs = [];
			this.$bottomTabsZone.find('.nav-item').each(function () {
				const id = $(this).data('id');
				const item = self.getItemData($(this));
				self.config.bottom_tabs.push(item);
			});

			// Drawer items
			this.config.drawer_items = [];
			this.$drawerZone.find('.nav-item').each(function () {
				const item = self.getItemData($(this));
				self.config.drawer_items.push(item);
			});

			this.updateTabCount();
		},

		getItemData: function ($item) {
			const id = $item.data('id');
			const baseItem = this.availableItems[id] || {};

			return {
				id: id,
				label: $item.find('.item-label').text() || baseItem.label || '',
				icon: $item.find('.item-icon').text() || baseItem.icon || 'home',
				route: $item.find('.item-route').text() || baseItem.route || '/',
				module: baseItem.module || ''
			};
		},

		updatePreview: function () {
			const self = this;
			let html = '';

			const tabs = this.config.bottom_tabs.slice(0, 5);
			tabs.forEach(function (tab, index) {
				html += `
                    <a href="#" class="preview-tab ${index === 0 ? 'active' : ''}">
                        <span class="tab-icon material-icons">${tab.icon}</span>
                        ${self.config.show_labels ? `<span class="tab-label">${tab.label}</span>` : ''}
                    </a>
                `;
			});

			this.$previewTabs.html(html);
		},

		updateTabCount: function () {
			const count = this.$bottomTabsZone.find('.nav-item').length;
			this.$tabCount.text(count);

			if (count >= 5) {
				this.$bottomTabsZone.addClass('is-full');
			} else {
				this.$bottomTabsZone.removeClass('is-full');
			}
		},

		updateUsedItems: function () {
			const self = this;
			this.usedItems.clear();

			this.$bottomTabsZone.find('.nav-item').each(function () {
				self.usedItems.add($(this).data('id'));
			});

			this.$drawerZone.find('.nav-item').each(function () {
				self.usedItems.add($(this).data('id'));
			});

			// Update available items visual state
			$('.available-item').each(function () {
				const id = $(this).data('id');
				if (self.usedItems.has(id)) {
					$(this).addClass('is-used');
				} else {
					$(this).removeClass('is-used');
				}
			});
		},

		updateSectionsVisibility: function () {
			const style = this.config.style;

			if (style === 'bottom_tabs') {
				$('#bottom-tabs-section').show();
				$('#drawer-section').hide();
			} else if (style === 'drawer') {
				$('#bottom-tabs-section').hide();
				$('#drawer-section').show();
			} else {
				$('#bottom-tabs-section').show();
				$('#drawer-section').show();
			}
		},

		filterAvailableItems: function (query) {
			$('.available-item').each(function () {
				const label = $(this).find('.item-label').text().toLowerCase();
				if (query === '' || label.includes(query)) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		},

		removeItem: function ($item) {
			$item.remove();
			this.updateConfigFromUI();
			this.updatePreview();
			this.updateUsedItems();
		},

		openEditModal: function ($item) {
			const id = $item.data('id');
			const label = $item.find('.item-label').text();
			const icon = $item.find('.item-icon').text();
			const route = $item.find('.item-route').text();

			$('#edit-item-id').val(id);
			$('#edit-label').val(label);
			$('#edit-icon').val(icon);
			$('#edit-route').val(route);

			// Render icon selector
			this.renderIconSelector(icon);

			this.$modal.addClass('is-visible');
			this.$modal.data('$item', $item);
		},

		renderIconSelector: function (selectedIcon) {
			const self = this;
			let html = '';

			this.icons.forEach(function (icon) {
				html += `
                    <span class="icon-option ${icon === selectedIcon ? 'selected' : ''}"
                          data-icon="${icon}">
                        <span class="material-icons">${icon}</span>
                    </span>
                `;
			});

			$('#icon-selector').html(html);
		},

		saveItemEdit: function () {
			const $item = this.$modal.data('$item');

			const label = $('#edit-label').val();
			const icon = $('#edit-icon').val();
			const route = $('#edit-route').val();

			$item.find('.item-label').text(label);
			$item.find('.item-icon').text(icon);
			$item.find('.item-route').text(route);

			this.updateConfigFromUI();
			this.updatePreview();
			this.closeModal();
		},

		closeModal: function () {
			this.$modal.removeClass('is-visible');
			this.$modal.removeData('$item');
		},

		saveNavigation: function () {
			const self = this;
			const $btn = $('#save-navigation');

			$btn.prop('disabled', true).text('Guardando...');

			$.ajax({
				url: flavorAppMenu.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_save_app_navigation',
					nonce: flavorAppMenu.nonce,
					style: this.config.style,
					bottom_tabs: JSON.stringify(this.config.bottom_tabs),
					drawer_items: JSON.stringify(this.config.drawer_items),
					show_labels: this.config.show_labels
				},
				success: function (response) {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar Cambios');

					if (response.success) {
						self.showToast(flavorAppMenu.i18n.saved, 'success');
					} else {
						self.showToast(response.data.message || flavorAppMenu.i18n.error, 'error');
					}
				},
				error: function () {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> Guardar Cambios');
					self.showToast(flavorAppMenu.i18n.error, 'error');
				}
			});
		},

		resetNavigation: function () {
			const self = this;

			$.ajax({
				url: flavorAppMenu.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_reset_app_navigation',
					nonce: flavorAppMenu.nonce
				},
				success: function (response) {
					if (response.success) {
						self.config = response.data.config;
						self.renderConfiguredItems();
						self.updatePreview();
						self.updateUsedItems();
						self.showToast('Navegación restablecida', 'success');
					}
				}
			});
		},

		showToast: function (message, type) {
			const $toast = $('<div class="menu-toast"></div>')
				.text(message)
				.addClass('is-' + (type || 'info'));

			$('body').append($toast);

			setTimeout(function () {
				$toast.addClass('is-visible');
			}, 10);

			setTimeout(function () {
				$toast.removeClass('is-visible');
				setTimeout(function () {
					$toast.remove();
				}, 300);
			}, 3000);
		}
	};

	$(document).ready(function () {
		if ($('.flavor-app-menu-configurator').length) {
			MenuConfigurator.init();
		}
	});

})(jQuery);
