<?php
/**
 * Componente: Certifications Badges
 *
 * Badges de certificaciones, logros o reconocimientos.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $badges      Array de badges: [['id' => x, 'name' => '', 'icon' => '', 'color' => '', 'description' => '', 'date' => '', 'verified' => false]]
 * @param string $variant     Variante: icons, cards, list, compact
 * @param int    $max_visible Máximo de badges visibles
 * @param bool   $show_dates  Mostrar fechas de obtención
 * @param bool   $show_verified Mostrar indicador verificado
 * @param string $empty_text  Texto si no hay badges
 */

if (!defined('ABSPATH')) {
    exit;
}

$badges = $badges ?? [];
$variant = $variant ?? 'icons';
$max_visible = intval($max_visible ?? 0);
$show_dates = $show_dates ?? false;
$show_verified = $show_verified ?? true;
$empty_text = $empty_text ?? __('Sin certificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN);

// Limitar badges visibles
$visible_badges = $max_visible > 0 ? array_slice($badges, 0, $max_visible) : $badges;
$remaining = $max_visible > 0 ? max(0, count($badges) - $max_visible) : 0;

// Colores predefinidos
$color_classes = [
    'gold'   => 'bg-gradient-to-br from-yellow-400 to-amber-500 text-white',
    'silver' => 'bg-gradient-to-br from-gray-300 to-gray-400 text-gray-800',
    'bronze' => 'bg-gradient-to-br from-orange-400 to-orange-600 text-white',
    'blue'   => 'bg-gradient-to-br from-blue-500 to-blue-600 text-white',
    'green'  => 'bg-gradient-to-br from-green-500 to-green-600 text-white',
    'purple' => 'bg-gradient-to-br from-purple-500 to-purple-600 text-white',
    'red'    => 'bg-gradient-to-br from-red-500 to-red-600 text-white',
    'pink'   => 'bg-gradient-to-br from-pink-500 to-pink-600 text-white',
];
?>

<?php if (empty($badges)): ?>
    <p class="text-sm text-gray-400"><?php echo esc_html($empty_text); ?></p>

<?php elseif ($variant === 'compact'): ?>
    <!-- Variante Compact (solo iconos pequeños) -->
    <div class="flavor-certifications flex flex-wrap items-center gap-1">
        <?php foreach ($visible_badges as $badge): ?>
            <span class="w-6 h-6 rounded-full <?php echo esc_attr($color_classes[$badge['color'] ?? 'blue'] ?? $color_classes['blue']); ?> flex items-center justify-center text-sm shadow-sm cursor-help"
                  title="<?php echo esc_attr($badge['name'] ?? ''); ?>">
                <?php echo esc_html($badge['icon'] ?? '🏆'); ?>
            </span>
        <?php endforeach; ?>
        <?php if ($remaining > 0): ?>
            <span class="w-6 h-6 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-medium">
                +<?php echo $remaining; ?>
            </span>
        <?php endif; ?>
    </div>

<?php elseif ($variant === 'icons'): ?>
    <!-- Variante Icons (badges medianos con tooltip) -->
    <div class="flavor-certifications flex flex-wrap items-center gap-2">
        <?php foreach ($visible_badges as $badge): ?>
            <div class="group relative">
                <div class="w-12 h-12 rounded-full <?php echo esc_attr($color_classes[$badge['color'] ?? 'blue'] ?? $color_classes['blue']); ?> flex items-center justify-center text-2xl shadow-md ring-2 ring-white cursor-pointer hover:scale-110 transition-transform">
                    <?php echo esc_html($badge['icon'] ?? '🏆'); ?>
                    <?php if ($show_verified && ($badge['verified'] ?? false)): ?>
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 rounded-full flex items-center justify-center text-white text-xs shadow">
                            ✓
                        </span>
                    <?php endif; ?>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                    <p class="font-medium"><?php echo esc_html($badge['name'] ?? ''); ?></p>
                    <?php if ($show_dates && !empty($badge['date'])): ?>
                        <p class="text-gray-400"><?php echo esc_html(date_i18n('M Y', strtotime($badge['date']))); ?></p>
                    <?php endif; ?>
                    <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1 border-4 border-transparent border-t-gray-900"></div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($remaining > 0): ?>
            <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center font-medium shadow cursor-pointer hover:bg-gray-200 transition-colors">
                +<?php echo $remaining; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($variant === 'list'): ?>
    <!-- Variante List -->
    <div class="flavor-certifications space-y-2">
        <?php foreach ($visible_badges as $badge): ?>
            <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                <div class="w-10 h-10 rounded-full <?php echo esc_attr($color_classes[$badge['color'] ?? 'blue'] ?? $color_classes['blue']); ?> flex items-center justify-center text-xl shadow-sm flex-shrink-0">
                    <?php echo esc_html($badge['icon'] ?? '🏆'); ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate">
                        <?php echo esc_html($badge['name'] ?? ''); ?>
                        <?php if ($show_verified && ($badge['verified'] ?? false)): ?>
                            <span class="text-green-500 text-sm ml-1">✓</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($show_dates && !empty($badge['date'])): ?>
                        <p class="text-xs text-gray-500"><?php echo esc_html(date_i18n('j \d\e F \d\e Y', strtotime($badge['date']))); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($remaining > 0): ?>
            <p class="text-sm text-gray-500 pl-2">
                <?php printf(__('+ %d más', FLAVOR_PLATFORM_TEXT_DOMAIN), $remaining); ?>
            </p>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Variante Cards -->
    <div class="flavor-certifications grid grid-cols-2 sm:grid-cols-3 gap-4">
        <?php foreach ($visible_badges as $badge): ?>
            <div class="bg-white rounded-xl shadow-sm border p-4 text-center hover:shadow-md transition-shadow">
                <div class="w-16 h-16 mx-auto rounded-full <?php echo esc_attr($color_classes[$badge['color'] ?? 'blue'] ?? $color_classes['blue']); ?> flex items-center justify-center text-3xl shadow-lg mb-3">
                    <?php echo esc_html($badge['icon'] ?? '🏆'); ?>
                </div>
                <h4 class="font-semibold text-gray-900 text-sm">
                    <?php echo esc_html($badge['name'] ?? ''); ?>
                </h4>
                <?php if (!empty($badge['description'])): ?>
                    <p class="text-xs text-gray-500 mt-1"><?php echo esc_html($badge['description']); ?></p>
                <?php endif; ?>
                <?php if ($show_dates && !empty($badge['date'])): ?>
                    <p class="text-xs text-gray-400 mt-2">
                        <?php echo esc_html(date_i18n('M Y', strtotime($badge['date']))); ?>
                    </p>
                <?php endif; ?>
                <?php if ($show_verified && ($badge['verified'] ?? false)): ?>
                    <span class="inline-flex items-center gap-1 mt-2 text-xs text-green-600">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <?php esc_html_e('Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
