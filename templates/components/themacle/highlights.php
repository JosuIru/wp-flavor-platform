<?php
/**
 * Themacle Highlights Component
 *
 * Displays highlighted items in one of three visual styles:
 *   - cards:   Full card with image, shadow, and rounded corners.
 *   - icons:   Circle image/icon at top with text below.
 *   - minimal: Simple text list with a small accent marker.
 *
 * @package FlavorChatIA
 *
 * @var string $titulo Section heading text.
 * @var array  $items  Repeater array with keys: imagen, titulo, descripcion, url.
 * @var string $estilo Visual style: 'cards', 'icons', or 'minimal'.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$items             = $items ?? array();
$estilo            = $estilo ?? 'cards';
$component_classes = $component_classes ?? '';

$estilos_permitidos = array( 'cards', 'icons', 'minimal' );
if ( ! in_array( $estilo, $estilos_permitidos, true ) ) {
    $estilo = 'cards';
}
?>

<section class="flavor-highlights w-full py-10 <?php echo esc_attr( $component_classes ); ?>">

    <?php if ( ! empty( $titulo ) ) : ?>
        <h2 class="text-2xl font-bold mb-8" style="color: var(--flavor-heading, #1a1a1a);">
            <?php echo esc_html( $titulo ); ?>
        </h2>
    <?php endif; ?>

    <?php if ( ! empty( $items ) && is_array( $items ) ) : ?>

        <?php
        /* ------------------------------------------------------------------ */
        /*  Style: cards                                                       */
        /* ------------------------------------------------------------------ */
        ?>
        <?php if ( 'cards' === $estilo ) : ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

                <?php foreach ( $items as $item_highlight ) :
                    $imagen_id               = isset( $item_highlight['imagen'] ) ? absint( $item_highlight['imagen'] ) : 0;
                    $highlight_titulo        = $item_highlight['titulo'] ?? '';
                    $highlight_descripcion   = $item_highlight['descripcion'] ?? '';
                    $highlight_url           = $item_highlight['url'] ?? '';
                    $imagen_url              = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'medium_large' ) : '';
                    $imagen_alt              = $imagen_id ? get_post_meta( $imagen_id, '_wp_attachment_image_alt', true ) : '';
                ?>

                    <div class="flavor-highlight-card rounded-xl overflow-hidden shadow-md hover:shadow-lg transition-all duration-300 bg-white hover:-translate-y-1">

                        <?php if ( ! empty( $imagen_url ) ) : ?>
                            <div class="aspect-video overflow-hidden">
                                <img
                                    src="<?php echo esc_url( $imagen_url ); ?>"
                                    alt="<?php echo esc_attr( $imagen_alt ); ?>"
                                    class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                    loading="lazy"
                                />
                            </div>
                        <?php endif; ?>

                        <div class="p-5">
                            <?php if ( ! empty( $highlight_titulo ) ) : ?>
                                <h3 class="text-lg font-semibold mb-2" style="color: var(--flavor-heading, #1a1a1a);">
                                    <?php echo esc_html( $highlight_titulo ); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( ! empty( $highlight_descripcion ) ) : ?>
                                <p class="text-sm leading-relaxed mb-3" style="color: var(--flavor-text, #6b7280);">
                                    <?php echo esc_html( $highlight_descripcion ); ?>
                                </p>
                            <?php endif; ?>

                            <?php if ( ! empty( $highlight_url ) ) : ?>
                                <a
                                    href="<?php echo esc_url( $highlight_url ); ?>"
                                    class="inline-flex items-center text-sm font-medium transition-colors duration-200 hover:underline"
                                    style="color: var(--flavor-primary, #2563eb);"
                                >
                                    <?php echo esc_html__( 'Descubrir', 'flavor-chat-ia' ); ?>
                                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php
        /* ------------------------------------------------------------------ */
        /*  Style: icons                                                       */
        /* ------------------------------------------------------------------ */
        ?>
        <?php elseif ( 'icons' === $estilo ) : ?>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">

                <?php foreach ( $items as $item_highlight ) :
                    $imagen_id               = isset( $item_highlight['imagen'] ) ? absint( $item_highlight['imagen'] ) : 0;
                    $highlight_titulo        = $item_highlight['titulo'] ?? '';
                    $highlight_descripcion   = $item_highlight['descripcion'] ?? '';
                    $highlight_url           = $item_highlight['url'] ?? '';
                    $imagen_url              = $imagen_id ? wp_get_attachment_image_url( $imagen_id, 'thumbnail' ) : '';
                    $imagen_alt              = $imagen_id ? get_post_meta( $imagen_id, '_wp_attachment_image_alt', true ) : '';
                ?>

                    <div class="flavor-highlight-icon text-center group">

                        <?php if ( ! empty( $highlight_url ) ) : ?>
                            <a href="<?php echo esc_url( $highlight_url ); ?>" class="block">
                        <?php endif; ?>

                            <div class="w-20 h-20 mx-auto mb-4 rounded-full overflow-hidden border-2 transition-all duration-300 group-hover:scale-110 group-hover:shadow-md" style="border-color: var(--flavor-primary, #2563eb);">
                                <?php if ( ! empty( $imagen_url ) ) : ?>
                                    <img
                                        src="<?php echo esc_url( $imagen_url ); ?>"
                                        alt="<?php echo esc_attr( $imagen_alt ); ?>"
                                        class="w-full h-full object-cover"
                                        loading="lazy"
                                    />
                                <?php else : ?>
                                    <div class="w-full h-full flex items-center justify-center" style="background-color: var(--flavor-primary-light, #eff6ff);">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="color: var(--flavor-primary, #2563eb);">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ( ! empty( $highlight_titulo ) ) : ?>
                                <h3 class="text-base font-semibold mb-1 transition-colors duration-200" style="color: var(--flavor-heading, #1a1a1a);">
                                    <?php echo esc_html( $highlight_titulo ); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( ! empty( $highlight_descripcion ) ) : ?>
                                <p class="text-xs leading-relaxed" style="color: var(--flavor-text, #6b7280);">
                                    <?php echo esc_html( $highlight_descripcion ); ?>
                                </p>
                            <?php endif; ?>

                        <?php if ( ! empty( $highlight_url ) ) : ?>
                            </a>
                        <?php endif; ?>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php
        /* ------------------------------------------------------------------ */
        /*  Style: minimal                                                     */
        /* ------------------------------------------------------------------ */
        ?>
        <?php elseif ( 'minimal' === $estilo ) : ?>

            <ul class="space-y-4">

                <?php foreach ( $items as $item_highlight ) :
                    $highlight_titulo        = $item_highlight['titulo'] ?? '';
                    $highlight_descripcion   = $item_highlight['descripcion'] ?? '';
                    $highlight_url           = $item_highlight['url'] ?? '';
                ?>

                    <li class="flavor-highlight-minimal flex items-start gap-3 group">

                        <span
                            class="flex-shrink-0 w-2 h-2 mt-2 rounded-full"
                            style="background-color: var(--flavor-primary, #2563eb);"
                            aria-hidden="true"
                        ></span>

                        <div class="flex-1">

                            <?php if ( ! empty( $highlight_titulo ) ) : ?>
                                <h3 class="text-base font-medium" style="color: var(--flavor-heading, #1a1a1a);">
                                    <?php if ( ! empty( $highlight_url ) ) : ?>
                                        <a
                                            href="<?php echo esc_url( $highlight_url ); ?>"
                                            class="hover:underline transition-colors duration-200"
                                            style="color: inherit;"
                                        >
                                            <?php echo esc_html( $highlight_titulo ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html( $highlight_titulo ); ?>
                                    <?php endif; ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( ! empty( $highlight_descripcion ) ) : ?>
                                <p class="text-sm mt-1" style="color: var(--flavor-text, #6b7280);">
                                    <?php echo esc_html( $highlight_descripcion ); ?>
                                </p>
                            <?php endif; ?>

                        </div>

                    </li>

                <?php endforeach; ?>

            </ul>

        <?php endif; ?>

    <?php else : ?>

        <p class="text-gray-500 text-center py-8">
            <?php echo esc_html__( 'No hay elementos destacados para mostrar.', 'flavor-chat-ia' ); ?>
        </p>

    <?php endif; ?>

</section>
