<?php
/**
 * Frontend: Búsqueda de Bicicletas Compartidas
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['centro', 'estación tren', 'parque', 'universidad'];

$resultados_ejemplo = !empty($resultados) ? $resultados : [
    [
        'id' => 1,
        'nombre' => 'Estación Plaza Mayor',
        'direccion' => 'Plaza Mayor, 1',
        'bicis_disponibles' => 12,
        'huecos_libres' => 8,
        'distancia' => '350m',
        'tipos_bici' => ['Normal', 'Eléctrica'],
        'estado' => 'operativa',
    ],
    [
        'id' => 2,
        'nombre' => 'Estación Parque Central',
        'direccion' => 'Av. del Parque, 15',
        'bicis_disponibles' => 3,
        'huecos_libres' => 12,
        'distancia' => '800m',
        'tipos_bici' => ['Normal', 'Eléctrica', 'Cargo'],
        'estado' => 'operativa',
    ],
    [
        'id' => 3,
        'nombre' => 'Estación Universidad',
        'direccion' => 'Campus Universitario, s/n',
        'bicis_disponibles' => 0,
        'huecos_libres' => 25,
        'distancia' => '1.2km',
        'tipos_bici' => ['Normal'],
        'estado' => 'mantenimiento',
    ],
];
?>

<div class="flavor-frontend flavor-bicicletas-search max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera de búsqueda -->
    <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-4 text-center"><?php echo esc_html__('Buscar Estaciones', 'flavor-chat-ia'); ?></h1>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input
                    type="text"
                    name="q"
                    value="<?php echo esc_attr($query); ?>"
                    placeholder="<?php echo esc_attr__('Buscar estaciones por nombre o zona...', 'flavor-chat-ia'); ?>"
                    class="w-full px-5 py-4 pr-14 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-300 text-lg"
                />
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 p-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Sugerencias de búsqueda -->
    <div class="mb-8">
        <p class="text-sm text-gray-500 mb-3"><?php echo esc_html__('Buscar cerca de:', 'flavor-chat-ia'); ?></p>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($sugerencias as $sugerencia) : ?>
                <a href="<?php echo esc_url(add_query_arg('q', $sugerencia)); ?>" class="px-4 py-2 bg-blue-50 text-blue-700 rounded-full text-sm hover:bg-blue-100 transition-colors">
                    <?php echo esc_html($sugerencia); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Resultados -->
    <?php if (!empty($query) || !empty($resultados_ejemplo)) : ?>
        <?php if (!empty($query)) : ?>
        <p class="text-gray-600 mb-6">
            <?php printf(
                esc_html__('Se encontraron %d estaciones para "%s"', 'flavor-chat-ia'),
                count($resultados_ejemplo),
                esc_html($query)
            ); ?>
        </p>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($resultados_ejemplo as $resultado) :
                $cantidad_bicis_busqueda = $resultado['bicis_disponibles'];
                if ($cantidad_bicis_busqueda >= 5) {
                    $color_busqueda = 'text-green-600 bg-green-100';
                } elseif ($cantidad_bicis_busqueda > 0) {
                    $color_busqueda = 'text-amber-600 bg-amber-100';
                } else {
                    $color_busqueda = 'text-red-600 bg-red-100';
                }
                $clase_estado_estacion = $resultado['estado'] === 'operativa'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-amber-100 text-amber-800';
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-gray-900"><?php echo esc_html($resultado['nombre']); ?></h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($clase_estado_estacion); ?>">
                                <?php echo esc_html(ucfirst($resultado['estado'])); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-0.5"><?php echo esc_html($resultado['direccion']); ?> &middot; <?php echo esc_html($resultado['distancia']); ?></p>
                        <div class="flex items-center gap-4 mt-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo esc_attr($color_busqueda); ?>">
                                <?php printf(esc_html__('%d bicis', 'flavor-chat-ia'), $cantidad_bicis_busqueda); ?>
                            </span>
                            <span class="text-xs text-gray-400">
                                <?php printf(esc_html__('%d huecos libres', 'flavor-chat-ia'), $resultado['huecos_libres']); ?>
                            </span>
                            <div class="flex gap-1">
                                <?php foreach ($resultado['tipos_bici'] as $tipo_bici_busqueda) : ?>
                                    <span class="px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded text-xs"><?php echo esc_html($tipo_bici_busqueda); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <a href="<?php echo esc_url('#estacion-' . $resultado['id']); ?>" class="hidden sm:inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex-shrink-0">
                        <?php echo esc_html__('Ver estación', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('Encuentra una estación cercana', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Busca estaciones por nombre o zona de la ciudad', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

</div>
