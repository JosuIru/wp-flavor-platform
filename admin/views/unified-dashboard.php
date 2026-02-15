<?php
/**
 * Vista: Dashboard Unificado v4.1.0
 *
 * Implementa el sistema de diseño unificado con:
 * - Breadcrumbs de navegacion
 * - Filtros por categoria
 * - Widgets agrupados por categoria
 * - Niveles visuales de widgets (featured, standard, compact)
 * - Drag & drop entre categorias
 * - Accesibilidad WCAG 2.1 AA
 *
 * Variables disponibles desde el controlador:
 * - $widgets        Widgets visibles ordenados
 * - $all_widgets    Todos los widgets (para personalizacion)
 * - $renderer       Instancia del renderizador
 * - $total_widgets  Total de widgets registrados
 * - $visible_count  Widgets visibles
 * - $last_refresh   Ultima actualizacion
 * - $user_prefs     Preferencias del usuario
 * - $categories     Categorias disponibles (si existe)
 *
 * @package FlavorChatIA
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener registry para categorias
$registry = class_exists('Flavor_Widget_Registry') ? Flavor_Widget_Registry::get_instance() : null;
$categories = $registry ? $registry->get_categories() : [];

// Obtener widgets agrupados por categoria
$widgets_by_category = [];
if ($registry && !empty($widgets)) {
    foreach ($widgets as $widget) {
        $config = $widget->get_widget_config();
        $category = $config['category'] ?? 'sistema';
        if (!isset($widgets_by_category[$category])) {
            $widgets_by_category[$category] = [];
        }
        $widgets_by_category[$category][] = $widget;
    }
}

// Categorias colapsadas del usuario
$collapsed_categories = $user_prefs['collapsedCategories'] ?? [];

// Filtro activo
$active_filter = $user_prefs['activeFilter'] ?? 'all';

// Vista actual
$view_mode = $user_prefs['viewMode'] ?? 'grid';
?>
<div class="wrap fl-dashboard-wrapper fud-wrapper"
     data-view-mode="<?php echo esc_attr($view_mode); ?>"
     data-active-filter="<?php echo esc_attr($active_filter); ?>">

    <!-- Skip Links (Accesibilidad) -->
    <a href="#fl-main-content" class="fl-skip-link"><?php esc_html_e('Saltar al contenido principal', 'flavor-chat-ia'); ?></a>
    <a href="#fl-category-filters" class="fl-skip-link"><?php esc_html_e('Saltar a filtros', 'flavor-chat-ia'); ?></a>

    <!-- Instrucciones Drag & Drop (Screen Readers) -->
    <div id="fl-drag-instructions" class="fl-sr-only">
        <?php esc_html_e('Para reordenar widgets: presione Enter o Espacio para activar el modo arrastre, use las flechas para mover, Enter para soltar o Escape para cancelar.', 'flavor-chat-ia'); ?>
    </div>

    <!-- =========================================================
         BREADCRUMBS
    ========================================================= -->
    <nav class="fl-breadcrumbs" aria-label="<?php esc_attr_e('Navegacion', 'flavor-chat-ia'); ?>">
        <ol class="fl-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li class="fl-breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?php echo esc_url(admin_url()); ?>" class="fl-breadcrumbs__link" itemprop="item">
                    <span class="fl-breadcrumbs__icon dashicons dashicons-admin-home" aria-hidden="true"></span>
                    <span itemprop="name"><?php esc_html_e('Inicio', 'flavor-chat-ia'); ?></span>
                </a>
                <meta itemprop="position" content="1">
            </li>
            <li class="fl-breadcrumbs__separator" aria-hidden="true">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </li>
            <li class="fl-breadcrumbs__item fl-breadcrumbs__item--current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span class="fl-breadcrumbs__current" itemprop="item">
                    <span class="fl-breadcrumbs__icon dashicons dashicons-dashboard" aria-hidden="true"></span>
                    <span itemprop="name"><?php esc_html_e('Dashboard', 'flavor-chat-ia'); ?></span>
                </span>
                <meta itemprop="position" content="2">
            </li>
        </ol>
    </nav>

    <!-- =========================================================
         HEADER
    ========================================================= -->
    <header class="fl-dashboard-header fud-header">
        <div class="fl-dashboard-header__left fud-header-left">
            <h1 class="fl-dashboard-header__title fud-title">
                <span class="dashicons dashicons-dashboard" aria-hidden="true"></span>
                <?php esc_html_e('Dashboard', 'flavor-chat-ia'); ?>
            </h1>
            <span class="fl-dashboard-header__subtitle fud-subtitle">
                <?php printf(
                    esc_html(_n('%d widget activo', '%d widgets activos', $visible_count, 'flavor-chat-ia')),
                    $visible_count
                ); ?>
            </span>
        </div>

        <div class="fl-dashboard-header__actions fud-header-actions">
            <!-- Toggle Vista -->
            <div class="fl-view-toggle fud-view-toggle" role="group" aria-label="<?php esc_attr_e('Modo de vista', 'flavor-chat-ia'); ?>">
                <button type="button"
                        class="fl-view-btn fud-view-btn <?php echo $view_mode === 'grid' ? 'active' : ''; ?>"
                        data-view="grid"
                        aria-pressed="<?php echo $view_mode === 'grid' ? 'true' : 'false'; ?>"
                        title="<?php esc_attr_e('Vista cuadricula', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-grid-view" aria-hidden="true"></span>
                    <span class="fl-sr-only"><?php esc_html_e('Cuadricula', 'flavor-chat-ia'); ?></span>
                </button>
                <button type="button"
                        class="fl-view-btn fud-view-btn <?php echo $view_mode === 'list' ? 'active' : ''; ?>"
                        data-view="list"
                        aria-pressed="<?php echo $view_mode === 'list' ? 'true' : 'false'; ?>"
                        title="<?php esc_attr_e('Vista lista', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-list-view" aria-hidden="true"></span>
                    <span class="fl-sr-only"><?php esc_html_e('Lista', 'flavor-chat-ia'); ?></span>
                </button>
            </div>

            <!-- Boton Personalizar -->
            <button type="button" class="button fl-btn fl-btn--secondary fud-btn-secondary" id="fud-customize-btn">
                <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                <?php esc_html_e('Personalizar', 'flavor-chat-ia'); ?>
            </button>

            <!-- Boton Actualizar -->
            <button type="button" class="button button-primary fl-btn fl-btn--primary fud-btn-primary" id="fud-refresh-all-btn">
                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                <?php esc_html_e('Actualizar todo', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </header>

    <!-- =========================================================
         FILTROS DE CATEGORIA
    ========================================================= -->
    <?php if (!empty($categories)): ?>
    <nav class="fl-category-filters" id="fl-category-filters" role="navigation" aria-label="<?php esc_attr_e('Filtrar por categoria', 'flavor-chat-ia'); ?>">
        <button type="button"
                class="fl-category-filter fl-category-filter--all <?php echo $active_filter === 'all' ? 'active' : ''; ?>"
                data-category="all"
                aria-pressed="<?php echo $active_filter === 'all' ? 'true' : 'false'; ?>">
            <span class="dashicons dashicons-menu" aria-hidden="true"></span>
            <span class="fl-category-filter__label"><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></span>
            <span class="fl-category-filter__badge" aria-label="<?php printf(esc_attr__('%d widgets', 'flavor-chat-ia'), $visible_count); ?>">
                <?php echo absint($visible_count); ?>
            </span>
        </button>

        <?php foreach ($categories as $cat_id => $cat_info):
            $cat_widgets_count = isset($widgets_by_category[$cat_id]) ? count($widgets_by_category[$cat_id]) : 0;
            if ($cat_widgets_count === 0) continue;

            $cat_icon = $cat_info['icon'] ?? 'dashicons-admin-generic';
            $cat_label = $cat_info['label'] ?? ucfirst($cat_id);
            $cat_color = $cat_info['color'] ?? '#4f46e5';
        ?>
            <button type="button"
                    class="fl-category-filter fl-category-filter--<?php echo esc_attr($cat_id); ?> <?php echo $active_filter === $cat_id ? 'active' : ''; ?>"
                    data-category="<?php echo esc_attr($cat_id); ?>"
                    aria-pressed="<?php echo $active_filter === $cat_id ? 'true' : 'false'; ?>"
                    style="--fl-cat-color: <?php echo esc_attr($cat_color); ?>;">
                <span class="dashicons <?php echo esc_attr($cat_icon); ?>" aria-hidden="true"></span>
                <span class="fl-category-filter__label"><?php echo esc_html($cat_label); ?></span>
                <span class="fl-category-filter__badge" aria-label="<?php printf(esc_attr__('%d widgets', 'flavor-chat-ia'), $cat_widgets_count); ?>">
                    <?php echo absint($cat_widgets_count); ?>
                </span>
            </button>
        <?php endforeach; ?>
    </nav>

    <!-- Dropdown para mobile -->
    <div class="fl-category-dropdown" style="display: none;">
        <select id="fl-category-select" class="fl-category-select" aria-label="<?php esc_attr_e('Filtrar por categoria', 'flavor-chat-ia'); ?>">
            <option value="all" <?php selected($active_filter, 'all'); ?>><?php esc_html_e('Todas las categorias', 'flavor-chat-ia'); ?></option>
            <?php foreach ($categories as $cat_id => $cat_info):
                $cat_widgets_count = isset($widgets_by_category[$cat_id]) ? count($widgets_by_category[$cat_id]) : 0;
                if ($cat_widgets_count === 0) continue;
            ?>
                <option value="<?php echo esc_attr($cat_id); ?>" <?php selected($active_filter, $cat_id); ?>>
                    <?php echo esc_html($cat_info['label'] ?? ucfirst($cat_id)); ?> (<?php echo $cat_widgets_count; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <!-- =========================================================
         CONTENIDO PRINCIPAL
    ========================================================= -->
    <main class="fl-dashboard-main fud-widgets-container" id="fl-main-content" role="main">
        <?php if (empty($widgets)): ?>
            <!-- Estado Vacio -->
            <div class="fl-empty-state fud-empty-state">
                <span class="fl-empty-state__icon dashicons dashicons-layout" aria-hidden="true"></span>
                <h2 class="fl-empty-state__title"><?php esc_html_e('No hay widgets visibles', 'flavor-chat-ia'); ?></h2>
                <p class="fl-empty-state__message"><?php esc_html_e('Usa el boton "Personalizar" para elegir que widgets mostrar.', 'flavor-chat-ia'); ?></p>
                <button type="button" class="button button-primary fl-empty-state__action" id="fl-empty-customize">
                    <span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
                    <?php esc_html_e('Personalizar Dashboard', 'flavor-chat-ia'); ?>
                </button>
            </div>

        <?php elseif (!empty($widgets_by_category)): ?>
            <!-- Widgets Agrupados por Categoria -->
            <div class="fl-widgets-grouped fud-widgets-grouped">
                <?php foreach ($widgets_by_category as $cat_id => $cat_widgets):
                    if (empty($cat_widgets)) continue;

                    $cat_info = $categories[$cat_id] ?? [
                        'label' => ucfirst($cat_id),
                        'icon'  => 'dashicons-admin-generic',
                        'color' => '#4f46e5',
                    ];

                    $is_collapsed = in_array($cat_id, $collapsed_categories);
                    $group_id = 'fl-group-' . esc_attr($cat_id);
                    $content_id = 'fl-group-content-' . esc_attr($cat_id);
                ?>
                <section class="fl-widget-group fud-widget-group <?php echo $is_collapsed ? 'fl-widget-group--collapsed' : ''; ?>"
                         data-category="<?php echo esc_attr($cat_id); ?>"
                         id="<?php echo $group_id; ?>"
                         aria-labelledby="<?php echo $group_id; ?>-title"
                         style="--fl-group-color: <?php echo esc_attr($cat_info['color'] ?? '#4f46e5'); ?>;">

                    <!-- Header del Grupo -->
                    <header class="fl-widget-group__header">
                        <button type="button"
                                class="fl-widget-group__toggle"
                                aria-expanded="<?php echo $is_collapsed ? 'false' : 'true'; ?>"
                                aria-controls="<?php echo $content_id; ?>">
                            <span class="fl-widget-group__icon dashicons <?php echo esc_attr($cat_info['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                            <h2 class="fl-widget-group__title" id="<?php echo $group_id; ?>-title">
                                <?php echo esc_html($cat_info['label'] ?? ucfirst($cat_id)); ?>
                            </h2>
                            <span class="fl-widget-group__count" aria-label="<?php printf(esc_attr__('%d widgets en esta categoria', 'flavor-chat-ia'), count($cat_widgets)); ?>">
                                <?php echo count($cat_widgets); ?>
                            </span>
                            <span class="fl-widget-group__arrow dashicons <?php echo $is_collapsed ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>" aria-hidden="true"></span>
                        </button>

                        <div class="fl-widget-group__actions">
                            <button type="button"
                                    class="fl-widget-group__action fl-widget-group__action--refresh"
                                    aria-label="<?php printf(esc_attr__('Actualizar widgets de %s', 'flavor-chat-ia'), $cat_info['label'] ?? $cat_id); ?>"
                                    data-category="<?php echo esc_attr($cat_id); ?>">
                                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                            </button>
                        </div>
                    </header>

                    <!-- Contenido del Grupo -->
                    <div class="fl-widget-group__content fl-widgets-grid fud-widgets-grid"
                         id="<?php echo $content_id; ?>"
                         <?php echo $is_collapsed ? 'hidden' : ''; ?>>
                        <?php foreach ($cat_widgets as $widget): ?>
                            <?php echo $renderer->render($widget); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <!-- Grid Simple (fallback) -->
            <div class="fl-widgets-grid fud-widgets-grid" id="fud-widgets-grid">
                <?php foreach ($widgets as $widget): ?>
                    <?php echo $renderer->render($widget); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- =========================================================
         FOOTER
    ========================================================= -->
    <footer class="fl-dashboard-footer fud-footer">
        <span class="fl-last-update fud-last-update">
            <span class="dashicons dashicons-clock" aria-hidden="true"></span>
            <?php esc_html_e('Ultima actualizacion:', 'flavor-chat-ia'); ?>
            <time datetime="<?php echo esc_attr($last_refresh); ?>" id="fud-last-refresh">
                <?php echo esc_html(date_i18n(get_option('time_format'), current_time('timestamp'))); ?>
            </time>
        </span>
    </footer>

    <!-- =========================================================
         MODAL DE PERSONALIZACION
    ========================================================= -->
    <div class="fl-modal fud-modal" id="fud-customize-modal" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="fud-modal-title">
        <div class="fl-modal__overlay fud-modal-overlay"></div>
        <div class="fl-modal__content fud-modal-content">
            <header class="fl-modal__header fud-modal-header">
                <h2 id="fud-modal-title"><?php esc_html_e('Personalizar Dashboard', 'flavor-chat-ia'); ?></h2>
                <button type="button" class="fl-modal__close fud-modal-close" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-no-alt" aria-hidden="true"></span>
                </button>
            </header>

            <div class="fl-modal__body fud-modal-body">
                <p class="fl-modal__help fud-modal-help">
                    <?php esc_html_e('Arrastra para reordenar. Desmarca para ocultar widgets.', 'flavor-chat-ia'); ?>
                </p>

                <ul class="fl-widgets-list fud-widgets-list" id="fud-widgets-sortable" role="listbox" aria-label="<?php esc_attr_e('Lista de widgets', 'flavor-chat-ia'); ?>">
                    <?php foreach ($all_widgets as $widget):
                        $config = $widget->get_widget_config();
                        $widget_id = $widget->get_widget_id();
                        $is_visible = in_array($widget_id, $user_prefs['widgetVisibility'] ?? array_map(fn($w) => $w->get_widget_id(), $all_widgets));
                        $widget_category = $config['category'] ?? 'sistema';
                        $cat_color = isset($categories[$widget_category]['color']) ? $categories[$widget_category]['color'] : '#4f46e5';
                    ?>
                        <li class="fl-widget-item fud-widget-item"
                            data-widget-id="<?php echo esc_attr($widget_id); ?>"
                            data-category="<?php echo esc_attr($widget_category); ?>"
                            role="option"
                            aria-selected="<?php echo $is_visible ? 'true' : 'false'; ?>">
                            <span class="fl-drag-handle fud-drag-handle" aria-hidden="true">
                                <span class="dashicons dashicons-menu"></span>
                            </span>
                            <label class="fl-widget-toggle fud-widget-toggle">
                                <input type="checkbox"
                                       name="widget_visibility[]"
                                       value="<?php echo esc_attr($widget_id); ?>"
                                       <?php checked($is_visible); ?>
                                       aria-describedby="widget-desc-<?php echo esc_attr($widget_id); ?>">
                                <span class="fl-widget-icon fud-widget-icon" style="background-color: <?php echo esc_attr($cat_color); ?>;">
                                    <span class="dashicons <?php echo esc_attr($config['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                                </span>
                                <span class="fl-widget-info">
                                    <span class="fl-widget-name fud-widget-name"><?php echo esc_html($config['title'] ?? $widget_id); ?></span>
                                    <span class="fl-widget-category" id="widget-desc-<?php echo esc_attr($widget_id); ?>">
                                        <?php echo esc_html($categories[$widget_category]['label'] ?? ucfirst($widget_category)); ?>
                                    </span>
                                </span>
                            </label>
                            <span class="fl-widget-size fud-widget-size"><?php echo esc_html($config['size'] ?? 'medium'); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <footer class="fl-modal__footer fud-modal-footer">
                <button type="button" class="button fl-btn" id="fud-reset-layout">
                    <span class="dashicons dashicons-undo" aria-hidden="true"></span>
                    <?php esc_html_e('Restablecer', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="button button-primary fl-btn fl-btn--primary" id="fud-save-layout">
                    <span class="dashicons dashicons-saved" aria-hidden="true"></span>
                    <?php esc_html_e('Guardar cambios', 'flavor-chat-ia'); ?>
                </button>
            </footer>
        </div>
    </div>

    <!-- Live Region para anuncios de accesibilidad -->
    <div id="fl-live-announcer" class="fl-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>
</div>

<script>
/**
 * Dashboard Unificado v4.1.0 - JavaScript
 */
jQuery(document).ready(function($) {
    'use strict';

    const FlavorDashboard = {
        // Configuracion
        config: {
            ajaxUrl: typeof ajaxurl !== 'undefined' ? ajaxurl : '/wp-admin/admin-ajax.php',
            nonce: typeof fudData !== 'undefined' ? fudData.nonce : '',
        },

        // Estado
        state: {
            viewMode: '<?php echo esc_js($view_mode); ?>',
            activeFilter: '<?php echo esc_js($active_filter); ?>',
            collapsedCategories: <?php echo wp_json_encode($collapsed_categories); ?>,
        },

        /**
         * Inicializa el dashboard
         */
        init: function() {
            this.bindEvents();
            this.initSortable();
            this.initCategoryFilters();
            this.initGroupCollapse();
        },

        /**
         * Bindea eventos
         */
        bindEvents: function() {
            const self = this;

            // Modal personalizar
            $('#fud-customize-btn, #fl-empty-customize').on('click', function() {
                $('#fud-customize-modal').fadeIn(200).attr('aria-hidden', 'false');
                $('#fud-customize-modal').find('.fud-modal-close').focus();
            });

            $('.fud-modal-close, .fud-modal-overlay, .fl-modal__close, .fl-modal__overlay').on('click', function() {
                $('#fud-customize-modal').fadeOut(200).attr('aria-hidden', 'true');
            });

            // Cerrar modal con Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#fud-customize-modal').is(':visible')) {
                    $('#fud-customize-modal').fadeOut(200).attr('aria-hidden', 'true');
                }
            });

            // Toggle vista
            $('.fl-view-btn, .fud-view-btn').on('click', function() {
                const view = $(this).data('view');
                self.setViewMode(view);
            });

            // Refresh all
            $('#fud-refresh-all-btn').on('click', function() {
                self.refreshAll($(this));
            });

            // Refresh categoria
            $('.fl-widget-group__action--refresh').on('click', function() {
                const category = $(this).data('category');
                self.refreshCategory(category, $(this));
            });

            // Guardar layout
            $('#fud-save-layout').on('click', function() {
                self.saveLayout();
            });

            // Reset layout
            $('#fud-reset-layout').on('click', function() {
                if (confirm(<?php echo wp_json_encode(__('¿Restablecer configuracion por defecto?', 'flavor-chat-ia')); ?>)) {
                    self.resetLayout();
                }
            });

            // Dropdown categoria (mobile)
            $('#fl-category-select').on('change', function() {
                self.filterByCategory($(this).val());
            });
        },

        /**
         * Inicializa filtros de categoria
         */
        initCategoryFilters: function() {
            const self = this;

            $('.fl-category-filter').on('click', function() {
                const category = $(this).data('category');
                self.filterByCategory(category);
            });
        },

        /**
         * Filtra widgets por categoria
         */
        filterByCategory: function(category) {
            this.state.activeFilter = category;

            // Actualizar botones
            $('.fl-category-filter').removeClass('active').attr('aria-pressed', 'false');
            $('.fl-category-filter[data-category="' + category + '"]').addClass('active').attr('aria-pressed', 'true');

            // Actualizar dropdown mobile
            $('#fl-category-select').val(category);

            // Mostrar/ocultar grupos
            if (category === 'all') {
                $('.fl-widget-group').show();
            } else {
                $('.fl-widget-group').hide();
                $('.fl-widget-group[data-category="' + category + '"]').show();
            }

            // Actualizar atributo
            $('.fl-dashboard-wrapper').attr('data-active-filter', category);

            // Anunciar cambio
            this.announce(<?php echo wp_json_encode(__('Mostrando widgets de categoria: ', 'flavor-chat-ia')); ?> + category);

            // Guardar preferencia
            this.savePreference('activeFilter', category);
        },

        /**
         * Inicializa colapso de grupos
         */
        initGroupCollapse: function() {
            const self = this;

            $('.fl-widget-group__toggle').on('click', function() {
                const $group = $(this).closest('.fl-widget-group');
                const category = $group.data('category');
                const $content = $group.find('.fl-widget-group__content');
                const $arrow = $(this).find('.fl-widget-group__arrow');
                const isCollapsed = $group.hasClass('fl-widget-group--collapsed');

                if (isCollapsed) {
                    // Expandir
                    $group.removeClass('fl-widget-group--collapsed');
                    $content.removeAttr('hidden').slideDown(200);
                    $(this).attr('aria-expanded', 'true');
                    $arrow.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');

                    // Quitar de colapsados
                    self.state.collapsedCategories = self.state.collapsedCategories.filter(c => c !== category);
                } else {
                    // Colapsar
                    $group.addClass('fl-widget-group--collapsed');
                    $content.slideUp(200, function() {
                        $(this).attr('hidden', '');
                    });
                    $(this).attr('aria-expanded', 'false');
                    $arrow.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');

                    // Agregar a colapsados
                    if (!self.state.collapsedCategories.includes(category)) {
                        self.state.collapsedCategories.push(category);
                    }
                }

                // Guardar preferencia
                self.savePreference('collapsedCategories', self.state.collapsedCategories);
            });
        },

        /**
         * Cambia modo de vista
         */
        setViewMode: function(mode) {
            this.state.viewMode = mode;

            // Actualizar botones
            $('.fl-view-btn, .fud-view-btn').removeClass('active').attr('aria-pressed', 'false');
            $('.fl-view-btn[data-view="' + mode + '"], .fud-view-btn[data-view="' + mode + '"]').addClass('active').attr('aria-pressed', 'true');

            // Actualizar wrapper
            $('.fl-dashboard-wrapper, .fud-wrapper').attr('data-view-mode', mode);

            // Guardar preferencia
            this.savePreference('viewMode', mode);
        },

        /**
         * Inicializa sortable
         */
        initSortable: function() {
            if ($.fn.sortable) {
                $('#fud-widgets-sortable').sortable({
                    handle: '.fl-drag-handle, .fud-drag-handle',
                    placeholder: 'fl-widget-placeholder fud-widget-placeholder',
                    tolerance: 'pointer',
                    update: function() {
                        $('#fud-save-layout').addClass('button-primary fl-btn--changed');
                    }
                });
            }
        },

        /**
         * Actualiza todo
         */
        refreshAll: function($btn) {
            $btn.prop('disabled', true).find('.dashicons').addClass('fl-spinning');
            this.announce(<?php echo wp_json_encode(__('Actualizando todos los widgets...', 'flavor-chat-ia')); ?>);

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_refresh_all',
                    nonce: this.config.nonce
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('fl-spinning');
                }
            });
        },

        /**
         * Actualiza una categoria
         */
        refreshCategory: function(category, $btn) {
            $btn.prop('disabled', true).find('.dashicons').addClass('fl-spinning');

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_refresh_category',
                    nonce: this.config.nonce,
                    category: category
                },
                success: function(response) {
                    if (response.success && response.data && response.data.html) {
                        $('#fl-group-content-' + category).html(response.data.html);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).find('.dashicons').removeClass('fl-spinning');
                }
            });
        },

        /**
         * Guarda layout
         */
        saveLayout: function() {
            const order = [];
            const visibility = [];

            $('#fud-widgets-sortable .fl-widget-item, #fud-widgets-sortable .fud-widget-item').each(function() {
                const id = $(this).data('widget-id');
                order.push(id);
                if ($(this).find('input').is(':checked')) {
                    visibility.push(id);
                }
            });

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_save_layout',
                    nonce: this.config.nonce,
                    order: order,
                    visibility: visibility
                },
                success: function(response) {
                    if (response.success) {
                        $('#fud-customize-modal').fadeOut(200);
                        location.reload();
                    }
                }
            });
        },

        /**
         * Resetea layout
         */
        resetLayout: function() {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_reset_layout',
                    nonce: this.config.nonce
                },
                success: function() {
                    location.reload();
                }
            });
        },

        /**
         * Guarda una preferencia
         */
        savePreference: function(key, value) {
            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fud_save_preference',
                    nonce: this.config.nonce,
                    key: key,
                    value: typeof value === 'object' ? JSON.stringify(value) : value
                }
            });
        },

        /**
         * Anuncia mensaje para screen readers
         */
        announce: function(message) {
            const $announcer = $('#fl-live-announcer');
            $announcer.text('');
            setTimeout(function() {
                $announcer.text(message);
            }, 100);
        }
    };

    // Inicializar
    FlavorDashboard.init();

    // Animacion de spinning
    $('<style>.fl-spinning { animation: fl-spin 1s linear infinite !important; } @keyframes fl-spin { to { transform: rotate(360deg); } }</style>').appendTo('head');
});
</script>
