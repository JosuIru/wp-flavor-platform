<?php
/**
 * Template de sección Testimonios
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
$items = $data['items'] ?? [];
$color_fondo = $data['color_fondo'] ?? '#f1f5f9';
?>

<div class="flavor-testimonios flavor-testimonios--<?php echo esc_attr($variant); ?>">
    <?php if ($titulo): ?>
        <div class="flavor-section__header">
            <h2 class="flavor-section__title"><?php echo esc_html($titulo); ?></h2>
        </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
        <div class="flavor-testimonios__grid">
            <?php foreach ($items as $item): ?>
                <div class="flavor-testimonio-card">
                    <?php if (!empty($item['valoracion']) && intval($item['valoracion']) > 0): ?>
                        <div class="flavor-testimonio-card__stars">
                            <?php for ($i = 0; $i < intval($item['valoracion']); $i++): ?>
                                <span class="dashicons dashicons-star-filled"></span>
                            <?php endfor; ?>
                            <?php for ($i = intval($item['valoracion']); $i < 5; $i++): ?>
                                <span class="dashicons dashicons-star-empty"></span>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['texto'])): ?>
                        <blockquote class="flavor-testimonio-card__quote">
                            "<?php echo esc_html($item['texto']); ?>"
                        </blockquote>
                    <?php endif; ?>

                    <div class="flavor-testimonio-card__author">
                        <?php if (!empty($item['imagen'])): ?>
                            <img
                                src="<?php echo esc_url($item['imagen']); ?>"
                                alt="<?php echo esc_attr($item['nombre'] ?? ''); ?>"
                                class="flavor-testimonio-card__avatar"
                                loading="lazy"
                            >
                        <?php else: ?>
                            <div class="flavor-testimonio-card__avatar flavor-testimonio-card__avatar--placeholder">
                                <span class="dashicons dashicons-admin-users"></span>
                            </div>
                        <?php endif; ?>

                        <div class="flavor-testimonio-card__info">
                            <?php if (!empty($item['nombre'])): ?>
                                <strong><?php echo esc_html($item['nombre']); ?></strong>
                            <?php endif; ?>
                            <?php if (!empty($item['cargo'])): ?>
                                <span><?php echo esc_html($item['cargo']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.flavor-testimonio-card__avatar--placeholder {
    width: 50px;
    height: 50px;
    background: var(--flavor-bg-alt, #f1f5f9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-testimonio-card__avatar--placeholder .dashicons {
    color: var(--flavor-text-light, #64748b);
}
</style>
