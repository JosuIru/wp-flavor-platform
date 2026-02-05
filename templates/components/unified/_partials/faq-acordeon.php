<?php
/**
 * Partial: FAQ - Acordeon
 * Collapsible accordion FAQ.
 *
 * Variables esperadas:
 *   $titulo          (string) Titulo de la seccion
 *   $subtitulo       (string) Subtitulo de la seccion
 *   $color_primario  (string) Color primario en formato hex
 *   $items           (array)  Preguntas: pregunta, respuesta
 *   $mostrar_buscador (bool)  Mostrar barra de busqueda
 */

$titulo_seccion     = $titulo ?? '';
$subtitulo_seccion  = $subtitulo ?? '';
$color_principal    = $color_primario ?? '#3B82F6';
$lista_preguntas    = $items ?? [];
$identificador_faq  = 'faq-acordeon-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-3xl mx-auto">
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

        <div class="space-y-3" id="<?php echo esc_attr( $identificador_faq ); ?>">
            <?php foreach ( $lista_preguntas as $indice_pregunta => $pregunta_item ) :
                $texto_pregunta  = $pregunta_item['pregunta'] ?? '';
                $texto_respuesta = $pregunta_item['respuesta'] ?? '';
                $identificador_item = $identificador_faq . '-item-' . $indice_pregunta;
            ?>
                <div class="border border-gray-200 rounded-xl overflow-hidden">
                    <button
                        type="button"
                        class="w-full flex items-center justify-between p-5 text-left bg-white hover:bg-gray-50 transition-colors"
                        onclick="(function(boton){
                            var contenido = document.getElementById('<?php echo esc_js( $identificador_item ); ?>');
                            var icono = boton.querySelector('svg');
                            if(contenido.classList.contains('hidden')){
                                contenido.classList.remove('hidden');
                                icono.style.transform='rotate(180deg)';
                            } else {
                                contenido.classList.add('hidden');
                                icono.style.transform='rotate(0deg)';
                            }
                        })(this)"
                    >
                        <span class="font-semibold text-gray-900 pr-4">
                            <?php echo esc_html( $texto_pregunta ); ?>
                        </span>
                        <svg class="w-5 h-5 shrink-0 text-gray-500 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="<?php echo esc_attr( $identificador_item ); ?>" class="hidden">
                        <div class="px-5 pb-5 text-gray-600 leading-relaxed border-t border-gray-100 pt-4">
                            <?php echo esc_html( $texto_respuesta ); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
