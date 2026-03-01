<?php
/**
 * Componente: Step Indicator
 *
 * Indicador de pasos/etapas para procesos multi-paso.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $steps        Array de pasos: [['label' => '', 'description' => '', 'icon' => '']]
 * @param int    $current      Paso actual (1-indexed)
 * @param string $variant      Variante: horizontal, vertical, dots, numbered
 * @param bool   $clickable    Pasos clickeables
 * @param bool   $show_labels  Mostrar etiquetas
 * @param string $size         Tamaño: sm, md, lg
 * @param string $color        Color: blue, green, purple
 */

if (!defined('ABSPATH')) {
    exit;
}

$steps = $steps ?? [];
$current = intval($current ?? 1);
$variant = $variant ?? 'horizontal';
$clickable = $clickable ?? false;
$show_labels = $show_labels ?? true;
$size = $size ?? 'md';
$color = $color ?? 'blue';

$total_steps = count($steps);

// Colores
$color_config = [
    'blue'   => ['active' => 'bg-blue-600 text-white', 'done' => 'bg-blue-600 text-white', 'pending' => 'bg-gray-200 text-gray-500', 'line_done' => 'bg-blue-600', 'line_pending' => 'bg-gray-200'],
    'green'  => ['active' => 'bg-green-600 text-white', 'done' => 'bg-green-600 text-white', 'pending' => 'bg-gray-200 text-gray-500', 'line_done' => 'bg-green-600', 'line_pending' => 'bg-gray-200'],
    'purple' => ['active' => 'bg-purple-600 text-white', 'done' => 'bg-purple-600 text-white', 'pending' => 'bg-gray-200 text-gray-500', 'line_done' => 'bg-purple-600', 'line_pending' => 'bg-gray-200'],
];
$col = $color_config[$color] ?? $color_config['blue'];

// Tamaños
$size_config = [
    'sm' => ['circle' => 'w-8 h-8 text-sm', 'icon' => 'w-4 h-4', 'line' => 'h-0.5', 'text' => 'text-xs'],
    'md' => ['circle' => 'w-10 h-10 text-base', 'icon' => 'w-5 h-5', 'line' => 'h-1', 'text' => 'text-sm'],
    'lg' => ['circle' => 'w-12 h-12 text-lg', 'icon' => 'w-6 h-6', 'line' => 'h-1', 'text' => 'text-base'],
];
$sz = $size_config[$size] ?? $size_config['md'];

$step_id = 'flavor-steps-' . wp_rand(1000, 9999);
?>

<?php if ($variant === 'dots'): ?>
    <!-- Variante Dots (minimalista) -->
    <div class="flavor-step-indicator flex items-center justify-center gap-2" id="<?php echo esc_attr($step_id); ?>">
        <?php foreach ($steps as $index => $step): ?>
            <?php
            $step_num = $index + 1;
            $is_done = $step_num < $current;
            $is_active = $step_num === $current;
            $dot_class = $is_done || $is_active ? str_replace('bg-', 'bg-', $col['active']) : $col['pending'];
            $dot_size = $is_active ? 'w-3 h-3' : 'w-2 h-2';
            ?>
            <button type="button"
                    class="<?php echo esc_attr($dot_size); ?> rounded-full transition-all <?php echo esc_attr($dot_class); ?> <?php echo $clickable ? 'cursor-pointer hover:opacity-80' : 'cursor-default'; ?>"
                    data-step="<?php echo $step_num; ?>"
                    title="<?php echo esc_attr($step['label'] ?? ''); ?>"
                    <?php echo !$clickable ? 'disabled' : ''; ?>>
            </button>
        <?php endforeach; ?>
    </div>

<?php elseif ($variant === 'vertical'): ?>
    <!-- Variante Vertical -->
    <div class="flavor-step-indicator" id="<?php echo esc_attr($step_id); ?>">
        <?php foreach ($steps as $index => $step): ?>
            <?php
            $step_num = $index + 1;
            $is_done = $step_num < $current;
            $is_active = $step_num === $current;
            $is_last = $step_num === $total_steps;

            $circle_class = $is_done ? $col['done'] : ($is_active ? $col['active'] : $col['pending']);
            $line_class = $is_done ? $col['line_done'] : $col['line_pending'];
            ?>
            <div class="flex <?php echo $clickable ? 'cursor-pointer' : ''; ?>" data-step="<?php echo $step_num; ?>">
                <!-- Círculo y línea -->
                <div class="flex flex-col items-center mr-4">
                    <div class="<?php echo esc_attr($sz['circle']); ?> rounded-full flex items-center justify-center font-medium <?php echo esc_attr($circle_class); ?>">
                        <?php if ($is_done): ?>
                            <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        <?php elseif (!empty($step['icon'])): ?>
                            <span><?php echo esc_html($step['icon']); ?></span>
                        <?php else: ?>
                            <?php echo $step_num; ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!$is_last): ?>
                        <div class="w-0.5 h-full min-h-[40px] <?php echo esc_attr($line_class); ?>"></div>
                    <?php endif; ?>
                </div>

                <!-- Contenido -->
                <?php if ($show_labels): ?>
                    <div class="pb-8 <?php echo !$is_last ? '' : 'pb-0'; ?>">
                        <p class="font-medium <?php echo $is_active ? 'text-gray-900' : 'text-gray-600'; ?> <?php echo esc_attr($sz['text']); ?>">
                            <?php echo esc_html($step['label'] ?? ''); ?>
                        </p>
                        <?php if (!empty($step['description'])): ?>
                            <p class="text-gray-400 text-xs mt-0.5"><?php echo esc_html($step['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <!-- Variante Horizontal (default) / Numbered -->
    <div class="flavor-step-indicator" id="<?php echo esc_attr($step_id); ?>">
        <div class="flex items-center">
            <?php foreach ($steps as $index => $step): ?>
                <?php
                $step_num = $index + 1;
                $is_done = $step_num < $current;
                $is_active = $step_num === $current;
                $is_last = $step_num === $total_steps;

                $circle_class = $is_done ? $col['done'] : ($is_active ? $col['active'] : $col['pending']);
                $line_class = $is_done ? $col['line_done'] : $col['line_pending'];
                ?>

                <!-- Step circle -->
                <div class="relative flex flex-col items-center <?php echo $clickable ? 'cursor-pointer group' : ''; ?>" data-step="<?php echo $step_num; ?>">
                    <div class="<?php echo esc_attr($sz['circle']); ?> rounded-full flex items-center justify-center font-medium transition-all <?php echo esc_attr($circle_class); ?> <?php echo $clickable ? 'group-hover:scale-110' : ''; ?>">
                        <?php if ($is_done): ?>
                            <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        <?php elseif (!empty($step['icon'])): ?>
                            <span><?php echo esc_html($step['icon']); ?></span>
                        <?php else: ?>
                            <?php echo $step_num; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($show_labels): ?>
                        <div class="absolute top-full mt-2 text-center whitespace-nowrap">
                            <p class="font-medium <?php echo $is_active ? 'text-gray-900' : 'text-gray-500'; ?> <?php echo esc_attr($sz['text']); ?>">
                                <?php echo esc_html($step['label'] ?? ''); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Line -->
                <?php if (!$is_last): ?>
                    <div class="flex-1 mx-2 <?php echo esc_attr($sz['line']); ?> <?php echo esc_attr($line_class); ?> rounded"></div>
                <?php endif; ?>

            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php if ($clickable): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('<?php echo esc_js($step_id); ?>');
    if (!container) return;

    container.querySelectorAll('[data-step]').forEach(el => {
        el.addEventListener('click', function() {
            const step = parseInt(this.dataset.step);
            container.dispatchEvent(new CustomEvent('step-click', {
                detail: { step: step },
                bubbles: true
            }));
        });
    });
});
</script>
<?php endif; ?>
