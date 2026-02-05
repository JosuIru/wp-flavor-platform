<?php
/**
 * Partial: Footer - Simple
 * Simple footer with copyright and social.
 *
 * Variables esperadas:
 *   $texto_copyright (string) Texto de copyright
 *   $color_fondo     (string) Color de fondo
 *   $color_texto     (string) Color del texto
 *   $columnas        (array)  Columnas del footer
 *   $redes_sociales  (array)  Redes sociales: nombre => url
 *   $logo            (string) URL del logo
 */

$texto_derechos     = $texto_copyright ?? '';
$color_fondo_footer = $color_fondo ?? '#111827';
$color_texto_footer = $color_texto ?? '#ffffff';
$lista_redes        = $redes_sociales ?? [];
$url_logo           = $logo ?? '';

if ( empty( $texto_derechos ) ) {
    $texto_derechos = sprintf( '&copy; %s. %s', date( 'Y' ), __( 'Todos los derechos reservados.', 'flavor-chat-ia' ) );
}
?>

<footer style="background-color: <?php echo esc_attr( $color_fondo_footer ); ?>; color: <?php echo esc_attr( $color_texto_footer ); ?>;">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
            <!-- Logo / Copyright -->
            <div class="flex items-center gap-4">
                <?php if ( $url_logo ) : ?>
                    <img
                        src="<?php echo esc_url( $url_logo ); ?>"
                        alt=""
                        class="h-8 w-auto"
                        loading="lazy"
                    />
                <?php endif; ?>
                <p class="text-sm opacity-80">
                    <?php echo wp_kses_post( $texto_derechos ); ?>
                </p>
            </div>

            <!-- Social links -->
            <?php if ( ! empty( $lista_redes ) ) : ?>
                <div class="flex items-center gap-4">
                    <?php foreach ( $lista_redes as $nombre_red => $url_red ) : ?>
                        <a
                            href="<?php echo esc_url( $url_red ); ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="opacity-70 hover:opacity-100 transition-opacity text-sm font-medium"
                            style="color: <?php echo esc_attr( $color_texto_footer ); ?>;"
                            aria-label="<?php echo esc_attr( $nombre_red ); ?>"
                        >
                            <?php echo esc_html( $nombre_red ); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</footer>
