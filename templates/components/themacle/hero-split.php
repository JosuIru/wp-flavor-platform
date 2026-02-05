<?php
/**
 * Template: Hero Split
 *
 * Hero con disposicion 50/50, imagen a un lado y texto al otro.
 * Responsive: se apila en movil. Permite invertir el orden con $invertir.
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var string $texto_cta
 * @var string $url_cta
 * @var string $imagen
 * @var bool   $invertir
 * @var string $color_fondo
 * @var string $component_classes
 */

$imagen_url          = ! empty( $imagen ) ? wp_get_attachment_image_url( $imagen, 'full' ) : '';
$color_fondo_valor   = ! empty( $color_fondo ) ? $color_fondo : '#ffffff';
$invertir_layout     = ! empty( $invertir );
$clase_direccion     = $invertir_layout ? 'md:flex-row-reverse' : 'md:flex-row';

// Calcular contraste de texto segun el color de fondo
$rgb_componentes     = sscanf( $color_fondo_valor, "#%02x%02x%02x" );
$brillo_fondo        = ( ( $rgb_componentes[0] * 299 ) + ( $rgb_componentes[1] * 587 ) + ( $rgb_componentes[2] * 114 ) ) / 1000;
$es_fondo_oscuro     = $brillo_fondo <= 155;
$clase_color_titulo  = $es_fondo_oscuro ? 'text-white' : 'text-gray-900';
$clase_color_texto   = $es_fondo_oscuro ? 'text-gray-200' : 'text-gray-600';
?>

<section
    class="min-h-screen flex items-center <?php echo esc_attr( $component_classes ?? '' ); ?>"
    style="background-color: <?php echo esc_attr( $color_fondo_valor ); ?>;"
>
    <div class="w-full">
        <div class="flex flex-col <?php echo esc_attr( $clase_direccion ); ?> min-h-screen">

            <!-- Columna de contenido -->
            <div class="w-full md:w-1/2 flex items-center justify-center px-6 py-16 md:px-12 lg:px-20">
                <div class="max-w-xl w-full">

                    <!-- Titulo -->
                    <?php if ( ! empty( $titulo ) ) : ?>
                        <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold leading-tight mb-6 <?php echo esc_attr( $clase_color_titulo ); ?>">
                            <?php echo esc_html( $titulo ); ?>
                        </h1>
                    <?php endif; ?>

                    <!-- Subtitulo -->
                    <?php if ( ! empty( $subtitulo ) ) : ?>
                        <p class="text-lg md:text-xl leading-relaxed mb-8 <?php echo esc_attr( $clase_color_texto ); ?>">
                            <?php echo esc_html( $subtitulo ); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Separador decorativo -->
                    <div class="w-16 h-1 rounded-full mb-8" style="background-color: var(--flavor-primary);"></div>

                    <!-- Boton CTA -->
                    <?php if ( ! empty( $texto_cta ) && ! empty( $url_cta ) ) : ?>
                        <a
                            href="<?php echo esc_url( $url_cta ); ?>"
                            class="inline-flex items-center px-8 py-4 text-lg font-semibold rounded-lg transition duration-300 transform hover:scale-105 shadow-lg hover:shadow-xl"
                            style="background-color: var(--flavor-primary); color: #ffffff;"
                        >
                            <?php echo esc_html( $texto_cta ); ?>
                            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                            </svg>
                        </a>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Columna de imagen -->
            <div class="w-full md:w-1/2 relative min-h-[50vh] md:min-h-screen">
                <?php if ( ! empty( $imagen_url ) ) : ?>
                    <img
                        src="<?php echo esc_url( $imagen_url ); ?>"
                        alt="<?php echo esc_attr( $titulo ?? '' ); ?>"
                        class="absolute inset-0 w-full h-full object-cover"
                        loading="eager"
                    >
                <?php else : ?>
                    <!-- Placeholder cuando no hay imagen -->
                    <div
                        class="absolute inset-0 flex items-center justify-center"
                        style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary, var(--flavor-primary)) 100%); opacity: 0.15;"
                    >
                        <svg class="w-24 h-24 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
