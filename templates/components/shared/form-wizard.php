<?php
/**
 * Componente: Form Wizard
 *
 * Formulario multi-paso con navegación y validación.
 *
 * @package FlavorPlatform
 * @since 5.0.0
 *
 * @param array  $steps       Pasos: [['id' => '', 'title' => '', 'icon' => '', 'fields' => [...], 'description' => '']]
 * @param string $title       Título del wizard
 * @param string $color       Color del tema
 * @param string $submit_label Etiqueta del botón final
 * @param string $submit_action Acción del formulario
 * @param bool   $show_progress Mostrar barra de progreso
 * @param bool   $allow_skip  Permitir saltar pasos opcionales
 * @param string $on_complete Callback JS al completar
 * @param string $on_step_change Callback JS al cambiar paso
 */

if (!defined('ABSPATH')) {
    exit;
}

$steps = $steps ?? [];
$title = $title ?? '';
$color = $color ?? 'blue';
$submit_label = $submit_label ?? __('Finalizar', FLAVOR_PLATFORM_TEXT_DOMAIN);
$submit_action = $submit_action ?? '';
$show_progress = $show_progress ?? true;
$allow_skip = $allow_skip ?? false;
$on_complete = $on_complete ?? '';
$on_step_change = $on_step_change ?? '';

if (empty($steps)) {
    return;
}

// Clases de color
if (function_exists('flavor_get_color_classes')) {
    $color_classes = flavor_get_color_classes($color);
} else {
    $color_classes = ['bg' => 'bg-blue-100', 'text' => 'text-blue-700', 'bg_solid' => 'bg-blue-500'];
}

$wizard_id = 'wizard-' . wp_rand(1000, 9999);
$total_steps = count($steps);
?>

<div id="<?php echo esc_attr($wizard_id); ?>"
     class="flavor-form-wizard bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
     data-current-step="0"
     data-total-steps="<?php echo esc_attr($total_steps); ?>">

    <!-- Header -->
    <?php if ($title): ?>
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-bold text-gray-900"><?php echo esc_html($title); ?></h2>
        </div>
    <?php endif; ?>

    <!-- Steps indicator -->
    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
        <div class="flex items-center justify-between">
            <?php foreach ($steps as $index => $step):
                $step_id = $step['id'] ?? 'step-' . $index;
                $step_title = $step['title'] ?? '';
                $step_icon = $step['icon'] ?? ($index + 1);
                $is_first = $index === 0;
                $is_last = $index === $total_steps - 1;
            ?>
                <div class="step-indicator flex items-center <?php echo $is_last ? '' : 'flex-1'; ?>"
                     data-step="<?php echo esc_attr($index); ?>">

                    <!-- Círculo del paso -->
                    <div class="flex flex-col items-center">
                        <div class="step-circle w-10 h-10 rounded-full flex items-center justify-center border-2 transition-colors
                            <?php echo $is_first ? esc_attr($color_classes['bg_solid']) . ' border-transparent text-white' : 'border-gray-300 text-gray-400 bg-white'; ?>">
                            <span class="step-icon text-sm font-medium"><?php echo esc_html($step_icon); ?></span>
                            <span class="step-check hidden">✓</span>
                        </div>
                        <?php if ($step_title): ?>
                            <span class="step-title mt-2 text-xs font-medium text-center max-w-[80px] truncate
                                <?php echo $is_first ? 'text-gray-900' : 'text-gray-400'; ?>">
                                <?php echo esc_html($step_title); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Línea conectora -->
                    <?php if (!$is_last): ?>
                        <div class="step-line flex-1 h-0.5 mx-4 bg-gray-200 transition-colors"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($show_progress): ?>
            <!-- Barra de progreso -->
            <div class="mt-4">
                <div class="flex items-center justify-between text-sm text-gray-500 mb-1">
                    <span class="progress-text"><?php printf(esc_html__('Paso %d de %d', FLAVOR_PLATFORM_TEXT_DOMAIN), 1, $total_steps); ?></span>
                    <span class="progress-percent">0%</span>
                </div>
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="progress-bar h-full <?php echo esc_attr($color_classes['bg_solid']); ?> rounded-full transition-all duration-500"
                         style="width: 0%"></div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Form -->
    <form id="<?php echo esc_attr($wizard_id); ?>-form"
          action="<?php echo esc_url($submit_action); ?>"
          method="post"
          class="wizard-form">

        <?php wp_nonce_field('flavor_wizard_nonce', '_wizard_nonce'); ?>

        <!-- Contenido de los pasos -->
        <?php foreach ($steps as $index => $step):
            $step_id = $step['id'] ?? 'step-' . $index;
            $step_description = $step['description'] ?? '';
            $step_fields = $step['fields'] ?? [];
            $is_optional = $step['optional'] ?? false;
        ?>
            <div class="wizard-step p-6 <?php echo $index > 0 ? 'hidden' : ''; ?>"
                 data-step="<?php echo esc_attr($index); ?>"
                 data-step-id="<?php echo esc_attr($step_id); ?>"
                 data-optional="<?php echo $is_optional ? 'true' : 'false'; ?>">

                <?php if ($step_description): ?>
                    <p class="text-gray-500 mb-6"><?php echo esc_html($step_description); ?></p>
                <?php endif; ?>

                <div class="space-y-4">
                    <?php foreach ($step_fields as $field):
                        // Renderizar campo usando form-field component
                        $form_field_path = dirname(__FILE__) . '/form-field.php';
                        if (file_exists($form_field_path)) {
                            extract([
                                'type' => $field['type'] ?? 'text',
                                'name' => $field['name'] ?? '',
                                'label' => $field['label'] ?? '',
                                'value' => $field['value'] ?? '',
                                'help' => $field['help'] ?? '',
                                'options' => $field['options'] ?? [],
                                'required' => $field['required'] ?? false,
                                'placeholder' => $field['placeholder'] ?? '',
                                'color' => $color,
                            ]);
                            include $form_field_path;
                        }
                    endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Footer con navegación -->
        <div class="p-6 border-t border-gray-100 bg-gray-50/50">
            <div class="flex items-center justify-between">
                <!-- Botón anterior -->
                <button type="button"
                        class="btn-prev hidden px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-xl transition-colors"
                        onclick="flavorWizard.prevStep('<?php echo esc_js($wizard_id); ?>')">
                    ← <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>

                <div class="flex items-center gap-3 ml-auto">
                    <?php if ($allow_skip): ?>
                        <button type="button"
                                class="btn-skip hidden px-4 py-2 text-sm text-gray-500 hover:text-gray-700 transition-colors"
                                onclick="flavorWizard.skipStep('<?php echo esc_js($wizard_id); ?>')">
                            <?php esc_html_e('Saltar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    <?php endif; ?>

                    <!-- Botón siguiente -->
                    <button type="button"
                            class="btn-next px-6 py-2 text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 rounded-xl transition-colors"
                            onclick="flavorWizard.nextStep('<?php echo esc_js($wizard_id); ?>')">
                        <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                    </button>

                    <!-- Botón enviar -->
                    <button type="submit"
                            class="btn-submit hidden px-6 py-2 text-sm font-medium text-white <?php echo esc_attr($color_classes['bg_solid']); ?> hover:opacity-90 rounded-xl transition-colors">
                        <?php echo esc_html($submit_label); ?>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
window.flavorWizard = window.flavorWizard || {
    prevStep: function(wizardId) {
        const wizard = document.getElementById(wizardId);
        if (!wizard) return;

        const currentStep = parseInt(wizard.dataset.currentStep);
        if (currentStep > 0) {
            this.goToStep(wizardId, currentStep - 1);
        }
    },

    nextStep: function(wizardId) {
        const wizard = document.getElementById(wizardId);
        if (!wizard) return;

        const currentStep = parseInt(wizard.dataset.currentStep);
        const totalSteps = parseInt(wizard.dataset.totalSteps);

        // Validar paso actual
        if (!this.validateStep(wizardId, currentStep)) {
            return;
        }

        if (currentStep < totalSteps - 1) {
            this.goToStep(wizardId, currentStep + 1);
        }
    },

    skipStep: function(wizardId) {
        const wizard = document.getElementById(wizardId);
        if (!wizard) return;

        const currentStep = parseInt(wizard.dataset.currentStep);
        const totalSteps = parseInt(wizard.dataset.totalSteps);
        const stepEl = wizard.querySelector(`[data-step="${currentStep}"]`);

        if (stepEl && stepEl.dataset.optional === 'true' && currentStep < totalSteps - 1) {
            this.goToStep(wizardId, currentStep + 1);
        }
    },

    goToStep: function(wizardId, stepIndex) {
        const wizard = document.getElementById(wizardId);
        if (!wizard) return;

        const totalSteps = parseInt(wizard.dataset.totalSteps);
        const previousStep = parseInt(wizard.dataset.currentStep);

        // Actualizar estado
        wizard.dataset.currentStep = stepIndex;

        // Mostrar/ocultar pasos
        wizard.querySelectorAll('.wizard-step').forEach((step, index) => {
            step.classList.toggle('hidden', index !== stepIndex);
        });

        // Actualizar indicadores
        wizard.querySelectorAll('.step-indicator').forEach((indicator, index) => {
            const circle = indicator.querySelector('.step-circle');
            const iconEl = indicator.querySelector('.step-icon');
            const checkEl = indicator.querySelector('.step-check');
            const titleEl = indicator.querySelector('.step-title');
            const lineEl = indicator.querySelector('.step-line');

            const isCompleted = index < stepIndex;
            const isCurrent = index === stepIndex;
            const isPending = index > stepIndex;

            // Círculo
            circle.classList.toggle('<?php echo esc_attr($color_classes['bg_solid']); ?>', isCurrent || isCompleted);
            circle.classList.toggle('border-transparent', isCurrent || isCompleted);
            circle.classList.toggle('text-white', isCurrent || isCompleted);
            circle.classList.toggle('border-gray-300', isPending);
            circle.classList.toggle('text-gray-400', isPending);
            circle.classList.toggle('bg-white', isPending);

            // Icono/Check
            if (iconEl && checkEl) {
                iconEl.classList.toggle('hidden', isCompleted);
                checkEl.classList.toggle('hidden', !isCompleted);
            }

            // Título
            if (titleEl) {
                titleEl.classList.toggle('text-gray-900', isCurrent || isCompleted);
                titleEl.classList.toggle('text-gray-400', isPending);
            }

            // Línea
            if (lineEl) {
                lineEl.classList.toggle('<?php echo esc_attr($color_classes['bg_solid']); ?>', isCompleted);
                lineEl.classList.toggle('bg-gray-200', !isCompleted);
            }
        });

        // Actualizar botones
        const btnPrev = wizard.querySelector('.btn-prev');
        const btnNext = wizard.querySelector('.btn-next');
        const btnSubmit = wizard.querySelector('.btn-submit');
        const btnSkip = wizard.querySelector('.btn-skip');

        if (btnPrev) btnPrev.classList.toggle('hidden', stepIndex === 0);
        if (btnNext) btnNext.classList.toggle('hidden', stepIndex === totalSteps - 1);
        if (btnSubmit) btnSubmit.classList.toggle('hidden', stepIndex !== totalSteps - 1);

        // Skip button
        if (btnSkip) {
            const currentStepEl = wizard.querySelector(`[data-step="${stepIndex}"]`);
            const isOptional = currentStepEl && currentStepEl.dataset.optional === 'true';
            btnSkip.classList.toggle('hidden', !isOptional || stepIndex === totalSteps - 1);
        }

        // Actualizar progreso
        const progressPercent = Math.round((stepIndex / (totalSteps - 1)) * 100);
        const progressBar = wizard.querySelector('.progress-bar');
        const progressText = wizard.querySelector('.progress-text');
        const progressPercentEl = wizard.querySelector('.progress-percent');

        if (progressBar) progressBar.style.width = progressPercent + '%';
        if (progressText) progressText.textContent = 'Paso ' + (stepIndex + 1) + ' de ' + totalSteps;
        if (progressPercentEl) progressPercentEl.textContent = progressPercent + '%';

        // Callback
        <?php if ($on_step_change): ?>
        <?php echo $on_step_change; ?>(stepIndex, previousStep);
        <?php endif; ?>

        // Dispatch event
        document.dispatchEvent(new CustomEvent('wizard-step-change', {
            detail: { wizardId, currentStep: stepIndex, previousStep, totalSteps }
        }));
    },

    validateStep: function(wizardId, stepIndex) {
        const wizard = document.getElementById(wizardId);
        if (!wizard) return false;

        const stepEl = wizard.querySelector(`[data-step="${stepIndex}"]`);
        if (!stepEl) return true;

        const requiredFields = stepEl.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            // Limpiar errores previos
            const errorEl = field.parentElement.querySelector('.field-error');
            if (errorEl) errorEl.remove();
            field.classList.remove('border-red-500');

            // Validar
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('border-red-500');

                const error = document.createElement('p');
                error.className = 'field-error mt-1 text-sm text-red-600';
                error.textContent = '<?php echo esc_js(__('Este campo es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                field.parentElement.appendChild(error);
            }
        });

        return isValid;
    },

    submit: function(wizardId) {
        const wizard = document.getElementById(wizardId);
        if (!wizard) return;

        const form = wizard.querySelector('.wizard-form');
        if (!form) return;

        // Validar último paso
        const currentStep = parseInt(wizard.dataset.currentStep);
        if (!this.validateStep(wizardId, currentStep)) {
            return;
        }

        <?php if ($on_complete): ?>
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        <?php echo $on_complete; ?>(data);
        <?php else: ?>
        form.submit();
        <?php endif; ?>
    }
};

// Initialize form submit handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('#<?php echo esc_js($wizard_id); ?>-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            flavorWizard.submit('<?php echo esc_js($wizard_id); ?>');
        });
    }
});
</script>
