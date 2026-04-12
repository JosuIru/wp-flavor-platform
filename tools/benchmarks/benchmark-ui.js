/**
 * VBP Benchmark UI
 *
 * Browser-based UI panel for running benchmarks manually.
 * Injects into VBP Editor for interactive benchmarking.
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

'use strict';

/**
 * Benchmark UI Component
 */
class BenchmarkUI {
    /**
     * Constructor
     *
     * @param {BenchmarkRunner} runner BenchmarkRunner instance
     * @param {Object} options UI options
     */
    constructor(runner, options = {}) {
        this.runner = runner;
        this.options = {
            containerId: 'vbp-benchmark-panel',
            position: 'bottom-right',
            minimized: false,
            theme: 'dark',
            ...options
        };

        this.container = null;
        this.timerInterval = null;
        this.isVisible = false;

        this.init();
    }

    /**
     * Initialize UI
     */
    init() {
        this.injectStyles();
        this.createContainer();
        this.bindKeyboardShortcuts();
    }

    /**
     * Inject CSS styles
     */
    injectStyles() {
        if (document.getElementById('vbp-benchmark-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'vbp-benchmark-styles';
        styles.textContent = `
            .vbp-benchmark-panel {
                position: fixed;
                z-index: 999999;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 13px;
                line-height: 1.4;
                color: #e5e7eb;
                background: #1f2937;
                border-radius: 12px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
                overflow: hidden;
                transition: all 0.3s ease;
                width: 380px;
            }

            .vbp-benchmark-panel.position-bottom-right {
                bottom: 20px;
                right: 20px;
            }

            .vbp-benchmark-panel.position-bottom-left {
                bottom: 20px;
                left: 20px;
            }

            .vbp-benchmark-panel.position-top-right {
                top: 60px;
                right: 20px;
            }

            .vbp-benchmark-panel.minimized {
                width: 200px;
            }

            .vbp-benchmark-panel.minimized .vbp-benchmark-body,
            .vbp-benchmark-panel.minimized .vbp-benchmark-footer {
                display: none;
            }

            .vbp-benchmark-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 12px 16px;
                background: #111827;
                border-bottom: 1px solid #374151;
                cursor: move;
            }

            .vbp-benchmark-title {
                display: flex;
                align-items: center;
                gap: 8px;
                font-weight: 600;
                font-size: 14px;
            }

            .vbp-benchmark-title-icon {
                width: 20px;
                height: 20px;
            }

            .vbp-benchmark-controls {
                display: flex;
                gap: 8px;
            }

            .vbp-benchmark-btn {
                padding: 6px 12px;
                border: none;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }

            .vbp-benchmark-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
            }

            .vbp-benchmark-btn-icon {
                width: 16px;
                height: 16px;
                padding: 4px;
                border-radius: 4px;
                background: transparent;
                border: none;
                cursor: pointer;
                color: #9ca3af;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .vbp-benchmark-btn-icon:hover {
                background: #374151;
                color: #fff;
            }

            .vbp-benchmark-btn-primary {
                background: #3b82f6;
                color: white;
            }

            .vbp-benchmark-btn-primary:hover:not(:disabled) {
                background: #2563eb;
            }

            .vbp-benchmark-btn-success {
                background: #22c55e;
                color: white;
            }

            .vbp-benchmark-btn-success:hover:not(:disabled) {
                background: #16a34a;
            }

            .vbp-benchmark-btn-danger {
                background: #ef4444;
                color: white;
            }

            .vbp-benchmark-btn-danger:hover:not(:disabled) {
                background: #dc2626;
            }

            .vbp-benchmark-btn-secondary {
                background: #374151;
                color: #e5e7eb;
            }

            .vbp-benchmark-btn-secondary:hover:not(:disabled) {
                background: #4b5563;
            }

            .vbp-benchmark-body {
                padding: 16px;
                max-height: 500px;
                overflow-y: auto;
            }

            .vbp-benchmark-section {
                margin-bottom: 16px;
            }

            .vbp-benchmark-section:last-child {
                margin-bottom: 0;
            }

            .vbp-benchmark-section-title {
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: #9ca3af;
                margin-bottom: 8px;
            }

            .vbp-benchmark-select {
                width: 100%;
                padding: 10px 12px;
                background: #374151;
                border: 1px solid #4b5563;
                border-radius: 8px;
                color: #e5e7eb;
                font-size: 13px;
                cursor: pointer;
            }

            .vbp-benchmark-select:focus {
                outline: none;
                border-color: #3b82f6;
            }

            .vbp-benchmark-timer {
                text-align: center;
                padding: 20px;
                background: #111827;
                border-radius: 12px;
            }

            .vbp-benchmark-timer-value {
                font-size: 48px;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                color: #fff;
            }

            .vbp-benchmark-timer-label {
                font-size: 12px;
                color: #6b7280;
                margin-top: 4px;
            }

            .vbp-benchmark-metrics {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 12px;
            }

            .vbp-benchmark-metric {
                text-align: center;
                padding: 12px;
                background: #111827;
                border-radius: 8px;
            }

            .vbp-benchmark-metric-value {
                font-size: 24px;
                font-weight: 700;
                color: #fff;
            }

            .vbp-benchmark-metric-label {
                font-size: 11px;
                color: #6b7280;
                margin-top: 2px;
            }

            .vbp-benchmark-steps {
                max-height: 200px;
                overflow-y: auto;
            }

            .vbp-benchmark-step {
                display: flex;
                align-items: center;
                gap: 10px;
                padding: 8px 12px;
                background: #111827;
                border-radius: 6px;
                margin-bottom: 6px;
            }

            .vbp-benchmark-step:last-child {
                margin-bottom: 0;
            }

            .vbp-benchmark-step-status {
                width: 20px;
                height: 20px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 12px;
                flex-shrink: 0;
            }

            .vbp-benchmark-step-status.pending {
                background: #374151;
                color: #6b7280;
            }

            .vbp-benchmark-step-status.current {
                background: #3b82f6;
                color: white;
                animation: pulse 1.5s infinite;
            }

            .vbp-benchmark-step-status.completed {
                background: #22c55e;
                color: white;
            }

            @keyframes pulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.5; }
            }

            .vbp-benchmark-step-name {
                flex: 1;
                font-size: 12px;
            }

            .vbp-benchmark-step-time {
                font-size: 11px;
                color: #6b7280;
                font-variant-numeric: tabular-nums;
            }

            .vbp-benchmark-progress {
                height: 6px;
                background: #374151;
                border-radius: 3px;
                overflow: hidden;
                margin-top: 12px;
            }

            .vbp-benchmark-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #3b82f6, #22c55e);
                border-radius: 3px;
                transition: width 0.3s ease;
            }

            .vbp-benchmark-results {
                padding: 16px;
            }

            .vbp-benchmark-rating {
                text-align: center;
                padding: 20px;
                background: #111827;
                border-radius: 12px;
                margin-bottom: 16px;
            }

            .vbp-benchmark-rating-emoji {
                font-size: 48px;
            }

            .vbp-benchmark-rating-score {
                font-size: 32px;
                font-weight: 700;
                color: #fff;
                margin: 8px 0;
            }

            .vbp-benchmark-rating-label {
                font-size: 14px;
                font-weight: 600;
            }

            .vbp-benchmark-comparison {
                margin-top: 16px;
            }

            .vbp-benchmark-comparison-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 12px;
                background: #111827;
                border-radius: 6px;
                margin-bottom: 6px;
            }

            .vbp-benchmark-comparison-name {
                font-size: 12px;
            }

            .vbp-benchmark-comparison-result {
                font-size: 12px;
                font-weight: 600;
            }

            .vbp-benchmark-comparison-result.faster {
                color: #22c55e;
            }

            .vbp-benchmark-comparison-result.slower {
                color: #ef4444;
            }

            .vbp-benchmark-footer {
                padding: 12px 16px;
                background: #111827;
                border-top: 1px solid #374151;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .vbp-benchmark-footer-text {
                font-size: 11px;
                color: #6b7280;
            }

            .vbp-benchmark-hidden {
                display: none !important;
            }

            /* Dark/Light theme toggle */
            .vbp-benchmark-panel.theme-light {
                background: #ffffff;
                color: #1f2937;
            }

            .vbp-benchmark-panel.theme-light .vbp-benchmark-header {
                background: #f3f4f6;
                border-bottom-color: #e5e7eb;
            }

            .vbp-benchmark-panel.theme-light .vbp-benchmark-timer,
            .vbp-benchmark-panel.theme-light .vbp-benchmark-metric,
            .vbp-benchmark-panel.theme-light .vbp-benchmark-step,
            .vbp-benchmark-panel.theme-light .vbp-benchmark-rating,
            .vbp-benchmark-panel.theme-light .vbp-benchmark-comparison-item {
                background: #f3f4f6;
            }

            .vbp-benchmark-panel.theme-light .vbp-benchmark-select {
                background: #f3f4f6;
                border-color: #d1d5db;
                color: #1f2937;
            }
        `;

        document.head.appendChild(styles);
    }

    /**
     * Create main container
     */
    createContainer() {
        if (document.getElementById(this.options.containerId)) return;

        this.container = document.createElement('div');
        this.container.id = this.options.containerId;
        this.container.className = `vbp-benchmark-panel position-${this.options.position} theme-${this.options.theme}`;

        if (this.options.minimized) {
            this.container.classList.add('minimized');
        }

        this.container.innerHTML = this.getInitialHTML();

        document.body.appendChild(this.container);

        this.bindEvents();
        this.makeDraggable();
    }

    /**
     * Get initial HTML template
     *
     * @returns {string} HTML content
     */
    getInitialHTML() {
        const benchmarks = this.runner.getAvailableBenchmarks();

        return `
            <div class="vbp-benchmark-header">
                <div class="vbp-benchmark-title">
                    <svg class="vbp-benchmark-title-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                    </svg>
                    VBP Benchmark
                </div>
                <div class="vbp-benchmark-controls">
                    <button class="vbp-benchmark-btn-icon" data-action="minimize" title="Minimizar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14"/>
                        </svg>
                    </button>
                    <button class="vbp-benchmark-btn-icon" data-action="close" title="Cerrar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="vbp-benchmark-body" id="vbp-benchmark-content">
                <!-- Select benchmark -->
                <div class="vbp-benchmark-section" id="vbp-benchmark-selector">
                    <div class="vbp-benchmark-section-title">Seleccionar Benchmark</div>
                    <select class="vbp-benchmark-select" id="vbp-benchmark-select">
                        ${benchmarks.map(benchmarkOption => `
                            <option value="${benchmarkOption.id}">
                                ${benchmarkOption.name} (${benchmarkOption.difficulty})
                            </option>
                        `).join('')}
                    </select>
                    <div style="margin-top: 12px; padding: 12px; background: #111827; border-radius: 8px;">
                        <div id="vbp-benchmark-description" style="font-size: 12px; color: #9ca3af;">
                            ${benchmarks[0]?.description || ''}
                        </div>
                        <div style="margin-top: 8px; font-size: 11px; color: #6b7280;">
                            <span id="vbp-benchmark-step-count">${benchmarks[0]?.stepCount || 0}</span> pasos |
                            Tiempo objetivo: <span id="vbp-benchmark-target-time">${benchmarks[0]?.expectedTime || 0}</span>s
                        </div>
                    </div>
                </div>

                <!-- Experience level -->
                <div class="vbp-benchmark-section" id="vbp-benchmark-experience">
                    <div class="vbp-benchmark-section-title">Nivel de experiencia</div>
                    <select class="vbp-benchmark-select" id="vbp-experience-select">
                        <option value="firstTime">Primera vez usando VBP</option>
                        <option value="beginner">Principiante (menos de 5 paginas)</option>
                        <option value="intermediate" selected>Intermedio (5-20 paginas)</option>
                        <option value="experienced">Experimentado (20-50 paginas)</option>
                        <option value="expert">Experto (mas de 50 paginas)</option>
                    </select>
                </div>

                <!-- Timer (hidden initially) -->
                <div class="vbp-benchmark-section vbp-benchmark-hidden" id="vbp-benchmark-timer-section">
                    <div class="vbp-benchmark-timer">
                        <div class="vbp-benchmark-timer-value" id="vbp-benchmark-timer">00:00</div>
                        <div class="vbp-benchmark-timer-label">Tiempo transcurrido</div>
                    </div>
                </div>

                <!-- Metrics (hidden initially) -->
                <div class="vbp-benchmark-section vbp-benchmark-hidden" id="vbp-benchmark-metrics-section">
                    <div class="vbp-benchmark-metrics">
                        <div class="vbp-benchmark-metric">
                            <div class="vbp-benchmark-metric-value" id="vbp-metric-clicks">0</div>
                            <div class="vbp-benchmark-metric-label">Clicks</div>
                        </div>
                        <div class="vbp-benchmark-metric">
                            <div class="vbp-benchmark-metric-value" id="vbp-metric-keys">0</div>
                            <div class="vbp-benchmark-metric-label">Teclas</div>
                        </div>
                        <div class="vbp-benchmark-metric">
                            <div class="vbp-benchmark-metric-value" id="vbp-metric-errors">0</div>
                            <div class="vbp-benchmark-metric-label">Errores</div>
                        </div>
                    </div>
                    <div class="vbp-benchmark-progress">
                        <div class="vbp-benchmark-progress-bar" id="vbp-progress-bar" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Steps (hidden initially) -->
                <div class="vbp-benchmark-section vbp-benchmark-hidden" id="vbp-benchmark-steps-section">
                    <div class="vbp-benchmark-section-title">Pasos</div>
                    <div class="vbp-benchmark-steps" id="vbp-benchmark-steps"></div>
                </div>

                <!-- Results (hidden initially) -->
                <div class="vbp-benchmark-section vbp-benchmark-hidden" id="vbp-benchmark-results-section">
                    <div class="vbp-benchmark-results" id="vbp-benchmark-results"></div>
                </div>
            </div>

            <div class="vbp-benchmark-footer">
                <span class="vbp-benchmark-footer-text" id="vbp-benchmark-status">Listo para iniciar</span>
                <div>
                    <button class="vbp-benchmark-btn vbp-benchmark-btn-primary" id="vbp-benchmark-start">
                        Iniciar
                    </button>
                    <button class="vbp-benchmark-btn vbp-benchmark-btn-danger vbp-benchmark-hidden" id="vbp-benchmark-stop">
                        Detener
                    </button>
                    <button class="vbp-benchmark-btn vbp-benchmark-btn-success vbp-benchmark-hidden" id="vbp-benchmark-finish">
                        Finalizar
                    </button>
                    <button class="vbp-benchmark-btn vbp-benchmark-btn-secondary vbp-benchmark-hidden" id="vbp-benchmark-reset">
                        Reiniciar
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Bind UI events
     */
    bindEvents() {
        // Close button
        this.container.querySelector('[data-action="close"]').addEventListener('click', () => {
            this.hide();
        });

        // Minimize button
        this.container.querySelector('[data-action="minimize"]').addEventListener('click', () => {
            this.toggleMinimize();
        });

        // Benchmark selector
        const benchmarkSelect = this.container.querySelector('#vbp-benchmark-select');
        benchmarkSelect.addEventListener('change', (event) => {
            this.onBenchmarkSelected(event.target.value);
        });

        // Start button
        this.container.querySelector('#vbp-benchmark-start').addEventListener('click', () => {
            this.startBenchmark();
        });

        // Stop button
        this.container.querySelector('#vbp-benchmark-stop').addEventListener('click', () => {
            this.stopBenchmark();
        });

        // Finish button
        this.container.querySelector('#vbp-benchmark-finish').addEventListener('click', () => {
            this.finishBenchmark();
        });

        // Reset button
        this.container.querySelector('#vbp-benchmark-reset').addEventListener('click', () => {
            this.resetUI();
        });
    }

    /**
     * Make panel draggable
     */
    makeDraggable() {
        const header = this.container.querySelector('.vbp-benchmark-header');
        let isDragging = false;
        let dragStartX = 0;
        let dragStartY = 0;
        let initialX = 0;
        let initialY = 0;

        header.addEventListener('mousedown', (event) => {
            if (event.target.closest('button')) return;

            isDragging = true;
            dragStartX = event.clientX;
            dragStartY = event.clientY;

            const rect = this.container.getBoundingClientRect();
            initialX = rect.left;
            initialY = rect.top;

            document.body.style.userSelect = 'none';
        });

        document.addEventListener('mousemove', (event) => {
            if (!isDragging) return;

            const deltaX = event.clientX - dragStartX;
            const deltaY = event.clientY - dragStartY;

            this.container.style.left = `${initialX + deltaX}px`;
            this.container.style.top = `${initialY + deltaY}px`;
            this.container.style.right = 'auto';
            this.container.style.bottom = 'auto';
        });

        document.addEventListener('mouseup', () => {
            isDragging = false;
            document.body.style.userSelect = '';
        });
    }

    /**
     * Bind keyboard shortcuts
     */
    bindKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            // Alt + B: Toggle benchmark panel
            if (event.altKey && event.key === 'b') {
                event.preventDefault();
                this.toggle();
            }

            // Alt + S: Mark step complete (when running)
            if (event.altKey && event.key === 's' && this.runner.isRunning) {
                event.preventDefault();
                this.markCurrentStepComplete();
            }

            // Alt + F: Finish benchmark (when running)
            if (event.altKey && event.key === 'f' && this.runner.isRunning) {
                event.preventDefault();
                this.finishBenchmark();
            }
        });
    }

    /**
     * Handle benchmark selection
     *
     * @param {string} benchmarkId Selected benchmark ID
     */
    onBenchmarkSelected(benchmarkId) {
        const benchmark = this.runner.getBenchmark(benchmarkId);

        if (benchmark) {
            this.container.querySelector('#vbp-benchmark-description').textContent = benchmark.description;
            this.container.querySelector('#vbp-benchmark-step-count').textContent = benchmark.steps.length;
            this.container.querySelector('#vbp-benchmark-target-time').textContent = benchmark.expectedMetrics.time.target;
        }
    }

    /**
     * Start benchmark
     */
    startBenchmark() {
        const benchmarkId = this.container.querySelector('#vbp-benchmark-select').value;
        const experienceLevel = this.container.querySelector('#vbp-experience-select').value;

        try {
            const runContext = this.runner.start(benchmarkId, { experienceLevel });

            // Update UI
            this.showRunningState(runContext.benchmark);

            // Start timer
            this.startTimer();

            // Update status
            this.updateStatus('Benchmark en curso...');

        } catch (error) {
            this.updateStatus(`Error: ${error.message}`);
        }
    }

    /**
     * Show running state UI
     *
     * @param {Object} benchmark Active benchmark
     */
    showRunningState(benchmark) {
        // Hide selector, show timer and metrics
        this.container.querySelector('#vbp-benchmark-selector').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-experience').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-timer-section').classList.remove('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-metrics-section').classList.remove('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-steps-section').classList.remove('vbp-benchmark-hidden');

        // Populate steps
        const stepsContainer = this.container.querySelector('#vbp-benchmark-steps');
        stepsContainer.innerHTML = benchmark.steps.map((step, index) => `
            <div class="vbp-benchmark-step" data-step-id="${step.id}">
                <div class="vbp-benchmark-step-status ${index === 0 ? 'current' : 'pending'}">
                    ${index + 1}
                </div>
                <div class="vbp-benchmark-step-name">${step.description}</div>
                <div class="vbp-benchmark-step-time" id="step-time-${step.id}">--</div>
            </div>
        `).join('');

        // Add click handlers to steps
        stepsContainer.querySelectorAll('.vbp-benchmark-step').forEach(stepElement => {
            stepElement.addEventListener('click', () => {
                const stepId = stepElement.dataset.stepId;
                this.markStepComplete(stepId);
            });
        });

        // Toggle buttons
        this.container.querySelector('#vbp-benchmark-start').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-stop').classList.remove('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-finish').classList.remove('vbp-benchmark-hidden');
    }

    /**
     * Start timer display
     */
    startTimer() {
        const timerDisplay = this.container.querySelector('#vbp-benchmark-timer');

        this.timerInterval = setInterval(() => {
            const status = this.runner.getStatus();
            if (!status) return;

            // Update timer
            const minutes = Math.floor(status.elapsedTime / 60);
            const seconds = Math.floor(status.elapsedTime % 60);
            timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            // Update metrics
            this.container.querySelector('#vbp-metric-clicks').textContent = status.clicks;
            this.container.querySelector('#vbp-metric-keys').textContent = status.keystrokes;
            this.container.querySelector('#vbp-metric-errors').textContent = status.errors;

            // Update progress bar
            this.container.querySelector('#vbp-progress-bar').style.width = `${status.progress}%`;

        }, 100);
    }

    /**
     * Stop timer display
     */
    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
    }

    /**
     * Mark current step as complete
     */
    markCurrentStepComplete() {
        const status = this.runner.getStatus();
        if (!status) return;

        const benchmark = this.runner.getBenchmark(status.benchmarkId);
        const nextStep = benchmark.steps[status.stepsCompleted];

        if (nextStep) {
            this.markStepComplete(nextStep.id);
        }
    }

    /**
     * Mark specific step as complete
     *
     * @param {string} stepId Step ID
     */
    markStepComplete(stepId) {
        const status = this.runner.getStatus();
        if (!status) return;

        // Record step
        this.runner.stepComplete(stepId);

        // Update UI
        const stepElement = this.container.querySelector(`[data-step-id="${stepId}"]`);
        if (stepElement) {
            const statusIndicator = stepElement.querySelector('.vbp-benchmark-step-status');
            statusIndicator.classList.remove('pending', 'current');
            statusIndicator.classList.add('completed');
            statusIndicator.innerHTML = `
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            `;

            // Show time
            const timeDisplay = stepElement.querySelector('.vbp-benchmark-step-time');
            const elapsedSeconds = (Date.now() - this.runner.currentRun.startTime) / 1000;
            timeDisplay.textContent = `${elapsedSeconds.toFixed(1)}s`;
        }

        // Mark next step as current
        const updatedStatus = this.runner.getStatus();
        const benchmark = this.runner.getBenchmark(updatedStatus.benchmarkId);
        const nextStep = benchmark.steps[updatedStatus.stepsCompleted];

        if (nextStep) {
            const nextStepElement = this.container.querySelector(`[data-step-id="${nextStep.id}"]`);
            if (nextStepElement) {
                const nextStatusIndicator = nextStepElement.querySelector('.vbp-benchmark-step-status');
                nextStatusIndicator.classList.remove('pending');
                nextStatusIndicator.classList.add('current');
            }
        }
    }

    /**
     * Stop benchmark (abort)
     */
    stopBenchmark() {
        const abortedRun = this.runner.abort('User stopped');

        this.stopTimer();
        this.updateStatus('Benchmark cancelado');

        // Show reset button
        this.container.querySelector('#vbp-benchmark-stop').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-finish').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-reset').classList.remove('vbp-benchmark-hidden');
    }

    /**
     * Finish benchmark
     */
    finishBenchmark() {
        const report = this.runner.finish();

        this.stopTimer();
        this.showResults(report);
        this.updateStatus('Benchmark completado');
    }

    /**
     * Show results
     *
     * @param {Object} report Benchmark report
     */
    showResults(report) {
        // Hide running elements
        this.container.querySelector('#vbp-benchmark-timer-section').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-metrics-section').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-steps-section').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-stop').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-finish').classList.add('vbp-benchmark-hidden');

        // Show results
        this.container.querySelector('#vbp-benchmark-results-section').classList.remove('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-reset').classList.remove('vbp-benchmark-hidden');

        const resultsContainer = this.container.querySelector('#vbp-benchmark-results');
        resultsContainer.innerHTML = `
            <div class="vbp-benchmark-rating">
                <div class="vbp-benchmark-rating-emoji">${report.rating.emoji}</div>
                <div class="vbp-benchmark-rating-score">${Math.round(report.rating.score)}</div>
                <div class="vbp-benchmark-rating-label" style="color: ${report.rating.color}">
                    ${report.rating.label}
                </div>
            </div>

            <div class="vbp-benchmark-metrics">
                <div class="vbp-benchmark-metric">
                    <div class="vbp-benchmark-metric-value">${report.vbp.time.toFixed(1)}s</div>
                    <div class="vbp-benchmark-metric-label">Tiempo</div>
                </div>
                <div class="vbp-benchmark-metric">
                    <div class="vbp-benchmark-metric-value">${report.vbp.clicks}</div>
                    <div class="vbp-benchmark-metric-label">Clicks</div>
                </div>
                <div class="vbp-benchmark-metric">
                    <div class="vbp-benchmark-metric-value">${report.vbp.errors}</div>
                    <div class="vbp-benchmark-metric-label">Errores</div>
                </div>
            </div>

            <div class="vbp-benchmark-comparison">
                <div class="vbp-benchmark-section-title">Comparacion vs Competidores</div>
                ${report.comparison
                    .filter(competitorResult => competitorResult.available)
                    .map(competitorResult => `
                        <div class="vbp-benchmark-comparison-item">
                            <span class="vbp-benchmark-comparison-name">${competitorResult.name}</span>
                            <span class="vbp-benchmark-comparison-result ${competitorResult.vbpFaster ? 'faster' : 'slower'}">
                                ${competitorResult.vbpFaster ? '-' : '+'}${Math.abs(competitorResult.timeDifference).toFixed(0)}s
                                (${competitorResult.vbpFaster ? '' : '+'}${competitorResult.timePercentage}%)
                            </span>
                        </div>
                    `).join('')}
            </div>

            ${report.analysis.recommendations.length > 0 ? `
                <div style="margin-top: 16px;">
                    <div class="vbp-benchmark-section-title">Recomendaciones</div>
                    ${report.analysis.recommendations.slice(0, 2).map(recommendation => `
                        <div style="padding: 10px; background: #111827; border-radius: 6px; margin-bottom: 6px;">
                            <div style="font-size: 12px; font-weight: 600; color: #fff;">${recommendation.title}</div>
                            <div style="font-size: 11px; color: #9ca3af; margin-top: 4px;">${recommendation.description}</div>
                        </div>
                    `).join('')}
                </div>
            ` : ''}
        `;
    }

    /**
     * Reset UI to initial state
     */
    resetUI() {
        // Show selector
        this.container.querySelector('#vbp-benchmark-selector').classList.remove('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-experience').classList.remove('vbp-benchmark-hidden');

        // Hide everything else
        this.container.querySelector('#vbp-benchmark-timer-section').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-metrics-section').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-steps-section').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-results-section').classList.add('vbp-benchmark-hidden');

        // Reset buttons
        this.container.querySelector('#vbp-benchmark-start').classList.remove('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-stop').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-finish').classList.add('vbp-benchmark-hidden');
        this.container.querySelector('#vbp-benchmark-reset').classList.add('vbp-benchmark-hidden');

        // Reset timer display
        this.container.querySelector('#vbp-benchmark-timer').textContent = '00:00';
        this.container.querySelector('#vbp-metric-clicks').textContent = '0';
        this.container.querySelector('#vbp-metric-keys').textContent = '0';
        this.container.querySelector('#vbp-metric-errors').textContent = '0';
        this.container.querySelector('#vbp-progress-bar').style.width = '0%';

        this.updateStatus('Listo para iniciar');
    }

    /**
     * Update status text
     *
     * @param {string} text Status message
     */
    updateStatus(text) {
        this.container.querySelector('#vbp-benchmark-status').textContent = text;
    }

    /**
     * Toggle minimize state
     */
    toggleMinimize() {
        this.container.classList.toggle('minimized');
    }

    /**
     * Show panel
     */
    show() {
        if (this.container) {
            this.container.style.display = 'block';
            this.isVisible = true;
        }
    }

    /**
     * Hide panel
     */
    hide() {
        if (this.container) {
            this.container.style.display = 'none';
            this.isVisible = false;
        }
    }

    /**
     * Toggle panel visibility
     */
    toggle() {
        if (this.isVisible) {
            this.hide();
        } else {
            this.show();
        }
    }

    /**
     * Destroy panel
     */
    destroy() {
        this.stopTimer();

        if (this.container) {
            this.container.remove();
            this.container = null;
        }

        const styles = document.getElementById('vbp-benchmark-styles');
        if (styles) {
            styles.remove();
        }
    }
}

/**
 * Export for browser
 */
if (typeof window !== 'undefined') {
    window.VBPBenchmarkUI = BenchmarkUI;
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BenchmarkUI };
}
