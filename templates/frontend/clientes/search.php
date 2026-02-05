<?php
/**
 * Frontend: Búsqueda de Clientes
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['empresa', 'contacto', 'sector', 'ciudad'];

$resultados_ejemplo = !empty($resultados) ? $resultados : [
    [
        'id' => 1,
        'empresa' => 'TechSolutions S.L.',
        'contacto' => 'Laura Sánchez',
        'iniciales' => 'TS',
        'email' => 'laura@techsolutions.es',
        'estado' => 'activo',
        'sector' => 'Tecnología',
        'color' => 'bg-slate-500',
        'coincidencia' => 'Sector: Tecnología',
    ],
    [
        'id' => 2,
        'empresa' => 'Diseños Creativos',
        'contacto' => 'Pedro Navarro',
        'iniciales' => 'DC',
        'email' => 'pedro@disenoscreativos.com',
        'estado' => 'potencial',
        'sector' => 'Diseño',
        'color' => 'bg-blue-600',
        'coincidencia' => 'Ciudad: Madrid',
    ],
    [
        'id' => 3,
        'empresa' => 'Consultoría Martín',
        'contacto' => 'Elena Martín',
        'iniciales' => 'CM',
        'email' => 'elena@consultoriamartin.es',
        'estado' => 'activo',
        'sector' => 'Consultoría',
        'color' => 'bg-gray-500',
        'coincidencia' => 'Contacto: Elena',
    ],
];
?>

<div class="flavor-frontend flavor-clientes-search max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera de búsqueda -->
    <div class="bg-gradient-to-r from-slate-500 to-blue-600 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-4 text-center"><?php echo esc_html__('Buscar Clientes', 'flavor-chat-ia'); ?></h1>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input
                    type="text"
                    name="q"
                    value="<?php echo esc_attr($query); ?>"
                    placeholder="<?php echo esc_attr__('Buscar por empresa, contacto, sector...', 'flavor-chat-ia'); ?>"
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
        <p class="text-sm text-gray-500 mb-3"><?php echo esc_html__('Sugerencias de búsqueda:', 'flavor-chat-ia'); ?></p>
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
                esc_html__('Se encontraron %d resultados para "%s"', 'flavor-chat-ia'),
                count($resultados_ejemplo),
                esc_html($query)
            ); ?>
        </p>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($resultados_ejemplo as $resultado) :
                $clase_estado_busqueda = match($resultado['estado']) {
                    'activo' => 'bg-green-100 text-green-800',
                    'inactivo' => 'bg-red-100 text-red-800',
                    'potencial' => 'bg-amber-100 text-amber-800',
                    default => 'bg-gray-100 text-gray-700',
                };
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="<?php echo esc_attr($resultado['color']); ?> w-12 h-12 rounded-lg flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        <?php echo esc_html($resultado['iniciales']); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-gray-900"><?php echo esc_html($resultado['empresa']); ?></h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($clase_estado_busqueda); ?>">
                                <?php echo esc_html(ucfirst($resultado['estado'])); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-0.5"><?php echo esc_html($resultado['contacto']); ?> &middot; <?php echo esc_html($resultado['email']); ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($resultado['coincidencia']); ?></p>
                    </div>
                    <a href="<?php echo esc_url('#cliente-' . $resultado['id']); ?>" class="hidden sm:inline-flex items-center px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50 transition-colors flex-shrink-0">
                        <?php echo esc_html__('Ver detalle', 'flavor-chat-ia'); ?>
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
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('Busca en tu cartera de clientes', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Encuentra clientes por empresa, contacto, sector o ciudad', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

</div>
