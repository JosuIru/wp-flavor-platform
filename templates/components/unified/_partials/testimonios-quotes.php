<?php
/**
 * Partial: Testimonios - Quotes
 * Large quote format, one at a time with navigation.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Testimonios: nombre, cargo, texto, foto, valoracion
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_testimonios = $items ?? [];
$identificador_seccion = 'testimonios-quotes-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-4xl mx-auto">
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

        <div id="<?php echo esc_attr( $identificador_seccion ); ?>" class="relative">
            <?php foreach ( $lista_testimonios as $indice_testimonio => $testimonio ) :
                $nombre_autor     = $testimonio['nombre'] ?? '';
                $cargo_autor      = $testimonio['cargo'] ?? '';
                $texto_testimonio = $testimonio['texto'] ?? '';
                $foto_autor       = $testimonio['foto'] ?? '';
                $valoracion_autor = intval( $testimonio['valoracion'] ?? 5 );
                $es_visible       = ( $indice_testimonio === 0 );
            ?>
                <div
                    class="quote-slide text-center <?php echo $es_visible ? '' : 'hidden'; ?>"
                    data-slide-index="<?php echo esc_attr( $indice_testimonio ); ?>"
                >
                    <!-- Large quote icon -->
                    <svg class="w-12 h-12 mx-auto mb-6 opacity-20" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zM0 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151C7.546 6.068 5.983 8.789 5.983 11H10v10H0z"/>
                    </svg>

                    <!-- Quote text -->
                    <?php if ( $texto_testimonio ) : ?>
                        <blockquote class="text-xl md:text-2xl lg:text-3xl text-gray-800 leading-relaxed font-light mb-8 italic">
                            &ldquo;<?php echo esc_html( $texto_testimonio ); ?>&rdquo;
                        </blockquote>
                    <?php endif; ?>

                    <!-- Stars -->
                    <?php if ( $valoracion_autor > 0 ) : ?>
                        <div class="flex gap-1 justify-center mb-6">
                            <?php for ( $estrella = 1; $estrella <= 5; $estrella++ ) : ?>
                                <svg class="w-5 h-5 <?php echo $estrella <= $valoracion_autor ? '' : 'opacity-30'; ?>"
                                     style="color: <?php echo esc_attr( $color_principal ); ?>;"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Author info -->
                    <div class="flex items-center justify-center gap-4">
                        <?php if ( $foto_autor ) : ?>
                            <img
                                src="<?php echo esc_url( $foto_autor ); ?>"
                                alt="<?php echo esc_attr( $nombre_autor ); ?>"
                                class="w-14 h-14 rounded-full object-cover shadow-md"
                                loading="lazy"
                            />
                        <?php endif; ?>
                        <div class="text-left">
                            <?php if ( $nombre_autor ) : ?>
                                <div class="font-bold text-gray-900">
                                    <?php echo esc_html( $nombre_autor ); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $cargo_autor ) : ?>
                                <div class="text-sm" style="color: <?php echo esc_attr( $color_principal ); ?>;">
                                    <?php echo esc_html( $cargo_autor ); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Navigation dots -->
            <?php if ( count( $lista_testimonios ) > 1 ) : ?>
                <div class="flex justify-center gap-2 mt-8">
                    <?php foreach ( $lista_testimonios as $indice_testimonio => $testimonio ) : ?>
                        <button
                            type="button"
                            class="w-3 h-3 rounded-full transition-all duration-200"
                            style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: <?php echo $indice_testimonio === 0 ? '1' : '0.3'; ?>;"
                            onclick="(function(){
                                var contenedor = document.getElementById('<?php echo esc_js( $identificador_seccion ); ?>');
                                contenedor.querySelectorAll('.quote-slide').forEach(function(s){s.classList.add('hidden')});
                                contenedor.querySelectorAll('.quote-slide')[<?php echo intval( $indice_testimonio ); ?>].classList.remove('hidden');
                                contenedor.parentElement.querySelectorAll('button[class*=rounded-full]').forEach(function(b){b.style.opacity='0.3'});
                                this.style.opacity='1';
                            }).call(this)"
                            aria-label="<?php echo esc_attr( sprintf( __( 'Testimonio %d', 'flavor-chat-ia' ), $indice_testimonio + 1 ) ); ?>"
                        ></button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
