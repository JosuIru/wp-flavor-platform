<?php
/**
 * Template: Hero Fullscreen
 *
 * Hero a pantalla completa con soporte para imagen o video de fondo,
 * overlay configurable, texto centrado y boton CTA.
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var string $imagen_fondo
 * @var string $video_fondo
 * @var string $texto_cta
 * @var string $url_cta
 * @var string $overlay_color
 * @var float  $overlay_opacidad
 * @var string $component_classes
 */

$imagen_fondo_url = ! empty( $imagen_fondo ) ? wp_get_attachment_image_url( $imagen_fondo, 'full' ) : '';
$video_fondo_url  = ! empty( $video_fondo ) ? wp_get_attachment_url( $video_fondo ) : '';

$overlay_color_valor    = ! empty( $overlay_color ) ? $overlay_color : '#000000';
$overlay_opacidad_valor = isset( $overlay_opacidad ) ? floatval( $overlay_opacidad ) : 0.5;

$tiene_fondo_multimedia = ! empty( $imagen_fondo_url ) || ! empty( $video_fondo_url );
?>

<section class="relative min-h-screen flex items-center justify-center overflow-hidden <?php echo esc_attr( $component_classes ?? '' ); ?>">

    <!-- Fondo: video o imagen -->
    <?php if ( ! empty( $video_fondo_url ) ) : ?>
        <video
            class="absolute inset-0 w-full h-full object-cover z-0"
            autoplay
            muted
            loop
            playsinline
            preload="auto"
        >
            <source src="<?php echo esc_url( $video_fondo_url ); ?>" type="video/mp4">
        </video>
    <?php elseif ( ! empty( $imagen_fondo_url ) ) : ?>
        <div class="absolute inset-0 z-0">
            <img
                src="<?php echo esc_url( $imagen_fondo_url ); ?>"
                alt=""
                class="w-full h-full object-cover"
                loading="eager"
            >
        </div>
    <?php endif; ?>

    <!-- Overlay -->
    <?php if ( $tiene_fondo_multimedia ) : ?>
        <div
            class="absolute inset-0 z-10"
            style="background-color: <?php echo esc_attr( $overlay_color_valor ); ?>; opacity: <?php echo esc_attr( $overlay_opacidad_valor ); ?>;"
        ></div>
    <?php else : ?>
        <!-- Sin multimedia: gradiente por defecto usando variables de tema -->
        <div
            class="absolute inset-0 z-10"
            style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary, var(--flavor-primary)) 100%);"
        ></div>
    <?php endif; ?>

    <!-- Contenido centrado -->
    <div class="relative z-20 container mx-auto px-4 py-20 text-center">
        <div class="max-w-4xl mx-auto">

            <!-- Titulo -->
            <?php if ( ! empty( $titulo ) ) : ?>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight mb-6" style="color: #ffffff;">
                    <?php echo esc_html( $titulo ); ?>
                </h1>
            <?php endif; ?>

            <!-- Subtitulo -->
            <?php if ( ! empty( $subtitulo ) ) : ?>
                <p class="text-lg md:text-xl lg:text-2xl leading-relaxed mb-10" style="color: rgba(255, 255, 255, 0.9);">
                    <?php echo esc_html( $subtitulo ); ?>
                </p>
            <?php endif; ?>

            <!-- Boton CTA -->
            <?php if ( ! empty( $texto_cta ) && ! empty( $url_cta ) ) : ?>
                <div class="mt-8">
                    <a
                        href="<?php echo esc_url( $url_cta ); ?>"
                        class="inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                        style="background-color: var(--flavor-primary); color: #ffffff;"
                    >
                        <?php echo esc_html( $texto_cta ); ?>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Indicador de scroll -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 z-20 animate-bounce">
        <svg class="w-6 h-6" style="color: rgba(255, 255, 255, 0.7);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
        </svg>
    </div>

</section>
