<?php
/**
 * Themacle Newsletter Component
 *
 * Centered section with heading, subtitle and an email subscription form.
 * The form is integration-agnostic (action="#") so it can be hooked
 * into any mailing service via JavaScript or a custom handler.
 *
 * @package FlavorChatIA
 *
 * @var string $titulo            Section heading text.
 * @var string $subtitulo         Section description text.
 * @var string $texto_placeholder Placeholder text for the email input.
 * @var string $texto_boton       Submit button label.
 * @var string $color_fondo       Hex or CSS color for section background.
 * @var string $component_classes Additional CSS classes for the wrapper.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$titulo            = isset( $titulo ) ? $titulo : '';
$subtitulo         = isset( $subtitulo ) ? $subtitulo : '';
$texto_placeholder = isset( $texto_placeholder ) ? $texto_placeholder : __( 'Tu email', 'flavor-chat-ia' );
$texto_boton       = isset( $texto_boton ) ? $texto_boton : __( 'Suscribirse', 'flavor-chat-ia' );
$color_fondo       = isset( $color_fondo ) ? $color_fondo : '';
$component_classes = isset( $component_classes ) ? $component_classes : '';

$estilo_seccion = '';
if ( $color_fondo ) {
    $estilo_seccion = sprintf( 'background-color: %s;', esc_attr( $color_fondo ) );
}

$clase_fondo = '';
if ( ! $color_fondo ) {
    $clase_fondo = 'bg-gray-100';
}

$identificador_formulario = 'flavor-newsletter-' . wp_unique_id();
?>

<section
    class="flavor-newsletter w-full py-16 <?php echo esc_attr( $clase_fondo ); ?> <?php echo esc_attr( $component_classes ); ?>"
    <?php if ( $estilo_seccion ) : ?>
        style="<?php echo esc_attr( $estilo_seccion ); ?>"
    <?php endif; ?>
>
    <div class="mx-auto max-w-2xl px-4 text-center">
        <?php if ( $titulo ) : ?>
            <h2 class="mb-3 text-2xl font-bold text-gray-900 sm:text-3xl">
                <?php echo esc_html( $titulo ); ?>
            </h2>
        <?php endif; ?>

        <?php if ( $subtitulo ) : ?>
            <p class="mx-auto mb-8 max-w-xl text-base text-gray-600 sm:text-lg">
                <?php echo esc_html( $subtitulo ); ?>
            </p>
        <?php endif; ?>

        <form
            id="<?php echo esc_attr( $identificador_formulario ); ?>"
            action="#"
            method="post"
            class="flavor-newsletter__form mx-auto flex max-w-lg flex-col items-stretch gap-3 sm:flex-row sm:gap-0"
        >
            <label for="<?php echo esc_attr( $identificador_formulario ); ?>-email" class="sr-only">
                <?php echo esc_html( $texto_placeholder ); ?>
            </label>

            <input
                id="<?php echo esc_attr( $identificador_formulario ); ?>-email"
                type="email"
                name="email"
                required
                placeholder="<?php echo esc_attr( $texto_placeholder ); ?>"
                class="flavor-newsletter__input w-full flex-1 rounded-lg border border-gray-300 bg-white px-4 py-3 text-base text-gray-900 placeholder-gray-400 transition-colors duration-200 focus:border-transparent focus:outline-none focus:ring-2 sm:rounded-r-none sm:border-r-0"
                style="--tw-ring-color: var(--flavor-primary, #2563eb);"
            />

            <button
                type="submit"
                class="flavor-newsletter__button inline-flex shrink-0 items-center justify-center rounded-lg px-6 py-3 text-base font-semibold text-white transition-opacity duration-200 hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 sm:rounded-l-none"
                style="background-color: var(--flavor-primary, #2563eb); --tw-ring-color: var(--flavor-primary, #2563eb);"
            >
                <?php echo esc_html( $texto_boton ); ?>
            </button>
        </form>
    </div>
</section>
