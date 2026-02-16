<?php
/**
 * Frontend: Archivo de Clientes
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$clientes_registrados = $estadisticas['registrados'] ?? 256;
$clientes_activos = $estadisticas['activos'] ?? 189;
$nuevos_mes = $estadisticas['nuevos_mes'] ?? 23;
$satisfaccion = $estadisticas['satisfaccion'] ?? '94%';

$clientes_ejemplo = !empty($items) ? $items : [
    [
        'id' => 1,
        'empresa' => 'TechSolutions S.L.',
        'contacto' => 'Laura Sánchez',
        'iniciales' => 'TS',
        'email' => 'laura@techsolutions.es',
        'telefono' => '+34 612 345 678',
        'ultima_interaccion' => '2024-01-15',
        'estado' => 'activo',
        'color' => 'bg-slate-500',
    ],
    [
        'id' => 2,
        'empresa' => 'Diseños Creativos',
        'contacto' => 'Pedro Navarro',
        'iniciales' => 'DC',
        'email' => 'pedro@disenoscreativos.com',
        'telefono' => '+34 623 456 789',
        'ultima_interaccion' => '2024-01-08',
        'estado' => 'potencial',
        'color' => 'bg-blue-600',
    ],
    [
        'id' => 3,
        'empresa' => 'Consultoría Martín',
        'contacto' => 'Elena Martín',
        'iniciales' => 'CM',
        'email' => 'elena@consultoriamartin.es',
        'telefono' => '+34 634 567 890',
        'ultima_interaccion' => '2023-12-20',
        'estado' => 'inactivo',
        'color' => 'bg-gray-500',
    ],
];
?>

<div class="flavor-frontend flavor-clientes-archive max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Cabecera con gradiente -->
    <div class="bg-gradient-to-r from-slate-500 to-blue-600 rounded-2xl p-8 mb-8 text-white">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('Gestión de Clientes', 'flavor-chat-ia'); ?></h1>
                <p class="text-slate-200 text-lg"><?php echo esc_html__('Administra y gestiona tu cartera de clientes', 'flavor-chat-ia'); ?></p>
            </div>
            <a href="#nuevo-cliente" class="mt-4 md:mt-0 inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition-colors">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?php echo esc_html__('Añadir Cliente', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>

    <!-- Cuadrícula de estadísticas -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-slate-600"><?php echo esc_html($clientes_registrados); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Clientes registrados', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-blue-600"><?php echo esc_html($clientes_activos); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Activos', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-slate-500"><?php echo esc_html($nuevos_mes); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Nuevos este mes', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 text-center">
            <p class="text-3xl font-bold text-green-600"><?php echo esc_html($satisfaccion); ?></p>
            <p class="text-sm text-gray-500 mt-1"><?php echo esc_html__('Satisfacción', 'flavor-chat-ia'); ?></p>
        </div>
    </div>

    <!-- Filtros de categoría -->
    <?php if (!empty($categorias)) : ?>
    <div class="flex flex-wrap gap-2 mb-8">
        <button class="px-4 py-2 bg-slate-500 text-white rounded-full text-sm font-medium"><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></button>
        <?php foreach ($categorias as $categoria) : ?>
            <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-full text-sm font-medium hover:bg-slate-100 hover:text-slate-700 transition-colors">
                <?php echo esc_html($categoria); ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Cuadrícula de clientes -->
    <?php if (!empty($clientes_ejemplo)) : ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <?php foreach ($clientes_ejemplo as $cliente) :
            $clase_estado = match($cliente['estado']) {
                'activo' => 'bg-green-100 text-green-800',
                'inactivo' => 'bg-red-100 text-red-800',
                'potencial' => 'bg-amber-100 text-amber-800',
                default => 'bg-gray-100 text-gray-700',
            };
            $etiqueta_estado = match($cliente['estado']) {
                'activo' => __('Activo', 'flavor-chat-ia'),
                'inactivo' => __('Inactivo', 'flavor-chat-ia'),
                'potencial' => __('Potencial', 'flavor-chat-ia'),
                default => $cliente['estado'],
            };
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center">
                    <div class="<?php echo esc_attr($cliente['color']); ?> w-12 h-12 rounded-lg flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                        <?php echo esc_html($cliente['iniciales']); ?>
                    </div>
                    <div class="ml-3">
                        <h3 class="font-semibold text-gray-900"><?php echo esc_html($cliente['empresa']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo esc_html($cliente['contacto']); ?></p>
                    </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo esc_attr($clase_estado); ?>">
                    <?php echo esc_html($etiqueta_estado); ?>
                </span>
            </div>
            <div class="space-y-2 text-sm text-gray-600 mb-4">
                <p class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <?php echo esc_html($cliente['email']); ?>
                </p>
                <p class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <?php echo esc_html($cliente['telefono']); ?>
                </p>
            </div>
            <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-400">
                    <?php echo esc_html__('Última interacción:', 'flavor-chat-ia'); ?>
                    <?php echo esc_html(date_i18n('j M Y', strtotime($cliente['ultima_interaccion']))); ?>
                </p>
                <a href="<?php echo esc_url('#cliente-' . $cliente['id']); ?>" class="text-blue-600 text-sm font-medium hover:text-blue-700">
                    <?php echo esc_html__('Ver detalle', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else : ?>
    <!-- Estado vacío -->
    <div class="text-center py-16 bg-white rounded-xl border border-gray-100">
        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo esc_html__('No hay clientes registrados', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500"><?php echo esc_html__('Comienza añadiendo tu primer cliente', 'flavor-chat-ia'); ?></p>
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
