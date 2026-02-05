<?php
/**
 * Template: Features Ofimática
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$caracteristicas = [
    ['icono' => '🔄', 'titulo' => 'Sincronización', 'descripcion' => 'Accede a tus documentos desde cualquier dispositivo con sincronización automática'],
    ['icono' => '👥', 'titulo' => 'Colaboración', 'descripcion' => 'Trabaja en equipo en tiempo real con edición simultánea'],
    ['icono' => '🔒', 'titulo' => 'Seguridad', 'descripcion' => 'Tus datos protegidos con encriptación de nivel empresarial'],
    ['icono' => '📱', 'titulo' => 'Multiplataforma', 'descripcion' => 'Disponible en web, escritorio y móvil'],
    ['icono' => '🔌', 'titulo' => 'Integraciones', 'descripcion' => 'Conecta con tus herramientas favoritas fácilmente'],
    ['icono' => '📈', 'titulo' => 'Análisis', 'descripcion' => 'Informes y métricas de productividad del equipo'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20 bg-gradient-to-b from-white to-sky-50">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Características Principales'); ?></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Herramientas diseñadas para maximizar tu productividad</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php foreach ($caracteristicas as $caracteristica): ?>
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 hover:shadow-lg hover:border-sky-200 transition-all">
                <div class="w-16 h-16 bg-sky-100 rounded-2xl flex items-center justify-center text-3xl mb-6">
                    <?php echo $caracteristica['icono']; ?>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-3"><?php echo esc_html($caracteristica['titulo']); ?></h3>
                <p class="text-gray-600"><?php echo esc_html($caracteristica['descripcion']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
