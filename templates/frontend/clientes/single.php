<?php
/**
 * Frontend: Detalle de Clientes
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$items = $items ?? [];
$total = $total ?? 0;
$estadisticas = $estadisticas ?? [];
$categorias = $categorias ?? [];

$cliente = $items[0] ?? [
    'id' => 1,
    'empresa' => 'TechSolutions S.L.',
    'iniciales' => 'TS',
    'contacto' => 'Laura Sánchez',
    'email' => 'laura@techsolutions.es',
    'telefono' => '+34 612 345 678',
    'direccion' => 'Calle Gran Vía 42, 3ª planta, Madrid',
    'sector' => 'Tecnología',
    'estado' => 'activo',
    'fecha_alta' => '2022-06-10',
    'descripcion' => 'Empresa especializada en soluciones tecnológicas para pymes. Servicios de consultoría IT, desarrollo web y soporte técnico.',
    'color' => 'bg-slate-500',
    'notas' => 'Cliente estratégico. Interesado en ampliar servicios de marketing digital.',
];

$historial_interacciones = [
    ['tipo' => 'reunion', 'texto' => 'Reunión de seguimiento trimestral', 'fecha' => '2024-01-15', 'autor' => 'Juan López'],
    ['tipo' => 'email', 'texto' => 'Envío de propuesta comercial actualizada', 'fecha' => '2024-01-08', 'autor' => 'María Ruiz'],
    ['tipo' => 'llamada', 'texto' => 'Llamada para confirmar renovación de contrato', 'fecha' => '2023-12-20', 'autor' => 'Juan López'],
];

$proyectos_relacionados = [
    ['nombre' => 'Rediseño web corporativo', 'estado' => 'En progreso'],
    ['nombre' => 'Campaña SEO Q1 2024', 'estado' => 'Planificado'],
    ['nombre' => 'Auditoría de seguridad', 'estado' => 'Completado'],
];
?>

<div class="flavor-frontend flavor-clientes-single max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Migas de pan -->
    <nav class="flex items-center text-sm text-gray-500 mb-6" aria-label="<?php echo esc_attr__('Navegación', 'flavor-chat-ia'); ?>">
        <a href="#" class="hover:text-blue-600 transition-colors"><?php echo esc_html__('Inicio', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <a href="#" class="hover:text-blue-600 transition-colors"><?php echo esc_html__('Clientes', 'flavor-chat-ia'); ?></a>
        <span class="mx-2">/</span>
        <span class="text-gray-900 font-medium"><?php echo esc_html($cliente['empresa']); ?></span>
    </nav>

    <!-- Cuadrícula de 3 columnas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        <!-- Columna principal (2 columnas) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Información de la empresa -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start gap-4 mb-6">
                    <div class="<?php echo esc_attr($cliente['color']); ?> w-16 h-16 rounded-xl flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
                        <?php echo esc_html($cliente['iniciales']); ?>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h1 class="text-2xl font-bold text-gray-900"><?php echo esc_html($cliente['empresa']); ?></h1>
                            <?php
                            $clase_estado_detalle = match($cliente['estado']) {
                                'activo' => 'bg-green-100 text-green-800',
                                'inactivo' => 'bg-red-100 text-red-800',
                                'potencial' => 'bg-amber-100 text-amber-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                            ?>
                            <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo esc_attr($clase_estado_detalle); ?>">
                                <?php echo esc_html(ucfirst($cliente['estado'])); ?>
                            </span>
                        </div>
                        <p class="text-gray-500 mt-1"><?php echo esc_html($cliente['sector'] ?? ''); ?></p>
                    </div>
                </div>
                <p class="text-gray-600 leading-relaxed mb-4"><?php echo wp_kses_post($cliente['descripcion']); ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <?php echo esc_html($cliente['contacto']); ?>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?php echo esc_html($cliente['email']); ?>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?php echo esc_html($cliente['telefono']); ?>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <?php echo esc_html($cliente['direccion']); ?>
                    </div>
                </div>
            </div>

            <!-- Historial de interacciones -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Historial de interacciones', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-4">
                    <?php foreach ($historial_interacciones as $interaccion) :
                        $icono_interaccion = match($interaccion['tipo']) {
                            'reunion' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                            'email' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                            'llamada' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                            default => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
                        };
                    ?>
                    <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo esc_attr($icono_interaccion); ?>"/></svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-gray-800 font-medium"><?php echo esc_html($interaccion['texto']); ?></p>
                            <div class="flex items-center gap-3 mt-1">
                                <p class="text-xs text-gray-400"><?php echo esc_html(date_i18n('j M Y', strtotime($interaccion['fecha']))); ?></p>
                                <span class="text-xs text-gray-300">|</span>
                                <p class="text-xs text-gray-500"><?php echo esc_html($interaccion['autor']); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Notas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4"><?php echo esc_html__('Notas', 'flavor-chat-ia'); ?></h2>
                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-sm text-gray-700">
                    <?php echo wp_kses_post($cliente['notas']); ?>
                </div>
            </div>
        </div>

        <!-- Barra lateral -->
        <div class="space-y-6">

            <!-- Estadísticas del cliente -->
            <div class="bg-gradient-to-br from-slate-500 to-blue-600 rounded-xl p-6 text-white">
                <h3 class="font-semibold text-lg mb-4"><?php echo esc_html__('Resumen del cliente', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-slate-200 text-sm"><?php echo esc_html__('Desde', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium"><?php echo esc_html(date_i18n('M Y', strtotime($cliente['fecha_alta']))); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-200 text-sm"><?php echo esc_html__('Sector', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium"><?php echo esc_html($cliente['sector']); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-200 text-sm"><?php echo esc_html__('Interacciones', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium">24</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-200 text-sm"><?php echo esc_html__('Proyectos', 'flavor-chat-ia'); ?></span>
                        <span class="font-medium">3</span>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Acciones rápidas', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-2">
                    <a href="<?php echo esc_url('mailto:' . ($cliente['email'] ?? '')); ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 transition-colors group">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-blue-600"><?php echo esc_html__('Enviar email', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="<?php echo esc_url('tel:' . ($cliente['telefono'] ?? '')); ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 transition-colors group">
                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-green-600"><?php echo esc_html__('Llamar', 'flavor-chat-ia'); ?></span>
                    </a>
                    <a href="#reunion" class="flex items-center gap-3 p-3 rounded-lg hover:bg-blue-50 transition-colors group">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <span class="text-sm text-gray-700 group-hover:text-purple-600"><?php echo esc_html__('Agendar reunión', 'flavor-chat-ia'); ?></span>
                    </a>
                </div>
            </div>

            <!-- Proyectos relacionados -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-4"><?php echo esc_html__('Proyectos relacionados', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($proyectos_relacionados as $proyecto) :
                        $clase_estado_proyecto = match($proyecto['estado']) {
                            'En progreso' => 'bg-blue-100 text-blue-700',
                            'Planificado' => 'bg-amber-100 text-amber-700',
                            'Completado' => 'bg-green-100 text-green-700',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($proyecto['nombre']); ?></p>
                        <span class="px-2 py-0.5 rounded text-xs font-medium <?php echo esc_attr($clase_estado_proyecto); ?>">
                            <?php echo esc_html($proyecto['estado']); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>
