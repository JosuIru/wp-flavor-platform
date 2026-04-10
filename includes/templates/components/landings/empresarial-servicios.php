<?php
/**
 * Template: Servicios Empresariales
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;
$columnas = $columnas ?? 3;

$servicios = [
    ['icono' => '💼', 'nombre' => 'Consultoría', 'descripcion' => 'Asesoramiento estratégico para tu negocio'],
    ['icono' => '📊', 'nombre' => 'Analítica', 'descripcion' => 'Datos e insights para tomar decisiones'],
    ['icono' => '🔧', 'nombre' => 'Desarrollo', 'descripcion' => 'Soluciones tecnológicas a medida'],
    ['icono' => '🎯', 'nombre' => 'Marketing', 'descripcion' => 'Estrategias de crecimiento digital'],
    ['icono' => '🛡️', 'nombre' => 'Seguridad', 'descripcion' => 'Protección de datos y sistemas'],
    ['icono' => '☁️', 'nombre' => 'Cloud', 'descripcion' => 'Infraestructura en la nube'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20 bg-gray-50" id="servicios">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Nuestros Servicios'); ?></h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Soluciones integrales diseñadas para impulsar tu negocio</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-<?php echo esc_attr($columnas); ?> gap-8">
            <?php foreach ($servicios as $servicio): ?>
            <div class="bg-white rounded-2xl p-8 shadow-sm hover:shadow-xl transition-all duration-300 border border-gray-100 group">
                <div class="text-4xl mb-4 group-hover:scale-110 transition-transform"><?php echo $servicio['icono']; ?></div>
                <h3 class="text-xl font-bold text-gray-800 mb-2"><?php echo esc_html($servicio['nombre']); ?></h3>
                <p class="text-gray-600"><?php echo esc_html($servicio['descripcion']); ?></p>
                <a href="#" class="inline-flex items-center text-blue-600 font-medium mt-4 group-hover:text-blue-700">
                    Más información
                    <svg class="w-4 h-4 ml-1 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
