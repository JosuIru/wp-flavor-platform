<?php
/**
 * Componente: Schedule Grid
 *
 * Grid de horarios/programación semanal tipo tabla.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $schedule   Horario: ['lunes' => [['start' => '09:00', 'end' => '14:00', 'label' => '', 'color' => '']], ...]
 * @param array  $days       Días a mostrar (si vacío, usa los que tienen datos)
 * @param string $start_hour Hora de inicio del grid (06:00)
 * @param string $end_hour   Hora de fin del grid (22:00)
 * @param int    $interval   Intervalo en minutos (30, 60)
 * @param string $variant    Variante: grid, list, compact
 * @param bool   $show_current Destacar hora actual
 * @param string $empty_text Texto para slots vacíos
 */

if (!defined('ABSPATH')) {
    exit;
}

$schedule = $schedule ?? [];
$days = $days ?? [];
$start_hour = $start_hour ?? '06:00';
$end_hour = $end_hour ?? '22:00';
$interval = intval($interval ?? 60);
$variant = $variant ?? 'grid';
$show_current = $show_current ?? true;
$empty_text = $empty_text ?? '-';

// Días de la semana
$all_days = [
    'lunes'     => __('Lunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'martes'    => __('Martes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'miercoles' => __('Miércoles', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'jueves'    => __('Jueves', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'viernes'   => __('Viernes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'sabado'    => __('Sábado', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'domingo'   => __('Domingo', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

// Días cortos para móvil
$days_short = [
    'lunes' => 'L', 'martes' => 'M', 'miercoles' => 'X',
    'jueves' => 'J', 'viernes' => 'V', 'sabado' => 'S', 'domingo' => 'D'
];

// Si no hay días específicos, usar los que tienen datos
if (empty($days)) {
    $days = array_keys(array_filter($schedule, fn($d) => !empty($d)));
    if (empty($days)) {
        $days = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes'];
    }
}

// Generar slots de tiempo
$slots = [];
$start_ts = strtotime($start_hour);
$end_ts = strtotime($end_hour);
$current_ts = $start_ts;

while ($current_ts <= $end_ts) {
    $slots[] = date('H:i', $current_ts);
    $current_ts += $interval * 60;
}

// Día y hora actual
$current_day = strtolower(date_i18n('l'));
$current_day_map = [
    'monday' => 'lunes', 'tuesday' => 'martes', 'wednesday' => 'miercoles',
    'thursday' => 'jueves', 'friday' => 'viernes', 'saturday' => 'sabado', 'sunday' => 'domingo'
];
$current_day_es = $current_day_map[$current_day] ?? '';
$current_time = date('H:i');

// Colores por defecto
$default_colors = ['blue', 'green', 'purple', 'orange', 'pink', 'teal', 'indigo'];
$color_classes = [
    'blue'   => 'bg-blue-100 text-blue-800 border-blue-200',
    'green'  => 'bg-green-100 text-green-800 border-green-200',
    'purple' => 'bg-purple-100 text-purple-800 border-purple-200',
    'orange' => 'bg-orange-100 text-orange-800 border-orange-200',
    'pink'   => 'bg-pink-100 text-pink-800 border-pink-200',
    'teal'   => 'bg-teal-100 text-teal-800 border-teal-200',
    'indigo' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
    'red'    => 'bg-red-100 text-red-800 border-red-200',
    'yellow' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
    'gray'   => 'bg-gray-100 text-gray-800 border-gray-200',
];

// Función para verificar si un slot está dentro de un rango
function slot_in_range($slot, $start, $end) {
    return $slot >= $start && $slot < $end;
}
?>

<?php if ($variant === 'list'): ?>
    <!-- Variante List -->
    <div class="flavor-schedule-list space-y-4">
        <?php foreach ($days as $day): ?>
            <?php
            $is_today = ($day === $current_day_es);
            $day_schedule = $schedule[$day] ?? [];
            ?>
            <div class="<?php echo $is_today && $show_current ? 'bg-blue-50 border-l-4 border-blue-500 pl-3 -ml-3' : ''; ?>">
                <h4 class="font-medium text-gray-900 <?php echo $is_today ? 'text-blue-700' : ''; ?>">
                    <?php echo esc_html($all_days[$day] ?? ucfirst($day)); ?>
                    <?php if ($is_today): ?>
                        <span class="text-xs bg-blue-100 text-blue-700 px-1.5 py-0.5 rounded ml-1"><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php endif; ?>
                </h4>

                <?php if (empty($day_schedule)): ?>
                    <p class="text-sm text-gray-400 mt-1"><?php esc_html_e('Sin horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else: ?>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <?php foreach ($day_schedule as $i => $block): ?>
                            <?php
                            $color = $block['color'] ?? $default_colors[$i % count($default_colors)];
                            $color_class = $color_classes[$color] ?? $color_classes['blue'];
                            $is_now = $is_today && $show_current && slot_in_range($current_time, $block['start'], $block['end']);
                            ?>
                            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm border <?php echo esc_attr($color_class); ?> <?php echo $is_now ? 'ring-2 ring-offset-1 ring-blue-500' : ''; ?>">
                                <span class="font-mono"><?php echo esc_html($block['start']); ?> - <?php echo esc_html($block['end']); ?></span>
                                <?php if (!empty($block['label'])): ?>
                                    <span class="font-medium"><?php echo esc_html($block['label']); ?></span>
                                <?php endif; ?>
                                <?php if ($is_now): ?>
                                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php elseif ($variant === 'compact'): ?>
    <!-- Variante Compact (solo horas de apertura) -->
    <div class="flavor-schedule-compact bg-white rounded-xl shadow-sm border divide-y">
        <?php foreach ($days as $day): ?>
            <?php
            $is_today = ($day === $current_day_es);
            $day_schedule = $schedule[$day] ?? [];
            ?>
            <div class="flex items-center gap-4 px-4 py-3 <?php echo $is_today && $show_current ? 'bg-blue-50' : ''; ?>">
                <span class="font-medium text-gray-900 w-24 <?php echo $is_today ? 'text-blue-700' : ''; ?>">
                    <?php echo esc_html($all_days[$day] ?? ucfirst($day)); ?>
                </span>
                <div class="flex-1 text-sm">
                    <?php if (empty($day_schedule)): ?>
                        <span class="text-gray-400"><?php esc_html_e('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <?php else: ?>
                        <?php
                        $ranges = array_map(function($b) {
                            return $b['start'] . ' - ' . $b['end'];
                        }, $day_schedule);
                        ?>
                        <span class="text-gray-700"><?php echo esc_html(implode(', ', $ranges)); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($is_today && $show_current && !empty($day_schedule)): ?>
                    <?php
                    $is_open = false;
                    foreach ($day_schedule as $block) {
                        if (slot_in_range($current_time, $block['start'], $block['end'])) {
                            $is_open = true;
                            break;
                        }
                    }
                    ?>
                    <span class="text-xs font-medium px-2 py-0.5 rounded-full <?php echo $is_open ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                        <?php echo $is_open ? esc_html__('Abierto', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Cerrado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

<?php else: ?>
    <!-- Variante Grid (default) -->
    <div class="flavor-schedule-grid overflow-x-auto">
        <table class="w-full border-collapse min-w-[600px]">
            <thead>
                <tr>
                    <th class="p-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b w-16">
                        <?php esc_html_e('Hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </th>
                    <?php foreach ($days as $day): ?>
                        <?php $is_today = ($day === $current_day_es); ?>
                        <th class="p-2 text-center text-xs font-medium uppercase tracking-wider border-b <?php echo $is_today && $show_current ? 'bg-blue-50 text-blue-700' : 'text-gray-500'; ?>">
                            <span class="hidden sm:inline"><?php echo esc_html($all_days[$day] ?? ucfirst($day)); ?></span>
                            <span class="sm:hidden"><?php echo esc_html($days_short[$day] ?? substr($day, 0, 1)); ?></span>
                            <?php if ($is_today && $show_current): ?>
                                <span class="block text-[10px] text-blue-500"><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slots as $slot_index => $slot): ?>
                    <?php
                    $is_current_slot = $show_current && $current_time >= $slot && (
                        !isset($slots[$slot_index + 1]) || $current_time < $slots[$slot_index + 1]
                    );
                    ?>
                    <tr class="<?php echo $is_current_slot ? 'bg-yellow-50' : ($slot_index % 2 === 0 ? 'bg-white' : 'bg-gray-50'); ?>">
                        <td class="p-2 text-xs font-mono text-gray-500 border-r">
                            <?php echo esc_html($slot); ?>
                        </td>
                        <?php foreach ($days as $day): ?>
                            <?php
                            $is_today = ($day === $current_day_es);
                            $day_schedule = $schedule[$day] ?? [];
                            $cell_content = '';
                            $cell_class = '';

                            foreach ($day_schedule as $i => $block) {
                                if (slot_in_range($slot, $block['start'], $block['end'])) {
                                    $color = $block['color'] ?? $default_colors[$i % count($default_colors)];
                                    $cell_class = $color_classes[$color] ?? $color_classes['blue'];
                                    $cell_content = $block['label'] ?? '';

                                    // Si es el primer slot del bloque, mostrar hora
                                    if ($slot === $block['start']) {
                                        $cell_content = $block['start'] . ($cell_content ? ' - ' . $cell_content : '');
                                    }
                                    break;
                                }
                            }
                            ?>
                            <td class="p-1 text-center border-l <?php echo $is_today && $show_current ? 'border-l-blue-200' : ''; ?>">
                                <?php if ($cell_content || $cell_class): ?>
                                    <div class="rounded px-2 py-1 text-xs font-medium truncate <?php echo esc_attr($cell_class); ?>">
                                        <?php echo esc_html($cell_content ?: '•'); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-gray-300 text-xs"><?php echo esc_html($empty_text); ?></span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
