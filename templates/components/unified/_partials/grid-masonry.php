<?php
/**
 * Partial: Grid Masonry con columnas CSS
 *
 * Layout masonry usando CSS columns para distribuir tarjetas
 * de alturas variables de forma orgánica.
 *
 * Variables disponibles:
 *   $titulo, $subtitulo, $color_primario, $columnas,
 *   $items (titulo, descripcion, imagen, url, icono),
 *   $limite, $mostrar_imagen, $mostrar_descripcion
 */

if ( empty( $items ) || ! is_array( $items ) ) {
    return;
}

$color_primario_escapado  = ! empty( $color_primario ) ? esc_attr( $color_primario ) : '#3b82f6';
$numero_columnas          = ! empty( $columnas ) ? absint( $columnas ) : 3;
$limite_items             = ! empty( $limite ) ? absint( $limite ) : 0;
$mostrar_imagen_flag      = isset( $mostrar_imagen ) ? (bool) $mostrar_imagen : true;
$mostrar_descripcion_flag = isset( $mostrar_descripcion ) ? (bool) $mostrar_descripcion : true;

$items_visibles = $limite_items > 0 ? array_slice( $items, 0, $limite_items ) : $items;

$identificador_unico = 'masonry-' . wp_unique_id();
?>

<div class="w-full">
    <?php if ( ! empty( $titulo ) || ! empty( $subtitulo ) ) : ?>
        <div class="mb-8 text-center">
            <?php if ( ! empty( $titulo ) ) : ?>
                <h2 class="text-2xl font-bold text-gray-900 md:text-3xl" style="color: <?php echo $color_primario_escapado; ?>;">
                    <?php echo esc_html( $titulo ); ?>
                </h2>
            <?php endif; ?>
            <?php if ( ! empty( $subtitulo ) ) : ?>
                <p class="mt-2 text-base text-gray-500 md:text-lg">
                    <?php echo esc_html( $subtitulo ); ?>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <style>
        #<?php echo esc_attr( $identificador_unico ); ?> {
            columns: 1;
            column-gap: 1.5rem;
        }
        @media (min-width: 640px) {
            #<?php echo esc_attr( $identificador_unico ); ?> {
                columns: <?php echo min( $numero_columnas, 2 ); ?>;
            }
        }
        @media (min-width: 1024px) {
            #<?php echo esc_attr( $identificador_unico ); ?> {
                columns: <?php echo $numero_columnas; ?>;
            }
        }
        #<?php echo esc_attr( $identificador_unico ); ?> .masonry-item {
            break-inside: avoid;
            margin-bottom: 1.5rem;
        }
    </style>

    <div id="<?php echo esc_attr( $identificador_unico ); ?>">
        <?php foreach ( $items_visibles as $item ) :
            $item_titulo      = $item['titulo'] ?? '';
            $item_descripcion = $item['descripcion'] ?? '';
            $item_imagen      = $item['imagen'] ?? '';
            $item_url         = $item['url'] ?? '';
            $item_icono       = $item['icono'] ?? '';

            $etiqueta_contenedor = ! empty( $item_url ) ? 'a' : 'div';
            $atributos_enlace    = ! empty( $item_url ) ? ' href="' . esc_url( $item_url ) . '"' : '';
        ?>
            <<?php echo $etiqueta_contenedor . $atributos_enlace; ?> class="masonry-item group block overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg">
                <?php if ( $mostrar_imagen_flag && ! empty( $item_imagen ) ) : ?>
                    <div class="w-full overflow-hidden bg-gray-100">
                        <img
                            src="<?php echo esc_url( $item_imagen ); ?>"
                            alt="<?php echo esc_attr( $item_titulo ); ?>"
                            class="w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            loading="lazy"
                        />
                    </div>
                <?php endif; ?>

                <div class="p-5">
                    <?php if ( ! empty( $item_icono ) && empty( $item_imagen ) ) : ?>
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: <?php echo $color_primario_escapado; ?>1a;">
                            <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: <?php echo $color_primario_escapado; ?>; font-size: 20px; width: 20px; height: 20px;"></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $item_titulo ) ) : ?>
                        <h3 class="text-base font-semibold text-gray-900">
                            <?php echo esc_html( $item_titulo ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $mostrar_descripcion_flag && ! empty( $item_descripcion ) ) : ?>
                        <p class="mt-2 text-sm leading-relaxed text-gray-600">
                            <?php echo esc_html( $item_descripcion ); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( ! empty( $item_url ) ) : ?>
                        <div class="mt-3 flex items-center text-xs font-medium" style="color: <?php echo $color_primario_escapado; ?>;">
                            <span><?php esc_html_e( 'Leer más', 'flavor-chat-ia' ); ?></span>
                            <svg class="ml-1 h-3 w-3 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
            </<?php echo $etiqueta_contenedor; ?>>
        <?php endforeach; ?>
    </div>
</div>
