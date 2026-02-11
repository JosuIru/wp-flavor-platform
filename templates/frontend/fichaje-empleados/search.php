<?php
/**
 * Frontend: Busqueda e Historial de Fichajes
 *
 * Pagina de busqueda y consulta del historial de fichajes con
 * selector de mes para navegacion rapida y tabla de resultados.
 *
 * @package FlavorChatIA
 * @subpackage FichajeEmpleados
 *
 * @var string $query            Termino de busqueda actual (rango de fechas o texto)
 * @var array  $resultados       Lista de resultados de fichajes encontrados
 * @var int    $total_resultados Numero total de resultados
 * @var array  $sugerencias      Meses sugeridos para navegacion rapida (ej: "enero 2024")
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['enero 2024', 'febrero 2024', 'marzo 2024', 'abril 2024'];
?>

<div class="flavor-frontend flavor-fichaje-search">
    <!-- Buscador con gradiente azul -->
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center"><?php echo esc_html__('Historial de Fichajes', 'flavor-chat-ia'); ?></h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="<?php echo esc_attr__('Buscar por fecha, mes o rango (ej: enero 2024, 15/01/2024...)', 'flavor-chat-ia'); ?>"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-blue-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-indigo-600 text-white p-3 rounded-lg hover:bg-indigo-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <!-- Selector rapido de meses -->
        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-blue-100 text-sm"><?php echo esc_html__('Meses:', 'flavor-chat-ia'); ?></span>
            <?php foreach ($sugerencias as $sugerencia_mes_fichaje): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_mes_fichaje); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_mes_fichaje); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> resultado<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-indigo-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-indigo-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">&#128337;</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2"><?php echo esc_html__('No se encontraron fichajes', 'flavor-chat-ia'); ?></h3>
        <p class="text-gray-500 mb-6"><?php echo esc_html__('Prueba con otro rango de fechas o un mes diferente', 'flavor-chat-ia'); ?></p>
        <a href="<?php echo esc_url(home_url('/fichaje/')); ?>"
           class="inline-block bg-blue-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-blue-600 transition-colors">
            <?php echo esc_html__('Volver al panel', 'flavor-chat-ia'); ?>
        </a>
    </div>
    <?php else: ?>
    <!-- Tabla de resultados -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="text-left py-4 px-5 text-sm font-semibold text-gray-500"><?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?></th>
                        <th class="text-left py-4 px-5 text-sm font-semibold text-gray-500"><?php echo esc_html__('Entrada', 'flavor-chat-ia'); ?></th>
                        <th class="text-left py-4 px-5 text-sm font-semibold text-gray-500"><?php echo esc_html__('Salida', 'flavor-chat-ia'); ?></th>
                        <th class="text-left py-4 px-5 text-sm font-semibold text-gray-500"><?php echo esc_html__('Horas trabajadas', 'flavor-chat-ia'); ?></th>
                        <th class="text-left py-4 px-5 text-sm font-semibold text-gray-500"><?php echo esc_html__('Estado', 'flavor-chat-ia'); ?></th>
                        <th class="text-right py-4 px-5 text-sm font-semibold text-gray-500"><?php echo esc_html__('Acciones', 'flavor-chat-ia'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($resultados as $resultado_fichaje): ?>
                    <tr class="border-b border-gray-50 hover:bg-blue-50/50 transition-colors">
                        <td class="py-4 px-5">
                            <div>
                                <p class="font-medium text-gray-800"><?php echo esc_html($resultado_fichaje['fecha_formateada'] ?? ''); ?></p>
                                <p class="text-xs text-gray-500"><?php echo esc_html($resultado_fichaje['dia_semana'] ?? ''); ?></p>
                            </div>
                        </td>
                        <td class="py-4 px-5">
                            <span class="font-mono text-gray-800"><?php echo esc_html($resultado_fichaje['hora_entrada'] ?? '--:--'); ?></span>
                        </td>
                        <td class="py-4 px-5">
                            <span class="font-mono text-gray-800"><?php echo esc_html($resultado_fichaje['hora_salida'] ?? '--:--'); ?></span>
                        </td>
                        <td class="py-4 px-5">
                            <span class="font-semibold text-indigo-600"><?php echo esc_html($resultado_fichaje['horas_trabajadas'] ?? '0h 0m'); ?></span>
                        </td>
                        <td class="py-4 px-5">
                            <?php
                            $estado_resultado_fichaje = $resultado_fichaje['estado'] ?? 'pendiente';
                            $colores_estado_resultado = [
                                'validado'  => 'bg-green-100 text-green-700',
                                'pendiente' => 'bg-amber-100 text-amber-700',
                                'rechazado' => 'bg-red-100 text-red-700',
                            ];
                            $clase_estado_resultado = $colores_estado_resultado[$estado_resultado_fichaje] ?? 'bg-gray-100 text-gray-700';
                            ?>
                            <span class="<?php echo esc_attr($clase_estado_resultado); ?> px-3 py-1 rounded-full text-xs font-medium capitalize">
                                <?php echo esc_html($estado_resultado_fichaje); ?>
                            </span>
                        </td>
                        <td class="py-4 px-5 text-right">
                            <a href="<?php echo esc_url($resultado_fichaje['url'] ?? '#'); ?>"
                               class="text-indigo-600 hover:text-indigo-700 font-medium text-sm">
                                <?php echo esc_html__('Ver detalle &rarr;', 'flavor-chat-ia'); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Resumen al pie de la tabla -->
        <?php if ($total_resultados > 0): ?>
        <div class="bg-blue-50 px-5 py-4 flex items-center justify-between">
            <span class="text-sm text-gray-600">
                Mostrando <?php echo esc_html(count($resultados)); ?> de <?php echo esc_html($total_resultados); ?> registros
            </span>
            <?php if (!empty($resultados)): ?>
            <span class="text-sm font-medium text-indigo-600">
                Total horas: <?php echo esc_html(array_sum(array_map(function($registro_resumen) { return floatval($registro_resumen['horas_decimal'] ?? 0); }, $resultados))); ?>h
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_resultados > 20): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors"><?php echo esc_html__('&larr; Anterior', 'flavor-chat-ia'); ?></button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_resultados / 20); ?></span>
            <button class="px-4 py-2 rounded-lg bg-blue-500 text-white hover:bg-blue-600 transition-colors"><?php echo esc_html__('Siguiente &rarr;', 'flavor-chat-ia'); ?></button>
        </nav>
    </div>
    <?php endif; ?>

    <?php endif; ?>
    <?php endif; ?>
</div>
