/**
 * Tests for VBP Responsive Variants functionality.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

describe('VBP Responsive Variants', () => {
    // Breakpoint definitions
    const breakpoints = {
        desktop: { id: 'desktop', minWidth: 1200, label: 'Desktop', icon: 'monitor' },
        laptop: { id: 'laptop', minWidth: 1024, maxWidth: 1199, label: 'Laptop', icon: 'laptop' },
        tablet: { id: 'tablet', minWidth: 768, maxWidth: 1023, label: 'Tablet', icon: 'tablet' },
        mobile: { id: 'mobile', minWidth: 0, maxWidth: 767, label: 'Mobile', icon: 'smartphone' },
    };

    // Sample element with responsive overrides
    const sampleElement = {
        id: 'element-1',
        type: 'container',
        props: {
            display: 'flex',
            flexDirection: 'row',
            gap: '24px',
            padding: '40px',
        },
        responsive: {
            tablet: {
                flexDirection: 'column',
                gap: '16px',
                padding: '24px',
            },
            mobile: {
                gap: '12px',
                padding: '16px',
            },
        },
    };

    describe('Breakpoint Management', () => {
        test('changes breakpoint', () => {
            let currentBreakpoint = 'desktop';

            // Simulate changing breakpoint
            currentBreakpoint = 'tablet';

            expect(currentBreakpoint).toBe('tablet');
            expect(breakpoints[currentBreakpoint]).toBeDefined();
            expect(breakpoints[currentBreakpoint].minWidth).toBe(768);
        });

        test('breakpoints are ordered correctly', () => {
            const orderedWidths = [
                breakpoints.desktop.minWidth,
                breakpoints.laptop.minWidth,
                breakpoints.tablet.minWidth,
                breakpoints.mobile.minWidth,
            ];

            // Should be in descending order
            for (let i = 0; i < orderedWidths.length - 1; i++) {
                expect(orderedWidths[i]).toBeGreaterThan(orderedWidths[i + 1]);
            }
        });

        test('detects breakpoint from viewport width', () => {
            function detectBreakpoint(width) {
                if (width >= 1200) return 'desktop';
                if (width >= 1024) return 'laptop';
                if (width >= 768) return 'tablet';
                return 'mobile';
            }

            expect(detectBreakpoint(1400)).toBe('desktop');
            expect(detectBreakpoint(1100)).toBe('laptop');
            expect(detectBreakpoint(800)).toBe('tablet');
            expect(detectBreakpoint(400)).toBe('mobile');
        });

        test('breakpoint has required properties', () => {
            const requiredProps = ['id', 'minWidth', 'label', 'icon'];

            Object.values(breakpoints).forEach(bp => {
                requiredProps.forEach(prop => {
                    expect(bp).toHaveProperty(prop);
                });
            });
        });
    });

    describe('Responsive Overrides', () => {
        test('applies responsive overrides', () => {
            function getComputedProps(element, breakpoint) {
                const baseProps = { ...element.props };

                if (element.responsive && element.responsive[breakpoint]) {
                    return { ...baseProps, ...element.responsive[breakpoint] };
                }

                return baseProps;
            }

            const desktopProps = getComputedProps(sampleElement, 'desktop');
            expect(desktopProps.flexDirection).toBe('row');
            expect(desktopProps.gap).toBe('24px');

            const tabletProps = getComputedProps(sampleElement, 'tablet');
            expect(tabletProps.flexDirection).toBe('column');
            expect(tabletProps.gap).toBe('16px');

            const mobileProps = getComputedProps(sampleElement, 'mobile');
            expect(mobileProps.gap).toBe('12px');
        });

        test('inherits from larger breakpoints', () => {
            function getInheritedProps(element, breakpoint) {
                const inheritanceOrder = ['desktop', 'laptop', 'tablet', 'mobile'];
                const currentIndex = inheritanceOrder.indexOf(breakpoint);

                let computedProps = { ...element.props };

                for (let i = 0; i <= currentIndex; i++) {
                    const bp = inheritanceOrder[i];
                    if (element.responsive && element.responsive[bp]) {
                        computedProps = { ...computedProps, ...element.responsive[bp] };
                    }
                }

                return computedProps;
            }

            // Mobile should inherit tablet's flexDirection
            const mobileProps = getInheritedProps(sampleElement, 'mobile');
            expect(mobileProps.flexDirection).toBe('column');
            expect(mobileProps.gap).toBe('12px');
        });

        test('sets override for specific breakpoint', () => {
            const element = JSON.parse(JSON.stringify(sampleElement));

            // Set a new override for tablet
            element.responsive.tablet.fontSize = '14px';

            expect(element.responsive.tablet.fontSize).toBe('14px');
        });

        test('removes override for specific breakpoint', () => {
            const element = JSON.parse(JSON.stringify(sampleElement));

            // Remove gap override from tablet
            delete element.responsive.tablet.gap;

            expect(element.responsive.tablet.gap).toBeUndefined();
        });

        test('clears all overrides for breakpoint', () => {
            const element = JSON.parse(JSON.stringify(sampleElement));

            element.responsive.tablet = {};

            expect(Object.keys(element.responsive.tablet)).toHaveLength(0);
        });
    });

    describe('CSS Generation', () => {
        test('generates correct CSS', () => {
            function generateResponsiveCSS(element) {
                let css = '';
                const selector = `.vbp-element-${element.id}`;

                // Base styles
                css += `${selector} {\n`;
                Object.entries(element.props).forEach(([prop, value]) => {
                    const cssProp = prop.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`);
                    css += `  ${cssProp}: ${value};\n`;
                });
                css += '}\n';

                // Responsive styles
                const breakpointOrder = ['laptop', 'tablet', 'mobile'];

                breakpointOrder.forEach(bp => {
                    if (element.responsive && element.responsive[bp]) {
                        const maxWidth = breakpoints[bp].maxWidth || breakpoints[bp].minWidth;
                        css += `@media (max-width: ${maxWidth}px) {\n`;
                        css += `  ${selector} {\n`;
                        Object.entries(element.responsive[bp]).forEach(([prop, value]) => {
                            const cssProp = prop.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`);
                            css += `    ${cssProp}: ${value};\n`;
                        });
                        css += '  }\n}\n';
                    }
                });

                return css;
            }

            const css = generateResponsiveCSS(sampleElement);

            expect(css).toContain('.vbp-element-element-1');
            expect(css).toContain('display: flex');
            expect(css).toContain('flex-direction: row');
            expect(css).toContain('@media (max-width:');
            expect(css).toContain('flex-direction: column');
        });

        test('converts camelCase to kebab-case', () => {
            const camelToKebab = (str) => str.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`);

            expect(camelToKebab('flexDirection')).toBe('flex-direction');
            expect(camelToKebab('backgroundColor')).toBe('background-color');
            expect(camelToKebab('marginTop')).toBe('margin-top');
            expect(camelToKebab('borderTopLeftRadius')).toBe('border-top-left-radius');
        });

        test('generates media query for each breakpoint', () => {
            function getMediaQuery(breakpoint) {
                const bp = breakpoints[breakpoint];

                if (bp.maxWidth) {
                    return `@media (max-width: ${bp.maxWidth}px)`;
                } else if (bp.minWidth > 0) {
                    return `@media (min-width: ${bp.minWidth}px)`;
                }

                return '';
            }

            expect(getMediaQuery('laptop')).toContain('1199px');
            expect(getMediaQuery('tablet')).toContain('1023px');
            expect(getMediaQuery('mobile')).toContain('767px');
        });
    });

    describe('Preview Mode', () => {
        test('sets preview breakpoint', () => {
            const store = {
                currentBreakpoint: 'desktop',
                previewWidth: null,
            };

            // Change to tablet preview
            store.currentBreakpoint = 'tablet';
            store.previewWidth = breakpoints.tablet.minWidth;

            expect(store.currentBreakpoint).toBe('tablet');
            expect(store.previewWidth).toBe(768);
        });

        test('calculates preview container dimensions', () => {
            function getPreviewDimensions(breakpoint) {
                const bp = breakpoints[breakpoint];
                const width = bp.maxWidth || bp.minWidth;
                const aspectRatio = breakpoint === 'mobile' ? 16 / 9 : 16 / 10;
                const height = Math.round(width / aspectRatio);

                return { width, height };
            }

            const mobileDims = getPreviewDimensions('mobile');
            expect(mobileDims.width).toBe(767);

            const tabletDims = getPreviewDimensions('tablet');
            expect(tabletDims.width).toBe(1023);
        });

        test('scales preview to fit viewport', () => {
            function calculateScale(containerWidth, viewportWidth) {
                if (containerWidth <= viewportWidth) return 1;
                return viewportWidth / containerWidth;
            }

            expect(calculateScale(768, 1024)).toBe(1);
            expect(calculateScale(1200, 800)).toBeCloseTo(0.667, 2);
        });
    });

    describe('Responsive Properties', () => {
        test('identifies responsive-enabled properties', () => {
            const responsiveProperties = [
                'display',
                'flexDirection',
                'alignItems',
                'justifyContent',
                'gap',
                'padding',
                'margin',
                'fontSize',
                'lineHeight',
                'width',
                'height',
                'gridTemplateColumns',
                'visibility',
            ];

            expect(responsiveProperties).toContain('fontSize');
            expect(responsiveProperties).toContain('gap');
            expect(responsiveProperties).not.toContain('id');
            expect(responsiveProperties).not.toContain('type');
        });

        test('validates responsive value', () => {
            function isValidCSSValue(value) {
                if (typeof value === 'number') return true;
                if (typeof value !== 'string') return false;
                if (value.trim() === '') return false;
                return true;
            }

            expect(isValidCSSValue('16px')).toBe(true);
            expect(isValidCSSValue(0)).toBe(true);
            expect(isValidCSSValue('')).toBe(false);
            expect(isValidCSSValue(null)).toBe(false);
        });
    });

    describe('State Management', () => {
        test('tracks modified breakpoints', () => {
            const element = JSON.parse(JSON.stringify(sampleElement));
            const modifiedBreakpoints = new Set();

            // Track which breakpoints have modifications
            if (element.responsive) {
                Object.keys(element.responsive).forEach(bp => {
                    if (Object.keys(element.responsive[bp]).length > 0) {
                        modifiedBreakpoints.add(bp);
                    }
                });
            }

            expect(modifiedBreakpoints.has('tablet')).toBe(true);
            expect(modifiedBreakpoints.has('mobile')).toBe(true);
            expect(modifiedBreakpoints.has('laptop')).toBe(false);
        });

        test('compares values across breakpoints', () => {
            function getValueAcrossBreakpoints(element, property) {
                const values = {
                    desktop: element.props[property],
                };

                ['laptop', 'tablet', 'mobile'].forEach(bp => {
                    if (element.responsive && element.responsive[bp] && element.responsive[bp][property]) {
                        values[bp] = element.responsive[bp][property];
                    } else {
                        // Inherit from previous
                        const order = ['desktop', 'laptop', 'tablet', 'mobile'];
                        const currentIdx = order.indexOf(bp);
                        for (let i = currentIdx - 1; i >= 0; i--) {
                            if (values[order[i]]) {
                                values[bp] = values[order[i]];
                                break;
                            }
                        }
                    }
                });

                return values;
            }

            const gapValues = getValueAcrossBreakpoints(sampleElement, 'gap');

            expect(gapValues.desktop).toBe('24px');
            expect(gapValues.tablet).toBe('16px');
            expect(gapValues.mobile).toBe('12px');
        });
    });
});
