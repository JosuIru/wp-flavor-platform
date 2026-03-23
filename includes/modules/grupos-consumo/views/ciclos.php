<?php
/**
 * Vista Ciclos - Grupos de Consumo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$paged = max(1, absint($_GET['paged'] ?? 1));
$per_page = 20;
$filtro_estado = sanitize_text_field(wp_unslash($_GET['estado'] ?? 'todos'));
$current_page = sanitize_key($_GET['page'] ?? 'gc-ciclos');

$allowed_estado = ['todos', 'abierto', 'activo', 'cerrado', 'borrador'];
if (!in_array($filtro_estado, $allowed_estado, true)) {
    $filtro_estado = 'todos';
}

$query_args = [
    'post_type' => 'gc_ciclo',
    'post_status' => ['publish', 'draft', 'pending', 'private'],
    'posts_per_page' => $per_page,
    'paged' => $paged,
    'orderby' => 'date',
    'order' => 'DESC',
];

if ($filtro_estado !== 'todos') {
    if ($filtro_estado === 'borrador') {
        $query_args['post_status'] = 'draft';
    } else {
        $query_args['post_status'] = 'publish';
        $query_args['meta_query'] = [
            [
                'key' => '_gc_estado',
                'value' => $filtro_estado,
                'compare' => '=',
            ],
        ];
    }
}

$ciclos_query = new WP_Query($query_args);
$tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
$tabla_pedidos_existe = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $tabla_pedidos)) === $tabla_pedidos;

$counts = wp_count_posts('gc_ciclo');
$total_publicados = isset($counts->publish) ? (int) $counts->publish : 0;
$total_borrador = isset($counts->draft) ? (int) $counts->draft : 0;

$total_abiertos = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT p.ID)
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_gc_estado'
     WHERE p.post_type = 'gc_ciclo' AND p.post_status = 'publish' AND pm.meta_value IN ('abierto', 'activo')"
);

$total_cerrados = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT p.ID)
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = '_gc_estado'
     WHERE p.post_type = 'gc_ciclo' AND p.post_status = 'publish' AND pm.meta_value = 'cerrado'"
);

$base_url = admin_url('admin.php?page=' . $current_page);
?>

<div class="wrap dm-dashboard">
    <div class="dm-header">
        <div class="dm-header__content">
            <h1 class="dm-header__title">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Ciclos de Pedido', 'flavor-chat-ia'); ?>
            </h1>
            <p class="dm-header__description">
                <?php esc_html_e('Seguimiento del estado de apertura, cierre y logística de cada ciclo.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <div class="dm-header__actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=gc_ciclo')); ?>" class="dm-btn dm-btn--primary">
                <span class="dashicons dashicons-plus-alt2"></span> <?php esc_html_e('Nuevo ciclo', 'flavor-chat-ia'); ?>
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gc_ciclo')); ?>" class="dm-btn dm-btn--secondary">
                <?php esc_html_e('Editor nativo', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <div class="dm-stats-grid" style="margin-bottom:16px;">
        <div class="dm-stat-card">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_publicados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Publicados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--success">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_abiertos); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Abiertos / activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--warning">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_cerrados); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Cerrados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
        <div class="dm-stat-card dm-stat-card--info">
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo number_format_i18n($total_borrador); ?></div>
                <div class="dm-stat-card__label"><?php esc_html_e('Borradores', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>

    <form method="get" class="dm-card" style="padding:16px; margin-bottom:16px; display:flex; gap:10px; align-items:end;">
        <input type="hidden" name="page" value="<?php echo esc_attr($current_page); ?>" />
        <div>
            <label for="gc-ciclos-estado"><strong><?php esc_html_e('Estado del ciclo', 'flavor-chat-ia'); ?></strong></label>
            <select id="gc-ciclos-estado" name="estado">
                <option value="todos" <?php selected($filtro_estado, 'todos'); ?>><?php esc_html_e('Todos', 'flavor-chat-ia'); ?></option>
                <option value="abierto" <?php selected($filtro_estado, 'abierto'); ?>><?php esc_html_e('Abierto', 'flavor-chat-ia'); ?></option>
                <option value="activo" <?php selected($filtro_estado, 'activo'); ?>><?php esc_html_e('Activo', 'flavor-chat-ia'); ?></option>
                <option value="cerrado" <?php selected($filtro_estado, 'cerrado'); ?>><?php esc_html_e('Cerrado', 'flavor-chat-ia'); ?></option>
                <option value="borrador" <?php selected($filtro_estado, 'borrador'); ?>><?php esc_html_e('Borrador', 'flavor-chat-ia'); ?></option>
            </select>
        </div>
        <div>
            <button type="submit" class="button button-primary"><?php esc_html_e('Filtrar', 'flavor-chat-ia'); ?></button>
        </div>
    </form>

    <div class="dm-card" style="padding:0; overflow:auto;">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Ciclo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Apertura', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Cierre', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Entrega', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Pedidos', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($ciclos_query->have_posts()) : ?>
                    <?php while ($ciclos_query->have_posts()) : $ciclos_query->the_post(); ?>
                        <?php
                        $ciclo_id = get_the_ID();
                        $estado = get_post_meta($ciclo_id, '_gc_estado', true) ?: ('publish' === get_post_status($ciclo_id) ? 'activo' : 'borrador');
                        $fecha_inicio = get_post_meta($ciclo_id, '_gc_fecha_inicio', true);
                        $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
                        $fecha_entrega = get_post_meta($ciclo_id, '_gc_fecha_entrega', true);

                        $total_pedidos = 0;
                        if ($tabla_pedidos_existe) {
                            $total_pedidos = (int) $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE ciclo_id = %d",
                                $ciclo_id
                            ));
                        }

                        $estado_class = 'dm-badge--info';
                        if (in_array($estado, ['abierto', 'activo'], true)) {
                            $estado_class = 'dm-badge--success';
                        } elseif ('cerrado' === $estado) {
                            $estado_class = 'dm-badge--warning';
                        }
                        ?>
                        <tr>
                            <td><strong><a href="<?php echo esc_url(get_edit_post_link($ciclo_id)); ?>"><?php echo esc_html(get_the_title()); ?></a></strong></td>
                            <td><span class="dm-badge <?php echo esc_attr($estado_class); ?>"><?php echo esc_html(ucfirst($estado)); ?></span></td>
                            <td><?php echo $fecha_inicio ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($fecha_inicio))) : '—'; ?></td>
                            <td><?php echo $fecha_cierre ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($fecha_cierre))) : '—'; ?></td>
                            <td><?php echo $fecha_entrega ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($fecha_entrega))) : '—'; ?></td>
                            <td><?php echo number_format_i18n($total_pedidos); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url(get_edit_post_link($ciclo_id)); ?>"><?php esc_html_e('Editar', 'flavor-chat-ia'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=gc-pedidos&ciclo=' . $ciclo_id)); ?>"><?php esc_html_e('Pedidos', 'flavor-chat-ia'); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7"><?php esc_html_e('No hay ciclos para los filtros seleccionados.', 'flavor-chat-ia'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php
    echo '<div class="tablenav" style="margin-top:12px;">';
    echo '<div class="tablenav-pages">';
    echo wp_kses_post(paginate_links([
        'base' => add_query_arg('paged', '%#%', remove_query_arg('paged', $base_url)),
        'format' => '',
        'current' => $paged,
        'total' => max(1, (int) $ciclos_query->max_num_pages),
        'add_args' => [
            'page' => $current_page,
            'estado' => $filtro_estado,
        ],
        'type' => 'plain',
    ]));
    echo '</div></div>';
    ?>
</div>
