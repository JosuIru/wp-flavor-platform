<?php
/**
 * Partial: Contadores estadísticos horizontales
 *
 * Fila horizontal de contadores con números grandes y etiquetas.
 * Ideal para mostrar KPIs o métricas destacadas.
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

$total_items = count( $items );
?>

<div class="w-full rounded-2xl p-8 shadow-sm" style="background-color: <?php echo $color_fondo_escapado; ?>;">
    <?php if ( ! empty( $titulo ) || ! empty( $subtitulo ) ) : ?>
        <div class="mb-8 text-center">
            <?php if ( ! empty( $titulo ) ) : ?>
                <h2 class="text-2xl font-bold md:text-3xl" style="color: <?php echo $color_primario_escapado; ?>;">
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

    <div class="flex flex-col items-center justify-center gap-8 sm:flex-row sm:flex-wrap md:gap-12 lg:gap-16">
        <?php foreach ( $items as $indice => $item ) :
            $item_valor    = $item['valor'] ?? '0';
            $item_etiqueta = $item['etiqueta'] ?? '';
            $item_icono    = $item['icono'] ?? '';
        ?>
            <div class="flex flex-col items-center text-center">
                <?php if ( ! empty( $item_icono ) ) : ?>
                    <div class="mb-2 flex h-10 w-10 items-center justify-center rounded-full" style="background-color: <?php echo $color_primario_escapado; ?>1a;">
                        <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: <?php echo $color_primario_escapado; ?>; font-size: 20px; width: 20px; height: 20px;"></span>
                    </div>
                <?php endif; ?>

                <span class="text-4xl font-extrabold tracking-tight md:text-5xl" style="color: <?php echo $color_primario_escapado; ?>;">
                    <?php echo esc_html( $item_valor ); ?>
                </span>

                <?php if ( ! empty( $item_etiqueta ) ) : ?>
                    <span class="mt-2 text-sm font-medium uppercase tracking-wider text-gray-500">
                        <?php echo esc_html( $item_etiqueta ); ?>
                    </span>
                <?php endif; ?>
            </div>

            <?php if ( $indice < $total_items - 1 ) : ?>
                <div class="hidden h-16 w-px bg-gray-200 sm:block" aria-hidden="true"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
