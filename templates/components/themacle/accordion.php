<?php
/**
 * Themacle Component: Accordion
 *
 * Expandable/collapsible FAQ-style section using native
 * <details>/<summary> elements — no JavaScript required.
 *
 * @package FlavorChatIA
 *
 * @var string $titulo            Section heading text.
 * @var array  $items             Repeater field — each item contains:
 *                                  'pregunta'  => (string) question / header,
 *                                  'respuesta' => (string) answer / body content.
 * @var string $component_classes Additional CSS classes passed from the builder.
 */

defined( 'ABSPATH' ) || exit;

$titulo            = $titulo ?? '';
$items             = $items ?? array();
$component_classes = $component_classes ?? '';

$clases_seccion = sprintf(
    'flavor-accordion w-full py-12 md:py-20 %s',
    esc_attr( $component_classes )
);

$identificador_acordeon = 'flavor-accordion-' . wp_unique_id();
?>

<section class="<?php echo esc_attr( trim( $clases_seccion ) ); ?>"
         style="font-family: var(--flavor-font-family, inherit);">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        <?php if ( ! empty( $titulo ) ) : ?>
            <h2 class="text-3xl md:text-4xl font-bold mb-10 text-center"
                style="color: var(--flavor-heading-color, #111827);">
                <?php echo esc_html( $titulo ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( ! empty( $items ) && is_array( $items ) ) : ?>
            <div class="space-y-3" id="<?php echo esc_attr( $identificador_acordeon ); ?>">
                <?php foreach ( $items as $indice_item => $elemento_acordeon ) :
                    $texto_pregunta  = isset( $elemento_acordeon['pregunta'] ) ? $elemento_acordeon['pregunta'] : '';
                    $texto_respuesta = isset( $elemento_acordeon['respuesta'] ) ? $elemento_acordeon['respuesta'] : '';

                    if ( empty( $texto_pregunta ) ) {
                        continue;
                    }
                ?>
                    <details class="flavor-accordion__item group rounded-xl border transition-all duration-300
                                    hover:shadow-md open:shadow-md"
                             style="border-color: var(--flavor-border-color, #e5e7eb);">

                        <summary class="flex items-center justify-between gap-4 cursor-pointer
                                        px-6 py-5 text-left select-none list-none
                                        [&::-webkit-details-marker]:hidden">

                            <span class="flex items-center gap-3 font-semibold text-base md:text-lg"
                                  style="color: var(--flavor-heading-color, #111827);">

                                <?php /* ── Active indicator bar ────────── */ ?>
                                <span class="hidden group-open:block w-1 h-6 rounded-full flex-shrink-0"
                                      style="background-color: var(--flavor-primary, #3b82f6);"></span>

                                <?php echo esc_html( $texto_pregunta ); ?>
                            </span>

                            <?php /* ── Chevron icon ────────────────── */ ?>
                            <span class="flex-shrink-0 transition-transform duration-300
                                         group-open:rotate-180"
                                  style="color: var(--flavor-primary, #3b82f6);">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </summary>

                        <div class="px-6 pb-6 pt-2">
                            <div class="prose prose-base max-w-none leading-relaxed"
                                 style="color: var(--flavor-text-color, #374151);">
                                <?php echo wp_kses_post( $texto_respuesta ); ?>
                            </div>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <p class="text-center py-8" style="color: var(--flavor-text-color, #6b7280);">
                <?php echo esc_html__( 'No items have been added to this accordion.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
            </p>
        <?php endif; ?>

    </div>

    <?php /* ── Inline transition styles for content reveal ──── */ ?>
    <style>
        #<?php echo esc_attr( $identificador_acordeon ); ?> details[open] > summary {
            border-bottom: 1px solid var(--flavor-border-color, #e5e7eb);
        }

        #<?php echo esc_attr( $identificador_acordeon ); ?> details > div {
            animation: flavorAccordionSlideDown 0.25s ease-out;
        }

        @keyframes flavorAccordionSlideDown {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</section>
