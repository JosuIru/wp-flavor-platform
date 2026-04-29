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
		playSplash();
		loadRealTabsConfig();
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

		// Flavor (client/admin) change → re-render
		$('#flavor').on('change', updatePreview);

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
     * Tabs nativas del cliente Flutter.
     * Empieza con los defaults de lib/main_client_home.dart pero se sobreescribe
     * dinámicamente con la config real del sitio (flavor_apps_config['tabs']) tras
     * el primer fetch a flavor_apk_tabs_config.
     */
	let NATIVE_TABS = [
		{ id: 'chat', label: 'Chat', icon: 'chat_bubble' },
		{ id: 'reservations', label: 'Reservar', icon: 'calendar_today' },
		{ id: 'my_tickets', label: 'Tickets', icon: 'confirmation_number' },
		{ id: 'info', label: 'Info', icon: 'info' },
	];
	let DEFAULT_TAB_ID = 'info';

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
     * Cambia la vista visible dentro de la pantalla del teléfono.
     * La vista 'tab' es la única "raíz" — la 'module' es modal sobre las tabs.
     */
	function setActiveView(viewName) {
		$('#preview-screen .screen-view').hide();
		$(`#preview-screen .screen-view[data-view="${viewName}"]`).css('display', 'flex');
	}

	/**
     * Devuelve el flavor activo del builder ('client' | 'admin').
     */
	function getFlavor() {
		return $('#flavor').val() || 'client';
	}

	/**
     * Cambia la tab nativa activa y renderiza su contenido.
     * En modo admin las "tabs" se reemplazan por un dashboard único.
     */
	function setActiveTab(tabId) {
		const meta = (window.flavorApkBuilder && flavorApkBuilder.modulesMeta) || {};
		const flavor = getFlavor();

		// Título del AppBar (siempre app name)
		const appName = $('#app_name').val() || 'Mi App';
		$('#preview-tab-title').text(appName);

		const $body = $('#preview-tab-body').empty();

		if (flavor === 'admin') {
			// Modo admin: dashboard único, sin tabs nativas funcionales
			$body.append(buildAdminDashboard(meta));
			// Mantener bottom nav visible pero solo "Inicio" activo
			$('#preview-navbar .nav-item').removeClass('active');
			$('#preview-navbar .nav-item[data-tab="info"]').addClass('active');
			$('#preview-screen').data('current-tab', 'admin_dashboard');
		} else {
			const tab = NATIVE_TABS.find((t) => t.id === tabId) || NATIVE_TABS[3];
			$('#preview-navbar .nav-item').removeClass('active');
			$(`#preview-navbar .nav-item[data-tab="${tab.id}"]`).addClass('active');
			$body.append(buildTabContent(tab.id, meta));
			$('#preview-screen').data('current-tab', tab.id);
		}

		closeDrawer();
		setActiveView('tab');
	}

	/**
     * Dashboard admin (KPIs + acciones rápidas).
     * Inspirado en main_admin.dart de la APK admin.
     */
	function buildAdminDashboard(meta) {
		const selectedIds = getSelectedModuleIds();
		const $wrap = $('<div class="admin-dashboard"></div>');

		const kpis = [
			{ value: '247', label: 'Socios activos', icon: 'groups', color: '#3F51B5', delta: '+12', up: true },
			{ value: '34', label: 'Eventos próximos', icon: 'event', color: '#9C27B0', delta: '+3', up: true },
			{ value: '128', label: 'Posts del mes', icon: 'article', color: '#FF9800', delta: '+24', up: true },
			{ value: '5', label: 'Incidencias abiertas', icon: 'report_problem', color: '#F44336', delta: '-2', up: false },
		];

		const $grid = $('<div class="admin-kpi-grid"></div>');
		kpis.forEach(function (k) {
			$grid.append(`
				<div class="admin-kpi-card">
					<div class="admin-kpi-card__icon" style="background:${k.color};">
						<span class="material-icons-outlined">${k.icon}</span>
					</div>
					<div class="admin-kpi-card__value">${k.value}</div>
					<div class="admin-kpi-card__label">${k.label}</div>
					<div class="admin-kpi-card__delta admin-kpi-card__delta--${k.up ? 'up' : 'down'}">${k.up ? '↗' : '↘'} ${k.delta}</div>
				</div>
			`);
		});
		$wrap.append($grid);

		// Acciones rápidas: una por cada módulo seleccionado (top 4)
		const $actions = $(`
			<div class="admin-quick-actions">
				<p class="admin-quick-actions__title">Acciones rápidas</p>
				<div class="admin-quick-actions__list"></div>
			</div>
		`);
		const $list = $actions.find('.admin-quick-actions__list');
		const fallback = [
			{ icon: 'add_circle_outline', label: 'Crear nueva publicación' },
			{ icon: 'event_available', label: 'Programar evento' },
			{ icon: 'notifications_active', label: 'Enviar aviso a socios' },
			{ icon: 'assessment', label: 'Ver estadísticas' },
		];

		const top = selectedIds.slice(0, 4);
		if (top.length) {
			top.forEach(function (id) {
				const m = meta[id];
				if (!m) return;
				$list.append(`
					<div class="admin-quick-action">
						<span class="material-icons-outlined" style="color:${m.color};">${m.material_icon}</span>
						<span>Gestionar ${m.name.toLowerCase()}</span>
					</div>
				`);
			});
		} else {
			fallback.forEach(function (a) {
				$list.append(`
					<div class="admin-quick-action">
						<span class="material-icons-outlined">${a.icon}</span>
						<span>${a.label}</span>
					</div>
				`);
			});
		}
		$wrap.append($actions);

		return $wrap;
	}

	/**
     * Reproduce splash screen sobre el preview.
     */
	function playSplash() {
		$('#preview-splash-name').text($('#app_name').val() || 'Mi App');
		const $splash = $('#preview-screen .screen-view[data-view="splash"]');
		$splash.css('display', 'flex');
		setTimeout(function () {
			$splash.fadeOut(220);
		}, 1400);
	}

	/**
     * Toggle light/dark del preview.
     */
	function toggleDarkMode() {
		const $frame = $('#preview-frame');
		const $btn = $('#preview-theme-toggle');
		const isDark = $frame.toggleClass('is-dark').hasClass('is-dark');
		$btn.toggleClass('is-active', isDark);
		$btn.find('.material-icons-outlined').text(isDark ? 'light_mode' : 'dark_mode');
	}

	/**
     * Refresca el badge del flavor activo.
     */
	function refreshFlavorBadge() {
		const flavor = getFlavor();
		const $badge = $('#preview-flavor-badge');
		const $label = $('#preview-flavor-label');
		const $hint = $('#preview-hint');
		$badge.removeClass('preview-flavor-badge--client preview-flavor-badge--admin');
		if (flavor === 'admin') {
			$badge.addClass('preview-flavor-badge--admin');
			$label.text('Modo Administrador');
			$badge.find('.material-icons-outlined').text('admin_panel_settings');
			$hint.text('Simula la app admin Flutter (dashboard con KPIs y acciones por módulo).');
		} else {
			$badge.addClass('preview-flavor-badge--client');
			$label.text('Modo Cliente');
			$badge.find('.material-icons-outlined').text('person');
			$hint.text('Simula la app cliente Flutter en modo hybrid (4 tabs nativas + drawer con módulos). Pulsa el menú o cualquier módulo del drawer.');
		}
	}

	/**
     * Devuelve un Widget HTML según la tab nativa.
     */
	function buildTabContent(tabId, meta) {
		const $wrap = $('<div class="tab-content"></div>');

		if (tabId === 'chat') {
			const sample = [
				{ name: 'Comisión huertos', last: '¿Quedamos el sábado?', badge: '3' },
				{ name: 'Banco de tiempo', last: 'Tengo 2h disponibles', badge: '' },
				{ name: 'Asamblea general', last: '12 mensajes nuevos', badge: '12' },
				{ name: 'Lucía R.', last: 'Te paso la receta luego', badge: '' },
			];
			sample.forEach(function (c) {
				const badge = c.badge
					? `<span class="tab-content__badge">${c.badge}</span>`
					: '';
				$wrap.append(`
					<div class="tab-content__card">
						<div class="tab-content__row">
							<div class="tab-content__avatar tab-content__avatar--primary">
								<span class="material-icons-outlined">forum</span>
							</div>
							<div class="tab-content__body">
								<p class="tab-content__title">${c.name}</p>
								<div class="tab-content__subtitle">${c.last}</div>
							</div>
							${badge}
						</div>
					</div>
				`);
			});
		} else if (tabId === 'reservations') {
			const sample = [
				{ title: 'Sala polivalente', sub: 'Hoy 18:00 – 20:00 · Confirmada', icon: 'event_available' },
				{ title: 'Pista de pádel', sub: 'Mañana 17:00 · Pendiente', icon: 'sports_tennis' },
				{ title: 'Cocina comunitaria', sub: 'Sáb 12:00 · Reserva activa', icon: 'restaurant' },
			];
			sample.forEach(function (r) {
				$wrap.append(`
					<div class="tab-content__card">
						<div class="tab-content__row">
							<div class="tab-content__avatar tab-content__avatar--primary">
								<span class="material-icons-outlined">${r.icon}</span>
							</div>
							<div class="tab-content__body">
								<p class="tab-content__title">${r.title}</p>
								<div class="tab-content__subtitle">${r.sub}</div>
							</div>
						</div>
					</div>
				`);
			});
		} else if (tabId === 'my_tickets') {
			const sample = [
				{ title: 'Concierto solidario', sub: 'Sáb 21:00 · QR válido', icon: 'qr_code_2' },
				{ title: 'Asamblea general', sub: 'Jue 19:00 · Confirmado', icon: 'how_to_vote' },
				{ title: 'Taller permacultura', sub: 'Próximo sábado · Inscrito', icon: 'school' },
			];
			sample.forEach(function (t) {
				$wrap.append(`
					<div class="tab-content__card">
						<div class="tab-content__row">
							<div class="tab-content__avatar tab-content__avatar--primary">
								<span class="material-icons-outlined">${t.icon}</span>
							</div>
							<div class="tab-content__body">
								<p class="tab-content__title">${t.title}</p>
								<div class="tab-content__subtitle">${t.sub}</div>
							</div>
						</div>
					</div>
				`);
			});
		} else {
			// info (default) o tab custom (modules, etc.) → fallback a hero + info
			const appName = $('#app_name').val() || 'Mi App';
			const customTab = NATIVE_TABS.find((t) => t.id === tabId);
			const heroIcon = customTab ? customTab.icon : 'apartment';
			const heroLabel = (customTab && tabId !== 'info')
				? customTab.label
				: 'Comunidad cooperativa local';
			$wrap.append(`
				<div class="tab-content__hero">
					<div class="tab-content__hero-icon">
						<span class="material-icons-outlined">${heroIcon}</span>
					</div>
					<div class="tab-content__hero-title">${appName}</div>
					<div class="tab-content__hero-subtitle">${heroLabel}</div>
				</div>
				<div class="tab-content__card">
					<div class="tab-content__row">
						<div class="tab-content__avatar"><span class="material-icons-outlined">place</span></div>
						<div class="tab-content__body">
							<p class="tab-content__title">Dirección</p>
							<div class="tab-content__subtitle">C/ Mayor 12 · Bilbao</div>
						</div>
					</div>
				</div>
				<div class="tab-content__card">
					<div class="tab-content__row">
						<div class="tab-content__avatar"><span class="material-icons-outlined">schedule</span></div>
						<div class="tab-content__body">
							<p class="tab-content__title">Horario de atención</p>
							<div class="tab-content__subtitle">Lun–Vie 10:00 – 14:00</div>
						</div>
					</div>
				</div>
				<div class="tab-content__card">
					<div class="tab-content__row">
						<div class="tab-content__avatar"><span class="material-icons-outlined">phone</span></div>
						<div class="tab-content__body">
							<p class="tab-content__title">Contacto</p>
							<div class="tab-content__subtitle">94 600 12 34 · info@cooperativa.org</div>
						</div>
					</div>
				</div>
			`);
		}

		return $wrap;
	}

	/**
     * Apertura/cierre del drawer (Material Drawer).
     */
	function openDrawer() {
		$('#preview-drawer').addClass('is-open');
		$('#preview-drawer-overlay').addClass('is-visible');
	}
	function closeDrawer() {
		$('#preview-drawer').removeClass('is-open');
		$('#preview-drawer-overlay').removeClass('is-visible');
	}

	/**
     * Re-renderiza el bottom nav con las tabs actuales (NATIVE_TABS).
     */
	function rebuildBottomNav() {
		const $nav = $('#preview-navbar').empty();
		NATIVE_TABS.forEach(function (tab) {
			$nav.append(`
				<div class="nav-item" data-tab="${tab.id}">
					<span class="material-icons-outlined">${tab.icon}</span>
					<span>${tab.label}</span>
				</div>
			`);
		});
	}

	/**
     * Carga las tabs reales del sitio (flavor_apps_config['tabs']) y, si difieren
     * de los defaults, sobreescribe NATIVE_TABS y re-pinta el bottom nav.
     */
	function loadRealTabsConfig() {
		$.post(flavorApkBuilder.ajaxUrl, {
			action: 'flavor_apk_tabs_config',
			nonce: flavorApkBuilder.nonce,
		}).done(function (response) {
			if (!response || !response.success || !response.data) return;
			const tabs = response.data.tabs || [];
			if (!tabs.length) return;

			NATIVE_TABS = tabs.map(function (t) {
				return { id: t.id, label: t.label, icon: t.icon };
			});

			// Si el default declarado existe entre las tabs, usarlo
			const declared = response.data.default_tab;
			if (declared && NATIVE_TABS.find((t) => t.id === declared)) {
				DEFAULT_TAB_ID = declared;
			} else {
				DEFAULT_TAB_ID = NATIVE_TABS[NATIVE_TABS.length - 1].id;
			}

			rebuildBottomNav();
			setActiveTab(DEFAULT_TAB_ID);

			if (response.data.source === 'config') {
				$('#preview-hint').append(
					' <span style="color:#0a7634;font-weight:500;">· Tabs cargadas de tu configuración</span>'
				);
			}
		});
	}

	/**
     * Renderiza items en la lista del módulo (helper compartido por mock y real).
     */
	function renderModuleItems(items, color, sourceLabel) {
		const $list = $('#preview-module-list').empty();
		items.forEach(function (item) {
			$list.append(`
				<div class="preview-module-list__item" style="border-left-color:${color};">
					<p class="preview-module-list__title">${item.title}</p>
					<div class="preview-module-list__subtitle">${item.subtitle}</div>
				</div>
			`);
		});
		if (sourceLabel) {
			$list.append(`<div class="preview-module-list__source">${sourceLabel}</div>`);
		}
		$list.append(`
			<div class="preview-module-list__fab" style="background:${color};" title="Crear">
				<span class="material-icons-outlined">add</span>
			</div>
		`);
	}

	/**
     * Renderiza la pantalla detalle de un módulo.
     * Intenta primero traer datos reales desde el sitio (vía AJAX) y si la
     * tabla está vacía o no existe, hace fallback a meta.mock_items.
     */
	function showModuleScreen(modId, meta) {
		const color = meta.color || '#3b82f6';

		$('#preview-module-header').css('background', color);
		$('#preview-module-title').text(meta.name || modId).data('current-module', modId);

		// Pintar mock inmediatamente para no dejar la pantalla en blanco
		renderModuleItems(meta.mock_items || [], color, 'Datos de ejemplo');
		setActiveView('module');

		// Pedir datos reales en paralelo
		$.post(flavorApkBuilder.ajaxUrl, {
			action: 'flavor_apk_module_preview',
			nonce: flavorApkBuilder.nonce,
			module: modId,
		}).done(function (response) {
			// Confirmar que el usuario sigue en este mismo módulo (no haya saltado)
			if ($('#preview-module-title').data('current-module') !== modId) {
				return;
			}
			if (response && response.success && response.data && response.data.source === 'real') {
				renderModuleItems(response.data.items, color, 'Datos reales del sitio');
			}
		});
	}

	/**
     * Construye un item del drawer.
     */
	function buildDrawerItem(opts) {
		// opts: { icon, label, color?, selected?, kind: 'tab'|'module', target }
		const colorStyle = opts.color
			? `style="--preview-primary:${opts.color};"`
			: '';
		const selectedCls = opts.selected ? 'app-drawer__item--selected' : '';
		const kindCls = `app-drawer__item--${opts.kind}`;

		return $(`
			<div class="app-drawer__item ${selectedCls} ${kindCls}" ${colorStyle}
			     data-target="${opts.target}" data-kind="${opts.kind}">
				<span class="app-drawer__item-icon">
					<span class="material-icons-outlined">${opts.icon}</span>
				</span>
				<span>${opts.label}</span>
			</div>
		`);
	}

	/**
     * Reconstruye el drawer con tabs nativas + módulos seleccionados.
     */
	function renderDrawer(selectedIds, meta, currentTabId) {
		const $list = $('#preview-drawer-list').empty();
		const $empty = $('#preview-drawer-empty');

		// Tabs nativas
		NATIVE_TABS.forEach(function (tab) {
			$list.append(buildDrawerItem({
				icon: tab.icon,
				label: tab.label,
				selected: tab.id === currentTabId,
				kind: 'tab',
				target: tab.id,
			}));
		});

		if (selectedIds.length) {
			$empty.hide();
			$list.append('<div class="app-drawer__divider"></div>');
			$list.append(`<div class="app-drawer__section-label">${(flavorApkBuilder.i18n.tabHome ? 'Módulos' : 'Modules')}</div>`);

			selectedIds.forEach(function (id) {
				const m = meta[id];
				if (!m) return;
				$list.append(buildDrawerItem({
					icon: m.material_icon,
					label: m.name,
					color: m.color,
					kind: 'module',
					target: id,
				}));
			});
		}
	}

	/**
     * Update preview — sincroniza header, colores, drawer y tab activa.
     */
	function updatePreview() {
		const meta = (window.flavorApkBuilder && flavorApkBuilder.modulesMeta) || {};

		// Color primario del AppBar
		const primaryColor = $('#color_primary').val() || '#3b82f6';
		document.documentElement.style.setProperty('--preview-primary', primaryColor);
		$('#preview-header').css('background', primaryColor);
		$('#preview-module-header').css('background', primaryColor);

		// Nombre del app en drawer y tab
		const appName = $('#app_name').val() || 'Mi App';
		$('#preview-drawer-name').text(appName);
		$('#preview-tab-title').text(appName);

		// Reconstruir drawer y badge de flavor
		const selectedIds = getSelectedModuleIds();
		const currentTabId = $('#preview-screen').data('current-tab') || DEFAULT_TAB_ID;
		renderDrawer(selectedIds, meta, currentTabId);
		refreshFlavorBadge();

		// Re-pintar la tab actual para reflejar cambio de flavor o nombre
		const $visibleTab = $('#preview-screen .screen-view[data-view="tab"]:visible');
		if ($visibleTab.length) {
			setActiveTab(currentTabId === 'admin_dashboard' ? DEFAULT_TAB_ID : currentTabId);
		}

		// Si está abierta la vista de un módulo desmarcado, volver a la tab.
		const $activeModuleView = $('#preview-screen .screen-view[data-view="module"]:visible');
		if ($activeModuleView.length) {
			const currentModId = $('#preview-module-title').data('current-module');
			if (currentModId && selectedIds.indexOf(currentModId) === -1) {
				setActiveTab(currentTabId);
			}
		}

		// Si la vista actual es 'tab' y aún no se ha pintado, pintar la default.
		const $tabView = $('#preview-screen .screen-view[data-view="tab"]:visible');
		if ($tabView.length && !$('#preview-tab-body').children().length) {
			setActiveTab(currentTabId);
		}
	}

	/**
     * Bind events del preview interactivo.
     */
	function bindPreviewInteractions() {
		// Pintar tab por defecto al iniciar
		setActiveTab(DEFAULT_TAB_ID);

		// Toolbar
		$('#preview-theme-toggle').on('click', toggleDarkMode);
		$('#preview-replay').on('click', playSplash);

		// Bottom navigation → cambia tab nativa
		$('#preview-navbar').on('click', '.nav-item', function () {
			const tab = $(this).data('tab');
			if (!tab) return;
			setActiveTab(tab);
		});

		// Hamburguesa → abrir drawer
		$('#preview-menu-toggle').on('click', openDrawer);
		$('#preview-drawer-overlay').on('click', closeDrawer);

		// Click en item del drawer
		$('#preview-drawer-list').on('click', '.app-drawer__item', function () {
			const kind = $(this).data('kind');
			const target = $(this).data('target');
			if (kind === 'tab') {
				setActiveTab(target);
			} else if (kind === 'module') {
				const meta = (window.flavorApkBuilder && flavorApkBuilder.modulesMeta) || {};
				const m = meta[target];
				if (m) {
					closeDrawer();
					showModuleScreen(target, m);
				}
			}
		});

		// Botón back en pantalla de módulo → vuelve a la tab activa
		$('#preview-back').on('click', function () {
			const currentTabId = $('#preview-screen').data('current-tab') || DEFAULT_TAB_ID;
			setActiveTab(currentTabId);
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
