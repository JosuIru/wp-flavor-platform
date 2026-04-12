/**
 * VBP Benchmark Suite - Main Entry Point
 *
 * Exports all benchmark modules for use in both Node.js and browser.
 *
 * @package FlavorPlatform
 * @since 3.5.0
 *
 * @example
 * // Node.js usage
 * const { BenchmarkRunner, BENCHMARKS, COMPETITORS } = require('./tools/benchmarks');
 *
 * const runner = new BenchmarkRunner({ verbose: true });
 * runner.start('landing-simple', { experienceLevel: 'intermediate' });
 * // ... perform benchmark steps ...
 * const report = runner.finish();
 * console.log(report);
 *
 * @example
 * // Browser usage (inject via WordPress)
 * // The modules attach to window.VBP namespace
 * const runner = new window.VBPBenchmarkRunner();
 * const ui = new window.VBPBenchmarkUI(runner);
 * ui.show();
 */

'use strict';

const { BENCHMARKS, COMPETITORS, METRIC_WEIGHTS, RATINGS, LEARNING_CURVE } = require('./benchmark-config');
const { BenchmarkRunner } = require('./benchmark-runner');

// Conditionally require modules that might not work in all environments
let BenchmarkUI = null;
let BenchmarkReportGenerator = null;

try {
    BenchmarkUI = require('./benchmark-ui').BenchmarkUI;
} catch (error) {
    // UI module requires DOM, skip in Node.js
}

try {
    BenchmarkReportGenerator = require('./benchmark-report').BenchmarkReportGenerator;
} catch (error) {
    // Report generator requires fs, might fail in browser
}

/**
 * Quick benchmark runner for CLI usage
 *
 * @param {string} benchmarkId Benchmark to run
 * @param {Object} options Run options
 * @returns {Object} Benchmark info
 */
function quickInfo(benchmarkId) {
    const benchmark = BENCHMARKS[benchmarkId];

    if (!benchmark) {
        console.error(`Benchmark not found: ${benchmarkId}`);
        console.log('Available benchmarks:', Object.keys(BENCHMARKS).join(', '));
        return null;
    }

    console.log('\n=== Benchmark Info ===');
    console.log(`ID: ${benchmark.id}`);
    console.log(`Name: ${benchmark.name}`);
    console.log(`Description: ${benchmark.description}`);
    console.log(`Category: ${benchmark.category}`);
    console.log(`Difficulty: ${benchmark.difficulty}`);
    console.log(`Steps: ${benchmark.steps.length}`);
    console.log('\nExpected Metrics:');
    console.log(`  Time: ${benchmark.expectedMetrics.time.target}s (min: ${benchmark.expectedMetrics.time.min}s, max: ${benchmark.expectedMetrics.time.max}s)`);
    console.log(`  Clicks: ${benchmark.expectedMetrics.clicks.target} (min: ${benchmark.expectedMetrics.clicks.min}, max: ${benchmark.expectedMetrics.clicks.max})`);
    console.log(`  Keystrokes: ${benchmark.expectedMetrics.keystrokes.target}`);
    console.log('\nSteps:');
    benchmark.steps.forEach((step, index) => {
        console.log(`  ${index + 1}. ${step.description || step.id}`);
    });
    console.log('\nCompetitor Baselines:');
    Object.entries(COMPETITORS).forEach(([competitorId, competitor]) => {
        const competitorData = competitor.benchmarks[benchmarkId];
        if (competitorData) {
            console.log(`  ${competitor.name}: ${competitorData.avgTime}s, ${competitorData.avgClicks} clicks`);
        }
    });
    console.log('');

    return benchmark;
}

/**
 * List all available benchmarks
 */
function listBenchmarks() {
    console.log('\n=== Available Benchmarks ===\n');

    Object.values(BENCHMARKS).forEach(benchmark => {
        console.log(`${benchmark.id}`);
        console.log(`  Name: ${benchmark.name}`);
        console.log(`  Description: ${benchmark.description}`);
        console.log(`  Difficulty: ${benchmark.difficulty}`);
        console.log(`  Target Time: ${benchmark.expectedMetrics.time.target}s`);
        console.log('');
    });
}

/**
 * Compare VBP with competitors for a specific benchmark
 *
 * @param {string} benchmarkId Benchmark ID
 * @param {number} vbpTime VBP actual time
 */
function compareWithCompetitors(benchmarkId, vbpTime) {
    const benchmark = BENCHMARKS[benchmarkId];

    if (!benchmark) {
        console.error(`Benchmark not found: ${benchmarkId}`);
        return;
    }

    console.log(`\n=== Comparison: ${benchmark.name} ===`);
    console.log(`VBP Time: ${vbpTime}s\n`);

    Object.entries(COMPETITORS).forEach(([competitorId, competitor]) => {
        const competitorData = competitor.benchmarks[benchmarkId];
        if (competitorData) {
            const timeDiff = competitorData.avgTime - vbpTime;
            const percentage = ((timeDiff / competitorData.avgTime) * 100).toFixed(1);
            const status = timeDiff > 0 ? 'FASTER' : 'SLOWER';
            const statusColor = timeDiff > 0 ? '\x1b[32m' : '\x1b[31m';

            console.log(`${competitor.name}:`);
            console.log(`  Their time: ${competitorData.avgTime}s`);
            console.log(`  Difference: ${statusColor}${timeDiff > 0 ? '-' : '+'}${Math.abs(timeDiff).toFixed(0)}s (${percentage}%) ${status}\x1b[0m`);
            console.log('');
        }
    });
}

/**
 * Create browser bundle script
 *
 * @returns {string} Script content for browser
 */
function createBrowserBundle() {
    const configContent = require('fs').readFileSync(require('path').join(__dirname, 'benchmark-config.js'), 'utf8');
    const runnerContent = require('fs').readFileSync(require('path').join(__dirname, 'benchmark-runner.js'), 'utf8');
    const uiContent = require('fs').readFileSync(require('path').join(__dirname, 'benchmark-ui.js'), 'utf8');

    return `
// VBP Benchmark Suite - Browser Bundle
// Generated: ${new Date().toISOString()}

(function(window) {
    'use strict';

    // Config module
    ${configContent.replace(/module\.exports\s*=\s*\{[^}]+\};?/s, '')}

    window.VBP_BENCHMARKS = BENCHMARKS;
    window.VBP_COMPETITORS = COMPETITORS;

    // Runner module
    ${runnerContent.replace(/const\s*\{[^}]+\}\s*=\s*require[^;]+;/g, '')
                   .replace(/module\.exports\s*=\s*\{[^}]+\};?/s, '')}

    // UI module
    ${uiContent.replace(/const\s*\{[^}]+\}\s*=\s*require[^;]+;/g, '')
               .replace(/module\.exports\s*=\s*\{[^}]+\};?/s, '')}

    // Initialize global VBP benchmark namespace
    window.VBPBenchmark = {
        Runner: BenchmarkRunner,
        UI: BenchmarkUI,
        BENCHMARKS: BENCHMARKS,
        COMPETITORS: COMPETITORS,

        // Quick initialization
        init: function(options) {
            const runner = new BenchmarkRunner(options);
            const ui = new BenchmarkUI(runner, options);
            return { runner, ui };
        }
    };

    console.log('[VBP Benchmark] Suite loaded. Press Alt+B to toggle benchmark panel.');

})(typeof window !== 'undefined' ? window : this);
`;
}

// CLI handling
if (require.main === module) {
    const args = process.argv.slice(2);
    const command = args[0];

    switch (command) {
        case 'list':
            listBenchmarks();
            break;

        case 'info':
            if (args[1]) {
                quickInfo(args[1]);
            } else {
                console.log('Usage: node index.js info <benchmark-id>');
                listBenchmarks();
            }
            break;

        case 'compare':
            if (args[1] && args[2]) {
                compareWithCompetitors(args[1], parseFloat(args[2]));
            } else {
                console.log('Usage: node index.js compare <benchmark-id> <vbp-time>');
                console.log('Example: node index.js compare landing-simple 95');
            }
            break;

        case 'report':
            if (BenchmarkReportGenerator) {
                const generator = new BenchmarkReportGenerator();
                const paths = generator.generateAllReports();
                if (paths) {
                    console.log('Reports generated successfully!');
                }
            } else {
                console.error('Report generator not available');
            }
            break;

        case 'bundle':
            console.log(createBrowserBundle());
            break;

        default:
            console.log('VBP Benchmark Suite\n');
            console.log('Commands:');
            console.log('  list                       List all benchmarks');
            console.log('  info <benchmark-id>        Show benchmark details');
            console.log('  compare <id> <time>        Compare VBP time with competitors');
            console.log('  report                     Generate reports from results');
            console.log('  bundle                     Output browser bundle');
            console.log('\nExamples:');
            console.log('  node index.js list');
            console.log('  node index.js info landing-simple');
            console.log('  node index.js compare landing-simple 95');
    }
}

module.exports = {
    // Configuration
    BENCHMARKS,
    COMPETITORS,
    METRIC_WEIGHTS,
    RATINGS,
    LEARNING_CURVE,

    // Classes
    BenchmarkRunner,
    BenchmarkUI,
    BenchmarkReportGenerator,

    // Utility functions
    quickInfo,
    listBenchmarks,
    compareWithCompetitors,
    createBrowserBundle
};
