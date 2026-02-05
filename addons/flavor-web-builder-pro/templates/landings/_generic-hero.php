<?php
/**
 * Template genérico para componentes Hero
 *
 * Variables disponibles: $titulo, $subtitulo, $imagen_fondo, $color_primario, etc.
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? 'Título de la Sección';
$subtitulo = $subtitulo ?? '';
$imagen_fondo = $imagen_fondo ?? $imagen ?? '';
$color_primario = $color_primario ?? '#3b82f6';
$texto_boton = $texto_boton ?? $cta_texto ?? '';
$url_boton = $url_boton ?? $cta_url ?? '#';
?>

<section class="flavor-hero" style="<?php if ($imagen_fondo): ?>background-image: url('<?php echo esc_url($imagen_fondo); ?>'); background-size: cover; background-position: center;<?php endif; ?>">
    <div class="flavor-hero-overlay" style="background: linear-gradient(135deg, <?php echo esc_attr($color_primario); ?>dd 0%, <?php echo esc_attr($color_primario); ?>99 100%);">
        <div class="flavor-hero-content">
            <h1 class="flavor-hero-title"><?php echo esc_html($titulo); ?></h1>
            <?php if ($subtitulo): ?>
                <p class="flavor-hero-subtitle"><?php echo esc_html($subtitulo); ?></p>
            <?php endif; ?>
            <?php if ($texto_boton): ?>
                <a href="<?php echo esc_url($url_boton); ?>" class="flavor-hero-btn">
                    <?php echo esc_html($texto_boton); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.flavor-hero {
    position: relative;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.flavor-hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}
.flavor-hero-content {
    text-align: center;
    color: white;
    max-width: 800px;
}
.flavor-hero-title {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 700;
    margin-bottom: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.2);
}
.flavor-hero-subtitle {
    font-size: clamp(1rem, 2vw, 1.25rem);
    opacity: 0.95;
    margin-bottom: 2rem;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}
.flavor-hero-btn {
    display: inline-block;
    padding: 1rem 2rem;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-hero-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>
