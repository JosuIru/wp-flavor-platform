<?php
/**
 * Themacle Card Grid Component
 *
 * Displays a responsive grid of cards with image, title, description, and link.
 *
 * @package FlavorPlatform
 *
 * @var string $titulo         Section heading text.
 * @var int    $columnas       Number of columns (2, 3, or 4).
 * @var string $estilo_card    Card visual style: 'shadow', 'border', or 'flat'.
 * @var string $fuente_datos   Data source identifier for dynamic content.
 * @var array  $items          Repeater array with keys: imagen, titulo, descripcion, url.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$columnas          = isset( $columnas ) ? absint( $columnas ) : 3;
$estilo_card       = $estilo_card ?? 'shadow';
$fuente_datos      = $fuente_datos ?? '';
$items             = $items ?? array();
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

$mapa_estilos_card = array(
    'shadow' => 'shadow-md hover:shadow-lg',
    'border' => 'border border-gray-200 hover:border-gray-400',
    'flat'   => '',
);

$estilos_permitidos = array( 'shadow', 'border', 'flat' );
if ( ! in_array( $estilo_card, $estilos_permitidos, true ) ) {
    $estilo_card = 'shadow';
}

$clase_estilo_card = $mapa_estilos_card[ $estilo_card ];
?>

<section class="flavor-card-grid w-full py-8 <?php echo esc_attr( $component_classes ); ?>">

    <?php if ( ! empty( $titulo ) ) : ?>
        <h2 class="text-2xl font-bold mb-6" style="color: var(--flavor-primary, #1a1a1a);">
            <?php echo esc_html( $titulo ); ?>
        </h2>
    <?php endif; ?>

    <?php if ( ! empty( $items ) && is_array( $items ) ) : ?>

        <div class="grid grid-cols-1 <?php echo esc_attr( $clase_columnas ); ?> gap-6">

            <?php foreach ( $items as $item ) :
                $imagen_id          = isset( $item['imagen'] ) ? absint( $item['imagen'] ) : 0;
                $item_titulo        = $item['titulo'] ?? '';
                $item_descripcion   = $item['descripcion'] ?? '';
                $item_url           = $item['url'] ?? '';
                $imagen_url         = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'medium_large' ) : '';
                $imagen_alt         = $imagen_id ? get_post_meta( $imagen_id, '_wp_attachment_image_alt', true ) : '';
            ?>

                <div class="flavor-card rounded-lg overflow-hidden transition-all duration-300 bg-white <?php echo esc_attr( $clase_estilo_card ); ?> hover:-translate-y-1">

                    <?php if ( ! empty( $imagen_url ) ) : ?>
                        <div class="flavor-card__imagen aspect-video overflow-hidden">
                            <img
                                src="<?php echo esc_url( $imagen_url ); ?>"
                                alt="<?php echo esc_attr( $imagen_alt ); ?>"
                                class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                loading="lazy"
                            />
                        </div>
                    <?php endif; ?>

                    <div class="flavor-card__contenido p-4">

                        <?php if ( ! empty( $item_titulo ) ) : ?>
                            <h3 class="text-lg font-semibold mb-2" style="color: var(--flavor-heading, #1a1a1a);">
                                <?php if ( ! empty( $item_url ) ) : ?>
                                    <a href="<?php echo esc_url( $item_url ); ?>" class="hover:underline transition-colors duration-200" style="color: inherit;">
                                        <?php echo esc_html( $item_titulo ); ?>
                                    </a>
                                <?php else : ?>
                                    <?php echo esc_html( $item_titulo ); ?>
                                <?php endif; ?>
                            </h3>
                        <?php endif; ?>

                        <?php if ( ! empty( $item_descripcion ) ) : ?>
                            <p class="text-sm leading-relaxed mb-3" style="color: var(--flavor-text, #4a4a4a);">
                                <?php echo esc_html( $item_descripcion ); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ( ! empty( $item_url ) ) : ?>
                            <a
                                href="<?php echo esc_url( $item_url ); ?>"
                                class="inline-flex items-center text-sm font-medium transition-colors duration-200 hover:underline"
                                style="color: var(--flavor-primary, #2563eb);"
                            >
                                <?php echo esc_html__( 'Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else : ?>

        <p class="text-gray-500 text-center py-8">
            <?php echo esc_html__( 'No hay elementos para mostrar.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
        </p>

    <?php endif; ?>

</section>
