<?php
/**
 * Template de sección FAQ (Preguntas Frecuentes)
 *
 * @package FlavorPlatform
 * @var array $data Datos de la sección
 * @var string $variant Variante de la sección
 * @var string $section_id ID de la sección
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer datos
$titulo = $data['titulo'] ?? '';
$subtitulo = $data['subtitulo'] ?? '';
$items = $data['items'] ?? [];
$color_fondo = $data['color_fondo'] ?? '#f8fafc';
?>

<div class="flavor-faq flavor-faq--<?php echo esc_attr($variant); ?>">
    <?php if ($titulo || $subtitulo): ?>
        <div class="flavor-section__header">
            <?php if ($titulo): ?>
                <h2 class="flavor-section__title"><?php echo esc_html($titulo); ?></h2>
            <?php endif; ?>
            <?php if ($subtitulo): ?>
                <p class="flavor-section__subtitle"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
        <div class="flavor-faq__list">
            <?php foreach ($items as $index => $item):
                $faq_id = $section_id . '-faq-' . $index;
            ?>
                <div class="flavor-faq__item">
                    <button
                        class="flavor-faq__question"
                        aria-expanded="false"
                        aria-controls="<?php echo esc_attr($faq_id); ?>"
                        onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'); document.getElementById('<?php echo esc_js($faq_id); ?>').hidden = this.getAttribute('aria-expanded') === 'false';"
                    >
                        <span><?php echo esc_html($item['pregunta'] ?? ''); ?></span>
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <div id="<?php echo esc_attr($faq_id); ?>" class="flavor-faq__answer" hidden>
                        <p><?php echo esc_html($item['respuesta'] ?? ''); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
