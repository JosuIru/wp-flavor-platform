<?php
/**
 * Frontend: Archive de Parkings
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$plazas_parking = $plazas_parking ?? [];
$total_plazas = $total_plazas ?? 0;
$estadisticas = $estadisticas ?? [];
$zonas_parking = $zonas_parking ?? [];
?>

<div class="flavor-frontend flavor-parkings-archive">
    <!-- Header con gradiente gris pizarra -->
    <div class="bg-gradient-to-r from-slate-500 to-gray-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2"><?php echo esc_html__('🅿️ Parkings Compartidos', 'flavor-chat-ia'); ?></h1>
                <p class="text-slate-200"><?php echo esc_html__('Comparte tu plaza de aparcamiento con los vecinos', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_plazas); ?> plazas disponibles
                </span>
                <button class="bg-white text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition-all shadow-md"
                        onclick="flavorParkings.publicarPlaza()">
                    <?php echo esc_html__('➕ Ofrecer Plaza', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">🅿️</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['plazas_disponibles'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Plazas disponibles', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📋</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['reservas_activas'] ?? 0); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Reservas activas', 'flavor-chat-ia'); ?></p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💰</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['ahorro_mensual'] ?? '0 €'); ?></p>
            <p class="text-sm text-gray-500"><?php echo esc_html__('Ahorro mensual', 'flavor-chat-ia'); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-gray-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('💡 ¿Como funciona?', 'flavor-chat-ia'); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-slate-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Busca', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Encuentra plazas de parking disponibles cerca de ti', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-slate-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📅</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Reserva', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Selecciona el horario que necesitas y confirma la reserva', 'flavor-chat-ia'); ?></p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-slate-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🅿️</div>
                <h3 class="font-semibold text-gray-800 mb-1"><?php echo esc_html__('Aparca', 'flavor-chat-ia'); ?></h3>
                <p class="text-sm text-gray-600"><?php echo esc_html__('Usa la plaza en el horario reservado y ahorra dinero', 'flavor-chat-ia'); ?></p>
            </div>
        </div>
    </div>

    <!-- Filtros por zona -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-slate-200 text-slate-700 font-medium hover:bg-slate-300 transition-colors filter-active" data-zona="todos">
            <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
        </button>
        <?php foreach ($zonas_parking as $zona_parking_item): ?>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-zona="<?php echo esc_attr($zona_parking_item['slug']); ?>">
            📍 <?php echo esc_html($zona_parking_item['nombre']); ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Grid de plazas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($plazas_parking)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">🅿️</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No hay plazas disponibles', 'flavor-chat-ia'); ?></h3>
            <p class="text-gray-500 mb-6"><?php echo esc_html__('¡Ofrece tu plaza cuando no la uses!', 'flavor-chat-ia'); ?></p>
            <button class="bg-slate-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-slate-600 transition-colors"
                    onclick="flavorParkings.publicarPlaza()">
                <?php echo esc_html__('Ofrecer Plaza', 'flavor-chat-ia'); ?>
            </button>
        </div>
        <?php else: ?>
        <?php foreach ($plazas_parking as $plaza): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Cabecera -->
                <div class="flex items-center justify-between mb-3">
                    <span class="bg-slate-100 text-slate-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($plaza['tipo_vehiculo'] ?? 'Coche'); ?>
                    </span>
                    <span class="<?php echo ($plaza['disponible'] ?? true) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo ($plaza['disponible'] ?? true) ? 'Disponible' : 'Ocupada'; ?>
                    </span>
                </div>

                <h3 class="text-lg font-semibold text-gray-800 mb-2 group-hover:text-slate-600 transition-colors">
                    <a href="<?php echo esc_url($plaza['url'] ?? '#'); ?>">
                        <?php echo esc_html($plaza['ubicacion'] ?? 'Plaza de parking'); ?>
                    </a>
                </h3>

                <p class="text-gray-600 text-sm mb-3 line-clamp-2">
                    <?php echo esc_html($plaza['descripcion'] ?? ''); ?>
                </p>

                <div class="flex flex-wrap gap-2 mb-3">
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        📍 <?php echo esc_html($plaza['zona'] ?? ''); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        🕐 <?php echo esc_html($plaza['horario'] ?? 'Flexible'); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                        🚗 <?php echo esc_html($plaza['tipo_vehiculo'] ?? 'Coche'); ?>
                    </span>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($plaza['propietario_nombre'] ?? 'P', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-600"><?php echo esc_html($plaza['propietario_nombre'] ?? 'Propietario'); ?></span>
                    </div>
                    <span class="bg-slate-100 text-slate-700 font-bold px-3 py-1 rounded-full text-sm">
                        <?php echo esc_html($plaza['precio'] ?? '0'); ?> €/<?php echo esc_html($plaza['periodo_precio'] ?? 'mes'); ?>
                    </span>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_plazas > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('← Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_plazas / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-slate-500 text-white hover:bg-slate-600 transition-colors"><?php echo esc_html__('Siguiente →', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>
</div>
