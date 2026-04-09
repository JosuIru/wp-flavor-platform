<?php
/**
 * Template Genérico: Hero Section
 *
 * Variables disponibles:
 * - $titulo (string): Título principal
 * - $subtitulo (string): Subtítulo
 * - $descripcion (string): Descripción
 * - $imagen_fondo (string): URL de imagen de fondo
 * - $color_primario (string): Color primario
 * - $color_fondo (string): Color de fondo
 * - $btn_texto (string): Texto del botón principal
 * - $btn_url (string): URL del botón principal
 * - $btn_secundario_texto (string): Texto botón secundario
 * - $btn_secundario_url (string): URL botón secundario
 * - $variante (string): centrado, izquierda, dividido
 * - $altura (string): pequeño, mediano, grande, completo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$titulo = $titulo ?? __('Bienvenido', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo = $subtitulo ?? '';
$descripcion = $descripcion ?? '';
$imagen_fondo = $imagen_fondo ?? '';
$color_primario = $color_primario ?? '#4f46e5';
$color_fondo = $color_fondo ?? '#f8fafc';
$btn_texto = $btn_texto ?? '';
$btn_url = $btn_url ?? '';
$btn_secundario_texto = $btn_secundario_texto ?? '';
$btn_secundario_url = $btn_secundario_url ?? '';
$variante = $variante ?? 'centrado';
$altura = $altura ?? 'mediano';

// Generar ID único
$unique_id = 'fgh-' . wp_unique_id();

// Determinar estilos
$altura_map = [
    'pequeño' => '40vh',
    'mediano' => '60vh',
    'grande' => '80vh',
    'completo' => '100vh',
];
$min_height = $altura_map[$altura] ?? '60vh';
?>

<section class="flavor-generic-hero <?php echo esc_attr($unique_id); ?> fgh--<?php echo esc_attr($variante); ?>"
         style="--fgh-primary: <?php echo esc_attr($color_primario); ?>;
                --fgh-bg: <?php echo esc_attr($color_fondo); ?>;
                --fgh-min-height: <?php echo esc_attr($min_height); ?>;
                <?php if (!empty($imagen_fondo)) : ?>
                background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url(<?php echo esc_url($imagen_fondo); ?>);
                background-size: cover;
                background-position: center;
                <?php endif; ?>">

    <div class="fgh-content">
        <?php if (!empty($subtitulo)) : ?>
            <span class="fgh-badge"><?php echo esc_html($subtitulo); ?></span>
        <?php endif; ?>

        <h1 class="fgh-title"><?php echo esc_html($titulo); ?></h1>

        <?php if (!empty($descripcion)) : ?>
            <p class="fgh-description"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <?php if (!empty($btn_texto) || !empty($btn_secundario_texto)) : ?>
        <div class="fgh-actions">
            <?php if (!empty($btn_texto) && !empty($btn_url)) : ?>
                <a href="<?php echo esc_url($btn_url); ?>" class="fgh-btn fgh-btn--primary">
                    <?php echo esc_html($btn_texto); ?>
                </a>
            <?php endif; ?>

            <?php if (!empty($btn_secundario_texto) && !empty($btn_secundario_url)) : ?>
                <a href="<?php echo esc_url($btn_secundario_url); ?>" class="fgh-btn fgh-btn--secondary">
                    <?php echo esc_html($btn_secundario_texto); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<style>
.flavor-generic-hero {
    min-height: var(--fgh-min-height, 60vh);
    background-color: var(--fgh-bg, #f8fafc);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    position: relative;
}

.flavor-generic-hero[style*="background-image"] {
    color: white;
}

.fgh-content {
    max-width: 800px;
    width: 100%;
}

.fgh--centrado .fgh-content {
    text-align: center;
}

.fgh--izquierda .fgh-content {
    text-align: left;
    max-width: 600px;
    margin-right: auto;
}

.fgh-badge {
    display: inline-block;
    padding: 6px 16px;
    background: var(--fgh-primary);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 20px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.fgh-title {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: 800;
    line-height: 1.2;
    margin: 0 0 20px;
    color: inherit;
}

.flavor-generic-hero:not([style*="background-image"]) .fgh-title {
    color: #111827;
}

.fgh-description {
    font-size: 1.25rem;
    line-height: 1.6;
    margin: 0 0 32px;
    opacity: 0.9;
}

.flavor-generic-hero:not([style*="background-image"]) .fgh-description {
    color: #6b7280;
}

.fgh-actions {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}

.fgh--centrado .fgh-actions {
    justify-content: center;
}

.fgh-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 16px 32px;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.fgh-btn--primary {
    background: var(--fgh-primary);
    color: white;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

.fgh-btn--primary:hover {
    background: color-mix(in srgb, var(--fgh-primary) 85%, black);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.fgh-btn--secondary {
    background: transparent;
    border: 2px solid currentColor;
}

.flavor-generic-hero:not([style*="background-image"]) .fgh-btn--secondary {
    border-color: #d1d5db;
    color: #374151;
}

.fgh-btn--secondary:hover {
    background: rgba(0,0,0,0.05);
}

@media (max-width: 640px) {
    .flavor-generic-hero {
        padding: 40px 16px;
    }

    .fgh-actions {
        flex-direction: column;
    }

    .fgh-btn {
        width: 100%;
    }
}
</style>
