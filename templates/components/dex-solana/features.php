<?php
/**
 * Template: DEX Solana Features
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_features = $titulo_features ?? 'Funcionalidades DeFi';

$funcionalidades_dex = $funcionalidades_dex ?? [
    [
        'titulo'      => 'Swap instantaneo',
        'descripcion' => 'Intercambia tokens en segundos con las tarifas mas bajas de la red Solana. Sin esperas ni confirmaciones lentas.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>',
        'color'       => '#8B5CF6',
    ],
    [
        'titulo'      => 'Pools de liquidez',
        'descripcion' => 'Proporciona liquidez y gana comisiones por cada swap. Rendimientos competitivos en pools verificados.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
        'color'       => '#7C3AED',
    ],
    [
        'titulo'      => 'Staking',
        'descripcion' => 'Bloquea tus tokens y recibe recompensas pasivas. Multiples opciones de staking con diferentes plazos.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => '#A78BFA',
    ],
    [
        'titulo'      => 'Gobernanza',
        'descripcion' => 'Participa en las decisiones del protocolo con tu voto. Tu opinion cuenta en el futuro de la plataforma.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'color'       => '#C084FC',
    ],
    [
        'titulo'      => 'Sin intermediarios',
        'descripcion' => 'Opera directamente con smart contracts. Sin custodia centralizada, tu controlas tus fondos en todo momento.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>',
        'color'       => '#6D28D9',
    ],
    [
        'titulo'      => 'Auditoria de contratos',
        'descripcion' => 'Todos los smart contracts han sido auditados por empresas de seguridad reconocidas en el ecosistema.',
        'icono'       => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
        'color'       => '#DDD6FE',
    ],
];
?>
<section class="flavor-component flavor-section py-12 lg:py-20 bg-gray-950">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-3"><?php echo esc_html($titulo_features); ?></h2>
            <p class="text-gray-400 text-lg max-w-2xl mx-auto"><?php echo esc_html__('Todo el poder de las finanzas descentralizadas en la blockchain mas rapida', 'flavor-chat-ia'); ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-5xl mx-auto">
            <?php foreach ($funcionalidades_dex as $funcionalidad_item) : ?>
                <div class="flavor-card bg-gray-900 rounded-2xl p-6 border border-gray-800 hover:border-violet-500/30 transition-all duration-300 group">
                    <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-transform duration-300 group-hover:scale-110" style="background: <?php echo esc_attr($funcionalidad_item['color']); ?>20;">
                        <svg class="w-6 h-6" style="color: <?php echo esc_attr($funcionalidad_item['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <?php echo $funcionalidad_item['icono']; ?>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-white mb-2"><?php echo esc_html($funcionalidad_item['titulo']); ?></h3>
                    <p class="text-sm text-gray-400 leading-relaxed"><?php echo esc_html($funcionalidad_item['descripcion']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
