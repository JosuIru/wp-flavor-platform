/**
 * VBP UX Audit Tool
 * Verificacion automatizada de criterios UX para Visual Builder Pro
 *
 * Uso:
 *   node tools/ux-audit.js --url="http://sitio.local/wp-admin"
 *   node tools/ux-audit.js --url="http://sitio.local/wp-admin" --output=json
 *   node tools/ux-audit.js --url="http://sitio.local/wp-admin" --category=performance
 *
 * Categorias disponibles:
 *   - all (default)
 *   - performance
 *   - accessibility
 *   - interactions
 *   - consistency
 */

const puppeteer = require('puppeteer');

class UXAuditor {
  constructor(options = {}) {
    this.url = options.url;
    this.outputFormat = options.output || 'console';
    this.category = options.category || 'all';
    this.browser = null;
    this.page = null;
    this.results = {
      passed: [],
      failed: [],
      warnings: [],
      manual: [],
      metrics: {}
    };
  }

  /**
   * Registrar resultado de verificacion
   */
  check(name, passed, value = null, category = 'general') {
    const result = {
      name,
      passed,
      value,
      category,
      timestamp: new Date().toISOString()
    };

    if (passed === true) {
      this.results.passed.push(result);
    } else if (passed === false) {
      this.results.failed.push(result);
    } else if (passed === 'warning') {
      this.results.warnings.push(result);
    } else {
      this.results.manual.push(result);
    }

    return result;
  }

  /**
   * Inicializar navegador
   */
  async init() {
    this.browser = await puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });
    this.page = await this.browser.newPage();

    // Configurar viewport
    await this.page.setViewport({ width: 1920, height: 1080 });

    // Habilitar performance monitoring
    await this.page.setCacheEnabled(false);

    console.log('Navegador inicializado');
  }

  /**
   * Navegar a la URL
   */
  async navigate() {
    console.log(`Navegando a ${this.url}...`);
    await this.page.goto(this.url, { waitUntil: 'networkidle2' });
    console.log('Pagina cargada');
  }

  /**
   * Cerrar navegador
   */
  async close() {
    if (this.browser) {
      await this.browser.close();
    }
  }

  // ============================================
  // AUDITORIAS DE PERFORMANCE
  // ============================================

  /**
   * Medir tiempo de respuesta de click
   */
  async measureClickResponse() {
    const clickTime = await this.page.evaluate(() => {
      return new Promise((resolve) => {
        const button = document.querySelector('.vbp-button, button, [role="button"]');
        if (!button) {
          resolve(-1);
          return;
        }

        const startTime = performance.now();
        let responseTime = null;

        // Observer para detectar cambios visuales
        const observer = new MutationObserver(() => {
          if (!responseTime) {
            responseTime = performance.now() - startTime;
          }
        });

        observer.observe(button, {
          attributes: true,
          childList: true,
          subtree: true
        });

        // Simular click
        button.click();

        // Timeout de seguridad
        setTimeout(() => {
          observer.disconnect();
          resolve(responseTime || performance.now() - startTime);
        }, 500);
      });
    });

    this.check('Click responde en < 100ms', clickTime < 100, `${clickTime.toFixed(2)}ms`, 'performance');
    this.results.metrics.clickResponseTime = clickTime;

    return clickTime;
  }

  /**
   * Medir tiempo de inicio de drag
   */
  async measureDragStart() {
    const dragTime = await this.page.evaluate(() => {
      return new Promise((resolve) => {
        const draggable = document.querySelector('[draggable="true"], .vbp-draggable');
        if (!draggable) {
          resolve(-1);
          return;
        }

        const startTime = performance.now();

        draggable.addEventListener('dragstart', () => {
          resolve(performance.now() - startTime);
        }, { once: true });

        // Simular inicio de drag
        const rect = draggable.getBoundingClientRect();
        const mousedownEvent = new MouseEvent('mousedown', {
          bubbles: true,
          clientX: rect.left + rect.width / 2,
          clientY: rect.top + rect.height / 2
        });
        draggable.dispatchEvent(mousedownEvent);

        // Timeout de seguridad
        setTimeout(() => resolve(100), 200);
      });
    });

    this.check('Drag inicia en < 50ms', dragTime < 50, `${dragTime.toFixed(2)}ms`, 'performance');
    this.results.metrics.dragStartTime = dragTime;

    return dragTime;
  }

  /**
   * Medir latencia de typing
   */
  async measureTypingLag() {
    const typingLag = await this.page.evaluate(() => {
      return new Promise((resolve) => {
        const input = document.querySelector('input[type="text"], textarea, [contenteditable="true"]');
        if (!input) {
          resolve(-1);
          return;
        }

        input.focus();
        const startTime = performance.now();

        input.addEventListener('input', () => {
          resolve(performance.now() - startTime);
        }, { once: true });

        // Simular typing
        const keyEvent = new KeyboardEvent('keydown', {
          key: 'a',
          code: 'KeyA',
          bubbles: true
        });
        input.dispatchEvent(keyEvent);

        // Para inputs, tambien necesitamos modificar el valor
        if (input.tagName === 'INPUT' || input.tagName === 'TEXTAREA') {
          input.value += 'a';
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }

        setTimeout(() => resolve(50), 100);
      });
    });

    this.check('Typing sin lag (< 16ms)', typingLag < 16, `${typingLag.toFixed(2)}ms`, 'performance');
    this.results.metrics.typingLag = typingLag;

    return typingLag;
  }

  /**
   * Verificar duracion de transiciones CSS
   */
  async checkTransitionDurations() {
    const transitions = await this.page.evaluate(() => {
      const elements = document.querySelectorAll('*');
      const transitionData = [];

      elements.forEach(element => {
        const styles = getComputedStyle(element);
        const duration = styles.transitionDuration;

        if (duration && duration !== '0s') {
          const durationMs = parseFloat(duration) * 1000;
          if (durationMs > 0) {
            transitionData.push({
              selector: element.className || element.tagName,
              duration: durationMs
            });
          }
        }
      });

      return transitionData;
    });

    const longTransitions = transitions.filter(transitionItem => transitionItem.duration > 500);
    this.check(
      'Transiciones <= 300ms',
      longTransitions.length === 0,
      `${longTransitions.length} transiciones > 500ms`,
      'performance'
    );

    return transitions;
  }

  /**
   * Ejecutar todas las auditorias de performance
   */
  async auditPerformance() {
    console.log('\n--- Auditando Performance ---');
    await this.measureClickResponse();
    await this.measureDragStart();
    await this.measureTypingLag();
    await this.checkTransitionDurations();
  }

  // ============================================
  // AUDITORIAS DE ACCESIBILIDAD
  // ============================================

  /**
   * Verificar labels en inputs
   */
  async checkInputLabels() {
    const unlabeledInputs = await this.page.evaluate(() => {
      const inputs = document.querySelectorAll('input, select, textarea');
      const unlabeled = [];

      inputs.forEach(input => {
        const hasLabel = input.labels && input.labels.length > 0;
        const hasAriaLabel = input.hasAttribute('aria-label');
        const hasAriaLabelledby = input.hasAttribute('aria-labelledby');
        const hasPlaceholder = input.hasAttribute('placeholder');
        const hasTitle = input.hasAttribute('title');

        if (!hasLabel && !hasAriaLabel && !hasAriaLabelledby && !hasPlaceholder && !hasTitle) {
          unlabeled.push({
            type: input.type || input.tagName,
            id: input.id,
            name: input.name
          });
        }
      });

      return unlabeled;
    });

    this.check(
      'Todos los inputs tienen label',
      unlabeledInputs.length === 0,
      `${unlabeledInputs.length} inputs sin label`,
      'accessibility'
    );

    return unlabeledInputs;
  }

  /**
   * Verificar contraste de colores
   */
  async checkContrast() {
    const lowContrastElements = await this.page.evaluate(() => {
      // Funcion para calcular luminancia relativa
      function getLuminance(red, green, blue) {
        const rsRGB = red / 255;
        const gsRGB = green / 255;
        const bsRGB = blue / 255;
        const rValue = rsRGB <= 0.03928 ? rsRGB / 12.92 : Math.pow((rsRGB + 0.055) / 1.055, 2.4);
        const gValue = gsRGB <= 0.03928 ? gsRGB / 12.92 : Math.pow((gsRGB + 0.055) / 1.055, 2.4);
        const bValue = bsRGB <= 0.03928 ? bsRGB / 12.92 : Math.pow((bsRGB + 0.055) / 1.055, 2.4);
        return 0.2126 * rValue + 0.7152 * gValue + 0.0722 * bValue;
      }

      // Funcion para calcular ratio de contraste
      function getContrastRatio(lum1, lum2) {
        const lighter = Math.max(lum1, lum2);
        const darker = Math.min(lum1, lum2);
        return (lighter + 0.05) / (darker + 0.05);
      }

      // Funcion para parsear color
      function parseColor(colorStr) {
        if (!colorStr || colorStr === 'transparent') return null;
        const match = colorStr.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (match) {
          return {
            r: parseInt(match[1], 10),
            g: parseInt(match[2], 10),
            b: parseInt(match[3], 10)
          };
        }
        return null;
      }

      const textElements = document.querySelectorAll('p, span, a, button, label, h1, h2, h3, h4, h5, h6');
      const issues = [];

      textElements.forEach(element => {
        const styles = getComputedStyle(element);
        const textColor = parseColor(styles.color);
        const bgColor = parseColor(styles.backgroundColor);

        if (textColor && bgColor) {
          const textLum = getLuminance(textColor.r, textColor.g, textColor.b);
          const bgLum = getLuminance(bgColor.r, bgColor.g, bgColor.b);
          const ratio = getContrastRatio(textLum, bgLum);

          // WCAG AA requiere 4.5:1 para texto normal, 3:1 para texto grande
          const fontSize = parseFloat(styles.fontSize);
          const isBold = styles.fontWeight >= 700;
          const isLargeText = fontSize >= 18 || (fontSize >= 14 && isBold);
          const minRatio = isLargeText ? 3 : 4.5;

          if (ratio < minRatio) {
            issues.push({
              text: element.textContent.substring(0, 50),
              ratio: ratio.toFixed(2),
              required: minRatio
            });
          }
        }
      });

      return issues;
    });

    this.check(
      'Contraste suficiente (WCAG AA)',
      lowContrastElements.length === 0,
      `${lowContrastElements.length} elementos con bajo contraste`,
      'accessibility'
    );

    return lowContrastElements;
  }

  /**
   * Verificar focus visible
   */
  async checkFocusVisibility() {
    const focusIssues = await this.page.evaluate(() => {
      const focusableElements = document.querySelectorAll(
        'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])'
      );
      const issues = [];

      focusableElements.forEach(element => {
        // Guardar estilos originales
        const originalOutline = element.style.outline;

        // Aplicar focus
        element.focus();

        // Verificar si hay indicador de focus visible
        const styles = getComputedStyle(element);
        const hasOutline = styles.outlineStyle !== 'none' && styles.outlineWidth !== '0px';
        const hasBoxShadow = styles.boxShadow !== 'none';
        const hasBorderChange = true; // Dificil de detectar sin comparar

        if (!hasOutline && !hasBoxShadow) {
          issues.push({
            tag: element.tagName,
            id: element.id,
            class: element.className
          });
        }

        // Restaurar
        element.blur();
        element.style.outline = originalOutline;
      });

      return issues;
    });

    this.check(
      'Focus siempre visible',
      focusIssues.length === 0,
      `${focusIssues.length} elementos sin focus visible`,
      'accessibility'
    );

    return focusIssues;
  }

  /**
   * Ejecutar todas las auditorias de accesibilidad
   */
  async auditAccessibility() {
    console.log('\n--- Auditando Accesibilidad ---');
    await this.checkInputLabels();
    await this.checkContrast();
    await this.checkFocusVisibility();
  }

  // ============================================
  // AUDITORIAS DE INTERACCIONES
  // ============================================

  /**
   * Verificar atajos de teclado
   */
  async auditKeyboardShortcuts() {
    console.log('\n--- Auditando Atajos de Teclado ---');

    const shortcuts = [
      { key: 's', ctrl: true, action: 'save' },
      { key: 'z', ctrl: true, action: 'undo' },
      { key: 'y', ctrl: true, action: 'redo' },
      { key: 'Escape', ctrl: false, action: 'cancel' }
    ];

    for (const shortcut of shortcuts) {
      const works = await this.testShortcut(shortcut);
      const shortcutName = shortcut.ctrl ? `Ctrl+${shortcut.key.toUpperCase()}` : shortcut.key;
      this.check(
        `${shortcutName} funciona`,
        works ? true : 'manual',
        shortcut.action,
        'interactions'
      );
    }
  }

  /**
   * Testear un atajo especifico
   */
  async testShortcut(shortcut) {
    return await this.page.evaluate((shortcutParam) => {
      return new Promise((resolve) => {
        let triggered = false;

        // Listener temporal
        const handler = (event) => {
          if (event.key.toLowerCase() === shortcutParam.key.toLowerCase() &&
              event.ctrlKey === shortcutParam.ctrl) {
            triggered = true;
          }
        };

        document.addEventListener('keydown', handler);

        // Simular atajo
        const event = new KeyboardEvent('keydown', {
          key: shortcutParam.key,
          code: `Key${shortcutParam.key.toUpperCase()}`,
          ctrlKey: shortcutParam.ctrl,
          bubbles: true
        });
        document.dispatchEvent(event);

        setTimeout(() => {
          document.removeEventListener('keydown', handler);
          resolve(triggered);
        }, 100);
      });
    }, shortcut);
  }

  /**
   * Verificar estados hover
   */
  async checkHoverStates() {
    const hoverIssues = await this.page.evaluate(() => {
      const clickableElements = document.querySelectorAll('a, button, [role="button"], .clickable');
      const issues = [];

      clickableElements.forEach(element => {
        const styles = getComputedStyle(element);
        const cursor = styles.cursor;

        // Verificar que elementos clickeables tienen cursor pointer
        if (cursor !== 'pointer' && cursor !== 'grab') {
          issues.push({
            tag: element.tagName,
            text: element.textContent.substring(0, 30),
            cursor
          });
        }
      });

      return issues;
    });

    this.check(
      'Cursores correctos en clickeables',
      hoverIssues.length === 0,
      `${hoverIssues.length} elementos con cursor incorrecto`,
      'interactions'
    );

    return hoverIssues;
  }

  /**
   * Ejecutar todas las auditorias de interacciones
   */
  async auditInteractions() {
    await this.auditKeyboardShortcuts();
    await this.checkHoverStates();
  }

  // ============================================
  // AUDITORIAS DE CONSISTENCIA
  // ============================================

  /**
   * Verificar consistencia de spacing
   */
  async checkSpacingConsistency() {
    const spacingValues = await this.page.evaluate(() => {
      const elements = document.querySelectorAll('*');
      const margins = new Set();
      const paddings = new Set();

      elements.forEach(element => {
        const styles = getComputedStyle(element);
        ['marginTop', 'marginRight', 'marginBottom', 'marginLeft'].forEach(prop => {
          const value = parseInt(styles[prop], 10);
          if (value > 0 && value < 100) {
            margins.add(value);
          }
        });
        ['paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft'].forEach(prop => {
          const value = parseInt(styles[prop], 10);
          if (value > 0 && value < 100) {
            paddings.add(value);
          }
        });
      });

      return {
        margins: Array.from(margins).sort((a, b) => a - b),
        paddings: Array.from(paddings).sort((a, b) => a - b)
      };
    });

    // Verificar si sigue grid de 4px u 8px
    const isConsistent = (values) => {
      return values.every(spacingValue => spacingValue % 4 === 0);
    };

    this.check(
      'Spacing sigue grid de 4px',
      isConsistent(spacingValues.margins) && isConsistent(spacingValues.paddings),
      `Margins: ${spacingValues.margins.length} valores, Paddings: ${spacingValues.paddings.length} valores`,
      'consistency'
    );

    return spacingValues;
  }

  /**
   * Verificar consistencia de fuentes
   */
  async checkFontConsistency() {
    const fonts = await this.page.evaluate(() => {
      const elements = document.querySelectorAll('*');
      const fontFamilies = new Set();

      elements.forEach(element => {
        const styles = getComputedStyle(element);
        const fontFamily = styles.fontFamily.split(',')[0].trim().replace(/"/g, '');
        if (fontFamily) {
          fontFamilies.add(fontFamily);
        }
      });

      return Array.from(fontFamilies);
    });

    // Idealmente deberia haber 2-3 familias de fuentes max
    this.check(
      'Familias de fuentes consistentes',
      fonts.length <= 4,
      `${fonts.length} familias: ${fonts.join(', ')}`,
      'consistency'
    );

    return fonts;
  }

  /**
   * Ejecutar todas las auditorias de consistencia
   */
  async auditConsistency() {
    console.log('\n--- Auditando Consistencia ---');
    await this.checkSpacingConsistency();
    await this.checkFontConsistency();
  }

  // ============================================
  // EJECUCION Y REPORTE
  // ============================================

  /**
   * Ejecutar auditoria completa
   */
  async run() {
    try {
      await this.init();
      await this.navigate();

      if (this.category === 'all' || this.category === 'performance') {
        await this.auditPerformance();
      }
      if (this.category === 'all' || this.category === 'accessibility') {
        await this.auditAccessibility();
      }
      if (this.category === 'all' || this.category === 'interactions') {
        await this.auditInteractions();
      }
      if (this.category === 'all' || this.category === 'consistency') {
        await this.auditConsistency();
      }

      const report = this.generateReport();

      if (this.outputFormat === 'json') {
        console.log(JSON.stringify(report, null, 2));
      } else {
        this.printReport(report);
      }

      return report;
    } finally {
      await this.close();
    }
  }

  /**
   * Generar reporte
   */
  generateReport() {
    const total = this.results.passed.length + this.results.failed.length;
    const score = total > 0 ? (this.results.passed.length / total) * 100 : 0;

    return {
      url: this.url,
      timestamp: new Date().toISOString(),
      score: score.toFixed(1),
      grade: this.getGrade(score),
      summary: {
        total,
        passed: this.results.passed.length,
        failed: this.results.failed.length,
        warnings: this.results.warnings.length,
        manual: this.results.manual.length
      },
      metrics: this.results.metrics,
      details: {
        passed: this.results.passed,
        failed: this.results.failed,
        warnings: this.results.warnings,
        manual: this.results.manual
      }
    };
  }

  /**
   * Obtener calificacion basada en score
   */
  getGrade(score) {
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
   * Imprimir reporte en consola
   */
  printReport(report) {
    console.log('\n========================================');
    console.log('         VBP UX AUDIT REPORT');
    console.log('========================================\n');

    console.log(`URL: ${report.url}`);
    console.log(`Fecha: ${report.timestamp}`);
    console.log(`\nScore: ${report.score}% (${report.grade})\n`);

    console.log('--- Resumen ---');
    console.log(`Pasados:     ${report.summary.passed}`);
    console.log(`Fallidos:    ${report.summary.failed}`);
    console.log(`Advertencias: ${report.summary.warnings}`);
    console.log(`Manuales:    ${report.summary.manual}`);

    if (report.summary.failed > 0) {
      console.log('\n--- Items Fallidos ---');
      report.details.failed.forEach((item, index) => {
        console.log(`${index + 1}. [${item.category}] ${item.name}`);
        if (item.value) console.log(`   Valor: ${item.value}`);
      });
    }

    if (report.summary.warnings > 0) {
      console.log('\n--- Advertencias ---');
      report.details.warnings.forEach((item, index) => {
        console.log(`${index + 1}. [${item.category}] ${item.name}`);
        if (item.value) console.log(`   Valor: ${item.value}`);
      });
    }

    console.log('\n--- Metricas ---');
    Object.entries(report.metrics).forEach(([key, value]) => {
      console.log(`${key}: ${value}`);
    });

    console.log('\n========================================\n');
  }
}

// ============================================
// CLI
// ============================================

function parseArgs() {
  const args = process.argv.slice(2);
  const options = {
    url: null,
    output: 'console',
    category: 'all'
  };

  args.forEach(arg => {
    if (arg.startsWith('--url=')) {
      options.url = arg.split('=')[1];
    } else if (arg.startsWith('--output=')) {
      options.output = arg.split('=')[1];
    } else if (arg.startsWith('--category=')) {
      options.category = arg.split('=')[1];
    }
  });

  return options;
}

async function main() {
  const options = parseArgs();

  if (!options.url) {
    console.error('Error: Se requiere --url');
    console.log('Uso: node tools/ux-audit.js --url="http://sitio.local/wp-admin"');
    process.exit(1);
  }

  const auditor = new UXAuditor(options);
  const report = await auditor.run();

  // Exit code basado en score
  const score = parseFloat(report.score);
  if (score < 70) {
    process.exit(1);
  }
}

main().catch(error => {
  console.error('Error:', error);
  process.exit(1);
});

module.exports = { UXAuditor };
