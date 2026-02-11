<?php
/**
 * Frontend: Detalle de Bicicletas Compartidas
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$estacion = $items[0] ?? [
    'id' => 1,
    'nombre' => 'Estación Plaza Mayor',
    'direccion' => 'Plaza Mayor, 1, 28012 Madrid',
    'bicis_disponibles' => 12,
    'capacidad_total' => 20,
    'huecos_libres' => 8,
    'horario' => '24h',
    'estado' => 'operativa',
];

$bicis_por_tipo = [
    ['tipo' => 'Normal', 'disponibles' => 7, 'icono' => 'bg-blue-500'],
    ['tipo' => 'Eléctrica', 'disponibles' => 4, 'icono' => 'bg-green-500'],
    ['tipo' => 'Cargo', 'disponibles' => 1, 'icono' => 'bg-amber-500'],
];

$estaciones_cercanas = [
    ['nombre' => 'Estación Sol', 'distancia' => '400m', 'bicis' => 8],
    ['nombre' => 'Estación Ópera', 'distancia' => '650m', 'bicis' => 5],
    ['nombre' => 'Estación La Latina', 'distancia' => '900m', 'bicis' => 15],
];

$consejos_uso = [
    'Ajusta el sillín antes de comenzar tu viaje',
    'Usa siempre el casco, es obligatorio',
    'Circula por los carriles bici siempre que sea posible',
    'Revisa los frenos antes de salir',
];

$rutas_sugeridas = [
    ['nombre' => 'Ruta del Retiro', 'distancia' => '5.2 km', 'duracion' => '25 min'],
    ['nombre' => 'Ruta Madrid Río', 'distancia' => '8.1 km', 'duracion' => '40 min'],
    ['nombre' => 'Ruta Parques', 'distancia' => '12.3 km', 'duracion' => '55 min'],
];
?>

<div class="flavor-frontend flavor-bicicletas-single max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Migas de pan -->
    <nav class="flex items-center text-sm text-gray-500 mb-6" aria-label="<?php echo esc_attr__('Navegación', 'flavor-chat-ia'); ?>">
        <a href="#" class="hover:text-blue-600 transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <a href="#" class="hover:text-blue-600 transition-colors"><?php echo esc_html__('Bicicletas Compartidas', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium"><?php echo esc_html($estacion['nombre']); ?></span>
    </nav>

    <!-- Cuadrícula de 3 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Columna principal (2 columnas) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Cabecera de la estación -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <!-- Placeholder del mapa -->
                <div class="bg-gradient-to-br from-blue-100 to-blue-200 h-48 flex items-center justify-center">
                    <div class="text-center">
                        <svg class="w-12 h-12 text-blue-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        <p class="text-sm text-blue-500"><?php echo esc_html__('Mapa de ubicación', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo esc_html($estacion['nombre']); ?></h1>
                            <p class="text-gray-500 mt-1 flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                                <?php echo esc_html($estacion['direccion']); ?>
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <?php echo esc_html(ucfirst($estacion['estado'])); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Bicicletas disponibles por tipo -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Bicicletas disponibles', 'flavor-chat-ia'); ?></h2>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <?php foreach ($bicis_por_tipo as $tipo) : ?>
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="<?php echo esc_attr($tipo['icono']); ?> w-10 h-10 rounded-full flex items-center justify-center mx-auto mb-2">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <p class="text-2xl font-bold text-gray-900"><?php echo esc_html($tipo['disponibles']); ?></p>
                        <p class="text-sm text-gray-500"><?php echo esc_html($tipo['tipo']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Barra de ocupación -->
                <div class="mt-6">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-500"><?php echo esc_html__('Ocupación de la estación', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium text-gray-900">
                            <?php echo esc_html($estacion['bicis_disponibles']); ?>/<?php echo esc_html($estacion['capacidad_total']); ?>
                        </span>
                    </div>
                    <?php $porcentaje_ocupacion = ($estacion['bicis_disponibles'] / max($estacion['capacidad_total'], 1)) * 100; ?>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full transition-all" style="width: <?php echo esc_attr($porcentaje_ocupacion); ?>%"></div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">
                        <?php printf(esc_html__('%d huecos libres para devolver bicicleta', 'flavor-chat-ia'), $estacion['huecos_libres']); ?>
                    </p>
                </div>
            </div>

            <!-- Estaciones cercanas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Estaciones cercanas', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-3">
                    <?php foreach ($estaciones_cercanas as $estacion_cercana) : ?>
                    <a href="#" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-blue-50 transition-colors">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/></svg>
                            </div>
                            <div>
                                <p class="font-medium text-gray-900 text-sm"><?php echo esc_html($estacion_cercana['nombre']); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($estacion_cercana['distancia']); ?></p>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-blue-600">
                            <?php printf(esc_html__('%d bicis', 'flavor-chat-ia'), $estacion_cercana['bicis']); ?>
                        </span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Consejos de uso -->
            <div class="bg-blue-50 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Consejos de uso', 'flavor-chat-ia'); ?></h2>
                <ul class="space-y-2">
                    <?php foreach ($consejos_uso as $consejo) : ?>
                    <li class="flex items-start gap-2 text-sm text-gray-700">
                        <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <?php echo esc_html($consejo); ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Barra lateral -->
        <div class="space-y-6">

            <!-- CTA Desbloquear -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-2"><?php echo esc_html__('¿Listo para pedalear?', 'flavor-chat-ia'); ?></h3>
                <p class="text-blue-200 text-sm mb-4"><?php echo esc_html__('Desbloquea una bicicleta de esta estación', 'flavor-chat-ia'); ?></p>
                <a href="#desbloquear" class="block w-full text-center py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-blue-50 transition-colors">
                    <?php echo esc_html__('Desbloquear bicicleta', 'flavor-chat-ia'); ?>
                </a>
                <p class="text-xs text-blue-300 mt-3 text-center"><?php echo esc_html__('Horario: 24 horas', 'flavor-chat-ia'); ?></p>
            </div>

            <!-- Estadísticas de la estación -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Estadísticas', 'flavor-chat-ia'); ?></h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Viajes hoy', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium">47</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Promedio diario', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium">63</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Hora punta', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium">08:00 - 09:30</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Valoración', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-900 font-medium">4.5/5</dd>
                    </div>
                </dl>
            </div>

            <!-- Rutas sugeridas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Rutas sugeridas', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($rutas_sugeridas as $ruta) : ?>
                    <a href="#" class="block p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                        <p class="font-medium text-gray-900 text-sm"><?php echo esc_html($ruta['nombre']); ?></p>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                            <span><?php echo esc_html($ruta['distancia']); ?></span>
                            <span><?php echo esc_html__('&middot;', 'flavor-chat-ia'); ?></span>
                            <span><?php echo esc_html($ruta['duracion']); ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>
