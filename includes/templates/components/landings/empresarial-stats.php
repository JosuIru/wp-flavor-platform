<?php
/**
 * Template: Estadísticas Empresariales
 * @package FlavorPlatform
 */
if (!defined('ABSPATH')) exit;

$stats = [
    ['numero' => '500+', 'label' => 'Clientes Satisfechos', 'icono' => '👥'],
    ['numero' => '15+', 'label' => 'Años de Experiencia', 'icono' => '📅'],
    ['numero' => '98%', 'label' => 'Tasa de Retención', 'icono' => '⭐'],
    ['numero' => '24/7', 'label' => 'Soporte Técnico', 'icono' => '🛟'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16 bg-gradient-to-r from-blue-600 to-blue-800">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-2xl md:text-3xl font-bold text-white text-center mb-12"><?php echo esc_html($titulo ?? 'Nuestros Resultados'); ?></h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <?php foreach ($stats as $stat): ?>
            <div class="text-center">
                <div class="text-3xl mb-2"><?php echo $stat['icono']; ?></div>
                <div class="text-4xl md:text-5xl font-bold text-white mb-2"><?php echo esc_html($stat['numero']); ?></div>
                <div class="text-blue-200"><?php echo esc_html($stat['label']); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
