<?php
/**
 * Themacle Related Items Component
 *
 * Displays a responsive grid of related content items.
 * Shows skeleton placeholders when no data source is available.
 *
 * @package FlavorPlatform
 *
 * @var string $titulo         Section heading text.
 * @var int    $columnas       Number of columns (2, 3, or 4).
 * @var string $fuente_datos   Data source identifier for dynamic content.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$columnas          = isset( $columnas ) ? absint( $columnas ) : 3;
$fuente_datos      = $fuente_datos ?? '';
$component_classes = $component_classes ?? '';

$columnas_permitidas = array( 2, 3, 4 );
if ( ! in_array( $columnas, $columnas_permitidas, true ) ) {
    $columnas = 3;
}

$mapa_columnas_grid = array(
    2 => 'md:grid-cols-2',
    3 => 'md:grid-cols-3',
    4 => 'md:grid-cols-4',
);

$clase_columnas = $mapa_columnas_grid[ $columnas ];

/*
 * Retrieve related items from the data source.
 * Expected format: array of arrays with keys 'imagen' (attachment ID),
 * 'titulo' (string), and 'url' (string).
 */
$elementos_relacionados = array();

if ( ! empty( $fuente_datos ) ) {
    /**
     * Filters the related items returned for a given data source.
     *
     * @param array  $elementos_relacionados Default empty array.
     * @param string $fuente_datos           The data source identifier.
     */
    $elementos_relacionados = apply_filters( 'flavor_related_items_data', $elementos_relacionados, $fuente_datos );
}

$tiene_elementos = ! empty( $elementos_relacionados ) && is_array( $elementos_relacionados );
?>

<section class="flavor-related-items w-full py-8 <?php echo esc_attr( $component_classes ); ?>">

    <?php if ( ! empty( $titulo ) ) : ?>
        <h2 class="text-2xl font-bold mb-6" style="color: var(--flavor-primary, #1a1a1a);">
            <?php echo esc_html( $titulo ); ?>
        </h2>
    <?php endif; ?>

    <div class="grid grid-cols-1 <?php echo esc_attr( $clase_columnas ); ?> gap-6">

        <?php if ( $tiene_elementos ) : ?>

            <?php foreach ( $elementos_relacionados as $elemento ) :
                $imagen_id      = isset( $elemento['imagen'] ) ? absint( $elemento['imagen'] ) : 0;
                $elemento_titulo = $elemento['titulo'] ?? '';
                $elemento_url   = $elemento['url'] ?? '';
                $imagen_url     = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'medium' ) : '';
                $imagen_alt     = $imagen_id ? get_post_meta( $imagen_id, '_wp_attachment_image_alt', true ) : '';
            ?>

                <div class="flavor-related-item group rounded-lg overflow-hidden bg-white shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-0.5">

                    <?php if ( ! empty( $elemento_url ) ) : ?>
                        <a href="<?php echo esc_url( $elemento_url ); ?>" class="block">
                    <?php endif; ?>

                        <?php if ( ! empty( $imagen_url ) ) : ?>
                            <div class="flavor-related-item__imagen aspect-video overflow-hidden">
                                <img
                                    src="<?php echo esc_url( $imagen_url ); ?>"
                                    alt="<?php echo esc_attr( $imagen_alt ); ?>"
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                    loading="lazy"
                                />
                            </div>
                        <?php else : ?>
                            <div class="flavor-related-item__imagen aspect-video flex items-center justify-center" style="background-color: var(--flavor-bg-alt, #f3f4f6);">
                                <svg class="w-10 h-10 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $elemento_titulo ) ) : ?>
                            <div class="p-3">
                                <h3 class="text-base font-medium leading-snug transition-colors duration-200" style="color: var(--flavor-heading, #1a1a1a);">
                                    <?php echo esc_html( $elemento_titulo ); ?>
                                </h3>
                            </div>
                        <?php endif; ?>

                    <?php if ( ! empty( $elemento_url ) ) : ?>
                        </a>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        <?php else : ?>

            <?php
            /*
             * Skeleton placeholders shown when no data source provides items.
             * The number of placeholders matches the configured column count.
             */
            $cantidad_placeholders = $columnas;
            for ( $indice_placeholder = 0; $indice_placeholder < $cantidad_placeholders; $indice_placeholder++ ) :
            ?>

                <div class="flavor-related-item--skeleton rounded-lg overflow-hidden animate-pulse">

                    <div class="aspect-video" style="background-color: var(--flavor-bg-alt, #e5e7eb);"></div>

                    <div class="p-3 space-y-2">
                        <div class="h-4 rounded w-3/4" style="background-color: var(--flavor-bg-alt, #e5e7eb);"></div>
                        <div class="h-4 rounded w-1/2" style="background-color: var(--flavor-bg-alt, #e5e7eb);"></div>
                    </div>

                </div>

            <?php endfor; ?>

        <?php endif; ?>

    </div>

</section>
