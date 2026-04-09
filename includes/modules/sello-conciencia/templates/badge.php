<?php
/**
 * Template: Badge del Sello de Conciencia
 *
 * Variables disponibles:
 * - $evaluacion: array con la evaluación completa
 * - $atts: atributos del shortcode (size, show_details)
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$nivel = $evaluacion['nivel'];
$puntuacion = $evaluacion['puntuacion_global'];
$size = $atts['size'] ?? 'medium';
$show_details = filter_var($atts['show_details'] ?? false, FILTER_VALIDATE_BOOLEAN);

$size_classes = [
    'small' => 'fsc-badge--small',
    'medium' => 'fsc-badge--medium',
    'large' => 'fsc-badge--large',
];

$size_class = $size_classes[$size] ?? $size_classes['medium'];
?>

<div class="fsc-badge <?php echo esc_attr($size_class); ?> fsc-badge--<?php echo esc_attr($nivel['id']); ?>"
     style="--fsc-color: <?php echo esc_attr($nivel['color']); ?>">

    <div class="fsc-badge__seal">
        <div class="fsc-badge__circle">
            <span class="fsc-badge__score"><?php echo esc_html($puntuacion); ?></span>
            <span class="fsc-badge__max">/100</span>
        </div>
        <span class="fsc-badge__icon dashicons <?php echo esc_attr($nivel['icono']); ?>"></span>
    </div>

    <div class="fsc-badge__content">
        <span class="fsc-badge__label"><?php esc_html_e('App Consciente', 'flavor-platform'); ?></span>
        <span class="fsc-badge__level"><?php echo esc_html($nivel['nombre']); ?></span>
    </div>

    <?php if ($show_details && !empty($evaluacion['puntuaciones_premisas'])) : ?>
    <div class="fsc-badge__details">
        <h4><?php esc_html_e('Contribución por premisa', 'flavor-platform'); ?></h4>
        <ul class="fsc-badge__premisas">
            <?php foreach (Flavor_Chat_Sello_Conciencia_Module::PREMISAS as $premisa_id => $premisa) :
                $premisa_puntuacion = $evaluacion['puntuaciones_premisas'][$premisa_id] ?? 0;
            ?>
            <li class="fsc-badge__premisa">
                <span class="dashicons <?php echo esc_attr($premisa['icono']); ?>"
                      style="color: <?php echo esc_attr($premisa['color']); ?>"></span>
                <span class="fsc-badge__premisa-nombre"><?php echo esc_html($premisa['nombre']); ?></span>
                <span class="fsc-badge__premisa-valor"><?php echo esc_html($premisa_puntuacion); ?>%</span>
                <div class="fsc-badge__premisa-bar">
                    <div class="fsc-badge__premisa-fill"
                         style="width: <?php echo esc_attr($premisa_puntuacion); ?>%;
                                background-color: <?php echo esc_attr($premisa['color']); ?>"></div>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
