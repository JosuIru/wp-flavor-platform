<?php
/**
 * Componente: Card de Podcast
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$podcast = $item ?? $card_item ?? [];
if (empty($podcast)) return;

$id = $podcast['id'] ?? 0;
$titulo = $podcast['titulo'] ?? $podcast['title'] ?? '';
$descripcion = $podcast['descripcion'] ?? '';
$url = $podcast['url'] ?? '#';
$portada = $podcast['portada'] ?? $podcast['imagen'] ?? 'https://picsum.photos/seed/podcast' . $id . '/400/400';
$autor = $podcast['autor'] ?? __('Creador', FLAVOR_PLATFORM_TEXT_DOMAIN);
$episodios = $podcast['episodios'] ?? 0;
$reproducciones = $podcast['reproducciones'] ?? 0;
$suscriptores = $podcast['suscriptores'] ?? 0;
$categoria = $podcast['categoria'] ?? '';
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="relative aspect-square overflow-hidden">
        <img src="<?php echo esc_url($portada); ?>"
             alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

        <!-- Botón play -->
        <button class="absolute bottom-4 right-4 w-12 h-12 rounded-full bg-teal-500 text-white flex items-center justify-center shadow-lg hover:bg-teal-600 transition-colors">
            ▶️
        </button>

        <!-- Episodios -->
        <span class="absolute top-3 left-3 px-2 py-1 rounded-full text-xs font-bold bg-black/50 text-white">
            <?php echo esc_html($episodios); ?> <?php echo esc_html__('episodios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </span>
    </div>

    <div class="p-5">
        <h2 class="text-lg font-bold text-gray-900 group-hover:text-teal-600 transition-colors mb-1">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h2>
        <p class="text-sm text-gray-500 mb-2"><?php echo esc_html($autor); ?></p>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 line-clamp-2 mb-3"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <div class="flex items-center gap-4 text-sm text-gray-500">
            <span class="flex items-center gap-1">
                👁️ <?php echo esc_html($reproducciones); ?>
            </span>
            <span class="flex items-center gap-1">
                ❤️ <?php echo esc_html($suscriptores); ?>
            </span>
        </div>
    </div>
</article>
