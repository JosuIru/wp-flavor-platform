<?php
/**
 * CTA Partial: Flotante
 *
 * Fixed/sticky floating CTA bar at the bottom of the viewport.
 *
 * Available variables:
 * @var string $titulo                  Main heading text
 * @var string $descripcion             Supporting description text
 * @var string $texto_boton             Primary button label
 * @var string $url_boton               Primary button URL
 * @var string $color_primario          Primary accent color (hex)
 * @var string $color_fondo             Background color (hex)
 * @var string $imagen_url              Optional small image/icon URL
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
$color_fondo_sanitizado           = ! empty( $color_fondo ) ? $color_fondo : '#ffffff';
$imagen_url_sanitizada            = ! empty( $imagen_url ) ? $imagen_url : '';
$icono_sanitizado                 = ! empty( $icono ) ? $icono : '';
$texto_boton_secundario_sanitizado = ! empty( $texto_boton_secundario ) ? $texto_boton_secundario : '';
$url_boton_secundario_sanitizada  = ! empty( $url_boton_secundario ) ? $url_boton_secundario : '#';

$identificador_unico = 'cta-flotante-' . wp_unique_id();
?>

<div
    id="<?php echo esc_attr( $identificador_unico ); ?>"
    class="fixed bottom-0 left-0 right-0 z-50 transform translate-y-full transition-transform duration-500 ease-out"
    style="background-color: <?php echo esc_attr( $color_fondo_sanitizado ); ?>;"
    data-cta-flotante
>
    <div class="border-t border-gray-200 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3 sm:py-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-3 sm:gap-6">

                <!-- Left: Text Content -->
                <div class="flex items-center gap-3 flex-1 min-w-0 text-center sm:text-left">
                    <?php if ( $icono_sanitizado ) : ?>
                        <span
                            class="hidden sm:inline-flex items-center justify-center w-10 h-10 rounded-full shrink-0 text-xl"
                            style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>15; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        >
                            <?php echo $icono_sanitizado; ?>
                        </span>
                    <?php endif; ?>

                    <div class="min-w-0">
                        <?php if ( $titulo_sanitizado ) : ?>
                            <p class="text-sm sm:text-base font-semibold text-gray-900 truncate">
                                <?php echo esc_html( $titulo_sanitizado ); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ( $descripcion_sanitizada ) : ?>
                            <p class="hidden md:block text-sm text-gray-500 truncate">
                                <?php echo esc_html( $descripcion_sanitizada ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Buttons -->
                <div class="flex items-center gap-3 shrink-0">
                    <?php if ( $texto_boton_secundario_sanitizado ) : ?>
                        <a
                            href="<?php echo esc_url( $url_boton_secundario_sanitizada ); ?>"
                            class="hidden sm:inline-flex items-center justify-center px-5 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 hover:opacity-80"
                            style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        >
                            <?php echo esc_html( $texto_boton_secundario_sanitizado ); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ( $texto_boton_sanitizado ) : ?>
                        <a
                            href="<?php echo esc_url( $url_boton_sanitizada ); ?>"
                            class="inline-flex items-center justify-center px-6 py-2.5 rounded-lg text-sm font-semibold text-white shadow-md transition-all duration-200 hover:opacity-90 hover:shadow-lg"
                            style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        >
                            <?php echo esc_html( $texto_boton_sanitizado ); ?>
                        </a>
                    <?php endif; ?>

                    <!-- Close Button -->
                    <button
                        type="button"
                        class="inline-flex items-center justify-center w-8 h-8 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors duration-200"
                        onclick="document.getElementById('<?php echo esc_attr( $identificador_unico ); ?>').classList.add('translate-y-full'); document.getElementById('<?php echo esc_attr( $identificador_unico ); ?>').classList.remove('translate-y-0');"
                        aria-label="<?php esc_attr_e( 'Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                    >
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var elementoFlotante = document.getElementById('<?php echo esc_js( $identificador_unico ); ?>');
    if ( ! elementoFlotante ) return;

    var seHaMostrado = false;

    function mostrarCtaFlotante() {
        if ( seHaMostrado ) return;

        var desplazamientoVertical = window.scrollY || window.pageYOffset;
        var alturaDocumento = document.documentElement.scrollHeight;
        var alturaVentana = window.innerHeight;
        var porcentajeDesplazamiento = ( desplazamientoVertical / ( alturaDocumento - alturaVentana ) ) * 100;

        if ( porcentajeDesplazamiento > 25 ) {
            elementoFlotante.classList.remove('translate-y-full');
            elementoFlotante.classList.add('translate-y-0');
            seHaMostrado = true;
        }
    }

    window.addEventListener('scroll', mostrarCtaFlotante, { passive: true });

    // Also show after 5 seconds as fallback
    setTimeout(function() {
        if ( ! seHaMostrado ) {
            elementoFlotante.classList.remove('translate-y-full');
            elementoFlotante.classList.add('translate-y-0');
            seHaMostrado = true;
        }
    }, 5000);
})();
</script>
