/**
 * Visual Builder Pro - Keyboard Export Module
 * Exportación a HTML, CSS, Tailwind, React, Vue, Svelte
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPKeyboardExport = {
    /**
     * Abrir opciones de exportación
     */
    openExportOptions: function() {
        var self = this;
        var store = Alpine.store('vbp');

        var modalId = 'vbp-export-modal';
        var existente = document.getElementById(modalId);
        if (existente) existente.remove();

        var html = '<div id="' + modalId + '" class="vbp-modal-overlay">';
        html += '<div class="vbp-modal" style="max-width: 650px;">';
        html += '<div class="vbp-modal-header">';
        html += '<h2>📤 Exportar Diseño</h2>';
        html += '<button class="vbp-modal-close" onclick="document.getElementById(\'' + modalId + '\').remove()">&times;</button>';
        html += '</div>';
        html += '<div class="vbp-modal-body">';

        html += '<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">';

        var opciones = [
            { type: 'html', icon: '🌐', titulo: 'HTML + CSS', desc: 'Código semántico' },
            { type: 'tailwind', icon: '🎐', titulo: 'Tailwind CSS', desc: 'Clases de utilidad' },
            { type: 'react', icon: '⚛️', titulo: 'React', desc: 'Componente JSX' },
            { type: 'vue', icon: '💚', titulo: 'Vue 3', desc: 'SFC con setup' },
            { type: 'svelte', icon: '🔥', titulo: 'Svelte', desc: 'Componente .svelte' },
            { type: 'json', icon: '{ }', titulo: 'JSON', desc: 'Datos estructurados' }
        ];

        opciones.forEach(function(opt) {
            html += '<div class="export-option" data-type="' + opt.type + '" style="background: var(--vbp-surface, #313244); border: 1px solid var(--vbp-border, #45475a); border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.2s; text-align: center;">';
            html += '<div style="font-size: 28px; margin-bottom: 8px;">' + opt.icon + '</div>';
            html += '<div style="font-weight: 600; font-size: 13px;">' + opt.titulo + '</div>';
            html += '<div style="font-size: 11px; color: var(--vbp-text-muted, #6c7086);">' + opt.desc + '</div>';
            html += '</div>';
        });

        html += '</div>';

        html += '<div id="export-output" style="display: none; margin-top: 20px;">';
        html += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">';
        html += '<span id="export-type-label" style="font-weight: 600;"></span>';
        html += '<div style="display: flex; gap: 8px;">';
        html += '<button id="copy-export" style="padding: 6px 12px; background: var(--vbp-primary, #89b4fa); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">📋 Copiar</button>';
        html += '<button id="download-export" style="padding: 6px 12px; background: var(--vbp-success, #a6e3a1); color: #1e1e2e; border: none; border-radius: 6px; cursor: pointer; font-size: 12px;">💾 Descargar</button>';
        html += '</div>';
        html += '</div>';
        html += '<pre id="export-code" style="background: #1a1b26; color: #a9b1d6; padding: 16px; border-radius: 8px; overflow-x: auto; max-height: 300px; font-size: 12px; line-height: 1.5;"></pre>';
        html += '</div>';

        html += '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);

        var modal = document.getElementById(modalId);

        modal.querySelectorAll('.export-option').forEach(function(opt) {
            opt.addEventListener('mouseenter', function() {
                this.style.borderColor = 'var(--vbp-primary, #89b4fa)';
                this.style.transform = 'translateY(-2px)';
            });
            opt.addEventListener('mouseleave', function() {
                this.style.borderColor = 'var(--vbp-border, #45475a)';
                this.style.transform = '';
            });
            opt.addEventListener('click', function() {
                var type = this.dataset.type;
                var code = self.generateExport(type, store.elements);

                document.getElementById('export-output').style.display = '';
                document.getElementById('export-type-label').textContent = this.querySelector('div:nth-child(2)').textContent;
                document.getElementById('export-code').textContent = code;

                modal.querySelectorAll('.export-option').forEach(function(o) {
                    o.style.borderColor = 'var(--vbp-border, #45475a)';
                });
                this.style.borderColor = 'var(--vbp-primary, #89b4fa)';
            });
        });

        modal.querySelector('#copy-export').addEventListener('click', function() {
            var code = document.getElementById('export-code').textContent;
            navigator.clipboard.writeText(code).then(function() {
                window.vbpKeyboard.showNotification('📋 Código copiado');
            });
        });

        modal.querySelector('#download-export').addEventListener('click', function() {
            var code = document.getElementById('export-code').textContent;
            var label = document.getElementById('export-type-label').textContent.toLowerCase();
            var extension = self.getExtension(label);
            var blob = new Blob([code], { type: 'text/plain' });
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = 'design-export' + extension;
            a.click();
            URL.revokeObjectURL(url);
            window.vbpKeyboard.showNotification('💾 Archivo descargado');
        });
    },

    /**
     * Obtener extensión de archivo
     */
    getExtension: function(label) {
        var extensiones = {
            'html + css': '.html',
            'tailwind css': '.html',
            'react': '.jsx',
            'vue 3': '.vue',
            'svelte': '.svelte',
            'json': '.json'
        };
        return extensiones[label] || '.txt';
    },

    /**
     * Generar código de exportación
     */
    generateExport: function(type, elements) {
        switch (type) {
            case 'html':
                return this.generateHTML(elements);
            case 'tailwind':
                return this.generateTailwind(elements);
            case 'react':
                return this.generateReact(elements);
            case 'vue':
                return this.generateVue(elements);
            case 'svelte':
                return this.generateSvelte(elements);
            case 'json':
                return JSON.stringify(elements, null, 2);
            default:
                return '';
        }
    },

    /**
     * Generar HTML limpio
     */
    generateHTML: function(elements) {
        var self = this;
        var html = '<!DOCTYPE html>\n<html lang="es">\n<head>\n  <meta charset="UTF-8">\n  <meta name="viewport" content="width=device-width, initial-scale=1.0">\n  <title>Mi Página</title>\n  <style>\n';

        html += this.generateCSS(elements);

        html += '\n  </style>\n</head>\n<body>\n';

        elements.forEach(function(el) {
            html += '  ' + self.elementToHTML(el, 1) + '\n';
        });

        html += '</body>\n</html>';

        return html;
    },

    /**
     * Elemento a HTML
     */
    elementToHTML: function(element, indent) {
        var self = this;
        var spaces = '  '.repeat(indent);
        var tag = this.getHTMLTag(element.type);
        var className = 'el-' + element.id.replace(/[^a-zA-Z0-9]/g, '');

        var html = spaces + '<' + tag + ' class="' + className + '">';

        if (element.content) {
            html += element.content;
        }

        if (element.children && element.children.length) {
            html += '\n';
            element.children.forEach(function(child) {
                html += self.elementToHTML(child, indent + 1) + '\n';
            });
            html += spaces;
        }

        html += '</' + tag + '>';

        return html;
    },

    /**
     * Obtener tag HTML según tipo
     */
    getHTMLTag: function(type) {
        var tags = {
            'text': 'p',
            'paragraph': 'p',
            'heading': 'h2',
            'button': 'button',
            'image': 'img',
            'container': 'div',
            'columns': 'div',
            'row': 'div',
            'section': 'section',
            'hero': 'section',
            'features': 'section',
            'input': 'input',
            'link': 'a',
            'list': 'ul',
            'nav': 'nav',
            'footer': 'footer',
            'header': 'header',
            'article': 'article',
            'aside': 'aside'
        };
        return tags[type] || 'div';
    },

    /**
     * Generar CSS
     */
    generateCSS: function(elements) {
        var self = this;
        var css = '    * { margin: 0; padding: 0; box-sizing: border-box; }\n';
        css += '    body { font-family: system-ui, -apple-system, sans-serif; }\n\n';

        elements.forEach(function(el) {
            css += self.elementToCSS(el);
        });

        return css;
    },

    /**
     * Elemento a CSS
     */
    elementToCSS: function(element) {
        var self = this;
        var className = 'el-' + element.id.replace(/[^a-zA-Z0-9]/g, '');
        var css = '    .' + className + ' {\n';

        if (element.styles) {
            if (element.styles.layout) {
                var layout = element.styles.layout;
                if (layout.display) css += '      display: ' + layout.display + ';\n';
                if (layout.flexDirection) css += '      flex-direction: ' + layout.flexDirection + ';\n';
                if (layout.alignItems) css += '      align-items: ' + layout.alignItems + ';\n';
                if (layout.justifyContent) css += '      justify-content: ' + layout.justifyContent + ';\n';
                if (layout.gap) css += '      gap: ' + layout.gap + ';\n';
            }

            if (element.styles.spacing) {
                var sp = element.styles.spacing;
                if (sp.padding && (sp.padding.top || sp.padding.right || sp.padding.bottom || sp.padding.left)) {
                    css += '      padding: ' + (sp.padding.top || 0) + 'px ' + (sp.padding.right || 0) + 'px ' + (sp.padding.bottom || 0) + 'px ' + (sp.padding.left || 0) + 'px;\n';
                }
                if (sp.margin && (sp.margin.top || sp.margin.right || sp.margin.bottom || sp.margin.left)) {
                    css += '      margin: ' + (sp.margin.top || 0) + 'px ' + (sp.margin.right || 0) + 'px ' + (sp.margin.bottom || 0) + 'px ' + (sp.margin.left || 0) + 'px;\n';
                }
            }

            if (element.styles.typography) {
                var typo = element.styles.typography;
                if (typo.fontSize) css += '      font-size: ' + typo.fontSize + 'px;\n';
                if (typo.fontWeight) css += '      font-weight: ' + typo.fontWeight + ';\n';
                if (typo.color) css += '      color: ' + typo.color + ';\n';
                if (typo.textAlign) css += '      text-align: ' + typo.textAlign + ';\n';
                if (typo.lineHeight) css += '      line-height: ' + typo.lineHeight + ';\n';
            }

            if (element.styles.background) {
                var bg = element.styles.background;
                if (bg.color) css += '      background-color: ' + bg.color + ';\n';
                if (bg.type === 'gradient' && bg.value) css += '      background: ' + bg.value + ';\n';
            }

            if (element.styles.border) {
                var border = element.styles.border;
                if (border.radius && (border.radius.tl || border.radius.tr || border.radius.br || border.radius.bl)) {
                    var r = border.radius;
                    css += '      border-radius: ' + (r.tl || 0) + 'px ' + (r.tr || 0) + 'px ' + (r.br || 0) + 'px ' + (r.bl || 0) + 'px;\n';
                }
                if (border.width) css += '      border-width: ' + border.width + 'px;\n';
                if (border.color) css += '      border-color: ' + border.color + ';\n';
                if (border.style) css += '      border-style: ' + border.style + ';\n';
            }

            if (element.styles.shadow) {
                var shadow = element.styles.shadow;
                css += '      box-shadow: ' + (shadow.x || 0) + 'px ' + (shadow.y || 0) + 'px ' + (shadow.blur || 0) + 'px ' + (shadow.color || 'rgba(0,0,0,0.1)') + ';\n';
            }
        }

        css += '    }\n\n';

        if (element.children) {
            element.children.forEach(function(child) {
                css += self.elementToCSS(child);
            });
        }

        return css;
    },

    /**
     * Generar Tailwind
     */
    generateTailwind: function(elements) {
        var self = this;
        var html = '<!-- Tailwind CSS v3 -->\n';
        html += '<!-- Incluir: <script src="https://cdn.tailwindcss.com"></script> -->\n\n';

        elements.forEach(function(el) {
            html += self.elementToTailwind(el, 0) + '\n';
        });

        return html;
    },

    /**
     * Elemento a Tailwind
     */
    elementToTailwind: function(element, indent) {
        var self = this;
        var spaces = '  '.repeat(indent);
        var tag = this.getHTMLTag(element.type);
        var classes = this.stylesToTailwind(element.styles);

        var html = spaces + '<' + tag + ' class="' + classes + '">';

        if (element.content) {
            html += element.content;
        }

        if (element.children && element.children.length) {
            html += '\n';
            element.children.forEach(function(child) {
                html += self.elementToTailwind(child, indent + 1) + '\n';
            });
            html += spaces;
        }

        html += '</' + tag + '>';

        return html;
    },

    /**
     * Estilos a clases Tailwind
     */
    stylesToTailwind: function(styles) {
        var classes = [];

        if (!styles) return classes.join(' ');

        if (styles.layout) {
            if (styles.layout.display === 'flex') classes.push('flex');
            if (styles.layout.display === 'grid') classes.push('grid');
            if (styles.layout.display === 'block') classes.push('block');
            if (styles.layout.flexDirection === 'column') classes.push('flex-col');
            if (styles.layout.flexDirection === 'row') classes.push('flex-row');
            if (styles.layout.alignItems === 'center') classes.push('items-center');
            if (styles.layout.alignItems === 'start') classes.push('items-start');
            if (styles.layout.alignItems === 'end') classes.push('items-end');
            if (styles.layout.justifyContent === 'center') classes.push('justify-center');
            if (styles.layout.justifyContent === 'space-between') classes.push('justify-between');
            if (styles.layout.justifyContent === 'space-around') classes.push('justify-around');
            if (styles.layout.gap) {
                var gapValue = parseInt(styles.layout.gap) / 4;
                classes.push('gap-' + Math.round(gapValue));
            }
        }

        if (styles.spacing) {
            var p = styles.spacing.padding;
            if (p) {
                if (p.top === p.bottom && p.left === p.right && p.top === p.left) {
                    classes.push('p-' + Math.round(p.top / 4));
                } else {
                    if (p.top === p.bottom) classes.push('py-' + Math.round(p.top / 4));
                    if (p.left === p.right) classes.push('px-' + Math.round(p.left / 4));
                }
            }

            var m = styles.spacing.margin;
            if (m) {
                if (m.top === m.bottom && m.left === m.right && m.top === m.left) {
                    classes.push('m-' + Math.round(m.top / 4));
                } else {
                    if (m.top) classes.push('mt-' + Math.round(m.top / 4));
                    if (m.bottom) classes.push('mb-' + Math.round(m.bottom / 4));
                }
            }
        }

        if (styles.typography) {
            var fs = styles.typography.fontSize;
            if (fs) {
                if (fs <= 12) classes.push('text-xs');
                else if (fs <= 14) classes.push('text-sm');
                else if (fs <= 16) classes.push('text-base');
                else if (fs <= 18) classes.push('text-lg');
                else if (fs <= 20) classes.push('text-xl');
                else if (fs <= 24) classes.push('text-2xl');
                else if (fs <= 30) classes.push('text-3xl');
                else if (fs <= 36) classes.push('text-4xl');
                else if (fs <= 48) classes.push('text-5xl');
                else classes.push('text-6xl');
            }

            var fw = styles.typography.fontWeight;
            if (fw >= 700) classes.push('font-bold');
            else if (fw >= 600) classes.push('font-semibold');
            else if (fw >= 500) classes.push('font-medium');

            if (styles.typography.textAlign === 'center') classes.push('text-center');
            if (styles.typography.textAlign === 'right') classes.push('text-right');
        }

        if (styles.border && styles.border.radius) {
            var r = styles.border.radius.tl || styles.border.radius;
            if (typeof r === 'number') {
                if (r >= 9999) classes.push('rounded-full');
                else if (r >= 24) classes.push('rounded-3xl');
                else if (r >= 16) classes.push('rounded-2xl');
                else if (r >= 12) classes.push('rounded-xl');
                else if (r >= 8) classes.push('rounded-lg');
                else if (r >= 6) classes.push('rounded-md');
                else if (r >= 4) classes.push('rounded');
                else if (r >= 2) classes.push('rounded-sm');
            }
        }

        if (styles.shadow) {
            var blur = styles.shadow.blur || 0;
            if (blur >= 25) classes.push('shadow-2xl');
            else if (blur >= 20) classes.push('shadow-xl');
            else if (blur >= 15) classes.push('shadow-lg');
            else if (blur >= 10) classes.push('shadow-md');
            else if (blur >= 5) classes.push('shadow');
            else if (blur > 0) classes.push('shadow-sm');
        }

        return classes.join(' ');
    },

    /**
     * Generar React Component
     */
    generateReact: function(elements) {
        var self = this;
        var jsx = '// Componente React generado por Visual Builder Pro\n';
        jsx += 'import React from \'react\';\n\n';

        jsx += 'const Component = () => {\n';
        jsx += '  return (\n';
        jsx += '    <div className="container">\n';

        elements.forEach(function(el) {
            jsx += self.elementToJSX(el, 3);
        });

        jsx += '    </div>\n';
        jsx += '  );\n';
        jsx += '};\n\n';

        jsx += '// Estilos CSS (usar CSS Modules o styled-components)\n';
        jsx += 'const styles = `\n';
        jsx += this.generateCSS(elements);
        jsx += '`;\n\n';

        jsx += 'export default Component;';

        return jsx;
    },

    /**
     * Elemento a JSX
     */
    elementToJSX: function(element, indent) {
        var self = this;
        var spaces = '  '.repeat(indent);
        var tag = this.getHTMLTag(element.type);
        var className = 'el-' + element.id.replace(/[^a-zA-Z0-9]/g, '');

        var jsx = spaces + '<' + tag + ' className="' + className + '">';

        if (element.content) {
            jsx += element.content;
        }

        if (element.children && element.children.length) {
            jsx += '\n';
            element.children.forEach(function(child) {
                jsx += self.elementToJSX(child, indent + 1);
            });
            jsx += spaces;
        }

        jsx += '</' + tag + '>\n';

        return jsx;
    },

    /**
     * Generar Vue Component
     */
    generateVue: function(elements) {
        var self = this;
        var vue = '<!-- Componente Vue 3 generado por Visual Builder Pro -->\n';
        vue += '<script setup>\n';
        vue += '// Importar composables si es necesario\n';
        vue += '</script>\n\n';

        vue += '<template>\n';
        vue += '  <div class="container">\n';

        elements.forEach(function(el) {
            vue += self.elementToVue(el, 2);
        });

        vue += '  </div>\n';
        vue += '</template>\n\n';

        vue += '<style scoped>\n';
        vue += this.generateCSS(elements);
        vue += '</style>';

        return vue;
    },

    /**
     * Elemento a Vue template
     */
    elementToVue: function(element, indent) {
        var self = this;
        var spaces = '  '.repeat(indent);
        var tag = this.getHTMLTag(element.type);
        var className = 'el-' + element.id.replace(/[^a-zA-Z0-9]/g, '');

        var html = spaces + '<' + tag + ' class="' + className + '">';

        if (element.content) {
            html += element.content;
        }

        if (element.children && element.children.length) {
            html += '\n';
            element.children.forEach(function(child) {
                html += self.elementToVue(child, indent + 1);
            });
            html += spaces;
        }

        html += '</' + tag + '>\n';

        return html;
    },

    /**
     * Generar Svelte Component
     */
    generateSvelte: function(elements) {
        var self = this;
        var svelte = '<!-- Componente Svelte generado por Visual Builder Pro -->\n';
        svelte += '<script>\n';
        svelte += '  // Importar stores o props si es necesario\n';
        svelte += '  export let data = {};\n';
        svelte += '</script>\n\n';

        elements.forEach(function(el) {
            svelte += self.elementToSvelte(el, 0);
        });

        svelte += '\n<style>\n';
        svelte += this.generateCSS(elements).replace(/    /g, '  ');
        svelte += '</style>';

        return svelte;
    },

    /**
     * Elemento a Svelte template
     */
    elementToSvelte: function(element, indent) {
        var self = this;
        var spaces = '  '.repeat(indent);
        var tag = this.getHTMLTag(element.type);
        var className = 'el-' + element.id.replace(/[^a-zA-Z0-9]/g, '');

        var html = spaces + '<' + tag + ' class="' + className + '">';

        if (element.content) {
            html += element.content;
        }

        if (element.children && element.children.length) {
            html += '\n';
            element.children.forEach(function(child) {
                html += self.elementToSvelte(child, indent + 1);
            });
            html += spaces;
        }

        html += '</' + tag + '>\n';

        return html;
    },

    /**
     * Exportar como imagen
     */
    exportAsImage: function() {
        var canvas = document.querySelector('.vbp-canvas');

        if (!canvas) {
            window.vbpKeyboard.showNotification('No se encontró el canvas', 'error');
            return;
        }

        if (typeof html2canvas !== 'undefined') {
            html2canvas(canvas).then(function(canvasEl) {
                var link = document.createElement('a');
                link.download = 'design-export.png';
                link.href = canvasEl.toDataURL();
                link.click();
                window.vbpKeyboard.showNotification('🖼 Imagen exportada');
            });
        } else {
            window.vbpKeyboard.showNotification('Necesitas html2canvas para exportar imágenes', 'warning');
        }
    }
};
