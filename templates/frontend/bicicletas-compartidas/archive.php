<?php
/**
 * Frontend: Archivo de Bicicletas Compartidas
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$bicis_disponibles = $estadisticas['disponibles'] ?? 342;
$total_estaciones = $estadisticas['estaciones'] ?? 48;
$km_recorridos = $estadisticas['km_recorridos'] ?? '125K';
$usuarios_activos = $estadisticas['usuarios_activos'] ?? 1850;

$pasos_como_funciona = [
    ['icono' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z', 'titulo' => 'Encuentra estación', 'descripcion' => 'Localiza la estación más cercana'],
    ['icono' => 'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z', 'titulo' => 'Desbloquea bici', 'descripcion' => 'Usa la app para desbloquear'],
    ['icono' => 'M13 10V3L4 14h7v7l9-11h-7z', 'titulo' => 'Pedalea', 'descripcion' => 'Disfruta tu recorrido por la ciudad'],
    ['icono' => 'M5 13l4 4L19 7', 'titulo' => 'Devuelve', 'descripcion' => 'Deja la bici en cualquier estación'],
];

$estaciones_ejemplo = !empty($items) ? $items : [
    [
        'id' => 1,
        'nombre' => 'Estación Plaza Mayor',
        'bicis_disponibles' => 12,
        'capacidad_total' => 20,
        'tipos_bici' => ['Normal', 'Eléctrica'],
        'distancia' => '350m',
        'huecos_libres' => 8,
    ],
    [
        'id' => 2,
        'nombre' => 'Estación Parque Central',
        'bicis_disponibles' => 3,
        'capacidad_total' => 15,
        'tipos_bici' => ['Normal', 'Eléctrica', 'Cargo'],
        'distancia' => '800m',
        'huecos_libres' => 12,
    ],
    [
        'id' => 3,
        'nombre' => 'Estación Universidad',
        'bicis_disponibles' => 0,
        'capacidad_total' => 25,
        'tipos_bici' => ['Normal'],
        'distancia' => '1.2km',
        'huecos_libres' => 25,
    ],
];
?>

<div class="flavor-frontend flavor-bicicletas-archive max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera con gradiente -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-2xl p-8 mb-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Bicicletas Compartidas', 'flavor-chat-ia'); ?></h1>
                <p class="text-blue-200 text-lg"><?php echo esc_html__('Muévete por la ciudad de forma sostenible', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="#mapa" class="mt-4 md:mt-0 inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                <?php echo esc_html__('Ver Mapa de Estaciones', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Cuadrícula de estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-blue-600"><?php echo esc_html($bicis_disponibles); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Bicicletas disponibles', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-blue-500"><?php echo esc_html($total_estaciones); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Estaciones', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-blue-700"><?php echo esc_html($km_recorridos); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Km recorridos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-blue-600"><?php echo esc_html($usuarios_activos); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Usuarios activos', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <!-- Cómo funciona -->
    <div class="bg-blue-50 rounded-xl p-6 mb-8">
        <h2 class="text-lg font-semibold text-gray-900 mb-4 text-center"><?php echo esc_html__('¿Cómo funciona?', 'flavor-chat-ia'); ?></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach ($pasos_como_funciona as $indice_paso => $paso) : ?>
            <div class="text-center">
                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($paso['icono']); ?>"/></svg>
                </div>
                <p class="font-medium text-gray-900 text-sm"><?php echo esc_html($paso['titulo']); ?></p>
                <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($paso['descripcion']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Cuadrícula de estaciones -->
    <?php if (!empty($estaciones_ejemplo)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($estaciones_ejemplo as $estacion) :
            $cantidad_bicis = $estacion['bicis_disponibles'];
            if ($cantidad_bicis >= 5) {
                $color_disponibilidad = 'text-green-600 bg-green-100';
                $texto_disponibilidad = __('Disponible', 'flavor-chat-ia');
            } elseif ($cantidad_bicis > 0) {
                $color_disponibilidad = 'text-amber-600 bg-amber-100';
                $texto_disponibilidad = __('Pocas bicis', 'flavor-chat-ia');
            } else {
                $color_disponibilidad = 'text-red-600 bg-red-100';
                $texto_disponibilidad = __('Sin bicis', 'flavor-chat-ia');
            }
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900"><?php echo esc_html($estacion['nombre']); ?></h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        <svg class="w-3.5 h-3.5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                        <?php echo esc_html($estacion['distancia']); ?>
                    </p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($color_disponibilidad); ?>">
                    <?php echo esc_html($texto_disponibilidad); ?>
                </span>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
                <div class="bg-blue-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-blue-600"><?php echo esc_html($cantidad_bicis); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Bicis disponibles', 'flavor-chat-ia'); ?></p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-gray-600"><?php echo esc_html($estacion['huecos_libres']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo esc_html__('Huecos libres', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5 mb-4">
                <?php foreach ($estacion['tipos_bici'] as $tipo_bici) : ?>
                    <span class="px-2 py-1 bg-blue-50 text-blue-700 rounded text-xs"><?php echo esc_html($tipo_bici); ?></span>
                <?php endforeach; ?>
            </div>

            <a href="<?php echo esc_url('#estacion-' . $estacion['id']); ?>" class="block w-full text-center py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors">
                <?php echo esc_html__('Ver estación', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('No se encontraron estaciones', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('No hay estaciones disponibles en esta zona', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Paginación -->
    <?php if ($total > 0) :
        $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $total_paginas = max(1, ceil($total / 12));
    ?>
    <nav class="flex justify-center mt-8" aria-label="<?php echo esc_attr__('Paginación', 'flavor-chat-ia'); ?>">
        <ul class="inline-flex items-center gap-1">
            <li><a href="<?php echo esc_url(add_query_arg('pagina', max(1, $pagina_actual - 1))); ?>" class="px-3 py-2 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-blue-50"><?php echo esc_html__('&laquo;', 'flavor-chat-ia'); ?></a></li>
            <?php for ($pag = 1; $pag <= min(3, $total_paginas); $pag++) : ?>
            <li><a href="<?php echo esc_url(add_query_arg('pagina', $pag)); ?>" class="px-3 py-2 <?php echo $pag === $pagina_actual ? 'bg-blue-600 text-white' : 'text-gray-700 hover:text-blue-600 hover:bg-blue-50'; ?> rounded-lg font-medium"><?php echo $pag; ?></a></li>
            <?php endfor; ?>
            <li><a href="<?php echo esc_url(add_query_arg('pagina', min($total_paginas, $pagina_actual + 1))); ?>" class="px-3 py-2 text-gray-500 hover:text-blue-600 rounded-lg hover:bg-blue-50"><?php echo esc_html__('&raquo;', 'flavor-chat-ia'); ?></a></li>
        </ul>
    </nav>
    <?php endif; ?>

</div>
