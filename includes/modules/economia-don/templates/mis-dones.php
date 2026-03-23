<?php
/**
 * Template: Mis Dones
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$modulo = new Flavor_Chat_Economia_Don_Module();
$stats = $modulo->get_estadisticas_usuario($user_id);
$categorias = Flavor_Chat_Economia_Don_Module::CATEGORIAS_DON;
$estados = Flavor_Chat_Economia_Don_Module::ESTADOS_DON;

// Dones ofrecidos
$mis_dones = get_posts([
    'post_type' => 'ed_don',
    'author' => $user_id,
    'posts_per_page' => -1,
    'post_status' => 'publish',
]);

// Dones recibidos
global $wpdb;
$dones_recibidos = $wpdb->get_results($wpdb->prepare(
    "SELECT p.* FROM {$wpdb->posts} p
     INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
     WHERE p.post_type = 'ed_don'
       AND pm.meta_key = '_ed_receptor_id'
       AND pm.meta_value = %d
     ORDER BY p.post_date DESC",
    $user_id
));
?>

<div class="ed-mis-dones">
    <h2><?php esc_html_e('Mis Dones', 'flavor-chat-ia'); ?></h2>

    <!-- Estadísticas -->
    <div class="ed-stats-row">
        <div class="ed-stat-box">
            <div class="ed-stat-box__valor"><?php echo esc_html($stats['dones_dados']); ?></div>
            <div class="ed-stat-box__label"><?php esc_html_e('Dones dados', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="ed-stat-box ed-stat-box--recibidos">
            <div class="ed-stat-box__valor"><?php echo esc_html($stats['dones_recibidos']); ?></div>
            <div class="ed-stat-box__label"><?php esc_html_e('Dones recibidos', 'flavor-chat-ia'); ?></div>
        </div>
        <div class="ed-stat-box ed-stat-box--activos">
            <div class="ed-stat-box__valor"><?php echo esc_html($stats['dones_activos']); ?></div>
            <div class="ed-stat-box__label"><?php esc_html_e('Activos ahora', 'flavor-chat-ia'); ?></div>
        </div>
    </div>

    <!-- Botón ofrecer -->
    <div style="text-align: center; margin-bottom: 2rem;">
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('economia_don', 'ofrecer')); ?>" class="ed-btn-publicar">
            <span class="dashicons dashicons-heart"></span>
            <?php esc_html_e('Ofrecer un nuevo don', 'flavor-chat-ia'); ?>
        </a>
    </div>

    <!-- Dones que he ofrecido -->
    <section class="ed-seccion">
        <h3><?php esc_html_e('Dones que he ofrecido', 'flavor-chat-ia'); ?></h3>

        <?php if ($mis_dones) : ?>
        <div class="ed-listado__grid">
            <?php foreach ($mis_dones as $don) :
                $categoria = get_post_meta($don->ID, '_ed_categoria', true);
                $cat_data = $categorias[$categoria] ?? $categorias['objetos'];
                $estado = get_post_meta($don->ID, '_ed_estado', true) ?: 'disponible';
                $receptor_id = get_post_meta($don->ID, '_ed_receptor_id', true);
            ?>
            <article class="ed-don-card" style="--don-color: <?php echo esc_attr($cat_data['color']); ?>">
                <div class="ed-don-card__imagen">
                    <?php if (has_post_thumbnail($don->ID)) : ?>
                        <?php echo get_the_post_thumbnail($don->ID, 'medium'); ?>
                    <?php else : ?>
                        <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
                    <?php endif; ?>
                    <span class="ed-don-card__estado ed-don-card__estado--<?php echo esc_attr($estado); ?>">
                        <?php echo esc_html($estados[$estado]['nombre']); ?>
                    </span>
                </div>

                <div class="ed-don-card__body">
                    <h4 class="ed-don-card__titulo">
                        <a href="<?php echo esc_url(get_permalink($don->ID)); ?>">
                            <?php echo esc_html($don->post_title); ?>
                        </a>
                    </h4>

                    <?php if ($estado === 'reservado' && $receptor_id) :
                        $receptor = get_userdata($receptor_id);
                    ?>
                    <p class="ed-don-card__receptor">
                        <?php printf(
                            esc_html__('Reservado para: %s', 'flavor-chat-ia'),
                            esc_html($receptor->display_name)
                        ); ?>
                    </p>
                    <button class="ed-btn-confirmar-entrega" data-don="<?php echo esc_attr($don->ID); ?>">
                        <?php esc_html_e('Confirmar entrega', 'flavor-chat-ia'); ?>
                    </button>
                    <?php elseif ($estado === 'entregado') : ?>
                    <span class="ed-badge-entregado">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Entregado', 'flavor-chat-ia'); ?>
                    </span>
                    <?php elseif ($estado === 'recibido') : ?>
                    <span class="ed-badge-recibido">
                        <span class="dashicons dashicons-smiley"></span>
                        <?php esc_html_e('Recibido con gratitud', 'flavor-chat-ia'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="ed-empty-text"><?php esc_html_e('Aún no has ofrecido ningún don.', 'flavor-chat-ia'); ?></p>
        <?php endif; ?>
    </section>

    <!-- Dones que he recibido -->
    <section class="ed-seccion" style="margin-top: 2rem;">
        <h3><?php esc_html_e('Dones que he recibido', 'flavor-chat-ia'); ?></h3>

        <?php if ($dones_recibidos) : ?>
        <div class="ed-listado__grid">
            <?php foreach ($dones_recibidos as $don) :
                $categoria = get_post_meta($don->ID, '_ed_categoria', true);
                $cat_data = $categorias[$categoria] ?? $categorias['objetos'];
                $estado = get_post_meta($don->ID, '_ed_estado', true);
                $donante_id = $don->post_author;
                $anonimo = get_post_meta($don->ID, '_ed_anonimo', true);
                $gratitud_id = get_post_meta($don->ID, '_ed_gratitud_id', true);

                $donante_nombre = $anonimo
                    ? __('Donante anónimo', 'flavor-chat-ia')
                    : get_userdata($donante_id)->display_name;
            ?>
            <article class="ed-don-card" style="--don-color: <?php echo esc_attr($cat_data['color']); ?>">
                <div class="ed-don-card__imagen">
                    <?php if (has_post_thumbnail($don->ID)) : ?>
                        <?php echo get_the_post_thumbnail($don->ID, 'medium'); ?>
                    <?php else : ?>
                        <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
                    <?php endif; ?>
                </div>

                <div class="ed-don-card__body">
                    <h4 class="ed-don-card__titulo"><?php echo esc_html($don->post_title); ?></h4>

                    <p class="ed-don-card__donante-info">
                        <?php printf(
                            esc_html__('Recibido de: %s', 'flavor-chat-ia'),
                            esc_html($donante_nombre)
                        ); ?>
                    </p>

                    <?php if ($estado === 'entregado' && !$gratitud_id) : ?>
                    <button class="ed-btn-agradecer" data-cc-modal="ed-modal-agradecer-<?php echo esc_attr($don->ID); ?>">
                        <span class="dashicons dashicons-smiley"></span>
                        <?php esc_html_e('Agradecer', 'flavor-chat-ia'); ?>
                    </button>

                    <!-- Modal agradecer -->
                    <div class="ed-modal" id="ed-modal-agradecer-<?php echo esc_attr($don->ID); ?>">
                        <div class="ed-modal__contenido">
                            <div class="ed-modal__header">
                                <h3 class="ed-modal__titulo"><?php esc_html_e('Expresar gratitud', 'flavor-chat-ia'); ?></h3>
                                <button class="ed-modal__cerrar">&times;</button>
                            </div>
                            <form class="ed-form-agradecer">
                                <input type="hidden" name="don_id" value="<?php echo esc_attr($don->ID); ?>">
                                <div class="ed-modal__body">
                                    <p style="margin-bottom: 1rem; color: var(--ed-text-light);">
                                        <?php esc_html_e('Tu agradecimiento se publicará en el Muro de Gratitud.', 'flavor-chat-ia'); ?>
                                    </p>
                                    <div class="ed-form-grupo">
                                        <label for="ed-gratitud-<?php echo esc_attr($don->ID); ?>">
                                            <?php esc_html_e('Tu mensaje de gratitud', 'flavor-chat-ia'); ?>
                                        </label>
                                        <textarea name="mensaje" id="ed-gratitud-<?php echo esc_attr($don->ID); ?>" rows="4" required
                                            placeholder="<?php esc_attr_e('Escribe unas palabras de agradecimiento...', 'flavor-chat-ia'); ?>"></textarea>
                                    </div>
                                </div>
                                <div class="ed-modal__footer">
                                    <button type="button" class="ed-btn ed-btn--secondary ed-modal__cerrar">
                                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                                    </button>
                                    <button type="submit" class="ed-btn-publicar">
                                        <?php esc_html_e('Publicar gratitud', 'flavor-chat-ia'); ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($gratitud_id) : ?>
                    <span class="ed-badge-agradecido">
                        <span class="dashicons dashicons-smiley"></span>
                        <?php esc_html_e('Agradecido', 'flavor-chat-ia'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else : ?>
        <p class="ed-empty-text"><?php esc_html_e('Aún no has recibido ningún don.', 'flavor-chat-ia'); ?></p>
        <?php endif; ?>
    </section>
</div>

<style>
.ed-seccion h3 {
    font-size: 1.25rem;
    margin: 0 0 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--ed-border);
}

.ed-badge-entregado,
.ed-badge-recibido,
.ed-badge-agradecido {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
}

.ed-badge-entregado {
    background: var(--ed-secondary);
    color: #fff;
}

.ed-badge-recibido,
.ed-badge-agradecido {
    background: var(--ed-success);
    color: #fff;
}

.ed-btn-agradecer {
    background: var(--ed-warning);
    color: #fff;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
}

.ed-don-card__receptor,
.ed-don-card__donante-info {
    font-size: 0.875rem;
    color: var(--ed-text-light);
    margin: 0.5rem 0 1rem;
}

.ed-empty-text {
    color: var(--ed-text-light);
    text-align: center;
    padding: 2rem;
}
</style>
