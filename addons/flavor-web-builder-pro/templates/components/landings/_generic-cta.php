<?php
/**
 * Template Genérico: Call to Action
 *
 * Variables disponibles:
 * - $titulo (string): Título del CTA
 * - $descripcion (string): Descripción
 * - $btn_texto (string): Texto del botón
 * - $btn_url (string): URL del botón
 * - $color_primario (string): Color primario
 * - $color_fondo (string): Color de fondo
 * - $icono (string): Icono dashicons
 * - $estilo (string): simple, destacado, banner
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$titulo = $titulo ?? __('¿Listo para empezar?', 'flavor-chat-ia');
$descripcion = $descripcion ?? '';
$btn_texto = $btn_texto ?? __('Comenzar', 'flavor-chat-ia');
$btn_url = $btn_url ?? '#';
$color_primario = $color_primario ?? '#4f46e5';
$color_fondo = $color_fondo ?? '';
$icono = $icono ?? '';
$estilo = $estilo ?? 'destacado';

// Generar ID único
$unique_id = 'fgc-' . wp_unique_id();
?>

<section class="flavor-generic-cta <?php echo esc_attr($unique_id); ?> fgc--<?php echo esc_attr($estilo); ?>"
         style="--fgc-primary: <?php echo esc_attr($color_primario); ?>;
                <?php if (!empty($color_fondo)) : ?>--fgc-bg: <?php echo esc_attr($color_fondo); ?>;<?php endif; ?>">

    <div class="fgc-content">
        <?php if (!empty($icono)) : ?>
        <div class="fgc-icon">
            <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
        </div>
        <?php endif; ?>

        <div class="fgc-text">
            <h2 class="fgc-title"><?php echo esc_html($titulo); ?></h2>
            <?php if (!empty($descripcion)) : ?>
                <p class="fgc-description"><?php echo esc_html($descripcion); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($btn_texto) && !empty($btn_url)) : ?>
        <div class="fgc-action">
            <a href="<?php echo esc_url($btn_url); ?>" class="fgc-btn">
                <?php echo esc_html($btn_texto); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-generic-cta {
    padding: 40px 20px;
}

.fgc-content {
    max-width: 1000px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}

.fgc--simple .fgc-content {
    padding: 24px;
    background: #f9fafb;
    border-radius: 12px;
}

.fgc--destacado .fgc-content {
    padding: 48px;
    background: linear-gradient(135deg, var(--fgc-primary), color-mix(in srgb, var(--fgc-primary) 70%, #000));
    border-radius: 16px;
    color: white;
    text-align: center;
    flex-direction: column;
}

.fgc--banner .fgc-content {
    padding: 32px 40px;
    background: var(--fgc-bg, var(--fgc-primary));
    border-radius: 12px;
    justify-content: space-between;
    color: white;
}

.fgc-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    background: rgba(255,255,255,0.2);
    border-radius: 12px;
    flex-shrink: 0;
}

.fgc-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.fgc--simple .fgc-icon {
    background: var(--fgc-primary);
    color: white;
}

.fgc-text {
    flex: 1;
}

.fgc-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0 0 8px;
}

.fgc--simple .fgc-title {
    color: #111827;
}

.fgc-description {
    font-size: 1rem;
    margin: 0;
    opacity: 0.9;
}

.fgc--simple .fgc-description {
    color: #6b7280;
}

.fgc-action {
    flex-shrink: 0;
}

.fgc-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.fgc--simple .fgc-btn {
    background: var(--fgc-primary);
    color: white;
}

.fgc--destacado .fgc-btn,
.fgc--banner .fgc-btn {
    background: white;
    color: var(--fgc-primary);
}

.fgc-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.fgc-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

@media (max-width: 768px) {
    .fgc--banner .fgc-content {
        flex-direction: column;
        text-align: center;
    }

    .fgc-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
