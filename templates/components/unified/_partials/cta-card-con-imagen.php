<?php
/**
 * CTA Partial: Card con Imagen
 *
 * Card layout with image on one side and CTA text + button on the other side.
 *
 * Available variables:
 * @var string $titulo                  Main heading text
 * @var string $descripcion             Supporting description text
 * @var string $texto_boton             Primary button label
 * @var string $url_boton               Primary button URL
 * @var string $color_primario          Primary accent color (hex)
 * @var string $color_fondo             Background color (hex)
 * @var string $imagen_url              Image URL displayed on one side
 * @var string $icono                   Optional icon class or SVG
 * @var string $texto_boton_secundario  Secondary button label
 * @var string $url_boton_secundario    Secondary button URL
 * @var string $posicion                Image position: 'izquierda' or 'derecha'
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
$color_fondo_sanitizado           = ! empty( $color_fondo ) ? $color_fondo : '#ffffff';
$imagen_url_sanitizada            = ! empty( $imagen_url ) ? $imagen_url : '';
$icono_sanitizado                 = ! empty( $icono ) ? $icono : '';
$texto_boton_secundario_sanitizado = ! empty( $texto_boton_secundario ) ? $texto_boton_secundario : '';
$url_boton_secundario_sanitizada  = ! empty( $url_boton_secundario ) ? $url_boton_secundario : '#';
$posicion_sanitizada              = ! empty( $posicion ) ? $posicion : 'derecha';

$imagen_a_la_izquierda = ( $posicion_sanitizada === 'izquierda' );
?>

<section class="w-full py-8 md:py-12 lg:py-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <div
            class="rounded-2xl shadow-xl overflow-hidden flex flex-col <?php echo $imagen_a_la_izquierda ? 'md:flex-row' : 'md:flex-row-reverse'; ?>"
            style="background-color: <?php echo esc_attr( $color_fondo_sanitizado ); ?>;"
        >

            <!-- Image Side -->
            <?php if ( $imagen_url_sanitizada ) : ?>
                <div class="md:w-1/2 relative min-h-[250px] sm:min-h-[300px] md:min-h-[400px]">
                    <img
                        src="<?php echo esc_url( $imagen_url_sanitizada ); ?>"
                        alt="<?php echo esc_attr( $titulo_sanitizado ); ?>"
                        class="absolute inset-0 w-full h-full object-cover"
                        loading="lazy"
                    />
                </div>
            <?php endif; ?>

            <!-- Content Side -->
            <div class="md:w-1/2 flex flex-col justify-center p-8 sm:p-10 lg:p-14">

                <?php if ( $icono_sanitizado ) : ?>
                    <span
                        class="inline-flex items-center justify-center w-12 h-12 rounded-lg text-2xl mb-5"
                        style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>15; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                    >
                        <?php echo $icono_sanitizado; ?>
                    </span>
                <?php endif; ?>

                <?php if ( $titulo_sanitizado ) : ?>
                    <h2 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 leading-tight mb-4">
                        <?php echo esc_html( $titulo_sanitizado ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( $descripcion_sanitizada ) : ?>
                    <p class="text-base sm:text-lg text-gray-600 leading-relaxed mb-8">
                        <?php echo esc_html( $descripcion_sanitizada ); ?>
                    </p>
                <?php endif; ?>

                <div class="flex flex-col sm:flex-row items-start gap-3">
                    <?php if ( $texto_boton_sanitizado ) : ?>
                        <a
                            href="<?php echo esc_url( $url_boton_sanitizada ); ?>"
                            class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-base font-semibold text-white shadow-md transition-all duration-200 hover:opacity-90 hover:shadow-lg hover:-translate-y-0.5"
                            style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        >
                            <?php echo esc_html( $texto_boton_sanitizado ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $texto_boton_secundario_sanitizado ) : ?>
                        <a
                            href="<?php echo esc_url( $url_boton_secundario_sanitizada ); ?>"
                            class="inline-flex items-center justify-center px-7 py-3.5 rounded-lg text-base font-semibold transition-all duration-200 hover:opacity-80"
                            style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        >
                            <?php echo esc_html( $texto_boton_secundario_sanitizado ); ?>
                            <svg class="w-4 h-4 ml-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>
</section>
