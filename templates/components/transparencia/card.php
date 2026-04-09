<?php
/**
 * Componente: Card de Documento de Transparencia
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$documento = $item ?? $card_item ?? [];
if (empty($documento)) return;

$id = $documento['id'] ?? 0;
$titulo = $documento['titulo'] ?? $documento['title'] ?? '';
$url = $documento['url'] ?? '#';
$categoria = $documento['categoria'] ?? '';
$formato = strtolower($documento['formato'] ?? 'pdf');
$fecha = $documento['fecha'] ?? '';
$tamano = $documento['tamano'] ?? '';
$enlace_descarga = $documento['enlace_descarga'] ?? $url;

$iconos_formato = [
    'pdf'   => ['bg' => 'bg-red-100', 'text' => 'text-red-600', 'label' => 'PDF'],
    'xls'   => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'XLS'],
    'xlsx'  => ['bg' => 'bg-green-100', 'text' => 'text-green-600', 'label' => 'XLS'],
    'csv'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'CSV'],
    'doc'   => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'DOC'],
    'docx'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'label' => 'DOC'],
];
$icono = $iconos_formato[$formato] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600', 'label' => strtoupper($formato)];
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-5 flex-1">
        <div class="flex items-start gap-3 mb-3">
            <div class="w-12 h-12 <?php echo esc_attr($icono['bg']); ?> rounded-xl flex items-center justify-center flex-shrink-0">
                <span class="<?php echo esc_attr($icono['text']); ?> font-bold text-xs"><?php echo esc_html($icono['label']); ?></span>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-800">
                    <a href="<?php echo esc_url($url); ?>" class="hover:text-teal-600 transition-colors">
                        <?php echo esc_html($titulo); ?>
                    </a>
                </h3>
                <?php if ($categoria): ?>
                <span class="text-xs text-gray-500"><?php echo esc_html($categoria); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 mt-4">
            <?php if ($fecha): ?>
            <span class="flex items-center gap-1">
                📅 <?php echo esc_html($fecha); ?>
            </span>
            <?php endif; ?>
            <?php if ($tamano): ?>
            <span class="flex items-center gap-1">
                📁 <?php echo esc_html($tamano); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100">
        <a href="<?php echo esc_url($enlace_descarga); ?>"
           class="w-full inline-flex items-center justify-center gap-2 bg-teal-500 text-white py-2 px-4 rounded-lg font-medium hover:bg-teal-600 transition-colors text-sm">
            ⬇️ <?php echo esc_html__('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>
</article>
