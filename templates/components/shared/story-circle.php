<?php
/**
 * Componente: Story Circle
 *
 * Círculo de historia/estado estilo Instagram/WhatsApp.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param array  $stories    Array de historias: [['id' => x, 'image' => '', 'user' => '', 'avatar' => '', 'seen' => false, 'url' => '']]
 * @param string $size       Tamaño: sm, md, lg
 * @param bool   $show_names Mostrar nombres
 * @param bool   $add_story  Mostrar botón añadir historia
 * @param string $add_url    URL para añadir historia
 * @param bool   $scrollable Scroll horizontal
 */

if (!defined('ABSPATH')) {
    exit;
}

$stories = $stories ?? [];
$size = $size ?? 'md';
$show_names = $show_names ?? true;
$add_story = $add_story ?? true;
$add_url = $add_url ?? '#';
$scrollable = $scrollable ?? true;

// Tamaños
$size_config = [
    'sm' => ['circle' => 'w-14 h-14', 'ring' => 'p-0.5', 'inner' => 'p-0.5', 'text' => 'text-[10px]', 'width' => 'w-16'],
    'md' => ['circle' => 'w-16 h-16', 'ring' => 'p-[3px]', 'inner' => 'p-0.5', 'text' => 'text-xs', 'width' => 'w-20'],
    'lg' => ['circle' => 'w-20 h-20', 'ring' => 'p-1', 'inner' => 'p-0.5', 'text' => 'text-sm', 'width' => 'w-24'],
];
$sz = $size_config[$size] ?? $size_config['md'];
?>

<div class="flavor-story-circles <?php echo $scrollable ? 'overflow-x-auto' : ''; ?>">
    <div class="flex gap-4 <?php echo $scrollable ? 'pb-2' : 'flex-wrap'; ?>">

        <?php if ($add_story): ?>
            <!-- Añadir historia -->
            <a href="<?php echo esc_url($add_url); ?>" class="flex flex-col items-center gap-1 flex-shrink-0 <?php echo esc_attr($sz['width']); ?>">
                <div class="<?php echo esc_attr($sz['circle']); ?> rounded-full bg-gray-100 flex items-center justify-center relative">
                    <!-- Avatar del usuario actual -->
                    <?php
                    $current_user = wp_get_current_user();
                    $current_avatar = get_avatar_url($current_user->ID, ['size' => 80]);
                    ?>
                    <?php if ($current_avatar): ?>
                        <img src="<?php echo esc_url($current_avatar); ?>" alt="" class="w-full h-full rounded-full object-cover opacity-50">
                    <?php endif; ?>
                    <!-- Botón + -->
                    <span class="absolute bottom-0 right-0 w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center text-white shadow-md border-2 border-white">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </span>
                </div>
                <?php if ($show_names): ?>
                    <span class="<?php echo esc_attr($sz['text']); ?> text-gray-600 truncate max-w-full">
                        <?php esc_html_e('Tu historia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endif; ?>

        <?php foreach ($stories as $story): ?>
            <?php
            $seen = $story['seen'] ?? false;
            $ring_class = $seen
                ? 'bg-gray-300'
                : 'bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-600';
            ?>
            <a href="<?php echo esc_url($story['url'] ?? '#'); ?>"
               class="flex flex-col items-center gap-1 flex-shrink-0 <?php echo esc_attr($sz['width']); ?> group"
               data-story-id="<?php echo esc_attr($story['id'] ?? ''); ?>">

                <!-- Ring -->
                <div class="<?php echo esc_attr($ring_class); ?> <?php echo esc_attr($sz['ring']); ?> rounded-full">
                    <!-- Inner white border -->
                    <div class="bg-white <?php echo esc_attr($sz['inner']); ?> rounded-full">
                        <!-- Avatar/imagen -->
                        <div class="<?php echo esc_attr($sz['circle']); ?> rounded-full overflow-hidden">
                            <img src="<?php echo esc_url($story['avatar'] ?? $story['image'] ?? ''); ?>"
                                 alt="<?php echo esc_attr($story['user'] ?? ''); ?>"
                                 class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-200">
                        </div>
                    </div>
                </div>

                <?php if ($show_names): ?>
                    <span class="<?php echo esc_attr($sz['text']); ?> <?php echo $seen ? 'text-gray-400' : 'text-gray-700'; ?> truncate max-w-full">
                        <?php echo esc_html($story['user'] ?? ''); ?>
                    </span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
