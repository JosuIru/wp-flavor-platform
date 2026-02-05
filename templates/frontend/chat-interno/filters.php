<?php
/**
 * Frontend: Filtros de Chat Interno
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
?>

<div class="flavor-frontend flavor-chat-interno-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-900">Filtros</h3>
            <?php if (!empty($filtros_activos)): ?>
            <a href="?" class="text-sm text-blue-600 hover:text-blue-700 font-medium">Limpiar</a>
            <?php endif; ?>
        </div>

        <form method="get" class="space-y-6">
            <!-- Estado de lectura -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Estado</h4>
                <div class="space-y-2">
                    <?php
                    $opciones_estado_lectura = [
                        '' => 'Todos',
                        'no_leido' => '🔔 No leidos',
                        'leido' => '✅ Leidos',
                    ];
                    foreach ($opciones_estado_lectura as $valor_estado_lectura => $etiqueta_estado_lectura):
                    ?>
                    <label class="flex items-center gap-3 cursor-pointer group">
                        <input type="radio" name="estado" value="<?php echo esc_attr($valor_estado_lectura); ?>"
                               <?php echo ($filtros_activos['estado'] ?? '') === $valor_estado_lectura ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                        <span class="text-sm text-gray-700 group-hover:text-blue-600 transition-colors">
                            <?php echo esc_html($etiqueta_estado_lectura); ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Rango de fechas -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Fecha</h4>
                <select name="fecha" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Cualquier fecha</option>
                    <option value="hoy" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                    <option value="esta_semana" <?php echo ($filtros_activos['fecha'] ?? '') === 'esta_semana' ? 'selected' : ''; ?>>Esta semana</option>
                    <option value="este_mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'este_mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="ultimos_3_meses" <?php echo ($filtros_activos['fecha'] ?? '') === 'ultimos_3_meses' ? 'selected' : ''; ?>>Ultimos 3 meses</option>
                </select>
            </div>

            <!-- Buscar usuario -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Usuario</h4>
                <input type="text" name="usuario" value="<?php echo esc_attr($filtros_activos['usuario'] ?? ''); ?>"
                       placeholder="Nombre del contacto..."
                       class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Ordenar -->
            <div>
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Ordenar por</h4>
                <select name="ordenar" class="w-full px-3 py-2 rounded-lg border border-gray-200 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="recientes" <?php echo ($filtros_activos['ordenar'] ?? '') === 'recientes' ? 'selected' : ''; ?>>Mas recientes</option>
                    <option value="no_leidos" <?php echo ($filtros_activos['ordenar'] ?? '') === 'no_leidos' ? 'selected' : ''; ?>>No leidos primero</option>
                    <option value="nombre" <?php echo ($filtros_activos['ordenar'] ?? '') === 'nombre' ? 'selected' : ''; ?>>Nombre A-Z</option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-sky-500 to-blue-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-sky-600 hover:to-blue-700 transition-all shadow-md">
                Aplicar Filtros
            </button>
        </form>
    </div>
</div>
