<?php
/**
 * Template Genérico: Contenido
 *
 * Variables disponibles:
 * - $titulo (string): Título de la sección
 * - $contenido (string): Contenido HTML/texto
 * - $items (array): Lista de elementos (pasos, características, etc.)
 * - $imagen (string): URL de imagen lateral
 * - $posicion_imagen (string): izquierda, derecha
 * - $color_primario (string): Color primario
 * - $estilo (string): texto, pasos, lista, proceso
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$titulo = $titulo ?? '';
$contenido = $contenido ?? '';
$items = $items ?? [];
$imagen = $imagen ?? '';
$posicion_imagen = $posicion_imagen ?? 'derecha';
$color_primario = $color_primario ?? '#4f46e5';
$estilo = $estilo ?? 'texto';

// Generar ID único
$unique_id = 'fgcnt-' . wp_unique_id();
?>

<section class="flavor-generic-content <?php echo esc_attr($unique_id); ?> fgcnt--<?php echo esc_attr($estilo); ?>"
         style="--fgcnt-primary: <?php echo esc_attr($color_primario); ?>;">

    <div class="fgcnt-wrapper <?php echo !empty($imagen) ? 'fgcnt-wrapper--with-image fgcnt-wrapper--image-' . esc_attr($posicion_imagen) : ''; ?>">

        <?php if (!empty($imagen) && $posicion_imagen === 'izquierda') : ?>
        <div class="fgcnt-image">
            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($titulo); ?>" loading="lazy">
        </div>
        <?php endif; ?>

        <div class="fgcnt-content">
            <?php if (!empty($titulo)) : ?>
                <h2 class="fgcnt-title"><?php echo esc_html($titulo); ?></h2>
            <?php endif; ?>

            <?php if (!empty($contenido)) : ?>
                <div class="fgcnt-text"><?php echo wp_kses_post($contenido); ?></div>
            <?php endif; ?>

            <?php if (!empty($items)) : ?>
            <div class="fgcnt-items fgcnt-items--<?php echo esc_attr($estilo); ?>">
                <?php foreach ($items as $index => $item) :
                    $item_titulo = $item['titulo'] ?? $item['title'] ?? '';
                    $item_desc = $item['descripcion'] ?? $item['description'] ?? '';
                    $item_icon = $item['icono'] ?? $item['icon'] ?? '';
                ?>
                <div class="fgcnt-item">
                    <?php if ($estilo === 'pasos' || $estilo === 'proceso') : ?>
                        <span class="fgcnt-item__number"><?php echo esc_html($index + 1); ?></span>
                    <?php elseif (!empty($item_icon)) : ?>
                        <span class="fgcnt-item__icon dashicons <?php echo esc_attr($item_icon); ?>"></span>
                    <?php else : ?>
                        <span class="fgcnt-item__check dashicons dashicons-yes-alt"></span>
                    <?php endif; ?>

                    <div class="fgcnt-item__content">
                        <?php if (!empty($item_titulo)) : ?>
                            <h3 class="fgcnt-item__title"><?php echo esc_html($item_titulo); ?></h3>
                        <?php endif; ?>
                        <?php if (!empty($item_desc)) : ?>
                            <p class="fgcnt-item__desc"><?php echo esc_html($item_desc); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (!empty($imagen) && $posicion_imagen === 'derecha') : ?>
        <div class="fgcnt-image">
            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($titulo); ?>" loading="lazy">
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-generic-content {
    padding: 60px 20px;
}

.fgcnt-wrapper {
    max-width: 1200px;
    margin: 0 auto;
}

.fgcnt-wrapper--with-image {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.fgcnt-image img {
    width: 100%;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.fgcnt-title {
    font-size: 2rem;
    font-weight: 700;
    color: #111827;
    margin: 0 0 20px;
}

.fgcnt-text {
    font-size: 1.0625rem;
    line-height: 1.7;
    color: #4b5563;
}

.fgcnt-text p {
    margin: 0 0 16px;
}

.fgcnt-items {
    margin-top: 32px;
}

.fgcnt-items--lista .fgcnt-item,
.fgcnt-items--texto .fgcnt-item {
    display: flex;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f3f4f6;
}

.fgcnt-items--pasos .fgcnt-item,
.fgcnt-items--proceso .fgcnt-item {
    display: flex;
    gap: 20px;
    padding: 24px 0;
    position: relative;
}

.fgcnt-items--proceso .fgcnt-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 56px;
    bottom: -24px;
    width: 2px;
    background: linear-gradient(to bottom, var(--fgcnt-primary), transparent);
}

.fgcnt-item__number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--fgcnt-primary);
    color: white;
    border-radius: 50%;
    font-weight: 700;
    flex-shrink: 0;
}

.fgcnt-item__icon,
.fgcnt-item__check {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    color: var(--fgcnt-primary);
    flex-shrink: 0;
}

.fgcnt-item__check {
    font-size: 24px;
}

.fgcnt-item__content {
    flex: 1;
}

.fgcnt-item__title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #111827;
    margin: 0 0 4px;
}

.fgcnt-item__desc {
    font-size: 0.9375rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .fgcnt-wrapper--with-image {
        grid-template-columns: 1fr;
        gap: 32px;
    }

    .fgcnt-wrapper--image-izquierda .fgcnt-image {
        order: -1;
    }

    .fgcnt-title {
        font-size: 1.5rem;
    }
}
</style>
