<?php
/**
 * Template: Vista de encuesta
 *
 * Variables disponibles:
 * - $encuesta: object - Datos de la encuesta
 * - $puede_responder: bool
 * - $ya_participo: bool
 * - $puede_ver_resultados: bool
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($encuesta) || !$encuesta) {
    return;
}

$estados_labels = [
    'borrador'  => __('Borrador', 'flavor-chat-ia'),
    'activa'    => __('Activa', 'flavor-chat-ia'),
    'cerrada'   => __('Cerrada', 'flavor-chat-ia'),
    'archivada' => __('Archivada', 'flavor-chat-ia'),
];

$tipos_labels = [
    'encuesta'   => __('Encuesta', 'flavor-chat-ia'),
    'formulario' => __('Formulario', 'flavor-chat-ia'),
    'quiz'       => __('Quiz', 'flavor-chat-ia'),
];
?>

<article class="flavor-encuesta flavor-encuesta--<?php echo esc_attr($encuesta->estado); ?>"
         data-encuesta-id="<?php echo esc_attr($encuesta->id); ?>"
         data-tipo="<?php echo esc_attr($encuesta->tipo); ?>">

    <header class="flavor-encuesta__header">
        <h2 class="flavor-encuesta__titulo"><?php echo esc_html($encuesta->titulo); ?></h2>

        <?php if (!empty($encuesta->descripcion)): ?>
            <div class="flavor-encuesta__descripcion">
                <?php echo wp_kses_post($encuesta->descripcion); ?>
            </div>
        <?php endif; ?>

        <div class="flavor-encuesta__meta">
            <span class="flavor-encuesta__badge flavor-encuesta__badge--tipo">
                <?php echo esc_html($tipos_labels[$encuesta->tipo] ?? $encuesta->tipo); ?>
            </span>

            <span class="flavor-encuesta__badge flavor-encuesta__badge--estado flavor-encuesta__badge--<?php echo esc_attr($encuesta->estado); ?>">
                <?php echo esc_html($estados_labels[$encuesta->estado] ?? $encuesta->estado); ?>
            </span>

            <?php if ($encuesta->es_anonima): ?>
                <span class="flavor-encuesta__badge flavor-encuesta__badge--anonima">
                    <?php esc_html_e('Anónima', 'flavor-chat-ia'); ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($encuesta->estado === 'cerrada'): ?>
        <div class="flavor-encuesta__notice flavor-encuesta__notice--info">
            <strong><?php esc_html_e('Encuesta finalizada', 'flavor-chat-ia'); ?></strong>
            <?php if (!empty($encuesta->fecha_cierre)): ?>
                - <?php printf(
                    esc_html__('Cerrada el %s', 'flavor-chat-ia'),
                    date_i18n(get_option('date_format'), strtotime($encuesta->fecha_cierre))
                ); ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($ya_participo) && !$encuesta->permite_multiples): ?>
        <div class="flavor-encuesta__notice flavor-encuesta__notice--success">
            <?php esc_html_e('Ya has participado en esta encuesta.', 'flavor-chat-ia'); ?>
        </div>
    <?php endif; ?>

    <div class="flavor-encuesta__body">
        <?php if (!empty($puede_responder) && $encuesta->estado === 'activa'): ?>
            <!-- Formulario de respuesta -->
            <form class="flavor-encuesta__form" method="post">
                <?php wp_nonce_field('flavor_encuestas_nonce', 'encuesta_nonce'); ?>

                <div class="flavor-encuesta__campos">
                    <?php
                    if (!empty($encuesta->campos)):
                        foreach ($encuesta->campos as $campo):
                            ?>
                            <div class="flavor-encuesta__campo flavor-encuesta__campo--<?php echo esc_attr($campo->tipo); ?>"
                                 data-campo-id="<?php echo esc_attr($campo->id); ?>">

                                <label class="flavor-encuesta__label">
                                    <?php echo esc_html($campo->etiqueta); ?>
                                    <?php if ($campo->es_requerido): ?>
                                        <span class="flavor-encuesta__required">*</span>
                                    <?php endif; ?>
                                </label>

                                <?php if (!empty($campo->descripcion)): ?>
                                    <p class="flavor-encuesta__help"><?php echo esc_html($campo->descripcion); ?></p>
                                <?php endif; ?>

                                <?php
                                // Renderizar input según tipo
                                include dirname(__FILE__) . '/partials/campo-' . $campo->tipo . '.php';
                                ?>
                            </div>
                            <?php
                        endforeach;
                    endif;
                    ?>
                </div>

                <div class="flavor-encuesta__actions">
                    <button type="submit" class="flavor-encuesta__submit">
                        <?php
                        if ($encuesta->tipo === 'quiz') {
                            esc_html_e('Enviar respuestas', 'flavor-chat-ia');
                        } else {
                            esc_html_e('Votar', 'flavor-chat-ia');
                        }
                        ?>
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <?php if (!empty($puede_ver_resultados)): ?>
            <!-- Resultados -->
            <div class="flavor-encuesta__resultados-section">
                <?php include dirname(__FILE__) . '/resultados.php'; ?>
            </div>
        <?php endif; ?>
    </div>

    <footer class="flavor-encuesta__footer">
        <div class="flavor-encuesta__stats">
            <span class="flavor-encuesta__participantes">
                <?php
                printf(
                    esc_html(_n('%d participante', '%d participantes', $encuesta->total_participantes, 'flavor-chat-ia')),
                    $encuesta->total_participantes
                );
                ?>
            </span>

            <?php if (!empty($encuesta->fecha_cierre) && $encuesta->estado === 'activa'): ?>
                <span class="flavor-encuesta__cierre">
                    <?php
                    $tiempo_restante = strtotime($encuesta->fecha_cierre) - time();
                    if ($tiempo_restante > 0) {
                        printf(
                            esc_html__('Cierra en %s', 'flavor-chat-ia'),
                            human_time_diff(time(), strtotime($encuesta->fecha_cierre))
                        );
                    }
                    ?>
                </span>
            <?php endif; ?>
        </div>

        <?php if (!empty($encuesta->autor_id)): ?>
            <?php $autor = get_userdata($encuesta->autor_id); ?>
            <?php if ($autor): ?>
                <div class="flavor-encuesta__autor">
                    <?php
                    printf(
                        esc_html__('Creada por %s', 'flavor-chat-ia'),
                        esc_html($autor->display_name)
                    );
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </footer>
</article>
