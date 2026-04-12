/**
 * VBP Stress Test Runner
 * Ejecuta todas las suites de stress tests
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

// Importar suites de tests
let stressTests, pluginCompatibility, dataConsistency, errorRecovery, limitsTests;

// Soporte para Node.js y navegador
if (typeof require !== 'undefined') {
    stressTests = require('./stress-tests.js');
    pluginCompatibility = require('./plugin-compatibility.js');
    dataConsistency = require('./data-consistency.js');
    errorRecovery = require('./error-recovery.js');
    limitsTests = require('./limits-test.js');
} else if (typeof window !== 'undefined') {
    stressTests = window.VBPStressTests;
    pluginCompatibility = window.VBPPluginCompatibility;
    dataConsistency = window.VBPDataConsistency;
    errorRecovery = window.VBPErrorRecovery;
    limitsTests = window.VBPLimitsTests;
}

/**
 * Configuracion de la suite de tests
 */
const CONFIG = {
    // Categorias de tests disponibles
    categories: {
        stress: {
            name: 'Stress Tests',
            description: 'Pruebas de rendimiento bajo carga',
            enabled: true
        },
        compatibility: {
            name: 'Plugin Compatibility',
            description: 'Compatibilidad con plugins populares',
            enabled: true
        },
        consistency: {
            name: 'Data Consistency',
            description: 'Integridad y consistencia de datos',
            enabled: true
        },
        recovery: {
            name: 'Error Recovery',
            description: 'Recuperacion de errores',
            enabled: true
        },
        limits: {
            name: 'System Limits',
            description: 'Limites del sistema',
            enabled: true
        }
    },

    // Opciones globales
    options: {
        verbose: false,
        stopOnFailure: false,
        quickMode: false,
        generateReport: true,
        reportFormat: 'markdown'
    }
};

/**
 * Clase principal del runner
 */
class VBPStressTestSuite {
    constructor(options = {}) {
        this.options = { ...CONFIG.options, ...options };
        this.results = {};
        this.startTime = null;
        this.endTime = null;
    }

    /**
     * Ejecutar todas las suites de tests
     */
    async runAll() {
        this.startTime = Date.now();

        console.log('='.repeat(60));
        console.log('VBP COMPREHENSIVE STRESS TEST SUITE');
        console.log('='.repeat(60));
        console.log(`Started: ${new Date().toISOString()}`);
        console.log(`Mode: ${this.options.quickMode ? 'Quick' : 'Full'}`);
        console.log('='.repeat(60) + '\n');

        const results = {
            stress: null,
            compatibility: null,
            consistency: null,
            recovery: null,
            limits: null
        };

        // 1. Stress Tests
        if (CONFIG.categories.stress.enabled && stressTests) {
            console.log('\n' + '-'.repeat(40));
            console.log('1. STRESS TESTS');
            console.log('-'.repeat(40));

            const runner = new stressTests.StressTestRunner({
                verbose: this.options.verbose,
                stopOnFailure: this.options.stopOnFailure
            });
            results.stress = await runner.runAll();
        }

        // 2. Plugin Compatibility
        if (CONFIG.categories.compatibility.enabled && pluginCompatibility) {
            console.log('\n' + '-'.repeat(40));
            console.log('2. PLUGIN COMPATIBILITY TESTS');
            console.log('-'.repeat(40));

            const runner = new pluginCompatibility.PluginCompatibilityTest({
                verbose: this.options.verbose
            });

            // En modo rapido, solo testear algunos plugins
            if (this.options.quickMode) {
                results.compatibility = await runner.runCategory('seo');
            } else {
                results.compatibility = await runner.runAll();
            }
        }

        // 3. Data Consistency
        if (CONFIG.categories.consistency.enabled && dataConsistency) {
            console.log('\n' + '-'.repeat(40));
            console.log('3. DATA CONSISTENCY TESTS');
            console.log('-'.repeat(40));

            const runner = new dataConsistency.DataConsistencyTestRunner({
                verbose: this.options.verbose,
                stopOnFailure: this.options.stopOnFailure
            });
            results.consistency = await runner.runAll();
        }

        // 4. Error Recovery
        if (CONFIG.categories.recovery.enabled && errorRecovery) {
            console.log('\n' + '-'.repeat(40));
            console.log('4. ERROR RECOVERY TESTS');
            console.log('-'.repeat(40));

            const runner = new errorRecovery.ErrorRecoveryTestRunner({
                verbose: this.options.verbose,
                stopOnFailure: this.options.stopOnFailure
            });
            results.recovery = await runner.runAll();
        }

        // 5. System Limits
        if (CONFIG.categories.limits.enabled && limitsTests) {
            console.log('\n' + '-'.repeat(40));
            console.log('5. SYSTEM LIMITS TESTS');
            console.log('-'.repeat(40));

            const runner = new limitsTests.LimitsTestRunner({
                verbose: this.options.verbose,
                quickMode: this.options.quickMode
            });
            results.limits = await runner.findAllLimits();
        }

        this.endTime = Date.now();
        this.results = results;

        // Generar resumen
        const summary = this.generateSummary(results);

        // Generar reporte si esta habilitado
        if (this.options.generateReport) {
            const report = this.generateReport(results, summary);
            console.log('\n' + report);
        }

        return { results, summary };
    }

    /**
     * Ejecutar una categoria especifica
     * @param {string} categoryName - Nombre de la categoria
     */
    async runCategory(categoryName) {
        const category = CONFIG.categories[categoryName];
        if (!category) {
            throw new Error(`Unknown category: ${categoryName}`);
        }

        console.log(`Running ${category.name}...`);

        switch (categoryName) {
            case 'stress':
                const stressRunner = new stressTests.StressTestRunner(this.options);
                return await stressRunner.runAll();

            case 'compatibility':
                const compatRunner = new pluginCompatibility.PluginCompatibilityTest(this.options);
                return await compatRunner.runAll();

            case 'consistency':
                const consistencyRunner = new dataConsistency.DataConsistencyTestRunner(this.options);
                return await consistencyRunner.runAll();

            case 'recovery':
                const recoveryRunner = new errorRecovery.ErrorRecoveryTestRunner(this.options);
                return await recoveryRunner.runAll();

            case 'limits':
                const limitsRunner = new limitsTests.LimitsTestRunner(this.options);
                return await limitsRunner.findAllLimits();

            default:
                throw new Error(`No runner for category: ${categoryName}`);
        }
    }

    /**
     * Generar resumen de resultados
     */
    generateSummary(results) {
        const summary = {
            totalTests: 0,
            passed: 0,
            failed: 0,
            duration: this.endTime - this.startTime,
            categories: {}
        };

        for (const [category, result] of Object.entries(results)) {
            if (result) {
                const categoryTotal = result.total || 0;
                const categoryPassed = result.passed || 0;

                summary.totalTests += categoryTotal;
                summary.passed += categoryPassed;
                summary.failed += (categoryTotal - categoryPassed);

                summary.categories[category] = {
                    total: categoryTotal,
                    passed: categoryPassed,
                    failed: categoryTotal - categoryPassed,
                    successRate: categoryTotal > 0
                        ? Math.round((categoryPassed / categoryTotal) * 100)
                        : 0
                };
            }
        }

        summary.successRate = summary.totalTests > 0
            ? Math.round((summary.passed / summary.totalTests) * 100)
            : 0;

        return summary;
    }

    /**
     * Generar reporte en formato Markdown
     */
    generateReport(results, summary) {
        const lines = [];

        lines.push('# VBP Reliability Report');
        lines.push('');
        lines.push(`Generated: ${new Date().toISOString()}`);
        lines.push(`Duration: ${Math.round(summary.duration / 1000)}s`);
        lines.push('');

        // Resumen general
        lines.push('## Summary');
        lines.push('');
        lines.push(`| Metric | Value |`);
        lines.push(`|--------|-------|`);
        lines.push(`| Total Tests | ${summary.totalTests} |`);
        lines.push(`| Passed | ${summary.passed} |`);
        lines.push(`| Failed | ${summary.failed} |`);
        lines.push(`| Success Rate | ${summary.successRate}% |`);
        lines.push('');

        // Por categoria
        lines.push('## Results by Category');
        lines.push('');
        lines.push(`| Category | Passed | Total | Rate |`);
        lines.push(`|----------|--------|-------|------|`);

        for (const [category, data] of Object.entries(summary.categories)) {
            const status = data.failed === 0 ? '  ' : '  ';
            lines.push(`| ${status} ${CONFIG.categories[category]?.name || category} | ${data.passed} | ${data.total} | ${data.successRate}% |`);
        }
        lines.push('');

        // Stress Tests detalle
        if (results.stress) {
            lines.push('## Stress Tests');
            lines.push('');
            lines.push(`| Test | Status | Metrics |`);
            lines.push(`|------|--------|---------|`);

            if (results.stress.results) {
                results.stress.results.forEach(test => {
                    const status = test.passed ? '  Pass' : '  Fail';
                    const metricsStr = test.metrics
                        ? Object.entries(test.metrics).slice(0, 3).map(([k, v]) => `${k}: ${v}`).join(', ')
                        : '-';
                    lines.push(`| ${test.testName || test.testId} | ${status} | ${metricsStr} |`);
                });
            }
            lines.push('');
        }

        // Plugin Compatibility
        if (results.compatibility && results.compatibility.details) {
            lines.push('## Plugin Compatibility');
            lines.push('');
            lines.push(`| Plugin | Compatible | Notes |`);
            lines.push(`|--------|------------|-------|`);

            const plugins = Object.values(results.compatibility.details).slice(0, 15);
            plugins.forEach(plugin => {
                const compatible = plugin.summary && plugin.summary.compatible ? '  ' : '  ';
                const percentage = plugin.summary ? `${plugin.summary.percentage}%` : 'N/A';
                lines.push(`| ${plugin.pluginName || plugin.pluginSlug} | ${compatible} ${percentage} | ${plugin.category || '-'} |`);
            });
            lines.push('');
        }

        // System Limits
        if (results.limits && results.limits.limits) {
            lines.push('## System Limits');
            lines.push('');
            lines.push(`| Metric | Recommended Limit |`);
            lines.push(`|--------|-------------------|`);

            for (const [limitId, limitData] of Object.entries(results.limits.limits)) {
                if (limitData.recommendation) {
                    lines.push(`| ${limitId.replace(/-/g, ' ')} | ${limitData.recommendation.replace('Limite recomendado: ', '')} |`);
                }
            }
            lines.push('');
        }

        // Notas
        lines.push('## Notes');
        lines.push('');
        lines.push('- Tests were run in ' + (this.options.quickMode ? 'quick' : 'full') + ' mode');
        lines.push('- Plugin compatibility tests use mocked contexts');
        lines.push('- Actual limits may vary based on server resources');
        lines.push('');

        // Footer
        lines.push('---');
        lines.push('*Report generated by VBP Stress Test Suite*');

        return lines.join('\n');
    }

    /**
     * Ejecutar un test especifico
     * @param {string} categoryName - Categoria del test
     * @param {string} testId - ID del test
     */
    async runSingleTest(categoryName, testId) {
        switch (categoryName) {
            case 'stress':
                const stressRunner = new stressTests.StressTestRunner(this.options);
                return await stressRunner.runTest(testId);

            case 'consistency':
                const consistencyRunner = new dataConsistency.DataConsistencyTestRunner(this.options);
                return await consistencyRunner.runTest(testId);

            case 'recovery':
                const recoveryRunner = new errorRecovery.ErrorRecoveryTestRunner(this.options);
                return await recoveryRunner.runTest(testId);

            case 'limits':
                const limitsRunner = new limitsTests.LimitsTestRunner(this.options);
                return await limitsRunner.runTest(testId);

            default:
                throw new Error(`Unknown category: ${categoryName}`);
        }
    }
}

/**
 * Funcion principal para ejecutar todos los tests
 */
async function runAllStressTests(options = {}) {
    const suite = new VBPStressTestSuite(options);
    return await suite.runAll();
}

/**
 * CLI helper
 */
function parseCliArgs(args) {
    const options = {
        verbose: false,
        quickMode: false,
        category: null,
        test: null
    };

    for (let i = 0; i < args.length; i++) {
        const arg = args[i];

        if (arg === '-v' || arg === '--verbose') {
            options.verbose = true;
        } else if (arg === '-q' || arg === '--quick') {
            options.quickMode = true;
        } else if (arg === '-c' || arg === '--category') {
            options.category = args[++i];
        } else if (arg === '-t' || arg === '--test') {
            options.test = args[++i];
        } else if (arg === '-h' || arg === '--help') {
            console.log(`
VBP Stress Test Suite

Usage: node run-stress-tests.js [options]

Options:
  -v, --verbose     Show detailed output
  -q, --quick       Run in quick mode (reduced iterations)
  -c, --category    Run specific category (stress, compatibility, consistency, recovery, limits)
  -t, --test        Run specific test within category
  -h, --help        Show this help

Examples:
  node run-stress-tests.js                     Run all tests
  node run-stress-tests.js -q                  Run all tests in quick mode
  node run-stress-tests.js -c stress           Run only stress tests
  node run-stress-tests.js -c stress -t massive-elements  Run specific test
            `);
            process.exit(0);
        }
    }

    return options;
}

// Ejecutar si es script principal (Node.js)
if (typeof require !== 'undefined' && require.main === module) {
    const options = parseCliArgs(process.argv.slice(2));

    const suite = new VBPStressTestSuite(options);

    if (options.test && options.category) {
        suite.runSingleTest(options.category, options.test)
            .then(result => {
                console.log('\nResult:', JSON.stringify(result, null, 2));
                process.exit(result.passed ? 0 : 1);
            })
            .catch(error => {
                console.error('Error:', error);
                process.exit(1);
            });
    } else if (options.category) {
        suite.runCategory(options.category)
            .then(result => {
                process.exit(result.failed === 0 ? 0 : 1);
            })
            .catch(error => {
                console.error('Error:', error);
                process.exit(1);
            });
    } else {
        runAllStressTests(options)
            .then(({ summary }) => {
                process.exit(summary.failed === 0 ? 0 : 1);
            })
            .catch(error => {
                console.error('Error:', error);
                process.exit(1);
            });
    }
}

// Exportar
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        VBPStressTestSuite,
        runAllStressTests,
        CONFIG
    };
}

if (typeof window !== 'undefined') {
    window.VBPStressTestSuite = {
        VBPStressTestSuite,
        runAllStressTests,
        CONFIG
    };
}
