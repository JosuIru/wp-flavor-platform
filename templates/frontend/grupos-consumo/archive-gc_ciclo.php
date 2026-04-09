<?php
/**
 * Template: Archive Ciclos de Compra
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<div class="gc-archive-ciclos">
    <header class="gc-archive-header">
        <h1><?php _e('Ciclos de Compra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <p class="gc-archive-intro">
            <?php _e('Consulta los ciclos de compra activos y realiza tus pedidos antes de la fecha de cierre.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </header>

    <div class="gc-filtros">
        <div class="gc-filtro-grupo">
            <label for="filtro-estado"><?php _e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="filtro-estado" class="gc-filtro-select">
                <option value=""><?php _e('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="abierto"><?php _e('Abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="cerrado"><?php _e('Cerrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
        </div>
    </div>

    <?php if (have_posts()): ?>
    <div class="gc-ciclos-lista">
        <?php while (have_posts()): the_post();
            $ciclo_id = get_the_ID();
            $grupo_id = get_post_meta($ciclo_id, '_gc_grupo_id', true);
            $estado = get_post_meta($ciclo_id, '_gc_estado', true) ?: 'abierto';
            $fecha_cierre = get_post_meta($ciclo_id, '_gc_fecha_cierre', true);
            $fecha_recogida = get_post_meta($ciclo_id, '_gc_fecha_recogida', true);
            $pedidos_count = get_post_meta($ciclo_id, '_gc_pedidos_count', true) ?: 0;

            $grupo = $grupo_id ? get_post($grupo_id) : null;
        ?>
        <article class="gc-ciclo-card" data-estado="<?php echo esc_attr($estado); ?>">
            <div class="gc-ciclo-card-header">
                <div class="gc-ciclo-card-info">
                    <?php if ($grupo): ?>
                    <p class="gc-ciclo-card-grupo">
                        <a href="<?php echo get_permalink($grupo->ID); ?>"><?php echo esc_html($grupo->post_title); ?></a>
                    </p>
                    <?php endif; ?>
                    <h2 class="gc-ciclo-card-titulo">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                </div>
                <span class="gc-badge gc-estado-<?php echo esc_attr($estado); ?>">
                    <?php
                    $estados_label = [
                        'abierto' => __('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'cerrado' => __('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'procesando' => __('Procesando', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'entregado' => __('Entregado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                    echo esc_html($estados_label[$estado] ?? ucfirst($estado));
                    ?>
                </span>
            </div>

            <div class="gc-ciclo-card-body">
                <div class="gc-ciclo-card-fechas">
                    <?php if ($fecha_cierre): ?>
                    <div class="gc-fecha-item">
                        <span class="gc-fecha-label"><?php _e('Cierre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="gc-fecha-valor"><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($fecha_cierre))); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($fecha_recogida): ?>
                    <div class="gc-fecha-item">
                        <span class="gc-fecha-label"><?php _e('Recogida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="gc-fecha-valor"><?php echo esc_html(date_i18n('d/m/Y', strtotime($fecha_recogida))); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="gc-ciclo-card-stats">
                    <span class="gc-pedidos">
                        <span class="dashicons dashicons-cart"></span>
                        <?php printf(_n('%d pedido', '%d pedidos', $pedidos_count, FLAVOR_PLATFORM_TEXT_DOMAIN), $pedidos_count); ?>
                    </span>
                </div>
            </div>

            <div class="gc-ciclo-card-footer">
                <a href="<?php the_permalink(); ?>" class="gc-btn gc-btn-primary">
                    <?php echo $estado === 'abierto' ? __('Ver y pedir', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Ver ciclo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </article>
        <?php endwhile; ?>
    </div>

    <nav class="gc-paginacion">
        <?php
        the_posts_pagination([
            'prev_text' => '<span class="dashicons dashicons-arrow-left-alt2"></span> ' . __('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'next_text' => __('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' <span class="dashicons dashicons-arrow-right-alt2"></span>',
        ]);
        ?>
    </nav>

    <?php else: ?>
    <div class="gc-empty-state">
        <span class="dashicons dashicons-calendar-alt"></span>
        <p><?php _e('No hay ciclos de compra disponibles actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filtroEstado = document.getElementById('filtro-estado');
    if (filtroEstado) {
        filtroEstado.addEventListener('change', function() {
            const estado = this.value;
            document.querySelectorAll('.gc-ciclo-card').forEach(card => {
                if (!estado || card.dataset.estado === estado) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php
get_footer();
