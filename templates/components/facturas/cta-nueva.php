<?php
/**
 * Template: Facturas CTA Nueva
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta_nueva = $titulo_cta_nueva ?? 'Crea Facturas Profesionales';
$descripcion_cta_nueva = $descripcion_cta_nueva ?? 'Genera facturas con aspecto profesional, envia a tus clientes y controla los cobros. Todo desde un solo lugar.';
$url_nueva_factura = $url_nueva_factura ?? '/facturas/crear/';
$url_mis_facturas = $url_mis_facturas ?? '/facturas/mis-facturas/';

$ventajas_facturacion = $ventajas_facturacion ?? [
    'Diseno profesional listo para enviar',
    'Calculo automatico de IVA e impuestos',
    'Seguimiento de pagos en tiempo real',
    'Exportacion a PDF con un clic',
];
?>
<section class="flavor-component flavor-section relative overflow-hidden py-16 lg:py-24" style="background: linear-gradient(135deg, var(--flavor-primary, #14B8A6) 0%, var(--flavor-secondary, #059669) 100%);">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="flavor-container relative z-10">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Facturacion simplificada', 'flavor-chat-ia'); ?></span>
            </div>

            <h2 class="text-3xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_cta_nueva); ?>
            </h2>
            <p class="text-lg lg:text-xl text-white/80 mb-10 max-w-2xl mx-auto leading-relaxed">
                <?php echo esc_html($descripcion_cta_nueva); ?>
            </p>

            <!-- Ventajas -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-xl mx-auto mb-10">
                <?php foreach ($ventajas_facturacion as $ventaja_texto) : ?>
                    <div class="flex items-center gap-2 text-left">
                        <svg class="w-5 h-5 text-emerald-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-white/90"><?php echo esc_html($ventaja_texto); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Botones CTA -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="<?php echo esc_url($url_nueva_factura); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white text-teal-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?php echo esc_html__('Nueva Factura', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url($url_mis_facturas); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-white/30 text-white font-semibold text-lg hover:bg-white/10 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <?php echo esc_html__('Mis Facturas', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
    </div>
</section>
