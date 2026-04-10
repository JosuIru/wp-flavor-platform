<?php
/**
 * Template: Listado Comunidades
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$limite = $limite ?? 6;
$tipo_filtro = $tipo ?? 'todas';

// Obtener comunidades reales de la base de datos
global $wpdb;
$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_comunidades'") === $tabla_comunidades;

$comunidades_reales = [];
if ($tabla_existe) {
    $comunidades_reales = $wpdb->get_results($wpdb->prepare(
        "SELECT c.*,
                (SELECT COUNT(*) FROM {$wpdb->prefix}flavor_comunidades_miembros WHERE comunidad_id = c.id) as total_miembros
         FROM $tabla_comunidades c
         WHERE c.estado = 'activa'
         ORDER BY c.fecha_creacion DESC
         LIMIT %d",
        $limite
    ));
}

$tiene_comunidades_reales = !empty($comunidades_reales);

// Demo data como fallback
$comunidades_demo = [
    ['id' => 0, 'nombre' => 'Vecinos del Centro', 'descripcion' => 'Comunidad del barrio centro', 'miembros' => 234, 'tipo' => 'Vecinal', 'verificada' => true],
    ['id' => 0, 'nombre' => 'Runners del Parque', 'descripcion' => 'Grupo de corredores', 'miembros' => 89, 'tipo' => 'Deportiva', 'verificada' => false],
    ['id' => 0, 'nombre' => 'Club de Lectura', 'descripcion' => 'Amantes de la literatura', 'miembros' => 45, 'tipo' => 'Cultural', 'verificada' => true],
];

// Pagina de detalle de comunidad
$pagina_comunidad = home_url('/comunidad/');
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? __('Comunidades Activas', 'flavor-platform')); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($tiene_comunidades_reales): ?>
                <?php foreach ($comunidades_reales as $comunidad):
                    $comunidad_url = add_query_arg('comunidad', $comunidad->id, $pagina_comunidad);
                    $categoria_nombre = ucfirst($comunidad->categoria ?? 'General');
                ?>
                <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100">
                    <div class="h-32 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center overflow-hidden">
                        <?php if (!empty($comunidad->imagen_portada)): ?>
                            <img src="<?php echo esc_url($comunidad->imagen_portada); ?>" alt="<?php echo esc_attr($comunidad->nombre); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-5xl">🏘️</span>
                        <?php endif; ?>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="bg-rose-100 text-rose-700 text-xs font-medium px-3 py-1 rounded-full">
                                <?php echo esc_html($categoria_nombre); ?>
                            </span>
                            <?php if (($comunidad->tipo ?? 'publica') === 'privada'): ?>
                            <span class="text-gray-400" title="<?php esc_attr_e('Privada', 'flavor-platform'); ?>">🔒</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">
                            <a href="<?php echo esc_url($comunidad_url); ?>" class="hover:text-rose-600">
                                <?php echo esc_html($comunidad->nombre); ?>
                            </a>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo esc_html(wp_trim_words($comunidad->descripcion ?? '', 15)); ?></p>
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>👥 <?php echo esc_html($comunidad->total_miembros ?? 0); ?> <?php _e('miembros', 'flavor-platform'); ?></span>
                            <a href="<?php echo esc_url($comunidad_url); ?>" class="text-rose-600 font-medium hover:underline">
                                <?php _e('Ver mas', 'flavor-platform'); ?> →
                            </a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <?php // Mostrar demo data cuando no hay comunidades reales ?>
                <?php foreach ($comunidades_demo as $comunidad): ?>
                <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 opacity-75">
                    <div class="h-32 bg-gradient-to-br from-rose-100 to-pink-100 flex items-center justify-center">
                        <span class="text-5xl">🏘️</span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="bg-rose-100 text-rose-700 text-xs font-medium px-3 py-1 rounded-full">
                                <?php echo esc_html($comunidad['tipo']); ?>
                            </span>
                            <?php if ($comunidad['verificada']): ?>
                            <span class="text-green-500" title="<?php esc_attr_e('Verificada', 'flavor-platform'); ?>">✓</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800 mb-2">
                            <?php echo esc_html($comunidad['nombre']); ?>
                            <span class="text-xs text-gray-400 font-normal">(<?php _e('Demo', 'flavor-platform'); ?>)</span>
                        </h3>
                        <p class="text-gray-600 text-sm mb-4"><?php echo esc_html($comunidad['descripcion']); ?></p>
                        <div class="flex items-center justify-between text-sm text-gray-500">
                            <span>👥 <?php echo esc_html($comunidad['miembros']); ?> <?php _e('miembros', 'flavor-platform'); ?></span>
                            <span class="text-gray-400 text-xs italic"><?php _e('Proximamente', 'flavor-platform'); ?></span>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url(home_url('/comunidades/')); ?>" class="inline-block bg-rose-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-rose-600 transition-colors">
                <?php _e('Ver todas las comunidades', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
</section>
