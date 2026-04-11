/**
 * VBP Performance Baseline
 * Sistema de medicion de rendimiento del editor y paginas generadas
 *
 * @package Flavor_Platform
 * @since 2.3.0
 */

'use strict';

/**
 * Definicion de metricas con umbrales
 */
const METRICS_DEFINITION = {
    // Metricas del Editor
    editor: {
        loadTime: {
            name: 'Tiempo de carga del editor',
            unit: 'ms',
            thresholds: { good: 2000, acceptable: 4000, poor: 6000 },
            description: 'Tiempo desde navegacion hasta DOMContentLoaded'
        },
        tti: {
            name: 'Time to Interactive',
            unit: 'ms',
            thresholds: { good: 3000, acceptable: 5000, poor: 8000 },
            description: 'Tiempo hasta que el editor es completamente interactivo'
        },
        memoryInitial: {
            name: 'Memoria inicial',
            unit: 'MB',
            thresholds: { good: 50, acceptable: 100, poor: 200 },
            description: 'Uso de memoria JS heap al cargar'
        },
        memoryPeak: {
            name: 'Memoria pico',
            unit: 'MB',
            thresholds: { good: 100, acceptable: 200, poor: 400 },
            description: 'Uso maximo de memoria durante operacion'
        },
        fpsDrag: {
            name: 'FPS durante drag',
            unit: 'fps',
            thresholds: { good: 55, acceptable: 45, poor: 30 },
            description: 'Frames por segundo durante arrastrar elementos'
        },
        fpsIdle: {
            name: 'FPS en reposo',
            unit: 'fps',
            thresholds: { good: 58, acceptable: 50, poor: 40 },
            description: 'Frames por segundo sin actividad'
        },
        renderTime: {
            name: 'Tiempo de render',
            unit: 'ms',
            thresholds: { good: 16, acceptable: 33, poor: 100 },
            description: 'Tiempo promedio de render de frame'
        },
        saveTime: {
            name: 'Tiempo de guardado',
            unit: 'ms',
            thresholds: { good: 500, acceptable: 1500, poor: 3000 },
            description: 'Tiempo para guardar cambios'
        }
    },

    // Metricas de Output (pagina generada)
    output: {
        ttfb: {
            name: 'Time to First Byte',
            unit: 'ms',
            thresholds: { good: 200, acceptable: 500, poor: 1000 },
            description: 'Tiempo hasta recibir primer byte del servidor'
        },
        fcp: {
            name: 'First Contentful Paint',
            unit: 'ms',
            thresholds: { good: 1800, acceptable: 3000, poor: 5000 },
            description: 'Tiempo hasta primer contenido visible'
        },
        lcp: {
            name: 'Largest Contentful Paint',
            unit: 'ms',
            thresholds: { good: 2500, acceptable: 4000, poor: 6000 },
            description: 'Tiempo hasta mayor elemento visible'
        },
        cls: {
            name: 'Cumulative Layout Shift',
            unit: 'score',
            thresholds: { good: 0.1, acceptable: 0.25, poor: 0.5 },
            description: 'Puntuacion de cambios de layout'
        },
        fid: {
            name: 'First Input Delay',
            unit: 'ms',
            thresholds: { good: 100, acceptable: 300, poor: 500 },
            description: 'Retraso de primera interaccion'
        },
        inp: {
            name: 'Interaction to Next Paint',
            unit: 'ms',
            thresholds: { good: 200, acceptable: 500, poor: 1000 },
            description: 'Latencia de interacciones'
        },
        domSize: {
            name: 'Tamano del DOM',
            unit: 'nodes',
            thresholds: { good: 800, acceptable: 1500, poor: 3000 },
            description: 'Numero total de nodos DOM'
        },
        domDepth: {
            name: 'Profundidad DOM',
            unit: 'levels',
            thresholds: { good: 15, acceptable: 25, poor: 40 },
            description: 'Maxima profundidad de anidamiento'
        },
        cssSize: {
            name: 'Tamano CSS',
            unit: 'KB',
            thresholds: { good: 50, acceptable: 100, poor: 200 },
            description: 'Tamano total de CSS transferido'
        },
        jsSize: {
            name: 'Tamano JS',
            unit: 'KB',
            thresholds: { good: 100, acceptable: 200, poor: 400 },
            description: 'Tamano total de JS transferido'
        },
        imageSize: {
            name: 'Tamano imagenes',
            unit: 'KB',
            thresholds: { good: 500, acceptable: 1000, poor: 2000 },
            description: 'Tamano total de imagenes transferidas'
        },
        totalRequests: {
            name: 'Total requests',
            unit: 'count',
            thresholds: { good: 30, acceptable: 60, poor: 100 },
            description: 'Numero total de peticiones HTTP'
        }
    },

    // Escalabilidad
    scalability: {
        elements10: {
            name: '10 elementos',
            unit: 'ms',
            thresholds: { good: 50, acceptable: 100, poor: 200 },
            description: 'Tiempo de render con 10 elementos'
        },
        elements50: {
            name: '50 elementos',
            unit: 'ms',
            thresholds: { good: 150, acceptable: 300, poor: 500 },
            description: 'Tiempo de render con 50 elementos'
        },
        elements100: {
            name: '100 elementos',
            unit: 'ms',
            thresholds: { good: 300, acceptable: 600, poor: 1000 },
            description: 'Tiempo de render con 100 elementos'
        },
        elements500: {
            name: '500 elementos',
            unit: 'ms',
            thresholds: { good: 1000, acceptable: 2000, poor: 4000 },
            description: 'Tiempo de render con 500 elementos'
        }
    }
};

/**
 * Evaluador de metricas
 */
function evaluateMetric(metricKey, value, category) {
    const metricDefinition = METRICS_DEFINITION[category]?.[metricKey];
    if (!metricDefinition) {
        return { status: 'unknown', score: 0 };
    }

    const { thresholds } = metricDefinition;

    // Para CLS menor es mejor
    const isLowerBetter = metricKey === 'cls' ||
                          metricKey.includes('Time') ||
                          metricKey.includes('Size') ||
                          metricKey === 'domSize' ||
                          metricKey === 'domDepth' ||
                          metricKey === 'totalRequests' ||
                          metricKey.includes('elements');

    // Para FPS mayor es mejor
    const isHigherBetter = metricKey.includes('fps');

    let status;
    let score;

    if (isHigherBetter) {
        if (value >= thresholds.good) {
            status = 'good';
            score = 100;
        } else if (value >= thresholds.acceptable) {
            status = 'acceptable';
            score = 50 + 50 * (value - thresholds.acceptable) / (thresholds.good - thresholds.acceptable);
        } else if (value >= thresholds.poor) {
            status = 'poor';
            score = 50 * (value - thresholds.poor) / (thresholds.acceptable - thresholds.poor);
        } else {
            status = 'critical';
            score = 0;
        }
    } else {
        if (value <= thresholds.good) {
            status = 'good';
            score = 100;
        } else if (value <= thresholds.acceptable) {
            status = 'acceptable';
            score = 50 + 50 * (thresholds.acceptable - value) / (thresholds.acceptable - thresholds.good);
        } else if (value <= thresholds.poor) {
            status = 'poor';
            score = 50 * (thresholds.poor - value) / (thresholds.poor - thresholds.acceptable);
        } else {
            status = 'critical';
            score = 0;
        }
    }

    return {
        status,
        score: Math.max(0, Math.min(100, Math.round(score))),
        thresholds,
        isHigherBetter
    };
}

/**
 * Obtener emoji de estado
 */
function getStatusEmoji(status) {
    const emojiMap = {
        good: '\u{1F7E2}',      // Green circle
        acceptable: '\u{1F7E1}', // Yellow circle
        poor: '\u{1F7E0}',       // Orange circle
        critical: '\u{1F534}',   // Red circle
        unknown: '\u{26AA}'      // White circle
    };
    return emojiMap[status] || emojiMap.unknown;
}

/**
 * Formatear valor con unidad
 */
function formatValue(value, unit) {
    if (typeof value !== 'number' || isNaN(value)) {
        return 'N/A';
    }

    switch (unit) {
        case 'ms':
            return value < 1000 ? `${Math.round(value)}ms` : `${(value / 1000).toFixed(2)}s`;
        case 'MB':
            return `${value.toFixed(1)}MB`;
        case 'KB':
            return value < 1024 ? `${Math.round(value)}KB` : `${(value / 1024).toFixed(2)}MB`;
        case 'fps':
            return `${Math.round(value)} fps`;
        case 'score':
            return value.toFixed(3);
        case 'nodes':
        case 'levels':
        case 'count':
            return Math.round(value).toString();
        default:
            return value.toString();
    }
}

/**
 * Generar reporte de baseline
 */
function generateBaselineReport(results) {
    const timestamp = new Date().toISOString();
    const report = {
        meta: {
            timestamp,
            version: '1.0.0',
            userAgent: typeof navigator !== 'undefined' ? navigator.userAgent : 'Node.js'
        },
        summary: {
            overallScore: 0,
            totalMetrics: 0,
            goodMetrics: 0,
            acceptableMetrics: 0,
            poorMetrics: 0,
            criticalMetrics: 0
        },
        categories: {},
        recommendations: []
    };

    let totalScore = 0;
    let metricCount = 0;

    // Procesar cada categoria
    for (const [categoryKey, categoryMetrics] of Object.entries(results)) {
        if (!METRICS_DEFINITION[categoryKey]) continue;

        report.categories[categoryKey] = {
            name: getCategoryName(categoryKey),
            metrics: {},
            categoryScore: 0
        };

        let categoryScore = 0;
        let categoryMetricCount = 0;

        for (const [metricKey, metricValue] of Object.entries(categoryMetrics)) {
            const definition = METRICS_DEFINITION[categoryKey][metricKey];
            if (!definition) continue;

            const evaluation = evaluateMetric(metricKey, metricValue, categoryKey);

            report.categories[categoryKey].metrics[metricKey] = {
                name: definition.name,
                value: metricValue,
                formattedValue: formatValue(metricValue, definition.unit),
                unit: definition.unit,
                ...evaluation,
                description: definition.description
            };

            categoryScore += evaluation.score;
            categoryMetricCount++;
            totalScore += evaluation.score;
            metricCount++;

            // Actualizar contadores de resumen
            report.summary.totalMetrics++;
            switch (evaluation.status) {
                case 'good':
                    report.summary.goodMetrics++;
                    break;
                case 'acceptable':
                    report.summary.acceptableMetrics++;
                    break;
                case 'poor':
                    report.summary.poorMetrics++;
                    addRecommendation(report, categoryKey, metricKey, metricValue, definition, 'poor');
                    break;
                case 'critical':
                    report.summary.criticalMetrics++;
                    addRecommendation(report, categoryKey, metricKey, metricValue, definition, 'critical');
                    break;
            }
        }

        if (categoryMetricCount > 0) {
            report.categories[categoryKey].categoryScore = Math.round(categoryScore / categoryMetricCount);
        }
    }

    if (metricCount > 0) {
        report.summary.overallScore = Math.round(totalScore / metricCount);
    }

    return report;
}

/**
 * Obtener nombre de categoria
 */
function getCategoryName(categoryKey) {
    const names = {
        editor: 'Metricas del Editor',
        output: 'Metricas de Pagina Generada',
        scalability: 'Escalabilidad'
    };
    return names[categoryKey] || categoryKey;
}

/**
 * Agregar recomendacion
 */
function addRecommendation(report, categoryKey, metricKey, value, definition, severity) {
    const recommendations = {
        loadTime: 'Optimizar carga inicial: lazy load de componentes, reducir JS bloqueante',
        tti: 'Diferir scripts no criticos, usar web workers para tareas pesadas',
        memoryInitial: 'Reducir datos iniciales, implementar paginacion',
        memoryPeak: 'Implementar limpieza de memoria, evitar referencias circulares',
        fpsDrag: 'Optimizar event handlers, usar requestAnimationFrame, reducir reflows',
        renderTime: 'Minimizar re-renders, usar virtualizacion para listas largas',
        saveTime: 'Implementar guardado incremental, comprimir datos',
        ttfb: 'Optimizar servidor: cache, CDN, reducir procesamiento backend',
        fcp: 'Inline CSS critico, preload de fuentes, optimizar ruta critica',
        lcp: 'Optimizar imagen principal, preload de recursos LCP',
        cls: 'Definir dimensiones de imagenes/ads, reservar espacio para contenido dinamico',
        fid: 'Dividir tareas JS largas, usar web workers',
        inp: 'Optimizar event handlers, reducir trabajo en main thread',
        domSize: 'Reducir elementos DOM, usar virtualizacion',
        domDepth: 'Aplanar estructura HTML, evitar anidamiento excesivo',
        cssSize: 'Purgar CSS no usado, minificar, usar critical CSS',
        jsSize: 'Code splitting, tree shaking, lazy loading de modulos',
        imageSize: 'Comprimir imagenes, usar formatos modernos (WebP, AVIF)',
        totalRequests: 'Combinar recursos, usar HTTP/2, implementar cache',
        elements10: 'Revisar rendimiento base del editor',
        elements50: 'Implementar virtualizacion temprana',
        elements100: 'Necesaria virtualizacion de elementos',
        elements500: 'Considerar paginacion o division de contenido'
    };

    const priority = severity === 'critical' ? 'high' : 'medium';

    report.recommendations.push({
        metric: metricKey,
        category: categoryKey,
        severity,
        priority,
        title: `${definition.name} ${severity === 'critical' ? 'critico' : 'bajo'}`,
        currentValue: formatValue(value, definition.unit),
        targetValue: formatValue(definition.thresholds.good, definition.unit),
        recommendation: recommendations[metricKey] || 'Revisar y optimizar esta metrica'
    });
}

/**
 * Generar markdown del reporte
 */
function generateMarkdownReport(report) {
    let markdown = `# VBP Performance Baseline

> Generado: ${report.meta.timestamp}
> Version: ${report.meta.version}

## Resumen Ejecutivo

| Metrica | Valor |
|---------|-------|
| **Puntuacion Global** | ${report.summary.overallScore}/100 |
| Total de metricas | ${report.summary.totalMetrics} |
| ${getStatusEmoji('good')} Buenas | ${report.summary.goodMetrics} |
| ${getStatusEmoji('acceptable')} Aceptables | ${report.summary.acceptableMetrics} |
| ${getStatusEmoji('poor')} Pobres | ${report.summary.poorMetrics} |
| ${getStatusEmoji('critical')} Criticas | ${report.summary.criticalMetrics} |

`;

    // Agregar cada categoria
    for (const [categoryKey, category] of Object.entries(report.categories)) {
        markdown += `## ${category.name}

**Puntuacion de categoria:** ${category.categoryScore}/100

| Metrica | Valor | Estado | Umbral Bueno |
|---------|-------|--------|--------------|
`;

        for (const [metricKey, metric] of Object.entries(category.metrics)) {
            const statusEmoji = getStatusEmoji(metric.status);
            const thresholdDisplay = metric.isHigherBetter
                ? `>= ${formatValue(metric.thresholds.good, metric.unit)}`
                : `<= ${formatValue(metric.thresholds.good, metric.unit)}`;

            markdown += `| ${metric.name} | ${metric.formattedValue} | ${statusEmoji} | ${thresholdDisplay} |\n`;
        }

        markdown += '\n';
    }

    // Recomendaciones
    if (report.recommendations.length > 0) {
        markdown += `## Recomendaciones de Optimizacion

`;
        // Ordenar por prioridad
        const sortedRecs = report.recommendations.sort((a, b) => {
            const priorityOrder = { high: 0, medium: 1, low: 2 };
            return priorityOrder[a.priority] - priorityOrder[b.priority];
        });

        for (const rec of sortedRecs) {
            const priorityEmoji = rec.priority === 'high' ? '\u{1F534}' : '\u{1F7E1}';
            markdown += `### ${priorityEmoji} ${rec.title}

- **Metrica:** ${rec.metric}
- **Valor actual:** ${rec.currentValue}
- **Objetivo:** ${rec.targetValue}
- **Accion:** ${rec.recommendation}

`;
        }
    }

    markdown += `---

*Reporte generado automaticamente por VBP Performance Baseline v${report.meta.version}*
`;

    return markdown;
}

// Exportar para uso en Node.js y browser
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        METRICS_DEFINITION,
        evaluateMetric,
        formatValue,
        getStatusEmoji,
        generateBaselineReport,
        generateMarkdownReport
    };
}

if (typeof window !== 'undefined') {
    window.VBPPerformanceBaseline = {
        METRICS_DEFINITION,
        evaluateMetric,
        formatValue,
        getStatusEmoji,
        generateBaselineReport,
        generateMarkdownReport
    };
}
