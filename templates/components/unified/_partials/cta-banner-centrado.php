<?php
/**
 * CTA Partial: Banner Centrado
 *
 * Centered banner with title, description, and centered button(s).
 *
 * Available variables:
 * @var string $titulo                  Main heading text
 * @var string $descripcion             Supporting description text
 * @var string $texto_boton             Primary button label
 * @var string $url_boton               Primary button URL
 * @var string $color_primario          Primary accent color (hex)
 * @var string $color_fondo             Background color (hex)
 * @var string $imagen_url              Optional background image URL
 * @var string $icono                   Optional icon class or SVG
 * @var string $texto_boton_secundario  Secondary button label
 * @var string $url_boton_secundario    Secondary button URL
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
$color_fondo_sanitizado           = ! empty( $color_fondo ) ? $color_fondo : '#f0f5ff';
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
            class="absolute inset-0 bg-cover bg-center opacity-15"
            style="background-image: url('<?php echo esc_url( $imagen_url_sanitizada ); ?>');"
        ></div>
    <?php endif; ?>

    <div class="relative max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-14 md:py-20 lg:py-24 text-center">

        <?php if ( $icono_sanitizado ) : ?>
            <div class="flex items-center justify-center mb-5">
                <span
                    class="inline-flex items-center justify-center w-16 h-16 rounded-full text-3xl"
                    style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>20; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                >
                    <?php echo $icono_sanitizado; ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ( $titulo_sanitizado ) : ?>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight mb-4" style="color: #1a202c;">
                <?php echo esc_html( $titulo_sanitizado ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( $descripcion_sanitizada ) : ?>
            <p class="text-lg sm:text-xl text-gray-600 max-w-2xl mx-auto leading-relaxed mb-8">
                <?php echo esc_html( $descripcion_sanitizada ); ?>
            </p>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <?php if ( $texto_boton_sanitizado ) : ?>
                <a
                    href="<?php echo esc_url( $url_boton_sanitizada ); ?>"
                    class="inline-flex items-center justify-center px-8 py-4 rounded-lg text-base font-semibold text-white shadow-lg transition-all duration-200 hover:opacity-90 hover:shadow-xl hover:-translate-y-0.5"
                    style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                >
                    <?php echo esc_html( $texto_boton_sanitizado ); ?>
                </a>
            <?php endif; ?>

            <?php if ( $texto_boton_secundario_sanitizado ) : ?>
                <a
                    href="<?php echo esc_url( $url_boton_secundario_sanitizada ); ?>"
                    class="inline-flex items-center justify-center px-8 py-4 rounded-lg text-base font-semibold border-2 transition-all duration-200 hover:opacity-80"
                    style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>; border-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                >
                    <?php echo esc_html( $texto_boton_secundario_sanitizado ); ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</section>
