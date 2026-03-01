<?php
/**
 * Componente: Card de Propuesta de Participación
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$propuesta = $item ?? $card_item ?? [];
if (empty($propuesta)) return;

$id = $propuesta['id'] ?? 0;
$titulo = $propuesta['titulo'] ?? $propuesta['title'] ?? '';
$descripcion = $propuesta['descripcion'] ?? '';
$url = $propuesta['url'] ?? '#';
$estado = $propuesta['estado'] ?? 'abierta';
$fecha = $propuesta['fecha'] ?? '';
$autor = $propuesta['autor'] ?? __('Anónimo', 'flavor-chat-ia');
$autor_inicial = mb_substr($autor, 0, 1);
$categoria = $propuesta['categoria'] ?? '';
$comentarios = $propuesta['comentarios'] ?? 0;
$votos_favor = intval($propuesta['votos_favor'] ?? 0);
$votos_contra = intval($propuesta['votos_contra'] ?? 0);
$votos_totales = $votos_favor + $votos_contra;
$porcentaje_favor = $votos_totales > 0 ? round(($votos_favor / $votos_totales) * 100) : 0;

$colores_estado = [
    'abierta'   => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
    'en-debate' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    'votacion'  => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
    'aprobada'  => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
    'rechazada' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
];
$color_estado = $colores_estado[$estado] ?? $colores_estado['abierta'];
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col"
         data-estado="<?php echo esc_attr($estado); ?>"
         data-categoria="<?php echo esc_attr(sanitize_title($categoria)); ?>">
    <div class="p-5 flex-1">
        <div class="flex items-center justify-between mb-3">
            <span class="inline-flex items-center <?php echo esc_attr($color_estado['bg']); ?> <?php echo esc_attr($color_estado['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                <?php echo esc_html(ucfirst(str_replace('-', ' ', $estado))); ?>
            </span>
            <?php if ($fecha): ?>
            <span class="text-xs text-gray-400"><?php echo esc_html($fecha); ?></span>
            <?php endif; ?>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">
            <a href="<?php echo esc_url($url); ?>" class="hover:text-amber-600 transition-colors">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>

        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <!-- Autor -->
        <div class="flex items-center gap-2 mb-4">
            <div class="w-6 h-6 rounded-full bg-amber-200 flex items-center justify-center text-xs font-bold text-amber-700">
                <?php echo esc_html($autor_inicial); ?>
            </div>
            <span class="text-sm text-gray-500"><?php echo esc_html($autor); ?></span>
        </div>

        <!-- Barra de votos -->
        <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span><?php echo esc_html($votos_favor); ?> <?php echo esc_html__('a favor', 'flavor-chat-ia'); ?></span>
                <span><?php echo esc_html($votos_contra); ?> <?php echo esc_html__('en contra', 'flavor-chat-ia'); ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-amber-500 to-orange-500 h-2 rounded-full" style="width: <?php echo esc_attr($porcentaje_favor); ?>%"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <?php if ($categoria): ?>
        <span class="text-xs text-gray-500"><?php echo esc_html($categoria); ?></span>
        <?php endif; ?>
        <span class="text-xs text-gray-500 flex items-center gap-1">
            💬 <?php echo esc_html($comentarios); ?>
        </span>
    </div>
</article>
