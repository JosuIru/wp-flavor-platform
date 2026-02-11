<?php
/**
 * Template: Chat Grupos Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$grupos_activos = $grupos_activos ?? 42;
$total_miembros = $total_miembros ?? 1250;
$mensajes_dia = $mensajes_dia ?? 830;
$titulo_hero = $titulo_hero ?? 'Grupos de Chat';
$subtitulo_hero = $subtitulo_hero ?? 'Conecta con personas que comparten tus intereses';
$url_crear_grupo = $url_crear_grupo ?? '#crear-grupo';

$avatares_ejemplo = [
    ['nombre' => 'Runners Madrid', 'color' => '#EC4899', 'iniciales' => 'RM'],
    ['nombre' => 'Foodies BCN', 'color' => '#D946EF', 'iniciales' => 'FB'],
    ['nombre' => 'Tech Talks', 'color' => '#A855F7', 'iniciales' => 'TT'],
];
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #EC4899) 0%, var(--flavor-secondary, #C026D3) 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', 'flavor-chat-ia'); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge superior -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html($grupos_activos); ?> grupos activos</span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-8">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- Avatares de ejemplo -->
            <div class="flex items-center justify-center gap-4 mb-8">
                <?php foreach ($avatares_ejemplo as $avatar_grupo) : ?>
                    <div class="flex flex-col items-center gap-2">
                        <div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-lg border-2 border-white/30" style="background: <?php echo esc_attr($avatar_grupo['color']); ?>;">
                            <?php echo esc_html($avatar_grupo['iniciales']); ?>
                        </div>
                        <span class="text-white/70 text-xs"><?php echo esc_html($avatar_grupo['nombre']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_crear_grupo); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-pink-600 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?php echo esc_html__('Crear un Grupo', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($grupos_activos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Grupos Activos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html(number_format_i18n($total_miembros)); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Miembros', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($mensajes_dia); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Mensajes/Dia', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
