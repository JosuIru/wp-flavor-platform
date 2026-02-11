<?php
/**
 * Frontend: Single Factura
 *
 * Vista detallada de una factura individual con informacion del cliente,
 * lineas de factura, totales y acciones de cobro.
 *
 * Variables esperadas:
 * @var array $factura               Datos de la factura
 * @var array $cliente                Datos del cliente asociado
 * @var array $lineas_factura         Lineas/conceptos de la factura
 * @var array $facturas_relacionadas  Otras facturas del mismo cliente
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$factura = $factura ?? [];
$cliente = $cliente ?? [];
$lineas_factura = $lineas_factura ?? [];
$facturas_relacionadas = $facturas_relacionadas ?? [];
?>

<div class="flavor-frontend flavor-facturas-single">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
        <a href="<?php echo esc_url(home_url('/facturas/')); ?>" class="hover:text-emerald-600 transition-colors"><?php echo esc_html__('Facturas', 'flavor-chat-ia'); ?></a>
        <span><?php echo esc_html__('&rsaquo;', 'flavor-chat-ia'); ?></span>
        <span class="text-gray-700"><?php echo esc_html($factura['numero_factura'] ?? 'Factura'); ?></span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Contenido principal -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Cabecera de la factura -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-br from-emerald-400 to-green-600 p-6 text-white">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div>
                            <p class="text-emerald-100 text-sm mb-1"><?php echo esc_html__('Factura', 'flavor-chat-ia'); ?></p>
                            <h1 class="text-2xl font-bold"><?php echo esc_html($factura['numero_factura'] ?? ''); ?></h1>
                        </div>
                        <?php
                        $estado_factura_actual = $factura['estado'] ?? 'borrador';
                        $clases_badge_estado = [
                            'pagada'    => 'bg-white/30 text-white',
                            'pendiente' => 'bg-amber-400 text-amber-900',
                            'vencida'   => 'bg-red-400 text-red-900',
                            'borrador'  => 'bg-white/20 text-white',
                        ];
                        $clase_badge_actual = $clases_badge_estado[$estado_factura_actual] ?? 'bg-white/20 text-white';
                        ?>
                        <span class="<?php echo esc_attr($clase_badge_actual); ?> px-4 py-2 rounded-full font-medium shadow">
                            <?php echo esc_html(ucfirst($estado_factura_actual)); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Informacion de la factura -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <!-- Info rapida -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-emerald-50 rounded-xl">
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Fecha emision', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($factura['fecha_emision'] ?? ''); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Fecha vencimiento', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($factura['fecha_vencimiento'] ?? ''); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Metodo de pago', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($factura['metodo_pago'] ?? 'Transferencia'); ?></p>
                    </div>
                    <div class="text-center">
                        <p class="text-sm text-gray-500"><?php echo esc_html__('Moneda', 'flavor-chat-ia'); ?></p>
                        <p class="font-medium text-gray-800"><?php echo esc_html($factura['moneda'] ?? 'EUR'); ?></p>
                    </div>
                </div>

                <!-- Datos del cliente en factura -->
                <div class="border-b border-gray-100 pb-6 mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-3"><?php echo esc_html__('Datos del cliente', 'flavor-chat-ia'); ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Nombre / Razon social', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($cliente['nombre'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('NIF / CIF', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($cliente['nif'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Direccion', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($cliente['direccion'] ?? ''); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500"><?php echo esc_html__('Email', 'flavor-chat-ia'); ?></p>
                            <p class="font-medium text-gray-800"><?php echo esc_html($cliente['email'] ?? ''); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Lineas de factura -->
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Conceptos', 'flavor-chat-ia'); ?></h2>
                    <?php if (!empty($lineas_factura)): ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200">
                                    <th class="text-left py-3 px-2 text-gray-500 font-medium"><?php echo esc_html__('Concepto', 'flavor-chat-ia'); ?></th>
                                    <th class="text-right py-3 px-2 text-gray-500 font-medium"><?php echo esc_html__('Cantidad', 'flavor-chat-ia'); ?></th>
                                    <th class="text-right py-3 px-2 text-gray-500 font-medium"><?php echo esc_html__('Precio unitario', 'flavor-chat-ia'); ?></th>
                                    <th class="text-right py-3 px-2 text-gray-500 font-medium"><?php echo esc_html__('Importe', 'flavor-chat-ia'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lineas_factura as $linea_concepto): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-2 text-gray-800">
                                        <p class="font-medium"><?php echo esc_html($linea_concepto['concepto'] ?? ''); ?></p>
                                        <?php if (!empty($linea_concepto['descripcion'])): ?>
                                        <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($linea_concepto['descripcion']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-2 text-right text-gray-700"><?php echo esc_html($linea_concepto['cantidad'] ?? 1); ?></td>
                                    <td class="py-3 px-2 text-right text-gray-700"><?php echo esc_html($linea_concepto['precio_unitario'] ?? '0') . ' &euro;'; ?></td>
                                    <td class="py-3 px-2 text-right text-gray-800 font-medium"><?php echo esc_html($linea_concepto['importe'] ?? '0') . ' &euro;'; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 text-sm"><?php echo esc_html__('No hay conceptos registrados en esta factura.', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Totales -->
                <div class="bg-gray-50 rounded-xl p-4">
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500"><?php echo esc_html__('Subtotal', 'flavor-chat-ia'); ?></span>
                            <span class="text-gray-800 font-medium"><?php echo esc_html($factura['subtotal'] ?? '0') . ' &euro;'; ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">IVA (<?php echo esc_html($factura['porcentaje_iva'] ?? '21'); ?>%)</span>
                            <span class="text-gray-800 font-medium"><?php echo esc_html($factura['importe_iva'] ?? '0') . ' &euro;'; ?></span>
                        </div>
                        <?php if (!empty($factura['retencion'])): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Retencion IRPF (<?php echo esc_html($factura['porcentaje_retencion'] ?? '15'); ?>%)</span>
                            <span class="text-red-600 font-medium">-<?php echo esc_html($factura['importe_retencion'] ?? '0') . ' &euro;'; ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex justify-between pt-3 border-t border-gray-200">
                            <span class="text-lg font-bold text-gray-800"><?php echo esc_html__('Total', 'flavor-chat-ia'); ?></span>
                            <span class="text-lg font-bold text-emerald-600"><?php echo esc_html($factura['importe_total'] ?? '0') . ' &euro;'; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notas de la factura -->
            <?php if (!empty($factura['notas'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-3"><?php echo esc_html__('Notas', 'flavor-chat-ia'); ?></h2>
                <div class="prose prose-emerald max-w-none text-sm text-gray-600">
                    <?php echo wp_kses_post($factura['notas']); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Historial de actividad -->
            <?php if (!empty($factura['historial'])): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4"><?php echo esc_html__('Historial de actividad', 'flavor-chat-ia'); ?></h2>
                <div class="space-y-4">
                    <?php foreach ($factura['historial'] as $entrada_historial): ?>
                    <div class="flex items-start gap-3 border-b border-gray-100 pb-3 last:border-0">
                        <div class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 text-sm flex-shrink-0">
                            <?php echo esc_html($entrada_historial['icono'] ?? '&#9679;'); ?>
                        </div>
                        <div>
                            <p class="text-sm text-gray-800"><?php echo esc_html($entrada_historial['descripcion'] ?? ''); ?></p>
                            <p class="text-xs text-gray-400 mt-1"><?php echo esc_html($entrada_historial['fecha'] ?? ''); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- CTA Estado y pago -->
            <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl p-6 text-white">
                <div class="text-center mb-4">
                    <p class="text-3xl font-bold">
                        <?php echo esc_html($factura['importe_total'] ?? '0') . ' &euro;'; ?>
                    </p>
                    <p class="text-emerald-100 text-sm mt-1">
                        <?php echo esc_html(ucfirst($estado_factura_actual)); ?>
                        <?php if ($estado_factura_actual === 'pendiente' && !empty($factura['fecha_vencimiento'])): ?>
                        &mdash; Vence el <?php echo esc_html($factura['fecha_vencimiento']); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($estado_factura_actual === 'pendiente' || $estado_factura_actual === 'vencida'): ?>
                <button class="w-full bg-white text-green-600 py-3 px-4 rounded-xl font-semibold hover:bg-emerald-50 transition-colors"
                        onclick="flavorFacturas.registrarPago(<?php echo esc_attr($factura['id'] ?? 0); ?>)">
                    <?php echo esc_html__('Registrar pago', 'flavor-chat-ia'); ?>
                </button>
                <?php elseif ($estado_factura_actual === 'borrador'): ?>
                <button class="w-full bg-white text-green-600 py-3 px-4 rounded-xl font-semibold hover:bg-emerald-50 transition-colors"
                        onclick="flavorFacturas.enviarFactura(<?php echo esc_attr($factura['id'] ?? 0); ?>)">
                    <?php echo esc_html__('Enviar factura', 'flavor-chat-ia'); ?>
                </button>
                <?php elseif ($estado_factura_actual === 'pagada'): ?>
                <div class="bg-white/20 backdrop-blur rounded-xl py-3 px-4 text-center font-medium">
                    <?php echo esc_html__('Factura cobrada', 'flavor-chat-ia'); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Resumen del cliente -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-700 text-3xl font-bold mx-auto mb-4">
                    <?php echo esc_html(mb_substr($cliente['nombre'] ?? 'C', 0, 1)); ?>
                </div>
                <p class="text-xs text-gray-500 mb-1"><?php echo esc_html__('Cliente', 'flavor-chat-ia'); ?></p>
                <h3 class="text-lg font-semibold text-gray-800"><?php echo esc_html($cliente['nombre'] ?? 'Cliente'); ?></h3>

                <?php if (!empty($cliente['empresa'])): ?>
                <p class="text-sm text-gray-600 mt-1"><?php echo esc_html($cliente['empresa']); ?></p>
                <?php endif; ?>

                <?php if (!empty($cliente['email'])): ?>
                <p class="text-sm text-gray-500 mt-2"><?php echo esc_html($cliente['email']); ?></p>
                <?php endif; ?>

                <div class="grid grid-cols-3 gap-2 mt-4 mb-4 text-center">
                    <div>
                        <p class="text-xl font-bold text-emerald-600"><?php echo esc_html($cliente['total_facturas'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Facturas', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-gray-800"><?php echo esc_html($cliente['facturas_pagadas'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Pagadas', 'flavor-chat-ia'); ?></p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-amber-500"><?php echo esc_html($cliente['facturas_pendientes'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html__('Pendientes', 'flavor-chat-ia'); ?></p>
                    </div>
                </div>

                <?php if (!empty($cliente['verificado'])): ?>
                <span class="inline-block bg-green-100 text-green-700 text-xs px-3 py-1 rounded-full">
                    <?php echo esc_html__('&#10003; Cliente verificado', 'flavor-chat-ia'); ?>
                </span>
                <?php endif; ?>
            </div>

            <!-- Facturas relacionadas -->
            <?php if (!empty($facturas_relacionadas)): ?>
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-800 mb-4"><?php echo esc_html__('Otras facturas del cliente', 'flavor-chat-ia'); ?></h3>
                <div class="space-y-3">
                    <?php foreach ($facturas_relacionadas as $factura_relacionada): ?>
                    <a href="<?php echo esc_url($factura_relacionada['url'] ?? '#'); ?>" class="flex gap-3 p-3 rounded-xl hover:bg-gray-50 transition-colors">
                        <div class="w-14 h-14 rounded-lg bg-emerald-50 flex-shrink-0 flex items-center justify-center">
                            <span class="text-emerald-600 text-xs font-bold"><?php echo esc_html($factura_relacionada['numero_factura'] ?? ''); ?></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-800 text-sm truncate"><?php echo esc_html($factura_relacionada['cliente_nombre'] ?? ''); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($factura_relacionada['fecha_emision'] ?? ''); ?></p>
                            <p class="text-xs text-emerald-600 font-medium">
                                <?php echo esc_html($factura_relacionada['importe_total'] ?? '0') . ' &euro;'; ?>
                            </p>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
