<?php
/**
 * Template: WooCommerce Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'Tu Tienda Online';
$subtitulo_hero = $subtitulo_hero ?? 'Crea y gestiona tu tienda con la potencia de WooCommerce';
$total_productos = $total_productos ?? 520;
$pedidos_procesados = $pedidos_procesados ?? '3.840';
$total_clientes = $total_clientes ?? 1250;
$url_tienda = $url_tienda ?? '#tienda';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #A855F7) 0%, var(--flavor-secondary, #6366F1) 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Powered by WooCommerce', 'flavor-chat-ia'); ?></span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_tienda); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-purple-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg mb-12">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                <?php echo esc_html__('Ver Tienda', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($total_productos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Productos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($pedidos_procesados); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Pedidos Procesados', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html(number_format_i18n($total_clientes)); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Clientes', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
