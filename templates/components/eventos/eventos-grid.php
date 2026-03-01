<?php
/**
 * Template: Eventos Grid
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) { exit; }

// Obtener eventos desde BD
$eventos_lista = [];
$modulo_eventos = null;
if (class_exists('Flavor_Chat_Module_Loader')) {
    $loader = Flavor_Chat_Module_Loader::get_instance();
    $modulo_eventos = $loader->get_module('eventos');
}

if ($modulo_eventos) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_eventos';
    if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
        $eventos_lista = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $tabla WHERE estado = 'publicado' AND fecha_inicio >= %s ORDER BY fecha_inicio ASC LIMIT 12",
                current_time('mysql')
            ), ARRAY_A
        );
    }
}

// Fallback de ejemplo si no hay eventos
if (empty($eventos_lista)) {
    $eventos_lista = [
        ['id'=>1, 'titulo'=>'Conferencia de Innovacion Digital', 'tipo'=>'conferencia', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+3 days')), 'ubicacion'=>'Centro de Convenciones', 'precio'=>15.00, 'precio_socios'=>10.00, 'aforo_maximo'=>100, 'inscritos_count'=>45, 'estado'=>'publicado', 'descripcion'=>'Descubre las ultimas tendencias en tecnologia digital'],
        ['id'=>2, 'titulo'=>'Taller de Ceramica Artesanal', 'tipo'=>'taller', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'ubicacion'=>'Sala de Artes', 'precio'=>25.00, 'precio_socios'=>20.00, 'aforo_maximo'=>20, 'inscritos_count'=>18, 'estado'=>'publicado', 'descripcion'=>'Aprende tecnicas ancestrales de ceramica'],
        ['id'=>3, 'titulo'=>'Charla: Alimentacion Saludable', 'tipo'=>'charla', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'ubicacion'=>'Biblioteca Municipal', 'precio'=>0, 'precio_socios'=>0, 'aforo_maximo'=>50, 'inscritos_count'=>12, 'estado'=>'publicado', 'descripcion'=>'Consejos practicos para una dieta equilibrada'],
        ['id'=>4, 'titulo'=>'Festival de Musica en Vivo', 'tipo'=>'festival', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+10 days')), 'ubicacion'=>'Plaza Mayor', 'precio'=>0, 'precio_socios'=>0, 'aforo_maximo'=>500, 'inscritos_count'=>230, 'estado'=>'publicado', 'descripcion'=>'Disfruta de bandas locales en directo'],
        ['id'=>5, 'titulo'=>'Torneo de Padel', 'tipo'=>'deportivo', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+12 days')), 'ubicacion'=>'Club Deportivo', 'precio'=>10.00, 'precio_socios'=>5.00, 'aforo_maximo'=>32, 'inscritos_count'=>24, 'estado'=>'publicado', 'descripcion'=>'Torneo por parejas, todos los niveles'],
        ['id'=>6, 'titulo'=>'Networking Emprendedores', 'tipo'=>'networking', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+14 days')), 'ubicacion'=>'Coworking Central', 'precio'=>0, 'precio_socios'=>0, 'aforo_maximo'=>40, 'inscritos_count'=>15, 'estado'=>'publicado', 'descripcion'=>'Conecta con otros emprendedores de la zona'],
    ];
}

$colores_tipo = [
    'conferencia' => '#3B82F6', 'taller' => '#8B5CF6', 'charla' => '#06B6D4',
    'festival' => '#F59E0B', 'deportivo' => '#10B981', 'cultural' => '#EC4899',
    'social' => '#F97316', 'networking' => '#6366F1',
];

$tipos_disponibles = array_unique(array_column($eventos_lista, 'tipo'));
?>
<section class="flavor-component flavor-section py-16 lg:py-24" style="background: var(--flavor-bg, #F9FAFB);">
    <div class="flavor-container">
        <div class="text-center mb-12">
            <h2 class="text-3xl lg:text-4xl font-bold mb-4" style="color: var(--flavor-text, #111827);">
                <?php echo esc_html__('Proximos Eventos', 'flavor-chat-ia'); ?>
            </h2>
            <p class="text-lg" style="color: var(--flavor-text-muted, #6B7280);">
                <?php echo esc_html__('Encuentra tu proximo evento y reserva tu plaza', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <!-- Filter Pills -->
        <div class="flex flex-wrap justify-center gap-2 mb-10">
            <button class="filter-pill active px-4 py-2 rounded-full text-sm font-medium transition-all" data-filter="all" style="background: var(--flavor-primary, #3B82F6); color: white;">
                <?php echo esc_html__('Todos', 'flavor-chat-ia'); ?>
            </button>
            <?php foreach ($tipos_disponibles as $tipo_item) : ?>
                <button class="filter-pill px-4 py-2 rounded-full text-sm font-medium transition-all border" data-filter="<?php echo esc_attr($tipo_item); ?>" style="border-color: <?php echo esc_attr($colores_tipo[$tipo_item] ?? '#6B7280'); ?>; color: <?php echo esc_attr($colores_tipo[$tipo_item] ?? '#6B7280'); ?>; background: transparent;">
                    <?php echo esc_html(ucfirst($tipo_item)); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Events Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($eventos_lista as $evento) :
                $color_tipo = $colores_tipo[$evento['tipo']] ?? '#6B7280';
                $precio = (float) ($evento['precio'] ?? 0);
                $aforo = (int) ($evento['aforo_maximo'] ?? 0);
                $inscritos = (int) ($evento['inscritos_count'] ?? 0);
                $porcentaje_aforo = ($aforo > 0) ? round(($inscritos / $aforo) * 100) : 0;
                $fecha_evento = strtotime($evento['fecha_inicio']);
            ?>
                <div class="flavor-card group rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 border" style="background: white; border-color: #E5E7EB;" data-tipo="<?php echo esc_attr($evento['tipo']); ?>">
                    <!-- Card Header with gradient -->
                    <div class="relative h-48 overflow-hidden" style="background: linear-gradient(135deg, <?php echo esc_attr($color_tipo); ?> 0%, <?php echo esc_attr($color_tipo); ?>CC 100%);">
                        <div class="absolute inset-0 opacity-20">
                            <svg class="w-full h-full" viewBox="0 0 200 200"><circle cx="150" cy="50" r="80" fill="white" opacity="0.1"/><circle cx="30" cy="150" r="60" fill="white" opacity="0.1"/></svg>
                        </div>
                        <!-- Date Badge -->
                        <div class="absolute top-4 left-4 bg-white rounded-xl p-2 text-center shadow-lg" style="min-width: 60px;">
                            <div class="text-xs font-bold uppercase" style="color: <?php echo esc_attr($color_tipo); ?>;">
                                <?php echo esc_html(date_i18n('M', $fecha_evento)); ?>
                            </div>
                            <div class="text-2xl font-bold" style="color: var(--flavor-text, #111827);">
                                <?php echo esc_html(date_i18n('d', $fecha_evento)); ?>
                            </div>
                            <div class="text-xs" style="color: var(--flavor-text-muted, #6B7280);">
                                <?php echo esc_html(date_i18n('H:i', $fecha_evento)); ?>
                            </div>
                        </div>
                        <!-- Type Badge -->
                        <div class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-semibold text-white" style="background: rgba(0,0,0,0.3); backdrop-filter: blur(10px);">
                            <?php echo esc_html(ucfirst($evento['tipo'])); ?>
                        </div>
                        <!-- Price Badge -->
                        <?php if ($precio > 0) : ?>
                            <div class="absolute bottom-4 right-4 px-3 py-1 rounded-full text-sm font-bold bg-white shadow-lg" style="color: <?php echo esc_attr($color_tipo); ?>;">
                                <?php echo esc_html(number_format($precio, 2, ',', '.') . 'â¬'); ?>
                            </div>
                        <?php else : ?>
                            <div class="absolute bottom-4 right-4 px-3 py-1 rounded-full text-sm font-bold bg-green-500 text-white shadow-lg">
                                <?php echo esc_html__('Gratuito', 'flavor-chat-ia'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <!-- Card Body -->
                    <div class="p-5">
                        <h3 class="text-lg font-bold mb-2 group-hover:text-blue-600 transition-colors" style="color: var(--flavor-text, #111827);">
                            <?php echo esc_html($evento['titulo']); ?>
                        </h3>
                        <?php if (!empty($evento['descripcion'])) : ?>
                            <p class="text-sm mb-3 line-clamp-2" style="color: var(--flavor-text-muted, #6B7280);">
                                <?php echo esc_html(wp_trim_words($evento['descripcion'], 15)); ?>
                            </p>
                        <?php endif; ?>

                        <!-- Location -->
                        <?php if (!empty($evento['ubicacion'])) : ?>
                            <div class="flex items-center gap-2 mb-3 text-sm" style="color: var(--flavor-text-muted, #6B7280);">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <span><?php echo esc_html($evento['ubicacion']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Capacity Bar -->
                        <?php if ($aforo > 0) : ?>
                            <div class="mb-4">
                                <div class="flex justify-between text-xs mb-1" style="color: var(--flavor-text-muted, #6B7280);">
                                    <span><?php echo esc_html($inscritos . '/' . $aforo . ' plazas'); ?></span>
                                    <span><?php echo esc_html($porcentaje_aforo . '%'); ?></span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-gray-100">
                                    <div class="h-full rounded-full transition-all" style="width: <?php echo esc_attr(min($porcentaje_aforo, 100)); ?>%; background: <?php echo esc_attr($porcentaje_aforo >= 90 ? '#EF4444' : ($porcentaje_aforo >= 70 ? '#F59E0B' : $color_tipo)); ?>;"></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- CTA -->
                        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_item_url('eventos', $evento['id'], 'inscribirse')); ?>" class="flavor-button flavor-button-primary w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90" style="background: <?php echo esc_attr($color_tipo); ?>;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                            <?php echo esc_html__('Inscribirse', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
