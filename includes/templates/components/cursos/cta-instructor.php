<?php
/**
 * Template: CTA Instructor
 *
 * Call-to-action para invitar a usuarios a convertirse en instructores
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Cursos
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_cta = $args['titulo'] ?? '¿Quieres Convertirte en Instructor?';
$subtitulo_cta = $args['subtitulo'] ?? 'Comparte tu conocimiento con miles de estudiantes y genera ingresos enseñando lo que amas';
$descripcion_cta = $args['descripcion'] ?? 'Únete a nuestra comunidad de instructores expertos y ayuda a transformar vidas a través de la educación online. Te proporcionamos todas las herramientas y el soporte necesario para crear cursos de calidad.';
$boton_texto = $args['boton_texto'] ?? 'Comenzar como Instructor';
$boton_url = $args['boton_url'] ?? '#registro-instructor';
$boton_secundario_texto = $args['boton_secundario_texto'] ?? 'Más Información';
$boton_secundario_url = $args['boton_secundario_url'] ?? '#info-instructor';
$mostrar_beneficios = $args['mostrar_beneficios'] ?? true;
$mostrar_estadisticas = $args['mostrar_estadisticas'] ?? true;
$imagen_instructor = $args['imagen'] ?? '';
$estilo_variante = $args['variante'] ?? 'default'; // default, minimal, featured

$beneficios_instructor = $args['beneficios'] ?? [
    [
        'icono' => 'money',
        'titulo' => 'Genera Ingresos',
        'descripcion' => 'Obtén ingresos pasivos compartiendo tu experiencia'
    ],
    [
        'icono' => 'users',
        'titulo' => 'Alcance Global',
        'descripcion' => 'Conecta con estudiantes de todo el mundo'
    ],
    [
        'icono' => 'tools',
        'titulo' => 'Herramientas Completas',
        'descripcion' => 'Accede a todas las herramientas para crear cursos profesionales'
    ],
    [
        'icono' => 'support',
        'titulo' => 'Soporte Dedicado',
        'descripcion' => 'Recibe ayuda y orientación en cada paso del camino'
    ]
];

$estadisticas_instructor = $args['estadisticas'] ?? [
    ['valor' => '10,000+', 'etiqueta' => 'Estudiantes Activos'],
    ['valor' => '95%', 'etiqueta' => 'Satisfacción'],
    ['valor' => '500+', 'etiqueta' => 'Instructores']
];

// Iconos SVG
$iconos_svg_disponibles = [
    'money' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
    'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>',
    'tools' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
    'support' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>',
    'certificate' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>',
];
?>

<?php if ($estilo_variante === 'minimal'): ?>
    <!-- Variante Minimalista -->
    <section class="py-16 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_cta); ?>
            </h2>
            <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_cta); ?>
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo esc_url($boton_url); ?>"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transform hover:scale-105 transition-all duration-200 shadow-lg">
                    <?php echo esc_html($boton_texto); ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <?php if ($boton_secundario_texto): ?>
                    <a href="<?php echo esc_url($boton_secundario_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-gray-100 text-gray-900 font-semibold rounded-lg hover:bg-gray-200 transition-all duration-200">
                        <?php echo esc_html($boton_secundario_texto); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php elseif ($estilo_variante === 'featured'): ?>
    <!-- Variante Destacada con Imagen de Fondo -->
    <section class="relative py-24 overflow-hidden">
        <!-- Imagen de fondo con overlay -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900 via-indigo-900 to-purple-900">
            <?php if ($imagen_instructor): ?>
                <img src="<?php echo esc_url($imagen_instructor); ?>"
                     alt="Instructor"
                     class="w-full h-full object-cover opacity-20">
            <?php endif; ?>
        </div>

        <!-- Patrón decorativo -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>

        <div class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-white text-sm font-medium mb-6">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                <span>Únete a Nuestra Comunidad de Expertos</span>
            </div>

            <h2 class="text-4xl sm:text-5xl font-bold text-white mb-6">
                <?php echo esc_html($titulo_cta); ?>
            </h2>

            <p class="text-xl text-blue-100 mb-12 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_cta); ?>
            </p>

            <?php if ($mostrar_estadisticas && !empty($estadisticas_instructor)): ?>
                <div class="grid grid-cols-3 gap-8 mb-12 max-w-2xl mx-auto">
                    <?php foreach ($estadisticas_instructor as $estadistica): ?>
                        <div class="text-center">
                            <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                <?php echo esc_html($estadistica['valor']); ?>
                            </div>
                            <div class="text-sm text-blue-200">
                                <?php echo esc_html($estadistica['etiqueta']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo esc_url($boton_url); ?>"
                   class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-blue-700 font-semibold rounded-lg hover:bg-blue-50 transform hover:scale-105 transition-all duration-200 shadow-xl">
                    <?php echo esc_html($boton_texto); ?>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                    </svg>
                </a>
                <?php if ($boton_secundario_texto): ?>
                    <a href="<?php echo esc_url($boton_secundario_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg border-2 border-white/30 hover:bg-white/20 transition-all duration-200">
                        <?php echo esc_html($boton_secundario_texto); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

<?php else: ?>
    <!-- Variante Default con Grid de Beneficios -->
    <section class="py-20 bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <!-- Contenido principal -->
                <div>
                    <!-- Badge decorativo -->
                    <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 text-blue-700 text-sm font-medium rounded-full mb-6">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <span>Conviértete en Instructor</span>
                    </div>

                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                        <?php echo esc_html($titulo_cta); ?>
                    </h2>

                    <p class="text-lg text-gray-700 mb-4">
                        <?php echo esc_html($subtitulo_cta); ?>
                    </p>

                    <?php if ($descripcion_cta): ?>
                        <p class="text-gray-600 mb-8">
                            <?php echo esc_html($descripcion_cta); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Botones CTA -->
                    <div class="flex flex-col sm:flex-row gap-4 mb-8">
                        <a href="<?php echo esc_url($boton_url); ?>"
                           class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transform hover:scale-105 transition-all duration-200 shadow-lg hover:shadow-xl">
                            <?php echo esc_html($boton_texto); ?>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                            </svg>
                        </a>

                        <?php if ($boton_secundario_texto): ?>
                            <a href="<?php echo esc_url($boton_secundario_url); ?>"
                               class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-gray-900 font-semibold rounded-lg border-2 border-gray-300 hover:border-blue-600 hover:text-blue-600 transition-all duration-200">
                                <?php echo esc_html($boton_secundario_texto); ?>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Testimonio rápido o nota -->
                    <div class="flex items-start gap-3 p-4 bg-white rounded-lg border border-gray-200">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-gray-700">
                                <strong class="font-semibold">¿Sabías qué?</strong> Los instructores mejor valorados pueden generar hasta €5,000/mes compartiendo su conocimiento.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Grid de beneficios -->
                <?php if ($mostrar_beneficios && !empty($beneficios_instructor)): ?>
                    <div class="grid sm:grid-cols-2 gap-6">
                        <?php foreach ($beneficios_instructor as $beneficio):
                            $icono_beneficio = $beneficio['icono'] ?? 'certificate';
                            $titulo_beneficio = $beneficio['titulo'] ?? '';
                            $descripcion_beneficio = $beneficio['descripcion'] ?? '';
                            $icono_svg_path = $iconos_svg_disponibles[$icono_beneficio] ?? $iconos_svg_disponibles['certificate'];
                        ?>
                            <div class="bg-white rounded-xl p-6 shadow-md hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <?php echo $icono_svg_path; ?>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2">
                                    <?php echo esc_html($titulo_beneficio); ?>
                                </h3>
                                <p class="text-gray-600 text-sm">
                                    <?php echo esc_html($descripcion_beneficio); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Estadísticas adicionales -->
            <?php if ($mostrar_estadisticas && !empty($estadisticas_instructor)): ?>
                <div class="mt-16 pt-12 border-t border-gray-200">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-8 max-w-3xl mx-auto text-center">
                        <?php foreach ($estadisticas_instructor as $estadistica): ?>
                            <div>
                                <div class="text-3xl sm:text-4xl font-bold text-gray-900 mb-1">
                                    <?php echo esc_html($estadistica['valor']); ?>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <?php echo esc_html($estadistica['etiqueta']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>
<?php endif; ?>
