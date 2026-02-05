<?php
/**
 * Template genérico para componentes Grid/Listado
 *
 * Variables: $titulo, $columnas, $items, $color_primario
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? 'Listado';
$columnas = $columnas ?? 3;
$items = $items ?? [];
$color_primario = $color_primario ?? '#3b82f6';
$id_seccion = $id_seccion ?? '';
$limite = count($items) > 0 ? count($items) : ($limite ?? 6);
?>

<section<?php if ($id_seccion): ?> id="<?php echo esc_attr($id_seccion); ?>"<?php endif; ?> class="flavor-grid-section" style="--color-primario: <?php echo esc_attr($color_primario); ?>;">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
        <div class="flavor-grid" style="--columns: <?php echo intval($columnas); ?>;">
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <div class="flavor-grid-item">
                        <?php if (!empty($item['icono'])): ?>
                            <div class="flavor-grid-item-icon" style="background: <?php echo esc_attr($color_primario); ?>15;">
                                <span class="dashicons dashicons-<?php echo esc_attr($item['icono']); ?>" style="color: <?php echo esc_attr($color_primario); ?>;"></span>
                            </div>
                        <?php elseif (!empty($item['imagen'])): ?>
                            <div class="flavor-grid-item-image">
                                <img src="<?php echo esc_url($item['imagen']); ?>" alt="<?php echo esc_attr($item['titulo'] ?? ''); ?>">
                            </div>
                        <?php else: ?>
                            <div class="flavor-grid-item-image flavor-placeholder-bg"></div>
                        <?php endif; ?>
                        <div class="flavor-grid-item-content">
                            <h3><?php echo esc_html($item['titulo'] ?? ''); ?></h3>
                            <?php if (!empty($item['descripcion'])): ?>
                                <p><?php echo esc_html($item['descripcion']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php for ($i = 0; $i < min($limite, 6); $i++): ?>
                    <div class="flavor-grid-item flavor-placeholder">
                        <div class="flavor-grid-item-image flavor-placeholder-bg"></div>
                        <div class="flavor-grid-item-content">
                            <h3>Elemento <?php echo $i + 1; ?></h3>
                            <p>Descripción del elemento</p>
                        </div>
                    </div>
                <?php endfor; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.flavor-grid-section {
    padding: 4rem 0;
}
.flavor-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 1rem;
}
.flavor-section-title {
    font-size: 2rem;
    text-align: center;
    margin-bottom: 2rem;
    color: #1f2937;
}
.flavor-grid {
    display: grid;
    grid-template-columns: repeat(var(--columns, 3), 1fr);
    gap: 1.5rem;
}
@media (max-width: 768px) {
    .flavor-grid {
        grid-template-columns: 1fr;
    }
}
.flavor-grid-item {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-grid-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
}
.flavor-grid-item-image {
    height: 200px;
    background: #f3f4f6;
}
.flavor-grid-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.flavor-grid-item-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.flavor-grid-item-icon .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
}
.flavor-placeholder-bg {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
}
.flavor-grid-item-content {
    padding: 1.5rem;
}
.flavor-grid-item h3 {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    color: #1f2937;
}
.flavor-grid-item p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}
.flavor-placeholder .flavor-grid-item-image {
    animation: pulse 2s infinite;
}
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}
</style>
