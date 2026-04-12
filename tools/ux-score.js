/**
 * VBP UX Score Calculator
 * Calcula un score de UX basado en metricas ponderadas
 *
 * Uso:
 *   const { calculateUXScore } = require('./ux-score.js');
 *   const score = calculateUXScore(metrics);
 *
 * O via CLI:
 *   node tools/ux-score.js --metrics='{"responseTime":95,"accessibility":100}'
 */

/**
 * Pesos por categoria
 * Basados en impacto en la experiencia de usuario
 */
const CATEGORY_WEIGHTS = {
  responseTime: 0.25,      // 25% - Critico para percepcion de velocidad
  predictability: 0.20,    // 20% - Confianza del usuario
  clarity: 0.15,           // 15% - Facilidad de uso
  errorHandling: 0.15,     // 15% - Recuperacion de errores
  efficiency: 0.15,        // 15% - Productividad
  accessibility: 0.10      // 10% - Inclusividad
};

/**
 * Thresholds para metricas especificas
 */
const METRIC_THRESHOLDS = {
  clickResponseMs: {
    excellent: 50,
    good: 100,
    acceptable: 200,
    poor: 500
  },
  dragStartMs: {
    excellent: 30,
    good: 50,
    acceptable: 100,
    poor: 200
  },
  typingLagMs: {
    excellent: 10,
    good: 16,
    acceptable: 50,
    poor: 100
  },
  saveTimeMs: {
    excellent: 500,
    good: 1000,
    acceptable: 2000,
    poor: 5000
  },
  transitionDurationMs: {
    excellent: 200,
    good: 300,
    acceptable: 400,
    poor: 500
  }
};

/**
 * Convertir tiempo a score (0-100)
 * Menor tiempo = mayor score
 */
function timeToScore(valueMs, thresholds) {
  if (valueMs <= thresholds.excellent) return 100;
  if (valueMs <= thresholds.good) return 85;
  if (valueMs <= thresholds.acceptable) return 70;
  if (valueMs <= thresholds.poor) return 50;
  return 30;
}

/**
 * Calcular score de tiempo de respuesta
 */
function calculateResponseTimeScore(metrics) {
  const scores = [];

  if (metrics.clickResponseMs !== undefined) {
    scores.push(timeToScore(metrics.clickResponseMs, METRIC_THRESHOLDS.clickResponseMs));
  }
  if (metrics.dragStartMs !== undefined) {
    scores.push(timeToScore(metrics.dragStartMs, METRIC_THRESHOLDS.dragStartMs));
  }
  if (metrics.typingLagMs !== undefined) {
    scores.push(timeToScore(metrics.typingLagMs, METRIC_THRESHOLDS.typingLagMs));
  }
  if (metrics.saveTimeMs !== undefined) {
    scores.push(timeToScore(metrics.saveTimeMs, METRIC_THRESHOLDS.saveTimeMs));
  }
  if (metrics.transitionDurationMs !== undefined) {
    scores.push(timeToScore(metrics.transitionDurationMs, METRIC_THRESHOLDS.transitionDurationMs));
  }

  // Si hay un score directo, usarlo
  if (metrics.responseTime !== undefined) {
    return metrics.responseTime;
  }

  // Promediar scores individuales
  if (scores.length === 0) return null;
  return scores.reduce((sum, score) => sum + score, 0) / scores.length;
}

/**
 * Calcular score de predictibilidad
 */
function calculatePredictabilityScore(metrics) {
  if (metrics.predictability !== undefined) {
    return metrics.predictability;
  }

  const scores = [];

  // Undo/Redo funcionan
  if (metrics.undoWorks !== undefined) {
    scores.push(metrics.undoWorks ? 100 : 0);
  }
  if (metrics.redoWorks !== undefined) {
    scores.push(metrics.redoWorks ? 100 : 0);
  }

  // Atajos funcionan
  if (metrics.shortcutsWorking !== undefined) {
    scores.push((metrics.shortcutsWorking / metrics.shortcutsTotal) * 100);
  }

  // Escape cancela
  if (metrics.escapeWorks !== undefined) {
    scores.push(metrics.escapeWorks ? 100 : 0);
  }

  if (scores.length === 0) return null;
  return scores.reduce((sum, score) => sum + score, 0) / scores.length;
}

/**
 * Calcular score de claridad visual
 */
function calculateClarityScore(metrics) {
  if (metrics.clarity !== undefined) {
    return metrics.clarity;
  }

  const scores = [];

  // Contraste suficiente
  if (metrics.contrastIssues !== undefined) {
    scores.push(metrics.contrastIssues === 0 ? 100 : Math.max(0, 100 - metrics.contrastIssues * 10));
  }

  // Estados visibles
  if (metrics.visibleStates !== undefined) {
    scores.push((metrics.visibleStates / metrics.totalStates) * 100);
  }

  // Jerarquia clara
  if (metrics.hierarchyScore !== undefined) {
    scores.push(metrics.hierarchyScore);
  }

  if (scores.length === 0) return null;
  return scores.reduce((sum, score) => sum + score, 0) / scores.length;
}

/**
 * Calcular score de manejo de errores
 */
function calculateErrorHandlingScore(metrics) {
  if (metrics.errorHandling !== undefined) {
    return metrics.errorHandling;
  }

  const scores = [];

  // Mensajes de error utiles
  if (metrics.helpfulErrorMessages !== undefined) {
    scores.push(metrics.helpfulErrorMessages ? 100 : 50);
  }

  // Confirmacion para destructivos
  if (metrics.destructiveConfirmation !== undefined) {
    scores.push(metrics.destructiveConfirmation ? 100 : 0);
  }

  // Autosave
  if (metrics.autosaveEnabled !== undefined) {
    scores.push(metrics.autosaveEnabled ? 100 : 50);
  }

  // Retry disponible
  if (metrics.retryAvailable !== undefined) {
    scores.push(metrics.retryAvailable ? 100 : 70);
  }

  if (scores.length === 0) return null;
  return scores.reduce((sum, score) => sum + score, 0) / scores.length;
}

/**
 * Calcular score de eficiencia
 */
function calculateEfficiencyScore(metrics) {
  if (metrics.efficiency !== undefined) {
    return metrics.efficiency;
  }

  const scores = [];

  // Clicks para tareas comunes
  if (metrics.clicksToAddBlock !== undefined) {
    // Objetivo: <= 2 clicks
    scores.push(metrics.clicksToAddBlock <= 2 ? 100 : metrics.clicksToAddBlock <= 3 ? 80 : 60);
  }

  // Command palette existe
  if (metrics.hasCommandPalette !== undefined) {
    scores.push(metrics.hasCommandPalette ? 100 : 70);
  }

  // Drag & drop funciona
  if (metrics.dragDropWorks !== undefined) {
    scores.push(metrics.dragDropWorks ? 100 : 50);
  }

  // Bulk operations
  if (metrics.bulkOperationsAvailable !== undefined) {
    scores.push(metrics.bulkOperationsAvailable ? 100 : 80);
  }

  if (scores.length === 0) return null;
  return scores.reduce((sum, score) => sum + score, 0) / scores.length;
}

/**
 * Calcular score de accesibilidad
 */
function calculateAccessibilityScore(metrics) {
  if (metrics.accessibility !== undefined) {
    return metrics.accessibility;
  }

  const scores = [];

  // Inputs con label
  if (metrics.unlabeledInputs !== undefined) {
    scores.push(metrics.unlabeledInputs === 0 ? 100 : Math.max(0, 100 - metrics.unlabeledInputs * 5));
  }

  // Focus visible
  if (metrics.focusIssues !== undefined) {
    scores.push(metrics.focusIssues === 0 ? 100 : Math.max(0, 100 - metrics.focusIssues * 10));
  }

  // Navegable por teclado
  if (metrics.keyboardNavigable !== undefined) {
    scores.push(metrics.keyboardNavigable ? 100 : 30);
  }

  // ARIA correcto
  if (metrics.ariaIssues !== undefined) {
    scores.push(metrics.ariaIssues === 0 ? 100 : Math.max(0, 100 - metrics.ariaIssues * 10));
  }

  if (scores.length === 0) return null;
  return scores.reduce((sum, score) => sum + score, 0) / scores.length;
}

/**
 * Calcular UX Score total
 * @param {Object} metrics - Objeto con metricas
 * @returns {Object} Score y desglose
 */
function calculateUXScore(metrics) {
  const breakdown = {
    responseTime: calculateResponseTimeScore(metrics),
    predictability: calculatePredictabilityScore(metrics),
    clarity: calculateClarityScore(metrics),
    errorHandling: calculateErrorHandlingScore(metrics),
    efficiency: calculateEfficiencyScore(metrics),
    accessibility: calculateAccessibilityScore(metrics)
  };

  // Calcular score ponderado
  let totalWeight = 0;
  let weightedSum = 0;

  for (const [category, weight] of Object.entries(CATEGORY_WEIGHTS)) {
    const categoryScore = breakdown[category];
    if (categoryScore !== null && categoryScore !== undefined) {
      weightedSum += categoryScore * weight;
      totalWeight += weight;
    }
  }

  // Normalizar si no todas las categorias tienen datos
  const finalScore = totalWeight > 0 ? (weightedSum / totalWeight) : 0;

  return {
    score: parseFloat(finalScore.toFixed(1)),
    grade: getGrade(finalScore),
    breakdown,
    weights: CATEGORY_WEIGHTS,
    recommendations: getRecommendations(breakdown)
  };
}

/**
 * Obtener calificacion
 */
function getGrade(score) {
  if (score >= 95) return 'A+';
  if (score >= 90) return 'A';
  if (score >= 85) return 'B+';
  if (score >= 80) return 'B';
  if (score >= 75) return 'C+';
  if (score >= 70) return 'C';
  if (score >= 60) return 'D';
  return 'F';
}

/**
 * Obtener color para score
 */
function getScoreColor(score) {
  if (score >= 90) return '\x1b[32m'; // Verde
  if (score >= 70) return '\x1b[33m'; // Amarillo
  return '\x1b[31m'; // Rojo
}

/**
 * Generar recomendaciones basadas en scores bajos
 */
function getRecommendations(breakdown) {
  const recommendations = [];

  if (breakdown.responseTime !== null && breakdown.responseTime < 80) {
    recommendations.push({
      category: 'responseTime',
      priority: 'high',
      message: 'Optimizar tiempos de respuesta. Los clicks deben responder en < 100ms.',
      actions: [
        'Reducir trabajo en main thread',
        'Usar requestAnimationFrame para animaciones',
        'Implementar debounce en operaciones costosas'
      ]
    });
  }

  if (breakdown.predictability !== null && breakdown.predictability < 80) {
    recommendations.push({
      category: 'predictability',
      priority: 'high',
      message: 'Mejorar predictibilidad. Los usuarios esperan comportamiento consistente.',
      actions: [
        'Implementar undo/redo completo',
        'Verificar que todos los atajos funcionan',
        'Asegurar que Escape siempre cancela'
      ]
    });
  }

  if (breakdown.clarity !== null && breakdown.clarity < 80) {
    recommendations.push({
      category: 'clarity',
      priority: 'medium',
      message: 'Mejorar claridad visual. Asegurar contraste y estados visibles.',
      actions: [
        'Verificar contraste WCAG AA (4.5:1)',
        'Agregar estados hover/focus visibles',
        'Revisar jerarquia visual'
      ]
    });
  }

  if (breakdown.errorHandling !== null && breakdown.errorHandling < 80) {
    recommendations.push({
      category: 'errorHandling',
      priority: 'medium',
      message: 'Mejorar manejo de errores. Los usuarios deben poder recuperarse facilmente.',
      actions: [
        'Agregar mensajes de error descriptivos',
        'Implementar autosave',
        'Agregar confirmacion para acciones destructivas'
      ]
    });
  }

  if (breakdown.efficiency !== null && breakdown.efficiency < 80) {
    recommendations.push({
      category: 'efficiency',
      priority: 'medium',
      message: 'Mejorar eficiencia. Reducir clicks necesarios para tareas comunes.',
      actions: [
        'Implementar command palette (Ctrl+K)',
        'Agregar atajos para acciones comunes',
        'Habilitar bulk operations'
      ]
    });
  }

  if (breakdown.accessibility !== null && breakdown.accessibility < 80) {
    recommendations.push({
      category: 'accessibility',
      priority: 'high',
      message: 'Mejorar accesibilidad. Todos los usuarios deben poder usar el editor.',
      actions: [
        'Agregar labels a todos los inputs',
        'Asegurar navegacion por teclado',
        'Implementar ARIA correctamente'
      ]
    });
  }

  return recommendations;
}

/**
 * Imprimir reporte en consola
 */
function printReport(result) {
  const reset = '\x1b[0m';

  console.log('\n========================================');
  console.log('         VBP UX SCORE REPORT');
  console.log('========================================\n');

  const scoreColor = getScoreColor(result.score);
  console.log(`Score Total: ${scoreColor}${result.score}% (${result.grade})${reset}\n`);

  console.log('--- Desglose por Categoria ---\n');

  for (const [category, score] of Object.entries(result.breakdown)) {
    if (score !== null) {
      const color = getScoreColor(score);
      const weight = (result.weights[category] * 100).toFixed(0);
      console.log(`${category.padEnd(20)} ${color}${score.toFixed(1).padStart(5)}%${reset} (peso: ${weight}%)`);
    } else {
      console.log(`${category.padEnd(20)}   N/A`);
    }
  }

  if (result.recommendations.length > 0) {
    console.log('\n--- Recomendaciones ---\n');

    result.recommendations.forEach((rec, index) => {
      const priorityColor = rec.priority === 'high' ? '\x1b[31m' : '\x1b[33m';
      console.log(`${index + 1}. [${priorityColor}${rec.priority.toUpperCase()}${reset}] ${rec.message}`);
      rec.actions.forEach(action => {
        console.log(`   - ${action}`);
      });
      console.log('');
    });
  }

  console.log('========================================\n');
}

// ============================================
// CLI
// ============================================

function parseArgs() {
  const args = process.argv.slice(2);
  let metrics = null;

  args.forEach(arg => {
    if (arg.startsWith('--metrics=')) {
      try {
        metrics = JSON.parse(arg.split('=')[1]);
      } catch (parseError) {
        console.error('Error parseando metricas JSON:', parseError.message);
      }
    }
  });

  return { metrics };
}

// Ejecutar si es llamado directamente
if (require.main === module) {
  const { metrics } = parseArgs();

  if (!metrics) {
    // Ejemplo con datos de prueba
    console.log('Usando metricas de ejemplo...\n');
    const exampleMetrics = {
      clickResponseMs: 80,
      dragStartMs: 40,
      typingLagMs: 12,
      saveTimeMs: 1500,
      undoWorks: true,
      redoWorks: true,
      shortcutsWorking: 8,
      shortcutsTotal: 10,
      escapeWorks: true,
      contrastIssues: 2,
      unlabeledInputs: 0,
      focusIssues: 1,
      keyboardNavigable: true,
      helpfulErrorMessages: true,
      destructiveConfirmation: true,
      autosaveEnabled: true,
      clicksToAddBlock: 2,
      hasCommandPalette: false,
      dragDropWorks: true
    };

    const result = calculateUXScore(exampleMetrics);
    printReport(result);
  } else {
    const result = calculateUXScore(metrics);
    printReport(result);
  }
}

module.exports = {
  calculateUXScore,
  getGrade,
  getRecommendations,
  CATEGORY_WEIGHTS,
  METRIC_THRESHOLDS
};
