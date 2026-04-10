<?php
/**
 * Template: Hero Section - Cursos
 *
 * Hero section destacada para la página principal de cursos
 *
 * @package FlavorPlatform
 * @subpackage Templates/Components/Cursos
 */

defined('ABSPATH') || exit;

// Valores por defecto
$titulo_principal = $args['titulo_principal'] ?? 'Aprende y Crece con Nuestros Cursos';
$descripcion_hero = $args['descripcion_hero'] ?? 'Descubre una amplia variedad de cursos diseñados para impulsar tu desarrollo personal y profesional. Aprende a tu ritmo, desde cualquier lugar.';
$boton_principal_texto = $args['boton_principal_texto'] ?? 'Explorar Cursos';
$boton_principal_url = $args['boton_principal_url'] ?? '#cursos';
$boton_secundario_texto = $args['boton_secundario_texto'] ?? 'Ver Categorías';
$boton_secundario_url = $args['boton_secundario_url'] ?? '#categorias';
$imagen_fondo = $args['imagen_fondo'] ?? '';
$mostrar_estadisticas = $args['mostrar_estadisticas'] ?? true;
$total_cursos = $args['total_cursos'] ?? 0;
$total_estudiantes = $args['total_estudiantes'] ?? 0;
$total_instructores = $args['total_instructores'] ?? 0;
?>

<section class="relative bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 overflow-hidden">
    <!-- Patrón de fondo decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <!-- Imagen de fondo opcional -->
    <?php if ($imagen_fondo): ?>
        <div class="absolute inset-0 opacity-20">
            <img src="<?php echo esc_url($imagen_fondo); ?>" alt="" class="w-full h-full object-cover">
        </div>
    <?php endif; ?>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Contenido principal -->
            <div class="text-center lg:text-left">
                <!-- Badge decorativo -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-white text-sm font-medium mb-6">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span>Formación Online de Calidad</span>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white mb-6 leading-tight">
                    <?php echo esc_html($titulo_principal); ?>
                </h1>

                <p class="text-lg sm:text-xl text-blue-100 mb-8 max-w-2xl mx-auto lg:mx-0">
                    <?php echo esc_html($descripcion_hero); ?>
                </p>

                <!-- Botones CTA -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start mb-12">
                    <a href="<?php echo esc_url($boton_principal_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white text-blue-700 font-semibold rounded-lg hover:bg-blue-50 transform hover:scale-105 transition-all duration-200 shadow-xl hover:shadow-2xl">
                        <?php echo esc_html($boton_principal_texto); ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>

                    <a href="<?php echo esc_url($boton_secundario_url); ?>"
                       class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-white/10 backdrop-blur-sm text-white font-semibold rounded-lg border-2 border-white/30 hover:bg-white/20 transition-all duration-200">
                        <?php echo esc_html($boton_secundario_texto); ?>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </a>
                </div>

                <!-- Estadísticas -->
                <?php if ($mostrar_estadisticas && ($total_cursos > 0 || $total_estudiantes > 0 || $total_instructores > 0)): ?>
                    <div class="grid grid-cols-3 gap-6 max-w-md mx-auto lg:mx-0">
                        <?php if ($total_cursos > 0): ?>
                            <div class="text-center lg:text-left">
                                <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                    <?php echo number_format($total_cursos, 0, ',', '.'); ?>+
                                </div>
                                <div class="text-sm text-blue-200">Cursos</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($total_estudiantes > 0): ?>
                            <div class="text-center lg:text-left">
                                <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                    <?php echo number_format($total_estudiantes, 0, ',', '.'); ?>+
                                </div>
                                <div class="text-sm text-blue-200">Estudiantes</div>
                            </div>
                        <?php endif; ?>

                        <?php if ($total_instructores > 0): ?>
                            <div class="text-center lg:text-left">
                                <div class="text-3xl sm:text-4xl font-bold text-white mb-1">
                                    <?php echo number_format($total_instructores, 0, ',', '.'); ?>+
                                </div>
                                <div class="text-sm text-blue-200">Instructores</div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ilustración decorativa -->
            <div class="hidden lg:block">
                <div class="relative">
                    <!-- Círculos decorativos de fondo -->
                    <div class="absolute top-0 right-0 w-72 h-72 bg-white/10 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-400/20 rounded-full blur-3xl"></div>

                    <!-- Tarjetas flotantes -->
                    <div class="relative z-10 space-y-4">
                        <!-- Tarjeta 1 -->
                        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-2xl transform hover:scale-105 transition-all duration-300 ml-12">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">Contenido Actualizado</h3>
                                    <p class="text-sm text-gray-600">Material didáctico de calidad</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 2 -->
                        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-2xl transform hover:scale-105 transition-all duration-300 mr-12">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">Certificados</h3>
                                    <p class="text-sm text-gray-600">Reconocimiento al completar</p>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta 3 -->
                        <div class="bg-white/95 backdrop-blur-sm rounded-2xl p-6 shadow-2xl transform hover:scale-105 transition-all duration-300 ml-12">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0 w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 mb-1">Aprende a tu Ritmo</h3>
                                    <p class="text-sm text-gray-600">Acceso 24/7 ilimitado</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onda decorativa inferior -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg class="w-full h-auto" viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="white"/>
        </svg>
    </div>
</section>
