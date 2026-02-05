<?php
/**
 * Partial: Navegacion - Pills
 * Pill-style nav buttons.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Opciones: label, url, icono, activo
 */

$titulo_seccion  = $titulo ?? '';
$color_principal = $color_primario ?? '#3B82F6';
$lista_opciones  = $items ?? [];
?>

<nav class="py-4 px-4 bg-white">
    <div class="max-w-6xl mx-auto">
        <?php if ( $titulo_seccion ) : ?>
            <h2 class="sr-only"><?php echo esc_html( $titulo_seccion ); ?></h2>
        <?php endif; ?>

        <div class="flex flex-wrap gap-2 justify-center" role="tablist">
            <?php foreach ( $lista_opciones as $opcion ) :
                $etiqueta_opcion = $opcion['label'] ?? '';
                $url_opcion      = $opcion['url'] ?? '#';
                $icono_opcion    = $opcion['icono'] ?? '';
                $esta_activa     = ! empty( $opcion['activo'] );
            ?>
                <a
                    href="<?php echo esc_url( $url_opcion ); ?>"
                    role="tab"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full text-sm font-medium transition-all duration-200 <?php echo $esta_activa ? 'text-white shadow-md' : 'text-gray-600 bg-gray-100 hover:bg-gray-200'; ?>"
                    <?php if ( $esta_activa ) : ?>
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                        aria-selected="true"
                    <?php else : ?>
                        aria-selected="false"
                    <?php endif; ?>
                >
                    <?php if ( $icono_opcion ) : ?>
                        <i class="<?php echo esc_attr( $icono_opcion ); ?>"></i>
                    <?php endif; ?>
                    <?php echo esc_html( $etiqueta_opcion ); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
