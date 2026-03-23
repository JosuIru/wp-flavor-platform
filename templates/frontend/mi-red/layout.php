<?php
/**
 * Layout Principal - Mi Red Social
 *
 * Layout de 3 columnas responsive tipo red social moderna.
 *
 * Variables disponibles:
 * - $usuario: array con datos del usuario actual
 * - $vista_actual: string con la vista actual
 * - $vistas: array con todas las vistas disponibles
 * - $content_types: array con tipos de contenido
 * - $notificaciones_no_leidas: int
 * - $mensajes_no_leidos: int
 * - $base_url: URL base de mi-red
 * - $datos_vista: array con datos específicos de la vista
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Detectar si estamos dentro del sistema de dynamic-pages
// (no necesitamos get_header/get_footer ya que dynamic-pages genera el HTML completo)
$in_dynamic_pages = did_action('flavor_app_render') > 0 || (isset($GLOBALS['flavor_dynamic_pages']) && $GLOBALS['flavor_dynamic_pages']);

if (!$in_dynamic_pages) {
    // Standalone: usar header/footer del tema
    get_header();
}
?>

<div class="mi-red-container">
    <!-- Header Mobile -->
    <header class="mi-red-header-mobile">
        <div class="mi-red-header-mobile__left">
            <a href="<?php echo esc_url($base_url); ?>" class="mi-red-logo">
                <span class="mi-red-logo__icon">🌐</span>
                <span class="mi-red-logo__text">Mi Red</span>
            </a>
        </div>
        <div class="mi-red-header-mobile__right">
            <button class="mi-red-btn-icon" id="btn-buscar-mobile" aria-label="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                <span>🔍</span>
            </button>
            <a href="<?php echo esc_url($base_url . 'notificaciones/'); ?>" class="mi-red-btn-icon mi-red-btn-icon--badge" aria-label="<?php esc_attr_e('Notificaciones', 'flavor-chat-ia'); ?>">
                <span>🔔</span>
                <?php if ($notificaciones_no_leidas > 0) : ?>
                    <span class="mi-red-badge"><?php echo esc_html($notificaciones_no_leidas); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url($base_url . 'mensajes/'); ?>" class="mi-red-btn-icon mi-red-btn-icon--badge" aria-label="<?php esc_attr_e('Mensajes', 'flavor-chat-ia'); ?>">
                <span>💬</span>
                <?php if ($mensajes_no_leidos > 0) : ?>
                    <span class="mi-red-badge"><?php echo esc_html($mensajes_no_leidos); ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <div class="mi-red-layout">
        <!-- Sidebar Izquierdo (Desktop) -->
        <aside class="mi-red-sidebar mi-red-sidebar--left">
            <!-- Perfil resumido -->
            <div class="mi-red-profile-card">
                <a href="<?php echo esc_url($usuario['perfil_url']); ?>" class="mi-red-profile-card__avatar">
                    <img src="<?php echo esc_url($usuario['avatar']); ?>" alt="<?php echo esc_attr($usuario['nombre']); ?>">
                </a>
                <div class="mi-red-profile-card__info">
                    <a href="<?php echo esc_url($usuario['perfil_url']); ?>" class="mi-red-profile-card__name">
                        <?php echo esc_html($usuario['nombre']); ?>
                    </a>
                    <span class="mi-red-profile-card__link"><?php esc_html_e('Ver perfil', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <!-- Navegación principal -->
            <nav class="mi-red-nav" aria-label="<?php esc_attr_e('Navegación principal', 'flavor-chat-ia'); ?>">
                <ul class="mi-red-nav__list">
                    <?php foreach ($vistas as $key => $vista) : ?>
                        <?php
                        $is_active = ($vista_actual === $key);
                        $url = $base_url . ($vista['slug'] ? $vista['slug'] . '/' : '');
                        ?>
                        <li class="mi-red-nav__item">
                            <a href="<?php echo esc_url($url); ?>"
                               class="mi-red-nav__link <?php echo $is_active ? 'mi-red-nav__link--active' : ''; ?>">
                                <span class="mi-red-nav__icon"><?php echo $vista['icon']; ?></span>
                                <span class="mi-red-nav__text"><?php echo esc_html($vista['label']); ?></span>
                                <?php if ($key === 'notificaciones' && $notificaciones_no_leidas > 0) : ?>
                                    <span class="mi-red-badge mi-red-badge--small"><?php echo esc_html($notificaciones_no_leidas); ?></span>
                                <?php elseif ($key === 'mensajes' && $mensajes_no_leidos > 0) : ?>
                                    <span class="mi-red-badge mi-red-badge--small"><?php echo esc_html($mensajes_no_leidos); ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </nav>

            <!-- Filtros por tipo de contenido -->
            <div class="mi-red-filters">
                <h3 class="mi-red-filters__title"><?php esc_html_e('Filtrar por tipo', 'flavor-chat-ia'); ?></h3>
                <div class="mi-red-filters__list" id="filtros-tipo">
                    <button class="mi-red-filter-btn mi-red-filter-btn--active" data-tipo="todos">
                        <span class="mi-red-filter-btn__icon">📋</span>
                        <span class="mi-red-filter-btn__text"><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></span>
                    </button>
                    <?php foreach ($content_types as $tipo_key => $tipo) : ?>
                        <button class="mi-red-filter-btn" data-tipo="<?php echo esc_attr($tipo_key); ?>">
                            <span class="mi-red-filter-btn__icon"><?php echo $tipo['icon']; ?></span>
                            <span class="mi-red-filter-btn__text"><?php echo esc_html($tipo['label']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Botón publicar (Desktop) -->
            <a href="<?php echo esc_url($base_url . 'publicar/'); ?>" class="mi-red-btn-publicar">
                <span class="mi-red-btn-publicar__icon">✏️</span>
                <span class="mi-red-btn-publicar__text"><?php esc_html_e('Publicar', 'flavor-chat-ia'); ?></span>
            </a>
        </aside>

        <!-- Contenido Principal -->
        <main class="mi-red-main" id="contenido-principal">
            <?php
            // Incluir el template de la vista actual
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                echo '<div class="mi-red-error">';
                esc_html_e('Vista no encontrada', 'flavor-chat-ia');
                echo '</div>';
            }
            ?>
        </main>

        <!-- Sidebar Derecho (Desktop) -->
        <aside class="mi-red-sidebar mi-red-sidebar--right">
            <!-- Buscador -->
            <div class="mi-red-search-box">
                <form action="<?php echo esc_url($base_url . 'buscar/'); ?>" method="get" class="mi-red-search-form">
                    <input type="search"
                           name="q"
                           class="mi-red-search-input"
                           placeholder="<?php esc_attr_e('Buscar en Mi Red...', 'flavor-chat-ia'); ?>"
                           aria-label="<?php esc_attr_e('Buscar', 'flavor-chat-ia'); ?>">
                    <button type="submit" class="mi-red-search-btn">
                        <span>🔍</span>
                    </button>
                </form>
            </div>

            <!-- Trending -->
            <?php if (!empty($datos_vista['trending'])) : ?>
                <div class="mi-red-widget">
                    <h3 class="mi-red-widget__title">
                        <span class="mi-red-widget__icon">🔥</span>
                        <?php esc_html_e('Tendencias', 'flavor-chat-ia'); ?>
                    </h3>
                    <ul class="mi-red-trending-list">
                        <?php foreach ($datos_vista['trending'] as $hashtag) : ?>
                            <li class="mi-red-trending-item">
                                <a href="<?php echo esc_url($base_url . 'buscar/?q=' . urlencode('#' . $hashtag['hashtag'])); ?>" class="mi-red-trending-link">
                                    <span class="mi-red-trending-tag">#<?php echo esc_html($hashtag['hashtag']); ?></span>
                                    <span class="mi-red-trending-count"><?php echo esc_html(number_format_i18n($hashtag['total_usos'])); ?> <?php esc_html_e('publicaciones', 'flavor-chat-ia'); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Sugerencias de usuarios -->
            <?php if (!empty($datos_vista['sugerencias'])) : ?>
                <div class="mi-red-widget">
                    <h3 class="mi-red-widget__title">
                        <span class="mi-red-widget__icon">👥</span>
                        <?php esc_html_e('Personas que quizás conozcas', 'flavor-chat-ia'); ?>
                    </h3>
                    <ul class="mi-red-suggestions-list">
                        <?php foreach ($datos_vista['sugerencias'] as $sugerencia) : ?>
                            <li class="mi-red-suggestion-item">
                                <a href="<?php echo esc_url($sugerencia['url']); ?>" class="mi-red-suggestion-link">
                                    <img src="<?php echo esc_url($sugerencia['avatar']); ?>"
                                         alt="<?php echo esc_attr($sugerencia['display_name']); ?>"
                                         class="mi-red-suggestion-avatar">
                                    <div class="mi-red-suggestion-info">
                                        <span class="mi-red-suggestion-name"><?php echo esc_html($sugerencia['display_name']); ?></span>
                                        <span class="mi-red-suggestion-followers">
                                            <?php echo esc_html(number_format_i18n($sugerencia['seguidores'])); ?>
                                            <?php esc_html_e('seguidores', 'flavor-chat-ia'); ?>
                                        </span>
                                    </div>
                                </a>
                                <button class="mi-red-btn-follow"
                                        data-usuario="<?php echo esc_attr($sugerencia['ID']); ?>"
                                        aria-label="<?php esc_attr_e('Seguir', 'flavor-chat-ia'); ?>">
                                    <?php esc_html_e('Seguir', 'flavor-chat-ia'); ?>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Enlaces rápidos -->
            <div class="mi-red-widget mi-red-widget--links">
                <ul class="mi-red-quick-links">
                    <li><a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('', '')); ?>"><?php esc_html_e('Mi Portal', 'flavor-chat-ia'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/red-social/')); ?>"><?php esc_html_e('Red Social', 'flavor-chat-ia'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/comunidades/')); ?>"><?php esc_html_e('Comunidades', 'flavor-chat-ia'); ?></a></li>
                    <li><a href="<?php echo esc_url(home_url('/foros/')); ?>"><?php esc_html_e('Foros', 'flavor-chat-ia'); ?></a></li>
                </ul>
                <p class="mi-red-copyright">
                    &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>
                </p>
            </div>
        </aside>
    </div>

    <!-- Navegación Bottom (Mobile) -->
    <nav class="mi-red-bottom-nav" aria-label="<?php esc_attr_e('Navegación móvil', 'flavor-chat-ia'); ?>">
        <a href="<?php echo esc_url($base_url); ?>" class="mi-red-bottom-nav__item <?php echo $vista_actual === 'feed' ? 'mi-red-bottom-nav__item--active' : ''; ?>">
            <span class="mi-red-bottom-nav__icon">🏠</span>
            <span class="mi-red-bottom-nav__text"><?php esc_html_e('Inicio', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url($base_url . 'explorar/'); ?>" class="mi-red-bottom-nav__item <?php echo $vista_actual === 'explorar' ? 'mi-red-bottom-nav__item--active' : ''; ?>">
            <span class="mi-red-bottom-nav__icon">🔍</span>
            <span class="mi-red-bottom-nav__text"><?php esc_html_e('Explorar', 'flavor-chat-ia'); ?></span>
        </a>
        <a href="<?php echo esc_url($base_url . 'publicar/'); ?>" class="mi-red-bottom-nav__item mi-red-bottom-nav__item--publicar">
            <span class="mi-red-bottom-nav__icon">➕</span>
        </a>
        <a href="<?php echo esc_url($base_url . 'mensajes/'); ?>" class="mi-red-bottom-nav__item <?php echo $vista_actual === 'mensajes' ? 'mi-red-bottom-nav__item--active' : ''; ?>">
            <span class="mi-red-bottom-nav__icon">💬</span>
            <span class="mi-red-bottom-nav__text"><?php esc_html_e('Mensajes', 'flavor-chat-ia'); ?></span>
            <?php if ($mensajes_no_leidos > 0) : ?>
                <span class="mi-red-badge mi-red-badge--nav"><?php echo esc_html($mensajes_no_leidos); ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo esc_url($base_url . 'perfil/'); ?>" class="mi-red-bottom-nav__item <?php echo $vista_actual === 'perfil' ? 'mi-red-bottom-nav__item--active' : ''; ?>">
            <span class="mi-red-bottom-nav__icon">👤</span>
            <span class="mi-red-bottom-nav__text"><?php esc_html_e('Perfil', 'flavor-chat-ia'); ?></span>
        </a>
    </nav>

    <!-- Modal de búsqueda mobile -->
    <div class="mi-red-modal mi-red-modal--buscar" id="modal-buscar" hidden>
        <div class="mi-red-modal__backdrop"></div>
        <div class="mi-red-modal__content">
            <form action="<?php echo esc_url($base_url . 'buscar/'); ?>" method="get" class="mi-red-search-form mi-red-search-form--modal">
                <input type="search"
                       name="q"
                       class="mi-red-search-input"
                       placeholder="<?php esc_attr_e('Buscar personas, publicaciones, hashtags...', 'flavor-chat-ia'); ?>"
                       autofocus>
                <button type="button" class="mi-red-modal__close" aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">✕</button>
            </form>
            <div class="mi-red-search-results" id="resultados-busqueda"></div>
        </div>
    </div>
</div>

<?php
if (!$in_dynamic_pages) {
    get_footer();
}
?>
