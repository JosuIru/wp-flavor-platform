<?php
/**
 * Frontend: Single Estacion de Bicicletas
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$estacion = $estacion ?? [];
$nombre = $estacion['nombre'] ?? 'Estacion';
$direccion = $estacion['direccion'] ?? '';
$bicis_disponibles = $estacion['bicis_disponibles'] ?? 0;
$huecos_libres = $estacion['huecos_libres'] ?? 0;
$capacidad = $estacion['capacidad'] ?? 10;
$horario = $estacion['horario'] ?? '24 horas';
?>

<div class="flavor-single bicicletas">
    <!-- Header -->
    <div class="bg-gradient-to-r from-lime-500 to-green-500 py-8 px-4">
        <div class="container mx-auto max-w-4xl">
            <nav class="flex items-center gap-2 text-sm text-white/80 mb-4">
                <a href="#" class="hover:text-white"><?php echo esc_html__('Bicicletas', 'flavor-chat-ia'); ?></a>
                <span>/</span>
                <span class="text-white"><?php echo esc_html($nombre); ?></span>
            </nav>
            <h1 class="text-2xl md:text-3xl font-bold text-white"><?php echo esc_html($nombre); ?></h1>
            <p class="text-white/90"><?php echo esc_html($direccion); ?></p>
        </div>
    </div>

    <div class="container mx-auto max-w-4xl px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Disponibilidad -->
            <div class="bg-white rounded-2xl p-6 shadow-md">
                <h2 class="text-lg font-bold text-gray-900 mb-6"><?php echo esc_html__('Disponibilidad Actual', 'flavor-chat-ia'); ?></h2>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="bg-lime-50 rounded-xl p-4 text-center">
                        <span class="text-4xl font-bold text-lime-600"><?php echo esc_html($bicis_disponibles); ?></span>
                        <p class="text-sm text-gray-600 mt-1"><?php echo esc_html__('Bicis disponibles', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-gray-50 rounded-xl p-4 text-center">
                        <span class="text-4xl font-bold text-gray-600"><?php echo esc_html($huecos_libres); ?></span>
                        <p class="text-sm text-gray-600 mt-1"><?php echo esc_html__('Huecos libres', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <!-- Barra visual -->
                <div class="mb-6">
                    <div class="flex gap-1">
                        <?php for ($i = 0; $i < $capacidad; $i++): ?>
                            <div class="flex-1 h-8 rounded <?php echo $i < $bicis_disponibles ? 'bg-lime-500' : 'bg-gray-200'; ?>"></div>
                        <?php endfor; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-2 text-center">Capacidad total: <?php echo esc_html($capacidad); ?> anclajes</p>
                </div>

                <?php if ($bicis_disponibles > 0): ?>
                    <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #84cc16 0%, #65a30d 100%);">
                        <?php echo esc_html__('Reservar Bicicleta', 'flavor-chat-ia'); ?>
                    </button>
                <?php else: ?>
                    <button class="w-full py-4 rounded-xl text-lg font-semibold text-white bg-gray-400 cursor-not-allowed">
                        <?php echo esc_html__('Sin Bicicletas', 'flavor-chat-ia'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Mapa -->
            <div class="bg-white rounded-2xl shadow-md overflow-hidden">
                <div class="h-64 bg-gray-200 flex items-center justify-center">
                    <div class="text-center text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        </svg>
                        <p class="text-sm"><?php echo esc_html__('Ubicacion en mapa', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
                <div class="p-4">
                    <a href="#" class="flex items-center justify-center gap-2 text-lime-600 font-medium hover:text-lime-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        <?php echo esc_html__('Como llegar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Info adicional -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
            <div class="bg-white rounded-xl p-5 shadow-md">
                <h3 class="font-bold text-gray-900 mb-2"><?php echo esc_html__('Horario', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-600"><?php echo esc_html($horario); ?></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-md">
                <h3 class="font-bold text-gray-900 mb-2"><?php echo esc_html__('Tiempo maximo', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-600"><?php echo esc_html__('30 minutos gratuitos', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="bg-white rounded-xl p-5 shadow-md">
                <h3 class="font-bold text-gray-900 mb-2"><?php echo esc_html__('Tipo de bicis', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-600"><?php echo esc_html__('Mecanicas y electricas', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <!-- Estaciones cercanas -->
        <div class="mt-8">
            <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Estaciones Cercanas', 'flavor-chat-ia'); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <a href="#" class="bg-white rounded-xl p-4 shadow-md hover:shadow-lg transition-shadow flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-lime-100 flex items-center justify-center text-lime-600 font-bold">
                            <?php echo rand(0, 8); ?>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900">Estacion <?php echo $i; ?></p>
                            <p class="text-sm text-gray-500"><?php echo rand(1, 5) * 100; ?>m</p>
                        </div>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</div>
