<?php
/**
 * Template: Listado Grupos de Consumo
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$columnas = $columnas ?? 3;
$limite = $limite ?? 6;

// Obtener grupos (demo data si no hay)
$grupos = apply_filters('flavor_grupos_consumo_listado', [
    ['id' => 1, 'nombre' => 'EcoConsumo Local', 'descripcion' => 'Productos ecológicos de proximidad', 'socios' => 45, 'tipo' => 'Ecológico'],
    ['id' => 2, 'nombre' => 'La Cesta Verde', 'descripcion' => 'Frutas y verduras de temporada', 'socios' => 32, 'tipo' => 'Frutas/Verduras'],
    ['id' => 3, 'nombre' => 'Pan Artesano', 'descripcion' => 'Pan de masa madre artesanal', 'socios' => 28, 'tipo' => 'Panadería'],
]);
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? 'Grupos Disponibles'); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-<?php echo esc_attr($columnas); ?> gap-6">
            <?php foreach (array_slice($grupos, 0, $limite) as $grupo): ?>
            <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100">
                <div class="h-32 bg-gradient-to-br from-lime-100 to-green-100 flex items-center justify-center">
                    <span class="text-5xl">🥕</span>
                </div>
                <div class="p-6">
                    <span class="bg-lime-100 text-lime-700 text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo esc_html($grupo['tipo']); ?>
                    </span>
                    <h3 class="text-lg font-bold text-gray-800 mt-3 mb-2">
                        <a href="<?php echo esc_url(home_url('/grupos-consumo/' . $grupo['id'] . '/')); ?>" class="hover:text-lime-600">
                            <?php echo esc_html($grupo['nombre']); ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo esc_html($grupo['descripcion']); ?></p>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span>👥 <?php echo esc_html($grupo['socios']); ?> socios</span>
                        <a href="<?php echo esc_url(home_url('/grupos-consumo/' . $grupo['id'] . '/')); ?>" class="text-lime-600 font-medium hover:underline">
                            Ver más →
                        </a>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url(home_url('/grupos-consumo/')); ?>" class="inline-block bg-lime-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-lime-600 transition-colors">
                Ver todos los grupos
            </a>
        </div>
    </div>
</section>
