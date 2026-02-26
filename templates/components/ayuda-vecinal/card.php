<?php
/**
 * Componente: Card de Solicitud Ayuda Vecinal
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$solicitud = $item ?? $card_item ?? [];
if (empty($solicitud)) return;

$id = $solicitud['id'] ?? 0;
$titulo = $solicitud['titulo'] ?? $solicitud['title'] ?? '';
$descripcion = $solicitud['descripcion'] ?? '';
$url = $solicitud['url'] ?? '#';
$autor = $solicitud['autor'] ?? __('Anónimo', 'flavor-chat-ia');
$avatar = $solicitud['avatar'] ?? 'https://i.pravatar.cc/150?img=' . ($id % 70 + 1);
$tiempo = $solicitud['tiempo'] ?? __('Hace 1 hora', 'flavor-chat-ia');
$urgente = !empty($solicitud['urgente']);
$categoria = $solicitud['categoria'] ?? __('General', 'flavor-chat-ia');
$ubicacion = $solicitud['ubicacion'] ?? __('Sin ubicación', 'flavor-chat-ia');
$respuestas = $solicitud['respuestas'] ?? 0;
$tipo = $solicitud['tipo'] ?? 'necesito'; // 'necesito' o 'ofrezco'
?>

<article class="group bg-white rounded-2xl p-5 shadow-md hover:shadow-xl transition-all duration-300 border border-gray-100"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>"
         data-tipo="<?php echo esc_attr($tipo); ?>">
    <div class="flex items-start gap-4">
        <img src="<?php echo esc_url($avatar); ?>"
             alt="<?php echo esc_attr($autor); ?>"
             class="w-12 h-12 rounded-full object-cover flex-shrink-0">

        <div class="flex-1 min-w-0">
            <div class="flex items-center flex-wrap gap-2 mb-1">
                <span class="font-bold text-gray-900"><?php echo esc_html($autor); ?></span>
                <?php if ($urgente): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">
                        <?php echo esc_html__('Urgente', 'flavor-chat-ia'); ?>
                    </span>
                <?php endif; ?>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
                    <?php echo esc_html($categoria); ?>
                </span>
            </div>
            <span class="text-sm text-gray-500"><?php echo esc_html($tiempo); ?></span>

            <h2 class="text-lg font-bold text-gray-900 mt-2 group-hover:text-orange-600 transition-colors">
                <a href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($titulo); ?>
                </a>
            </h2>

            <?php if ($descripcion): ?>
            <p class="text-gray-600 mt-2 line-clamp-2">
                <?php echo esc_html($descripcion); ?>
            </p>
            <?php endif; ?>

            <div class="flex items-center gap-6 mt-4 text-sm text-gray-500">
                <span class="flex items-center gap-1">
                    📍 <?php echo esc_html($ubicacion); ?>
                </span>
                <span class="flex items-center gap-1">
                    💬 <?php echo esc_html($respuestas); ?> <?php echo esc_html__('respuestas', 'flavor-chat-ia'); ?>
                </span>
            </div>
        </div>

        <a href="<?php echo esc_url($url); ?>"
           class="px-4 py-2 rounded-xl text-white font-semibold text-sm transition-all hover:scale-105 flex-shrink-0 bg-gradient-to-r from-orange-500 to-amber-500">
            <?php echo esc_html__('Responder', 'flavor-chat-ia'); ?>
        </a>
    </div>
</article>
