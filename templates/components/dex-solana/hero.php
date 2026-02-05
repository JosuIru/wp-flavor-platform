<?php
/**
 * Template: DEX Solana Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'DEX en Solana';
$subtitulo_hero = $subtitulo_hero ?? 'Intercambia tokens de forma descentralizada en la blockchain de Solana';
$total_tvl = $total_tvl ?? '$2.4M';
$tokens_listados = $tokens_listados ?? 156;
$volumen_24h = $volumen_24h ?? '$890K';
$url_conectar = $url_conectar ?? '/dex-solana/conectar/';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #8B5CF6) 0%, var(--flavor-secondary, #7C3AED) 100%); min-height: 500px;">
    <!-- Patron Web3 -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 60 60%22><rect width=%2260%22 height=%2260%22 fill=%22none%22/><polygon points=%2230,5 55,20 55,50 30,65 5,50 5,20%22 fill=%22none%22 stroke=%22white%22 stroke-width=%220.5%22/></svg>'); background-size: 60px 60px;"></div>
    </div>
    <!-- Particulas decorativas -->
    <div class="absolute top-20 left-10 w-2 h-2 rounded-full bg-violet-300/30 animate-pulse"></div>
    <div class="absolute top-40 right-20 w-3 h-3 rounded-full bg-purple-300/20 animate-pulse"></div>
    <div class="absolute bottom-32 left-1/4 w-2 h-2 rounded-full bg-violet-400/25 animate-pulse"></div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Powered by Solana', 'flavor-chat-ia'); ?></span>
            </div>

            <h1 class="text-4xl lg:text-6xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_conectar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-violet-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg mb-12">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <?php echo esc_html__('Conectar Wallet', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-3xl mx-auto">
            <div class="text-center p-5 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl lg:text-4xl font-bold text-white"><?php echo esc_html($total_tvl); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('TVL', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-5 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl lg:text-4xl font-bold text-white"><?php echo esc_html($tokens_listados); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Tokens Listados', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-5 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl lg:text-4xl font-bold text-white"><?php echo esc_html($volumen_24h); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Volumen 24h', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
