<?php
/**
 * Template: Guia de Reciclaje
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$titulo = $titulo ?? 'Guia de Reciclaje';
$descripcion = $descripcion ?? 'Aprende a separar correctamente tus residuos';

$contenedores = [
    [
        'nombre' => 'Contenedor Azul',
        'subtitulo' => 'Papel y Carton',
        'color' => 'blue',
        'icono' => '📦',
        'si' => ['Periodicos y revistas', 'Cajas de carton', 'Folios y cuadernos', 'Envases de carton', 'Sobres y papel de regalo'],
        'no' => ['Bricks de leche/zumo', 'Papel plastificado', 'Papel sucio o con grasa', 'Pañales', 'Servilletas usadas'],
    ],
    [
        'nombre' => 'Contenedor Amarillo',
        'subtitulo' => 'Plasticos y Envases',
        'color' => 'yellow',
        'icono' => '🥤',
        'si' => ['Botellas de plastico', 'Latas de bebidas', 'Bricks', 'Bolsas de plastico', 'Bandejas de corcho blanco'],
        'no' => ['Juguetes de plastico', 'Cubos y barreños', 'Utensilios de cocina', 'Perchas', 'CDs y DVDs'],
    ],
    [
        'nombre' => 'Contenedor Verde',
        'subtitulo' => 'Vidrio',
        'color' => 'green',
        'icono' => '🍾',
        'si' => ['Botellas de vidrio', 'Tarros y frascos', 'Envases de conservas', 'Botellas de perfume', 'Tarros de cosmética'],
        'no' => ['Cristal de ventanas', 'Espejos', 'Ceramica y porcelana', 'Bombillas', 'Vasos y copas'],
    ],
    [
        'nombre' => 'Contenedor Marron',
        'subtitulo' => 'Organico',
        'color' => 'amber',
        'icono' => '🍂',
        'si' => ['Restos de comida', 'Cascaras de fruta', 'Posos de cafe', 'Bolsitas de infusion', 'Servilletas de papel'],
        'no' => ['Aceite de cocina', 'Pañales', 'Colillas', 'Pelo y cabello', 'Arena de gato'],
    ],
];
?>

<section class="flavor-component py-16 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold mb-4" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <?php echo esc_html__('Guia', 'flavor-chat-ia'); ?>
            </span>
            <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4"><?php echo esc_html($titulo); ?></h2>
            <p class="text-xl text-gray-600"><?php echo esc_html($descripcion); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <?php foreach ($contenedores as $cont): ?>
                <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
                    <!-- Header del contenedor -->
                    <div class="p-6 bg-<?php echo $cont['color']; ?>-500 text-white">
                        <div class="flex items-center gap-4">
                            <span class="text-5xl"><?php echo $cont['icono']; ?></span>
                            <div>
                                <h3 class="text-2xl font-bold"><?php echo esc_html($cont['nombre']); ?></h3>
                                <p class="text-white/90"><?php echo esc_html($cont['subtitulo']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-6">
                            <!-- SI -->
                            <div>
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-green-100 text-green-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                    <span class="font-bold text-green-700"><?php echo esc_html__('SI depositar', 'flavor-chat-ia'); ?></span>
                                </div>
                                <ul class="space-y-2">
                                    <?php foreach ($cont['si'] as $item): ?>
                                        <li class="flex items-start gap-2 text-sm text-gray-600">
                                            <span class="text-green-500 mt-0.5">✓</span>
                                            <?php echo esc_html($item); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- NO -->
                            <div>
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="flex items-center justify-center w-8 h-8 rounded-full bg-red-100 text-red-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </span>
                                    <span class="font-bold text-red-700"><?php echo esc_html__('NO depositar', 'flavor-chat-ia'); ?></span>
                                </div>
                                <ul class="space-y-2">
                                    <?php foreach ($cont['no'] as $item): ?>
                                        <li class="flex items-start gap-2 text-sm text-gray-600">
                                            <span class="text-red-500 mt-0.5">✗</span>
                                            <?php echo esc_html($item); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Residuos especiales -->
        <div class="mt-12 bg-gradient-to-r from-emerald-50 to-teal-50 rounded-2xl p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6 text-center"><?php echo esc_html__('Residuos Especiales', 'flavor-chat-ia'); ?></h3>
            <p class="text-center text-gray-600 mb-8"><?php echo esc_html__('Estos residuos requieren tratamiento especial. Llevalos al Punto Limpio', 'flavor-chat-ia'); ?></p>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php
                $especiales = [
                    ['nombre' => 'Pilas', 'icono' => '🔋'],
                    ['nombre' => 'Electronica', 'icono' => '📱'],
                    ['nombre' => 'Aceite usado', 'icono' => '🛢️'],
                    ['nombre' => 'Medicamentos', 'icono' => '💊'],
                    ['nombre' => 'Pintura', 'icono' => '🎨'],
                    ['nombre' => 'Muebles', 'icono' => '🪑'],
                ];
                foreach ($especiales as $esp): ?>
                    <div class="bg-white rounded-xl p-4 text-center shadow-md">
                        <span class="text-3xl mb-2 block"><?php echo $esp['icono']; ?></span>
                        <span class="text-sm font-medium text-gray-700"><?php echo esc_html($esp['nombre']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="text-center mt-12">
            <a href="#guia-completa" class="inline-flex items-center gap-2 px-8 py-4 text-lg font-semibold rounded-xl transition-all duration-300 hover:shadow-lg hover:scale-105" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white;">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                <span><?php echo esc_html__('Descargar Guia PDF', 'flavor-chat-ia'); ?></span>
            </a>
        </div>
    </div>
</section>
