<?php
/**
 * Componente: Card de Elemento Multimedia
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$elemento = $item ?? $card_item ?? [];
if (empty($elemento)) return;

$id = $elemento['id'] ?? 0;
$titulo = $elemento['titulo'] ?? $elemento['title'] ?? __('Contenido multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN);
$url = $elemento['url'] ?? '#';
$tipo = $elemento['tipo'] ?? 'video';
$autor = $elemento['autor'] ?? __('Autor', FLAVOR_PLATFORM_TEXT_DOMAIN);
$vistas = $elemento['vistas'] ?? 0;
$fecha = $elemento['fecha'] ?? '';
$duracion = $elemento['duracion'] ?? '';
$thumbnail = $elemento['thumbnail'] ?? $elemento['imagen'] ?? '';

$gradientes_tipo = [
    'video' => 'from-indigo-400 to-indigo-600',
    'foto'  => 'from-purple-400 to-indigo-500',
    'audio' => 'from-indigo-500 to-indigo-700',
];
$gradiente = $gradientes_tipo[$tipo] ?? $gradientes_tipo['video'];
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
         data-tipo="<?php echo esc_attr($tipo); ?>">
    <!-- Thumbnail -->
    <div class="relative aspect-video overflow-hidden">
        <?php if ($thumbnail): ?>
        <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
        <?php else: ?>
        <div class="w-full h-full bg-gradient-to-br <?php echo esc_attr($gradiente); ?> flex items-center justify-center">
            <?php if ($tipo === 'video'): ?>
            <div class="w-16 h-16 rounded-full bg-white/20 flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition-transform">
                ▶️
            </div>
            <?php elseif ($tipo === 'foto'): ?>
            <span class="text-4xl">📷</span>
            <?php else: ?>
            <span class="text-4xl">🎵</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Badge de tipo -->
        <span class="absolute top-3 left-3 px-2 py-1 rounded-full text-xs font-bold bg-white/90 text-indigo-700">
            <?php echo esc_html(ucfirst($tipo)); ?>
        </span>

        <!-- Duración -->
        <?php if ($duracion && ($tipo === 'video' || $tipo === 'audio')): ?>
        <span class="absolute bottom-3 right-3 px-2 py-1 rounded text-xs font-bold bg-black/70 text-white">
            <?php echo esc_html($duracion); ?>
        </span>
        <?php endif; ?>
    </div>

    <div class="p-5">
        <h2 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition-colors mb-2">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h2>

        <div class="flex items-center gap-3 text-sm text-gray-500">
            <span class="flex items-center gap-1">
                👤 <?php echo esc_html($autor); ?>
            </span>
            <span class="flex items-center gap-1">
                👁️ <?php echo esc_html($vistas); ?>
            </span>
            <?php if ($fecha): ?>
            <span><?php echo esc_html($fecha); ?></span>
            <?php endif; ?>
        </div>
    </div>
</article>
