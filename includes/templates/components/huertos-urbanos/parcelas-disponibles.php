<?php
/**
 * Template: Grid de Parcelas Disponibles
 *
 * @package FlavorChatIA
 * @subpackage Templates/Components/HuertosUrbanos
 */

defined('ABSPATH') || exit;

// Sample data - Replace with actual data from database
$parcelas_disponibles = [
    [
        'id' => 1,
        'numero' => 'P-001',
        'huerto' => 'Huerto Norte',
        'tamanyo' => '25 m²',
        'orientacion' => 'Sur',
        'tipo_suelo' => 'Franco-arcilloso',
        'precio_mensual' => 35,
        'caracteristicas' => ['Riego por goteo', 'Sol directo 8h', 'Compost incluido'],
        'estado' => 'disponible',
        'imagen' => 'parcela-1.jpg',
    ],
    [
        'id' => 2,
        'numero' => 'P-002',
        'huerto' => 'Huerto Norte',
        'tamanyo' => '30 m²',
        'orientacion' => 'Sureste',
        'tipo_suelo' => 'Franco',
        'precio_mensual' => 40,
        'caracteristicas' => ['Riego automático', 'Sol directo 6h', 'Caseta cercana'],
        'estado' => 'disponible',
        'imagen' => 'parcela-2.jpg',
    ],
    [
        'id' => 3,
        'numero' => 'P-015',
        'huerto' => 'Huerto Central',
        'tamanyo' => '20 m²',
        'orientacion' => 'Este',
        'tipo_suelo' => 'Arenoso',
        'precio_mensual' => 30,
        'caracteristicas' => ['Riego manual', 'Sol directo 5h', 'Zona sombreada'],
        'estado' => 'disponible',
        'imagen' => 'parcela-3.jpg',
    ],
    [
        'id' => 4,
        'numero' => 'P-023',
        'huerto' => 'Huerto Sur',
        'tamanyo' => '35 m²',
        'orientacion' => 'Sur',
        'tipo_suelo' => 'Franco-arenoso',
        'precio_mensual' => 45,
        'caracteristicas' => ['Riego por goteo', 'Sol directo 9h', 'Invernadero pequeño'],
        'estado' => 'reservada',
        'imagen' => 'parcela-4.jpg',
    ],
    [
        'id' => 5,
        'numero' => 'P-024',
        'huerto' => 'Huerto Sur',
        'tamanyo' => '28 m²',
        'orientacion' => 'Suroeste',
        'tipo_suelo' => 'Franco',
        'precio_mensual' => 38,
        'caracteristicas' => ['Riego por aspersión', 'Sol directo 7h', 'Acceso fácil'],
        'estado' => 'disponible',
        'imagen' => 'parcela-5.jpg',
    ],
    [
        'id' => 6,
        'numero' => 'P-030',
        'huerto' => 'Huerto Sur',
        'tamanyo' => '22 m²',
        'orientacion' => 'Oeste',
        'tipo_suelo' => 'Arcilloso',
        'precio_mensual' => 32,
        'caracteristicas' => ['Riego por goteo', 'Sol directo 6h', 'Zona protegida'],
        'estado' => 'disponible',
        'imagen' => 'parcela-6.jpg',
    ],
];
?>

<section class="py-12 sm:py-16 lg:py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Section Header -->
        <div class="text-center mb-10 sm:mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-medium mb-4">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                </svg>
                <span>Parcelas</span>
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Parcelas Disponibles
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                Elige la parcela que mejor se adapte a tus necesidades. Todas incluyen asesoramiento y acceso a herramientas comunitarias
            </p>
        </div>

        <!-- Filters and Search -->
        <div class="mb-8 bg-gray-50 rounded-xl p-6 shadow-sm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="lg:col-span-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                    <div class="relative">
                        <input type="text"
                               placeholder="Buscar parcela..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>

                <!-- Filter by Huerto -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Huerto</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        <option value="">Todos los huertos</option>
                        <option value="norte">Huerto Norte</option>
                        <option value="central">Huerto Central</option>
                        <option value="sur">Huerto Sur</option>
                        <option value="este">Huerto Este</option>
                    </select>
                </div>

                <!-- Filter by Size -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tamaño</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        <option value="">Todos los tamaños</option>
                        <option value="pequenyo">Pequeño (< 25 m²)</option>
                        <option value="mediano">Mediano (25-30 m²)</option>
                        <option value="grande">Grande (> 30 m²)</option>
                    </select>
                </div>

                <!-- Filter by Price -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Precio mensual</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-colors">
                        <option value="">Todos los precios</option>
                        <option value="bajo">< 35€</option>
                        <option value="medio">35€ - 40€</option>
                        <option value="alto">> 40€</option>
                    </select>
                </div>
            </div>

            <!-- Filter Tags -->
            <div class="mt-4 flex flex-wrap gap-2">
                <button class="px-4 py-2 bg-white border-2 border-green-600 text-green-600 rounded-lg text-sm font-medium hover:bg-green-50 transition-colors">
                    Todas las parcelas
                </button>
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                    Disponibles ahora
                </button>
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                    Con riego automático
                </button>
                <button class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors">
                    Orientación sur
                </button>
            </div>
        </div>

        <!-- Results Count -->
        <div class="mb-6 flex items-center justify-between">
            <p class="text-gray-600">
                Mostrando <span class="font-semibold text-gray-900">6</span> parcelas
                <span class="mx-2">•</span>
                <span class="text-green-600 font-semibold">5 disponibles</span>
            </p>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600">Ordenar por:</label>
                <select class="px-3 py-1 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <option>Más recientes</option>
                    <option>Precio (menor a mayor)</option>
                    <option>Precio (mayor a menor)</option>
                    <option>Tamaño (menor a mayor)</option>
                    <option>Tamaño (mayor a menor)</option>
                </select>
            </div>
        </div>

        <!-- Parcelas Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <?php foreach ($parcelas_disponibles as $parcela): ?>
                <div class="bg-white border-2 border-gray-200 rounded-xl overflow-hidden hover:border-green-500 hover:shadow-xl transition-all duration-300 group <?php echo $parcela['estado'] === 'reservada' ? 'opacity-75' : ''; ?>">

                    <!-- Image Container -->
                    <div class="relative h-48 bg-gradient-to-br from-green-200 to-emerald-300 overflow-hidden">
                        <!-- Placeholder Image - Replace with actual image -->
                        <div class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-20 h-20 text-green-600 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                            </svg>
                        </div>

                        <!-- Status Badge -->
                        <?php if ($parcela['estado'] === 'disponible'): ?>
                            <div class="absolute top-3 right-3 px-3 py-1 bg-green-600 text-white text-xs font-semibold rounded-full shadow-lg">
                                Disponible
                            </div>
                        <?php else: ?>
                            <div class="absolute top-3 right-3 px-3 py-1 bg-gray-600 text-white text-xs font-semibold rounded-full shadow-lg">
                                Reservada
                            </div>
                        <?php endif; ?>

                        <!-- Parcela Number -->
                        <div class="absolute top-3 left-3 w-12 h-12 bg-white rounded-full flex items-center justify-center shadow-lg">
                            <span class="text-sm font-bold text-green-600"><?php echo esc_html(substr($parcela['numero'], -2)); ?></span>
                        </div>

                        <!-- Hover Overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-10 transition-all duration-300"></div>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Header -->
                        <div class="mb-4">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="text-xl font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                                    <?php echo esc_html($parcela['numero']); ?>
                                </h3>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-green-600">
                                        <?php echo esc_html($parcela['precio_mensual']); ?>€
                                    </div>
                                    <div class="text-xs text-gray-500">por mes</div>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <?php echo esc_html($parcela['huerto']); ?>
                            </p>
                        </div>

                        <!-- Specs Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-4 pb-4 border-b border-gray-200">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                </svg>
                                <div>
                                    <div class="text-xs text-gray-500">Tamaño</div>
                                    <div class="text-sm font-semibold text-gray-900"><?php echo esc_html($parcela['tamanyo']); ?></div>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <div>
                                    <div class="text-xs text-gray-500">Orientación</div>
                                    <div class="text-sm font-semibold text-gray-900"><?php echo esc_html($parcela['orientacion']); ?></div>
                                </div>
                            </div>

                            <div class="col-span-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                                </svg>
                                <div>
                                    <div class="text-xs text-gray-500">Tipo de suelo</div>
                                    <div class="text-sm font-semibold text-gray-900"><?php echo esc_html($parcela['tipo_suelo']); ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Features -->
                        <div class="mb-5">
                            <h4 class="text-xs font-semibold text-gray-700 mb-2 uppercase tracking-wide">Características</h4>
                            <div class="space-y-2">
                                <?php foreach ($parcela['caracteristicas'] as $caracteristica): ?>
                                    <div class="flex items-center gap-2 text-sm text-gray-700">
                                        <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span><?php echo esc_html($caracteristica); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <?php if ($parcela['estado'] === 'disponible'): ?>
                            <button class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg transform hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Reservar parcela</span>
                            </button>
                            <button class="w-full mt-2 px-6 py-2 bg-white hover:bg-gray-50 text-green-600 font-medium rounded-lg border-2 border-green-200 transition-colors duration-200 flex items-center justify-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Ver detalles</span>
                            </button>
                        <?php else: ?>
                            <button class="w-full px-6 py-3 bg-gray-300 text-gray-600 font-semibold rounded-lg cursor-not-allowed" disabled>
                                Ya reservada
                            </button>
                            <button class="w-full mt-2 px-6 py-2 bg-white hover:bg-gray-50 text-gray-600 font-medium rounded-lg border-2 border-gray-200 transition-colors duration-200">
                                Lista de espera
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>

        <!-- Load More Button -->
        <div class="mt-12 text-center">
            <button class="inline-flex items-center gap-2 px-8 py-4 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border-2 border-gray-300 hover:border-green-500 shadow-md hover:shadow-lg transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                <span>Cargar más parcelas</span>
            </button>
        </div>

        <!-- Info Banner -->
        <div class="mt-12 bg-gradient-to-r from-green-50 to-emerald-50 border-2 border-green-200 rounded-xl p-6 sm:p-8">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="flex-shrink-0 w-16 h-16 bg-green-600 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-xl font-bold text-gray-900 mb-2">¿No encuentras lo que buscas?</h3>
                    <p class="text-gray-700 mb-4">
                        Contáctanos y te ayudaremos a encontrar la parcela perfecta para ti. También podemos avisarte cuando haya nuevas parcelas disponibles.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <button class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors duration-200">
                            Contactar
                        </button>
                        <button class="px-6 py-2 bg-white hover:bg-gray-50 text-green-600 font-semibold rounded-lg border-2 border-green-600 transition-colors duration-200">
                            Recibir alertas
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
