<?php
/**
 * Template: Testimonios Empresariales
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;

$testimonios = [
    ['nombre' => 'María García', 'cargo' => 'CEO', 'empresa' => 'TechCorp', 'texto' => 'Excelente servicio y atención personalizada. Han transformado nuestra operativa.', 'avatar' => '👩‍💼'],
    ['nombre' => 'Carlos López', 'cargo' => 'CTO', 'empresa' => 'InnovaLab', 'texto' => 'La mejor inversión que hemos hecho. Resultados visibles desde el primer mes.', 'avatar' => '👨‍💻'],
    ['nombre' => 'Ana Martínez', 'cargo' => 'Directora', 'empresa' => 'GlobalTech', 'texto' => 'Profesionales excepcionales. Siempre disponibles y proactivos.', 'avatar' => '👩‍🔬'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-20 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4"><?php echo esc_html($titulo ?? 'Lo que dicen nuestros clientes'); ?></h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php foreach ($testimonios as $testimonio): ?>
            <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100">
                <div class="flex items-center gap-1 text-yellow-400 mb-4">
                    ⭐⭐⭐⭐⭐
                </div>
                <p class="text-gray-600 mb-6 italic">"<?php echo esc_html($testimonio['texto']); ?>"</p>
                <div class="flex items-center gap-4">
                    <div class="text-4xl"><?php echo $testimonio['avatar']; ?></div>
                    <div>
                        <div class="font-semibold text-gray-800"><?php echo esc_html($testimonio['nombre']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo esc_html($testimonio['cargo']); ?>, <?php echo esc_html($testimonio['empresa']); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
