<?php
/**
 * Partial: Proceso - Pasos Horizontal
 * Horizontal step process with numbered circles and connecting lines.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Lista de pasos: titulo, descripcion, icono, numero
 */

$titulo_seccion   = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal  = $color_primario ?? '#3B82F6';
$lista_pasos      = $items ?? [];
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

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-8">
            <?php foreach ( $lista_pasos as $indice_paso => $paso ) :
                $numero_paso     = $paso['numero'] ?? ( $indice_paso + 1 );
                $titulo_paso     = $paso['titulo'] ?? '';
                $descripcion_paso = $paso['descripcion'] ?? '';
                $icono_paso      = $paso['icono'] ?? '';
                $es_ultimo_paso  = ( $indice_paso === count( $lista_pasos ) - 1 );
            ?>
                <div class="flex-1 flex flex-col items-center text-center relative">
                    <!-- Numbered circle -->
                    <div
                        class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl font-bold mb-4 relative z-10 shadow-lg"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                    >
                        <?php if ( $icono_paso ) : ?>
                            <i class="<?php echo esc_attr( $icono_paso ); ?>"></i>
                        <?php else : ?>
                            <?php echo esc_html( $numero_paso ); ?>
                        <?php endif; ?>
                    </div>

                    <!-- Connecting line (hidden on last item) -->
                    <?php if ( ! $es_ultimo_paso ) : ?>
                        <div
                            class="hidden md:block absolute top-8 left-[calc(50%+2rem)] w-[calc(100%-4rem)] h-0.5"
                            style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.3;"
                        ></div>
                    <?php endif; ?>

                    <?php if ( $titulo_paso ) : ?>
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">
                            <?php echo esc_html( $titulo_paso ); ?>
                        </h3>
                    <?php endif; ?>

                    <?php if ( $descripcion_paso ) : ?>
                        <p class="text-sm text-gray-600 max-w-xs">
                            <?php echo esc_html( $descripcion_paso ); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
