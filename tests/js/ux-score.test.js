/**
 * Tests para UX Score Calculator
 */

const {
  calculateUXScore,
  getGrade,
  getRecommendations,
  CATEGORY_WEIGHTS,
  METRIC_THRESHOLDS
} = require('../../tools/ux-score.js');

describe('UX Score Calculator', () => {
  describe('getGrade', () => {
    test('retorna A+ para scores >= 95', () => {
      expect(getGrade(95)).toBe('A+');
      expect(getGrade(100)).toBe('A+');
    });

    test('retorna A para scores 90-94', () => {
      expect(getGrade(90)).toBe('A');
      expect(getGrade(94)).toBe('A');
    });

    test('retorna B+ para scores 85-89', () => {
      expect(getGrade(85)).toBe('B+');
      expect(getGrade(89)).toBe('B+');
    });

    test('retorna B para scores 80-84', () => {
      expect(getGrade(80)).toBe('B');
      expect(getGrade(84)).toBe('B');
    });

    test('retorna C+ para scores 75-79', () => {
      expect(getGrade(75)).toBe('C+');
      expect(getGrade(79)).toBe('C+');
    });

    test('retorna C para scores 70-74', () => {
      expect(getGrade(70)).toBe('C');
      expect(getGrade(74)).toBe('C');
    });

    test('retorna D para scores 60-69', () => {
      expect(getGrade(60)).toBe('D');
      expect(getGrade(69)).toBe('D');
    });

    test('retorna F para scores < 60', () => {
      expect(getGrade(59)).toBe('F');
      expect(getGrade(0)).toBe('F');
    });
  });

  describe('calculateUXScore', () => {
    test('calcula score con metricas directas por categoria', () => {
      const metrics = {
        responseTime: 90,
        predictability: 85,
        clarity: 80,
        errorHandling: 75,
        efficiency: 70,
        accessibility: 95
      };

      const result = calculateUXScore(metrics);

      expect(result.score).toBeGreaterThan(0);
      expect(result.grade).toBeDefined();
      expect(result.breakdown).toBeDefined();
      expect(result.recommendations).toBeDefined();
    });

    test('calcula responseTime desde metricas de tiempo', () => {
      const metrics = {
        clickResponseMs: 50,   // excellent
        dragStartMs: 30,       // excellent
        typingLagMs: 10,       // excellent
        saveTimeMs: 500        // excellent
      };

      const result = calculateUXScore(metrics);

      expect(result.breakdown.responseTime).toBe(100);
    });

    test('penaliza tiempos de respuesta lentos', () => {
      const fastMetrics = {
        clickResponseMs: 50
      };

      const slowMetrics = {
        clickResponseMs: 300
      };

      const fastResult = calculateUXScore(fastMetrics);
      const slowResult = calculateUXScore(slowMetrics);

      expect(fastResult.breakdown.responseTime).toBeGreaterThan(slowResult.breakdown.responseTime);
    });

    test('calcula predictability desde metricas booleanas', () => {
      const metrics = {
        undoWorks: true,
        redoWorks: true,
        escapeWorks: true,
        shortcutsWorking: 10,
        shortcutsTotal: 10
      };

      const result = calculateUXScore(metrics);

      expect(result.breakdown.predictability).toBe(100);
    });

    test('calcula accessibility desde metricas de issues', () => {
      const perfectMetrics = {
        unlabeledInputs: 0,
        focusIssues: 0,
        keyboardNavigable: true,
        ariaIssues: 0
      };

      const result = calculateUXScore(perfectMetrics);

      expect(result.breakdown.accessibility).toBe(100);
    });

    test('reduce score con issues de accesibilidad', () => {
      const metricsWithIssues = {
        unlabeledInputs: 5,
        focusIssues: 3,
        keyboardNavigable: false,
        ariaIssues: 2
      };

      const result = calculateUXScore(metricsWithIssues);

      expect(result.breakdown.accessibility).toBeLessThan(80);
    });

    test('retorna null para categorias sin datos', () => {
      const metrics = {
        responseTime: 90
      };

      const result = calculateUXScore(metrics);

      expect(result.breakdown.responseTime).toBe(90);
      expect(result.breakdown.predictability).toBeNull();
    });

    test('normaliza score cuando faltan categorias', () => {
      const partialMetrics = {
        responseTime: 100,
        accessibility: 100
      };

      const result = calculateUXScore(partialMetrics);

      // Deberia ser 100 ya que las categorias disponibles son perfectas
      expect(result.score).toBe(100);
    });

    test('aplica pesos correctamente', () => {
      // responseTime tiene peso 0.25, accessibility tiene peso 0.10
      const metrics = {
        responseTime: 100,
        accessibility: 0
      };

      const result = calculateUXScore(metrics);

      // Score ponderado: (100 * 0.25 + 0 * 0.10) / (0.25 + 0.10) = 25 / 0.35 = 71.43
      expect(result.score).toBeCloseTo(71.4, 1);
    });
  });

  describe('getRecommendations', () => {
    test('genera recomendaciones para scores bajos', () => {
      const breakdown = {
        responseTime: 60,
        predictability: 90,
        clarity: 90,
        errorHandling: 90,
        efficiency: 90,
        accessibility: 60
      };

      const recommendations = getRecommendations(breakdown);

      expect(recommendations.length).toBeGreaterThan(0);
      expect(recommendations.some(rec => rec.category === 'responseTime')).toBe(true);
      expect(recommendations.some(rec => rec.category === 'accessibility')).toBe(true);
    });

    test('no genera recomendaciones para scores altos', () => {
      const breakdown = {
        responseTime: 95,
        predictability: 95,
        clarity: 95,
        errorHandling: 95,
        efficiency: 95,
        accessibility: 95
      };

      const recommendations = getRecommendations(breakdown);

      expect(recommendations.length).toBe(0);
    });

    test('marca prioridad alta para accesibilidad', () => {
      const breakdown = {
        accessibility: 50
      };

      const recommendations = getRecommendations(breakdown);
      const accessibilityRec = recommendations.find(rec => rec.category === 'accessibility');

      expect(accessibilityRec).toBeDefined();
      expect(accessibilityRec.priority).toBe('high');
    });

    test('incluye acciones especificas en recomendaciones', () => {
      const breakdown = {
        responseTime: 50
      };

      const recommendations = getRecommendations(breakdown);
      const responseTimeRec = recommendations.find(rec => rec.category === 'responseTime');

      expect(responseTimeRec.actions).toBeDefined();
      expect(responseTimeRec.actions.length).toBeGreaterThan(0);
    });
  });

  describe('CATEGORY_WEIGHTS', () => {
    test('suma de pesos es 1', () => {
      const totalWeight = Object.values(CATEGORY_WEIGHTS).reduce((sum, weight) => sum + weight, 0);
      expect(totalWeight).toBe(1);
    });

    test('todas las categorias tienen peso positivo', () => {
      Object.values(CATEGORY_WEIGHTS).forEach(weight => {
        expect(weight).toBeGreaterThan(0);
      });
    });

    test('responseTime tiene el mayor peso', () => {
      const maxWeight = Math.max(...Object.values(CATEGORY_WEIGHTS));
      expect(CATEGORY_WEIGHTS.responseTime).toBe(maxWeight);
    });
  });

  describe('METRIC_THRESHOLDS', () => {
    test('clickResponseMs tiene thresholds correctos', () => {
      expect(METRIC_THRESHOLDS.clickResponseMs.excellent).toBeLessThan(METRIC_THRESHOLDS.clickResponseMs.good);
      expect(METRIC_THRESHOLDS.clickResponseMs.good).toBeLessThan(METRIC_THRESHOLDS.clickResponseMs.acceptable);
      expect(METRIC_THRESHOLDS.clickResponseMs.acceptable).toBeLessThan(METRIC_THRESHOLDS.clickResponseMs.poor);
    });

    test('excellent threshold para click es <= 50ms', () => {
      expect(METRIC_THRESHOLDS.clickResponseMs.excellent).toBeLessThanOrEqual(50);
    });

    test('good threshold para typing es <= 16ms (1 frame a 60fps)', () => {
      expect(METRIC_THRESHOLDS.typingLagMs.good).toBeLessThanOrEqual(16);
    });
  });

  describe('Escenarios de uso real', () => {
    test('editor optimizado obtiene A+', () => {
      const optimizedMetrics = {
        clickResponseMs: 40,
        dragStartMs: 25,
        typingLagMs: 8,
        saveTimeMs: 400,
        undoWorks: true,
        redoWorks: true,
        escapeWorks: true,
        shortcutsWorking: 10,
        shortcutsTotal: 10,
        contrastIssues: 0,
        unlabeledInputs: 0,
        focusIssues: 0,
        keyboardNavigable: true,
        helpfulErrorMessages: true,
        destructiveConfirmation: true,
        autosaveEnabled: true,
        clicksToAddBlock: 2,
        hasCommandPalette: true,
        dragDropWorks: true
      };

      const result = calculateUXScore(optimizedMetrics);

      expect(result.grade).toBe('A+');
      expect(result.score).toBeGreaterThanOrEqual(95);
    });

    test('editor con problemas de performance obtiene score bajo', () => {
      const slowMetrics = {
        clickResponseMs: 500,
        dragStartMs: 200,
        typingLagMs: 100,
        saveTimeMs: 5000
      };

      const result = calculateUXScore(slowMetrics);

      expect(result.score).toBeLessThan(70);
      expect(result.recommendations.some(rec => rec.category === 'responseTime')).toBe(true);
    });

    test('editor con problemas de accesibilidad genera recomendaciones', () => {
      const inaccessibleMetrics = {
        unlabeledInputs: 10,
        focusIssues: 5,
        keyboardNavigable: false,
        ariaIssues: 8
      };

      const result = calculateUXScore(inaccessibleMetrics);

      expect(result.breakdown.accessibility).toBeLessThan(50);
      const accessibilityRec = result.recommendations.find(rec => rec.category === 'accessibility');
      expect(accessibilityRec).toBeDefined();
      expect(accessibilityRec.priority).toBe('high');
    });
  });
});
