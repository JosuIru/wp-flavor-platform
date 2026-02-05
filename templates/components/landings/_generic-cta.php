<?php
/**
 * Template genérico para componentes CTA
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo = $titulo ?? 'Llamada a la acción';
$subtitulo = $subtitulo ?? $descripcion ?? '';
$texto_boton = $texto_boton ?? $boton_texto ?? 'Comenzar';
$url_boton = $url_boton ?? $boton_url ?? '#';
$color_fondo = $color_fondo ?? $color_primario ?? '#3b82f6';
$id_seccion = $id_seccion ?? '';
?>

<section<?php if ($id_seccion): ?> id="<?php echo esc_attr($id_seccion); ?>"<?php endif; ?> class="flavor-cta" style="background: <?php echo esc_attr($color_fondo); ?>;">
    <div class="flavor-cta-content">
        <h2 class="flavor-cta-title"><?php echo esc_html($titulo); ?></h2>
        <?php if ($subtitulo): ?>
            <p class="flavor-cta-subtitle"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>
        <a href="<?php echo esc_url($url_boton); ?>" class="flavor-cta-btn">
            <?php echo esc_html($texto_boton); ?>
        </a>
    </div>
</section>

<style>
.flavor-cta {
    padding: 4rem 2rem;
    text-align: center;
    color: white;
}
.flavor-cta-content {
    max-width: 600px;
    margin: 0 auto;
}
.flavor-cta-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem;
}
.flavor-cta-subtitle {
    font-size: 1.125rem;
    opacity: 0.9;
    margin: 0 0 2rem;
}
.flavor-cta-btn {
    display: inline-block;
    padding: 1rem 2.5rem;
    background: white;
    color: #333;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 1rem;
    transition: transform 0.2s, box-shadow 0.2s;
}
.flavor-cta-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>
