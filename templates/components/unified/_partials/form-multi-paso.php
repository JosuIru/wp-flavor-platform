<?php
/**
 * Partial: Form - Multi Paso
 * Multi-step wizard form.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $campos         (array)  Lista de campos: nombre, tipo, label, requerido
 *   $texto_boton    (string) Texto del boton de envio
 *   $accion         (string) URL de accion del formulario
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$lista_campos      = $campos ?? [];
$etiqueta_boton    = $texto_boton ?? 'Enviar';
$url_accion        = $accion ?? '';
$identificador_form = 'form-multi-' . wp_unique_id();

// Split fields into steps (3 fields per step)
$campos_por_paso   = 3;
$pasos_formulario  = array_chunk( $lista_campos, $campos_por_paso );
$total_pasos       = count( $pasos_formulario );
?>

<section class="py-16 px-4 bg-gray-50">
    <div class="max-w-2xl mx-auto">
        <?php if ( $titulo_seccion || $subtitulo_seccion ) : ?>
            <div class="text-center mb-10">
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>
                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-lg text-gray-600">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Progress bar -->
        <?php if ( $total_pasos > 1 ) : ?>
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <?php for ( $numero_paso = 1; $numero_paso <= $total_pasos; $numero_paso++ ) : ?>
                        <div class="flex items-center <?php echo $numero_paso < $total_pasos ? 'flex-1' : ''; ?>">
                            <div
                                class="step-indicator-<?php echo esc_attr( $identificador_form ); ?> w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold shrink-0 transition-colors <?php echo $numero_paso === 1 ? 'text-white' : 'text-gray-400 bg-gray-200'; ?>"
                                data-step="<?php echo intval( $numero_paso ); ?>"
                                <?php if ( $numero_paso === 1 ) : ?>
                                    style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                                <?php endif; ?>
                            >
                                <?php echo esc_html( $numero_paso ); ?>
                            </div>
                            <?php if ( $numero_paso < $total_pasos ) : ?>
                                <div class="flex-1 h-1 mx-2 bg-gray-200 rounded">
                                    <div
                                        class="step-progress-<?php echo esc_attr( $identificador_form ); ?> h-full rounded transition-all duration-300"
                                        data-step="<?php echo intval( $numero_paso ); ?>"
                                        style="width: 0%; background-color: <?php echo esc_attr( $color_principal ); ?>;"
                                    ></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>

        <form
            id="<?php echo esc_attr( $identificador_form ); ?>"
            class="bg-white rounded-2xl shadow-md border border-gray-100 p-8"
            method="post"
            <?php if ( $url_accion ) : ?>
                action="<?php echo esc_url( $url_accion ); ?>"
            <?php endif; ?>
        >
            <?php foreach ( $pasos_formulario as $indice_paso => $campos_del_paso ) :
                $numero_paso_actual = $indice_paso + 1;
                $esta_visible = ( $numero_paso_actual === 1 );
            ?>
                <div
                    class="step-panel-<?php echo esc_attr( $identificador_form ); ?> <?php echo $esta_visible ? '' : 'hidden'; ?> space-y-6"
                    data-step="<?php echo intval( $numero_paso_actual ); ?>"
                >
                    <p class="text-sm text-gray-500 mb-4">
                        <?php echo esc_html( sprintf( __( 'Paso %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN ), $numero_paso_actual, $total_pasos ) ); ?>
                    </p>

                    <?php foreach ( $campos_del_paso as $campo ) :
                        $nombre_campo   = $campo['nombre'] ?? '';
                        $tipo_campo     = $campo['tipo'] ?? 'text';
                        $etiqueta_campo = $campo['label'] ?? $nombre_campo;
                        $es_requerido   = ! empty( $campo['requerido'] );
                        $identificador_campo = $identificador_form . '-' . sanitize_title( $nombre_campo );
                    ?>
                        <div>
                            <label for="<?php echo esc_attr( $identificador_campo ); ?>" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo esc_html( $etiqueta_campo ); ?>
                                <?php if ( $es_requerido ) : ?>
                                    <span class="text-red-500">*</span>
                                <?php endif; ?>
                            </label>

                            <?php if ( $tipo_campo === 'textarea' ) : ?>
                                <textarea
                                    id="<?php echo esc_attr( $identificador_campo ); ?>"
                                    name="<?php echo esc_attr( $nombre_campo ); ?>"
                                    rows="4"
                                    <?php echo $es_requerido ? 'required' : ''; ?>
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900 resize-vertical"
                                    style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                                    onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                                    onblur="this.style.borderColor=''"
                                ></textarea>
                            <?php else : ?>
                                <input
                                    type="<?php echo esc_attr( $tipo_campo ); ?>"
                                    id="<?php echo esc_attr( $identificador_campo ); ?>"
                                    name="<?php echo esc_attr( $nombre_campo ); ?>"
                                    <?php echo $es_requerido ? 'required' : ''; ?>
                                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900"
                                    style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                                    onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                                    onblur="this.style.borderColor=''"
                                />
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- Navigation buttons -->
                    <div class="flex justify-between pt-4">
                        <?php if ( $numero_paso_actual > 1 ) : ?>
                            <button
                                type="button"
                                class="px-6 py-2.5 rounded-lg border-2 font-medium text-sm transition-colors hover:bg-gray-50"
                                style="border-color: <?php echo esc_attr( $color_principal ); ?>; color: <?php echo esc_attr( $color_principal ); ?>;"
                                onclick="(function(){
                                    var formulario = document.getElementById('<?php echo esc_js( $identificador_form ); ?>');
                                    var paneles = formulario.querySelectorAll('.step-panel-<?php echo esc_js( $identificador_form ); ?>');
                                    var indicadores = document.querySelectorAll('.step-indicator-<?php echo esc_js( $identificador_form ); ?>');
                                    var barras = document.querySelectorAll('.step-progress-<?php echo esc_js( $identificador_form ); ?>');
                                    paneles.forEach(function(p){p.classList.add('hidden')});
                                    formulario.querySelector('[data-step=\'<?php echo intval( $numero_paso_actual - 1 ); ?>\']').classList.remove('hidden');
                                    indicadores.forEach(function(ind){
                                        if(parseInt(ind.dataset.step) <= <?php echo intval( $numero_paso_actual - 1 ); ?>){
                                            ind.style.backgroundColor='<?php echo esc_js( $color_principal ); ?>';ind.classList.remove('text-gray-400','bg-gray-200');ind.classList.add('text-white');
                                        } else {
                                            ind.style.backgroundColor='';ind.classList.add('text-gray-400','bg-gray-200');ind.classList.remove('text-white');
                                        }
                                    });
                                    barras.forEach(function(b){b.style.width=parseInt(b.dataset.step)< <?php echo intval( $numero_paso_actual - 1 ); ?> ? '100%':'0%';});
                                })()"
                            >
                                <?php echo esc_html__( 'Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        <?php else : ?>
                            <div></div>
                        <?php endif; ?>

                        <?php if ( $numero_paso_actual < $total_pasos ) : ?>
                            <button
                                type="button"
                                class="px-6 py-2.5 rounded-lg text-white font-medium text-sm transition-opacity hover:opacity-90"
                                style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                                onclick="(function(){
                                    var formulario = document.getElementById('<?php echo esc_js( $identificador_form ); ?>');
                                    var paneles = formulario.querySelectorAll('.step-panel-<?php echo esc_js( $identificador_form ); ?>');
                                    var indicadores = document.querySelectorAll('.step-indicator-<?php echo esc_js( $identificador_form ); ?>');
                                    var barras = document.querySelectorAll('.step-progress-<?php echo esc_js( $identificador_form ); ?>');
                                    paneles.forEach(function(p){p.classList.add('hidden')});
                                    formulario.querySelector('.step-panel-<?php echo esc_js( $identificador_form ); ?>[data-step=\'<?php echo intval( $numero_paso_actual + 1 ); ?>\']').classList.remove('hidden');
                                    indicadores.forEach(function(ind){
                                        if(parseInt(ind.dataset.step) <= <?php echo intval( $numero_paso_actual + 1 ); ?>){
                                            ind.style.backgroundColor='<?php echo esc_js( $color_principal ); ?>';ind.classList.remove('text-gray-400','bg-gray-200');ind.classList.add('text-white');
                                        } else {
                                            ind.style.backgroundColor='';ind.classList.add('text-gray-400','bg-gray-200');ind.classList.remove('text-white');
                                        }
                                    });
                                    barras.forEach(function(b){b.style.width=parseInt(b.dataset.step)<= <?php echo intval( $numero_paso_actual ); ?> ? '100%':'0%';});
                                })()"
                            >
                                <?php echo esc_html__( 'Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                            </button>
                        <?php else : ?>
                            <button
                                type="submit"
                                class="px-8 py-2.5 rounded-lg text-white font-medium text-sm transition-opacity hover:opacity-90"
                                style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                            >
                                <?php echo esc_html( $etiqueta_boton ); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </form>
    </div>
</section>
