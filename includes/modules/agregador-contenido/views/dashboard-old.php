<?php
/**
 * Dashboard del módulo Agregador de Contenido
 *
 * @package Flavor_Chat_IA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'overview';

// Estadísticas
$total_fuentes  = wp_count_posts( Flavor_Agregador_Contenido_Module::CPT_FUENTE )->publish;
$total_noticias = wp_count_posts( Flavor_Agregador_Contenido_Module::CPT_NOTICIA )->publish;
$total_videos   = wp_count_posts( Flavor_Agregador_Contenido_Module::CPT_VIDEO )->publish;

// Fuentes RSS
$fuentes = get_posts(
    array(
        'post_type'      => Flavor_Agregador_Contenido_Module::CPT_FUENTE,
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    )
);
?>
<div class="wrap flavor-admin-page">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-rss" style="margin-right: 10px;"></span>
        <?php esc_html_e( 'Agregador de Contenido Comunitario', 'flavor-platform' ); ?>
    </h1>

    <nav class="nav-tab-wrapper">
        <a href="?page=flavor-agregador&tab=overview"
           class="nav-tab <?php echo $active_tab === 'overview' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Resumen', 'flavor-platform' ); ?>
        </a>
        <a href="?page=flavor-agregador&tab=fuentes"
           class="nav-tab <?php echo $active_tab === 'fuentes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Fuentes RSS', 'flavor-platform' ); ?>
        </a>
        <a href="?page=flavor-agregador&tab=videos"
           class="nav-tab <?php echo $active_tab === 'videos' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Videos YouTube', 'flavor-platform' ); ?>
        </a>
        <a href="?page=flavor-agregador&tab=shortcodes"
           class="nav-tab <?php echo $active_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e( 'Shortcodes', 'flavor-platform' ); ?>
        </a>
    </nav>

    <div class="tab-content" style="margin-top: 20px;">
        <?php if ( $active_tab === 'overview' ) : ?>
            <!-- Panel de Resumen -->
            <div class="flavor-dashboard-widgets" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 36px; font-weight: bold; color: #3b82f6;"><?php echo esc_html( $total_fuentes ); ?></div>
                    <div style="color: #64748b;"><?php esc_html_e( 'Fuentes RSS', 'flavor-platform' ); ?></div>
                    <a href="<?php echo admin_url( 'edit.php?post_type=' . Flavor_Agregador_Contenido_Module::CPT_FUENTE ); ?>" style="font-size: 12px;">
                        <?php esc_html_e( 'Gestionar →', 'flavor-platform' ); ?>
                    </a>
                </div>

                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 36px; font-weight: bold; color: #22c55e;"><?php echo esc_html( $total_noticias ); ?></div>
                    <div style="color: #64748b;"><?php esc_html_e( 'Noticias Importadas', 'flavor-platform' ); ?></div>
                    <a href="<?php echo admin_url( 'edit.php?post_type=' . Flavor_Agregador_Contenido_Module::CPT_NOTICIA ); ?>" style="font-size: 12px;">
                        <?php esc_html_e( 'Ver todas →', 'flavor-platform' ); ?>
                    </a>
                </div>

                <div class="flavor-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <div style="font-size: 36px; font-weight: bold; color: #ef4444;"><?php echo esc_html( $total_videos ); ?></div>
                    <div style="color: #64748b;"><?php esc_html_e( 'Videos YouTube', 'flavor-platform' ); ?></div>
                    <a href="<?php echo admin_url( 'edit.php?post_type=' . Flavor_Agregador_Contenido_Module::CPT_VIDEO ); ?>" style="font-size: 12px;">
                        <?php esc_html_e( 'Ver todos →', 'flavor-platform' ); ?>
                    </a>
                </div>
            </div>

            <!-- Últimas noticias -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px; margin: 0; border-bottom: 1px solid #ccd0d4;">
                    <?php esc_html_e( 'Últimas Noticias Importadas', 'flavor-platform' ); ?>
                </h2>
                <div class="inside" style="padding: 0;">
                    <?php
                    $ultimas_noticias = get_posts(
                        array(
                            'post_type'      => Flavor_Agregador_Contenido_Module::CPT_NOTICIA,
                            'posts_per_page' => 5,
                        )
                    );
                    ?>
                    <?php if ( $ultimas_noticias ) : ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Título', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'Fuente', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'Fecha', 'flavor-platform' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $ultimas_noticias as $noticia ) : ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo get_edit_post_link( $noticia ); ?>">
                                                <?php echo esc_html( $noticia->post_title ); ?>
                                            </a>
                                        </td>
                                        <td><?php echo esc_html( get_post_meta( $noticia->ID, '_source_name', true ) ); ?></td>
                                        <td><?php echo get_the_date( '', $noticia ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p style="padding: 20px; color: #64748b; text-align: center;">
                            <?php esc_html_e( 'No hay noticias importadas aún. Añade una fuente RSS para empezar.', 'flavor-platform' ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ( $active_tab === 'fuentes' ) : ?>
            <!-- Gestión de Fuentes RSS -->
            <div class="postbox">
                <h2 class="hndle" style="padding: 10px 15px; margin: 0; display: flex; justify-content: space-between; align-items: center;">
                    <span><?php esc_html_e( 'Fuentes RSS Configuradas', 'flavor-platform' ); ?></span>
                    <a href="<?php echo admin_url( 'post-new.php?post_type=' . Flavor_Agregador_Contenido_Module::CPT_FUENTE ); ?>" class="button button-primary">
                        <?php esc_html_e( '+ Añadir Fuente', 'flavor-platform' ); ?>
                    </a>
                </h2>
                <div class="inside" style="padding: 0;">
                    <?php if ( $fuentes ) : ?>
                        <table class="wp-list-table widefat striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Nombre', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'URL', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'Filtro', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'Auto', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'Última Import.', 'flavor-platform' ); ?></th>
                                    <th><?php esc_html_e( 'Acciones', 'flavor-platform' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $fuentes as $fuente ) :
                                    $feed_url    = get_post_meta( $fuente->ID, '_feed_url', true );
                                    $keywords    = get_post_meta( $fuente->ID, '_keywords', true );
                                    $auto_import = get_post_meta( $fuente->ID, '_auto_import', true );
                                    $last_import = get_post_meta( $fuente->ID, '_last_import', true );
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html( $fuente->post_title ); ?></strong>
                                        </td>
                                        <td>
                                            <code style="font-size: 11px;"><?php echo esc_url( $feed_url ); ?></code>
                                        </td>
                                        <td>
                                            <?php if ( $keywords ) : ?>
                                                <span style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 3px; font-size: 11px;">
                                                    <?php echo esc_html( $keywords ); ?>
                                                </span>
                                            <?php else : ?>
                                                <span style="color: #94a3b8; font-size: 11px;">Sin filtro</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ( $auto_import ) : ?>
                                                <span style="color: #22c55e;">✓</span>
                                            <?php else : ?>
                                                <span style="color: #94a3b8;">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $last_import ? esc_html( date_i18n( 'd/m/Y H:i', strtotime( $last_import ) ) ) : '—'; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo get_edit_post_link( $fuente ); ?>" class="button button-small">
                                                <?php esc_html_e( 'Editar', 'flavor-platform' ); ?>
                                            </a>
                                            <button type="button" class="button button-small import-feed-btn" data-fuente-id="<?php echo esc_attr( $fuente->ID ); ?>">
                                                <?php esc_html_e( 'Importar', 'flavor-platform' ); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <div style="padding: 40px; text-align: center;">
                            <span class="dashicons dashicons-rss" style="font-size: 48px; color: #94a3b8;"></span>
                            <p style="color: #64748b; margin: 15px 0;">
                                <?php esc_html_e( 'No hay fuentes RSS configuradas.', 'flavor-platform' ); ?>
                            </p>
                            <a href="<?php echo admin_url( 'post-new.php?post_type=' . Flavor_Agregador_Contenido_Module::CPT_FUENTE ); ?>" class="button button-primary button-large">
                                <?php esc_html_e( 'Añadir Primera Fuente', 'flavor-platform' ); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="postbox" style="margin-top: 20px;">
                <h2 class="hndle" style="padding: 10px 15px; margin: 0;">
                    <?php esc_html_e( 'Ejemplos de Fuentes RSS', 'flavor-platform' ); ?>
                </h2>
                <div class="inside">
                    <p><?php esc_html_e( 'Algunos ejemplos de feeds RSS de medios:', 'flavor-platform' ); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><code>https://elpais.com/rss/elpais/portada.xml</code> - El País</li>
                        <li><code>https://www.20minutos.es/rss/</code> - 20 Minutos</li>
                        <li><code>https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/espana/subsection/pais-vasco</code> - El País (País Vasco)</li>
                        <li><code>https://www.naiz.eus/rss/</code> - Naiz</li>
                    </ul>
                    <p style="color: #64748b; font-size: 12px;">
                        <?php esc_html_e( 'Usa el campo "Palabras clave" para filtrar solo las noticias que mencionen tu comunidad, pueblo o asociación.', 'flavor-platform' ); ?>
                    </p>
                </div>
            </div>

        <?php elseif ( $active_tab === 'videos' ) : ?>
            <!-- Añadir Videos YouTube -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px; margin: 0;">
                    <?php esc_html_e( 'Añadir Video de YouTube', 'flavor-platform' ); ?>
                </h2>
                <div class="inside">
                    <form id="add-youtube-form" style="display: flex; gap: 10px; align-items: flex-start;">
                        <div style="flex: 1;">
                            <input type="url" id="youtube-url" placeholder="https://youtube.com/watch?v=..." class="large-text" required>
                            <p class="description"><?php esc_html_e( 'Pega la URL de un video de YouTube', 'flavor-platform' ); ?></p>
                        </div>
                        <button type="submit" class="button button-primary">
                            <?php esc_html_e( 'Añadir Video', 'flavor-platform' ); ?>
                        </button>
                    </form>
                    <div id="youtube-result" style="margin-top: 15px;"></div>
                </div>
            </div>

            <!-- Lista de Videos -->
            <div class="postbox">
                <h2 class="hndle" style="padding: 10px 15px; margin: 0; display: flex; justify-content: space-between;">
                    <span><?php esc_html_e( 'Videos Añadidos', 'flavor-platform' ); ?></span>
                    <a href="<?php echo admin_url( 'edit.php?post_type=' . Flavor_Agregador_Contenido_Module::CPT_VIDEO ); ?>" class="button">
                        <?php esc_html_e( 'Ver todos', 'flavor-platform' ); ?>
                    </a>
                </h2>
                <div class="inside" style="padding: 0;">
                    <?php
                    $videos = get_posts(
                        array(
                            'post_type'      => Flavor_Agregador_Contenido_Module::CPT_VIDEO,
                            'posts_per_page' => 8,
                        )
                    );
                    ?>
                    <?php if ( $videos ) : ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; padding: 15px;">
                            <?php foreach ( $videos as $video ) :
                                $video_id = get_post_meta( $video->ID, '_video_id', true );
                            ?>
                                <div style="background: #f8fafc; border-radius: 8px; overflow: hidden;">
                                    <div style="aspect-ratio: 16/9; position: relative;">
                                        <img src="https://img.youtube.com/vi/<?php echo esc_attr( $video_id ); ?>/hqdefault.jpg"
                                             alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    <div style="padding: 10px;">
                                        <div style="font-size: 13px; font-weight: 500; line-height: 1.3;">
                                            <?php echo esc_html( wp_trim_words( $video->post_title, 8 ) ); ?>
                                        </div>
                                        <div style="margin-top: 8px;">
                                            <a href="<?php echo get_edit_post_link( $video ); ?>" style="font-size: 11px;">Editar</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <p style="padding: 30px; text-align: center; color: #64748b;">
                            <?php esc_html_e( 'No hay videos añadidos aún.', 'flavor-platform' ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        <?php elseif ( $active_tab === 'shortcodes' ) : ?>
            <!-- Documentación de Shortcodes -->
            <div class="postbox">
                <h2 class="hndle" style="padding: 10px 15px; margin: 0;">
                    <?php esc_html_e( 'Shortcodes Disponibles', 'flavor-platform' ); ?>
                </h2>
                <div class="inside">
                    <h3><?php esc_html_e( 'Grid de Noticias', 'flavor-platform' ); ?></h3>
                    <code style="display: block; background: #f1f5f9; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                        [agregador_noticias limite="6" columnas="3" categoria="local" mostrar_fuente="true"]
                    </code>
                    <p class="description" style="margin-bottom: 20px;">
                        <strong>Atributos:</strong> limite, columnas (2-4), categoria, fuente (ID), mostrar_fuente, mostrar_fecha, mostrar_extracto
                    </p>

                    <h3><?php esc_html_e( 'Grid de Videos', 'flavor-platform' ); ?></h3>
                    <code style="display: block; background: #f1f5f9; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                        [agregador_videos limite="6" columnas="3" categoria="eventos" layout="grid"]
                    </code>
                    <p class="description" style="margin-bottom: 20px;">
                        <strong>Atributos:</strong> limite, columnas (2-4), categoria, canal, layout (grid/list)
                    </p>

                    <h3><?php esc_html_e( 'Feed Combinado', 'flavor-platform' ); ?></h3>
                    <code style="display: block; background: #f1f5f9; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                        [agregador_feed_combinado limite="12" columnas="4" categoria="comunidad"]
                    </code>
                    <p class="description" style="margin-bottom: 20px;">
                        Muestra noticias y videos mezclados ordenados por fecha.
                    </p>

                    <h3><?php esc_html_e( 'Carrusel de Videos', 'flavor-platform' ); ?></h3>
                    <code style="display: block; background: #f1f5f9; padding: 15px; border-radius: 6px; margin-bottom: 10px;">
                        [agregador_carrusel_videos limite="8" categoria="destacados" autoplay="true"]
                    </code>
                    <p class="description">
                        Carrusel horizontal con navegación. Ideal para sección destacada.
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Añadir video YouTube
    $('#add-youtube-form').on('submit', function(e) {
        e.preventDefault();
        var url = $('#youtube-url').val();
        var $result = $('#youtube-result');
        var $btn = $(this).find('button');

        $btn.prop('disabled', true).text('Añadiendo...');
        $result.html('');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'flavor_add_youtube_video',
                video_url: url,
                nonce: '<?php echo wp_create_nonce( 'flavor_agregador' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success"><p>Video añadido: <strong>' + response.data.title + '</strong> <a href="' + response.data.edit_url + '">Editar</a></p></div>');
                    $('#youtube-url').val('');
                } else {
                    $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error"><p>Error de conexión</p></div>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Añadir Video');
            }
        });
    });

    // Importar feed
    $('.import-feed-btn').on('click', function() {
        var $btn = $(this);
        var fuenteId = $btn.data('fuente-id');

        $btn.prop('disabled', true).text('Importando...');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'flavor_import_single_feed',
                fuente_id: fuenteId,
                nonce: '<?php echo wp_create_nonce( 'flavor_agregador' ); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Importación completada: ' + response.data.imported + ' nuevas, ' + response.data.skipped + ' omitidas');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('Error de conexión');
            },
            complete: function() {
                $btn.prop('disabled', false).text('Importar');
            }
        });
    });
});
</script>
