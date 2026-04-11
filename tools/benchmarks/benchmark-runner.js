/**
 * VBP Benchmark Runner
 *
 * Core engine for executing and measuring benchmarks.
 * Can run in browser (manual) or Node.js (automated with Playwright).
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

'use strict';

const { BENCHMARKS, COMPETITORS, METRIC_WEIGHTS, RATINGS, LEARNING_CURVE } = require('./benchmark-config');

/**
 * Benchmark Runner Class
 */
class BenchmarkRunner {
    /**
     * Constructor
     *
     * @param {Object} options Configuration options
     */
    constructor(options = {}) {
        this.options = {
            autoTrack: true,
            verbose: false,
            saveResults: true,
            storageKey: 'vbp_benchmark_results',
            ...options
        };

        this.results = [];
        this.currentRun = null;
        this.isRunning = false;

        // Event handlers storage
        this.boundHandlers = {
            click: null,
            keydown: null,
            error: null
        };

        // Callbacks
        this.onStepComplete = options.onStepComplete || (() => {});
        this.onProgress = options.onProgress || (() => {});
        this.onError = options.onError || (() => {});
    }

    /**
     * Get list of available benchmarks
     *
     * @returns {Array} Benchmark list
     */
    getAvailableBenchmarks() {
        return Object.values(BENCHMARKS).map(benchmark => ({
            id: benchmark.id,
            name: benchmark.name,
            description: benchmark.description,
            category: benchmark.category,
            difficulty: benchmark.difficulty,
            stepCount: benchmark.steps.length,
            expectedTime: benchmark.expectedMetrics.time.target
        }));
    }

    /**
     * Get benchmark by ID
     *
     * @param {string} benchmarkId Benchmark identifier
     * @returns {Object|null} Benchmark definition
     */
    getBenchmark(benchmarkId) {
        return BENCHMARKS[benchmarkId] || null;
    }

    /**
     * Start a benchmark run
     *
     * @param {string} benchmarkId Benchmark to run
     * @param {Object} metadata Additional metadata
     * @returns {Object} Run context
     */
    start(benchmarkId, metadata = {}) {
        const benchmark = this.getBenchmark(benchmarkId);

        if (!benchmark) {
            throw new Error(`Benchmark not found: ${benchmarkId}`);
        }

        if (this.isRunning) {
            throw new Error('A benchmark is already running. Call finish() first.');
        }

        this.isRunning = true;

        this.currentRun = {
            id: this.generateRunId(),
            benchmarkId: benchmarkId,
            benchmarkName: benchmark.name,
            startTime: Date.now(),
            endTime: null,
            totalTime: null,

            // Metrics
            clicks: 0,
            keystrokes: 0,
            errors: 0,
            scrolls: 0,

            // Step tracking
            steps: [],
            currentStepIndex: 0,
            totalSteps: benchmark.steps.length,

            // Detailed timing
            timing: {
                firstInteraction: null,
                lastInteraction: null,
                idleTime: 0,
                activeTime: 0
            },

            // Environment
            environment: this.captureEnvironment(),

            // User metadata
            metadata: {
                experienceLevel: metadata.experienceLevel || 'intermediate',
                notes: metadata.notes || '',
                ...metadata
            },

            // Status
            status: 'running',
            aborted: false
        };

        // Setup event tracking
        if (this.options.autoTrack && typeof document !== 'undefined') {
            this.setupEventTracking();
        }

        this.log(`Started benchmark: ${benchmark.name}`);

        return {
            runId: this.currentRun.id,
            benchmark: benchmark,
            startTime: this.currentRun.startTime
        };
    }

    /**
     * Record step completion
     *
     * @param {string} stepId Step identifier
     * @param {Object} stepData Additional step data
     */
    stepComplete(stepId, stepData = {}) {
        if (!this.currentRun) {
            throw new Error('No benchmark running. Call start() first.');
        }

        const benchmark = this.getBenchmark(this.currentRun.benchmarkId);
        const stepDefinition = benchmark.steps.find(stepDefinitionItem => stepDefinitionItem.id === stepId);

        const stepRecord = {
            id: stepId,
            name: stepDefinition?.description || stepId,
            timestamp: Date.now(),
            elapsedTime: Date.now() - this.currentRun.startTime,
            clicksAtStep: this.currentRun.clicks,
            keystrokesAtStep: this.currentRun.keystrokes,
            errorsAtStep: this.currentRun.errors,
            ...stepData
        };

        this.currentRun.steps.push(stepRecord);
        this.currentRun.currentStepIndex++;

        // Calculate progress
        const progressPercentage = (this.currentRun.currentStepIndex / this.currentRun.totalSteps) * 100;

        this.onStepComplete(stepRecord, progressPercentage);
        this.onProgress(progressPercentage, stepRecord);

        this.log(`Step completed: ${stepId} (${progressPercentage.toFixed(1)}%)`);
    }

    /**
     * Record an error during benchmark
     *
     * @param {string} errorMessage Error description
     * @param {Object} errorData Additional error data
     */
    recordError(errorMessage, errorData = {}) {
        if (!this.currentRun) return;

        this.currentRun.errors++;

        const errorRecord = {
            timestamp: Date.now(),
            message: errorMessage,
            stepIndex: this.currentRun.currentStepIndex,
            ...errorData
        };

        if (!this.currentRun.errorLog) {
            this.currentRun.errorLog = [];
        }

        this.currentRun.errorLog.push(errorRecord);
        this.onError(errorRecord);

        this.log(`Error recorded: ${errorMessage}`, 'error');
    }

    /**
     * Finish the current benchmark run
     *
     * @param {Object} finalData Final run data
     * @returns {Object} Complete run report
     */
    finish(finalData = {}) {
        if (!this.currentRun) {
            throw new Error('No benchmark running.');
        }

        // Calculate final timing
        this.currentRun.endTime = Date.now();
        this.currentRun.totalTime = (this.currentRun.endTime - this.currentRun.startTime) / 1000;
        this.currentRun.status = 'completed';

        // Calculate active time
        if (this.currentRun.timing.firstInteraction && this.currentRun.timing.lastInteraction) {
            this.currentRun.timing.activeTime =
                (this.currentRun.timing.lastInteraction - this.currentRun.timing.firstInteraction) / 1000;
        }

        // Apply any final data
        Object.assign(this.currentRun, finalData);

        // Generate report
        const report = this.generateReport();

        // Store result
        this.results.push(this.currentRun);

        if (this.options.saveResults) {
            this.saveResults();
        }

        // Cleanup
        this.teardownEventTracking();
        const completedRun = this.currentRun;
        this.currentRun = null;
        this.isRunning = false;

        this.log(`Benchmark completed: ${completedRun.totalTime.toFixed(2)}s`);

        return report;
    }

    /**
     * Abort the current benchmark
     *
     * @param {string} reason Abort reason
     * @returns {Object} Partial results
     */
    abort(reason = 'User cancelled') {
        if (!this.currentRun) return null;

        this.currentRun.endTime = Date.now();
        this.currentRun.totalTime = (this.currentRun.endTime - this.currentRun.startTime) / 1000;
        this.currentRun.status = 'aborted';
        this.currentRun.aborted = true;
        this.currentRun.abortReason = reason;

        const abortedRun = this.currentRun;

        this.teardownEventTracking();
        this.currentRun = null;
        this.isRunning = false;

        this.log(`Benchmark aborted: ${reason}`, 'warn');

        return abortedRun;
    }

    /**
     * Generate comprehensive report
     *
     * @returns {Object} Complete benchmark report
     */
    generateReport() {
        if (!this.currentRun) {
            throw new Error('No benchmark data available.');
        }

        const benchmark = this.getBenchmark(this.currentRun.benchmarkId);
        const expectedMetrics = benchmark.expectedMetrics;

        // Calculate individual scores
        const timeScore = this.calculateMetricScore(
            this.currentRun.totalTime,
            expectedMetrics.time,
            'lower'
        );

        const clickScore = this.calculateMetricScore(
            this.currentRun.clicks,
            expectedMetrics.clicks,
            'lower'
        );

        const keystrokeScore = this.calculateMetricScore(
            this.currentRun.keystrokes,
            expectedMetrics.keystrokes,
            'lower'
        );

        const errorScore = this.calculateMetricScore(
            this.currentRun.errors,
            expectedMetrics.errors,
            'lower'
        );

        // Calculate weighted overall score
        const overallScore =
            (timeScore * METRIC_WEIGHTS.time) +
            (clickScore * METRIC_WEIGHTS.clicks) +
            (keystrokeScore * METRIC_WEIGHTS.keystrokes) +
            (errorScore * METRIC_WEIGHTS.errors);

        // Get rating
        const rating = this.getRating(overallScore);

        // Build competitor comparison
        const competitorComparison = this.buildCompetitorComparison();

        // Build step analysis
        const stepAnalysis = this.analyzeSteps();

        return {
            // Run identification
            runId: this.currentRun.id,
            benchmarkId: this.currentRun.benchmarkId,
            benchmarkName: this.currentRun.benchmarkName,
            timestamp: new Date(this.currentRun.startTime).toISOString(),

            // VBP Results
            vbp: {
                time: this.currentRun.totalTime,
                clicks: this.currentRun.clicks,
                keystrokes: this.currentRun.keystrokes,
                errors: this.currentRun.errors,
                stepsCompleted: this.currentRun.steps.length,
                totalSteps: this.currentRun.totalSteps
            },

            // Scores
            scores: {
                time: timeScore,
                clicks: clickScore,
                keystrokes: keystrokeScore,
                errors: errorScore,
                overall: overallScore
            },

            // Rating
            rating: {
                score: overallScore,
                label: rating.label,
                color: rating.color,
                emoji: rating.emoji
            },

            // Competitor comparison
            comparison: competitorComparison,

            // Analysis
            analysis: {
                steps: stepAnalysis,
                efficiency: this.calculateEfficiency(),
                recommendations: this.generateRecommendations(overallScore, stepAnalysis)
            },

            // Metadata
            environment: this.currentRun.environment,
            metadata: this.currentRun.metadata
        };
    }

    /**
     * Calculate score for a single metric
     *
     * @param {number} actual Actual value
     * @param {Object} expected Expected values {min, target, max}
     * @param {string} direction 'lower' or 'higher' is better
     * @returns {number} Score 0-100
     */
    calculateMetricScore(actual, expected, direction = 'lower') {
        if (direction === 'lower') {
            if (actual <= expected.min) return 100;
            if (actual <= expected.target) {
                return 100 - ((actual - expected.min) / (expected.target - expected.min)) * 25;
            }
            if (actual <= expected.max) {
                return 75 - ((actual - expected.target) / (expected.max - expected.target)) * 35;
            }
            // Beyond max
            const overageRatio = (actual - expected.max) / expected.max;
            return Math.max(0, 40 - (overageRatio * 40));
        } else {
            // Higher is better (not common for these metrics)
            if (actual >= expected.max) return 100;
            if (actual >= expected.target) {
                return 75 + ((actual - expected.target) / (expected.max - expected.target)) * 25;
            }
            if (actual >= expected.min) {
                return 40 + ((actual - expected.min) / (expected.target - expected.min)) * 35;
            }
            return Math.max(0, (actual / expected.min) * 40);
        }
    }

    /**
     * Get rating based on score
     *
     * @param {number} score Overall score
     * @returns {Object} Rating object
     */
    getRating(score) {
        if (score >= RATINGS.excellent.min) return RATINGS.excellent;
        if (score >= RATINGS.good.min) return RATINGS.good;
        if (score >= RATINGS.acceptable.min) return RATINGS.acceptable;
        if (score >= RATINGS.needsWork.min) return RATINGS.needsWork;
        return RATINGS.poor;
    }

    /**
     * Build competitor comparison data
     *
     * @returns {Array} Comparison data
     */
    buildCompetitorComparison() {
        const currentBenchmarkId = this.currentRun.benchmarkId;

        return Object.entries(COMPETITORS).map(([competitorId, competitor]) => {
            const competitorBenchmark = competitor.benchmarks[currentBenchmarkId];

            if (!competitorBenchmark) {
                return {
                    id: competitorId,
                    name: competitor.name,
                    available: false
                };
            }

            const timeDifference = competitorBenchmark.avgTime - this.currentRun.totalTime;
            const clickDifference = competitorBenchmark.avgClicks - this.currentRun.clicks;

            return {
                id: competitorId,
                name: competitor.name,
                category: competitor.category,
                available: true,

                // Their metrics
                theirTime: competitorBenchmark.avgTime,
                theirClicks: competitorBenchmark.avgClicks,
                theirKeystrokes: competitorBenchmark.avgKeystrokes,

                // Differences (positive = VBP is better)
                timeDifference: timeDifference,
                clickDifference: clickDifference,
                timePercentage: ((timeDifference / competitorBenchmark.avgTime) * 100).toFixed(1),

                // VBP wins?
                vbpFaster: timeDifference > 0,
                vbpFewerClicks: clickDifference > 0,

                // Summary
                summary: timeDifference > 0
                    ? `VBP es ${Math.abs(timeDifference).toFixed(0)}s más rápido`
                    : `${competitor.name} es ${Math.abs(timeDifference).toFixed(0)}s más rápido`
            };
        });
    }

    /**
     * Analyze step completion data
     *
     * @returns {Object} Step analysis
     */
    analyzeSteps() {
        const steps = this.currentRun.steps;

        if (steps.length < 2) {
            return { detailed: steps, bottlenecks: [], fastestSteps: [] };
        }

        // Calculate time between steps
        const stepTimings = steps.map((currentStep, index) => {
            const previousTime = index === 0 ? this.currentRun.startTime : steps[index - 1].timestamp;
            const stepDuration = (currentStep.timestamp - previousTime) / 1000;

            return {
                ...currentStep,
                duration: stepDuration,
                index: index
            };
        });

        // Sort by duration to find bottlenecks
        const sortedByDuration = [...stepTimings].sort((stepA, stepB) => stepB.duration - stepA.duration);

        return {
            detailed: stepTimings,
            bottlenecks: sortedByDuration.slice(0, 3).map(stepTiming => ({
                step: stepTiming.name,
                duration: stepTiming.duration,
                suggestion: this.getSuggestionForStep(stepTiming)
            })),
            fastestSteps: sortedByDuration.slice(-3).reverse().map(stepTiming => ({
                step: stepTiming.name,
                duration: stepTiming.duration
            })),
            averageStepTime: stepTimings.reduce((sum, stepTiming) => sum + stepTiming.duration, 0) / stepTimings.length
        };
    }

    /**
     * Get improvement suggestion for a slow step
     *
     * @param {Object} stepTiming Step with timing data
     * @returns {string} Suggestion
     */
    getSuggestionForStep(stepTiming) {
        const suggestions = {
            'add-section': 'Usa atajos de teclado para añadir secciones más rápido',
            'edit-text': 'Doble clic para edición inline directa',
            'add-button': 'Arrastra desde la biblioteca de bloques',
            'configure': 'Considera crear presets para configuraciones comunes',
            'save': 'Activa autoguardado para reducir guardados manuales',
            'default': 'Practica este paso para mejorar velocidad'
        };

        for (const [keyword, suggestion] of Object.entries(suggestions)) {
            if (stepTiming.id.includes(keyword) || stepTiming.name.toLowerCase().includes(keyword)) {
                return suggestion;
            }
        }

        return suggestions.default;
    }

    /**
     * Calculate efficiency metrics
     *
     * @returns {Object} Efficiency data
     */
    calculateEfficiency() {
        const run = this.currentRun;
        const benchmark = this.getBenchmark(run.benchmarkId);

        return {
            // Clicks per step
            clicksPerStep: (run.clicks / run.steps.length).toFixed(2),
            targetClicksPerStep: (benchmark.expectedMetrics.clicks.target / benchmark.steps.length).toFixed(2),

            // Time per step
            timePerStep: (run.totalTime / run.steps.length).toFixed(2),
            targetTimePerStep: (benchmark.expectedMetrics.time.target / benchmark.steps.length).toFixed(2),

            // Error rate
            errorRate: ((run.errors / run.steps.length) * 100).toFixed(2) + '%',

            // Learning curve adjustment
            experienceLevel: run.metadata.experienceLevel,
            learningMultiplier: LEARNING_CURVE[run.metadata.experienceLevel] || 1,
            adjustedTime: run.totalTime * (LEARNING_CURVE[run.metadata.experienceLevel] || 1)
        };
    }

    /**
     * Generate improvement recommendations
     *
     * @param {number} overallScore Overall benchmark score
     * @param {Object} stepAnalysis Step analysis data
     * @returns {Array} Recommendations
     */
    generateRecommendations(overallScore, stepAnalysis) {
        const recommendations = [];

        // Based on overall score
        if (overallScore < 60) {
            recommendations.push({
                priority: 'high',
                category: 'general',
                title: 'Práctica necesaria',
                description: 'Considera revisar los tutoriales de VBP para familiarizarte con el flujo de trabajo'
            });
        }

        // Based on bottlenecks
        if (stepAnalysis.bottlenecks && stepAnalysis.bottlenecks.length > 0) {
            const slowestStep = stepAnalysis.bottlenecks[0];
            if (slowestStep.duration > 30) {
                recommendations.push({
                    priority: 'medium',
                    category: 'performance',
                    title: `Optimizar: ${slowestStep.step}`,
                    description: slowestStep.suggestion
                });
            }
        }

        // Based on clicks
        const run = this.currentRun;
        const benchmark = this.getBenchmark(run.benchmarkId);
        if (run.clicks > benchmark.expectedMetrics.clicks.max) {
            recommendations.push({
                priority: 'medium',
                category: 'efficiency',
                title: 'Reducir clicks',
                description: 'Usa más atajos de teclado y drag & drop para reducir navegación por menús'
            });
        }

        // Based on errors
        if (run.errors > benchmark.expectedMetrics.errors.target) {
            recommendations.push({
                priority: 'high',
                category: 'accuracy',
                title: 'Reducir errores',
                description: 'Revisa la documentación de los elementos que más errores producen'
            });
        }

        // Experience-based
        if (run.metadata.experienceLevel === 'firstTime' || run.metadata.experienceLevel === 'beginner') {
            recommendations.push({
                priority: 'low',
                category: 'learning',
                title: 'Continúa practicando',
                description: 'Los tiempos mejorarán significativamente con la práctica. Tu objetivo: reducir 40% en 5 sesiones'
            });
        }

        return recommendations;
    }

    /**
     * Setup DOM event tracking
     */
    setupEventTracking() {
        if (typeof document === 'undefined') return;

        // Click tracking
        this.boundHandlers.click = (event) => {
            if (!this.currentRun) return;

            this.currentRun.clicks++;

            if (!this.currentRun.timing.firstInteraction) {
                this.currentRun.timing.firstInteraction = Date.now();
            }
            this.currentRun.timing.lastInteraction = Date.now();
        };

        // Keystroke tracking
        this.boundHandlers.keydown = (event) => {
            if (!this.currentRun) return;

            // Don't count modifier keys alone
            if (['Shift', 'Control', 'Alt', 'Meta'].includes(event.key)) return;

            this.currentRun.keystrokes++;

            if (!this.currentRun.timing.firstInteraction) {
                this.currentRun.timing.firstInteraction = Date.now();
            }
            this.currentRun.timing.lastInteraction = Date.now();
        };

        // Error tracking
        this.boundHandlers.error = (event) => {
            if (!this.currentRun) return;
            this.recordError(event.message || 'Unknown error', { type: 'window-error' });
        };

        // Attach listeners
        document.addEventListener('click', this.boundHandlers.click, true);
        document.addEventListener('keydown', this.boundHandlers.keydown, true);
        window.addEventListener('error', this.boundHandlers.error);
    }

    /**
     * Remove DOM event tracking
     */
    teardownEventTracking() {
        if (typeof document === 'undefined') return;

        if (this.boundHandlers.click) {
            document.removeEventListener('click', this.boundHandlers.click, true);
        }
        if (this.boundHandlers.keydown) {
            document.removeEventListener('keydown', this.boundHandlers.keydown, true);
        }
        if (this.boundHandlers.error) {
            window.removeEventListener('error', this.boundHandlers.error);
        }
    }

    /**
     * Capture environment information
     *
     * @returns {Object} Environment data
     */
    captureEnvironment() {
        const environment = {
            timestamp: new Date().toISOString(),
            platform: 'unknown',
            browser: 'unknown',
            screenSize: 'unknown',
            vbpVersion: 'unknown'
        };

        if (typeof navigator !== 'undefined') {
            environment.platform = navigator.platform || 'unknown';
            environment.browser = navigator.userAgent || 'unknown';
        }

        if (typeof window !== 'undefined') {
            environment.screenSize = `${window.innerWidth}x${window.innerHeight}`;

            // Try to get VBP version
            if (window.VBP_VERSION) {
                environment.vbpVersion = window.VBP_VERSION;
            } else if (window.flavorVBP && window.flavorVBP.version) {
                environment.vbpVersion = window.flavorVBP.version;
            }
        }

        if (typeof process !== 'undefined') {
            environment.platform = process.platform || environment.platform;
            environment.nodeVersion = process.version;
        }

        return environment;
    }

    /**
     * Generate unique run ID
     *
     * @returns {string} Unique ID
     */
    generateRunId() {
        const timestamp = Date.now().toString(36);
        const randomPart = Math.random().toString(36).substring(2, 8);
        return `vbp-bench-${timestamp}-${randomPart}`;
    }

    /**
     * Save results to storage
     */
    saveResults() {
        if (typeof localStorage === 'undefined') return;

        try {
            const existingResults = this.loadResults();
            existingResults.push(this.currentRun);

            // Keep last 100 results
            const trimmedResults = existingResults.slice(-100);

            localStorage.setItem(this.options.storageKey, JSON.stringify(trimmedResults));
        } catch (error) {
            this.log(`Failed to save results: ${error.message}`, 'error');
        }
    }

    /**
     * Load results from storage
     *
     * @returns {Array} Stored results
     */
    loadResults() {
        if (typeof localStorage === 'undefined') return [];

        try {
            const stored = localStorage.getItem(this.options.storageKey);
            return stored ? JSON.parse(stored) : [];
        } catch {
            return [];
        }
    }

    /**
     * Get historical results for a benchmark
     *
     * @param {string} benchmarkId Benchmark ID
     * @returns {Array} Historical results
     */
    getHistory(benchmarkId) {
        const allResults = this.loadResults();

        return allResults
            .filter(result => result.benchmarkId === benchmarkId && result.status === 'completed')
            .map(result => ({
                runId: result.id,
                timestamp: new Date(result.startTime).toISOString(),
                time: result.totalTime,
                clicks: result.clicks,
                errors: result.errors
            }));
    }

    /**
     * Get personal best for a benchmark
     *
     * @param {string} benchmarkId Benchmark ID
     * @returns {Object|null} Best result
     */
    getPersonalBest(benchmarkId) {
        const history = this.getHistory(benchmarkId);

        if (history.length === 0) return null;

        return history.reduce((best, current) => {
            return current.time < best.time ? current : best;
        });
    }

    /**
     * Clear stored results
     */
    clearHistory() {
        if (typeof localStorage !== 'undefined') {
            localStorage.removeItem(this.options.storageKey);
        }
        this.results = [];
    }

    /**
     * Log message (if verbose enabled)
     *
     * @param {string} message Message to log
     * @param {string} level Log level
     */
    log(message, level = 'info') {
        if (!this.options.verbose) return;

        const prefix = '[VBP Benchmark]';
        const timestamp = new Date().toISOString().split('T')[1].split('.')[0];

        switch (level) {
            case 'error':
                console.error(`${prefix} ${timestamp} ERROR: ${message}`);
                break;
            case 'warn':
                console.warn(`${prefix} ${timestamp} WARN: ${message}`);
                break;
            default:
                console.log(`${prefix} ${timestamp} ${message}`);
        }
    }

    /**
     * Export results to JSON
     *
     * @param {string} benchmarkId Optional filter by benchmark
     * @returns {string} JSON string
     */
    exportToJSON(benchmarkId = null) {
        let results = this.loadResults();

        if (benchmarkId) {
            results = results.filter(result => result.benchmarkId === benchmarkId);
        }

        return JSON.stringify({
            exportDate: new Date().toISOString(),
            version: '1.0',
            results: results
        }, null, 2);
    }

    /**
     * Get current run status
     *
     * @returns {Object|null} Current run info
     */
    getStatus() {
        if (!this.currentRun) return null;

        return {
            isRunning: this.isRunning,
            benchmarkId: this.currentRun.benchmarkId,
            benchmarkName: this.currentRun.benchmarkName,
            elapsedTime: (Date.now() - this.currentRun.startTime) / 1000,
            clicks: this.currentRun.clicks,
            keystrokes: this.currentRun.keystrokes,
            errors: this.currentRun.errors,
            stepsCompleted: this.currentRun.steps.length,
            totalSteps: this.currentRun.totalSteps,
            progress: (this.currentRun.steps.length / this.currentRun.totalSteps) * 100
        };
    }
}

/**
 * Export for both Node.js and browser
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BenchmarkRunner };
}

if (typeof window !== 'undefined') {
    window.VBPBenchmarkRunner = BenchmarkRunner;
}
