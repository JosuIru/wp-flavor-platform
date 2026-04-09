<?php
/**
 * Template: Hero Slider
 *
 * Carrusel de slides hero con navegacion por bullets.
 * Cada slide tiene imagen de fondo, overlay y texto superpuesto.
 * Usa scroll horizontal nativo con JavaScript ligero para la navegacion.
 *
 * @var array  $slides     Array de slides, cada uno con: titulo, subtitulo, imagen, url_cta, texto_cta
 * @var bool   $autoplay
 * @var int    $intervalo   Intervalo en milisegundos para el autoplay
 * @var string $component_classes
 */

$slides_datos            = ! empty( $slides ) && is_array( $slides ) ? $slides : [];
$autoplay_activo         = ! empty( $autoplay );
$intervalo_milisegundos  = ! empty( $intervalo ) ? intval( $intervalo ) : 5000;
$identificador_slider    = 'hero-slider-' . wp_unique_id();
$total_slides            = count( $slides_datos );

if ( $total_slides === 0 ) {
    return;
}
?>

<section class="relative overflow-hidden <?php echo esc_attr( $component_classes ?? '' ); ?>" id="<?php echo esc_attr( $identificador_slider ); ?>">

    <!-- Contenedor de slides con scroll horizontal -->
    <div
        class="flex overflow-x-hidden scroll-smooth min-h-screen"
        data-slider-track="<?php echo esc_attr( $identificador_slider ); ?>"
        style="scroll-snap-type: x mandatory;"
    >

        <?php foreach ( $slides_datos as $indice_slide => $slide_individual ) :
            $imagen_slide_id  = $slide_individual['imagen'] ?? '';
            $imagen_slide_url = ! empty( $imagen_slide_id ) ? wp_get_attachment_image_url( $imagen_slide_id, 'full' ) : '';
            $titulo_slide     = $slide_individual['titulo'] ?? '';
            $subtitulo_slide  = $slide_individual['subtitulo'] ?? '';
            $texto_cta_slide  = $slide_individual['texto_cta'] ?? '';
            $url_cta_slide    = $slide_individual['url_cta'] ?? '';
        ?>
            <div
                class="relative flex-shrink-0 w-full min-h-screen flex items-center justify-center"
                data-slide-index="<?php echo esc_attr( $indice_slide ); ?>"
                style="scroll-snap-align: start;"
            >

                <!-- Imagen de fondo del slide -->
                <?php if ( ! empty( $imagen_slide_url ) ) : ?>
                    <div class="absolute inset-0 z-0">
                        <img
                            src="<?php echo esc_url( $imagen_slide_url ); ?>"
                            alt=""
                            class="w-full h-full object-cover"
                            loading="<?php echo $indice_slide === 0 ? 'eager' : 'lazy'; ?>"
                        >
                    </div>
                <?php endif; ?>

                <!-- Overlay oscuro -->
                <div class="absolute inset-0 z-10 bg-black" style="opacity: 0.5;"></div>

                <!-- Contenido del slide -->
                <div class="relative z-20 container mx-auto px-4 py-20 text-center">
                    <div class="max-w-4xl mx-auto">

                        <!-- Titulo del slide -->
                        <?php if ( ! empty( $titulo_slide ) ) : ?>
                            <h2 class="text-3xl md:text-4xl lg:text-6xl font-bold leading-tight mb-6" style="color: #ffffff;">
                                <?php echo esc_html( $titulo_slide ); ?>
                            </h2>
                        <?php endif; ?>

                        <!-- Subtitulo del slide -->
                        <?php if ( ! empty( $subtitulo_slide ) ) : ?>
                            <p class="text-lg md:text-xl lg:text-2xl leading-relaxed mb-10" style="color: rgba(255, 255, 255, 0.9);">
                                <?php echo esc_html( $subtitulo_slide ); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Boton CTA del slide -->
                        <?php if ( ! empty( $texto_cta_slide ) && ! empty( $url_cta_slide ) ) : ?>
                            <a
                                href="<?php echo esc_url( $url_cta_slide ); ?>"
                                class="inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                                style="background-color: var(--flavor-primary); color: #ffffff;"
                            >
                                <?php echo esc_html( $texto_cta_slide ); ?>
                                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        <?php endforeach; ?>

    </div>

    <!-- Flechas de navegacion -->
    <?php if ( $total_slides > 1 ) : ?>
        <button
            type="button"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-30 w-12 h-12 rounded-full flex items-center justify-center transition duration-300 hover:scale-110"
            style="background-color: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px);"
            data-slider-prev="<?php echo esc_attr( $identificador_slider ); ?>"
            aria-label="<?php esc_attr_e( 'Slide anterior', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
        >
            <svg class="w-6 h-6" style="color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </button>

        <button
            type="button"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-30 w-12 h-12 rounded-full flex items-center justify-center transition duration-300 hover:scale-110"
            style="background-color: rgba(255, 255, 255, 0.2); backdrop-filter: blur(4px);"
            data-slider-next="<?php echo esc_attr( $identificador_slider ); ?>"
            aria-label="<?php esc_attr_e( 'Slide siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
        >
            <svg class="w-6 h-6" style="color: #ffffff;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
        </button>
    <?php endif; ?>

    <!-- Bullets de navegacion -->
    <?php if ( $total_slides > 1 ) : ?>
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 z-30 flex items-center space-x-3">
            <?php for ( $indice_bullet = 0; $indice_bullet < $total_slides; $indice_bullet++ ) : ?>
                <button
                    type="button"
                    class="w-3 h-3 rounded-full transition duration-300 hover:scale-125"
                    style="background-color: <?php echo $indice_bullet === 0 ? 'var(--flavor-primary)' : 'rgba(255, 255, 255, 0.5)'; ?>;"
                    data-slider-bullet="<?php echo esc_attr( $identificador_slider ); ?>"
                    data-slide-target="<?php echo esc_attr( $indice_bullet ); ?>"
                    aria-label="<?php echo esc_attr( sprintf( __( 'Ir al slide %d', FLAVOR_PLATFORM_TEXT_DOMAIN ), $indice_bullet + 1 ) ); ?>"
                ></button>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

</section>

<?php if ( $total_slides > 1 ) : ?>
<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        var sliderId           = '<?php echo esc_js( $identificador_slider ); ?>';
        var contenedorSlider   = document.getElementById(sliderId);
        if (!contenedorSlider) return;

        var pistaSlides        = contenedorSlider.querySelector('[data-slider-track="' + sliderId + '"]');
        var botonesVinetas     = contenedorSlider.querySelectorAll('[data-slider-bullet="' + sliderId + '"]');
        var botonAnterior      = contenedorSlider.querySelector('[data-slider-prev="' + sliderId + '"]');
        var botonSiguiente     = contenedorSlider.querySelector('[data-slider-next="' + sliderId + '"]');
        var indiceSlideActual  = 0;
        var totalSlidesJs      = <?php echo intval( $total_slides ); ?>;
        var autoplayActivo     = <?php echo $autoplay_activo ? 'true' : 'false'; ?>;
        var intervaloAutoplay  = <?php echo intval( $intervalo_milisegundos ); ?>;
        var temporizadorAuto   = null;

        function navegarAlSlide(indiceDestino) {
            if (indiceDestino < 0) indiceDestino = totalSlidesJs - 1;
            if (indiceDestino >= totalSlidesJs) indiceDestino = 0;

            var anchoSlide = pistaSlides.offsetWidth;
            pistaSlides.scrollTo({
                left: indiceDestino * anchoSlide,
                behavior: 'smooth'
            });

            indiceSlideActual = indiceDestino;
            actualizarEstadoVinetas();
        }

        function actualizarEstadoVinetas() {
            botonesVinetas.forEach(function(botonVineta, indiceBullet) {
                if (indiceBullet === indiceSlideActual) {
                    botonVineta.style.backgroundColor = 'var(--flavor-primary)';
                    botonVineta.style.transform = 'scale(1.25)';
                } else {
                    botonVineta.style.backgroundColor = 'rgba(255, 255, 255, 0.5)';
                    botonVineta.style.transform = 'scale(1)';
                }
            });
        }

        function iniciarAutoplay() {
            if (!autoplayActivo) return;
            detenerAutoplay();
            temporizadorAuto = setInterval(function() {
                navegarAlSlide(indiceSlideActual + 1);
            }, intervaloAutoplay);
        }

        function detenerAutoplay() {
            if (temporizadorAuto) {
                clearInterval(temporizadorAuto);
                temporizadorAuto = null;
            }
        }

        // Eventos de los bullets
        botonesVinetas.forEach(function(botonVineta) {
            botonVineta.addEventListener('click', function() {
                var indiceObjetivo = parseInt(this.getAttribute('data-slide-target'), 10);
                navegarAlSlide(indiceObjetivo);
                detenerAutoplay();
                iniciarAutoplay();
            });
        });

        // Eventos de las flechas
        if (botonAnterior) {
            botonAnterior.addEventListener('click', function() {
                navegarAlSlide(indiceSlideActual - 1);
                detenerAutoplay();
                iniciarAutoplay();
            });
        }

        if (botonSiguiente) {
            botonSiguiente.addEventListener('click', function() {
                navegarAlSlide(indiceSlideActual + 1);
                detenerAutoplay();
                iniciarAutoplay();
            });
        }

        // Pausar autoplay al hacer hover
        contenedorSlider.addEventListener('mouseenter', detenerAutoplay);
        contenedorSlider.addEventListener('mouseleave', iniciarAutoplay);

        // Iniciar autoplay
        iniciarAutoplay();
    });
})();
</script>
<?php endif; ?>
