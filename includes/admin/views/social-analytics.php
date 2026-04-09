<?php
/**
 * Vista del Dashboard de Analytics Social
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$periodos = [
    'hoy' => __('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'semana' => __('Última semana', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'mes' => __('Último mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'trimestre' => __('Último trimestre', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'año' => __('Último año', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap flavor-analytics-wrap">
    <h1 class="flavor-analytics-title">
        <span class="dashicons dashicons-chart-area"></span>
        <?php esc_html_e('Analytics Social', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <!-- Selector de período -->
    <div class="flavor-analytics-toolbar">
        <div class="periodo-selector">
            <label for="periodo-select"><?php esc_html_e('Período:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="periodo-select" class="periodo-select">
                <?php foreach ($periodos as $valor => $etiqueta): ?>
                    <option value="<?php echo esc_attr($valor); ?>" <?php selected($periodo, $valor); ?>>
                        <?php echo esc_html($etiqueta); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="toolbar-actions">
            <button type="button" id="btn-refresh" class="button" title="<?php esc_attr_e('Actualizar datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <span class="dashicons dashicons-update"></span>
            </button>
            <button type="button" id="btn-export-csv" class="button">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Exportar CSV', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" id="btn-export-json" class="button">
                <span class="dashicons dashicons-media-code"></span>
                <?php esc_html_e('Exportar JSON', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        </div>
    </div>

    <!-- KPIs principales -->
    <div class="flavor-analytics-kpis">
        <div class="kpi-card" data-kpi="usuarios">
            <div class="kpi-icon">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value"><?php echo esc_html($stats['usuarios_activos'] ?? 0); ?></span>
                <span class="kpi-label"><?php esc_html_e('Usuarios Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if (isset($stats['tendencia_usuarios'])): ?>
                    <span class="kpi-trend <?php echo $stats['tendencia_usuarios'] >= 0 ? 'positive' : 'negative'; ?>">
                        <span class="dashicons <?php echo $stats['tendencia_usuarios'] >= 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'; ?>"></span>
                        <?php echo esc_html(abs($stats['tendencia_usuarios'])); ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="kpi-card" data-kpi="nuevos">
            <div class="kpi-icon icon-new">
                <span class="dashicons dashicons-admin-users"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value"><?php echo esc_html($stats['usuarios_nuevos'] ?? 0); ?></span>
                <span class="kpi-label"><?php esc_html_e('Usuarios Nuevos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="kpi-card" data-kpi="publicaciones">
            <div class="kpi-icon icon-posts">
                <span class="dashicons dashicons-edit"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value"><?php echo esc_html($stats['publicaciones'] ?? 0); ?></span>
                <span class="kpi-label"><?php esc_html_e('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php if (isset($stats['tendencia_publicaciones'])): ?>
                    <span class="kpi-trend <?php echo $stats['tendencia_publicaciones'] >= 0 ? 'positive' : 'negative'; ?>">
                        <span class="dashicons <?php echo $stats['tendencia_publicaciones'] >= 0 ? 'dashicons-arrow-up-alt' : 'dashicons-arrow-down-alt'; ?>"></span>
                        <?php echo esc_html(abs($stats['tendencia_publicaciones'])); ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="kpi-card" data-kpi="engagement">
            <div class="kpi-icon icon-engagement">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value"><?php echo esc_html($stats['engagement_rate'] ?? 0); ?>%</span>
                <span class="kpi-label"><?php esc_html_e('Engagement Rate', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="kpi-card" data-kpi="likes">
            <div class="kpi-icon icon-likes">
                <span class="dashicons dashicons-thumbs-up"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value"><?php echo esc_html($stats['likes'] ?? 0); ?></span>
                <span class="kpi-label"><?php esc_html_e('Likes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>

        <div class="kpi-card" data-kpi="comentarios">
            <div class="kpi-icon icon-comments">
                <span class="dashicons dashicons-admin-comments"></span>
            </div>
            <div class="kpi-content">
                <span class="kpi-value"><?php echo esc_html($stats['comentarios'] ?? 0); ?></span>
                <span class="kpi-label"><?php esc_html_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Gráfico de actividad -->
    <div class="flavor-analytics-section">
        <div class="section-header">
            <h2><?php esc_html_e('Actividad en el Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        </div>
        <div class="section-content">
            <div class="chart-container">
                <canvas id="chart-actividad"></canvas>
            </div>
        </div>
    </div>

    <!-- Grid de paneles -->
    <div class="flavor-analytics-grid">
        <!-- Hashtags Trending -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h3>
                    <span class="dashicons dashicons-tag"></span>
                    <?php esc_html_e('Hashtags Trending', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="panel-content">
                <?php if (!empty($trending)): ?>
                    <ul class="trending-list">
                        <?php foreach ($trending as $indice => $hashtag): ?>
                            <li class="trending-item">
                                <span class="trending-rank"><?php echo esc_html($indice + 1); ?></span>
                                <span class="trending-tag">#<?php echo esc_html($hashtag->nombre); ?></span>
                                <span class="trending-count"><?php echo esc_html($hashtag->usos); ?> usos</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data"><?php esc_html_e('Sin hashtags en este período', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Usuarios -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h3>
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php esc_html_e('Top Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="panel-content">
                <?php if (!empty($top_users)): ?>
                    <ul class="top-users-list">
                        <?php foreach ($top_users as $indice => $usuario): ?>
                            <li class="user-item">
                                <span class="user-rank"><?php echo esc_html($indice + 1); ?></span>
                                <img src="<?php echo esc_url($usuario->avatar); ?>" alt="" class="user-avatar">
                                <div class="user-info">
                                    <span class="user-name"><?php echo esc_html($usuario->display_name); ?></span>
                                    <span class="user-stats">
                                        <?php printf(
                                            esc_html__('%d seguidores · %d posts', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                            $usuario->seguidores_count,
                                            $usuario->publicaciones_count
                                        ); ?>
                                    </span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="no-data"><?php esc_html_e('Sin usuarios con actividad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Top Publicaciones -->
        <div class="analytics-panel panel-wide">
            <div class="panel-header">
                <h3>
                    <span class="dashicons dashicons-format-status"></span>
                    <?php esc_html_e('Publicaciones Destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="panel-content">
                <?php if (!empty($top_posts)): ?>
                    <table class="widefat top-posts-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="num"><?php esc_html_e('Likes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="num"><?php esc_html_e('Comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="num"><?php esc_html_e('Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th class="num"><?php esc_html_e('Engagement', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($top_posts as $post): ?>
                                <tr>
                                    <td class="author-cell">
                                        <img src="<?php echo esc_url($post->avatar); ?>" alt="" class="post-avatar">
                                        <?php echo esc_html($post->display_name); ?>
                                    </td>
                                    <td class="content-cell" title="<?php echo esc_attr($post->contenido); ?>">
                                        <?php echo esc_html($post->contenido_corto); ?>
                                    </td>
                                    <td class="num"><?php echo esc_html($post->likes_count); ?></td>
                                    <td class="num"><?php echo esc_html($post->comentarios_count); ?></td>
                                    <td class="num"><?php echo esc_html($post->compartidos_count); ?></td>
                                    <td class="num engagement-score"><?php echo esc_html($post->engagement); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-data"><?php esc_html_e('Sin publicaciones destacadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comunidades -->
        <div class="analytics-panel">
            <div class="panel-header">
                <h3>
                    <span class="dashicons dashicons-networking"></span>
                    <?php esc_html_e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="panel-content">
                <?php if (!empty($comunidades_stats)): ?>
                    <div class="comunidades-summary">
                        <div class="summary-stat">
                            <span class="stat-value"><?php echo esc_html($comunidades_stats['total'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value"><?php echo esc_html($comunidades_stats['nuevas'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Nuevas (7d)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </div>

                    <?php if (!empty($comunidades_stats['top_comunidades'])): ?>
                        <h4><?php esc_html_e('Top por Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <ul class="comunidades-list">
                            <?php foreach ($comunidades_stats['top_comunidades'] as $comunidad): ?>
                                <li class="comunidad-item">
                                    <span class="comunidad-nombre"><?php echo esc_html($comunidad->nombre); ?></span>
                                    <span class="comunidad-miembros"><?php echo esc_html($comunidad->miembros_count); ?> miembros</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="no-data"><?php esc_html_e('Sin datos de comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Estados/Stories -->
        <?php $estados_stats = $this->get_estados_stats(); ?>
        <div class="analytics-panel">
            <div class="panel-header">
                <h3>
                    <span class="dashicons dashicons-format-gallery"></span>
                    <?php esc_html_e('Estados (Stories)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>
            </div>
            <div class="panel-content">
                <?php if (!empty($estados_stats)): ?>
                    <div class="estados-summary">
                        <div class="summary-stat">
                            <span class="stat-value"><?php echo esc_html($estados_stats['ultimas_24h'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Estados (24h)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <div class="summary-stat">
                            <span class="stat-value"><?php echo esc_html($estados_stats['usuarios_activos'] ?? 0); ?></span>
                            <span class="stat-label"><?php esc_html_e('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                        <?php if (isset($estados_stats['vistas_24h'])): ?>
                            <div class="summary-stat">
                                <span class="stat-value"><?php echo esc_html($estados_stats['vistas_24h']); ?></span>
                                <span class="stat-label"><?php esc_html_e('Vistas (24h)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data"><?php esc_html_e('Sin datos de estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Datos ocultos para JS -->
    <script type="application/json" id="chart-data">
        <?php echo wp_json_encode($stats['grafico_actividad'] ?? []); ?>
    </script>
</div>
