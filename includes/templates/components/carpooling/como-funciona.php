<?php
/**
 * Template: Cómo Funciona
 *
 * Pasos explicativos del proceso de carpooling
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$titulo = $data['titulo'] ?? '¿Cómo funciona?';
$paso1_titulo = $data['paso1_titulo'] ?? 'Busca tu viaje';
$paso1_texto = $data['paso1_texto'] ?? 'Introduce origen, destino y fecha';
$paso2_titulo = $data['paso2_titulo'] ?? 'Reserva tu plaza';
$paso2_texto = $data['paso2_texto'] ?? 'Selecciona el viaje que mejor se ajuste';
$paso3_titulo = $data['paso3_titulo'] ?? '¡Viaja!';
$paso3_texto = $data['paso3_texto'] ?? 'Comparte tu viaje y ahorra';

$pasos = [
    [
        'numero' => '01',
        'titulo' => $paso1_titulo,
        'texto' => $paso1_texto,
        'icono' => '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>',
        'color_fondo' => 'from-blue-500 to-blue-600',
        'color_icono' => 'text-blue-600',
        'color_badge' => 'bg-blue-100 text-blue-600',
    ],
    [
        'numero' => '02',
        'titulo' => $paso2_titulo,
        'texto' => $paso2_texto,
        'icono' => '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>',
        'color_fondo' => 'from-indigo-500 to-indigo-600',
        'color_icono' => 'text-indigo-600',
        'color_badge' => 'bg-indigo-100 text-indigo-600',
    ],
    [
        'numero' => '03',
        'titulo' => $paso3_titulo,
        'texto' => $paso3_texto,
        'icono' => '<svg class="w-full h-full" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"></path>
                    </svg>',
        'color_fondo' => 'from-green-500 to-green-600',
        'color_icono' => 'text-green-600',
        'color_badge' => 'bg-green-100 text-green-600',
    ],
];
?>

<section class="py-16 sm:py-20 bg-white relative overflow-hidden">
    <!-- Elementos decorativos de fondo -->
    <div class="absolute top-0 left-0 w-96 h-96 bg-blue-50 rounded-full blur-3xl opacity-50"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-indigo-50 rounded-full blur-3xl opacity-50"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Encabezado de sección -->
        <div class="text-center mb-16">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 rounded-full text-blue-700 text-sm font-semibold mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Proceso Simple y Rápido</span>
            </div>

            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                En solo tres sencillos pasos estarás compartiendo viajes con tus vecinos
            </p>
        </div>

        <!-- Grid de pasos -->
        <div class="grid md:grid-cols-3 gap-8 lg:gap-12 mb-16">
            <?php foreach ($pasos as $index => $paso): ?>
                <div class="relative group">
                    <!-- Línea conectora (solo en desktop) -->
                    <?php if ($index < count($pasos) - 1): ?>
                        <div class="hidden md:block absolute top-24 left-1/2 w-full h-0.5 bg-gradient-to-r from-gray-200 via-gray-300 to-transparent transform translate-y-1/2 z-0">
                            <div class="absolute right-0 top-1/2 transform -translate-y-1/2">
                                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 5.293a1 1 0 011.414 0l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414-1.414L12.586 11H5a1 1 0 110-2h7.586l-2.293-2.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tarjeta del paso -->
                    <div class="relative bg-white rounded-2xl p-8 shadow-lg hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-2 border border-gray-100 hover:border-<?php echo str_replace(['from-', '-500', '-600'], ['', '', ''], $paso['color_fondo']); ?>-200">

                        <!-- Badge con número -->
                        <div class="absolute -top-4 -right-4 w-16 h-16 <?php echo esc_attr($paso['color_badge']); ?> rounded-full flex items-center justify-center text-2xl font-bold shadow-lg transform group-hover:scale-110 transition-transform">
                            <?php echo esc_html($paso['numero']); ?>
                        </div>

                        <!-- Icono -->
                        <div class="relative mb-6">
                            <div class="w-20 h-20 mx-auto bg-gradient-to-br <?php echo esc_attr($paso['color_fondo']); ?> rounded-2xl p-5 text-white shadow-lg shadow-<?php echo str_replace(['from-', '-500'], ['', ''], $paso['color_fondo']); ?>-500/40 transform group-hover:rotate-6 transition-transform">
                                <?php echo $paso['icono']; ?>
                            </div>

                            <!-- Elemento decorativo detrás del icono -->
                            <div class="absolute inset-0 w-20 h-20 mx-auto bg-gradient-to-br <?php echo esc_attr($paso['color_fondo']); ?> rounded-2xl opacity-20 blur-xl transform scale-110"></div>
                        </div>

                        <!-- Contenido -->
                        <div class="text-center space-y-3">
                            <h3 class="text-2xl font-bold text-gray-900">
                                <?php echo esc_html($paso['titulo']); ?>
                            </h3>
                            <p class="text-gray-600 leading-relaxed">
                                <?php echo esc_html($paso['texto']); ?>
                            </p>
                        </div>

                        <!-- Decoración inferior -->
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r <?php echo esc_attr($paso['color_fondo']); ?> transform scale-x-0 group-hover:scale-x-100 transition-transform duration-300 rounded-b-2xl"></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Sección de beneficios adicionales -->
        <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-3xl p-8 lg:p-12 border border-gray-100">
            <div class="grid lg:grid-cols-2 gap-8 items-center">

                <!-- Beneficios -->
                <div class="space-y-6">
                    <h3 class="text-2xl lg:text-3xl font-bold text-gray-900 mb-6">
                        Ventajas de compartir viaje
                    </h3>

                    <div class="space-y-4">
                        <!-- Beneficio 1 -->
                        <div class="flex items-start gap-4 group cursor-pointer">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">Ahorra hasta un 50%</h4>
                                <p class="text-gray-600 text-sm">Comparte los gastos de combustible, peajes y parking</p>
                            </div>
                        </div>

                        <!-- Beneficio 2 -->
                        <div class="flex items-start gap-4 group cursor-pointer">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">Reduce tu huella de carbono</h4>
                                <p class="text-gray-600 text-sm">Ayuda al medio ambiente con menos coches en la carretera</p>
                            </div>
                        </div>

                        <!-- Beneficio 3 -->
                        <div class="flex items-start gap-4 group cursor-pointer">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">Conecta con tu comunidad</h4>
                                <p class="text-gray-600 text-sm">Conoce a tus vecinos y haz nuevas amistades</p>
                            </div>
                        </div>

                        <!-- Beneficio 4 -->
                        <div class="flex items-start gap-4 group cursor-pointer">
                            <div class="flex-shrink-0 w-12 h-12 bg-gradient-to-br from-orange-500 to-orange-600 rounded-xl flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-900 mb-1">Viaja seguro y verificado</h4>
                                <p class="text-gray-600 text-sm">Sistema de valoraciones y conductores verificados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ilustración -->
                <div class="relative hidden lg:block">
                    <div class="relative">
                        <!-- Tarjeta de estadísticas flotante -->
                        <div class="absolute top-0 right-0 bg-white rounded-2xl shadow-2xl p-6 transform rotate-3 hover:rotate-0 transition-transform z-10">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                                <div>
                                    <div class="text-2xl font-bold text-gray-900">2.5T</div>
                                    <div class="text-sm text-gray-600">CO₂ Ahorrado</div>
                                </div>
                            </div>
                        </div>

                        <!-- Icono principal de coche -->
                        <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-3xl p-12 shadow-2xl">
                            <svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>

                        <!-- Tarjeta de usuarios flotante -->
                        <div class="absolute bottom-0 left-0 bg-white rounded-2xl shadow-2xl p-6 transform -rotate-3 hover:rotate-0 transition-transform">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="flex -space-x-2">
                                    <img src="https://i.pravatar.cc/40?img=1" alt="Usuario" class="w-8 h-8 rounded-full border-2 border-white">
                                    <img src="https://i.pravatar.cc/40?img=2" alt="Usuario" class="w-8 h-8 rounded-full border-2 border-white">
                                    <img src="https://i.pravatar.cc/40?img=3" alt="Usuario" class="w-8 h-8 rounded-full border-2 border-white">
                                </div>
                                <div class="text-xl font-bold text-gray-900">+850</div>
                            </div>
                            <div class="text-sm text-gray-600">Usuarios activos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Final -->
        <div class="text-center mt-12">
            <a
                href="#buscar-viaje"
                class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 transform hover:scale-105 shadow-lg shadow-blue-500/30"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span>Empezar Ahora</span>
            </a>
        </div>
    </div>
</section>
