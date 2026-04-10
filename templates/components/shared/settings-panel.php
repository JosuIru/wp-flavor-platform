<?php
/**
 * Componente: Settings Panel
 *
 * Panel de configuración con secciones y campos.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $sections   Secciones: [['id' => '', 'title' => '', 'icon' => '', 'fields' => [...]]]
 * @param string $title      Título del panel
 * @param string $description Descripción
 * @param string $color      Color del tema
 * @param string $save_action Acción del formulario
 * @param string $save_label Etiqueta del botón guardar
 * @param array  $actions    Acciones adicionales del header
 * @param bool   $ajax       Usar AJAX para guardar
 * @param string $nonce_action Acción para el nonce
 */

if (!defined('ABSPATH')) {
    exit;
}

$sections = $sections ?? [];
$title = $title ?? __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN);
$description = $description ?? '';
$color = $color ?? 'blue';
$save_action = $save_action ?? '';
$save_label = $save_label ?? __('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN);
$actions = $actions ?? [];
$ajax = $ajax ?? true;
$nonce_action = $nonce_action ?? 'flavor_settings_nonce';

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

$panel_id = 'settings-panel-' . wp_rand(1000, 9999);
$active_section = $sections[0]['id'] ?? '';
?>

<div id="<?php echo esc_attr($panel_id); ?>" class="flavor-settings-panel bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="p-6 border-b border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900"><?php echo esc_html($title); ?></h2>
                <?php if ($description): ?>
                    <p class="mt-1 text-sm text-gray-500"><?php echo esc_html($description); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($actions)): ?>
                <div class="flex items-center gap-2">
                    <?php foreach ($actions as $action): ?>
                        <button type="button"
                                onclick="<?php echo esc_attr($action['action'] ?? ''); ?>"
                                class="px-4 py-2 text-sm font-medium rounded-xl transition-colors
                                    <?php echo ($action['primary'] ?? false)
                                        ? esc_attr($color_classes['bg_solid']) . ' text-white hover:opacity-90'
                                        : 'text-gray-600 hover:bg-gray-100'; ?>">
                            <?php if (!empty($action['icon'])): ?>
                                <span class="mr-1"><?php echo esc_html($action['icon']); ?></span>
                            <?php endif; ?>
                            <?php echo esc_html($action['label'] ?? ''); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="flex">
        <!-- Sidebar con secciones -->
        <?php if (count($sections) > 1): ?>
            <div class="w-64 border-r border-gray-100 bg-gray-50/50">
                <nav class="p-4 space-y-1">
                    <?php foreach ($sections as $section): ?>
                        <button type="button"
                                data-section="<?php echo esc_attr($section['id'] ?? ''); ?>"
                                onclick="flavorSettings.switchSection('<?php echo esc_js($panel_id); ?>', '<?php echo esc_js($section['id'] ?? ''); ?>')"
                                class="settings-nav-item w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-colors text-left
                                    <?php echo ($section['id'] ?? '') === $active_section
                                        ? esc_attr($color_classes['bg']) . ' ' . esc_attr($color_classes['text'])
                                        : 'text-gray-600 hover:bg-gray-100'; ?>">
                            <?php if (!empty($section['icon'])): ?>
                                <span class="text-lg"><?php echo esc_html($section['icon']); ?></span>
                            <?php endif; ?>
                            <span><?php echo esc_html($section['title'] ?? ''); ?></span>
                        </button>
                    <?php endforeach; ?>
                </nav>
            </div>
        <?php endif; ?>

        <!-- Contenido -->
        <div class="flex-1">
            <form id="<?php echo esc_attr($panel_id); ?>-form"
                  action="<?php echo esc_url($save_action); ?>"
                  method="post"
                  class="settings-form">

                <?php wp_nonce_field($nonce_action, '_wpnonce'); ?>

                <?php foreach ($sections as $section): ?>
                    <div class="settings-section p-6 <?php echo ($section['id'] ?? '') !== $active_section ? 'hidden' : ''; ?>"
                         data-section="<?php echo esc_attr($section['id'] ?? ''); ?>">

                        <?php if (!empty($section['title']) && count($sections) <= 1): ?>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <?php if (!empty($section['icon'])): ?>
                                    <span class="mr-2"><?php echo esc_html($section['icon']); ?></span>
                                <?php endif; ?>
                                <?php echo esc_html($section['title']); ?>
                            </h3>
                        <?php endif; ?>

                        <?php if (!empty($section['description'])): ?>
                            <p class="text-sm text-gray-500 mb-6"><?php echo esc_html($section['description']); ?></p>
                        <?php endif; ?>

                        <div class="space-y-6">
                            <?php foreach (($section['fields'] ?? []) as $field):
                                $field_type = $field['type'] ?? 'text';
                                $field_name = $field['name'] ?? '';
                                $field_label = $field['label'] ?? '';
                                $field_value = $field['value'] ?? '';
                                $field_help = $field['help'] ?? '';
                                $field_options = $field['options'] ?? [];
                                $field_required = $field['required'] ?? false;
                            ?>
                                <div class="setting-field">
                                    <?php if ($field_type === 'toggle'): ?>
                                        <!-- Toggle switch -->
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <label class="text-sm font-medium text-gray-900">
                                                    <?php echo esc_html($field_label); ?>
                                                </label>
                                                <?php if ($field_help): ?>
                                                    <p class="text-sm text-gray-500"><?php echo esc_html($field_help); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr($field_name); ?>"
                                                       value="1"
                                                       class="sr-only peer"
                                                       <?php checked($field_value, '1'); ?>>
                                                <div class="w-11 h-6 bg-gray-200 rounded-full peer peer-checked:<?php echo esc_attr($color_classes['bg_solid']); ?> peer-focus:ring-4 peer-focus:ring-<?php echo esc_attr($color); ?>-500/20 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                                            </label>
                                        </div>

                                    <?php elseif ($field_type === 'group'): ?>
                                        <!-- Grupo de campos -->
                                        <fieldset class="border border-gray-200 rounded-xl p-4">
                                            <legend class="text-sm font-medium text-gray-700 px-2">
                                                <?php echo esc_html($field_label); ?>
                                            </legend>
                                            <div class="space-y-4 mt-2">
                                                <?php foreach ($field_options as $subfield):
                                                    // Renderizar sub-campos recursivamente
                                                    $sub_type = $subfield['type'] ?? 'text';
                                                    $sub_name = $subfield['name'] ?? '';
                                                    $sub_label = $subfield['label'] ?? '';
                                                    $sub_value = $subfield['value'] ?? '';
                                                ?>
                                                    <div class="flex items-center gap-4">
                                                        <label class="w-32 text-sm text-gray-600 flex-shrink-0">
                                                            <?php echo esc_html($sub_label); ?>
                                                        </label>
                                                        <input type="<?php echo esc_attr($sub_type); ?>"
                                                               name="<?php echo esc_attr($sub_name); ?>"
                                                               value="<?php echo esc_attr($sub_value); ?>"
                                                               class="flex-1 px-3 py-2 rounded-lg border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-<?php echo esc_attr($color); ?>-500/20 focus:border-<?php echo esc_attr($color); ?>-500">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </fieldset>

                                    <?php elseif ($field_type === 'color'): ?>
                                        <!-- Color picker -->
                                        <div class="flex items-center justify-between">
                                            <label class="text-sm font-medium text-gray-900">
                                                <?php echo esc_html($field_label); ?>
                                            </label>
                                            <div class="flex items-center gap-2">
                                                <input type="color"
                                                       name="<?php echo esc_attr($field_name); ?>"
                                                       value="<?php echo esc_attr($field_value ?: '#3B82F6'); ?>"
                                                       class="w-10 h-10 rounded-lg border border-gray-200 cursor-pointer">
                                                <input type="text"
                                                       value="<?php echo esc_attr($field_value ?: '#3B82F6'); ?>"
                                                       class="w-24 px-3 py-2 rounded-lg border border-gray-200 text-sm font-mono"
                                                       readonly>
                                            </div>
                                        </div>

                                    <?php elseif ($field_type === 'range'): ?>
                                        <!-- Range slider -->
                                        <div>
                                            <div class="flex items-center justify-between mb-2">
                                                <label class="text-sm font-medium text-gray-900">
                                                    <?php echo esc_html($field_label); ?>
                                                </label>
                                                <span class="text-sm font-medium <?php echo esc_attr($color_classes['text']); ?>">
                                                    <?php echo esc_html($field_value); ?><?php echo esc_html($field['suffix'] ?? ''); ?>
                                                </span>
                                            </div>
                                            <input type="range"
                                                   name="<?php echo esc_attr($field_name); ?>"
                                                   value="<?php echo esc_attr($field_value); ?>"
                                                   min="<?php echo esc_attr($field['min'] ?? 0); ?>"
                                                   max="<?php echo esc_attr($field['max'] ?? 100); ?>"
                                                   step="<?php echo esc_attr($field['step'] ?? 1); ?>"
                                                   class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-<?php echo esc_attr($color); ?>-500">
                                        </div>

                                    <?php else: ?>
                                        <!-- Campo estándar usando form-field component -->
                                        <?php
                                        // Incluir el componente form-field
                                        $form_field_path = dirname(__FILE__) . '/form-field.php';
                                        if (file_exists($form_field_path)) {
                                            extract([
                                                'type' => $field_type,
                                                'name' => $field_name,
                                                'label' => $field_label,
                                                'value' => $field_value,
                                                'help' => $field_help,
                                                'options' => $field_options,
                                                'required' => $field_required,
                                                'placeholder' => $field['placeholder'] ?? '',
                                                'color' => $color,
                                            ]);
                                            include $form_field_path;
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Footer con botón guardar -->
                <div class="p-6 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between">
                    <div class="settings-status text-sm text-gray-500 hidden">
                        <span class="saving hidden"><?php esc_html_e('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="saved hidden text-green-600">✓ <?php esc_html_e('Guardado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="error hidden text-red-600">✗ <?php esc_html_e('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="reset"
                                class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition-colors">
                            <?php esc_html_e('Restablecer', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="submit"
                                class="px-6 py-2 text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 rounded-xl transition-colors">
                            <?php echo esc_html($save_label); ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
window.flavorSettings = window.flavorSettings || {
    switchSection: function(panelId, sectionId) {
        const panel = document.getElementById(panelId);
        if (!panel) return;

        // Update nav items
        panel.querySelectorAll('.settings-nav-item').forEach(item => {
            const isActive = item.dataset.section === sectionId;
            item.classList.toggle('<?php echo esc_attr($color_classes['bg']); ?>', isActive);
            item.classList.toggle('<?php echo esc_attr($color_classes['text']); ?>', isActive);
            item.classList.toggle('text-gray-600', !isActive);
        });

        // Show/hide sections
        panel.querySelectorAll('.settings-section').forEach(section => {
            section.classList.toggle('hidden', section.dataset.section !== sectionId);
        });
    },

    init: function(panelId) {
        const panel = document.getElementById(panelId);
        if (!panel) return;

        const form = panel.querySelector('.settings-form');
        if (!form) return;

        <?php if ($ajax): ?>
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const status = panel.querySelector('.settings-status');
            const saving = status.querySelector('.saving');
            const saved = status.querySelector('.saved');
            const error = status.querySelector('.error');

            status.classList.remove('hidden');
            saving.classList.remove('hidden');
            saved.classList.add('hidden');
            error.classList.add('hidden');

            try {
                const formData = new FormData(form);
                formData.append('action', '<?php echo esc_js($save_action); ?>');

                const response = await fetch(flavorChat.ajaxurl, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                saving.classList.add('hidden');
                if (result.success) {
                    saved.classList.remove('hidden');
                    setTimeout(() => status.classList.add('hidden'), 3000);
                } else {
                    error.classList.remove('hidden');
                }
            } catch (err) {
                saving.classList.add('hidden');
                error.classList.remove('hidden');
            }
        });
        <?php endif; ?>

        // Color picker sync
        panel.querySelectorAll('input[type="color"]').forEach(picker => {
            const textInput = picker.nextElementSibling;
            if (textInput) {
                picker.addEventListener('input', () => textInput.value = picker.value);
            }
        });

        // Range value display
        panel.querySelectorAll('input[type="range"]').forEach(range => {
            const display = range.closest('div').querySelector('span:last-child');
            if (display) {
                range.addEventListener('input', () => {
                    display.textContent = range.value + (range.dataset.suffix || '');
                });
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    flavorSettings.init('<?php echo esc_js($panel_id); ?>');
});
</script>
