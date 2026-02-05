<?php
/**
 * Partial: Contacto - Con Info
 * Form with contact info cards.
 *
 * Variables esperadas:
 *   $titulo            (string) Titulo de la seccion
 *   $subtitulo         (string) Subtitulo de la seccion
 *   $color_primario    (string) Color primario en formato hex
 *   $email_destino     (string) Email de destino
 *   $mostrar_telefono  (bool)   Mostrar campo telefono
 *   $telefono          (string) Numero de telefono
 *   $mostrar_direccion (bool)   Mostrar direccion
 *   $direccion         (string) Direccion fisica
 *   $mostrar_mapa      (bool)   Mostrar mapa
 */

$titulo_seccion    = $titulo ?? '';
$subtitulo_seccion = $subtitulo ?? '';
$color_principal   = $color_primario ?? '#3B82F6';
$correo_destino    = $email_destino ?? '';
$tiene_telefono    = $mostrar_telefono ?? false;
$numero_telefono   = $telefono ?? '';
$tiene_direccion   = $mostrar_direccion ?? false;
$texto_direccion   = $direccion ?? '';
$identificador_form = 'contacto-info-' . wp_unique_id();
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

        <!-- Info cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-12">
            <?php if ( $correo_destino ) : ?>
                <div class="text-center p-6 rounded-xl bg-gray-50 border border-gray-100">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.9;"
                    >
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1"><?php echo esc_html__( 'Email', 'flavor-chat-ia' ); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html( $correo_destino ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $tiene_telefono && $numero_telefono ) : ?>
                <div class="text-center p-6 rounded-xl bg-gray-50 border border-gray-100">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.9;"
                    >
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1"><?php echo esc_html__( 'Telefono', 'flavor-chat-ia' ); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html( $numero_telefono ); ?></p>
                </div>
            <?php endif; ?>

            <?php if ( $tiene_direccion && $texto_direccion ) : ?>
                <div class="text-center p-6 rounded-xl bg-gray-50 border border-gray-100">
                    <div
                        class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>; opacity: 0.9;"
                    >
                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1"><?php echo esc_html__( 'Direccion', 'flavor-chat-ia' ); ?></h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html( $texto_direccion ); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact form -->
        <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-md p-8 border border-gray-100">
            <form id="<?php echo esc_attr( $identificador_form ); ?>" class="space-y-5" method="post">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label for="<?php echo esc_attr( $identificador_form ); ?>-nombre" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php echo esc_html__( 'Nombre', 'flavor-chat-ia' ); ?>
                        </label>
                        <input
                            type="text"
                            id="<?php echo esc_attr( $identificador_form ); ?>-nombre"
                            name="nombre"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900"
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
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900"
                            style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                            onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                            onblur="this.style.borderColor=''"
                        />
                    </div>
                </div>

                <div>
                    <label for="<?php echo esc_attr( $identificador_form ); ?>-asunto" class="block text-sm font-medium text-gray-700 mb-1">
                        <?php echo esc_html__( 'Asunto', 'flavor-chat-ia' ); ?>
                    </label>
                    <input
                        type="text"
                        id="<?php echo esc_attr( $identificador_form ); ?>-asunto"
                        name="asunto"
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900"
                        style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                        onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                        onblur="this.style.borderColor=''"
                    />
                </div>

                <div>
                    <label for="<?php echo esc_attr( $identificador_form ); ?>-mensaje" class="block text-sm font-medium text-gray-700 mb-1">
                        <?php echo esc_html__( 'Mensaje', 'flavor-chat-ia' ); ?>
                    </label>
                    <textarea
                        id="<?php echo esc_attr( $identificador_form ); ?>-mensaje"
                        name="mensaje"
                        rows="5"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900 resize-vertical"
                        style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                        onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                        onblur="this.style.borderColor=''"
                    ></textarea>
                </div>

                <div class="text-center">
                    <button
                        type="submit"
                        class="inline-flex items-center px-8 py-3 rounded-lg text-white font-semibold transition-opacity hover:opacity-90"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                    >
                        <?php echo esc_html__( 'Enviar mensaje', 'flavor-chat-ia' ); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
