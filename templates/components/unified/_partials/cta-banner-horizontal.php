<?php
/**
 * CTA Partial: Banner Horizontal
 *
 * Full-width banner with text on the left and button(s) on the right,
 * using a colored background.
 *
 * Available variables:
 * @var string $titulo                  Main heading text
 * @var string $descripcion             Supporting description text
 * @var string $texto_boton             Primary button label
 * @var string $url_boton               Primary button URL
 * @var string $color_primario          Primary accent color (hex)
 * @var string $color_fondo             Background color (hex)
 * @var string $imagen_url              Optional background or decorative image URL
 * @var string $icono                   Optional icon class or SVG
 * @var string $texto_boton_secundario  Secondary button label
 * @var string $url_boton_secundario    Secondary button URL
 * @var string $posicion                Layout position hint
 *
 * @package FlavorChatIA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$titulo_sanitizado                = ! empty( $titulo ) ? $titulo : '';
$descripcion_sanitizada           = ! empty( $descripcion ) ? $descripcion : '';
$texto_boton_sanitizado           = ! empty( $texto_boton ) ? $texto_boton : '';
$url_boton_sanitizada             = ! empty( $url_boton ) ? $url_boton : '#';
$color_primario_sanitizado        = ! empty( $color_primario ) ? $color_primario : '#2563eb';
$color_fondo_sanitizado           = ! empty( $color_fondo ) ? $color_fondo : '#1e3a5f';
$imagen_url_sanitizada            = ! empty( $imagen_url ) ? $imagen_url : '';
$icono_sanitizado                 = ! empty( $icono ) ? $icono : '';
$texto_boton_secundario_sanitizado = ! empty( $texto_boton_secundario ) ? $texto_boton_secundario : '';
$url_boton_secundario_sanitizada  = ! empty( $url_boton_secundario ) ? $url_boton_secundario : '#';
?>

<section
    class="relative w-full overflow-hidden"
    style="background-color: <?php echo esc_attr( $color_fondo_sanitizado ); ?>;"
>
    <?php if ( $imagen_url_sanitizada ) : ?>
        <div
            class="absolute inset-0 bg-cover bg-center opacity-20"
            style="background-image: url('<?php echo esc_url( $imagen_url_sanitizada ); ?>');"
        ></div>
    <?php endif; ?>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 md:py-14 lg:py-16">
        <div class="flex flex-col md:flex-row items-center justify-between gap-6 md:gap-10">

            <!-- Text Content -->
            <div class="flex-1 text-center md:text-left">
                <?php if ( $icono_sanitizado ) : ?>
                    <span class="inline-block mb-3 text-3xl" style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;">
                        <?php echo $icono_sanitizado; ?>
                    </span>
                <?php endif; ?>

                <?php if ( $titulo_sanitizado ) : ?>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white leading-tight mb-3">
                        <?php echo esc_html( $titulo_sanitizado ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( $descripcion_sanitizada ) : ?>
                    <p class="text-base sm:text-lg text-white/80 max-w-2xl leading-relaxed">
                        <?php echo esc_html( $descripcion_sanitizada ); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row items-center gap-3 shrink-0">
                <?php if ( $texto_boton_sanitizado ) : ?>
                    <a
                        href="<?php echo esc_url( $url_boton_sanitizada ); ?>"
                        class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-base font-semibold text-white shadow-lg transition-all duration-200 hover:opacity-90 hover:shadow-xl hover:-translate-y-0.5"
                        style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                    >
                        <?php echo esc_html( $texto_boton_sanitizado ); ?>
                    </a>
                <?php endif; ?>

                <?php if ( $texto_boton_secundario_sanitizado ) : ?>
                    <a
                        href="<?php echo esc_url( $url_boton_secundario_sanitizada ); ?>"
                        class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-base font-semibold border-2 border-white/30 text-white transition-all duration-200 hover:bg-white/10 hover:border-white/50"
                    >
                        <?php echo esc_html( $texto_boton_secundario_sanitizado ); ?>
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
