<?php
/**
 * Template: Necesidades de Cuidado
 *
 * Muestra las necesidades de cuidado abiertas.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$args = [
    'post_type' => 'cc_necesidad',
    'posts_per_page' => $atts['limite'] ?? 10,
    'post_status' => 'publish',
    'meta_query' => [
        [
            'key' => '_cc_estado',
            'value' => $atts['estado'] ?? 'abierta',
        ],
    ],
    'orderby' => 'meta_value',
    'meta_key' => '_cc_urgencia',
    'order' => 'DESC',
];

// Filtrar por urgencia si se especifica
if (!empty($atts['urgencia'])) {
    $args['meta_query'][] = [
        'key' => '_cc_urgencia',
        'value' => $atts['urgencia'],
    ];
}

$necesidades = new WP_Query($args);
$user_id = get_current_user_id();

// Obtener círculos del usuario para verificar si puede ayudar
$mis_circulos_ids = [];
if ($user_id) {
    global $wpdb;
    $mis_circulos = $wpdb->get_col($wpdb->prepare(
        "SELECT p.ID
         FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
         WHERE p.post_type = 'cc_circulo'
           AND pm.meta_key = '_cc_miembros'
           AND pm.meta_value LIKE %s",
        '%"' . $user_id . '"%'
    ));
    $mis_circulos_ids = array_map('intval', $mis_circulos);
}

$urgencias_orden = ['urgente' => 1, 'alta' => 2, 'normal' => 3, 'baja' => 4];
?>

<div class="cc-necesidades">
    <header class="cc-listado__header">
        <h2><?php esc_html_e('Necesidades de Cuidado', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Personas de tu comunidad que necesitan ayuda', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Filtros -->
    <div class="cc-filtros" style="margin-bottom: 2rem;">
        <form class="cc-filtros__form" method="get">
            <select name="urgencia" onchange="this.form.submit()">
                <option value=""><?php esc_html_e('Todas las urgencias', 'flavor-chat-ia'); ?></option>
                <option value="urgente" <?php selected(($_GET['urgencia'] ?? ''), 'urgente'); ?>>
                    <?php esc_html_e('Urgente', 'flavor-chat-ia'); ?>
                </option>
                <option value="alta" <?php selected(($_GET['urgencia'] ?? ''), 'alta'); ?>>
                    <?php esc_html_e('Alta', 'flavor-chat-ia'); ?>
                </option>
                <option value="normal" <?php selected(($_GET['urgencia'] ?? ''), 'normal'); ?>>
                    <?php esc_html_e('Normal', 'flavor-chat-ia'); ?>
                </option>
                <option value="baja" <?php selected(($_GET['urgencia'] ?? ''), 'baja'); ?>>
                    <?php esc_html_e('Baja', 'flavor-chat-ia'); ?>
                </option>
            </select>
        </form>
    </div>

    <?php if ($necesidades->have_posts()) : ?>
    <div class="cc-necesidades-lista">
        <?php while ($necesidades->have_posts()) : $necesidades->the_post();
            $urgencia = get_post_meta(get_the_ID(), '_cc_urgencia', true) ?: 'normal';
            $circulo_id = get_post_meta(get_the_ID(), '_cc_circulo_id', true);
            $fecha_inicio = get_post_meta(get_the_ID(), '_cc_fecha_inicio', true);
            $horas_necesarias = get_post_meta(get_the_ID(), '_cc_horas_necesarias', true);
            $ayudantes = get_post_meta(get_the_ID(), '_cc_ayudantes', true) ?: [];
            $anonimo = get_post_meta(get_the_ID(), '_cc_anonimo', true);
            $estado = get_post_meta(get_the_ID(), '_cc_estado', true) ?: 'abierta';

            // Calcular horas ya ofrecidas
            $horas_ofrecidas = array_sum(array_column($ayudantes, 'horas'));

            // Verificar si el usuario actual ya ofreció ayuda
            $ya_ofrecio = false;
            foreach ($ayudantes as $ayudante) {
                if ($ayudante['user_id'] == $user_id) {
                    $ya_ofrecio = true;
                    break;
                }
            }

            // Verificar si el usuario puede ayudar (es miembro del círculo)
            $puede_ayudar = $user_id && in_array($circulo_id, $mis_circulos_ids);

            // Nombre del solicitante
            $autor_nombre = $anonimo ? __('Anónimo', 'flavor-chat-ia') : get_the_author();
        ?>
        <article class="cc-necesidad-card cc-necesidad-card--<?php echo esc_attr($urgencia); ?>">
            <header class="cc-necesidad-card__header">
                <h3 class="cc-necesidad-card__titulo">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <span class="cc-urgencia-badge cc-urgencia-badge--<?php echo esc_attr($urgencia); ?>">
                    <?php echo esc_html(ucfirst($urgencia)); ?>
                </span>
            </header>

            <div class="cc-necesidad-card__meta">
                <span>
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php echo esc_html($autor_nombre); ?>
                </span>
                <?php if ($fecha_inicio) : ?>
                <span>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php echo date_i18n('d M, H:i', strtotime($fecha_inicio)); ?>
                </span>
                <?php endif; ?>
                <?php if ($horas_necesarias) : ?>
                <span>
                    <span class="dashicons dashicons-clock"></span>
                    <?php printf(
                        esc_html__('%s horas (%s ofrecidas)', 'flavor-chat-ia'),
                        $horas_necesarias,
                        $horas_ofrecidas
                    ); ?>
                </span>
                <?php endif; ?>
                <?php if ($circulo_id) :
                    $circulo = get_post($circulo_id);
                    if ($circulo) :
                ?>
                <span>
                    <span class="dashicons dashicons-heart"></span>
                    <?php echo esc_html($circulo->post_title); ?>
                </span>
                <?php endif; endif; ?>
            </div>

            <div class="cc-necesidad-card__descripcion">
                <?php the_excerpt(); ?>
            </div>

            <?php if ($horas_necesarias && $horas_ofrecidas > 0) : ?>
            <div class="cc-necesidad-card__progreso">
                <div class="cc-barra-progreso">
                    <div class="cc-barra-progreso__fill"
                         style="width: <?php echo min(100, ($horas_ofrecidas / $horas_necesarias) * 100); ?>%">
                    </div>
                </div>
                <span class="cc-progreso-texto">
                    <?php printf(
                        esc_html__('%d%% cubierto', 'flavor-chat-ia'),
                        min(100, round(($horas_ofrecidas / $horas_necesarias) * 100))
                    ); ?>
                </span>
            </div>
            <?php endif; ?>

            <footer class="cc-necesidad-card__footer">
                <span class="cc-necesidad-card__ayudantes">
                    <?php if (count($ayudantes) > 0) : ?>
                    <span class="dashicons dashicons-groups"></span>
                    <?php printf(
                        esc_html(_n('%d persona ayudando', '%d personas ayudando', count($ayudantes), 'flavor-chat-ia')),
                        count($ayudantes)
                    ); ?>
                    <?php endif; ?>
                </span>

                <?php if (!is_user_logged_in()) : ?>
                <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="cc-btn-ayudar">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Inicia sesión para ayudar', 'flavor-chat-ia'); ?>
                </a>
                <?php elseif ($ya_ofrecio) : ?>
                <span class="cc-badge-miembro">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Ya has ofrecido ayuda', 'flavor-chat-ia'); ?>
                </span>
                <?php elseif ($estado === 'cubierta') : ?>
                <span class="cc-badge-cubierta">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Necesidad cubierta', 'flavor-chat-ia'); ?>
                </span>
                <?php elseif (get_current_user_id() == get_the_author_meta('ID')) : ?>
                <span class="cc-badge-mia">
                    <?php esc_html_e('Tu solicitud', 'flavor-chat-ia'); ?>
                </span>
                <?php elseif ($puede_ayudar) : ?>
                <button class="cc-btn-ayudar" data-necesidad="<?php echo esc_attr(get_the_ID()); ?>">
                    <span class="dashicons dashicons-heart"></span>
                    <?php esc_html_e('Quiero ayudar', 'flavor-chat-ia'); ?>
                </button>
                <?php else : ?>
                <span class="cc-info-unirse">
                    <?php esc_html_e('Únete al círculo para ayudar', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </footer>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php else : ?>
    <div class="cc-empty-state">
        <span class="dashicons dashicons-smiley"></span>
        <p><?php esc_html_e('¡Genial! No hay necesidades de cuidado pendientes.', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>
</div>

<style>
.cc-barra-progreso {
    height: 8px;
    background: var(--cc-border, #ecf0f1);
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.5rem;
}

.cc-barra-progreso__fill {
    height: 100%;
    background: var(--cc-success, #27ae60);
    border-radius: 4px;
    transition: width 0.3s ease;
}

.cc-progreso-texto {
    font-size: 0.75rem;
    color: var(--cc-text-light, #7f8c8d);
}

.cc-necesidad-card__progreso {
    margin-bottom: 1rem;
}

.cc-badge-cubierta {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: var(--cc-success, #27ae60);
    color: #fff;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.cc-badge-mia {
    background: var(--cc-primary, #e74c3c);
    color: #fff;
    padding: 0.375rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.cc-info-unirse {
    font-size: 0.8125rem;
    color: var(--cc-text-light, #7f8c8d);
    font-style: italic;
}

.cc-filtros__form select {
    padding: 0.5rem 1rem;
    border: 1px solid var(--cc-border, #ecf0f1);
    border-radius: 6px;
    font-size: 0.875rem;
}
</style>
