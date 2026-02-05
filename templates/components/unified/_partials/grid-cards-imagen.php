<?php
/**
 * Partial: Grid de cards con imagen
 *
 * Grid responsivo de tarjetas con imagen, título y descripción.
 * Soporta 1, 2, 3 o 4 columnas según la variable $columnas.
 *
 * Variables disponibles:
 *   $titulo, $subtitulo, $color_primario, $columnas,
 *   $items (titulo, descripcion, imagen, url, icono),
 *   $limite, $mostrar_imagen, $mostrar_descripcion
 */

if ( empty( $items ) || ! is_array( $items ) ) {
    return;
}

$color_primario_escapado = ! empty( $color_primario ) ? esc_attr( $color_primario ) : '#3b82f6';
$numero_columnas         = ! empty( $columnas ) ? absint( $columnas ) : 3;
$limite_items            = ! empty( $limite ) ? absint( $limite ) : 0;
$mostrar_imagen_flag     = isset( $mostrar_imagen ) ? (bool) $mostrar_imagen : true;
$mostrar_descripcion_flag = isset( $mostrar_descripcion ) ? (bool) $mostrar_descripcion : true;

$mapa_columnas_grid = [
    1 => 'sm:grid-cols-1',
    2 => 'sm:grid-cols-1 md:grid-cols-2',
    3 => 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4 => 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
];
$clases_columnas = $mapa_columnas_grid[ $numero_columnas ] ?? $mapa_columnas_grid[3];

$items_visibles = $limite_items > 0 ? array_slice( $items, 0, $limite_items ) : $items;
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

    <div class="grid grid-cols-1 gap-6 <?php echo esc_attr( $clases_columnas ); ?>">
        <?php foreach ( $items_visibles as $item ) :
            $item_titulo      = $item['titulo'] ?? '';
            $item_descripcion = $item['descripcion'] ?? '';
            $item_imagen      = $item['imagen'] ?? '';
            $item_url         = $item['url'] ?? '';

            $etiqueta_contenedor = ! empty( $item_url ) ? 'a' : 'div';
            $atributos_enlace    = ! empty( $item_url ) ? ' href="' . esc_url( $item_url ) . '"' : '';
        ?>
            <<?php echo $etiqueta_contenedor . $atributos_enlace; ?> class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                <?php if ( $mostrar_imagen_flag && ! empty( $item_imagen ) ) : ?>
                    <div class="relative aspect-video w-full overflow-hidden bg-gray-100">
                        <img
                            src="<?php echo esc_url( $item_imagen ); ?>"
                            alt="<?php echo esc_attr( $item_titulo ); ?>"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                            loading="lazy"
                        />
                    </div>
                <?php endif; ?>

                <div class="flex flex-1 flex-col p-5">
                    <?php if ( ! empty( $item_titulo ) ) : ?>
                        <h3 class="text-lg font-semibold text-gray-900 transition-colors duration-200 group-hover:text-opacity-80" style="--hover-color: <?php echo $color_primario_escapado; ?>;">
                            <?php echo esc_html( $item_titulo ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $mostrar_descripcion_flag && ! empty( $item_descripcion ) ) : ?>
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-gray-600">
                            <?php echo esc_html( $item_descripcion ); ?>
                        </p>
                    <?php endif; ?>

                    <?php if ( ! empty( $item_url ) ) : ?>
                        <div class="mt-4 flex items-center text-sm font-medium" style="color: <?php echo $color_primario_escapado; ?>;">
                            <span><?php esc_html_e( 'Ver más', 'flavor-chat-ia' ); ?></span>
                            <svg class="ml-1 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                </div>
            </<?php echo $etiqueta_contenedor; ?>>
        <?php endforeach; ?>
    </div>
</div>
