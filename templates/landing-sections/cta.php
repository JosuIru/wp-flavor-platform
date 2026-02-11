<?php
/**
 * Template de sección CTA (Call to Action)
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
$descripcion = $data['descripcion'] ?? '';
$imagen = $data['imagen'] ?? '';
$boton_texto = $data['boton_texto'] ?? '';
$boton_url = $data['boton_url'] ?? '#';
$boton_secundario_texto = $data['boton_secundario_texto'] ?? '';
$boton_secundario_url = $data['boton_secundario_url'] ?? '';
$color_fondo = $data['color_fondo'] ?? '#3b82f6';
$color_texto = $data['color_texto'] ?? '#ffffff';
?>

<div class="flavor-cta flavor-cta--<?php echo esc_attr($variant); ?>">
    <?php if ($variant === 'con_imagen' && $imagen): ?>
        <div class="flavor-cta__image">
            <img src="<?php echo esc_url($imagen); ?>" alt="" loading="lazy">
        </div>
    <?php endif; ?>

    <div class="flavor-cta__content">
        <?php if ($titulo): ?>
            <h2 class="flavor-cta__title"><?php echo esc_html($titulo); ?></h2>
        <?php endif; ?>

        <?php if ($descripcion): ?>
            <p class="flavor-cta__description"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <?php if ($boton_texto): ?>
            <div class="flavor-cta__buttons">
                <a href="<?php echo esc_url($boton_url); ?>" class="flavor-btn flavor-btn--white flavor-btn--large">
                    <?php echo esc_html($boton_texto); ?>
                </a>
                <?php if ($boton_secundario_texto): ?>
                    <a href="<?php echo esc_url($boton_secundario_url); ?>" class="flavor-btn flavor-btn--secondary" style="border-color: #fff; color: #fff;">
                        <?php echo esc_html($boton_secundario_texto); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
