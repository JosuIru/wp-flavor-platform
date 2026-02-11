<?php
/**
 * Template de sección Pricing (Precios)
 *
 * @package FlavorChatIA
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
$color_fondo = $data['color_fondo'] ?? '#ffffff';
?>

<div class="flavor-pricing flavor-pricing--<?php echo esc_attr($variant); ?>">
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
        <div class="flavor-pricing__grid">
            <?php foreach ($items as $item):
                $destacado_class = !empty($item['destacado']) ? ' flavor-pricing-card--featured' : '';
            ?>
                <div class="flavor-pricing-card<?php echo $destacado_class; ?>">
                    <?php if (!empty($item['nombre'])): ?>
                        <h3 class="flavor-pricing-card__name"><?php echo esc_html($item['nombre']); ?></h3>
                    <?php endif; ?>

                    <div class="flavor-pricing-card__price">
                        <span class="flavor-pricing-card__amount"><?php echo esc_html($item['precio'] ?? ''); ?></span>
                        <span class="flavor-pricing-card__period"><?php echo esc_html($item['periodo'] ?? ''); ?></span>
                    </div>

                    <?php if (!empty($item['descripcion'])): ?>
                        <p class="flavor-pricing-card__description"><?php echo esc_html($item['descripcion']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($item['caracteristicas'])):
                        $features = array_filter(array_map('trim', explode("\n", $item['caracteristicas'])));
                    ?>
                        <ul class="flavor-pricing-card__features">
                            <?php foreach ($features as $feature): ?>
                                <li>
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php echo esc_html($feature); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if (!empty($item['cta_texto'])): ?>
                        <a href="<?php echo esc_url($item['cta_url'] ?? '#'); ?>" class="flavor-btn flavor-btn--primary">
                            <?php echo esc_html($item['cta_texto']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
