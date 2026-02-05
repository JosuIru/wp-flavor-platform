<?php
/**
 * Partial: Estadísticas con iconos prominentes
 *
 * Contadores de estadísticas con iconos grandes y prominentes
 * encima de cada valor numérico y etiqueta.
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

$total_items     = count( $items );
$clases_columnas = $total_items <= 2
    ? 'grid-cols-1 sm:grid-cols-2'
    : ( $total_items === 3
        ? 'grid-cols-1 sm:grid-cols-3'
        : 'grid-cols-2 sm:grid-cols-2 lg:grid-cols-4'
    );
?>

<div class="w-full rounded-2xl p-8" style="background-color: <?php echo $color_fondo_escapado; ?>;">
    <?php if ( ! empty( $titulo ) || ! empty( $subtitulo ) ) : ?>
        <div class="mb-10 text-center">
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

    <div class="grid gap-8 <?php echo esc_attr( $clases_columnas ); ?>">
        <?php foreach ( $items as $item ) :
            $item_valor    = $item['valor'] ?? '0';
            $item_etiqueta = $item['etiqueta'] ?? '';
            $item_icono    = $item['icono'] ?? 'dashicons-chart-bar';
        ?>
            <div class="group flex flex-col items-center text-center transition-transform duration-300 hover:-translate-y-1">
                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-2xl shadow-md transition-shadow duration-300 group-hover:shadow-lg" style="background-color: <?php echo $color_primario_escapado; ?>;">
                    <span class="dashicons <?php echo esc_attr( $item_icono ); ?>" style="color: #ffffff; font-size: 36px; width: 36px; height: 36px;"></span>
                </div>

                <span class="text-3xl font-extrabold tracking-tight text-gray-900 md:text-4xl" style="color: <?php echo $color_primario_escapado; ?>;">
                    <?php echo esc_html( $item_valor ); ?>
                </span>

                <?php if ( ! empty( $item_etiqueta ) ) : ?>
                    <span class="mt-2 text-sm font-medium text-gray-500">
                        <?php echo esc_html( $item_etiqueta ); ?>
                    </span>
                <?php endif; ?>

                <div class="mt-3 h-1 w-12 rounded-full opacity-30" style="background-color: <?php echo $color_primario_escapado; ?>;"></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
