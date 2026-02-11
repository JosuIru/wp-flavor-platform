<?php
/**
 * Template: Grid de Huertos Urbanos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Huertos Comunitarios';
$descripcion = $descripcion ?? 'Espacios verdes para cultivar en comunidad';

$huertos = [
    ['nombre' => 'Huerto del Parque Central', 'ubicacion' => 'Parque Central, Zona Norte', 'parcelas_total' => 40, 'parcelas_disponibles' => 3, 'tamano' => '15m²', 'imagen' => 'https://picsum.photos/seed/huerto1/600/400', 'servicios' => ['Agua', 'Herramientas', 'Compostera'], 'precio' => '25€/mes'],
    ['nombre' => 'Huerto Escolar Las Acacias', 'ubicacion' => 'C/ Las Acacias, 45', 'parcelas_total' => 25, 'parcelas_disponibles' => 0, 'tamano' => '10m²', 'imagen' => 'https://picsum.photos/seed/huerto2/600/400', 'servicios' => ['Agua', 'Invernadero'], 'precio' => '20€/mes'],
    ['nombre' => 'Huerto Vecinal del Rio', 'ubicacion' => 'Ribera del Rio, Km 2', 'parcelas_total' => 60, 'parcelas_disponibles' => 12, 'tamano' => '20m²', 'imagen' => 'https://picsum.photos/seed/huerto3/600/400', 'servicios' => ['Agua', 'Herramientas', 'Caseta', 'Parking'], 'precio' => '30€/mes'],
    ['nombre' => 'Huerto Intergeneracional', 'ubicacion' => 'Centro Civico Sur', 'parcelas_total' => 30, 'parcelas_disponibles' => 5, 'tamano' => '12m²', 'imagen' => 'https://picsum.photos/seed/huerto4/600/400', 'servicios' => ['Agua', 'Adaptado PMR', 'Formacion'], 'precio' => '15€/mes'],
];

$stats = [
    ['numero' => '8', 'texto' => 'Huertos activos'],
    ['numero' => '285', 'texto' => 'Parcelas totales'],
    ['numero' => '412', 'texto' => 'Hortelanos'],
    ['numero' => '2.5T', 'texto' => 'Cosecha anual'],
];
?>

<section class="flavor-component py-16 bg-gradient-to-b from-green-50 to-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                </svg>
                <?php echo esc_html__('Huertos', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
            <?php foreach ($stats as $stat): ?>
                <div class="bg-white rounded-xl p-5 shadow-md text-center border border-green-100">
                    <div class="text-3xl font-bold text-green-600 mb-1"><?php echo esc_html($stat['numero']); ?></div>
                    <div class="text-sm text-gray-600"><?php echo esc_html($stat['texto']); ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Grid de huertos -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($huertos as $huerto): ?>
                <?php $disponible = $huerto['parcelas_disponibles'] > 0; ?>
                <article class="group bg-white rounded-2xl overflow-hidden shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100">
                    <div class="relative aspect-[16/9] overflow-hidden">
                        <img src="<?php echo esc_url($huerto['imagen']); ?>" alt="<?php echo esc_attr($huerto['nombre']); ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

                        <!-- Badge disponibilidad -->
                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-bold <?php echo $disponible ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                            <?php echo $disponible ? $huerto['parcelas_disponibles'] . ' disponibles' : 'Completo'; ?>
                        </span>

                        <!-- Info superpuesta -->
                        <div class="absolute bottom-4 left-4 right-4">
                            <h3 class="text-2xl font-bold text-white mb-1"><?php echo esc_html($huerto['nombre']); ?></h3>
                            <p class="text-white/80 text-sm flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                </svg>
                                <?php echo esc_html($huerto['ubicacion']); ?>
                            </p>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <span class="text-sm text-gray-500"><?php echo esc_html__('Tamano parcela', 'flavor-chat-ia'); ?></span>
                                <p class="text-lg font-bold text-gray-900"><?php echo esc_html($huerto['tamano']); ?></p>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-gray-500"><?php echo esc_html__('Precio', 'flavor-chat-ia'); ?></span>
                                <p class="text-lg font-bold text-green-600"><?php echo esc_html($huerto['precio']); ?></p>
                            </div>
                        </div>

                        <!-- Barra de ocupacion -->
                        <div class="mb-4">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                <span><?php echo esc_html__('Ocupacion', 'flavor-chat-ia'); ?></span>
                                <span><?php echo $huerto['parcelas_total'] - $huerto['parcelas_disponibles']; ?>/<?php echo esc_html($huerto['parcelas_total']); ?> parcelas</span>
                            </div>
                            <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-green-400 to-green-600" style="width: <?php echo (($huerto['parcelas_total'] - $huerto['parcelas_disponibles']) / $huerto['parcelas_total']) * 100; ?>%"></div>
                            </div>
                        </div>

                        <!-- Servicios -->
                        <div class="flex flex-wrap gap-2 mb-4">
                            <?php foreach ($huerto['servicios'] as $servicio): ?>
                                <span class="px-2 py-1 rounded-lg text-xs font-medium bg-green-100 text-green-700"><?php echo esc_html($servicio); ?></span>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="#solicitar-<?php echo sanitize_title($huerto['nombre']); ?>" class="flex-1 py-3 rounded-xl text-center font-semibold transition-all <?php echo $disponible ? 'text-white hover:scale-105' : 'bg-gray-200 text-gray-500'; ?>" style="<?php echo $disponible ? 'background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);' : ''; ?>">
                                <?php echo $disponible ? 'Solicitar Parcela' : 'Lista de Espera'; ?>
                            </a>
                            <button class="p-3 rounded-xl bg-gray-100 text-gray-600 hover:bg-green-100 hover:text-green-600 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <a href="#todos-huertos" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                <span><?php echo esc_html__('Ver Todos los Huertos', 'flavor-chat-ia'); ?></span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </a>
        </div>
    </div>
</section>
