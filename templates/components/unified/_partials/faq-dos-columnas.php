<?php
/**
 * Partial: FAQ - Dos Columnas
 * Two-column FAQ layout.
 *
 * Variables esperadas:
 *   $titulo          (string) Titulo de la seccion
 *   $subtitulo       (string) Subtitulo de la seccion
 *   $color_primario  (string) Color primario en formato hex
 *   $items           (array)  Preguntas: pregunta, respuesta
 *   $mostrar_buscador (bool)  Mostrar barra de busqueda
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_preguntas   = $items ?? [];

// Dividir preguntas en dos columnas
$total_preguntas       = count( $lista_preguntas );
$mitad_preguntas       = ceil( $total_preguntas / 2 );
$columna_izquierda     = array_slice( $lista_preguntas, 0, $mitad_preguntas );
$columna_derecha       = array_slice( $lista_preguntas, $mitad_preguntas );
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left column -->
            <div class="space-y-6">
                <?php foreach ( $columna_izquierda as $pregunta_item ) :
                    $texto_pregunta  = $pregunta_item['pregunta'] ?? '';
                    $texto_respuesta = $pregunta_item['respuesta'] ?? '';
                ?>
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <?php if ( $texto_pregunta ) : ?>
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-start gap-3">
                                <span
                                    class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                    style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.15;"
                                >
                                    <span class="text-xs font-bold" style="color: <?php echo esc_attr( $color_principal ); ?>;">?</span>
                                </span>
                                <?php echo esc_html( $texto_pregunta ); ?>
                            </h3>
                        <?php endif; ?>
                        <?php if ( $texto_respuesta ) : ?>
                            <p class="text-gray-600 leading-relaxed pl-9">
                                <?php echo esc_html( $texto_respuesta ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right column -->
            <div class="space-y-6">
                <?php foreach ( $columna_derecha as $pregunta_item ) :
                    $texto_pregunta  = $pregunta_item['pregunta'] ?? '';
                    $texto_respuesta = $pregunta_item['respuesta'] ?? '';
                ?>
                    <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                        <?php if ( $texto_pregunta ) : ?>
                            <h3 class="font-semibold text-gray-900 mb-3 flex items-start gap-3">
                                <span
                                    class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 mt-0.5"
                                    style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.15;"
                                >
                                    <span class="text-xs font-bold" style="color: <?php echo esc_attr( $color_principal ); ?>;">?</span>
                                </span>
                                <?php echo esc_html( $texto_pregunta ); ?>
                            </h3>
                        <?php endif; ?>
                        <?php if ( $texto_respuesta ) : ?>
                            <p class="text-gray-600 leading-relaxed pl-9">
                                <?php echo esc_html( $texto_respuesta ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
