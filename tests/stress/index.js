/**
 * VBP Stress Tests - Entry Point
 *
 * Exporta todas las suites de tests para uso en Node.js y navegador
 *
 * @package FlavorPlatform
 * @since 3.4.0
 */

// Stress Tests
const stressTests = require('./stress-tests.js');

// Plugin Compatibility
const pluginCompatibility = require('./plugin-compatibility.js');

// Data Consistency
const dataConsistency = require('./data-consistency.js');

// Error Recovery
const errorRecovery = require('./error-recovery.js');

// Limits Tests
const limitsTests = require('./limits-test.js');

// Main Runner
const runner = require('./run-stress-tests.js');

/**
 * API publica del modulo
 */
module.exports = {
    // Suites individuales
    stress: {
        STRESS_TESTS: stressTests.STRESS_TESTS,
        StressTestRunner: stressTests.StressTestRunner,
        StressTestUtils: stressTests.StressTestUtils,
        MockVBPStore: stressTests.MockVBPStore
    },

    compatibility: {
        POPULAR_PLUGINS: pluginCompatibility.POPULAR_PLUGINS,
        COMPATIBILITY_CHECKS: pluginCompatibility.COMPATIBILITY_CHECKS,
        PluginCompatibilityTest: pluginCompatibility.PluginCompatibilityTest,
        KNOWN_COMPATIBILITY_MATRIX: pluginCompatibility.KNOWN_COMPATIBILITY_MATRIX
    },

    consistency: {
        DATA_CONSISTENCY_TESTS: dataConsistency.DATA_CONSISTENCY_TESTS,
        DataConsistencyTestRunner: dataConsistency.DataConsistencyTestRunner,
        ConsistencyMockStore: dataConsistency.ConsistencyMockStore,
        ConsistencyUtils: dataConsistency.ConsistencyUtils
    },

    recovery: {
        ERROR_RECOVERY_TESTS: errorRecovery.ERROR_RECOVERY_TESTS,
        ErrorRecoveryTestRunner: errorRecovery.ErrorRecoveryTestRunner,
        RecoveryMockStore: errorRecovery.RecoveryMockStore,
        NetworkErrorSimulator: errorRecovery.NetworkErrorSimulator
    },

    limits: {
        LIMITS_TESTS: limitsTests.LIMITS_TESTS,
        LimitsTestRunner: limitsTests.LimitsTestRunner,
        LimitsMockStore: limitsTests.LimitsMockStore,
        LimitsTestUtils: limitsTests.LimitsTestUtils
    },

    // Runner principal
    VBPStressTestSuite: runner.VBPStressTestSuite,
    runAllStressTests: runner.runAllStressTests,
    CONFIG: runner.CONFIG,

    // Helpers
    version: '3.4.0',

    /**
     * Ejecutar todos los tests con opciones por defecto
     * @param {Object} options - Opciones de ejecucion
     * @returns {Promise<Object>} Resultados
     */
    async run(options = {}) {
        return await runner.runAllStressTests(options);
    },

    /**
     * Ejecutar en modo rapido
     * @returns {Promise<Object>} Resultados
     */
    async runQuick() {
        return await runner.runAllStressTests({ quickMode: true });
    },

    /**
     * Ejecutar con salida verbose
     * @returns {Promise<Object>} Resultados
     */
    async runVerbose() {
        return await runner.runAllStressTests({ verbose: true });
    },

    /**
     * Obtener lista de tests disponibles
     * @returns {Object} Tests por categoria
     */
    getAvailableTests() {
        return {
            stress: Object.keys(stressTests.STRESS_TESTS),
            compatibility: pluginCompatibility.POPULAR_PLUGINS.map(p => p.slug),
            consistency: Object.keys(dataConsistency.DATA_CONSISTENCY_TESTS),
            recovery: Object.keys(errorRecovery.ERROR_RECOVERY_TESTS),
            limits: Object.keys(limitsTests.LIMITS_TESTS)
        };
    },

    /**
     * Obtener informacion de un test especifico
     * @param {string} category - Categoria del test
     * @param {string} testId - ID del test
     * @returns {Object|null} Informacion del test
     */
    getTestInfo(category, testId) {
        switch (category) {
            case 'stress':
                return stressTests.STRESS_TESTS[testId] || null;
            case 'consistency':
                return dataConsistency.DATA_CONSISTENCY_TESTS[testId] || null;
            case 'recovery':
                return errorRecovery.ERROR_RECOVERY_TESTS[testId] || null;
            case 'limits':
                return limitsTests.LIMITS_TESTS[testId] || null;
            case 'compatibility':
                return pluginCompatibility.POPULAR_PLUGINS.find(p => p.slug === testId) || null;
            default:
                return null;
        }
    }
};
