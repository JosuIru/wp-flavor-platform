<?php
/**
 * Componente: Card de Proyecto de Presupuesto Participativo
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$proyecto = $item ?? $card_item ?? [];
if (empty($proyecto)) return;

$id = $proyecto['id'] ?? 0;
$titulo = $proyecto['titulo'] ?? $proyecto['title'] ?? '';
$url = $proyecto['url'] ?? '#';
$descripcion = $proyecto['descripcion'] ?? '';
$fase = $proyecto['fase'] ?? 'propuestas';
$presupuesto = $proyecto['presupuesto'] ?? '0';
$categoria = $proyecto['categoria'] ?? '';
$distrito = $proyecto['distrito'] ?? '';
$votos = intval($proyecto['votos'] ?? 0);
$umbral = intval($proyecto['umbral'] ?? 100);
$porcentaje = $umbral > 0 ? min(100, round(($votos / $umbral) * 100)) : 0;

$colores_fase = [
    'propuestas'  => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    'evaluacion'  => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
    'votacion'    => ['bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
    'ejecucion'   => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
    'completado'  => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
];
$color = $colores_fase[$fase] ?? $colores_fase['propuestas'];
?>

<article class="bg-white rounded-2xl shadow-sm hover:shadow-md transition-all border border-gray-100 overflow-hidden flex flex-col"
         data-fase="<?php echo esc_attr($fase); ?>">
    <div class="p-5 flex-1">
        <div class="flex items-center justify-between mb-3">
            <span class="<?php echo esc_attr($color['bg']); ?> <?php echo esc_attr($color['text']); ?> px-3 py-1 rounded-full text-xs font-medium">
                <?php echo esc_html(ucfirst($fase)); ?>
            </span>
            <span class="text-lg font-bold text-amber-600"><?php echo esc_html($presupuesto); ?></span>
        </div>

        <h3 class="text-lg font-semibold text-gray-800 mb-2">
            <a href="<?php echo esc_url($url); ?>" class="hover:text-amber-600 transition-colors">
                <?php echo esc_html($titulo); ?>
            </a>
        </h3>

        <?php if ($descripcion): ?>
        <p class="text-gray-600 text-sm mb-4 line-clamp-3"><?php echo esc_html($descripcion); ?></p>
        <?php endif; ?>

        <?php if ($categoria): ?>
        <span class="inline-block bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs mb-3">
            <?php echo esc_html($categoria); ?>
        </span>
        <?php endif; ?>

        <!-- Barra de progreso de votos -->
        <div>
            <div class="flex justify-between text-xs text-gray-500 mb-1">
                <span><?php echo esc_html($votos); ?> <?php echo esc_html__('votos', 'flavor-chat-ia'); ?></span>
                <span><?php echo esc_html__('Umbral:', 'flavor-chat-ia'); ?> <?php echo esc_html($umbral); ?></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-amber-500 to-yellow-500 h-2 rounded-full" style="width: <?php echo esc_attr($porcentaje); ?>%"></div>
            </div>
        </div>
    </div>

    <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
        <?php if ($distrito): ?>
        <span class="text-xs text-gray-500">📍 <?php echo esc_html($distrito); ?></span>
        <?php else: ?>
        <span></span>
        <?php endif; ?>
        <a href="<?php echo esc_url($url); ?>" class="text-amber-600 text-sm font-medium hover:text-amber-700 transition-colors">
            <?php echo esc_html__('Ver proyecto', 'flavor-chat-ia'); ?> →
        </a>
    </div>
</article>
