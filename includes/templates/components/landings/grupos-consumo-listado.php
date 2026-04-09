<?php
/**
 * Template: Listado Grupos de Consumo
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$columnas = $columnas ?? 3;
$limite = $limite ?? 6;

// Obtener grupos reales del CPT gc_grupo
$grupos_query = new WP_Query([
    'post_type' => 'gc_grupo',
    'posts_per_page' => $limite,
    'post_status' => 'publish',
    'orderby' => 'title',
    'order' => 'ASC',
]);

// Si no hay grupos reales, usar demo data
$tiene_grupos_reales = $grupos_query->have_posts();

// Demo data como fallback
$grupos_demo = [
    ['id' => 0, 'nombre' => 'EcoConsumo Local', 'descripcion' => 'Productos ecológicos de proximidad', 'socios' => 45, 'tipo' => 'Ecológico', 'url' => '#'],
    ['id' => 0, 'nombre' => 'La Cesta Verde', 'descripcion' => 'Frutas y verduras de temporada', 'socios' => 32, 'tipo' => 'Frutas/Verduras', 'url' => '#'],
    ['id' => 0, 'nombre' => 'Pan Artesano', 'descripcion' => 'Pan de masa madre artesanal', 'socios' => 28, 'tipo' => 'Panadería', 'url' => '#'],
];

// Permitir filtrar los grupos (para extensibilidad)
$grupos_demo = apply_filters('flavor_grupos_consumo_listado_demo', $grupos_demo);
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? 'Grupos Disponibles'); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-<?php echo esc_attr($columnas); ?> gap-6">
            <?php if ($tiene_grupos_reales): ?>
                <?php while ($grupos_query->have_posts()): $grupos_query->the_post();
                    $grupo_id = get_the_ID();
                    $grupo_url = get_permalink($grupo_id);
                    $grupo_tipo = get_post_meta($grupo_id, '_gc_tipo', true) ?: __('Grupo', 'flavor-platform');
                    $grupo_socios = (int) get_post_meta($grupo_id, '_gc_num_socios', true) ?: 0;
                    $grupo_imagen = get_the_post_thumbnail_url($grupo_id, 'medium');
                ?>
                <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100">
                    <div class="h-32 bg-gradient-to-br from-lime-100 to-green-100 flex items-center justify-center overflow-hidden">
                        <?php if ($grupo_imagen): ?>
                            <img src="<?php echo esc_url($grupo_imagen); ?>" alt="<?php the_title_attribute(); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-5xl">🥕</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <span class="bg-lime-100 text-lime-700 text-xs font-medium px-3 py-1 rounded-full">
                            <?php echo esc_html($grupo_tipo); ?>
                        </span>
                        <h3 class="text-lg font-bold text-gray-800 mt-3 mb-2">
                            <a href="<?php echo esc_url($grupo_url); ?>" class="hover:text-lime-600">
                                <?php the_title(); ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 15)); ?></p>
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>👥 <?php echo esc_html($grupo_socios); ?> miembros</span>
                            <a href="<?php echo esc_url($grupo_url); ?>" class="text-lime-600 font-medium hover:underline">
                                <?php _e('Ver más', 'flavor-platform'); ?> →
                            </a>
                        </div>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); ?>
            <?php else: ?>
                <?php // Mostrar demo data cuando no hay grupos reales ?>
                <?php foreach ($grupos_demo as $grupo): ?>
                <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 opacity-75">
                    <div class="h-32 bg-gradient-to-br from-lime-100 to-green-100 flex items-center justify-center">
                        <span class="text-5xl">🥕</span>
                    </div>
                    <div class="p-6">
                        <span class="bg-lime-100 text-lime-700 text-xs font-medium px-3 py-1 rounded-full">
                            <?php echo esc_html($grupo['tipo']); ?>
                        </span>
                        <h3 class="text-lg font-bold text-gray-800 mt-3 mb-2">
                            <?php echo esc_html($grupo['nombre']); ?>
                            <span class="text-xs text-gray-400 font-normal">(<?php _e('Demo', 'flavor-platform'); ?>)</span>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo esc_html($grupo['descripcion']); ?></p>
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>👥 <?php echo esc_html($grupo['socios']); ?> miembros</span>
                            <span class="text-gray-400 text-xs italic"><?php _e('Próximamente', 'flavor-platform'); ?></span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url(get_post_type_archive_link('gc_grupo') ?: home_url('/gc-grupos/')); ?>" class="inline-block bg-lime-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors">
                <?php _e('Ver todos los grupos', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
</section>
