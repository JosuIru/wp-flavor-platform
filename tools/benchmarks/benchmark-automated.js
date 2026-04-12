/**
 * VBP Automated Benchmarks with Playwright
 *
 * Runs benchmarks automatically using browser automation.
 * Execute with: npx playwright test tools/benchmarks/benchmark-automated.js
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

// @ts-check
const { test, expect } = require('@playwright/test');
const path = require('path');
const fs = require('fs');

const { BENCHMARKS, COMPETITORS } = require('./benchmark-config');

/**
 * Configuration
 */
const CONFIG = {
    baseURL: process.env.WP_BASE_URL || 'http://sitio-prueba.local',
    editorPath: '/wp-admin/admin.php?page=vbp-editor',
    adminUser: process.env.WP_ADMIN_USER || 'admin',
    adminPass: process.env.WP_ADMIN_PASS || 'admin',
    outputDir: path.join(__dirname, '../../reports/benchmarks'),
    screenshotsEnabled: true
};

/**
 * Benchmark result storage
 */
const benchmarkResults = [];

/**
 * Helper: Format time as mm:ss.ms
 *
 * @param {number} milliseconds Time in ms
 * @returns {string} Formatted time
 */
function formatTime(milliseconds) {
    const seconds = milliseconds / 1000;
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = (seconds % 60).toFixed(2);
    return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(5, '0')}`;
}

/**
 * Helper: Calculate score
 *
 * @param {number} actual Actual value
 * @param {Object} expected Expected thresholds
 * @returns {number} Score 0-100
 */
function calculateScore(actual, expected) {
    if (actual <= expected.min) return 100;
    if (actual <= expected.target) {
        return 100 - ((actual - expected.min) / (expected.target - expected.min)) * 25;
    }
    if (actual <= expected.max) {
        return 75 - ((actual - expected.target) / (expected.max - expected.target)) * 35;
    }
    const overageRatio = (actual - expected.max) / expected.max;
    return Math.max(0, 40 - (overageRatio * 40));
}

/**
 * Test setup: Authenticate once
 */
test.beforeAll(async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();

    // Login to WordPress
    await page.goto(`${CONFIG.baseURL}/wp-login.php`);
    await page.fill('#user_login', CONFIG.adminUser);
    await page.fill('#user_pass', CONFIG.adminPass);
    await page.click('#wp-submit');
    await page.waitForURL('**/wp-admin/**');

    // Save authentication state
    await context.storageState({ path: path.join(__dirname, '../../tests/e2e/.auth/benchmark-user.json') });
    await context.close();
});

/**
 * Benchmark: Landing Simple
 */
test.describe('Benchmark: Landing Simple', () => {
    test.use({
        storageState: path.join(__dirname, '../../tests/e2e/.auth/benchmark-user.json')
    });

    test('completes landing-simple benchmark', async ({ page }) => {
        const benchmark = BENCHMARKS['landing-simple'];
        const metrics = {
            clicks: 0,
            keystrokes: 0,
            errors: 0,
            steps: []
        };

        // Track interactions
        page.on('click', () => metrics.clicks++);

        const startTime = Date.now();

        // Navigate to editor
        await page.goto(`${CONFIG.baseURL}${CONFIG.editorPath}&post_id=new`);
        await page.waitForSelector('.vbp-editor, .vbp-canvas', { timeout: 15000 });

        // Step 1: Add hero section
        let stepStart = Date.now();
        try {
            await page.click('[data-action="open-blocks"], .vbp-add-block-btn');
            await page.waitForSelector('.vbp-block-library, .vbp-blocks-panel');
            await page.click('[data-block-type="hero"], [data-section-type="hero"]');
            await page.waitForSelector('.vbp-element[data-type="hero"], .vbp-section--hero');

            metrics.steps.push({
                id: 'add-hero',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'add-hero',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 2: Edit title
        stepStart = Date.now();
        try {
            await page.dblclick('.vbp-element[data-type="heading"] h1, .vbp-hero-title');
            await page.keyboard.selectAll();
            await page.keyboard.type('Titulo Principal');
            metrics.keystrokes += 17;

            metrics.steps.push({
                id: 'edit-title',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'edit-title',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 3: Edit subtitle
        stepStart = Date.now();
        try {
            await page.dblclick('.vbp-element[data-type="text"] p, .vbp-hero-subtitle, .vbp-subtitle');
            await page.keyboard.selectAll();
            await page.keyboard.type('Subtitulo descriptivo');
            metrics.keystrokes += 21;

            metrics.steps.push({
                id: 'edit-subtitle',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'edit-subtitle',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 4: Add CTA button
        stepStart = Date.now();
        try {
            await page.click('[data-action="open-blocks"], .vbp-add-block-btn');
            await page.click('[data-block-type="button"]');
            await page.waitForSelector('.vbp-element[data-type="button"]');

            // Edit button text
            await page.dblclick('.vbp-element[data-type="button"]');
            await page.keyboard.selectAll();
            await page.keyboard.type('Comenzar');
            metrics.keystrokes += 8;

            metrics.steps.push({
                id: 'add-cta-button',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'add-cta-button',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 5: Add features section
        stepStart = Date.now();
        try {
            await page.click('[data-action="open-blocks"]');
            await page.click('[data-block-type="features-3col"], [data-section-type="features"]');
            await page.waitForSelector('.vbp-element[data-type="features"], .vbp-section--features');

            metrics.steps.push({
                id: 'add-features',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'add-features',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 6-8: Edit features
        for (let featureIndex = 0; featureIndex < 3; featureIndex++) {
            stepStart = Date.now();
            try {
                const featureSelector = `.vbp-feature:nth-child(${featureIndex + 1}) .vbp-feature-title, .vbp-features-item:nth-child(${featureIndex + 1})`;
                await page.dblclick(featureSelector);
                await page.keyboard.selectAll();
                await page.keyboard.type(`Caracteristica ${featureIndex + 1}`);
                metrics.keystrokes += 16;

                metrics.steps.push({
                    id: `edit-feature-${featureIndex + 1}`,
                    duration: Date.now() - stepStart,
                    success: true
                });
            } catch (error) {
                metrics.errors++;
                metrics.steps.push({
                    id: `edit-feature-${featureIndex + 1}`,
                    duration: Date.now() - stepStart,
                    success: false,
                    error: error.message
                });
            }
        }

        // Step 9: Add CTA section
        stepStart = Date.now();
        try {
            await page.click('[data-action="open-blocks"]');
            await page.click('[data-block-type="cta"], [data-section-type="cta"]');
            await page.waitForSelector('.vbp-element[data-type="cta"], .vbp-section--cta');

            metrics.steps.push({
                id: 'add-cta-section',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'add-cta-section',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 10: Customize CTA
        stepStart = Date.now();
        try {
            await page.dblclick('.vbp-cta-title, .vbp-section--cta h2');
            await page.keyboard.selectAll();
            await page.keyboard.type('Listo para empezar?');
            metrics.keystrokes += 20;

            metrics.steps.push({
                id: 'customize-cta',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'customize-cta',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Step 11: Save
        stepStart = Date.now();
        try {
            await page.click('[data-action="save"], .vbp-save-btn');
            await page.waitForSelector('.vbp-toast-success, .vbp-save-indicator.saved', { timeout: 10000 });

            metrics.steps.push({
                id: 'save',
                duration: Date.now() - stepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'save',
                duration: Date.now() - stepStart,
                success: false,
                error: error.message
            });
        }

        // Calculate final metrics
        const totalTime = (Date.now() - startTime) / 1000;

        // Calculate scores
        const timeScore = calculateScore(totalTime, benchmark.expectedMetrics.time);
        const clickScore = calculateScore(metrics.clicks, benchmark.expectedMetrics.clicks);
        const errorScore = calculateScore(metrics.errors, benchmark.expectedMetrics.errors);
        const overallScore = (timeScore * 0.5) + (clickScore * 0.3) + (errorScore * 0.2);

        // Build result
        const result = {
            benchmarkId: 'landing-simple',
            benchmarkName: benchmark.name,
            timestamp: new Date().toISOString(),
            automated: true,
            vbp: {
                time: totalTime,
                clicks: metrics.clicks,
                keystrokes: metrics.keystrokes,
                errors: metrics.errors,
                stepsCompleted: metrics.steps.filter(stepItem => stepItem.success).length,
                totalSteps: benchmark.steps.length
            },
            scores: {
                time: timeScore,
                clicks: clickScore,
                errors: errorScore,
                overall: overallScore
            },
            steps: metrics.steps,
            comparison: Object.entries(COMPETITORS).map(([competitorId, competitor]) => {
                const competitorBenchmark = competitor.benchmarks['landing-simple'];
                const timeDiff = competitorBenchmark.avgTime - totalTime;
                return {
                    id: competitorId,
                    name: competitor.name,
                    theirTime: competitorBenchmark.avgTime,
                    timeDiff: timeDiff,
                    vbpFaster: timeDiff > 0,
                    percentage: ((timeDiff / competitorBenchmark.avgTime) * 100).toFixed(1)
                };
            })
        };

        benchmarkResults.push(result);

        // Take screenshot
        if (CONFIG.screenshotsEnabled) {
            await page.screenshot({
                path: path.join(CONFIG.outputDir, `landing-simple-${Date.now()}.png`),
                fullPage: true
            });
        }

        // Assertions
        expect(totalTime).toBeLessThan(benchmark.expectedMetrics.time.max);
        expect(metrics.errors).toBeLessThanOrEqual(benchmark.expectedMetrics.errors.max);

        // Log results
        console.log('\n=== Landing Simple Benchmark Results ===');
        console.log(`Time: ${totalTime.toFixed(2)}s (target: ${benchmark.expectedMetrics.time.target}s)`);
        console.log(`Clicks: ${metrics.clicks} (target: ${benchmark.expectedMetrics.clicks.target})`);
        console.log(`Errors: ${metrics.errors}`);
        console.log(`Overall Score: ${overallScore.toFixed(1)}/100`);
        console.log('\nComparison:');
        result.comparison.forEach(comp => {
            const indicator = comp.vbpFaster ? '✓ faster' : '✗ slower';
            console.log(`  ${comp.name}: ${indicator} by ${Math.abs(comp.timeDiff).toFixed(0)}s (${comp.percentage}%)`);
        });
    });
});

/**
 * Benchmark: Home Corporativa
 */
test.describe('Benchmark: Home Corporate', () => {
    test.use({
        storageState: path.join(__dirname, '../../tests/e2e/.auth/benchmark-user.json')
    });

    test('completes home-corporate benchmark', async ({ page }) => {
        const benchmark = BENCHMARKS['home-corporate'];
        const metrics = {
            clicks: 0,
            keystrokes: 0,
            errors: 0,
            steps: []
        };

        page.on('click', () => metrics.clicks++);

        const startTime = Date.now();

        await page.goto(`${CONFIG.baseURL}${CONFIG.editorPath}&post_id=new`);
        await page.waitForSelector('.vbp-editor, .vbp-canvas', { timeout: 15000 });

        // Simplified steps for home corporate
        const sectionTypes = [
            'header-nav',
            'hero-video',
            'about-2col',
            'services-grid',
            'team-cards',
            'testimonials-slider',
            'contact-form',
            'footer'
        ];

        for (const sectionType of sectionTypes) {
            const stepStart = Date.now();
            try {
                await page.click('[data-action="open-blocks"]');
                await page.waitForSelector('.vbp-block-library, .vbp-blocks-panel');

                // Try to find and click the section
                const sectionSelector = `[data-block-type="${sectionType}"], [data-section-type="${sectionType}"]`;
                const sectionExists = await page.$(sectionSelector);

                if (sectionExists) {
                    await page.click(sectionSelector);
                    await page.waitForTimeout(500); // Wait for section to load
                }

                metrics.steps.push({
                    id: `add-${sectionType}`,
                    duration: Date.now() - stepStart,
                    success: !!sectionExists
                });

                if (!sectionExists) {
                    metrics.errors++;
                }
            } catch (error) {
                metrics.errors++;
                metrics.steps.push({
                    id: `add-${sectionType}`,
                    duration: Date.now() - stepStart,
                    success: false,
                    error: error.message
                });
            }
        }

        // Customize colors
        const colorStepStart = Date.now();
        try {
            await page.click('[data-action="design-settings"], .vbp-design-btn');
            await page.waitForSelector('.vbp-design-panel, .vbp-style-panel');

            // Change primary color
            const colorInput = await page.$('.vbp-color-picker-primary input, [name="primaryColor"]');
            if (colorInput) {
                await colorInput.fill('#3b82f6');
                metrics.keystrokes += 7;
            }

            metrics.steps.push({
                id: 'customize-colors',
                duration: Date.now() - colorStepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'customize-colors',
                duration: Date.now() - colorStepStart,
                success: false,
                error: error.message
            });
        }

        // Save
        const saveStepStart = Date.now();
        try {
            await page.click('[data-action="save"], .vbp-save-btn');
            await page.waitForSelector('.vbp-toast-success, .vbp-save-indicator.saved', { timeout: 10000 });

            metrics.steps.push({
                id: 'save',
                duration: Date.now() - saveStepStart,
                success: true
            });
        } catch (error) {
            metrics.errors++;
            metrics.steps.push({
                id: 'save',
                duration: Date.now() - saveStepStart,
                success: false,
                error: error.message
            });
        }

        const totalTime = (Date.now() - startTime) / 1000;

        // Calculate scores
        const timeScore = calculateScore(totalTime, benchmark.expectedMetrics.time);
        const clickScore = calculateScore(metrics.clicks, benchmark.expectedMetrics.clicks);
        const errorScore = calculateScore(metrics.errors, benchmark.expectedMetrics.errors);
        const overallScore = (timeScore * 0.5) + (clickScore * 0.3) + (errorScore * 0.2);

        const result = {
            benchmarkId: 'home-corporate',
            benchmarkName: benchmark.name,
            timestamp: new Date().toISOString(),
            automated: true,
            vbp: {
                time: totalTime,
                clicks: metrics.clicks,
                keystrokes: metrics.keystrokes,
                errors: metrics.errors,
                stepsCompleted: metrics.steps.filter(stepItem => stepItem.success).length,
                totalSteps: benchmark.steps.length
            },
            scores: {
                time: timeScore,
                clicks: clickScore,
                errors: errorScore,
                overall: overallScore
            },
            steps: metrics.steps,
            comparison: Object.entries(COMPETITORS).map(([competitorId, competitor]) => {
                const competitorBenchmark = competitor.benchmarks['home-corporate'];
                const timeDiff = competitorBenchmark.avgTime - totalTime;
                return {
                    id: competitorId,
                    name: competitor.name,
                    theirTime: competitorBenchmark.avgTime,
                    timeDiff: timeDiff,
                    vbpFaster: timeDiff > 0,
                    percentage: ((timeDiff / competitorBenchmark.avgTime) * 100).toFixed(1)
                };
            })
        };

        benchmarkResults.push(result);

        if (CONFIG.screenshotsEnabled) {
            await page.screenshot({
                path: path.join(CONFIG.outputDir, `home-corporate-${Date.now()}.png`),
                fullPage: true
            });
        }

        expect(totalTime).toBeLessThan(benchmark.expectedMetrics.time.max);

        console.log('\n=== Home Corporate Benchmark Results ===');
        console.log(`Time: ${totalTime.toFixed(2)}s (target: ${benchmark.expectedMetrics.time.target}s)`);
        console.log(`Clicks: ${metrics.clicks} (target: ${benchmark.expectedMetrics.clicks.target})`);
        console.log(`Errors: ${metrics.errors}`);
        console.log(`Overall Score: ${overallScore.toFixed(1)}/100`);
    });
});

/**
 * Generate final report
 */
test.afterAll(async () => {
    // Ensure output directory exists
    if (!fs.existsSync(CONFIG.outputDir)) {
        fs.mkdirSync(CONFIG.outputDir, { recursive: true });
    }

    // Save JSON results
    const jsonPath = path.join(CONFIG.outputDir, `benchmark-results-${Date.now()}.json`);
    fs.writeFileSync(jsonPath, JSON.stringify({
        generatedAt: new Date().toISOString(),
        version: '1.0',
        automated: true,
        results: benchmarkResults
    }, null, 2));

    // Generate markdown report
    const markdownReport = generateMarkdownReport(benchmarkResults);
    const markdownPath = path.join(CONFIG.outputDir, 'BENCHMARK-RESULTS.md');
    fs.writeFileSync(markdownPath, markdownReport);

    console.log(`\nResults saved to: ${CONFIG.outputDir}`);
});

/**
 * Generate markdown report
 *
 * @param {Array} results Benchmark results
 * @returns {string} Markdown content
 */
function generateMarkdownReport(results) {
    const timestamp = new Date().toISOString().split('T')[0];

    let markdown = `# VBP Benchmark Results

Generated: ${timestamp}
Mode: Automated (Playwright)

## Executive Summary

| Benchmark | VBP Time | Score | vs Elementor | vs Gutenberg |
|-----------|----------|-------|--------------|--------------|
`;

    results.forEach(result => {
        const elementorComp = result.comparison.find(comparisonItem => comparisonItem.id === 'elementor');
        const gutenbergComp = result.comparison.find(comparisonItem => comparisonItem.id === 'gutenberg');

        markdown += `| ${result.benchmarkName} | ${result.vbp.time.toFixed(1)}s | ${result.scores.overall.toFixed(0)}/100 | `;
        markdown += `${elementorComp.vbpFaster ? '✓' : '✗'} ${Math.abs(elementorComp.timeDiff).toFixed(0)}s | `;
        markdown += `${gutenbergComp.vbpFaster ? '✓' : '✗'} ${Math.abs(gutenbergComp.timeDiff).toFixed(0)}s |\n`;
    });

    markdown += `\n## Detailed Results\n\n`;

    results.forEach(result => {
        markdown += `### ${result.benchmarkName}\n\n`;
        markdown += `- **Time**: ${result.vbp.time.toFixed(2)}s\n`;
        markdown += `- **Clicks**: ${result.vbp.clicks}\n`;
        markdown += `- **Keystrokes**: ${result.vbp.keystrokes}\n`;
        markdown += `- **Errors**: ${result.vbp.errors}\n`;
        markdown += `- **Steps Completed**: ${result.vbp.stepsCompleted}/${result.vbp.totalSteps}\n`;
        markdown += `- **Overall Score**: ${result.scores.overall.toFixed(1)}/100\n\n`;

        markdown += `#### Competitor Comparison\n\n`;
        markdown += `| Competitor | Their Time | Difference | VBP Faster? |\n`;
        markdown += `|------------|------------|------------|-------------|\n`;

        result.comparison.forEach(comp => {
            const status = comp.vbpFaster ? 'Yes' : 'No';
            const diff = comp.vbpFaster ? `-${Math.abs(comp.timeDiff).toFixed(0)}s` : `+${Math.abs(comp.timeDiff).toFixed(0)}s`;
            markdown += `| ${comp.name} | ${comp.theirTime}s | ${diff} (${comp.percentage}%) | ${status} |\n`;
        });

        markdown += `\n#### Step Breakdown\n\n`;
        markdown += `| Step | Duration | Status |\n`;
        markdown += `|------|----------|--------|\n`;

        result.steps.forEach(step => {
            const status = step.success ? 'Completed' : 'Failed';
            const duration = (step.duration / 1000).toFixed(2);
            markdown += `| ${step.id} | ${duration}s | ${status} |\n`;
        });

        markdown += `\n---\n\n`;
    });

    markdown += `## Methodology

- Tests run automatically using Playwright
- WordPress admin authenticated before tests
- Each benchmark creates a new page from scratch
- Metrics tracked: time, clicks, keystrokes, errors
- Competitor data based on industry benchmarks

## Recommendations

Based on these results:

1. Focus optimization on slowest steps
2. Add keyboard shortcuts for common actions
3. Implement templates to reduce repetitive work
4. Consider auto-save to reduce save step time

---

*Report generated by VBP Benchmark Suite*
`;

    return markdown;
}
