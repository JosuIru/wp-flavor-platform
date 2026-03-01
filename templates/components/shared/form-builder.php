<?php
/**
 * Componente: Form Builder
 *
 * Genera formularios dinámicos a partir de configuración.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 *
 * Variables disponibles:
 * @var array  $args     Configuración del formulario
 * @var string $module   Slug del módulo
 * @var string $action   Acción del formulario (crear, registrar, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Valores por defecto
$defaults = [
    'title' => __('Formulario', 'flavor-chat-ia'),
    'subtitle' => '',
    'icon' => '📝',
    'color' => 'primary',
    'fields' => [],
    'submit_text' => __('Enviar', 'flavor-chat-ia'),
    'cancel_url' => '',
    'require_login' => true,
    'ajax' => true,
];

$args = wp_parse_args($args ?? [], $defaults);
$module = $module ?? '';
$action = $action ?? '';

// Generar ID único para el formulario
$form_id = 'flavor-form-' . sanitize_title($module . '-' . $action) . '-' . wp_rand(1000, 9999);

// Cargar funciones helper si no están cargadas
if (!function_exists('flavor_get_gradient_classes')) {
    require_once __DIR__ . '/_functions.php';
}

// Resolver colores del tema
$gradient = flavor_get_gradient_classes($args['color']);
$gradient_classes = "bg-gradient-to-r {$gradient['from']} {$gradient['to']}";

$color_classes = flavor_get_color_classes($args['color']);
$button_classes = $color_classes['bg_solid'] . ' hover:opacity-90';
?>

<div class="flavor-form-wrapper flavor-<?php echo esc_attr($module); ?>-form" id="<?php echo esc_attr($form_id); ?>-wrapper">
    <!-- Header -->
    <div class="<?php echo esc_attr($gradient_classes); ?> rounded-t-2xl p-6 text-white">
        <div class="flex items-center gap-3">
            <span class="text-3xl"><?php echo esc_html($args['icon']); ?></span>
            <div>
                <h2 class="text-2xl font-bold"><?php echo esc_html($args['title']); ?></h2>
                <?php if ($args['subtitle']): ?>
                <p class="text-white/80"><?php echo esc_html($args['subtitle']); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-b-2xl shadow-lg p-6">
        <form id="<?php echo esc_attr($form_id); ?>" 
              class="space-y-4"
              data-module="<?php echo esc_attr($module); ?>"
              data-action="<?php echo esc_attr($action); ?>"
              <?php if ($args['ajax']): ?>data-ajax="true"<?php endif; ?>>
            
            <?php wp_nonce_field('flavor_' . $module . '_' . $action, '_wpnonce'); ?>
            <input type="hidden" name="action" value="flavor_<?php echo esc_attr($module); ?>_<?php echo esc_attr($action); ?>">
            
            <?php foreach ($args['fields'] as $field): 
                $field_id = $form_id . '-' . sanitize_title($field['name']);
                $field_type = $field['type'] ?? 'text';
                $is_required = !empty($field['required']);
                $label = $field['label'] ?? '';
                $placeholder = $field['placeholder'] ?? '';
                $value = $field['value'] ?? '';
            ?>
            <div class="flavor-form-field">
                <?php if ($label && $field_type !== 'checkbox'): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="block text-sm font-medium text-gray-700 mb-1">
                    <?php echo esc_html($label); ?>
                    <?php if ($is_required): ?>
                    <span class="text-red-500">*</span>
                    <?php endif; ?>
                </label>
                <?php endif; ?>

                <?php switch ($field_type):
                    case 'text':
                    case 'email':
                    case 'number':
                    case 'tel':
                    case 'url':
                    case 'date':
                    case 'time':
                    case 'datetime':
                    case 'datetime-local':
                    ?>
                        <input type="<?php echo esc_attr($field_type); ?>"
                               id="<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($field['name']); ?>"
                               value="<?php echo esc_attr($value); ?>"
                               placeholder="<?php echo esc_attr($placeholder); ?>"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               <?php if ($is_required): ?>required<?php endif; ?>
                               <?php if (!empty($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
                               <?php if (!empty($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>
                               <?php if (!empty($field['step'])): ?>step="<?php echo esc_attr($field['step']); ?>"<?php endif; ?>
                               <?php if (!empty($field['pattern'])): ?>pattern="<?php echo esc_attr($field['pattern']); ?>"<?php endif; ?>>
                    <?php break;

                    case 'textarea':
                    ?>
                        <textarea id="<?php echo esc_attr($field_id); ?>"
                                  name="<?php echo esc_attr($field['name']); ?>"
                                  placeholder="<?php echo esc_attr($placeholder); ?>"
                                  rows="<?php echo intval($field['rows'] ?? 4); ?>"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-y"
                                  <?php if ($is_required): ?>required<?php endif; ?>><?php echo esc_textarea($value); ?></textarea>
                    <?php break;

                    case 'select':
                    ?>
                        <select id="<?php echo esc_attr($field_id); ?>"
                                name="<?php echo esc_attr($field['name']); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                                <?php if ($is_required): ?>required<?php endif; ?>>
                            <option value=""><?php echo esc_html($placeholder ?: __('Seleccionar...', 'flavor-chat-ia')); ?></option>
                            <?php foreach (($field['options'] ?? []) as $opt_value => $opt_label): ?>
                            <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($value, $opt_value); ?>>
                                <?php echo esc_html($opt_label); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php break;

                    case 'checkbox':
                    ?>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox"
                                   id="<?php echo esc_attr($field_id); ?>"
                                   name="<?php echo esc_attr($field['name']); ?>"
                                   value="1"
                                   class="w-5 h-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   <?php checked($value, '1'); ?>
                                   <?php if ($is_required): ?>required<?php endif; ?>>
                            <span class="text-gray-700"><?php echo esc_html($placeholder ?: $label); ?></span>
                            <?php if ($is_required): ?>
                            <span class="text-red-500">*</span>
                            <?php endif; ?>
                        </label>
                    <?php break;

                    case 'radio':
                    ?>
                        <div class="space-y-2">
                            <?php foreach (($field['options'] ?? []) as $opt_value => $opt_label): ?>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="radio"
                                       name="<?php echo esc_attr($field['name']); ?>"
                                       value="<?php echo esc_attr($opt_value); ?>"
                                       class="w-5 h-5 border-gray-300 text-blue-600 focus:ring-blue-500"
                                       <?php checked($value, $opt_value); ?>
                                       <?php if ($is_required): ?>required<?php endif; ?>>
                                <span class="text-gray-700"><?php echo esc_html($opt_label); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    <?php break;

                    case 'file':
                    ?>
                        <div class="relative">
                            <input type="file"
                                   id="<?php echo esc_attr($field_id); ?>"
                                   name="<?php echo esc_attr($field['name']); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                                   <?php if (!empty($field['accept'])): ?>accept="<?php echo esc_attr($field['accept']); ?>"<?php endif; ?>
                                   <?php if (!empty($field['multiple'])): ?>multiple<?php endif; ?>
                                   <?php if ($is_required): ?>required<?php endif; ?>>
                        </div>
                    <?php break;

                    case 'hidden':
                    ?>
                        <input type="hidden"
                               id="<?php echo esc_attr($field_id); ?>"
                               name="<?php echo esc_attr($field['name']); ?>"
                               value="<?php echo esc_attr($value); ?>">
                    <?php break;

                endswitch; ?>

                <?php if (!empty($field['help'])): ?>
                <p class="mt-1 text-sm text-gray-500"><?php echo esc_html($field['help']); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <!-- Mensajes -->
            <div class="flavor-form-messages hidden">
                <div class="flavor-form-success hidden p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
                    <span class="font-medium">✅ </span>
                    <span class="flavor-form-success-text"></span>
                </div>
                <div class="flavor-form-error hidden p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                    <span class="font-medium">❌ </span>
                    <span class="flavor-form-error-text"></span>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex gap-3 pt-4">
                <button type="submit"
                        class="flex-1 py-3 px-6 <?php echo esc_attr($button_classes); ?> text-white font-medium rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2">
                    <span class="flavor-form-submit-text"><?php echo esc_html($args['submit_text']); ?></span>
                    <span class="flavor-form-loading hidden">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>

                <?php if ($args['cancel_url']): ?>
                <a href="<?php echo esc_url($args['cancel_url']); ?>"
                   class="py-3 px-6 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition-colors">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($args['ajax']): ?>
<script>
(function() {
    const form = document.getElementById('<?php echo esc_js($form_id); ?>');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const wrapper = form.closest('.flavor-form-wrapper');
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitText = form.querySelector('.flavor-form-submit-text');
        const loading = form.querySelector('.flavor-form-loading');
        const messages = form.querySelector('.flavor-form-messages');
        const successDiv = form.querySelector('.flavor-form-success');
        const errorDiv = form.querySelector('.flavor-form-error');
        const successText = form.querySelector('.flavor-form-success-text');
        const errorText = form.querySelector('.flavor-form-error-text');

        // Reset messages
        messages.classList.add('hidden');
        successDiv.classList.add('hidden');
        errorDiv.classList.add('hidden');

        // Show loading
        submitBtn.disabled = true;
        submitText.classList.add('hidden');
        loading.classList.remove('hidden');

        try {
            const formData = new FormData(form);
            
            const response = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });

            const data = await response.json();

            messages.classList.remove('hidden');

            if (data.success) {
                successText.textContent = data.data?.message || '<?php echo esc_js(__('Enviado correctamente', 'flavor-chat-ia')); ?>';
                successDiv.classList.remove('hidden');
                
                if (data.data?.redirect) {
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 1500);
                } else {
                    // Reset form
                    form.reset();
                }
            } else {
                errorText.textContent = data.data?.message || '<?php echo esc_js(__('Ha ocurrido un error', 'flavor-chat-ia')); ?>';
                errorDiv.classList.remove('hidden');
            }
        } catch (error) {
            messages.classList.remove('hidden');
            errorText.textContent = '<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>';
            errorDiv.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
            submitText.classList.remove('hidden');
            loading.classList.add('hidden');
        }
    });
})();
</script>
<?php endif; ?>
