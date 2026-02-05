<?php
/**
 * Template: DEX Solana CTA Conectar Wallet
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_cta = $titulo_cta ?? 'Conecta tu wallet y empieza';
$descripcion_cta = $descripcion_cta ?? 'Conecta tu wallet de Solana para acceder al intercambio descentralizado, pools de liquidez y staking.';
$url_conectar = $url_conectar ?? '/dex-solana/conectar/';

$wallets_soportadas = $wallets_soportadas ?? [
    [
        'nombre'    => 'Phantom',
        'color'     => '#AB9FF2',
        'iniciales' => 'Ph',
    ],
    [
        'nombre'    => 'Solflare',
        'color'     => '#FC8C03',
        'iniciales' => 'Sf',
    ],
    [
        'nombre'    => 'Backpack',
        'color'     => '#E33E3F',
        'iniciales' => 'Bp',
    ],
    [
        'nombre'    => 'Ledger',
        'color'     => '#000000',
        'iniciales' => 'Lg',
    ],
];
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: linear-gradient(135deg, #1E1B4B 0%, #0F172A 100%);">
    <div class="flavor-container">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Badge -->
            <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-violet-500/20 text-violet-300 text-sm font-medium mb-6 border border-violet-500/30">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <?php echo esc_html__('Web3 ready', 'flavor-chat-ia'); ?>
            </span>

            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_cta); ?>
            </h2>
            <p class="text-lg text-gray-400 mb-10 max-w-2xl mx-auto leading-relaxed">
                <?php echo esc_html($descripcion_cta); ?>
            </p>

            <!-- Wallets soportadas -->
            <div class="flex items-center justify-center gap-6 mb-10">
                <?php foreach ($wallets_soportadas as $wallet_item) : ?>
                    <div class="flex flex-col items-center gap-2 group cursor-pointer">
                        <div class="w-16 h-16 rounded-2xl flex items-center justify-center text-white font-bold text-lg border-2 border-gray-700 group-hover:border-violet-500/50 transition-all duration-300 group-hover:scale-110 shadow-lg" style="background: <?php echo esc_attr($wallet_item['color']); ?>;">
                            <?php echo esc_html($wallet_item['iniciales']); ?>
                        </div>
                        <span class="text-gray-400 text-xs group-hover:text-violet-300 transition-colors"><?php echo esc_html($wallet_item['nombre']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Boton principal -->
            <a href="<?php echo esc_url($url_conectar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-violet-500 to-purple-600 text-white font-semibold text-lg hover:from-violet-600 hover:to-purple-700 transition-all shadow-lg hover:shadow-violet-500/25 hover:shadow-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                </svg>
                <?php echo esc_html__('Conectar Wallet', 'flavor-chat-ia'); ?>
            </a>

            <!-- Info de seguridad -->
            <div class="mt-8 flex items-center justify-center gap-6 text-sm text-gray-500">
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span><?php echo esc_html__('Contratos auditados', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span><?php echo esc_html__('Sin custodia', 'flavor-chat-ia'); ?></span>
                </div>
                <div class="flex items-center gap-1">
                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span><?php echo esc_html__('Transacciones rapidas', 'flavor-chat-ia'); ?></span>
                </div>
            </div>
        </div>
    </div>
</section>
