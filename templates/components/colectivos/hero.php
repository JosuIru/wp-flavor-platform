<?php
/**
 * Template: Hero Colectivos y Asociaciones
 *
 * @var string $titulo
 * @var string $subtitulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion    = !empty($titulo) ? $titulo : __('Colectivos y Asociaciones', 'flavor-chat-ia');
$subtitulo_seccion = !empty($subtitulo) ? $subtitulo : __('Descubre y participa en los colectivos de tu comunidad', 'flavor-chat-ia');

// Datos fallback para estadisticas del hero
$total_colectivos_activos = 0;
$total_miembros_activos   = 0;
$total_proyectos_activos  = 0;

if (function_exists('is_plugin_active')) {
    global $wpdb;
    $tabla_colectivos           = $wpdb->prefix . 'flavor_colectivos';
    $tabla_colectivos_miembros  = $wpdb->prefix . 'flavor_colectivos_miembros';
    $tabla_colectivos_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';

    if (Flavor_Chat_Helpers::tabla_existe($tabla_colectivos)) {
        $total_colectivos_activos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos WHERE estado = 'activo'");
        $total_miembros_activos   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos_miembros WHERE estado = 'activo'");
        $total_proyectos_activos  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_colectivos_proyectos WHERE estado IN ('planificado','en_curso')");
    }
}

// Datos fallback si las tablas no existen
if ($total_colectivos_activos === 0) {
    $total_colectivos_activos = 24;
    $total_miembros_activos   = 580;
    $total_proyectos_activos  = 47;
}
?>

<section class="flavor-component flavor-section relative min-h-screen flex items-center" style="padding-top: 0; padding-bottom: 0;">
    <!-- Fondo con gradiente -->
    <div class="absolute inset-0 z-0">
        <div class="absolute inset-0" style="background: linear-gradient(135deg, var(--flavor-primary, #6366F1) 0%, var(--flavor-secondary, #8B5CF6) 50%, #A78BFA 100%);"></div>

        <!-- Pattern Overlay -->
        <div class="absolute inset-0 opacity-10"
             style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;80&quot; height=&quot;80&quot; viewBox=&quot;0 0 80 80&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23ffffff&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M40 0C17.9 0 0 17.9 0 40s17.9 40 40 40 40-17.9 40-40S62.1 0 40 0zm0 70c-16.5 0-30-13.5-30-30S23.5 10 40 10s30 13.5 30 30-13.5 30-30 30z&quot; opacity=&quot;.1&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>

        <!-- Decorative circles -->
        <div class="absolute top-20 left-10 w-64 h-64 rounded-full opacity-10" style="background: radial-gradient(circle, white 0%, transparent 70%);"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 rounded-full opacity-5" style="background: radial-gradient(circle, white 0%, transparent 70%);"></div>
    </div>

    <!-- Contenido -->
    <div class="flavor-container relative z-10 py-20">
        <div class="max-w-4xl mx-auto text-center">
            <!-- Icono -->
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full mb-8" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-10 h-10" fill="none" stroke="white" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                </svg>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6" style="color: white;">
                <?php echo esc_html($titulo_seccion); ?>
            </h1>
            <p class="text-xl md:text-2xl mb-12" style="color: rgba(255,255,255,0.9);">
                <?php echo esc_html($subtitulo_seccion); ?>
            </p>

            <!-- Botones de accion -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                <a href="#colectivos-grid" class="inline-flex items-center justify-center px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1" style="background: white; color: var(--flavor-primary, #6366F1);">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <?php esc_html_e('Explorar Colectivos', 'flavor-chat-ia'); ?>
                </a>
                <a href="#crear-colectivo" class="inline-flex items-center justify-center px-8 py-4 rounded-xl font-semibold text-lg transition-all duration-300 transform hover:-translate-y-1" style="background: rgba(255,255,255,0.15); color: white; border: 2px solid rgba(255,255,255,0.3); backdrop-filter: blur(10px);">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?php esc_html_e('Crear Colectivo', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <!-- Estadisticas -->
            <div class="flavor-card max-w-3xl mx-auto">
                <div class="grid grid-cols-3 gap-6 py-4">
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold" style="color: var(--flavor-primary, #6366F1);">
                            <?php echo esc_html($total_colectivos_activos); ?>
                        </div>
                        <div class="text-sm mt-1" style="color: var(--flavor-text-muted, #6B7280);">
                            <?php esc_html_e('Colectivos Activos', 'flavor-chat-ia'); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold" style="color: var(--flavor-primary, #6366F1);">
                            <?php echo esc_html($total_miembros_activos); ?>
                        </div>
                        <div class="text-sm mt-1" style="color: var(--flavor-text-muted, #6B7280);">
                            <?php esc_html_e('Miembros', 'flavor-chat-ia'); ?>
                        </div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold" style="color: var(--flavor-primary, #6366F1);">
                            <?php echo esc_html($total_proyectos_activos); ?>
                        </div>
                        <div class="text-sm mt-1" style="color: var(--flavor-text-muted, #6B7280);">
                            <?php esc_html_e('Proyectos Activos', 'flavor-chat-ia'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
