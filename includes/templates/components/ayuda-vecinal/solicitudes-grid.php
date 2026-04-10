<?php
/**
 * Template: Grid de Solicitudes de Ayuda
 *
 * Muestra las solicitudes activas de ayuda vecinal en formato grid
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Extraer variables del array $data
$solicitudes_ayuda = $data['solicitudes'] ?? [];
$mostrar_filtros = $data['mostrar_filtros'] ?? true;
$titulo_seccion = $data['titulo'] ?? 'Solicitudes de Ayuda Activas';
$subtitulo_seccion = $data['subtitulo'] ?? 'Encuentra una forma de ayudar a tus vecinos';
?>

<section class="py-12 sm:py-16 lg:py-20 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Encabezado de sección -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-rose-100 rounded-full text-rose-700 text-sm font-medium mb-4">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                </svg>
                Ayuda Comunitaria
            </div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                <?php echo esc_html($titulo_seccion); ?>
            </h2>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                <?php echo esc_html($subtitulo_seccion); ?>
            </p>
        </div>

        <!-- Filtros -->
        <?php if ($mostrar_filtros): ?>
            <div class="mb-8 flex flex-wrap gap-3 justify-center">
                <button class="px-4 py-2 bg-rose-600 text-white rounded-full font-medium hover:bg-rose-700 transition-colors">
                    Todas
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-300 transition-colors">
                    Transporte
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-300 transition-colors">
                    Compras
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-300 transition-colors">
                    Acompañamiento
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-300 transition-colors">
                    Gestiones
                </button>
                <button class="px-4 py-2 bg-white text-gray-700 rounded-full font-medium hover:bg-gray-100 border border-gray-300 transition-colors">
                    Urgentes
                </button>
            </div>
        <?php endif; ?>

        <!-- Grid de solicitudes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">

            <?php if (empty($solicitudes_ayuda)): ?>
                <!-- Solicitudes de ejemplo si no hay datos -->

                <!-- Solicitud 1 -->
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-rose-200 transform hover:-translate-y-1">
                    <div class="p-6 space-y-4">
                        <!-- Header con urgencia -->
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">Ayuda con compra</h3>
                                    <p class="text-sm text-gray-500">Hace 2 horas</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full whitespace-nowrap">
                                Urgente
                            </span>
                        </div>

                        <!-- Descripción -->
                        <p class="text-gray-600 line-clamp-3">
                            Necesito ayuda para hacer la compra semanal. Tengo movilidad reducida y me cuesta mucho cargar con las bolsas.
                        </p>

                        <!-- Detalles -->
                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Zona Centro</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Mañana, 10:00</span>
                            </div>
                        </div>

                        <!-- Tags -->
                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-rose-50 text-rose-700 text-xs font-medium rounded-lg">
                                Compras
                            </span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg">
                                1-2 horas
                            </span>
                        </div>

                        <!-- Botón de acción -->
                        <button class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2 mt-4">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            Quiero Ayudar
                        </button>
                    </div>
                </div>

                <!-- Solicitud 2 -->
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-rose-200 transform hover:-translate-y-1">
                    <div class="p-6 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">Acompañamiento médico</h3>
                                    <p class="text-sm text-gray-500">Hace 5 horas</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full whitespace-nowrap">
                                Esta semana
                            </span>
                        </div>

                        <p class="text-gray-600 line-clamp-3">
                            Busco a alguien que pueda acompañarme al centro de salud el jueves. Es una consulta rutinaria pero me siento más segura con compañía.
                        </p>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Barrio Norte</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Jueves, 11:30</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-blue-50 text-blue-700 text-xs font-medium rounded-lg">
                                Acompañamiento
                            </span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg">
                                2-3 horas
                            </span>
                        </div>

                        <button class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2 mt-4">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            Quiero Ayudar
                        </button>
                    </div>
                </div>

                <!-- Solicitud 3 -->
                <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-rose-200 transform hover:-translate-y-1">
                    <div class="p-6 space-y-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-pink-600 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 text-lg">Ayuda con tecnología</h3>
                                    <p class="text-sm text-gray-500">Hace 1 día</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-semibold rounded-full whitespace-nowrap">
                                Flexible
                            </span>
                        </div>

                        <p class="text-gray-600 line-clamp-3">
                            Me gustaría que alguien me ayudara a configurar mi nueva tablet para poder hacer videollamadas con mi familia.
                        </p>

                        <div class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span>Zona Sur</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm text-gray-600">
                                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Cuando puedas</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="px-2 py-1 bg-purple-50 text-purple-700 text-xs font-medium rounded-lg">
                                Tecnología
                            </span>
                            <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg">
                                1 hora
                            </span>
                        </div>

                        <button class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2 mt-4">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            Quiero Ayudar
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <!-- Mostrar solicitudes reales del array -->
                <?php foreach ($solicitudes_ayuda as $solicitud): ?>
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100 hover:border-rose-200 transform hover:-translate-y-1">
                        <div class="p-6 space-y-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-gradient-to-br from-rose-500 to-pink-600 rounded-full flex items-center justify-center flex-shrink-0">
                                        <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 text-lg"><?php echo esc_html($solicitud['titulo']); ?></h3>
                                        <p class="text-sm text-gray-500"><?php echo esc_html($solicitud['fecha']); ?></p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-semibold rounded-full whitespace-nowrap">
                                    <?php echo esc_html($solicitud['urgencia']); ?>
                                </span>
                            </div>

                            <p class="text-gray-600 line-clamp-3">
                                <?php echo esc_html($solicitud['descripcion']); ?>
                            </p>

                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span><?php echo esc_html($solicitud['zona']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span><?php echo esc_html($solicitud['horario']); ?></span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <span class="px-2 py-1 bg-rose-50 text-rose-700 text-xs font-medium rounded-lg">
                                    <?php echo esc_html($solicitud['categoria']); ?>
                                </span>
                                <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg">
                                    <?php echo esc_html($solicitud['duracion']); ?>
                                </span>
                            </div>

                            <button class="w-full bg-rose-600 hover:bg-rose-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 transform hover:scale-105 flex items-center justify-center gap-2 mt-4">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                                </svg>
                                Quiero Ayudar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>

        <!-- Botón para ver más -->
        <div class="text-center mt-12">
            <button class="inline-flex items-center gap-2 px-8 py-4 bg-white text-rose-600 font-semibold rounded-xl border-2 border-rose-600 hover:bg-rose-50 transition-all duration-200 transform hover:scale-105 shadow-lg">
                Ver Más Solicitudes
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
        </div>
    </div>
</section>
