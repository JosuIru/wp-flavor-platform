<?php
/**
 * Frontend: Filtros de Transparencia
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$filtros_activos = $filtros_activos ?? [];
$categorias = $categorias ?? [];
?>

<div class="flavor-frontend flavor-transparencia-filters">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-bold text-gray-800">Filtrar documentos</h3>
            <?php if (!empty($filtros_activos)): ?>
            <button class="text-sm text-teal-600 hover:text-teal-700 font-medium"
                    onclick="flavorTransparencia.limpiarFiltros()">
                Limpiar filtros
            </button>
            <?php endif; ?>
        </div>

        <form id="filtros-transparencia" class="space-y-6">
            <!-- Categoria -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Categoria</label>
                <div class="space-y-2">
                    <?php
                    $opciones_categoria_transparencia = [
                        'presupuestos'  => 'Presupuestos',
                        'contratos'     => 'Contratos',
                        'personal'      => 'Personal',
                        'subvenciones'  => 'Subvenciones',
                        'plenos'        => 'Plenos',
                    ];
                    foreach ($opciones_categoria_transparencia as $valor_cat_trans => $etiqueta_cat_trans): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($valor_cat_trans); ?>"
                               <?php echo in_array($valor_cat_trans, $filtros_activos['categorias'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-teal-500 border-gray-300 rounded focus:ring-teal-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_cat_trans); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tipo de documento -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de documento</label>
                <div class="space-y-2">
                    <?php
                    $opciones_tipo_documento = [
                        'acta'         => 'Acta',
                        'informe'      => 'Informe',
                        'presupuesto'  => 'Presupuesto',
                        'contrato'     => 'Contrato',
                        'resolucion'   => 'Resolucion',
                        'ordenanza'    => 'Ordenanza',
                    ];
                    foreach ($opciones_tipo_documento as $valor_tipo => $etiqueta_tipo): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="tipos[]" value="<?php echo esc_attr($valor_tipo); ?>"
                               <?php echo in_array($valor_tipo, $filtros_activos['tipos'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-teal-500 border-gray-300 rounded focus:ring-teal-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_tipo); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fecha -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Ano</label>
                <select name="ano" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <option value="">Cualquier ano</option>
                    <?php for ($anio_seleccion = intval(date('Y')); $anio_seleccion >= 2018; $anio_seleccion--): ?>
                    <option value="<?php echo esc_attr($anio_seleccion); ?>"
                            <?php echo ($filtros_activos['ano'] ?? '') == $anio_seleccion ? 'selected' : ''; ?>>
                        <?php echo esc_html($anio_seleccion); ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
                <select name="mes" class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    <option value="">Cualquier mes</option>
                    <?php
                    $nombres_meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
                    foreach ($nombres_meses as $indice_mes => $nombre_mes): ?>
                    <option value="<?php echo esc_attr($indice_mes + 1); ?>"
                            <?php echo ($filtros_activos['mes'] ?? '') == ($indice_mes + 1) ? 'selected' : ''; ?>>
                        <?php echo esc_html($nombre_mes); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Formato -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Formato</label>
                <div class="space-y-2">
                    <?php
                    $opciones_formato = [
                        'pdf'   => 'PDF',
                        'excel' => 'Excel',
                        'csv'   => 'CSV',
                    ];
                    foreach ($opciones_formato as $valor_formato => $etiqueta_formato): ?>
                    <label class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input type="checkbox" name="formatos[]" value="<?php echo esc_attr($valor_formato); ?>"
                               <?php echo in_array($valor_formato, $filtros_activos['formatos'] ?? []) ? 'checked' : ''; ?>
                               class="w-4 h-4 text-teal-500 border-gray-300 rounded focus:ring-teal-500">
                        <span class="text-gray-700"><?php echo esc_html($etiqueta_formato); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Boton aplicar -->
            <button type="submit"
                    class="w-full bg-gradient-to-r from-teal-500 to-cyan-500 text-white py-3 px-6 rounded-xl font-semibold hover:from-teal-600 hover:to-cyan-600 transition-all shadow-md">
                Aplicar filtros
            </button>
        </form>
    </div>
</div>
