<?php
/**
 * Template: Eventos Hero
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) { exit; }

// Obtener datos del modulo
$modulo_eventos = null;
if (class_exists('Flavor_Chat_Module_Loader')) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $modulo_eventos = $loader->get_module('eventos');
}

// Estadisticas desde BD
$total_eventos = 0;
$eventos_proximos = 0;
$total_tipos = 8;
if ($modulo_eventos) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_eventos';
    if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
        $total_eventos = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado'");
        $eventos_proximos = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla WHERE estado = 'publicado' AND fecha_inicio > %s", current_time('mysql'))
        );
        $total_tipos = (int) $wpdb->get_var("SELECT COUNT(DISTINCT tipo) FROM $tabla WHERE estado = 'publicado'");
    }
}
if ($total_eventos <= 0) { $total_eventos = 24; }
if ($eventos_proximos <= 0) { $eventos_proximos = 8; }
if ($total_tipos <= 0) { $total_tipos = 6; }

$tipos_evento = [
    'conferencia' => ['label' => 'Conferencias', 'icon' => 'microphone',              'color' => '#3B82F6'],
    'taller'      => ['label' => 'Talleres',      'icon' => 'wrench',                  'color' => '#8B5CF6'],
    'charla'      => ['label' => 'Charlas',       'icon' => 'chat-bubble-left-right',  'color' => '#06B6D4'],
    'festival'    => ['label' => 'Festivales',    'icon' => 'musical-note',            'color' => '#F59E0B'],
    'deportivo'   => ['label' => 'Deportivos',    'icon' => 'trophy',                  'color' => '#10B981'],
    'cultural'    => ['label' => 'Culturales',    'icon' => 'paint-brush',             'color' => '#EC4899'],
    'social'      => ['label' => 'Sociales',      'icon' => 'users',                   'color' => '#F97316'],
    'networking'  => ['label' => 'Networking',    'icon' => 'link',                    'color' => '#6366F1'],
];
?>
<section class="flavor-component flavor-section relative overflow-hidden" style="background: linear-gradient(135deg, var(--flavor-primary, #3B82F6) 0%, var(--flavor-secondary, #1E40AF) 100%); min-height: 500px;">
    <div class="absolute inset-0 opacity-10">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"><rect width="80" height="80" fill="none"/><circle cx="40" cy="40" r="2" fill="white"/></svg>'); background-size: 80px 80px;"></div>
    </div>
    <div class="flavor-container relative z-10 py-16 lg:py-24">
        <div class="max-w-4xl mx-auto text-center mb-12">
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full mb-6" style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-white text-sm font-medium"><?php echo esc_html($eventos_proximos); ?> eventos proximos</span>
            </div>
            <h1 class="text-4xl lg:text-5xl font-bold text-white mb-4">
                <?php echo esc_html__('Eventos y Actividades', 'flavor-chat-ia'); ?>
            </h1>
            <p class="text-xl text-white/80 mb-8">
                <?php echo esc_html__('Descubre conferencias, talleres, festivales y mucho mas. Inscribete y no te pierdas nada.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <!-- Search & Filters -->
        <div class="max-w-3xl mx-auto mb-10">
            <form class="flex flex-col sm:flex-row gap-3" method="get">
                <div class="flex-1 relative">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text" name="buscar" placeholder="<?php echo esc_attr__('Buscar eventos...', 'flavor-chat-ia'); ?>" class="w-full pl-12 pr-4 py-3 rounded-xl bg-white/10 backdrop-blur text-white placeholder-white/60 border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30" />
                </div>
                <input type="date" name="desde" class="px-4 py-3 rounded-xl bg-white/10 backdrop-blur text-white border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30" />
                <select name="tipo" class="px-4 py-3 rounded-xl bg-white/10 backdrop-blur text-white border border-white/20 focus:outline-none focus:ring-2 focus:ring-white/30">
                    <option value=""><?php echo esc_html__('Todos los tipos', 'flavor-chat-ia'); ?></option>
                    <?php foreach ($tipos_evento as $tipo_key => $tipo_info) : ?>
                        <option value="<?php echo esc_attr($tipo_key); ?>"><?php echo esc_html($tipo_info['label']); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="flavor-button flavor-button-primary px-6 py-3 rounded-xl bg-white text-blue-600 font-semibold hover:bg-white/90 transition-colors">
                    <?php echo esc_html__('Buscar', 'flavor-chat-ia'); ?>
                </button>
            </form>
        </div>

        <!-- Quick Filters -->
        <div class="flex flex-wrap justify-center gap-3 mb-10">
            <a href="?filtro=hoy" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white text-sm font-medium hover:bg-white/20 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?php echo esc_html__('Hoy', 'flavor-chat-ia'); ?>
            </a>
            <a href="?filtro=semana" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white text-sm font-medium hover:bg-white/20 transition-colors">
                <?php echo esc_html__('Esta Semana', 'flavor-chat-ia'); ?>
            </a>
            <a href="?filtro=mes" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white text-sm font-medium hover:bg-white/20 transition-colors">
                <?php echo esc_html__('Este Mes', 'flavor-chat-ia'); ?>
            </a>
            <a href="?filtro=gratuitos" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white/10 backdrop-blur border border-white/20 text-white text-sm font-medium hover:bg-white/20 transition-colors">
                <?php echo esc_html__('Gratuitos', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <!-- Category Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3 mb-12">
            <?php foreach ($tipos_evento as $tipo_key => $tipo_info) : ?>
                <a href="?tipo=<?php echo esc_attr($tipo_key); ?>" class="flavor-card flex flex-col items-center gap-2 p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20 hover:bg-white/20 transition-all text-center group">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: <?php echo esc_attr($tipo_info['color']); ?>20;">
                        <svg class="w-5 h-5" style="color: <?php echo esc_attr($tipo_info['color']); ?>;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <span class="text-white text-xs font-medium"><?php echo esc_html($tipo_info['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <!-- Stats Bar -->
        <div class="grid grid-cols-3 gap-4 max-w-2xl mx-auto">
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($total_eventos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Eventos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($eventos_proximos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Proximos', 'flavor-chat-ia'); ?></div>
            </div>
            <div class="text-center p-4 rounded-xl bg-white/10 backdrop-blur border border-white/20">
                <div class="text-3xl font-bold text-white"><?php echo esc_html($total_tipos); ?></div>
                <div class="text-sm text-white/70"><?php echo esc_html__('Categorias', 'flavor-chat-ia'); ?></div>
            </div>
        </div>
    </div>
</section>
