<?php
/**
 * Template: Las 5 Premisas de Conciencia
 *
 * Muestra las 5 premisas fundamentales de una economía consciente.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$premisas = Flavor_Chat_Sello_Conciencia_Module::PREMISAS;
?>

<div class="fsc-premisas">
    <header class="fsc-premisas__header">
        <h2 class="fsc-premisas__title">
            <?php esc_html_e('Las 5 Premisas de una Economía Consciente', 'flavor-chat-ia'); ?>
        </h2>
        <p class="fsc-premisas__intro">
            <?php esc_html_e('Estas premisas constituyen el marco ético y filosófico sobre el cual se evalúa cada módulo de la aplicación.', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <div class="fsc-premisas__grid">
        <?php $numero = 1; foreach ($premisas as $premisa_id => $premisa) : ?>
        <article class="fsc-premisa" style="--premisa-color: <?php echo esc_attr($premisa['color']); ?>">
            <header class="fsc-premisa__header">
                <span class="fsc-premisa__numero"><?php echo esc_html($numero); ?></span>
                <span class="fsc-premisa__icono dashicons <?php echo esc_attr($premisa['icono']); ?>"></span>
            </header>

            <h3 class="fsc-premisa__nombre"><?php echo esc_html($premisa['nombre']); ?></h3>

            <p class="fsc-premisa__descripcion">
                <?php echo esc_html($premisa['descripcion']); ?>
            </p>

            <div class="fsc-premisa__principio">
                <strong><?php esc_html_e('Principio:', 'flavor-chat-ia'); ?></strong>
                <p><?php echo esc_html($premisa['principio']); ?></p>
            </div>

            <div class="fsc-premisa__consecuencia">
                <strong><?php esc_html_e('En la práctica:', 'flavor-chat-ia'); ?></strong>
                <p><?php echo esc_html($premisa['consecuencia']); ?></p>
            </div>
        </article>
        <?php $numero++; endforeach; ?>
    </div>
</div>
