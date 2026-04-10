<?php
/**
 * Template: Hero Clientes / CRM
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_hero = $titulo ?? __('Gestion de Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subtitulo_hero = $subtitulo ?? __('CRM integrado para gestionar tus contactos, notas e interacciones', FLAVOR_PLATFORM_TEXT_DOMAIN);
$imagen_fondo_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';

// Estadisticas rapidas (fallback si no hay datos reales)
$total_clientes_stat = 0;
$clientes_activos_stat = 0;
$nuevos_mes_stat = 0;
$valor_pipeline_stat = 0;

if (class_exists('wpdb')) {
    global $wpdb;
    $tabla_clientes = $wpdb->prefix . 'flavor_clientes';

    if (Flavor_Platform_Helpers::tabla_existe($tabla_clientes)) {
        $total_clientes_stat = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes");
        $clientes_activos_stat = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_clientes WHERE estado = 'activo'");
        $nuevos_mes_stat = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_clientes WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        );
        $valor_pipeline_stat = (float) $wpdb->get_var(
            "SELECT IFNULL(SUM(valor_estimado), 0) FROM $tabla_clientes WHERE valor_estimado > 0"
        );
    }
}

// Si no hay datos reales, usar datos de ejemplo
$usar_datos_ejemplo = ($total_clientes_stat === 0);
if ($usar_datos_ejemplo) {
    $total_clientes_stat = 248;
    $clientes_activos_stat = 156;
    $nuevos_mes_stat = 32;
    $valor_pipeline_stat = 125400;
}

// Formatear valor pipeline
if ($valor_pipeline_stat >= 1000000) {
    $valor_pipeline_formateado = number_format($valor_pipeline_stat / 1000000, 1, ',', '.') . 'M';
} elseif ($valor_pipeline_stat >= 1000) {
    $valor_pipeline_formateado = number_format($valor_pipeline_stat / 1000, 1, ',', '.') . 'K';
} else {
    $valor_pipeline_formateado = number_format($valor_pipeline_stat, 0, ',', '.');
}
?>

<section class="flavor-component flavor-section relative min-h-[70vh] flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_fondo_url): ?>
            <img src="<?php echo esc_url($imagen_fondo_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay, 0.85);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
        <?php endif; ?>

        <!-- Patron decorativo: personas/contactos -->
        <div class="absolute inset-0 opacity-5">
            <svg width="100%" height="100%">
                <pattern id="crm-pattern" x="0" y="0" width="120" height="120" patternUnits="userSpaceOnUse">
                    <circle cx="30" cy="25" r="10" fill="white"/>
                    <path d="M15,50 Q30,40 45,50" stroke="white" stroke-width="2" fill="none"/>
                    <circle cx="90" cy="25" r="10" fill="white"/>
                    <path d="M75,50 Q90,40 105,50" stroke="white" stroke-width="2" fill="none"/>
                    <circle cx="60" cy="75" r="10" fill="white"/>
                    <path d="M45,100 Q60,90 75,100" stroke="white" stroke-width="2" fill="none"/>
                    <line x1="45" y1="30" x2="75" y2="30" stroke="white" stroke-width="1" stroke-dasharray="4,4"/>
                    <line x1="40" y1="50" x2="50" y2="70" stroke="white" stroke-width="1" stroke-dasharray="4,4"/>
                    <line x1="80" y1="50" x2="70" y2="70" stroke="white" stroke-width="1" stroke-dasharray="4,4"/>
                </pattern>
                <rect width="100%" height="100%" fill="url(#crm-pattern)"/>
            </svg>
        </div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono CRM -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl mb-8 bg-white bg-opacity-15 backdrop-blur-sm">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo_hero); ?>
            </h1>

            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo_hero); ?>
            </p>

            <!-- Tarjeta de estadisticas rapidas -->
            <div class="flavor-card max-w-3xl mx-auto bg-white bg-opacity-10 backdrop-blur-md rounded-2xl border border-white border-opacity-20">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 p-8">
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-white">
                            <?php echo esc_html(number_format($total_clientes_stat, 0, ',', '.')); ?>
                        </div>
                        <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);">
                            <?php esc_html_e('Total Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-white">
                            <?php echo esc_html(number_format($clientes_activos_stat, 0, ',', '.')); ?>
                        </div>
                        <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);">
                            <?php esc_html_e('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-white">
                            <?php echo esc_html($nuevos_mes_stat); ?>
                        </div>
                        <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);">
                            <?php esc_html_e('Nuevos este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-white">
                            <?php echo esc_html($valor_pipeline_formateado); ?>&euro;
                        </div>
                        <div class="text-sm mt-1" style="color: rgba(255,255,255,0.75);">
                            <?php esc_html_e('Pipeline', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
