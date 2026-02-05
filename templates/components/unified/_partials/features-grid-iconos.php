<?php
/**
 * Features Partial: Grid de Iconos
 *
 * Grid of feature cards with icon, title, and description.
 *
 * Available variables:
 * @var string $titulo          Section heading text
 * @var string $subtitulo       Section subheading text
 * @var string $color_primario  Primary accent color (hex)
 * @var int    $columnas        Number of columns (2, 3, or 4)
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
$columnas_sanitizadas     = ! empty( $columnas ) ? intval( $columnas ) : 3;
$items_sanitizados        = ! empty( $items ) && is_array( $items ) ? $items : array();

// Map column count to Tailwind grid classes
$clases_columnas_mapa = array(
    2 => 'sm:grid-cols-2',
    3 => 'sm:grid-cols-2 lg:grid-cols-3',
    4 => 'sm:grid-cols-2 lg:grid-cols-4',
);
$clases_columnas = isset( $clases_columnas_mapa[ $columnas_sanitizadas ] )
    ? $clases_columnas_mapa[ $columnas_sanitizadas ]
    : $clases_columnas_mapa[3];
?>

<section class="w-full py-12 md:py-16 lg:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <?php if ( $titulo_sanitizado || $subtitulo_sanitizado ) : ?>
            <div class="text-center mb-12 md:mb-16">
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

        <!-- Features Grid -->
        <?php if ( ! empty( $items_sanitizados ) ) : ?>
            <div class="grid grid-cols-1 <?php echo esc_attr( $clases_columnas ); ?> gap-6 md:gap-8">

                <?php foreach ( $items_sanitizados as $elemento ) :
                    $elemento_titulo      = ! empty( $elemento['titulo'] ) ? $elemento['titulo'] : '';
                    $elemento_descripcion = ! empty( $elemento['descripcion'] ) ? $elemento['descripcion'] : '';
                    $elemento_icono       = ! empty( $elemento['icono'] ) ? $elemento['icono'] : '';
                ?>
                    <div class="group relative bg-white rounded-xl border border-gray-100 p-6 md:p-8 shadow-sm hover:shadow-lg transition-all duration-300 hover:-translate-y-1">

                        <?php if ( $elemento_icono ) : ?>
                            <div
                                class="inline-flex items-center justify-center w-14 h-14 rounded-xl text-2xl mb-5 transition-transform duration-300 group-hover:scale-110"
                                style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>12; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                            >
                                <?php echo $elemento_icono; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $elemento_titulo ) : ?>
                            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2">
                                <?php echo esc_html( $elemento_titulo ); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if ( $elemento_descripcion ) : ?>
                            <p class="text-base text-gray-500 leading-relaxed">
                                <?php echo esc_html( $elemento_descripcion ); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Accent bar at top -->
                        <div
                            class="absolute top-0 left-6 right-6 h-0.5 rounded-b opacity-0 group-hover:opacity-100 transition-opacity duration-300"
                            style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        ></div>
                    </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>

    </div>
</section>
