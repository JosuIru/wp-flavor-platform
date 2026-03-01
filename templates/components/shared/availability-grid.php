<?php
/**
 * Componente: Availability Grid
 *
 * Grid de disponibilidad para mostrar slots de tiempo disponibles/ocupados.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $dates       Fechas a mostrar: ['2026-02-27', '2026-02-28', ...]
 * @param array  $slots       Slots por fecha: ['2026-02-27' => [['time' => '09:00', 'available' => true, 'label' => ''], ...]]
 * @param string $selected    Slot seleccionado: '2026-02-27|09:00'
 * @param bool   $selectable  Permitir seleccionar slots
 * @param string $variant     Variante: grid, list, calendar
 * @param string $empty_text  Texto cuando no hay disponibilidad
 * @param string $input_name  Nombre del input hidden para el valor seleccionado
 */

if (!defined('ABSPATH')) {
    exit;
}

$dates = $dates ?? [];
$slots = $slots ?? [];
$selected = $selected ?? '';
$selectable = $selectable ?? true;
$variant = $variant ?? 'grid';
$empty_text = $empty_text ?? __('No hay horarios disponibles', 'flavor-chat-ia');
$input_name = $input_name ?? 'selected_slot';

$grid_id = 'flavor-availability-' . wp_rand(1000, 9999);

// Días de la semana
$day_names = [
    0 => __('Dom', 'flavor-chat-ia'),
    1 => __('Lun', 'flavor-chat-ia'),
    2 => __('Mar', 'flavor-chat-ia'),
    3 => __('Mié', 'flavor-chat-ia'),
    4 => __('Jue', 'flavor-chat-ia'),
    5 => __('Vie', 'flavor-chat-ia'),
    6 => __('Sáb', 'flavor-chat-ia'),
];
?>

<div class="flavor-availability-grid" id="<?php echo esc_attr($grid_id); ?>">

    <?php if (empty($dates)): ?>
        <div class="text-center py-8 text-gray-500">
            <span class="text-3xl">📅</span>
            <p class="mt-2"><?php echo esc_html($empty_text); ?></p>
        </div>

    <?php elseif ($variant === 'list'): ?>
        <!-- Variante List -->
        <div class="space-y-4">
            <?php foreach ($dates as $date): ?>
                <?php
                $date_slots = $slots[$date] ?? [];
                $date_ts = strtotime($date);
                $day_name = $day_names[date('w', $date_ts)];
                $day_num = date('j', $date_ts);
                $month = date_i18n('M', $date_ts);
                $available_slots = array_filter($date_slots, fn($s) => $s['available'] ?? false);
                ?>
                <div class="bg-white rounded-xl shadow-sm border p-4">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="text-center">
                            <p class="text-xs text-gray-500 uppercase"><?php echo esc_html($day_name); ?></p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo esc_html($day_num); ?></p>
                            <p class="text-xs text-gray-500"><?php echo esc_html($month); ?></p>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm text-gray-600">
                                <?php printf(__('%d horarios disponibles', 'flavor-chat-ia'), count($available_slots)); ?>
                            </p>
                        </div>
                    </div>

                    <?php if (empty($available_slots)): ?>
                        <p class="text-sm text-gray-400"><?php esc_html_e('Sin disponibilidad', 'flavor-chat-ia'); ?></p>
                    <?php else: ?>
                        <div class="flex flex-wrap gap-2">
                            <?php foreach ($date_slots as $slot): ?>
                                <?php
                                $is_available = $slot['available'] ?? false;
                                $slot_value = $date . '|' . $slot['time'];
                                $is_selected = ($selected === $slot_value);
                                ?>
                                <?php if ($is_available): ?>
                                    <button type="button"
                                            class="slot-btn px-3 py-1.5 text-sm rounded-lg border transition-all <?php echo $is_selected ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-700 hover:border-blue-500 hover:text-blue-600'; ?>"
                                            data-value="<?php echo esc_attr($slot_value); ?>"
                                            <?php echo !$selectable ? 'disabled' : ''; ?>>
                                        <?php echo esc_html($slot['time']); ?>
                                    </button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Variante Grid (default) -->
        <div class="overflow-x-auto">
            <div class="flex gap-3 min-w-max pb-2">
                <?php foreach ($dates as $date): ?>
                    <?php
                    $date_slots = $slots[$date] ?? [];
                    $date_ts = strtotime($date);
                    $day_name = $day_names[date('w', $date_ts)];
                    $day_num = date('j', $date_ts);
                    $is_today = (date('Y-m-d') === $date);
                    ?>
                    <div class="flex-shrink-0 w-24">
                        <!-- Header del día -->
                        <div class="text-center mb-2 pb-2 border-b <?php echo $is_today ? 'border-blue-500' : 'border-gray-200'; ?>">
                            <p class="text-xs font-medium <?php echo $is_today ? 'text-blue-600' : 'text-gray-500'; ?> uppercase">
                                <?php echo esc_html($day_name); ?>
                                <?php if ($is_today): ?>
                                    <span class="block text-[10px]"><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                            </p>
                            <p class="text-lg font-bold <?php echo $is_today ? 'text-blue-600' : 'text-gray-900'; ?>">
                                <?php echo esc_html($day_num); ?>
                            </p>
                        </div>

                        <!-- Slots -->
                        <div class="space-y-1">
                            <?php foreach ($date_slots as $slot): ?>
                                <?php
                                $is_available = $slot['available'] ?? false;
                                $slot_value = $date . '|' . $slot['time'];
                                $is_selected = ($selected === $slot_value);
                                ?>
                                <button type="button"
                                        class="slot-btn w-full py-1.5 text-xs font-medium rounded transition-all <?php
                                        if (!$is_available) {
                                            echo 'bg-gray-100 text-gray-400 cursor-not-allowed';
                                        } elseif ($is_selected) {
                                            echo 'bg-blue-600 text-white';
                                        } else {
                                            echo 'bg-green-100 text-green-700 hover:bg-green-200';
                                        }
                                        ?>"
                                        data-value="<?php echo esc_attr($slot_value); ?>"
                                        <?php echo (!$is_available || !$selectable) ? 'disabled' : ''; ?>>
                                    <?php echo esc_html($slot['time']); ?>
                                </button>
                            <?php endforeach; ?>

                            <?php if (empty($date_slots)): ?>
                                <p class="text-xs text-gray-400 text-center py-2"><?php esc_html_e('—', 'flavor-chat-ia'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Input hidden para el valor seleccionado -->
    <?php if ($selectable): ?>
        <input type="hidden" name="<?php echo esc_attr($input_name); ?>" id="<?php echo esc_attr($grid_id); ?>-value" value="<?php echo esc_attr($selected); ?>">
    <?php endif; ?>
</div>

<?php if ($selectable): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('<?php echo esc_js($grid_id); ?>');
    if (!container) return;

    const hiddenInput = document.getElementById('<?php echo esc_js($grid_id); ?>-value');
    const buttons = container.querySelectorAll('.slot-btn:not([disabled])');

    buttons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Deseleccionar todos
            buttons.forEach(b => {
                b.classList.remove('bg-blue-600', 'text-white');
                if (!b.disabled) {
                    b.classList.add('bg-green-100', 'text-green-700');
                }
            });

            // Seleccionar este
            this.classList.remove('bg-green-100', 'text-green-700');
            this.classList.add('bg-blue-600', 'text-white');

            // Actualizar input
            if (hiddenInput) {
                hiddenInput.value = this.dataset.value;
            }

            // Disparar evento
            container.dispatchEvent(new CustomEvent('slot-selected', {
                detail: { value: this.dataset.value },
                bubbles: true
            }));
        });
    });
});
</script>
<?php endif; ?>
