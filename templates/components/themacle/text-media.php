<?php
/**
 * Themacle Component: Text Media
 *
 * Two-column layout combining text content with an image.
 * Supports reversed order and overlay style.
 *
 * @package FlavorPlatform
 *
 * @var string $titulo            Section heading text.
 * @var string $contenido         Rich-text body content.
 * @var int    $imagen            WordPress attachment ID for the media image.
 * @var bool   $invertir          Whether to reverse column order (image first).
 * @var string $estilo            Display style: 'simple' or 'overlay'.
 * @var string $component_classes Additional CSS classes passed from the builder.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$contenido         = $contenido ?? '';
$imagen            = $imagen ?? 0;
$invertir          = ! empty( $invertir );
$estilo            = $estilo ?? 'simple';
$component_classes = $component_classes ?? '';

$direccion_flex     = $invertir ? 'md:flex-row-reverse' : 'md:flex-row';
$es_estilo_overlay  = ( 'overlay' === $estilo );

$clases_seccion = sprintf(
    'flavor-text-media w-full py-12 md:py-20 %s',
    esc_attr( $component_classes )
);
?>

<section class="<?php echo esc_attr( trim( $clases_seccion ) ); ?>"
         style="font-family: var(--flavor-font-family, inherit);">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col <?php echo esc_attr( $direccion_flex ); ?> items-center gap-8 md:gap-12 <?php echo $es_estilo_overlay ? 'relative' : ''; ?>">

            <?php /* ── Text Column ────────────────────────────────── */ ?>
            <div class="w-full md:w-1/2 <?php echo $es_estilo_overlay ? 'relative z-10' : ''; ?>">
                <?php if ( $es_estilo_overlay ) : ?>
                    <div class="bg-white/90 backdrop-blur-sm rounded-2xl p-8 md:p-10 shadow-lg"
                         style="border-left: 4px solid var(--flavor-primary, #3b82f6);">
                <?php endif; ?>

                <?php if ( ! empty( $titulo ) ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold mb-6 leading-tight"
                        style="color: var(--flavor-heading-color, #111827);">
                        <?php echo esc_html( $titulo ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( ! empty( $contenido ) ) : ?>
                    <div class="prose prose-lg max-w-none leading-relaxed"
                         style="color: var(--flavor-text-color, #374151);">
                        <?php echo wp_kses_post( $contenido ); ?>
                    </div>
                <?php endif; ?>

                <?php if ( $es_estilo_overlay ) : ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php /* ── Image Column ───────────────────────────────── */ ?>
            <?php if ( ! empty( $imagen ) ) : ?>
                <div class="w-full md:w-1/2 <?php echo $es_estilo_overlay ? 'relative' : ''; ?>">
                    <div class="<?php echo $es_estilo_overlay
                        ? 'md:-ml-16 md:mt-8 rounded-2xl overflow-hidden shadow-2xl'
                        : 'rounded-xl overflow-hidden shadow-lg'; ?>">
                        <?php
                        echo wp_get_attachment_image(
                            (int) $imagen,
                            'large',
                            false,
                            array(
                                'class'   => 'w-full h-auto object-cover',
                                'loading' => 'lazy',
                            )
                        );
                        ?>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>
