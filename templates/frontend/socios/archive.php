<?php
/**
 * Frontend: Archivo de Socios
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$socios_activos = $estadisticas['activos'] ?? 128;
$nuevos_mes = $estadisticas['nuevos_mes'] ?? 14;
$eventos_total = $estadisticas['eventos'] ?? 8;
$beneficios_activos = $estadisticas['beneficios'] ?? 32;

$socios_ejemplo = !empty($items) ? $items : [
    [
        'id' => 1,
        'nombre' => 'María García López',
        'iniciales' => 'MG',
        'nivel' => 'Premium',
        'miembro_desde' => '2023-03-15',
        'intereses' => ['Tecnología', 'Marketing', 'Networking'],
        'color' => 'bg-rose-500',
    ],
    [
        'id' => 2,
        'nombre' => 'Carlos Rodríguez Pérez',
        'iniciales' => 'CR',
        'nivel' => 'Pro',
        'miembro_desde' => '2022-11-08',
        'intereses' => ['Finanzas', 'Emprendimiento'],
        'color' => 'bg-pink-600',
    ],
    [
        'id' => 3,
        'nombre' => 'Ana Martínez Ruiz',
        'iniciales' => 'AM',
        'nivel' => 'Básico',
        'miembro_desde' => '2024-01-20',
        'intereses' => ['Diseño', 'Arte', 'Fotografía'],
        'color' => 'bg-fuchsia-500',
    ],
];
?>

<div class="flavor-frontend flavor-socios-archive max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera con gradiente -->
    <div class="bg-gradient-to-r from-rose-500 to-pink-600 rounded-2xl p-8 mb-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Directorio de Socios', 'flavor-chat-ia'); ?></h1>
                <p class="text-rose-100 text-lg"><?php echo esc_html__('Conecta con nuestra comunidad de socios activos', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="/socios/unirme/" class="mt-4 md:mt-0 inline-flex items-center px-6 py-3 bg-white text-rose-600 font-semibold rounded-xl hover:bg-rose-50 transition-colors">
                <?php echo esc_html__('Únete como Socio', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Cuadrícula de estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-rose-600"><?php echo esc_html($socios_activos); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Socios activos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-pink-600"><?php echo esc_html($nuevos_mes); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Nuevos este mes', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-rose-500"><?php echo esc_html($eventos_total); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Eventos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-pink-500"><?php echo esc_html($beneficios_activos); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Beneficios activos', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <!-- Filtros de categoría -->
    <?php if (!empty($categorias)) : ?>
    <div class="flex flex-wrap gap-2 mb-8">
        <button class="px-4 py-2 bg-rose-500 text-white rounded-full text-sm font-medium"><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></button>
        <?php foreach ($categorias as $categoria) : ?>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-rose-100 hover:text-rose-700 transition-colors">
                <?php echo esc_html($categoria); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Cuadrícula de socios -->
    <?php if (!empty($socios_ejemplo)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($socios_ejemplo as $socio) :
            $nivel_clase = match($socio['nivel'] ?? 'Básico') {
                'Premium' => 'bg-amber-100 text-amber-800',
                'Pro' => 'bg-purple-100 text-purple-800',
                default => 'bg-gray-100 text-gray-700',
            };
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="<?php echo esc_attr($socio['color'] ?? 'bg-rose-500'); ?> w-14 h-14 rounded-full flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                    <?php echo esc_html($socio['iniciales'] ?? '??'); ?>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900"><?php echo esc_html($socio['nombre']); ?></h3>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($nivel_clase); ?>">
                        <?php echo esc_html($socio['nivel']); ?>
                    </span>
                </div>
            </div>
            <p class="text-sm text-gray-500 mb-3">
                <?php echo esc_html__('Socio desde', 'flavor-chat-ia'); ?>
                <?php echo esc_html(date_i18n('F Y', strtotime($socio['miembro_desde']))); ?>
            </p>
            <div class="flex flex-wrap gap-1.5 mb-4">
                <?php foreach (($socio['intereses'] ?? []) as $interes) : ?>
                    <span class="px-2 py-1 bg-rose-50 text-rose-700 rounded text-xs"><?php echo esc_html($interes); ?></span>
                <?php endforeach; ?>
            </div>
            <a href="/socios/mi-perfil/?id=<?php echo esc_attr($socio['id'] ?? 0); ?>" class="block w-full text-center py-2 border border-rose-300 text-rose-600 rounded-lg text-sm font-medium hover:bg-rose-50 transition-colors">
                <?php echo esc_html__('Contactar', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-rose-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('No se encontraron socios', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Intenta ajustar los filtros de búsqueda', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Paginación -->
    <?php if ($total > 0) : ?>
    <nav class="flex justify-center mt-8" aria-label="<?php echo esc_attr__('Paginación', 'flavor-chat-ia'); ?>">
        <ul class="inline-flex items-center gap-1">
            <li><a href="#" class="px-3 py-2 text-gray-500 hover:text-rose-600 rounded-lg hover:bg-rose-50">&laquo;</a></li>
            <li><a href="#" class="px-3 py-2 bg-rose-500 text-white rounded-lg font-medium">1</a></li>
            <li><a href="#" class="px-3 py-2 text-gray-700 hover:text-rose-600 rounded-lg hover:bg-rose-50">2</a></li>
            <li><a href="#" class="px-3 py-2 text-gray-700 hover:text-rose-600 rounded-lg hover:bg-rose-50">3</a></li>
            <li><a href="#" class="px-3 py-2 text-gray-500 hover:text-rose-600 rounded-lg hover:bg-rose-50">&raquo;</a></li>
        </ul>
    </nav>
    <?php endif; ?>

</div>
