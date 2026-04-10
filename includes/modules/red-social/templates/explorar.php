<?php
/**
 * Template: Explorar - Descubrir contenido y usuarios
 *
 * Pagina de exploracion con tendencias, hashtags y usuarios sugeridos.
 *
 * @package FlavorPlatform
 * @subpackage RedSocial/Templates
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$usuario_id = get_current_user_id();
$tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
$tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';
$tabla_hashtags_posts = $wpdb->prefix . 'flavor_social_hashtags_posts';
$tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';
$tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

// Parametros de filtrado
$hashtag_filtro = isset($_GET['rs_hashtag']) ? sanitize_text_field($_GET['rs_hashtag']) : '';
if (!$hashtag_filtro && isset($_GET['hashtag'])) {
    $hashtag_filtro = sanitize_text_field($_GET['hashtag']);
}
$busqueda = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
$seccion_activa = isset($_GET['seccion']) ? sanitize_text_field($_GET['seccion']) : 'tendencias';

// Obtener hashtags trending
$hashtags_trending = $wpdb->get_results(
    "SELECT h.*, COUNT(hp.id) as usos_recientes
     FROM $tabla_hashtags h
     LEFT JOIN $tabla_hashtags_posts hp ON h.id = hp.hashtag_id
        AND hp.fecha_creacion > DATE_SUB(NOW(), INTERVAL 7 DAY)
     WHERE h.fecha_ultimo_uso > DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY h.id
     ORDER BY usos_recientes DESC, h.total_usos DESC
     LIMIT 10"
);

// Obtener usuarios populares
$usuarios_populares = $wpdb->get_results(
    "SELECT p.usuario_id, p.total_seguidores, p.bio, p.es_verificado,
            u.display_name, u.user_login
     FROM $tabla_perfiles p
     INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
     ORDER BY p.total_seguidores DESC
     LIMIT 10"
);

// Obtener publicaciones con media para galeria
$publicaciones_galeria = $wpdb->get_results(
    "SELECT * FROM $tabla_publicaciones
     WHERE estado = 'publicado'
     AND visibilidad IN ('publica', 'comunidad')
     AND adjuntos IS NOT NULL AND adjuntos != '' AND adjuntos != '[]'
     ORDER BY (me_gusta + comentarios * 2) DESC, fecha_publicacion DESC
     LIMIT 30"
);

// Si hay hashtag filtro, obtener publicaciones de ese hashtag
$publicaciones_hashtag = [];
if ($hashtag_filtro) {
    $publicaciones_hashtag = $wpdb->get_results($wpdb->prepare(
        "SELECT p.* FROM $tabla_publicaciones p
         INNER JOIN $tabla_hashtags_posts hp ON p.id = hp.publicacion_id
         INNER JOIN $tabla_hashtags h ON hp.hashtag_id = h.id
         WHERE h.hashtag = %s AND p.estado = 'publicado'
         ORDER BY p.fecha_publicacion DESC
         LIMIT 50",
        mb_strtolower($hashtag_filtro)
    ));

    $hashtag_info = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_hashtags WHERE hashtag = %s",
        mb_strtolower($hashtag_filtro)
    ));
}

// Si hay busqueda, buscar usuarios y publicaciones
$resultados_busqueda_usuarios = [];
$resultados_busqueda_posts = [];
if ($busqueda && strlen($busqueda) >= 2) {
    $resultados_busqueda_usuarios = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_login, p.bio, p.total_seguidores
         FROM {$wpdb->users} u
         LEFT JOIN $tabla_perfiles p ON u.ID = p.usuario_id
         WHERE u.display_name LIKE %s OR u.user_login LIKE %s
         ORDER BY COALESCE(p.total_seguidores, 0) DESC
         LIMIT 20",
        '%' . $wpdb->esc_like($busqueda) . '%',
        '%' . $wpdb->esc_like($busqueda) . '%'
    ));

    $resultados_busqueda_posts = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_publicaciones
         WHERE estado = 'publicado'
         AND visibilidad IN ('publica', 'comunidad')
         AND MATCH(contenido) AGAINST(%s IN NATURAL LANGUAGE MODE)
         ORDER BY fecha_publicacion DESC
         LIMIT 30",
        $busqueda
    ));
}
?>

<div class="rs-container">
    <div class="rs-explorar">
        <!-- Barra de busqueda -->
        <div class="rs-explorar-busqueda">
            <form method="get" action="" class="rs-busqueda-form">
                <div class="rs-busqueda-wrapper">
                    <svg class="rs-busqueda-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="M21 21l-4.35-4.35"></path>
                    </svg>
                    <input type="text"
                           name="q"
                           class="rs-busqueda-input"
                           value="<?php echo esc_attr($busqueda); ?>"
                           placeholder="<?php echo esc_attr__('Buscar publicaciones, personas o hashtags...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <?php if ($busqueda): ?>
                        <a href="?" class="rs-busqueda-clear">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <?php if ($hashtag_filtro): ?>
            <!-- Vista de Hashtag -->
            <div class="rs-hashtag-header">
                <div class="rs-hashtag-info">
                    <h1 class="rs-hashtag-titulo">#<?php echo esc_html($hashtag_filtro); ?></h1>
                    <?php if ($hashtag_info): ?>
                        <p class="rs-hashtag-stats">
                            <?php printf(
                                esc_html__('%s publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                number_format($hashtag_info->total_usos)
                            ); ?>
                        </p>
                    <?php endif; ?>
                </div>
                <a href="?" class="rs-hashtag-volver">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7"/>
                    </svg>
                    <?php echo esc_html__('Volver a explorar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>

            <div class="rs-feed">
                <?php if (empty($publicaciones_hashtag)): ?>
                    <div class="rs-explorar-vacio">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <line x1="4" y1="9" x2="20" y2="9"></line>
                            <line x1="4" y1="15" x2="20" y2="15"></line>
                            <line x1="10" y1="3" x2="8" y2="21"></line>
                            <line x1="16" y1="3" x2="14" y2="21"></line>
                        </svg>
                        <p><?php echo esc_html__('No hay publicaciones con este hashtag.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($publicaciones_hashtag as $publicacion): ?>
                        <?php
                        // Renderizar publicacion (asumiendo que el metodo existe en el modulo)
                        $autor = get_userdata($publicacion->autor_id);
                        $adjuntos = json_decode($publicacion->adjuntos, true);
                        ?>
                        <article class="rs-post" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                            <header class="rs-post-header">
                                <div class="rs-post-autor">
                                    <img class="rs-post-avatar"
                                         src="<?php echo esc_url(get_avatar_url($publicacion->autor_id, ['size' => 50])); ?>"
                                         alt="">
                                    <div class="rs-post-autor-info">
                                        <h4><a href="<?php echo esc_url(add_query_arg('usuario_id', intval($publicacion->autor_id), Flavor_Platform_Helpers::get_action_url('red_social', 'perfil'))); ?>">
                                            <?php echo esc_html($autor ? $autor->display_name : 'Usuario'); ?>
                                        </a></h4>
                                        <span class="rs-post-tiempo"><?php echo human_time_diff(strtotime($publicacion->fecha_publicacion), current_time('timestamp')); ?></span>
                                    </div>
                                </div>
                            </header>
                            <div class="rs-post-contenido">
                                <p class="rs-post-texto"><?php echo esc_html($publicacion->contenido); ?></p>
                            </div>
                            <?php if (!empty($adjuntos)): ?>
                                <div class="rs-post-media">
                                    <div class="rs-post-media-grid">
                                        <?php foreach (array_slice($adjuntos, 0, 4) as $adjunto): ?>
                                            <img src="<?php echo esc_url($adjunto); ?>" alt="" loading="lazy">
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="rs-post-stats">
                                <span><?php echo number_format($publicacion->me_gusta); ?> me gusta</span>
                                <span><?php echo number_format($publicacion->comentarios); ?> comentarios</span>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($busqueda): ?>
            <!-- Resultados de busqueda -->
            <div class="rs-resultados-busqueda">
                <div class="rs-resultados-tabs">
                    <button class="rs-resultados-tab active" data-tab="todos"><?php echo esc_html__('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button class="rs-resultados-tab" data-tab="usuarios">
                        <?php echo esc_html__('Personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="rs-tab-badge"><?php echo count($resultados_busqueda_usuarios); ?></span>
                    </button>
                    <button class="rs-resultados-tab" data-tab="publicaciones">
                        <?php echo esc_html__('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="rs-tab-badge"><?php echo count($resultados_busqueda_posts); ?></span>
                    </button>
                </div>

                <!-- Usuarios encontrados -->
                <?php if (!empty($resultados_busqueda_usuarios)): ?>
                    <div class="rs-resultados-seccion" data-seccion="usuarios">
                        <h3 class="rs-resultados-titulo"><?php echo esc_html__('Personas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="rs-resultados-usuarios">
                            <?php foreach ($resultados_busqueda_usuarios as $usuario_encontrado): ?>
                                <?php
                                $sigo_usuario = false;
                                if ($usuario_id && $usuario_encontrado->ID != $usuario_id) {
                                    $sigo_usuario = (bool) $wpdb->get_var($wpdb->prepare(
                                        "SELECT id FROM $tabla_seguimientos WHERE seguidor_id = %d AND seguido_id = %d",
                                        $usuario_id,
                                        $usuario_encontrado->ID
                                    ));
                                }
                                ?>
                                <div class="rs-resultado-usuario">
                                    <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($usuario_encontrado->ID), Flavor_Platform_Helpers::get_action_url('red_social', 'perfil'))); ?>" class="rs-resultado-usuario-link">
                                        <img class="rs-resultado-avatar"
                                             src="<?php echo esc_url(get_avatar_url($usuario_encontrado->ID, ['size' => 56])); ?>"
                                             alt="">
                                        <div class="rs-resultado-info">
                                            <h4><?php echo esc_html($usuario_encontrado->display_name); ?></h4>
                                            <span>@<?php echo esc_html($usuario_encontrado->user_login); ?></span>
                                            <?php if ($usuario_encontrado->bio): ?>
                                                <p><?php echo esc_html(wp_trim_words($usuario_encontrado->bio, 12)); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <?php if ($usuario_encontrado->ID != $usuario_id): ?>
                                        <button class="rs-btn-seguir-mini <?php echo $sigo_usuario ? 'rs-siguiendo' : ''; ?>"
                                                data-usuario-id="<?php echo esc_attr($usuario_encontrado->ID); ?>">
                                            <?php echo $sigo_usuario ? esc_html__('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Publicaciones encontradas -->
                <?php if (!empty($resultados_busqueda_posts)): ?>
                    <div class="rs-resultados-seccion" data-seccion="publicaciones">
                        <h3 class="rs-resultados-titulo"><?php echo esc_html__('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <div class="rs-feed">
                            <?php foreach ($resultados_busqueda_posts as $publicacion): ?>
                                <?php
                                $autor = get_userdata($publicacion->autor_id);
                                ?>
                                <article class="rs-post-mini" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <img class="rs-post-mini-avatar"
                                         src="<?php echo esc_url(get_avatar_url($publicacion->autor_id, ['size' => 40])); ?>"
                                         alt="">
                                    <div class="rs-post-mini-contenido">
                                        <div class="rs-post-mini-header">
                                            <strong><?php echo esc_html($autor ? $autor->display_name : 'Usuario'); ?></strong>
                                            <span><?php echo human_time_diff(strtotime($publicacion->fecha_publicacion), current_time('timestamp')); ?></span>
                                        </div>
                                        <p><?php echo esc_html(wp_trim_words($publicacion->contenido, 30)); ?></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (empty($resultados_busqueda_usuarios) && empty($resultados_busqueda_posts)): ?>
                    <div class="rs-explorar-vacio">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                        <p><?php printf(esc_html__('No se encontraron resultados para "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($busqueda)); ?></p>
                        <span><?php echo esc_html__('Intenta con otros terminos de busqueda.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Vista principal de explorar -->
            <div class="rs-explorar-layout">
                <!-- Columna izquierda: Tendencias y hashtags -->
                <div class="rs-explorar-sidebar">
                    <div class="rs-widget rs-tendencias">
                        <h3 class="rs-widget-titulo">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                                <polyline points="17 6 23 6 23 12"></polyline>
                            </svg>
                            <?php echo esc_html__('Tendencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <div class="rs-tendencias-lista">
                            <?php foreach ($hashtags_trending as $indice => $hashtag): ?>
                                <a href="<?php echo esc_url(add_query_arg('hashtag', rawurlencode($hashtag->hashtag), Flavor_Platform_Helpers::get_action_url('red_social', 'explorar'))); ?>" class="rs-tendencia-item">
                                    <span class="rs-tendencia-numero"><?php echo $indice + 1; ?></span>
                                    <div class="rs-tendencia-info">
                                        <span class="rs-tendencia-hashtag">#<?php echo esc_html($hashtag->hashtag); ?></span>
                                        <span class="rs-tendencia-posts"><?php echo number_format($hashtag->total_usos); ?> publicaciones</span>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php if (empty($hashtags_trending)): ?>
                                <p class="rs-widget-vacio"><?php echo esc_html__('No hay tendencias aun.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Usuarios populares -->
                    <div class="rs-widget rs-populares">
                        <h3 class="rs-widget-titulo">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <?php echo esc_html__('Usuarios populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h3>
                        <div class="rs-populares-lista">
                            <?php foreach (array_slice($usuarios_populares, 0, 5) as $popular): ?>
                                <?php
                                $sigo_popular = false;
                                if ($usuario_id && $popular->usuario_id != $usuario_id) {
                                    $sigo_popular = (bool) $wpdb->get_var($wpdb->prepare(
                                        "SELECT id FROM $tabla_seguimientos WHERE seguidor_id = %d AND seguido_id = %d",
                                        $usuario_id,
                                        $popular->usuario_id
                                    ));
                                }
                                ?>
                                <div class="rs-popular-item">
                                    <a href="<?php echo esc_url(add_query_arg('usuario_id', intval($popular->usuario_id), Flavor_Platform_Helpers::get_action_url('red_social', 'perfil'))); ?>" class="rs-popular-link">
                                        <img class="rs-popular-avatar"
                                             src="<?php echo esc_url(get_avatar_url($popular->usuario_id, ['size' => 48])); ?>"
                                             alt="">
                                        <div class="rs-popular-info">
                                            <span class="rs-popular-nombre">
                                                <?php echo esc_html($popular->display_name); ?>
                                                <?php if ($popular->es_verificado): ?>
                                                    <svg class="rs-verificado" width="16" height="16" viewBox="0 0 24 24" fill="var(--rs-primary)">
                                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                <?php endif; ?>
                                            </span>
                                            <span class="rs-popular-seguidores"><?php echo number_format($popular->total_seguidores); ?> seguidores</span>
                                        </div>
                                    </a>
                                    <?php if ($popular->usuario_id != $usuario_id): ?>
                                        <button class="rs-btn-seguir-sm <?php echo $sigo_popular ? 'rs-siguiendo' : ''; ?>"
                                                data-usuario-id="<?php echo esc_attr($popular->usuario_id); ?>">
                                            <?php echo $sigo_popular ? esc_html__('Siguiendo', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Columna principal: Galeria -->
                <div class="rs-explorar-main">
                    <h2 class="rs-explorar-section-titulo"><?php echo esc_html__('Descubre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

                    <div class="rs-explorar-grid">
                        <?php foreach ($publicaciones_galeria as $publicacion): ?>
                            <?php
                            $adjuntos = json_decode($publicacion->adjuntos, true);
                            if (!empty($adjuntos)):
                            ?>
                                <div class="rs-explorar-item" data-post-id="<?php echo esc_attr($publicacion->id); ?>">
                                    <img src="<?php echo esc_url($adjuntos[0]); ?>" alt="" loading="lazy">
                                    <div class="rs-explorar-overlay">
                                        <span class="rs-explorar-stat">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                            </svg>
                                            <?php echo number_format($publicacion->me_gusta); ?>
                                        </span>
                                        <span class="rs-explorar-stat">
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                            </svg>
                                            <?php echo number_format($publicacion->comentarios); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if (empty($publicaciones_galeria)): ?>
                            <div class="rs-explorar-vacio-full">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <path d="M21 15l-5-5L5 21"/>
                                </svg>
                                <p><?php echo esc_html__('Aun no hay contenido para explorar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <?php if ($usuario_id): ?>
                                    <span><?php echo esc_html__('Se el primero en compartir algo con la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.rs-explorar {
    padding: 24px 0;
}

.rs-explorar-busqueda {
    margin-bottom: 24px;
}

.rs-busqueda-wrapper {
    position: relative;
    max-width: 600px;
    margin: 0 auto;
}

.rs-busqueda-icon {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--rs-text-muted);
}

.rs-busqueda-input {
    width: 100%;
    padding: 16px 50px 16px 56px;
    border: 2px solid var(--rs-border);
    border-radius: 50px;
    font-size: 16px;
    background: var(--rs-bg-card);
    transition: var(--rs-transition);
}

.rs-busqueda-input:focus {
    outline: none;
    border-color: var(--rs-primary);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.rs-busqueda-clear {
    position: absolute;
    right: 16px;
    top: 50%;
    transform: translateY(-50%);
    padding: 8px;
    color: var(--rs-text-muted);
    transition: var(--rs-transition);
}

.rs-busqueda-clear:hover {
    color: var(--rs-danger);
}

/* Hashtag Header */
.rs-hashtag-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 24px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    margin-bottom: 24px;
}

.rs-hashtag-titulo {
    margin: 0 0 4px;
    font-size: 28px;
    font-weight: 700;
    color: var(--rs-primary);
}

.rs-hashtag-stats {
    margin: 0;
    color: var(--rs-text-muted);
}

.rs-hashtag-volver {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--rs-text-muted);
    text-decoration: none;
    font-weight: 500;
    transition: var(--rs-transition);
}

.rs-hashtag-volver:hover {
    color: var(--rs-primary);
}

/* Layout principal */
.rs-explorar-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 24px;
}

.rs-explorar-sidebar {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.rs-widget {
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    padding: 20px;
}

.rs-widget-titulo {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 0 16px;
    font-size: 16px;
    font-weight: 600;
    color: var(--rs-text);
}

.rs-widget-titulo svg {
    color: var(--rs-primary);
}

.rs-widget-vacio {
    color: var(--rs-text-muted);
    font-size: 14px;
    text-align: center;
    padding: 20px;
}

/* Tendencias */
.rs-tendencias-lista {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.rs-tendencia-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: var(--rs-radius-sm);
    text-decoration: none;
    color: inherit;
    transition: var(--rs-transition);
}

.rs-tendencia-item:hover {
    background: var(--rs-bg-light);
}

.rs-tendencia-numero {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--rs-bg-light);
    border-radius: 50%;
    font-size: 12px;
    font-weight: 600;
    color: var(--rs-text-muted);
}

.rs-tendencia-hashtag {
    display: block;
    font-weight: 600;
    color: var(--rs-text);
}

.rs-tendencia-posts {
    font-size: 13px;
    color: var(--rs-text-muted);
}

/* Usuarios populares */
.rs-populares-lista {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.rs-popular-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.rs-popular-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    flex: 1;
    min-width: 0;
}

.rs-popular-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.rs-popular-info {
    min-width: 0;
}

.rs-popular-nombre {
    display: flex;
    align-items: center;
    gap: 4px;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rs-verificado {
    flex-shrink: 0;
}

.rs-popular-seguidores {
    font-size: 13px;
    color: var(--rs-text-muted);
}

.rs-btn-seguir-sm {
    padding: 6px 14px;
    border: 1px solid var(--rs-primary);
    background: transparent;
    color: var(--rs-primary);
    border-radius: 16px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--rs-transition);
    flex-shrink: 0;
}

.rs-btn-seguir-sm:hover {
    background: var(--rs-primary);
    color: white;
}

.rs-btn-seguir-sm.rs-siguiendo {
    background: var(--rs-bg-light);
    border-color: var(--rs-border);
    color: var(--rs-text-muted);
}

/* Galeria */
.rs-explorar-section-titulo {
    margin: 0 0 20px;
    font-size: 22px;
    font-weight: 600;
}

.rs-explorar-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 4px;
}

.rs-explorar-item {
    position: relative;
    aspect-ratio: 1;
    overflow: hidden;
    border-radius: 4px;
    cursor: pointer;
}

.rs-explorar-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.rs-explorar-item:hover img {
    transform: scale(1.05);
}

.rs-explorar-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 24px;
    opacity: 0;
    transition: var(--rs-transition);
}

.rs-explorar-item:hover .rs-explorar-overlay {
    opacity: 1;
}

.rs-explorar-stat {
    display: flex;
    align-items: center;
    gap: 6px;
    color: white;
    font-weight: 600;
}

.rs-explorar-vacio,
.rs-explorar-vacio-full {
    text-align: center;
    padding: 60px 20px;
    color: var(--rs-text-muted);
}

.rs-explorar-vacio-full {
    grid-column: 1 / -1;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
}

.rs-explorar-vacio svg,
.rs-explorar-vacio-full svg {
    margin-bottom: 16px;
    opacity: 0.5;
}

.rs-explorar-vacio p,
.rs-explorar-vacio-full p {
    margin: 0 0 8px;
    font-size: 16px;
    color: var(--rs-text);
}

/* Resultados de busqueda */
.rs-resultados-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--rs-border);
}

.rs-resultados-tab {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    background: var(--rs-bg-light);
    color: var(--rs-text-muted);
    border-radius: var(--rs-radius-sm);
    font-weight: 500;
    cursor: pointer;
    transition: var(--rs-transition);
}

.rs-resultados-tab:hover,
.rs-resultados-tab.active {
    background: var(--rs-primary);
    color: white;
}

.rs-tab-badge {
    padding: 2px 8px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    font-size: 12px;
}

.rs-resultados-seccion {
    margin-bottom: 32px;
}

.rs-resultados-titulo {
    margin: 0 0 16px;
    font-size: 18px;
    font-weight: 600;
}

.rs-resultados-usuarios {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rs-resultado-usuario {
    display: flex;
    align-items: center;
    padding: 16px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
}

.rs-resultado-usuario-link {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none;
    color: inherit;
    flex: 1;
}

.rs-resultado-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    object-fit: cover;
}

.rs-resultado-info h4 {
    margin: 0 0 2px;
    font-size: 16px;
}

.rs-resultado-info span {
    color: var(--rs-text-muted);
    font-size: 14px;
}

.rs-resultado-info p {
    margin: 6px 0 0;
    font-size: 14px;
    color: var(--rs-text-muted);
}

/* Post mini para resultados */
.rs-post-mini {
    display: flex;
    gap: 12px;
    padding: 16px;
    background: var(--rs-bg-card);
    border-radius: var(--rs-radius);
    box-shadow: var(--rs-shadow);
    cursor: pointer;
    transition: var(--rs-transition);
}

.rs-post-mini:hover {
    box-shadow: var(--rs-shadow-lg);
}

.rs-post-mini-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    flex-shrink: 0;
}

.rs-post-mini-contenido {
    flex: 1;
    min-width: 0;
}

.rs-post-mini-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.rs-post-mini-header strong {
    font-size: 15px;
}

.rs-post-mini-header span {
    color: var(--rs-text-muted);
    font-size: 13px;
}

.rs-post-mini p {
    margin: 0;
    color: var(--rs-text-muted);
    font-size: 14px;
}

@media (max-width: 900px) {
    .rs-explorar-layout {
        grid-template-columns: 1fr;
    }

    .rs-explorar-sidebar {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .rs-explorar-sidebar {
        grid-template-columns: 1fr;
    }

    .rs-explorar-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .rs-hashtag-header {
        flex-direction: column;
        gap: 16px;
        text-align: center;
    }
}
</style>
