<?php
/**
 * Partial: Pricing - Comparativa
 * Feature comparison table.
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

// Recopilar todas las caracteristicas unicas de todos los planes
$todas_las_caracteristicas = [];
foreach ( $lista_planes as $plan ) {
    $caracteristicas_del_plan = $plan['caracteristicas'] ?? [];
    foreach ( $caracteristicas_del_plan as $caracteristica ) {
        if ( ! in_array( $caracteristica, $todas_las_caracteristicas, true ) ) {
            $todas_las_caracteristicas[] = $caracteristica;
        }
    }
}
?>

<section class="py-16 px-4 bg-white">
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

        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left p-4 bg-gray-50 border-b border-gray-200 min-w-[200px]">
                            <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html__( 'Caracteristicas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </span>
                        </th>
                        <?php foreach ( $lista_planes as $plan ) :
                            $es_destacado = ! empty( $plan['destacado'] );
                        ?>
                            <th class="p-4 text-center border-b min-w-[180px] <?php echo $es_destacado ? 'border-2 border-b rounded-t-xl' : 'bg-gray-50 border-gray-200'; ?>"
                                <?php if ( $es_destacado ) : ?>
                                    style="border-color: <?php echo esc_attr( $color_principal ); ?>; background-color: <?php echo esc_attr( $color_principal ); ?>10;"
                                <?php endif; ?>
                            >
                                <div class="font-bold text-gray-900 text-lg">
                                    <?php echo esc_html( $plan['nombre'] ?? '' ); ?>
                                </div>
                                <div class="mt-1">
                                    <span class="text-2xl font-extrabold text-gray-900"><?php echo esc_html( $plan['precio'] ?? '' ); ?></span>
                                    <?php if ( ! empty( $plan['periodo'] ) ) : ?>
                                        <span class="text-gray-500 text-xs">/ <?php echo esc_html( $plan['periodo'] ); ?></span>
                                    <?php endif; ?>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $todas_las_caracteristicas as $indice_caracteristica => $caracteristica ) : ?>
                        <tr class="<?php echo ( $indice_caracteristica % 2 === 0 ) ? 'bg-white' : 'bg-gray-50'; ?>">
                            <td class="p-4 text-sm text-gray-700 border-b border-gray-100">
                                <?php echo esc_html( $caracteristica ); ?>
                            </td>
                            <?php foreach ( $lista_planes as $plan ) :
                                $caracteristicas_del_plan = $plan['caracteristicas'] ?? [];
                                $tiene_caracteristica     = in_array( $caracteristica, $caracteristicas_del_plan, true );
                                $es_destacado             = ! empty( $plan['destacado'] );
                            ?>
                                <td class="p-4 text-center border-b border-gray-100 <?php echo $es_destacado ? 'border-x-2' : ''; ?>"
                                    <?php if ( $es_destacado ) : ?>
                                        style="border-color: <?php echo esc_attr( $color_principal ); ?>;"
                                    <?php endif; ?>
                                >
                                    <?php if ( $tiene_caracteristica ) : ?>
                                        <svg class="w-5 h-5 mx-auto" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg class="w-5 h-5 mx-auto text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                        </svg>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                    <!-- CTA row -->
                    <tr>
                        <td class="p-4"></td>
                        <?php foreach ( $lista_planes as $plan ) :
                            $es_destacado     = ! empty( $plan['destacado'] );
                            $texto_boton_plan = $plan['texto_boton'] ?? 'Elegir';
                            $url_boton_plan   = $plan['url_boton'] ?? '#';
                        ?>
                            <td class="p-4 text-center <?php echo $es_destacado ? 'border-x-2 border-b-2 rounded-b-xl' : ''; ?>"
                                <?php if ( $es_destacado ) : ?>
                                    style="border-color: <?php echo esc_attr( $color_principal ); ?>;"
                                <?php endif; ?>
                            >
                                <a
                                    href="<?php echo esc_url( $url_boton_plan ); ?>"
                                    class="inline-block py-2 px-6 rounded-lg font-semibold text-sm transition-opacity hover:opacity-90 <?php echo $es_destacado ? 'text-white' : 'border-2'; ?>"
                                    style="<?php echo $es_destacado
                                        ? 'background-color: ' . esc_attr( $color_principal ) . ';'
                                        : 'border-color: ' . esc_attr( $color_principal ) . '; color: ' . esc_attr( $color_principal ) . ';'; ?>"
                                >
                                    <?php echo esc_html( $texto_boton_plan ); ?>
                                </a>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
