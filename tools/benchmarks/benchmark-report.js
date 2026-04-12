/**
 * VBP Benchmark Report Generator
 *
 * Generates comprehensive reports from benchmark results.
 * Supports multiple output formats: Markdown, HTML, JSON.
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

'use strict';

const fs = require('fs');
const path = require('path');
const { BENCHMARKS, COMPETITORS, RATINGS } = require('./benchmark-config');

/**
 * Report Generator Class
 */
class BenchmarkReportGenerator {
    /**
     * Constructor
     *
     * @param {Object} options Generator options
     */
    constructor(options = {}) {
        this.options = {
            outputDir: options.outputDir || path.join(__dirname, '../../reports/benchmarks'),
            includeCharts: options.includeCharts !== false,
            includeRecommendations: options.includeRecommendations !== false,
            ...options
        };

        this.ensureOutputDir();
    }

    /**
     * Ensure output directory exists
     */
    ensureOutputDir() {
        if (!fs.existsSync(this.options.outputDir)) {
            fs.mkdirSync(this.options.outputDir, { recursive: true });
        }
    }

    /**
     * Load results from JSON files
     *
     * @param {string} filterBenchmarkId Optional benchmark ID filter
     * @returns {Array} Loaded results
     */
    loadResults(filterBenchmarkId = null) {
        const files = fs.readdirSync(this.options.outputDir)
            .filter(fileName => fileName.startsWith('benchmark-results-') && fileName.endsWith('.json'))
            .sort()
            .reverse();

        const allResults = [];

        files.forEach(fileName => {
            try {
                const filePath = path.join(this.options.outputDir, fileName);
                const data = JSON.parse(fs.readFileSync(filePath, 'utf8'));

                if (data.results) {
                    data.results.forEach(result => {
                        if (!filterBenchmarkId || result.benchmarkId === filterBenchmarkId) {
                            allResults.push({
                                ...result,
                                sourceFile: fileName
                            });
                        }
                    });
                }
            } catch (error) {
                console.error(`Error loading ${fileName}:`, error.message);
            }
        });

        return allResults;
    }

    /**
     * Calculate aggregate statistics
     *
     * @param {Array} results Results array
     * @returns {Object} Aggregated stats
     */
    calculateAggregateStats(results) {
        if (results.length === 0) return null;

        const stats = {};

        // Group by benchmark
        const groupedResults = {};
        results.forEach(result => {
            if (!groupedResults[result.benchmarkId]) {
                groupedResults[result.benchmarkId] = [];
            }
            groupedResults[result.benchmarkId].push(result);
        });

        Object.entries(groupedResults).forEach(([benchmarkId, benchmarkResults]) => {
            const times = benchmarkResults.map(resultItem => resultItem.vbp.time);
            const clicks = benchmarkResults.map(resultItem => resultItem.vbp.clicks);
            const scores = benchmarkResults.map(resultItem => resultItem.scores.overall);

            stats[benchmarkId] = {
                benchmarkName: benchmarkResults[0].benchmarkName,
                runs: benchmarkResults.length,
                time: {
                    min: Math.min(...times),
                    max: Math.max(...times),
                    avg: times.reduce((sum, timeValue) => sum + timeValue, 0) / times.length,
                    median: this.median(times)
                },
                clicks: {
                    min: Math.min(...clicks),
                    max: Math.max(...clicks),
                    avg: clicks.reduce((sum, clickValue) => sum + clickValue, 0) / clicks.length
                },
                score: {
                    min: Math.min(...scores),
                    max: Math.max(...scores),
                    avg: scores.reduce((sum, scoreValue) => sum + scoreValue, 0) / scores.length
                },
                trend: this.calculateTrend(times),
                bestRun: benchmarkResults.reduce((best, current) =>
                    current.vbp.time < best.vbp.time ? current : best
                )
            };
        });

        return stats;
    }

    /**
     * Calculate median
     *
     * @param {Array} values Numeric values
     * @returns {number} Median value
     */
    median(values) {
        const sorted = [...values].sort((valueA, valueB) => valueA - valueB);
        const mid = Math.floor(sorted.length / 2);
        return sorted.length % 2 !== 0
            ? sorted[mid]
            : (sorted[mid - 1] + sorted[mid]) / 2;
    }

    /**
     * Calculate trend (improving/declining)
     *
     * @param {Array} values Time series values
     * @returns {Object} Trend information
     */
    calculateTrend(values) {
        if (values.length < 2) {
            return { direction: 'neutral', percentage: 0 };
        }

        // Compare recent average vs older average
        const halfIndex = Math.floor(values.length / 2);
        const recentValues = values.slice(0, halfIndex);
        const olderValues = values.slice(halfIndex);

        const recentAvg = recentValues.reduce((sum, valueItem) => sum + valueItem, 0) / recentValues.length;
        const olderAvg = olderValues.reduce((sum, valueItem) => sum + valueItem, 0) / olderValues.length;

        const changePercentage = ((olderAvg - recentAvg) / olderAvg) * 100;

        if (changePercentage > 5) {
            return { direction: 'improving', percentage: changePercentage.toFixed(1) };
        } else if (changePercentage < -5) {
            return { direction: 'declining', percentage: Math.abs(changePercentage).toFixed(1) };
        }
        return { direction: 'stable', percentage: Math.abs(changePercentage).toFixed(1) };
    }

    /**
     * Generate Markdown report
     *
     * @param {Array} results Results array
     * @returns {string} Markdown content
     */
    generateMarkdown(results) {
        const timestamp = new Date().toISOString().split('T')[0];
        const stats = this.calculateAggregateStats(results);

        let markdown = `# VBP Benchmark Results

> Generated: ${timestamp}
> Total Runs: ${results.length}

## Executive Summary

`;

        // Summary table
        markdown += `| Benchmark | Runs | Avg Time | Best Time | Avg Score | Trend |\n`;
        markdown += `|-----------|------|----------|-----------|-----------|-------|\n`;

        if (stats) {
            Object.entries(stats).forEach(([benchmarkId, benchmarkStats]) => {
                const trendIcon = benchmarkStats.trend.direction === 'improving' ? 'up' :
                    benchmarkStats.trend.direction === 'declining' ? 'down' : 'right';
                const trendEmoji = benchmarkStats.trend.direction === 'improving' ? 'Improving' :
                    benchmarkStats.trend.direction === 'declining' ? 'Declining' : 'Stable';

                markdown += `| ${benchmarkStats.benchmarkName} | ${benchmarkStats.runs} | ${benchmarkStats.time.avg.toFixed(1)}s | ${benchmarkStats.time.min.toFixed(1)}s | ${benchmarkStats.score.avg.toFixed(0)}/100 | ${trendEmoji} |\n`;
            });
        }

        // Competitor comparison
        markdown += `\n## Competitor Comparison\n\n`;

        Object.entries(BENCHMARKS).forEach(([benchmarkId, benchmark]) => {
            const benchmarkResults = results.filter(resultItem => resultItem.benchmarkId === benchmarkId);
            if (benchmarkResults.length === 0) return;

            const avgTime = benchmarkResults.reduce((sum, resultItem) => sum + resultItem.vbp.time, 0) / benchmarkResults.length;

            markdown += `### ${benchmark.name}\n\n`;
            markdown += `VBP Average: **${avgTime.toFixed(1)}s**\n\n`;
            markdown += `| Competitor | Their Time | Difference | VBP Faster? |\n`;
            markdown += `|------------|------------|------------|-------------|\n`;

            Object.entries(COMPETITORS).forEach(([competitorId, competitor]) => {
                const competitorBenchmark = competitor.benchmarks[benchmarkId];
                if (!competitorBenchmark) return;

                const timeDiff = competitorBenchmark.avgTime - avgTime;
                const percentage = ((timeDiff / competitorBenchmark.avgTime) * 100).toFixed(1);
                const status = timeDiff > 0 ? 'Yes' : 'No';
                const diffText = timeDiff > 0 ? `-${timeDiff.toFixed(0)}s` : `+${Math.abs(timeDiff).toFixed(0)}s`;

                markdown += `| ${competitor.name} | ${competitorBenchmark.avgTime}s | ${diffText} (${percentage}%) | ${status} |\n`;
            });

            markdown += `\n`;
        });

        // Best runs
        markdown += `## Personal Bests\n\n`;
        markdown += `| Benchmark | Best Time | Date | Score |\n`;
        markdown += `|-----------|-----------|------|-------|\n`;

        if (stats) {
            Object.entries(stats).forEach(([benchmarkId, benchmarkStats]) => {
                const bestRun = benchmarkStats.bestRun;
                const date = new Date(bestRun.timestamp).toLocaleDateString();
                markdown += `| ${benchmarkStats.benchmarkName} | ${bestRun.vbp.time.toFixed(1)}s | ${date} | ${bestRun.scores.overall.toFixed(0)}/100 |\n`;
            });
        }

        // Recommendations
        if (this.options.includeRecommendations) {
            markdown += `\n## Recommendations\n\n`;

            const recommendations = this.generateRecommendations(stats);
            recommendations.forEach((rec, index) => {
                markdown += `${index + 1}. **${rec.title}**\n`;
                markdown += `   ${rec.description}\n\n`;
            });
        }

        // Learning curve analysis
        markdown += `## Learning Curve Analysis\n\n`;
        markdown += `Based on ${results.length} runs, here's the learning progression:\n\n`;

        if (results.length >= 3) {
            const firstThird = results.slice(-Math.ceil(results.length / 3));
            const lastThird = results.slice(0, Math.ceil(results.length / 3));

            const firstAvg = firstThird.reduce((sum, resultItem) => sum + resultItem.vbp.time, 0) / firstThird.length;
            const lastAvg = lastThird.reduce((sum, resultItem) => sum + resultItem.vbp.time, 0) / lastThird.length;

            const improvementPercentage = ((firstAvg - lastAvg) / firstAvg * 100).toFixed(1);

            markdown += `- First runs average: ${firstAvg.toFixed(1)}s\n`;
            markdown += `- Recent runs average: ${lastAvg.toFixed(1)}s\n`;
            markdown += `- Improvement: **${improvementPercentage}%**\n`;
        } else {
            markdown += `*Need at least 3 runs for learning curve analysis*\n`;
        }

        markdown += `\n---\n\n*Report generated by VBP Benchmark Suite*\n`;

        return markdown;
    }

    /**
     * Generate HTML report
     *
     * @param {Array} results Results array
     * @returns {string} HTML content
     */
    generateHTML(results) {
        const timestamp = new Date().toISOString().split('T')[0];
        const stats = this.calculateAggregateStats(results);

        let html = `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VBP Benchmark Results - ${timestamp}</title>
    <style>
        :root {
            --primary: #3b82f6;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1f2937;
            --light: #f3f4f6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--dark);
            color: #e5e7eb;
            line-height: 1.6;
            padding: 2rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #9ca3af;
            margin-bottom: 2rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: #111827;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .card-title {
            font-size: 0.875rem;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1rem;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #fff;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }

        th, td {
            text-align: left;
            padding: 1rem;
            border-bottom: 1px solid #374151;
        }

        th {
            background: #111827;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-success { background: rgba(34, 197, 94, 0.2); color: var(--success); }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: var(--warning); }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: var(--danger); }

        .progress-bar {
            height: 8px;
            background: #374151;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary), var(--success));
            border-radius: 4px;
        }

        .chart-container {
            height: 200px;
            background: #111827;
            border-radius: 8px;
            display: flex;
            align-items: flex-end;
            padding: 1rem;
            gap: 4px;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, var(--primary), var(--success));
            border-radius: 4px 4px 0 0;
            transition: height 0.3s ease;
        }

        footer {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid #374151;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>VBP Benchmark Results</h1>
        <p class="subtitle">Generated: ${timestamp} | Total Runs: ${results.length}</p>

        <div class="grid">
`;

        // Summary cards
        if (stats) {
            const allScores = [];
            const allTimes = [];

            Object.values(stats).forEach(benchmarkStats => {
                allScores.push(benchmarkStats.score.avg);
                allTimes.push(benchmarkStats.time.avg);
            });

            const avgScore = allScores.reduce((sum, scoreValue) => sum + scoreValue, 0) / allScores.length;
            const avgTime = allTimes.reduce((sum, timeValue) => sum + timeValue, 0) / allTimes.length;

            html += `
            <div class="card">
                <div class="card-title">Average Score</div>
                <div class="stat-value">${avgScore.toFixed(0)}</div>
                <div class="stat-label">out of 100</div>
            </div>
            <div class="card">
                <div class="card-title">Average Time</div>
                <div class="stat-value">${avgTime.toFixed(1)}s</div>
                <div class="stat-label">per benchmark</div>
            </div>
            <div class="card">
                <div class="card-title">Total Runs</div>
                <div class="stat-value">${results.length}</div>
                <div class="stat-label">benchmarks executed</div>
            </div>
`;
        }

        html += `
        </div>

        <div class="card">
            <div class="card-title">Results by Benchmark</div>
            <table>
                <thead>
                    <tr>
                        <th>Benchmark</th>
                        <th>Runs</th>
                        <th>Avg Time</th>
                        <th>Best Time</th>
                        <th>Score</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
`;

        if (stats) {
            Object.entries(stats).forEach(([benchmarkId, benchmarkStats]) => {
                const scoreClass = benchmarkStats.score.avg >= 75 ? 'success' :
                    benchmarkStats.score.avg >= 50 ? 'warning' : 'danger';

                html += `
                    <tr>
                        <td><strong>${benchmarkStats.benchmarkName}</strong></td>
                        <td>${benchmarkStats.runs}</td>
                        <td>${benchmarkStats.time.avg.toFixed(1)}s</td>
                        <td>${benchmarkStats.time.min.toFixed(1)}s</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div class="progress-bar" style="width: 100px;">
                                    <div class="progress-fill" style="width: ${benchmarkStats.score.avg}%;"></div>
                                </div>
                                <span>${benchmarkStats.score.avg.toFixed(0)}</span>
                            </div>
                        </td>
                        <td><span class="badge badge-${scoreClass}">${scoreClass.toUpperCase()}</span></td>
                    </tr>
`;
            });
        }

        html += `
                </tbody>
            </table>
        </div>

        <div class="card">
            <div class="card-title">Competitor Comparison</div>
            <table>
                <thead>
                    <tr>
                        <th>Benchmark</th>
                        <th>VBP</th>
`;

        Object.values(COMPETITORS).forEach(competitor => {
            html += `<th>${competitor.name}</th>`;
        });

        html += `
                    </tr>
                </thead>
                <tbody>
`;

        Object.entries(BENCHMARKS).forEach(([benchmarkId, benchmark]) => {
            const benchmarkResults = results.filter(resultItem => resultItem.benchmarkId === benchmarkId);
            if (benchmarkResults.length === 0) return;

            const avgTime = benchmarkResults.reduce((sum, resultItem) => sum + resultItem.vbp.time, 0) / benchmarkResults.length;

            html += `<tr><td>${benchmark.name}</td><td><strong>${avgTime.toFixed(1)}s</strong></td>`;

            Object.entries(COMPETITORS).forEach(([competitorId, competitor]) => {
                const competitorBenchmark = competitor.benchmarks[benchmarkId];
                if (competitorBenchmark) {
                    const diff = competitorBenchmark.avgTime - avgTime;
                    const colorClass = diff > 0 ? 'success' : 'danger';
                    html += `<td style="color: var(--${colorClass})">${competitorBenchmark.avgTime}s (${diff > 0 ? '-' : '+'}${Math.abs(diff).toFixed(0)}s)</td>`;
                } else {
                    html += `<td>-</td>`;
                }
            });

            html += `</tr>`;
        });

        html += `
                </tbody>
            </table>
        </div>

        <footer>
            <p>VBP Benchmark Suite | Visual Builder Pro Performance Testing</p>
        </footer>
    </div>
</body>
</html>`;

        return html;
    }

    /**
     * Generate recommendations based on stats
     *
     * @param {Object} stats Aggregate statistics
     * @returns {Array} Recommendations
     */
    generateRecommendations(stats) {
        const recommendations = [];

        if (!stats) return recommendations;

        Object.entries(stats).forEach(([benchmarkId, benchmarkStats]) => {
            // Low score recommendation
            if (benchmarkStats.score.avg < 60) {
                recommendations.push({
                    priority: 'high',
                    title: `Improve ${benchmarkStats.benchmarkName}`,
                    description: `Average score is ${benchmarkStats.score.avg.toFixed(0)}/100. Consider practicing this benchmark or reviewing VBP shortcuts.`
                });
            }

            // High variance recommendation
            const variance = benchmarkStats.time.max - benchmarkStats.time.min;
            if (variance > benchmarkStats.time.avg * 0.5) {
                recommendations.push({
                    priority: 'medium',
                    title: `Reduce inconsistency in ${benchmarkStats.benchmarkName}`,
                    description: `Time variance is high (${variance.toFixed(0)}s). Focus on consistent workflows.`
                });
            }

            // Declining trend
            if (benchmarkStats.trend.direction === 'declining') {
                recommendations.push({
                    priority: 'high',
                    title: `Address declining performance in ${benchmarkStats.benchmarkName}`,
                    description: `Performance has declined ${benchmarkStats.trend.percentage}%. Review recent changes.`
                });
            }
        });

        // Sort by priority
        const priorityOrder = { high: 0, medium: 1, low: 2 };
        recommendations.sort((recA, recB) => priorityOrder[recA.priority] - priorityOrder[recB.priority]);

        return recommendations.slice(0, 5);
    }

    /**
     * Save report to file
     *
     * @param {string} content Report content
     * @param {string} format File format (md, html, json)
     * @returns {string} File path
     */
    saveReport(content, format = 'md') {
        const timestamp = Date.now();
        const fileName = `benchmark-report-${timestamp}.${format}`;
        const filePath = path.join(this.options.outputDir, fileName);

        fs.writeFileSync(filePath, content);

        return filePath;
    }

    /**
     * Generate all reports
     *
     * @returns {Object} Generated file paths
     */
    generateAllReports() {
        const results = this.loadResults();

        if (results.length === 0) {
            console.log('No benchmark results found.');
            return null;
        }

        const paths = {};

        // Markdown
        const markdownContent = this.generateMarkdown(results);
        paths.markdown = this.saveReport(markdownContent, 'md');

        // Also save as BENCHMARK-RESULTS.md for easy access
        fs.writeFileSync(
            path.join(this.options.outputDir, 'BENCHMARK-RESULTS.md'),
            markdownContent
        );

        // HTML
        const htmlContent = this.generateHTML(results);
        paths.html = this.saveReport(htmlContent, 'html');

        // JSON summary
        const stats = this.calculateAggregateStats(results);
        const jsonContent = JSON.stringify({
            generatedAt: new Date().toISOString(),
            totalRuns: results.length,
            statistics: stats,
            recommendations: this.generateRecommendations(stats)
        }, null, 2);
        paths.json = this.saveReport(jsonContent, 'json');

        return paths;
    }
}

/**
 * CLI execution
 */
if (require.main === module) {
    const generator = new BenchmarkReportGenerator();
    const paths = generator.generateAllReports();

    if (paths) {
        console.log('Reports generated:');
        console.log(`  Markdown: ${paths.markdown}`);
        console.log(`  HTML: ${paths.html}`);
        console.log(`  JSON: ${paths.json}`);
    }
}

module.exports = { BenchmarkReportGenerator };
