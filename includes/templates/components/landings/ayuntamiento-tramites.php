<?php
/**
 * Template: Trámites Ayuntamiento
 * @package FlavorChatIA
 */
if (!defined('ABSPATH')) exit;
$limite = $limite ?? 6;

$tramites = [
    ['nombre' => 'Empadronamiento', 'icono' => '📋', 'url' => '/ayuntamiento/tramite/empadronamiento/'],
    ['nombre' => 'Certificado de residencia', 'icono' => '📄', 'url' => '/ayuntamiento/tramite/certificado-residencia/'],
    ['nombre' => 'Pago de tributos', 'icono' => '💰', 'url' => '/ayuntamiento/tramite/pago-tributos/'],
    ['nombre' => 'Licencia de obras', 'icono' => '🏗️', 'url' => '/ayuntamiento/tramite/licencia-obras/'],
    ['nombre' => 'Registro civil', 'icono' => '📝', 'url' => '/ayuntamiento/tramite/registro-civil/'],
    ['nombre' => 'Cita previa', 'icono' => '📅', 'url' => '/ayuntamiento/cita-previa/'],
];
?>
<section class="<?php echo esc_attr($component_classes ?? ''); ?> py-16 bg-gray-50">
    <div class="max-w-6xl mx-auto px-6">
        <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center"><?php echo esc_html($titulo ?? 'Trámites más solicitados'); ?></h2>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            <?php foreach (array_slice($tramites, 0, $limite) as $tramite): ?>
            <a href="<?php echo esc_url(home_url($tramite['url'])); ?>"
               class="bg-white rounded-xl p-6 shadow-sm border border-gray-100 hover:shadow-md hover:border-blue-200 transition-all text-center group">
                <div class="text-3xl mb-3"><?php echo esc_html($tramite['icono']); ?></div>
                <p class="text-sm font-medium text-gray-700 group-hover:text-blue-600"><?php echo esc_html($tramite['nombre']); ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-8">
            <a href="<?php echo esc_url(home_url('/ayuntamiento/tramites/')); ?>" class="inline-block bg-blue-700 text-white px-8 py-3 rounded-xl font-semibold hover:bg-blue-800 transition-colors">
                Ver todos los trámites
            </a>
        </div>
    </div>
</section>
