<?php
/**
 * Template de sección Features/Características
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
$color_fondo = $data['color_fondo'] ?? '#ffffff';

// Determinar columnas según variante
$columns = 3;
switch ($variant) {
    case 'grid_4':
        $columns = 4;
        break;
    case 'lista':
        $columns = 1;
        break;
    case 'grid_3':
    default:
        $columns = 3;
}

$grid_class = 'flavor-grid--' . $columns;
?>

<div class="flavor-features flavor-features--<?php echo esc_attr($variant); ?>">
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
        <div class="flavor-features__grid <?php echo esc_attr($grid_class); ?>">
            <?php foreach ($items as $item): ?>
                <div class="flavor-feature-card">
                    <?php if (!empty($item['icono'])): ?>
                        <div class="flavor-feature-card__icon">
                            <span class="dashicons dashicons-<?php echo esc_attr($item['icono']); ?>"></span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($item['titulo'])): ?>
                        <h3 class="flavor-feature-card__title"><?php echo esc_html($item['titulo']); ?></h3>
                    <?php endif; ?>

                    <?php if (!empty($item['descripcion'])): ?>
                        <p class="flavor-feature-card__description"><?php echo esc_html($item['descripcion']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
