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
                <h1 class="text-3xl font-bold mb-2">🅿️ Parkings Compartidos</h1>
                <p class="text-slate-200">Comparte tu plaza de aparcamiento con los vecinos</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_plazas); ?> plazas disponibles
                </span>
                <button class="bg-white text-gray-700 px-6 py-3 rounded-xl font-semibold hover:bg-gray-50 transition-all shadow-md"
                        onclick="flavorParkings.publicarPlaza()">
                    ➕ Ofrecer Plaza
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
            <p class="text-sm text-gray-500">Plazas disponibles</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">📋</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['reservas_activas'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Reservas activas</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">💰</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['ahorro_mensual'] ?? '0 €'); ?></p>
            <p class="text-sm text-gray-500">Ahorro mensual</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-gray-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">💡 ¿Como funciona?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-slate-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🔍</div>
                <h3 class="font-semibold text-gray-800 mb-1">Busca</h3>
                <p class="text-sm text-gray-600">Encuentra plazas de parking disponibles cerca de ti</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-slate-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">📅</div>
                <h3 class="font-semibold text-gray-800 mb-1">Reserva</h3>
                <p class="text-sm text-gray-600">Selecciona el horario que necesitas y confirma la reserva</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-slate-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">🅿️</div>
                <h3 class="font-semibold text-gray-800 mb-1">Aparca</h3>
                <p class="text-sm text-gray-600">Usa la plaza en el horario reservado y ahorra dinero</p>
            </div>
        </div>
    </div>

    <!-- Filtros por zona -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-slate-200 text-slate-700 font-medium hover:bg-slate-300 transition-colors filter-active" data-zona="todos">
            Todos
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
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay plazas disponibles</h3>
            <p class="text-gray-500 mb-6">¡Ofrece tu plaza cuando no la uses!</p>
            <button class="bg-slate-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-slate-600 transition-colors"
                    onclick="flavorParkings.publicarPlaza()">
                Ofrecer Plaza
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
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">← Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_plazas / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-slate-500 text-white hover:bg-slate-600 transition-colors">Siguiente →</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
