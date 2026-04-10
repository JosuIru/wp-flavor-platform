<?php
/**
 * Features Partial: Tabs
 *
 * Tabbed interface showing one feature at a time.
 * Each tab displays the feature icon, title, and description.
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

$identificador_unico_tabs = 'features-tabs-' . wp_unique_id();
?>

<section class="w-full py-12 md:py-16 lg:py-20">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

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

        <!-- Tabs Component -->
        <?php if ( ! empty( $items_sanitizados ) ) : ?>
            <div id="<?php echo esc_attr( $identificador_unico_tabs ); ?>" data-features-tabs>

                <!-- Tab Navigation -->
                <div class="flex flex-wrap justify-center gap-2 mb-8 md:mb-12 border-b border-gray-200 pb-0">
                    <?php foreach ( $items_sanitizados as $indice_tab => $elemento_tab ) :
                        $titulo_tab = ! empty( $elemento_tab['titulo'] ) ? $elemento_tab['titulo'] : '';
                        $icono_tab  = ! empty( $elemento_tab['icono'] ) ? $elemento_tab['icono'] : '';
                        $esta_activo = ( $indice_tab === 0 );
                    ?>
                        <button
                            type="button"
                            class="tab-boton inline-flex items-center gap-2 px-5 py-3 text-sm sm:text-base font-medium rounded-t-lg border-b-2 transition-all duration-200 focus:outline-none <?php echo $esta_activo ? 'border-current text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>"
                            data-tab-indice="<?php echo esc_attr( $indice_tab ); ?>"
                            <?php if ( $esta_activo ) : ?>
                                style="border-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                            <?php endif; ?>
                            aria-selected="<?php echo $esta_activo ? 'true' : 'false'; ?>"
                            role="tab"
                        >
                            <?php if ( $icono_tab ) : ?>
                                <span class="text-lg"><?php echo $icono_tab; ?></span>
                            <?php endif; ?>
                            <span class="hidden sm:inline"><?php echo esc_html( $titulo_tab ); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Tab Panels -->
                <?php foreach ( $items_sanitizados as $indice_panel => $elemento_panel ) :
                    $titulo_panel      = ! empty( $elemento_panel['titulo'] ) ? $elemento_panel['titulo'] : '';
                    $descripcion_panel = ! empty( $elemento_panel['descripcion'] ) ? $elemento_panel['descripcion'] : '';
                    $icono_panel       = ! empty( $elemento_panel['icono'] ) ? $elemento_panel['icono'] : '';
                    $panel_visible     = ( $indice_panel === 0 );
                ?>
                    <div
                        class="tab-panel <?php echo $panel_visible ? '' : 'hidden'; ?>"
                        data-panel-indice="<?php echo esc_attr( $indice_panel ); ?>"
                        role="tabpanel"
                    >
                        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-8 md:p-12 lg:p-16">
                            <div class="flex flex-col md:flex-row items-center gap-8 md:gap-12">

                                <!-- Icon / Visual -->
                                <?php if ( $icono_panel ) : ?>
                                    <div class="shrink-0">
                                        <div
                                            class="flex items-center justify-center w-24 h-24 md:w-32 md:h-32 rounded-2xl text-4xl md:text-5xl"
                                            style="background-color: <?php echo esc_attr( $color_primario_sanitizado ); ?>10; color: <?php echo esc_attr( $color_primario_sanitizado ); ?>;"
                                        >
                                            <?php echo $icono_panel; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Text Content -->
                                <div class="flex-1 text-center md:text-left">
                                    <?php if ( $titulo_panel ) : ?>
                                        <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                                            <?php echo esc_html( $titulo_panel ); ?>
                                        </h3>
                                    <?php endif; ?>

                                    <?php if ( $descripcion_panel ) : ?>
                                        <p class="text-base sm:text-lg text-gray-500 leading-relaxed max-w-2xl">
                                            <?php echo esc_html( $descripcion_panel ); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            </div>

            <script>
            (function() {
                var contenedorTabs = document.getElementById('<?php echo esc_js( $identificador_unico_tabs ); ?>');
                if ( ! contenedorTabs ) return;

                var botonesTabs = contenedorTabs.querySelectorAll('.tab-boton');
                var panelesTabs = contenedorTabs.querySelectorAll('.tab-panel');
                var colorActivo = '<?php echo esc_js( $color_primario_sanitizado ); ?>';

                botonesTabs.forEach(function(botonTab) {
                    botonTab.addEventListener('click', function() {
                        var indiceSeleccionado = this.getAttribute('data-tab-indice');

                        // Reset all tabs
                        botonesTabs.forEach(function(boton) {
                            boton.classList.remove('text-gray-900');
                            boton.classList.add('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                            boton.classList.replace('border-current', 'border-transparent');
                            boton.style.borderColor = '';
                            boton.style.color = '';
                            boton.setAttribute('aria-selected', 'false');
                        });

                        // Activate clicked tab
                        this.classList.remove('text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300', 'border-transparent');
                        this.classList.add('text-gray-900', 'border-current');
                        this.style.borderColor = colorActivo;
                        this.style.color = colorActivo;
                        this.setAttribute('aria-selected', 'true');

                        // Show/hide panels
                        panelesTabs.forEach(function(panel) {
                            var indicePanelActual = panel.getAttribute('data-panel-indice');
                            if ( indicePanelActual === indiceSeleccionado ) {
                                panel.classList.remove('hidden');
                            } else {
                                panel.classList.add('hidden');
                            }
                        });
                    });
                });
            })();
            </script>
        <?php endif; ?>

    </div>
</section>
