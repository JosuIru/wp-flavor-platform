<?php
/**
 * Componente: Subscription Toggle
 *
 * Toggle para suscribirse/desuscribirse a elementos (newsletter, notificaciones, seguir, etc.)
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param int    $item_id       ID del item
 * @param string $item_type     Tipo: newsletter, topic, user, entity, notifications
 * @param bool   $subscribed    Estado actual de suscripción
 * @param string $subscribe_text Texto para suscribirse
 * @param string $unsubscribe_text Texto para desuscribirse
 * @param string $subscribe_icon Icono para suscribirse
 * @param string $unsubscribe_icon Icono para desuscribirse
 * @param string $variant       Variante: button, toggle, chip, icon
 * @param string $size          Tamaño: sm, md, lg
 * @param int    $subscribers   Número de suscriptores (opcional)
 * @param string $module        Módulo para AJAX
 */

if (!defined('ABSPATH')) {
    exit;
}

$item_id = intval($item_id ?? 0);
$item_type = $item_type ?? 'newsletter';
$subscribed = $subscribed ?? false;
$subscribe_text = $subscribe_text ?? __('Suscribirse', FLAVOR_PLATFORM_TEXT_DOMAIN);
$unsubscribe_text = $unsubscribe_text ?? __('Suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN);
$subscribe_icon = $subscribe_icon ?? '🔔';
$unsubscribe_icon = $unsubscribe_icon ?? '✓';
$variant = $variant ?? 'button';
$size = $size ?? 'md';
$subscribers = $subscribers ?? null;
$module = $module ?? '';

$toggle_id = 'flavor-subscription-' . wp_rand(1000, 9999);
$is_logged_in = is_user_logged_in();

// Tamaños
$size_config = [
    'sm' => ['btn' => 'px-3 py-1.5 text-xs', 'icon' => 'w-4 h-4', 'toggle' => 'w-10 h-5'],
    'md' => ['btn' => 'px-4 py-2 text-sm', 'icon' => 'w-5 h-5', 'toggle' => 'w-12 h-6'],
    'lg' => ['btn' => 'px-5 py-2.5 text-base', 'icon' => 'w-6 h-6', 'toggle' => 'w-14 h-7'],
];
$sz = $size_config[$size] ?? $size_config['md'];
?>

<div class="flavor-subscription-toggle inline-flex items-center gap-2"
     id="<?php echo esc_attr($toggle_id); ?>"
     data-item-id="<?php echo esc_attr($item_id); ?>"
     data-item-type="<?php echo esc_attr($item_type); ?>"
     data-module="<?php echo esc_attr($module); ?>"
     data-subscribed="<?php echo $subscribed ? 'true' : 'false'; ?>">

    <?php if ($variant === 'toggle'): ?>
        <!-- Variante Toggle Switch -->
        <button type="button"
                role="switch"
                aria-checked="<?php echo $subscribed ? 'true' : 'false'; ?>"
                class="subscription-btn relative inline-flex <?php echo esc_attr($sz['toggle']); ?> shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 <?php echo $subscribed ? 'bg-blue-600' : 'bg-gray-200'; ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <span class="sr-only"><?php echo esc_html($subscribe_text); ?></span>
            <span class="toggle-dot pointer-events-none inline-block transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out <?php echo $subscribed ? 'translate-x-5' : 'translate-x-0'; ?>"
                  style="height: calc(100% - 4px); width: calc(50% - 2px); margin: 2px;"></span>
        </button>
        <span class="subscription-label text-sm text-gray-700">
            <?php echo $subscribed ? esc_html($unsubscribe_text) : esc_html($subscribe_text); ?>
        </span>

    <?php elseif ($variant === 'chip'): ?>
        <!-- Variante Chip -->
        <button type="button"
                class="subscription-btn inline-flex items-center gap-1.5 <?php echo esc_attr($sz['btn']); ?> rounded-full font-medium transition-all <?php echo $subscribed ? 'bg-blue-100 text-blue-700 hover:bg-blue-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <span class="subscription-icon"><?php echo $subscribed ? esc_html($unsubscribe_icon) : esc_html($subscribe_icon); ?></span>
            <span class="subscription-text"><?php echo $subscribed ? esc_html($unsubscribe_text) : esc_html($subscribe_text); ?></span>
        </button>

    <?php elseif ($variant === 'icon'): ?>
        <!-- Variante Icon Only -->
        <button type="button"
                class="subscription-btn p-2 rounded-full transition-all <?php echo $subscribed ? 'bg-blue-100 text-blue-600' : 'bg-gray-100 text-gray-600 hover:bg-blue-50 hover:text-blue-500'; ?>"
                title="<?php echo $subscribed ? esc_attr($unsubscribe_text) : esc_attr($subscribe_text); ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <?php if ($subscribed): ?>
                <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
            <?php else: ?>
                <svg class="<?php echo esc_attr($sz['icon']); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            <?php endif; ?>
        </button>
        <?php if ($subscribers !== null): ?>
            <span class="text-sm text-gray-500"><?php echo number_format_i18n($subscribers); ?></span>
        <?php endif; ?>

    <?php else: ?>
        <!-- Variante Button (default) -->
        <button type="button"
                class="subscription-btn inline-flex items-center gap-2 <?php echo esc_attr($sz['btn']); ?> rounded-lg font-medium transition-all <?php echo $subscribed ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 hover:border-blue-500 hover:text-blue-600'; ?>"
                <?php if (!$is_logged_in): ?>data-login-required="true"<?php endif; ?>>
            <span class="subscription-icon"><?php echo $subscribed ? esc_html($unsubscribe_icon) : esc_html($subscribe_icon); ?></span>
            <span class="subscription-text"><?php echo $subscribed ? esc_html($unsubscribe_text) : esc_html($subscribe_text); ?></span>
        </button>
        <?php if ($subscribers !== null): ?>
            <span class="text-sm text-gray-500"><?php echo number_format_i18n($subscribers); ?> <?php esc_html_e('suscriptores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('<?php echo esc_js($toggle_id); ?>');
    if (!container) return;

    const btn = container.querySelector('.subscription-btn');
    let subscribed = container.dataset.subscribed === 'true';

    const texts = {
        subscribe: '<?php echo esc_js($subscribe_text); ?>',
        unsubscribe: '<?php echo esc_js($unsubscribe_text); ?>',
        subscribeIcon: '<?php echo esc_js($subscribe_icon); ?>',
        unsubscribeIcon: '<?php echo esc_js($unsubscribe_icon); ?>'
    };

    btn.addEventListener('click', function() {
        if (this.dataset.loginRequired) {
            alert('<?php esc_html_e('Inicia sesión para suscribirte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>');
            return;
        }

        const newState = !subscribed;

        // Optimistic UI update
        subscribed = newState;
        container.dataset.subscribed = newState ? 'true' : 'false';
        updateUI(newState);

        // AJAX
        fetch(flavorAjax?.url || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'flavor_toggle_subscription',
                item_id: container.dataset.itemId,
                item_type: container.dataset.itemType,
                module: container.dataset.module,
                subscribe: newState ? '1' : '0',
                _wpnonce: flavorAjax?.nonce || ''
            })
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                // Revert on error
                subscribed = !newState;
                container.dataset.subscribed = !newState ? 'true' : 'false';
                updateUI(!newState);
            }
        })
        .catch(() => {
            // Revert on error
            subscribed = !newState;
            container.dataset.subscribed = !newState ? 'true' : 'false';
            updateUI(!newState);
        });
    });

    function updateUI(isSubscribed) {
        const variant = '<?php echo esc_js($variant); ?>';
        const iconEl = container.querySelector('.subscription-icon');
        const textEl = container.querySelector('.subscription-text');
        const labelEl = container.querySelector('.subscription-label');
        const dotEl = container.querySelector('.toggle-dot');

        if (variant === 'toggle') {
            btn.setAttribute('aria-checked', isSubscribed);
            btn.classList.toggle('bg-blue-600', isSubscribed);
            btn.classList.toggle('bg-gray-200', !isSubscribed);
            dotEl?.classList.toggle('translate-x-5', isSubscribed);
            dotEl?.classList.toggle('translate-x-0', !isSubscribed);
            if (labelEl) labelEl.textContent = isSubscribed ? texts.unsubscribe : texts.subscribe;
        } else if (variant === 'chip') {
            btn.classList.toggle('bg-blue-100', isSubscribed);
            btn.classList.toggle('text-blue-700', isSubscribed);
            btn.classList.toggle('bg-gray-100', !isSubscribed);
            btn.classList.toggle('text-gray-700', !isSubscribed);
            if (iconEl) iconEl.textContent = isSubscribed ? texts.unsubscribeIcon : texts.subscribeIcon;
            if (textEl) textEl.textContent = isSubscribed ? texts.unsubscribe : texts.subscribe;
        } else if (variant === 'icon') {
            btn.classList.toggle('bg-blue-100', isSubscribed);
            btn.classList.toggle('text-blue-600', isSubscribed);
            btn.classList.toggle('bg-gray-100', !isSubscribed);
            btn.classList.toggle('text-gray-600', !isSubscribed);
            btn.title = isSubscribed ? texts.unsubscribe : texts.subscribe;
            // Update SVG
            const svg = btn.querySelector('svg');
            if (svg) {
                svg.innerHTML = isSubscribed
                    ? '<path fill="currentColor" d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>';
                svg.setAttribute('fill', isSubscribed ? 'currentColor' : 'none');
                svg.setAttribute('stroke', isSubscribed ? 'none' : 'currentColor');
            }
        } else {
            // Button variant
            btn.classList.toggle('bg-blue-600', isSubscribed);
            btn.classList.toggle('text-white', isSubscribed);
            btn.classList.toggle('bg-white', !isSubscribed);
            btn.classList.toggle('border', !isSubscribed);
            btn.classList.toggle('text-gray-700', !isSubscribed);
            if (iconEl) iconEl.textContent = isSubscribed ? texts.unsubscribeIcon : texts.subscribeIcon;
            if (textEl) textEl.textContent = isSubscribed ? texts.unsubscribe : texts.subscribe;
        }
    }
});
</script>
