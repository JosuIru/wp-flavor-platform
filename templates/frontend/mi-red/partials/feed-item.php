<?php
/**
 * Feed Item - Tarjeta de contenido universal
 *
 * Renderiza un item del feed unificado con formato normalizado.
 *
 * Variables disponibles:
 * - $item: array normalizado del item
 *   - id, tipo, tipo_info, origen, autor, contenido, multimedia, contexto, interacciones, fecha, fecha_humana, url
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer datos del item
$item_id = $item['id'] ?? 0;
$tipo = $item['tipo'] ?? 'publicacion';
$tipo_info = $item['tipo_info'] ?? [];
$origen = $item['origen'] ?? '';
$autor = $item['autor'] ?? [];
$contenido = $item['contenido'] ?? [];
$multimedia = $item['multimedia'] ?? [];
$contexto = $item['contexto'] ?? [];
$interacciones = $item['interacciones'] ?? [];
$fecha_humana = $item['fecha_humana'] ?? '';
$url = $item['url'] ?? '#';

// Clases del item
$item_classes = [
    'mi-red-card',
    'mi-red-card--' . $tipo,
];
?>

<article class="<?php echo esc_attr(implode(' ', $item_classes)); ?>"
         data-item-id="<?php echo esc_attr($item_id); ?>"
         data-tipo="<?php echo esc_attr($tipo); ?>"
         data-origen="<?php echo esc_attr($origen); ?>">

    <!-- Cabecera -->
    <header class="mi-red-card__header">
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_item_url('mi-red', $autor['id'], 'perfil')); ?>" class="mi-red-card__avatar">
            <img src="<?php echo esc_url($autor['avatar']); ?>" alt="<?php echo esc_attr($autor['nombre']); ?>" loading="lazy">
        </a>

        <div class="mi-red-card__meta">
            <div class="mi-red-card__author-line">
                <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_item_url('mi-red', $autor['id'], 'perfil')); ?>" class="mi-red-card__author-name">
                    <?php echo esc_html($autor['nombre']); ?>
                </a>
                <span class="mi-red-card__badge" style="--badge-color: <?php echo esc_attr($tipo_info['color'] ?? '#6b7280'); ?>">
                    <span class="mi-red-card__badge-icon"><?php echo $tipo_info['icon'] ?? '📄'; ?></span>
                    <span class="mi-red-card__badge-label"><?php echo esc_html($tipo_info['label'] ?? ucfirst($tipo)); ?></span>
                </span>
            </div>

            <div class="mi-red-card__info-line">
                <time datetime="<?php echo esc_attr($item['fecha']); ?>" class="mi-red-card__time">
                    <?php echo esc_html($fecha_humana); ?>
                </time>

                <?php if (!empty($contexto)) : ?>
                    <?php if (!empty($contexto['comunidad_nombre'])) : ?>
                        <span class="mi-red-card__context">
                            <?php esc_html_e('en', 'flavor-chat-ia'); ?>
                            <a href="<?php echo esc_url(home_url('/comunidades/' . $contexto['comunidad_id'] . '/')); ?>">
                                <?php echo $contexto['comunidad_icono'] ?? '👥'; ?>
                                <?php echo esc_html($contexto['comunidad_nombre']); ?>
                            </a>
                        </span>
                    <?php elseif (!empty($contexto['foro_nombre'])) : ?>
                        <span class="mi-red-card__context">
                            <?php esc_html_e('en foro', 'flavor-chat-ia'); ?>
                            <a href="#"><?php echo esc_html($contexto['foro_nombre']); ?></a>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mi-red-card__actions-menu">
            <button class="mi-red-card__menu-btn" aria-label="<?php esc_attr_e('Más opciones', 'flavor-chat-ia'); ?>">
                ⋮
            </button>
            <div class="mi-red-card__dropdown" hidden>
                <button data-accion="guardar"><?php esc_html_e('Guardar', 'flavor-chat-ia'); ?></button>
                <button data-accion="ocultar"><?php esc_html_e('No me interesa', 'flavor-chat-ia'); ?></button>
                <button data-accion="reportar"><?php esc_html_e('Reportar', 'flavor-chat-ia'); ?></button>
                <a href="<?php echo esc_url($url); ?>" target="_blank"><?php esc_html_e('Ver original', 'flavor-chat-ia'); ?></a>
            </div>
        </div>
    </header>

    <!-- Contenido -->
    <div class="mi-red-card__content">
        <?php if (!empty($contenido['titulo'])) : ?>
            <h3 class="mi-red-card__title">
                <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($contenido['titulo']); ?></a>
            </h3>
        <?php endif; ?>

        <?php if (!empty($contenido['texto'])) : ?>
            <div class="mi-red-card__text">
                <?php echo wp_kses_post(nl2br($contenido['texto'])); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($contenido['categoria'])) : ?>
            <span class="mi-red-card__category">
                <?php echo esc_html($contenido['categoria']); ?>
            </span>
        <?php endif; ?>

        <?php if (!empty($contenido['urgencia']) && $contenido['urgencia'] !== 'normal') : ?>
            <span class="mi-red-card__urgency mi-red-card__urgency--<?php echo esc_attr($contenido['urgencia']); ?>">
                <?php
                $urgencias = [
                    'alta' => __('Urgente', 'flavor-chat-ia'),
                    'media' => __('Prioridad media', 'flavor-chat-ia'),
                ];
                echo esc_html($urgencias[$contenido['urgencia']] ?? '');
                ?>
            </span>
        <?php endif; ?>
    </div>

    <!-- Multimedia -->
    <?php if (!empty($multimedia)) : ?>
        <div class="mi-red-card__media <?php echo count($multimedia) > 1 ? 'mi-red-card__media--grid' : ''; ?>">
            <?php foreach ($multimedia as $index => $media) : ?>
                <?php
                $media_tipo = $media['tipo'] ?? 'imagen';
                $media_url = $media['url'] ?? '';
                $media_thumbnail = $media['thumbnail'] ?? $media_url;

                if (empty($media_url)) continue;
                ?>

                <?php if ($media_tipo === 'imagen') : ?>
                    <figure class="mi-red-media mi-red-media--imagen">
                        <img src="<?php echo esc_url($media_url); ?>"
                             alt=""
                             loading="lazy"
                             class="mi-red-media__img"
                             data-action="lightbox">
                    </figure>

                <?php elseif ($media_tipo === 'video') : ?>
                    <figure class="mi-red-media mi-red-media--video">
                        <video controls
                               poster="<?php echo esc_url($media_thumbnail); ?>"
                               preload="metadata"
                               class="mi-red-media__video">
                            <source src="<?php echo esc_url($media_url); ?>" type="video/mp4">
                        </video>
                    </figure>

                <?php elseif ($media_tipo === 'audio') : ?>
                    <figure class="mi-red-media mi-red-media--audio">
                        <?php if (!empty($media_thumbnail)) : ?>
                            <img src="<?php echo esc_url($media_thumbnail); ?>"
                                 alt=""
                                 class="mi-red-media__thumbnail"
                                 loading="lazy">
                        <?php endif; ?>
                        <audio controls preload="metadata" class="mi-red-media__audio">
                            <source src="<?php echo esc_url($media_url); ?>" type="audio/mpeg">
                        </audio>
                        <?php if (!empty($contenido['duracion'])) : ?>
                            <span class="mi-red-media__duration">
                                <?php echo esc_html(gmdate('H:i:s', $contenido['duracion'])); ?>
                            </span>
                        <?php endif; ?>
                    </figure>
                <?php endif; ?>

                <?php if ($index >= 3) break; // Máximo 4 medias ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Footer con interacciones -->
    <footer class="mi-red-card__footer">
        <div class="mi-red-card__stats">
            <?php if (isset($interacciones['likes'])) : ?>
                <span class="mi-red-card__stat">
                    <span class="mi-red-card__stat-icon">❤️</span>
                    <span class="mi-red-card__stat-count" data-stat="likes"><?php echo esc_html(number_format_i18n($interacciones['likes'])); ?></span>
                </span>
            <?php endif; ?>

            <?php if (isset($interacciones['comentarios'])) : ?>
                <span class="mi-red-card__stat">
                    <span class="mi-red-card__stat-icon">💬</span>
                    <span class="mi-red-card__stat-count" data-stat="comentarios"><?php echo esc_html(number_format_i18n($interacciones['comentarios'])); ?></span>
                </span>
            <?php endif; ?>

            <?php if (isset($interacciones['vistas'])) : ?>
                <span class="mi-red-card__stat">
                    <span class="mi-red-card__stat-icon">👁️</span>
                    <span class="mi-red-card__stat-count" data-stat="vistas"><?php echo esc_html(number_format_i18n($interacciones['vistas'])); ?></span>
                </span>
            <?php endif; ?>

            <?php if (isset($interacciones['reproducciones'])) : ?>
                <span class="mi-red-card__stat">
                    <span class="mi-red-card__stat-icon">▶️</span>
                    <span class="mi-red-card__stat-count" data-stat="reproducciones"><?php echo esc_html(number_format_i18n($interacciones['reproducciones'])); ?></span>
                </span>
            <?php endif; ?>

            <?php if (isset($interacciones['respuestas'])) : ?>
                <span class="mi-red-card__stat">
                    <span class="mi-red-card__stat-icon">💬</span>
                    <span class="mi-red-card__stat-count" data-stat="respuestas"><?php echo esc_html(number_format_i18n($interacciones['respuestas'])); ?></span>
                    <?php esc_html_e('respuestas', 'flavor-chat-ia'); ?>
                </span>
            <?php endif; ?>

            <?php if (isset($interacciones['ofertas'])) : ?>
                <span class="mi-red-card__stat">
                    <span class="mi-red-card__stat-icon">🤝</span>
                    <span class="mi-red-card__stat-count" data-stat="ofertas"><?php echo esc_html(number_format_i18n($interacciones['ofertas'])); ?></span>
                    <?php esc_html_e('ofertas', 'flavor-chat-ia'); ?>
                </span>
            <?php endif; ?>
        </div>

        <div class="mi-red-card__buttons">
            <button class="mi-red-card__btn mi-red-card__btn--like"
                    data-action="like"
                    data-item="<?php echo esc_attr($item_id); ?>"
                    data-tipo="<?php echo esc_attr($tipo); ?>"
                    aria-label="<?php esc_attr_e('Me gusta', 'flavor-chat-ia'); ?>">
                <span class="mi-red-card__btn-icon">❤️</span>
                <span class="mi-red-card__btn-text"><?php esc_html_e('Me gusta', 'flavor-chat-ia'); ?></span>
            </button>

            <button class="mi-red-card__btn mi-red-card__btn--comment"
                    data-action="comentar"
                    data-item="<?php echo esc_attr($item_id); ?>"
                    aria-label="<?php esc_attr_e('Comentar', 'flavor-chat-ia'); ?>">
                <span class="mi-red-card__btn-icon">💬</span>
                <span class="mi-red-card__btn-text"><?php esc_html_e('Comentar', 'flavor-chat-ia'); ?></span>
            </button>

            <button class="mi-red-card__btn mi-red-card__btn--share"
                    data-action="compartir"
                    data-url="<?php echo esc_url($url); ?>"
                    aria-label="<?php esc_attr_e('Compartir', 'flavor-chat-ia'); ?>">
                <span class="mi-red-card__btn-icon">🔗</span>
                <span class="mi-red-card__btn-text"><?php esc_html_e('Compartir', 'flavor-chat-ia'); ?></span>
            </button>

            <button class="mi-red-card__btn mi-red-card__btn--save"
                    data-action="guardar"
                    data-item="<?php echo esc_attr($item_id); ?>"
                    aria-label="<?php esc_attr_e('Guardar', 'flavor-chat-ia'); ?>">
                <span class="mi-red-card__btn-icon">🔖</span>
            </button>
        </div>
    </footer>

    <!-- Sección de comentarios (oculta por defecto) -->
    <div class="mi-red-card__comments" id="comentarios-<?php echo esc_attr($item_id); ?>" hidden>
        <div class="mi-red-comments">
            <div class="mi-red-comments__list" data-item="<?php echo esc_attr($item_id); ?>">
                <!-- Comentarios se cargan vía AJAX -->
            </div>

            <form class="mi-red-comments__form" data-item="<?php echo esc_attr($item_id); ?>" data-tipo="<?php echo esc_attr($tipo); ?>">
                <input type="text"
                       name="comentario"
                       class="mi-red-comments__input"
                       placeholder="<?php esc_attr_e('Escribe un comentario...', 'flavor-chat-ia'); ?>"
                       required>
                <button type="submit" class="mi-red-comments__submit">
                    <?php esc_html_e('Enviar', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>
    </div>
</article>
