<?php
/**
 * Hero Section Template - Talleres
 *
 * Displays the hero section for the workshops page
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/Talleres
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Default values
$hero_titulo = $args['titulo'] ?? 'Talleres y Aprendizaje Comunitario';
$hero_subtitulo = $args['subtitulo'] ?? 'Aprende nuevas habilidades y comparte conocimientos con tu comunidad';
$hero_descripcion = $args['descripcion'] ?? 'Descubre talleres prácticos sobre agricultura ecológica, artesanía tradicional, tecnologías sostenibles y mucho más. Todos los talleres son impartidos por miembros de nuestra comunidad.';
$mostrar_busqueda = $args['mostrar_busqueda'] ?? true;
$imagen_fondo = $args['imagen_fondo'] ?? '';
$mostrar_estadisticas = $args['mostrar_estadisticas'] ?? true;
?>

<section class="relative bg-gradient-to-br from-emerald-600 via-green-600 to-teal-700 text-white overflow-hidden">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"1\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
    </div>

    <?php if ($imagen_fondo): ?>
    <!-- Background Image Overlay -->
    <div class="absolute inset-0 bg-cover bg-center opacity-20" style="background-image: url('<?php echo esc_url($imagen_fondo); ?>');"></div>
    <?php endif; ?>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-24">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Content Column -->
            <div class="space-y-6">
                <!-- Icon Badge -->
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm rounded-full px-4 py-2 text-sm font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Aprendizaje Colaborativo</span>
                </div>

                <!-- Title -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                    <?php echo esc_html($hero_titulo); ?>
                </h1>

                <!-- Subtitle -->
                <p class="text-xl sm:text-2xl text-emerald-50 font-medium">
                    <?php echo esc_html($hero_subtitulo); ?>
                </p>

                <!-- Description -->
                <p class="text-lg text-emerald-100 leading-relaxed">
                    <?php echo esc_html($hero_descripcion); ?>
                </p>

                <!-- Search Bar -->
                <?php if ($mostrar_busqueda): ?>
                <div class="pt-4">
                    <form action="<?php echo esc_url(home_url('/talleres')); ?>" method="get" class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text"
                                   name="buscar"
                                   placeholder="Buscar talleres por nombre, categoría o instructor..."
                                   class="block w-full pl-12 pr-4 py-4 rounded-lg bg-white text-gray-900 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent shadow-lg">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-amber-500 hover:bg-amber-600 text-white font-semibold rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            <span class="hidden sm:inline">Buscar</span>
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Statistics -->
                <?php if ($mostrar_estadisticas): ?>
                <div class="grid grid-cols-3 gap-6 pt-8 border-t border-white/20">
                    <div class="text-center sm:text-left">
                        <div class="text-3xl sm:text-4xl font-bold text-white">
                            <?php echo esc_html($args['total_talleres'] ?? '24+'); ?>
                        </div>
                        <div class="text-sm text-emerald-100 mt-1">Talleres Activos</div>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="text-3xl sm:text-4xl font-bold text-white">
                            <?php echo esc_html($args['total_instructores'] ?? '15+'); ?>
                        </div>
                        <div class="text-sm text-emerald-100 mt-1">Instructores</div>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="text-3xl sm:text-4xl font-bold text-white">
                            <?php echo esc_html($args['total_participantes'] ?? '200+'); ?>
                        </div>
                        <div class="text-sm text-emerald-100 mt-1">Participantes</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Illustration Column -->
            <div class="hidden lg:block">
                <div class="relative">
                    <!-- Main Workshop Illustration -->
                    <div class="bg-white/10 backdrop-blur-md rounded-2xl p-8 shadow-2xl border border-white/20">
                        <div class="space-y-6">
                            <!-- Workshop Icon -->
                            <div class="flex items-center justify-center w-20 h-20 bg-amber-500 rounded-xl shadow-lg mx-auto">
                                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
                                </svg>
                            </div>

                            <!-- Floating Tools -->
                            <div class="grid grid-cols-3 gap-4">
                                <!-- Tool 1 -->
                                <div class="bg-white/20 rounded-lg p-4 transform hover:scale-105 transition-transform">
                                    <svg class="w-8 h-8 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                </div>
                                <!-- Tool 2 -->
                                <div class="bg-white/20 rounded-lg p-4 transform hover:scale-105 transition-transform">
                                    <svg class="w-8 h-8 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                    </svg>
                                </div>
                                <!-- Tool 3 -->
                                <div class="bg-white/20 rounded-lg p-4 transform hover:scale-105 transition-transform">
                                    <svg class="w-8 h-8 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Progress Indicator -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-xs text-white/80">
                                    <span>Próximo taller</span>
                                    <span>En 2 días</span>
                                </div>
                                <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full w-3/4 bg-gradient-to-r from-amber-400 to-amber-500 rounded-full"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Floating Badge -->
                    <div class="absolute -top-4 -right-4 bg-amber-500 text-white px-4 py-2 rounded-full shadow-lg transform rotate-12">
                        <div class="text-xs font-semibold">¡Nuevo!</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Wave Divider -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg class="w-full h-12 sm:h-16 text-white" preserveAspectRatio="none" viewBox="0 0 1200 120" fill="currentColor">
            <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z"></path>
        </svg>
    </div>
</section>
