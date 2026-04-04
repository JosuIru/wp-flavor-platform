/**
 * Visual Builder Pro - Store Style Helpers
 *
 * Helpers puros para estilos y paths anidados.
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

window.VBPStoreStyleHelpers = {
    ensureStylesComplete: function(element, defaults) {
        if (!element) return element;

        if (!element.styles) {
            element.styles = defaults;
            return element;
        }

        var sections = ['spacing', 'colors', 'background', 'typography', 'borders', 'shadows', 'layout', 'dimensions', 'position', 'transform', 'states', 'transition', 'advanced'];

        sections.forEach(function(section) {
            if (!element.styles[section]) {
                element.styles[section] = defaults[section];
            } else if (typeof defaults[section] === 'object') {
                Object.keys(defaults[section]).forEach(function(key) {
                    if (element.styles[section][key] === undefined) {
                        element.styles[section][key] = defaults[section][key];
                    }
                });
            }
        });

        if (element.styles.spacing) {
            if (!element.styles.spacing.margin) {
                element.styles.spacing.margin = { top: '', right: '', bottom: '', left: '' };
            }
            if (!element.styles.spacing.padding) {
                element.styles.spacing.padding = { top: '', right: '', bottom: '', left: '' };
            }
        }

        if (element.styles.overflow === undefined) {
            element.styles.overflow = '';
        }
        if (element.styles.opacity === undefined) {
            element.styles.opacity = '';
        }

        return element;
    },

    mergeStyles: function(base, overrides) {
        var result = JSON.parse(JSON.stringify(base));

        for (var key in overrides) {
            if (overrides.hasOwnProperty(key)) {
                if (typeof overrides[key] === 'object' && overrides[key] !== null && !Array.isArray(overrides[key])) {
                    result[key] = this.mergeStyles(result[key] || {}, overrides[key]);
                } else if (overrides[key] !== '' && overrides[key] !== null && overrides[key] !== undefined) {
                    result[key] = overrides[key];
                }
            }
        }

        return result;
    },

    setNestedValue: function(obj, path, value) {
        var parts = path.split('.');
        var current = obj;

        for (var i = 0; i < parts.length - 1; i++) {
            if (!current[parts[i]]) {
                current[parts[i]] = {};
            }
            current = current[parts[i]];
        }

        current[parts[parts.length - 1]] = value;
    },

    getNestedValue: function(obj, path) {
        var parts = path.split('.');
        var current = obj;

        for (var i = 0; i < parts.length; i++) {
            if (!current || !Object.prototype.hasOwnProperty.call(current, parts[i])) {
                return undefined;
            }
            current = current[parts[i]];
        }

        return current;
    }
};
