<?php
/**
 * Partial: Pricing - Columnas
 * Side-by-side pricing columns with highlighted recommended plan.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Planes: nombre, precio, periodo, caracteristicas, destacado, texto_boton, url_boton
 *   $mostrar_toggle (bool)   Mostrar toggle mensual/anual
 *   $texto_mensual  (string) Texto para opcion mensual
 *   $texto_anual    (string) Texto para opcion anual
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_planes      = $items ?? [];
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-12">
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>
                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min( count( $lista_planes ), 4 ); ?> gap-8 items-stretch">
            <?php foreach ( $lista_planes as $plan ) :
                $nombre_plan          = $plan['nombre'] ?? '';
                $precio_plan          = $plan['precio'] ?? '';
                $periodo_plan         = $plan['periodo'] ?? '';
                $caracteristicas_plan = $plan['caracteristicas'] ?? [];
                $es_destacado         = ! empty( $plan['destacado'] );
                $texto_boton_plan     = $plan['texto_boton'] ?? 'Elegir plan';
                $url_boton_plan       = $plan['url_boton'] ?? '#';
            ?>
                <div
                    class="relative rounded-2xl p-8 flex flex-col <?php echo $es_destacado ? 'shadow-2xl scale-105 border-2 bg-white' : 'shadow-md bg-white border border-gray-200'; ?>"
                    <?php if ( $es_destacado ) : ?>
                        style="border-color: <?php echo esc_attr( $color_principal ); ?>;"
                    <?php endif; ?>
                >
                    <?php if ( $es_destacado ) : ?>
                        <div
                            class="absolute -top-4 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full text-white text-sm font-semibold"
                            style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                        >
                            <?php echo esc_html__( 'Recomendado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mb-6">
                        <?php if ( $nombre_plan ) : ?>
                            <h3 class="text-xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html( $nombre_plan ); ?>
                            </h3>
                        <?php endif; ?>
                        <div class="flex items-baseline justify-center gap-1">
                            <span class="text-4xl font-extrabold text-gray-900">
                                <?php echo esc_html( $precio_plan ); ?>
                            </span>
                            <?php if ( $periodo_plan ) : ?>
                                <span class="text-gray-500 text-sm">
                                    / <?php echo esc_html( $periodo_plan ); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ( ! empty( $caracteristicas_plan ) ) : ?>
                        <ul class="space-y-3 mb-8 flex-1">
                            <?php foreach ( $caracteristicas_plan as $caracteristica ) : ?>
                                <li class="flex items-start gap-3 text-gray-700">
                                    <svg class="w-5 h-5 shrink-0 mt-0.5" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <span class="text-sm"><?php echo esc_html( $caracteristica ); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <a
                        href="<?php echo esc_url( $url_boton_plan ); ?>"
                        class="block w-full text-center py-3 px-6 rounded-lg font-semibold transition-all duration-200 <?php echo $es_destacado ? 'text-white hover:opacity-90' : 'border-2 hover:opacity-80'; ?>"
                        style="<?php echo $es_destacado
                            ? 'background-color: ' . esc_attr( $color_principal ) . ';'
                            : 'border-color: ' . esc_attr( $color_principal ) . '; color: ' . esc_attr( $color_principal ) . ';'; ?>"
                    >
                        <?php echo esc_html( $texto_boton_plan ); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
