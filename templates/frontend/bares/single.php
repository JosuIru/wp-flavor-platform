<?php
/**
 * Frontend: Single Bar/Restaurante
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$local = $local ?? [];
$resenas = $resenas ?? [];
$locales_relacionados = $locales_relacionados ?? [];
?>

<div class="flavor-frontend flavor-bares-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/bares/')); ?>" class="hover:text-orange-600 transition-colors">Bares y Restaurantes</a>
        <span>›</span>
        <?php if (!empty($local['tipo_cocina'])): ?>
        <a href="<?php echo esc_url(home_url('/bares/?tipo=' . ($local['tipo_cocina_slug'] ?? ''))); ?>" class="hover:text-orange-600 transition-colors">
            <?php echo esc_html($local['tipo_cocina']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($local['nombre'] ?? 'Local'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Foto principal -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <?php if (!empty($local['imagen'])): ?>
                    <img src="<?php echo esc_url($local['imagen']); ?>" alt="<?php echo esc_attr($local['nombre'] ?? ''); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center text-gray-400">
                        <span class="text-6xl">🍽️</span>
                    </div>
                    <?php endif; ?>
                    <span class="absolute top-4 left-4 <?php echo ($local['abierto'] ?? false) ? 'bg-green-500' : 'bg-red-500'; ?> text-white px-4 py-2 rounded-full font-medium shadow">
                        <?php echo ($local['abierto'] ?? false) ? '✓ Abierto ahora' : '✗ Cerrado'; ?>
                    </span>
                </div>
            </div>

            <!-- Informacion del local -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-amber-100 text-amber-700 px-4 py-2 rounded-full font-medium text-sm">
                        <?php echo esc_html($local['tipo_cocina'] ?? 'Variada'); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo esc_html(str_repeat('€', $local['rango_precio'] ?? 1)); ?>
                    </span>
                    <div class="flex items-center gap-1 text-yellow-400">
                        <?php
                        $valoracion_detalle = floatval($local['valoracion'] ?? 0);
                        for ($estrella_detalle = 1; $estrella_detalle <= 5; $estrella_detalle++):
                        ?>
                        <span><?php echo $estrella_detalle <= $valoracion_detalle ? '★' : '☆'; ?></span>
                        <?php endfor; ?>
                        <span class="text-sm text-gray-500 ml-1">(<?php echo esc_html($local['total_resenas'] ?? 0); ?>)</span>
                    </div>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($local['nombre'] ?? ''); ?>
                </h1>

                <div class="prose prose-orange max-w-none mb-6">
                    <?php echo wp_kses_post($local['descripcion'] ?? ''); ?>
                </div>

                <!-- Platos destacados -->
                <?php if (!empty($local['platos_destacados'])): ?>
                <div class="border-t border-gray-100 pt-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">🍳 Platos destacados</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php foreach ($local['platos_destacados'] as $plato_destacado): ?>
                        <div class="flex items-center justify-between p-3 bg-amber-50 rounded-xl">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo esc_html($plato_destacado['nombre']); ?></p>
                                <p class="text-sm text-gray-500"><?php echo esc_html($plato_destacado['descripcion'] ?? ''); ?></p>
                            </div>
                            <span class="text-orange-600 font-bold whitespace-nowrap ml-3"><?php echo esc_html($plato_destacado['precio'] ?? ''); ?> €</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Horario -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">🕐 Horario de apertura</h2>
                <?php if (!empty($local['horario'])): ?>
                <div class="space-y-2">
                    <?php foreach ($local['horario'] as $dia_semana => $horas_apertura): ?>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <span class="text-gray-700 font-medium"><?php echo esc_html($dia_semana); ?></span>
                        <span class="text-gray-600"><?php echo esc_html($horas_apertura); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500">Horario no disponible</p>
                <?php endif; ?>
            </div>

            <!-- Ubicacion -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">📍 Ubicacion</h2>
                <div class="bg-gray-100 rounded-xl h-48 flex items-center justify-center text-gray-400 mb-3">
                    <span class="text-lg">Mapa de ubicacion</span>
                </div>
                <p class="text-gray-600"><?php echo esc_html($local['direccion'] ?? 'Direccion no disponible'); ?></p>
            </div>

            <!-- Resenas -->
            <?php if (!empty($resenas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">⭐ Resenas</h2>
                <div class="space-y-4">
                    <?php foreach ($resenas as $resena_individual): ?>
                    <div class="border-b border-gray-100 pb-4 last:border-0">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="flex text-yellow-400">
                                <?php for ($indice_estrella = 0; $indice_estrella < 5; $indice_estrella++): ?>
                                <span><?php echo $indice_estrella < ($resena_individual['puntuacion'] ?? 0) ? '★' : '☆'; ?></span>
                                <?php endfor; ?>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo esc_html($resena_individual['fecha'] ?? ''); ?></span>
                        </div>
                        <p class="text-gray-600 text-sm"><?php echo esc_html($resena_individual['comentario'] ?? ''); ?></p>
                        <p class="text-xs text-gray-400 mt-1">— <?php echo esc_html($resena_individual['autor'] ?? ''); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Reservar -->
            <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2">Reserva tu mesa</h3>
                <p class="text-amber-100 text-sm mb-4">Contacta con <?php echo esc_html($local['nombre'] ?? 'el local'); ?> para reservar.</p>
                <button class="w-full bg-white text-orange-600 py-3 px-4 rounded-xl font-semibold hover:bg-amber-50 transition-colors mb-3"
                        onclick="flavorBares.reservar(<?php echo esc_attr($local['id'] ?? 0); ?>)">
                    📞 Reservar mesa
                </button>
                <button class="w-full bg-white/20 backdrop-blur text-white py-3 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors"
                        onclick="flavorBares.guardarFavorito(<?php echo esc_attr($local['id'] ?? 0); ?>)">
                    ❤️ Guardar favorito
                </button>
            </div>

            <!-- Informacion de contacto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">📋 Informacion de contacto</h3>
                <div class="space-y-3">
                    <?php if (!empty($local['telefono'])): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-lg">📞</span>
                        <span class="text-gray-700"><?php echo esc_html($local['telefono']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($local['email'])): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-lg">📧</span>
                        <span class="text-gray-700"><?php echo esc_html($local['email']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($local['web'])): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-lg">🌐</span>
                        <a href="<?php echo esc_url($local['web']); ?>" class="text-orange-600 hover:text-orange-700" target="_blank" rel="noopener noreferrer">
                            Sitio web
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($local['direccion'])): ?>
                    <div class="flex items-center gap-3">
                        <span class="text-lg">📍</span>
                        <span class="text-gray-700"><?php echo esc_html($local['direccion']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Locales relacionados -->
            <?php if (!empty($locales_relacionados)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Locales similares</h3>
                <div class="space-y-3">
                    <?php foreach ($locales_relacionados as $local_relacionado): ?>
                    <a href="<?php echo esc_url($local_relacionado['url'] ?? '#'); ?>" class="flex gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-14 h-14 rounded-lg bg-gray-100 flex-shrink-0 overflow-hidden">
                            <?php if (!empty($local_relacionado['imagen'])): ?>
                            <img src="<?php echo esc_url($local_relacionado['imagen']); ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-gray-400">🍽️</div>
                            <?php endif; ?>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($local_relacionado['nombre'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($local_relacionado['tipo_cocina'] ?? ''); ?></p>
                            <div class="flex items-center gap-1 text-yellow-400 text-xs">
                                <span>★</span>
                                <span class="text-gray-500"><?php echo esc_html($local_relacionado['valoracion'] ?? ''); ?></span>
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
