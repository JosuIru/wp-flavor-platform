<?php
/**
 * Template: Hero Banco de Tiempo
 *
 * Seccion hero para la landing del banco de tiempo comunitario.
 * Muestra titulo, subtitulo, estadisticas y botones de accion.
 *
 * @var string $titulo_hero
 * @var string $subtitulo_hero
 * @var int    $servicios_disponibles
 * @var int    $horas_intercambiadas
 * @var int    $miembros_activos
 * @var string $url_ofrecer_servicio
 * @var string $url_buscar_servicio
 * @var string $component_classes
 *
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero             = $titulo_hero ?? 'Banco de Tiempo';
$subtitulo_hero          = $subtitulo_hero ?? 'Intercambia servicios con tu comunidad. Tu tiempo vale, 1 hora = 1 credito de tiempo';
$servicios_disponibles   = $servicios_disponibles ?? 156;
$horas_intercambiadas    = $horas_intercambiadas ?? 2340;
$miembros_activos        = $miembros_activos ?? 89;
$url_ofrecer_servicio    = $url_ofrecer_servicio ?? '/banco-tiempo/ofrecer/';
$url_buscar_servicio     = $url_buscar_servicio ?? '/banco-tiempo/buscar/';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, #F59E0B 0%, #CA8A04 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg>'); background-size: 80px 80px;"></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html__('1 hora = 1 credito de tiempo', 'flavor-chat-ia'); ?></span>
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
                        <input type="text" name="buscar_servicio" placeholder="<?php echo esc_attr__('Que servicio necesitas?', 'flavor-chat-ia'); ?>" class="w-full pl-12 pr-4 py-4 rounded-xl bg-white/10 backdrop-blur text-white placeholder-white/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30" />
                    </div>
                    <button type="submit" class="px-6 py-4 rounded-xl bg-white text-amber-600 font-semibold hover:bg-white/90 transition-colors">
                        <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>

            <!-- Botones CTA -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="<?php echo esc_url($url_ofrecer_servicio); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-white text-amber-600 font-semibold text-lg hover:bg-white/90 transition-all shadow-lg hover:shadow-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?php echo esc_html__('Ofrecer Servicio', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url($url_buscar_servicio); ?>" class="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border-2 border-white/30 text-white font-semibold text-lg hover:bg-white/10 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <?php echo esc_html__('Buscar Servicios', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($servicios_disponibles); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Servicios Disponibles', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html(number_format_i18n($horas_intercambiadas)); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Horas Intercambiadas', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($miembros_activos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Miembros Activos', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
