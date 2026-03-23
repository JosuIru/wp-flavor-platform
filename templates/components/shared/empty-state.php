<?php
/**
 * Empty State Component
 *
 * Componente reutilizable para mostrar estados vacíos de forma visual y accionable.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renderiza un estado vacío visual con icono, mensaje y acciones
 *
 * @param array $args {
 *     Argumentos del componente de estado vacío
 *
 *     @type string $icon       Clase de Dashicon (ej: 'dashicons-carrot', 'dashicons-groups')
 *     @type string $title      Título principal del estado vacío
 *     @type string $message    Mensaje descriptivo (opcional)
 *     @type array  $actions    Array de botones de acción: ['text' => '...', 'url' => '...', 'class' => 'primary']
 *     @type string $image      URL de imagen SVG personalizada (alternativa al icono)
 *     @type string $class      Clase CSS adicional para el contenedor
 * }
 *
 * @example
 * flavor_render_empty_state([
 *     'icon' => 'dashicons-admin-post',
 *     'title' => 'No hay eventos',
 *     'message' => 'Aún no se han creado eventos. ¡Sé el primero en crear uno!',
 *     'actions' => [
 *         ['text' => 'Crear evento', 'url' => '/crear-evento', 'class' => 'primary']
 *     ]
 * ]);
 */
function flavor_render_empty_state($args = []) {
    $defaults = [
        'icon' => 'dashicons-info',
        'title' => __('No hay contenido disponible', 'flavor-chat-ia'),
        'message' => '',
        'actions' => [],
        'image' => '',
        'class' => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $container_classes = ['flavor-empty-state'];
    if (!empty($args['class'])) {
        $container_classes[] = $args['class'];
    }
    ?>
    <div class="<?php echo esc_attr(implode(' ', $container_classes)); ?>">
        <div class="empty-state-icon">
            <?php if (!empty($args['image'])): ?>
                <img src="<?php echo esc_url($args['image']); ?>"
                     alt=""
                     class="empty-state-image">
            <?php else: ?>
                <span class="dashicons <?php echo esc_attr($args['icon']); ?>"></span>
            <?php endif; ?>
        </div>

        <h3 class="empty-state-title"><?php echo esc_html($args['title']); ?></h3>

        <?php if (!empty($args['message'])): ?>
            <p class="empty-state-message"><?php echo esc_html($args['message']); ?></p>
        <?php endif; ?>

        <?php if (!empty($args['actions'])): ?>
            <div class="empty-state-actions">
                <?php foreach ($args['actions'] as $action): ?>
                    <?php
                    $button_class = 'button';
                    if (isset($action['class'])) {
                        $button_class .= ' button-' . esc_attr($action['class']);
                    } else {
                        $button_class .= ' button-secondary';
                    }

                    $target = isset($action['target']) ? $action['target'] : '_self';
                    ?>
                    <a href="<?php echo esc_url($action['url']); ?>"
                       class="<?php echo esc_attr($button_class); ?>"
                       target="<?php echo esc_attr($target); ?>">
                        <?php if (isset($action['icon'])): ?>
                            <span class="dashicons <?php echo esc_attr($action['icon']); ?>"></span>
                        <?php endif; ?>
                        <?php echo esc_html($action['text']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
