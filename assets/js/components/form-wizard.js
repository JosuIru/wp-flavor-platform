/**
 * Form Wizard Component
 *
 * Convierte formularios largos en pasos manejables con navegación y validación.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 *
 * @example
 * const wizard = new FlavorFormWizard(document.getElementById('mi-formulario'), {
 *     onStepChange: (currentStep, totalSteps) => console.log(`Paso ${currentStep}/${totalSteps}`),
 *     onComplete: (formData) => console.log('Formulario completo', formData)
 * });
 */

class FlavorFormWizard {
    /**
     * Constructor del wizard de formularios
     *
     * @param {HTMLFormElement} formElement - El elemento form a convertir en wizard
     * @param {Object} options - Opciones de configuración
     * @param {Function} options.onStepChange - Callback al cambiar de paso
     * @param {Function} options.onComplete - Callback al completar el formulario
     * @param {boolean} options.validateOnNext - Validar antes de avanzar (default: true)
     * @param {string} options.nextButtonText - Texto del botón "Siguiente"
     * @param {string} options.prevButtonText - Texto del botón "Anterior"
     * @param {string} options.submitButtonText - Texto del botón "Finalizar"
     */
    constructor(formElement, options = {}) {
        this.form = formElement;
        this.options = {
            onStepChange: null,
            onComplete: null,
            validateOnNext: true,
            nextButtonText: 'Siguiente',
            prevButtonText: 'Anterior',
            submitButtonText: 'Finalizar',
            ...options
        };

        this.steps = Array.from(this.form.querySelectorAll('.wizard-step'));
        this.currentStep = 0;
        this.totalSteps = this.steps.length;

        if (this.totalSteps === 0) {
            console.error('FlavorFormWizard: No se encontraron elementos con clase .wizard-step');
            return;
        }

        this.init();
    }

    /**
     * Inicializa el wizard
     */
    init() {
        // Ocultar todos los pasos excepto el primero
        this.steps.forEach((step, index) => {
            step.style.display = index === 0 ? 'block' : 'none';
        });

        // Crear barra de progreso
        this.createProgressBar();

        // Crear navegación
        this.createNavigation();

        // Mostrar primer paso
        this.showStep(0);
    }

    /**
     * Crea la barra de progreso
     */
    createProgressBar() {
        const progressContainer = document.createElement('div');
        progressContainer.className = 'wizard-progress';
        progressContainer.innerHTML = `
            <div class="wizard-progress-bar">
                <div class="wizard-progress-fill" style="width: 0%"></div>
            </div>
            <div class="wizard-progress-text">
                <span class="wizard-current-step">1</span> /
                <span class="wizard-total-steps">${this.totalSteps}</span>
            </div>
        `;

        // Insertar antes del primer paso
        this.form.insertBefore(progressContainer, this.steps[0]);

        this.progressBar = progressContainer.querySelector('.wizard-progress-fill');
        this.progressText = progressContainer.querySelector('.wizard-current-step');
    }

    /**
     * Crea los botones de navegación
     */
    createNavigation() {
        const navContainer = document.createElement('div');
        navContainer.className = 'wizard-navigation';
        navContainer.innerHTML = `
            <button type="button" class="button button-secondary wizard-btn-prev">
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                ${this.options.prevButtonText}
            </button>
            <button type="button" class="button button-primary wizard-btn-next">
                ${this.options.nextButtonText}
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
            <button type="submit" class="button button-primary wizard-btn-submit" style="display: none;">
                ${this.options.submitButtonText}
            </button>
        `;

        // Insertar después del último paso
        this.form.appendChild(navContainer);

        // Event listeners
        this.btnPrev = navContainer.querySelector('.wizard-btn-prev');
        this.btnNext = navContainer.querySelector('.wizard-btn-next');
        this.btnSubmit = navContainer.querySelector('.wizard-btn-submit');

        this.btnPrev.addEventListener('click', () => this.previousStep());
        this.btnNext.addEventListener('click', () => this.nextStep());
    }

    /**
     * Muestra un paso específico
     *
     * @param {number} stepIndex - Índice del paso a mostrar
     */
    showStep(stepIndex) {
        // Ocultar todos los pasos
        this.steps.forEach(step => step.style.display = 'none');

        // Mostrar paso actual
        this.steps[stepIndex].style.display = 'block';
        this.currentStep = stepIndex;

        // Actualizar progreso
        const progress = ((stepIndex + 1) / this.totalSteps) * 100;
        this.progressBar.style.width = `${progress}%`;
        this.progressText.textContent = stepIndex + 1;

        // Actualizar navegación
        this.updateNavigation();

        // Scroll al inicio del formulario
        this.form.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Callback
        if (this.options.onStepChange) {
            this.options.onStepChange(stepIndex + 1, this.totalSteps);
        }
    }

    /**
     * Actualiza el estado de los botones de navegación
     */
    updateNavigation() {
        const isFirstStep = this.currentStep === 0;
        const isLastStep = this.currentStep === this.totalSteps - 1;

        this.btnPrev.style.display = isFirstStep ? 'none' : 'inline-flex';
        this.btnNext.style.display = isLastStep ? 'none' : 'inline-flex';
        this.btnSubmit.style.display = isLastStep ? 'inline-block' : 'none';
    }

    /**
     * Avanza al siguiente paso
     */
    async nextStep() {
        // Validar paso actual si está habilitado
        if (this.options.validateOnNext) {
            const isValid = await this.validateStep(this.currentStep);
            if (!isValid) {
                return;
            }
        }

        if (this.currentStep < this.totalSteps - 1) {
            this.showStep(this.currentStep + 1);
        }
    }

    /**
     * Retrocede al paso anterior
     */
    previousStep() {
        if (this.currentStep > 0) {
            this.showStep(this.currentStep - 1);
        }
    }

    /**
     * Valida un paso específico
     *
     * @param {number} stepIndex - Índice del paso a validar
     * @return {boolean} - True si es válido, false si no
     */
    async validateStep(stepIndex) {
        const step = this.steps[stepIndex];
        const inputs = step.querySelectorAll('input, select, textarea');

        let isValid = true;
        const errors = [];

        // Limpiar errores previos
        step.querySelectorAll('.wizard-error').forEach(el => el.remove());

        inputs.forEach(input => {
            // Validación nativa HTML5
            if (!input.checkValidity()) {
                isValid = false;
                errors.push({
                    input: input,
                    message: input.validationMessage
                });
            }

            // Validación custom (data-validate)
            const customValidation = input.getAttribute('data-validate');
            if (customValidation) {
                try {
                    const validationFn = new Function('value', customValidation);
                    const customResult = validationFn(input.value);
                    if (customResult !== true) {
                        isValid = false;
                        errors.push({
                            input: input,
                            message: typeof customResult === 'string' ? customResult : 'Error de validación'
                        });
                    }
                } catch (e) {
                    console.error('Error en validación custom:', e);
                }
            }
        });

        // Mostrar errores
        if (!isValid) {
            errors.forEach(error => {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'wizard-error';
                errorDiv.textContent = error.message;
                error.input.parentNode.insertBefore(errorDiv, error.input.nextSibling);
                error.input.classList.add('error');
            });

            // Scroll al primer error
            const firstError = step.querySelector('.wizard-error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }

        return isValid;
    }

    /**
     * Obtiene los datos del formulario como objeto
     *
     * @return {Object} - Datos del formulario
     */
    getFormData() {
        const formData = new FormData(this.form);
        const data = {};

        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }

        return data;
    }

    /**
     * Va a un paso específico
     *
     * @param {number} stepIndex - Índice del paso
     */
    goToStep(stepIndex) {
        if (stepIndex >= 0 && stepIndex < this.totalSteps) {
            this.showStep(stepIndex);
        }
    }

    /**
     * Destruye el wizard y restaura el formulario original
     */
    destroy() {
        // Mostrar todos los pasos
        this.steps.forEach(step => step.style.display = 'block');

        // Eliminar elementos del wizard
        this.form.querySelector('.wizard-progress')?.remove();
        this.form.querySelector('.wizard-navigation')?.remove();
    }
}

// Exportar para uso global
window.FlavorFormWizard = FlavorFormWizard;
