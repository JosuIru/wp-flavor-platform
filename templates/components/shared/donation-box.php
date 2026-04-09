<?php
/**
 * Componente: Donation Box
 *
 * Caja de donación con opciones predefinidas y progreso.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $title       Título de la campaña
 * @param string $description Descripción
 * @param float  $goal        Meta de recaudación
 * @param float  $raised      Cantidad recaudada
 * @param int    $donors      Número de donantes
 * @param array  $amounts     Cantidades predefinidas [5, 10, 25, 50]
 * @param string $currency    Moneda
 * @param bool   $allow_custom Permitir cantidad personalizada
 * @param string $action_url  URL del formulario
 * @param string $image       Imagen de la campaña
 * @param string $end_date    Fecha de fin (opcional)
 * @param string $variant     Variante: default, compact, card
 */

if (!defined('ABSPATH')) {
    exit;
}

$title = $title ?? __('Apoya esta causa', FLAVOR_PLATFORM_TEXT_DOMAIN);
$description = $description ?? '';
$goal = floatval($goal ?? 0);
$raised = floatval($raised ?? 0);
$donors = intval($donors ?? 0);
$amounts = $amounts ?? [5, 10, 25, 50, 100];
$currency = $currency ?? '€';
$allow_custom = $allow_custom ?? true;
$action_url = $action_url ?? '';
$image = $image ?? '';
$end_date = $end_date ?? '';
$variant = $variant ?? 'default';

// Calcular progreso
$progress = $goal > 0 ? min(100, ($raised / $goal) * 100) : 0;

// Días restantes
$days_left = null;
if ($end_date) {
    $end_ts = strtotime($end_date);
    $days_left = max(0, ceil(($end_ts - time()) / DAY_IN_SECONDS));
}

$donation_id = 'flavor-donation-' . wp_rand(1000, 9999);
?>

<?php if ($variant === 'compact'): ?>
    <!-- Variante Compact -->
    <div class="flavor-donation-box bg-white rounded-xl shadow-md p-4" id="<?php echo esc_attr($donation_id); ?>">
        <div class="flex items-center gap-4 mb-4">
            <?php if ($image): ?>
                <img src="<?php echo esc_url($image); ?>" alt="" class="w-16 h-16 rounded-lg object-cover">
            <?php endif; ?>
            <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-gray-900 truncate"><?php echo esc_html($title); ?></h4>
                <div class="flex items-center gap-2 mt-1 text-sm">
                    <span class="text-green-600 font-bold"><?php echo esc_html(number_format_i18n($raised, 0) . ' ' . $currency); ?></span>
                    <?php if ($goal > 0): ?>
                        <span class="text-gray-400">/ <?php echo esc_html(number_format_i18n($goal, 0) . ' ' . $currency); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Barra de progreso -->
        <?php if ($goal > 0): ?>
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden mb-3">
                <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-500" style="width: <?php echo $progress; ?>%;"></div>
            </div>
        <?php endif; ?>

        <!-- Botón donar -->
        <a href="<?php echo esc_url($action_url ?: '#' . $donation_id . '-form'); ?>" class="block w-full py-2 bg-green-600 hover:bg-green-700 text-white text-center font-medium rounded-lg transition-colors">
            💚 <?php esc_html_e('Donar ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </div>

<?php else: ?>
    <!-- Variante Default/Card -->
    <div class="flavor-donation-box bg-white rounded-2xl shadow-lg overflow-hidden" id="<?php echo esc_attr($donation_id); ?>">

        <?php if ($image): ?>
            <div class="relative h-48">
                <img src="<?php echo esc_url($image); ?>" alt="" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                <?php if ($days_left !== null): ?>
                    <span class="absolute top-3 right-3 bg-white/90 text-gray-800 text-xs font-medium px-2 py-1 rounded-full">
                        ⏰ <?php printf(__('%d días restantes', FLAVOR_PLATFORM_TEXT_DOMAIN), $days_left); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900"><?php echo esc_html($title); ?></h3>

            <?php if ($description): ?>
                <p class="mt-2 text-gray-600 text-sm"><?php echo esc_html($description); ?></p>
            <?php endif; ?>

            <!-- Progreso -->
            <div class="mt-4">
                <div class="flex items-baseline justify-between mb-2">
                    <span class="text-2xl font-bold text-green-600">
                        <?php echo esc_html(number_format_i18n($raised, 0) . ' ' . $currency); ?>
                    </span>
                    <?php if ($goal > 0): ?>
                        <span class="text-sm text-gray-500">
                            <?php printf(__('de %s %s', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($goal, 0), $currency); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($goal > 0): ?>
                    <div class="h-3 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-green-400 to-green-600 rounded-full transition-all duration-500" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <p class="text-xs text-gray-500 mt-1"><?php printf(__('%s%% completado', FLAVOR_PLATFORM_TEXT_DOMAIN), number_format_i18n($progress, 0)); ?></p>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="flex items-center gap-4 mt-4 py-3 border-y border-gray-100 text-sm">
                <div class="flex items-center gap-1">
                    <span class="text-green-600">👥</span>
                    <span class="font-medium text-gray-900"><?php echo number_format_i18n($donors); ?></span>
                    <span class="text-gray-500"><?php esc_html_e('donantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>
                <?php if ($days_left !== null): ?>
                    <div class="flex items-center gap-1">
                        <span class="text-orange-500">⏱️</span>
                        <span class="font-medium text-gray-900"><?php echo $days_left; ?></span>
                        <span class="text-gray-500"><?php esc_html_e('días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Cantidades predefinidas -->
            <form method="post" action="<?php echo esc_url($action_url); ?>" class="mt-4" id="<?php echo esc_attr($donation_id); ?>-form">
                <p class="text-sm font-medium text-gray-700 mb-2"><?php esc_html_e('Selecciona una cantidad:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="grid grid-cols-3 gap-2 mb-3">
                    <?php foreach ($amounts as $i => $amount): ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="amount" value="<?php echo esc_attr($amount); ?>" class="peer sr-only" <?php echo $i === 1 ? 'checked' : ''; ?>>
                            <div class="p-3 text-center rounded-xl border-2 border-gray-200 peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300 transition-all">
                                <span class="font-bold text-gray-900"><?php echo esc_html($amount . ' ' . $currency); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <?php if ($allow_custom): ?>
                    <div class="mb-4">
                        <label class="flex items-center gap-2 text-sm text-gray-600 mb-1">
                            <input type="radio" name="amount" value="custom" class="peer">
                            <?php esc_html_e('Otra cantidad:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <div class="relative">
                            <input type="number" name="custom_amount" min="1" step="1"
                                   class="w-full px-4 py-2 pr-12 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                   placeholder="0">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500"><?php echo esc_html($currency); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all">
                    💚 <?php esc_html_e('Donar ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>

                <p class="mt-3 text-xs text-gray-400 text-center">
                    🔒 <?php esc_html_e('Pago seguro con encriptación SSL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </form>
        </div>
    </div>
<?php endif; ?>
