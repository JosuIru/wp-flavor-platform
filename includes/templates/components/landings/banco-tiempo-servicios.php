<?php
/**
 * Template: Servicios Banco de Tiempo
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$limite = $limite ?? 8;
$tipo_filtro = $tipo ?? 'todos';

// Obtener servicios (demo data si no hay)
$servicios = apply_filters('flavor_banco_tiempo_servicios', [
    ['id' => 1, 'titulo' => 'Clases de inglés', 'tipo' => 'oferta', 'horas' => 1, 'usuario' => 'María G.', 'categoria' => 'Idiomas'],
    ['id' => 2, 'titulo' => 'Ayuda con mudanza', 'tipo' => 'demanda', 'horas' => 3, 'usuario' => 'Carlos L.', 'categoria' => 'Hogar'],
    ['id' => 3, 'titulo' => 'Reparación de bicis', 'tipo' => 'oferta', 'horas' => 2, 'usuario' => 'Ana M.', 'categoria' => 'Reparaciones'],
    ['id' => 4, 'titulo' => 'Clases de guitarra', 'tipo' => 'demanda', 'horas' => 1, 'usuario' => 'Pedro S.', 'categoria' => 'Música'],
]);
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? 'Servicios Disponibles'); ?></h2>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach (array_slice($servicios, 0, $limite) as $servicio): ?>
            <article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-3">
                    <span class="<?php echo $servicio['tipo'] === 'oferta' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?> text-xs font-medium px-3 py-1 rounded-full">
                        <?php echo $servicio['tipo'] === 'oferta' ? '🎁 Ofrezco' : '🙋 Busco'; ?>
                    </span>
                    <span class="text-violet-600 font-bold"><?php echo esc_html($servicio['horas']); ?>h</span>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                    <a href="<?php echo esc_url(home_url('/banco-tiempo/' . $servicio['id'] . '/')); ?>" class="hover:text-violet-600">
                        <?php echo esc_html($servicio['titulo']); ?>
                    </a>
                </h3>
                <p class="text-sm text-gray-500 mb-3"><?php echo esc_html($servicio['categoria']); ?></p>
                <div class="flex items-center justify-between text-sm text-gray-500">
                    <span><?php echo esc_html($servicio['usuario']); ?></span>
                    <span>⭐ 5.0</span>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url(home_url('/banco-tiempo/')); ?>" class="inline-block bg-violet-500 text-white px-8 py-3 rounded-xl font-semibold hover:bg-violet-600 transition-colors">
                Ver todos los servicios
            </a>
        </div>
    </div>
</section>
