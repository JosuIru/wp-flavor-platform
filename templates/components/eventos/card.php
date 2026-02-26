<?php
/**
 * Componente: Card de Evento
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$evento = $item ?? $card_item ?? [];
if (empty($evento)) return;

$id = $evento['id'] ?? 0;
$titulo = $evento['titulo'] ?? $evento['title'] ?? '';
$descripcion = $evento['descripcion'] ?? '';
$url = $evento['url'] ?? '#';
$dia = $evento['dia'] ?? '01';
$mes = $evento['mes'] ?? 'Ene';
$hora = $evento['hora'] ?? '';
$ubicacion = $evento['ubicacion'] ?? __('Por confirmar', 'flavor-chat-ia');
$asistentes = $evento['asistentes'] ?? 0;
$precio = $evento['precio'] ?? 0;
$categoria = $evento['categoria'] ?? '';
$imagen = $evento['imagen'] ?? '';

$precio_label = $precio == 0 ? __('Gratis', 'flavor-chat-ia') : $precio . ' €';
$precio_class = $precio == 0 ? 'bg-green-100 text-green-700' : 'bg-rose-100 text-rose-700';
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all overflow-hidden border border-gray-100 group"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-5">
        <!-- Fecha y contenido -->
        <div class="flex items-start gap-4 mb-4">
            <div class="bg-rose-100 text-rose-700 rounded-xl p-3 text-center min-w-[60px] flex-shrink-0">
                <p class="text-xs font-medium uppercase"><?php echo esc_html($mes); ?></p>
                <p class="text-2xl font-bold leading-none"><?php echo esc_html($dia); ?></p>
            </div>
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-gray-800 mb-1 group-hover:text-pink-600 transition-colors">
                    <a href="<?php echo esc_url($url); ?>"><?php echo esc_html($titulo); ?></a>
                </h3>
                <?php if ($descripcion): ?>
                <p class="text-gray-600 text-sm line-clamp-2"><?php echo esc_html($descripcion); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Meta badges -->
        <div class="flex flex-wrap gap-2 mb-3">
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                📍 <?php echo esc_html($ubicacion); ?>
            </span>
            <?php if ($hora): ?>
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                🕐 <?php echo esc_html($hora); ?>
            </span>
            <?php endif; ?>
            <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full flex items-center gap-1">
                👥 <?php echo esc_html($asistentes); ?> asistentes
            </span>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between pt-3 border-t border-gray-100">
            <span class="<?php echo esc_attr($precio_class); ?> text-xs font-medium px-3 py-1 rounded-full">
                <?php echo esc_html($precio_label); ?>
            </span>
            <a href="<?php echo esc_url($url); ?>" class="bg-rose-500 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-rose-600 transition-colors">
                <?php echo esc_html__('Inscribirse', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</article>
