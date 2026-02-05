<?php
/**
 * Partial: Proceso - Timeline
 * Timeline with alternating left/right content.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Lista de pasos: titulo, descripcion, icono, numero
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_pasos       = $items ?? [];
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-5xl mx-auto">
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

        <div class="relative">
            <!-- Central vertical line -->
            <div
                class="absolute left-4 md:left-1/2 md:-translate-x-0.5 top-0 bottom-0 w-0.5"
                style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.2;"
            ></div>

            <?php foreach ( $lista_pasos as $indice_paso => $paso ) :
                $numero_paso      = $paso['numero'] ?? ( $indice_paso + 1 );
                $titulo_paso      = $paso['titulo'] ?? '';
                $descripcion_paso = $paso['descripcion'] ?? '';
                $icono_paso       = $paso['icono'] ?? '';
                $es_lado_izquierdo = ( $indice_paso % 2 === 0 );
            ?>
                <div class="relative flex items-center mb-12 last:mb-0">
                    <!-- Mobile: always left-aligned. Desktop: alternating -->
                    <div class="w-full flex flex-col md:flex-row <?php echo $es_lado_izquierdo ? 'md:flex-row' : 'md:flex-row-reverse'; ?> items-center">
                        <!-- Content block -->
                        <div class="w-full md:w-5/12 pl-12 md:pl-0 <?php echo $es_lado_izquierdo ? 'md:text-right md:pr-8' : 'md:text-left md:pl-8'; ?>">
                            <?php if ( $titulo_paso ) : ?>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                    <?php echo esc_html( $titulo_paso ); ?>
                                </h3>
                            <?php endif; ?>

                            <?php if ( $descripcion_paso ) : ?>
                                <p class="text-gray-600 leading-relaxed">
                                    <?php echo esc_html( $descripcion_paso ); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Center circle -->
                        <div class="absolute left-0 md:left-1/2 md:-translate-x-1/2 flex items-center justify-center">
                            <div
                                class="w-10 h-10 rounded-full flex items-center justify-center text-white text-sm font-bold shadow-lg z-10"
                                style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                            >
                                <?php if ( $icono_paso ) : ?>
                                    <i class="<?php echo esc_attr( $icono_paso ); ?>"></i>
                                <?php else : ?>
                                    <?php echo esc_html( $numero_paso ); ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Spacer for the other side -->
                        <div class="hidden md:block md:w-5/12"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
