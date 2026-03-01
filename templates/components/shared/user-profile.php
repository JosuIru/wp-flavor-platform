<?php
/**
 * Componente: User Profile
 *
 * Vista de perfil de usuario completa.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $user       Datos del usuario: id, name, email, avatar, bio, role, verified, member_since
 * @param array  $stats      Estadísticas: [['value' => 125, 'label' => 'Publicaciones', 'icon' => '📝']]
 * @param array  $badges     Insignias: [['icon' => '🏆', 'label' => 'Top Contributor', 'color' => 'amber']]
 * @param array  $social     Redes sociales: [['type' => 'twitter', 'url' => '...', 'icon' => '🐦']]
 * @param array  $tabs       Pestañas de contenido: [['id' => 'posts', 'label' => 'Posts', 'content' => '']]
 * @param array  $actions    Acciones: [['label' => 'Seguir', 'icon' => '➕', 'action' => 'follow()', 'primary' => true]]
 * @param string $color      Color del tema
 * @param string $layout     Layout: 'full', 'compact', 'card'
 * @param bool   $editable   Mostrar opciones de edición
 */

if (!defined('ABSPATH')) {
    exit;
}

$user = $user ?? [];
$stats = $stats ?? [];
$badges = $badges ?? [];
$social = $social ?? [];
$tabs = $tabs ?? [];
$actions = $actions ?? [];
$color = $color ?? 'blue';
$layout = $layout ?? 'full';
$editable = $editable ?? false;

// Extraer datos del usuario
$user_id = $user['id'] ?? 0;
$user_name = $user['name'] ?? $user['display_name'] ?? __('Usuario', 'flavor-chat-ia');
$user_email = $user['email'] ?? '';
$user_avatar = $user['avatar'] ?? '';
$user_bio = $user['bio'] ?? $user['description'] ?? '';
$user_role = $user['role'] ?? '';
$user_verified = $user['verified'] ?? false;
$user_member_since = $user['member_since'] ?? '';
$user_location = $user['location'] ?? '';
$user_website = $user['website'] ?? '';
$user_cover = $user['cover_image'] ?? '';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}
?>

<div class="flavor-user-profile bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

    <?php if ($layout !== 'compact'): ?>
        <!-- Cover Image -->
        <div class="relative h-32 md:h-48 bg-gradient-to-br from-<?php echo esc_attr($color); ?>-400 to-<?php echo esc_attr($color); ?>-600">
            <?php if ($user_cover): ?>
                <img src="<?php echo esc_url($user_cover); ?>" alt="" class="w-full h-full object-cover">
            <?php endif; ?>

            <?php if ($editable): ?>
                <button class="absolute top-4 right-4 p-2 bg-white/20 backdrop-blur-sm rounded-lg text-white hover:bg-white/30 transition-colors">
                    📷 <?php esc_html_e('Cambiar portada', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="<?php echo $layout !== 'compact' ? '-mt-16 md:-mt-20' : ''; ?> px-6 pb-6">
        <div class="flex flex-col md:flex-row md:items-end gap-4">
            <!-- Avatar -->
            <div class="relative <?php echo $layout !== 'compact' ? 'flex-shrink-0' : ''; ?>">
                <?php if ($user_avatar): ?>
                    <img src="<?php echo esc_url($user_avatar); ?>"
                         alt="<?php echo esc_attr($user_name); ?>"
                         class="<?php echo $layout === 'compact' ? 'w-16 h-16' : 'w-24 h-24 md:w-32 md:h-32'; ?> rounded-full object-cover border-4 border-white shadow-lg">
                <?php else: ?>
                    <div class="<?php echo $layout === 'compact' ? 'w-16 h-16' : 'w-24 h-24 md:w-32 md:h-32'; ?> rounded-full <?php echo esc_attr($color_classes['bg']); ?> border-4 border-white shadow-lg flex items-center justify-center">
                        <span class="<?php echo $layout === 'compact' ? 'text-2xl' : 'text-4xl'; ?>">👤</span>
                    </div>
                <?php endif; ?>

                <?php if ($user_verified): ?>
                    <span class="absolute bottom-0 right-0 w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center border-2 border-white"
                          title="<?php esc_attr_e('Verificado', 'flavor-chat-ia'); ?>">
                        <span class="text-white text-sm">✓</span>
                    </span>
                <?php endif; ?>

                <?php if ($editable): ?>
                    <button class="absolute bottom-0 right-0 w-8 h-8 bg-gray-800 rounded-full flex items-center justify-center border-2 border-white hover:bg-gray-700 transition-colors">
                        <span class="text-white text-xs">📷</span>
                    </button>
                <?php endif; ?>
            </div>

            <!-- User Info -->
            <div class="flex-1 <?php echo $layout !== 'compact' ? 'pt-4 md:pt-0' : ''; ?>">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-xl md:text-2xl font-bold text-gray-900 flex items-center gap-2">
                            <?php echo esc_html($user_name); ?>
                            <?php if ($user_role): ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo esc_attr($color_classes['bg']); ?> <?php echo esc_attr($color_classes['text']); ?>">
                                    <?php echo esc_html($user_role); ?>
                                </span>
                            <?php endif; ?>
                        </h1>

                        <?php if ($user_email && $editable): ?>
                            <p class="text-sm text-gray-500"><?php echo esc_html($user_email); ?></p>
                        <?php endif; ?>

                        <div class="flex flex-wrap items-center gap-3 mt-2 text-sm text-gray-500">
                            <?php if ($user_location): ?>
                                <span class="flex items-center gap-1">
                                    📍 <?php echo esc_html($user_location); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($user_member_since): ?>
                                <span class="flex items-center gap-1">
                                    📅 <?php echo esc_html__('Desde', 'flavor-chat-ia'); ?> <?php echo esc_html($user_member_since); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($user_website): ?>
                                <a href="<?php echo esc_url($user_website); ?>" target="_blank" rel="noopener"
                                   class="flex items-center gap-1 <?php echo esc_attr($color_classes['text']); ?> hover:underline">
                                    🔗 <?php echo esc_html(parse_url($user_website, PHP_URL_HOST)); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <?php if (!empty($actions)): ?>
                        <div class="flex items-center gap-2">
                            <?php foreach ($actions as $action):
                                $is_primary = $action['primary'] ?? false;
                                $action_label = $action['label'] ?? '';
                                $action_icon = $action['icon'] ?? '';
                                $action_onclick = $action['action'] ?? '';
                                $action_url = $action['url'] ?? '';

                                if ($is_primary) {
                                    $btn_class = "px-4 py-2 rounded-xl text-sm font-medium text-white {$color_classes['bg_solid']} hover:opacity-90 transition-all";
                                } else {
                                    $btn_class = "px-4 py-2 rounded-xl text-sm font-medium {$color_classes['bg']} {$color_classes['text']} hover:opacity-80 transition-all";
                                }
                            ?>
                                <?php if ($action_onclick): ?>
                                    <button onclick="<?php echo esc_attr($action_onclick); ?>" class="<?php echo esc_attr($btn_class); ?> flex items-center gap-2">
                                        <?php if ($action_icon): ?><span><?php echo esc_html($action_icon); ?></span><?php endif; ?>
                                        <?php echo esc_html($action_label); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($action_url ?: '#'); ?>" class="<?php echo esc_attr($btn_class); ?> flex items-center gap-2">
                                        <?php if ($action_icon): ?><span><?php echo esc_html($action_icon); ?></span><?php endif; ?>
                                        <?php echo esc_html($action_label); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Bio -->
        <?php if ($user_bio): ?>
            <p class="mt-4 text-gray-600 max-w-2xl"><?php echo esc_html($user_bio); ?></p>
        <?php endif; ?>

        <!-- Social Links -->
        <?php if (!empty($social)): ?>
            <div class="flex items-center gap-3 mt-4">
                <?php foreach ($social as $link): ?>
                    <a href="<?php echo esc_url($link['url'] ?? '#'); ?>"
                       target="_blank"
                       rel="noopener"
                       class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 transition-colors"
                       title="<?php echo esc_attr($link['label'] ?? $link['type'] ?? ''); ?>">
                        <span><?php echo esc_html($link['icon'] ?? '🔗'); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Badges -->
        <?php if (!empty($badges)): ?>
            <div class="flex flex-wrap gap-2 mt-4">
                <?php foreach ($badges as $badge):
                    $badge_color = $badge['color'] ?? 'gray';
                    if (function_exists('flavor_get_color_classes')) {
                        $badge_classes = flavor_get_color_classes($badge_color);
                    } else {
                        $badge_classes = ['bg' => 'bg-gray-100', 'text' => 'text-gray-700'];
                    }
                ?>
                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm <?php echo esc_attr($badge_classes['bg']); ?> <?php echo esc_attr($badge_classes['text']); ?>">
                        <?php if (!empty($badge['icon'])): ?>
                            <span><?php echo esc_html($badge['icon']); ?></span>
                        <?php endif; ?>
                        <?php echo esc_html($badge['label'] ?? ''); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Stats -->
        <?php if (!empty($stats)): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6 p-4 bg-gray-50 rounded-xl">
                <?php foreach ($stats as $stat): ?>
                    <div class="text-center">
                        <?php if (!empty($stat['icon'])): ?>
                            <span class="text-2xl"><?php echo esc_html($stat['icon']); ?></span>
                        <?php endif; ?>
                        <p class="text-2xl font-bold text-gray-900"><?php echo esc_html($stat['value'] ?? 0); ?></p>
                        <p class="text-sm text-gray-500"><?php echo esc_html($stat['label'] ?? ''); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tabs Content -->
    <?php if (!empty($tabs)): ?>
        <div class="border-t border-gray-100">
            <?php
            include __DIR__ . '/tabs.php';
            ?>
        </div>
    <?php endif; ?>
</div>
