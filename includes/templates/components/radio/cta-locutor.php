<?php
/**
 * Template: Radio Presenter CTA
 *
 * Call-to-action section for radio presenters recruitment
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Radio
 */

defined('ABSPATH') || exit;

// Default values
$titulo_principal = $args['titulo_principal'] ?? '¿Tienes una idea para un programa?';
$subtitulo = $args['subtitulo'] ?? 'Conviértete en locutor comunitario';
$descripcion = $args['descripcion'] ?? 'Nuestra radio es un espacio abierto para todas las voces de la comunidad. Si tienes algo que compartir, una pasión que difundir o simplemente quieres formar parte de este proyecto, ¡queremos conocerte!';
$beneficios = $args['beneficios'] ?? [
    [
        'titulo' => 'Libertad creativa',
        'descripcion' => 'Desarrolla tu propio programa con total autonomía',
        'icono' => 'lightbulb'
    ],
    [
        'titulo' => 'Formación continua',
        'descripcion' => 'Talleres de locución, producción y comunicación',
        'icono' => 'academic-cap'
    ],
    [
        'titulo' => 'Impacto comunitario',
        'descripcion' => 'Tu voz llegará a cientos de oyentes locales',
        'icono' => 'users'
    ],
    [
        'titulo' => 'Horarios flexibles',
        'descripcion' => 'Adapta tu programa a tu disponibilidad',
        'icono' => 'clock'
    ]
];
$texto_boton = $args['texto_boton'] ?? 'Únete como Locutor';
$url_formulario = $args['url_formulario'] ?? '#contacto';
$imagen_fondo = $args['imagen_fondo'] ?? '';
$mostrar_testimonios = $args['mostrar_testimonios'] ?? true;
$testimonios = $args['testimonios'] ?? [
    [
        'nombre' => 'María González',
        'programa' => 'Buenos Días Comunidad',
        'testimonio' => 'Ser locutora en la radio comunitaria me ha permitido conectar con mis vecinos de una forma única. ¡Es una experiencia increíble!',
        'avatar' => ''
    ],
    [
        'nombre' => 'Carlos Ruiz',
        'programa' => 'Música Tradicional',
        'testimonio' => 'Comparto mi pasión por la música local cada semana. La radio me dio la oportunidad de preservar nuestras tradiciones.',
        'avatar' => ''
    ]
];
$color_principal = $args['color_principal'] ?? 'purple';

// Icon SVG paths
$iconos_svg = [
    'lightbulb' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    'academic-cap' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222',
    'users' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'microphone' => 'M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z',
    'star' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z'
];
?>

<section class="relative py-16 md:py-24 overflow-hidden">
    <!-- Background -->
    <div class="absolute inset-0 bg-gradient-to-br from-<?php echo esc_attr($color_principal); ?>-50 via-white to-blue-50">
        <?php if ($imagen_fondo): ?>
            <div class="absolute inset-0 bg-cover bg-center opacity-5" style="background-image: url('<?php echo esc_url($imagen_fondo); ?>');"></div>
        <?php endif; ?>
    </div>

    <!-- Decorative Elements -->
    <div class="absolute top-20 right-10 w-72 h-72 bg-<?php echo esc_attr($color_principal); ?>-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
    <div class="absolute -bottom-20 left-10 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
    <div class="absolute top-1/2 left-1/2 w-72 h-72 bg-pink-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>

    <div class="relative container mx-auto px-4">
        <!-- Main Content -->
        <div class="max-w-4xl mx-auto text-center mb-16">
            <!-- Icon -->
            <div class="mb-8 flex justify-center">
                <div class="relative">
                    <div class="w-20 h-20 md:w-24 md:h-24 bg-gradient-to-br from-<?php echo esc_attr($color_principal); ?>-500 to-<?php echo esc_attr($color_principal); ?>-700 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                        <svg class="w-10 h-10 md:w-12 md:h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $iconos_svg['microphone']; ?>"/>
                        </svg>
                    </div>
                    <!-- Sound waves -->
                    <div class="absolute -right-2 top-1/2 -translate-y-1/2 flex gap-1">
                        <div class="w-1 h-3 bg-<?php echo esc_attr($color_principal); ?>-400 rounded-full animate-pulse"></div>
                        <div class="w-1 h-5 bg-<?php echo esc_attr($color_principal); ?>-400 rounded-full animate-pulse" style="animation-delay: 0.2s;"></div>
                        <div class="w-1 h-4 bg-<?php echo esc_attr($color_principal); ?>-400 rounded-full animate-pulse" style="animation-delay: 0.4s;"></div>
                    </div>
                </div>
            </div>

            <!-- Title -->
            <h2 class="text-3xl md:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_principal); ?>
            </h2>

            <!-- Subtitle -->
            <p class="text-xl md:text-2xl text-<?php echo esc_attr($color_principal); ?>-600 font-semibold mb-6">
                <?php echo esc_html($subtitulo); ?>
            </p>

            <!-- Description -->
            <p class="text-lg text-gray-600 leading-relaxed max-w-3xl mx-auto mb-10">
                <?php echo esc_html($descripcion); ?>
            </p>

            <!-- Primary CTA -->
            <a href="<?php echo esc_url($url_formulario); ?>"
               class="inline-flex items-center gap-3 px-10 py-5 bg-gradient-to-r from-<?php echo esc_attr($color_principal); ?>-600 to-<?php echo esc_attr($color_principal); ?>-700 text-white font-bold text-lg rounded-full hover:from-<?php echo esc_attr($color_principal); ?>-700 hover:to-<?php echo esc_attr($color_principal); ?>-800 transition-all duration-300 shadow-2xl hover:shadow-<?php echo esc_attr($color_principal); ?>-500/50 hover:scale-105 transform">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $iconos_svg['microphone']; ?>"/>
                </svg>
                <span><?php echo esc_html($texto_boton); ?></span>
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>

        <!-- Benefits Grid -->
        <div class="max-w-6xl mx-auto mb-16">
            <h3 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-10">
                ¿Por qué ser locutor comunitario?
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($beneficios as $beneficio): ?>
                    <?php
                    $titulo_beneficio = $beneficio['titulo'] ?? '';
                    $descripcion_beneficio = $beneficio['descripcion'] ?? '';
                    $icono_beneficio = $beneficio['icono'] ?? 'star';
                    $path_icono = $iconos_svg[$icono_beneficio] ?? $iconos_svg['star'];
                    ?>

                    <div class="group bg-white rounded-2xl p-6 shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-<?php echo esc_attr($color_principal); ?>-200 hover:-translate-y-2">
                        <!-- Icon -->
                        <div class="w-14 h-14 bg-gradient-to-br from-<?php echo esc_attr($color_principal); ?>-100 to-<?php echo esc_attr($color_principal); ?>-200 rounded-xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform duration-300">
                            <svg class="w-7 h-7 text-<?php echo esc_attr($color_principal); ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $path_icono; ?>"/>
                            </svg>
                        </div>

                        <!-- Title -->
                        <h4 class="text-lg font-bold text-gray-900 mb-2 group-hover:text-<?php echo esc_attr($color_principal); ?>-600 transition-colors">
                            <?php echo esc_html($titulo_beneficio); ?>
                        </h4>

                        <!-- Description -->
                        <p class="text-gray-600 text-sm leading-relaxed">
                            <?php echo esc_html($descripcion_beneficio); ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($mostrar_testimonios && !empty($testimonios)): ?>
            <!-- Testimonials -->
            <div class="max-w-6xl mx-auto">
                <h3 class="text-2xl md:text-3xl font-bold text-center text-gray-900 mb-10">
                    Lo que dicen nuestros locutores
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <?php foreach ($testimonios as $testimonio): ?>
                        <?php
                        $nombre_locutor = $testimonio['nombre'] ?? '';
                        $programa_locutor = $testimonio['programa'] ?? '';
                        $texto_testimonio = $testimonio['testimonio'] ?? '';
                        $avatar_locutor = $testimonio['avatar'] ?? '';
                        ?>

                        <div class="bg-white rounded-2xl p-8 shadow-xl border border-gray-100 relative">
                            <!-- Quote Icon -->
                            <div class="absolute top-6 right-6 opacity-10">
                                <svg class="w-16 h-16 text-<?php echo esc_attr($color_principal); ?>-600" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                                </svg>
                            </div>

                            <!-- Content -->
                            <div class="relative">
                                <!-- Stars -->
                                <div class="flex gap-1 mb-4">
                                    <?php for ($i = 0; $i < 5; $i++): ?>
                                        <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="<?php echo $iconos_svg['star']; ?>"/>
                                        </svg>
                                    <?php endfor; ?>
                                </div>

                                <!-- Testimonial Text -->
                                <p class="text-gray-700 leading-relaxed mb-6 italic">
                                    "<?php echo esc_html($texto_testimonio); ?>"
                                </p>

                                <!-- Author -->
                                <div class="flex items-center gap-4">
                                    <?php if ($avatar_locutor): ?>
                                        <img src="<?php echo esc_url($avatar_locutor); ?>"
                                             alt="<?php echo esc_attr($nombre_locutor); ?>"
                                             class="w-12 h-12 rounded-full object-cover border-2 border-<?php echo esc_attr($color_principal); ?>-200">
                                    <?php else: ?>
                                        <div class="w-12 h-12 bg-gradient-to-br from-<?php echo esc_attr($color_principal); ?>-400 to-<?php echo esc_attr($color_principal); ?>-600 rounded-full flex items-center justify-center text-white font-bold text-lg">
                                            <?php echo esc_html(strtoupper(substr($nombre_locutor, 0, 1))); ?>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <div class="font-bold text-gray-900">
                                            <?php echo esc_html($nombre_locutor); ?>
                                        </div>
                                        <div class="text-sm text-<?php echo esc_attr($color_principal); ?>-600">
                                            <?php echo esc_html($programa_locutor); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Secondary CTA -->
        <div class="mt-16 text-center">
            <div class="bg-white rounded-2xl p-8 md:p-12 shadow-xl max-w-3xl mx-auto border-2 border-<?php echo esc_attr($color_principal); ?>-200">
                <h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4">
                    ¿Tienes dudas?
                </h3>
                <p class="text-gray-600 mb-6">
                    Escríbenos o llámanos para resolver todas tus preguntas sobre cómo participar en la radio comunitaria.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="mailto:contacto@radiocomunitaria.org"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span>Enviar Email</span>
                    </a>
                    <a href="tel:+34900000000"
                       class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-green-500 hover:bg-green-600 text-white font-semibold rounded-xl transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                        </svg>
                        <span>Llamar</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes blob {
    0%, 100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(30px, -50px) scale(1.1);
    }
    66% {
        transform: translate(-20px, 20px) scale(0.9);
    }
}

.animate-blob {
    animation: blob 7s infinite;
}

.animation-delay-2000 {
    animation-delay: 2s;
}

.animation-delay-4000 {
    animation-delay: 4s;
}
</style>
