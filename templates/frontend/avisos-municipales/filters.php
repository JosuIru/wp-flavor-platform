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
            <h3 class="text-lg font-bold text-gray-800">Filtrar avisos</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-sky-600 hover:text-sky-700 font-medium"
                    onclick="flavorAvisos.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-avisos" class="space-y-6">
            <!-- Urgencia -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Urgencia</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Barrio / Zona</label>
                <select name="barrio" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value="">Todos los barrios</option>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
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
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                <select name="fecha" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    <option value="">Cualquier fecha</option>
                    <option value="hoy" <?php echo ($filtros_activos['fecha'] ?? '') === 'hoy' ? 'selected' : ''; ?>>Hoy</option>
                    <option value="semana" <?php echo ($filtros_activos['fecha'] ?? '') === 'semana' ? 'selected' : ''; ?>>Esta semana</option>
                    <option value="mes" <?php echo ($filtros_activos['fecha'] ?? '') === 'mes' ? 'selected' : ''; ?>>Este mes</option>
                    <option value="trimestre" <?php echo ($filtros_activos['fecha'] ?? '') === 'trimestre' ? 'selected' : ''; ?>>Ultimos 3 meses</option>
                </select>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-sky-500 to-blue-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-sky-600 hover:to-blue-700 transition-all shadow-md">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
