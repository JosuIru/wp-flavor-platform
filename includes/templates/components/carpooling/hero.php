<?php
/**
 * Template: Hero Carpooling
 *
 * Sección hero principal con buscador de viajes compartidos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo = $data['titulo'] ?? 'Comparte tu viaje, ahorra dinero';
$subtitulo = $data['subtitulo'] ?? 'Viaja de forma económica y sostenible con tus vecinos';
$imagen_fondo = $data['imagen_fondo'] ?? '';
$mostrar_buscador = $data['mostrar_buscador'] ?? true;

// Clase de fondo según si hay imagen o no
$clase_fondo = $imagen_fondo
    ? 'bg-cover bg-center bg-no-repeat'
    : 'bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800';
$estilo_fondo = $imagen_fondo ? sprintf('background-image: url(%s);', esc_url($imagen_fondo)) : '';
?>

<section class="relative <?php echo esc_attr($clase_fondo); ?> text-white overflow-hidden" style="<?php echo esc_attr($estilo_fondo); ?>">
    <!-- Overlay para mejorar legibilidad -->
    <?php if ($imagen_fondo): ?>
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/80 to-indigo-900/80"></div>
    <?php endif; ?>

    <!-- Elementos decorativos -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-20 lg:py-28">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            <!-- Contenido principal -->
            <div class="text-center lg:text-left space-y-6 animate-fade-in-up">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 backdrop-blur-sm rounded-full text-sm font-medium border border-white/20">
                    <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Sostenible y Económico</span>
                </div>

                <!-- Título -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold leading-tight">
                    <?php echo esc_html($titulo); ?>
                </h1>

                <!-- Subtítulo -->
                <p class="text-lg sm:text-xl text-blue-100 max-w-2xl mx-auto lg:mx-0">
                    <?php echo esc_html($subtitulo); ?>
                </p>

                <!-- Estadísticas rápidas -->
                <div class="grid grid-cols-3 gap-4 pt-6">
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold">-50%</div>
                        <div class="text-sm text-blue-200">Ahorro medio</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold">1.2T</div>
                        <div class="text-sm text-blue-200">CO₂ ahorrado</div>
                    </div>
                    <div class="text-center lg:text-left">
                        <div class="text-3xl font-bold">350+</div>
                        <div class="text-sm text-blue-200">Viajes activos</div>
                    </div>
                </div>
            </div>

            <!-- Buscador de viajes -->
            <?php if ($mostrar_buscador): ?>
                <div class="animate-fade-in-up animation-delay-200">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 sm:p-8 space-y-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="p-3 bg-blue-600 rounded-xl">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <h2 class="text-2xl font-bold text-gray-900">Buscar Viaje</h2>
                        </div>

                        <form class="space-y-4" action="#" method="GET">
                            <!-- Origen -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <circle cx="10" cy="10" r="8"/>
                                    </svg>
                                    Origen
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        name="origen"
                                        placeholder="¿Desde dónde sales?"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all text-gray-900 placeholder-gray-400"
                                        required
                                    >
                                    <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Destino -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Destino
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        name="destino"
                                        placeholder="¿A dónde vas?"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all text-gray-900 placeholder-gray-400"
                                        required
                                    >
                                    <svg class="absolute right-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Fecha -->
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Fecha
                                </label>
                                <input
                                    type="date"
                                    name="fecha"
                                    min="<?php echo esc_attr(date('Y-m-d')); ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-transparent transition-all text-gray-900"
                                    required
                                >
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-2">
                                <button
                                    type="submit"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-4 px-6 rounded-xl transition-all duration-200 transform hover:scale-105 hover:shadow-lg flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Buscar Viajes
                                </button>

                                <a
                                    href="#publicar-viaje"
                                    class="flex-1 bg-white hover:bg-gray-50 text-blue-600 font-semibold py-4 px-6 rounded-xl border-2 border-blue-600 transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Publicar Viaje
                                </a>
                            </div>
                        </form>

                        <!-- Información adicional -->
                        <div class="flex items-start gap-2 pt-4 border-t border-gray-100">
                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-sm text-gray-600">
                                Encuentra viajes compartidos en tu zona o publica el tuyo para compartir gastos
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Indicador de scroll -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce hidden lg:block">
        <svg class="w-6 h-6 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>

<style>
@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in-up {
    animation: fade-in-up 0.6s ease-out forwards;
}

.animation-delay-200 {
    animation-delay: 0.2s;
    opacity: 0;
}
</style>
