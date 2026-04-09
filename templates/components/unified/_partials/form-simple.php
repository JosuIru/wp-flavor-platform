<?php
/**
 * Partial: Form - Simple
 * Simple single-step form.
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
$identificador_form = 'form-simple-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-white">
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

        <form
            id="<?php echo esc_attr( $identificador_form ); ?>"
            class="bg-white rounded-2xl shadow-md border border-gray-100 p-8 space-y-6"
            method="post"
            <?php if ( $url_accion ) : ?>
                action="<?php echo esc_url( $url_accion ); ?>"
            <?php endif; ?>
        >
            <?php foreach ( $lista_campos as $campo ) :
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
                    <?php elseif ( $tipo_campo === 'select' ) : ?>
                        <select
                            id="<?php echo esc_attr( $identificador_campo ); ?>"
                            name="<?php echo esc_attr( $nombre_campo ); ?>"
                            <?php echo $es_requerido ? 'required' : ''; ?>
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900 bg-white"
                            style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                            onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                            onblur="this.style.borderColor=''"
                        >
                            <option value=""><?php echo esc_html__( 'Seleccionar...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
                        </select>
                    <?php elseif ( $tipo_campo === 'checkbox' ) : ?>
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="<?php echo esc_attr( $identificador_campo ); ?>"
                                name="<?php echo esc_attr( $nombre_campo ); ?>"
                                <?php echo $es_requerido ? 'required' : ''; ?>
                                class="w-4 h-4 rounded border-gray-300"
                                style="accent-color: <?php echo esc_attr( $color_principal ); ?>;"
                            />
                            <span class="text-sm text-gray-600"><?php echo esc_html( $etiqueta_campo ); ?></span>
                        </div>
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

            <div class="pt-2">
                <button
                    type="submit"
                    class="w-full py-3 px-6 rounded-lg text-white font-semibold transition-opacity hover:opacity-90"
                    style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                >
                    <?php echo esc_html( $etiqueta_boton ); ?>
                </button>
            </div>
        </form>
    </div>
</section>
