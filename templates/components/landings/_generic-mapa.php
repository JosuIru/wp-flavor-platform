<?php
/**
 * Template genérico para componentes de mapa
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? 'Mapa';
$altura_mapa = $altura_mapa ?? 400;
$id_seccion = $id_seccion ?? '';
?>

<section<?php if ($id_seccion): ?> id="<?php echo esc_attr($id_seccion); ?>"<?php endif; ?> class="flavor-map-section">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
        <div class="flavor-map-placeholder" style="height: <?php echo intval($altura_mapa); ?>px;">
            <div class="flavor-map-overlay">
                <span class="dashicons dashicons-location"></span>
                <p>Mapa interactivo</p>
                <small>Configura las coordenadas para mostrar el mapa</small>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-map-section {
    padding: 4rem 0;
}
.flavor-map-placeholder {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.flavor-map-placeholder::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image:
        linear-gradient(rgba(156, 163, 175, 0.3) 1px, transparent 1px),
        linear-gradient(90deg, rgba(156, 163, 175, 0.3) 1px, transparent 1px);
    background-size: 40px 40px;
}
.flavor-map-overlay {
    text-align: center;
    color: #6b7280;
    z-index: 1;
}
.flavor-map-overlay .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #9ca3af;
}
.flavor-map-overlay p {
    margin: 1rem 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 500;
}
.flavor-map-overlay small {
    font-size: 0.875rem;
}
</style>
