<?php
/**
 * Template: Feed - Feed de publicaciones
 *
 * Muestra el feed principal de publicaciones con opciones de filtrado.
 *
 * @package FlavorChatIA
 * @subpackage RedSocial/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$rs_url_explorar = home_url('/mi-portal/red-social/explorar/');
$rs_url_perfil = home_url('/mi-portal/red-social/perfil/');

$usuario_id = get_current_user_id();
$tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
$tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
$tabla_reacciones = $wpdb->prefix . 'flavor_social_reacciones';
$tabla_guardados = $wpdb->prefix . 'flavor_social_guardados';
$tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';

// Parametros de filtrado
$tipo_feed = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'timeline';
$pagina_actual = isset($_GET['pag']) ? max(1, absint($_GET['pag'])) : 1;
$por_pagina = 15;
$offset = ($pagina_actual - 1) * $por_pagina;

// Obtener publicaciones segun el tipo de feed
$publicaciones = [];
$total_publicaciones = 0;

switch ($tipo_feed) {
    case 'timeline':
        if ($usuario_id) {
            // Feed personalizado: propias + seguidos + publicas
            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT p.* FROM $tabla_publicaciones p
                 WHERE p.estado = 'publicado'
                 AND (
                     p.autor_id = %d
                     OR p.autor_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
                     OR p.visibilidad IN ('publica', 'comunidad')
                 )
                 ORDER BY p.fecha_publicacion DESC
                 LIMIT %d OFFSET %d",
                $usuario_id,
                $usuario_id,
                $por_pagina,
                $offset
            ));
            $total_publicaciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_publicaciones p
                 WHERE p.estado = 'publicado'
                 AND (
                     p.autor_id = %d
                     OR p.autor_id IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
                     OR p.visibilidad IN ('publica', 'comunidad')
                 )",
                $usuario_id,
                $usuario_id
            ));
        } else {
            // Feed publico para visitantes
            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $tabla_publicaciones
                 WHERE estado = 'publicado' AND visibilidad = 'publica'
                 ORDER BY fecha_publicacion DESC
                 LIMIT %d OFFSET %d",
                $por_pagina,
                $offset
            ));
            $total_publicaciones = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_publicaciones
                 WHERE estado = 'publicado' AND visibilidad = 'publica'"
            );
        }
        break;

    case 'comunidad':
        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_publicaciones
             WHERE estado = 'publicado' AND visibilidad IN ('publica', 'comunidad')
             ORDER BY fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            $por_pagina,
            $offset
        ));
        $total_publicaciones = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_publicaciones
             WHERE estado = 'publicado' AND visibilidad IN ('publica', 'comunidad')"
        );
        break;

    case 'trending':
        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_publicaciones
             WHERE estado = 'publicado'
             AND visibilidad IN ('publica', 'comunidad')
             AND fecha_publicacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY (me_gusta + comentarios * 2 + compartidos * 3) DESC, fecha_publicacion DESC
             LIMIT %d OFFSET %d",
            $por_pagina,
            $offset
        ));
        $total_publicaciones = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_publicaciones
             WHERE estado = 'publicado'
             AND visibilidad IN ('publica', 'comunidad')
             AND fecha_publicacion > DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        break;

    case 'siguiendo':
        if ($usuario_id) {
            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT p.* FROM $tabla_publicaciones p
                 INNER JOIN $tabla_seguimientos s ON p.autor_id = s.seguido_id
                 WHERE s.seguidor_id = %d AND p.estado = 'publicado'
                 ORDER BY p.fecha_publicacion DESC
                 LIMIT %d OFFSET %d",
                $usuario_id,
                $por_pagina,
                $offset
            ));
            $total_publicaciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_publicaciones p
                 INNER JOIN $tabla_seguimientos s ON p.autor_id = s.seguido_id
                 WHERE s.seguidor_id = %d AND p.estado = 'publicado'",
                $usuario_id
            ));
        }
        break;

    case 'guardados':
        if ($usuario_id) {
            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT p.* FROM $tabla_publicaciones p
                 INNER JOIN $tabla_guardados g ON p.id = g.publicacion_id
                 WHERE g.usuario_id = %d AND p.estado = 'publicado'
                 ORDER BY g.fecha_guardado DESC
                 LIMIT %d OFFSET %d",
                $usuario_id,
                $por_pagina,
                $offset
            ));
            $total_publicaciones = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_publicaciones p
                 INNER JOIN $tabla_guardados g ON p.id = g.publicacion_id
                 WHERE g.usuario_id = %d AND p.estado = 'publicado'",
                $usuario_id
            ));
        }
        break;
}

$total_paginas = ceil($total_publicaciones / $por_pagina);

// Obtener hashtags trending para sidebar
$hashtags_trending = $wpdb->get_results(
    "SELECT * FROM $tabla_hashtags
     WHERE fecha_ultimo_uso > DATE_SUB(NOW(), INTERVAL 7 DAY)
     ORDER BY total_usos DESC
     LIMIT 5"
);

// Obtener sugerencias de usuarios
$sugerencias_usuarios = [];
if ($usuario_id) {
    $sugerencias_usuarios = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_login
         FROM {$wpdb->users} u
         WHERE u.ID != %d
         AND u.ID NOT IN (SELECT seguido_id FROM $tabla_seguimientos WHERE seguidor_id = %d)
         ORDER BY RAND()
         LIMIT 5",
        $usuario_id,
        $usuario_id
    ));
}
?>

<div class="rs-container">
    <div class="rs-layout rs-layout-two-col">
        <!-- Feed principal -->
        <main class="rs-feed-main">
            <!-- Formulario de crear publicacion -->
            <?php if ($usuario_id): ?>
                <div class="rs-crear-post">
                    <form class="rs-crear-post-form" enctype="multipart/form-data">
                        <div class="rs-crear-post-header">
                            <img class="rs-crear-post-avatar"
                                 src="<?php echo esc_url(get_avatar_url($usuario_id, ['size' => 50])); ?>"
                                 alt="">
                            <div class="rs-crear-post-input">
                                <textarea class="rs-crear-post-textarea"
                                          id="rs-contenido-nuevo"
                                          placeholder="<?php echo esc_attr__('Que quieres compartir con la comunidad?', 'flavor-chat-ia'); ?>"
                                          maxlength="5000"></textarea>
                            </div>
                        </div>
                        <div class="rs-crear-post-preview" id="rs-preview-adjuntos" style="display: none;">
                            <!-- Preview de imagenes se inserta via JS -->
                        </div>
                        <div class="rs-crear-post-acciones">
                            <div class="rs-crear-post-adjuntos">
                                <label class="rs-adjunto-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <path d="M21 15l-5-5L5 21"/>
                                    </svg>
                                    <?php echo esc_html__('Foto', 'flavor-chat-ia'); ?>
                                    <input type="file" name="adjuntos[]" accept="image/*" multiple style="display: none;" id="rs-input-fotos">
                                </label>
                                <label class="rs-adjunto-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polygon points="23 7 16 12 23 17 23 7"/>
                                        <rect x="1" y="5" width="15" height="14" rx="2"/>
                                    </svg>
                                    <?php echo esc_html__('Video', 'flavor-chat-ia'); ?>
                                    <input type="file" name="video" accept="video/*" style="display: none;" id="rs-input-video">
                                </label>
                            </div>
                            <div class="rs-crear-post-opciones">
                                <select name="visibilidad" class="rs-select-visibilidad">
                                    <option value="comunidad"><?php echo esc_html__('Comunidad', 'flavor-chat-ia'); ?></option>
                                    <option value="publica"><?php echo esc_html__('Publica', 'flavor-chat-ia'); ?></option>
                                    <option value="seguidores"><?php echo esc_html__('Seguidores', 'flavor-chat-ia'); ?></option>
                                    <option value="privada"><?php echo esc_html__('Solo yo', 'flavor-chat-ia'); ?></option>
                                </select>
                                <button type="submit" class="rs-btn-publicar" id="rs-btn-publicar">
                                    <?php echo esc_html__('Publicar', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="rs-login-prompt">
                    <p><?php echo esc_html__('Inicia sesion para compartir con la comunidad.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/red-social/'))); ?>" class="rs-btn-primary">
                        <?php echo esc_html__('Iniciar sesion', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Tabs del feed -->
            <div class="rs-feed-header">
                <div class="rs-feed-tabs">
                    <a href="?tipo=timeline" class="rs-feed-tab <?php echo $tipo_feed === 'timeline' ? 'active' : ''; ?>">
                        <?php echo esc_html__('Para ti', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="?tipo=comunidad" class="rs-feed-tab <?php echo $tipo_feed === 'comunidad' ? 'active' : ''; ?>">
                        <?php echo esc_html__('Comunidad', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="?tipo=trending" class="rs-feed-tab <?php echo $tipo_feed === 'trending' ? 'active' : ''; ?>">
                        <?php echo esc_html__('Trending', 'flavor-chat-ia'); ?>
                    </a>
                    <?php if ($usuario_id): ?>
                        <a href="?tipo=siguiendo" class="rs-feed-tab <?php echo $tipo_feed === 'siguiendo' ? 'active' : ''; ?>">
                            <?php echo esc_html__('Siguiendo', 'flavor-chat-ia'); ?>
                        </a>
                        <a href="?tipo=guardados" class="rs-feed-tab <?php echo $tipo_feed === 'guardados' ? 'active' : ''; ?>">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Lista de publicaciones -->
            <div class="rs-feed" id="rs-feed-lista">
                <?php if (empty($publicaciones)): ?>
                    <div class="rs-feed-vacio">
                        <?php if ($tipo_feed === 'siguiendo'): ?>
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                            <p><?php echo esc_html__('Aun no sigues a nadie.', 'flavor-chat-ia'); ?></p>
                            <span><?php echo esc_html__('Explora la comunidad y sigue a personas interesantes.', 'flavor-chat-ia'); ?></span>
                            <a href="<?php echo esc_url($rs_url_explorar); ?>" class="rs-btn-primary"><?php echo esc_html__('Explorar', 'flavor-chat-ia'); ?></a>
                        <?php elseif ($tipo_feed === 'guardados'): ?>
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                            </svg>
                            <p><?php echo esc_html__('No has guardado publicaciones.', 'flavor-chat-ia'); ?></p>
                            <span><?php echo esc_html__('Guarda publicaciones para verlas mas tarde.', 'flavor-chat-ia'); ?></span>
                        <?php else: ?>
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                            </svg>
                            <p><?php echo esc_html__('No hay publicaciones aun.', 'flavor-chat-ia'); ?></p>
                            <?php if ($usuario_id): ?>
                                <span><?php echo esc_html__('Se el primero en compartir algo.', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($publicaciones as $publicacion): ?>
                        <?php
                        $autor = get_userdata($publicacion->autor_id);
                        $adjuntos = json_decode($publicacion->adjuntos, true);

                        // Verificar interacciones del usuario actual
                        $usuario_dio_like = false;
                        $usuario_guardo = false;
                        if ($usuario_id) {
                            $usuario_dio_like = (bool) $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM $tabla_reacciones WHERE publicacion_id = %d AND usuario_id = %d",
                                $publicacion->id,
                                $usuario_id
                            ));
                            $usuario_guardo = (bool) $wpdb->get_var($wpdb->prepare(
                                "SELECT id FROM $tabla_guardados WHERE publicacion_id = %d AND usuario_id = %d",
                                $publicacion->id,
                                $usuario_id
                            ));
                        }

                        // Procesar contenido con hashtags y menciones
                        $contenido_html = esc_html($publicacion->contenido);
                        $contenido_html = preg_replace(
                            '/#([a-zA-Z0-9_\p{L}]+)/u',
                            '<a href="' . esc_url($rs_url_explorar) . '?hashtag=$1" class="rs-hashtag">#$1</a>',
                            $contenido_html
                        );
                        $contenido_html = preg_replace(
                            '/@([a-zA-Z0-9_]+)/',
                            '<a href="' . esc_url($rs_url_explorar) . '?q=$1" class="rs-mencion">@$1</a>',
                            $contenido_html
                        );
                        ?>
                        <article class="rs-post" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                            <header class="rs-post-header">
                                <div class="rs-post-autor">
                                    <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($publicacion->autor_id), $rs_url_perfil)); ?>">
                                        <img class="rs-post-avatar"
                                             src="<?php echo esc_url(get_avatar_url($publicacion->autor_id, ['size' => 50])); ?>"
                                             alt="">
                                    </a>
                                    <div class="rs-post-autor-info">
                                        <h4>
                                            <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($publicacion->autor_id), $rs_url_perfil)); ?>">
                                                <?php echo esc_html($autor ? $autor->display_name : 'Usuario'); ?>
                                            </a>
                                        </h4>
                                        <div class="rs-post-meta">
                                            <span>@<?php echo esc_html($autor ? $autor->user_login : ''); ?></span>
                                            <span class="rs-post-tiempo"><?php echo human_time_diff(strtotime($publicacion->fecha_publicacion), current_time('timestamp')); ?></span>
                                            <?php if ($publicacion->visibilidad === 'seguidores'): ?>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="<?php echo esc_attr__('Solo seguidores', 'flavor-chat-ia'); ?>">
                                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                                    <circle cx="9" cy="7" r="4"/>
                                                </svg>
                                            <?php elseif ($publicacion->visibilidad === 'privada'): ?>
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" title="<?php echo esc_attr__('Privada', 'flavor-chat-ia'); ?>">
                                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                                </svg>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <button class="rs-post-menu-btn" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <circle cx="12" cy="5" r="2"/>
                                        <circle cx="12" cy="12" r="2"/>
                                        <circle cx="12" cy="19" r="2"/>
                                    </svg>
                                </button>
                            </header>

                            <div class="rs-post-contenido">
                                <p class="rs-post-texto"><?php echo $contenido_html; ?></p>
                            </div>

                            <?php if (!empty($adjuntos)): ?>
                                <div class="rs-post-media">
                                    <?php $total_adjuntos = count($adjuntos); ?>
                                    <div class="rs-post-media-grid <?php echo $total_adjuntos > 1 ? 'rs-media-' . min($total_adjuntos, 4) : ''; ?>">
                                        <?php foreach (array_slice($adjuntos, 0, 4) as $indice => $adjunto): ?>
                                            <div class="rs-media-item <?php echo ($total_adjuntos > 4 && $indice === 3) ? 'rs-media-mas' : ''; ?>">
                                                <img src="<?php echo esc_url($adjunto); ?>" alt="" loading="lazy">
                                                <?php if ($total_adjuntos > 4 && $indice === 3): ?>
                                                    <span class="rs-media-contador">+<?php echo $total_adjuntos - 4; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($publicacion->me_gusta > 0 || $publicacion->comentarios > 0): ?>
                                <div class="rs-post-stats">
                                    <div class="rs-post-likes-count">
                                        <?php if ($publicacion->me_gusta > 0): ?>
                                            <span><?php echo number_format($publicacion->me_gusta); ?> <?php echo esc_html__('me gusta', 'flavor-chat-ia'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($publicacion->comentarios > 0): ?>
                                            <span><?php echo number_format($publicacion->comentarios); ?> <?php echo esc_html__('comentarios', 'flavor-chat-ia'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="rs-post-acciones">
                                <button class="rs-post-accion <?php echo $usuario_dio_like ? 'rs-liked' : ''; ?>"
                                        data-accion="like"
                                        data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24"
                                         fill="<?php echo $usuario_dio_like ? 'currentColor' : 'none'; ?>"
                                         stroke="currentColor" stroke-width="2">
                                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                    </svg>
                                    <span class="rs-like-count"><?php echo $publicacion->me_gusta ?: ''; ?></span>
                                </button>
                                <button class="rs-post-accion"
                                        data-accion="comentar"
                                        data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                    </svg>
                                    <span><?php echo $publicacion->comentarios ?: ''; ?></span>
                                </button>
                                <button class="rs-post-accion" data-accion="compartir" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="18" cy="5" r="3"/>
                                        <circle cx="6" cy="12" r="3"/>
                                        <circle cx="18" cy="19" r="3"/>
                                        <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                                        <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                                    </svg>
                                </button>
                                <button class="rs-post-accion <?php echo $usuario_guardo ? 'rs-guardado' : ''; ?>"
                                        data-accion="guardar"
                                        data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24"
                                         fill="<?php echo $usuario_guardo ? 'currentColor' : 'none'; ?>"
                                         stroke="currentColor" stroke-width="2">
                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                                    </svg>
                                </button>
                            </div>

                            <!-- Seccion de comentarios (colapsada por defecto) -->
                            <div class="rs-post-comentarios" id="rs-comentarios-<?php echo esc_attr($publicacion->id); ?>" style="display: none;">
                                <!-- Se carga via AJAX -->
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Cargando mas -->
            <div class="rs-loading" id="rs-loading" style="display: none;">
                <div class="rs-spinner"></div>
                <span><?php echo esc_html__('Cargando mas publicaciones...', 'flavor-chat-ia'); ?></span>
            </div>

            <!-- Paginacion -->
            <?php if ($total_paginas > 1): ?>
                <div class="rs-paginacion">
                    <?php if ($pagina_actual > 1): ?>
                        <a href="?tipo=<?php echo esc_attr($tipo_feed); ?>&pag=<?php echo $pagina_actual - 1; ?>"
                           class="rs-paginacion-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="15 18 9 12 15 6"></polyline>
                            </svg>
                            <?php echo esc_html__('Anterior', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>

                    <span class="rs-paginacion-info">
                        <?php printf(esc_html__('Pagina %d de %d', 'flavor-chat-ia'), $pagina_actual, $total_paginas); ?>
                    </span>

                    <?php if ($pagina_actual < $total_paginas): ?>
                        <a href="?tipo=<?php echo esc_attr($tipo_feed); ?>&pag=<?php echo $pagina_actual + 1; ?>"
                           class="rs-paginacion-btn">
                            <?php echo esc_html__('Siguiente', 'flavor-chat-ia'); ?>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 18 15 12 9 6"></polyline>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>

        <!-- Sidebar derecha -->
        <aside class="rs-sidebar-right">
            <!-- Widget de sugerencias -->
            <?php if (!empty($sugerencias_usuarios)): ?>
                <div class="rs-widget">
                    <h3 class="rs-widget-titulo"><?php echo esc_html__('Sugerencias para ti', 'flavor-chat-ia'); ?></h3>
                    <div class="rs-sugerencias-lista">
                        <?php foreach ($sugerencias_usuarios as $sugerencia): ?>
                            <div class="rs-sugerencia">
                                <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($sugerencia->ID), $rs_url_perfil)); ?>" class="rs-sugerencia-link">
                                    <img class="rs-sugerencia-avatar"
                                         src="<?php echo esc_url(get_avatar_url($sugerencia->ID, ['size' => 50])); ?>"
                                         alt="">
                                    <div class="rs-sugerencia-info">
                                        <h4 class="rs-sugerencia-nombre"><?php echo esc_html($sugerencia->display_name); ?></h4>
                                        <span class="rs-sugerencia-username">@<?php echo esc_html($sugerencia->user_login); ?></span>
                                    </div>
                                </a>
                                <button class="rs-btn-seguir-sm" data-usuario-id="<?php echo esc_attr($sugerencia->ID); ?>">
                                    <?php echo esc_html__('Seguir', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Widget de tendencias -->
            <?php if (!empty($hashtags_trending)): ?>
                <div class="rs-widget">
                    <h3 class="rs-widget-titulo"><?php echo esc_html__('Tendencias', 'flavor-chat-ia'); ?></h3>
                    <div class="rs-trending-lista">
                        <?php foreach ($hashtags_trending as $indice => $hashtag): ?>
                            <a href="<?php echo esc_url(add_query_arg('hashtag', $hashtag->hashtag, $rs_url_explorar)); ?>" class="rs-trending-item">
                                <div class="rs-trending-categoria"><?php echo $indice + 1; ?>. <?php echo esc_html__('Tendencia', 'flavor-chat-ia'); ?></div>
                                <div class="rs-trending-hashtag">#<?php echo esc_html($hashtag->hashtag); ?></div>
                                <div class="rs-trending-posts"><?php echo number_format($hashtag->total_usos); ?> <?php echo esc_html__('publicaciones', 'flavor-chat-ia'); ?></div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Pie de pagina de la red -->
            <div class="rs-sidebar-footer">
                <p><?php echo esc_html__('Red social comunitaria sin publicidad.', 'flavor-chat-ia'); ?></p>
                <nav class="rs-sidebar-nav">
                    <span><?php echo esc_html__('Acerca de', 'flavor-chat-ia'); ?></span>
                    <span><?php echo esc_html__('Privacidad', 'flavor-chat-ia'); ?></span>
                    <span><?php echo esc_html__('Terminos', 'flavor-chat-ia'); ?></span>
                </nav>
            </div>
        </aside>
    </div>
</div>

<style>
/* Estilos adicionales especificos del feed */
.rs-login-prompt {
    text-align: center;
    padding: 40px 20px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    margin-bottom: 16px;
}

.rs-login-prompt p {
    margin: 0 0 16px;
    color: var(--rs-text-muted);
}

.rs-crear-post-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    padding: 12px 0;
}

.rs-crear-post-preview img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
}

.rs-select-visibilidad {
    padding: 8px 12px;
    border: 1px solid var(--rs-border);
    border-radius: var(--rs-radius-sm);
    background: var(--rs-bg-light);
    color: var(--rs-text);
    font-size: 14px;
    cursor: pointer;
}

.rs-crear-post-opciones {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rs-feed-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
}

.rs-post-media-grid {
    display: grid;
    gap: 4px;
    border-radius: var(--rs-radius);
    overflow: hidden;
}

.rs-post-media-grid.rs-media-2 {
    grid-template-columns: 1fr 1fr;
}

.rs-post-media-grid.rs-media-3 {
    grid-template-columns: 2fr 1fr;
    grid-template-rows: 1fr 1fr;
}

.rs-post-media-grid.rs-media-3 .rs-media-item:first-child {
    grid-row: 1 / 3;
}

.rs-post-media-grid.rs-media-4 {
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
}

.rs-media-item {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
}

.rs-media-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.rs-media-mas::after {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
}

.rs-media-contador {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 24px;
    font-weight: 700;
    z-index: 1;
}

.rs-post-stats {
    display: flex;
    justify-content: space-between;
    padding: 12px 20px;
    color: var(--rs-text-muted);
    font-size: 14px;
    border-bottom: 1px solid var(--rs-border);
}

.rs-post-acciones {
    display: flex;
    justify-content: space-around;
    padding: 8px 0;
}

.rs-post-accion {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 10px 16px;
    border: none;
    background: transparent;
    color: var(--rs-text-muted);
    cursor: pointer;
    border-radius: var(--rs-radius-sm);
    transition: var(--rs-transition);
}

.rs-post-accion:hover {
    background: var(--rs-bg-light);
    color: var(--rs-text);
}

.rs-post-accion.rs-liked {
    color: var(--rs-danger);
}

.rs-post-accion.rs-guardado {
    color: var(--rs-warning);
}

.rs-hashtag,
.rs-mencion {
    color: var(--rs-primary);
    text-decoration: none;
    font-weight: 500;
}

.rs-hashtag:hover,
.rs-mencion:hover {
    text-decoration: underline;
}

.rs-feed-vacio {
    text-align: center;
    padding: 60px 20px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
}

.rs-feed-vacio svg {
    color: var(--rs-text-muted);
    margin-bottom: 16px;
    opacity: 0.5;
}

.rs-feed-vacio p {
    font-size: 18px;
    margin: 0 0 8px;
    color: var(--rs-text);
}

.rs-feed-vacio span {
    display: block;
    color: var(--rs-text-muted);
    margin-bottom: 20px;
}

.rs-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 12px;
    padding: 40px;
    color: var(--rs-text-muted);
}

.rs-spinner {
    width: 32px;
    height: 32px;
    border: 3px solid var(--rs-border);
    border-top-color: var(--rs-primary);
    border-radius: 50%;
    animation: rs-spin 0.8s linear infinite;
}

@keyframes rs-spin {
    to { transform: rotate(360deg); }
}

.rs-sugerencia {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px 0;
}

.rs-sugerencia-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    flex: 1;
    min-width: 0;
}

.rs-sugerencia-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    object-fit: cover;
}

.rs-sugerencia-nombre {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rs-sugerencia-username {
    font-size: 13px;
    color: var(--rs-text-muted);
}

.rs-btn-seguir-sm {
    padding: 6px 14px;
    border: none;
    background: var(--rs-primary);
    color: white;
    border-radius: 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--rs-transition);
}

.rs-btn-seguir-sm:hover {
    background: var(--rs-primary-hover);
}

.rs-trending-lista {
    display: flex;
    flex-direction: column;
}

.rs-trending-item {
    display: block;
    padding: 12px;
    border-radius: var(--rs-radius-sm);
    text-decoration: none;
    color: inherit;
    transition: var(--rs-transition);
}

.rs-trending-item:hover {
    background: var(--rs-bg-light);
}

.rs-trending-categoria {
    font-size: 12px;
    color: var(--rs-text-muted);
}

.rs-trending-hashtag {
    font-weight: 600;
    font-size: 15px;
    margin: 2px 0;
}

.rs-trending-posts {
    font-size: 13px;
    color: var(--rs-text-muted);
}

.rs-sidebar-footer {
    margin-top: 20px;
    padding: 16px;
    font-size: 13px;
    color: var(--rs-text-muted);
}

.rs-sidebar-footer p {
    margin: 0 0 12px;
}

.rs-sidebar-nav {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.rs-sidebar-nav a {
    color: var(--rs-text-muted);
    text-decoration: none;
}

.rs-sidebar-nav a:hover {
    color: var(--rs-primary);
    text-decoration: underline;
}

.rs-paginacion {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-top: 24px;
    padding: 16px;
}

.rs-paginacion-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: var(--rs-bg-card);
    border: 1px solid var(--rs-border);
    border-radius: var(--rs-radius-sm);
    color: var(--rs-text);
    text-decoration: none;
    font-weight: 500;
    transition: var(--rs-transition);
}

.rs-paginacion-btn:hover {
    background: var(--rs-primary);
    border-color: var(--rs-primary);
    color: white;
}

.rs-paginacion-info {
    color: var(--rs-text-muted);
}

@media (max-width: 1024px) {
    .rs-layout-two-col {
        grid-template-columns: 1fr;
    }

    .rs-sidebar-right {
        display: none;
    }
}
</style>
