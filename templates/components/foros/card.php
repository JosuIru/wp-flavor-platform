<?php
/**
 * Componente: Card de Tema de Foro
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$tema = $item ?? $card_item ?? [];
if (empty($tema)) return;

$id = $tema['id'] ?? 0;
$titulo = $tema['titulo'] ?? $tema['title'] ?? '';
$url = $tema['url'] ?? '#';
$autor = $tema['autor'] ?? __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN);
$autor_avatar = $tema['autor_avatar'] ?? 'https://i.pravatar.cc/150?img=' . ($id % 70 + 1);
$respuestas = $tema['respuestas'] ?? 0;
$vistas = $tema['vistas'] ?? 0;
$ultima_respuesta = $tema['ultima_respuesta'] ?? '';
$fijado = !empty($tema['fijado']);
$popular = !empty($tema['popular']);
$categoria = $tema['categoria'] ?? '';
?>

<article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="flex items-start gap-4">
        <!-- Avatar autor -->
        <img src="<?php echo esc_url($autor_avatar); ?>"
             alt="<?php echo esc_attr($autor); ?>"
             class="w-10 h-10 rounded-full object-cover flex-shrink-0">

        <div class="flex-1 min-w-0">
            <div class="flex items-center flex-wrap gap-2 mb-1">
                <?php if ($fijado): ?>
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-indigo-100 text-indigo-700">
                    📌 <?php echo esc_html__('Fijado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <?php endif; ?>
                <?php if ($popular): ?>
                <span class="px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-600">
                    🔥 <?php echo esc_html__('Popular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <?php endif; ?>
            </div>

            <h2 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-1">
                <a href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($titulo); ?>
                </a>
            </h2>

            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                <span class="font-medium text-gray-700"><?php echo esc_html($autor); ?></span>
                <span class="flex items-center gap-1">
                    💬 <?php echo esc_html($respuestas); ?> <?php echo esc_html__('respuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <span class="flex items-center gap-1">
                    👁️ <?php echo esc_html($vistas); ?> <?php echo esc_html__('vistas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </span>
                <?php if ($ultima_respuesta): ?>
                <span><?php echo esc_html($ultima_respuesta); ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</article>
