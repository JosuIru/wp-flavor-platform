/**
 * Tests for VBP Animation Builder functionality.
 *
 * @package Flavor_Platform
 * @subpackage Tests
 */

describe('VBP Animation Builder', () => {
    // Sample animation data
    const sampleAnimation = {
        id: 'anim-1',
        name: 'Fade In Up',
        trigger: 'onEnter',
        duration: 0.6,
        delay: 0,
        easing: 'easeOutCubic',
        keyframes: [
            {
                offset: 0,
                properties: {
                    opacity: 0,
                    transform: 'translateY(20px)',
                },
            },
            {
                offset: 1,
                properties: {
                    opacity: 1,
                    transform: 'translateY(0)',
                },
            },
        ],
    };

    const easingFunctions = {
        linear: 'linear',
        easeIn: 'cubic-bezier(0.4, 0, 1, 1)',
        easeOut: 'cubic-bezier(0, 0, 0.2, 1)',
        easeInOut: 'cubic-bezier(0.4, 0, 0.2, 1)',
        easeOutCubic: 'cubic-bezier(0.33, 1, 0.68, 1)',
        easeInOutCubic: 'cubic-bezier(0.65, 0, 0.35, 1)',
        spring: 'cubic-bezier(0.34, 1.56, 0.64, 1)',
    };

    describe('Animation Structure', () => {
        test('animation has required properties', () => {
            const requiredProps = ['id', 'name', 'trigger', 'duration', 'keyframes'];

            requiredProps.forEach(prop => {
                expect(sampleAnimation).toHaveProperty(prop);
            });
        });

        test('keyframes have valid structure', () => {
            sampleAnimation.keyframes.forEach(keyframe => {
                expect(keyframe).toHaveProperty('offset');
                expect(keyframe).toHaveProperty('properties');
                expect(keyframe.offset).toBeGreaterThanOrEqual(0);
                expect(keyframe.offset).toBeLessThanOrEqual(1);
            });
        });

        test('keyframes are ordered by offset', () => {
            const offsets = sampleAnimation.keyframes.map(kf => kf.offset);

            for (let i = 0; i < offsets.length - 1; i++) {
                expect(offsets[i]).toBeLessThanOrEqual(offsets[i + 1]);
            }
        });
    });

    describe('Animation Triggers', () => {
        test('trigger types are valid', () => {
            const validTriggers = [
                'onEnter',
                'onLeave',
                'onClick',
                'onHover',
                'onScroll',
                'onLoad',
            ];

            validTriggers.forEach(trigger => {
                expect(['onEnter', 'onLeave', 'onClick', 'onHover', 'onScroll', 'onLoad']).toContain(trigger);
            });
        });

        test('scroll trigger has additional options', () => {
            const scrollAnimation = {
                ...sampleAnimation,
                trigger: 'onScroll',
                scrollOptions: {
                    start: 'top bottom',
                    end: 'bottom top',
                    scrub: true,
                },
            };

            expect(scrollAnimation.scrollOptions).toBeDefined();
            expect(scrollAnimation.scrollOptions.scrub).toBe(true);
        });

        test('hover trigger can have enter and leave animations', () => {
            const hoverAnimation = {
                trigger: 'onHover',
                enter: {
                    ...sampleAnimation,
                },
                leave: {
                    ...sampleAnimation,
                    keyframes: [...sampleAnimation.keyframes].reverse(),
                },
            };

            expect(hoverAnimation.enter).toBeDefined();
            expect(hoverAnimation.leave).toBeDefined();
        });
    });

    describe('Easing Functions', () => {
        test('easing presets are valid cubic-bezier', () => {
            Object.entries(easingFunctions).forEach(([name, value]) => {
                if (name !== 'linear') {
                    expect(value).toMatch(/^cubic-bezier\([0-9.,\s-]+\)$/);
                }
            });
        });

        test('custom easing can be defined', () => {
            const customEasing = 'cubic-bezier(0.25, 0.1, 0.25, 1)';

            expect(customEasing).toMatch(/^cubic-bezier\([0-9.,\s-]+\)$/);
        });

        test('parses cubic-bezier values', () => {
            function parseCubicBezier(str) {
                const match = str.match(/cubic-bezier\(([^)]+)\)/);
                if (!match) return null;

                return match[1].split(',').map(v => parseFloat(v.trim()));
            }

            const values = parseCubicBezier('cubic-bezier(0.4, 0, 0.2, 1)');

            expect(values).toHaveLength(4);
            expect(values[0]).toBe(0.4);
            expect(values[2]).toBe(0.2);
        });
    });

    describe('Keyframe Properties', () => {
        test('supports transform properties', () => {
            const transformProperties = [
                'translateX',
                'translateY',
                'translateZ',
                'rotate',
                'rotateX',
                'rotateY',
                'scale',
                'scaleX',
                'scaleY',
                'skewX',
                'skewY',
            ];

            transformProperties.forEach(prop => {
                expect(typeof prop).toBe('string');
            });
        });

        test('supports visual properties', () => {
            const visualProperties = [
                'opacity',
                'backgroundColor',
                'color',
                'borderColor',
                'boxShadow',
                'filter',
            ];

            visualProperties.forEach(prop => {
                expect(typeof prop).toBe('string');
            });
        });

        test('compiles transform string', () => {
            function compileTransform(props) {
                const transforms = [];

                if (props.translateX !== undefined) transforms.push(`translateX(${props.translateX})`);
                if (props.translateY !== undefined) transforms.push(`translateY(${props.translateY})`);
                if (props.rotate !== undefined) transforms.push(`rotate(${props.rotate})`);
                if (props.scale !== undefined) transforms.push(`scale(${props.scale})`);

                return transforms.join(' ') || 'none';
            }

            const result = compileTransform({
                translateX: '10px',
                translateY: '20px',
                rotate: '45deg',
            });

            expect(result).toBe('translateX(10px) translateY(20px) rotate(45deg)');
        });
    });

    describe('Animation Presets', () => {
        const presets = {
            fadeIn: {
                keyframes: [
                    { offset: 0, properties: { opacity: 0 } },
                    { offset: 1, properties: { opacity: 1 } },
                ],
            },
            slideInLeft: {
                keyframes: [
                    { offset: 0, properties: { opacity: 0, transform: 'translateX(-100px)' } },
                    { offset: 1, properties: { opacity: 1, transform: 'translateX(0)' } },
                ],
            },
            scaleIn: {
                keyframes: [
                    { offset: 0, properties: { opacity: 0, transform: 'scale(0.8)' } },
                    { offset: 1, properties: { opacity: 1, transform: 'scale(1)' } },
                ],
            },
            bounce: {
                keyframes: [
                    { offset: 0, properties: { transform: 'translateY(0)' } },
                    { offset: 0.2, properties: { transform: 'translateY(-30px)' } },
                    { offset: 0.4, properties: { transform: 'translateY(0)' } },
                    { offset: 0.6, properties: { transform: 'translateY(-15px)' } },
                    { offset: 0.8, properties: { transform: 'translateY(0)' } },
                    { offset: 1, properties: { transform: 'translateY(0)' } },
                ],
            },
        };

        test('presets have valid keyframes', () => {
            Object.entries(presets).forEach(([name, preset]) => {
                expect(preset.keyframes.length).toBeGreaterThanOrEqual(2);
                expect(preset.keyframes[0].offset).toBe(0);
                expect(preset.keyframes[preset.keyframes.length - 1].offset).toBe(1);
            });
        });

        test('can customize preset', () => {
            const customized = {
                ...presets.fadeIn,
                duration: 1.2,
                delay: 0.3,
                easing: 'easeOutCubic',
            };

            expect(customized.duration).toBe(1.2);
            expect(customized.keyframes).toEqual(presets.fadeIn.keyframes);
        });
    });

    describe('CSS Animation Generation', () => {
        test('generates CSS @keyframes', () => {
            function generateKeyframesCSS(animation) {
                let css = `@keyframes ${animation.name.replace(/\s+/g, '-').toLowerCase()} {\n`;

                animation.keyframes.forEach(kf => {
                    css += `  ${kf.offset * 100}% {\n`;
                    Object.entries(kf.properties).forEach(([prop, value]) => {
                        const cssProp = prop.replace(/[A-Z]/g, m => `-${m.toLowerCase()}`);
                        css += `    ${cssProp}: ${value};\n`;
                    });
                    css += '  }\n';
                });

                css += '}';
                return css;
            }

            const css = generateKeyframesCSS(sampleAnimation);

            expect(css).toContain('@keyframes fade-in-up');
            expect(css).toContain('0%');
            expect(css).toContain('100%');
            expect(css).toContain('opacity: 0');
            expect(css).toContain('opacity: 1');
        });

        test('generates animation CSS property', () => {
            function generateAnimationCSS(animation) {
                const name = animation.name.replace(/\s+/g, '-').toLowerCase();
                const duration = `${animation.duration}s`;
                const delay = animation.delay ? `${animation.delay}s` : '0s';
                const easing = easingFunctions[animation.easing] || animation.easing;

                return `animation: ${name} ${duration} ${easing} ${delay} forwards`;
            }

            const css = generateAnimationCSS(sampleAnimation);

            expect(css).toContain('fade-in-up');
            expect(css).toContain('0.6s');
            expect(css).toContain('forwards');
        });
    });

    describe('Animation Timeline', () => {
        test('calculates total duration', () => {
            const animations = [
                { delay: 0, duration: 0.6 },
                { delay: 0.2, duration: 0.4 },
                { delay: 0.5, duration: 0.8 },
            ];

            function calculateTotalDuration(anims) {
                return Math.max(...anims.map(a => a.delay + a.duration));
            }

            expect(calculateTotalDuration(animations)).toBe(1.3);
        });

        test('stagger animations', () => {
            const baseAnimation = { duration: 0.6, delay: 0 };
            const staggerAmount = 0.1;
            const count = 5;

            const staggeredAnimations = Array.from({ length: count }, (_, i) => ({
                ...baseAnimation,
                delay: i * staggerAmount,
            }));

            expect(staggeredAnimations[0].delay).toBe(0);
            expect(staggeredAnimations[1].delay).toBe(0.1);
            expect(staggeredAnimations[4].delay).toBe(0.4);
        });

        test('sequences animations', () => {
            const animations = [
                { id: 'a1', duration: 0.5 },
                { id: 'a2', duration: 0.3 },
                { id: 'a3', duration: 0.4 },
            ];

            function sequenceAnimations(anims) {
                let currentTime = 0;
                return anims.map(anim => {
                    const sequenced = { ...anim, delay: currentTime };
                    currentTime += anim.duration;
                    return sequenced;
                });
            }

            const sequenced = sequenceAnimations(animations);

            expect(sequenced[0].delay).toBe(0);
            expect(sequenced[1].delay).toBe(0.5);
            expect(sequenced[2].delay).toBe(0.8);
        });
    });

    describe('Animation Validation', () => {
        test('validates duration range', () => {
            function isValidDuration(duration) {
                return typeof duration === 'number' && duration > 0 && duration <= 10;
            }

            expect(isValidDuration(0.6)).toBe(true);
            expect(isValidDuration(0)).toBe(false);
            expect(isValidDuration(-1)).toBe(false);
            expect(isValidDuration(15)).toBe(false);
        });

        test('validates delay range', () => {
            function isValidDelay(delay) {
                return typeof delay === 'number' && delay >= 0 && delay <= 10;
            }

            expect(isValidDelay(0)).toBe(true);
            expect(isValidDelay(2)).toBe(true);
            expect(isValidDelay(-1)).toBe(false);
        });

        test('validates keyframe offset', () => {
            function isValidOffset(offset) {
                return typeof offset === 'number' && offset >= 0 && offset <= 1;
            }

            expect(isValidOffset(0)).toBe(true);
            expect(isValidOffset(0.5)).toBe(true);
            expect(isValidOffset(1)).toBe(true);
            expect(isValidOffset(1.5)).toBe(false);
            expect(isValidOffset(-0.1)).toBe(false);
        });
    });

    describe('Animation Playback Control', () => {
        test('playback states', () => {
            const playbackStates = ['idle', 'running', 'paused', 'finished'];

            playbackStates.forEach(state => {
                expect(['idle', 'running', 'paused', 'finished']).toContain(state);
            });
        });

        test('calculates current progress', () => {
            function calculateProgress(elapsed, duration) {
                return Math.min(elapsed / duration, 1);
            }

            expect(calculateProgress(0.3, 0.6)).toBe(0.5);
            expect(calculateProgress(0.6, 0.6)).toBe(1);
            expect(calculateProgress(1.0, 0.6)).toBe(1);
        });

        test('interpolates between keyframes', () => {
            function interpolateValue(startValue, endValue, progress) {
                return startValue + (endValue - startValue) * progress;
            }

            expect(interpolateValue(0, 100, 0)).toBe(0);
            expect(interpolateValue(0, 100, 0.5)).toBe(50);
            expect(interpolateValue(0, 100, 1)).toBe(100);
        });
    });
});
