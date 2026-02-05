<?php
/**
 * Frontend: Single Plaza de Parking
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$plaza = $plaza ?? [];
$propietario = $propietario ?? [];
$plazas_cercanas = $plazas_cercanas ?? [];
?>

<div class="flavor-frontend flavor-parkings-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/parkings/')); ?>" class="hover:text-slate-600 transition-colors">Parkings</a>
        <span>›</span>
        <?php if (!empty($plaza['zona'])): ?>
        <a href="<?php echo esc_url(home_url('/parkings/?zona=' . ($plaza['zona_slug'] ?? ''))); ?>" class="hover:text-slate-600 transition-colors">
            <?php echo esc_html($plaza['zona']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($plaza['ubicacion'] ?? 'Plaza de parking'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Ubicacion mapa placeholder -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <div class="w-full h-full bg-gradient-to-br from-slate-400 to-gray-600 flex items-center justify-center">
                        <div class="text-center text-white">
                            <span class="text-6xl block mb-2">🗺️</span>
                            <p class="text-lg font-medium">Ubicacion de la plaza</p>
                        </div>
                    </div>
                    <div class="absolute top-4 left-4">
                        <span class="<?php echo ($plaza['disponible'] ?? true) ? 'bg-green-500' : 'bg-red-500'; ?> text-white text-sm font-medium px-4 py-2 rounded-full shadow-lg">
                            <?php echo ($plaza['disponible'] ?? true) ? 'Disponible' : 'Ocupada'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Informacion de la plaza -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-slate-100 text-slate-700 px-4 py-2 rounded-full font-medium text-sm">
                        <?php echo esc_html($plaza['tipo_vehiculo'] ?? 'Coche'); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        <?php echo esc_html($plaza['tamano'] ?? 'Estandar'); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($plaza['ubicacion'] ?? 'Plaza de parking'); ?>
                </h1>

                <!-- Info rapida -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-gray-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📍</span>
                        <div>
                            <p class="text-sm text-gray-500">Direccion</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($plaza['direccion'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🏢</span>
                        <div>
                            <p class="text-sm text-gray-500">Planta / Numero</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($plaza['planta'] ?? ''); ?> - N.<?php echo esc_html($plaza['numero'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🕐</span>
                        <div>
                            <p class="text-sm text-gray-500">Horario disponible</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($plaza['horario'] ?? 'Flexible'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🚗</span>
                        <div>
                            <p class="text-sm text-gray-500">Tipo de vehiculo</p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($plaza['tipo_vehiculo'] ?? 'Coche'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Descripcion -->
                <div class="prose prose-slate max-w-none mb-6">
                    <?php echo wp_kses_post($plaza['descripcion'] ?? ''); ?>
                </div>

                <!-- Normas de uso -->
                <?php if (!empty($plaza['normas'])): ?>
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">📋 Normas de uso</h2>
                    <div class="prose prose-slate max-w-none">
                        <?php echo wp_kses_post($plaza['normas']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Caracteristicas -->
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($plaza['techado'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">🏗️ Techado</span>
                    <?php endif; ?>
                    <?php if (!empty($plaza['vigilancia'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">📹 Vigilancia</span>
                    <?php endif; ?>
                    <?php if (!empty($plaza['acceso_24h'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">🔑 Acceso 24h</span>
                    <?php endif; ?>
                    <?php if (!empty($plaza['cargador_electrico'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">⚡ Cargador electrico</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Horario semanal -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📅 Disponibilidad semanal</h2>
                <div class="bg-gray-100 rounded-xl h-48 flex items-center justify-center text-gray-400">
                    <span class="text-lg">Calendario de disponibilidad</span>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Reservar -->
            <div class="bg-gradient-to-br from-slate-500 to-gray-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2">Reserva esta plaza</h3>
                <p class="text-slate-200 text-sm mb-2">
                    Precio: <?php echo esc_html($plaza['precio'] ?? '0'); ?> €/<?php echo esc_html($plaza['periodo_precio'] ?? 'mes'); ?>
                </p>
                <?php if (($plaza['disponible'] ?? true)): ?>
                <p class="text-slate-200 text-sm mb-4">Plaza disponible para reservar</p>
                <button class="w-full bg-white text-gray-700 py-3 px-4 rounded-xl font-semibold hover:bg-gray-50 transition-colors"
                        onclick="flavorParkings.reservarPlaza(<?php echo esc_attr($plaza['id'] ?? 0); ?>)">
                    🅿️ Reservar Plaza
                </button>
                <?php else: ?>
                <p class="text-slate-200 text-sm mb-4">Plaza no disponible en este momento</p>
                <button class="w-full bg-white/20 text-white py-3 px-4 rounded-xl font-semibold cursor-not-allowed" disabled>
                    Plaza ocupada
                </button>
                <?php endif; ?>
            </div>

            <!-- Propietario -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-slate-100 flex items-center justify-center text-slate-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($propietario['nombre'] ?? 'P', 0, 1)); ?>
                </div>
                <p class="text-xs text-gray-500 mb-1">Propietario</p>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($propietario['nombre'] ?? 'Propietario'); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($propietario['plazas_publicadas'] ?? 0); ?> plazas publicadas</p>
                <?php if (!empty($propietario['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full mb-4">
                    ✓ Vecino verificado
                </span>
                <?php endif; ?>
                <button class="w-full bg-slate-100 text-slate-700 py-2 px-4 rounded-xl text-sm font-medium hover:bg-slate-200 transition-colors"
                        onclick="flavorParkings.contactarPropietario(<?php echo esc_attr($propietario['id'] ?? 0); ?>)">
                    💬 Contactar
                </button>
            </div>

            <!-- Precio desglosado -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">💰 Precio</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Por hora</span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($plaza['precio_hora'] ?? '-'); ?> €</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Por dia</span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($plaza['precio_dia'] ?? '-'); ?> €</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600">Por mes</span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($plaza['precio_mes'] ?? '-'); ?> €</span>
                    </div>
                </div>
            </div>

            <!-- Plazas cercanas -->
            <?php if (!empty($plazas_cercanas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Plazas cercanas</h3>
                <div class="space-y-3">
                    <?php foreach ($plazas_cercanas as $plaza_cercana): ?>
                    <a href="<?php echo esc_url($plaza_cercana['url'] ?? '#'); ?>" class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="bg-slate-100 text-slate-700 rounded-lg p-2 text-center min-w-[44px] flex-shrink-0">
                                <span class="text-lg">🅿️</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($plaza_cercana['ubicacion'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($plaza_cercana['tipo_vehiculo'] ?? ''); ?> - <?php echo esc_html($plaza_cercana['precio'] ?? '0'); ?> €/<?php echo esc_html($plaza_cercana['periodo_precio'] ?? 'mes'); ?></p>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
