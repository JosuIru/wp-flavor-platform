<?php
/**
 * Template: Facturas Lista
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_lista = $titulo_lista ?? 'Facturas Recientes';
$url_crear_factura = $url_crear_factura ?? '/facturas/crear/';
$url_buscar_factura = $url_buscar_factura ?? '/facturas/buscar/';

$listado_facturas = $listado_facturas ?? [
    [
        'numero'  => 'FAC-2024-0056',
        'cliente' => 'Empresa Soluciones S.L.',
        'fecha'   => '28/01/2025',
        'importe' => '2.450,00',
        'estado'  => 'pagada',
    ],
    [
        'numero'  => 'FAC-2024-0055',
        'cliente' => 'Consultoria Digital S.A.',
        'fecha'   => '22/01/2025',
        'importe' => '1.800,00',
        'estado'  => 'pendiente',
    ],
    [
        'numero'  => 'FAC-2024-0054',
        'cliente' => 'Marketing Pro S.L.',
        'fecha'   => '15/01/2025',
        'importe' => '3.200,00',
        'estado'  => 'pagada',
    ],
    [
        'numero'  => 'FAC-2024-0053',
        'cliente' => 'Servicios Integrales S.A.',
        'fecha'   => '05/01/2025',
        'importe' => '950,00',
        'estado'  => 'vencida',
    ],
    [
        'numero'  => 'FAC-2024-0052',
        'cliente' => 'Diseno Creativo S.L.',
        'fecha'   => '28/12/2024',
        'importe' => '1.575,00',
        'estado'  => 'pagada',
    ],
];

$mapa_estilos_estado = [
    'pagada'    => 'background: #D1FAE5; color: #065F46;',
    'pendiente' => 'background: #FEF3C7; color: #92400E;',
    'vencida'   => 'background: #FEE2E2; color: #991B1B;',
];

$mapa_etiquetas_estado = [
    'pagada'    => __('Pagada', 'flavor-chat-ia'),
    'pendiente' => __('Pendiente', 'flavor-chat-ia'),
    'vencida'   => __('Vencida', 'flavor-chat-ia'),
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-white">
    <div class="flavor-container">
        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
                <div>
                    <h2 class="text-3xl lg:text-4xl font-bold text-gray-800 mb-1">
                        <?php echo esc_html($titulo_lista); ?>
                    </h2>
                    <p class="text-gray-500 text-sm">
                        <?php echo esc_html__('Listado de tus ultimas facturas emitidas', 'flavor-chat-ia'); ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="<?php echo esc_url($url_buscar_factura); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl border-2 border-teal-300 text-teal-600 font-semibold text-sm hover:bg-teal-50 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                    </a>
                    <a href="<?php echo esc_url($url_crear_factura); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-emerald-600 text-white font-semibold text-sm hover:from-teal-600 hover:to-emerald-700 transition-all shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?php echo esc_html__('Nueva Factura', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </div>

            <!-- Tabla Desktop -->
            <div class="hidden md:block bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100" style="background: #F9FAFB;">
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html__('Numero', 'flavor-chat-ia'); ?>
                            </th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html__('Cliente', 'flavor-chat-ia'); ?>
                            </th>
                            <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html__('Fecha', 'flavor-chat-ia'); ?>
                            </th>
                            <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html__('Importe', 'flavor-chat-ia'); ?>
                            </th>
                            <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html__('Estado', 'flavor-chat-ia'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($listado_facturas as $factura_item) : ?>
                            <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-teal-600"><?php echo esc_html($factura_item['numero']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-800"><?php echo esc_html($factura_item['cliente']); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-500"><?php echo esc_html($factura_item['fecha']); ?></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-sm font-semibold text-gray-800"><?php echo esc_html($factura_item['importe']); ?>&euro;</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                          style="<?php echo esc_attr($mapa_estilos_estado[$factura_item['estado']] ?? ''); ?>">
                                        <?php echo esc_html($mapa_etiquetas_estado[$factura_item['estado']] ?? $factura_item['estado']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cards Mobile -->
            <div class="md:hidden space-y-4">
                <?php foreach ($listado_facturas as $factura_item) : ?>
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-semibold text-teal-600"><?php echo esc_html($factura_item['numero']); ?></span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                  style="<?php echo esc_attr($mapa_estilos_estado[$factura_item['estado']] ?? ''); ?>">
                                <?php echo esc_html($mapa_etiquetas_estado[$factura_item['estado']] ?? $factura_item['estado']); ?>
                            </span>
                        </div>
                        <p class="text-sm font-medium text-gray-800 mb-2"><?php echo esc_html($factura_item['cliente']); ?></p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500"><?php echo esc_html($factura_item['fecha']); ?></span>
                            <span class="font-semibold text-gray-800"><?php echo esc_html($factura_item['importe']); ?>&euro;</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
