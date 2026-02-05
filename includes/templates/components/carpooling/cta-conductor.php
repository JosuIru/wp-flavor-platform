<?php
/**
 * Template: CTA Conductor
 *
 * Llamada a la acción para publicar viajes como conductor
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo = $data['titulo'] ?? '¿Tienes coche? Comparte tus viajes';
$texto = $data['texto'] ?? 'Recupera parte del coste de tus desplazamientos habituales';
$boton_texto = $data['boton_texto'] ?? 'Publicar Viaje';
$boton_url = $data['boton_url'] ?? '#';
$color_fondo = $data['color_fondo'] ?? '#3b82f6';

// Convertir color hex a RGB para efectos de transparencia
function hex_to_rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

$color_rgb = hex_to_rgb($color_fondo);
?>

<section class="py-16 sm:py-20 relative overflow-hidden" style="background: linear-gradient(135deg, rgba(<?php echo esc_attr($color_rgb); ?>, 0.95) 0%, rgba(<?php echo esc_attr($color_rgb); ?>, 0.85) 100%);">

    <!-- Elementos decorativos de fondo -->
    <div class="absolute inset-0 overflow-hidden">
        <!-- Patrón de puntos -->
        <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, white 1px, transparent 1px); background-size: 30px 30px;"></div>

        <!-- Círculos decorativos -->
        <div class="absolute top-0 left-0 w-96 h-96 bg-white/10 rounded-full blur-3xl transform -translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 bg-white/10 rounded-full blur-3xl transform translate-x-1/2 translate-y-1/2"></div>

        <!-- Iconos flotantes animados -->
        <div class="absolute top-20 left-10 animate-float">
            <svg class="w-16 h-16 text-white/20" fill="currentColor" viewBox="0 0 20 20">
                <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>
            </svg>
        </div>
        <div class="absolute bottom-20 right-10 animate-float animation-delay-1000">
            <svg class="w-12 h-12 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        <div class="absolute top-1/2 left-1/4 animate-float animation-delay-500">
            <svg class="w-10 h-10 text-white/15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid lg:grid-cols-2 gap-12 items-center">

            <!-- Contenido principal -->
            <div class="text-white space-y-6">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 backdrop-blur-sm rounded-full text-sm font-medium border border-white/30">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Para Conductores</span>
                </div>

                <!-- Título -->
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold leading-tight">
                    <?php echo esc_html($titulo); ?>
                </h2>

                <!-- Texto descriptivo -->
                <p class="text-xl text-white/90 leading-relaxed max-w-xl">
                    <?php echo esc_html($texto); ?>
                </p>

                <!-- Lista de beneficios -->
                <ul class="space-y-4 pt-4">
                    <li class="flex items-center gap-3 text-white/90">
                        <div class="flex-shrink-0 w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="font-medium">Publica tus viajes en 2 minutos</span>
                    </li>
                    <li class="flex items-center gap-3 text-white/90">
                        <div class="flex-shrink-0 w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="font-medium">Elige tus pasajeros y confirma reservas</span>
                    </li>
                    <li class="flex items-center gap-3 text-white/90">
                        <div class="flex-shrink-0 w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="font-medium">Recupera hasta el 50% del coste</span>
                    </li>
                    <li class="flex items-center gap-3 text-white/90">
                        <div class="flex-shrink-0 w-8 h-8 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <span class="font-medium">Sin comisiones ni costes ocultos</span>
                    </li>
                </ul>

                <!-- Botón de acción -->
                <div class="pt-6">
                    <a
                        href="<?php echo esc_url($boton_url); ?>"
                        class="inline-flex items-center gap-3 px-8 py-4 bg-white text-gray-900 font-bold text-lg rounded-xl transition-all duration-200 transform hover:scale-105 hover:shadow-2xl shadow-lg group"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <span><?php echo esc_html($boton_texto); ?></span>
                        <svg class="w-5 h-5 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                        </svg>
                    </a>

                    <p class="text-sm text-white/70 mt-4">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                        Registro seguro y verificado
                    </p>
                </div>
            </div>

            <!-- Tarjeta de ejemplo de viaje -->
            <div class="relative">
                <!-- Tarjeta principal -->
                <div class="bg-white rounded-3xl shadow-2xl p-8 transform hover:scale-105 transition-all duration-300">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-xl shadow-lg">
                                JM
                            </div>
                            <div>
                                <div class="font-bold text-gray-900">Juan Martínez</div>
                                <div class="flex items-center gap-1 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-yellow-400 fill-current" viewBox="0 0 20 20">
                                        <path d="M10 15l-5.878 3.09 1.123-6.545L.489 6.91l6.572-.955L10 0l2.939 5.955 6.572.955-4.756 4.635 1.123 6.545z"/>
                                    </svg>
                                    <span>4.9</span>
                                    <span class="text-gray-400">· 67 viajes</span>
                                </div>
                            </div>
                        </div>
                        <div class="px-4 py-2 bg-green-50 text-green-700 rounded-xl font-bold text-xl">
                            12€
                        </div>
                    </div>

                    <!-- Ruta -->
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start gap-3">
                            <div class="w-3 h-3 rounded-full bg-green-500 mt-1.5 ring-4 ring-green-100"></div>
                            <div>
                                <div class="text-xs text-gray-500">Origen</div>
                                <div class="font-bold text-gray-900">Bilbao Centro</div>
                                <div class="text-sm text-gray-600">Hoy, 18:00</div>
                            </div>
                        </div>

                        <div class="ml-1.5 w-0.5 h-8 bg-gradient-to-b from-green-200 to-red-200"></div>

                        <div class="flex items-start gap-3">
                            <div class="mt-1">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="text-xs text-gray-500">Destino</div>
                                <div class="font-bold text-gray-900">San Sebastián</div>
                                <div class="text-sm text-gray-600">~1h 15min</div>
                            </div>
                        </div>
                    </div>

                    <!-- Detalles del viaje -->
                    <div class="flex items-center gap-3 mb-6 pb-6 border-b border-gray-100">
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span>3 plazas</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Volkswagen Golf</span>
                        </div>
                    </div>

                    <!-- Badges de características -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <span class="px-3 py-1.5 bg-green-50 text-green-700 rounded-lg text-xs font-medium">
                            Mascotas OK
                        </span>
                        <span class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-xs font-medium">
                            Equipaje
                        </span>
                        <span class="px-3 py-1.5 bg-purple-50 text-purple-700 rounded-lg text-xs font-medium">
                            Música
                        </span>
                    </div>

                    <!-- Botón de ejemplo -->
                    <button class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-3 rounded-xl hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <span>Reservar Plaza</span>
                    </button>
                </div>

                <!-- Badge flotante -->
                <div class="absolute -top-4 -right-4 bg-gradient-to-r from-green-500 to-green-600 text-white px-6 py-3 rounded-2xl shadow-xl transform rotate-3 hover:rotate-0 transition-transform">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                        <div>
                            <div class="font-bold">¡Ejemplo!</div>
                            <div class="text-xs text-white/90">Así se verá tu viaje</div>
                        </div>
                    </div>
                </div>

                <!-- Indicador de peticiones -->
                <div class="absolute -bottom-4 -left-4 bg-white rounded-2xl shadow-xl px-5 py-3 transform -rotate-3 hover:rotate-0 transition-transform border-2 border-orange-200">
                    <div class="flex items-center gap-2">
                        <div class="flex -space-x-2">
                            <img src="https://i.pravatar.cc/32?img=15" alt="" class="w-8 h-8 rounded-full border-2 border-white">
                            <img src="https://i.pravatar.cc/32?img=16" alt="" class="w-8 h-8 rounded-full border-2 border-white">
                            <img src="https://i.pravatar.cc/32?img=17" alt="" class="w-8 h-8 rounded-full border-2 border-white">
                        </div>
                        <div>
                            <div class="font-bold text-gray-900">+12</div>
                            <div class="text-xs text-gray-600">Solicitudes</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas finales -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-16 pt-12 border-t border-white/20">
            <div class="text-center text-white">
                <div class="text-4xl font-bold mb-2">850+</div>
                <div class="text-white/80">Conductores activos</div>
            </div>
            <div class="text-center text-white">
                <div class="text-4xl font-bold mb-2">5,200</div>
                <div class="text-white/80">Viajes publicados</div>
            </div>
            <div class="text-center text-white">
                <div class="text-4xl font-bold mb-2">98%</div>
                <div class="text-white/80">Satisfacción</div>
            </div>
            <div class="text-center text-white">
                <div class="text-4xl font-bold mb-2">12k€</div>
                <div class="text-white/80">Ahorrados este mes</div>
            </div>
        </div>
    </div>
</section>

<style>
@keyframes float {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-20px);
    }
}

.animate-float {
    animation: float 6s ease-in-out infinite;
}

.animation-delay-500 {
    animation-delay: 0.5s;
}

.animation-delay-1000 {
    animation-delay: 1s;
}
</style>
