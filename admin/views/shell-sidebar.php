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
?>

<!-- Skip Link para Accesibilidad -->
<a href="#wpbody-content" class="fls-shell__skip-link">
    <?php esc_html_e('Saltar al contenido', 'flavor-chat-ia'); ?>
</a>

<!-- Mobile Toggle Button -->
<button
    type="button"
    class="fls-shell__mobile-toggle"
    x-data
    @click="$dispatch('toggle-mobile-shell')"
    aria-label="<?php esc_attr_e('Abrir menú', 'flavor-chat-ia'); ?>"
>
    <span class="dashicons dashicons-menu"></span>
</button>

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
            :aria-label="collapsed ? '<?php echo esc_js(__('Expandir menú', 'flavor-chat-ia')); ?>' : '<?php echo esc_js(__('Colapsar menú', 'flavor-chat-ia')); ?>'"
            :title="collapsed ? '<?php echo esc_js(__('Expandir menú', 'flavor-chat-ia')); ?>' : '<?php echo esc_js(__('Colapsar menú', 'flavor-chat-ia')); ?>'"
        >
            <span class="dashicons dashicons-arrow-left-alt2"></span>
        </button>
    </div>

    <!-- Vista Selector -->
    <?php
    $menu_manager = Flavor_Admin_Menu_Manager::get_instance();
    $vista_activa = $menu_manager->obtener_vista_activa();
    $es_vista_admin = $vista_activa === Flavor_Admin_Menu_Manager::VISTA_ADMIN;
    ?>
    <div class="fls-shell__vista-selector">
        <button
            type="button"
            class="fls-shell__vista-btn"
            @click="vistaOpen = !vistaOpen"
            @click.outside="vistaOpen = false"
            x-init="vistaOpen = false"
        >
            <span class="fls-shell__vista-icon"><?php echo $es_vista_admin ? '👤' : '👥'; ?></span>
            <span class="fls-shell__vista-text">
                <?php echo $es_vista_admin ? __('Admin', 'flavor-chat-ia') : __('Gestor', 'flavor-chat-ia'); ?>
            </span>
            <span class="dashicons dashicons-arrow-down-alt2 fls-shell__vista-arrow"></span>
        </button>
        <div class="fls-shell__vista-dropdown" x-show="vistaOpen" x-transition x-cloak>
            <button
                type="button"
                class="fls-shell__vista-option <?php echo $es_vista_admin ? 'fls-shell__vista-option--active' : ''; ?>"
                @click="cambiarVista('<?php echo esc_js(Flavor_Admin_Menu_Manager::VISTA_ADMIN); ?>')"
            >
                <span class="fls-shell__vista-check"><?php echo $es_vista_admin ? '✓' : ''; ?></span>
                <span>👤 <?php esc_html_e('Administrador', 'flavor-chat-ia'); ?></span>
            </button>
            <button
                type="button"
                class="fls-shell__vista-option <?php echo !$es_vista_admin ? 'fls-shell__vista-option--active' : ''; ?>"
                @click="cambiarVista('<?php echo esc_js(Flavor_Admin_Menu_Manager::VISTA_GESTOR_GRUPOS); ?>')"
            >
                <span class="fls-shell__vista-check"><?php echo !$es_vista_admin ? '✓' : ''; ?></span>
                <span>👥 <?php esc_html_e('Gestor de Grupos', 'flavor-chat-ia'); ?></span>
            </button>
            <hr class="fls-shell__vista-divider">
            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-config-vistas')); ?>" class="fls-shell__vista-option">
                <span class="fls-shell__vista-check"></span>
                <span>⚙️ <?php esc_html_e('Configurar vistas', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="fls-shell__nav" role="navigation" aria-label="<?php esc_attr_e('Menú principal', 'flavor-chat-ia'); ?>">
        <?php foreach ($navigation as $section_id => $section) : ?>
            <div class="fls-shell__section">
                <div class="fls-shell__section-title">
                    <?php echo esc_html($section['label']); ?>
                </div>

                <ul class="fls-shell__menu" role="menu">
                    <?php foreach ($section['items'] as $item) : ?>
                        <li class="fls-shell__menu-item" role="none">
                            <a
                                href="<?php echo esc_url(admin_url('admin.php?page=' . $item['slug'])); ?>"
                                class="fls-shell__menu-link<?php echo $current_page === $item['slug'] ? ' fls-shell__menu-link--active' : ''; ?>"
                                role="menuitem"
                                data-tooltip="<?php echo esc_attr($item['label']); ?>"
                                <?php if ($current_page === $item['slug']) : ?>
                                    aria-current="page"
                                <?php endif; ?>
                            >
                                <span class="fls-shell__menu-icon">
                                    <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                                </span>
                                <span class="fls-shell__menu-text">
                                    <?php echo esc_html($item['label']); ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Footer -->
    <div class="fls-shell__footer">
        <!-- Dark Mode Toggle -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            :class="{ 'fls-shell__footer-btn--active': darkMode }"
            @click="toggleDarkMode()"
            data-tooltip="<?php echo esc_attr__('Modo oscuro', 'flavor-chat-ia'); ?>"
            aria-label="<?php esc_attr_e('Alternar modo oscuro', 'flavor-chat-ia'); ?>"
        >
            <span class="dashicons" :class="darkMode ? 'dashicons-lightbulb' : 'dashicons-admin-generic'"></span>
        </button>

        <!-- Back to WordPress -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            @click="goToWPDashboard()"
            data-tooltip="<?php echo esc_attr__('Volver a WordPress', 'flavor-chat-ia'); ?>"
            aria-label="<?php esc_attr_e('Volver al escritorio de WordPress', 'flavor-chat-ia'); ?>"
        >
            <span class="dashicons dashicons-wordpress"></span>
        </button>

        <!-- Disable Shell -->
        <button
            type="button"
            class="fls-shell__footer-btn"
            @click="disableShell()"
            data-tooltip="<?php echo esc_attr__('Desactivar shell', 'flavor-chat-ia'); ?>"
            aria-label="<?php esc_attr_e('Desactivar shell de navegación', 'flavor-chat-ia'); ?>"
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
                <?php esc_html_e('Editar perfil', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('profile.php#password')); ?>" class="fls-shell__user-option">
                <span class="dashicons dashicons-lock"></span>
                <?php esc_html_e('Cambiar contraseña', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('options-general.php')); ?>" class="fls-shell__user-option">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php esc_html_e('Ajustes', 'flavor-chat-ia'); ?>
            </a>
            <hr class="fls-shell__user-divider">
            <a href="<?php echo esc_url(wp_logout_url(admin_url())); ?>" class="fls-shell__user-option fls-shell__user-option--logout">
                <span class="dashicons dashicons-exit"></span>
                <?php esc_html_e('Cerrar sesión', 'flavor-chat-ia'); ?>
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
