<?php
/**
 * Template: Hero Incidencias
 * @package FlavorPlatform
 */

$imagen_url = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';

// Obtener estadísticas reales de la base de datos
global $wpdb;
$tabla_incidencias = $wpdb->prefix . 'flavor_incidencias';
$tabla_existe = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tabla_incidencias)) === $tabla_incidencias;

$porcentaje_resueltas = 0;
$tiempo_respuesta = '—';

if ($tabla_existe) {
    $total = intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado != 'eliminada'"));
    $resueltas = intval($wpdb->get_var("SELECT COUNT(*) FROM $tabla_incidencias WHERE estado IN ('resuelta', 'resolved', 'cerrada', 'closed')"));
    $porcentaje_resueltas = $total > 0 ? round(($resueltas / $total) * 100) : 0;
    $tiempo_respuesta = '24h'; // Valor simplificado
}
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($imagen_url): ?>
            <img src="<?php echo esc_url($imagen_url); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
        <?php endif; ?>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <h1 class="mb-6" style="color: white;"><?php echo esc_html($titulo ?? 'Gestión de Incidencias'); ?></h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);"><?php echo esc_html($subtitulo ?? 'Respuesta rápida 24/7'); ?></p>

            <div class="grid grid-cols-2 gap-4 mb-8 max-w-2xl mx-auto">
                <div class="flavor-card text-center">
                    <div class="text-3xl font-bold mb-1" style="color: var(--flavor-primary);"><?php echo esc_html($tiempo_respuesta); ?></div>
                    <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Tiempo Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
                <div class="flavor-card text-center">
                    <div class="text-3xl font-bold mb-1" style="color: var(--flavor-primary);"><?php echo esc_html($porcentaje_resueltas); ?>%</div>
                    <div class="text-sm" style="color: var(--flavor-text-muted);"><?php echo esc_html__('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="#nueva-incidencia" class="flavor-button flavor-button-primary px-8"><?php echo esc_html__('Reportar Incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
                <a href="#mis-incidencias" class="flavor-button px-8"><?php echo esc_html__('Mis Reportes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            </div>
        </div>
    </div>
</section>
