/**
 * Feature Flags Admin JavaScript
 *
 * @package Flavor_Chat_IA
 */

(function ($) {
	'use strict';

	const FeatureFlags = {
		flags: [],
		currentFilter: 'all',
		searchQuery: '',

		init: function () {
			this.cacheElements();
			this.bindEvents();
			this.loadFlags();
		},

		cacheElements: function () {
			this.$container = $('.flavor-feature-flags');
			this.$grid = this.$container.find('.flags-grid');
			this.$searchInput = this.$container.find('.search-box input');
			this.$filterButtons = this.$container.find('.filter-btn');
			this.$createForm = this.$container.find('.create-flag-form');
			this.$modal = this.$container.find('.flag-modal-overlay');
			this.$stats = this.$container.find('.flags-stats');
		},

		bindEvents: function () {
			const self = this;

			// Search
			this.$searchInput.on('input', _.debounce(function () {
				self.searchQuery = $(this).val().toLowerCase();
				self.renderFlags();
			}, 300));

			// Filter buttons
			this.$filterButtons.on('click', function () {
				self.$filterButtons.removeClass('active');
				$(this).addClass('active');
				self.currentFilter = $(this).data('filter');
				self.renderFlags();
			});

			// Create form
			this.$createForm.on('submit', function (e) {
				e.preventDefault();
				self.createFlag();
			});

			// Toggle flag
			this.$grid.on('change', '.flag-toggle input', function () {
				const flagKey = $(this).closest('.flag-card').data('key');
				const isEnabled = $(this).is(':checked');
				self.toggleFlag(flagKey, isEnabled);
			});

			// Rollout slider
			this.$grid.on('input', '.rollout-slider input', function () {
				const value = $(this).val();
				$(this).siblings('.rollout-value').text(value + '%');
			});

			this.$grid.on('change', '.rollout-slider input', function () {
				const flagKey = $(this).closest('.flag-card').data('key');
				const percentage = parseInt($(this).val());
				self.updateRollout(flagKey, percentage);
			});

			// Edit button
			this.$grid.on('click', '.btn-edit', function () {
				const flagKey = $(this).closest('.flag-card').data('key');
				self.openEditModal(flagKey);
			});

			// Delete button
			this.$grid.on('click', '.btn-delete', function () {
				const flagKey = $(this).closest('.flag-card').data('key');
				self.deleteFlag(flagKey);
			});

			// Modal close
			this.$modal.on('click', '.flag-modal-close, .btn-cancel', function () {
				self.closeModal();
			});

			this.$modal.on('click', function (e) {
				if ($(e.target).is('.flag-modal-overlay')) {
					self.closeModal();
				}
			});

			// Modal save
			this.$modal.on('click', '.btn-save', function () {
				self.saveModalChanges();
			});

			// Add rule
			this.$grid.on('click', '.add-rule-btn', function () {
				const flagKey = $(this).closest('.flag-card').data('key');
				self.openRuleModal(flagKey);
			});

			// Remove rule tag
			this.$grid.on('click', '.rule-tag .remove-rule', function (e) {
				e.stopPropagation();
				const $tag = $(this).closest('.rule-tag');
				const flagKey = $tag.closest('.flag-card').data('key');
				const ruleIndex = $tag.data('index');
				self.removeRule(flagKey, ruleIndex);
			});
		},

		loadFlags: function () {
			const self = this;

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_get_feature_flags',
					nonce: flavorFeatureFlags.nonce
				},
				success: function (response) {
					if (response.success) {
						self.flags = response.data.flags || [];
						self.renderFlags();
						self.updateStats();
					}
				},
				error: function () {
					self.showToast('Error al cargar flags', 'error');
				}
			});
		},

		renderFlags: function () {
			const self = this;
			let filteredFlags = this.flags;

			// Apply filter
			if (this.currentFilter !== 'all') {
				filteredFlags = filteredFlags.filter(function (flag) {
					switch (self.currentFilter) {
						case 'active':
							return flag.enabled && flag.rollout_percentage === 100;
						case 'rollout':
							return flag.enabled && flag.rollout_percentage < 100;
						case 'inactive':
							return !flag.enabled;
						default:
							return true;
					}
				});
			}

			// Apply search
			if (this.searchQuery) {
				filteredFlags = filteredFlags.filter(function (flag) {
					return flag.key.toLowerCase().includes(self.searchQuery) ||
                           (flag.description && flag.description.toLowerCase().includes(self.searchQuery));
				});
			}

			// Render
			if (filteredFlags.length === 0) {
				this.$grid.html(this.getEmptyStateHtml());
				return;
			}

			const html = filteredFlags.map(function (flag) {
				return self.getFlagCardHtml(flag);
			}).join('');

			this.$grid.html(html);
		},

		getFlagCardHtml: function (flag) {
			const isActive = flag.enabled && flag.rollout_percentage === 100;
			const isRollout = flag.enabled && flag.rollout_percentage < 100;
			const isInactive = !flag.enabled;

			let statusClass = '';
			if (isActive) {statusClass = 'is-active';} else if (isRollout) {statusClass = 'is-rollout';} else if (isInactive) {statusClass = 'is-inactive';}

			const rulesHtml = this.getRulesHtml(flag.rules || []);
			const updatedDate = flag.updated_at ? new Date(flag.updated_at).toLocaleDateString() : '-';

			return `
                <div class="flag-card ${statusClass}" data-key="${flag.key}">
                    <div class="flag-card-header">
                        <div class="flag-info">
                            <h4 class="flag-key">${this.escapeHtml(flag.key)}</h4>
                            <p class="flag-description">${this.escapeHtml(flag.description || '')}</p>
                        </div>
                        <div class="flag-toggle">
                            <label class="toggle-switch ${isRollout ? 'rollout' : ''}">
                                <input type="checkbox" ${flag.enabled ? 'checked' : ''}>
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                    <div class="flag-card-body">
                        <div class="rollout-control">
                            <label>Porcentaje de despliegue</label>
                            <div class="rollout-slider">
                                <input type="range" min="0" max="100" step="5"
                                       value="${flag.rollout_percentage || 100}"
                                       ${!flag.enabled ? 'disabled' : ''}>
                                <span class="rollout-value">${flag.rollout_percentage || 100}%</span>
                            </div>
                        </div>
                        <div class="targeting-rules">
                            <label>Reglas de segmentación</label>
                            <div class="rule-tags">
                                ${rulesHtml}
                                <button type="button" class="add-rule-btn">+ Añadir regla</button>
                            </div>
                        </div>
                    </div>
                    <div class="flag-card-footer">
                        <span class="flag-meta">Actualizado: ${updatedDate}</span>
                        <div class="flag-actions">
                            <button type="button" class="btn-edit">Editar</button>
                            <button type="button" class="btn-delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </div>
                    </div>
                </div>
            `;
		},

		getRulesHtml: function (rules) {
			if (!rules || rules.length === 0) {
				return '<span class="rule-tag">Todos los usuarios</span>';
			}

			return rules.map(function (rule, index) {
				let icon = 'admin-generic';
				let className = '';

				if (rule.type === 'platform') {
					icon = rule.value === 'ios' ? 'apple' : 'smartphone';
					className = 'platform-' + rule.value;
				} else if (rule.type === 'user_role') {
					icon = 'admin-users';
					className = 'user-role';
				} else if (rule.type === 'app_version') {
					icon = 'update';
				}

				return `
                    <span class="rule-tag ${className}" data-index="${index}">
                        <span class="dashicons dashicons-${icon}"></span>
                        ${rule.label || rule.value}
                        <span class="remove-rule dashicons dashicons-no-alt"></span>
                    </span>
                `;
			}).join('');
		},

		getEmptyStateHtml: function () {
			return `
                <div class="flags-empty">
                    <span class="dashicons dashicons-flag"></span>
                    <h3>No hay feature flags</h3>
                    <p>Crea tu primer feature flag para controlar funcionalidades de la app.</p>
                </div>
            `;
		},

		updateStats: function () {
			const stats = {
				total: this.flags.length,
				active: 0,
				rollout: 0,
				inactive: 0
			};

			this.flags.forEach(function (flag) {
				if (!flag.enabled) {
					stats.inactive++;
				} else if (flag.rollout_percentage < 100) {
					stats.rollout++;
				} else {
					stats.active++;
				}
			});

			this.$stats.find('.stat-card.total .stat-value').text(stats.total);
			this.$stats.find('.stat-card.active .stat-value').text(stats.active);
			this.$stats.find('.stat-card.rollout .stat-value').text(stats.rollout);
			this.$stats.find('.stat-card.inactive .stat-value').text(stats.inactive);
		},

		createFlag: function () {
			const self = this;
			const $form = this.$createForm;

			const flagData = {
				key: $form.find('[name="flag_key"]').val(),
				description: $form.find('[name="flag_description"]').val(),
				enabled: $form.find('[name="flag_enabled"]').is(':checked'),
				rollout_percentage: parseInt($form.find('[name="rollout_percentage"]').val()) || 100
			};

			if (!flagData.key) {
				this.showToast('El nombre del flag es requerido', 'error');
				return;
			}

			// Check duplicate
			if (this.flags.some(f => f.key === flagData.key)) {
				this.showToast('Ya existe un flag con ese nombre', 'error');
				return;
			}

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_save_feature_flag',
					nonce: flavorFeatureFlags.nonce,
					flag: flagData
				},
				success: function (response) {
					if (response.success) {
						self.flags.push(flagData);
						self.renderFlags();
						self.updateStats();
						self.showToast('Flag creado correctamente', 'success');
						$form[0].reset();
					} else {
						self.showToast(response.data.message || 'Error al crear flag', 'error');
					}
				},
				error: function () {
					self.showToast('Error de conexión', 'error');
				}
			});
		},

		toggleFlag: function (flagKey, isEnabled) {
			const self = this;
			const flag = this.flags.find(f => f.key === flagKey);

			if (!flag) {return;}

			const $card = this.$grid.find(`[data-key="${flagKey}"]`);
			$card.addClass('is-loading');

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_toggle_feature_flag',
					nonce: flavorFeatureFlags.nonce,
					key: flagKey,
					enabled: isEnabled
				},
				success: function (response) {
					$card.removeClass('is-loading');

					if (response.success) {
						flag.enabled = isEnabled;
						self.renderFlags();
						self.updateStats();
						self.showToast(
							isEnabled ? 'Flag activado' : 'Flag desactivado',
							'success'
						);
					} else {
						// Revert
						$card.find('.flag-toggle input').prop('checked', !isEnabled);
						self.showToast('Error al cambiar estado', 'error');
					}
				},
				error: function () {
					$card.removeClass('is-loading');
					$card.find('.flag-toggle input').prop('checked', !isEnabled);
					self.showToast('Error de conexión', 'error');
				}
			});
		},

		updateRollout: function (flagKey, percentage) {
			const self = this;
			const flag = this.flags.find(f => f.key === flagKey);

			if (!flag) {return;}

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_update_rollout',
					nonce: flavorFeatureFlags.nonce,
					key: flagKey,
					percentage: percentage
				},
				success: function (response) {
					if (response.success) {
						flag.rollout_percentage = percentage;
						self.updateStats();
						self.showToast(`Despliegue actualizado a ${percentage}%`, 'success');
					}
				}
			});
		},

		deleteFlag: function (flagKey) {
			const self = this;

			if (!confirm('¿Eliminar este feature flag? Esta acción no se puede deshacer.')) {
				return;
			}

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_delete_feature_flag',
					nonce: flavorFeatureFlags.nonce,
					key: flagKey
				},
				success: function (response) {
					if (response.success) {
						self.flags = self.flags.filter(f => f.key !== flagKey);
						self.renderFlags();
						self.updateStats();
						self.showToast('Flag eliminado', 'success');
					}
				}
			});
		},

		openEditModal: function (flagKey) {
			const flag = this.flags.find(f => f.key === flagKey);
			if (!flag) {return;}

			const $modal = this.$modal;
			$modal.find('.flag-modal-header h3').text('Editar: ' + flag.key);
			$modal.find('[name="modal_key"]').val(flag.key);
			$modal.find('[name="modal_description"]').val(flag.description || '');
			$modal.find('[name="modal_enabled"]').prop('checked', flag.enabled);
			$modal.find('[name="modal_rollout"]').val(flag.rollout_percentage || 100);

			$modal.data('editKey', flagKey);
			$modal.addClass('is-visible');
		},

		openRuleModal: function (flagKey) {
			// Simple rule add via prompt for now
			const ruleType = prompt('Tipo de regla (platform, user_role, app_version):');
			if (!ruleType) {return;}

			const ruleValue = prompt('Valor (ej: ios, android, subscriber, admin, >=2.0.0):');
			if (!ruleValue) {return;}

			this.addRule(flagKey, { type: ruleType, value: ruleValue, label: ruleValue });
		},

		addRule: function (flagKey, rule) {
			const self = this;
			const flag = this.flags.find(f => f.key === flagKey);

			if (!flag) {return;}

			if (!flag.rules) {flag.rules = [];}
			flag.rules.push(rule);

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_update_flag_rules',
					nonce: flavorFeatureFlags.nonce,
					key: flagKey,
					rules: JSON.stringify(flag.rules)
				},
				success: function (response) {
					if (response.success) {
						self.renderFlags();
						self.showToast('Regla añadida', 'success');
					}
				}
			});
		},

		removeRule: function (flagKey, ruleIndex) {
			const self = this;
			const flag = this.flags.find(f => f.key === flagKey);

			if (!flag || !flag.rules) {return;}

			flag.rules.splice(ruleIndex, 1);

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_update_flag_rules',
					nonce: flavorFeatureFlags.nonce,
					key: flagKey,
					rules: JSON.stringify(flag.rules)
				},
				success: function (response) {
					if (response.success) {
						self.renderFlags();
						self.showToast('Regla eliminada', 'success');
					}
				}
			});
		},

		closeModal: function () {
			this.$modal.removeClass('is-visible');
			this.$modal.removeData('editKey');
		},

		saveModalChanges: function () {
			const self = this;
			const flagKey = this.$modal.data('editKey');
			const flag = this.flags.find(f => f.key === flagKey);

			if (!flag) {return;}

			const updates = {
				description: this.$modal.find('[name="modal_description"]').val(),
				enabled: this.$modal.find('[name="modal_enabled"]').is(':checked'),
				rollout_percentage: parseInt(this.$modal.find('[name="modal_rollout"]').val())
			};

			$.ajax({
				url: flavorFeatureFlags.ajaxUrl,
				type: 'POST',
				data: {
					action: 'flavor_save_feature_flag',
					nonce: flavorFeatureFlags.nonce,
					flag: Object.assign({ key: flagKey }, updates)
				},
				success: function (response) {
					if (response.success) {
						Object.assign(flag, updates);
						self.renderFlags();
						self.updateStats();
						self.closeModal();
						self.showToast('Flag actualizado', 'success');
					}
				}
			});
		},

		showToast: function (message, type) {
			const $toast = $('<div class="flag-toast"></div>')
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
		},

		escapeHtml: function (text) {
			const div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	// Initialize when ready
	$(document).ready(function () {
		if ($('.flavor-feature-flags').length) {
			FeatureFlags.init();
		}
	});

})(jQuery);
