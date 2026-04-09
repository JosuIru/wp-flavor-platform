<?php
/**
 * Componente: Booking Slot
 *
 * Slot de reserva individual con información y acciones.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $date        Fecha (YYYY-MM-DD)
 * @param string $start_time  Hora inicio
 * @param string $end_time    Hora fin
 * @param string $resource    Nombre del recurso/espacio
 * @param string $status      Estado: available, booked, pending, blocked
 * @param string $booked_by   Nombre de quien reservó
 * @param int    $capacity    Capacidad total
 * @param int    $occupied    Plazas ocupadas
 * @param float  $price       Precio (0 = gratis)
 * @param string $currency    Moneda
 * @param string $action_url  URL de acción (reservar)
 * @param string $variant     Variante: default, compact, card
 * @param bool   $show_actions Mostrar botón de acción
 */

if (!defined('ABSPATH')) {
    exit;
}

$date = $date ?? date('Y-m-d');
$start_time = $start_time ?? '';
$end_time = $end_time ?? '';
$resource = $resource ?? '';
$status = $status ?? 'available';
$booked_by = $booked_by ?? '';
$capacity = intval($capacity ?? 0);
$occupied = intval($occupied ?? 0);
$price = floatval($price ?? 0);
$currency = $currency ?? '€';
$action_url = $action_url ?? '';
$variant = $variant ?? 'default';
$show_actions = $show_actions ?? true;

// Estados
$status_config = [
    'available' => ['label' => __('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'green', 'icon' => '✓', 'bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-700'],
    'booked'    => ['label' => __('Reservado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'blue', 'icon' => '📅', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-700'],
    'pending'   => ['label' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'yellow', 'icon' => '⏳', 'bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-700'],
    'blocked'   => ['label' => __('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'gray', 'icon' => '🚫', 'bg' => 'bg-gray-100', 'border' => 'border-gray-200', 'text' => 'text-gray-500'],
    'full'      => ['label' => __('Completo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'color' => 'red', 'icon' => '🔴', 'bg' => 'bg-red-50', 'border' => 'border-red-200', 'text' => 'text-red-700'],
];

// Si tiene capacidad y está lleno
if ($capacity > 0 && $occupied >= $capacity && $status === 'available') {
    $status = 'full';
}

$st = $status_config[$status] ?? $status_config['available'];

// Formatear fecha
$date_ts = strtotime($date);
$day_name = date_i18n('l', $date_ts);
$formatted_date = date_i18n('j \d\e F', $date_ts);

// Plazas restantes
$remaining = $capacity > 0 ? max(0, $capacity - $occupied) : null;
?>

<?php if ($variant === 'compact'): ?>
    <!-- Variante Compact -->
    <div class="flavor-booking-slot inline-flex items-center gap-2 px-3 py-2 rounded-lg border <?php echo esc_attr($st['bg'] . ' ' . $st['border']); ?>">
        <span class="text-sm font-mono font-medium <?php echo esc_attr($st['text']); ?>">
            <?php echo esc_html($start_time); ?><?php if ($end_time): ?> - <?php echo esc_html($end_time); ?><?php endif; ?>
        </span>
        <span class="text-xs <?php echo esc_attr($st['text']); ?>"><?php echo esc_html($st['icon']); ?></span>
        <?php if ($price > 0): ?>
            <span class="text-xs font-medium text-gray-600"><?php echo esc_html($price . ' ' . $currency); ?></span>
        <?php endif; ?>
    </div>

<?php elseif ($variant === 'card'): ?>
    <!-- Variante Card -->
    <div class="flavor-booking-slot bg-white rounded-xl shadow-md overflow-hidden border <?php echo esc_attr($st['border']); ?>">
        <div class="<?php echo esc_attr($st['bg']); ?> px-4 py-3 border-b <?php echo esc_attr($st['border']); ?>">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-xl"><?php echo esc_html($st['icon']); ?></span>
                    <span class="font-medium <?php echo esc_attr($st['text']); ?>"><?php echo esc_html($st['label']); ?></span>
                </div>
                <?php if ($price > 0): ?>
                    <span class="font-bold text-gray-900"><?php echo esc_html($price . ' ' . $currency); ?></span>
                <?php elseif ($status === 'available'): ?>
                    <span class="text-green-600 font-medium"><?php esc_html_e('Gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="p-4">
            <!-- Fecha y hora -->
            <div class="flex items-center gap-3 mb-3">
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex flex-col items-center justify-center">
                    <span class="text-xs text-gray-500 uppercase"><?php echo date_i18n('M', $date_ts); ?></span>
                    <span class="text-lg font-bold text-gray-900"><?php echo date('j', $date_ts); ?></span>
                </div>
                <div>
                    <p class="font-medium text-gray-900"><?php echo esc_html($day_name); ?></p>
                    <p class="text-sm text-gray-500">
                        <?php echo esc_html($start_time); ?>
                        <?php if ($end_time): ?> - <?php echo esc_html($end_time); ?><?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Recurso -->
            <?php if ($resource): ?>
                <p class="text-sm text-gray-600 mb-3">
                    📍 <?php echo esc_html($resource); ?>
                </p>
            <?php endif; ?>

            <!-- Capacidad -->
            <?php if ($capacity > 0): ?>
                <div class="flex items-center gap-2 mb-3">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 rounded-full" style="width: <?php echo min(100, ($occupied / $capacity) * 100); ?>%;"></div>
                    </div>
                    <span class="text-xs text-gray-500">
                        <?php printf(__('%d/%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), $occupied, $capacity); ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Reservado por -->
            <?php if ($status === 'booked' && $booked_by): ?>
                <p class="text-sm text-gray-500">
                    <?php esc_html_e('Reservado por:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="font-medium text-gray-700"><?php echo esc_html($booked_by); ?></span>
                </p>
            <?php endif; ?>

            <!-- Acción -->
            <?php if ($show_actions && $status === 'available' && $remaining !== 0): ?>
                <a href="<?php echo esc_url($action_url); ?>"
                   class="mt-4 block w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-center font-medium rounded-lg transition-colors">
                    <?php esc_html_e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

<?php else: ?>
    <!-- Variante Default -->
    <div class="flavor-booking-slot flex items-center gap-4 p-4 rounded-xl border <?php echo esc_attr($st['bg'] . ' ' . $st['border']); ?>">
        <!-- Hora -->
        <div class="flex-shrink-0 text-center">
            <p class="text-lg font-bold text-gray-900"><?php echo esc_html($start_time); ?></p>
            <?php if ($end_time): ?>
                <p class="text-xs text-gray-500"><?php echo esc_html($end_time); ?></p>
            <?php endif; ?>
        </div>

        <!-- Separador -->
        <div class="w-px h-10 bg-gray-300"></div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
            <?php if ($resource): ?>
                <p class="font-medium text-gray-900 truncate"><?php echo esc_html($resource); ?></p>
            <?php endif; ?>
            <div class="flex items-center gap-2 text-sm">
                <span class="inline-flex items-center gap-1 <?php echo esc_attr($st['text']); ?>">
                    <?php echo esc_html($st['icon']); ?>
                    <?php echo esc_html($st['label']); ?>
                </span>
                <?php if ($remaining !== null): ?>
                    <span class="text-gray-400">•</span>
                    <span class="text-gray-500">
                        <?php printf(__('%d plazas', FLAVOR_PLATFORM_TEXT_DOMAIN), $remaining); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Precio -->
        <?php if ($price > 0): ?>
            <div class="flex-shrink-0 text-right">
                <p class="font-bold text-gray-900"><?php echo esc_html($price . ' ' . $currency); ?></p>
            </div>
        <?php endif; ?>

        <!-- Acción -->
        <?php if ($show_actions && $status === 'available' && $remaining !== 0): ?>
            <a href="<?php echo esc_url($action_url); ?>"
               class="flex-shrink-0 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                <?php esc_html_e('Reservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>
