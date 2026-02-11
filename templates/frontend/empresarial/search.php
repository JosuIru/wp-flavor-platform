<?php
/**
 * Frontend: Búsqueda de Empresarial
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['tecnología', 'consultoría', 'marketing', 'diseño', 'construcción'];

$resultados_ejemplo = !empty($resultados) ? $resultados : [
    [
        'id' => 1,
        'nombre' => 'Innovatech Solutions',
        'iniciales' => 'IS',
        'sector' => 'Tecnología',
        'descripcion' => 'Desarrollo de software a medida y soluciones cloud para empresas.',
        'empleados' => 45,
        'ubicacion' => 'Madrid',
        'color' => 'bg-gray-500',
    ],
    [
        'id' => 2,
        'nombre' => 'Estrategia Global',
        'iniciales' => 'EG',
        'sector' => 'Consultoría',
        'descripcion' => 'Consultoría estratégica para expansión de negocios internacionales.',
        'empleados' => 120,
        'ubicacion' => 'Barcelona',
        'color' => 'bg-slate-600',
    ],
    [
        'id' => 3,
        'nombre' => 'Creativa Studio',
        'iniciales' => 'CS',
        'sector' => 'Marketing',
        'descripcion' => 'Agencia de marketing digital especializada en branding y redes sociales.',
        'empleados' => 12,
        'ubicacion' => 'Valencia',
        'color' => 'bg-gray-600',
    ],
];
?>

<div class="flavor-frontend flavor-empresarial-search max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera de búsqueda -->
    <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-4 text-center"><?php echo esc_html__('Buscar Empresas', 'flavor-chat-ia'); ?></h1>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input
                    type="text"
                    name="q"
                    value="<?php echo esc_attr($query); ?>"
                    placeholder="<?php echo esc_attr__('Buscar empresas por nombre, sector, ubicación...', 'flavor-chat-ia'); ?>"
                    class="w-full px-5 py-4 pr-14 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-slate-300 text-lg"
                />
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 p-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </div>
        </form>
    </div>

    <!-- Sugerencias de búsqueda -->
    <div class="mb-8">
        <p class="text-sm text-gray-500 mb-3"><?php echo esc_html__('Sectores populares:', 'flavor-chat-ia'); ?></p>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($sugerencias as $sugerencia) : ?>
                <a href="<?php echo esc_url(add_query_arg('q', $sugerencia)); ?>" class="px-4 py-2 bg-slate-50 text-slate-700 rounded-full text-sm hover:bg-slate-100 transition-colors">
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
                esc_html__('Se encontraron %d empresas para "%s"', 'flavor-chat-ia'),
                count($resultados_ejemplo),
                esc_html($query)
            ); ?>
        </p>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($resultados_ejemplo as $resultado) : ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="<?php echo esc_attr($resultado['color']); ?> w-14 h-14 rounded-xl flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                        <?php echo esc_html($resultado['iniciales']); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-gray-900"><?php echo esc_html($resultado['nombre']); ?></h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                <?php echo esc_html($resultado['sector']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1 line-clamp-1"><?php echo esc_html($resultado['descripcion']); ?></p>
                        <div class="flex items-center gap-4 mt-2 text-xs text-gray-400">
                            <span><?php printf(esc_html__('%d empleados', 'flavor-chat-ia'), $resultado['empleados']); ?></span>
                            <span><?php echo esc_html__('&middot;', 'flavor-chat-ia'); ?></span>
                            <span><?php echo esc_html($resultado['ubicacion']); ?></span>
                        </div>
                    </div>
                    <a href="<?php echo esc_url('#empresa-' . $resultado['id']); ?>" class="hidden sm:inline-flex items-center px-4 py-2 border border-slate-300 text-slate-600 rounded-lg text-sm font-medium hover:bg-slate-50 transition-colors flex-shrink-0">
                        <?php echo esc_html__('Ver perfil', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('Explora el directorio empresarial', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Busca empresas por nombre, sector o ubicación', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

</div>
