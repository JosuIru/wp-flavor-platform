<?php
/**
 * Template: Evaluación de Necesidades
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$categorias = Flavor_Chat_Economia_Suficiencia_Module::CATEGORIAS_NECESIDADES;
$evaluacion_anterior = get_user_meta($user_id, '_es_evaluacion_necesidades', true) ?: [];
?>

<div class="es-container">
    <header class="es-header">
        <h2><?php esc_html_e('Evaluación de Necesidades', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('¿Cómo de satisfechas están tus necesidades fundamentales? Evalúa cada área del 1 al 5.', 'flavor-chat-ia'); ?></p>
    </header>

    <div class="es-cita" style="margin-bottom: 2rem;">
        <p class="es-cita__texto"><?php esc_html_e('Las necesidades humanas son pocas, finitas y clasificables. Los satisfactores son infinitos.', 'flavor-chat-ia'); ?></p>
        <span class="es-cita__autor">— Manfred Max-Neef</span>
    </div>

    <form class="es-evaluacion es-form-evaluacion">
        <?php foreach ($categorias as $cat_id => $cat_data) :
            $valor_actual = $evaluacion_anterior[$cat_id] ?? 0;
        ?>
        <div class="es-necesidad-item">
            <div class="es-necesidad__header">
                <div class="es-necesidad__icono" style="background: <?php echo esc_attr($cat_data['color']); ?>">
                    <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>"></span>
                </div>
                <div class="es-necesidad__info">
                    <h4><?php echo esc_html($cat_data['nombre']); ?></h4>
                    <p><?php echo esc_html($cat_data['descripcion']); ?></p>
                </div>
            </div>

            <input type="hidden" id="es-necesidad-<?php echo esc_attr($cat_id); ?>" name="<?php echo esc_attr($cat_id); ?>" value="<?php echo esc_attr($valor_actual); ?>">

            <div class="es-necesidad__escala" data-categoria="<?php echo esc_attr($cat_id); ?>">
                <?php for ($i = 1; $i <= 5; $i++) : ?>
                <button type="button" class="es-escala-btn <?php echo $valor_actual == $i ? 'seleccionado' : ''; ?>" data-valor="<?php echo esc_attr($i); ?>">
                    <?php echo esc_html($i); ?>
                </button>
                <?php endfor; ?>
            </div>
            <div class="es-escala-labels">
                <span><?php esc_html_e('Insatisfecha', 'flavor-chat-ia'); ?></span>
                <span><?php esc_html_e('Plenamente satisfecha', 'flavor-chat-ia'); ?></span>
            </div>
        </div>
        <?php endforeach; ?>

        <div style="text-align: center; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--es-border);">
            <button type="submit" class="es-btn es-btn--primary">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Guardar evaluación', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </form>
</div>
