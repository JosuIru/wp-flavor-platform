<?php
/**
 * Frontend: Archive de Facturas
 *
 * Listado principal de facturas con estadisticas, filtros por estado
 * y grid de tarjetas de factura.
 *
 * Variables esperadas:
 * @var array $facturas           Lista de facturas a mostrar
 * @var int   $total_facturas     Total de facturas
 * @var array $estadisticas       Estadisticas generales (total, pendientes, facturado_mes, tasa_cobro)
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$facturas = $facturas ?? [];
$total_facturas = $total_facturas ?? 0;
$estadisticas = $estadisticas ?? [];
?>

<div class="flavor-frontend flavor-facturas-archive">
    <!-- Header con gradiente verde/esmeralda -->
    <div class="bg-gradient-to-r from-emerald-500 to-green-600 text-white rounded-2xl p-8 mb-8 shadow-lg">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-3xl font-bold mb-2">Facturas</h1>
                <p class="text-emerald-100">Gestiona y controla todas tus facturas en un solo lugar</p>
            </div>
            <div class="flex items-center gap-4">
                <span class="bg-white/20 backdrop-blur px-4 py-2 rounded-full text-sm">
                    <?php echo esc_html($total_facturas); ?> facturas registradas
                </span>
                <a href="<?php echo esc_url(home_url('/facturas/crear/')); ?>"
                   class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-emerald-50 transition-all shadow-md">
                    Nueva Factura
                </a>
            </div>
        </div>
    </div>

    <!-- Estadisticas -->
    <?php if (!empty($estadisticas)): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#128196;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['total_facturas'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Total facturas</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#9203;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['pendientes_pago'] ?? 0); ?></p>
            <p class="text-sm text-gray-500">Pendientes de pago</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#128176;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['facturado_mes'] ?? '0 &euro;'); ?></p>
            <p class="text-sm text-gray-500">Facturado este mes</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 text-center">
            <div class="text-3xl mb-2">&#128200;</div>
            <p class="text-2xl font-bold text-gray-800"><?php echo esc_html($estadisticas['tasa_cobro'] ?? '0%'); ?></p>
            <p class="text-sm text-gray-500">Tasa de cobro</p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="bg-emerald-50 rounded-2xl p-6 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-4">Como funciona</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="text-center">
                <div class="w-16 h-16 bg-emerald-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">&#128221;</div>
                <h3 class="font-semibold text-gray-800 mb-1">Crea</h3>
                <p class="text-sm text-gray-600">Genera facturas profesionales con todos los datos fiscales</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-emerald-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">&#128233;</div>
                <h3 class="font-semibold text-gray-800 mb-1">Envia</h3>
                <p class="text-sm text-gray-600">Envia la factura al cliente directamente por email</p>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-emerald-500 text-white rounded-full flex items-center justify-center mx-auto mb-3 text-2xl">&#9989;</div>
                <h3 class="font-semibold text-gray-800 mb-1">Cobra</h3>
                <p class="text-sm text-gray-600">Controla el estado de pago y gestiona los cobros</p>
            </div>
        </div>
    </div>

    <!-- Filtros por estado -->
    <div class="flex flex-wrap gap-2 mb-6">
        <button class="px-4 py-2 rounded-full bg-emerald-100 text-emerald-700 font-medium hover:bg-emerald-200 transition-colors filter-active" data-estado="todas">
            Todas
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-estado="pendiente">
            Pendientes
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-estado="pagada">
            Pagadas
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-estado="vencida">
            Vencidas
        </button>
        <button class="px-4 py-2 rounded-full bg-gray-100 text-gray-600 font-medium hover:bg-gray-200 transition-colors" data-estado="borrador">
            Borradores
        </button>
    </div>

    <!-- Grid de facturas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($facturas)): ?>
        <div class="col-span-full text-center py-16 bg-gray-50 rounded-2xl">
            <div class="text-6xl mb-4">&#128196;</div>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">No hay facturas registradas</h3>
            <p class="text-gray-500 mb-6">Empieza a facturar creando tu primera factura</p>
            <a href="<?php echo esc_url(home_url('/facturas/crear/')); ?>"
               class="inline-block bg-emerald-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-emerald-600 transition-colors">
                Nueva Factura
            </a>
        </div>
        <?php else: ?>
        <?php foreach ($facturas as $factura_item): ?>
        <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group">
            <div class="p-5">
                <!-- Cabecera: numero y estado -->
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-lg font-semibold text-gray-800 group-hover:text-emerald-600 transition-colors">
                        <a href="<?php echo esc_url($factura_item['url'] ?? '#'); ?>">
                            <?php echo esc_html($factura_item['numero_factura'] ?? ''); ?>
                        </a>
                    </h3>
                    <?php
                    $estado_factura = $factura_item['estado'] ?? 'borrador';
                    $clases_estado_factura = [
                        'pagada'    => 'bg-green-100 text-green-700',
                        'pendiente' => 'bg-amber-100 text-amber-700',
                        'vencida'   => 'bg-red-100 text-red-700',
                        'borrador'  => 'bg-gray-100 text-gray-600',
                    ];
                    $clase_estado_actual = $clases_estado_factura[$estado_factura] ?? 'bg-gray-100 text-gray-600';
                    ?>
                    <span class="<?php echo esc_attr($clase_estado_actual); ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html(ucfirst($estado_factura)); ?>
                    </span>
                </div>

                <!-- Cliente -->
                <div class="flex items-center gap-2 mb-3">
                    <div class="w-7 h-7 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-xs font-medium">
                        <?php echo esc_html(mb_substr($factura_item['cliente_nombre'] ?? 'C', 0, 1)); ?>
                    </div>
                    <span class="text-sm text-gray-600"><?php echo esc_html($factura_item['cliente_nombre'] ?? 'Cliente'); ?></span>
                </div>

                <!-- Metadatos -->
                <div class="flex flex-wrap gap-2 mb-3 text-xs text-gray-500">
                    <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                        &#128197; <?php echo esc_html($factura_item['fecha_emision'] ?? ''); ?>
                    </span>
                    <?php if (!empty($factura_item['fecha_vencimiento'])): ?>
                    <span class="bg-gray-100 px-2 py-1 rounded-full flex items-center gap-1">
                        &#9203; Vence: <?php echo esc_html($factura_item['fecha_vencimiento']); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Importe y enlace -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="text-lg font-bold text-emerald-600">
                        <?php echo esc_html($factura_item['importe_total'] ?? '0') . ' &euro;'; ?>
                    </span>
                    <a href="<?php echo esc_url($factura_item['url'] ?? '#'); ?>"
                       class="bg-emerald-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-emerald-600 transition-colors">
                        Ver factura
                    </a>
                </div>
            </div>
        </article>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Paginacion -->
    <?php if ($total_facturas > 12): ?>
    <div class="flex justify-center mt-8">
        <nav class="flex items-center gap-2">
            <button class="px-4 py-2 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition-colors">&larr; Anterior</button>
            <span class="px-4 py-2 text-gray-600">Pagina 1 de <?php echo ceil($total_facturas / 12); ?></span>
            <button class="px-4 py-2 rounded-lg bg-emerald-500 text-white hover:bg-emerald-600 transition-colors">Siguiente &rarr;</button>
        </nav>
    </div>
    <?php endif; ?>
</div>
