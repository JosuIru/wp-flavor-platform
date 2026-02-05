<?php
/**
 * Themacle Feature Grid Component
 *
 * Displays a grid of feature cards with dashicon, title, and description.
 * All items are center-aligned with subtle hover effects.
 *
 * @package FlavorChatIA
 *
 * @var string $titulo    Section heading text.
 * @var string $subtitulo Section subheading text.
 * @var int    $columnas  Number of columns (2, 3, or 4).
 * @var array  $items     Repeater array with keys: icono, titulo, descripcion.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$subtitulo         = $subtitulo ?? '';
$columnas          = isset( $columnas ) ? absint( $columnas ) : 3;
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
?>

<section class="flavor-feature-grid w-full py-10 <?php echo esc_attr( $component_classes ); ?>">

    <?php if ( ! empty( $titulo ) || ! empty( $subtitulo ) ) : ?>
        <div class="text-center mb-10">

            <?php if ( ! empty( $titulo ) ) : ?>
                <h2 class="text-3xl font-bold mb-3" style="color: var(--flavor-heading, #1a1a1a);">
                    <?php echo esc_html( $titulo ); ?>
                </h2>
            <?php endif; ?>

            <?php if ( ! empty( $subtitulo ) ) : ?>
                <p class="text-lg max-w-2xl mx-auto" style="color: var(--flavor-text, #6b7280);">
                    <?php echo esc_html( $subtitulo ); ?>
                </p>
            <?php endif; ?>

        </div>
    <?php endif; ?>

    <?php if ( ! empty( $items ) && is_array( $items ) ) : ?>

        <div class="grid grid-cols-1 <?php echo esc_attr( $clase_columnas ); ?> gap-8">

            <?php foreach ( $items as $item_feature ) :
                $icono_clase          = $item_feature['icono'] ?? 'dashicons-star-filled';
                $feature_titulo       = $item_feature['titulo'] ?? '';
                $feature_descripcion  = $item_feature['descripcion'] ?? '';

                /*
                 * Sanitize the dashicons class to prevent injection.
                 * Only allow alphanumeric characters and hyphens.
                 */
                $icono_clase_sanitizada = preg_replace( '/[^a-zA-Z0-9\-]/', '', $icono_clase );

                /*
                 * Ensure the class starts with "dashicons-" to keep
                 * it within the expected icon set.
                 */
                if ( strpos( $icono_clase_sanitizada, 'dashicons-' ) !== 0 ) {
                    $icono_clase_sanitizada = 'dashicons-star-filled';
                }
            ?>

                <div class="flavor-feature-card text-center p-6 rounded-xl transition-all duration-300 hover:shadow-md hover:-translate-y-1" style="background-color: var(--flavor-bg-card, #ffffff);">

                    <div class="flavor-feature-card__icono flex items-center justify-center w-16 h-16 mx-auto mb-5 rounded-full transition-transform duration-300 hover:scale-110" style="background-color: var(--flavor-primary-light, #eff6ff);">
                        <span
                            class="dashicons <?php echo esc_attr( $icono_clase_sanitizada ); ?>"
                            style="font-size: 28px; width: 28px; height: 28px; color: var(--flavor-primary, #2563eb);"
                            aria-hidden="true"
                        ></span>
                    </div>

                    <?php if ( ! empty( $feature_titulo ) ) : ?>
                        <h3 class="text-lg font-semibold mb-2" style="color: var(--flavor-heading, #1a1a1a);">
                            <?php echo esc_html( $feature_titulo ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( ! empty( $feature_descripcion ) ) : ?>
                        <p class="text-sm leading-relaxed" style="color: var(--flavor-text, #6b7280);">
                            <?php echo esc_html( $feature_descripcion ); ?>
                        </p>
                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    <?php else : ?>

        <p class="text-gray-500 text-center py-8">
            <?php echo esc_html__( 'No hay características para mostrar.', 'flavor-chat-ia' ); ?>
        </p>

    <?php endif; ?>

</section>
