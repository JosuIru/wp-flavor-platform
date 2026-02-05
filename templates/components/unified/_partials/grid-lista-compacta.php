<?php
/**
 * Partial: Lista compacta vertical
 *
 * Lista compacta con imagen pequeña o icono, título y descripción breve.
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
$limite_items             = ! empty( $limite ) ? absint( $limite ) : 0;
$mostrar_imagen_flag      = isset( $mostrar_imagen ) ? (bool) $mostrar_imagen : true;
$mostrar_descripcion_flag = isset( $mostrar_descripcion ) ? (bool) $mostrar_descripcion : true;

$items_visibles = $limite_items > 0 ? array_slice( $items, 0, $limite_items ) : $items;
?>

<div class="w-full">
    <?php if ( ! empty( $titulo ) || ! empty( $subtitulo ) ) : ?>
        <div class="mb-6 text-center">
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

    <div class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <?php foreach ( $items_visibles as $indice => $item ) :
            $item_titulo      = $item['titulo'] ?? '';
            $item_descripcion = $item['descripcion'] ?? '';
            $item_imagen      = $item['imagen'] ?? '';
            $item_icono       = $item['icono'] ?? '';
            $item_url         = $item['url'] ?? '';

            $etiqueta_contenedor = ! empty( $item_url ) ? 'a' : 'div';
            $atributos_enlace    = ! empty( $item_url ) ? ' href="' . esc_url( $item_url ) . '"' : '';
        ?>
            <<?php echo $etiqueta_contenedor . $atributos_enlace; ?> class="group flex items-center gap-4 px-5 py-4 transition-colors duration-200 hover:bg-gray-50">
                <?php if ( $mostrar_imagen_flag ) : ?>
                    <?php if ( ! empty( $item_imagen ) ) : ?>
                        <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                            <img
                                src="<?php echo esc_url( $item_imagen ); ?>"
                                alt="<?php echo esc_attr( $item_titulo ); ?>"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </div>
                    <?php elseif ( ! empty( $item_icono ) ) : ?>
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg" style="background-color: <?php echo $color_primario_escapado; ?>1a;">
                            <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: <?php echo $color_primario_escapado; ?>; font-size: 22px; width: 22px; height: 22px;"></span>
                        </div>
                    <?php else : ?>
                        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100">
                            <span class="text-lg font-bold text-gray-400"><?php echo esc_html( $indice + 1 ); ?></span>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <?php if ( ! empty( $item_titulo ) ) : ?>
                        <h3 class="truncate text-sm font-semibold text-gray-900 group-hover:text-opacity-80">
                            <?php echo esc_html( $item_titulo ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $mostrar_descripcion_flag && ! empty( $item_descripcion ) ) : ?>
                        <p class="mt-0.5 truncate text-xs text-gray-500">
                            <?php echo esc_html( $item_descripcion ); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $item_url ) ) : ?>
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-gray-400 transition-transform duration-200 group-hover:translate-x-0.5" style="color: <?php echo $color_primario_escapado; ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                <?php endif; ?>
            </<?php echo $etiqueta_contenedor; ?>>
        <?php endforeach; ?>
    </div>
</div>
