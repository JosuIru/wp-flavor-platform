<?php
/**
 * Componente: Impact Meter
 *
 * Medidor de impacto/huella con visualización atractiva.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param float  $value       Valor actual
 * @param float  $max         Valor máximo (para escala)
 * @param string $unit        Unidad (kg CO2, litros, árboles, etc.)
 * @param string $label       Etiqueta del indicador
 * @param string $description Descripción del impacto
 * @param string $type        Tipo: positive (verde), negative (rojo), neutral
 * @param string $icon        Emoji o icono
 * @param string $variant     Variante: gauge, thermometer, wave, number
 * @param array  $comparison  Comparación: ['label' => 'vs. media', 'value' => 50, 'better' => true]
 * @param array  $milestones  Hitos: [['value' => 100, 'label' => 'Bronce'], ...]
 */

if (!defined('ABSPATH')) {
    exit;
}

$value = floatval($value ?? 0);
$max = floatval($max ?? 100);
$unit = $unit ?? '';
$label = $label ?? __('Impacto', 'flavor-chat-ia');
$description = $description ?? '';
$type = $type ?? 'positive';
$icon = $icon ?? '🌱';
$variant = $variant ?? 'gauge';
$comparison = $comparison ?? [];
$milestones = $milestones ?? [];

// Porcentaje
$percent = $max > 0 ? min(100, ($value / $max) * 100) : 0;

// Colores según tipo
$type_colors = [
    'positive' => ['gradient' => 'from-green-400 to-emerald-500', 'text' => 'text-green-600', 'bg' => 'bg-green-100', 'ring' => 'ring-green-500'],
    'negative' => ['gradient' => 'from-red-400 to-orange-500', 'text' => 'text-red-600', 'bg' => 'bg-red-100', 'ring' => 'ring-red-500'],
    'neutral'  => ['gradient' => 'from-blue-400 to-indigo-500', 'text' => 'text-blue-600', 'bg' => 'bg-blue-100', 'ring' => 'ring-blue-500'],
];
$colors = $type_colors[$type] ?? $type_colors['neutral'];

$meter_id = 'flavor-impact-' . wp_rand(1000, 9999);
?>

<?php if ($variant === 'number'): ?>
    <!-- Variante Number (solo número grande) -->
    <div class="flavor-impact-meter text-center p-6 <?php echo esc_attr($colors['bg']); ?> rounded-2xl">
        <span class="text-5xl mb-2 block"><?php echo esc_html($icon); ?></span>
        <p class="text-4xl font-bold <?php echo esc_attr($colors['text']); ?>">
            <?php echo esc_html(number_format_i18n($value, $value == floor($value) ? 0 : 1)); ?>
            <span class="text-lg font-normal text-gray-500"><?php echo esc_html($unit); ?></span>
        </p>
        <p class="text-sm text-gray-600 mt-1"><?php echo esc_html($label); ?></p>

        <?php if (!empty($comparison)): ?>
            <div class="mt-4 pt-4 border-t border-gray-200">
                <span class="inline-flex items-center gap-1 text-sm <?php echo ($comparison['better'] ?? false) ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo ($comparison['better'] ?? false) ? '↑' : '↓'; ?>
                    <?php echo esc_html($comparison['label'] ?? ''); ?>
                </span>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($variant === 'thermometer'): ?>
    <!-- Variante Thermometer -->
    <div class="flavor-impact-meter flex items-center gap-6 p-4 bg-white rounded-xl shadow-md">
        <!-- Termómetro -->
        <div class="relative w-8 h-40 bg-gray-200 rounded-full overflow-hidden">
            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t <?php echo esc_attr($colors['gradient']); ?> transition-all duration-1000"
                 style="height: <?php echo $percent; ?>%;"></div>
            <!-- Marcas -->
            <?php for ($i = 0; $i <= 4; $i++): ?>
                <div class="absolute left-full ml-1 w-2 h-px bg-gray-300" style="bottom: <?php echo $i * 25; ?>%;"></div>
            <?php endfor; ?>
            <!-- Bulbo -->
            <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 w-12 h-12 rounded-full bg-gradient-to-br <?php echo esc_attr($colors['gradient']); ?> shadow-lg flex items-center justify-center text-2xl">
                <?php echo esc_html($icon); ?>
            </div>
        </div>

        <!-- Info -->
        <div class="flex-1">
            <p class="text-3xl font-bold text-gray-900">
                <?php echo esc_html(number_format_i18n($value, $value == floor($value) ? 0 : 1)); ?>
                <span class="text-base font-normal text-gray-500"><?php echo esc_html($unit); ?></span>
            </p>
            <p class="text-gray-600 font-medium"><?php echo esc_html($label); ?></p>
            <?php if ($description): ?>
                <p class="text-sm text-gray-500 mt-2"><?php echo esc_html($description); ?></p>
            <?php endif; ?>

            <!-- Milestones -->
            <?php if (!empty($milestones)): ?>
                <div class="mt-4 flex flex-wrap gap-2">
                    <?php foreach ($milestones as $milestone): ?>
                        <?php $achieved = $value >= ($milestone['value'] ?? 0); ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs <?php echo $achieved ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>">
                            <?php echo $achieved ? '✓' : '○'; ?>
                            <?php echo esc_html($milestone['label'] ?? ''); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($variant === 'wave'): ?>
    <!-- Variante Wave (circular con onda) -->
    <div class="flavor-impact-meter relative w-48 h-48 mx-auto">
        <svg class="w-full h-full" viewBox="0 0 200 200">
            <!-- Círculo de fondo -->
            <circle cx="100" cy="100" r="90" fill="none" stroke="#E5E7EB" stroke-width="8"/>
            <!-- Círculo de progreso -->
            <circle cx="100" cy="100" r="90" fill="none" stroke="url(#gradient-<?php echo esc_attr($meter_id); ?>)" stroke-width="8"
                    stroke-linecap="round" transform="rotate(-90 100 100)"
                    stroke-dasharray="<?php echo 2 * pi() * 90; ?>"
                    stroke-dashoffset="<?php echo 2 * pi() * 90 * (1 - $percent / 100); ?>"
                    class="transition-all duration-1000"/>
            <!-- Gradiente -->
            <defs>
                <linearGradient id="gradient-<?php echo esc_attr($meter_id); ?>" x1="0%" y1="0%" x2="100%" y2="0%">
                    <stop offset="0%" class="<?php echo $type === 'positive' ? 'text-green-400' : ($type === 'negative' ? 'text-red-400' : 'text-blue-400'); ?>" stop-color="currentColor"/>
                    <stop offset="100%" class="<?php echo $type === 'positive' ? 'text-emerald-500' : ($type === 'negative' ? 'text-orange-500' : 'text-indigo-500'); ?>" stop-color="currentColor"/>
                </linearGradient>
            </defs>
        </svg>

        <!-- Contenido central -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-4xl mb-1"><?php echo esc_html($icon); ?></span>
            <p class="text-2xl font-bold text-gray-900">
                <?php echo esc_html(number_format_i18n($value, 0)); ?>
            </p>
            <p class="text-sm text-gray-500"><?php echo esc_html($unit); ?></p>
        </div>
    </div>
    <p class="text-center mt-3 font-medium text-gray-700"><?php echo esc_html($label); ?></p>

<?php else: ?>
    <!-- Variante Gauge (default) -->
    <div class="flavor-impact-meter bg-white rounded-2xl shadow-lg p-6" id="<?php echo esc_attr($meter_id); ?>">
        <div class="flex items-start justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="text-4xl"><?php echo esc_html($icon); ?></span>
                <div>
                    <h4 class="font-bold text-gray-900"><?php echo esc_html($label); ?></h4>
                    <?php if ($description): ?>
                        <p class="text-sm text-gray-500"><?php echo esc_html($description); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="text-right">
                <p class="text-3xl font-bold <?php echo esc_attr($colors['text']); ?>">
                    <?php echo esc_html(number_format_i18n($value, $value == floor($value) ? 0 : 1)); ?>
                </p>
                <p class="text-sm text-gray-500"><?php echo esc_html($unit); ?></p>
            </div>
        </div>

        <!-- Gauge semicircular -->
        <div class="relative h-24 mb-4">
            <svg class="w-full h-full" viewBox="0 0 200 100">
                <!-- Arco de fondo -->
                <path d="M 10 100 A 90 90 0 0 1 190 100" fill="none" stroke="#E5E7EB" stroke-width="12" stroke-linecap="round"/>
                <!-- Arco de progreso -->
                <path d="M 10 100 A 90 90 0 0 1 190 100" fill="none" stroke="url(#gauge-gradient-<?php echo esc_attr($meter_id); ?>)" stroke-width="12" stroke-linecap="round"
                      stroke-dasharray="<?php echo pi() * 90; ?>"
                      stroke-dashoffset="<?php echo pi() * 90 * (1 - $percent / 100); ?>"
                      class="transition-all duration-1000"/>
                <!-- Gradiente -->
                <defs>
                    <linearGradient id="gauge-gradient-<?php echo esc_attr($meter_id); ?>" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" class="<?php echo $type === 'positive' ? 'text-green-400' : ($type === 'negative' ? 'text-red-400' : 'text-blue-400'); ?>" stop-color="currentColor"/>
                        <stop offset="100%" class="<?php echo $type === 'positive' ? 'text-emerald-600' : ($type === 'negative' ? 'text-orange-600' : 'text-indigo-600'); ?>" stop-color="currentColor"/>
                    </linearGradient>
                </defs>
            </svg>

            <!-- Indicador central -->
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 text-center">
                <span class="text-2xl font-bold <?php echo esc_attr($colors['text']); ?>"><?php echo round($percent); ?>%</span>
            </div>
        </div>

        <!-- Comparación -->
        <?php if (!empty($comparison)): ?>
            <div class="flex items-center justify-center gap-2 p-3 <?php echo esc_attr($colors['bg']); ?> rounded-lg">
                <span class="<?php echo ($comparison['better'] ?? false) ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo ($comparison['better'] ?? false) ? '👍' : '👎'; ?>
                </span>
                <span class="text-sm text-gray-700">
                    <?php echo esc_html($comparison['label'] ?? ''); ?>
                </span>
            </div>
        <?php endif; ?>

        <!-- Milestones -->
        <?php if (!empty($milestones)): ?>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs text-gray-500 mb-2"><?php esc_html_e('Logros:', 'flavor-chat-ia'); ?></p>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($milestones as $milestone): ?>
                        <?php $achieved = $value >= ($milestone['value'] ?? 0); ?>
                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-xs font-medium <?php echo $achieved ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400'; ?>">
                            <?php echo $achieved ? '🏆' : '🔒'; ?>
                            <?php echo esc_html($milestone['label'] ?? ''); ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
