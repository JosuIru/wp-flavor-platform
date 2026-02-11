<?php
/**
 * Template: Facturas Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'Gestion de Facturas';
$subtitulo_hero = $subtitulo_hero ?? 'Crea, envia y gestiona tus facturas facilmente';
$facturas_emitidas = $facturas_emitidas ?? 1280;
$importe_total = $importe_total ?? '45.600';
$total_clientes = $total_clientes ?? 340;
$url_crear_factura = $url_crear_factura ?? '/facturas/crear/';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #14B8A6) 0%, var(--flavor-secondary, #059669) 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Facturacion profesional', 'flavor-chat-ia'); ?></span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_crear_factura); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-teal-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg mb-12">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?php echo esc_html__('Crear Factura', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html(number_format_i18n($facturas_emitidas)); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Facturas Emitidas', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($importe_total); ?>&euro;</div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Importe Total', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($total_clientes); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Clientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
