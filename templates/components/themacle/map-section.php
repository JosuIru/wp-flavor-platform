<?php
/**
 * Themacle Component: Map Section
 *
 * Two-column contact layout: contact info with SVG icons on the left,
 * embedded Google Maps iframe on the right, plus an optional contact form.
 *
 * @package FlavorPlatform
 *
 * @var string $titulo              Section heading text.
 * @var string $direccion           Physical address displayed and used for the map embed.
 * @var string $telefono            Phone number.
 * @var string $email               Email address.
 * @var string $horario             Business hours text.
 * @var bool   $mostrar_formulario  Whether to display the contact form.
 * @var string $component_classes   Additional CSS classes passed from the builder.
 */

defined( 'ABSPATH' ) || exit;

$titulo              = $titulo ?? '';
$direccion           = $direccion ?? '';
$telefono            = $telefono ?? '';
$email               = $email ?? '';
$horario             = $horario ?? '';
$mostrar_formulario  = ! empty( $mostrar_formulario );
$component_classes   = $component_classes ?? '';

$clases_seccion = sprintf(
    'flavor-map-section w-full py-12 md:py-20 %s',
    esc_attr( $component_classes )
);

$url_mapa_incrustado = ! empty( $direccion )
    ? 'https://www.google.com/maps?q=' . urlencode( $direccion ) . '&output=embed'
    : '';
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 md:gap-12">

            <?php /* ── Left Column: Contact Info & Optional Form ── */ ?>
            <div class="space-y-8">

                <?php /* ── Contact details ─────────────────────── */ ?>
                <div class="space-y-5">

                    <?php if ( ! empty( $direccion ) ) : ?>
                        <div class="flex items-start gap-4">
                            <span class="flex-shrink-0 mt-1" style="color: var(--flavor-primary, #3b82f6);">
                                <?php /* SVG: Location pin */ ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </span>
                            <div>
                                <p class="font-semibold text-sm uppercase tracking-wider mb-1"
                                   style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Address', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </p>
                                <p style="color: var(--flavor-text-color, #374151);">
                                    <?php echo esc_html( $direccion ); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $telefono ) ) : ?>
                        <div class="flex items-start gap-4">
                            <span class="flex-shrink-0 mt-1" style="color: var(--flavor-primary, #3b82f6);">
                                <?php /* SVG: Phone */ ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </span>
                            <div>
                                <p class="font-semibold text-sm uppercase tracking-wider mb-1"
                                   style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Phone', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </p>
                                <a href="tel:<?php echo esc_attr( $telefono ); ?>"
                                   class="hover:underline transition-colors duration-200"
                                   style="color: var(--flavor-primary, #3b82f6);">
                                    <?php echo esc_html( $telefono ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $email ) ) : ?>
                        <div class="flex items-start gap-4">
                            <span class="flex-shrink-0 mt-1" style="color: var(--flavor-primary, #3b82f6);">
                                <?php /* SVG: Email envelope */ ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <div>
                                <p class="font-semibold text-sm uppercase tracking-wider mb-1"
                                   style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </p>
                                <a href="mailto:<?php echo esc_attr( $email ); ?>"
                                   class="hover:underline transition-colors duration-200"
                                   style="color: var(--flavor-primary, #3b82f6);">
                                    <?php echo esc_html( $email ); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $horario ) ) : ?>
                        <div class="flex items-start gap-4">
                            <span class="flex-shrink-0 mt-1" style="color: var(--flavor-primary, #3b82f6);">
                                <?php /* SVG: Clock */ ?>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <p class="font-semibold text-sm uppercase tracking-wider mb-1"
                                   style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Hours', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </p>
                                <p style="color: var(--flavor-text-color, #374151);">
                                    <?php echo esc_html( $horario ); ?>
                                </p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <?php /* ── Contact Form (optional) ─────────────── */ ?>
                <?php if ( $mostrar_formulario ) : ?>
                    <div class="rounded-xl border p-6 md:p-8"
                         style="border-color: var(--flavor-border-color, #e5e7eb);
                                background-color: var(--flavor-surface-color, #f9fafb);">

                        <h3 class="text-xl font-bold mb-6"
                            style="color: var(--flavor-heading-color, #111827);">
                            <?php echo esc_html__( 'Send us a message', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </h3>

                        <form class="flavor-contact-form space-y-4" method="post">

                            <?php wp_nonce_field( 'flavor_contact_form', 'flavor_contact_nonce' ); ?>

                            <div>
                                <label for="flavor_contact_nombre"
                                       class="block text-sm font-medium mb-1"
                                       style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Name', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                                <input type="text"
                                       id="flavor_contact_nombre"
                                       name="flavor_contact_nombre"
                                       required
                                       class="w-full rounded-lg border px-4 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 transition-shadow duration-200"
                                       style="border-color: var(--flavor-border-color, #d1d5db);
                                              focus-ring-color: var(--flavor-primary, #3b82f6);"
                                       placeholder="<?php echo esc_attr__( 'Your name', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" />
                            </div>

                            <div>
                                <label for="flavor_contact_email"
                                       class="block text-sm font-medium mb-1"
                                       style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                                <input type="email"
                                       id="flavor_contact_email"
                                       name="flavor_contact_email"
                                       required
                                       class="w-full rounded-lg border px-4 py-2.5 text-sm
                                              focus:outline-none focus:ring-2 transition-shadow duration-200"
                                       style="border-color: var(--flavor-border-color, #d1d5db);"
                                       placeholder="<?php echo esc_attr__( 'your@email.com', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>" />
                            </div>

                            <div>
                                <label for="flavor_contact_mensaje"
                                       class="block text-sm font-medium mb-1"
                                       style="color: var(--flavor-heading-color, #111827);">
                                    <?php echo esc_html__( 'Message', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                </label>
                                <textarea id="flavor_contact_mensaje"
                                          name="flavor_contact_mensaje"
                                          rows="4"
                                          required
                                          class="w-full rounded-lg border px-4 py-2.5 text-sm resize-y
                                                 focus:outline-none focus:ring-2 transition-shadow duration-200"
                                          style="border-color: var(--flavor-border-color, #d1d5db);"
                                          placeholder="<?php echo esc_attr__( 'How can we help?', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"></textarea>
                            </div>

                            <button type="submit"
                                    class="w-full rounded-lg px-6 py-3 text-sm font-semibold text-white
                                           transition-opacity duration-200 hover:opacity-90
                                           focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    style="background-color: var(--flavor-primary, #3b82f6);
                                           --tw-ring-color: var(--flavor-primary, #3b82f6);">
                                <?php echo esc_html__( 'Send message', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        </form>
                    </div>
                <?php endif; ?>

            </div>

            <?php /* ── Right Column: Map ───────────────────────── */ ?>
            <div class="w-full">
                <?php if ( ! empty( $url_mapa_incrustado ) ) : ?>
                    <div class="rounded-xl overflow-hidden shadow-lg h-full min-h-[400px]">
                        <iframe src="<?php echo esc_url( $url_mapa_incrustado ); ?>"
                                width="100%"
                                height="100%"
                                style="border:0; min-height: 400px;"
                                allowfullscreen=""
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="<?php echo esc_attr__( 'Map location', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
                        </iframe>
                    </div>
                <?php else : ?>
                    <div class="rounded-xl flex items-center justify-center h-full min-h-[400px]"
                         style="background-color: var(--flavor-surface-color, #f3f4f6);">
                        <p class="text-center px-6" style="color: var(--flavor-text-color, #6b7280);">
                            <?php echo esc_html__( 'No address provided for the map.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>
