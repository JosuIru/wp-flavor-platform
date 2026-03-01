<?php
/**
 * Componente: Alert Banner
 *
 * Banner de alerta para notificaciones importantes.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $type        Tipo: 'info', 'success', 'warning', 'error', 'announcement'
 * @param string $title       Título del alert
 * @param string $message     Mensaje del alert
 * @param string $icon        Icono personalizado
 * @param bool   $dismissible Puede cerrarse
 * @param array  $actions     Acciones: [['label' => '', 'url' => '', 'action' => '', 'primary' => true]]
 * @param string $position    Posición: 'inline', 'top', 'bottom'
 * @param string $id          ID para persistir estado de dismiss
 */

if (!defined('ABSPATH')) {
    exit;
}

$type = $type ?? 'info';
$alert_title = $title ?? '';
$message = $message ?? '';
$icon = $icon ?? '';
$dismissible = $dismissible ?? true;
$actions = $actions ?? [];
$position = $position ?? 'inline';
$alert_id = $id ?? 'alert-' . wp_rand(1000, 9999);

// Configuración por tipo
$type_config = [
    'info' => [
        'icon' => 'ℹ️',
        'bg' => 'bg-blue-50',
        'border' => 'border-blue-200',
        'text' => 'text-blue-800',
        'icon_bg' => 'bg-blue-100',
    ],
    'success' => [
        'icon' => '✓',
        'bg' => 'bg-green-50',
        'border' => 'border-green-200',
        'text' => 'text-green-800',
        'icon_bg' => 'bg-green-100',
    ],
    'warning' => [
        'icon' => '⚠',
        'bg' => 'bg-yellow-50',
        'border' => 'border-yellow-200',
        'text' => 'text-yellow-800',
        'icon_bg' => 'bg-yellow-100',
    ],
    'error' => [
        'icon' => '✕',
        'bg' => 'bg-red-50',
        'border' => 'border-red-200',
        'text' => 'text-red-800',
        'icon_bg' => 'bg-red-100',
    ],
    'announcement' => [
        'icon' => '📢',
        'bg' => 'bg-purple-50',
        'border' => 'border-purple-200',
        'text' => 'text-purple-800',
        'icon_bg' => 'bg-purple-100',
    ],
];

$config = $type_config[$type] ?? $type_config['info'];
$display_icon = $icon ?: $config['icon'];

// Clases de posición
$position_classes = '';
if ($position === 'top') {
    $position_classes = 'fixed top-0 left-0 right-0 z-50 rounded-none border-t-0 border-x-0';
} elseif ($position === 'bottom') {
    $position_classes = 'fixed bottom-0 left-0 right-0 z-50 rounded-none border-b-0 border-x-0';
}
?>

<div id="<?php echo esc_attr($alert_id); ?>"
     class="flavor-alert-banner <?php echo esc_attr($config['bg']); ?> <?php echo esc_attr($config['border']); ?> border rounded-xl p-4 <?php echo esc_attr($position_classes); ?>"
     role="alert">

    <div class="flex items-start gap-4">
        <!-- Icono -->
        <div class="flex-shrink-0">
            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg <?php echo esc_attr($config['icon_bg']); ?> <?php echo esc_attr($config['text']); ?>">
                <?php echo esc_html($display_icon); ?>
            </span>
        </div>

        <!-- Contenido -->
        <div class="flex-1 min-w-0">
            <?php if ($alert_title): ?>
                <h4 class="font-semibold <?php echo esc_attr($config['text']); ?>">
                    <?php echo esc_html($alert_title); ?>
                </h4>
            <?php endif; ?>

            <?php if ($message): ?>
                <p class="mt-1 text-sm <?php echo esc_attr($config['text']); ?> opacity-90">
                    <?php echo wp_kses_post($message); ?>
                </p>
            <?php endif; ?>

            <?php if (!empty($actions)): ?>
                <div class="flex flex-wrap items-center gap-3 mt-3">
                    <?php foreach ($actions as $action):
                        $is_primary = $action['primary'] ?? false;
                        $action_class = $is_primary
                            ? 'font-medium underline hover:no-underline'
                            : 'opacity-75 hover:opacity-100';
                    ?>
                        <?php if (!empty($action['action'])): ?>
                            <button type="button"
                                    onclick="<?php echo esc_attr($action['action']); ?>"
                                    class="text-sm <?php echo esc_attr($config['text']); ?> <?php echo esc_attr($action_class); ?>">
                                <?php echo esc_html($action['label'] ?? ''); ?>
                            </button>
                        <?php else: ?>
                            <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                               class="text-sm <?php echo esc_attr($config['text']); ?> <?php echo esc_attr($action_class); ?>">
                                <?php echo esc_html($action['label'] ?? ''); ?>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Botón cerrar -->
        <?php if ($dismissible): ?>
            <button type="button"
                    onclick="flavorAlert.dismiss('<?php echo esc_js($alert_id); ?>')"
                    class="flex-shrink-0 p-1 rounded-lg <?php echo esc_attr($config['text']); ?> opacity-50 hover:opacity-100 hover:<?php echo esc_attr($config['icon_bg']); ?> transition-all"
                    aria-label="<?php esc_attr_e('Cerrar', 'flavor-chat-ia'); ?>">
                <span class="text-lg">×</span>
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
window.flavorAlert = window.flavorAlert || {
    dismiss: function(alertId) {
        const alert = document.getElementById(alertId);
        if (!alert) return;

        alert.style.transition = 'opacity 0.3s, transform 0.3s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';

        setTimeout(() => {
            alert.remove();
        }, 300);

        // Guardar en localStorage si tiene ID persistente
        if (!alertId.startsWith('alert-')) {
            localStorage.setItem('flavor_alert_dismissed_' + alertId, Date.now());
        }
    },

    isDismissed: function(alertId) {
        const dismissed = localStorage.getItem('flavor_alert_dismissed_' + alertId);
        if (!dismissed) return false;

        // Expirar después de 7 días
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        return (Date.now() - parseInt(dismissed)) < sevenDays;
    }
};

// Auto-hide dismissed alerts
document.addEventListener('DOMContentLoaded', function() {
    const alertId = '<?php echo esc_js($alert_id); ?>';
    if (flavorAlert.isDismissed(alertId)) {
        const alert = document.getElementById(alertId);
        if (alert) alert.style.display = 'none';
    }
});
</script>
