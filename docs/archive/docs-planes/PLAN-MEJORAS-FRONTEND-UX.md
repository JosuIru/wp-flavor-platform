# PLAN DE MEJORAS FRONTEND UX - Flavor Platform

**Fecha**: 2026-03-22
**Basado en**: Análisis exhaustivo de 10 módulos principales
**Objetivo**: Mejorar usabilidad de forma sistemática y reutilizable

---

## 🎯 ESTRATEGIA GENERAL

### Enfoque: Componentes Reutilizables

En lugar de mejorar módulo por módulo, crear **librería de componentes UX** que todos puedan usar.

**Beneficios**:
- ✅ Consistencia visual entre módulos
- ✅ Mantenimiento centralizado
- ✅ Desarrollo más rápido
- ✅ Mejor experiencia de usuario

---

## 📦 COMPONENTES A CREAR

### 1. **Empty States Component** (PRIORIDAD 1)

**Problema Actual**: Todos los módulos muestran solo texto plano "No hay X".

**Solución**: Componente reutilizable para estados vacíos.

**Ubicación**: `templates/components/shared/empty-state.php`

```php
<?php
/**
 * Empty State Component
 *
 * @param array $args {
 *     @type string $icon       Dashicon class (ej: 'dashicons-carrot')
 *     @type string $title      Título del estado vacío
 *     @type string $message    Mensaje descriptivo
 *     @type array  $actions    Array de botones: ['text' => '...', 'url' => '...', 'class' => 'primary']
 * }
 */
function flavor_render_empty_state($args = []) {
    $defaults = [
        'icon' => 'dashicons-info',
        'title' => __('No hay contenido', 'flavor-chat-ia'),
        'message' => '',
        'actions' => [],
        'image' => '', // Opcional: URL de imagen SVG
    ];

    $args = wp_parse_args($args, $defaults);
    ?>
    <div class="flavor-empty-state">
        <?php if ($args['image']): ?>
            <img src="<?php echo esc_url($args['image']); ?>" alt="" class="empty-state-image">
        <?php else: ?>
            <span class="dashicons <?php echo esc_attr($args['icon']); ?>"></span>
        <?php endif; ?>

        <h3 class="empty-state-title"><?php echo esc_html($args['title']); ?></h3>

        <?php if ($args['message']): ?>
            <p class="empty-state-message"><?php echo esc_html($args['message']); ?></p>
        <?php endif; ?>

        <?php if (!empty($args['actions'])): ?>
            <div class="empty-state-actions">
                <?php foreach ($args['actions'] as $action): ?>
                    <a href="<?php echo esc_url($action['url']); ?>"
                       class="button button-<?php echo esc_attr($action['class'] ?? 'secondary'); ?>">
                        <?php echo esc_html($action['text']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
```

**CSS**: `assets/css/components/empty-state.css`

```css
.flavor-empty-state {
    text-align: center;
    padding: 60px 20px;
    max-width: 500px;
    margin: 0 auto;
}

.flavor-empty-state .dashicons {
    font-size: 64px;
    width: 64px;
    height: 64px;
    color: #999;
    margin-bottom: 20px;
}

.flavor-empty-state .empty-state-image {
    max-width: 200px;
    opacity: 0.7;
    margin-bottom: 20px;
}

.empty-state-title {
    font-size: 20px;
    font-weight: 600;
    color: #333;
    margin: 0 0 12px 0;
}

.empty-state-message {
    color: #666;
    font-size: 14px;
    line-height: 1.6;
    margin: 0 0 24px 0;
}

.empty-state-actions {
    display: flex;
    gap: 12px;
    justify-content: center;
    flex-wrap: wrap;
}
```

**Uso en Módulos**:

```php
// En grupos-consumo cuando no hay productos
flavor_render_empty_state([
    'icon' => 'dashicons-carrot',
    'title' => __('No hay productos en el catálogo', 'flavor-chat-ia'),
    'message' => __('El próximo ciclo de pedidos comenzará pronto. Explora otras categorías o vuelve más tarde.', 'flavor-chat-ia'),
    'actions' => [
        ['text' => __('Ver categorías', 'flavor-chat-ia'), 'url' => '/categorias/', 'class' => 'primary'],
        ['text' => __('Ver próximo ciclo', 'flavor-chat-ia'), 'url' => '/ciclos/', 'class' => 'secondary']
    ]
]);
```

---

### 2. **Form Wizard Component** (PRIORIDAD 1)

**Problema**: Formularios largos abruman (Socios: 15 campos, Checkout: 8 campos).

**Solución**: Wizard multi-paso con barra de progreso.

**Ubicación**: `assets/js/components/form-wizard.js`

```javascript
/**
 * Form Wizard Component
 * Convierte formularios largos en wizards de pasos
 */
class FlavorFormWizard {
    constructor(formElement, options = {}) {
        this.form = formElement;
        this.steps = Array.from(formElement.querySelectorAll('.wizard-step'));
        this.currentStep = 0;
        this.options = {
            showProgress: true,
            validateOnNext: true,
            ...options
        };

        this.init();
    }

    init() {
        this.hideAllSteps();
        this.showStep(0);
        this.createNavigation();
        if (this.options.showProgress) {
            this.createProgressBar();
        }
        this.bindEvents();
    }

    createProgressBar() {
        const progress = document.createElement('div');
        progress.className = 'wizard-progress';
        progress.innerHTML = `
            <div class="wizard-progress-bar">
                <div class="wizard-progress-fill" style="width: ${this.getProgress()}%"></div>
            </div>
            <div class="wizard-steps-indicator">
                ${this.steps.map((step, i) => `
                    <div class="wizard-step-dot ${i === 0 ? 'active' : ''}" data-step="${i}">
                        <span>${i + 1}</span>
                        <small>${step.dataset.title || 'Paso ' + (i + 1)}</small>
                    </div>
                `).join('')}
            </div>
        `;
        this.form.prepend(progress);
    }

    createNavigation() {
        const nav = document.createElement('div');
        nav.className = 'wizard-navigation';
        nav.innerHTML = `
            <button type="button" class="button wizard-prev" disabled>
                <span class="dashicons dashicons-arrow-left-alt2"></span>
                ${this.options.prevText || 'Anterior'}
            </button>
            <button type="button" class="button button-primary wizard-next">
                ${this.options.nextText || 'Siguiente'}
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </button>
            <button type="submit" class="button button-primary wizard-submit" style="display:none;">
                ${this.options.submitText || 'Finalizar'}
            </button>
        `;
        this.form.appendChild(nav);
    }

    showStep(index) {
        this.steps.forEach((step, i) => {
            step.style.display = i === index ? 'block' : 'none';
        });

        this.currentStep = index;
        this.updateNavigation();
        this.updateProgress();

        // Scroll to top
        this.form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    updateNavigation() {
        const prevBtn = this.form.querySelector('.wizard-prev');
        const nextBtn = this.form.querySelector('.wizard-next');
        const submitBtn = this.form.querySelector('.wizard-submit');

        prevBtn.disabled = this.currentStep === 0;

        if (this.currentStep === this.steps.length - 1) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'inline-block';
        } else {
            nextBtn.style.display = 'inline-block';
            submitBtn.style.display = 'none';
        }
    }

    updateProgress() {
        const progress = this.form.querySelector('.wizard-progress-fill');
        const dots = this.form.querySelectorAll('.wizard-step-dot');

        if (progress) {
            progress.style.width = this.getProgress() + '%';
        }

        dots.forEach((dot, i) => {
            dot.classList.toggle('active', i === this.currentStep);
            dot.classList.toggle('completed', i < this.currentStep);
        });
    }

    getProgress() {
        return ((this.currentStep + 1) / this.steps.length) * 100;
    }

    validateStep(stepIndex) {
        const step = this.steps[stepIndex];
        const inputs = step.querySelectorAll('input[required], select[required], textarea[required]');

        let valid = true;
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                valid = false;
                input.classList.add('error');
            } else {
                input.classList.remove('error');
            }
        });

        return valid;
    }

    bindEvents() {
        this.form.querySelector('.wizard-next').addEventListener('click', () => {
            if (this.options.validateOnNext && !this.validateStep(this.currentStep)) {
                alert('Por favor completa todos los campos requeridos.');
                return;
            }

            if (this.currentStep < this.steps.length - 1) {
                this.showStep(this.currentStep + 1);
            }
        });

        this.form.querySelector('.wizard-prev').addEventListener('click', () => {
            if (this.currentStep > 0) {
                this.showStep(this.currentStep - 1);
            }
        });

        // Click en dots para navegar
        this.form.querySelectorAll('.wizard-step-dot').forEach(dot => {
            dot.addEventListener('click', (e) => {
                const step = parseInt(e.currentTarget.dataset.step);
                if (step < this.currentStep) { // Solo permitir ir atrás
                    this.showStep(step);
                }
            });
        });
    }

    hideAllSteps() {
        this.steps.forEach(step => step.style.display = 'none');
    }
}

// Auto-inicializar wizards
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.flavor-form-wizard').forEach(form => {
        new FlavorFormWizard(form);
    });
});
```

**CSS**: `assets/css/components/form-wizard.css`

```css
.wizard-progress {
    margin-bottom: 32px;
}

.wizard-progress-bar {
    background: #e0e0e0;
    height: 6px;
    border-radius: 3px;
    overflow: hidden;
    margin-bottom: 20px;
}

.wizard-progress-fill {
    background: linear-gradient(90deg, #2271b1, #4a90e2);
    height: 100%;
    transition: width 0.3s ease;
}

.wizard-steps-indicator {
    display: flex;
    justify-content: space-between;
    gap: 12px;
}

.wizard-step-dot {
    flex: 1;
    text-align: center;
    cursor: pointer;
}

.wizard-step-dot span {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #e0e0e0;
    color: #666;
    font-weight: 600;
    margin-bottom: 8px;
    transition: all 0.3s;
}

.wizard-step-dot.active span {
    background: #2271b1;
    color: white;
    transform: scale(1.1);
}

.wizard-step-dot.completed span {
    background: #46b450;
    color: white;
}

.wizard-step-dot small {
    display: block;
    font-size: 11px;
    color: #666;
}

.wizard-navigation {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #ddd;
}

.wizard-step {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
```

**Uso en HTML**:

```php
<!-- Formulario de alta de socios convertido en wizard -->
<form class="flavor-form-wizard" method="post">

    <!-- Paso 1: Tipo de Membresía -->
    <div class="wizard-step" data-title="Tipo">
        <h3>Selecciona tu tipo de membresía</h3>
        <!-- Campos del paso 1 -->
    </div>

    <!-- Paso 2: Datos Personales -->
    <div class="wizard-step" data-title="Datos">
        <h3>Completa tus datos</h3>
        <!-- Campos del paso 2 -->
    </div>

    <!-- Paso 3: Confirmación -->
    <div class="wizard-step" data-title="Confirmar">
        <h3>Revisa tu solicitud</h3>
        <!-- Resumen y confirmación -->
    </div>

</form>
```

---

### 3. **Advanced Search Component** (PRIORIDAD 2)

**Problema**: Búsquedas básicas sin autocomplete ni sugerencias.

**Solución**: Componente de búsqueda con autocomplete AJAX.

**Ubicación**: `assets/js/components/advanced-search.js`

```javascript
/**
 * Advanced Search with Autocomplete
 */
class FlavorAdvancedSearch {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            minChars: 3,
            endpoint: '/wp-json/flavor/v1/search',
            resultTemplate: null,
            onSelect: null,
            ...options
        };

        this.resultsContainer = null;
        this.debounceTimer = null;
        this.init();
    }

    init() {
        this.createResultsContainer();
        this.bindEvents();
    }

    createResultsContainer() {
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.className = 'flavor-search-results';
        this.resultsContainer.style.display = 'none';
        this.input.parentNode.style.position = 'relative';
        this.input.parentNode.appendChild(this.resultsContainer);
    }

    bindEvents() {
        this.input.addEventListener('input', (e) => {
            clearTimeout(this.debounceTimer);
            const query = e.target.value.trim();

            if (query.length < this.options.minChars) {
                this.hideResults();
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.search(query);
            }, 300);
        });

        this.input.addEventListener('blur', () => {
            setTimeout(() => this.hideResults(), 200);
        });

        this.input.addEventListener('focus', () => {
            if (this.resultsContainer.children.length > 0) {
                this.showResults();
            }
        });
    }

    async search(query) {
        try {
            const url = new URL(this.options.endpoint, window.location.origin);
            url.searchParams.append('q', query);
            url.searchParams.append('module', this.options.module || '');

            const response = await fetch(url.toString());
            const data = await response.json();

            this.renderResults(data.results || []);
        } catch (error) {
            console.error('Search error:', error);
        }
    }

    renderResults(results) {
        this.resultsContainer.innerHTML = '';

        if (results.length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="search-no-results">
                    <span class="dashicons dashicons-search"></span>
                    <p>No se encontraron resultados</p>
                </div>
            `;
            this.showResults();
            return;
        }

        results.forEach(result => {
            const item = document.createElement('a');
            item.className = 'search-result-item';
            item.href = result.url;

            if (this.options.resultTemplate) {
                item.innerHTML = this.options.resultTemplate(result);
            } else {
                item.innerHTML = `
                    <div class="result-icon">
                        <span class="dashicons ${result.icon || 'dashicons-admin-post'}"></span>
                    </div>
                    <div class="result-content">
                        <div class="result-title">${this.highlight(result.title, this.input.value)}</div>
                        ${result.excerpt ? `<div class="result-excerpt">${result.excerpt}</div>` : ''}
                    </div>
                `;
            }

            item.addEventListener('click', (e) => {
                if (this.options.onSelect) {
                    e.preventDefault();
                    this.options.onSelect(result);
                }
            });

            this.resultsContainer.appendChild(item);
        });

        this.showResults();
    }

    highlight(text, query) {
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    showResults() {
        this.resultsContainer.style.display = 'block';
    }

    hideResults() {
        this.resultsContainer.style.display = 'none';
    }
}

// Auto-inicializar
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-flavor-search]').forEach(input => {
        new FlavorAdvancedSearch(input, {
            endpoint: input.dataset.endpoint,
            module: input.dataset.module
        });
    });
});
```

**CSS**: `assets/css/components/advanced-search.css`

```css
.flavor-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #ddd;
    border-top: none;
    border-radius: 0 0 4px 4px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 1000;
    margin-top: -1px;
}

.search-result-item {
    display: flex;
    gap: 12px;
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    text-decoration: none;
    color: inherit;
    transition: background 0.2s;
}

.search-result-item:hover {
    background: #f8f9fa;
}

.result-icon .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    color: #666;
}

.result-content {
    flex: 1;
}

.result-title {
    font-weight: 500;
    color: #1d2327;
    margin-bottom: 4px;
}

.result-title mark {
    background: #fff3cd;
    padding: 0 2px;
}

.result-excerpt {
    font-size: 13px;
    color: #646970;
    line-height: 1.4;
}

.search-no-results {
    padding: 32px 16px;
    text-align: center;
    color: #666;
}

.search-no-results .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    opacity: 0.3;
    margin-bottom: 12px;
}
```

**Uso**:

```php
<input type="search"
       placeholder="Buscar productos..."
       data-flavor-search
       data-endpoint="/wp-json/flavor/v1/search/productos"
       data-module="grupos_consumo">
```

---

### 4. **Responsive Grid Component** (PRIORIDAD 1)

**Problema**: Grids con columnas fijas que no adaptan en móvil.

**Solución**: CSS Grid adaptativo universal.

**Ubicación**: `assets/css/components/responsive-grid.css`

```css
/**
 * Responsive Grid System
 * Grids que adaptan automáticamente según viewport
 */

/* Grid básico adaptativo */
.flavor-grid {
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

/* Variantes de tamaño mínimo */
.flavor-grid--compact {
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}

.flavor-grid--wide {
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
}

/* Columnas fijas en desktop, adaptativas en móvil */
.flavor-grid--2-cols {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
}

@media (min-width: 768px) {
    .flavor-grid--2-cols {
        grid-template-columns: repeat(2, 1fr);
    }
}

.flavor-grid--3-cols {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

@media (min-width: 992px) {
    .flavor-grid--3-cols {
        grid-template-columns: repeat(3, 1fr);
    }
}

.flavor-grid--4-cols {
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

@media (min-width: 1200px) {
    .flavor-grid--4-cols {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* Cards dentro del grid */
.flavor-grid-card {
    background: white;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
    transition: box-shadow 0.3s, transform 0.3s;
}

.flavor-grid-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

/* Masonry layout opcional */
.flavor-grid--masonry {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    grid-auto-rows: 20px;
    gap: 16px;
}

.flavor-grid--masonry > * {
    grid-row-end: span var(--rows, 10);
}
```

**Uso**:

```php
<!-- Simple -->
<div class="flavor-grid">
    <div class="flavor-grid-card">Card 1</div>
    <div class="flavor-grid-card">Card 2</div>
    <div class="flavor-grid-card">Card 3</div>
</div>

<!-- 3 columnas en desktop -->
<div class="flavor-grid flavor-grid--3-cols">
    <!-- Cards -->
</div>
```

---

### 5. **Filter Bar Component** (PRIORIDAD 2)

**Problema**: Módulos sin filtros o filtros básicos.

**Solución**: Barra de filtros reutilizable con AJAX.

**Ubicación**: `templates/components/shared/filter-bar.php`

```php
<?php
/**
 * Filter Bar Component
 *
 * @param array $args {
 *     @type array $filters Array de filtros: ['type' => 'search|select|checkbox|date', 'name' => '...', 'options' => [...]]
 *     @type string $action AJAX action para filtrar
 *     @type string $target_container Selector del contenedor de resultados
 * }
 */
function flavor_render_filter_bar($args = []) {
    $defaults = [
        'filters' => [],
        'action' => '',
        'target_container' => '#results-container',
        'show_reset' => true,
    ];

    $args = wp_parse_args($args, $defaults);
    ?>
    <form class="flavor-filter-bar"
          data-action="<?php echo esc_attr($args['action']); ?>"
          data-target="<?php echo esc_attr($args['target_container']); ?>">

        <div class="filter-bar-items">
            <?php foreach ($args['filters'] as $filter): ?>
                <div class="filter-item filter-item--<?php echo esc_attr($filter['type']); ?>">

                    <?php if ($filter['type'] === 'search'): ?>
                        <input type="search"
                               name="<?php echo esc_attr($filter['name']); ?>"
                               placeholder="<?php echo esc_attr($filter['placeholder'] ?? __('Buscar...', 'flavor-chat-ia')); ?>"
                               class="filter-input filter-search">

                    <?php elseif ($filter['type'] === 'select'): ?>
                        <select name="<?php echo esc_attr($filter['name']); ?>" class="filter-select">
                            <option value=""><?php echo esc_html($filter['label'] ?? __('Todos', 'flavor-chat-ia')); ?></option>
                            <?php foreach ($filter['options'] as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($filter['type'] === 'checkbox'): ?>
                        <label class="filter-checkbox">
                            <input type="checkbox" name="<?php echo esc_attr($filter['name']); ?>" value="1">
                            <?php echo esc_html($filter['label']); ?>
                        </label>

                    <?php elseif ($filter['type'] === 'date'): ?>
                        <input type="date"
                               name="<?php echo esc_attr($filter['name']); ?>"
                               class="filter-date">
                    <?php endif; ?>

                </div>
            <?php endforeach; ?>
        </div>

        <div class="filter-bar-actions">
            <button type="submit" class="button button-primary">
                <span class="dashicons dashicons-filter"></span>
                <?php _e('Filtrar', 'flavor-chat-ia'); ?>
            </button>

            <?php if ($args['show_reset']): ?>
                <button type="reset" class="button button-secondary">
                    <?php _e('Limpiar', 'flavor-chat-ia'); ?>
                </button>
            <?php endif; ?>
        </div>

        <div class="filter-loading" style="display:none;">
            <span class="spinner is-active"></span>
        </div>
    </form>
    <?php
}
```

**JavaScript**: `assets/js/components/filter-bar.js`

```javascript
/**
 * Filter Bar Component with AJAX
 */
class FlavorFilterBar {
    constructor(formElement) {
        this.form = formElement;
        this.action = formElement.dataset.action;
        this.target = document.querySelector(formElement.dataset.target);
        this.loading = formElement.querySelector('.filter-loading');

        this.bindEvents();
    }

    bindEvents() {
        // Submit en formulario
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.applyFilters();
        });

        // Auto-submit en cambios de select
        this.form.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => {
                this.applyFilters();
            });
        });

        // Auto-submit en checkboxes
        this.form.querySelectorAll('.filter-checkbox input').forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.applyFilters();
            });
        });

        // Debounced search
        let searchTimer;
        this.form.querySelectorAll('.filter-search').forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => {
                    this.applyFilters();
                }, 500);
            });
        });

        // Reset
        this.form.addEventListener('reset', () => {
            setTimeout(() => {
                this.applyFilters();
            }, 10);
        });
    }

    async applyFilters() {
        const formData = new FormData(this.form);
        formData.append('action', this.action);
        formData.append('nonce', flavorAjax.nonce);

        this.showLoading();

        try {
            const response = await fetch(flavorAjax.url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.target.innerHTML = data.data.html;

                // Dispatch custom event
                this.target.dispatchEvent(new CustomEvent('flavor:filtered', {
                    detail: { filters: Object.fromEntries(formData) }
                }));
            }
        } catch (error) {
            console.error('Filter error:', error);
        } finally {
            this.hideLoading();
        }
    }

    showLoading() {
        this.loading.style.display = 'block';
        this.target.style.opacity = '0.5';
    }

    hideLoading() {
        this.loading.style.display = 'none';
        this.target.style.opacity = '1';
    }
}

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.flavor-filter-bar').forEach(form => {
        new FlavorFilterBar(form);
    });
});
```

**Uso en Talleres**:

```php
flavor_render_filter_bar([
    'action' => 'flavor_filter_talleres',
    'target_container' => '#talleres-grid',
    'filters' => [
        [
            'type' => 'search',
            'name' => 's',
            'placeholder' => __('Buscar talleres...', 'flavor-chat-ia')
        ],
        [
            'type' => 'select',
            'name' => 'categoria',
            'label' => __('Categoría', 'flavor-chat-ia'),
            'options' => [
                'artesania' => __('Artesanía', 'flavor-chat-ia'),
                'cocina' => __('Cocina', 'flavor-chat-ia'),
                'tecnologia' => __('Tecnología', 'flavor-chat-ia'),
            ]
        ],
        [
            'type' => 'select',
            'name' => 'nivel',
            'label' => __('Nivel', 'flavor-chat-ia'),
            'options' => [
                'principiante' => __('Principiante', 'flavor-chat-ia'),
                'intermedio' => __('Intermedio', 'flavor-chat-ia'),
                'avanzado' => __('Avanzado', 'flavor-chat-ia'),
            ]
        ],
        [
            'type' => 'checkbox',
            'name' => 'plazas_disponibles',
            'label' => __('Solo con plazas', 'flavor-chat-ia')
        ]
    ]
]);
```

---

## 🎯 PLAN DE IMPLEMENTACIÓN

### Fase 1: Componentes Base (Semana 1)
**Objetivo**: Crear librería de componentes reutilizables

1. **Empty State Component** ✅
   - Crear template PHP
   - Crear CSS
   - Documentar uso

2. **Form Wizard Component** ✅
   - Crear JavaScript clase
   - Crear CSS
   - Crear helper PHP

3. **Responsive Grid** ✅
   - Crear CSS con variantes
   - Documentar clases

4. **Advanced Search** ✅
   - Crear JavaScript
   - Crear endpoint REST API base
   - Crear CSS

5. **Filter Bar** ✅
   - Crear template PHP
   - Crear JavaScript
   - Crear CSS

### Fase 2: Aplicar a Módulos P1 (Semana 2)
**Objetivo**: Mejorar módulos críticos

**Módulos a mejorar**:
1. **Socios** - Aplicar Form Wizard al alta
2. **Grupos-Consumo** - Aplicar Form Wizard al checkout
3. **Marketplace** - Aplicar Responsive Grid + Filter Bar
4. **Talleres** - Aplicar Filter Bar + Empty States
5. **Espacios-Comunes** - Crear calendario visual

**Tareas**:
- Refactorizar formularios largos con wizard
- Aplicar empty states a todos los listados vacíos
- Hacer grids responsive
- Agregar filtros donde falten

### Fase 3: Mejoras Específicas (Semana 3)
**Objetivo**: Resolver problemas específicos

1. **Calendario Visual** (Espacios-Comunes, Talleres)
   - Integrar librería FullCalendar o crear custom
   - Vista mensual/semanal

2. **Galerías de Imágenes** (Marketplace, Biblioteca)
   - Lightbox con zoom
   - Slider/carrusel

3. **Sistema de Valoraciones** (Marketplace, Biblioteca)
   - Componente de estrellas
   - AJAX para votar

4. **Badges y Gamificación** (Socios, Biblioteca)
   - Diseñar badges
   - Mostrar en perfiles

### Fase 4: Testing y Refinamiento (Semana 4)
**Objetivo**: Asegurar calidad

- Testing responsive en dispositivos reales
- Testing de usabilidad con usuarios
- Ajustes de accesibilidad (ARIA labels, keyboard navigation)
- Optimización de performance
- Documentación de cada componente

---

## 📊 MÉTRICAS DE ÉXITO

### Objetivos Cuantificables

| Métrica | Antes | Objetivo | Método |
|---------|-------|----------|--------|
| Tiempo completar formulario alta | 5-8 min | <3 min | Analytics |
| Tasa de abandono checkout | 40% | <20% | Tracking |
| Búsquedas sin resultados | 25% | <10% | Logs |
| Uso de filtros | 15% | >40% | Click tracking |
| Satisfacción UX (1-5) | 3.2 | >4.2 | Encuesta |

### Indicadores Cualitativos

- ✅ Comentarios positivos de usuarios
- ✅ Reducción de tickets de soporte "no encuentro X"
- ✅ Aumento de engagement (tiempo en plataforma)
- ✅ Mayor tasa de conversión (inscripciones, pedidos)

---

## 🛠️ HERRAMIENTAS Y LIBRERÍAS

### Ya Disponibles en Flavor
- ✅ WordPress jQuery
- ✅ Dashicons
- ✅ CSS Grid nativo

### A Considerar Agregar
- **FullCalendar.js** - Para calendarios visuales (MIT License)
- **PhotoSwipe** - Para galerías lightbox (MIT License)
- **Choices.js** - Para selects mejorados (MIT License)
- **Flatpickr** - Para date pickers (MIT License)

**Nota**: Todas son ligeras y sin dependencias pesadas.

---

## 📚 DOCUMENTACIÓN A CREAR

1. **Guía de Componentes**
   - `/docs/COMPONENTES-FRONTEND.md`
   - Ejemplos de uso de cada componente
   - Props y configuración

2. **Guía de Estilos UX**
   - `/docs/UX-STYLE-GUIDE.md`
   - Patrones de diseño
   - Mejores prácticas

3. **Changelog de Mejoras**
   - Documentar cada mejora implementada
   - Antes/después con screenshots

---

## ⚡ QUICK WINS (Implementación Inmediata)

Mejoras que se pueden hacer YA sin componentes nuevos:

### 1. Responsive Fixes CSS (30 min)
```css
/* Agregar a todos los grids problemáticos */
.module-grid {
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)) !important;
}
```

### 2. Empty States Básicos (1 hora)
Reemplazar todos los "No hay X" por estructura básica con icono.

### 3. Loading States (30 min)
Agregar spinners en todos los AJAX handlers.

### 4. Focus States (30 min)
Mejorar `:focus` en formularios para accesibilidad.

---

## 🎨 MOCKUPS SUGERIDOS

### Antes/Después de Formulario Alta Socios

**ANTES**:
```
┌─────────────────────────────────┐
│ Alta de Socio                   │
├─────────────────────────────────┤
│ Nombre: [_________________]     │
│ Apellido: [_______________]     │
│ Email: [__________________]     │
│ Teléfono: [_______________]     │
│ Dirección: [______________]     │
│ Ciudad: [_________________]     │
│ CP: [________]                  │
│ Tipo: [▼ Seleccionar]          │
│ Cuota: [▼ Seleccionar]         │
│ IBAN: [___________________]     │
│ ... (5 campos más)              │
│                                 │
│ [     Enviar Solicitud    ]     │
└─────────────────────────────────┘
```

**DESPUÉS (Wizard)**:
```
┌─────────────────────────────────┐
│ Alta de Socio                   │
├─────────────────────────────────┤
│ ━━━━━━━━━━━━━━━━━━━━━━━━ 33%   │
│ ● ─────── ○ ─────── ○            │
│ Tipo    Datos   Confirmar       │
│                                 │
│ Paso 1: Selecciona Membresía    │
│                                 │
│ ┌───────────────┐  ┌──────────┐ │
│ │ ⭐ STANDARD  │  │ PREMIUM  │ │
│ │ 30€/año      │  │ 50€/año  │ │
│ │ [Elegir]     │  │ [Elegir] │ │
│ └───────────────┘  └──────────┘ │
│                                 │
│ [ ← Atrás ]  [ Siguiente → ]    │
└─────────────────────────────────┘
```

---

## 🚀 CONCLUSIÓN

Con estos componentes reutilizables, podemos:

1. ✅ **Mejorar usabilidad** de forma consistente
2. ✅ **Reducir tiempo de desarrollo** (reutilización)
3. ✅ **Mantener coherencia** visual y UX
4. ✅ **Escalar fácilmente** a nuevos módulos

**Próximo paso**: Implementar Fase 1 (componentes base) y empezar a aplicarlos módulo por módulo.

---

**Autor**: Claude Opus 4.5
**Fecha**: 2026-03-22
**Versión**: 1.0
