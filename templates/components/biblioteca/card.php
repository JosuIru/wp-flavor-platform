<?php
/**
 * Componente: Card de Libro
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$libro = $item ?? $card_item ?? [];
if (empty($libro)) return;

$id = $libro['id'] ?? 0;
$titulo = $libro['titulo'] ?? $libro['title'] ?? '';
$url = $libro['url'] ?? '#';
$autor = $libro['autor'] ?? __('Autor desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN);
$genero = $libro['genero'] ?? 'General';
$portada = $libro['portada'] ?? 'https://picsum.photos/seed/libro' . ($id ?: rand(1, 100)) . '/300/450';
$disponible = !empty($libro['disponible']);
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
         data-genero="<?php echo esc_attr(sanitize_title($genero)); ?>">
    <div class="relative aspect-[2/3] overflow-hidden">
        <img src="<?php echo esc_url($portada); ?>"
             alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">

        <?php if ($disponible): ?>
            <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-bold bg-green-500 text-white">
                <?php echo esc_html__('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        <?php else: ?>
            <span class="absolute top-2 right-2 px-2 py-0.5 rounded-full text-xs font-bold bg-orange-500 text-white">
                <?php echo esc_html__('Prestado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="p-4">
        <h2 class="font-bold text-gray-900 group-hover:text-indigo-600 transition-colors line-clamp-2 mb-1">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h2>
        <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($autor); ?></p>
        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-700">
            <?php echo esc_html($genero); ?>
        </span>
    </div>
</article>
