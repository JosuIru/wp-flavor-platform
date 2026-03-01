<?php
/**
 * Componente: Card de Compostera
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$compostera = $item ?? $card_item ?? [];
if (empty($compostera)) return;

$id = $compostera['id'] ?? 0;
$nombre = $compostera['nombre'] ?? __('Compostera', 'flavor-chat-ia');
$url = $compostera['url'] ?? '#';
$ubicacion = $compostera['ubicacion'] ?? '';
$estado = $compostera['estado'] ?? 'activa';
$capacidad = $compostera['capacidad'] ?? 65;
$proxima_recogida = $compostera['proxima_recogida'] ?? '';
$usuarios = $compostera['usuarios'] ?? 0;

$colores_estado = [
    'activa'         => 'bg-green-100 text-green-700',
    'llena'          => 'bg-amber-100 text-amber-700',
    'mantenimiento'  => 'bg-red-100 text-red-700',
];
$clase_estado = $colores_estado[$estado] ?? $colores_estado['activa'];
$color_barra = $capacidad > 80 ? 'bg-amber-500' : 'bg-green-500';
?>

<article class="group bg-white rounded-2xl shadow-md hover:shadow-xl transition-all duration-300 overflow-hidden border border-gray-100"
         data-estado="<?php echo esc_attr($estado); ?>">
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                <a href="<?php echo esc_url($url); ?>">
                    <?php echo esc_html($nombre); ?>
                </a>
            </h2>
            <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo esc_attr($clase_estado); ?>">
                <?php echo esc_html(ucfirst($estado)); ?>
            </span>
        </div>

        <!-- Ubicación -->
        <?php if ($ubicacion): ?>
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            📍 <span><?php echo esc_html($ubicacion); ?></span>
        </div>
        <?php endif; ?>

        <!-- Barra de capacidad -->
        <div class="mb-4">
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600"><?php echo esc_html__('Capacidad', 'flavor-chat-ia'); ?></span>
                <span class="font-bold <?php echo $capacidad > 80 ? 'text-amber-600' : 'text-green-600'; ?>">
                    <?php echo esc_html($capacidad); ?>%
                </span>
            </div>
            <div class="w-full h-2 bg-gray-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all <?php echo esc_attr($color_barra); ?>"
                     style="width: <?php echo esc_attr($capacidad); ?>%"></div>
            </div>
        </div>

        <!-- Info adicional -->
        <div class="space-y-2 text-sm text-gray-500">
            <?php if ($proxima_recogida): ?>
            <div class="flex items-center gap-2">
                📅 <span><?php echo esc_html__('Próxima recogida:', 'flavor-chat-ia'); ?> <?php echo esc_html($proxima_recogida); ?></span>
            </div>
            <?php endif; ?>
            <div class="flex items-center gap-2">
                👥 <span><?php echo esc_html($usuarios); ?> <?php echo esc_html__('usuarios', 'flavor-chat-ia'); ?></span>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100">
            <a href="<?php echo esc_url($url); ?>"
               class="block w-full py-2 rounded-xl text-center text-green-600 font-semibold text-sm bg-green-50 hover:bg-green-100 transition-colors">
                <?php echo esc_html__('Ver compostera', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </div>
</article>
