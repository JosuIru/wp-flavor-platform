<?php
/**
 * Frontend: Filtros de Avisos Municipales
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$barrios = $barrios ?? [];
?>

<div class="flavor-frontend flavor-avisos-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800"><?php echo esc_html__('Filtrar avisos', 'flavor-chat-ia'); ?></h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-sky-600 hover:text-sky-700 font-medium"
                    onclick="flavorAvisos.limpiarFiltros()">
                <?php echo esc_html__('Limpiar filtros', 'flavor-chat-ia'); ?>
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-avisos" class="space-y-6">
            <!-- Urgencia -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Urgencia', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <?php
                    $opciones_urgencia = [
                        'informativo' => ['label' => 'Informativo', 'color' => 'bg-sky-500'],
                        'importante'  => ['label' => 'Importante', 'color' => 'bg-amber-500'],
                        'urgente'     => ['label' => 'Urgente', 'color' => 'bg-red-500'],
                    ];
                    foreach ($opciones_urgencia as $valor_urgencia => $datos_urgencia): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="urgencia[]" value="<?php echo esc_attr($valor_urgencia); ?>"
                               <?php echo in_array($valor_urgencia, $filtros_activos['urgencia'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="flex items-center gap-2">
                            <span class="w-3 h-3 <?php echo esc_attr($datos_urgencia['color']); ?> rounded-full"></span>
                            <span class="text-gray-700"><?php echo esc_html($datos_urgencia['label']); ?></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Barrio / Zona -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Barrio / Zona', 'flavor-chat-ia'); ?></label>
                <select name="barrio" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value=""><?php echo esc_html__('Todos los barrios', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($barrios as $barrio_opcion): ?>
                    <option value="<?php echo esc_attr($barrio_opcion['id'] ?? ''); ?>"
                            <?php echo ($filtros_activos['barrio'] ?? '') == ($barrio_opcion['id'] ?? '') ? 'selected' : ''; ?>>
                        <?php echo esc_html($barrio_opcion['nombre'] ?? ''); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Categoria -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Categoria', 'flavor-chat-ia'); ?></label>
                <div class="space-y-2">
                    <?php
                    $opciones_categoria_aviso = [
                        'obras'           => 'Obras',
                        'servicios'       => 'Servicios',
                        'trafico'         => 'Trafico',
                        'medio-ambiente'  => 'Medio Ambiente',
                        'cultural'        => 'Cultural',
                    ];
                    foreach ($opciones_categoria_aviso as $valor_cat_aviso => $etiqueta_cat_aviso): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_cat_aviso); ?>"
                               <?php echo in_array($valor_cat_aviso, $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_cat_aviso); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></label>
                <select name="fecha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value=""><?php echo esc_html__('Cualquier fecha', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('hoy', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>><?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('semana', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>><?php echo esc_html__('Esta semana', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('mes', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>><?php echo esc_html__('Este mes', 'flavor-chat-ia'); ?></option>
                    <option value="<?php echo esc_attr__('trimestre', 'flavor-chat-ia'); ?>" <?php echo ($filtros_activos['fecha'] ?? '') === 'trimestre' ? 'selected' : ''; ?>><?php echo esc_html__('Ultimos 3 meses', 'flavor-chat-ia'); ?></option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-sky-500 to-blue-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-sky-600 hover:to-blue-700 transition-all shadow-md">
                <?php echo esc_html__('Aplicar filtros', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </div>
</div>
