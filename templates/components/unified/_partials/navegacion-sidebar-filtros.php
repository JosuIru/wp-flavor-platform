<?php
/**
 * Partial: Navegacion - Sidebar Filtros
 * Vertical sidebar filter nav.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $items          (array)  Filtros: label, url, icono, activo
 */

$titulo_seccion  = $titulo ?? '';
$color_principal = $color_primario ?? '#3B82F6';
$lista_filtros   = $items ?? [];
?>

<aside class="w-full md:w-64 shrink-0">
    <?php if ( $titulo_seccion ) : ?>
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4 px-3">
            <?php echo esc_html( $titulo_seccion ); ?>
        </h3>
    <?php endif; ?>

    <nav class="space-y-1" role="navigation">
        <?php foreach ( $lista_filtros as $filtro ) :
            $etiqueta_filtro = $filtro['label'] ?? '';
            $url_filtro      = $filtro['url'] ?? '#';
            $icono_filtro    = $filtro['icono'] ?? '';
            $esta_activo     = ! empty( $filtro['activo'] );
        ?>
            <a
                href="<?php echo esc_url( $url_filtro ); ?>"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors <?php echo $esta_activo ? 'text-white' : 'text-gray-700 hover:bg-gray-100'; ?>"
                <?php if ( $esta_activo ) : ?>
                    style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                    aria-current="page"
                <?php endif; ?>
            >
                <?php if ( $icono_filtro ) : ?>
                    <i class="<?php echo esc_attr( $icono_filtro ); ?> w-5 text-center"></i>
                <?php endif; ?>
                <span><?php echo esc_html( $etiqueta_filtro ); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
