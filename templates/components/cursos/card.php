<?php
/**
 * Componente: Card de Curso
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$curso = $item ?? $card_item ?? [];
if (empty($curso)) return;

$id = $curso['id'] ?? 0;
$titulo = $curso['titulo'] ?? $curso['title'] ?? '';
$descripcion = $curso['descripcion'] ?? '';
$url = $curso['url'] ?? '#';
$imagen = $curso['imagen'] ?? 'https://picsum.photos/seed/curso' . $id . '/600/340';
$fecha = $curso['fecha'] ?? '';
$categoria = $curso['categoria'] ?? __('General', 'flavor-chat-ia');
$instructor = $curso['instructor'] ?? __('Instructor', 'flavor-chat-ia');
$instructor_avatar = $curso['instructor_avatar'] ?? 'https://i.pravatar.cc/150?img=' . ($id % 70 + 1);
$precio = $curso['precio'] ?? '25€';
$gratuito = !empty($curso['gratuito']) || $precio == 0 || $precio === 'Gratis';
$plazas_limitadas = !empty($curso['plazas_limitadas']);
$nivel = $curso['nivel'] ?? '';
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="relative aspect-[16/9] overflow-hidden">
        <img src="<?php echo esc_url($imagen); ?>"
             alt="<?php echo esc_attr($titulo); ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>

        <!-- Fecha -->
        <?php if ($fecha): ?>
        <div class="absolute bottom-3 left-3 flex items-center gap-2 text-white text-sm">
            📅 <?php echo esc_html($fecha); ?>
        </div>
        <?php endif; ?>

        <!-- Badge -->
        <?php if ($gratuito): ?>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                <?php echo esc_html__('Gratuito', 'flavor-chat-ia'); ?>
            </span>
        <?php elseif ($plazas_limitadas): ?>
            <span class="absolute top-3 right-3 px-2 py-1 rounded-full text-xs font-bold bg-orange-500 text-white">
                <?php echo esc_html__('Últimas plazas', 'flavor-chat-ia'); ?>
            </span>
        <?php endif; ?>
    </div>

    <div class="p-5">
        <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-purple-100 text-purple-700 mb-2">
            <?php echo esc_html($categoria); ?>
        </span>

        <h2 class="text-lg font-bold text-gray-900 group-hover:text-purple-600 transition-colors mb-2">
            <a href="<?php echo esc_url($url); ?>">
                <?php echo esc_html($titulo); ?>
            </a>
        </h2>

        <?php if ($descripcion): ?>
        <p class="text-sm text-gray-600 mb-3 line-clamp-2">
            <?php echo esc_html($descripcion); ?>
        </p>
        <?php endif; ?>

        <!-- Instructor -->
        <div class="flex items-center gap-2 mb-4">
            <img src="<?php echo esc_url($instructor_avatar); ?>"
                 alt="" class="w-6 h-6 rounded-full object-cover">
            <span class="text-sm text-gray-500"><?php echo esc_html($instructor); ?></span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-lg font-bold <?php echo $gratuito ? 'text-green-600' : 'text-purple-600'; ?>">
                <?php echo $gratuito ? __('Gratis', 'flavor-chat-ia') : esc_html($precio); ?>
            </span>
            <a href="<?php echo esc_url($url); ?>"
               class="px-4 py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105 bg-gradient-to-r from-purple-600 to-violet-600">
                <?php echo esc_html__('Inscribirse', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</article>
