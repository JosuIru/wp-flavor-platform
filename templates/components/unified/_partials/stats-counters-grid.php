<?php
/**
 * Partial: Grid de contadores estadísticos
 *
 * Tarjetas de estadísticas en grid de 2x2 o 3 columnas,
 * con valor numérico grande y etiqueta descriptiva.
 *
 * Variables disponibles:
 *   $titulo, $subtitulo, $color_primario, $color_fondo,
 *   $items (valor, etiqueta, icono)
 */

if ( empty( $items ) || ! is_array( $items ) ) {
    return;
}

$color_primario_escapado = ! empty( $color_primario ) ? esc_attr( $color_primario ) : '#3b82f6';
$color_fondo_escapado    = ! empty( $color_fondo ) ? esc_attr( $color_fondo ) : '#ffffff';

$total_items    = count( $items );
$clases_columnas = $total_items <= 4 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3';
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

    <div class="grid gap-5 <?php echo esc_attr( $clases_columnas ); ?>">
        <?php foreach ( $items as $item ) :
            $item_valor    = $item['valor'] ?? '0';
            $item_etiqueta = $item['etiqueta'] ?? '';
            $item_icono    = $item['icono'] ?? '';
        ?>
            <div class="relative overflow-hidden rounded-xl border border-gray-200 p-6 shadow-sm transition-all duration-300 hover:shadow-md" style="background-color: <?php echo $color_fondo_escapado; ?>;">
                <div class="absolute right-0 top-0 h-24 w-24 -translate-y-4 translate-x-4 rounded-full opacity-10" style="background-color: <?php echo $color_primario_escapado; ?>;"></div>

                <div class="relative">
                    <?php if ( ! empty( $item_icono ) ) : ?>
                        <div class="mb-3 flex h-10 w-10 items-center justify-center rounded-lg" style="background-color: <?php echo $color_primario_escapado; ?>1a;">
                            <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: <?php echo $color_primario_escapado; ?>; font-size: 20px; width: 20px; height: 20px;"></span>
                        </div>
                    <?php endif; ?>

                    <span class="block text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl" style="color: <?php echo $color_primario_escapado; ?>;">
                        <?php echo esc_html( $item_valor ); ?>
                    </span>

                    <?php if ( ! empty( $item_etiqueta ) ) : ?>
                        <span class="mt-2 block text-sm font-medium text-gray-500">
                            <?php echo esc_html( $item_etiqueta ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
