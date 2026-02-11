<?php
/**
 * Template de sección Hero
 *
 * @package FlavorChatIA
 * @var array $data Datos de la sección
 * @var string $variant Variante de la sección
 * @var string $section_id ID de la sección
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer datos con valores por defecto
$titulo = $data['titulo'] ?? '';
$subtitulo = $data['subtitulo'] ?? '';
$imagen = $data['imagen'] ?? '';
$video_url = $data['video_url'] ?? '';
$cta_texto = $data['cta_texto'] ?? '';
$cta_url = $data['cta_url'] ?? '#';
$cta_secundario_texto = $data['cta_secundario_texto'] ?? '';
$cta_secundario_url = $data['cta_secundario_url'] ?? '';
$color_fondo = $data['color_fondo'] ?? '#f8fafc';
$color_texto = $data['color_texto'] ?? '#1e293b';

// Estilos inline
$section_style = sprintf(
    'background-color: %s; color: %s;',
    esc_attr($color_fondo),
    esc_attr($color_texto)
);

// Si es variante con imagen de fondo
if ($variant === 'con_imagen_fondo' && $imagen) {
    $section_style .= sprintf(' background-image: url(%s); background-size: cover; background-position: center;', esc_url($imagen));
}

$class_variant = 'flavor-hero--' . esc_attr($variant);
?>

<div class="flavor-hero <?php echo $class_variant; ?>" style="<?php echo esc_attr($section_style); ?>">
    <?php if ($variant === 'split' && $imagen): ?>
    <div class="flavor-hero__content">
    <?php endif; ?>

    <div class="flavor-hero__text">
        <?php if ($titulo): ?>
            <h1 class="flavor-hero__title"><?php echo esc_html($titulo); ?></h1>
        <?php endif; ?>

        <?php if ($subtitulo): ?>
            <p class="flavor-hero__subtitle"><?php echo esc_html($subtitulo); ?></p>
        <?php endif; ?>

        <?php if ($cta_texto): ?>
            <div class="flavor-hero__buttons">
                <a href="<?php echo esc_url($cta_url); ?>" class="flavor-btn flavor-btn--primary">
                    <?php echo esc_html($cta_texto); ?>
                </a>
                <?php if ($cta_secundario_texto): ?>
                    <a href="<?php echo esc_url($cta_secundario_url); ?>" class="flavor-btn flavor-btn--secondary">
                        <?php echo esc_html($cta_secundario_texto); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($variant === 'con_video' && $video_url): ?>
        <div class="flavor-hero__video">
            <?php
            // Obtener embed URL
            $embed_url = '';
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]+)/', $video_url, $matches)) {
                $embed_url = 'https://www.youtube.com/embed/' . $matches[1];
            } elseif (preg_match('/vimeo\.com\/(?:video\/)?(\d+)/', $video_url, $matches)) {
                $embed_url = 'https://player.vimeo.com/video/' . $matches[1];
            }

            if ($embed_url): ?>
                <div class="flavor-video__player">
                    <iframe src="<?php echo esc_url($embed_url); ?>" frameborder="0" allowfullscreen loading="lazy"></iframe>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($imagen && $variant !== 'con_imagen_fondo'): ?>
        <div class="flavor-hero__image">
            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($titulo); ?>" loading="lazy">
        </div>
    <?php endif; ?>

    <?php if ($variant === 'split' && $imagen): ?>
    </div>
    <?php endif; ?>
</div>
