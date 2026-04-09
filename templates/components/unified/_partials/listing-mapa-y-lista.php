<?php
/**
 * Partial: Mapa y lista en dos columnas
 *
 * Layout de dos columnas: placeholder de mapa a la izquierda
 * y lista de items a la derecha. En móvil se apilan verticalmente.
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
$identificador_unico = 'listing-mapa-' . wp_unique_id();
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
        <!-- Columna del mapa -->
        <div class="lg:col-span-3">
            <div class="relative flex h-72 items-center justify-center overflow-hidden rounded-xl border border-gray-200 bg-gray-100 shadow-sm lg:sticky lg:top-4 lg:h-[500px]">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="mt-3 text-sm font-medium text-gray-400">
                        <?php esc_html_e( 'Mapa interactivo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </p>
                    <p class="mt-1 text-xs text-gray-400">
                        <?php esc_html_e( 'Integra aquí tu proveedor de mapas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </p>
                </div>
                <div class="absolute bottom-0 left-0 right-0 h-1" style="background-color: <?php echo $color_primario_escapado; ?>;"></div>
            </div>
        </div>

        <!-- Columna de la lista -->
        <div class="lg:col-span-2">
            <?php if ( $mostrar_buscador_flag ) : ?>
                <div class="relative mb-4">
                    <input
                        type="text"
                        class="w-full rounded-lg border border-gray-300 bg-white py-2.5 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2"
                        style="--tw-ring-color: <?php echo $color_primario_escapado; ?>;"
                        placeholder="<?php esc_attr_e( 'Buscar ubicación...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        data-buscador-mapa
                    />
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            <?php endif; ?>

            <div class="space-y-3 overflow-y-auto lg:max-h-[460px] lg:pr-2" data-lista-mapa-contenedor>
                <?php foreach ( $items_visibles as $indice => $item ) :
                    $item_titulo      = $item['titulo'] ?? '';
                    $item_descripcion = $item['descripcion'] ?? '';
                    $item_imagen      = $item['imagen'] ?? '';
                    $item_url         = $item['url'] ?? '';
                    $item_icono       = $item['icono'] ?? '';

                    $etiqueta_contenedor = ! empty( $item_url ) ? 'a' : 'div';
                    $atributos_enlace    = ! empty( $item_url ) ? ' href="' . esc_url( $item_url ) . '"' : '';
                ?>
                    <<?php echo $etiqueta_contenedor . $atributos_enlace; ?>
                        class="group flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition-all duration-200 hover:border-gray-300 hover:shadow-md"
                        data-item-mapa
                        data-titulo-mapa="<?php echo esc_attr( strtolower( $item_titulo ) ); ?>"
                    >
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold text-white" style="background-color: <?php echo $color_primario_escapado; ?>;">
                            <?php echo esc_html( $indice + 1 ); ?>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-3">
                                <?php if ( $mostrar_imagen_flag && ! empty( $item_imagen ) ) : ?>
                                    <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100">
                                        <img src="<?php echo esc_url( $item_imagen ); ?>" alt="<?php echo esc_attr( $item_titulo ); ?>" class="h-full w-full object-cover" loading="lazy" />
                                    </div>
                                <?php endif; ?>

                                <div class="min-w-0 flex-1">
                                    <?php if ( ! empty( $item_titulo ) ) : ?>
                                        <h3 class="text-sm font-semibold text-gray-900 group-hover:text-opacity-80">
                                            <?php echo esc_html( $item_titulo ); ?>
                                        </h3>
                                    <?php endif; ?>

                                    <?php if ( $mostrar_descripcion_flag && ! empty( $item_descripcion ) ) : ?>
                                        <p class="mt-1 text-xs leading-relaxed text-gray-500">
                                            <?php echo esc_html( $item_descripcion ); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ( ! empty( $item_url ) ) : ?>
                            <svg class="mt-1 h-4 w-4 flex-shrink-0 text-gray-400 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        <?php endif; ?>
                    </<?php echo $etiqueta_contenedor; ?>>
                <?php endforeach; ?>
            </div>

            <div class="mt-3 hidden py-8 text-center" data-sin-resultados-mapa>
                <p class="text-sm text-gray-500"><?php esc_html_e( 'No se encontraron ubicaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if ( $mostrar_buscador_flag ) : ?>
<script>
(function() {
    var contenedor    = document.getElementById('<?php echo esc_js( $identificador_unico ); ?>');
    var inputBuscador = contenedor ? contenedor.querySelector('[data-buscador-mapa]') : null;
    if (!inputBuscador) return;

    var itemsMapa              = contenedor.querySelectorAll('[data-item-mapa]');
    var mensajeSinResultados   = contenedor.querySelector('[data-sin-resultados-mapa]');

    inputBuscador.addEventListener('input', function() {
        var termino          = this.value.toLowerCase().trim();
        var contadorVisibles = 0;
        itemsMapa.forEach(function(item) {
            var tituloItem  = item.dataset.tituloMapa || '';
            var esVisible   = !termino || tituloItem.indexOf(termino) !== -1;
            item.style.display = esVisible ? '' : 'none';
            if (esVisible) contadorVisibles++;
        });
        if (mensajeSinResultados) {
            mensajeSinResultados.classList.toggle('hidden', contadorVisibles > 0);
        }
    });
})();
</script>
<?php endif; ?>
