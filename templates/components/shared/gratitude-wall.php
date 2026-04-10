<?php
/**
 * Componente: Gratitude Wall
 *
 * Muro de agradecimientos/testimonios tipo post-its.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $messages    Array de mensajes: [['id' => x, 'message' => '', 'author' => '', 'avatar' => '', 'date' => '', 'color' => '', 'likes' => 0]]
 * @param string $title       Título del muro
 * @param bool   $allow_add   Permitir añadir mensajes
 * @param string $add_url     URL para añadir mensajes
 * @param int    $columns     Número de columnas (2-4)
 * @param string $variant     Variante: postits, cards, minimal
 * @param bool   $show_likes  Mostrar likes
 * @param string $empty_text  Texto si no hay mensajes
 */

if (!defined('ABSPATH')) {
    exit;
}

$messages = $messages ?? [];
$title = $title ?? __('Muro de agradecimientos', FLAVOR_PLATFORM_TEXT_DOMAIN);
$allow_add = $allow_add ?? true;
$add_url = $add_url ?? '';
$columns = max(2, min(4, intval($columns ?? 3)));
$variant = $variant ?? 'postits';
$show_likes = $show_likes ?? true;
$empty_text = $empty_text ?? __('Sé el primero en dejar un mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN);

$wall_id = 'flavor-gratitude-' . wp_rand(1000, 9999);

// Colores para post-its
$postit_colors = [
    'yellow' => 'bg-yellow-200 shadow-yellow-300/50',
    'pink'   => 'bg-pink-200 shadow-pink-300/50',
    'blue'   => 'bg-blue-200 shadow-blue-300/50',
    'green'  => 'bg-green-200 shadow-green-300/50',
    'purple' => 'bg-purple-200 shadow-purple-300/50',
    'orange' => 'bg-orange-200 shadow-orange-300/50',
];

// Rotaciones aleatorias para post-its
$rotations = ['-rotate-2', '-rotate-1', 'rotate-0', 'rotate-1', 'rotate-2'];
?>

<div class="flavor-gratitude-wall" id="<?php echo esc_attr($wall_id); ?>">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
            <span>💝</span>
            <?php echo esc_html($title); ?>
        </h3>

        <?php if ($allow_add): ?>
            <button type="button"
                    class="gratitude-add-btn inline-flex items-center gap-2 px-4 py-2 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-full shadow-md hover:shadow-lg transition-all"
                    data-url="<?php echo esc_url($add_url); ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <?php esc_html_e('Agradecer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
    </div>

    <?php if (empty($messages)): ?>
        <!-- Estado vacío -->
        <div class="text-center py-12 bg-gradient-to-br from-pink-50 to-yellow-50 rounded-2xl border-2 border-dashed border-pink-200">
            <span class="text-6xl">💌</span>
            <p class="mt-4 text-gray-600"><?php echo esc_html($empty_text); ?></p>
            <?php if ($allow_add): ?>
                <button type="button"
                        class="gratitude-add-btn mt-4 inline-flex items-center gap-2 px-6 py-3 bg-pink-500 hover:bg-pink-600 text-white font-medium rounded-full shadow-md hover:shadow-lg transition-all"
                        data-url="<?php echo esc_url($add_url); ?>">
                    <?php esc_html_e('Deja tu mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            <?php endif; ?>
        </div>

    <?php elseif ($variant === 'postits'): ?>
        <!-- Variante Post-its -->
        <div class="grid grid-cols-2 md:grid-cols-<?php echo $columns; ?> gap-4">
            <?php foreach ($messages as $i => $msg): ?>
                <?php
                $color = $msg['color'] ?? array_keys($postit_colors)[array_rand(array_keys($postit_colors))];
                $color_class = $postit_colors[$color] ?? $postit_colors['yellow'];
                $rotation = $rotations[$i % count($rotations)];
                ?>
                <div class="<?php echo esc_attr($color_class); ?> <?php echo esc_attr($rotation); ?> p-4 rounded shadow-lg hover:shadow-xl hover:scale-105 hover:rotate-0 transition-all cursor-default">
                    <!-- Mensaje -->
                    <p class="text-gray-800 text-sm leading-relaxed mb-3 font-handwriting">
                        "<?php echo esc_html($msg['message'] ?? ''); ?>"
                    </p>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-2 border-t border-black/10">
                        <div class="flex items-center gap-2">
                            <?php if (!empty($msg['avatar'])): ?>
                                <img src="<?php echo esc_url($msg['avatar']); ?>" alt="" class="w-6 h-6 rounded-full">
                            <?php endif; ?>
                            <span class="text-xs font-medium text-gray-700">
                                <?php echo esc_html($msg['author'] ?? __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </span>
                        </div>

                        <?php if ($show_likes): ?>
                            <button type="button" class="like-btn flex items-center gap-1 text-xs text-gray-600 hover:text-pink-600 transition-colors" data-id="<?php echo esc_attr($msg['id'] ?? ''); ?>">
                                <span>❤️</span>
                                <span class="like-count"><?php echo intval($msg['likes'] ?? 0); ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

    <?php elseif ($variant === 'minimal'): ?>
        <!-- Variante Minimal -->
        <div class="space-y-3">
            <?php foreach ($messages as $msg): ?>
                <div class="flex items-start gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <span class="text-xl">💬</span>
                    <div class="flex-1 min-w-0">
                        <p class="text-gray-700 text-sm">"<?php echo esc_html($msg['message'] ?? ''); ?>"</p>
                        <p class="text-xs text-gray-500 mt-1">
                            — <?php echo esc_html($msg['author'] ?? __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                        </p>
                    </div>
                    <?php if ($show_likes && ($msg['likes'] ?? 0) > 0): ?>
                        <span class="flex items-center gap-1 text-xs text-pink-500">
                            ❤️ <?php echo intval($msg['likes']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- Variante Cards -->
        <div class="grid grid-cols-1 md:grid-cols-<?php echo $columns; ?> gap-4">
            <?php foreach ($messages as $msg): ?>
                <div class="bg-white rounded-xl shadow-md p-5 hover:shadow-lg transition-shadow">
                    <div class="flex items-start gap-3 mb-3">
                        <?php if (!empty($msg['avatar'])): ?>
                            <img src="<?php echo esc_url($msg['avatar']); ?>" alt="" class="w-10 h-10 rounded-full">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-pink-400 to-purple-500 flex items-center justify-center text-white text-lg">
                                💝
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-medium text-gray-900"><?php echo esc_html($msg['author'] ?? __('Anónimo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>
                            <?php if (!empty($msg['date'])): ?>
                                <p class="text-xs text-gray-500"><?php echo esc_html(human_time_diff(strtotime($msg['date']), current_time('timestamp'))); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <p class="text-gray-700 leading-relaxed">
                        <?php echo esc_html($msg['message'] ?? ''); ?>
                    </p>

                    <?php if ($show_likes): ?>
                        <div class="mt-4 pt-3 border-t border-gray-100">
                            <button type="button" class="like-btn flex items-center gap-1.5 text-sm text-gray-500 hover:text-pink-500 transition-colors" data-id="<?php echo esc_attr($msg['id'] ?? ''); ?>">
                                <span>❤️</span>
                                <span class="like-count"><?php echo intval($msg['likes'] ?? 0); ?></span>
                                <span><?php esc_html_e('me gusta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Modal para añadir mensaje -->
<?php if ($allow_add): ?>
<div id="<?php echo esc_attr($wall_id); ?>-modal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm gratitude-modal-backdrop"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform scale-95 opacity-0 transition-all gratitude-modal-content">
            <button type="button" class="gratitude-modal-close absolute top-4 right-4 text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            <div class="text-center mb-6">
                <span class="text-5xl">💝</span>
                <h3 class="text-xl font-bold text-gray-900 mt-2"><?php esc_html_e('Deja tu agradecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            </div>

            <form class="gratitude-form space-y-4" method="post" action="<?php echo esc_url($add_url); ?>">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        <?php esc_html_e('Tu mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *
                    </label>
                    <textarea name="message" rows="4" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500 resize-none"
                              placeholder="<?php esc_attr_e('Escribe aquí tu mensaje de agradecimiento...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>

                <?php if (!is_user_logged_in()): ?>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            <?php esc_html_e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="text" name="author"
                               class="w-full px-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-pink-500 focus:border-pink-500"
                               placeholder="<?php esc_attr_e('Opcional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <?php esc_html_e('Color del post-it', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <div class="flex gap-2">
                        <?php foreach (array_keys($postit_colors) as $color): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="color" value="<?php echo esc_attr($color); ?>" class="sr-only peer" <?php checked($color, 'yellow'); ?>>
                                <span class="block w-8 h-8 rounded-full <?php echo esc_attr(str_replace('shadow-', '', $postit_colors[$color])); ?> peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-pink-500"></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-gradient-to-r from-pink-500 to-purple-500 hover:from-pink-600 hover:to-purple-600 text-white font-bold rounded-xl shadow-md hover:shadow-lg transition-all">
                    💌 <?php esc_html_e('Enviar agradecimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const wall = document.getElementById('<?php echo esc_js($wall_id); ?>');
    const modal = document.getElementById('<?php echo esc_js($wall_id); ?>-modal');
    if (!wall || !modal) return;

    const modalContent = modal.querySelector('.gratitude-modal-content');

    // Abrir modal
    wall.querySelectorAll('.gratitude-add-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });
    });

    // Cerrar modal
    function closeModal() {
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    modal.querySelectorAll('.gratitude-modal-close, .gratitude-modal-backdrop').forEach(el => {
        el.addEventListener('click', closeModal);
    });

    // Likes
    wall.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const countEl = this.querySelector('.like-count');
            const current = parseInt(countEl.textContent) || 0;

            // Optimistic update
            countEl.textContent = current + 1;
            this.classList.add('text-pink-500');

            // AJAX
            fetch(flavorAjax?.url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'flavor_gratitude_like',
                    id: id,
                    _wpnonce: flavorAjax?.nonce || ''
                })
            }).catch(() => {
                countEl.textContent = current; // Revert
            });
        });
    });
});
</script>
<?php endif; ?>

<style>
.font-handwriting {
    font-family: 'Comic Sans MS', 'Chalkboard', cursive;
}
</style>
