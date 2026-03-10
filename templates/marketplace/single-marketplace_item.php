<?php
/**
 * Template para mostrar un anuncio individual del Marketplace
 *
 * Este archivo puede ser sobrescrito copiándolo a:
 * tu-tema/flavor-chat-ia/marketplace/single-marketplace_item.php
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

while (have_posts()) : the_post();
    $post_id = get_the_ID();

    // Obtener datos del anuncio
    $precio = get_post_meta($post_id, '_marketplace_precio', true);
    $estado_conservacion = get_post_meta($post_id, '_marketplace_estado', true);
    $ubicacion = get_post_meta($post_id, '_marketplace_ubicacion', true);
    $contacto_preferido = get_post_meta($post_id, '_marketplace_contacto', true);
    $fecha_expiracion = get_post_meta($post_id, '_marketplace_fecha_expiracion', true);
    $intercambio_preferencias = get_post_meta($post_id, '_marketplace_intercambio_prefs', true);

    // Obtener taxonomías
    $tipos = wp_get_post_terms($post_id, 'marketplace_tipo');
    $categorias = wp_get_post_terms($post_id, 'marketplace_categoria');

    // Autor del anuncio
    $autor_id = get_the_author_meta('ID');
    $autor_nombre = get_the_author();
    $autor_avatar = Flavor_Chat_Helpers::obtener_avatar_url($autor_id, 96);
    $fecha_publicacion = Flavor_Chat_Helpers::tiempo_transcurrido(get_the_time('U'));

    // Traducciones de estado
    $estados_traduccion = [
        'nuevo' => __('Nuevo', 'flavor-chat-ia'),
        'como_nuevo' => __('Como nuevo', 'flavor-chat-ia'),
        'buen_estado' => __('Buen estado', 'flavor-chat-ia'),
        'usado' => __('Usado', 'flavor-chat-ia'),
        'reparar' => __('Necesita reparación', 'flavor-chat-ia'),
    ];
    ?>

    <article id="marketplace-<?php the_ID(); ?>" <?php post_class('marketplace-single'); ?>>

        <div class="marketplace-container">

            <!-- Galería de imágenes -->
            <div class="marketplace-gallery">
                <?php if (has_post_thumbnail()): ?>
                    <div class="marketplace-imagen-principal">
                        <?php the_post_thumbnail('large', ['class' => 'marketplace-img']); ?>
                    </div>
                <?php else: ?>
                    <div class="marketplace-imagen-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                        <p><?php _e('Sin imagen', 'flavor-chat-ia'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Badges -->
                <div class="marketplace-badges">
                    <?php if (!empty($tipos)): ?>
                        <span class="marketplace-badge marketplace-badge-tipo marketplace-tipo-<?php echo esc_attr($tipos[0]->slug); ?>">
                            <?php echo esc_html($tipos[0]->name); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($estado_conservacion): ?>
                        <span class="marketplace-badge marketplace-badge-estado">
                            <?php echo esc_html($estados_traduccion[$estado_conservacion] ?? $estado_conservacion); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información del anuncio -->
            <div class="marketplace-info">

                <!-- Header -->
                <header class="marketplace-header">
                    <h1 class="marketplace-titulo"><?php the_title(); ?></h1>

                    <?php if ($precio && in_array($tipos[0]->slug ?? '', ['venta', 'alquiler'])): ?>
                        <div class="marketplace-precio">
                            <?php echo Flavor_Chat_Helpers::formatear_precio($precio); ?>
                            <?php if ($tipos[0]->slug === 'alquiler'): ?>
                                <span class="marketplace-precio-periodo"><?php _e('/ día', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($tipos[0]->slug ?? '' === 'regalo'): ?>
                        <div class="marketplace-precio marketplace-precio-regalo">
                            <?php _e('¡GRATIS!', 'flavor-chat-ia'); ?>
                        </div>
                    <?php endif; ?>
                </header>

                <!-- Metadatos -->
                <div class="marketplace-meta">
                    <div class="marketplace-meta-item">
                        <span class="dashicons dashicons-location"></span>
                        <span><?php echo $ubicacion ? esc_html($ubicacion) : __('No especificada', 'flavor-chat-ia'); ?></span>
                    </div>

                    <div class="marketplace-meta-item">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span><?php echo esc_html($fecha_publicacion); ?></span>
                    </div>

                    <?php if (!empty($categorias)): ?>
                        <div class="marketplace-meta-item">
                            <span class="dashicons dashicons-category"></span>
                            <span><?php echo esc_html($categorias[0]->name); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($fecha_expiracion): ?>
                        <div class="marketplace-meta-item">
                            <span class="dashicons dashicons-clock"></span>
                            <span>
                                <?php
                                printf(
                                    __('Válido hasta %s', 'flavor-chat-ia'),
                                    Flavor_Chat_Helpers::formatear_fecha($fecha_expiracion, 'short')
                                );
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Descripción -->
                <div class="marketplace-descripcion">
                    <h2><?php _e('Descripción', 'flavor-chat-ia'); ?></h2>
                    <div class="marketplace-contenido">
                        <?php the_content(); ?>
                    </div>
                </div>

                <!-- Preferencias de intercambio (si es cambio) -->
                <?php if ($tipos[0]->slug ?? '' === 'cambio' && $intercambio_preferencias): ?>
                    <div class="marketplace-intercambio">
                        <h3><?php _e('Busco a cambio:', 'flavor-chat-ia'); ?></h3>
                        <p><?php echo esc_html($intercambio_preferencias); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Información del vendedor -->
                <div class="marketplace-vendedor">
                    <h3><?php _e('Publicado por', 'flavor-chat-ia'); ?></h3>
                    <div class="marketplace-vendedor-info">
                        <?php if ($autor_avatar): ?>
                            <img src="<?php echo esc_url($autor_avatar); ?>"
                                 alt="<?php echo esc_attr($autor_nombre); ?>"
                                 class="marketplace-vendedor-avatar" />
                        <?php endif; ?>
                        <div class="marketplace-vendedor-datos">
                            <strong><?php echo esc_html($autor_nombre); ?></strong>
                            <span class="marketplace-vendedor-registro">
                                <?php
                                printf(
                                    __('Miembro desde %s', 'flavor-chat-ia'),
                                    Flavor_Chat_Helpers::formatear_fecha(get_the_author_meta('user_registered', $autor_id), 'short')
                                );
                                ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Botón de contacto -->
                <div class="marketplace-acciones">
                    <?php if (is_user_logged_in()): ?>
                        <?php if ($autor_id !== get_current_user_id()): ?>
                            <button class="marketplace-btn marketplace-btn-contactar"
                                    data-anuncio-id="<?php echo esc_attr($post_id); ?>"
                                    data-vendedor-id="<?php echo esc_attr($autor_id); ?>">
                                <span class="dashicons dashicons-email"></span>
                                <?php _e('Contactar', 'flavor-chat-ia'); ?>
                            </button>

                            <button class="marketplace-btn marketplace-btn-secundario marketplace-btn-favorito"
                                    data-anuncio-id="<?php echo esc_attr($post_id); ?>">
                                <span class="dashicons dashicons-heart"></span>
                                <?php _e('Guardar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo get_edit_post_link(); ?>" class="marketplace-btn marketplace-btn-secundario">
                                <span class="dashicons dashicons-edit"></span>
                                <?php _e('Editar anuncio', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="marketplace-aviso">
                            <?php
                            printf(
                                __('<a href="%s">Inicia sesión</a> para contactar con el vendedor', 'flavor-chat-ia'),
                                wp_login_url(flavor_current_request_url())
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Compartir en redes sociales -->
                <div class="marketplace-compartir">
                    <span><?php _e('Compartir:', 'flavor-chat-ia'); ?></span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>"
                       target="_blank" class="marketplace-social-link">
                        <span class="dashicons dashicons-facebook"></span>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(get_permalink()); ?>&text=<?php echo urlencode(get_the_title()); ?>"
                       target="_blank" class="marketplace-social-link">
                        <span class="dashicons dashicons-twitter"></span>
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode(get_the_title() . ' ' . get_permalink()); ?>"
                       target="_blank" class="marketplace-social-link">
                        <span class="dashicons dashicons-whatsapp"></span>
                    </a>
                </div>

            </div>

        </div>

        <!-- Anuncios relacionados -->
        <?php
        $anuncios_relacionados = new WP_Query([
            'post_type' => 'marketplace_item',
            'posts_per_page' => 3,
            'post__not_in' => [$post_id],
            'tax_query' => [
                [
                    'taxonomy' => 'marketplace_categoria',
                    'terms' => wp_get_post_terms($post_id, 'marketplace_categoria', ['fields' => 'ids']),
                ],
            ],
        ]);

        if ($anuncios_relacionados->have_posts()): ?>
            <div class="marketplace-relacionados">
                <h2><?php _e('Anuncios relacionados', 'flavor-chat-ia'); ?></h2>
                <div class="marketplace-grid">
                    <?php while ($anuncios_relacionados->have_posts()): $anuncios_relacionados->the_post(); ?>
                        <div class="marketplace-card">
                            <a href="<?php the_permalink(); ?>" class="marketplace-card-link">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('medium'); ?>
                                <?php endif; ?>
                                <h3><?php the_title(); ?></h3>
                                <?php
                                $precio_rel = get_post_meta(get_the_ID(), '_marketplace_precio', true);
                                if ($precio_rel): ?>
                                    <p class="marketplace-card-precio"><?php echo Flavor_Chat_Helpers::formatear_precio($precio_rel); ?></p>
                                <?php endif; ?>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php wp_reset_postdata(); ?>
        <?php endif; ?>

    </article>

    <style>
    /* Estilos básicos del marketplace */
    .marketplace-single {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .marketplace-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        margin-bottom: 40px;
    }

    .marketplace-gallery {
        position: relative;
    }

    .marketplace-imagen-principal img,
    .marketplace-imagen-placeholder {
        width: 100%;
        height: 500px;
        object-fit: cover;
        border-radius: 12px;
    }

    .marketplace-imagen-placeholder {
        background: #f0f0f0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #999;
    }

    .marketplace-badges {
        position: absolute;
        top: 15px;
        left: 15px;
        display: flex;
        gap: 8px;
    }

    .marketplace-badge {
        background: white;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 600;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .marketplace-tipo-regalo { color: #4caf50; }
    .marketplace-tipo-venta { color: #2196f3; }
    .marketplace-tipo-cambio { color: #ff9800; }
    .marketplace-tipo-alquiler { color: #9c27b0; }

    .marketplace-header {
        margin-bottom: 24px;
    }

    .marketplace-titulo {
        font-size: 32px;
        margin: 0 0 12px 0;
    }

    .marketplace-precio {
        font-size: 28px;
        font-weight: 700;
        color: #2196f3;
    }

    .marketplace-precio-regalo {
        color: #4caf50;
    }

    .marketplace-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        padding: 20px 0;
        border-top: 1px solid #eee;
        border-bottom: 1px solid #eee;
        margin-bottom: 24px;
    }

    .marketplace-meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
        color: #666;
    }

    .marketplace-descripcion h2 {
        font-size: 20px;
        margin-bottom: 16px;
    }

    .marketplace-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .marketplace-btn-contactar {
        background: #2196f3;
        color: white;
    }

    .marketplace-btn-contactar:hover {
        background: #1976d2;
    }

    .marketplace-btn-secundario {
        background: #f5f5f5;
        color: #333;
    }

    .marketplace-acciones {
        display: flex;
        gap: 12px;
        margin: 24px 0;
    }

    .marketplace-vendedor {
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin: 24px 0;
    }

    .marketplace-vendedor-info {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-top: 12px;
    }

    .marketplace-vendedor-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
    }

    .marketplace-compartir {
        display: flex;
        align-items: center;
        gap: 12px;
        padding-top: 16px;
        border-top: 1px solid #eee;
    }

    .marketplace-social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        background: #f0f0f0;
        border-radius: 50%;
        color: #666;
        transition: all 0.3s;
    }

    .marketplace-social-link:hover {
        background: #2196f3;
        color: white;
    }

    .marketplace-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 24px;
    }

    .marketplace-card {
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: transform 0.3s;
    }

    .marketplace-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .marketplace-card img {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }

    .marketplace-card h3 {
        padding: 12px 16px 8px;
        font-size: 16px;
        margin: 0;
    }

    .marketplace-card-precio {
        padding: 0 16px 12px;
        font-weight: 600;
        color: #2196f3;
        margin: 0;
    }

    @media (max-width: 768px) {
        .marketplace-container {
            grid-template-columns: 1fr;
        }

        .marketplace-acciones {
            flex-direction: column;
        }

        .marketplace-btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>

<?php endwhile;

get_footer();
