<?php
/**
 * Template: Calendario de Cultivos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Calendario de Cultivos';
$descripcion = $descripcion ?? 'Guia estacional para planificar tu huerto';

$meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
$mes_actual = 1; // Febrero (0-indexed: Enero=0)

$cultivos = [
    ['nombre' => 'Tomate', 'icono' => '🍅', 'siembra' => [2, 3, 4], 'cosecha' => [6, 7, 8, 9], 'categoria' => 'Hortalizas'],
    ['nombre' => 'Lechuga', 'icono' => '🥬', 'siembra' => [0, 1, 2, 8, 9, 10], 'cosecha' => [2, 3, 4, 10, 11], 'categoria' => 'Hortalizas'],
    ['nombre' => 'Zanahoria', 'icono' => '🥕', 'siembra' => [1, 2, 3, 7, 8], 'cosecha' => [4, 5, 6, 10, 11], 'categoria' => 'Raices'],
    ['nombre' => 'Pimiento', 'icono' => '🫑', 'siembra' => [2, 3, 4], 'cosecha' => [6, 7, 8, 9], 'categoria' => 'Hortalizas'],
    ['nombre' => 'Calabacin', 'icono' => '🥒', 'siembra' => [3, 4, 5], 'cosecha' => [5, 6, 7, 8, 9], 'categoria' => 'Hortalizas'],
    ['nombre' => 'Fresa', 'icono' => '🍓', 'siembra' => [8, 9, 10], 'cosecha' => [3, 4, 5], 'categoria' => 'Frutas'],
    ['nombre' => 'Ajo', 'icono' => '🧄', 'siembra' => [9, 10, 11], 'cosecha' => [5, 6], 'categoria' => 'Bulbos'],
    ['nombre' => 'Cebolla', 'icono' => '🧅', 'siembra' => [0, 1, 8, 9], 'cosecha' => [5, 6, 7], 'categoria' => 'Bulbos'],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <?php echo esc_html__('Calendario', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <!-- Leyenda -->
        <div class="flex items-center justify-center gap-6 mb-8">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded bg-amber-200 border-2 border-amber-400"></span>
                <span class="text-sm text-gray-600"><?php echo esc_html__('Siembra', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded bg-green-200 border-2 border-green-400"></span>
                <span class="text-sm text-gray-600"><?php echo esc_html__('Cosecha', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded bg-gradient-to-r from-amber-200 to-green-200 border-2 border-lime-400"></span>
                <span class="text-sm text-gray-600"><?php echo esc_html__('Ambos', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <!-- Tabla calendario -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[800px]">
                    <thead>
                        <tr class="bg-green-50">
                            <th class="p-4 text-left text-sm font-semibold text-gray-700 border-b border-r border-gray-200 sticky left-0 bg-green-50 z-10"><?php echo esc_html__('Cultivo', 'flavor-chat-ia'); ?></th>
                            <?php foreach ($meses as $indice => $mes): ?>
                                <th class="p-3 text-center text-sm font-semibold border-b border-r border-gray-200 last:border-r-0 <?php echo $indice === $mes_actual ? 'bg-green-500 text-white' : 'text-gray-700'; ?>">
                                    <?php echo esc_html($mes); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cultivos as $cultivo): ?>
                            <tr class="hover:bg-green-50 transition-colors">
                                <td class="p-4 border-b border-r border-gray-200 sticky left-0 bg-white z-10">
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl"><?php echo $cultivo['icono']; ?></span>
                                        <div>
                                            <span class="font-medium text-gray-900"><?php echo esc_html($cultivo['nombre']); ?></span>
                                            <span class="block text-xs text-gray-500"><?php echo esc_html($cultivo['categoria']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <?php for ($mesIndice = 0; $mesIndice < 12; $mesIndice++): ?>
                                    <?php
                                    $esSiembra = in_array($mesIndice, $cultivo['siembra']);
                                    $esCosecha = in_array($mesIndice, $cultivo['cosecha']);
                                    $esActual = $mesIndice === $mes_actual;
                                    ?>
                                    <td class="p-2 border-b border-r border-gray-200 last:border-r-0 <?php echo $esActual ? 'bg-green-50' : ''; ?>">
                                        <?php if ($esSiembra && $esCosecha): ?>
                                            <div class="w-full h-8 rounded bg-gradient-to-r from-amber-200 to-green-200 border-2 border-lime-400"></div>
                                        <?php elseif ($esSiembra): ?>
                                            <div class="w-full h-8 rounded bg-amber-200 border-2 border-amber-400"></div>
                                        <?php elseif ($esCosecha): ?>
                                            <div class="w-full h-8 rounded bg-green-200 border-2 border-green-400"></div>
                                        <?php else: ?>
                                            <div class="w-full h-8"></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Consejos del mes -->
        <div class="mt-12 bg-green-50 rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <span class="text-3xl">📅</span>
                Que hacer en <?php echo esc_html($meses[$mes_actual]); ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl p-5 shadow-md">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">🌱</span>
                        <h4 class="font-bold text-gray-900"><?php echo esc_html__('Sembrar', 'flavor-chat-ia'); ?></h4>
                    </div>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Tomate, lechuga, zanahoria y cebolla. Protege del frio con tuneles.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-md">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">🧺</span>
                        <h4 class="font-bold text-gray-900"><?php echo esc_html__('Cosechar', 'flavor-chat-ia'); ?></h4>
                    </div>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Ultimas lechugas de invierno, coles y puerros.', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="bg-white rounded-xl p-5 shadow-md">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">💧</span>
                        <h4 class="font-bold text-gray-900"><?php echo esc_html__('Cuidados', 'flavor-chat-ia'); ?></h4>
                    </div>
                    <p class="text-sm text-gray-600"><?php echo esc_html__('Prepara el terreno para primavera. Anade compost y acolchado.', 'flavor-chat-ia'); ?></p>
                </div>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="#descargar-calendario" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span><?php echo esc_html__('Descargar Calendario PDF', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>
</section>
