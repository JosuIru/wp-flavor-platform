<?php
/**
 * Componente: Voting Buttons
 *
 * Botones de votación (up/down, like, apoyo, etc.)
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param int    $item_id    ID del item a votar
 * @param string $item_type  Tipo de item: post, comment, proposal, etc.
 * @param int    $upvotes    Votos positivos actuales
 * @param int    $downvotes  Votos negativos actuales
 * @param string $user_vote  Voto del usuario actual: up, down, null
 * @param string $variant    Variante: updown, like, support, star
 * @param string $size       Tamaño: sm, md, lg
 * @param bool   $show_count Mostrar contadores
 * @param bool   $animated   Animaciones al votar
 * @param string $module     Módulo para AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

$item_id = intval($item_id ?? 0);
$item_type = $item_type ?? 'post';
$upvotes = intval($upvotes ?? 0);
$downvotes = intval($downvotes ?? 0);
$user_vote = $user_vote ?? '';
$variant = $variant ?? 'updown';
$size = $size ?? 'md';
$show_count = $show_count ?? true;
$animated = $animated ?? true;
$module = $module ?? '';

$voting_id = 'flavor-voting-' . wp_rand(1000, 9999);
$is_logged_in = is_user_logged_in();
$net_votes = $upvotes - $downvotes;

// Tamaños
$size_config = [
    'sm' => ['btn' => 'w-8 h-8', 'icon' => 'w-4 h-4', 'text' => 'text-xs', 'gap' => 'gap-1'],
    'md' => ['btn' => 'w-10 h-10', 'icon' => 'w-5 h-5', 'text' => 'text-sm', 'gap' => 'gap-2'],
    'lg' => ['btn' => 'w-12 h-12', 'icon' => 'w-6 h-6', 'text' => 'text-base', 'gap' => 'gap-3'],
];
$sz = $size_config[$size] ?? $size_config['md'];
?>

<div class="flavor-voting-buttons inline-flex items-center <?php echo esc_attr($sz['gap']); ?>"
     id="<?php echo esc_attr($voting_id); ?>"
     data-item-id="<?php echo esc_attr($item_id); ?>"
     data-item-type="<?php echo esc_attr($item_type); ?>"
     data-module="<?php echo esc_attr($module); ?>"
     data-user-vote="<?php echo esc_attr($user_vote); ?>">

    <?php if ($variant === 'like'): ?>
        <!-- Variante Like (solo positivo) -->
        <button type="button"
                class="vote-btn vote-up flex items-center <?php echo esc_attr($sz['gap']); ?> px-3 py-1.5 rounded-full transition-all <?php echo $user_vote === 'up' ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-600 hover:bg-red-50 hover:text-red-500'; ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <svg class="<?php echo esc_attr($sz['icon']); ?> <?php echo $animated ? 'transition-transform' : ''; ?>" fill="<?php echo $user_vote === 'up' ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
            <?php if ($show_count): ?>
                <span class="vote-count <?php echo esc_attr($sz['text']); ?> font-medium"><?php echo number_format_i18n($upvotes); ?></span>
            <?php endif; ?>
        </button>

    <?php elseif ($variant === 'support'): ?>
        <!-- Variante Support (apoyo) -->
        <button type="button"
                class="vote-btn vote-up flex items-center <?php echo esc_attr($sz['gap']); ?> px-4 py-2 rounded-lg font-medium transition-all <?php echo $user_vote === 'up' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-green-50 hover:text-green-600'; ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
            </svg>
            <?php if ($show_count): ?>
                <span class="vote-count"><?php echo number_format_i18n($upvotes); ?> <?php esc_html_e('apoyos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <?php else: ?>
                <span><?php echo $user_vote === 'up' ? esc_html__('Apoyado', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Apoyar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            <?php endif; ?>
        </button>

    <?php elseif ($variant === 'star'): ?>
        <!-- Variante Star (favorito) -->
        <button type="button"
                class="vote-btn vote-up flex items-center <?php echo esc_attr($sz['gap']); ?> transition-all <?php echo $user_vote === 'up' ? 'text-yellow-500' : 'text-gray-400 hover:text-yellow-400'; ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <svg class="<?php echo esc_attr($sz['icon']); ?> <?php echo $animated ? 'transition-transform hover:scale-110' : ''; ?>" fill="<?php echo $user_vote === 'up' ? 'currentColor' : 'none'; ?>" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            <?php if ($show_count): ?>
                <span class="vote-count <?php echo esc_attr($sz['text']); ?>"><?php echo number_format_i18n($upvotes); ?></span>
            <?php endif; ?>
        </button>

    <?php else: ?>
        <!-- Variante Default (updown) -->
        <div class="flex items-center bg-gray-100 rounded-full p-1">
            <!-- Upvote -->
            <button type="button"
                    class="vote-btn vote-up <?php echo esc_attr($sz['btn']); ?> rounded-full flex items-center justify-center transition-all <?php echo $user_vote === 'up' ? 'bg-green-500 text-white' : 'text-gray-600 hover:bg-green-100 hover:text-green-600'; ?>"
                    title="<?php esc_attr_e('Votar a favor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
                <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
                </svg>
            </button>

            <!-- Count -->
            <?php if ($show_count): ?>
                <span class="vote-count px-2 <?php echo esc_attr($sz['text']); ?> font-semibold min-w-[2rem] text-center <?php echo $net_votes > 0 ? 'text-green-600' : ($net_votes < 0 ? 'text-red-600' : 'text-gray-600'); ?>">
                    <?php echo $net_votes >= 0 ? '+' . number_format_i18n($net_votes) : number_format_i18n($net_votes); ?>
                </span>
            <?php endif; ?>

            <!-- Downvote -->
            <button type="button"
                    class="vote-btn vote-down <?php echo esc_attr($sz['btn']); ?> rounded-full flex items-center justify-center transition-all <?php echo $user_vote === 'down' ? 'bg-red-500 text-white' : 'text-gray-600 hover:bg-red-100 hover:text-red-600'; ?>"
                    title="<?php esc_attr_e('Votar en contra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
                <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('<?php echo esc_js($voting_id); ?>');
    if (!container) return;

    const buttons = container.querySelectorAll('.vote-btn');
    let currentVote = container.dataset.userVote;

    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            // Check login
            if (this.dataset.loginRequired) {
                <?php if (!$is_logged_in): ?>
                alert('<?php esc_html_e('Inicia sesión para votar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
                return;
                <?php endif; ?>
            }

            const isUpvote = this.classList.contains('vote-up');
            const voteType = isUpvote ? 'up' : 'down';
            const newVote = currentVote === voteType ? '' : voteType;

            // Animación
            <?php if ($animated): ?>
            const icon = this.querySelector('svg');
            if (icon) {
                icon.style.transform = 'scale(1.3)';
                setTimeout(() => icon.style.transform = '', 200);
            }
            <?php endif; ?>

            // AJAX
            fetch(flavorAjax?.url || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'flavor_vote',
                    item_id: container.dataset.itemId,
                    item_type: container.dataset.itemType,
                    module: container.dataset.module,
                    vote: newVote,
                    _wpnonce: flavorAjax?.nonce || ''
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    currentVote = newVote;
                    container.dataset.userVote = newVote;

                    // Actualizar UI
                    updateVoteUI(data.data.upvotes, data.data.downvotes, newVote);
                }
            })
            .catch(err => console.error('Vote error:', err));
        });
    });

    function updateVoteUI(upvotes, downvotes, vote) {
        const upBtn = container.querySelector('.vote-up');
        const downBtn = container.querySelector('.vote-down');
        const countEl = container.querySelector('.vote-count');
        const variant = '<?php echo esc_js($variant); ?>';

        if (variant === 'updown') {
            // Reset
            upBtn?.classList.remove('bg-green-500', 'text-white');
            upBtn?.classList.add('text-gray-600');
            downBtn?.classList.remove('bg-red-500', 'text-white');
            downBtn?.classList.add('text-gray-600');

            // Set active
            if (vote === 'up') {
                upBtn?.classList.add('bg-green-500', 'text-white');
                upBtn?.classList.remove('text-gray-600');
            } else if (vote === 'down') {
                downBtn?.classList.add('bg-red-500', 'text-white');
                downBtn?.classList.remove('text-gray-600');
            }

            // Update count
            if (countEl) {
                const net = upvotes - downvotes;
                countEl.textContent = (net >= 0 ? '+' : '') + net.toLocaleString();
                countEl.classList.remove('text-green-600', 'text-red-600', 'text-gray-600');
                countEl.classList.add(net > 0 ? 'text-green-600' : (net < 0 ? 'text-red-600' : 'text-gray-600'));
            }
        } else {
            // Like, support, star variants
            if (vote === 'up') {
                upBtn?.classList.add(variant === 'like' ? 'bg-red-100' : (variant === 'support' ? 'bg-green-600' : ''), variant === 'like' ? 'text-red-600' : 'text-white');
            } else {
                upBtn?.classList.remove('bg-red-100', 'bg-green-600', 'text-red-600', 'text-white', 'text-yellow-500');
                upBtn?.classList.add('bg-gray-100', 'text-gray-600');
            }

            if (countEl) {
                countEl.textContent = upvotes.toLocaleString();
            }
        }
    }
});
</script>
