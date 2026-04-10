<?php
/**
 * Template: Servicios Banco de Tiempo
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$limite = $limite ?? 8;
$tipo_filtro = $tipo ?? 'todos';

// Obtener servicios reales de la base de datos
global $wpdb;
$tabla_servicios = $wpdb->prefix . 'flavor_banco_tiempo_servicios';
$tabla_existe = $wpdb->get_var("SHOW TABLES LIKE '$tabla_servicios'") === $tabla_servicios;

$servicios_reales = [];
if ($tabla_existe) {
    $servicios_reales = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, u.display_name as nombre_usuario
         FROM $tabla_servicios s
         LEFT JOIN {$wpdb->users} u ON s.usuario_id = u.ID
         WHERE s.estado = 'activo'
         ORDER BY s.fecha_publicacion DESC
         LIMIT %d",
        $limite
    ));
}

$tiene_servicios_reales = !empty($servicios_reales);

// Página base del banco de tiempo
$pagina_banco_tiempo = home_url('/banco-tiempo/');

// Demo data como fallback
$servicios_demo = [
    ['id' => 0, 'titulo' => 'Clases de inglés', 'tipo' => 'oferta', 'horas' => 1, 'usuario' => 'María G.', 'categoria' => 'Idiomas'],
    ['id' => 0, 'titulo' => 'Ayuda con mudanza', 'tipo' => 'demanda', 'horas' => 3, 'usuario' => 'Carlos L.', 'categoria' => 'Hogar'],
    ['id' => 0, 'titulo' => 'Reparación de bicis', 'tipo' => 'oferta', 'horas' => 2, 'usuario' => 'Ana M.', 'categoria' => 'Reparaciones'],
    ['id' => 0, 'titulo' => 'Clases de guitarra', 'tipo' => 'demanda', 'horas' => 1, 'usuario' => 'Pedro S.', 'categoria' => 'Música'],
];

// Permitir filtrar los servicios (para extensibilidad)
$servicios_demo = apply_filters('flavor_banco_tiempo_servicios_demo', $servicios_demo);

// Categorías con colores
$categorias_colores = [
    'educacion' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    'bricolaje' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
    'cuidados' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-700'],
    'tecnologia' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'],
    'idiomas' => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
    'hogar' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
    'default' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? __('Servicios Disponibles', 'flavor-platform')); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php if ($tiene_servicios_reales): ?>
                <?php foreach ($servicios_reales as $servicio):
                    $servicio_url = add_query_arg('servicio', $servicio->id, $pagina_banco_tiempo);
                    $categoria_slug = strtolower($servicio->categoria ?? 'default');
                    $colores_categoria = $categorias_colores[$categoria_slug] ?? $categorias_colores['default'];
                    $nombre_usuario = !empty($servicio->nombre_usuario) ? $servicio->nombre_usuario : __('Anónimo', 'flavor-platform');
                ?>
                <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="<?php echo esc_attr($colores_categoria['bg'] . ' ' . $colores_categoria['text']); ?> text-xs font-medium px-3 py-1 rounded-full">
                            <?php echo esc_html(ucfirst($servicio->categoria ?? __('General', 'flavor-platform'))); ?>
                        </span>
                        <span class="text-violet-600 font-bold"><?php echo esc_html(number_format($servicio->horas_estimadas, 1)); ?>h</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <a href="<?php echo esc_url($servicio_url); ?>" class="hover:text-violet-600">
                            <?php echo esc_html($servicio->titulo); ?>
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-4"><?php echo esc_html(wp_trim_words($servicio->descripcion ?? '', 12)); ?></p>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span><?php echo esc_html($nombre_usuario); ?></span>
                        <a href="<?php echo esc_url($servicio_url); ?>" class="text-violet-600 font-medium hover:underline">
                            <?php _e('Ver más', 'flavor-platform'); ?> →
                        </a>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <?php // Mostrar demo data cuando no hay servicios reales ?>
                <?php foreach ($servicios_demo as $servicio): ?>
                <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 p-6 opacity-75">
                    <div class="flex items-center justify-between mb-3">
                        <span class="<?php echo $servicio['tipo'] === 'oferta' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?> text-xs font-medium px-3 py-1 rounded-full">
                            <?php echo $servicio['tipo'] === 'oferta' ? '🎁 ' . __('Ofrezco', 'flavor-platform') : '🙋 ' . __('Busco', 'flavor-platform'); ?>
                        </span>
                        <span class="text-violet-600 font-bold"><?php echo esc_html($servicio['horas']); ?>h</span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <?php echo esc_html($servicio['titulo']); ?>
                        <span class="text-xs text-gray-400 font-normal">(<?php _e('Demo', 'flavor-platform'); ?>)</span>
                    </h3>
                    <p class="text-sm text-gray-500 mb-3"><?php echo esc_html($servicio['categoria']); ?></p>
                    <div class="flex items-center justify-between text-sm text-gray-500">
                        <span><?php echo esc_html($servicio['usuario']); ?></span>
                        <span class="text-gray-400 text-xs italic"><?php _e('Próximamente', 'flavor-platform'); ?></span>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url($pagina_banco_tiempo); ?>" class="inline-block bg-violet-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-violet-600 transition-colors">
                <?php _e('Ver todos los servicios', 'flavor-platform'); ?>
            </a>
        </div>
    </div>
</section>
