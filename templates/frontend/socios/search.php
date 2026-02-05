<?php
/**
 * Frontend: Búsqueda de Socios
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['nombre', 'intereses', 'barrio', 'profesión'];

$resultados_ejemplo = !empty($resultados) ? $resultados : [
    [
        'id' => 1,
        'nombre' => 'María García López',
        'iniciales' => 'MG',
        'nivel' => 'Premium',
        'intereses' => ['Tecnología', 'Marketing'],
        'color' => 'bg-rose-500',
        'coincidencia' => 'Intereses: Tecnología',
    ],
    [
        'id' => 2,
        'nombre' => 'Carlos Rodríguez Pérez',
        'iniciales' => 'CR',
        'nivel' => 'Pro',
        'intereses' => ['Finanzas', 'Emprendimiento'],
        'color' => 'bg-pink-600',
        'coincidencia' => 'Barrio: Centro',
    ],
    [
        'id' => 3,
        'nombre' => 'Ana Martínez Ruiz',
        'iniciales' => 'AM',
        'nivel' => 'Básico',
        'intereses' => ['Diseño', 'Arte'],
        'color' => 'bg-fuchsia-500',
        'coincidencia' => 'Profesión: Diseñadora',
    ],
];
?>

<div class="flavor-frontend flavor-socios-search max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera de búsqueda -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-2xl p-8 mb-8 text-white">
        <h1 class="text-3xl font-bold mb-4 text-center"><?php echo esc_html__('Buscar Socios', 'flavor-chat-ia'); ?></h1>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input
                    type="text"
                    name="q"
                    value="<?php echo esc_attr($query); ?>"
                    placeholder="<?php echo esc_attr__('Buscar por nombre, intereses, barrio...', 'flavor-chat-ia'); ?>"
                    class="w-full px-5 py-4 pr-14 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-rose-300 text-lg"
                />
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 p-2 bg-rose-500 text-white rounded-lg hover:bg-rose-600 transition-colors">
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
                <a href="<?php echo esc_url(add_query_arg('q', $sugerencia)); ?>" class="px-4 py-2 bg-rose-50 text-rose-700 rounded-full text-sm hover:bg-rose-100 transition-colors">
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
                $nivel_clase_resultado = match($resultado['nivel']) {
                    'Premium' => 'bg-amber-100 text-amber-800',
                    'Pro' => 'bg-purple-100 text-purple-800',
                    default => 'bg-gray-100 text-gray-700',
                };
            ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-4">
                    <div class="<?php echo esc_attr($resultado['color']); ?> w-14 h-14 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                        <?php echo esc_html($resultado['iniciales']); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="font-semibold text-gray-900"><?php echo esc_html($resultado['nombre']); ?></h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($nivel_clase_resultado); ?>">
                                <?php echo esc_html($resultado['nivel']); ?>
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1"><?php echo esc_html($resultado['coincidencia']); ?></p>
                        <div class="flex flex-wrap gap-1.5 mt-2">
                            <?php foreach ($resultado['intereses'] as $interes) : ?>
                                <span class="px-2 py-0.5 bg-rose-50 text-rose-600 rounded text-xs"><?php echo esc_html($interes); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="/socios/mi-perfil/?id=<?php echo esc_attr($resultado['id']); ?>" class="hidden sm:inline-flex items-center px-4 py-2 border border-rose-300 text-rose-600 rounded-lg text-sm font-medium hover:bg-rose-50 transition-colors flex-shrink-0">
                        <?php echo esc_html__('Ver perfil', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('Busca socios en nuestro directorio', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Usa el buscador para encontrar socios por nombre, intereses o barrio', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

</div>
