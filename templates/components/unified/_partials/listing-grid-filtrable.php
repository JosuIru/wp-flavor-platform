<?php
/**
 * Partial: Grid filtrable con botones de filtro
 *
 * Grid de tarjetas con botones de filtro en la parte superior
 * y opcionalmente un buscador. Los filtros se aplican vía JS
 * usando data-attributes.
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
$numero_columnas          = ! empty( $columnas ) ? absint( $columnas ) : 3;
$limite_items             = ! empty( $limite ) ? absint( $limite ) : 0;
$mostrar_imagen_flag      = isset( $mostrar_imagen ) ? (bool) $mostrar_imagen : true;
$mostrar_descripcion_flag = isset( $mostrar_descripcion ) ? (bool) $mostrar_descripcion : true;
$mostrar_filtros_flag     = isset( $mostrar_filtros ) ? (bool) $mostrar_filtros : true;
$mostrar_buscador_flag    = isset( $mostrar_buscador ) ? (bool) $mostrar_buscador : false;
$lista_filtros            = ! empty( $filtros ) && is_array( $filtros ) ? $filtros : [];

$mapa_columnas_grid = [
    1 => 'sm:grid-cols-1',
    2 => 'sm:grid-cols-1 md:grid-cols-2',
    3 => 'sm:grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
    4 => 'sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4',
];
$clases_columnas = $mapa_columnas_grid[ $numero_columnas ] ?? $mapa_columnas_grid[3];

$items_visibles      = $limite_items > 0 ? array_slice( $items, 0, $limite_items ) : $items;
$identificador_unico = 'listing-filtrable-' . wp_unique_id();
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

    <?php if ( $mostrar_filtros_flag || $mostrar_buscador_flag ) : ?>
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
            <?php if ( $mostrar_filtros_flag && ! empty( $lista_filtros ) ) : ?>
                <div class="flex flex-wrap gap-2" data-filtros-contenedor>
                    <button
                        type="button"
                        class="filtro-btn rounded-full px-4 py-2 text-sm font-medium transition-all duration-200 border"
                        style="background-color: <?php echo $color_primario_escapado; ?>; color: #fff; border-color: <?php echo $color_primario_escapado; ?>;"
                        data-filtro="todos"
                    >
                        <?php esc_html_e( 'Todos', 'flavor-chat-ia' ); ?>
                    </button>
                    <?php foreach ( $lista_filtros as $filtro ) : ?>
                        <button
                            type="button"
                            class="filtro-btn rounded-full border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-all duration-200 hover:border-gray-400 hover:bg-gray-50"
                            data-filtro="<?php echo esc_attr( $filtro['valor'] ?? '' ); ?>"
                        >
                            <?php echo esc_html( $filtro['label'] ?? '' ); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ( $mostrar_buscador_flag ) : ?>
                <div class="relative w-full sm:w-64">
                    <input
                        type="text"
                        class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 transition-colors duration-200 focus:border-transparent focus:outline-none focus:ring-2"
                        style="--tw-ring-color: <?php echo $color_primario_escapado; ?>;"
                        placeholder="<?php esc_attr_e( 'Buscar...', 'flavor-chat-ia' ); ?>"
                        data-buscador-input
                    />
                    <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-6 <?php echo esc_attr( $clases_columnas ); ?>" data-items-contenedor>
        <?php foreach ( $items_visibles as $item ) :
            $item_titulo      = $item['titulo'] ?? '';
            $item_descripcion = $item['descripcion'] ?? '';
            $item_imagen      = $item['imagen'] ?? '';
            $item_url         = $item['url'] ?? '';
            $item_icono       = $item['icono'] ?? '';
            $item_categoria   = $item['categoria'] ?? $item['filtro'] ?? '';

            $etiqueta_contenedor = ! empty( $item_url ) ? 'a' : 'div';
            $atributos_enlace    = ! empty( $item_url ) ? ' href="' . esc_url( $item_url ) . '"' : '';
        ?>
            <<?php echo $etiqueta_contenedor . $atributos_enlace; ?>
                class="group flex flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-300 hover:shadow-lg"
                data-item-filtrable
                data-categoria="<?php echo esc_attr( $item_categoria ); ?>"
                data-titulo="<?php echo esc_attr( strtolower( $item_titulo ) ); ?>"
            >
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
                        <p class="mt-2 flex-1 text-sm leading-relaxed text-gray-600">
                            <?php echo esc_html( $item_descripcion ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </<?php echo $etiqueta_contenedor; ?>>
        <?php endforeach; ?>
    </div>

    <div class="mt-8 hidden py-12 text-center" data-sin-resultados>
        <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="mt-4 text-sm text-gray-500"><?php esc_html_e( 'No se encontraron resultados.', 'flavor-chat-ia' ); ?></p>
    </div>
</div>

<script>
(function() {
    var contenedor = document.getElementById('<?php echo esc_js( $identificador_unico ); ?>');
    if (!contenedor) return;

    var botonesFiltro    = contenedor.querySelectorAll('[data-filtro]');
    var itemsFiltrables  = contenedor.querySelectorAll('[data-item-filtrable]');
    var inputBuscador    = contenedor.querySelector('[data-buscador-input]');
    var mensajeSinResultados = contenedor.querySelector('[data-sin-resultados]');
    var filtroActivo     = 'todos';
    var terminoBusqueda  = '';

    function aplicarFiltros() {
        var contadorVisibles = 0;
        itemsFiltrables.forEach(function(item) {
            var coincideFiltro   = filtroActivo === 'todos' || item.dataset.categoria === filtroActivo;
            var coincideBusqueda = !terminoBusqueda || (item.dataset.titulo && item.dataset.titulo.indexOf(terminoBusqueda) !== -1);
            var esVisible        = coincideFiltro && coincideBusqueda;
            item.style.display   = esVisible ? '' : 'none';
            if (esVisible) contadorVisibles++;
        });
        if (mensajeSinResultados) {
            mensajeSinResultados.classList.toggle('hidden', contadorVisibles > 0);
        }
    }

    botonesFiltro.forEach(function(boton) {
        boton.addEventListener('click', function() {
            filtroActivo = this.dataset.filtro;
            botonesFiltro.forEach(function(btn) {
                btn.style.backgroundColor = '';
                btn.style.color           = '';
                btn.style.borderColor     = '#d1d5db';
                btn.classList.add('bg-white', 'text-gray-700');
            });
            this.style.backgroundColor = '<?php echo $color_primario_escapado; ?>';
            this.style.color           = '#ffffff';
            this.style.borderColor     = '<?php echo $color_primario_escapado; ?>';
            this.classList.remove('bg-white', 'text-gray-700');
            aplicarFiltros();
        });
    });

    if (inputBuscador) {
        inputBuscador.addEventListener('input', function() {
            terminoBusqueda = this.value.toLowerCase().trim();
            aplicarFiltros();
        });
    }
})();
</script>
