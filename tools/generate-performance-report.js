#!/usr/bin/env node
/**
 * VBP Performance Report Generator
 * Genera reportes de rendimiento a partir de resultados de Lighthouse y tests
 *
 * Uso: node tools/generate-performance-report.js [--input DIR] [--output FILE]
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

'use strict';

const fs = require('fs');
const path = require('path');

// Importar modulos de metricas
const { METRICS_DEFINITION, generateBaselineReport, generateMarkdownReport } = require('./performance-baseline.js');

// Configuracion por defecto
const DEFAULT_INPUT_DIR = path.join(__dirname, '..', 'lighthouse-results');
const DEFAULT_OUTPUT_FILE = path.join(__dirname, '..', 'PERFORMANCE-BASELINE.md');

/**
 * Parsear argumentos de linea de comandos
 */
function parseArgs() {
    const args = {
        inputDir: DEFAULT_INPUT_DIR,
        outputFile: DEFAULT_OUTPUT_FILE,
        format: 'markdown',
        verbose: false
    };

    for (let i = 2; i < process.argv.length; i++) {
        const arg = process.argv[i];

        if (arg === '--input' || arg === '-i') {
            args.inputDir = process.argv[++i];
        } else if (arg === '--output' || arg === '-o') {
            args.outputFile = process.argv[++i];
        } else if (arg === '--format' || arg === '-f') {
            args.format = process.argv[++i];
        } else if (arg === '--verbose' || arg === '-v') {
            args.verbose = true;
        } else if (arg === '--help' || arg === '-h') {
            showHelp();
            process.exit(0);
        }
    }

    return args;
}

/**
 * Mostrar ayuda
 */
function showHelp() {
    console.log(`
VBP Performance Report Generator

Uso: node generate-performance-report.js [opciones]

Opciones:
  -i, --input DIR     Directorio con resultados de Lighthouse (default: lighthouse-results/)
  -o, --output FILE   Archivo de salida (default: PERFORMANCE-BASELINE.md)
  -f, --format FMT    Formato: markdown, json, html (default: markdown)
  -v, --verbose       Mostrar informacion detallada
  -h, --help          Mostrar esta ayuda

Ejemplos:
  node generate-performance-report.js
  node generate-performance-report.js -i ./results -o ./report.md
  node generate-performance-report.js --format json -o metrics.json
`);
}

/**
 * Leer archivos JSON de un directorio
 */
function readJsonFiles(directoryPath) {
    const files = [];

    if (!fs.existsSync(directoryPath)) {
        console.error(`Error: Directorio no encontrado: ${directoryPath}`);
        return files;
    }

    const entries = fs.readdirSync(directoryPath);

    for (const entry of entries) {
        if (entry.endsWith('.json')) {
            const filePath = path.join(directoryPath, entry);
            try {
                const content = fs.readFileSync(filePath, 'utf8');
                const data = JSON.parse(content);
                files.push({ name: entry, data });
            } catch (error) {
                console.error(`Error leyendo ${entry}:`, error.message);
            }
        }
    }

    return files;
}

/**
 * Extraer metricas de resultados de Lighthouse
 */
function extractLighthouseMetrics(lighthouseData) {
    const lhr = lighthouseData.lhr || lighthouseData;

    if (!lhr.audits) {
        return null;
    }

    const audits = lhr.audits;

    return {
        url: lhr.requestedUrl || lhr.finalUrl || 'unknown',
        performanceScore: lhr.categories?.performance?.score * 100 || 0,
        ttfb: audits['server-response-time']?.numericValue || 0,
        fcp: audits['first-contentful-paint']?.numericValue || 0,
        lcp: audits['largest-contentful-paint']?.numericValue || 0,
        cls: audits['cumulative-layout-shift']?.numericValue || 0,
        fid: audits['max-potential-fid']?.numericValue || 0,
        tbt: audits['total-blocking-time']?.numericValue || 0,
        si: audits['speed-index']?.numericValue || 0,
        tti: audits['interactive']?.numericValue || 0,
        domSize: audits['dom-size']?.numericValue || 0,
        totalByteWeight: audits['total-byte-weight']?.numericValue || 0,
        bootupTime: audits['bootup-time']?.numericValue || 0,
        mainThreadTime: audits['mainthread-work-breakdown']?.numericValue || 0
    };
}

/**
 * Calcular estadisticas de multiples ejecuciones
 */
function calculateStats(values) {
    if (!values || values.length === 0) {
        return { min: 0, max: 0, avg: 0, median: 0 };
    }

    const sorted = [...values].sort((a, b) => a - b);
    const sum = values.reduce((accumulator, value) => accumulator + value, 0);

    return {
        min: sorted[0],
        max: sorted[sorted.length - 1],
        avg: sum / values.length,
        median: sorted[Math.floor(sorted.length / 2)]
    };
}

/**
 * Agregar metricas por URL
 */
function aggregateMetrics(jsonFiles) {
    const byUrl = {};

    for (const file of jsonFiles) {
        const metrics = extractLighthouseMetrics(file.data);
        if (!metrics) continue;

        const url = metrics.url;
        if (!byUrl[url]) {
            byUrl[url] = {
                url,
                runs: [],
                metrics: {}
            };
        }

        byUrl[url].runs.push(metrics);
    }

    // Calcular estadisticas para cada URL
    for (const urlData of Object.values(byUrl)) {
        const metricNames = Object.keys(urlData.runs[0] || {}).filter(k => k !== 'url');

        for (const metricName of metricNames) {
            const values = urlData.runs.map(run => run[metricName]).filter(v => typeof v === 'number');
            urlData.metrics[metricName] = calculateStats(values);
        }
    }

    return byUrl;
}

/**
 * Generar reporte en formato Markdown
 */
function generateMarkdownFromAggregated(aggregatedData) {
    const timestamp = new Date().toISOString();

    let markdown = `# VBP Performance Baseline

> Generado: ${timestamp}
> Archivos procesados: ${Object.keys(aggregatedData).length} URLs

## Resumen por URL

`;

    // Tabla de resumen
    markdown += `| URL | Score | LCP | CLS | TBT | Ejecuciones |
|-----|-------|-----|-----|-----|-------------|
`;

    for (const [url, data] of Object.entries(aggregatedData)) {
        const shortUrl = url.replace(/https?:\/\/[^/]+/, '').slice(0, 30) || '/';
        const score = data.metrics.performanceScore?.avg?.toFixed(0) || 'N/A';
        const lcp = data.metrics.lcp?.median ? `${Math.round(data.metrics.lcp.median)}ms` : 'N/A';
        const cls = data.metrics.cls?.median?.toFixed(3) || 'N/A';
        const tbt = data.metrics.tbt?.median ? `${Math.round(data.metrics.tbt.median)}ms` : 'N/A';

        markdown += `| ${shortUrl} | ${score} | ${lcp} | ${cls} | ${tbt} | ${data.runs.length} |\n`;
    }

    // Detalle por URL
    for (const [url, data] of Object.entries(aggregatedData)) {
        markdown += `
## ${url}

### Core Web Vitals

| Metrica | Min | Mediana | Max | Estado |
|---------|-----|---------|-----|--------|
`;

        const webVitals = ['lcp', 'cls', 'fid', 'tbt'];
        const thresholds = {
            lcp: { good: 2500, poor: 4000 },
            cls: { good: 0.1, poor: 0.25 },
            fid: { good: 100, poor: 300 },
            tbt: { good: 200, poor: 600 }
        };

        for (const metric of webVitals) {
            const stats = data.metrics[metric];
            if (!stats) continue;

            const threshold = thresholds[metric];
            let status = 'N/A';
            if (threshold) {
                if (stats.median <= threshold.good) {
                    status = 'Bueno';
                } else if (stats.median <= threshold.poor) {
                    status = 'Mejorable';
                } else {
                    status = 'Pobre';
                }
            }

            const format = metric === 'cls' ? (v) => v.toFixed(4) : (v) => `${Math.round(v)}ms`;

            markdown += `| ${metric.toUpperCase()} | ${format(stats.min)} | ${format(stats.median)} | ${format(stats.max)} | ${status} |\n`;
        }

        // Otras metricas
        markdown += `
### Metricas Adicionales

| Metrica | Valor (mediana) |
|---------|-----------------|
`;

        const otherMetrics = [
            ['TTFB', 'ttfb', 'ms'],
            ['FCP', 'fcp', 'ms'],
            ['Speed Index', 'si', 'ms'],
            ['TTI', 'tti', 'ms'],
            ['DOM Size', 'domSize', ' nodos'],
            ['Total Weight', 'totalByteWeight', ' bytes'],
            ['Bootup Time', 'bootupTime', 'ms'],
            ['Main Thread', 'mainThreadTime', 'ms']
        ];

        for (const [label, key, unit] of otherMetrics) {
            const stats = data.metrics[key];
            if (!stats) continue;

            const value = unit === ' nodos' || unit === ' bytes'
                ? Math.round(stats.median)
                : `${Math.round(stats.median)}`;

            markdown += `| ${label} | ${value}${unit} |\n`;
        }
    }

    // Agregar umbrales de referencia
    markdown += `
## Umbrales de Referencia (Core Web Vitals)

| Metrica | Bueno | Mejorable | Pobre |
|---------|-------|-----------|-------|
| LCP | <= 2.5s | <= 4.0s | > 4.0s |
| CLS | <= 0.1 | <= 0.25 | > 0.25 |
| FID | <= 100ms | <= 300ms | > 300ms |
| TBT | <= 200ms | <= 600ms | > 600ms |

## Recomendaciones Generales

1. **LCP > 2.5s**: Optimizar imagen principal, usar preload, considerar CDN
2. **CLS > 0.1**: Definir dimensiones de imagenes/iframes, reservar espacio para ads
3. **TBT > 200ms**: Dividir tareas largas de JS, diferir scripts no criticos
4. **DOM > 1500 nodos**: Simplificar estructura, usar virtualizacion

---

*Reporte generado automaticamente por VBP Performance Report Generator*
`;

    return markdown;
}

/**
 * Funcion principal
 */
async function main() {
    const args = parseArgs();

    console.log('VBP Performance Report Generator');
    console.log('================================\n');

    if (args.verbose) {
        console.log('Configuracion:');
        console.log(`  Input:  ${args.inputDir}`);
        console.log(`  Output: ${args.outputFile}`);
        console.log(`  Format: ${args.format}\n`);
    }

    // Leer archivos JSON
    console.log('Leyendo archivos de resultados...');
    const jsonFiles = readJsonFiles(args.inputDir);

    if (jsonFiles.length === 0) {
        console.log('No se encontraron archivos JSON en el directorio.');
        console.log('Ejecuta primero: bash tools/run-performance-tests.sh');
        process.exit(1);
    }

    console.log(`  Encontrados: ${jsonFiles.length} archivos\n`);

    // Agregar metricas
    console.log('Procesando metricas...');
    const aggregatedData = aggregateMetrics(jsonFiles);
    const urlCount = Object.keys(aggregatedData).length;
    console.log(`  URLs procesadas: ${urlCount}\n`);

    // Generar reporte
    console.log('Generando reporte...');

    let output;
    let extension;

    switch (args.format.toLowerCase()) {
        case 'json':
            output = JSON.stringify(aggregatedData, null, 2);
            extension = '.json';
            break;

        case 'html':
            // Para HTML, generar markdown y envolver en HTML basico
            const mdContent = generateMarkdownFromAggregated(aggregatedData);
            output = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>VBP Performance Report</title>
    <style>
        body { font-family: -apple-system, sans-serif; max-width: 900px; margin: 40px auto; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
        h1, h2, h3 { color: #333; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<pre style="white-space: pre-wrap;">${mdContent}</pre>
</body>
</html>`;
            extension = '.html';
            break;

        case 'markdown':
        default:
            output = generateMarkdownFromAggregated(aggregatedData);
            extension = '.md';
            break;
    }

    // Ajustar extension del archivo de salida si es necesario
    let outputPath = args.outputFile;
    if (!outputPath.endsWith(extension)) {
        outputPath = outputPath.replace(/\.[^.]+$/, extension);
    }

    // Escribir archivo
    fs.writeFileSync(outputPath, output);
    console.log(`  Guardado: ${outputPath}\n`);

    // Mostrar resumen
    console.log('Resumen:');
    for (const [url, data] of Object.entries(aggregatedData)) {
        const score = data.metrics.performanceScore?.avg?.toFixed(0) || 'N/A';
        const status = score >= 90 ? 'OK' : score >= 50 ? 'MEJORABLE' : 'POBRE';
        console.log(`  ${url.slice(0, 50)}: ${score}/100 [${status}]`);
    }

    console.log('\nReporte generado exitosamente!');
}

// Ejecutar si es el script principal
if (require.main === module) {
    main().catch(error => {
        console.error('Error:', error.message);
        process.exit(1);
    });
}

module.exports = {
    readJsonFiles,
    extractLighthouseMetrics,
    aggregateMetrics,
    generateMarkdownFromAggregated
};
