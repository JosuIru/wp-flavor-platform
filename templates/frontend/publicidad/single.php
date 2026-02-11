<?php
/**
 * Frontend: Single Campana Publicitaria
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$campania = $campania ?? [];
$anunciante = $anunciante ?? [];
$metricas_rendimiento = $metricas_rendimiento ?? [];
$campanias_relacionadas = $campanias_relacionadas ?? [];
?>

<div class="flavor-frontend flavor-publicidad-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/publicidad/')); ?>" class="hover:text-pink-600 transition-colors"><?php echo esc_html__('Publicidad', 'flavor-chat-ia'); ?></a>
        <span>›</span>
        <?php if (!empty($campania['tipo'])): ?>
        <a href="<?php echo esc_url(home_url('/publicidad/?tipo=' . ($campania['tipo_slug'] ?? ''))); ?>" class="hover:text-pink-600 transition-colors">
            <?php echo esc_html($campania['tipo']); ?>
        </a>
        <span>›</span>
        <?php endif; ?>
        <span class="text-gray-700"><?php echo esc_html($campania['titulo'] ?? 'Campana'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Vista previa del creativo -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="aspect-video bg-gray-100 relative">
                    <?php if (!empty($campania['imagen_creativo'])): ?>
                    <img src="<?php echo esc_url($campania['imagen_creativo']); ?>" alt="<?php echo esc_attr($campania['titulo'] ?? ''); ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full bg-gradient-to-br from-pink-400 to-rose-600 flex items-center justify-center">
                        <span class="text-6xl">📢</span>
                    </div>
                    <?php endif; ?>
                    <div class="absolute top-4 left-4">
                        <span class="<?php
                            $estado_campania_detalle = $campania['estado'] ?? 'activa';
                            echo $estado_campania_detalle === 'activa' ? 'bg-green-500' : ($estado_campania_detalle === 'pausada' ? 'bg-amber-500' : 'bg-gray-500');
                        ?> text-white text-sm font-medium px-4 py-2 rounded-full shadow-lg">
                            <?php echo esc_html(ucfirst($estado_campania_detalle)); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Informacion de la campana -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-3 mb-4">
                    <span class="bg-pink-100 text-pink-700 px-4 py-2 rounded-full font-medium text-sm">
                        <?php echo esc_html($campania['tipo'] ?? 'Banner'); ?>
                    </span>
                    <span class="bg-gray-100 text-gray-600 px-3 py-1 rounded-full text-sm">
                        <?php echo esc_html($campania['formato'] ?? 'Estandar'); ?>
                    </span>
                </div>

                <h1 class="text-2xl font-bold text-gray-800 mb-4">
                    <?php echo esc_html($campania['titulo'] ?? ''); ?>
                </h1>

                <!-- Info rapida -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 p-4 bg-pink-50 rounded-xl">
                    <div class="flex items-center gap-3">
                        <span class="text-xl">💰</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Presupuesto', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($campania['presupuesto'] ?? '0'); ?> €</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📅</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Periodo', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($campania['fecha_inicio'] ?? ''); ?> - <?php echo esc_html($campania['fecha_fin'] ?? ''); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">🎯</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Segmentacion', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($campania['segmentacion'] ?? 'General'); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-xl">📍</span>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Zona geografica', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($campania['zona'] ?? 'Toda la comunidad'); ?></p>
                        </div>
                    </div>
                </div>

                <div class="prose prose-pink max-w-none">
                    <?php echo wp_kses_post($campania['descripcion'] ?? ''); ?>
                </div>
            </div>

            <!-- Metricas de rendimiento -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('📊 Rendimiento de la campana', 'flavor-chat-ia'); ?></h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-pink-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($metricas_rendimiento['impresiones'] ?? 0); ?></p>
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Impresiones', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-pink-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($metricas_rendimiento['clics'] ?? 0); ?></p>
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Clics', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-pink-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($metricas_rendimiento['ctr'] ?? '0%'); ?></p>
                        <p class="text-sm text-gray-500"><?php echo esc_html__('CTR', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div class="bg-pink-50 rounded-xl p-4 text-center">
                        <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($metricas_rendimiento['conversiones'] ?? 0); ?></p>
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Conversiones', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>
                <!-- Grafico de rendimiento placeholder -->
                <div class="bg-gray-100 rounded-xl h-48 flex items-center justify-center text-gray-400">
                    <span class="text-lg"><?php echo esc_html__('Grafico de rendimiento', 'flavor-chat-ia'); ?></span>
                </div>
            </div>

            <!-- Vista previa del creativo -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('🎨 Vista previa del creativo', 'flavor-chat-ia'); ?></h2>
                <div class="bg-gray-50 rounded-xl p-6 border-2 border-dashed border-gray-200 text-center">
                    <?php if (!empty($campania['imagen_creativo'])): ?>
                    <img src="<?php echo esc_url($campania['imagen_creativo']); ?>" alt="<?php echo esc_attr($campania['titulo'] ?? ''); ?>" class="max-w-full h-auto rounded-lg mx-auto">
                    <?php else: ?>
                    <div class="text-gray-400">
                        <span class="text-4xl block mb-2">🖼️</span>
                        <p><?php echo esc_html__('Vista previa del creativo no disponible', 'flavor-chat-ia'); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Gestion -->
            <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-2xl p-6 text-white">
                <h3 class="font-semibold mb-2"><?php echo esc_html__('Gestion de campana', 'flavor-chat-ia'); ?></h3>
                <p class="text-pink-100 text-sm mb-2">
                    Presupuesto: <?php echo esc_html($campania['presupuesto'] ?? '0'); ?> €
                </p>
                <p class="text-pink-100 text-sm mb-4">
                    Gastado: <?php echo esc_html($campania['gastado'] ?? '0'); ?> € (<?php echo esc_html($campania['porcentaje_gastado'] ?? '0'); ?>%)
                </p>
                <button class="w-full bg-white text-rose-600 py-3 px-4 rounded-xl font-semibold hover:bg-pink-50 transition-colors mb-2"
                        onclick="flavorPublicidad.editarCampania(<?php echo esc_attr($campania['id'] ?? 0); ?>)">
                    <?php echo esc_html__('✏️ Editar Campana', 'flavor-chat-ia'); ?>
                </button>
                <button class="w-full bg-white/20 text-white py-3 px-4 rounded-xl font-semibold hover:bg-white/30 transition-colors"
                        onclick="flavorPublicidad.pausarCampania(<?php echo esc_attr($campania['id'] ?? 0); ?>)">
                    <?php echo esc_html__('⏸️ Pausar Campana', 'flavor-chat-ia'); ?>
                </button>
            </div>

            <!-- Anunciante -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-pink-100 flex items-center justify-center text-pink-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($anunciante['nombre'] ?? 'A', 0, 1)); ?>
                </div>
                <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Anunciante', 'flavor-chat-ia'); ?></p>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($anunciante['nombre'] ?? 'Anunciante'); ?></h3>
                <p class="text-sm text-gray-500 mb-4"><?php echo esc_html($anunciante['campanias_totales'] ?? 0); ?> campanas publicadas</p>
                <?php if (!empty($anunciante['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">
                    <?php echo esc_html__('✓ Anunciante verificado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Targeting -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('🎯 Segmentacion', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Edad', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($campania['targeting_edad'] ?? 'Todas'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Intereses', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($campania['targeting_intereses'] ?? 'Todos'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Zona', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($campania['targeting_zona'] ?? 'Toda'); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600"><?php echo esc_html__('Dispositivo', 'flavor-chat-ia'); ?></span>
                        <span class="text-sm font-medium text-gray-800"><?php echo esc_html($campania['targeting_dispositivo'] ?? 'Todos'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Campanas relacionadas -->
            <?php if (!empty($campanias_relacionadas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Campanas similares', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($campanias_relacionadas as $campania_relacionada): ?>
                    <a href="<?php echo esc_url($campania_relacionada['url'] ?? '#'); ?>" class="block p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="flex items-start gap-3">
                            <div class="bg-pink-100 text-pink-700 rounded-lg p-2 text-center min-w-[44px] flex-shrink-0">
                                <span class="text-lg">📢</span>
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($campania_relacionada['titulo'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($campania_relacionada['tipo'] ?? ''); ?> - <?php echo esc_html($campania_relacionada['estado'] ?? ''); ?></p>
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
