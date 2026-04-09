<?php
/**
 * Template: Fichaje Empleados Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$titulo_hero = $titulo_hero ?? 'Control de Fichaje';
$subtitulo_hero = $subtitulo_hero ?? 'Gestiona horarios y asistencia de tu equipo';
$empleados_registrados = $empleados_registrados ?? 85;
$fichajes_hoy = $fichajes_hoy ?? 64;
$horas_totales = $horas_totales ?? '1.240';
$url_fichar = $url_fichar ?? '/fichaje/';
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #64748B) 0%, var(--flavor-secondary, #4B5563) 100%); min-height: 500px;">
    <!-- Patron decorativo -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><rect width=%2280%22 height=%2280%22 fill=%22none%22/><circle cx=%2240%22 cy=%2240%22 r=%222%22 fill=%22white%22/></svg><?php echo esc_html__('\'); background-size: 80px 80px;">', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
    </div>

    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html($fichajes_hoy); ?> <?php echo esc_html__('fichajes hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html($titulo_hero); ?>
            </h1>
            <p class="text-xl text-white/80 mb-10">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- CTA -->
            <a href="<?php echo esc_url($url_fichar); ?>" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-slate-700 font-semibold text-lg hover:bg-white/90 transition-colors shadow-lg mb-12">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <?php echo esc_html__('Fichar Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </div>

        <!-- Estadisticas -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($empleados_registrados); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Empleados Registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($fichajes_hoy); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Fichajes Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($horas_totales); ?>h</div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Horas Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
            </div>
        </div>
    </div>
</section>
