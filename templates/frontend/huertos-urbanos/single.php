<?php
/**
 * Frontend: Single Huerto Urbano
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$huerto = $huerto ?? [];
$nombre = $huerto['nombre'] ?? 'Huerto Comunitario';
$descripcion = $huerto['descripcion'] ?? '';
$ubicacion = $huerto['ubicacion'] ?? '';
$imagenes = $huerto['imagenes'] ?? [];
$parcelas_totales = $huerto['parcelas_totales'] ?? 20;
$parcelas_libres = $huerto['parcelas_libres'] ?? 5;
$tamano_parcela = $huerto['tamano_parcela'] ?? '25m²';
$precio = $huerto['precio'] ?? '20€';
$servicios = $huerto['servicios'] ?? [];
$horarios = $huerto['horarios'] ?? [];
?>

<div class="flavor-single huertos-urbanos">
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-3 px-4">
        <div class="container mx-auto max-w-6xl">
            <nav class="flex items-center gap-2 text-sm text-gray-600">
                <a href="#" class="hover:text-green-600"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
                <span>/</span>
                <a href="#" class="hover:text-green-600"><?php echo esc_html__('Huertos', 'flavor-chat-ia'); ?></a>
                <span>/</span>
                <span class="text-gray-900 font-medium"><?php echo esc_html($nombre); ?></span>
            </nav>
        </div>
    </div>

    <div class="container mx-auto max-w-6xl px-4 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Contenido principal -->
            <div class="lg:col-span-2">
                <!-- Galeria de imagenes -->
                <div class="mb-8">
                    <div class="relative aspect-[16/9] rounded-2xl overflow-hidden mb-4">
                        <img id="imagen-principal"
                             src="<?php echo esc_url($imagenes[0] ?? 'https://picsum.photos/seed/huerto1/800/450'); ?>"
                             alt="<?php echo esc_attr($nombre); ?>"
                             class="w-full h-full object-cover">
                        <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-sm font-bold <?php echo $parcelas_libres > 0 ? 'bg-green-500' : 'bg-red-500'; ?> text-white">
                            <?php echo $parcelas_libres > 0 ? $parcelas_libres . ' parcelas libres' : 'Completo'; ?>
                        </span>
                    </div>
                    <?php if (count($imagenes) > 1): ?>
                        <div class="grid grid-cols-4 gap-2">
                            <?php foreach ($imagenes as $img): ?>
                                <button onclick="document.getElementById('imagen-principal').src='<?php echo esc_url($img); ?>'"
                                        class="aspect-video rounded-lg overflow-hidden border-2 border-transparent hover:border-green-500 transition-colors">
                                    <img src="<?php echo esc_url($img); ?>" alt="" class="w-full h-full object-cover">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info basica -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-4"><?php echo esc_html($nombre); ?></h1>

                    <div class="flex items-center gap-6 text-gray-600 mb-6">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            </svg>
                            <?php echo esc_html($ubicacion); ?>
                        </span>
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6z"/>
                            </svg>
                            <?php echo esc_html($parcelas_totales); ?> parcelas
                        </span>
                    </div>

                    <p class="text-gray-700 leading-relaxed"><?php echo esc_html($descripcion); ?></p>
                </div>

                <!-- Servicios incluidos -->
                <?php if (!empty($servicios)): ?>
                    <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Servicios Incluidos', 'flavor-chat-ia'); ?></h2>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                            <?php foreach ($servicios as $servicio): ?>
                                <div class="flex items-center gap-2 p-3 rounded-xl bg-green-50">
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    <span class="text-gray-700"><?php echo esc_html($servicio); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Calendario de cultivos -->
                <div class="bg-white rounded-2xl p-6 shadow-md mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Calendario de Cultivos', 'flavor-chat-ia'); ?></h2>
                    <div class="grid grid-cols-4 gap-2">
                        <?php
                        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
                        $mes_actual = date('n') - 1;
                        foreach ($meses as $indice => $mes):
                        ?>
                            <div class="p-2 rounded-lg text-center text-sm <?php echo $indice === $mes_actual ? 'bg-green-500 text-white font-bold' : 'bg-gray-100 text-gray-600'; ?>">
                                <?php echo $mes; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-4"><?php echo esc_html__('Epoca ideal de siembra para la zona', 'flavor-chat-ia'); ?></p>
                </div>

                <!-- Normas -->
                <div class="bg-white rounded-2xl p-6 shadow-md">
                    <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Normas del Huerto', 'flavor-chat-ia'); ?></h2>
                    <ul class="space-y-2">
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo esc_html__('Cultivo ecologico obligatorio (sin pesticidas)', 'flavor-chat-ia'); ?>
                        </li>
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo esc_html__('Mantener la parcela limpia y cuidada', 'flavor-chat-ia'); ?>
                        </li>
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo esc_html__('Participar en las actividades comunitarias mensuales', 'flavor-chat-ia'); ?>
                        </li>
                        <li class="flex items-start gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <?php echo esc_html__('Respetar los horarios de acceso establecidos', 'flavor-chat-ia'); ?>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Sidebar de solicitud -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-2xl p-6 shadow-md sticky top-4">
                    <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo esc_html__('Solicitar Parcela', 'flavor-chat-ia'); ?></h2>

                    <!-- Info parcela -->
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600"><?php echo esc_html__('Tamano parcela', 'flavor-chat-ia'); ?></span>
                            <span class="font-bold text-gray-900"><?php echo esc_html($tamano_parcela); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600"><?php echo esc_html__('Cuota mensual', 'flavor-chat-ia'); ?></span>
                            <span class="font-bold text-green-600 text-xl"><?php echo esc_html($precio); ?></span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50">
                            <span class="text-gray-600"><?php echo esc_html__('Parcelas libres', 'flavor-chat-ia'); ?></span>
                            <span class="font-bold <?php echo $parcelas_libres > 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                <?php echo esc_html($parcelas_libres); ?> de <?php echo esc_html($parcelas_totales); ?>
                            </span>
                        </div>
                    </div>

                    <?php if ($parcelas_libres > 0): ?>
                        <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105"
                                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                            <?php echo esc_html__('Solicitar Parcela', 'flavor-chat-ia'); ?>
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-4">
                            <?php echo esc_html__('La solicitud sera revisada en 48-72h', 'flavor-chat-ia'); ?>
                        </p>
                    <?php else: ?>
                        <button class="w-full py-4 rounded-xl text-lg font-semibold text-white bg-gray-400 cursor-not-allowed">
                            <?php echo esc_html__('Lista de Espera', 'flavor-chat-ia'); ?>
                        </button>
                        <p class="text-xs text-gray-500 text-center mt-4">
                            <?php echo esc_html__('Actualmente no hay parcelas disponibles', 'flavor-chat-ia'); ?>
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Horarios -->
                <div class="bg-white rounded-2xl p-6 shadow-md mt-6">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo esc_html__('Horarios de Acceso', 'flavor-chat-ia'); ?></h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo esc_html__('Lunes a Viernes', 'flavor-chat-ia'); ?></span>
                            <span class="font-medium">7:00 - 21:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo esc_html__('Sabados', 'flavor-chat-ia'); ?></span>
                            <span class="font-medium">8:00 - 20:00</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600"><?php echo esc_html__('Domingos', 'flavor-chat-ia'); ?></span>
                            <span class="font-medium">9:00 - 14:00</span>
                        </div>
                    </div>
                </div>

                <!-- Contacto -->
                <div class="bg-white rounded-2xl p-6 shadow-md mt-6">
                    <h3 class="font-bold text-gray-900 mb-4"><?php echo esc_html__('Contacto', 'flavor-chat-ia'); ?></h3>
                    <a href="#contacto" class="flex items-center gap-3 p-3 rounded-xl bg-gray-50 hover:bg-green-50 transition-colors">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="block font-medium text-gray-900"><?php echo esc_html__('Preguntas?', 'flavor-chat-ia'); ?></span>
                            <span class="text-sm text-gray-500"><?php echo esc_html__('Contacta con el coordinador', 'flavor-chat-ia'); ?></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
