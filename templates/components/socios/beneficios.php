<?php
/**
 * Template: Beneficios de Ser Socio
 *
 * Grid de tarjetas mostrando los beneficios de la membresia:
 * descuentos, eventos, acceso prioritario, newsletter, etc.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$beneficios_socios = [
    [
        'titulo'      => 'Descuentos exclusivos',
        'descripcion' => 'Hasta un 25% de descuento en comercios, restaurantes y servicios locales asociados.',
        'icono'       => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'color'       => 'rose',
    ],
    [
        'titulo'      => 'Eventos exclusivos',
        'descripcion' => 'Accede a charlas, talleres, excursiones y actividades reservadas solo para miembros.',
        'icono'       => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        'color'       => 'pink',
    ],
    [
        'titulo'      => 'Acceso prioritario',
        'descripcion' => 'Reserva anticipada en actividades, cursos y eventos con aforo limitado del municipio.',
        'icono'       => 'M13 10V3L4 14h7v7l9-11h-7z',
        'color'       => 'rose',
    ],
    [
        'titulo'      => 'Newsletter semanal',
        'descripcion' => 'Recibe informacion privilegiada sobre novedades, ofertas y oportunidades antes que nadie.',
        'icono'       => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'color'       => 'pink',
    ],
    [
        'titulo'      => 'Voto en asambleas',
        'descripcion' => 'Participa y vota en las decisiones importantes de la asociacion. Tu opinion cuenta.',
        'icono'       => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4',
        'color'       => 'rose',
    ],
    [
        'titulo'      => 'Red de contactos',
        'descripcion' => 'Conecta con otros miembros, comparte intereses y amplifica tu red profesional y social.',
        'icono'       => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
        'color'       => 'pink',
    ],
];

$colores_config = [
    'rose' => ['fondo_icono' => 'from-rose-400 to-rose-500', 'fondo_tarjeta' => 'hover:border-rose-200', 'texto_hover' => 'group-hover:text-rose-600'],
    'pink' => ['fondo_icono' => 'from-pink-400 to-pink-500', 'fondo_tarjeta' => 'hover:border-pink-200', 'texto_hover' => 'group-hover:text-pink-600'],
];
?>

<section class="flavor-component flavor-section py-20 bg-white">
    <div class="container mx-auto px-4">
        <!-- Titulo -->
        <div class="text-center mb-14">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo ?? 'Ventajas de Ser Socio'); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo ?? 'Descubre todo lo que obtienes al formar parte de nuestra comunidad.'); ?>
            </p>
            <div class="w-20 h-1 bg-rose-500 mx-auto rounded-full mt-4"></div>
        </div>

        <!-- Grid de beneficios -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php foreach ($beneficios_socios as $beneficio_actual):
                $color_actual = $colores_config[$beneficio_actual['color']] ?? $colores_config['rose'];
            ?>
                <div class="group bg-white rounded-xl p-6 border border-gray-100 <?php echo esc_attr($color_actual['fondo_tarjeta']); ?> shadow-sm hover:shadow-lg transition duration-300">
                    <!-- Icono -->
                    <div class="w-14 h-14 bg-gradient-to-br <?php echo esc_attr($color_actual['fondo_icono']); ?> rounded-xl flex items-center justify-center shadow-md mb-5 group-hover:scale-110 transition duration-300">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($beneficio_actual['icono']); ?>" />
                        </svg>
                    </div>

                    <!-- Titulo -->
                    <h3 class="text-lg font-bold text-gray-900 mb-2 <?php echo esc_attr($color_actual['texto_hover']); ?> transition duration-300">
                        <?php echo esc_html($beneficio_actual['titulo']); ?>
                    </h3>

                    <!-- Descripcion -->
                    <p class="text-gray-600 text-sm leading-relaxed">
                        <?php echo esc_html($beneficio_actual['descripcion']); ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Estadistica de satisfaccion -->
        <div class="mt-14 max-w-2xl mx-auto bg-rose-50 rounded-xl p-6 text-center">
            <div class="flex items-center justify-center mb-2">
                <?php for ($indice_estrella = 0; $indice_estrella < 5; $indice_estrella++): ?>
                    <svg class="w-6 h-6 text-rose-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                <?php endfor; ?>
            </div>
            <p class="text-rose-700 font-medium">
                <?php echo esc_html__('El 96% de nuestros miembros recomiendan la membresia', 'flavor-chat-ia'); ?>
            </p>
        </div>
    </div>
</section>
