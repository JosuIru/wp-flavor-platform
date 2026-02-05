<?php
/**
 * Template: Tipos de Bicicletas - Bicicletas Compartidas
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var bool $mostrar_precios
 * @var string $component_classes
 */

if (!defined('ABSPATH')) exit;

// Tipos de bicicletas disponibles
$tipos_bicicletas = [
    [
        'id' => 1,
        'nombre' => 'Bici Urbana',
        'descripcion' => 'Perfecta para trayectos cortos por la ciudad. Cómoda y fácil de usar.',
        'caracteristicas' => [
            'Cesta delantera',
            'Luces LED',
            'Frenos de disco',
            '3 velocidades',
            'Sillín ergonómico',
        ],
        'disponibles' => 25,
        'precio_30min' => 0.50,
        'precio_hora' => 0.90,
        'icono_color' => 'blue',
        'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />',
    ],
    [
        'id' => 2,
        'nombre' => 'Bici Eléctrica',
        'descripcion' => 'Asistencia eléctrica hasta 25 km/h. Ideal para largas distancias sin esfuerzo.',
        'caracteristicas' => [
            'Motor eléctrico 250W',
            'Batería 40km autonomía',
            'Pantalla digital',
            'Frenos hidráulicos',
            'GPS integrado',
        ],
        'disponibles' => 12,
        'precio_30min' => 1.20,
        'precio_hora' => 2.00,
        'icono_color' => 'emerald',
        'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />',
        'destacada' => true,
    ],
    [
        'id' => 3,
        'nombre' => 'Cargo Bike',
        'descripcion' => 'Para transportar compras, niños o mascotas. Gran capacidad de carga.',
        'caracteristicas' => [
            'Caja frontal 100kg',
            'Asientos para niños',
            'Eléctrica opcional',
            'Estabilidad máxima',
            'Cinturones seguridad',
        ],
        'disponibles' => 6,
        'precio_30min' => 1.50,
        'precio_hora' => 2.50,
        'icono_color' => 'orange',
        'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />',
    ],
    [
        'id' => 4,
        'nombre' => 'MTB Urbana',
        'descripcion' => 'Mountain bike adaptada para ciudad. Resistente y versátil.',
        'caracteristicas' => [
            'Suspensión delantera',
            '21 velocidades',
            'Neumáticos todoterreno',
            'Portabicicletas compatible',
            'Frenos V-brake',
        ],
        'disponibles' => 8,
        'precio_30min' => 0.80,
        'precio_hora' => 1.40,
        'icono_color' => 'purple',
        'icono' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />',
    ],
];
?>

<section class="py-16 bg-gradient-to-b from-gray-50 to-white <?php echo esc_attr($component_classes); ?>">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12">
                <h2 class="text-4xl md:text-5xl font-black text-gray-900 mb-4">
                    <?php echo esc_html($titulo ?? 'Tipos de Bicicletas'); ?>
                </h2>
                <?php if (!empty($subtitulo)): ?>
                    <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                        <?php echo esc_html($subtitulo ?? 'Elige la bicicleta perfecta para tu viaje'); ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Grid de bicicletas -->
            <div class="grid md:grid-cols-2 gap-8 mb-12">
                <?php foreach ($tipos_bicicletas as $bicicleta):
                    $color_icono = $bicicleta['icono_color'];
                    $es_destacada = !empty($bicicleta['destacada']);
                ?>
                <div class="group bg-white rounded-2xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 <?php echo $es_destacada ? 'ring-4 ring-emerald-500 relative' : ''; ?>">

                    <?php if ($es_destacada): ?>
                    <!-- Badge de destacada -->
                    <div class="absolute top-4 right-4 z-10 px-4 py-2 bg-emerald-500 text-white text-sm font-bold rounded-full shadow-lg flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                        </svg>
                        Popular
                    </div>
                    <?php endif; ?>

                    <!-- Header con icono -->
                    <div class="relative h-64 bg-gradient-to-br from-<?php echo $color_icono; ?>-400 to-<?php echo $color_icono; ?>-600 overflow-hidden">
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-40 h-40 text-white/20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $bicicleta['icono']; ?>
                            </svg>
                        </div>

                        <!-- Ilustración de bicicleta central -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="text-center">
                                <svg class="w-32 h-32 mx-auto text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                </svg>
                            </div>
                        </div>

                        <!-- Badge de disponibilidad -->
                        <div class="absolute bottom-4 left-4 px-4 py-2 bg-black/50 backdrop-blur-sm rounded-lg text-white font-bold flex items-center gap-2">
                            <div class="w-3 h-3 bg-emerald-400 rounded-full animate-pulse"></div>
                            <span><?php echo esc_html($bicicleta['disponibles']); ?> disponibles</span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <h3 class="text-2xl font-bold text-gray-900 mb-3 group-hover:text-<?php echo $color_icono; ?>-600 transition-colors">
                            <?php echo esc_html($bicicleta['nombre']); ?>
                        </h3>
                        <p class="text-gray-600 mb-6">
                            <?php echo esc_html($bicicleta['descripcion']); ?>
                        </p>

                        <!-- Características -->
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-gray-700 mb-3 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-<?php echo $color_icono; ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Características
                            </h4>
                            <div class="grid grid-cols-2 gap-2">
                                <?php foreach ($bicicleta['caracteristicas'] as $caracteristica): ?>
                                <div class="flex items-center text-sm text-gray-600">
                                    <svg class="w-4 h-4 mr-2 text-<?php echo $color_icono; ?>-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span><?php echo esc_html($caracteristica); ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <?php if (!empty($mostrar_precios)): ?>
                        <!-- Precios -->
                        <div class="bg-gradient-to-r from-<?php echo $color_icono; ?>-50 to-<?php echo $color_icono; ?>-100 rounded-xl p-4 mb-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-<?php echo $color_icono; ?>-700">
                                        €<?php echo number_format($bicicleta['precio_30min'], 2); ?>
                                    </div>
                                    <div class="text-xs text-gray-600 font-medium">30 minutos</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-<?php echo $color_icono; ?>-700">
                                        €<?php echo number_format($bicicleta['precio_hora'], 2); ?>
                                    </div>
                                    <div class="text-xs text-gray-600 font-medium">1 hora</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Botón de acción -->
                        <button class="w-full px-6 py-3 bg-<?php echo $color_icono; ?>-600 hover:bg-<?php echo $color_icono; ?>-700 text-white font-bold rounded-xl transition-all duration-300 transform group-hover:scale-105 shadow-lg flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Desbloquear Ahora
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Cómo funciona -->
            <div class="bg-gradient-to-br from-blue-600 to-cyan-600 rounded-3xl p-8 md:p-12 text-white">
                <h3 class="text-3xl font-black mb-8 text-center">¿Cómo Funciona?</h3>

                <div class="grid md:grid-cols-4 gap-8">
                    <!-- Paso 1 -->
                    <div class="text-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-black text-blue-600">1</span>
                            </div>
                        </div>
                        <h4 class="text-xl font-bold mb-2">Encuentra</h4>
                        <p class="text-blue-100 text-sm">Localiza la estación más cercana en el mapa</p>
                    </div>

                    <!-- Paso 2 -->
                    <div class="text-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-black text-blue-600">2</span>
                            </div>
                        </div>
                        <h4 class="text-xl font-bold mb-2">Escanea</h4>
                        <p class="text-blue-100 text-sm">Escanea el código QR de la bicicleta elegida</p>
                    </div>

                    <!-- Paso 3 -->
                    <div class="text-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-black text-blue-600">3</span>
                            </div>
                        </div>
                        <h4 class="text-xl font-bold mb-2">Pedalea</h4>
                        <p class="text-blue-100 text-sm">Disfruta tu viaje de forma sostenible</p>
                    </div>

                    <!-- Paso 4 -->
                    <div class="text-center">
                        <div class="w-20 h-20 bg-white/20 backdrop-blur-sm rounded-full flex items-center justify-center mx-auto mb-4">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center">
                                <span class="text-3xl font-black text-blue-600">4</span>
                            </div>
                        </div>
                        <h4 class="text-xl font-bold mb-2">Devuelve</h4>
                        <p class="text-blue-100 text-sm">Ancla en cualquier estación disponible</p>
                    </div>
                </div>

                <!-- CTA -->
                <div class="text-center mt-10">
                    <a href="#descargar-app" class="inline-flex items-center gap-3 px-8 py-4 bg-white text-blue-600 font-bold rounded-xl hover:bg-blue-50 transition-colors shadow-2xl">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        Descargar App Gratuita
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Beneficios ambientales -->
            <div class="mt-12 grid md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-emerald-500">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-2">Cero Emisiones</h4>
                    <p class="text-gray-600">Reduce tu huella de carbono y contribuye al medio ambiente</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-blue-500">
                    <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-2">Salud y Bienestar</h4>
                    <p class="text-gray-600">Ejercicio diario mientras te desplazas por la ciudad</p>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6 text-center border-t-4 border-purple-500">
                    <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 mb-2">Comunidad Activa</h4>
                    <p class="text-gray-600">Únete a miles de vecinos que ya pedalean</p>
                </div>
            </div>
        </div>
    </div>
</section>
