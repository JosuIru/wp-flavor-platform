<?php
/**
 * Template: Hero Avisos Municipales
 *
 * Seccion hero para la landing de avisos municipales.
 * Muestra titulo, badge de avisos urgentes, estadisticas y botones de accion.
 *
 * @var string $titulo_hero
 * @var string $subtitulo_hero
 * @var int    $avisos_activos
 * @var int    $avisos_urgentes
 * @var int    $avisos_leidos
 * @var string $url_ver_avisos
 * @var string $url_suscribirse
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero      = $titulo_hero ?? 'Avisos Municipales';
$subtitulo_hero   = $subtitulo_hero ?? 'Canal oficial de comunicacion del ayuntamiento. Mantente informado de todo lo que ocurre en tu municipio';
$avisos_activos   = $avisos_activos ?? 34;
$avisos_urgentes  = $avisos_urgentes ?? 3;
$avisos_leidos    = $avisos_leidos ?? 1280;
$url_ver_avisos   = $url_ver_avisos ?? '/avisos-municipales/';
$url_suscribirse  = $url_suscribirse ?? '/avisos-municipales/suscribirse/';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, #EF4444 0%, #E11D48 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg>'); background-size: 80px 80px;"></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge de urgentes -->
            <?php if ($avisos_urgentes > 0) : ?>
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.2); backdrop-filter: blur(10px);">
                    <span class="w-2.5 h-2.5 bg-yellow-400 rounded-full animate-pulse"></span>
                    <span class="text-white text-sm font-medium">
                        <?php echo esc_html($avisos_urgentes); ?> <?php echo esc_html__('avisos urgentes', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Badge oficial -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6 ml-2" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('Canal Oficial', 'flavor-chat-ia'); ?></span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- Barra de busqueda -->
            <div class="max-w-2xl mx-auto mb-10">
                <form class="flex flex-col sm:flex-row gap-3" method="get">
                    <div class="flex-1 relative">
                        <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" name="buscar_aviso" placeholder="<?php echo esc_attr__('Buscar avisos...', 'flavor-chat-ia'); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl bg-white/10 backdrop-blur text-white placeholder-white/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30" />
                    </div>
                    <button type="submit" class="px-6 py-4 rounded-xl bg-white text-red-600 font-semibold hover:bg-white/90 transition-colors">
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>

            <!-- Botones CTA -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="<?php echo esc_url($url_ver_avisos); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white text-red-600 font-semibold text-lg hover:bg-white/90 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <?php echo esc_html__('Ver Todos los Avisos', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url($url_suscribirse); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-white/30 text-white font-semibold text-lg hover:bg-white/10 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <?php echo esc_html__('Suscribirme a Alertas', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($avisos_activos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Avisos Activos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($avisos_urgentes); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Urgentes', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html(number_format_i18n($avisos_leidos)); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Lecturas Totales', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
