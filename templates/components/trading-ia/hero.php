<?php
/**
 * Template: Trading IA Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'Trading con IA';
$subtitulo_hero = $subtitulo_hero ?? 'Analisis predictivo y senales de trading impulsados por inteligencia artificial';
$senales_generadas = $senales_generadas ?? '12.450';
$precision_porcentaje = $precision_porcentaje ?? '87.3';
$mercados_analizados = $mercados_analizados ?? 24;
$url_comenzar = $url_comenzar ?? '/trading-ia/comenzar/';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #06B6D4) 0%, var(--flavor-secondary, #0D9488) 100%); min-height: 500px;">
    <!-- Patron futurista -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22none%22/><line x1=%220%22 y1=%2250%22 x2=%22100%22 y2=%2250%22 stroke=%22white%22 stroke-width=%220.5%22/><line x1=%2250%22 y1=%220%22 x2=%2250%22 y2=%22100%22 stroke=%22white%22 stroke-width=%220.5%22/></svg>'); background-size: 100px 100px;"></div>
    </div>
    <!-- Efecto de lineas de grafico -->
    <div class="absolute bottom-0 left-0 right-0 h-32 opacity-20">
        <svg class="w-full h-full" viewBox="0 0 1200 120" preserveAspectRatio="none">
            <path d="M0,80 L100,60 L200,70 L300,40 L400,50 L500,20 L600,35 L700,15 L800,30 L900,10 L1000,25 L1100,5 L1200,20" fill="none" stroke="white" stroke-width="2"/>
            <path d="M0,100 L100,85 L200,95 L300,70 L400,80 L500,55 L600,65 L700,45 L800,60 L900,40 L1000,50 L1100,35 L1200,45" fill="none" stroke="white" stroke-width="1" opacity="0.5"/>
        </svg>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Inteligencia Artificial', 'flavor-chat-ia'); ?></span>
            </div>

            <h1 class="text-4xl lg:text-6xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10 max-w-2xl mx-auto">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_comenzar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-cyan-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg mb-12">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <?php echo esc_html__('Empezar con IA', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-3xl mx-auto">
            <div class="text-center p-5 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl lg:text-4xl font-bold text-white"><?php echo esc_html($senales_generadas); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Senales Generadas', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-5 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl lg:text-4xl font-bold text-white"><?php echo esc_html($precision_porcentaje); ?>%</div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Precision', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-5 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl lg:text-4xl font-bold text-white"><?php echo esc_html($mercados_analizados); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Mercados Analizados', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
