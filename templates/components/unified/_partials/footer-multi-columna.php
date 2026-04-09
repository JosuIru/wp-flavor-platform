<?php
/**
 * Partial: Footer - Multi Columna
 * Multi-column footer with links.
 *
 * Variables esperadas:
 *   $texto_copyright (string) Texto de copyright
 *   $color_fondo     (string) Color de fondo
 *   $color_texto     (string) Color del texto
 *   $columnas        (array)  Columnas: titulo, enlaces (array de: label, url)
 *   $redes_sociales  (array)  Redes sociales: nombre => url
 *   $logo            (string) URL del logo
 */

$texto_derechos     = $texto_copyright ?? '';
$color_fondo_footer = $color_fondo ?? '#111827';
$color_texto_footer = $color_texto ?? '#ffffff';
$lista_columnas     = $columnas ?? [];
$lista_redes        = $redes_sociales ?? [];
$url_logo           = $logo ?? '';

if ( empty( $texto_derechos ) ) {
    $texto_derechos = sprintf( '&copy; %s. %s', date( 'Y' ), __( 'Todos los derechos reservados.', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
}
?>

<footer style="background-color: <?php echo esc_attr( $color_fondo_footer ); ?>; color: <?php echo esc_attr( $color_texto_footer ); ?>;">
    <div class="max-w-6xl mx-auto px-4 pt-12 pb-8">
        <!-- Top section -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min( count( $lista_columnas ) + 1, 5 ); ?> gap-8 mb-10">
            <!-- Brand column -->
            <div class="lg:col-span-1">
                <?php if ( $url_logo ) : ?>
                    <img
                        src="<?php echo esc_url( $url_logo ); ?>"
                        alt=""
                        class="h-10 w-auto mb-4"
                        loading="lazy"
                    />
                <?php endif; ?>

                <?php if ( ! empty( $lista_redes ) ) : ?>
                    <div class="flex items-center gap-3 mt-4">
                        <?php foreach ( $lista_redes as $nombre_red => $url_red ) : ?>
                            <a
                                href="<?php echo esc_url( $url_red ); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-9 h-9 rounded-full flex items-center justify-center opacity-60 hover:opacity-100 transition-opacity border"
                                style="border-color: <?php echo esc_attr( $color_texto_footer ); ?>; color: <?php echo esc_attr( $color_texto_footer ); ?>;"
                                aria-label="<?php echo esc_attr( $nombre_red ); ?>"
                            >
                                <span class="text-xs font-bold uppercase"><?php echo esc_html( mb_substr( $nombre_red, 0, 2 ) ); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Link columns -->
            <?php foreach ( $lista_columnas as $columna_footer ) :
                $titulo_columna  = $columna_footer['titulo'] ?? '';
                $enlaces_columna = $columna_footer['enlaces'] ?? [];
            ?>
                <div>
                    <?php if ( $titulo_columna ) : ?>
                        <h4 class="font-semibold text-sm uppercase tracking-wider mb-4 opacity-90">
                            <?php echo esc_html( $titulo_columna ); ?>
                        </h4>
                    <?php endif; ?>

                    <?php if ( ! empty( $enlaces_columna ) ) : ?>
                        <ul class="space-y-2.5">
                            <?php foreach ( $enlaces_columna as $enlace ) :
                                $etiqueta_enlace = $enlace['label'] ?? '';
                                $url_enlace      = $enlace['url'] ?? '#';
                            ?>
                                <li>
                                    <a
                                        href="<?php echo esc_url( $url_enlace ); ?>"
                                        class="text-sm opacity-60 hover:opacity-100 transition-opacity"
                                        style="color: <?php echo esc_attr( $color_texto_footer ); ?>;"
                                    >
                                        <?php echo esc_html( $etiqueta_enlace ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bottom bar -->
        <div class="border-t pt-6" style="border-color: rgba(255,255,255,0.1);">
            <p class="text-sm opacity-60 text-center">
                <?php echo wp_kses_post( $texto_derechos ); ?>
            </p>
        </div>
    </div>
</footer>
