<?php
/**
 * Frontend: Archivo de Empresarial
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$total_empresas = $estadisticas['empresas'] ?? 312;
$total_sectores = $estadisticas['sectores'] ?? 18;
$total_empleos = $estadisticas['empleos'] ?? 87;
$eventos_networking = $estadisticas['eventos_networking'] ?? 5;

$empresas_ejemplo = !empty($items) ? $items : [
    [
        'id' => 1,
        'nombre' => 'Innovatech Solutions',
        'iniciales' => 'IS',
        'sector' => 'Tecnología',
        'descripcion' => 'Desarrollo de software a medida y soluciones cloud para empresas.',
        'empleados' => 45,
        'ubicacion' => 'Madrid, España',
        'web' => 'https://innovatech.es',
        'color' => 'bg-gray-500',
    ],
    [
        'id' => 2,
        'nombre' => 'Estrategia Global',
        'iniciales' => 'EG',
        'sector' => 'Consultoría',
        'descripcion' => 'Consultoría estratégica para expansión de negocios internacionales.',
        'empleados' => 120,
        'ubicacion' => 'Barcelona, España',
        'web' => 'https://estrategiaglobal.com',
        'color' => 'bg-slate-600',
    ],
    [
        'id' => 3,
        'nombre' => 'Creativa Studio',
        'iniciales' => 'CS',
        'sector' => 'Marketing',
        'descripcion' => 'Agencia de marketing digital especializada en branding y redes sociales.',
        'empleados' => 12,
        'ubicacion' => 'Valencia, España',
        'web' => 'https://creativastudio.es',
        'color' => 'bg-gray-600',
    ],
];
?>

<div class="flavor-frontend flavor-empresarial-archive max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera con gradiente -->
    <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-2xl p-8 mb-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Directorio Empresarial', 'flavor-chat-ia'); ?></h1>
                <p class="text-gray-300 text-lg"><?php echo esc_html__('Encuentra empresas y oportunidades de colaboración', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="#registrar-empresa" class="mt-4 md:mt-0 inline-flex items-center px-6 py-3 bg-white text-slate-600 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <?php echo esc_html__('Registrar Empresa', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Cuadrícula de estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-gray-600"><?php echo esc_html($total_empresas); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Empresas', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-slate-600"><?php echo esc_html($total_sectores); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Sectores', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-gray-500"><?php echo esc_html($total_empleos); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Empleos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-slate-500"><?php echo esc_html($eventos_networking); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Eventos networking', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <!-- Filtros de categoría -->
    <?php if (!empty($categorias)) : ?>
    <div class="flex flex-wrap gap-2 mb-8">
        <button class="px-4 py-2 bg-gray-600 text-white rounded-full text-sm font-medium"><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></button>
        <?php foreach ($categorias as $categoria) : ?>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-slate-100 hover:text-slate-700 transition-colors">
                <?php echo esc_html($categoria); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Cuadrícula de empresas -->
    <?php if (!empty($empresas_ejemplo)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($empresas_ejemplo as $empresa) : ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center mb-4">
                <div class="<?php echo esc_attr($empresa['color']); ?> w-14 h-14 rounded-xl flex items-center justify-center text-white text-lg font-bold flex-shrink-0">
                    <?php echo esc_html($empresa['iniciales']); ?>
                </div>
                <div class="ml-4">
                    <h3 class="font-semibold text-gray-900"><?php echo esc_html($empresa['nombre']); ?></h3>
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                        <?php echo esc_html($empresa['sector']); ?>
                    </span>
                </div>
            </div>
            <p class="text-sm text-gray-600 mb-4 line-clamp-2"><?php echo esc_html($empresa['descripcion']); ?></p>
            <div class="space-y-2 text-sm text-gray-500 mb-4">
                <p class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?php printf(esc_html__('%d empleados', 'flavor-chat-ia'), $empresa['empleados']); ?>
                </p>
                <p class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?php echo esc_html($empresa['ubicacion']); ?>
                </p>
            </div>
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <a href="<?php echo esc_url($empresa['web']); ?>" target="_blank" rel="noopener noreferrer" class="text-sm text-slate-600 hover:text-slate-800 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <?php echo esc_html__('Sitio web', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url('#empresa-' . $empresa['id']); ?>" class="text-sm text-slate-600 font-medium hover:text-slate-800">
                    <?php echo esc_html__('Ver perfil', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('No se encontraron empresas', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Sé el primero en registrar tu empresa en el directorio', 'flavor-chat-ia'); ?></p>
    </div>
    <?php endif; ?>

    <!-- Paginación -->
    <?php if ($total > 0) :
        $pagina_actual = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
        $total_paginas = max(1, ceil($total / 12));
    ?>
    <nav class="flex justify-center mt-8" aria-label="<?php echo esc_attr__('Paginación', 'flavor-chat-ia'); ?>">
        <ul class="inline-flex items-center gap-1">
            <li><a href="<?php echo esc_url(add_query_arg('pagina', max(1, $pagina_actual - 1))); ?>" class="px-3 py-2 text-gray-500 hover:text-slate-600 rounded-lg hover:bg-slate-50"><?php echo esc_html__('&laquo;', 'flavor-chat-ia'); ?></a></li>
            <?php for ($pag = 1; $pag <= min(3, $total_paginas); $pag++) : ?>
            <li><a href="<?php echo esc_url(add_query_arg('pagina', $pag)); ?>" class="px-3 py-2 <?php echo $pag === $pagina_actual ? 'bg-slate-600 text-white' : 'text-gray-700 hover:text-slate-600 hover:bg-slate-50'; ?> rounded-lg font-medium"><?php echo $pag; ?></a></li>
            <?php endfor; ?>
            <li><a href="<?php echo esc_url(add_query_arg('pagina', min($total_paginas, $pagina_actual + 1))); ?>" class="px-3 py-2 text-gray-500 hover:text-slate-600 rounded-lg hover:bg-slate-50"><?php echo esc_html__('&raquo;', 'flavor-chat-ia'); ?></a></li>
        </ul>
    </nav>
    <?php endif; ?>

</div>
