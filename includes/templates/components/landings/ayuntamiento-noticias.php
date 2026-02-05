<?php
/**
 * Template: Noticias Ayuntamiento
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$limite = $limite ?? 4;

$noticias = [
    ['titulo' => 'Nuevas ayudas para familias', 'extracto' => 'Paquete de medidas de apoyo económico para familias vulnerables.', 'fecha' => '28 Ene', 'categoria' => 'Servicios Sociales'],
    ['titulo' => 'Obras de mejora en el parque central', 'extracto' => 'Comienzan las obras de remodelación con nuevas zonas verdes.', 'fecha' => '25 Ene', 'categoria' => 'Urbanismo'],
    ['titulo' => 'Campaña de vacunación antigripal', 'extracto' => 'Arranca la campaña para mayores de 65 años.', 'fecha' => '22 Ene', 'categoria' => 'Salud'],
    ['titulo' => 'Nuevo servicio de transporte municipal', 'extracto' => 'Se amplían las rutas del autobús urbano.', 'fecha' => '20 Ene', 'categoria' => 'Transporte'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16">
    <div class="max-w-6xl mx-auto px-6">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-3xl font-bold text-gray-800"><?php echo esc_html($titulo ?? 'Últimas Noticias'); ?></h2>
            <a href="<?php echo esc_url(home_url('/ayuntamiento/noticias/')); ?>" class="text-blue-600 font-medium hover:underline">
                Ver todas →
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach (array_slice($noticias, 0, $limite) as $noticia): ?>
            <article class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition-all">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="text-xs text-blue-600 font-medium"><?php echo esc_html($noticia['categoria']); ?></span>
                        <span class="text-xs text-gray-400"><?php echo esc_html($noticia['fecha']); ?></span>
                    </div>
                    <h3 class="font-semibold text-gray-800 mb-2 hover:text-blue-600 cursor-pointer">
                        <?php echo esc_html($noticia['titulo']); ?>
                    </h3>
                    <p class="text-sm text-gray-600"><?php echo esc_html($noticia['extracto']); ?></p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
