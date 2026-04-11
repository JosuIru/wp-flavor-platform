/**
 * Visual Builder Pro - Dev Mode / Handoff
 * Sistema de inspección para desarrolladores
 *
 * @package Flavor_Platform
 * @since 2.5.0
 */

// Fallback de vbpLog si no está definido
if (!window.vbpLog) {
    window.vbpLog = {
        log: function() { if (window.VBP_DEBUG) console.log.apply(console, ['[VBP Dev]'].concat(Array.prototype.slice.call(arguments))); },
        warn: function() { if (window.VBP_DEBUG) console.warn.apply(console, ['[VBP Dev]'].concat(Array.prototype.slice.call(arguments))); },
        error: function() { console.error.apply(console, ['[VBP Dev]'].concat(Array.prototype.slice.call(arguments))); }
    };
}

/**
 * Utilidades de conversión de unidades
 */
const VBPUnitConverter = {
    remBase: 16,

    /**
     * Convierte px a rem
     * @param {number|string} pxValue - Valor en píxeles
     * @param {number} base - Base rem (default 16)
     * @returns {string} Valor en rem
     */
    pxToRem: function(pxValue, base) {
        base = base || this.remBase;
        var numericValue = parseFloat(pxValue);
        if (isNaN(numericValue)) return '0rem';
        return (numericValue / base).toFixed(4).replace(/\.?0+$/, '') + 'rem';
    },

    /**
     * Convierte px a em
     * @param {number|string} pxValue - Valor en píxeles
     * @param {number} parentFontSize - Tamaño de fuente del padre
     * @returns {string} Valor en em
     */
    pxToEm: function(pxValue, parentFontSize) {
        parentFontSize = parentFontSize || 16;
        var numericValue = parseFloat(pxValue);
        if (isNaN(numericValue)) return '0em';
        return (numericValue / parentFontSize).toFixed(4).replace(/\.?0+$/, '') + 'em';
    },

    /**
     * Convierte valor a unidad específica
     * @param {string} value - Valor con unidad
     * @param {string} targetUnit - Unidad destino (px, rem, em, %)
     * @param {object} context - Contexto para conversión
     * @returns {string} Valor convertido
     */
    convert: function(value, targetUnit, context) {
        context = context || {};
        var numericValue = parseFloat(value);
        var currentUnit = String(value).replace(/[\d.-]/g, '').trim() || 'px';

        if (isNaN(numericValue)) return value;

        // Primero convertir a px
        var pxValue = numericValue;
        if (currentUnit === 'rem') {
            pxValue = numericValue * (context.remBase || this.remBase);
        } else if (currentUnit === 'em') {
            pxValue = numericValue * (context.parentFontSize || 16);
        } else if (currentUnit === '%') {
            pxValue = numericValue * (context.parentSize || 100) / 100;
        }

        // Luego convertir a unidad destino
        switch (targetUnit) {
            case 'rem':
                return this.pxToRem(pxValue, context.remBase);
            case 'em':
                return this.pxToEm(pxValue, context.parentFontSize);
            case '%':
                var parentSize = context.parentSize || 100;
                return ((pxValue / parentSize) * 100).toFixed(2).replace(/\.?0+$/, '') + '%';
            case 'px':
            default:
                return Math.round(pxValue) + 'px';
        }
    }
};

/**
 * Generador de código CSS
 */
const VBPCodeGenerator = {
    /**
     * Genera CSS plano
     * @param {object} styles - Objeto de estilos
     * @param {string} selector - Selector CSS
     * @param {string} unit - Unidad para valores numéricos
     * @returns {string} Código CSS
     */
    generateCSS: function(styles, selector, unit) {
        selector = selector || '.element';
        unit = unit || 'px';

        var cssLines = [];
        cssLines.push(selector + ' {');

        var propertyOrder = [
            // Display & Position
            'display', 'position', 'top', 'right', 'bottom', 'left', 'z-index',
            // Flexbox
            'flex-direction', 'flex-wrap', 'justify-content', 'align-items', 'align-content', 'gap', 'row-gap', 'column-gap',
            // Grid
            'grid-template-columns', 'grid-template-rows', 'grid-gap',
            // Box Model
            'width', 'min-width', 'max-width', 'height', 'min-height', 'max-height',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            // Typography
            'font-family', 'font-size', 'font-weight', 'font-style', 'line-height', 'letter-spacing', 'text-align', 'text-transform', 'text-decoration', 'color',
            // Background
            'background', 'background-color', 'background-image', 'background-size', 'background-position', 'background-repeat',
            // Border
            'border', 'border-width', 'border-style', 'border-color', 'border-radius',
            'border-top', 'border-right', 'border-bottom', 'border-left',
            // Effects
            'box-shadow', 'text-shadow', 'opacity', 'transform', 'transition',
            // Other
            'overflow', 'cursor', 'visibility'
        ];

        var self = this;
        var sortedProps = Object.keys(styles).sort(function(propA, propB) {
            var indexA = propertyOrder.indexOf(propA);
            var indexB = propertyOrder.indexOf(propB);
            if (indexA === -1) indexA = 999;
            if (indexB === -1) indexB = 999;
            return indexA - indexB;
        });

        sortedProps.forEach(function(prop) {
            var value = styles[prop];
            if (value === undefined || value === null || value === '') return;

            // Convertir unidades si es necesario
            if (unit !== 'px' && self.isNumericProperty(prop)) {
                value = VBPUnitConverter.convert(value, unit);
            }

            cssLines.push('  ' + self.camelToDash(prop) + ': ' + value + ';');
        });

        cssLines.push('}');
        return cssLines.join('\n');
    },

    /**
     * Genera SCSS con variables
     * @param {object} styles - Objeto de estilos
     * @param {string} selector - Selector CSS
     * @param {object} tokens - Design tokens a usar
     * @returns {string} Código SCSS
     */
    generateSCSS: function(styles, selector, tokens) {
        selector = selector || '.element';
        tokens = tokens || {};

        var scssLines = [];
        var variables = [];

        // Generar variables SCSS
        if (tokens.colors) {
            Object.keys(tokens.colors).forEach(function(tokenName) {
                variables.push('$' + tokenName + ': ' + tokens.colors[tokenName] + ';');
            });
        }
        if (tokens.spacing) {
            Object.keys(tokens.spacing).forEach(function(tokenName) {
                variables.push('$' + tokenName + ': ' + tokens.spacing[tokenName] + ';');
            });
        }

        if (variables.length > 0) {
            scssLines.push('// Variables');
            scssLines = scssLines.concat(variables);
            scssLines.push('');
        }

        scssLines.push(selector + ' {');

        var self = this;
        Object.keys(styles).forEach(function(prop) {
            var value = styles[prop];
            if (value === undefined || value === null || value === '') return;

            // Intentar mapear a variable
            var tokenValue = self.findTokenForValue(value, tokens);
            var finalValue = tokenValue || value;

            scssLines.push('  ' + self.camelToDash(prop) + ': ' + finalValue + ';');
        });

        // Agregar estados hover si existen
        if (styles._hover) {
            scssLines.push('');
            scssLines.push('  &:hover {');
            Object.keys(styles._hover).forEach(function(prop) {
                var value = styles._hover[prop];
                if (value === undefined || value === null || value === '') return;
                scssLines.push('    ' + self.camelToDash(prop) + ': ' + value + ';');
            });
            scssLines.push('  }');
        }

        scssLines.push('}');
        return scssLines.join('\n');
    },

    /**
     * Genera clases Tailwind CSS
     * @param {object} styles - Objeto de estilos
     * @returns {string} Clases Tailwind
     */
    generateTailwind: function(styles) {
        var classes = [];
        var self = this;

        // Mapeo de propiedades CSS a Tailwind
        var tailwindMap = {
            // Display
            'display': { 'flex': 'flex', 'grid': 'grid', 'block': 'block', 'inline': 'inline', 'inline-block': 'inline-block', 'none': 'hidden' },
            'flexDirection': { 'row': 'flex-row', 'column': 'flex-col', 'row-reverse': 'flex-row-reverse', 'column-reverse': 'flex-col-reverse' },
            'justifyContent': { 'flex-start': 'justify-start', 'flex-end': 'justify-end', 'center': 'justify-center', 'space-between': 'justify-between', 'space-around': 'justify-around', 'space-evenly': 'justify-evenly' },
            'alignItems': { 'flex-start': 'items-start', 'flex-end': 'items-end', 'center': 'items-center', 'baseline': 'items-baseline', 'stretch': 'items-stretch' },
            'flexWrap': { 'wrap': 'flex-wrap', 'nowrap': 'flex-nowrap', 'wrap-reverse': 'flex-wrap-reverse' },
            // Position
            'position': { 'relative': 'relative', 'absolute': 'absolute', 'fixed': 'fixed', 'sticky': 'sticky', 'static': 'static' },
            // Text
            'textAlign': { 'left': 'text-left', 'center': 'text-center', 'right': 'text-right', 'justify': 'text-justify' },
            'fontWeight': { '100': 'font-thin', '200': 'font-extralight', '300': 'font-light', '400': 'font-normal', '500': 'font-medium', '600': 'font-semibold', '700': 'font-bold', '800': 'font-extrabold', '900': 'font-black' },
            // Overflow
            'overflow': { 'auto': 'overflow-auto', 'hidden': 'overflow-hidden', 'visible': 'overflow-visible', 'scroll': 'overflow-scroll' }
        };

        Object.keys(styles).forEach(function(prop) {
            var value = styles[prop];
            if (value === undefined || value === null || value === '') return;

            // Mapeo directo
            if (tailwindMap[prop] && tailwindMap[prop][value]) {
                classes.push(tailwindMap[prop][value]);
                return;
            }

            // Valores numéricos
            var numValue = parseFloat(value);

            switch (prop) {
                case 'gap':
                    classes.push(self.getTailwindSpacing('gap', numValue));
                    break;
                case 'padding':
                    classes.push(self.getTailwindSpacing('p', numValue));
                    break;
                case 'paddingTop':
                    classes.push(self.getTailwindSpacing('pt', numValue));
                    break;
                case 'paddingRight':
                    classes.push(self.getTailwindSpacing('pr', numValue));
                    break;
                case 'paddingBottom':
                    classes.push(self.getTailwindSpacing('pb', numValue));
                    break;
                case 'paddingLeft':
                    classes.push(self.getTailwindSpacing('pl', numValue));
                    break;
                case 'margin':
                    classes.push(self.getTailwindSpacing('m', numValue));
                    break;
                case 'marginTop':
                    classes.push(self.getTailwindSpacing('mt', numValue));
                    break;
                case 'marginRight':
                    classes.push(self.getTailwindSpacing('mr', numValue));
                    break;
                case 'marginBottom':
                    classes.push(self.getTailwindSpacing('mb', numValue));
                    break;
                case 'marginLeft':
                    classes.push(self.getTailwindSpacing('ml', numValue));
                    break;
                case 'width':
                    classes.push(self.getTailwindSize('w', value));
                    break;
                case 'height':
                    classes.push(self.getTailwindSize('h', value));
                    break;
                case 'maxWidth':
                    classes.push(self.getTailwindMaxWidth(value));
                    break;
                case 'fontSize':
                    classes.push(self.getTailwindFontSize(numValue));
                    break;
                case 'borderRadius':
                    classes.push(self.getTailwindBorderRadius(numValue));
                    break;
                case 'backgroundColor':
                    classes.push('bg-[' + value + ']');
                    break;
                case 'color':
                    classes.push('text-[' + value + ']');
                    break;
                case 'borderColor':
                    classes.push('border-[' + value + ']');
                    break;
                case 'borderWidth':
                    if (numValue === 1) {
                        classes.push('border');
                    } else if (numValue > 1) {
                        classes.push('border-' + numValue);
                    }
                    break;
                case 'opacity':
                    var opacityValue = Math.round(parseFloat(value) * 100);
                    classes.push('opacity-' + opacityValue);
                    break;
            }
        });

        return classes.filter(Boolean).join(' ');
    },

    /**
     * Genera código Styled Components
     * @param {object} styles - Objeto de estilos
     * @param {string} componentName - Nombre del componente
     * @returns {string} Código Styled Components
     */
    generateStyledComponents: function(styles, componentName) {
        componentName = componentName || 'Element';
        var self = this;

        var lines = [];
        lines.push('const ' + componentName + ' = styled.div`');

        Object.keys(styles).forEach(function(prop) {
            var value = styles[prop];
            if (value === undefined || value === null || value === '') return;
            if (prop.startsWith('_')) return; // Omitir estados especiales

            lines.push('  ' + self.camelToDash(prop) + ': ' + value + ';');
        });

        // Agregar estados hover
        if (styles._hover) {
            lines.push('');
            lines.push('  &:hover {');
            Object.keys(styles._hover).forEach(function(prop) {
                var value = styles._hover[prop];
                if (value === undefined || value === null || value === '') return;
                lines.push('    ' + self.camelToDash(prop) + ': ' + value + ';');
            });
            lines.push('  }');
        }

        lines.push('`;');
        return lines.join('\n');
    },

    /**
     * Genera objeto CSS-in-JS
     * @param {object} styles - Objeto de estilos
     * @returns {string} Código CSS-in-JS
     */
    generateCSSinJS: function(styles) {
        var self = this;
        var cleanStyles = {};

        Object.keys(styles).forEach(function(prop) {
            var value = styles[prop];
            if (value === undefined || value === null || value === '') return;
            if (prop.startsWith('_')) return;
            cleanStyles[prop] = value;
        });

        return 'const styles = ' + JSON.stringify(cleanStyles, null, 2) + ';';
    },

    // Helpers
    camelToDash: function(str) {
        return str.replace(/([A-Z])/g, '-$1').toLowerCase();
    },

    dashToCamel: function(str) {
        return str.replace(/-([a-z])/g, function(match, letter) {
            return letter.toUpperCase();
        });
    },

    isNumericProperty: function(prop) {
        var numericProps = ['width', 'height', 'minWidth', 'maxWidth', 'minHeight', 'maxHeight',
            'margin', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft',
            'padding', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'gap', 'rowGap', 'columnGap', 'fontSize', 'lineHeight', 'letterSpacing',
            'borderWidth', 'borderRadius', 'top', 'right', 'bottom', 'left'];
        return numericProps.indexOf(prop) !== -1;
    },

    findTokenForValue: function(value, tokens) {
        if (!tokens) return null;

        // Buscar en colores
        if (tokens.colors) {
            var colorKeys = Object.keys(tokens.colors);
            for (var i = 0; i < colorKeys.length; i++) {
                if (tokens.colors[colorKeys[i]] === value) {
                    return '$' + colorKeys[i];
                }
            }
        }

        // Buscar en spacing
        if (tokens.spacing) {
            var spacingKeys = Object.keys(tokens.spacing);
            for (var j = 0; j < spacingKeys.length; j++) {
                if (tokens.spacing[spacingKeys[j]] === value) {
                    return '$' + spacingKeys[j];
                }
            }
        }

        return null;
    },

    getTailwindSpacing: function(prefix, pxValue) {
        // Tailwind spacing scale: 0, px, 0.5, 1, 1.5, 2, 2.5, 3, 3.5, 4, 5, 6, 7, 8, 9, 10, 11, 12, 14, 16, 20, 24, 28, 32, 36, 40, 44, 48, 52, 56, 60, 64, 72, 80, 96
        var spacingMap = {
            0: '0', 1: 'px', 2: '0.5', 4: '1', 6: '1.5', 8: '2', 10: '2.5',
            12: '3', 14: '3.5', 16: '4', 20: '5', 24: '6', 28: '7', 32: '8',
            36: '9', 40: '10', 44: '11', 48: '12', 56: '14', 64: '16',
            80: '20', 96: '24', 112: '28', 128: '32', 144: '36', 160: '40',
            176: '44', 192: '48', 208: '52', 224: '56', 240: '60', 256: '64',
            288: '72', 320: '80', 384: '96'
        };

        if (spacingMap[pxValue] !== undefined) {
            return prefix + '-' + spacingMap[pxValue];
        }

        // Valor arbitrario
        return prefix + '-[' + pxValue + 'px]';
    },

    getTailwindSize: function(prefix, value) {
        if (value === '100%') return prefix + '-full';
        if (value === 'auto') return prefix + '-auto';
        if (value === '100vw') return prefix + '-screen';
        if (value === 'fit-content') return prefix + '-fit';
        if (value === 'min-content') return prefix + '-min';
        if (value === 'max-content') return prefix + '-max';

        var numValue = parseFloat(value);
        if (!isNaN(numValue)) {
            return this.getTailwindSpacing(prefix, numValue);
        }

        return prefix + '-[' + value + ']';
    },

    getTailwindMaxWidth: function(value) {
        var maxWidthMap = {
            '20rem': 'max-w-xs', '24rem': 'max-w-sm', '28rem': 'max-w-md',
            '32rem': 'max-w-lg', '36rem': 'max-w-xl', '42rem': 'max-w-2xl',
            '48rem': 'max-w-3xl', '56rem': 'max-w-4xl', '64rem': 'max-w-5xl',
            '72rem': 'max-w-6xl', '80rem': 'max-w-7xl', '100%': 'max-w-full',
            'none': 'max-w-none'
        };

        return maxWidthMap[value] || 'max-w-[' + value + ']';
    },

    getTailwindFontSize: function(pxValue) {
        var fontSizeMap = {
            12: 'text-xs', 14: 'text-sm', 16: 'text-base', 18: 'text-lg',
            20: 'text-xl', 24: 'text-2xl', 30: 'text-3xl', 36: 'text-4xl',
            48: 'text-5xl', 60: 'text-6xl', 72: 'text-7xl', 96: 'text-8xl',
            128: 'text-9xl'
        };

        return fontSizeMap[pxValue] || 'text-[' + pxValue + 'px]';
    },

    getTailwindBorderRadius: function(pxValue) {
        var radiusMap = {
            0: 'rounded-none', 2: 'rounded-sm', 4: 'rounded',
            6: 'rounded-md', 8: 'rounded-lg', 12: 'rounded-xl',
            16: 'rounded-2xl', 24: 'rounded-3xl', 9999: 'rounded-full'
        };

        return radiusMap[pxValue] || 'rounded-[' + pxValue + 'px]';
    }
};

/**
 * Generador de código de componentes
 */
const VBPComponentGenerator = {
    /**
     * Genera componente React
     * @param {object} element - Elemento VBP
     * @param {object} styles - Estilos del elemento
     * @returns {string} Código React
     */
    generateReact: function(element, styles) {
        var componentName = this.toComponentName(element.name || element.type);
        var lines = [];

        lines.push('import React from \'react\';');
        lines.push('import styles from \'./' + componentName + '.module.css\';');
        lines.push('');
        lines.push('export function ' + componentName + '({ children, className, ...props }) {');
        lines.push('  return (');
        lines.push('    <div className={`${styles.root} ${className || \'\'}`} {...props}>');

        // Generar contenido basado en tipo
        if (element.type === 'heading') {
            var level = (element.data && element.data.level) || 2;
            var text = (element.data && element.data.text) || 'Heading';
            lines.push('      <h' + level + '>' + this.escapeJSX(text) + '</h' + level + '>');
        } else if (element.type === 'text') {
            var textContent = (element.data && element.data.content) || 'Text content';
            lines.push('      <p>' + this.escapeJSX(textContent) + '</p>');
        } else if (element.type === 'button') {
            var buttonText = (element.data && element.data.text) || 'Button';
            lines.push('      <button type="button">' + this.escapeJSX(buttonText) + '</button>');
        } else if (element.type === 'image') {
            var src = (element.data && element.data.src) || '';
            var alt = (element.data && element.data.alt) || '';
            lines.push('      <img src="' + src + '" alt="' + this.escapeJSX(alt) + '" />');
        } else {
            lines.push('      {children}');
        }

        lines.push('    </div>');
        lines.push('  );');
        lines.push('}');
        lines.push('');
        lines.push('export default ' + componentName + ';');

        return lines.join('\n');
    },

    /**
     * Genera componente Vue
     * @param {object} element - Elemento VBP
     * @param {object} styles - Estilos del elemento
     * @returns {string} Código Vue
     */
    generateVue: function(element, styles) {
        var componentName = this.toComponentName(element.name || element.type);
        var lines = [];

        lines.push('<template>');
        lines.push('  <div :class="[\'root\', className]" v-bind="$attrs">');

        // Generar contenido basado en tipo
        if (element.type === 'heading') {
            var level = (element.data && element.data.level) || 2;
            var text = (element.data && element.data.text) || 'Heading';
            lines.push('    <h' + level + '>{{ title || \'' + this.escapeTemplate(text) + '\' }}</h' + level + '>');
        } else if (element.type === 'text') {
            var textContent = (element.data && element.data.content) || 'Text content';
            lines.push('    <p>{{ content || \'' + this.escapeTemplate(textContent) + '\' }}</p>');
        } else if (element.type === 'button') {
            var buttonText = (element.data && element.data.text) || 'Button';
            lines.push('    <button type="button" @click="$emit(\'click\')">{{ text || \'' + this.escapeTemplate(buttonText) + '\' }}</button>');
        } else if (element.type === 'image') {
            lines.push('    <img :src="src" :alt="alt" />');
        } else {
            lines.push('    <slot />');
        }

        lines.push('  </div>');
        lines.push('</template>');
        lines.push('');
        lines.push('<script setup>');
        lines.push('defineProps({');
        lines.push('  className: String,');

        if (element.type === 'heading') {
            lines.push('  title: String,');
        } else if (element.type === 'text') {
            lines.push('  content: String,');
        } else if (element.type === 'button') {
            lines.push('  text: String,');
        } else if (element.type === 'image') {
            lines.push('  src: String,');
            lines.push('  alt: String,');
        }

        lines.push('});');
        lines.push('</script>');
        lines.push('');
        lines.push('<style scoped>');
        lines.push(VBPCodeGenerator.generateCSS(styles, '.root'));
        lines.push('</style>');

        return lines.join('\n');
    },

    /**
     * Genera componente HTML
     * @param {object} element - Elemento VBP
     * @param {object} styles - Estilos del elemento
     * @returns {string} Código HTML
     */
    generateHTML: function(element, styles) {
        var lines = [];
        var className = this.toClassName(element.name || element.type);

        lines.push('<!-- ' + (element.name || element.type) + ' -->');
        lines.push('<div class="' + className + '">');

        // Generar contenido basado en tipo
        if (element.type === 'heading') {
            var level = (element.data && element.data.level) || 2;
            var text = (element.data && element.data.text) || 'Heading';
            lines.push('  <h' + level + '>' + text + '</h' + level + '>');
        } else if (element.type === 'text') {
            var textContent = (element.data && element.data.content) || 'Text content';
            lines.push('  <p>' + textContent + '</p>');
        } else if (element.type === 'button') {
            var buttonText = (element.data && element.data.text) || 'Button';
            var url = (element.data && element.data.url) || '#';
            lines.push('  <a href="' + url + '" class="button">' + buttonText + '</a>');
        } else if (element.type === 'image') {
            var src = (element.data && element.data.src) || '';
            var alt = (element.data && element.data.alt) || '';
            lines.push('  <img src="' + src + '" alt="' + alt + '" />');
        } else {
            lines.push('  <!-- Content -->');
        }

        lines.push('</div>');
        lines.push('');
        lines.push('<style>');
        lines.push(VBPCodeGenerator.generateCSS(styles, '.' + className));
        lines.push('</style>');

        return lines.join('\n');
    },

    // Helpers
    toComponentName: function(name) {
        return name
            .split(/[\s-_]+/)
            .map(function(word) {
                return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
            })
            .join('');
    },

    toClassName: function(name) {
        return name
            .toLowerCase()
            .replace(/[\s_]+/g, '-')
            .replace(/[^a-z0-9-]/g, '');
    },

    escapeJSX: function(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;')
            .replace(/{/g, '&#123;')
            .replace(/}/g, '&#125;');
    },

    escapeTemplate: function(str) {
        return String(str)
            .replace(/'/g, "\\'")
            .replace(/"/g, '\\"');
    }
};

/**
 * Extractor de estilos computados
 */
const VBPStyleExtractor = {
    /**
     * Extrae estilos computados de un elemento DOM
     * @param {HTMLElement} domElement - Elemento DOM
     * @returns {object} Estilos categorizados
     */
    extractFromDOM: function(domElement) {
        if (!domElement) return this.getEmptyStyles();

        var computed = window.getComputedStyle(domElement);

        return {
            layout: this.extractLayout(computed),
            typography: this.extractTypography(computed),
            background: this.extractBackground(computed),
            border: this.extractBorder(computed),
            effects: this.extractEffects(computed),
            position: this.extractPosition(computed)
        };
    },

    /**
     * Extrae estilos de un elemento VBP
     * @param {object} element - Elemento VBP
     * @returns {object} Estilos categorizados
     */
    extractFromVBP: function(element) {
        if (!element || !element.styles) return this.getEmptyStyles();

        var styles = element.styles.desktop || element.styles;

        return {
            layout: {
                display: styles.display || 'block',
                flexDirection: styles.flexDirection || '',
                justifyContent: styles.justifyContent || '',
                alignItems: styles.alignItems || '',
                flexWrap: styles.flexWrap || '',
                gap: styles.gap || '',
                padding: this.formatBoxModel(styles, 'padding'),
                margin: this.formatBoxModel(styles, 'margin'),
                width: styles.width || 'auto',
                height: styles.height || 'auto',
                minWidth: styles.minWidth || '',
                maxWidth: styles.maxWidth || '',
                minHeight: styles.minHeight || '',
                maxHeight: styles.maxHeight || ''
            },
            typography: {
                fontFamily: styles.fontFamily || '',
                fontSize: styles.fontSize || '',
                fontWeight: styles.fontWeight || '',
                fontStyle: styles.fontStyle || '',
                lineHeight: styles.lineHeight || '',
                letterSpacing: styles.letterSpacing || '',
                textAlign: styles.textAlign || '',
                textTransform: styles.textTransform || '',
                textDecoration: styles.textDecoration || '',
                color: styles.color || ''
            },
            background: {
                backgroundColor: styles.backgroundColor || '',
                backgroundImage: styles.backgroundImage || '',
                backgroundSize: styles.backgroundSize || '',
                backgroundPosition: styles.backgroundPosition || '',
                backgroundRepeat: styles.backgroundRepeat || ''
            },
            border: {
                borderWidth: styles.borderWidth || '',
                borderStyle: styles.borderStyle || '',
                borderColor: styles.borderColor || '',
                borderRadius: styles.borderRadius || '',
                borderTop: styles.borderTop || '',
                borderRight: styles.borderRight || '',
                borderBottom: styles.borderBottom || '',
                borderLeft: styles.borderLeft || ''
            },
            effects: {
                boxShadow: styles.boxShadow || '',
                textShadow: styles.textShadow || '',
                opacity: styles.opacity || '1',
                transform: styles.transform || '',
                transition: styles.transition || ''
            },
            position: {
                position: styles.position || 'static',
                top: styles.top || '',
                right: styles.right || '',
                bottom: styles.bottom || '',
                left: styles.left || '',
                zIndex: styles.zIndex || ''
            }
        };
    },

    /**
     * Convierte estilos categorizados a objeto plano
     * @param {object} categorizedStyles - Estilos categorizados
     * @returns {object} Objeto plano de estilos
     */
    flatten: function(categorizedStyles) {
        var flat = {};

        Object.keys(categorizedStyles).forEach(function(category) {
            var categoryStyles = categorizedStyles[category];
            Object.keys(categoryStyles).forEach(function(prop) {
                var value = categoryStyles[prop];
                if (value !== '' && value !== undefined && value !== null) {
                    flat[prop] = value;
                }
            });
        });

        return flat;
    },

    // Helpers
    extractLayout: function(computed) {
        return {
            display: computed.display,
            flexDirection: computed.flexDirection,
            justifyContent: computed.justifyContent,
            alignItems: computed.alignItems,
            flexWrap: computed.flexWrap,
            gap: computed.gap,
            padding: computed.padding,
            margin: computed.margin,
            width: computed.width,
            height: computed.height,
            minWidth: computed.minWidth,
            maxWidth: computed.maxWidth,
            minHeight: computed.minHeight,
            maxHeight: computed.maxHeight
        };
    },

    extractTypography: function(computed) {
        return {
            fontFamily: computed.fontFamily,
            fontSize: computed.fontSize,
            fontWeight: computed.fontWeight,
            fontStyle: computed.fontStyle,
            lineHeight: computed.lineHeight,
            letterSpacing: computed.letterSpacing,
            textAlign: computed.textAlign,
            textTransform: computed.textTransform,
            textDecoration: computed.textDecoration,
            color: computed.color
        };
    },

    extractBackground: function(computed) {
        return {
            backgroundColor: computed.backgroundColor,
            backgroundImage: computed.backgroundImage,
            backgroundSize: computed.backgroundSize,
            backgroundPosition: computed.backgroundPosition,
            backgroundRepeat: computed.backgroundRepeat
        };
    },

    extractBorder: function(computed) {
        return {
            borderWidth: computed.borderWidth,
            borderStyle: computed.borderStyle,
            borderColor: computed.borderColor,
            borderRadius: computed.borderRadius,
            borderTop: computed.borderTop,
            borderRight: computed.borderRight,
            borderBottom: computed.borderBottom,
            borderLeft: computed.borderLeft
        };
    },

    extractEffects: function(computed) {
        return {
            boxShadow: computed.boxShadow,
            textShadow: computed.textShadow,
            opacity: computed.opacity,
            transform: computed.transform,
            transition: computed.transition
        };
    },

    extractPosition: function(computed) {
        return {
            position: computed.position,
            top: computed.top,
            right: computed.right,
            bottom: computed.bottom,
            left: computed.left,
            zIndex: computed.zIndex
        };
    },

    formatBoxModel: function(styles, prop) {
        var top = styles[prop + 'Top'] || '0';
        var right = styles[prop + 'Right'] || '0';
        var bottom = styles[prop + 'Bottom'] || '0';
        var left = styles[prop + 'Left'] || '0';

        if (top === right && right === bottom && bottom === left) {
            return top;
        }
        if (top === bottom && left === right) {
            return top + ' ' + right;
        }
        return top + ' ' + right + ' ' + bottom + ' ' + left;
    },

    getEmptyStyles: function() {
        return {
            layout: {},
            typography: {},
            background: {},
            border: {},
            effects: {},
            position: {}
        };
    }
};

/**
 * Extractor de Design Tokens
 */
const VBPTokenExtractor = {
    /**
     * Extrae tokens usados en un elemento
     * @param {object} element - Elemento VBP
     * @param {object} globalTokens - Tokens globales del proyecto
     * @returns {object} Tokens encontrados
     */
    extractUsedTokens: function(element, globalTokens) {
        globalTokens = globalTokens || this.getDefaultTokens();

        var usedTokens = {
            colors: {},
            spacing: {},
            typography: {},
            shadows: {},
            borders: {}
        };

        if (!element || !element.styles) return usedTokens;

        var styles = element.styles.desktop || element.styles;
        var self = this;

        // Extraer colores
        var colorProps = ['color', 'backgroundColor', 'borderColor'];
        colorProps.forEach(function(prop) {
            if (styles[prop]) {
                var tokenMatch = self.findMatchingToken(styles[prop], globalTokens.colors);
                if (tokenMatch) {
                    usedTokens.colors[tokenMatch.name] = tokenMatch.value;
                }
            }
        });

        // Extraer spacing
        var spacingProps = ['padding', 'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft',
            'margin', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'gap'];
        spacingProps.forEach(function(prop) {
            if (styles[prop]) {
                var tokenMatch = self.findMatchingToken(styles[prop], globalTokens.spacing);
                if (tokenMatch) {
                    usedTokens.spacing[tokenMatch.name] = tokenMatch.value;
                }
            }
        });

        // Extraer typography
        var typoProps = ['fontSize', 'fontFamily', 'fontWeight', 'lineHeight'];
        typoProps.forEach(function(prop) {
            if (styles[prop]) {
                var tokenMatch = self.findMatchingToken(styles[prop], globalTokens.typography);
                if (tokenMatch) {
                    usedTokens.typography[tokenMatch.name] = tokenMatch.value;
                }
            }
        });

        // Extraer shadows
        if (styles.boxShadow) {
            var shadowMatch = this.findMatchingToken(styles.boxShadow, globalTokens.shadows);
            if (shadowMatch) {
                usedTokens.shadows[shadowMatch.name] = shadowMatch.value;
            }
        }

        // Extraer border radius
        if (styles.borderRadius) {
            var radiusMatch = this.findMatchingToken(styles.borderRadius, globalTokens.borders);
            if (radiusMatch) {
                usedTokens.borders[radiusMatch.name] = radiusMatch.value;
            }
        }

        return usedTokens;
    },

    /**
     * Busca un token que coincida con el valor
     * @param {string} value - Valor a buscar
     * @param {object} tokenSet - Set de tokens donde buscar
     * @returns {object|null} Token encontrado o null
     */
    findMatchingToken: function(value, tokenSet) {
        if (!tokenSet) return null;

        var normalizedValue = this.normalizeValue(value);

        var keys = Object.keys(tokenSet);
        for (var i = 0; i < keys.length; i++) {
            var tokenName = keys[i];
            var tokenValue = tokenSet[tokenName];
            if (this.normalizeValue(tokenValue) === normalizedValue) {
                return { name: tokenName, value: tokenValue };
            }
        }

        return null;
    },

    /**
     * Normaliza un valor para comparación
     * @param {string} value - Valor a normalizar
     * @returns {string} Valor normalizado
     */
    normalizeValue: function(value) {
        if (!value) return '';

        // Normalizar colores
        if (value.startsWith('#')) {
            return value.toLowerCase();
        }

        // Normalizar rgb/rgba
        if (value.startsWith('rgb')) {
            return value.replace(/\s+/g, '');
        }

        // Normalizar valores numéricos
        return String(value).trim().toLowerCase();
    },

    /**
     * Obtiene tokens por defecto
     * @returns {object} Tokens por defecto
     */
    getDefaultTokens: function() {
        return {
            colors: {
                '--color-primary': '#6366f1',
                '--color-primary-dark': '#4f46e5',
                '--color-secondary': '#64748b',
                '--color-success': '#22c55e',
                '--color-warning': '#f59e0b',
                '--color-danger': '#ef4444',
                '--color-text-primary': '#1a1a1a',
                '--color-text-secondary': '#6b7280',
                '--color-text-muted': '#9ca3af',
                '--color-bg-primary': '#ffffff',
                '--color-bg-secondary': '#f9fafb',
                '--color-bg-tertiary': '#f3f4f6',
                '--color-border': '#e5e7eb'
            },
            spacing: {
                '--spacing-xs': '4px',
                '--spacing-sm': '8px',
                '--spacing-md': '16px',
                '--spacing-lg': '24px',
                '--spacing-xl': '32px',
                '--spacing-2xl': '48px',
                '--spacing-3xl': '64px'
            },
            typography: {
                '--font-family-base': 'Inter, system-ui, sans-serif',
                '--font-family-heading': 'Inter, system-ui, sans-serif',
                '--font-family-mono': 'JetBrains Mono, monospace',
                '--font-size-xs': '12px',
                '--font-size-sm': '14px',
                '--font-size-base': '16px',
                '--font-size-lg': '18px',
                '--font-size-xl': '20px',
                '--font-size-2xl': '24px',
                '--font-size-3xl': '30px',
                '--font-size-4xl': '36px'
            },
            shadows: {
                '--shadow-sm': '0 1px 2px rgba(0, 0, 0, 0.05)',
                '--shadow-md': '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                '--shadow-lg': '0 10px 15px -3px rgba(0, 0, 0, 0.1)',
                '--shadow-xl': '0 20px 25px -5px rgba(0, 0, 0, 0.1)'
            },
            borders: {
                '--radius-sm': '4px',
                '--radius-md': '6px',
                '--radius-lg': '8px',
                '--radius-xl': '12px',
                '--radius-2xl': '16px',
                '--radius-full': '9999px'
            }
        };
    },

    /**
     * Genera CSS con variables de tokens
     * @param {object} tokens - Tokens a generar
     * @returns {string} CSS con variables
     */
    generateTokensCSS: function(tokens) {
        var lines = [':root {'];

        Object.keys(tokens).forEach(function(category) {
            var categoryTokens = tokens[category];
            if (Object.keys(categoryTokens).length > 0) {
                lines.push('  /* ' + category + ' */');
                Object.keys(categoryTokens).forEach(function(tokenName) {
                    lines.push('  ' + tokenName + ': ' + categoryTokens[tokenName] + ';');
                });
                lines.push('');
            }
        });

        lines.push('}');
        return lines.join('\n');
    }
};

/**
 * Exportador de Assets
 */
const VBPAssetExporter = {
    /**
     * Extrae assets de un elemento
     * @param {object} element - Elemento VBP
     * @returns {array} Lista de assets
     */
    extractAssets: function(element) {
        var assets = [];

        if (!element) return assets;

        // Imágenes en data
        if (element.data) {
            if (element.data.src) {
                assets.push({
                    name: this.getAssetName(element.data.src),
                    type: this.getAssetType(element.data.src),
                    url: element.data.src,
                    source: 'data.src'
                });
            }
            if (element.data.backgroundImage) {
                assets.push({
                    name: this.getAssetName(element.data.backgroundImage),
                    type: this.getAssetType(element.data.backgroundImage),
                    url: element.data.backgroundImage,
                    source: 'data.backgroundImage'
                });
            }
            // Items con imágenes
            if (element.data.items && Array.isArray(element.data.items)) {
                element.data.items.forEach(function(item, index) {
                    if (item.image) {
                        assets.push({
                            name: this.getAssetName(item.image),
                            type: this.getAssetType(item.image),
                            url: item.image,
                            source: 'data.items[' + index + '].image'
                        });
                    }
                    if (item.icon && item.icon.startsWith('http')) {
                        assets.push({
                            name: this.getAssetName(item.icon),
                            type: 'svg',
                            url: item.icon,
                            source: 'data.items[' + index + '].icon'
                        });
                    }
                }.bind(this));
            }
        }

        // Imágenes en estilos
        if (element.styles) {
            var styles = element.styles.desktop || element.styles;
            if (styles.backgroundImage && styles.backgroundImage !== 'none') {
                var urlMatch = styles.backgroundImage.match(/url\(['"]?([^'"]+)['"]?\)/);
                if (urlMatch && urlMatch[1]) {
                    assets.push({
                        name: this.getAssetName(urlMatch[1]),
                        type: this.getAssetType(urlMatch[1]),
                        url: urlMatch[1],
                        source: 'styles.backgroundImage'
                    });
                }
            }
        }

        // Buscar en hijos recursivamente
        if (element.children && Array.isArray(element.children)) {
            var self = this;
            element.children.forEach(function(child) {
                assets = assets.concat(self.extractAssets(child));
            });
        }

        return assets;
    },

    /**
     * Descarga un asset
     * @param {object} asset - Asset a descargar
     * @param {object} options - Opciones de descarga
     */
    downloadAsset: function(asset, options) {
        options = options || {};

        var link = document.createElement('a');
        link.href = asset.url;
        link.download = options.filename || asset.name;
        link.target = '_blank';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    },

    /**
     * Descarga todos los assets
     * @param {array} assets - Lista de assets
     * @param {object} options - Opciones de descarga
     */
    downloadAllAssets: function(assets, options) {
        var self = this;
        var delay = 500; // Delay entre descargas

        assets.forEach(function(asset, index) {
            setTimeout(function() {
                self.downloadAsset(asset, options);
            }, index * delay);
        });
    },

    /**
     * Genera ZIP con todos los assets (si JSZip está disponible)
     * @param {array} assets - Lista de assets
     * @param {string} filename - Nombre del archivo ZIP
     * @returns {Promise} Promesa con el blob del ZIP
     */
    generateZip: function(assets, filename) {
        filename = filename || 'assets.zip';

        if (typeof JSZip === 'undefined') {
            vbpLog.warn('JSZip no está disponible. Use downloadAllAssets en su lugar.');
            return Promise.reject(new Error('JSZip not available'));
        }

        var zip = new JSZip();
        var fetchPromises = [];

        assets.forEach(function(asset) {
            var fetchPromise = fetch(asset.url)
                .then(function(response) {
                    return response.blob();
                })
                .then(function(blob) {
                    zip.file(asset.name, blob);
                })
                .catch(function(error) {
                    vbpLog.warn('No se pudo descargar: ' + asset.url, error);
                });

            fetchPromises.push(fetchPromise);
        });

        return Promise.all(fetchPromises).then(function() {
            return zip.generateAsync({ type: 'blob' });
        }).then(function(blob) {
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(link.href);
            return blob;
        });
    },

    // Helpers
    getAssetName: function(url) {
        if (!url) return 'asset';

        try {
            var urlObj = new URL(url, window.location.origin);
            var pathname = urlObj.pathname;
            var filename = pathname.split('/').pop();
            return filename || 'asset';
        } catch (error) {
            var parts = url.split('/');
            return parts[parts.length - 1] || 'asset';
        }
    },

    getAssetType: function(url) {
        if (!url) return 'unknown';

        var extension = url.split('.').pop().toLowerCase().split('?')[0];

        var imageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'ico'];
        var svgTypes = ['svg'];
        var videoTypes = ['mp4', 'webm', 'ogg', 'mov'];
        var audioTypes = ['mp3', 'wav', 'ogg', 'aac'];

        if (imageTypes.indexOf(extension) !== -1) return 'image';
        if (svgTypes.indexOf(extension) !== -1) return 'svg';
        if (videoTypes.indexOf(extension) !== -1) return 'video';
        if (audioTypes.indexOf(extension) !== -1) return 'audio';

        return 'unknown';
    }
};

/**
 * Sistema de medición de elementos
 */
const VBPMeasurement = {
    /**
     * Obtiene medidas de un elemento DOM
     * @param {HTMLElement} element - Elemento DOM
     * @returns {object} Medidas del elemento
     */
    measure: function(element) {
        if (!element) return null;

        var rect = element.getBoundingClientRect();
        var computed = window.getComputedStyle(element);

        return {
            // Posición
            x: Math.round(rect.left),
            y: Math.round(rect.top),
            // Tamaño
            width: Math.round(rect.width),
            height: Math.round(rect.height),
            // Box model
            padding: {
                top: parseFloat(computed.paddingTop),
                right: parseFloat(computed.paddingRight),
                bottom: parseFloat(computed.paddingBottom),
                left: parseFloat(computed.paddingLeft)
            },
            margin: {
                top: parseFloat(computed.marginTop),
                right: parseFloat(computed.marginRight),
                bottom: parseFloat(computed.marginBottom),
                left: parseFloat(computed.marginLeft)
            },
            border: {
                top: parseFloat(computed.borderTopWidth),
                right: parseFloat(computed.borderRightWidth),
                bottom: parseFloat(computed.borderBottomWidth),
                left: parseFloat(computed.borderLeftWidth)
            },
            // Tamaño del contenido
            contentWidth: Math.round(rect.width - parseFloat(computed.paddingLeft) - parseFloat(computed.paddingRight) - parseFloat(computed.borderLeftWidth) - parseFloat(computed.borderRightWidth)),
            contentHeight: Math.round(rect.height - parseFloat(computed.paddingTop) - parseFloat(computed.paddingBottom) - parseFloat(computed.borderTopWidth) - parseFloat(computed.borderBottomWidth))
        };
    },

    /**
     * Calcula distancia entre dos elementos
     * @param {HTMLElement} elementA - Primer elemento
     * @param {HTMLElement} elementB - Segundo elemento
     * @returns {object} Distancias entre elementos
     */
    measureDistance: function(elementA, elementB) {
        if (!elementA || !elementB) return null;

        var rectA = elementA.getBoundingClientRect();
        var rectB = elementB.getBoundingClientRect();

        return {
            horizontal: {
                left: Math.round(rectB.left - rectA.right),
                right: Math.round(rectA.left - rectB.right),
                center: Math.round((rectB.left + rectB.width / 2) - (rectA.left + rectA.width / 2))
            },
            vertical: {
                top: Math.round(rectB.top - rectA.bottom),
                bottom: Math.round(rectA.top - rectB.bottom),
                center: Math.round((rectB.top + rectB.height / 2) - (rectA.top + rectA.height / 2))
            },
            // Distancia más corta
            shortestHorizontal: Math.min(
                Math.abs(rectB.left - rectA.right),
                Math.abs(rectA.left - rectB.right)
            ),
            shortestVertical: Math.min(
                Math.abs(rectB.top - rectA.bottom),
                Math.abs(rectA.top - rectB.bottom)
            )
        };
    },

    /**
     * Obtiene posición relativa al canvas
     * @param {HTMLElement} element - Elemento
     * @param {HTMLElement} canvas - Canvas de referencia
     * @returns {object} Posición relativa
     */
    getRelativePosition: function(element, canvas) {
        if (!element || !canvas) return null;

        var elementRect = element.getBoundingClientRect();
        var canvasRect = canvas.getBoundingClientRect();

        return {
            x: Math.round(elementRect.left - canvasRect.left),
            y: Math.round(elementRect.top - canvasRect.top),
            width: Math.round(elementRect.width),
            height: Math.round(elementRect.height)
        };
    }
};

/**
 * Comparador de estilos (diseño vs implementación)
 */
const VBPStyleComparator = {
    /**
     * Compara estilos de diseño con implementación
     * @param {object} designStyles - Estilos del diseño
     * @param {object} codeStyles - Estilos implementados
     * @returns {object} Resultado de comparación
     */
    compare: function(designStyles, codeStyles) {
        var result = {
            matches: [],
            differs: [],
            missing: [],
            extra: []
        };

        var self = this;

        // Propiedades que coinciden
        Object.keys(designStyles).forEach(function(prop) {
            var designValue = designStyles[prop];
            var codeValue = codeStyles[prop];

            if (codeValue === undefined) {
                result.missing.push({
                    property: prop,
                    design: designValue
                });
            } else if (self.valuesMatch(designValue, codeValue)) {
                result.matches.push(prop);
            } else {
                result.differs.push({
                    property: prop,
                    design: designValue,
                    code: codeValue
                });
            }
        });

        // Propiedades extra en código
        Object.keys(codeStyles).forEach(function(prop) {
            if (designStyles[prop] === undefined) {
                result.extra.push({
                    property: prop,
                    code: codeStyles[prop]
                });
            }
        });

        // Calcular porcentaje de coincidencia
        var totalProps = Object.keys(designStyles).length;
        result.matchPercentage = totalProps > 0 ? Math.round((result.matches.length / totalProps) * 100) : 100;

        return result;
    },

    /**
     * Compara si dos valores son equivalentes
     * @param {string} value1 - Primer valor
     * @param {string} value2 - Segundo valor
     * @returns {boolean} True si son equivalentes
     */
    valuesMatch: function(value1, value2) {
        if (value1 === value2) return true;

        // Normalizar valores
        var normalizedValue1 = this.normalizeValue(value1);
        var normalizedValue2 = this.normalizeValue(value2);

        if (normalizedValue1 === normalizedValue2) return true;

        // Comparar valores numéricos con tolerancia
        var numericValue1 = parseFloat(value1);
        var numericValue2 = parseFloat(value2);

        if (!isNaN(numericValue1) && !isNaN(numericValue2)) {
            return Math.abs(numericValue1 - numericValue2) < 1; // 1px de tolerancia
        }

        return false;
    },

    /**
     * Normaliza un valor para comparación
     * @param {string} value - Valor a normalizar
     * @returns {string} Valor normalizado
     */
    normalizeValue: function(value) {
        if (!value) return '';

        var stringValue = String(value).trim().toLowerCase();

        // Normalizar colores
        if (stringValue.startsWith('#')) {
            // Expandir formato corto
            if (stringValue.length === 4) {
                return '#' + stringValue[1] + stringValue[1] + stringValue[2] + stringValue[2] + stringValue[3] + stringValue[3];
            }
            return stringValue;
        }

        // Normalizar rgb a hex
        var rgbMatch = stringValue.match(/rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)/);
        if (rgbMatch) {
            var r = parseInt(rgbMatch[1]).toString(16).padStart(2, '0');
            var g = parseInt(rgbMatch[2]).toString(16).padStart(2, '0');
            var b = parseInt(rgbMatch[3]).toString(16).padStart(2, '0');
            return '#' + r + g + b;
        }

        // Normalizar valores numéricos
        var numericValue = parseFloat(stringValue);
        if (!isNaN(numericValue)) {
            var unit = stringValue.replace(/[\d.-]/g, '').trim();
            return Math.round(numericValue) + (unit || 'px');
        }

        return stringValue;
    }
};

// Exportar utilidades globalmente
window.VBPUnitConverter = VBPUnitConverter;
window.VBPCodeGenerator = VBPCodeGenerator;
window.VBPComponentGenerator = VBPComponentGenerator;
window.VBPStyleExtractor = VBPStyleExtractor;
window.VBPTokenExtractor = VBPTokenExtractor;
window.VBPAssetExporter = VBPAssetExporter;
window.VBPMeasurement = VBPMeasurement;
window.VBPStyleComparator = VBPStyleComparator;

vbpLog.log('VBP Dev Mode utilities loaded');
