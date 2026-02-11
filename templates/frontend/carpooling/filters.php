<?php
/**
 * Frontend: Filtros de Carpooling
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-carpooling-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900"><?php echo esc_html__('Filtros', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-green-600 hover:text-green-700 font-medium"><?php echo esc_html__('Limpiar', 'flavor-chat-ia'); ?></a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Origen -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Origen', 'flavor-chat-ia'); ?></h4>
                <input type="text" name="origen" value="<?php echo esc_attr($filtros_activos['origen'] ?? ''); ?>"
                       placeholder="<?php echo esc_attr__('Ciudad o punto de salida...', 'flavor-chat-ia'); ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Destino -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Destino', 'flavor-chat-ia'); ?></h4>
                <input type="text" name="destino" value="<?php echo esc_attr($filtros_activos['destino'] ?? ''); ?>"
                       placeholder="<?php echo esc_attr__('Ciudad o punto de llegada...', 'flavor-chat-ia'); ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Fecha -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></h4>
                <input type="date" name="fecha" value="<?php echo esc_attr($filtros_activos['fecha'] ?? ''); ?>"
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
            </div>

            <!-- Plazas minimas -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Plazas minimas', 'flavor-chat-ia'); ?></h4>
                <select name="plazas_minimas" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                    <option value=""><?php echo esc_html__('Cualquiera', 'flavor-chat-ia'); ?></option>
                    <option value="1" <?php echo ($filtros_activos['plazas_minimas'] ?? '') === '1' ? 'selected' : ''; ?>><?php echo esc_html__('1 plaza', 'flavor-chat-ia'); ?></option>
                    <option value="2" <?php echo ($filtros_activos['plazas_minimas'] ?? '') === '2' ? 'selected' : ''; ?>><?php echo esc_html__('2 plazas', 'flavor-chat-ia'); ?></option>
                    <option value="3" <?php echo ($filtros_activos['plazas_minimas'] ?? '') === '3' ? 'selected' : ''; ?>><?php echo esc_html__('3 plazas', 'flavor-chat-ia'); ?></option>
                    <option value="4" <?php echo ($filtros_activos['plazas_minimas'] ?? '') === '4' ? 'selected' : ''; ?>><?php echo esc_html__('4+ plazas', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Precio maximo -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Precio maximo', 'flavor-chat-ia'); ?></h4>
                <div class="relative">
                    <input type="number" name="precio_maximo" value="<?php echo esc_attr($filtros_activos['precio_maximo'] ?? ''); ?>"
                           placeholder="<?php echo esc_attr__('Ej: 15', 'flavor-chat-ia'); ?>"
                           min="0" step="0.50"
                           class="w-full px-3 py-2 pr-8 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">€</span>
                </div>
            </div>

            <!-- Ordenar -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3"><?php echo esc_html__('Ordenar por', 'flavor-chat-ia'); ?></h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-green-500">
                    <option value="<?php echo esc_attr__('fecha_asc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'fecha_asc' ? 'selected' : ''; ?>><?php echo esc_html__('Proximos primero', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('precio_asc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'precio_asc' ? 'selected' : ''; ?>><?php echo esc_html__('Precio mas bajo', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('plazas_desc', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'plazas_desc' ? 'selected' : ''; ?>><?php echo esc_html__('Mas plazas libres', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('valoracion', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['ordenar'] ?? '') === 'valoracion' ? 'selected' : ''; ?>><?php echo esc_html__('Mejor valorados', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Boton aplicar -->
                <button type="submit"
                    class="w-full bg-gradient-to-r from-lime-500 to-green-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-lime-600 hover:to-green-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar Filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
