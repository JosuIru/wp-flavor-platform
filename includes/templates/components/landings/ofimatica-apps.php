<?php
/**
 * Template: Apps Ofimática
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$apps = [
    ['icono' => '📝', 'nombre' => 'Docs', 'color' => 'blue', 'descripcion' => 'Procesador de textos con colaboración en tiempo real'],
    ['icono' => '📊', 'nombre' => 'Sheets', 'color' => 'green', 'descripcion' => 'Hojas de cálculo potentes con fórmulas avanzadas'],
    ['icono' => '📽️', 'nombre' => 'Slides', 'color' => 'yellow', 'descripcion' => 'Crea presentaciones impactantes'],
    ['icono' => '📅', 'nombre' => 'Calendar', 'color' => 'red', 'descripcion' => 'Organiza tu agenda y reuniones'],
    ['icono' => '✉️', 'nombre' => 'Mail', 'color' => 'purple', 'descripcion' => 'Correo profesional seguro'],
    ['icono' => '💾', 'nombre' => 'Drive', 'color' => 'sky', 'descripcion' => 'Almacenamiento en la nube ilimitado'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Nuestras Aplicaciones'); ?></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Todo lo que necesitas para trabajar de forma eficiente</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($apps as $app): ?>
            <div class="group bg-white rounded-2xl p-6 border border-gray-100 hover:border-<?php echo $app['color']; ?>-200 hover:shadow-lg transition-all cursor-pointer">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 bg-<?php echo $app['color']; ?>-100 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform">
                        <?php echo $app['icono']; ?>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-gray-800 mb-1"><?php echo esc_html($app['nombre']); ?></h3>
                        <p class="text-gray-600 text-sm"><?php echo esc_html($app['descripcion']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
