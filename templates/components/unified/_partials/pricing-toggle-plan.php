<?php
/**
 * Partial: Pricing - Toggle Plan
 * Monthly/yearly toggle with pricing cards.
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

$titulo_seccion       = $titulo ?? '';
$subtitulo_seccion    = $subtitulo ?? '';
$color_principal      = $color_primario ?? '#3B82F6';
$lista_planes         = $items ?? [];
$tiene_toggle         = $mostrar_toggle ?? true;
$etiqueta_mensual     = $texto_mensual ?? 'Mensual';
$etiqueta_anual       = $texto_anual ?? 'Anual';
$identificador_unico  = 'pricing-toggle-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-8">
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

        <?php if ( $tiene_toggle ) : ?>
            <div class="flex items-center justify-center gap-4 mb-12" x-data="{ anual: false }">
                <span
                    class="text-sm font-medium transition-colors"
                    :class="anual ? 'text-gray-400' : 'text-gray-900'"
                >
                    <?php echo esc_html( $etiqueta_mensual ); ?>
                </span>
                <button
                    type="button"
                    class="relative inline-flex h-7 w-14 items-center rounded-full transition-colors"
                    :style="anual ? 'background-color: <?php echo esc_attr( $color_principal ); ?>' : 'background-color: #d1d5db'"
                    @click="anual = !anual; document.querySelectorAll('[data-pricing-toggle=<?php echo esc_attr( $identificador_unico ); ?>]').forEach(el => el.classList.toggle('hidden'))"
                >
                    <span
                        class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                        :class="anual ? 'translate-x-8' : 'translate-x-1'"
                    ></span>
                </button>
                <span
                    class="text-sm font-medium transition-colors"
                    :class="anual ? 'text-gray-900' : 'text-gray-400'"
                >
                    <?php echo esc_html( $etiqueta_anual ); ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo min( count( $lista_planes ), 4 ); ?> gap-8">
            <?php foreach ( $lista_planes as $plan ) :
                $nombre_plan          = $plan['nombre'] ?? '';
                $precio_plan          = $plan['precio'] ?? '';
                $periodo_plan         = $plan['periodo'] ?? '';
                $caracteristicas_plan = $plan['caracteristicas'] ?? [];
                $es_destacado         = ! empty( $plan['destacado'] );
                $texto_boton_plan     = $plan['texto_boton'] ?? 'Comenzar';
                $url_boton_plan       = $plan['url_boton'] ?? '#';
            ?>
                <div class="rounded-2xl p-8 flex flex-col <?php echo $es_destacado ? 'shadow-xl border-2 bg-white' : 'shadow-md bg-white border border-gray-200'; ?>"
                    <?php if ( $es_destacado ) : ?>
                        style="border-color: <?php echo esc_attr( $color_principal ); ?>;"
                    <?php endif; ?>
                >
                    <?php if ( $nombre_plan ) : ?>
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            <?php echo esc_html( $nombre_plan ); ?>
                        </h3>
                    <?php endif; ?>

                    <div class="mb-6">
                        <span class="text-4xl font-extrabold text-gray-900">
                            <?php echo esc_html( $precio_plan ); ?>
                        </span>
                        <?php if ( $periodo_plan ) : ?>
                            <span class="text-gray-500 text-sm">/ <?php echo esc_html( $periodo_plan ); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $caracteristicas_plan ) ) : ?>
                        <ul class="space-y-3 mb-8 flex-1">
                            <?php foreach ( $caracteristicas_plan as $caracteristica ) : ?>
                                <li class="flex items-center gap-2 text-gray-600 text-sm">
                                    <svg class="w-4 h-4 shrink-0" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <?php echo esc_html( $caracteristica ); ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                    <a
                        href="<?php echo esc_url( $url_boton_plan ); ?>"
                        class="block w-full text-center py-3 px-6 rounded-lg font-semibold text-white transition-opacity hover:opacity-90"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                    >
                        <?php echo esc_html( $texto_boton_plan ); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
