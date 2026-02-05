<?php
/**
 * Themacle CTA Banner Component
 *
 * Full-width banner with background image or solid color,
 * centered text content (title, subtitle) and a CTA button.
 *
 * @package FlavorChatIA
 *
 * @var string $titulo           Banner heading text.
 * @var string $subtitulo        Banner subheading text.
 * @var string $texto_cta        CTA button label.
 * @var string $url_cta          CTA button destination URL.
 * @var int    $imagen_fondo     Attachment ID for background image.
 * @var string $color_fondo      Hex or CSS color for background.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$titulo            = isset( $titulo ) ? $titulo : '';
$subtitulo         = isset( $subtitulo ) ? $subtitulo : '';
$texto_cta         = isset( $texto_cta ) ? $texto_cta : '';
$url_cta           = isset( $url_cta ) ? $url_cta : '#';
$imagen_fondo      = isset( $imagen_fondo ) ? absint( $imagen_fondo ) : 0;
$color_fondo       = isset( $color_fondo ) ? $color_fondo : '';
$component_classes = isset( $component_classes ) ? $component_classes : '';

$url_imagen_fondo    = '';
$tiene_imagen_fondo  = false;
$estilos_contenedor  = '';

if ( $imagen_fondo ) {
    $url_imagen_fondo = wp_get_attachment_image_url( $imagen_fondo, 'full' );
    if ( $url_imagen_fondo ) {
        $tiene_imagen_fondo = true;
        $estilos_contenedor = sprintf(
            'background-image: url(%s); background-size: cover; background-position: center; background-repeat: no-repeat;',
            esc_url( $url_imagen_fondo )
        );
    }
}

if ( ! $tiene_imagen_fondo && $color_fondo ) {
    $estilos_contenedor = sprintf( 'background-color: %s;', esc_attr( $color_fondo ) );
}

/**
 * Determine if the background is dark to choose appropriate text and button colors.
 * When a background image is present we assume dark (overlay will darken it).
 * For solid colours we do a simple luminance check.
 */
$fondo_oscuro = false;

if ( $tiene_imagen_fondo ) {
    $fondo_oscuro = true;
} elseif ( $color_fondo ) {
    $hex_limpio = ltrim( $color_fondo, '#' );
    if ( strlen( $hex_limpio ) === 3 ) {
        $hex_limpio = $hex_limpio[0] . $hex_limpio[0]
                    . $hex_limpio[1] . $hex_limpio[1]
                    . $hex_limpio[2] . $hex_limpio[2];
    }
    if ( strlen( $hex_limpio ) === 6 ) {
        $rojo  = hexdec( substr( $hex_limpio, 0, 2 ) );
        $verde = hexdec( substr( $hex_limpio, 2, 2 ) );
        $azul  = hexdec( substr( $hex_limpio, 4, 2 ) );
        $luminancia = ( $rojo * 0.299 + $verde * 0.587 + $azul * 0.114 );
        if ( $luminancia < 128 ) {
            $fondo_oscuro = true;
        }
    }
}

$clases_texto  = $fondo_oscuro ? 'text-white' : 'text-gray-900';
$clases_boton  = $fondo_oscuro
    ? 'bg-white text-gray-900 hover:bg-gray-100'
    : 'text-white hover:opacity-90';
$estilo_boton  = $fondo_oscuro ? '' : 'background-color: var(--flavor-primary, #2563eb);';
?>

<section
    class="flavor-cta-banner relative w-full py-20 <?php echo esc_attr( $component_classes ); ?>"
    style="<?php echo esc_attr( $estilos_contenedor ); ?>"
>
    <?php if ( $tiene_imagen_fondo ) : ?>
        <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <?php endif; ?>

    <div class="relative z-10 mx-auto max-w-4xl px-4 text-center">
        <?php if ( $titulo ) : ?>
            <h2 class="mb-4 text-3xl font-bold leading-tight sm:text-4xl lg:text-5xl <?php echo esc_attr( $clases_texto ); ?>">
                <?php echo esc_html( $titulo ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( $subtitulo ) : ?>
            <p class="mx-auto mb-8 max-w-2xl text-lg sm:text-xl <?php echo esc_attr( $clases_texto ); ?> opacity-90">
                <?php echo esc_html( $subtitulo ); ?>
            </p>
        <?php endif; ?>

        <?php if ( $texto_cta ) : ?>
            <a
                href="<?php echo esc_url( $url_cta ); ?>"
                class="inline-block rounded-full px-8 py-3 text-base font-semibold transition-colors duration-200 <?php echo esc_attr( $clases_boton ); ?>"
                <?php if ( $estilo_boton ) : ?>
                    style="<?php echo esc_attr( $estilo_boton ); ?>"
                <?php endif; ?>
            >
                <?php echo esc_html( $texto_cta ); ?>
            </a>
        <?php endif; ?>
    </div>
</section>
