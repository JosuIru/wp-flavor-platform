<?php
/**
 * Frontend: Single Grupo de Consumo
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$grupo = $grupo ?? [];
$productores = $productores ?? [];
$productos = $productos ?? [];
$miembros = $miembros ?? [];
$es_miembro = $es_miembro ?? false;
?>

<div class="flavor-frontend flavor-grupos-consumo-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/grupos-consumo/')); ?>" class="hover:text-lime-600 transition-colors"><?php echo esc_html__('Grupos de Consumo', 'flavor-chat-ia'); ?></a>
        <span>›</span>
        <span class="text-gray-700"><?php echo esc_html($grupo['nombre'] ?? 'Grupo'); ?></span>
    </nav>

    <!-- Header del grupo -->
    <div class="bg-gradient-to-r from-lime-500 to-green-500 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex flex-col md:flex-row items-start gap-6">
            <div class="w-24 h-24 bg-white/20 rounded-2xl flex items-center justify-center text-5xl flex-shrink-0">
                <?php echo esc_html($grupo['icono'] ?? '🥕'); ?>
            </div>
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold"><?php echo esc_html($grupo['nombre']); ?></h1>
                    <?php if ($grupo['verificado'] ?? false): ?>
                    <span class="bg-white/20 px-3 py-1 rounded-full text-sm"><?php echo esc_html__('✓ Verificado', 'flavor-chat-ia'); ?></span>
                    <?php endif; ?>
                </div>
                <p class="text-lime-100 mb-4"><?php echo esc_html($grupo['descripcion']); ?></p>
                <div class="flex flex-wrap gap-4 text-sm">
                    <span class="flex items-center gap-1">👥 <?php echo esc_html($grupo['num_miembros'] ?? 0); ?> miembros</span>
                    <span class="flex items-center gap-1">📍 <?php echo esc_html($grupo['zona'] ?? ''); ?></span>
                    <span class="flex items-center gap-1">📅 Pedidos: <?php echo esc_html($grupo['dia_pedido'] ?? 'Semanal'); ?></span>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <?php if ($es_miembro): ?>
                <span class="bg-white/20 px-4 py-2 rounded-xl text-center"><?php echo esc_html__('✓ Eres miembro', 'flavor-chat-ia'); ?></span>
                <a href="#pedido" class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 transition-all text-center">
                    <?php echo esc_html__('🛒 Hacer pedido', 'flavor-chat-ia'); ?>
                </a>
                <?php else: ?>
                <button class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 transition-all"
                        onclick="flavorGruposConsumo.unirse(<?php echo esc_attr($grupo['id']); ?>)">
                    <?php echo esc_html__('Unirme al grupo', 'flavor-chat-ia'); ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Próximo pedido -->
            <?php if (!empty($grupo['proximo_pedido'])): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-6">
                <h2 class="text-lg font-semibold text-yellow-800 mb-2"><?php echo esc_html__('📦 Próximo pedido', 'flavor-chat-ia'); ?></h2>
                <p class="text-yellow-700">
                    <?php echo esc_html__('Fecha límite:', 'flavor-chat-ia'); ?> <strong><?php echo esc_html($grupo['proximo_pedido']['fecha_limite']); ?></strong>
                </p>
                <p class="text-yellow-600 text-sm mt-1">
                    Recogida: <?php echo esc_html($grupo['proximo_pedido']['fecha_recogida']); ?>
                    en <?php echo esc_html($grupo['proximo_pedido']['punto_recogida']); ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Productores -->
            <?php if (!empty($productores)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('🌾 Nuestros productores', 'flavor-chat-ia'); ?></h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($productores as $prod): ?>
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl">
                        <div class="w-16 h-16 rounded-full bg-lime-100 flex items-center justify-center text-2xl flex-shrink-0">
                            <?php echo esc_html($prod['icono'] ?? '👨‍🌾'); ?>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo esc_html($prod['nombre']); ?></h3>
                            <p class="text-sm text-gray-500"><?php echo esc_html($prod['productos']); ?></p>
                            <p class="text-xs text-lime-600">📍 <?php echo esc_html($prod['ubicacion']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Productos disponibles -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6" id="pedido">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('🥬 Productos disponibles', 'flavor-chat-ia'); ?></h2>
                <?php if (empty($productos)): ?>
                <p class="text-gray-500 text-center py-8"><?php echo esc_html__('No hay productos disponibles en este momento.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($productos as $prod): ?>
                    <div class="border border-gray-200 rounded-xl p-4 hover:border-lime-300 transition-colors">
                        <div class="aspect-square rounded-lg bg-gray-100 mb-3 flex items-center justify-center text-4xl">
                            <?php if (!empty($prod['imagen'])): ?>
                            <img src="<?php echo esc_url($prod['imagen']); ?>" alt="<?php echo esc_attr($prod['nombre']); ?>" class="w-full h-full object-cover rounded-lg">
                            <?php else: ?>
                            <?php echo esc_html($prod['icono'] ?? '🥬'); ?>
                            <?php endif; ?>
                        </div>
                        <h3 class="font-medium text-gray-800 text-sm"><?php echo esc_html($prod['nombre']); ?></h3>
                        <p class="text-lime-600 font-bold"><?php echo esc_html($prod['precio']); ?>€/<?php echo esc_html($prod['unidad']); ?></p>
                        <?php if ($es_miembro): ?>
                        <button class="w-full mt-2 bg-lime-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-lime-600 transition-colors"
                                onclick="flavorGruposConsumo.agregarProducto(<?php echo esc_attr($prod['id']); ?>)">
                            <?php echo esc_html__('+ Añadir', 'flavor-chat-ia'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Información del grupo -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4"><?php echo esc_html__('ℹ️ Sobre este grupo', 'flavor-chat-ia'); ?></h2>
                <div class="prose prose-lime max-w-none">
                    <?php echo wp_kses_post($grupo['descripcion_larga'] ?? $grupo['descripcion']); ?>
                </div>

                <?php if (!empty($grupo['normas'])): ?>
                <div class="mt-6 p-4 bg-gray-50 rounded-xl">
                    <h3 class="font-semibold text-gray-800 mb-2"><?php echo esc_html__('📋 Normas del grupo', 'flavor-chat-ia'); ?></h3>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <?php foreach ($grupo['normas'] as $norma): ?>
                        <li>• <?php echo esc_html($norma); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Info rápida -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('📊 Información', 'flavor-chat-ia'); ?></h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Creado', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-800"><?php echo esc_html($grupo['fecha_creacion'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Coordinador', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-800"><?php echo esc_html($grupo['coordinador'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Día de pedido', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-800"><?php echo esc_html($grupo['dia_pedido'] ?? 'Semanal'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Punto de recogida', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-800"><?php echo esc_html($grupo['punto_recogida'] ?? ''); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500"><?php echo esc_html__('Cuota mensual', 'flavor-chat-ia'); ?></dt>
                        <dd class="text-gray-800 font-semibold"><?php echo $grupo['cuota'] > 0 ? esc_html($grupo['cuota']) . '€' : 'Gratuito'; ?></dd>
                    </div>
                </dl>
            </div>

            <!-- Miembros -->
            <?php if (!empty($miembros)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4">👥 Miembros (<?php echo count($miembros); ?>)</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array_slice($miembros, 0, 12) as $miembro): ?>
                    <div class="w-10 h-10 rounded-full bg-lime-100 flex items-center justify-center text-lime-700 font-medium" title="<?php echo esc_attr($miembro['nombre']); ?>">
                        <?php echo esc_html(mb_substr($miembro['nombre'], 0, 1)); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($miembros) > 12): ?>
                    <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 text-xs">
                        +<?php echo count($miembros) - 12; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Contacto -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('📬 Contacto', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php if (!empty($grupo['email'])): ?>
                    <a href="mailto:<?php echo esc_attr($grupo['email']); ?>" class="flex items-center gap-2 text-gray-600 hover:text-lime-600 transition-colors">
                        ✉️ <?php echo esc_html($grupo['email']); ?>
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($grupo['telefono'])): ?>
                    <a href="tel:<?php echo esc_attr($grupo['telefono']); ?>" class="flex items-center gap-2 text-gray-600 hover:text-lime-600 transition-colors">
                        📞 <?php echo esc_html($grupo['telefono']); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Acciones -->
            <?php if (!$es_miembro): ?>
            <button class="w-full bg-gradient-to-r from-lime-500 to-green-500 text-white py-4 px-6 rounded-xl font-semibold hover:from-lime-600 hover:to-green-600 transition-all shadow-lg"
                    onclick="flavorGruposConsumo.unirse(<?php echo esc_attr($grupo['id']); ?>)">
                <?php echo esc_html__('🤝 Unirme a este grupo', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>
