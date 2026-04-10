<?php
/**
 * Features Partial: Acordeon
 *
 * Accordion/collapsible list of features.
 * Each feature can be expanded to reveal its full description.
 *
 * Available variables:
 * @var string $titulo          Section heading text
 * @var string $subtitulo       Section subheading text
 * @var string $color_primario  Primary accent color (hex)
 * @var int    $columnas        Number of columns (unused in this variant)
 * @var array  $items           Array of features, each with: titulo, descripcion, icono
 *
 * @package FlavorPlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$titulo_sanitizado        = ! empty( $titulo ) ? $titulo : '';
$subtitulo_sanitizado     = ! empty( $subtitulo ) ? $subtitulo : '';
$color_primario_sanitizado = ! empty( $color_primario ) ? $color_primario : '#2563eb';
$items_sanitizados        = ! empty( $items ) && is_array( $items ) ? $items : array();

$identificador_unico_acordeon = 'features-acordeon-' . wp_unique_id();
?>

<section class="w-full py-12 md:py-16 lg:py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <?php if ( $titulo_sanitizado || $subtitulo_sanitizado ) : ?>
            <div class="text-center mb-12 md:mb-16">
                <?php if ( $titulo_sanitizado ) : ?>
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight mb-4">
                        <?php echo esc_html( $titulo_sanitizado ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( $subtitulo_sanitizado ) : ?>
                    <p class="text-lg sm:text-xl text-gray-500 max-w-3xl mx-auto leading-relaxed">
                        <?php echo esc_html( $subtitulo_sanitizado ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Accordion -->
        <?php if ( ! empty( $items_sanitizados ) ) : ?>
            <div id="<?php echo esc_attr( $identificador_unico_acordeon ); ?>" class="space-y-3" data-features-acordeon>

                <?php foreach ( $items_sanitizados as $indice_acordeon => $elemento_acordeon ) :
                    $titulo_elemento      = ! empty( $elemento_acordeon['titulo'] ) ? $elemento_acordeon['titulo'] : '';
                    $descripcion_elemento = ! empty( $elemento_acordeon['descripcion'] ) ? $elemento_acordeon['descripcion'] : '';
                    $icono_elemento       = ! empty( $elemento_acordeon['icono'] ) ? $elemento_acordeon['icono'] : '';
                    $esta_abierto         = ( $indice_acordeon === 0 );
                ?>
                    <div
                        class="acordeon-item bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden transition-shadow duration-300 hover:shadow-md"
                        data-acordeon-indice="<?php echo esc_attr( $indice_acordeon ); ?>"
                    >
                        <!-- Accordion Header -->
                        <button
                            type="button"
                            class="acordeon-boton w-full flex items-center gap-4 px-6 py-5 text-left focus:outline-none focus:ring-2 focus:ring-offset-1 rounded-xl transition-colors duration-200"
                            style="focus:ring-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                            aria-expanded="<?php echo $esta_abierto ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr( $identificador_unico_acordeon ); ?>-panel-<?php echo esc_attr( $indice_acordeon ); ?>"
                        >
                            <?php if ( $icono_elemento ) : ?>
                                <span
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-lg text-xl shrink-0"
                                    style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>10; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                                >
                                    <?php echo $icono_elemento; ?>
                                </span>
                            <?php endif; ?>

                            <span class="flex-1 text-base sm:text-lg font-semibold text-gray-900">
                                <?php echo esc_html( $titulo_elemento ); ?>
                            </span>

                            <!-- Chevron Icon -->
                            <svg
                                class="acordeon-icono-flecha w-5 h-5 text-gray-400 shrink-0 transition-transform duration-300 <?php echo $esta_abierto ? 'rotate-180' : ''; ?>"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="2"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        <!-- Accordion Panel -->
                        <div
                            id="<?php echo esc_attr( $identificador_unico_acordeon ); ?>-panel-<?php echo esc_attr( $indice_acordeon ); ?>"
                            class="acordeon-panel overflow-hidden transition-all duration-300 ease-in-out"
                            style="<?php echo $esta_abierto ? '' : 'max-height: 0;'; ?>"
                            role="region"
                        >
                            <div class="px-6 pb-6 <?php echo $icono_elemento ? 'pl-20' : ''; ?>">
                                <?php if ( $descripcion_elemento ) : ?>
                                    <p class="text-base text-gray-500 leading-relaxed">
                                        <?php echo esc_html( $descripcion_elemento ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Active indicator bar -->
                        <div
                            class="acordeon-barra-activa h-0.5 transition-all duration-300 <?php echo $esta_abierto ? 'opacity-100' : 'opacity-0'; ?>"
                            style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                        ></div>
                    </div>
                <?php endforeach; ?>

            </div>

            <script>
            (function() {
                var contenedorAcordeon = document.getElementById('<?php echo esc_js( $identificador_unico_acordeon ); ?>');
                if ( ! contenedorAcordeon ) return;

                var botonesAcordeon = contenedorAcordeon.querySelectorAll('.acordeon-boton');

                botonesAcordeon.forEach(function(botonAcordeon) {
                    botonAcordeon.addEventListener('click', function() {
                        var elementoAcordeon = this.closest('.acordeon-item');
                        var panelAcordeon    = elementoAcordeon.querySelector('.acordeon-panel');
                        var iconoFlecha      = elementoAcordeon.querySelector('.acordeon-icono-flecha');
                        var barraActiva      = elementoAcordeon.querySelector('.acordeon-barra-activa');
                        var estaExpandido    = this.getAttribute('aria-expanded') === 'true';

                        // Close all items
                        contenedorAcordeon.querySelectorAll('.acordeon-item').forEach(function(item) {
                            var panelItem  = item.querySelector('.acordeon-panel');
                            var botonItem  = item.querySelector('.acordeon-boton');
                            var flechaItem = item.querySelector('.acordeon-icono-flecha');
                            var barraItem  = item.querySelector('.acordeon-barra-activa');

                            panelItem.style.maxHeight = '0';
                            botonItem.setAttribute('aria-expanded', 'false');
                            flechaItem.classList.remove('rotate-180');
                            barraItem.classList.remove('opacity-100');
                            barraItem.classList.add('opacity-0');
                        });

                        // Toggle clicked item (open if it was closed)
                        if ( ! estaExpandido ) {
                            panelAcordeon.style.maxHeight = panelAcordeon.scrollHeight + 'px';
                            this.setAttribute('aria-expanded', 'true');
                            iconoFlecha.classList.add('rotate-180');
                            barraActiva.classList.remove('opacity-0');
                            barraActiva.classList.add('opacity-100');
                        }
                    });
                });

                // Initialize: expand first item
                var primerPanel = contenedorAcordeon.querySelector('.acordeon-panel');
                if ( primerPanel ) {
                    primerPanel.style.maxHeight = primerPanel.scrollHeight + 'px';
                }
            })();
            </script>
        <?php endif; ?>

    </div>
</section>
