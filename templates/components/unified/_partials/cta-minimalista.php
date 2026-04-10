<?php
/**
 * CTA Partial: Minimalista
 *
 * Simple inline text with link/button, no background color.
 * Clean and understated design.
 *
 * Available variables:
 * @var string $titulo                  Main heading text
 * @var string $descripcion             Supporting description text
 * @var string $texto_boton             Primary button/link label
 * @var string $url_boton               Primary button/link URL
 * @var string $color_primario          Primary accent color (hex)
 * @var string $color_fondo             Background color (hex) - unused in this variant
 * @var string $imagen_url              Optional image URL
 * @var string $icono                   Optional icon class or SVG
 * @var string $texto_boton_secundario  Secondary button/link label
 * @var string $url_boton_secundario    Secondary button/link URL
 * @var string $posicion                Layout position hint
 *
 * @package FlavorPlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$titulo_sanitizado                = ! empty( $titulo ) ? $titulo : '';
$descripcion_sanitizada           = ! empty( $descripcion ) ? $descripcion : '';
$texto_boton_sanitizado           = ! empty( $texto_boton ) ? $texto_boton : '';
$url_boton_sanitizada             = ! empty( $url_boton ) ? $url_boton : '#';
$color_primario_sanitizado        = ! empty( $color_primario ) ? $color_primario : '#2563eb';
$icono_sanitizado                 = ! empty( $icono ) ? $icono : '';
$texto_boton_secundario_sanitizado = ! empty( $texto_boton_secundario ) ? $texto_boton_secundario : '';
$url_boton_secundario_sanitizada  = ! empty( $url_boton_secundario ) ? $url_boton_secundario : '#';
?>

<div class="w-full py-6 md:py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex flex-col sm:flex-row items-center sm:items-baseline gap-3 sm:gap-4 text-center sm:text-left">

            <?php if ( $icono_sanitizado ) : ?>
                <span class="text-xl shrink-0" style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;">
                    <?php echo $icono_sanitizado; ?>
                </span>
            <?php endif; ?>

            <?php if ( $titulo_sanitizado ) : ?>
                <p class="text-base sm:text-lg font-medium text-gray-800">
                    <?php echo esc_html( $titulo_sanitizado ); ?>
                </p>
            <?php endif; ?>

            <?php if ( $descripcion_sanitizada ) : ?>
                <p class="text-sm sm:text-base text-gray-500">
                    <?php echo esc_html( $descripcion_sanitizada ); ?>
                </p>
            <?php endif; ?>

            <div class="flex items-center gap-4 shrink-0">
                <?php if ( $texto_boton_sanitizado ) : ?>
                    <a
                        href="<?php echo esc_url( $url_boton_sanitizada ); ?>"
                        class="inline-flex items-center text-base font-semibold transition-all duration-200 hover:opacity-80 group"
                        style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                    >
                        <?php echo esc_html( $texto_boton_sanitizado ); ?>
                        <svg class="w-4 h-4 ml-1.5 transition-transform duration-200 group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                <?php endif; ?>

                <?php if ( $texto_boton_secundario_sanitizado ) : ?>
                    <span class="text-gray-300">|</span>
                    <a
                        href="<?php echo esc_url( $url_boton_secundario_sanitizada ); ?>"
                        class="inline-flex items-center text-sm font-medium text-gray-500 transition-colors duration-200 hover:text-gray-700"
                    >
                        <?php echo esc_html( $texto_boton_secundario_sanitizado ); ?>
                    </a>
                <?php endif; ?>
            </div>

        </div>

    </div>
</div>
