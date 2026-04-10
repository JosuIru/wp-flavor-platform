<?php
/**
 * Componente: Card de Programa de Radio
 *
 * @package FlavorPlatform
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$programa = $item ?? $card_item ?? [];
if (empty($programa)) return;

$id = $programa['id'] ?? 0;
$titulo = $programa['titulo'] ?? $programa['title'] ?? '';
$url = $programa['url'] ?? '#';
$descripcion = $programa['descripcion'] ?? '';
$imagen = $programa['imagen'] ?? 'https://picsum.photos/seed/radio' . ($id ?: rand(1, 100)) . '/400/225';
$horario = $programa['horario'] ?? '';
$locutor = $programa['locutor'] ?? '';
$categoria = $programa['categoria'] ?? '';
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="relative aspect-[16/9] overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        <div class="absolute bottom-3 left-3 text-white">
            <p class="font-bold"><?php echo esc_html($titulo); ?></p>
            <?php if ($horario): ?>
            <p class="text-sm text-white/80"><?php echo esc_html($horario); ?></p>
            <?php endif; ?>
        </div>

        <!-- Botón de reproducción -->
        <div class="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity">
            <button class="w-10 h-10 bg-red-600 text-white rounded-full flex items-center justify-center shadow-lg hover:bg-red-700 transition-colors">
                ▶️
            </button>
        </div>
    </div>

    <div class="p-5">
        <?php if ($locutor): ?>
        <p class="text-sm text-gray-500 mb-2">🎙️ <?php echo esc_html($locutor); ?></p>
        <?php endif; ?>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <a href="<?php echo esc_url($url); ?>"
           class="text-red-600 font-medium text-sm hover:text-red-700 transition-colors">
            <?php echo esc_html__('Ver programa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
        </a>
    </div>
</article>
