<?php
/**
 * Componente: Modal
 *
 * Modal/diálogo genérico reutilizable.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * @param string $id         ID único del modal (requerido)
 * @param string $title      Título del modal
 * @param string $subtitle   Subtítulo opcional
 * @param string $icon       Icono emoji
 * @param string $color      Color del tema
 * @param string $size       Tamaño: 'sm', 'md', 'lg', 'xl', 'full'
 * @param string $content    Contenido HTML
 * @param array  $footer     Botones del footer: [['label' => 'Guardar', 'action' => 'save()', 'primary' => true]]
 * @param bool   $closable   Permitir cerrar con X y backdrop
 * @param bool   $open       Mostrar abierto por defecto
 */

if (!defined('ABSPATH')) {
    exit;
}

$modal_id = $id ?? 'modal-' . wp_rand(1000, 9999);
$title = $title ?? '';
$subtitle = $subtitle ?? '';
$icon = $icon ?? '';
$color = $color ?? 'blue';
$size = $size ?? 'md';
$footer = $footer ?? [];
$closable = $closable ?? true;
$open = $open ?? false;

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

// Clases de tamaño
$size_classes = [
    'sm'   => 'max-w-sm',
    'md'   => 'max-w-lg',
    'lg'   => 'max-w-2xl',
    'xl'   => 'max-w-4xl',
    'full' => 'max-w-full mx-4',
];
$modal_size = $size_classes[$size] ?? $size_classes['md'];
?>

<div id="<?php echo esc_attr($modal_id); ?>"
     class="flavor-modal fixed inset-0 z-50 <?php echo $open ? '' : 'hidden'; ?>"
     role="dialog"
     aria-modal="true"
     aria-labelledby="<?php echo esc_attr($modal_id); ?>-title">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/50 transition-opacity"
         <?php if ($closable): ?>onclick="flavorModal.close('<?php echo esc_js($modal_id); ?>')"<?php endif; ?>></div>

    <!-- Modal Content -->
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full <?php echo esc_attr($modal_size); ?> bg-white rounded-2xl shadow-2xl transform transition-all">

            <!-- Header -->
            <?php if ($title || $closable): ?>
                <div class="flex items-center justify-between p-6 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <?php if ($icon): ?>
                            <span class="text-2xl"><?php echo esc_html($icon); ?></span>
                        <?php endif; ?>
                        <div>
                            <?php if ($title): ?>
                                <h3 id="<?php echo esc_attr($modal_id); ?>-title" class="text-lg font-bold text-gray-900">
                                    <?php echo esc_html($title); ?>
                                </h3>
                            <?php endif; ?>
                            <?php if ($subtitle): ?>
                                <p class="text-sm text-gray-500"><?php echo esc_html($subtitle); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($closable): ?>
                        <button type="button"
                                onclick="flavorModal.close('<?php echo esc_js($modal_id); ?>')"
                                class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                aria-label="<?php esc_attr_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                            <span class="text-xl">✕</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Body -->
            <div class="p-6 max-h-[70vh] overflow-y-auto" id="<?php echo esc_attr($modal_id); ?>-body">
                <?php
                if (!empty($content)) {
                    echo wp_kses_post($content);
                } elseif (!empty($content_callback) && is_callable($content_callback)) {
                    call_user_func($content_callback);
                }
                ?>
            </div>

            <!-- Footer -->
            <?php if (!empty($footer)): ?>
                <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100">
                    <?php foreach ($footer as $btn):
                        $is_primary = $btn['primary'] ?? false;
                        $btn_label = $btn['label'] ?? '';
                        $btn_icon = $btn['icon'] ?? '';
                        $btn_action = $btn['action'] ?? '';
                        $btn_close = $btn['close'] ?? false;
                        $btn_type = $btn['type'] ?? 'button';
                        $btn_disabled = $btn['disabled'] ?? false;

                        if ($is_primary) {
                            $btn_class = "px-6 py-3 rounded-xl text-sm font-medium text-white {$color_classes['bg_solid']} hover:opacity-90 transition-all";
                        } else {
                            $btn_class = "px-6 py-3 rounded-xl text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors";
                        }

                        $onclick_parts = [];
                        if ($btn_action) {
                            $onclick_parts[] = $btn_action;
                        }
                        if ($btn_close) {
                            $onclick_parts[] = "flavorModal.close('{$modal_id}')";
                        }
                        $onclick = implode('; ', $onclick_parts);
                    ?>
                        <button type="<?php echo esc_attr($btn_type); ?>"
                                class="<?php echo esc_attr($btn_class); ?> flex items-center gap-2"
                                <?php if ($onclick): ?>onclick="<?php echo esc_attr($onclick); ?>"<?php endif; ?>
                                <?php if ($btn_disabled): ?>disabled<?php endif; ?>>
                            <?php if ($btn_icon): ?>
                                <span><?php echo esc_html($btn_icon); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($btn_label); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
window.flavorModal = window.flavorModal || {
    open: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Focus first focusable element
        const focusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusable) focusable.focus();

        // Dispatch event
        modal.dispatchEvent(new CustomEvent('modal-open', { detail: { modalId } }));
    },

    close: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');

        // Dispatch event
        modal.dispatchEvent(new CustomEvent('modal-close', { detail: { modalId } }));
    },

    toggle: function(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        if (modal.classList.contains('hidden')) {
            this.open(modalId);
        } else {
            this.close(modalId);
        }
    },

    setContent: function(modalId, content) {
        const body = document.getElementById(modalId + '-body');
        if (body) body.innerHTML = content;
    }
};

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.flavor-modal:not(.hidden)');
        openModals.forEach(modal => {
            if (modal.querySelector('[onclick*="flavorModal.close"]')) {
                flavorModal.close(modal.id);
            }
        });
    }
});
</script>
