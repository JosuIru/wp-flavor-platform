<?php
/**
 * Template genérico para mostrar características/pasos
 *
 * Variables: $titulo, $subtitulo, $items, $color_primario, $id_seccion
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? 'Características';
$subtitulo = $subtitulo ?? '';
$items = $items ?? [];
$color_primario = $color_primario ?? '#3b82f6';
$id_seccion = $id_seccion ?? '';
$columnas = count($items) > 4 ? 4 : count($items);
?>

<section<?php if ($id_seccion): ?> <?php esc_html_e('id="', FLAVOR_PLATFORM_TEXT_DOMAIN); ?><?php echo esc_attr($id_seccion); ?>"<?php endif; ?> <?php esc_html_e('class="flavor-features-section" style="--color-primario:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <header class="flavor-features-header">
            <h2 class="flavor-features-title"><?php echo esc_html($titulo); ?></h2>
            <?php if ($subtitulo): ?>
                <p class="flavor-features-subtitle"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
        </header>

        <?php if (!empty($items)): ?>
            <div class="flavor-features-grid flavor-features-cols-<?php echo $columnas; ?>">
                <?php foreach ($items as $index => $item): ?>
                    <div class="flavor-feature-item">
                        <div class="flavor-feature-icon">
                            <?php if (!empty($item['icono'])): ?>
                                <span class="dashicons dashicons-<?php echo esc_attr($item['icono']); ?>"></span>
                            <?php else: ?>
                                <span class="flavor-feature-number"><?php echo $index + 1; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="flavor-feature-content">
                            <h3 class="flavor-feature-titulo"><?php echo esc_html($item['titulo'] ?? ''); ?></h3>
                            <?php if (!empty($item['descripcion'])): ?>
                                <p class="flavor-feature-descripcion"><?php echo esc_html($item['descripcion']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-features-section {
    padding: 4rem 0;
    background: #fff;
}
.flavor-features-header {
    text-align: center;
    margin-bottom: 3rem;
}
.flavor-features-title {
    font-size: 2rem;
    margin: 0 0 0.75rem;
    color: #1e293b;
}
.flavor-features-subtitle {
    font-size: 1.125rem;
    margin: 0;
    color: #64748b;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}
.flavor-features-grid {
    display: grid;
    gap: 2rem;
}
.flavor-features-cols-2 { grid-template-columns: repeat(2, 1fr); }
.flavor-features-cols-3 { grid-template-columns: repeat(3, 1fr); }
.flavor-features-cols-4 { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 992px) {
    .flavor-features-cols-4 { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 768px) {
    .flavor-features-grid {
        grid-template-columns: 1fr !important;
    }
}
.flavor-feature-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1.5rem;
}
.flavor-feature-icon {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: color-mix(in srgb, var(--color-primario) 10%, transparent);
    border-radius: 16px;
    margin-bottom: 1.25rem;
    color: var(--color-primario);
}
.flavor-feature-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}
.flavor-feature-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-primario);
}
.flavor-feature-titulo {
    font-size: 1.125rem;
    margin: 0 0 0.5rem;
    color: #1e293b;
}
.flavor-feature-descripcion {
    font-size: 0.9375rem;
    margin: 0;
    color: #64748b;
    line-height: 1.6;
}
</style>
