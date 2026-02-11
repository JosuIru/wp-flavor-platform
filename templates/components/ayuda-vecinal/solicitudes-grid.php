<?php
/**
 * Template: Grid de Solicitudes de Ayuda
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Solicitudes de Ayuda';
$descripcion = $descripcion ?? 'Vecinos que necesitan una mano amiga';

$solicitudes = [
    ['titulo' => 'Necesito ayuda con la compra', 'categoria' => 'Compras', 'descripcion' => 'Soy mayor y me cuesta cargar las bolsas. Necesito alguien que me acompane al supermercado.', 'autor' => 'Carmen, 78 anos', 'ubicacion' => 'Calle Mayor, 15', 'urgencia' => 'media', 'respuestas' => 3, 'fecha' => 'Hace 2 horas'],
    ['titulo' => 'Cuidar mascotas fin de semana', 'categoria' => 'Mascotas', 'descripcion' => 'Viajo por trabajo y necesito alguien que cuide a mi gato Leo del viernes al domingo.', 'autor' => 'Miguel, 35 anos', 'ubicacion' => 'Plaza Central, 8', 'urgencia' => 'baja', 'respuestas' => 5, 'fecha' => 'Hace 5 horas'],
    ['titulo' => 'Reparar grifo urgente', 'categoria' => 'Bricolaje', 'descripcion' => 'Se me ha roto el grifo de la cocina y esta goteando mucho. No tengo herramientas.', 'autor' => 'Laura, 42 anos', 'ubicacion' => 'Av. Libertad, 23', 'urgencia' => 'alta', 'respuestas' => 2, 'fecha' => 'Hace 1 dia'],
    ['titulo' => 'Clases de informatica basica', 'categoria' => 'Educacion', 'descripcion' => 'Me gustaria aprender a usar el movil y el ordenador para hablar con mis nietos.', 'autor' => 'Antonio, 72 anos', 'ubicacion' => 'Calle Luna, 7', 'urgencia' => 'baja', 'respuestas' => 8, 'fecha' => 'Hace 1 dia'],
    ['titulo' => 'Acompanar al medico', 'categoria' => 'Salud', 'descripcion' => 'Tengo cita en el hospital y necesito que alguien me acompane porque no puedo ir sola.', 'autor' => 'Rosa, 81 anos', 'ubicacion' => 'Paseo del Rio, 12', 'urgencia' => 'alta', 'respuestas' => 4, 'fecha' => 'Hace 2 dias'],
    ['titulo' => 'Montar mueble de IKEA', 'categoria' => 'Bricolaje', 'descripcion' => 'Compre una estanteria y no consigo montarla. Alguien con experiencia?', 'autor' => 'David, 28 anos', 'ubicacion' => 'Calle Sol, 45', 'urgencia' => 'baja', 'respuestas' => 6, 'fecha' => 'Hace 3 dias'],
];

$urgencias = ['alta' => ['texto' => 'Urgente', 'color' => 'red'], 'media' => ['texto' => 'Media', 'color' => 'amber'], 'baja' => ['texto' => 'Normal', 'color' => 'green']];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-amber-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                <?php echo esc_html__('Solicitudes', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Filtros rapidos -->
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <button class="px-4 py-2 rounded-full text-sm font-medium transition-all" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white;">
                <?php echo esc_html__('Todas', 'flavor-chat-ia'); ?>
            </button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-red-100 text-red-700 hover:bg-red-200 transition-colors">
                <?php echo esc_html__('🔴 Urgentes', 'flavor-chat-ia'); ?>
            </button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-white text-gray-700 border border-gray-200 hover:bg-amber-50 transition-colors">
                <?php echo esc_html__('Compras', 'flavor-chat-ia'); ?>
            </button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-white text-gray-700 border border-gray-200 hover:bg-amber-50 transition-colors">
                <?php echo esc_html__('Bricolaje', 'flavor-chat-ia'); ?>
            </button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-white text-gray-700 border border-gray-200 hover:bg-amber-50 transition-colors">
                <?php echo esc_html__('Mascotas', 'flavor-chat-ia'); ?>
            </button>
            <button class="px-4 py-2 rounded-full text-sm font-medium bg-white text-gray-700 border border-gray-200 hover:bg-amber-50 transition-colors">
                <?php echo esc_html__('Salud', 'flavor-chat-ia'); ?>
            </button>
        </div>

        <!-- Grid de solicitudes -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($solicitudes as $solicitud): ?>
                <?php $urgenciaInfo = $urgencias[$solicitud['urgencia']]; ?>
                <article class="group bg-white rounded-2xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-gray-100 <?php echo $solicitud['urgencia'] === 'alta' ? 'ring-2 ring-red-200' : ''; ?>">
                    <div class="flex items-start justify-between mb-4">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                            <?php echo esc_html($solicitud['categoria']); ?>
                        </span>
                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-<?php echo $urgenciaInfo['color']; ?>-100 text-<?php echo $urgenciaInfo['color']; ?>-700">
                            <?php echo esc_html($urgenciaInfo['texto']); ?>
                        </span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-amber-600 transition-colors mb-3"><?php echo esc_html($solicitud['titulo']); ?></h3>
                    <p class="text-sm text-gray-600 mb-4 line-clamp-2"><?php echo esc_html($solicitud['descripcion']); ?></p>

                    <div class="flex items-center gap-3 mb-4 pb-4 border-b border-gray-100">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white font-bold">
                            <?php echo esc_html(substr($solicitud['autor'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo esc_html($solicitud['autor']); ?></p>
                            <p class="text-xs text-gray-500 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                <?php echo esc_html($solicitud['ubicacion']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4 text-xs text-gray-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                                </svg>
                                <?php echo esc_html($solicitud['respuestas']); ?> respuestas
                            </span>
                            <span><?php echo esc_html($solicitud['fecha']); ?></span>
                        </div>
                        <button class="px-4 py-2 rounded-xl text-sm font-semibold text-white transition-all hover:scale-105" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                            <?php echo esc_html__('Ayudar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="#todas-solicitudes" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%); color: white;">
                <span><?php echo esc_html__('Ver Todas las Solicitudes', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
