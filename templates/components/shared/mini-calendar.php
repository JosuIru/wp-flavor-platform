<?php
/**
 * Componente: Mini Calendar
 *
 * Calendario compacto para dashboards y sidebars.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $events     Eventos: [['date' => '2024-01-15', 'title' => 'Evento', 'color' => 'blue', 'url' => '#']]
 * @param string $month      Mes a mostrar (Y-m) o vacío para actual
 * @param string $color      Color del tema
 * @param bool   $show_nav   Mostrar navegación de mes
 * @param array  $selected   Fechas seleccionadas
 * @param string $on_select  Callback JS al seleccionar fecha
 */

if (!defined('ABSPATH')) {
    exit;
}

$events = $events ?? [];
$month = $month ?? current_time('Y-m');
$color = $color ?? 'blue';
$show_nav = $show_nav ?? true;
$selected = $selected ?? [];
$on_select = $on_select ?? '';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Parsear mes
$month_timestamp = strtotime($month . '-01');
$year = (int) date('Y', $month_timestamp);
$month_num = (int) date('n', $month_timestamp);
$month_name = date_i18n('F Y', $month_timestamp);
$days_in_month = (int) date('t', $month_timestamp);
$first_day_of_week = (int) date('w', $month_timestamp); // 0 = domingo
$today = current_time('Y-m-d');

// Indexar eventos por fecha
$events_by_date = [];
foreach ($events as $event) {
    $date = $event['date'] ?? '';
    if ($date) {
        if (!isset($events_by_date[$date])) {
            $events_by_date[$date] = [];
        }
        $events_by_date[$date][] = $event;
    }
}

// Días de la semana
$weekdays = [
    __('D', FLAVOR_PLATFORM_TEXT_DOMAIN),
    __('L', FLAVOR_PLATFORM_TEXT_DOMAIN),
    __('M', FLAVOR_PLATFORM_TEXT_DOMAIN),
    __('X', FLAVOR_PLATFORM_TEXT_DOMAIN),
    __('J', FLAVOR_PLATFORM_TEXT_DOMAIN),
    __('V', FLAVOR_PLATFORM_TEXT_DOMAIN),
    __('S', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

$calendar_id = 'calendar-' . wp_rand(1000, 9999);
?>

<div id="<?php echo esc_attr($calendar_id); ?>"
     class="flavor-mini-calendar bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
     data-month="<?php echo esc_attr($month); ?>">

    <!-- Header -->
    <div class="flex items-center justify-between p-4 border-b border-gray-100">
        <?php if ($show_nav): ?>
            <button onclick="flavorCalendar.navigate('<?php echo esc_js($calendar_id); ?>', -1)"
                    class="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                ←
            </button>
        <?php endif; ?>

        <h3 class="font-semibold text-gray-900 capitalize"><?php echo esc_html($month_name); ?></h3>

        <?php if ($show_nav): ?>
            <button onclick="flavorCalendar.navigate('<?php echo esc_js($calendar_id); ?>', 1)"
                    class="p-1 rounded hover:bg-gray-100 text-gray-400 hover:text-gray-600 transition-colors">
                →
            </button>
        <?php endif; ?>
    </div>

    <!-- Calendar Grid -->
    <div class="p-3">
        <!-- Weekday headers -->
        <div class="grid grid-cols-7 gap-1 mb-2">
            <?php foreach ($weekdays as $day): ?>
                <div class="text-center text-xs font-medium text-gray-400 py-1">
                    <?php echo esc_html($day); ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Days -->
        <div class="grid grid-cols-7 gap-1">
            <?php
            // Días vacíos antes del primer día del mes
            for ($i = 0; $i < $first_day_of_week; $i++): ?>
                <div class="aspect-square"></div>
            <?php endfor; ?>

            <?php
            // Días del mes
            for ($day = 1; $day <= $days_in_month; $day++):
                $date = sprintf('%04d-%02d-%02d', $year, $month_num, $day);
                $is_today = $date === $today;
                $is_selected = in_array($date, $selected);
                $day_events = $events_by_date[$date] ?? [];
                $has_events = !empty($day_events);

                $day_class = 'aspect-square flex flex-col items-center justify-center rounded-lg text-sm relative cursor-pointer transition-colors ';
                if ($is_today) {
                    $day_class .= $color_classes['bg_solid'] . ' text-white font-bold ';
                } elseif ($is_selected) {
                    $day_class .= $color_classes['bg'] . ' ' . $color_classes['text'] . ' font-medium ';
                } else {
                    $day_class .= 'text-gray-700 hover:bg-gray-100 ';
                }
            ?>
                <div class="<?php echo esc_attr($day_class); ?>"
                     data-date="<?php echo esc_attr($date); ?>"
                     <?php if ($on_select): ?>
                     onclick="<?php echo esc_attr($on_select); ?>('<?php echo esc_js($date); ?>')"
                     <?php endif; ?>
                     <?php if ($has_events): ?>
                     title="<?php echo esc_attr(implode(', ', array_column($day_events, 'title'))); ?>"
                     <?php endif; ?>>

                    <span><?php echo esc_html($day); ?></span>

                    <?php if ($has_events): ?>
                        <div class="flex gap-0.5 mt-0.5">
                            <?php foreach (array_slice($day_events, 0, 3) as $event):
                                $event_color = $event['color'] ?? $color;
                                if (function_exists('flavor_get_color_classes')) {
                                    $event_colors = flavor_get_color_classes($event_color);
                                } else {
                                    $event_colors = ['bg_solid' => 'bg-blue-500'];
                                }
                            ?>
                                <span class="w-1 h-1 rounded-full <?php echo $is_today ? 'bg-white' : esc_attr($event_colors['bg_solid']); ?>"></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Events for selected date / today -->
    <?php
    $show_events = $events_by_date[$today] ?? [];
    if (!empty($show_events)):
    ?>
        <div class="border-t border-gray-100 p-3">
            <h4 class="text-xs font-medium text-gray-500 mb-2"><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <div class="space-y-2">
                <?php foreach (array_slice($show_events, 0, 3) as $event):
                    $event_color = $event['color'] ?? $color;
                    if (function_exists('flavor_get_color_classes')) {
                        $event_colors = flavor_get_color_classes($event_color);
                    } else {
                        $event_colors = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'];
                    }
                ?>
                    <?php if (!empty($event['url'])): ?>
                        <a href="<?php echo esc_url($event['url']); ?>"
                           class="flex items-center gap-2 p-2 rounded-lg <?php echo esc_attr($event_colors['bg']); ?> hover:opacity-80 transition-opacity">
                    <?php else: ?>
                        <div class="flex items-center gap-2 p-2 rounded-lg <?php echo esc_attr($event_colors['bg']); ?>">
                    <?php endif; ?>
                        <span class="w-2 h-2 rounded-full <?php echo esc_attr($event_colors['bg_solid'] ?? $event_colors['text']); ?>"></span>
                        <span class="text-sm <?php echo esc_attr($event_colors['text']); ?> truncate">
                            <?php echo esc_html($event['title'] ?? ''); ?>
                        </span>
                        <?php if (!empty($event['time'])): ?>
                            <span class="text-xs <?php echo esc_attr($event_colors['text']); ?> opacity-75 ml-auto">
                                <?php echo esc_html($event['time']); ?>
                            </span>
                        <?php endif; ?>
                    <?php echo !empty($event['url']) ? '</a>' : '</div>'; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
window.flavorCalendar = window.flavorCalendar || {
    navigate: function(containerId, direction) {
        const container = document.getElementById(containerId);
        const currentMonth = container.dataset.month;
        const [year, month] = currentMonth.split('-').map(Number);

        let newMonth = month + direction;
        let newYear = year;

        if (newMonth < 1) {
            newMonth = 12;
            newYear--;
        } else if (newMonth > 12) {
            newMonth = 1;
            newYear++;
        }

        const newMonthStr = `${newYear}-${String(newMonth).padStart(2, '0')}`;

        // Dispatch event for AJAX reload
        document.dispatchEvent(new CustomEvent('calendar-navigate', {
            detail: { containerId, month: newMonthStr }
        }));
    }
};
</script>
