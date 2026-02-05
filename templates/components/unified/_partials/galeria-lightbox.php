<?php
/**
 * Partial: Galeria - Lightbox
 * Grid with lightbox on click.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Imagenes: imagen, titulo, descripcion
 *   $columnas       (int)    Numero de columnas
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_imagenes    = $items ?? [];
$numero_columnas   = $columnas ?? 3;
$identificador_lightbox = 'galeria-lightbox-' . wp_unique_id();
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

        <!-- Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-<?php echo intval( $numero_columnas ); ?> gap-4">
            <?php foreach ( $lista_imagenes as $indice_imagen => $imagen ) :
                $url_imagen         = $imagen['imagen'] ?? '';
                $titulo_imagen      = $imagen['titulo'] ?? '';
                $descripcion_imagen = $imagen['descripcion'] ?? '';
            ?>
                <div
                    class="group cursor-pointer relative overflow-hidden rounded-xl shadow-md hover:shadow-xl transition-shadow"
                    onclick="(function(){
                        var modal = document.getElementById('<?php echo esc_js( $identificador_lightbox ); ?>-modal');
                        var img = modal.querySelector('img');
                        var titulo = modal.querySelector('.lightbox-titulo');
                        var desc = modal.querySelector('.lightbox-descripcion');
                        img.src = '<?php echo esc_js( esc_url( $url_imagen ) ); ?>';
                        img.alt = '<?php echo esc_js( esc_attr( $titulo_imagen ) ); ?>';
                        titulo.textContent = '<?php echo esc_js( $titulo_imagen ); ?>';
                        desc.textContent = '<?php echo esc_js( $descripcion_imagen ); ?>';
                        modal.classList.remove('hidden');
                        modal.classList.add('flex');
                        document.body.style.overflow = 'hidden';
                    })()"
                >
                    <?php if ( $url_imagen ) : ?>
                        <img
                            src="<?php echo esc_url( $url_imagen ); ?>"
                            alt="<?php echo esc_attr( $titulo_imagen ); ?>"
                            class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-300"
                            loading="lazy"
                        />
                    <?php else : ?>
                        <div class="w-full h-64 bg-gray-200 flex items-center justify-center">
                            <svg class="w-12 h-12 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <!-- Hover overlay with zoom icon -->
                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"/>
                        </svg>
                    </div>

                    <?php if ( $titulo_imagen ) : ?>
                        <div class="absolute bottom-0 left-0 right-0 p-3 bg-gradient-to-t from-black/60 to-transparent">
                            <p class="text-white text-sm font-medium"><?php echo esc_html( $titulo_imagen ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Lightbox modal -->
        <div
            id="<?php echo esc_attr( $identificador_lightbox ); ?>-modal"
            class="hidden fixed inset-0 z-50 items-center justify-center bg-black/90 p-4"
            onclick="(function(e){
                if(e.target===this || e.target.closest('.lightbox-cerrar')){
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                    document.body.style.overflow='';
                }
            }).call(this, event)"
        >
            <button type="button" class="lightbox-cerrar absolute top-4 right-4 text-white hover:text-gray-300 transition-colors z-10">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <div class="max-w-4xl max-h-[90vh] text-center">
                <img src="" alt="" class="max-w-full max-h-[75vh] object-contain rounded-lg mx-auto" />
                <p class="lightbox-titulo text-white font-semibold mt-4 text-lg"></p>
                <p class="lightbox-descripcion text-gray-300 text-sm mt-1"></p>
            </div>
        </div>
    </div>
</section>
