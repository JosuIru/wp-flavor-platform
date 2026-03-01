<?php
/**
 * Componente: Countdown
 *
 * Temporizador de cuenta regresiva para eventos, ofertas, etc.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $target_date  Fecha objetivo (YYYY-MM-DD HH:MM:SS o timestamp)
 * @param string $title        Título del countdown
 * @param string $expired_text Texto cuando expira
 * @param string $expired_url  URL de redirección al expirar
 * @param bool   $show_days    Mostrar días
 * @param bool   $show_hours   Mostrar horas
 * @param bool   $show_minutes Mostrar minutos
 * @param bool   $show_seconds Mostrar segundos
 * @param string $size         Tamaño: sm, md, lg, xl
 * @param string $variant      Variante: default, compact, flip, minimal
 * @param string $color        Color del tema
 */

if (!defined('ABSPATH')) {
    exit;
}

$target_date = $target_date ?? '';
$title = $title ?? '';
$expired_text = $expired_text ?? __('¡Tiempo agotado!', 'flavor-chat-ia');
$expired_url = $expired_url ?? '';
$show_days = $show_days ?? true;
$show_hours = $show_hours ?? true;
$show_minutes = $show_minutes ?? true;
$show_seconds = $show_seconds ?? true;
$size = $size ?? 'md';
$variant = $variant ?? 'default';
$color = $color ?? 'blue';

// Convertir fecha a timestamp
$target_timestamp = is_numeric($target_date) ? intval($target_date) : strtotime($target_date);
$now = time();
$remaining = max(0, $target_timestamp - $now);

// ID único
$countdown_id = 'flavor-countdown-' . wp_rand(1000, 9999);

// Configuración de tamaño
$size_config = [
    'sm' => ['box' => 'w-12 h-12', 'number' => 'text-lg', 'label' => 'text-[10px]', 'gap' => 'gap-1'],
    'md' => ['box' => 'w-16 h-16', 'number' => 'text-2xl', 'label' => 'text-xs', 'gap' => 'gap-2'],
    'lg' => ['box' => 'w-20 h-20', 'number' => 'text-3xl', 'label' => 'text-sm', 'gap' => 'gap-3'],
    'xl' => ['box' => 'w-24 h-24', 'number' => 'text-4xl', 'label' => 'text-base', 'gap' => 'gap-4'],
];
$sz = $size_config[$size] ?? $size_config['md'];

// Colores
$color_classes = [
    'blue'   => 'bg-blue-600 text-white',
    'green'  => 'bg-green-600 text-white',
    'red'    => 'bg-red-600 text-white',
    'purple' => 'bg-purple-600 text-white',
    'orange' => 'bg-orange-500 text-white',
    'gray'   => 'bg-gray-700 text-white',
    'white'  => 'bg-white text-gray-900 shadow-lg',
];
$color_class = $color_classes[$color] ?? $color_classes['blue'];

// Labels
$labels = [
    'days'    => __('Días', 'flavor-chat-ia'),
    'hours'   => __('Horas', 'flavor-chat-ia'),
    'minutes' => __('Min', 'flavor-chat-ia'),
    'seconds' => __('Seg', 'flavor-chat-ia'),
];
?>

<div class="flavor-countdown text-center"
     id="<?php echo esc_attr($countdown_id); ?>"
     data-target="<?php echo esc_attr($target_timestamp); ?>"
     data-expired-text="<?php echo esc_attr($expired_text); ?>"
     data-expired-url="<?php echo esc_attr($expired_url); ?>">

    <?php if ($title): ?>
        <p class="text-gray-600 mb-3 <?php echo $size === 'xl' ? 'text-lg' : 'text-sm'; ?>">
            <?php echo esc_html($title); ?>
        </p>
    <?php endif; ?>

    <?php if ($remaining > 0): ?>
        <?php if ($variant === 'minimal'): ?>
            <!-- Variante Minimal -->
            <div class="flavor-countdown-display inline-flex items-center <?php echo esc_attr($sz['gap']); ?> font-mono <?php echo esc_attr($sz['number']); ?> font-bold text-gray-900">
                <?php if ($show_days): ?>
                    <span class="countdown-days">00</span><span class="text-gray-400">:</span>
                <?php endif; ?>
                <?php if ($show_hours): ?>
                    <span class="countdown-hours">00</span><span class="text-gray-400">:</span>
                <?php endif; ?>
                <?php if ($show_minutes): ?>
                    <span class="countdown-minutes">00</span>
                    <?php if ($show_seconds): ?><span class="text-gray-400">:</span><?php endif; ?>
                <?php endif; ?>
                <?php if ($show_seconds): ?>
                    <span class="countdown-seconds">00</span>
                <?php endif; ?>
            </div>

        <?php elseif ($variant === 'compact'): ?>
            <!-- Variante Compact -->
            <div class="flavor-countdown-display inline-flex items-center bg-gray-100 rounded-lg px-4 py-2 <?php echo esc_attr($sz['gap']); ?>">
                <?php if ($show_days): ?>
                    <div class="text-center">
                        <span class="countdown-days font-bold <?php echo esc_attr($sz['number']); ?> text-gray-900">00</span>
                        <span class="<?php echo esc_attr($sz['label']); ?> text-gray-500">d</span>
                    </div>
                    <span class="text-gray-300 mx-1">·</span>
                <?php endif; ?>
                <?php if ($show_hours): ?>
                    <div class="text-center">
                        <span class="countdown-hours font-bold <?php echo esc_attr($sz['number']); ?> text-gray-900">00</span>
                        <span class="<?php echo esc_attr($sz['label']); ?> text-gray-500">h</span>
                    </div>
                    <span class="text-gray-300 mx-1">·</span>
                <?php endif; ?>
                <?php if ($show_minutes): ?>
                    <div class="text-center">
                        <span class="countdown-minutes font-bold <?php echo esc_attr($sz['number']); ?> text-gray-900">00</span>
                        <span class="<?php echo esc_attr($sz['label']); ?> text-gray-500">m</span>
                    </div>
                    <?php if ($show_seconds): ?><span class="text-gray-300 mx-1">·</span><?php endif; ?>
                <?php endif; ?>
                <?php if ($show_seconds): ?>
                    <div class="text-center">
                        <span class="countdown-seconds font-bold <?php echo esc_attr($sz['number']); ?> text-gray-900">00</span>
                        <span class="<?php echo esc_attr($sz['label']); ?> text-gray-500">s</span>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- Variante Default (boxes) -->
            <div class="flavor-countdown-display inline-flex items-center <?php echo esc_attr($sz['gap']); ?>">
                <?php if ($show_days): ?>
                    <div class="flex flex-col items-center">
                        <div class="<?php echo esc_attr($sz['box']); ?> <?php echo esc_attr($color_class); ?> rounded-lg flex items-center justify-center">
                            <span class="countdown-days font-bold <?php echo esc_attr($sz['number']); ?>">00</span>
                        </div>
                        <span class="mt-1 <?php echo esc_attr($sz['label']); ?> text-gray-500 uppercase tracking-wider">
                            <?php echo esc_html($labels['days']); ?>
                        </span>
                    </div>
                    <span class="<?php echo esc_attr($sz['number']); ?> text-gray-300 font-bold self-start mt-3">:</span>
                <?php endif; ?>

                <?php if ($show_hours): ?>
                    <div class="flex flex-col items-center">
                        <div class="<?php echo esc_attr($sz['box']); ?> <?php echo esc_attr($color_class); ?> rounded-lg flex items-center justify-center">
                            <span class="countdown-hours font-bold <?php echo esc_attr($sz['number']); ?>">00</span>
                        </div>
                        <span class="mt-1 <?php echo esc_attr($sz['label']); ?> text-gray-500 uppercase tracking-wider">
                            <?php echo esc_html($labels['hours']); ?>
                        </span>
                    </div>
                    <span class="<?php echo esc_attr($sz['number']); ?> text-gray-300 font-bold self-start mt-3">:</span>
                <?php endif; ?>

                <?php if ($show_minutes): ?>
                    <div class="flex flex-col items-center">
                        <div class="<?php echo esc_attr($sz['box']); ?> <?php echo esc_attr($color_class); ?> rounded-lg flex items-center justify-center">
                            <span class="countdown-minutes font-bold <?php echo esc_attr($sz['number']); ?>">00</span>
                        </div>
                        <span class="mt-1 <?php echo esc_attr($sz['label']); ?> text-gray-500 uppercase tracking-wider">
                            <?php echo esc_html($labels['minutes']); ?>
                        </span>
                    </div>
                    <?php if ($show_seconds): ?>
                        <span class="<?php echo esc_attr($sz['number']); ?> text-gray-300 font-bold self-start mt-3">:</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($show_seconds): ?>
                    <div class="flex flex-col items-center">
                        <div class="<?php echo esc_attr($sz['box']); ?> <?php echo esc_attr($color_class); ?> rounded-lg flex items-center justify-center">
                            <span class="countdown-seconds font-bold <?php echo esc_attr($sz['number']); ?>">00</span>
                        </div>
                        <span class="mt-1 <?php echo esc_attr($sz['label']); ?> text-gray-500 uppercase tracking-wider">
                            <?php echo esc_html($labels['seconds']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- Expirado -->
        <div class="flavor-countdown-expired">
            <span class="text-2xl">⏰</span>
            <p class="text-lg font-medium text-gray-700 mt-2"><?php echo esc_html($expired_text); ?></p>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('<?php echo esc_js($countdown_id); ?>');
    if (!container) return;

    const target = parseInt(container.dataset.target) * 1000; // Convert to ms
    const expiredText = container.dataset.expiredText;
    const expiredUrl = container.dataset.expiredUrl;

    const elements = {
        days: container.querySelector('.countdown-days'),
        hours: container.querySelector('.countdown-hours'),
        minutes: container.querySelector('.countdown-minutes'),
        seconds: container.querySelector('.countdown-seconds')
    };

    function update() {
        const now = Date.now();
        const remaining = Math.max(0, target - now);

        if (remaining <= 0) {
            clearInterval(interval);
            if (expiredUrl) {
                window.location.href = expiredUrl;
            } else {
                container.innerHTML = `
                    <div class="flavor-countdown-expired">
                        <span class="text-2xl">⏰</span>
                        <p class="text-lg font-medium text-gray-700 mt-2">${expiredText}</p>
                    </div>
                `;
            }
            return;
        }

        const days = Math.floor(remaining / (1000 * 60 * 60 * 24));
        const hours = Math.floor((remaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((remaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((remaining % (1000 * 60)) / 1000);

        if (elements.days) elements.days.textContent = String(days).padStart(2, '0');
        if (elements.hours) elements.hours.textContent = String(hours).padStart(2, '0');
        if (elements.minutes) elements.minutes.textContent = String(minutes).padStart(2, '0');
        if (elements.seconds) elements.seconds.textContent = String(seconds).padStart(2, '0');
    }

    update();
    const interval = setInterval(update, 1000);
});
</script>
