<?php
/**
 * Componente: Price Tag
 *
 * Etiqueta de precio con formato, descuentos y moneda.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param float  $price        Precio actual
 * @param float  $original     Precio original (para mostrar descuento)
 * @param string $currency     Símbolo de moneda (€, $, etc.)
 * @param string $period       Período: once, hour, day, month, year
 * @param string $label        Etiqueta adicional (desde, por persona, etc.)
 * @param bool   $free         Mostrar como "Gratis" si es 0
 * @param string $size         Tamaño: sm, md, lg, xl
 * @param string $variant      Variante: default, badge, inline, card
 * @param string $discount_label Label del descuento (-20%, Oferta, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

$price = floatval($price ?? 0);
$original = floatval($original ?? 0);
$currency = $currency ?? '€';
$period = $period ?? '';
$label = $label ?? '';
$free = $free ?? true;
$size = $size ?? 'md';
$variant = $variant ?? 'default';
$discount_label = $discount_label ?? '';

// Calcular descuento si hay precio original
$has_discount = $original > 0 && $original > $price;
$discount_percent = $has_discount ? round((($original - $price) / $original) * 100) : 0;

// Período labels
$period_labels = [
    'hour'  => __('/hora', 'flavor-chat-ia'),
    'day'   => __('/día', 'flavor-chat-ia'),
    'week'  => __('/semana', 'flavor-chat-ia'),
    'month' => __('/mes', 'flavor-chat-ia'),
    'year'  => __('/año', 'flavor-chat-ia'),
    'once'  => '',
];
$period_text = $period_labels[$period] ?? '';

// Formatear precio
function flavor_format_price($amount, $currency) {
    if ($currency === '€') {
        return number_format_i18n($amount, 2) . ' €';
    }
    return $currency . number_format_i18n($amount, 2);
}

// Tamaños
$size_config = [
    'sm' => ['price' => 'text-lg', 'original' => 'text-xs', 'period' => 'text-xs'],
    'md' => ['price' => 'text-2xl', 'original' => 'text-sm', 'period' => 'text-sm'],
    'lg' => ['price' => 'text-3xl', 'original' => 'text-base', 'period' => 'text-base'],
    'xl' => ['price' => 'text-4xl', 'original' => 'text-lg', 'period' => 'text-lg'],
];
$sz = $size_config[$size] ?? $size_config['md'];
?>

<?php if ($variant === 'badge'): ?>
    <!-- Variante Badge -->
    <span class="flavor-price-tag inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-100 text-green-800 rounded-full font-semibold <?php echo esc_attr($sz['price']); ?>">
        <?php if ($price == 0 && $free): ?>
            <?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>
        <?php else: ?>
            <?php echo flavor_format_price($price, $currency); ?>
            <?php if ($period_text): ?>
                <span class="font-normal <?php echo esc_attr($sz['period']); ?>"><?php echo esc_html($period_text); ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </span>

<?php elseif ($variant === 'inline'): ?>
    <!-- Variante Inline -->
    <span class="flavor-price-tag inline-flex items-baseline gap-2 flex-wrap">
        <?php if ($has_discount): ?>
            <span class="line-through text-gray-400 <?php echo esc_attr($sz['original']); ?>">
                <?php echo flavor_format_price($original, $currency); ?>
            </span>
        <?php endif; ?>
        <span class="font-bold text-gray-900 <?php echo esc_attr($sz['price']); ?>">
            <?php if ($price == 0 && $free): ?>
                <span class="text-green-600"><?php esc_html_e('Gratis', 'flavor-chat-ia'); ?></span>
            <?php else: ?>
                <?php echo flavor_format_price($price, $currency); ?>
            <?php endif; ?>
        </span>
        <?php if ($period_text): ?>
            <span class="text-gray-500 <?php echo esc_attr($sz['period']); ?>"><?php echo esc_html($period_text); ?></span>
        <?php endif; ?>
        <?php if ($has_discount && $discount_percent > 0): ?>
            <span class="bg-red-100 text-red-700 text-xs font-medium px-1.5 py-0.5 rounded">
                -<?php echo $discount_percent; ?>%
            </span>
        <?php endif; ?>
    </span>

<?php elseif ($variant === 'card'): ?>
    <!-- Variante Card -->
    <div class="flavor-price-tag bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-4 text-center relative overflow-hidden">
        <?php if ($has_discount): ?>
            <div class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold px-3 py-1 transform rotate-45 translate-x-6 translate-y-2 shadow-sm">
                <?php echo $discount_label ?: ('-' . $discount_percent . '%'); ?>
            </div>
        <?php endif; ?>

        <?php if ($label): ?>
            <p class="text-xs text-gray-500 mb-1"><?php echo esc_html($label); ?></p>
        <?php endif; ?>

        <?php if ($has_discount): ?>
            <p class="line-through text-gray-400 <?php echo esc_attr($sz['original']); ?>">
                <?php echo flavor_format_price($original, $currency); ?>
            </p>
        <?php endif; ?>

        <p class="font-bold <?php echo esc_attr($sz['price']); ?> <?php echo ($price == 0 && $free) ? 'text-green-600' : 'text-gray-900'; ?>">
            <?php if ($price == 0 && $free): ?>
                <?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>
            <?php else: ?>
                <?php echo flavor_format_price($price, $currency); ?>
            <?php endif; ?>
        </p>

        <?php if ($period_text): ?>
            <p class="text-gray-500 <?php echo esc_attr($sz['period']); ?>"><?php echo esc_html($period_text); ?></p>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Variante Default -->
    <div class="flavor-price-tag">
        <?php if ($label): ?>
            <p class="text-xs text-gray-500 mb-0.5"><?php echo esc_html($label); ?></p>
        <?php endif; ?>

        <div class="flex items-baseline gap-2 flex-wrap">
            <?php if ($price == 0 && $free): ?>
                <span class="font-bold text-green-600 <?php echo esc_attr($sz['price']); ?>">
                    <?php esc_html_e('Gratis', 'flavor-chat-ia'); ?>
                </span>
            <?php else: ?>
                <span class="font-bold text-gray-900 <?php echo esc_attr($sz['price']); ?>">
                    <?php echo flavor_format_price($price, $currency); ?>
                </span>
                <?php if ($period_text): ?>
                    <span class="text-gray-500 <?php echo esc_attr($sz['period']); ?>"><?php echo esc_html($period_text); ?></span>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($has_discount): ?>
                <span class="line-through text-gray-400 <?php echo esc_attr($sz['original']); ?>">
                    <?php echo flavor_format_price($original, $currency); ?>
                </span>
                <span class="bg-red-100 text-red-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                    <?php echo $discount_label ?: ('-' . $discount_percent . '%'); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
