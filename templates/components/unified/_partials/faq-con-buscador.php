<?php
/**
 * Partial: FAQ - Con Buscador
 * FAQ with search bar at top.
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
$tiene_buscador    = $mostrar_buscador ?? true;
$identificador_faq = 'faq-buscador-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-3xl mx-auto">
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

        <?php if ( $tiene_buscador ) : ?>
            <div class="mb-8">
                <div class="relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input
                        type="text"
                        placeholder="<?php echo esc_attr__( 'Buscar en las preguntas frecuentes...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                        class="w-full pl-12 pr-4 py-4 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 text-gray-900 placeholder-gray-400"
                        style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                        onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                        onblur="this.style.borderColor=''"
                        oninput="(function(input){
                            var termino = input.value.toLowerCase();
                            var contenedor = document.getElementById('<?php echo esc_js( $identificador_faq ); ?>');
                            contenedor.querySelectorAll('.faq-item').forEach(function(item){
                                var texto = item.textContent.toLowerCase();
                                item.style.display = texto.includes(termino) ? '' : 'none';
                            });
                        })(this)"
                    />
                </div>
            </div>
        <?php endif; ?>

        <div id="<?php echo esc_attr( $identificador_faq ); ?>" class="space-y-3">
            <?php foreach ( $lista_preguntas as $indice_pregunta => $pregunta_item ) :
                $texto_pregunta    = $pregunta_item['pregunta'] ?? '';
                $texto_respuesta   = $pregunta_item['respuesta'] ?? '';
                $identificador_item = $identificador_faq . '-item-' . $indice_pregunta;
            ?>
                <div class="faq-item border border-gray-200 rounded-xl overflow-hidden">
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
