<?php
/**
 * Themacle Component: Gallery
 *
 * Responsive image grid with dynamic columns, hover effects,
 * and lightbox-ready data attributes.
 *
 * @package FlavorPlatform
 *
 * @var string $titulo            Section heading text.
 * @var int    $columnas          Number of grid columns: 2, 3, or 4.
 * @var array  $imagenes          Repeater field — each item contains:
 *                                  'imagen' => (int) attachment ID,
 *                                  'titulo'  => (string) caption text.
 * @var string $component_classes Additional CSS classes passed from the builder.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$columnas          = $columnas ?? 3;
$imagenes          = $imagenes ?? array();
$component_classes = $component_classes ?? '';

$mapa_columnas_grid = array(
    2 => 'grid-cols-1 sm:grid-cols-2',
    3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    4 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
);

$clases_grid = $mapa_columnas_grid[ (int) $columnas ] ?? $mapa_columnas_grid[3];

$clases_seccion = sprintf(
    'flavor-gallery w-full py-12 md:py-20 %s',
    esc_attr( $component_classes )
);

$identificador_galeria = 'flavor-gallery-' . wp_unique_id();
?>

<section class="<?php echo esc_attr( trim( $clases_seccion ) ); ?>"
         style="font-family: var(--flavor-font-family, inherit);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if ( ! empty( $titulo ) ) : ?>
            <h2 class="text-3xl md:text-4xl font-bold mb-10 text-center"
                style="color: var(--flavor-heading-color, #111827);">
                <?php echo esc_html( $titulo ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( ! empty( $imagenes ) && is_array( $imagenes ) ) : ?>
            <div class="grid <?php echo esc_attr( $clases_grid ); ?> gap-4 md:gap-6">
                <?php foreach ( $imagenes as $indice_imagen => $elemento_imagen ) :
                    $id_adjunto     = isset( $elemento_imagen['imagen'] ) ? (int) $elemento_imagen['imagen'] : 0;
                    $titulo_imagen  = isset( $elemento_imagen['titulo'] ) ? $elemento_imagen['titulo'] : '';
                    $url_completa   = $id_adjunto ? wp_get_attachment_image_url( $id_adjunto, 'full' ) : '';
                    $url_mediana    = $id_adjunto ? wp_get_attachment_image_url( $id_adjunto, 'medium_large' ) : '';

                    if ( empty( $url_mediana ) ) {
                        continue;
                    }
                ?>
                    <figure class="group relative overflow-hidden rounded-xl shadow-md cursor-pointer
                                   transition-shadow duration-300 hover:shadow-xl"
                            data-lightbox-gallery="<?php echo esc_attr( $identificador_galeria ); ?>"
                            data-lightbox-src="<?php echo esc_url( $url_completa ); ?>"
                            data-lightbox-caption="<?php echo esc_attr( $titulo_imagen ); ?>"
                            data-lightbox-index="<?php echo esc_attr( $indice_imagen ); ?>">

                        <div class="aspect-square overflow-hidden">
                            <img src="<?php echo esc_url( $url_mediana ); ?>"
                                 alt="<?php echo esc_attr( $titulo_imagen ); ?>"
                                 loading="lazy"
                                 class="w-full h-full object-cover transition-transform duration-500
                                        group-hover:scale-110" />
                        </div>

                        <?php /* ── Hover overlay ───────────────────────── */ ?>
                        <div class="absolute inset-0 flex items-end
                                    bg-gradient-to-t from-black/60 via-black/20 to-transparent
                                    opacity-0 group-hover:opacity-100 transition-opacity duration-300">

                            <?php if ( ! empty( $titulo_imagen ) ) : ?>
                                <p class="w-full px-4 pb-4 text-white text-sm font-medium truncate">
                                    <?php echo esc_html( $titulo_imagen ); ?>
                                </p>
                            <?php endif; ?>

                            <span class="absolute top-3 right-3 text-white/80">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7" />
                                </svg>
                            </span>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="text-center py-8" style="color: var(--flavor-text-color, #6b7280);">
                <?php echo esc_html__( 'No images have been added to this gallery yet.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        <?php endif; ?>

    </div>
</section>
