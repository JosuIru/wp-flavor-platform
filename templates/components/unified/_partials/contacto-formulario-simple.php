<?php
/**
 * Partial: Contacto - Formulario Simple
 * Simple contact form.
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

$titulo_seccion       = $titulo ?? '';
$subtitulo_seccion    = $subtitulo ?? '';
$color_principal      = $color_primario ?? '#3B82F6';
$correo_destino       = $email_destino ?? '';
$identificador_form   = 'contacto-simple-' . wp_unique_id();
?>

<section class="py-16 px-4 bg-white">
    <div class="max-w-2xl mx-auto">
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

        <form id="<?php echo esc_attr( $identificador_form ); ?>" class="space-y-6" method="post">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="<?php echo esc_attr( $identificador_form ); ?>-nombre" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__( 'Nombre', 'flavor-chat-ia' ); ?>
                    </label>
                    <input
                        type="text"
                        id="<?php echo esc_attr( $identificador_form ); ?>-nombre"
                        name="nombre"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:border-transparent text-gray-900 placeholder-gray-400"
                        style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                        onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                        onblur="this.style.borderColor=''"
                        placeholder="<?php echo esc_attr__( 'Tu nombre', 'flavor-chat-ia' ); ?>"
                    />
                </div>
                <div>
                    <label for="<?php echo esc_attr( $identificador_form ); ?>-email" class="block text-sm font-medium text-gray-700 mb-2">
                        <?php echo esc_html__( 'Email', 'flavor-chat-ia' ); ?>
                    </label>
                    <input
                        type="email"
                        id="<?php echo esc_attr( $identificador_form ); ?>-email"
                        name="email"
                        required
                        class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:border-transparent text-gray-900 placeholder-gray-400"
                        style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                        onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                        onblur="this.style.borderColor=''"
                        placeholder="<?php echo esc_attr__( 'tu@email.com', 'flavor-chat-ia' ); ?>"
                    />
                </div>
            </div>

            <div>
                <label for="<?php echo esc_attr( $identificador_form ); ?>-asunto" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php echo esc_html__( 'Asunto', 'flavor-chat-ia' ); ?>
                </label>
                <input
                    type="text"
                    id="<?php echo esc_attr( $identificador_form ); ?>-asunto"
                    name="asunto"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:border-transparent text-gray-900 placeholder-gray-400"
                    style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                    onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                    onblur="this.style.borderColor=''"
                    placeholder="<?php echo esc_attr__( 'Asunto del mensaje', 'flavor-chat-ia' ); ?>"
                />
            </div>

            <div>
                <label for="<?php echo esc_attr( $identificador_form ); ?>-mensaje" class="block text-sm font-medium text-gray-700 mb-2">
                    <?php echo esc_html__( 'Mensaje', 'flavor-chat-ia' ); ?>
                </label>
                <textarea
                    id="<?php echo esc_attr( $identificador_form ); ?>-mensaje"
                    name="mensaje"
                    rows="5"
                    required
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:border-transparent text-gray-900 placeholder-gray-400 resize-vertical"
                    style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                    onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                    onblur="this.style.borderColor=''"
                    placeholder="<?php echo esc_attr__( 'Escribe tu mensaje aqui...', 'flavor-chat-ia' ); ?>"
                ></textarea>
            </div>

            <div class="text-center">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center px-8 py-3 rounded-lg text-white font-semibold transition-opacity hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2"
                    style="background-color: <?php echo esc_attr( $color_principal ); ?>; --tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                >
                    <?php echo esc_html__( 'Enviar mensaje', 'flavor-chat-ia' ); ?>
                </button>
            </div>
        </form>
    </div>
</section>
