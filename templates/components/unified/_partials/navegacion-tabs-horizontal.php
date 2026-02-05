<?php
/**
 * Partial: Navegacion - Tabs Horizontal
 * Horizontal tabs.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Pestanas: label, url, icono, activo
 */

$titulo_seccion  = $titulo ?? '';
$color_principal = $color_primario ?? '#3B82F6';
$lista_pestanias = $items ?? [];
?>

<nav class="bg-white border-b border-gray-200">
    <div class="max-w-6xl mx-auto px-4">
        <?php if ( $titulo_seccion ) : ?>
            <h2 class="sr-only"><?php echo esc_html( $titulo_seccion ); ?></h2>
        <?php endif; ?>

        <div class="flex overflow-x-auto -mb-px" role="tablist">
            <?php foreach ( $lista_pestanias as $pestania ) :
                $etiqueta_pestania = $pestania['label'] ?? '';
                $url_pestania      = $pestania['url'] ?? '#';
                $icono_pestania    = $pestania['icono'] ?? '';
                $esta_activa       = ! empty( $pestania['activo'] );
            ?>
                <a
                    href="<?php echo esc_url( $url_pestania ); ?>"
                    role="tab"
                    class="flex items-center gap-2 px-5 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors <?php echo $esta_activa ? 'text-gray-900' : 'text-gray-500 border-transparent hover:text-gray-700 hover:border-gray-300'; ?>"
                    <?php if ( $esta_activa ) : ?>
                        style="border-color: <?php echo esc_attr( $color_principal ); ?>; color: <?php echo esc_attr( $color_principal ); ?>;"
                        aria-selected="true"
                    <?php else : ?>
                        aria-selected="false"
                    <?php endif; ?>
                >
                    <?php if ( $icono_pestania ) : ?>
                        <i class="<?php echo esc_attr( $icono_pestania ); ?>"></i>
                    <?php endif; ?>
                    <?php echo esc_html( $etiqueta_pestania ); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
