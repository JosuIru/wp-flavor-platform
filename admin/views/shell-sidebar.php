<?php
/**
 * Flavor Admin Shell - Sidebar Template
 *
 * Template del sidebar de navegación elegante
 *
 * @package FlavorChatIA
 * @since 3.2.0
 *
 * Variables disponibles:
 * - $navigation: array con estructura de navegación
 * - $current_page: string con la página actual
 * - $is_dark_mode: bool si está en modo oscuro
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener datos de favoritos y recientes
$favorites_recent_manager = class_exists('Flavor_Shell_Favorites_Recent')
    ? Flavor_Shell_Favorites_Recent::get_instance()
    : null;

$shell_data = $favorites_recent_manager
    ? $favorites_recent_manager->get_shell_data()
    : ['favorites' => [], 'recent' => [], 'max_favorites' => 15, 'favorites_count' => 0];

// Obtener vistas personalizadas disponibles
$custom_views_manager = class_exists('Flavor_Shell_Custom_Views')
    ? Flavor_Shell_Custom_Views::get_instance()
    : null;

$available_views = $custom_views_manager
    ? $custom_views_manager->get_available_views()
    : [];

$active_custom_view = $custom_views_manager
    ? $custom_views_manager->get_user_active_view()
    : null;

$current_post_type = isset($_GET['post_type']) ? sanitize_key((string) $_GET['post_type']) : '';
$current_taxonomy = isset($_GET['taxonomy']) ? sanitize_key((string) $_GET['taxonomy']) : '';
$current_pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';

/**
 * Determina si un item del shell debe marcarse como activo.
 *
 * @param array $item
 * @return bool
 */
$shell_item_is_active = static function(array $item) use ($current_page, $current_post_type, $current_taxonomy, $current_pagenow) {
    $item_slug = isset($item['slug']) ? sanitize_key((string) $item['slug']) : '';
    if ($item_slug !== '' && $current_page === $item_slug) {
        return true;
    }

    // Considerar subpáginas de módulo (ej: contabilidad-dashboard -> contabilidad-movimientos).
    if ($item_slug !== '' && substr($item_slug, -strlen('-dashboard')) === '-dashboard') {
        $module_prefix = substr($item_slug, 0, -strlen('-dashboard'));
        if ($module_prefix !== '' && strpos($current_page, $module_prefix . '-') === 0) {
            return true;
        }
    }

    // Soportar items que navegan por URL (edit.php?post_type=... / admin.php?page=...).
    $item_url = isset($item['url']) ? (string) $item['url'] : '';
    if ($item_url !== '') {
        $query = wp_parse_url($item_url, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $query_vars);
            $query_page = isset($query_vars['page']) ? sanitize_key((string) $query_vars['page']) : '';
            $query_post_type = isset($query_vars['post_type']) ? sanitize_key((string) $query_vars['post_type']) : '';
            $query_taxonomy = isset($query_vars['taxonomy']) ? sanitize_key((string) $query_vars['taxonomy']) : '';

            if ($query_page !== '' && $query_page === $current_page) {
                return true;
            }
            if ($query_post_type !== '' && $query_post_type === $current_post_type && in_array($current_pagenow, ['edit.php', 'post-new.php'], true)) {
                return true;
            }
            if ($query_taxonomy !== '' && $query_taxonomy === $current_taxonomy) {
                return true;
            }
        }
    }

    return false;
};
?>

<!-- Skip Link para Accesibilidad -->
<a href="#wpbody-content" class="fls-shell__skip-link">
    <?php esc_html_e('Saltar al contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
</a>

<!-- Mobile Toggle Button -->
<button
    type="button"
    class="fls-shell__mobile-toggle"
    x-data
    @click="$dispatch('toggle-mobile-shell')"
    aria-label="<?php esc_attr_e('Abrir menú', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
>
    <span class="dashicons dashicons-menu"></span>
</button>

<!-- Quick Search Modal (Cmd+K) -->
<div
    x-data="flavorShellSearch"
    x-show="searchOpen"
    x-cloak
    @keydown.escape.window="closeSearch()"
    @keydown.meta.k.window.prevent="toggleSearch()"
    @keydown.ctrl.k.window.prevent="toggleSearch()"
    class="fls-shell-search"
>
    <div class="fls-shell-search__backdrop" @click="closeSearch()"></div>
    <div class="fls-shell-search__modal" @click.outside="closeSearch()">
        <div class="fls-shell-search__header">
            <span class="dashicons dashicons-search fls-shell-search__icon"></span>
            <input
                type="text"
                x-model="query"
                x-ref="searchInput"
                @input.debounce.150ms="search()"
                @keydown.down.prevent="navigateResults(1)"
                @keydown.up.prevent="navigateResults(-1)"
                @keydown.enter.prevent="selectResult()"
                class="fls-shell-search__input"
                placeholder="<?php esc_attr_e('Buscar páginas, módulos, acciones...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                autocomplete="off"
            >
            <kbd class="fls-shell-search__kbd">ESC</kbd>
        </div>

        <div class="fls-shell-search__results" x-show="results.length > 0 || query.length > 0">
            <template x-if="results.length === 0 && query.length > 0">
                <div class="fls-shell-search__empty">
                    <span class="dashicons dashicons-search"></span>
                    <p><?php esc_html_e('No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </template>

            <template x-for="(group, groupIndex) in groupedResults" :key="groupIndex">
                <div class="fls-shell-search__group">
                    <div class="fls-shell-search__group-title" x-text="group.label"></div>
                    <template x-for="(result, index) in group.items" :key="`${result.slug}-${index}`">
                        <a
                            :href="result.url"
                            class="fls-shell-search__result"
                            :class="{ 'fls-shell-search__result--active': isActive(result) }"
                            @mouseenter="setActive(result)"
                            @click="trackVisit(result)"
                        >
                            <span class="fls-shell-search__result-icon">
                                <span class="dashicons" :class="result.icon"></span>
                            </span>
                            <span class="fls-shell-search__result-text">
                                <span class="fls-shell-search__result-title" x-text="result.label"></span>
                                <span class="fls-shell-search__result-section" x-text="result.section" x-show="result.section"></span>
                            </span>
                            <span class="fls-shell-search__result-action">
                                <span class="dashicons dashicons-arrow-right-alt2"></span>
                            </span>
                        </a>
                    </template>
                </div>
            </template>
        </div>

        <div class="fls-shell-search__footer">
            <span><kbd>&uarr;</kbd><kbd>&darr;</kbd> <?php esc_html_e('navegar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <span><kbd>Enter</kbd> <?php esc_html_e('ir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <span><kbd>Esc</kbd> <?php esc_html_e('cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>
</div>

<!-- Shell Sidebar -->
<div
    x-data="flavorShell"
    class="fls-shell<?php echo $is_dark_mode ? ' fls-shell-dark' : ''; ?>"
    :class="{
        'fls-shell--collapsed': collapsed,
        'fls-shell--mobile-open': mobileOpen
    }"
    @toggle-mobile-shell.window="toggleMobile()"
    @keydown.escape.window="closeMobile()"
>
    <!-- Header -->
    <div class="fls-shell__header">
        <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-dashboard')); ?>" class="fls-shell__logo">
            <span class="fls-shell__logo-icon">
                <span class="dashicons dashicons-superhero-alt"></span>
            </span>
            <span class="fls-shell__logo-text">Flavor</span>
        </a>

        <button
            type="button"
            class="fls-shell__collapse-btn"
            @click="toggleCollapse()"
            :aria-label="collapsed ? '<?php echo esc_js(__('Expandir menú', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>' : '<?php echo esc_js(__('Colapsar menú', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'"
            :title="collapsed ? '<?php echo esc_js(__('Expandir menú', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>' : '<?php echo esc_js(__('Colapsar menú', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'"
        >
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
    </div>

    <!-- Quick Search Button -->
    <button
        type="button"
        class="fls-shell__search-btn"
        @click="$dispatch('open-shell-search')"
        data-tooltip="<?php esc_attr_e('Buscar (Cmd+K)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
    >
        <span class="dashicons dashicons-search"></span>
        <span class="fls-shell__search-text"><?php esc_html_e('Buscar...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        <kbd class="fls-shell__search-kbd">
            <span><?php echo (strpos(strtolower($_SERVER['HTTP_USER_AGENT'] ?? ''), 'mac') !== false) ? '⌘' : 'Ctrl'; ?></span>K
        </kbd>
    </button>

    <!-- Quick Portal Link -->
    <a
        href="<?php echo esc_url(home_url('/mi-portal/')); ?>"
        target="_blank"
        class="fls-shell__portal-btn"
        data-tooltip="<?php esc_attr_e('Abrir Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
    >
        <span class="dashicons dashicons-admin-home"></span>
        <span class="fls-shell__portal-text"><?php esc_html_e('Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        <span class="dashicons dashicons-external fls-shell__portal-external"></span>
    </a>

    <!-- Vista Selector Mejorado -->
    <?php
    $menu_manager = Flavor_Admin_Menu_Manager::get_instance();
    $vista_activa = $menu_manager->obtener_vista_activa();
    $es_vista_admin = $vista_activa === Flavor_Admin_Menu_Manager::VISTA_ADMIN;

    // Determinar nombre e icono de vista activa
    $vista_nombre = $es_vista_admin ? __('Admin', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Gestor', FLAVOR_PLATFORM_TEXT_DOMAIN);
    $vista_icono = $es_vista_admin ? '👤' : '👥';

    if ($active_custom_view && !in_array($active_custom_view, ['admin', 'gestor'], true)) {
        foreach ($available_views as $view) {
            if ($view['id'] === $active_custom_view) {
                $vista_nombre = $view['name'];
                $vista_icono = $view['icon'] ?: ($es_vista_admin ? '👤' : '👥');
                break;
            }
        }
    }
    ?>
    <div class="fls-shell__vista-selector">
        <button
            type="button"
            class="fls-shell__vista-btn"
            @click="vistaOpen = !vistaOpen"
            @click.outside="vistaOpen = false"
            x-init="vistaOpen = false"
        >
            <span class="fls-shell__vista-icon"><?php echo esc_html($vista_icono); ?></span>
            <span class="fls-shell__vista-text"><?php echo esc_html($vista_nombre); ?></span>
            <span class="dashicons dashicons-arrow-down-alt2 fls-shell__vista-arrow"></span>
        </button>
        <div class="fls-shell__vista-dropdown" x-show="vistaOpen" x-transition x-cloak>
            <!-- Vistas del sistema -->
            <div class="fls-shell__vista-group-title"><?php esc_html_e('Vistas del sistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            <button
                type="button"
                class="fls-shell__vista-option <?php echo ($es_vista_admin && !$active_custom_view) ? 'fls-shell__vista-option--active' : ''; ?>"
                @click="cambiarVista('<?php echo esc_js(Flavor_Admin_Menu_Manager::VISTA_ADMIN); ?>')"
            >
                <span class="fls-shell__vista-check"><?php echo ($es_vista_admin && !$active_custom_view) ? '✓' : ''; ?></span>
                <span>👤 <?php esc_html_e('Administrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>
            <button
                type="button"
                class="fls-shell__vista-option <?php echo (!$es_vista_admin && !$active_custom_view) ? 'fls-shell__vista-option--active' : ''; ?>"
                @click="cambiarVista('<?php echo esc_js(Flavor_Admin_Menu_Manager::VISTA_GESTOR_GRUPOS); ?>')"
            >
                <span class="fls-shell__vista-check"><?php echo (!$es_vista_admin && !$active_custom_view) ? '✓' : ''; ?></span>
                <span>👥 <?php esc_html_e('Gestor de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </button>

            <?php if (!empty($available_views)) : ?>
                <!-- Vistas personalizadas -->
                <?php
                $custom_views_only = array_filter($available_views, function($v) {
                    return empty($v['is_system']);
                });
                if (!empty($custom_views_only)) :
                ?>
                    <div class="fls-shell__vista-group-title"><?php esc_html_e('Vistas personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    <?php foreach ($custom_views_only as $view) : ?>
                        <button
                            type="button"
                            class="fls-shell__vista-option <?php echo ($active_custom_view === $view['id']) ? 'fls-shell__vista-option--active' : ''; ?>"
                            @click="cambiarVistaPersonalizada('<?php echo esc_js($view['id']); ?>')"
                            style="<?php echo $view['color'] ? '--vista-color: ' . esc_attr($view['color']) . ';' : ''; ?>"
                        >
                            <span class="fls-shell__vista-check"><?php echo ($active_custom_view === $view['id']) ? '✓' : ''; ?></span>
                            <span><?php echo $view['icon'] ? esc_html($view['icon']) . ' ' : ''; ?><?php echo esc_html($view['name']); ?></span>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php endif; ?>

            <hr class="fls-shell__vista-divider">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-module-dashboards')); ?>" class="fls-shell__vista-option">
                <span class="fls-shell__vista-check"></span>
                <span>📊 <?php esc_html_e('Índice de dashboards', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-platform-views')); ?>" class="fls-shell__vista-option">
                <span class="fls-shell__vista-check"></span>
                <span>⚙️ <?php esc_html_e('Configurar vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </a>
            <?php if (current_user_can('manage_options')) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-shell-views')); ?>" class="fls-shell__vista-option">
                    <span class="fls-shell__vista-check"></span>
                    <span>➕ <?php esc_html_e('Crear vista', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Favoritos Section -->
    <?php if (!empty($shell_data['favorites'])) : ?>
        <div class="fls-shell__favorites" x-data="{ favoritesOpen: true }">
            <button
                type="button"
                class="fls-shell__section-toggle"
                @click="favoritesOpen = !favoritesOpen"
            >
                <span class="fls-shell__section-title">
                    <span class="dashicons dashicons-star-filled fls-shell__section-icon"></span>
                    <?php esc_html_e('Favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <span class="dashicons fls-shell__section-arrow" :class="favoritesOpen ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'"></span>
            </button>
            <ul class="fls-shell__quick-list" x-show="favoritesOpen" x-transition x-cloak>
                <?php foreach ($shell_data['favorites'] as $favorite) :
                    $fav_url = admin_url('admin.php?page=' . $favorite['slug']);
                    $fav_is_active = $current_page === $favorite['slug'];
                ?>
                    <li class="fls-shell__quick-item">
                        <a
                            href="<?php echo esc_url($fav_url); ?>"
                            class="fls-shell__quick-link<?php echo $fav_is_active ? ' fls-shell__quick-link--active' : ''; ?>"
                            data-slug="<?php echo esc_attr($favorite['slug']); ?>"
                        >
                            <span class="fls-shell__quick-icon">
                                <span class="dashicons <?php echo esc_attr($favorite['icon'] ?? 'dashicons-star-filled'); ?>"></span>
                            </span>
                            <span class="fls-shell__quick-text"><?php echo esc_html($favorite['label']); ?></span>
                            <button
                                type="button"
                                class="fls-shell__quick-remove"
                                @click.prevent.stop="removeFavorite('<?php echo esc_js($favorite['slug']); ?>')"
                                title="<?php esc_attr_e('Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                            >
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Recientes Section -->
    <?php if (!empty($shell_data['recent'])) : ?>
        <div class="fls-shell__recent" x-data="{ recentOpen: true }">
            <button
                type="button"
                class="fls-shell__section-toggle"
                @click="recentOpen = !recentOpen"
            >
                <span class="fls-shell__section-title">
                    <span class="dashicons dashicons-backup fls-shell__section-icon"></span>
                    <?php esc_html_e('Recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <span class="dashicons fls-shell__section-arrow" :class="recentOpen ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'"></span>
            </button>
            <ul class="fls-shell__quick-list" x-show="recentOpen" x-transition x-cloak>
                <?php foreach ($shell_data['recent'] as $recent_item) :
                    $recent_url = admin_url('admin.php?page=' . $recent_item['slug']);
                    $recent_is_active = $current_page === $recent_item['slug'];
                ?>
                    <li class="fls-shell__quick-item">
                        <a
                            href="<?php echo esc_url($recent_url); ?>"
                            class="fls-shell__quick-link<?php echo $recent_is_active ? ' fls-shell__quick-link--active' : ''; ?>"
                            data-slug="<?php echo esc_attr($recent_item['slug']); ?>"
                        >
                            <span class="fls-shell__quick-icon">
                                <span class="dashicons <?php echo esc_attr($recent_item['icon'] ?? 'dashicons-admin-page'); ?>"></span>
                            </span>
                            <span class="fls-shell__quick-text"><?php echo esc_html($recent_item['label']); ?></span>
                            <button
                                type="button"
                                class="fls-shell__quick-favorite"
                                @click.prevent.stop="addToFavorites('<?php echo esc_js($recent_item['slug']); ?>', '<?php echo esc_js($recent_item['label']); ?>', '<?php echo esc_js($recent_item['icon'] ?? 'dashicons-star-filled'); ?>')"
                                title="<?php esc_attr_e('Añadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                            >
                                <span class="dashicons dashicons-star-empty"></span>
                            </button>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Navigation -->
    <nav class="fls-shell__nav" role="navigation" aria-label="<?php esc_attr_e('Menú principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <?php
        $section_index = 0;
        foreach ($navigation as $section_id => $section) :
            $section_has_active = false;
            if (!empty($section['items']) && is_array($section['items'])) {
                foreach ($section['items'] as $section_item) {
                    $section_item_active = $shell_item_is_active($section_item) || !empty($section_item['is_expanded']);
                    if (!$section_item_active && !empty($section_item['subpages']) && is_array($section_item['subpages'])) {
                        foreach ($section_item['subpages'] as $section_subpage) {
                            $sub_slug = isset($section_subpage['slug']) ? sanitize_key((string) $section_subpage['slug']) : '';
                            if ($sub_slug !== '' && $current_page === $sub_slug) {
                                $section_item_active = true;
                                break;
                            }
                        }
                    }
                    if ($section_item_active) {
                        $section_has_active = true;
                        break;
                    }
                }
            }

            // Abrir sección activa; fallback primera sección.
            $default_open = ($section_has_active || $section_index === 0) ? 'true' : 'false';
            $section_index++;
        ?>
            <div class="fls-shell__section" x-data="{ sectionOpen: <?php echo $default_open; ?> }">
                <button
                    type="button"
                    class="fls-shell__section-toggle"
                    @click="sectionOpen = !sectionOpen"
                >
                    <span class="fls-shell__section-title">
                        <?php echo esc_html($section['label']); ?>
                    </span>
                    <span class="dashicons fls-shell__section-arrow" :class="sectionOpen ? 'dashicons-arrow-up-alt2' : 'dashicons-arrow-down-alt2'"></span>
                </button>

                <ul class="fls-shell__menu" role="menu" x-show="sectionOpen" x-transition x-cloak>
                    <?php foreach ($section['items'] as $item) :
                        // Soportar URLs directas o slugs de página
                        $item_url = isset($item['url']) ? admin_url($item['url']) : admin_url('admin.php?page=' . $item['slug']);
                        $is_expanded = !empty($item['is_expanded']);
                        $has_subpages = !empty($item['subpages']);
                        // Activo por contexto actual (no solo igualdad exacta).
                        $is_active = $shell_item_is_active($item) || $is_expanded;
                        $has_badge = !empty($item['badge']) && $item['badge']['count'] > 0;
                        $badge_severity = $has_badge ? ($item['badge']['severity'] ?? 'info') : '';
                        // Verificar si es favorito
                        $is_favorite = $favorites_recent_manager ? $favorites_recent_manager->is_favorite($item['slug']) : false;
                    ?>
                        <li class="fls-shell__menu-item<?php echo $is_expanded ? ' fls-shell__menu-item--expanded' : ''; ?>" role="none">
                            <a
                                href="<?php echo esc_url($item_url); ?>"
                                class="fls-shell__menu-link<?php echo $is_active ? ' fls-shell__menu-link--active' : ''; ?><?php echo $is_expanded ? ' fls-shell__menu-link--parent' : ''; ?>"
                                role="menuitem"
                                data-tooltip="<?php echo esc_attr($item['label']); ?>"
                                data-slug="<?php echo esc_attr($item['slug']); ?>"
                                data-label="<?php echo esc_attr($item['label']); ?>"
                                data-icon="<?php echo esc_attr($item['icon']); ?>"
                                <?php if ($is_active) : ?>
                                    aria-current="page"
                                <?php endif; ?>
                            >
                                <span class="fls-shell__menu-icon">
                                    <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                                </span>
                                <span class="fls-shell__menu-text">
                                    <?php echo esc_html($item['label']); ?>
                                </span>
                                <?php if ($has_badge) :
                                    $badge_tooltip = Flavor_Admin_Shell::get_badge_tooltip($item['slug'], $item['badge']);
                                ?>
                                    <span
                                        class="fls-shell__menu-badge fls-shell__menu-badge--<?php echo esc_attr($badge_severity); ?>"
                                        data-slug="<?php echo esc_attr($item['slug']); ?>"
                                        <?php if ($badge_tooltip) : ?>data-tooltip="<?php echo esc_attr($badge_tooltip); ?>"<?php endif; ?>
                                    >
                                        <?php echo $item['badge']['count'] > 99 ? '99+' : number_format_i18n($item['badge']['count']); ?>
                                    </span>
                                <?php endif; ?>
                                <!-- Favorite toggle (visible on hover) -->
                                <button
                                    type="button"
                                    class="fls-shell__menu-fav<?php echo $is_favorite ? ' fls-shell__menu-fav--active' : ''; ?>"
                                    @click.prevent.stop="toggleFavorite('<?php echo esc_js($item['slug']); ?>', '<?php echo esc_js($item['label']); ?>', '<?php echo esc_js($item['icon']); ?>')"
                                    title="<?php echo $is_favorite ? esc_attr__('Quitar de favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_attr__('Añadir a favoritos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                                >
                                    <span class="dashicons <?php echo $is_favorite ? 'dashicons-star-filled' : 'dashicons-star-empty'; ?>"></span>
                                </button>
                            </a>

                            <?php if ($has_subpages && $is_expanded) : ?>
                                <ul class="fls-shell__submenu" role="menu">
                                    <?php foreach ($item['subpages'] as $subpage) :
                                        $subpage_url = admin_url('admin.php?page=' . $subpage['slug']);
                                        $subpage_active = $current_page === $subpage['slug'];
                                        $subpage_has_badge = !empty($subpage['badge']) && $subpage['badge']['count'] > 0;
                                        $subpage_badge_severity = $subpage_has_badge ? ($subpage['badge']['severity'] ?? 'info') : '';
                                    ?>
                                        <li class="fls-shell__submenu-item" role="none">
                                            <a
                                                href="<?php echo esc_url($subpage_url); ?>"
                                                class="fls-shell__submenu-link<?php echo $subpage_active ? ' fls-shell__submenu-link--active' : ''; ?>"
                                                role="menuitem"
                                                <?php if ($subpage_active) : ?>
                                                    aria-current="page"
                                                <?php endif; ?>
                                            >
                                                <span class="fls-shell__submenu-icon">
                                                    <span class="dashicons <?php echo esc_attr($subpage['icon'] ?? 'dashicons-arrow-right-alt2'); ?>"></span>
                                                </span>
                                                <span class="fls-shell__submenu-text">
                                                    <?php echo esc_html($subpage['label']); ?>
                                                </span>
                                                <?php if ($subpage_has_badge) :
                                                    $subpage_tooltip = Flavor_Admin_Shell::get_badge_tooltip($subpage['slug'], $subpage['badge']);
                                                ?>
                                                    <span
                                                        class="fls-shell__submenu-badge fls-shell__submenu-badge--<?php echo esc_attr($subpage_badge_severity); ?>"
                                                        data-slug="<?php echo esc_attr($subpage['slug']); ?>"
                                                        <?php if ($subpage_tooltip) : ?>data-tooltip="<?php echo esc_attr($subpage_tooltip); ?>"<?php endif; ?>
                                                    >
                                                        <?php echo $subpage['badge']['count'] > 99 ? '99+' : number_format_i18n($subpage['badge']['count']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="fls-shell__footer">
        <!-- Quick Search -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            @click="$dispatch('open-shell-search')"
            data-tooltip="<?php echo esc_attr__('Buscar (Cmd+K)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
            aria-label="<?php esc_attr_e('Abrir búsqueda rápida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
        >
            <span class="dashicons dashicons-search"></span>
        </button>

        <!-- Dark Mode Toggle -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            :class="{ 'fls-shell__footer-btn--active': darkMode }"
            @click="toggleDarkMode()"
            :data-tooltip="darkMode ? '<?php echo esc_attr__('Modo claro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' : '<?php echo esc_attr__('Modo oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>'"
            :aria-label="darkMode ? '<?php echo esc_attr__('Cambiar a modo claro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>' : '<?php echo esc_attr__('Cambiar a modo oscuro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>'"
        >
            <span class="fls-shell__darkmode-icon">
                <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
                <svg x-show="darkMode" x-cloak xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="5"></circle>
                    <line x1="12" y1="1" x2="12" y2="3"></line>
                    <line x1="12" y1="21" x2="12" y2="23"></line>
                    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                    <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                    <line x1="1" y1="12" x2="3" y2="12"></line>
                    <line x1="21" y1="12" x2="23" y2="12"></line>
                    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                    <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                </svg>
            </span>
        </button>

        <!-- Back to WordPress -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            @click="goToWPDashboard()"
            data-tooltip="<?php echo esc_attr__('Volver a WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
            aria-label="<?php esc_attr_e('Volver al escritorio de WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
        >
            <span class="dashicons dashicons-wordpress"></span>
        </button>

        <!-- Disable Shell -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            @click="disableShell()"
            data-tooltip="<?php echo esc_attr__('Desactivar shell', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
            aria-label="<?php esc_attr_e('Desactivar shell de navegación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
        >
            <span class="dashicons dashicons-dismiss"></span>
        </button>
    </div>

    <!-- User Profile -->
    <?php
    $current_user = wp_get_current_user();
    $avatar_url = get_avatar_url($current_user->ID, ['size' => 32]);
    $display_name = $current_user->display_name ?: $current_user->user_login;
    $user_email = $current_user->user_email;
    ?>
    <div class="fls-shell__user" x-data="{ userMenuOpen: false }">
        <button
            type="button"
            class="fls-shell__user-btn"
            @click="userMenuOpen = !userMenuOpen"
            @click.outside="userMenuOpen = false"
        >
            <img
                src="<?php echo esc_url($avatar_url); ?>"
                alt="<?php echo esc_attr($display_name); ?>"
                class="fls-shell__user-avatar"
            >
            <span class="fls-shell__user-info">
                <span class="fls-shell__user-name"><?php echo esc_html($display_name); ?></span>
                <span class="fls-shell__user-role"><?php echo esc_html(ucfirst($current_user->roles[0] ?? 'Usuario')); ?></span>
            </span>
            <span class="dashicons dashicons-arrow-up-alt2 fls-shell__user-arrow"></span>
        </button>
        <div class="fls-shell__user-dropdown" x-show="userMenuOpen" x-transition>
            <div class="fls-shell__user-header">
                <img
                    src="<?php echo esc_url(get_avatar_url($current_user->ID, ['size' => 48])); ?>"
                    alt="<?php echo esc_attr($display_name); ?>"
                    class="fls-shell__user-avatar-lg"
                >
                <div class="fls-shell__user-details">
                    <strong><?php echo esc_html($display_name); ?></strong>
                    <small><?php echo esc_html($user_email); ?></small>
                </div>
            </div>
            <hr class="fls-shell__user-divider">
            <a href="<?php echo esc_url(admin_url('profile.php')); ?>" class="fls-shell__user-option">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Editar perfil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('profile.php#password')); ?>" class="fls-shell__user-option">
                <span class="dashicons dashicons-lock"></span>
                <?php esc_html_e('Cambiar contraseña', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" class="fls-shell__user-option">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Ajustes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <hr class="fls-shell__user-divider">
            <a href="<?php echo esc_url(home_url('/mi-portal/')); ?>" class="fls-shell__user-option" target="_blank">
                <span class="dashicons dashicons-admin-home"></span>
                <?php esc_html_e('Ver Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-external" style="font-size: 12px; margin-left: auto; opacity: 0.5;"></span>
            </a>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="fls-shell__user-option" target="_blank">
                <span class="dashicons dashicons-welcome-view-site"></span>
                <?php esc_html_e('Ver sitio web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                <span class="dashicons dashicons-external" style="font-size: 12px; margin-left: auto; opacity: 0.5;"></span>
            </a>
            <hr class="fls-shell__user-divider">
            <a href="<?php echo esc_url(wp_logout_url(admin_url())); ?>" class="fls-shell__user-option fls-shell__user-option--logout">
                <span class="dashicons dashicons-exit"></span>
                <?php esc_html_e('Cerrar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>
    </div>
</div>

<!-- Mobile Overlay -->
<div
    class="fls-shell__overlay"
    x-data
    @click="$dispatch('toggle-mobile-shell')"
    aria-hidden="true"
></div>
