<?php
/**
 * Template principal del dashboard "Mi Cuenta"
 *
 * Variables disponibles:
 *   $usuario                 - WP_User objeto del usuario actual
 *   $avatar_url              - URL del avatar del usuario
 *   $nombre_completo         - Nombre completo del usuario
 *   $tabs                    - Array de tabs disponibles
 *   $tab_activo              - Slug del tab actualmente activo
 *   $notificaciones_sin_leer - Cantidad de notificaciones sin leer
 *   $dashboard_instance      - Instancia de Flavor_User_Dashboard
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="flavor-dashboard" data-active-tab="<?php echo esc_attr($tab_activo); ?>">

    <!-- Sidebar lateral -->
    <aside class="flavor-dashboard-sidebar">

        <!-- Informacion del usuario -->
        <div class="flavor-dashboard-user-info">
            <div class="flavor-dashboard-avatar">
                <img src="<?php echo esc_url($avatar_url); ?>"
                     alt="<?php echo esc_attr($nombre_completo); ?>"
                     width="64"
                     height="64" />
            </div>
            <div class="flavor-dashboard-user-details">
                <h3 class="flavor-dashboard-user-name"><?php echo esc_html($nombre_completo); ?></h3>
                <p class="flavor-dashboard-user-email"><?php echo esc_html($usuario->user_email); ?></p>
            </div>
        </div>

        <!-- Navegacion por tabs -->
        <nav class="flavor-dashboard-nav" role="navigation" aria-label="<?php esc_attr_e('Menu de Mi Cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <ul class="flavor-dashboard-nav-list">
                <?php foreach ($tabs as $slug_tab => $configuracion_tab) : ?>
                    <?php
                    $esta_activo       = ($slug_tab === $tab_activo);
                    $clases_css_tab    = 'flavor-dashboard-nav-item';
                    $url_tab           = $dashboard_instance->obtener_url_tab($slug_tab);

                    if ($esta_activo) {
                        $clases_css_tab .= ' flavor-dashboard-nav-item--activo';
                    }
                    ?>
                    <li class="<?php echo esc_attr($clases_css_tab); ?>">
                        <a href="<?php echo esc_url($url_tab); ?>"
                           class="flavor-dashboard-nav-link"
                           data-tab="<?php echo esc_attr($slug_tab); ?>"
                           <?php if ($esta_activo) : ?>aria-current="page"<?php endif; ?>>
                            <span class="flavor-dashboard-nav-icon">
                                <?php echo $dashboard_instance->obtener_icono_svg($configuracion_tab['icon'] ?? 'file'); ?>
                            </span>
                            <span class="flavor-dashboard-nav-label">
                                <?php echo esc_html($configuracion_tab['label']); ?>
                            </span>
                            <?php if ($slug_tab === 'notificaciones' && $notificaciones_sin_leer > 0) : ?>
                                <span class="flavor-dashboard-badge" id="flavor-badge-notificaciones">
                                    <?php echo intval($notificaciones_sin_leer); ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>

                <!-- Enlace de cerrar sesion -->
                <li class="flavor-dashboard-nav-item flavor-dashboard-nav-item--logout">
                    <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="flavor-dashboard-nav-link">
                        <span class="flavor-dashboard-nav-icon">
                            <?php echo $dashboard_instance->obtener_icono_svg('logout'); ?>
                        </span>
                        <span class="flavor-dashboard-nav-label">
                            <?php esc_html_e('Cerrar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </span>
                    </a>
                </li>
            </ul>
        </nav>
    </aside>

    <!-- Contenido principal -->
    <main class="flavor-dashboard-content" id="flavor-dashboard-content">
        <!-- Mensaje de feedback -->
        <div class="flavor-dashboard-mensaje" id="flavor-dashboard-mensaje" style="display:none;" role="alert"></div>

        <!-- Contenido del tab activo -->
        <div class="flavor-dashboard-tab-content" id="flavor-tab-<?php echo esc_attr($tab_activo); ?>">
            <?php $dashboard_instance->render_contenido_tab($tab_activo); ?>
        </div>
    </main>

</div>
