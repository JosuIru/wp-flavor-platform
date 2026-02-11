<?php
/**
 * Frontend: Single Viaje Carpooling
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$viaje = $viaje ?? [];
$conductor = $conductor ?? [];
$pasajeros_lista = $pasajeros_lista ?? [];
$viajes_relacionados = $viajes_relacionados ?? [];
?>

<div class="flavor-frontend flavor-carpooling-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/carpooling/')); ?>" class="hover:text-green-600 transition-colors"><?php echo esc_html__('Carpooling', 'flavor-chat-ia'); ?></a>
        <span>›</span>
        <span class="text-gray-700"><?php echo esc_html($viaje['origen'] ?? ''); ?> → <?php echo esc_html($viaje['destino'] ?? ''); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Mapa de ruta placeholder -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <div class="w-full h-full bg-gradient-to-br from-lime-400 to-green-600 flex items-center justify-center">
                        <div class="text-center text-white">
                            <span class="text-6xl block mb-2">🗺️</span>
                            <p class="text-lg font-medium"><?php echo esc_html__('Mapa de ruta', 'flavor-chat-ia'); ?></p>
                        </div>
                    </div>
                    <div class="absolute top-4 left-4 bg-white rounded-xl px-4 py-2 shadow-lg">
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($viaje['distancia'] ?? ''); ?> km</p>
                    </div>
                    <div class="absolute top-4 right-4 bg-white rounded-xl px-4 py-2 shadow-lg">
                        <p class="text-sm font-medium text-gray-800"><?php echo esc_html($viaje['duracion'] ?? ''); ?></p>
                    </div>
                </div>
            </div>

            <!-- Informacion del viaje -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($viaje['origen'] ?? ''); ?> → <?php echo esc_html($viaje['destino'] ?? ''); ?>
                </h1>

                <!-- Ruta detallada -->
                <div class="mb-6 p-4 bg-lime-50 rounded-xl">
                    <div class="flex items-start gap-4">
                        <div class="flex flex-col items-center mt-1">
                            <div class="w-4 h-4 rounded-full bg-lime-500 border-2 border-white shadow"></div>
                            <div class="w-0.5 h-16 bg-lime-300"></div>
                            <div class="w-4 h-4 rounded-full bg-green-600 border-2 border-white shadow"></div>
                        </div>
                        <div class="flex-1 space-y-6">
                            <div>
                                <p class="text-sm text-gray-500"><?php echo esc_html__('Origen', 'flavor-chat-ia'); ?></p>
                                <p class="font-medium text-gray-800"><?php echo esc_html($viaje['origen'] ?? ''); ?></p>
                                <p class="text-sm text-gray-600"><?php echo esc_html($viaje['direccion_origen'] ?? ''); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500"><?php echo esc_html__('Destino', 'flavor-chat-ia'); ?></p>
                                <p class="font-medium text-gray-800"><?php echo esc_html($viaje['destino'] ?? ''); ?></p>
                                <p class="text-sm text-gray-600"><?php echo esc_html($viaje['direccion_destino'] ?? ''); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Info rapida -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📅</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($viaje['fecha_completa'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🕐</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Hora de salida', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($viaje['hora_salida'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">💺</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Plazas disponibles', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($viaje['plazas_libres'] ?? 0); ?> de <?php echo esc_html($viaje['plazas_totales'] ?? 0); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">💰</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Precio por plaza', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($viaje['precio'] ?? '0'); ?> €</p>
                        </div>
                    </div>
                </div>

                <!-- Normas del viaje -->
                <?php if (!empty($viaje['normas'])): ?>
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3">📋 <?php echo esc_html__('Normas del viaje', 'flavor-chat-ia'); ?></h2>
                    <div class="prose prose-green max-w-none">
                        <?php echo wp_kses_post($viaje['normas']); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Preferencias -->
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($viaje['permite_fumar'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">🚭 <?php echo esc_html__('No fumar', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($viaje['permite_mascotas'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">🐾 <?php echo esc_html__('Mascotas OK', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($viaje['permite_equipaje'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">🧳 <?php echo esc_html__('Equipaje mediano', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($viaje['musica'])): ?>
                    <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full">🎵 <?php echo esc_html__('Musica OK', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pasajeros confirmados -->
            <?php if (!empty($pasajeros_lista)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">👥 <?php echo esc_html__('Pasajeros confirmados', 'flavor-chat-ia'); ?> (<?php echo count($pasajeros_lista); ?>)</h2>
                <div class="flex flex-wrap gap-3">
                    <?php foreach ($pasajeros_lista as $pasajero_persona): ?>
                    <div class="flex items-center gap-2 bg-gray-50 rounded-full px-3 py-1">
                        <div class="w-8 h-8 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-xs font-medium">
                            <?php echo esc_html(mb_substr($pasajero_persona['nombre'] ?? 'P', 0, 1)); ?>
                        </div>
                        <span class="text-sm text-gray-700"><?php echo esc_html($pasajero_persona['nombre'] ?? ''); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Reservar plaza -->
            <div class="bg-gradient-to-br from-lime-500 to-green-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2"><?php echo esc_html__('¡Reserva tu plaza!', 'flavor-chat-ia'); ?></h3>
                <p class="text-lime-100 text-sm mb-2">
                    <?php echo esc_html__('Precio por plaza:', 'flavor-chat-ia'); ?> <?php echo esc_html($viaje['precio'] ?? '0'); ?> €
                </p>
                <?php if (($viaje['plazas_libres'] ?? 0) > 0): ?>
                <p class="text-lime-100 text-sm mb-4">
                    <?php
                    echo esc_html(
                        sprintf(
                            _n('Queda %d plaza disponible', 'Quedan %d plazas disponibles', (int) $viaje['plazas_libres'], 'flavor-chat-ia'),
                            (int) $viaje['plazas_libres']
                        )
                    );
                    ?>
                </p>
                <?php else: ?>
                <p class="text-lime-100 text-sm mb-4"><?php echo esc_html__('No quedan plazas disponibles', 'flavor-chat-ia'); ?></p>
                <?php endif; ?>
                <button class="w-full bg-white text-green-600 py-3 px-4 rounded-xl font-semibold hover:bg-lime-50 transition-colors"
                        onclick="flavorCarpooling.reservarPlaza(<?php echo esc_attr($viaje['id'] ?? 0); ?>)">
                    🚗 <?php echo esc_html__('Reservar Plaza', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Conductor -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($conductor['nombre'] ?? 'C', 0, 1)); ?>
                </div>
                <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Conductor', 'flavor-chat-ia'); ?></p>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($conductor['nombre'] ?? __('Conductor', 'flavor-chat-ia')); ?></h3>
                <p class="text-sm text-gray-500 mb-2">
                    <?php
                    echo esc_html(
                        sprintf(
                            _n('%d viaje realizado', '%d viajes realizados', (int) ($conductor['viajes_realizados'] ?? 0), 'flavor-chat-ia'),
                            (int) ($conductor['viajes_realizados'] ?? 0)
                        )
                    );
                    ?>
                </p>
                <?php if (!empty($conductor['valoracion'])): ?>
                <div class="flex items-center justify-center gap-1 mb-4">
                    <span class="text-amber-400">⭐</span>
                    <span class="text-sm font-medium text-gray-700"><?php echo esc_html($conductor['valoracion']); ?>/5</span>
                </div>
                <?php endif; ?>
                <?php if (!empty($conductor['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">
                    ✓ <?php echo esc_html__('Conductor verificado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Vehiculo -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">🚗 <?php echo esc_html__('Vehiculo', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Modelo', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($conductor['vehiculo_modelo'] ?? ''); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Color', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($conductor['vehiculo_color'] ?? ''); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Plazas', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($conductor['vehiculo_plazas'] ?? ''); ?></span>
                    </div>
                </div>
            </div>

            <!-- Impacto ambiental -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">🌿 <?php echo esc_html__('Impacto ambiental', 'flavor-chat-ia'); ?></h3>
                <div class="text-center">
                    <p class="text-3xl font-bold text-green-600 mb-1"><?php echo esc_html($viaje['co2_ahorrado'] ?? '0'); ?> kg</p>
                    <p class="text-sm text-gray-500"><?php echo esc_html__('CO2 ahorrado por pasajero', 'flavor-chat-ia'); ?></p>
                </div>
            </div>

            <!-- Viajes relacionados -->
            <?php if (!empty($viajes_relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Viajes similares', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($viajes_relacionados as $viaje_relacionado): ?>
                    <a href="<?php echo esc_url($viaje_relacionado['url'] ?? '#'); ?>" class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="bg-lime-100 text-lime-700 rounded-lg p-2 text-center min-w-[44px] flex-shrink-0">
                                <span class="text-lg">🚗</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($viaje_relacionado['origen'] ?? ''); ?> → <?php echo esc_html($viaje_relacionado['destino'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($viaje_relacionado['fecha'] ?? ''); ?> - <?php echo esc_html($viaje_relacionado['precio'] ?? '0'); ?> €</p>
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
