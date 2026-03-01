<?php
/**
 * Componente: Notification
 *
 * Alertas y notificaciones reutilizables.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $type       Tipo: 'info', 'success', 'warning', 'error'
 * @param string $title      Título de la notificación
 * @param string $message    Mensaje principal
 * @param string $icon       Icono personalizado (default basado en type)
 * @param bool   $dismissible Permitir cerrar
 * @param array  $actions    Acciones: [['label' => 'Ver más', 'url' => '#', 'action' => 'fn()']]
 * @param int    $auto_close Segundos para cerrar automáticamente (0 = no cerrar)
 * @param string $position   Posición para toast: 'inline', 'top-right', 'top-left', 'bottom-right', 'bottom-left'
 * @param string $id         ID único
 */

if (!defined('ABSPATH')) {
    exit;
}

$type = $type ?? 'info';
$title = $title ?? '';
$message = $message ?? '';
$icon = $icon ?? '';
$dismissible = $dismissible ?? true;
$actions = $actions ?? [];
$auto_close = $auto_close ?? 0;
$position = $position ?? 'inline';
$notification_id = $id ?? 'notification-' . wp_rand(1000, 9999);

// Configuración por tipo
$type_config = [
    'info' => [
        'icon'    => 'ℹ️',
        'bg'      => 'bg-blue-50',
        'border'  => 'border-blue-200',
        'text'    => 'text-blue-800',
        'icon_bg' => 'bg-blue-100',
    ],
    'success' => [
        'icon'    => '✅',
        'bg'      => 'bg-green-50',
        'border'  => 'border-green-200',
        'text'    => 'text-green-800',
        'icon_bg' => 'bg-green-100',
    ],
    'warning' => [
        'icon'    => '⚠️',
        'bg'      => 'bg-amber-50',
        'border'  => 'border-amber-200',
        'text'    => 'text-amber-800',
        'icon_bg' => 'bg-amber-100',
    ],
    'error' => [
        'icon'    => '❌',
        'bg'      => 'bg-red-50',
        'border'  => 'border-red-200',
        'text'    => 'text-red-800',
        'icon_bg' => 'bg-red-100',
    ],
];

$config = $type_config[$type] ?? $type_config['info'];
$display_icon = $icon ?: $config['icon'];

// Clases de posición para toast
$position_classes = [
    'inline'       => '',
    'top-right'    => 'fixed top-4 right-4 z-50 max-w-sm',
    'top-left'     => 'fixed top-4 left-4 z-50 max-w-sm',
    'bottom-right' => 'fixed bottom-4 right-4 z-50 max-w-sm',
    'bottom-left'  => 'fixed bottom-4 left-4 z-50 max-w-sm',
    'top-center'   => 'fixed top-4 left-1/2 -translate-x-1/2 z-50 max-w-sm',
    'bottom-center'=> 'fixed bottom-4 left-1/2 -translate-x-1/2 z-50 max-w-sm',
];
$position_class = $position_classes[$position] ?? '';
?>

<div id="<?php echo esc_attr($notification_id); ?>"
     class="flavor-notification <?php echo esc_attr($position_class); ?> <?php echo esc_attr($config['bg']); ?> <?php echo esc_attr($config['border']); ?> border rounded-xl p-4 transition-all"
     role="alert"
     <?php if ($auto_close > 0): ?>data-auto-close="<?php echo esc_attr($auto_close); ?>"<?php endif; ?>>

    <div class="flex items-start gap-3">
        <!-- Icon -->
        <div class="flex-shrink-0 w-10 h-10 rounded-full <?php echo esc_attr($config['icon_bg']); ?> flex items-center justify-center">
            <span class="text-lg"><?php echo esc_html($display_icon); ?></span>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <?php if ($title): ?>
                <h4 class="font-semibold <?php echo esc_attr($config['text']); ?>">
                    <?php echo esc_html($title); ?>
                </h4>
            <?php endif; ?>

            <?php if ($message): ?>
                <p class="text-sm <?php echo esc_attr($config['text']); ?> opacity-90 <?php echo $title ? 'mt-1' : ''; ?>">
                    <?php echo wp_kses_post($message); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($actions)): ?>
                <div class="flex items-center gap-3 mt-3">
                    <?php foreach ($actions as $action):
                        $action_label = $action['label'] ?? '';
                        $action_url = $action['url'] ?? '';
                        $action_onclick = $action['action'] ?? '';
                    ?>
                        <?php if ($action_onclick): ?>
                            <button onclick="<?php echo esc_attr($action_onclick); ?>"
                                    class="text-sm font-medium <?php echo esc_attr($config['text']); ?> underline hover:no-underline">
                                <?php echo esc_html($action_label); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url($action_url ?: '#'); ?>"
                               class="text-sm font-medium <?php echo esc_attr($config['text']); ?> underline hover:no-underline">
                                <?php echo esc_html($action_label); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dismiss button -->
        <?php if ($dismissible): ?>
            <button type="button"
                    onclick="flavorNotification.dismiss('<?php echo esc_js($notification_id); ?>')"
                    class="flex-shrink-0 p-1 rounded-lg <?php echo esc_attr($config['text']); ?> opacity-60 hover:opacity-100 hover:bg-white/50 transition-all"
                    aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                <span>✕</span>
            </button>
        <?php endif; ?>
    </div>

    <?php if ($auto_close > 0): ?>
        <!-- Progress bar for auto-close -->
        <div class="mt-3 h-1 bg-white/30 rounded-full overflow-hidden">
            <div class="h-full <?php echo esc_attr($config['text']); ?> bg-current opacity-30 rounded-full notification-progress"
                 style="animation: shrink <?php echo esc_attr($auto_close); ?>s linear forwards;"></div>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes shrink {
    from { width: 100%; }
    to { width: 0%; }
}
</style>

<script>
window.flavorNotification = window.flavorNotification || {
    dismiss: function(notificationId) {
        const notification = document.getElementById(notificationId);
        if (!notification) return;

        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    },

    show: function(options) {
        const defaults = {
            type: 'info',
            title: '',
            message: '',
            position: 'top-right',
            dismissible: true,
            autoClose: 5,
        };
        const config = { ...defaults, ...options };
        const id = 'notification-' + Date.now();

        // Create notification element (simplified for JS creation)
        const typeConfig = {
            info: { icon: 'ℹ️', bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-800' },
            success: { icon: '✅', bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-800' },
            warning: { icon: '⚠️', bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-800' },
            error: { icon: '❌', bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800' },
        };

        const tc = typeConfig[config.type] || typeConfig.info;
        const positionClasses = {
            'top-right': 'fixed top-4 right-4 z-50 max-w-sm',
            'top-left': 'fixed top-4 left-4 z-50 max-w-sm',
            'bottom-right': 'fixed bottom-4 right-4 z-50 max-w-sm',
            'bottom-left': 'fixed bottom-4 left-4 z-50 max-w-sm',
        };

        const html = `
            <div id="${id}" class="flavor-notification ${positionClasses[config.position] || ''} ${tc.bg} ${tc.border} border rounded-xl p-4 transition-all" role="alert">
                <div class="flex items-start gap-3">
                    <span class="text-lg">${tc.icon}</span>
                    <div class="flex-1">
                        ${config.title ? `<h4 class="font-semibold ${tc.text}">${config.title}</h4>` : ''}
                        ${config.message ? `<p class="text-sm ${tc.text} opacity-90">${config.message}</p>` : ''}
                    </div>
                    ${config.dismissible ? `<button onclick="flavorNotification.dismiss('${id}')" class="p-1 ${tc.text} opacity-60 hover:opacity-100">✕</button>` : ''}
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);

        if (config.autoClose > 0) {
            setTimeout(() => this.dismiss(id), config.autoClose * 1000);
        }

        return id;
    }
};

// Auto-close notifications
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.flavor-notification[data-auto-close]').forEach(notification => {
        const seconds = parseInt(notification.dataset.autoClose) * 1000;
        setTimeout(() => {
            flavorNotification.dismiss(notification.id);
        }, seconds);
    });
});
</script>
