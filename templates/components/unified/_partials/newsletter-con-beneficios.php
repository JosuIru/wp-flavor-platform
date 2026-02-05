<?php
/**
 * Partial: Newsletter - Con Beneficios
 * Newsletter form with benefit list.
 *
 * Variables esperadas:
 *   $titulo         (string) Titulo de la seccion
 *   $subtitulo      (string) Subtitulo de la seccion
 *   $color_primario (string) Color primario en formato hex
 *   $texto_boton    (string) Texto del boton
 *   $placeholder    (string) Placeholder del campo email
 *   $beneficios     (array)  Lista de beneficios
 */

$titulo_seccion     = $titulo ?? '';
$subtitulo_seccion  = $subtitulo ?? '';
$color_principal    = $color_primario ?? '#3B82F6';
$etiqueta_boton     = $texto_boton ?? 'Suscribirse';
$texto_placeholder  = $placeholder ?? 'tu@email.com';
$lista_beneficios   = $beneficios ?? [];
$identificador_form = 'newsletter-beneficios-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10 items-center bg-gray-50 rounded-2xl p-8 md:p-12 border border-gray-100">
            <!-- Benefits -->
            <div>
                <?php if ( $titulo_seccion ) : ?>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                        <?php echo esc_html( $titulo_seccion ); ?>
                    </h2>
                <?php endif; ?>

                <?php if ( $subtitulo_seccion ) : ?>
                    <p class="text-gray-600 mb-6">
                        <?php echo esc_html( $subtitulo_seccion ); ?>
                    </p>
                <?php endif; ?>

                <?php if ( ! empty( $lista_beneficios ) ) : ?>
                    <ul class="space-y-3">
                        <?php foreach ( $lista_beneficios as $beneficio ) : ?>
                            <li class="flex items-center gap-3">
                                <svg class="w-5 h-5 shrink-0" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="text-gray-700"><?php echo esc_html( $beneficio ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <form id="<?php echo esc_attr( $identificador_form ); ?>" class="space-y-4" method="post">
                    <div>
                        <label for="<?php echo esc_attr( $identificador_form ); ?>-nombre" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php echo esc_html__( 'Nombre', 'flavor-chat-ia' ); ?>
                        </label>
                        <input
                            type="text"
                            id="<?php echo esc_attr( $identificador_form ); ?>-nombre"
                            name="nombre"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 text-gray-900 focus:outline-none focus:ring-2"
                            style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                            onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                            onblur="this.style.borderColor=''"
                        />
                    </div>
                    <div>
                        <label for="<?php echo esc_attr( $identificador_form ); ?>-email" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php echo esc_html__( 'Email', 'flavor-chat-ia' ); ?>
                        </label>
                        <input
                            type="email"
                            id="<?php echo esc_attr( $identificador_form ); ?>-email"
                            name="email"
                            required
                            placeholder="<?php echo esc_attr( $texto_placeholder ); ?>"
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2"
                            style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                            onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                            onblur="this.style.borderColor=''"
                        />
                    </div>
                    <button
                        type="submit"
                        class="w-full py-3 rounded-lg text-white font-semibold transition-opacity hover:opacity-90"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                    >
                        <?php echo esc_html( $etiqueta_boton ); ?>
                    </button>
                    <p class="text-xs text-gray-400 text-center">
                        <?php echo esc_html__( 'No spam. Puedes darte de baja en cualquier momento.', 'flavor-chat-ia' ); ?>
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
