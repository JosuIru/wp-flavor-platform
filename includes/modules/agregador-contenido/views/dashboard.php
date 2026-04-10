<?php
/**
 * Dashboard Admin MEJORADO - Agregador de Contenido
 * Con widgets de datos en vivo de módulos relacionados
 *
 * @package Flavor_Platform
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

// ==================== WIDGETS DE DATOS EN VIVO ====================
$modulos_relacionados = [];
$active_modules = get_option('flavor_active_modules', []);
global $wpdb;

// 1. Multimedia - Galería de medios
if (in_array('multimedia', $active_modules)) {
    $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia_items';
    $tabla_multimedia_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_multimedia'") === $tabla_multimedia;

    if ($tabla_multimedia_existe) {
        $multimedia_recientes = $wpdb->get_results(
            "SELECT id, titulo, tipo, fecha_publicacion
             FROM $tabla_multimedia
             WHERE estado = 'publicado'
             ORDER BY fecha_publicacion DESC
             LIMIT 3"
        );

        if (!empty($multimedia_recientes)) {
            ob_start();
            ?>
            <div class="dm-widget-card">
                <div class="dm-widget-header">
                    <span class="dm-widget-icon">🎬</span>
                    <h4><?php esc_html_e('Multimedia', 'flavor-platform'); ?></h4>
                </div>
                <div class="dm-widget-items">
                    <?php foreach ($multimedia_recientes as $item): ?>
                    <div class="dm-widget-item">
                        <div class="dm-widget-item-title"><?php echo esc_html($item->titulo); ?></div>
                        <div class="dm-widget-item-meta">
                            <span class="dm-badge dm-badge--purple"><?php echo esc_html(ucfirst($item->tipo)); ?></span>
                            <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($item->fecha_publicacion))); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $modulos_relacionados['multimedia'] = ob_get_clean();
        }
    }
}

// 2. Podcast - Episodios de audio
if (in_array('podcast', $active_modules)) {
    $tabla_podcast = $wpdb->prefix . 'flavor_podcast_episodios';
    $tabla_podcast_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_podcast'") === $tabla_podcast;

    if ($tabla_podcast_existe) {
        $episodios_recientes = $wpdb->get_results(
            "SELECT id, titulo, numero, duracion, fecha_publicacion
             FROM $tabla_podcast
             WHERE estado = 'publicado'
             ORDER BY fecha_publicacion DESC
             LIMIT 3"
        );

        if (!empty($episodios_recientes)) {
            ob_start();
            ?>
            <div class="dm-widget-card">
                <div class="dm-widget-header">
                    <span class="dm-widget-icon">🎙️</span>
                    <h4><?php esc_html_e('Podcast', 'flavor-platform'); ?></h4>
                </div>
                <div class="dm-widget-items">
                    <?php foreach ($episodios_recientes as $item): ?>
                    <div class="dm-widget-item">
                        <div class="dm-widget-item-title"><?php echo esc_html($item->titulo); ?></div>
                        <div class="dm-widget-item-meta">
                            <span class="dm-badge dm-badge--info">Ep. <?php echo number_format_i18n($item->numero ?? 0); ?></span>
                            <span><?php echo number_format_i18n($item->duracion ?? 0); ?> min</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $modulos_relacionados['podcast'] = ob_get_clean();
        }
    }
}

// 3. Radio - Programas de radio
if (in_array('radio', $active_modules)) {
    $tabla_radio = $wpdb->prefix . 'flavor_radio_programas';
    $tabla_radio_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_radio'") === $tabla_radio;

    if ($tabla_radio_existe) {
        $programas_recientes = $wpdb->get_results(
            "SELECT id, nombre, categoria, horario, created_at
             FROM $tabla_radio
             WHERE estado = 'activo'
             ORDER BY created_at DESC
             LIMIT 3"
        );

        if (!empty($programas_recientes)) {
            ob_start();
            ?>
            <div class="dm-widget-card">
                <div class="dm-widget-header">
                    <span class="dm-widget-icon">📻</span>
                    <h4><?php esc_html_e('Radio', 'flavor-platform'); ?></h4>
                </div>
                <div class="dm-widget-items">
                    <?php foreach ($programas_recientes as $item): ?>
                    <div class="dm-widget-item">
                        <div class="dm-widget-item-title"><?php echo esc_html($item->nombre); ?></div>
                        <div class="dm-widget-item-meta">
                            <span class="dm-badge dm-badge--warning"><?php echo esc_html($item->categoria ?? 'Programa'); ?></span>
                            <span><?php echo esc_html($item->horario ?? ''); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $modulos_relacionados['radio'] = ob_get_clean();
        }
    }
}

// 4. Eventos - Eventos comunitarios que generan noticias
if (in_array('eventos', $active_modules)) {
    $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
    $tabla_eventos_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos;

    if ($tabla_eventos_existe) {
        $eventos_proximos = $wpdb->get_results(
            "SELECT id, titulo, categoria, fecha_inicio
             FROM $tabla_eventos
             WHERE estado = 'publicado'
             AND fecha_inicio >= CURDATE()
             ORDER BY fecha_inicio ASC
             LIMIT 3"
        );

        if (!empty($eventos_proximos)) {
            ob_start();
            ?>
            <div class="dm-widget-card">
                <div class="dm-widget-header">
                    <span class="dm-widget-icon">📅</span>
                    <h4><?php esc_html_e('Eventos', 'flavor-platform'); ?></h4>
                </div>
                <div class="dm-widget-items">
                    <?php foreach ($eventos_proximos as $item): ?>
                    <div class="dm-widget-item">
                        <div class="dm-widget-item-title"><?php echo esc_html($item->titulo); ?></div>
                        <div class="dm-widget-item-meta">
                            <span class="dm-badge dm-badge--primary"><?php echo esc_html($item->categoria ?? 'Evento'); ?></span>
                            <span><?php echo esc_html(date_i18n('d/m/Y', strtotime($item->fecha_inicio))); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $modulos_relacionados['eventos'] = ob_get_clean();
        }
    }
}

// 5. Comunidades - Grupos que generan contenido
if (in_array('comunidades', $active_modules)) {
    $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
    $tabla_comunidades_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_comunidades'") === $tabla_comunidades;

    if ($tabla_comunidades_existe) {
        $comunidades_activas = $wpdb->get_results(
            "SELECT id, nombre, tipo, num_miembros, created_at
             FROM $tabla_comunidades
             WHERE estado = 'activa'
             ORDER BY created_at DESC
             LIMIT 3"
        );

        if (!empty($comunidades_activas)) {
            ob_start();
            ?>
            <div class="dm-widget-card">
                <div class="dm-widget-header">
                    <span class="dm-widget-icon">🏘️</span>
                    <h4><?php esc_html_e('Comunidades', 'flavor-platform'); ?></h4>
                </div>
                <div class="dm-widget-items">
                    <?php foreach ($comunidades_activas as $item): ?>
                    <div class="dm-widget-item">
                        <div class="dm-widget-item-title"><?php echo esc_html($item->nombre); ?></div>
                        <div class="dm-widget-item-meta">
                            <span class="dm-badge dm-badge--success"><?php echo esc_html(ucfirst($item->tipo ?? 'comunidad')); ?></span>
                            <span>👥 <?php echo number_format_i18n($item->num_miembros ?? 0); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $modulos_relacionados['comunidades'] = ob_get_clean();
        }
    }
}

// 6. Foros - Debates sobre noticias
if (in_array('foros', $active_modules)) {
    $tabla_foros = $wpdb->prefix . 'flavor_foros_hilos';
    $tabla_foros_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_foros'") === $tabla_foros;

    if ($tabla_foros_existe) {
        $hilos_recientes = $wpdb->get_results(
            "SELECT id, titulo, num_respuestas, fecha_creacion
             FROM $tabla_foros
             WHERE estado = 'abierto'
             ORDER BY fecha_creacion DESC
             LIMIT 3"
        );

        if (!empty($hilos_recientes)) {
            ob_start();
            ?>
            <div class="dm-widget-card">
                <div class="dm-widget-header">
                    <span class="dm-widget-icon">💬</span>
                    <h4><?php esc_html_e('Foros', 'flavor-platform'); ?></h4>
                </div>
                <div class="dm-widget-items">
                    <?php foreach ($hilos_recientes as $item): ?>
                    <div class="dm-widget-item">
                        <div class="dm-widget-item-title"><?php echo esc_html($item->titulo); ?></div>
                        <div class="dm-widget-item-meta">
                            <span class="dm-badge dm-badge--info"><?php echo number_format_i18n($item->num_respuestas ?? 0); ?> resp.</span>
                            <span><?php echo esc_html(human_time_diff(strtotime($item->fecha_creacion), current_time('timestamp'))); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
            $modulos_relacionados['foros'] = ob_get_clean();
        }
    }
}
?>

<style>
.dm-widgets-relacionados {
    margin: 0 0 32px;
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    border-radius: 16px;
    padding: 24px;
    border: 2px solid #a78bfa;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.15);
}
.dm-widgets-relacionados h2 {
    color: white;
    font-size: 20px;
    font-weight: 600;
    margin: 0 0 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.dm-widgets-relacionados h2 span {
    font-size: 24px;
}
.dm-widgets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 16px;
}
.dm-widget-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
    border: 1px solid #e5e7eb;
}
.dm-widget-card:hover {
    box-shadow: 0 6px 20px rgba(139, 92, 246, 0.15);
    transform: translateY(-2px);
}
.dm-widget-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
    padding-bottom: 10px;
    border-bottom: 2px solid #f3f4f6;
}
.dm-widget-icon {
    font-size: 20px;
    line-height: 1;
}
.dm-widget-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
}
.dm-widget-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.dm-widget-item {
    padding: 8px 0;
}
.dm-widget-item:not(:last-child) {
    border-bottom: 1px solid #f3f4f6;
}
.dm-widget-item-title {
    font-size: 13px;
    font-weight: 500;
    color: #374151;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.dm-widget-item-meta {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #6b7280;
}
.dm-widget-item-meta .dm-badge {
    font-size: 11px;
    padding: 2px 8px;
}
</style>

<div class="wrap flavor-admin-page">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-rss" style="margin-right: 10px;"></span>
        <?php esc_html_e( 'Agregador de Contenido Comunitario', 'flavor-platform' ); ?>
    </h1>

    <?php if (!empty($modulos_relacionados)): ?>
    <!-- WIDGETS DE DATOS EN VIVO -->
    <div class="dm-widgets-relacionados" style="margin-top: 20px;">
        <h2><span>🔗</span> <?php esc_html_e('Actividad en Módulos Relacionados', 'flavor-platform'); ?></h2>
        <div class="dm-widgets-grid">
            <?php foreach ($modulos_relacionados as $widget_html): ?>
                <?php echo $widget_html; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

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
