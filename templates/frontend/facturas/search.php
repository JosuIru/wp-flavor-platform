<?php
/**
 * Frontend: Busqueda de Facturas
 *
 * Pagina de busqueda de facturas con barra de busqueda,
 * sugerencias y resultados en grid.
 *
 * Variables esperadas:
 * @var string $query             Termino de busqueda actual
 * @var array  $resultados        Lista de facturas encontradas
 * @var int    $total_resultados  Total de resultados
 * @var array  $sugerencias       Sugerencias de busqueda
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$query = $query ?? '';
$resultados = $resultados ?? [];
$total_resultados = $total_resultados ?? 0;
$sugerencias = $sugerencias ?? ['cliente X', 'factura 2024', 'pendientes enero', 'vencidas'];
?>

<div class="flavor-frontend flavor-facturas-search">
    <!-- Buscador -->
    <div class="bg-gradient-to-r from-emerald-500 to-green-600 rounded-2xl p-8 mb-8 shadow-lg">
        <h2 class="text-2xl font-bold text-white mb-4 text-center">Buscar facturas</h2>
        <form action="" method="get" class="max-w-2xl mx-auto">
            <div class="relative">
                <input type="text" name="q" value="<?php echo esc_attr($query); ?>"
                       placeholder="Buscar por numero, cliente, importe... (ej: FAC-2024, Empresa S.L.)"
                       class="w-full px-6 py-4 pr-14 rounded-xl text-lg border-0 shadow-lg focus:ring-4 focus:ring-emerald-300">
                <button type="submit" class="absolute right-2 top-1/2 -translate-y-1/2 bg-green-600 text-white p-3 rounded-lg hover:bg-green-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </form>

        <?php if (!empty($sugerencias) && empty($query)): ?>
        <div class="flex flex-wrap justify-center gap-2 mt-4">
            <span class="text-emerald-100 text-sm">Populares:</span>
            <?php foreach ($sugerencias as $sugerencia_factura): ?>
            <a href="?q=<?php echo esc_attr($sugerencia_factura); ?>" class="bg-white/20 text-white px-3 py-1 rounded-full text-sm hover:bg-white/30 transition-colors">
                <?php echo esc_html($sugerencia_factura); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($query)): ?>
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800">
            <?php if ($total_resultados > 0): ?>
                <?php echo esc_html($total_resultados); ?> factura<?php echo $total_resultados !== 1 ? 's' : ''; ?>
                para "<span class="text-emerald-600"><?php echo esc_html($query); ?></span>"
            <?php else: ?>
                Sin resultados para "<span class="text-emerald-600"><?php echo esc_html($query); ?></span>"
            <?php endif; ?>
        </h3>
    </div>

    <?php if (empty($resultados)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-2xl">
        <div class="text-6xl mb-4">&#128196;</div>
        <h3 class="text-xl font-semibold text-gray-700 mb-2">No encontramos facturas</h3>
        <p class="text-gray-500 mb-6">Intenta con otro termino de busqueda o crea una nueva factura</p>
        <a href="<?php echo esc_url(home_url('/facturas/crear/')); ?>"
           class="inline-block bg-emerald-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-emerald-600 transition-colors">
            Nueva Factura
        </a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($resultados as $resultado_factura): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Cabecera: numero y estado -->
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-800 group-hover:text-emerald-600 transition-colors">
                        <a href="<?php echo esc_url($resultado_factura['url'] ?? '#'); ?>">
                            <?php echo esc_html($resultado_factura['numero_factura'] ?? ''); ?>
                        </a>
                    </h3>
                    <?php
                    $estado_resultado_factura = $resultado_factura['estado'] ?? 'borrador';
                    $clases_estado_resultado = [
                        'pagada'    => 'bg-green-100 text-green-700',
                        'pendiente' => 'bg-amber-100 text-amber-700',
                        'vencida'   => 'bg-red-100 text-red-700',
                        'borrador'  => 'bg-gray-100 text-gray-600',
                    ];
                    $clase_estado_resultado = $clases_estado_resultado[$estado_resultado_factura] ?? 'bg-gray-100 text-gray-600';
                    ?>
                    <span class="<?php echo esc_attr($clase_estado_resultado); ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html(ucfirst($estado_resultado_factura)); ?>
                    </span>
                </div>

                <!-- Cliente -->
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-xs font-medium">
                        <?php echo esc_html(mb_substr($resultado_factura['cliente_nombre'] ?? 'C', 0, 1)); ?>
                    </div>
                    <span class="text-sm text-gray-600"><?php echo esc_html($resultado_factura['cliente_nombre'] ?? ''); ?></span>
                </div>

                <!-- Metadatos -->
                <div class="flex flex-wrap gap-2 mb-3 text-xs text-gray-500">
                    <span class="bg-gray-100 px-2 py-1 rounded-full">&#128197; <?php echo esc_html($resultado_factura['fecha_emision'] ?? ''); ?></span>
                    <?php if (!empty($resultado_factura['fecha_vencimiento'])): ?>
                    <span class="bg-gray-100 px-2 py-1 rounded-full">&#9203; Vence: <?php echo esc_html($resultado_factura['fecha_vencimiento']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Importe y enlace -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="font-bold text-emerald-600">
                        <?php echo esc_html($resultado_factura['importe_total'] ?? '0') . ' &euro;'; ?>
                    </span>
                    <a href="<?php echo esc_url($resultado_factura['url'] ?? '#'); ?>"
                       class="text-emerald-600 hover:text-emerald-700 font-medium text-sm">
                        Ver factura &rarr;
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
