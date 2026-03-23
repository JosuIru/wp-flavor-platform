<?php
/**
 * Vista Dashboard - Marketplace
 *
 * Dashboard administrativo operativo para el módulo Marketplace.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$post_counts = wp_count_posts('marketplace_item');
$anuncios_publicados = isset($post_counts->publish) ? (int) $post_counts->publish : 0;
$anuncios_borrador = isset($post_counts->draft) ? (int) $post_counts->draft : 0;
$anuncios_pendientes = isset($post_counts->pending) ? (int) $post_counts->pending : 0;
$anuncios_privados = isset($post_counts->private) ? (int) $post_counts->private : 0;
$total_anuncios = $anuncios_publicados + $anuncios_borrador + $anuncios_pendientes + $anuncios_privados;

$categorias = get_terms([
    'taxonomy' => 'marketplace_categoria',
    'hide_empty' => false,
]);
$categorias_stats = [];

if (!is_wp_error($categorias)) {
    foreach ($categorias as $cat) {
        $categorias_stats[] = [
            'nombre' => $cat->name,
            'total' => (int) $cat->count,
            'slug' => $cat->slug,
        ];
    }
}

usort($categorias_stats, static function ($a, $b) {
    return $b['total'] <=> $a['total'];
});

$categorias_activas = count(array_filter($categorias_stats, static function ($cat) {
    return $cat['total'] > 0;
}));

$tipos = get_terms([
    'taxonomy' => 'marketplace_tipo',
    'hide_empty' => false,
]);
$tipos_stats = [];

if (!is_wp_error($tipos)) {
    foreach ($tipos as $tipo) {
        $tipos_stats[] = [
            'nombre' => $tipo->name,
            'total' => (int) $tipo->count,
        ];
    }
}

usort($tipos_stats, static function ($a, $b) {
    return $b['total'] <=> $a['total'];
});

$anuncios_recientes = get_posts([
    'post_type' => 'marketplace_item',
    'post_status' => ['publish', 'pending', 'draft'],
    'posts_per_page' => 8,
    'orderby' => 'date',
    'order' => 'DESC',
]);

$anuncios_pendientes_lista = get_posts([
    'post_type' => 'marketplace_item',
    'post_status' => 'pending',
    'posts_per_page' => 6,
    'orderby' => 'date',
    'order' => 'ASC',
]);

$anuncios_borrador_lista = get_posts([
    'post_type' => 'marketplace_item',
    'post_status' => 'draft',
    'posts_per_page' => 6,
    'orderby' => 'modified',
    'order' => 'ASC',
]);

$usuarios_activos = $wpdb->get_results(
    "SELECT post_author, COUNT(*) as total_anuncios
     FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item' AND post_status = 'publish'
     GROUP BY post_author
     ORDER BY total_anuncios DESC
     LIMIT 6"
);

$anuncios_mensuales = $wpdb->get_results(
    "SELECT DATE_FORMAT(post_date, '%Y-%m') as mes, COUNT(*) as total
     FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
       AND post_status = 'publish'
       AND post_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
     GROUP BY mes
     ORDER BY mes ASC"
);

$publicados_hoy = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
       AND post_status = 'publish'
       AND post_date >= %s",
    current_time('Y-m-d 00:00:00')
));

$pendientes_antiguos = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
       AND post_status = 'pending'
       AND post_date < %s",
    gmdate('Y-m-d H:i:s', strtotime('-3 days', current_time('timestamp', true)))
));

$borradores_antiguos = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts}
     WHERE post_type = 'marketplace_item'
       AND post_status = 'draft'
       AND post_modified < %s",
    gmdate('Y-m-d H:i:s', strtotime('-14 days', current_time('timestamp', true)))
));

$tabla_reportes = $wpdb->prefix . 'flavor_marketplace_reportes';
$tabla_reportes_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_reportes)) === $tabla_reportes;
$total_reportes_pendientes = 0;
$reportes_recientes = [];

if ($tabla_reportes_existe) {
    $total_reportes_pendientes = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$tabla_reportes} WHERE estado = 'pendiente'"
    );

    $reportes_recientes = $wpdb->get_results(
        "SELECT r.*, p.post_title as anuncio_titulo, u.display_name as reportador_nombre
         FROM {$tabla_reportes} r
         LEFT JOIN {$wpdb->posts} p ON p.ID = r.anuncio_id
         LEFT JOIN {$wpdb->users} u ON u.ID = r.usuario_reportador_id
         ORDER BY r.fecha_reporte DESC
         LIMIT 5"
    );
}

$max_mes = 0;
foreach ($anuncios_mensuales as $mes) {
    $max_mes = max($max_mes, (int) $mes->total);
}

$estado_badges = [
    'publish' => 'dm-badge--success',
    'pending' => 'dm-badge--warning',
    'draft' => 'dm-badge--secondary',
    'private' => 'dm-badge--info',
];

$estado_labels = [
    'publish' => __('Publicado', 'flavor-chat-ia'),
    'pending' => __('Pendiente', 'flavor-chat-ia'),
    'draft' => __('Borrador', 'flavor-chat-ia'),
    'private' => __('Privado', 'flavor-chat-ia'),
];
?>

<div class="wrap dm-dashboard">
    <?php
    if (function_exists('flavor_dashboard_help')) {
        flavor_dashboard_help('marketplace');
    }
    ?>

    <!-- Cabecera -->
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-store"></span>
                <?php esc_html_e('Dashboard Marketplace', 'flavor-chat-ia'); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Panel operativo para seguir el estado del catálogo, la moderación y la actividad reciente del marketplace.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=marketplace_item')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo anuncio', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="dm-card">
        <h2 class="dm-card__title">
            <span class="dashicons dashicons-admin-links"></span> <?php esc_html_e('Accesos Rápidos', 'flavor-chat-ia'); ?>
        </h2>
        <div class="dm-action-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=marketplace-anuncios')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-megaphone dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Anuncios', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marketplace-moderacion')); ?>" class="dm-action-card dm-action-card--warning">
                <span class="dashicons dashicons-shield dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Moderación', 'flavor-chat-ia'); ?></span>
                <?php if ($anuncios_pendientes + $total_reportes_pendientes > 0): ?>
                    <span class="dm-badge dm-badge--error"><?php echo $anuncios_pendientes + $total_reportes_pendientes; ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=marketplace-categorias')); ?>" class="dm-action-card dm-action-card--success">
                <span class="dashicons dashicons-category dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Categorías', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=marketplace_tipo&post_type=marketplace_item')); ?>" class="dm-action-card dm-action-card--purple">
                <span class="dashicons dashicons-tag dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Tipos', 'flavor-chat-ia'); ?></span>
            </a>
            <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('marketplace', '')); ?>" class="dm-action-card">
                <span class="dashicons dashicons-external dm-action-card__icon"></span>
                <span class="dm-action-card__label"><?php esc_html_e('Portal público', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="dm-stats-grid">
        <div class="dm-stat-card">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-visibility"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($anuncios_publicados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Publicados', 'flavor-chat-ia'); ?></div>
                <small class="dm-text-muted"><?php esc_html_e('anuncios visibles', 'flavor-chat-ia'); ?></small>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($anuncios_pendientes); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('En revisión', 'flavor-chat-ia'); ?></div>
                <small class="dm-text-muted"><?php esc_html_e('esperando moderación', 'flavor-chat-ia'); ?></small>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($publicados_hoy); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Publicados hoy', 'flavor-chat-ia'); ?></div>
                <small class="dm-text-muted"><?php esc_html_e('nuevas altas del día', 'flavor-chat-ia'); ?></small>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-category"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($categorias_activas); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Categorías activas', 'flavor-chat-ia'); ?></div>
                <small class="dm-text-muted"><?php esc_html_e('con contenido publicado', 'flavor-chat-ia'); ?></small>
            </div>
        </div>
    </div>

    <!-- Alertas operativas -->
    <?php if ($anuncios_pendientes > 0 || $total_reportes_pendientes > 0 || $pendientes_antiguos > 0): ?>
    <div class="dm-alert dm-alert--warning">
        <span class="dashicons dashicons-warning"></span>
        <div>
            <strong><?php esc_html_e('Cola operativa', 'flavor-chat-ia'); ?></strong>
            <ul style="margin: 8px 0 0; padding-left: 20px;">
                <?php if ($anuncios_pendientes > 0): ?>
                    <li><?php printf(esc_html__('%s pendientes de moderación', 'flavor-chat-ia'), number_format_i18n($anuncios_pendientes)); ?></li>
                <?php endif; ?>
                <?php if ($total_reportes_pendientes > 0): ?>
                    <li><?php printf(esc_html__('%s reportes de abuso', 'flavor-chat-ia'), number_format_i18n($total_reportes_pendientes)); ?></li>
                <?php endif; ?>
                <?php if ($pendientes_antiguos > 0): ?>
                    <li><?php printf(esc_html__('%s pendientes con más de 3 días', 'flavor-chat-ia'), number_format_i18n($pendientes_antiguos)); ?></li>
                <?php endif; ?>
                <?php if ($borradores_antiguos > 0): ?>
                    <li><?php printf(esc_html__('%s borradores antiguos', 'flavor-chat-ia'), number_format_i18n($borradores_antiguos)); ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Grid de paneles -->
    <div class="dm-grid dm-grid--3">
        <!-- Por estado -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-tag"></span> <?php esc_html_e('Por estado', 'flavor-chat-ia'); ?>
            </h3>
            <div class="dm-badge-list">
                <?php foreach ([
                    'publish' => $anuncios_publicados,
                    'pending' => $anuncios_pendientes,
                    'draft' => $anuncios_borrador,
                    'private' => $anuncios_privados,
                ] as $estado => $total_estado): ?>
                    <div class="dm-badge-item">
                        <span class="dm-badge <?php echo esc_attr($estado_badges[$estado]); ?>">
                            <?php echo esc_html($estado_labels[$estado]); ?>
                        </span>
                        <strong><?php echo number_format_i18n($total_estado); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tipos de transacción -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-randomize"></span> <?php esc_html_e('Tipos', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($tipos_stats)): ?>
                <ol class="dm-ranking">
                    <?php foreach (array_slice($tipos_stats, 0, 5) as $tipo): ?>
                        <li>
                            <span><?php echo esc_html($tipo['nombre']); ?></span>
                            <strong><?php echo number_format_i18n($tipo['total']); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Sin tipos registrados.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Tendencia -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-chart-bar"></span> <?php esc_html_e('Ritmo mensual', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($anuncios_mensuales)): ?>
                <div class="dm-trend">
                    <?php foreach ($anuncios_mensuales as $mes):
                        $fecha = DateTime::createFromFormat('Y-m', $mes->mes);
                        $altura = $max_mes > 0 ? max(20, (int) round(((int) $mes->total / $max_mes) * 100)) : 20;
                    ?>
                        <div class="dm-trend__item">
                            <div class="dm-trend__bar" style="height: <?php echo esc_attr($altura); ?>px;"></div>
                            <strong><?php echo number_format_i18n((int) $mes->total); ?></strong>
                            <small><?php echo esc_html($fecha ? $fecha->format('M') : $mes->mes); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Sin datos suficientes.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tablas -->
    <div class="dm-grid dm-grid--2">
        <!-- Pendientes prioritarios -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-clock"></span> <?php esc_html_e('Pendientes prioritarios', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($anuncios_pendientes_lista)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Anuncio', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Espera', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Acción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($anuncios_pendientes_lista, 0, 5) as $anuncio):
                            $dias = max(0, floor((current_time('timestamp') - get_post_time('U', false, $anuncio)) / DAY_IN_SECONDS));
                            $clase_dias = $dias >= 3 ? 'dm-badge--error' : ($dias >= 1 ? 'dm-badge--warning' : 'dm-badge--success');
                        ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(wp_trim_words(get_the_title($anuncio), 5)); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo esc_html(get_the_author_meta('display_name', (int) $anuncio->post_author)); ?></div>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo $clase_dias; ?>">
                                        <?php printf(esc_html__('%d días', 'flavor-chat-ia'), $dias); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($anuncio->ID)); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                                        <?php esc_html_e('Revisar', 'flavor-chat-ia'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-yes-alt dm-empty__icon"></span>
                    <p><?php esc_html_e('No hay anuncios pendientes de revisión.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=marketplace-moderacion')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Abrir moderación', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>

        <!-- Actividad reciente -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-update"></span> <?php esc_html_e('Actividad reciente', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($anuncios_recientes)): ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Anuncio', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($anuncios_recientes, 0, 5) as $anuncio): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html(wp_trim_words(get_the_title($anuncio), 5)); ?></strong>
                                    <div class="dm-table__subtitle"><?php echo esc_html(get_the_author_meta('display_name', (int) $anuncio->post_author)); ?></div>
                                </td>
                                <td>
                                    <span class="dm-badge <?php echo esc_attr($estado_badges[$anuncio->post_status] ?? 'dm-badge--secondary'); ?>">
                                        <?php echo esc_html($estado_labels[$anuncio->post_status] ?? $anuncio->post_status); ?>
                                    </span>
                                </td>
                                <td class="dm-table__muted">
                                    <?php echo esc_html(get_the_date('d M', $anuncio)); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-store dm-empty__icon"></span>
                    <p><?php esc_html_e('Sin actividad reciente.', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
            <div class="dm-card__footer">
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=marketplace_item')); ?>" class="dm-btn dm-btn--secondary dm-btn--sm">
                    <?php esc_html_e('Ver listado completo', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Rankings -->
    <div class="dm-grid dm-grid--2">
        <!-- Top vendedores -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-businessman"></span> <?php esc_html_e('Top vendedores', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($usuarios_activos)): ?>
                <ol class="dm-ranking">
                    <?php foreach ($usuarios_activos as $usuario):
                        $user_data = get_userdata((int) $usuario->post_author);
                        if (!$user_data) continue;
                    ?>
                        <li>
                            <span><?php echo esc_html($user_data->display_name); ?></span>
                            <strong><?php echo number_format_i18n((int) $usuario->total_anuncios); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Sin vendedores activos.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Top categorías -->
        <div class="dm-card">
            <h3 class="dm-card__title">
                <span class="dashicons dashicons-category"></span> <?php esc_html_e('Top categorías', 'flavor-chat-ia'); ?>
            </h3>
            <?php if (!empty($categorias_stats)): ?>
                <ol class="dm-ranking">
                    <?php foreach (array_slice($categorias_stats, 0, 6) as $categoria): ?>
                        <li>
                            <span>
                                <a href="<?php echo esc_url(admin_url('edit.php?post_type=marketplace_item&marketplace_categoria=' . rawurlencode($categoria['slug']))); ?>">
                                    <?php echo esc_html($categoria['nombre']); ?>
                                </a>
                            </span>
                            <strong><?php echo number_format_i18n($categoria['total']); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php else: ?>
                <p class="dm-text-muted"><?php esc_html_e('Sin categorías.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Resumen -->
    <div class="dm-card">
        <h3 class="dm-card__title">
            <span class="dashicons dashicons-lightbulb"></span> <?php esc_html_e('Foco recomendado', 'flavor-chat-ia'); ?>
        </h3>
        <div class="dm-focus-list">
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($total_anuncios); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('anuncios en total', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($anuncios_pendientes + $total_reportes_pendientes); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('tareas de moderación', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n($borradores_antiguos); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('borradores para limpiar', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="dm-focus-item">
                <span class="dm-focus-item__value"><?php echo number_format_i18n(count($categorias_stats)); ?></span>
                <span class="dm-focus-item__label"><?php esc_html_e('categorías configuradas', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
    </div>
</div>
