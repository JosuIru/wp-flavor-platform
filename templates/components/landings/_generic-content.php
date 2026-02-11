<?php
/**
 * Template genérico para componentes de contenido
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? 'Sección de Contenido';
?>

<section class="flavor-content-section">
    <div class="flavor-container">
        <h2 class="flavor-section-title"><?php echo esc_html($titulo); ?></h2>
        <div class="flavor-content-placeholder">
            <div class="flavor-content-items">
                <div class="flavor-content-item">
                    <div class="flavor-content-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h3><?php echo esc_html__('Paso 1', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Descripción del primer paso', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-content-item">
                    <div class="flavor-content-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h3><?php echo esc_html__('Paso 2', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Descripción del segundo paso', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="flavor-content-item">
                    <div class="flavor-content-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <h3><?php echo esc_html__('Paso 3', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Descripción del tercer paso', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.flavor-content-section {
    padding: 4rem 0;
    background: #f9fafb;
}
.flavor-content-items {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}
.flavor-content-item {
    text-align: center;
    padding: 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
.flavor-content-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    background: #dbeafe;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-content-icon .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #3b82f6;
}
.flavor-content-item h3 {
    margin: 0 0 0.5rem;
    color: #1f2937;
}
.flavor-content-item p {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}
</style>
