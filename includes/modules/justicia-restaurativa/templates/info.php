<?php
/**
 * Template: Información sobre Justicia Restaurativa
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$tipos = Flavor_Chat_Justicia_Restaurativa_Module::TIPOS_PROCESO;
?>

<div class="jr-info">
    <header class="jr-info__header">
        <h2><?php esc_html_e('Justicia Restaurativa', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Un enfoque comunitario para resolver conflictos, basado en el diálogo, la reparación y la reconciliación.', 'flavor-chat-ia'); ?></p>
    </header>

    <!-- Principios -->
    <div class="jr-principios">
        <div class="jr-principio">
            <div class="jr-principio__icono">
                <span class="dashicons dashicons-heart"></span>
            </div>
            <h3 class="jr-principio__titulo"><?php esc_html_e('Reparar, no castigar', 'flavor-chat-ia'); ?></h3>
            <p class="jr-principio__descripcion">
                <?php esc_html_e('El objetivo es sanar el daño causado, no infligir sufrimiento como respuesta.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="jr-principio">
            <div class="jr-principio__icono">
                <span class="dashicons dashicons-groups"></span>
            </div>
            <h3 class="jr-principio__titulo"><?php esc_html_e('Diálogo comunitario', 'flavor-chat-ia'); ?></h3>
            <p class="jr-principio__descripcion">
                <?php esc_html_e('Las personas afectadas participan activamente en la búsqueda de soluciones.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="jr-principio">
            <div class="jr-principio__icono">
                <span class="dashicons dashicons-universal-access"></span>
            </div>
            <h3 class="jr-principio__titulo"><?php esc_html_e('Dignidad de todas las partes', 'flavor-chat-ia'); ?></h3>
            <p class="jr-principio__descripcion">
                <?php esc_html_e('Todas las personas involucradas merecen respeto y tienen necesidades legítimas.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <div class="jr-principio">
            <div class="jr-principio__icono">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <h3 class="jr-principio__titulo"><?php esc_html_e('Confidencialidad', 'flavor-chat-ia'); ?></h3>
            <p class="jr-principio__descripcion">
                <?php esc_html_e('Todo lo dicho en los procesos es confidencial y no puede usarse en otros contextos.', 'flavor-chat-ia'); ?>
            </p>
        </div>
    </div>

    <!-- Tipos de proceso -->
    <section class="jr-tipos">
        <h3 class="jr-tipos__titulo"><?php esc_html_e('Tipos de procesos restaurativos', 'flavor-chat-ia'); ?></h3>

        <div class="jr-tipos__grid">
            <?php foreach ($tipos as $tipo_id => $tipo_data) : ?>
            <article class="jr-tipo-card" style="--tipo-color: <?php echo esc_attr($tipo_data['color']); ?>">
                <header class="jr-tipo-card__header">
                    <div class="jr-tipo-card__icono">
                        <span class="dashicons <?php echo esc_attr($tipo_data['icono']); ?>"></span>
                    </div>
                    <h4 class="jr-tipo-card__nombre"><?php echo esc_html($tipo_data['nombre']); ?></h4>
                </header>
                <p class="jr-tipo-card__descripcion"><?php echo esc_html($tipo_data['descripcion']); ?></p>
                <span class="jr-tipo-card__duracion">
                    <span class="dashicons dashicons-clock"></span>
                    <?php echo esc_html($tipo_data['duracion_estimada']); ?>
                </span>
            </article>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA -->
    <?php if (is_user_logged_in()) : ?>
    <div style="text-align: center; margin-top: 3rem;">
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('justicia_restaurativa', 'solicitar')); ?>" class="jr-btn jr-btn--primary">
            <span class="dashicons dashicons-shield"></span>
            <?php esc_html_e('Solicitar mediación', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>
