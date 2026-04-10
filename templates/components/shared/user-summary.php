<?php
/**
 * Componente: User Summary
 *
 * Resumen de usuario para dashboards y headers.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $user       Datos del usuario: id, name, avatar, role, verified, email
 * @param array  $stats      Stats rápidas: [['value' => 10, 'label' => 'Posts']]
 * @param array  $badges     Badges/insignias: [['icon' => '🏆', 'label' => 'Pro']]
 * @param string $greeting   Saludo personalizado
 * @param string $color      Color del tema
 * @param string $layout     Layout: 'horizontal', 'vertical', 'card'
 * @param array  $actions    Acciones rápidas
 * @param array  $menu       Menú dropdown: [['label' => 'Perfil', 'url' => '#']]
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = $user ?? [];
$stats = $stats ?? [];
$badges = $badges ?? [];
$greeting = $greeting ?? '';
$color = $color ?? 'blue';
$layout = $layout ?? 'horizontal';
$actions = $actions ?? [];
$menu = $menu ?? [];

// Datos del usuario
$user_id = $user['id'] ?? get_current_user_id();
$user_name = $user['name'] ?? $user['display_name'] ?? '';
$user_avatar = $user['avatar'] ?? '';
$user_role = $user['role'] ?? '';
$user_verified = $user['verified'] ?? false;
$user_email = $user['email'] ?? '';
$user_url = $user['url'] ?? '';

// Si no hay datos, intentar obtener del usuario actual
if (empty($user_name) && $user_id) {
    $wp_user = get_userdata($user_id);
    if ($wp_user) {
        $user_name = $wp_user->display_name;
        $user_email = $wp_user->user_email;
        $user_avatar = get_avatar_url($user_id, ['size' => 80]);
    }
}

// Generar saludo si no hay
if (empty($greeting) && $user_name) {
    $hour = (int) current_time('G');
    if ($hour < 12) {
        $greeting = sprintf(__('Buenos días, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $user_name);
    } elseif ($hour < 18) {
        $greeting = sprintf(__('Buenas tardes, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $user_name);
    } else {
        $greeting = sprintf(__('Buenas noches, %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $user_name);
    }
}

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

$summary_id = 'user-summary-' . wp_rand(1000, 9999);
?>

<?php if ($layout === 'card'): ?>
    <!-- Layout Card -->
    <div class="flavor-user-summary bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
        <div class="flex items-center gap-4 mb-4">
            <?php if ($user_avatar): ?>
                <img src="<?php echo esc_url($user_avatar); ?>"
                     alt="<?php echo esc_attr($user_name); ?>"
                     class="w-16 h-16 rounded-full object-cover border-2 border-gray-100">
            <?php else: ?>
                <div class="w-16 h-16 rounded-full <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center">
                    <span class="text-2xl">👤</span>
                </div>
            <?php endif; ?>

            <div class="flex-1">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-gray-900"><?php echo esc_html($user_name); ?></h3>
                    <?php if ($user_verified): ?>
                        <span class="text-blue-500" title="<?php esc_attr_e('Verificado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">✓</span>
                    <?php endif; ?>
                </div>
                <?php if ($user_role): ?>
                    <span class="inline-flex px-2 py-0.5 text-xs rounded-full <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?>">
                        <?php echo esc_html($user_role); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($stats)): ?>
            <div class="grid grid-cols-<?php echo min(count($stats), 4); ?> gap-3 py-3 border-y border-gray-100">
                <?php foreach ($stats as $stat): ?>
                    <div class="text-center">
                        <p class="text-lg font-bold text-gray-900"><?php echo esc_html($stat['value'] ?? 0); ?></p>
                        <p class="text-xs text-gray-500"><?php echo esc_html($stat['label'] ?? ''); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($actions)): ?>
            <div class="flex gap-2 mt-4">
                <?php foreach ($actions as $action):
                    $is_primary = $action['primary'] ?? false;
                    $btn_class = $is_primary
                        ? "flex-1 py-2 px-4 rounded-xl text-sm font-medium text-white {$color_classes['bg_solid']} hover:opacity-90"
                        : "flex-1 py-2 px-4 rounded-xl text-sm font-medium {$color_classes['bg']} {$color_classes['text']} hover:opacity-80";
                ?>
                    <?php if (!empty($action['action'])): ?>
                        <button onclick="<?php echo esc_attr($action['action']); ?>" class="<?php echo esc_attr($btn_class); ?>">
                            <?php echo esc_html($action['label'] ?? ''); ?>
                        </button>
                    <?php else: ?>
                        <a href="<?php echo esc_url($action['url'] ?? '#'); ?>" class="<?php echo esc_attr($btn_class); ?> text-center">
                            <?php echo esc_html($action['label'] ?? ''); ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($layout === 'vertical'): ?>
    <!-- Layout Vertical -->
    <div class="flavor-user-summary text-center">
        <?php if ($user_avatar): ?>
            <img src="<?php echo esc_url($user_avatar); ?>"
                 alt="<?php echo esc_attr($user_name); ?>"
                 class="w-20 h-20 rounded-full object-cover border-4 border-white shadow-lg mx-auto">
        <?php else: ?>
            <div class="w-20 h-20 rounded-full <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center mx-auto shadow-lg">
                <span class="text-3xl">👤</span>
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <h3 class="font-bold text-gray-900 flex items-center justify-center gap-2">
                <?php echo esc_html($user_name); ?>
                <?php if ($user_verified): ?>
                    <span class="text-blue-500">✓</span>
                <?php endif; ?>
            </h3>
            <?php if ($user_role): ?>
                <p class="text-sm text-gray-500"><?php echo esc_html($user_role); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($badges)): ?>
            <div class="flex justify-center gap-1 mt-2">
                <?php foreach ($badges as $badge): ?>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-600"
                          title="<?php echo esc_attr($badge['label'] ?? ''); ?>">
                        <?php echo esc_html($badge['icon'] ?? ''); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>
    <!-- Layout Horizontal (default) -->
    <div class="flavor-user-summary flex items-center justify-between" id="<?php echo esc_attr($summary_id); ?>">
        <div class="flex items-center gap-4">
            <?php if ($user_avatar): ?>
                <img src="<?php echo esc_url($user_avatar); ?>"
                     alt="<?php echo esc_attr($user_name); ?>"
                     class="w-12 h-12 rounded-full object-cover border-2 border-white shadow">
            <?php else: ?>
                <div class="w-12 h-12 rounded-full <?php echo esc_attr($color_classes['bg']); ?> flex items-center justify-center shadow">
                    <span class="text-xl">👤</span>
                </div>
            <?php endif; ?>

            <div>
                <?php if ($greeting): ?>
                    <h2 class="text-lg font-bold text-gray-900"><?php echo esc_html($greeting); ?></h2>
                <?php else: ?>
                    <h2 class="text-lg font-bold text-gray-900"><?php echo esc_html($user_name); ?></h2>
                <?php endif; ?>
                <p class="text-sm text-gray-500">
                    <?php if ($user_role): ?>
                        <?php echo esc_html($user_role); ?>
                    <?php else: ?>
                        <?php echo esc_html(current_time('l, j \d\e F')); ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <?php if (!empty($badges)): ?>
                <?php foreach (array_slice($badges, 0, 3) as $badge): ?>
                    <span class="px-2 py-1 text-xs rounded-full <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?>"
                          title="<?php echo esc_attr($badge['label'] ?? ''); ?>">
                        <?php echo esc_html($badge['icon'] ?? ''); ?> <?php echo esc_html($badge['label'] ?? ''); ?>
                    </span>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($menu)): ?>
                <div class="relative">
                    <button onclick="document.getElementById('<?php echo esc_js($summary_id); ?>-menu').classList.toggle('hidden')"
                            class="p-2 rounded-lg hover:bg-gray-100 transition-colors">
                        <span class="text-gray-400">▼</span>
                    </button>
                    <div id="<?php echo esc_attr($summary_id); ?>-menu"
                         class="hidden absolute right-0 top-full mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-10">
                        <?php foreach ($menu as $item): ?>
                            <?php if (!empty($item['divider'])): ?>
                                <hr class="my-1 border-gray-100">
                            <?php else: ?>
                                <a href="<?php echo esc_url($item['url'] ?? '#'); ?>"
                                   class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                    <?php if (!empty($item['icon'])): ?>
                                        <span><?php echo esc_html($item['icon']); ?></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($item['label'] ?? ''); ?>
                                </a>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
