<?php
/**
 * Template: Hero Comunidades
 *
 * @var string $titulo
 * @var string $subtitulo
 * @var string $imagen_fondo
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$url_imagen_fondo = !empty($imagen_fondo) ? wp_get_attachment_image_url($imagen_fondo, 'full') : '';

// Datos de ejemplo para estadisticas
$estadisticas_comunidades = [
    'total_comunidades' => 0,
    'total_miembros'    => 0,
    'total_actividad'   => 0,
];

// Intentar obtener datos reales de la base de datos
global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
$tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

if (Flavor_Chat_Helpers::tabla_existe($tabla_comunidades)) {
    $estadisticas_comunidades['total_comunidades'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'"
    );
    $estadisticas_comunidades['total_miembros'] = (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id) FROM $tabla_miembros WHERE estado = 'activo'"
    );
    $estadisticas_comunidades['total_actividad'] = (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM $tabla_actividad"
    );
}

// Valores fallback de ejemplo
$total_comunidades_mostrar = $estadisticas_comunidades['total_comunidades'] > 0
    ? $estadisticas_comunidades['total_comunidades']
    : 24;
$total_miembros_mostrar = $estadisticas_comunidades['total_miembros'] > 0
    ? $estadisticas_comunidades['total_miembros']
    : 580;
$total_actividad_mostrar = $estadisticas_comunidades['total_actividad'] > 0
    ? $estadisticas_comunidades['total_actividad']
    : 1200;
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <?php if ($url_imagen_fondo): ?>
            <img src="<?php echo esc_url($url_imagen_fondo); ?>" alt="" class="w-full h-full object-cover">
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%); opacity: var(--flavor-hero-overlay);"></div>
        <?php else: ?>
            <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary) 0%, var(--flavor-secondary) 100%);"></div>
            <!-- Patron decorativo -->
            <div class="absolute inset-0 opacity-10">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="comunidades-pattern" x="0" y="0" width="60" height="60" patternUnits="userSpaceOnUse">
                            <circle cx="30" cy="30" r="8" fill="white" opacity="0.3"/>
                            <circle cx="10" cy="10" r="4" fill="white" opacity="0.2"/>
                            <circle cx="50" cy="50" r="4" fill="white" opacity="0.2"/>
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#comunidades-pattern)"/>
                </svg>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono principal -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-8" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>

            <h1 class="mb-6" style="color: white; font-size: 3rem; font-weight: 800; line-height: 1.1;">
                <?php echo esc_html($titulo ?? __('Comunidades', 'flavor-chat-ia')); ?>
            </h1>

            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9); max-width: 600px; margin-left: auto; margin-right: auto;">
                <?php echo esc_html($subtitulo ?? __('Encuentra tu tribu y conecta con personas que comparten tus intereses', 'flavor-chat-ia')); ?>
            </p>

            <!-- Botones de accion -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="#comunidades" class="flavor-button flavor-button-primary px-8 py-3 text-lg font-bold rounded-full shadow-lg hover:shadow-xl transition-all" style="background: white; color: var(--flavor-primary);">
                    <?php esc_html_e('Explorar Comunidades', 'flavor-chat-ia'); ?>
                </a>
                <a href="#crear" class="flavor-button px-8 py-3 text-lg font-bold rounded-full transition-all" style="background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                    <?php esc_html_e('Crear Comunidad', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Estadisticas -->
            <div class="grid grid-cols-3 gap-8 max-w-2xl mx-auto">
                <div class="text-center">
                    <div class="text-4xl font-black mb-1" style="color: white;">
                        <?php echo esc_html($total_comunidades_mostrar); ?>+
                    </div>
                    <div class="text-sm font-medium" style="color: rgba(255,255,255,0.75);">
                        <?php esc_html_e('Comunidades', 'flavor-chat-ia'); ?>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-black mb-1" style="color: white;">
                        <?php echo esc_html($total_miembros_mostrar); ?>+
                    </div>
                    <div class="text-sm font-medium" style="color: rgba(255,255,255,0.75);">
                        <?php esc_html_e('Miembros', 'flavor-chat-ia'); ?>
                    </div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-black mb-1" style="color: white;">
                        <?php echo esc_html($total_actividad_mostrar); ?>+
                    </div>
                    <div class="text-sm font-medium" style="color: rgba(255,255,255,0.75);">
                        <?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
