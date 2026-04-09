<?php
/**
 * Partial: Contacto - Split Con Mapa
 * Form on left, map on right.
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
$tiene_telefono       = $mostrar_telefono ?? false;
$numero_telefono      = $telefono ?? '';
$tiene_direccion      = $mostrar_direccion ?? false;
$texto_direccion      = $direccion ?? '';
$tiene_mapa           = $mostrar_mapa ?? true;
$identificador_form   = 'contacto-split-' . wp_unique_id();

$latitud_mapa  = 40.4168;
$longitud_mapa = -3.7038;
$url_iframe_mapa = sprintf(
    'https://www.openstreetmap.org/export/embed.html?bbox=%s,%s,%s,%s&marker=%s,%s&layer=mapnik',
    $longitud_mapa - 0.01,
    $latitud_mapa - 0.01,
    $longitud_mapa + 0.01,
    $latitud_mapa + 0.01,
    $latitud_mapa,
    $longitud_mapa
);
?>

<section class="py-16 px-4 bg-gray-50">
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

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Form -->
            <div class="bg-white rounded-2xl shadow-md p-8">
                <form id="<?php echo esc_attr( $identificador_form ); ?>" class="space-y-5" method="post">
                    <div>
                        <label for="<?php echo esc_attr( $identificador_form ); ?>-nombre" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php echo esc_html__( 'Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
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
                            <?php echo esc_html__( 'Email', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
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

                    <div>
                        <label for="<?php echo esc_attr( $identificador_form ); ?>-mensaje" class="block text-sm font-medium text-gray-700 mb-1">
                            <?php echo esc_html__( 'Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                        </label>
                        <textarea
                            id="<?php echo esc_attr( $identificador_form ); ?>-mensaje"
                            name="mensaje"
                            rows="4"
                            required
                            class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 text-gray-900 resize-vertical"
                            style="--tw-ring-color: <?php echo esc_attr( $color_principal ); ?>;"
                            onfocus="this.style.borderColor='<?php echo esc_attr( $color_principal ); ?>'"
                            onblur="this.style.borderColor=''"
                        ></textarea>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 px-6 rounded-lg text-white font-semibold transition-opacity hover:opacity-90"
                        style="background-color: <?php echo esc_attr( $color_principal ); ?>;"
                    >
                        <?php echo esc_html__( 'Enviar mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
                    </button>
                </form>
            </div>

            <!-- Map -->
            <div class="rounded-2xl overflow-hidden shadow-md bg-white">
                <?php if ( $tiene_mapa ) : ?>
                    <iframe
                        src="<?php echo esc_url( $url_iframe_mapa ); ?>"
                        class="w-full"
                        style="height: 100%; min-height: 400px; border: 0;"
                        allowfullscreen
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="<?php echo esc_attr__( 'Mapa de ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"
                    ></iframe>
                <?php else : ?>
                    <div class="h-full min-h-[400px] flex flex-col items-center justify-center p-8 text-center">
                        <?php if ( $tiene_direccion && $texto_direccion ) : ?>
                            <svg class="w-12 h-12 mb-4" style="color: <?php echo esc_attr( $color_principal ); ?>;" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-gray-700 font-medium mb-2"><?php echo esc_html( $texto_direccion ); ?></p>
                        <?php endif; ?>
                        <?php if ( $tiene_telefono && $numero_telefono ) : ?>
                            <p class="text-gray-600">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                                <?php echo esc_html( $numero_telefono ); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ( $correo_destino ) : ?>
                            <p class="text-gray-600 mt-1">
                                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                                </svg>
                                <?php echo esc_html( $correo_destino ); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
