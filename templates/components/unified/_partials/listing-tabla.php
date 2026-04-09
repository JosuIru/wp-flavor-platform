<?php
/**
 * Partial: Tabla responsiva
 *
 * Layout de tabla responsiva con filas para listar items.
 * En móvil se convierte en tarjetas apiladas.
 *
 * Variables disponibles:
 *   $titulo, $subtitulo, $color_primario, $columnas,
 *   $items (titulo, descripcion, imagen, url, icono),
 *   $limite, $mostrar_imagen, $mostrar_descripcion,
 *   $mostrar_filtros, $filtros (label, valor), $mostrar_buscador
 */

if ( empty( $items ) || ! is_array( $items ) ) {
    return;
}

$color_primario_escapado  = ! empty( $color_primario ) ? esc_attr( $color_primario ) : '#3b82f6';
$limite_items             = ! empty( $limite ) ? absint( $limite ) : 0;
$mostrar_imagen_flag      = isset( $mostrar_imagen ) ? (bool) $mostrar_imagen : true;
$mostrar_descripcion_flag = isset( $mostrar_descripcion ) ? (bool) $mostrar_descripcion : true;
$mostrar_buscador_flag    = isset( $mostrar_buscador ) ? (bool) $mostrar_buscador : false;

$items_visibles      = $limite_items > 0 ? array_slice( $items, 0, $limite_items ) : $items;
$identificador_unico = 'listing-tabla-' . wp_unique_id();
?>

<div class="w-full" id="<?php echo esc_attr( $identificador_unico ); ?>">
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

    <?php if ( $mostrar_buscador_flag ) : ?>
        <div class="mb-4 flex justify-end">
            <div class="relative w-full sm:w-64">
                <input
                    type="text"
                    class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2"
                    style="--tw-ring-color: <?php echo $color_primario_escapado; ?>;"
                    placeholder="<?php esc_attr_e( 'Buscar en la tabla...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                    data-buscador-tabla
                />
                <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
        </div>
    <?php endif; ?>

    <!-- Vista de escritorio -->
    <div class="hidden overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm md:block">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200" style="background-color: <?php echo $color_primario_escapado; ?>0d;">
                    <?php if ( $mostrar_imagen_flag ) : ?>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500"><?php echo esc_html__('&nbsp;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <?php endif; ?>
                    <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        <?php esc_html_e( 'Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </th>
                    <?php if ( $mostrar_descripcion_flag ) : ?>
                        <th class="px-6 py-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                            <?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </th>
                    <?php endif; ?>
                    <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"><?php echo esc_html__('&nbsp;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ( $items_visibles as $item ) :
                    $item_titulo      = $item['titulo'] ?? '';
                    $item_descripcion = $item['descripcion'] ?? '';
                    $item_imagen      = $item['imagen'] ?? '';
                    $item_url         = $item['url'] ?? '';
                    $item_icono       = $item['icono'] ?? '';
                ?>
                    <tr class="transition-colors duration-150 hover:bg-gray-50" data-fila-tabla data-titulo-fila="<?php echo esc_attr( strtolower( $item_titulo ) ); ?>">
                        <?php if ( $mostrar_imagen_flag ) : ?>
                            <td class="w-16 px-6 py-4">
                                <?php if ( ! empty( $item_imagen ) ) : ?>
                                    <div class="h-10 w-10 overflow-hidden rounded-lg bg-gray-100">
                                        <img src="<?php echo esc_url( $item_imagen ); ?>" alt="<?php echo esc_attr( $item_titulo ); ?>" class="h-full w-full object-cover" loading="lazy" />
                                    </div>
                                <?php elseif ( ! empty( $item_icono ) ) : ?>
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: <?php echo $color_primario_escapado; ?>1a;">
                                        <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: <?php echo $color_primario_escapado; ?>; font-size: 18px; width: 18px; height: 18px;"></span>
                                    </div>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-gray-900"><?php echo esc_html( $item_titulo ); ?></span>
                        </td>
                        <?php if ( $mostrar_descripcion_flag ) : ?>
                            <td class="max-w-xs px-6 py-4">
                                <p class="truncate text-sm text-gray-500"><?php echo esc_html( $item_descripcion ); ?></p>
                            </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 text-right">
                            <?php if ( ! empty( $item_url ) ) : ?>
                                <a href="<?php echo esc_url( $item_url ); ?>" class="inline-flex items-center text-sm font-medium transition-colors duration-200" style="color: <?php echo $color_primario_escapado; ?>;">
                                    <?php esc_html_e( 'Ver', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Vista móvil: tarjetas apiladas -->
    <div class="space-y-3 md:hidden">
        <?php foreach ( $items_visibles as $item ) :
            $item_titulo      = $item['titulo'] ?? '';
            $item_descripcion = $item['descripcion'] ?? '';
            $item_imagen      = $item['imagen'] ?? '';
            $item_url         = $item['url'] ?? '';
            $item_icono       = $item['icono'] ?? '';

            $etiqueta_contenedor = ! empty( $item_url ) ? 'a' : 'div';
            $atributos_enlace    = ! empty( $item_url ) ? ' href="' . esc_url( $item_url ) . '"' : '';
        ?>
            <<?php echo $etiqueta_contenedor . $atributos_enlace; ?> class="group flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:shadow-md" data-fila-tabla data-titulo-fila="<?php echo esc_attr( strtolower( $item_titulo ) ); ?>">
                <?php if ( $mostrar_imagen_flag && ! empty( $item_imagen ) ) : ?>
                    <div class="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                        <img src="<?php echo esc_url( $item_imagen ); ?>" alt="<?php echo esc_attr( $item_titulo ); ?>" class="h-full w-full object-cover" loading="lazy" />
                    </div>
                <?php elseif ( $mostrar_imagen_flag && ! empty( $item_icono ) ) : ?>
                    <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-lg" style="background-color: <?php echo $color_primario_escapado; ?>1a;">
                        <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: <?php echo $color_primario_escapado; ?>; font-size: 24px; width: 24px; height: 24px;"></span>
                    </div>
                <?php endif; ?>

                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-semibold text-gray-900"><?php echo esc_html( $item_titulo ); ?></h3>
                    <?php if ( $mostrar_descripcion_flag && ! empty( $item_descripcion ) ) : ?>
                        <p class="mt-1 truncate text-xs text-gray-500"><?php echo esc_html( $item_descripcion ); ?></p>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $item_url ) ) : ?>
                    <svg class="h-5 w-5 flex-shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                <?php endif; ?>
            </<?php echo $etiqueta_contenedor; ?>>
        <?php endforeach; ?>
    </div>

    <?php if ( $mostrar_buscador_flag ) : ?>
    <script>
    (function() {
        var contenedor    = document.getElementById('<?php echo esc_js( $identificador_unico ); ?>');
        var inputBuscador = contenedor ? contenedor.querySelector('[data-buscador-tabla]') : null;
        if (!inputBuscador) return;

        inputBuscador.addEventListener('input', function() {
            var termino = this.value.toLowerCase().trim();
            var filas   = contenedor.querySelectorAll('[data-fila-tabla]');
            filas.forEach(function(fila) {
                var tituloFila = fila.dataset.tituloFila || '';
                fila.style.display = !termino || tituloFila.indexOf(termino) !== -1 ? '' : 'none';
            });
        });
    })();
    </script>
    <?php endif; ?>
</div>
