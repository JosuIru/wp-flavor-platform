<?php
/**
 * Template del Dashboard de Cliente v4.1.0
 *
 * Sistema de Diseno Unificado con:
 * - Breadcrumbs de navegacion
 * - Design tokens CSS
 * - Accesibilidad WCAG 2.1 AA
 * - Responsive mejorado
 *
 * Variables disponibles:
 *   $usuario            - WP_User objeto del usuario actual
 *   $avatar_url         - URL del avatar del usuario
 *   $nombre_usuario     - Nombre para mostrar del usuario
 *   $saludo             - Saludo personalizado segun hora
 *   $estadisticas       - Array de estadisticas con valores
 *   $atajos             - Array de atajos rapidos
 *   $widgets            - Array de widgets registrados
 *   $actividad_reciente - Array de actividad reciente
 *   $notificaciones     - Array de notificaciones pendientes
 *   $preferencias       - Preferencias del usuario
 *   $atributos          - Atributos del shortcode
 *   $dashboard_instance - Instancia de Flavor_Client_Dashboard
 *
 * @package FlavorPlatform
 * @since 4.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$tema_usuario = $preferencias['tema'] ?? 'auto';
$clase_tema = $tema_usuario === 'dark' ? 'flavor-dark fl-dark' : ($tema_usuario === 'light' ? 'flavor-light fl-light' : '');

// Obtener URL actual para breadcrumbs
$url_actual = home_url($_SERVER['REQUEST_URI'] ?? Flavor_Platform_Helpers::get_action_url('', ''));
$nombre_pagina = get_the_title() ?: __('Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN);
?>

<div class="flavor-client-dashboard fl-dashboard-wrapper fl-dashboard-container <?php echo esc_attr($clase_tema); ?>"
     data-theme="<?php echo esc_attr($tema_usuario); ?>"
     data-user-id="<?php echo esc_attr($usuario->ID); ?>">

    <?php if (!empty($legacy_notice) && !empty($portal_url)) : ?>
        <section class="flavor-client-dashboard__legacy-notice fl-legacy-notice" aria-label="<?php esc_attr_e('Aviso de compatibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <div class="flavor-client-dashboard__legacy-copy">
                <span class="flavor-client-dashboard__legacy-eyebrow"><?php echo esc_html($legacy_notice['eyebrow'] ?? ''); ?></span>
                <h2 class="flavor-client-dashboard__legacy-title"><?php echo esc_html($legacy_notice['title'] ?? ''); ?></h2>
                <p class="flavor-client-dashboard__legacy-text"><?php echo esc_html($legacy_notice['text'] ?? ''); ?></p>
            </div>
            <a href="<?php echo esc_url($portal_url); ?>" class="flavor-client-dashboard__legacy-cta fl-btn fl-btn--primary">
                <?php echo esc_html($legacy_notice['cta'] ?? __('Abrir Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
            </a>
        </section>
    <?php endif; ?>

    <!-- Skip Links (Accesibilidad) -->
    <a href="#fl-main-content" class="fl-skip-link"><?php esc_html_e('Saltar al contenido principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    <a href="#fl-shortcuts" class="fl-skip-link"><?php esc_html_e('Saltar a acciones rapidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>

    <!-- =========================================================
         BREADCRUMBS
    ========================================================= -->
    <nav class="fl-breadcrumbs" aria-label="<?php esc_attr_e('Navegacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <ol class="fl-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
            <li class="fl-breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <a href="<?php echo esc_url(home_url('/')); ?>" class="fl-breadcrumbs__link" itemprop="item">
                    <span class="fl-breadcrumbs__icon dashicons dashicons-admin-home" aria-hidden="true"></span>
                    <span itemprop="name"><?php esc_html_e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
                <meta itemprop="position" content="1">
            </li>
            <li class="fl-breadcrumbs__separator" aria-hidden="true">
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </li>
            <li class="fl-breadcrumbs__item fl-breadcrumbs__item--current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <span class="fl-breadcrumbs__current" itemprop="item">
                    <span class="fl-breadcrumbs__icon dashicons dashicons-dashboard" aria-hidden="true"></span>
                    <span itemprop="name"><?php echo esc_html($nombre_pagina); ?></span>
                </span>
                <meta itemprop="position" content="2">
            </li>
        </ol>
    </nav>

    <!-- =========================================================
         HEADER DEL DASHBOARD
    ========================================================= -->
    <header class="flavor-client-dashboard__header fl-dashboard-header">
        <div class="flavor-client-dashboard__header-content fl-dashboard-header__content">
            <div class="flavor-client-dashboard__user fl-user-info">
                <div class="flavor-client-dashboard__avatar fl-avatar fl-avatar--lg">
                    <img src="<?php echo esc_url($avatar_url); ?>"
                         alt="<?php echo esc_attr($nombre_usuario); ?>"
                         width="64"
                         height="64"
                         loading="lazy" />
                </div>
                <div class="flavor-client-dashboard__greeting fl-greeting">
                    <p class="flavor-client-dashboard__saludo fl-greeting__saludo"><?php echo esc_html($saludo); ?>,</p>
                    <h1 class="flavor-client-dashboard__nombre fl-greeting__nombre"><?php echo esc_html($nombre_usuario); ?></h1>
                </div>
            </div>

            <div class="flavor-client-dashboard__header-actions fl-dashboard-header__actions">
                <!-- Boton de refrescar -->
                <button type="button"
                        class="flavor-client-dashboard__btn-icon fl-btn fl-btn--icon"
                        id="flavor-dashboard-refresh"
                        title="<?php esc_attr_e('Actualizar (Ctrl+R)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                        aria-label="<?php esc_attr_e('Actualizar datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <?php echo $dashboard_instance->obtener_icono_svg('refresh', 20); ?>
                </button>

                <!-- Toggle de tema -->
                <button type="button"
                        class="flavor-client-dashboard__btn-icon fl-btn fl-btn--icon"
                        id="flavor-dashboard-theme-toggle"
                        title="<?php esc_attr_e('Cambiar tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                        aria-label="<?php esc_attr_e('Cambiar tema claro/oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="flavor-icon-sun"><?php echo $dashboard_instance->obtener_icono_svg('sun', 20); ?></span>
                    <span class="flavor-icon-moon"><?php echo $dashboard_instance->obtener_icono_svg('moon', 20); ?></span>
                </button>

                <!-- Notificaciones -->
                <?php if (!empty($notificaciones)) : ?>
                    <button type="button"
                            class="flavor-client-dashboard__btn-icon flavor-client-dashboard__btn-icon--notifications fl-btn fl-btn--icon fl-btn--badge"
                            id="flavor-dashboard-notifications-toggle"
                            title="<?php esc_attr_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                            aria-label="<?php printf(esc_attr__('%d notificaciones pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), count($notificaciones)); ?>"
                            aria-expanded="false"
                            aria-controls="flavor-notifications-panel">
                        <?php echo $dashboard_instance->obtener_icono_svg('bell', 20); ?>
                        <span class="flavor-client-dashboard__notification-badge fl-badge fl-badge--danger"><?php echo count($notificaciones); ?></span>
                    </button>
                <?php endif; ?>

                <!-- Enlace a Mi Cuenta -->
                <a href="<?php echo esc_url(home_url('/mi-cuenta/')); ?>"
                   class="flavor-client-dashboard__btn-icon fl-btn fl-btn--icon"
                   title="<?php esc_attr_e('Mi Cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <?php echo $dashboard_instance->obtener_icono_svg('settings', 20); ?>
                </a>
            </div>
        </div>

        <!-- Fecha actual -->
        <div class="flavor-client-dashboard__date fl-date-display">
            <span class="flavor-client-dashboard__date-text fl-date-display__text">
                <?php echo esc_html(date_i18n('l, j \d\e F \d\e Y')); ?>
            </span>
        </div>
    </header>

    <!-- =========================================================
         PANEL DE NOTIFICACIONES (colapsable)
    ========================================================= -->
    <?php if ($atributos['mostrar_notificaciones'] === 'true' && !empty($notificaciones)) : ?>
        <aside class="flavor-client-dashboard__notifications-panel fl-notifications-panel"
               id="flavor-notifications-panel"
               aria-hidden="true"
               role="region"
               aria-label="<?php esc_attr_e('Panel de notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <div class="flavor-client-dashboard__notifications-header fl-notifications-panel__header">
                <h3><?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <button type="button"
                        class="flavor-client-dashboard__btn-text fl-btn fl-btn--text"
                        id="flavor-mark-all-read">
                    <?php esc_html_e('Marcar todas como leidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
            <ul class="flavor-client-dashboard__notifications-list fl-notifications-list" role="list">
                <?php foreach ($notificaciones as $notificacion) :
                    $titulo_notificacion = $notificacion['title'] ?? __('Notificacion', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $mensaje_notificacion = $notificacion['message'] ?? '';
                    $fecha_notificacion = isset($notificacion['created_at']) ?
                        $dashboard_instance->formatear_fecha_relativa($notificacion['created_at']) : '';
                    $id_notificacion = $notificacion['id'] ?? 0;
                    ?>
                    <li class="flavor-client-dashboard__notification-item fl-notification-item"
                        data-notification-id="<?php echo esc_attr($id_notificacion); ?>">
                        <div class="flavor-client-dashboard__notification-content fl-notification-item__content">
                            <span class="flavor-client-dashboard__notification-title fl-notification-item__title">
                                <?php echo esc_html($titulo_notificacion); ?>
                            </span>
                            <?php if ($mensaje_notificacion) : ?>
                                <span class="flavor-client-dashboard__notification-message fl-notification-item__message">
                                    <?php echo esc_html(wp_trim_words($mensaje_notificacion, 15)); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($fecha_notificacion) : ?>
                                <span class="flavor-client-dashboard__notification-time fl-notification-item__time">
                                    <?php echo esc_html($fecha_notificacion); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="flavor-client-dashboard__notification-dismiss fl-btn fl-btn--icon fl-btn--sm"
                                data-notification-id="<?php echo esc_attr($id_notificacion); ?>"
                                aria-label="<?php esc_attr_e('Descartar notificacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <?php echo $dashboard_instance->obtener_icono_svg('x', 16); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url(home_url('/mi-cuenta/?tab=notificaciones')); ?>"
               class="flavor-client-dashboard__notifications-footer fl-notifications-panel__footer">
                <?php esc_html_e('Ver todas las notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
            </a>
        </aside>
    <?php endif; ?>

    <!-- =========================================================
         GRID DE ESTADISTICAS
    ========================================================= -->
    <?php if ($atributos['mostrar_estadisticas'] === 'true' && !empty($estadisticas)) : ?>
        <section class="flavor-client-dashboard__stats fl-stats-section" aria-labelledby="stats-heading">
            <h2 id="stats-heading" class="fl-sr-only">
                <?php esc_html_e('Resumen de tu actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <div class="flavor-client-dashboard__stats-grid fl-stats-grid fl-widget-stats">
                <?php foreach ($estadisticas as $identificador => $estadistica) :
                    $url_estadistica = $estadistica['url'] ?? '#';
                    $color_estadistica = $estadistica['color'] ?? 'primary';
                    ?>
                    <a href="<?php echo esc_url($url_estadistica); ?>"
                       class="flavor-client-dashboard__stat-card flavor-client-dashboard__stat-card--<?php echo esc_attr($color_estadistica); ?> fl-stat-card fl-stat-card--<?php echo esc_attr($color_estadistica); ?>"
                       data-stat-id="<?php echo esc_attr($identificador); ?>">
                        <div class="flavor-client-dashboard__stat-icon fl-stat-card__icon">
                            <?php echo $dashboard_instance->obtener_icono_svg($estadistica['icon'] ?? 'chart', 28); ?>
                        </div>
                        <div class="flavor-client-dashboard__stat-content fl-stat-card__content">
                            <span class="flavor-client-dashboard__stat-value fl-stat-card__value fl-stat-value" data-value="<?php echo esc_attr($estadistica['valor']); ?>">
                                <?php echo esc_html(number_format_i18n($estadistica['valor'])); ?>
                            </span>
                            <span class="flavor-client-dashboard__stat-label fl-stat-card__label fl-stat-label">
                                <?php echo esc_html($estadistica['label']); ?>
                            </span>
                            <?php if (!empty($estadistica['texto'])) : ?>
                                <span class="flavor-client-dashboard__stat-meta fl-stat-card__meta">
                                    <?php echo esc_html($estadistica['texto']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($ecosystem_hierarchy)) : ?>
        <section class="flavor-client-dashboard__ecosystems" aria-labelledby="ecosystems-heading">
            <div class="flavor-client-dashboard__section-header">
                <h2 id="ecosystems-heading" class="flavor-client-dashboard__section-title fl-section-title">
                    <?php esc_html_e('Mis ecosistemas activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <div class="flavor-client-dashboard__ecosystems-grid">
                <?php foreach ($ecosystem_hierarchy as $node) : ?>
                    <article class="flavor-client-dashboard__ecosystem-card">
                        <div class="flavor-client-dashboard__ecosystem-head">
                            <div>
                                <h3 class="flavor-client-dashboard__ecosystem-name"><?php echo esc_html($node['name']); ?></h3>
                                <span class="flavor-client-dashboard__ecosystem-role"><?php echo esc_html($node['role_label']); ?></span>
                                <?php if (!empty($dashboard_contexts) && !empty($node['context_match_score'])) : ?>
                                    <div class="flavor-client-dashboard__ecosystem-context">
                                        <?php esc_html_e('Relevante en este contexto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="flavor-client-dashboard__ecosystem-count"><?php echo esc_html($node['satellite_count']); ?></span>
                        </div>

                        <?php if (!empty($node['satellites'])) : ?>
                            <div class="flavor-client-dashboard__ecosystem-block">
                                <div class="flavor-client-dashboard__ecosystem-label"><?php esc_html_e('Satelites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="flavor-client-dashboard__ecosystem-tags">
                                    <?php foreach ($node['satellites'] as $satellite) : ?>
                                        <span class="flavor-client-dashboard__ecosystem-tag">
                                            <?php echo esc_html($satellite['name']); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($node['transversals'])) : ?>
                            <div class="flavor-client-dashboard__ecosystem-block">
                                <div class="flavor-client-dashboard__ecosystem-label"><?php esc_html_e('Capas transversales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="flavor-client-dashboard__ecosystem-tags">
                                    <?php foreach ($node['transversals'] as $transversal) : ?>
                                        <?php if (!empty($transversal['url'])) : ?>
                                            <a href="<?php echo esc_url($transversal['url']); ?>" class="flavor-client-dashboard__ecosystem-tag <?php echo !empty($transversal['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                                                <?php echo esc_html($transversal['name']); ?>
                                            </a>
                                        <?php else : ?>
                                            <span class="flavor-client-dashboard__ecosystem-tag <?php echo !empty($transversal['is_active']) ? 'is-active' : 'is-suggested'; ?>">
                                                <?php echo esc_html($transversal['name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($node['shortcuts'])) : ?>
                            <div class="flavor-client-dashboard__ecosystem-block">
                                <div class="flavor-client-dashboard__ecosystem-label"><?php esc_html_e('Accesos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                                <div class="flavor-client-dashboard__ecosystem-links">
                                    <?php foreach (array_slice($node['shortcuts'], 0, 3) as $shortcut) : ?>
                                        <a href="<?php echo esc_url($shortcut['url'] ?? '#'); ?>" class="flavor-client-dashboard__ecosystem-link">
                                            <?php echo esc_html($shortcut['label']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="flavor-client-dashboard__ecosystem-actions">
                            <a href="<?php echo esc_url($node['url']); ?>" class="flavor-client-dashboard__ecosystem-open">
                                <?php esc_html_e('Abrir modulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($panel_layers['signals'])) : ?>
        <section class="flavor-client-dashboard__signals" aria-labelledby="signals-heading">
            <div class="flavor-client-dashboard__section-header">
                <h2 id="signals-heading" class="flavor-client-dashboard__section-title fl-section-title">
                    <?php esc_html_e('Senales del nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <div class="flavor-client-dashboard__layer-list">
                <?php foreach ($panel_layers['signals'] as $signal_item) : ?>
                    <a href="<?php echo esc_url($signal_item['url'] ?? '#'); ?>" class="flavor-client-dashboard__layer-card flavor-client-dashboard__layer-card--signal">
                        <span class="flavor-client-dashboard__layer-kind"><?php echo esc_html($signal_item['kind'] ?? ''); ?></span>
                        <?php if (!empty($signal_item['severity']['label'])) : ?>
                            <span class="flavor-client-dashboard__layer-severity flavor-client-dashboard__layer-severity--<?php echo esc_attr($signal_item['severity']['slug'] ?? 'stable'); ?>">
                                <?php echo esc_html($signal_item['severity']['label']); ?>
                            </span>
                        <?php endif; ?>
                        <strong class="flavor-client-dashboard__layer-title"><?php echo esc_html($signal_item['label'] ?? ''); ?></strong>
                        <?php if (!empty($signal_item['meta'])) : ?>
                            <span class="flavor-client-dashboard__layer-meta"><?php echo esc_html(wp_trim_words($signal_item['meta'], 12)); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($panel_layers['actions'])) : ?>
        <section class="flavor-client-dashboard__actions-layer" aria-labelledby="actions-layer-heading">
            <div class="flavor-client-dashboard__section-header">
                <h2 id="actions-layer-heading" class="flavor-client-dashboard__section-title fl-section-title">
                    <?php esc_html_e('Que hacer ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
            </div>
            <div class="flavor-client-dashboard__layer-list">
                <?php foreach ($panel_layers['actions'] as $action_item) : ?>
                    <a href="<?php echo esc_url($action_item['url'] ?? '#'); ?>" class="flavor-client-dashboard__layer-card flavor-client-dashboard__layer-card--action">
                        <span class="flavor-client-dashboard__layer-kind"><?php echo esc_html($action_item['kind'] ?? ''); ?></span>
                        <?php if (!empty($action_item['severity']['label'])) : ?>
                            <span class="flavor-client-dashboard__layer-severity flavor-client-dashboard__layer-severity--<?php echo esc_attr($action_item['severity']['slug'] ?? 'stable'); ?>">
                                <?php echo esc_html($action_item['severity']['label']); ?>
                            </span>
                        <?php endif; ?>
                        <strong class="flavor-client-dashboard__layer-title"><?php echo esc_html($action_item['label'] ?? ''); ?></strong>
                        <?php if (!empty($action_item['meta'])) : ?>
                            <span class="flavor-client-dashboard__layer-meta"><?php echo esc_html($action_item['meta']); ?></span>
                        <?php elseif (!empty($action_item['context'])) : ?>
                            <span class="flavor-client-dashboard__layer-meta"><?php echo esc_html($action_item['context']); ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- =========================================================
         ATAJOS RAPIDOS
    ========================================================= -->
    <?php if ($atributos['mostrar_atajos'] === 'true' && !empty($panel_layers['services'])) : ?>
        <section class="flavor-client-dashboard__shortcuts fl-quick-actions-section" id="fl-shortcuts" aria-labelledby="shortcuts-heading">
            <h2 id="shortcuts-heading" class="flavor-client-dashboard__section-title fl-section-title">
                <?php esc_html_e('Herramientas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>
            <div class="flavor-client-dashboard__shortcuts-grid fl-quick-actions">
                <?php foreach ($panel_layers['services'] as $identificador => $atajo) :
                    $color_atajo = $atajo['color'] ?? 'secondary';
                    $target_atajo = $atajo['target'] ?? '_self';
                    ?>
                    <a href="<?php echo esc_url($atajo['url']); ?>"
                       class="flavor-client-dashboard__shortcut flavor-client-dashboard__shortcut--<?php echo esc_attr($color_atajo); ?> fl-quick-action fl-quick-action--<?php echo esc_attr($color_atajo); ?>"
                       target="<?php echo esc_attr($target_atajo); ?>"
                       data-shortcut-id="<?php echo esc_attr($identificador); ?>">
                        <span class="flavor-client-dashboard__shortcut-icon fl-quick-action__icon">
                            <?php echo $dashboard_instance->obtener_icono_svg($atajo['icon'] ?? 'link', 20); ?>
                        </span>
                        <span class="flavor-client-dashboard__shortcut-label fl-quick-action__label">
                            <?php echo esc_html($atajo['label']); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- =========================================================
         CONTENIDO PRINCIPAL: WIDGETS Y ACTIVIDAD
    ========================================================= -->
    <div class="flavor-client-dashboard__main fl-dashboard-main" id="fl-main-content" role="main">

        <?php if (defined('WP_DEBUG') && WP_DEBUG && current_user_can('manage_options')) : ?>
        <!-- Debug info solo para admins en modo debug -->
        <details class="fl-debug-panel" style="margin-bottom:16px;">
            <summary style="cursor:pointer;padding:8px 12px;background:#fef3c7;border-radius:8px;font-size:12px;color:#92400e;">
                🔧 Debug Dashboard v4.1
            </summary>
            <div style="padding:12px;background:#fffbeb;border-radius:0 0 8px 8px;font-size:12px;">
                Widgets: <?php echo count($widgets ?? []); ?> |
                Grupos: <?php echo count($widgets_agrupados ?? []); ?> |
                Categorías: <?php echo count($categorias ?? []); ?>
            </div>
        </details>
        <?php endif; ?>

        <!-- =========================================================
             FILTROS DE CATEGORIA
        ========================================================= -->
        <?php if ($atributos['mostrar_widgets'] === 'true' && !empty($categorias)) : ?>
            <nav class="fl-category-filters" role="tablist" aria-label="<?php esc_attr_e('Filtrar por categoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <button type="button"
                        class="fl-category-filter fl-category-filter--active"
                        role="tab"
                        aria-selected="true"
                        data-category="all"
                        id="fl-filter-all">
                    <span class="fl-category-filter__label"><?php esc_html_e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="fl-category-filter__count"><?php echo esc_html($total_widgets); ?></span>
                </button>
                <?php foreach ($categorias as $categoria_id => $categoria_info) :
                    if (($categoria_info['count'] ?? 0) === 0) continue;
                    $color_categoria = $categoria_info['color'] ?? '#6b7280';
                    ?>
                    <button type="button"
                            class="fl-category-filter"
                            role="tab"
                            aria-selected="false"
                            data-category="<?php echo esc_attr($categoria_id); ?>"
                            id="fl-filter-<?php echo esc_attr($categoria_id); ?>"
                            style="--category-color: <?php echo esc_attr($color_categoria); ?>">
                        <span class="fl-category-filter__icon dashicons <?php echo esc_attr($categoria_info['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                        <span class="fl-category-filter__label"><?php echo esc_html($categoria_info['label']); ?></span>
                        <span class="fl-category-filter__count"><?php echo esc_html($categoria_info['count']); ?></span>
                    </button>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>

        <!-- =========================================================
             WIDGETS AGRUPADOS POR CATEGORIA
        ========================================================= -->
        <?php if ($atributos['mostrar_widgets'] === 'true' && !empty($widgets_agrupados)) : ?>
            <section class="flavor-client-dashboard__widgets fl-widgets-section" aria-labelledby="widgets-heading">
                <h2 id="widgets-heading" class="fl-sr-only">
                    <?php esc_html_e('Widgets del dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>

                <?php foreach ($widgets_agrupados as $categoria_id => $grupo) :
                    $categoria_info = $grupo['info'];
                    $widgets_categoria = $grupo['widgets'];
                    $grupo_colapsado = $grupo['collapsed'] ?? false;
                    $color_categoria = $categoria_info['color'] ?? '#6b7280';
                    $clase_grupo = $grupo_colapsado ? 'fl-widget-group fl-widget-group--collapsed' : 'fl-widget-group';
                    ?>
                    <div class="<?php echo esc_attr($clase_grupo); ?>"
                         data-category="<?php echo esc_attr($categoria_id); ?>"
                         style="--category-color: <?php echo esc_attr($color_categoria); ?>">

                        <!-- Header del grupo con toggle -->
                        <header class="fl-widget-group__header">
                            <button type="button"
                                    class="fl-widget-group__toggle"
                                    aria-expanded="<?php echo $grupo_colapsado ? 'false' : 'true'; ?>"
                                    aria-controls="fl-group-content-<?php echo esc_attr($categoria_id); ?>"
                                    data-category="<?php echo esc_attr($categoria_id); ?>">
                                <span class="fl-widget-group__icon dashicons <?php echo esc_attr($categoria_info['icon'] ?? 'dashicons-admin-generic'); ?>" aria-hidden="true"></span>
                                <h2 class="fl-widget-group__title" id="fl-group-<?php echo esc_attr($categoria_id); ?>-title">
                                    <?php echo esc_html($categoria_info['label']); ?>
                                </h2>
                                <span class="fl-widget-group__count" aria-label="<?php printf(esc_attr__('%d widgets en esta categoria', FLAVOR_PLATFORM_TEXT_DOMAIN), count($widgets_categoria)); ?>">
                                    <?php echo count($widgets_categoria); ?>
                                </span>
                                <span class="fl-widget-group__arrow dashicons dashicons-arrow-up-alt2" aria-hidden="true"></span>
                            </button>
                        </header>

                        <!-- Contenido del grupo: grid de widgets -->
                        <div class="fl-widget-group__content fl-widgets-grid fud-widgets-grid"
                             id="fl-group-content-<?php echo esc_attr($categoria_id); ?>"
                             role="tabpanel"
                             aria-labelledby="fl-group-<?php echo esc_attr($categoria_id); ?>-title"
                             <?php echo $grupo_colapsado ? 'hidden' : ''; ?>>

                            <?php foreach ($widgets_categoria as $identificador => $widget) :
                                // Verificar si es un objeto widget del registry o config local
                                $es_widget_registry = is_object($widget) && method_exists($widget, 'get_widget_config');

                                if ($es_widget_registry) {
                                    $config_widget = $widget->get_widget_config();
                                    $titulo_widget = $config_widget['title'] ?? $config_widget['label'] ?? '';
                                    $icono_widget = $config_widget['icon'] ?? 'box';
                                    $tamano_widget = $config_widget['size'] ?? 'medium';
                                    $nivel_widget = $config_widget['level'] ?? 2;
                                } else {
                                    $titulo_widget = $widget['title'] ?? '';
                                    $icono_widget = $widget['icon'] ?? 'box';
                                    $tamano_widget = $widget['size'] ?? 'medium';
                                    $nivel_widget = 2;
                                }

                                $colapsado = in_array($identificador, $preferencias['widgets_colapsados'] ?? [], true);

                                // Clases del widget segun nivel
                                $clases_widget = [
                                    'fl-widget',
                                    'fud-widget',
                                    "fl-widget--{$tamano_widget}",
                                    "fl-widget--level-{$nivel_widget}",
                                ];

                                if ($nivel_widget === 1) {
                                    $clases_widget[] = 'fl-widget--featured';
                                } elseif ($nivel_widget === 3) {
                                    $clases_widget[] = 'fl-widget--compact';
                                }

                                if ($colapsado) {
                                    $clases_widget[] = 'fl-widget--collapsed';
                                }
                                ?>
                                <article class="<?php echo esc_attr(implode(' ', $clases_widget)); ?>"
                                         data-widget-id="<?php echo esc_attr($identificador); ?>"
                                         data-category="<?php echo esc_attr($categoria_id); ?>"
                                         data-collapsed="<?php echo $colapsado ? 'true' : 'false'; ?>"
                                         role="region"
                                         aria-labelledby="widget-title-<?php echo esc_attr($identificador); ?>">

                                    <header class="fl-widget__header fud-widget__header">
                                        <div class="fl-widget__title-wrap">
                                            <span class="fl-widget__icon" aria-hidden="true">
                                                <?php
                                                if (strpos($icono_widget, 'dashicons-') === 0) {
                                                    echo '<span class="dashicons ' . esc_attr($icono_widget) . '"></span>';
                                                } else {
                                                    echo $dashboard_instance->obtener_icono_svg($icono_widget, 18);
                                                }
                                                ?>
                                            </span>
                                            <h3 class="fl-widget__title" id="widget-title-<?php echo esc_attr($identificador); ?>">
                                                <?php echo esc_html($titulo_widget); ?>
                                            </h3>
                                        </div>

                                        <nav class="fl-widget__actions" aria-label="<?php printf(esc_attr__('Acciones de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo_widget); ?>">
                                            <button type="button"
                                                    class="fl-widget__action fl-widget__action--refresh"
                                                    data-widget-id="<?php echo esc_attr($identificador); ?>"
                                                    aria-label="<?php printf(esc_attr__('Actualizar %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo_widget); ?>">
                                                <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                            </button>
                                            <button type="button"
                                                    class="fl-widget__action fl-widget__action--collapse"
                                                    data-widget-id="<?php echo esc_attr($identificador); ?>"
                                                    aria-expanded="<?php echo $colapsado ? 'false' : 'true'; ?>"
                                                    aria-controls="widget-content-<?php echo esc_attr($identificador); ?>"
                                                    aria-label="<?php echo $colapsado ? esc_attr__('Expandir widget', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_attr__('Colapsar widget', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                                <span class="dashicons <?php echo $colapsado ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>" aria-hidden="true"></span>
                                            </button>
                                        </nav>
                                    </header>

                                    <div class="fl-widget__body fud-widget__body"
                                         id="widget-content-<?php echo esc_attr($identificador); ?>"
                                         <?php echo $colapsado ? 'hidden' : ''; ?>>
                                        <?php
                                        if ($es_widget_registry) {
                                            $widget->render_widget();
                                        } elseif (is_callable($widget['callback'] ?? null)) {
                                            call_user_func($widget['callback'], $usuario->ID);
                                        }
                                        ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>
        <?php elseif ($atributos['mostrar_widgets'] === 'true' && !empty($widgets)) : ?>
            <!-- Fallback: Widgets sin agrupacion (compatibilidad) -->
            <section class="flavor-client-dashboard__widgets fl-widgets-section" aria-labelledby="widgets-heading">
                <h2 id="widgets-heading" class="fl-sr-only">
                    <?php esc_html_e('Widgets del dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <div class="fl-widgets-grid fud-widgets-grid" data-columns="<?php echo esc_attr($atributos['columnas_widgets']); ?>">
                    <?php foreach ($widgets as $identificador => $widget) :
                        $colapsado = in_array($identificador, $preferencias['widgets_colapsados'] ?? [], true);
                        $tamano_widget = $widget['size'] ?? 'medium';
                        ?>
                        <article class="fl-widget fud-widget fl-widget--<?php echo esc_attr($tamano_widget); ?> <?php echo $colapsado ? 'fl-widget--collapsed' : ''; ?>"
                                 data-widget-id="<?php echo esc_attr($identificador); ?>"
                                 data-collapsed="<?php echo $colapsado ? 'true' : 'false'; ?>"
                                 role="region"
                                 aria-labelledby="widget-title-<?php echo esc_attr($identificador); ?>">
                            <header class="fl-widget__header fud-widget__header">
                                <div class="fl-widget__title-wrap">
                                    <span class="fl-widget__icon" aria-hidden="true">
                                        <?php echo $dashboard_instance->obtener_icono_svg($widget['icon'] ?? 'box', 18); ?>
                                    </span>
                                    <h3 class="fl-widget__title" id="widget-title-<?php echo esc_attr($identificador); ?>">
                                        <?php echo esc_html($widget['title']); ?>
                                    </h3>
                                </div>
                                <nav class="fl-widget__actions">
                                    <button type="button" class="fl-widget__action" data-widget-id="<?php echo esc_attr($identificador); ?>">
                                        <span class="dashicons dashicons-update" aria-hidden="true"></span>
                                    </button>
                                    <button type="button"
                                            class="fl-widget__action fl-widget__action--collapse"
                                            data-widget-id="<?php echo esc_attr($identificador); ?>"
                                            aria-expanded="<?php echo $colapsado ? 'false' : 'true'; ?>">
                                        <span class="dashicons <?php echo $colapsado ? 'dashicons-arrow-down-alt2' : 'dashicons-arrow-up-alt2'; ?>" aria-hidden="true"></span>
                                    </button>
                                </nav>
                            </header>
                            <div class="fl-widget__body fud-widget__body" id="widget-content-<?php echo esc_attr($identificador); ?>" <?php echo $colapsado ? 'hidden' : ''; ?>>
                                <?php
                                if (is_callable($widget['callback'])) {
                                    call_user_func($widget['callback'], $usuario->ID);
                                }
                                ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Timeline de Actividad Reciente -->
        <?php if ($atributos['mostrar_actividad'] === 'true') : ?>
            <aside class="flavor-client-dashboard__activity fl-activity-section" aria-labelledby="activity-heading">
                <header class="flavor-client-dashboard__activity-header fl-section-header">
                    <h2 id="activity-heading" class="flavor-client-dashboard__section-title fl-section-title">
                        <?php esc_html_e('Actividad Reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h2>
                    <a href="<?php echo esc_url(home_url('/mi-cuenta/?tab=actividad')); ?>"
                       class="flavor-client-dashboard__btn-text fl-btn fl-btn--text">
                        <?php esc_html_e('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                    </a>
                </header>

                <div class="flavor-client-dashboard__activity-content fl-activity-content" id="flavor-activity-timeline">
                    <?php if (!empty($actividad_reciente)) : ?>
                        <ul class="flavor-client-dashboard__timeline fl-timeline" role="list">
                            <?php foreach ($actividad_reciente as $item_actividad) :
                                $icono_actividad = $item_actividad['icon'] ?? 'activity';
                                $tipo_actividad = $item_actividad['tipo'] ?? 'default';
                                $fecha_actividad = isset($item_actividad['fecha']) ?
                                    $dashboard_instance->formatear_fecha_relativa($item_actividad['fecha']) : '';
                                ?>
                                <li class="flavor-client-dashboard__timeline-item flavor-client-dashboard__timeline-item--<?php echo esc_attr($tipo_actividad); ?> fl-timeline__item fl-timeline__item--<?php echo esc_attr($tipo_actividad); ?>">
                                    <span class="flavor-client-dashboard__timeline-icon fl-timeline__icon" aria-hidden="true">
                                        <?php echo $dashboard_instance->obtener_icono_svg($icono_actividad, 16); ?>
                                    </span>
                                    <div class="flavor-client-dashboard__timeline-content fl-timeline__content">
                                        <span class="flavor-client-dashboard__timeline-text fl-timeline__text">
                                            <?php echo esc_html($item_actividad['texto'] ?? ''); ?>
                                        </span>
                                        <?php if ($fecha_actividad) : ?>
                                            <time class="flavor-client-dashboard__timeline-time fl-timeline__time">
                                                <?php echo esc_html($fecha_actividad); ?>
                                            </time>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item_actividad['url'])) : ?>
                                        <a href="<?php echo esc_url($item_actividad['url']); ?>"
                                           class="flavor-client-dashboard__timeline-link fl-timeline__link"
                                           aria-label="<?php esc_attr_e('Ver detalle', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                            <span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="flavor-client-dashboard__empty-state fl-empty-state">
                            <div class="flavor-client-dashboard__empty-icon fl-empty-state__icon">
                                <?php echo $dashboard_instance->obtener_icono_svg('activity', 48); ?>
                            </div>
                            <p class="flavor-client-dashboard__empty-text fl-empty-state__message">
                                <?php esc_html_e('No hay actividad reciente para mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        <?php endif; ?>

    </div>

    <!-- =========================================================
         FOOTER
    ========================================================= -->
    <footer class="fl-dashboard-footer">
        <span class="fl-last-update">
            <span class="dashicons dashicons-clock" aria-hidden="true"></span>
            <?php esc_html_e('Ultima actualizacion:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <time datetime="<?php echo esc_attr(current_time('c')); ?>" id="fl-last-refresh">
                <?php echo esc_html(date_i18n(get_option('time_format'), current_time('timestamp'))); ?>
            </time>
        </span>
    </footer>

    <!-- Toast de notificaciones -->
    <div class="flavor-client-dashboard__toasts fl-toasts" id="flavor-dashboard-toasts" aria-live="polite" role="status"></div>

    <!-- Indicador de carga -->
    <div class="flavor-client-dashboard__loading fl-loading-overlay" id="flavor-dashboard-loading" aria-hidden="true">
        <div class="flavor-client-dashboard__loading-spinner fl-loading-spinner"></div>
        <span class="flavor-client-dashboard__loading-text fl-loading-text"><?php esc_html_e('Actualizando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
    </div>

    <!-- Live Region para anuncios de accesibilidad -->
    <div id="fl-live-announcer" class="fl-sr-only" role="status" aria-live="polite" aria-atomic="true"></div>

</div>
