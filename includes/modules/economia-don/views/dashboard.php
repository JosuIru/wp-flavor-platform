<?php
/**
 * Vista Dashboard - Economía del Don
 *
 * Panel principal con estadísticas de dones, solicitudes y gratitudes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options') && !current_user_can('flavor_ver_dashboard')) {
    wp_die(__('No tienes permisos suficientes para acceder a esta página.', 'flavor-chat-ia'));
}

global $wpdb;

// Verificar si el CPT existe
$cpt_exists = post_type_exists('ed_don');
$tablas_disponibles = $cpt_exists;

// Valores por defecto
$donesDisponibles = 0;
$donesEntregados = 0;
$totalDones = 0;
$solicitudesPendientes = 0;
$totalSolicitudes = 0;
$totalGratitudes = 0;
$donesPorCategoria = [];
$donesRecientes = [];
$gratitudesRecientes = [];
$donantesMasActivos = [];

if ($cpt_exists) {
    // Obtener estadísticas de dones
    $donesDisponibles = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'ed_don' AND p.post_status = 'publish'
           AND pm.meta_key = '_ed_estado' AND pm.meta_value = 'disponible'"
    );

    $donesEntregados = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'ed_don' AND p.post_status = 'publish'
           AND pm.meta_key = '_ed_estado' AND pm.meta_value = 'entregado'"
    );

    $totalDones = (int) wp_count_posts('ed_don')->publish;

    // Solicitudes
    $solicitudesPendientes = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'ed_solicitud' AND p.post_status = 'publish'
           AND pm.meta_key = '_ed_estado' AND pm.meta_value = 'pendiente'"
    );

    $totalSolicitudes = (int) wp_count_posts('ed_solicitud')->publish;

    // Gratitudes
    $totalGratitudes = (int) wp_count_posts('ed_gratitud')->publish;

    // Dones por categoría
    if (class_exists('Flavor_Chat_Economia_Don_Module') && defined('Flavor_Chat_Economia_Don_Module::CATEGORIAS_DON')) {
        $categoriasDon = Flavor_Chat_Economia_Don_Module::CATEGORIAS_DON;
        foreach ($categoriasDon as $categoriaKey => $categoriaInfo) {
            $cantidad = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                 WHERE p.post_type = 'ed_don' AND p.post_status = 'publish'
                   AND pm.meta_key = '_ed_categoria' AND pm.meta_value = %s",
                $categoriaKey
            ));
            $donesPorCategoria[$categoriaKey] = [
                'cantidad' => $cantidad,
                'info' => $categoriaInfo,
            ];
        }
    }

    // Dones recientes
    $donesRecientes = get_posts([
        'post_type' => 'ed_don',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    // Gratitudes recientes
    $gratitudesRecientes = get_posts([
        'post_type' => 'ed_gratitud',
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC',
    ]);

    // Usuarios más activos donando
    $donantesMasActivos = $wpdb->get_results(
        "SELECT post_author, COUNT(*) as total_dones
         FROM {$wpdb->posts}
         WHERE post_type = 'ed_don' AND post_status = 'publish'
         GROUP BY post_author
         ORDER BY total_dones DESC
         LIMIT 5"
    );
}

// Definir estados para badges
$estados_don = [
    'disponible' => ['nombre' => __('Disponible', 'flavor-chat-ia'), 'color' => 'success'],
    'reservado' => ['nombre' => __('Reservado', 'flavor-chat-ia'), 'color' => 'warning'],
    'entregado' => ['nombre' => __('Entregado', 'flavor-chat-ia'), 'color' => 'info'],
];
?>

<div class="dm-dashboard">
    <!-- Header -->
    <div class="dm-header">
        <div class="dm-header__title">
            <span class="dashicons dashicons-heart" style="font-size: 28px; color: #ec4899;"></span>
            <div>
                <h1><?php esc_html_e('Dashboard de Economía del Don', 'flavor-chat-ia'); ?></h1>
                <p><?php esc_html_e('Dar y recibir sin esperar nada a cambio', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=ed_don')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Nuevo Don', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <?php if (!$tablas_disponibles) : ?>
    <div class="dm-alert dm-alert--info">
        <span class="dashicons dashicons-info"></span>
        <?php esc_html_e('El módulo Economía del Don no está disponible porque falta el tipo de contenido requerido.', 'flavor-chat-ia'); ?>
    </div>
    <?php endif; ?>

    <!-- Quick Links -->
    <div class="dm-quick-links">
        <h3 class="dm-quick-links__title"><?php esc_html_e('Acceso Rápido', 'flavor-chat-ia'); ?></h3>
        <div class="dm-quick-links__grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=economia-don-listado')); ?>" class="dm-quick-links__item dm-quick-links__item--pink">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Ver Dones', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=economia-don-solicitudes')); ?>" class="dm-quick-links__item dm-quick-links__item--warning">
                <span class="dashicons dashicons-email"></span>
                <?php esc_html_e('Solicitudes', 'flavor-chat-ia'); ?>
                <?php if ($solicitudesPendientes > 0) : ?>
                    <span class="dm-badge dm-badge--error"><?php echo esc_html($solicitudesPendientes); ?></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=economia-don-gratitudes')); ?>" class="dm-quick-links__item dm-quick-links__item--purple">
                <span class="dashicons dashicons-smiley"></span>
                <?php esc_html_e('Muro de Gratitud', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=economia-don-estadisticas')); ?>" class="dm-quick-links__item dm-quick-links__item--info">
                <span class="dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Estadísticas', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="dm-stats-grid dm-stats-grid--4">
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($donesDisponibles)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Dones Disponibles', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('para compartir', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($donesEntregados)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Dones Entregados', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('compartidos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($solicitudesPendientes)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Solicitudes Pendientes', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('por revisar', 'flavor-chat-ia'); ?></div>
            </div>
        </div>

        <div class="dm-stat-card dm-stat-card--purple">
            <div class="dm-stat-card__icon">
                <span class="dashicons dashicons-smiley"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html(number_format_i18n($totalGratitudes)); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Gratitudes', 'flavor-chat-ia'); ?></div>
                <div class="dm-stat-card__meta"><?php esc_html_e('compartidas', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="dm-grid dm-grid--2-1">
        <!-- Columna principal -->
        <div>
            <!-- Dones por categoría -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-category"></span>
                        <?php esc_html_e('Dones por Categoría', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
                <?php if (!empty($donesPorCategoria)) : ?>
                <div class="dm-category-grid">
                    <?php foreach ($donesPorCategoria as $categoriaKey => $datos) : ?>
                    <div class="dm-category-card" style="--category-color: <?php echo esc_attr($datos['info']['color']); ?>">
                        <span class="dashicons <?php echo esc_attr($datos['info']['icono']); ?>"></span>
                        <div class="dm-category-card__value"><?php echo esc_html(number_format_i18n($datos['cantidad'])); ?></div>
                        <div class="dm-category-card__label"><?php echo esc_html($datos['info']['nombre']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-category"></span>
                    <p><?php esc_html_e('No hay categorías configuradas.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Dones recientes -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Dones Recientes', 'flavor-chat-ia'); ?>
                    </h3>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=economia-don-listado')); ?>" class="dm-btn dm-btn--ghost dm-btn--sm">
                        <?php esc_html_e('Ver todos', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php if (!empty($donesRecientes)) : ?>
                <table class="dm-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Don', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Donante', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donesRecientes as $don) :
                            $estado = get_post_meta($don->ID, '_ed_estado', true) ?: 'disponible';
                            $donante = get_userdata($don->post_author);
                            $donante_nombre = $donante ? $donante->display_name : __('Anónimo', 'flavor-chat-ia');
                            $estado_info = $estados_don[$estado] ?? ['nombre' => $estado, 'color' => 'secondary'];
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($don->ID)); ?>" class="dm-link">
                                    <?php echo esc_html($don->post_title); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($donante_nombre); ?></td>
                            <td>
                                <span class="dm-badge dm-badge--<?php echo esc_attr($estado_info['color']); ?>">
                                    <?php echo esc_html($estado_info['nombre']); ?>
                                </span>
                            </td>
                            <td class="dm-text-muted">
                                <?php echo esc_html(human_time_diff(strtotime($don->post_date), current_time('timestamp'))); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-heart"></span>
                    <p><?php esc_html_e('No hay dones publicados todavía.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div>
            <!-- Gratitudes recientes -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-format-quote"></span>
                        <?php esc_html_e('Últimas Gratitudes', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
                <?php if (!empty($gratitudesRecientes)) : ?>
                <div class="dm-gratitude-list">
                    <?php foreach ($gratitudesRecientes as $gratitud) :
                        $autor_nombre = get_the_author_meta('display_name', $gratitud->post_author);
                        $contenido = $gratitud->post_content;
                    ?>
                    <div class="dm-gratitude-item">
                        <p class="dm-gratitude-item__text">"<?php echo esc_html(wp_trim_words($contenido, 15)); ?>"</p>
                        <span class="dm-gratitude-item__author">— <?php echo esc_html($autor_nombre); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-smiley"></span>
                    <p><?php esc_html_e('No hay gratitudes compartidas.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Donantes destacados -->
            <div class="dm-card">
                <div class="dm-card__header">
                    <h3>
                        <span class="dashicons dashicons-star-filled"></span>
                        <?php esc_html_e('Donantes Destacados', 'flavor-chat-ia'); ?>
                    </h3>
                </div>
                <?php if (!empty($donantesMasActivos)) : ?>
                <ol class="dm-ranking">
                    <?php foreach ($donantesMasActivos as $donante) :
                        $usuario = get_userdata($donante->post_author);
                        $nombre = $usuario ? $usuario->display_name : __('Usuario', 'flavor-chat-ia');
                        $total = $donante->total_dones;
                    ?>
                    <li>
                        <span><?php echo esc_html($nombre); ?></span>
                        <strong><?php echo esc_html(sprintf(_n('%d don', '%d dones', $total, 'flavor-chat-ia'), $total)); ?></strong>
                    </li>
                    <?php endforeach; ?>
                </ol>
                <?php else : ?>
                <div class="dm-empty">
                    <span class="dashicons dashicons-groups"></span>
                    <p><?php esc_html_e('No hay donantes registrados.', 'flavor-chat-ia'); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
