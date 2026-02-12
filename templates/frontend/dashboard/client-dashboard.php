<?php
/**
 * Template del Dashboard de Cliente
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
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tema_usuario = $preferencias['tema'] ?? 'auto';
$clase_tema = $tema_usuario === 'dark' ? 'flavor-dark' : ($tema_usuario === 'light' ? 'flavor-light' : '');
?>

<div class="flavor-client-dashboard <?php echo esc_attr($clase_tema); ?>"
     data-theme="<?php echo esc_attr($tema_usuario); ?>"
     data-user-id="<?php echo esc_attr($usuario->ID); ?>">

    <!-- Header del Dashboard -->
    <header class="flavor-client-dashboard__header">
        <div class="flavor-client-dashboard__header-content">
            <div class="flavor-client-dashboard__user">
                <div class="flavor-client-dashboard__avatar">
                    <img src="<?php echo esc_url($avatar_url); ?>"
                         alt="<?php echo esc_attr($nombre_usuario); ?>"
                         width="64"
                         height="64"
                         loading="lazy" />
                </div>
                <div class="flavor-client-dashboard__greeting">
                    <p class="flavor-client-dashboard__saludo"><?php echo esc_html($saludo); ?>,</p>
                    <h1 class="flavor-client-dashboard__nombre"><?php echo esc_html($nombre_usuario); ?></h1>
                </div>
            </div>

            <div class="flavor-client-dashboard__header-actions">
                <!-- Boton de refrescar -->
                <button type="button"
                        class="flavor-client-dashboard__btn-icon"
                        id="flavor-dashboard-refresh"
                        title="<?php esc_attr_e('Actualizar (Ctrl+R)', 'flavor-chat-ia'); ?>"
                        aria-label="<?php esc_attr_e('Actualizar datos', 'flavor-chat-ia'); ?>">
                    <?php echo $dashboard_instance->obtener_icono_svg('refresh', 20); ?>
                </button>

                <!-- Toggle de tema -->
                <button type="button"
                        class="flavor-client-dashboard__btn-icon"
                        id="flavor-dashboard-theme-toggle"
                        title="<?php esc_attr_e('Cambiar tema', 'flavor-chat-ia'); ?>"
                        aria-label="<?php esc_attr_e('Cambiar tema claro/oscuro', 'flavor-chat-ia'); ?>">
                    <span class="flavor-icon-sun"><?php echo $dashboard_instance->obtener_icono_svg('sun', 20); ?></span>
                    <span class="flavor-icon-moon"><?php echo $dashboard_instance->obtener_icono_svg('moon', 20); ?></span>
                </button>

                <!-- Notificaciones -->
                <?php if (!empty($notificaciones)) : ?>
                    <button type="button"
                            class="flavor-client-dashboard__btn-icon flavor-client-dashboard__btn-icon--notifications"
                            id="flavor-dashboard-notifications-toggle"
                            title="<?php esc_attr_e('Notificaciones', 'flavor-chat-ia'); ?>"
                            aria-label="<?php esc_attr_e('Ver notificaciones', 'flavor-chat-ia'); ?>"
                            aria-expanded="false"
                            aria-controls="flavor-notifications-panel">
                        <?php echo $dashboard_instance->obtener_icono_svg('bell', 20); ?>
                        <span class="flavor-client-dashboard__notification-badge"><?php echo count($notificaciones); ?></span>
                    </button>
                <?php endif; ?>

                <!-- Enlace a Mi Cuenta -->
                <a href="<?php echo esc_url(home_url('/mi-cuenta/')); ?>"
                   class="flavor-client-dashboard__btn-icon"
                   title="<?php esc_attr_e('Mi Cuenta', 'flavor-chat-ia'); ?>">
                    <?php echo $dashboard_instance->obtener_icono_svg('settings', 20); ?>
                </a>
            </div>
        </div>

        <!-- Fecha actual -->
        <div class="flavor-client-dashboard__date">
            <span class="flavor-client-dashboard__date-text">
                <?php echo esc_html(date_i18n('l, j \d\e F \d\e Y')); ?>
            </span>
        </div>
    </header>

    <!-- Panel de Notificaciones (colapsable) -->
    <?php if ($atributos['mostrar_notificaciones'] === 'true' && !empty($notificaciones)) : ?>
        <aside class="flavor-client-dashboard__notifications-panel"
               id="flavor-notifications-panel"
               aria-hidden="true">
            <div class="flavor-client-dashboard__notifications-header">
                <h3><?php esc_html_e('Notificaciones', 'flavor-chat-ia'); ?></h3>
                <button type="button"
                        class="flavor-client-dashboard__btn-text"
                        id="flavor-mark-all-read">
                    <?php esc_html_e('Marcar todas como leidas', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <ul class="flavor-client-dashboard__notifications-list">
                <?php foreach ($notificaciones as $notificacion) :
                    $titulo_notificacion = $notificacion['title'] ?? __('Notificacion', 'flavor-chat-ia');
                    $mensaje_notificacion = $notificacion['message'] ?? '';
                    $fecha_notificacion = isset($notificacion['created_at']) ?
                        $dashboard_instance->formatear_fecha_relativa($notificacion['created_at']) : '';
                    $id_notificacion = $notificacion['id'] ?? 0;
                    ?>
                    <li class="flavor-client-dashboard__notification-item"
                        data-notification-id="<?php echo esc_attr($id_notificacion); ?>">
                        <div class="flavor-client-dashboard__notification-content">
                            <span class="flavor-client-dashboard__notification-title">
                                <?php echo esc_html($titulo_notificacion); ?>
                            </span>
                            <?php if ($mensaje_notificacion) : ?>
                                <span class="flavor-client-dashboard__notification-message">
                                    <?php echo esc_html(wp_trim_words($mensaje_notificacion, 15)); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($fecha_notificacion) : ?>
                                <span class="flavor-client-dashboard__notification-time">
                                    <?php echo esc_html($fecha_notificacion); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <button type="button"
                                class="flavor-client-dashboard__notification-dismiss"
                                data-notification-id="<?php echo esc_attr($id_notificacion); ?>"
                                aria-label="<?php esc_attr_e('Descartar', 'flavor-chat-ia'); ?>">
                            <?php echo $dashboard_instance->obtener_icono_svg('x', 16); ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
            <a href="<?php echo esc_url(home_url('/mi-cuenta/?tab=notificaciones')); ?>"
               class="flavor-client-dashboard__notifications-footer">
                <?php esc_html_e('Ver todas las notificaciones', 'flavor-chat-ia'); ?>
            </a>
        </aside>
    <?php endif; ?>

    <!-- Grid de Estadisticas -->
    <?php if ($atributos['mostrar_estadisticas'] === 'true' && !empty($estadisticas)) : ?>
        <section class="flavor-client-dashboard__stats" aria-labelledby="stats-heading">
            <h2 id="stats-heading" class="screen-reader-text">
                <?php esc_html_e('Resumen de tu actividad', 'flavor-chat-ia'); ?>
            </h2>
            <div class="flavor-client-dashboard__stats-grid">
                <?php foreach ($estadisticas as $identificador => $estadistica) :
                    $url_estadistica = $estadistica['url'] ?? '#';
                    $color_estadistica = $estadistica['color'] ?? 'primary';
                    ?>
                    <a href="<?php echo esc_url($url_estadistica); ?>"
                       class="flavor-client-dashboard__stat-card flavor-client-dashboard__stat-card--<?php echo esc_attr($color_estadistica); ?>"
                       data-stat-id="<?php echo esc_attr($identificador); ?>">
                        <div class="flavor-client-dashboard__stat-icon">
                            <?php echo $dashboard_instance->obtener_icono_svg($estadistica['icon'] ?? 'chart', 28); ?>
                        </div>
                        <div class="flavor-client-dashboard__stat-content">
                            <span class="flavor-client-dashboard__stat-value" data-value="<?php echo esc_attr($estadistica['valor']); ?>">
                                <?php echo esc_html(number_format_i18n($estadistica['valor'])); ?>
                            </span>
                            <span class="flavor-client-dashboard__stat-label">
                                <?php echo esc_html($estadistica['label']); ?>
                            </span>
                            <?php if (!empty($estadistica['texto'])) : ?>
                                <span class="flavor-client-dashboard__stat-meta">
                                    <?php echo esc_html($estadistica['texto']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Atajos Rapidos -->
    <?php if ($atributos['mostrar_atajos'] === 'true' && !empty($atajos)) : ?>
        <section class="flavor-client-dashboard__shortcuts" aria-labelledby="shortcuts-heading">
            <h2 id="shortcuts-heading" class="flavor-client-dashboard__section-title">
                <?php esc_html_e('Acciones Rapidas', 'flavor-chat-ia'); ?>
            </h2>
            <div class="flavor-client-dashboard__shortcuts-grid">
                <?php foreach ($atajos as $identificador => $atajo) :
                    $color_atajo = $atajo['color'] ?? 'secondary';
                    $target_atajo = $atajo['target'] ?? '_self';
                    ?>
                    <a href="<?php echo esc_url($atajo['url']); ?>"
                       class="flavor-client-dashboard__shortcut flavor-client-dashboard__shortcut--<?php echo esc_attr($color_atajo); ?>"
                       target="<?php echo esc_attr($target_atajo); ?>"
                       data-shortcut-id="<?php echo esc_attr($identificador); ?>">
                        <span class="flavor-client-dashboard__shortcut-icon">
                            <?php echo $dashboard_instance->obtener_icono_svg($atajo['icon'] ?? 'link', 20); ?>
                        </span>
                        <span class="flavor-client-dashboard__shortcut-label">
                            <?php echo esc_html($atajo['label']); ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Contenido Principal: Widgets y Actividad -->
    <div class="flavor-client-dashboard__main">

        <!-- Widgets -->
        <?php if ($atributos['mostrar_widgets'] === 'true' && !empty($widgets)) : ?>
            <section class="flavor-client-dashboard__widgets" aria-labelledby="widgets-heading">
                <h2 id="widgets-heading" class="screen-reader-text">
                    <?php esc_html_e('Widgets del dashboard', 'flavor-chat-ia'); ?>
                </h2>
                <div class="flavor-client-dashboard__widgets-grid"
                     data-columns="<?php echo esc_attr($atributos['columnas_widgets']); ?>">
                    <?php foreach ($widgets as $identificador => $widget) :
                        $colapsado = in_array($identificador, $preferencias['widgets_colapsados'] ?? [], true);
                        $tamano_widget = $widget['size'] ?? 'medium';
                        ?>
                        <article class="flavor-client-dashboard__widget flavor-client-dashboard__widget--<?php echo esc_attr($tamano_widget); ?> <?php echo $colapsado ? 'flavor-client-dashboard__widget--collapsed' : ''; ?>"
                                 data-widget-id="<?php echo esc_attr($identificador); ?>"
                                 data-collapsed="<?php echo $colapsado ? 'true' : 'false'; ?>">
                            <header class="flavor-client-dashboard__widget-header">
                                <div class="flavor-client-dashboard__widget-title-wrapper">
                                    <span class="flavor-client-dashboard__widget-icon">
                                        <?php echo $dashboard_instance->obtener_icono_svg($widget['icon'] ?? 'box', 18); ?>
                                    </span>
                                    <h3 class="flavor-client-dashboard__widget-title">
                                        <?php echo esc_html($widget['title']); ?>
                                    </h3>
                                </div>
                                <div class="flavor-client-dashboard__widget-actions">
                                    <button type="button"
                                            class="flavor-client-dashboard__widget-refresh"
                                            data-widget-id="<?php echo esc_attr($identificador); ?>"
                                            aria-label="<?php esc_attr_e('Actualizar widget', 'flavor-chat-ia'); ?>">
                                        <?php echo $dashboard_instance->obtener_icono_svg('refresh', 16); ?>
                                    </button>
                                    <button type="button"
                                            class="flavor-client-dashboard__widget-toggle"
                                            data-widget-id="<?php echo esc_attr($identificador); ?>"
                                            aria-expanded="<?php echo $colapsado ? 'false' : 'true'; ?>"
                                            aria-label="<?php echo $colapsado ? esc_attr__('Expandir widget', 'flavor-chat-ia') : esc_attr__('Colapsar widget', 'flavor-chat-ia'); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6 9 12 15 18 9"/>
                                        </svg>
                                    </button>
                                </div>
                            </header>
                            <div class="flavor-client-dashboard__widget-content"
                                 id="widget-content-<?php echo esc_attr($identificador); ?>">
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
            <aside class="flavor-client-dashboard__activity" aria-labelledby="activity-heading">
                <header class="flavor-client-dashboard__activity-header">
                    <h2 id="activity-heading" class="flavor-client-dashboard__section-title">
                        <?php esc_html_e('Actividad Reciente', 'flavor-chat-ia'); ?>
                    </h2>
                    <a href="<?php echo esc_url(home_url('/mi-cuenta/?tab=actividad')); ?>"
                       class="flavor-client-dashboard__btn-text">
                        <?php esc_html_e('Ver todo', 'flavor-chat-ia'); ?>
                    </a>
                </header>

                <div class="flavor-client-dashboard__activity-content" id="flavor-activity-timeline">
                    <?php if (!empty($actividad_reciente)) : ?>
                        <ul class="flavor-client-dashboard__timeline">
                            <?php foreach ($actividad_reciente as $item_actividad) :
                                $icono_actividad = $item_actividad['icon'] ?? 'activity';
                                $tipo_actividad = $item_actividad['tipo'] ?? 'default';
                                $fecha_actividad = isset($item_actividad['fecha']) ?
                                    $dashboard_instance->formatear_fecha_relativa($item_actividad['fecha']) : '';
                                ?>
                                <li class="flavor-client-dashboard__timeline-item flavor-client-dashboard__timeline-item--<?php echo esc_attr($tipo_actividad); ?>">
                                    <span class="flavor-client-dashboard__timeline-icon">
                                        <?php echo $dashboard_instance->obtener_icono_svg($icono_actividad, 16); ?>
                                    </span>
                                    <div class="flavor-client-dashboard__timeline-content">
                                        <span class="flavor-client-dashboard__timeline-text">
                                            <?php echo esc_html($item_actividad['texto'] ?? ''); ?>
                                        </span>
                                        <?php if ($fecha_actividad) : ?>
                                            <time class="flavor-client-dashboard__timeline-time">
                                                <?php echo esc_html($fecha_actividad); ?>
                                            </time>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item_actividad['url'])) : ?>
                                        <a href="<?php echo esc_url($item_actividad['url']); ?>"
                                           class="flavor-client-dashboard__timeline-link"
                                           aria-label="<?php esc_attr_e('Ver detalle', 'flavor-chat-ia'); ?>">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="9 18 15 12 9 6"/>
                                            </svg>
                                        </a>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else : ?>
                        <div class="flavor-client-dashboard__empty-state">
                            <div class="flavor-client-dashboard__empty-icon">
                                <?php echo $dashboard_instance->obtener_icono_svg('activity', 48); ?>
                            </div>
                            <p class="flavor-client-dashboard__empty-text">
                                <?php esc_html_e('No hay actividad reciente para mostrar', 'flavor-chat-ia'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </aside>
        <?php endif; ?>

    </div>

    <!-- Toast de notificaciones -->
    <div class="flavor-client-dashboard__toasts" id="flavor-dashboard-toasts" aria-live="polite"></div>

    <!-- Indicador de carga -->
    <div class="flavor-client-dashboard__loading" id="flavor-dashboard-loading" aria-hidden="true">
        <div class="flavor-client-dashboard__loading-spinner"></div>
        <span class="flavor-client-dashboard__loading-text"><?php esc_html_e('Actualizando...', 'flavor-chat-ia'); ?></span>
    </div>

</div>
