<?php
/**
 * Frontend: Busqueda de Ayuda Vecinal
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
?>

<div class="flavor-search ayuda-vecinal">
    <!-- Header de busqueda -->
    <div class="bg-gradient-to-r from-orange-500 to-amber-500 py-12 px-4">
        <div class="container mx-auto max-w-4xl">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-6 text-center"><?php echo esc_html__('Buscar Ayuda', 'flavor-chat-ia'); ?></h1>

            <!-- Formulario de busqueda -->
            <form method="get" class="bg-white rounded-2xl p-4 shadow-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Que buscas -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Que necesitas?', 'flavor-chat-ia'); ?></label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="text"
                                   name="q"
                                   value="<?php echo esc_attr($query); ?>"
                                   placeholder="<?php echo esc_attr__('Ej: pasear perro, hacer compras, cuidar ninos...', 'flavor-chat-ia'); ?>"
                                   class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                        </div>
                    </div>

                    <!-- Tipo -->
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Tipo de ayuda', 'flavor-chat-ia'); ?></label>
                        <select name="tipo" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value=""><?php echo esc_html__('Todos', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('necesito', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Necesitan ayuda', 'flavor-chat-ia'); ?></option>
                            <option value="<?php echo esc_attr__('ofrezco', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Ofrecen ayuda', 'flavor-chat-ia'); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Filtros avanzados -->
                <details class="mt-4">
                    <summary class="text-sm text-orange-600 font-medium cursor-pointer hover:text-orange-700">
                        <?php echo esc_html__('Filtros avanzados', 'flavor-chat-ia'); ?>
                    </summary>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t border-gray-100">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></label>
                            <select name="categoria" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                                <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('compras', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Compras', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('transporte', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Transporte', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('cuidados', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Cuidados', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('mascotas', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Mascotas', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('hogar', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Hogar', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Distancia', 'flavor-chat-ia'); ?></label>
                            <select name="distancia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                                <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                                <option value="500"><?php echo esc_html__('500m', 'flavor-chat-ia'); ?></option>
                                <option value="1000"><?php echo esc_html__('1 km', 'flavor-chat-ia'); ?></option>
                                <option value="2000"><?php echo esc_html__('2 km', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Urgencia', 'flavor-chat-ia'); ?></label>
                            <select name="urgencia" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                                <option value=""><?php echo esc_html__('Todas', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('urgente', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Solo urgentes', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></label>
                            <select name="fecha" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-orange-500">
                                <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('semana', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                                <option value="<?php echo esc_attr__('mes', 'flavor-chat-ia'); ?>"><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
                            </select>
                        </div>
                    </div>
                </details>

                <div class="mt-4 flex justify-center">
                    <button type="submit"
                            class="px-8 py-3 rounded-xl text-white font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, #f59e0b 0%, #ea580c 100%);">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados -->
    <div class="container mx-auto max-w-4xl px-4 py-8">
        <?php if (!empty($query)): ?>
            <div class="mb-6">
                <h2 class="text-xl font-bold text-gray-900">
                    <?php echo esc_html($total_resultados); ?> resultados para "<?php echo esc_html($query); ?>"
                </h2>
            </div>
        <?php endif; ?>

        <?php if (empty($resultados) && !empty($query)): ?>
            <div class="bg-gray-50 rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No encontramos resultados', 'flavor-chat-ia'); ?></h3>
                <p class="text-gray-500 mb-4"><?php echo esc_html__('Prueba con otros terminos o crea tu propia solicitud', 'flavor-chat-ia'); ?></p>
                <a href="?" class="text-orange-600 font-medium hover:text-orange-700"><?php echo esc_html__('Ver todas las solicitudes', 'flavor-chat-ia'); ?></a>
            </div>
        <?php elseif (!empty($resultados)): ?>
            <div class="space-y-4">
                <?php foreach ($resultados as $solicitud): ?>
                    <article class="bg-white rounded-xl p-5 shadow-md hover:shadow-lg transition-shadow">
                        <div class="flex items-start gap-4">
                            <img src="<?php echo esc_url($solicitud['avatar'] ?? 'https://i.pravatar.cc/150?img=' . rand(1,70)); ?>"
                                 alt="" class="w-10 h-10 rounded-full object-cover">
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 hover:text-orange-600">
                                    <a href="<?php echo esc_url($solicitud['url'] ?? '#'); ?>">
                                        <?php echo esc_html($solicitud['titulo'] ?? 'Sin titulo'); ?>
                                    </a>
                                </h3>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo esc_html($solicitud['autor'] ?? 'Anonimo'); ?> · <?php echo esc_html($solicitud['tiempo'] ?? 'Hace 1 hora'); ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo ($solicitud['tipo'] ?? 'necesito') === 'necesito' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700'; ?>">
                                <?php echo ($solicitud['tipo'] ?? 'necesito') === 'necesito' ? 'Necesita' : 'Ofrece'; ?>
                            </span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
