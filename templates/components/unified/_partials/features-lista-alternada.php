<?php
/**
 * Features Partial: Lista Alternada
 *
 * Alternating left-right layout of features with icons.
 * Odd items show icon on left, even items show icon on right.
 *
 * Available variables:
 * @var string $titulo          Section heading text
 * @var string $subtitulo       Section subheading text
 * @var string $color_primario  Primary accent color (hex)
 * @var int    $columnas        Number of columns (unused in this variant)
 * @var array  $items           Array of features, each with: titulo, descripcion, icono
 *
 * @package FlavorChatIA
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$titulo_sanitizado        = ! empty( $titulo ) ? $titulo : '';
$subtitulo_sanitizado     = ! empty( $subtitulo ) ? $subtitulo : '';
$color_primario_sanitizado = ! empty( $color_primario ) ? $color_primario : '#2563eb';
$items_sanitizados        = ! empty( $items ) && is_array( $items ) ? $items : array();
?>

<section class="w-full py-12 md:py-16 lg:py-20">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <?php if ( $titulo_sanitizado || $subtitulo_sanitizado ) : ?>
            <div class="text-center mb-14 md:mb-20">
                <?php if ( $titulo_sanitizado ) : ?>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight mb-4">
                        <?php echo esc_html( $titulo_sanitizado ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( $subtitulo_sanitizado ) : ?>
                    <p class="text-lg sm:text-xl text-gray-500 max-w-3xl mx-auto leading-relaxed">
                        <?php echo esc_html( $subtitulo_sanitizado ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Alternating Features List -->
        <?php if ( ! empty( $items_sanitizados ) ) : ?>
            <div class="space-y-12 md:space-y-20">

                <?php foreach ( $items_sanitizados as $indice => $elemento ) :
                    $elemento_titulo      = ! empty( $elemento['titulo'] ) ? $elemento['titulo'] : '';
                    $elemento_descripcion = ! empty( $elemento['descripcion'] ) ? $elemento['descripcion'] : '';
                    $elemento_icono       = ! empty( $elemento['icono'] ) ? $elemento['icono'] : '';
                    $es_par               = ( $indice % 2 === 0 );
                ?>
                    <div class="flex flex-col <?php echo $es_par ? 'md:flex-row' : 'md:flex-row-reverse'; ?> items-center gap-8 md:gap-14">

                        <!-- Icon / Visual Side -->
                        <div class="md:w-5/12 flex justify-center">
                            <div
                                class="relative flex items-center justify-center w-32 h-32 sm:w-40 sm:h-40 md:w-48 md:h-48 rounded-2xl text-5xl sm:text-6xl"
                                style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>08;"
                            >
                                <?php if ( $elemento_icono ) : ?>
                                    <span style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;">
                                        <?php echo $elemento_icono; ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Decorative corner accent -->
                                <div
                                    class="absolute -top-2 -right-2 w-6 h-6 rounded-full opacity-30"
                                    style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                                ></div>
                                <div
                                    class="absolute -bottom-1 -left-1 w-4 h-4 rounded-full opacity-20"
                                    style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                                ></div>
                            </div>
                        </div>

                        <!-- Text Side -->
                        <div class="md:w-7/12 text-center md:text-left">
                            <!-- Step number -->
                            <span
                                class="inline-block text-sm font-bold tracking-wider uppercase mb-3"
                                style="color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                            >
                                <?php
                                /* translators: %d is the feature number */
                                printf( esc_html__( '%02d', FLAVOR_PLATFORM_TEXT_DOMAIN ), $indice + 1 );
                                ?>
                            </span>

                            <?php if ( $elemento_titulo ) : ?>
                                <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 leading-tight mb-3">
                                    <?php echo esc_html( $elemento_titulo ); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( $elemento_descripcion ) : ?>
                                <p class="text-base sm:text-lg text-gray-500 leading-relaxed max-w-lg">
                                    <?php echo esc_html( $elemento_descripcion ); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- Connector line between items (not after last) -->
                    <?php if ( $indice < count( $items_sanitizados ) - 1 ) : ?>
                        <div class="hidden md:flex justify-center">
                            <div class="w-px h-8" style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>30;"></div>
                        </div>
                    <?php endif; ?>

                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </div>
</section>
