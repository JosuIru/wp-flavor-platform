/**
 * VBP 3D Blocks - Definiciones de bloques 3D para el catálogo
 *
 * Define los bloques 3D disponibles en el Visual Builder Pro,
 * incluyendo escenas, objetos, textos y efectos.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

(function() {
    'use strict';

    /**
     * Categoría de bloques 3D
     */
    var CATEGORY_3D = {
        id: '3d',
        name: '3D / WebGL',
        icon: '🎲',
        description: 'Escenas y objetos 3D interactivos'
    };

    /**
     * Definiciones de bloques 3D
     */
    var BLOCKS_3D = {
        /**
         * Escena 3D - Contenedor principal
         */
        '3d-scene': {
            type: '3d-scene',
            name: 'Escena 3D',
            category: '3d',
            icon: '🎬',
            description: 'Contenedor de escena 3D con cámara e iluminación',
            isContainer: true,
            allowedChildren: ['3d-object', '3d-model', '3d-text', '3d-particles', '3d-light', '3d-group'],
            defaultData: {
                width: '100%',
                height: '400px',
                preset: 'minimal'
            },
            defaultStyles: {
                borderRadius: '8px',
                overflow: 'hidden'
            },
            props: {
                width: { type: 'text', label: 'Ancho', default: '100%' },
                height: { type: 'text', label: 'Alto', default: '400px' },
                preset: {
                    type: 'select',
                    label: 'Preset de escena',
                    options: [
                        { value: 'minimal', label: 'Minimalista' },
                        { value: 'product-showcase', label: 'Showcase de Producto' },
                        { value: 'floating-cards', label: 'Tarjetas Flotantes' },
                        { value: 'particle-background', label: 'Fondo de Partículas' },
                        { value: 'hero-3d', label: 'Hero 3D' },
                        { value: 'gallery-3d', label: 'Galería 3D' }
                    ],
                    default: 'minimal'
                },
                camera: {
                    type: 'group',
                    label: 'Cámara',
                    props: {
                        type: {
                            type: 'select',
                            label: 'Tipo',
                            options: [
                                { value: 'perspective', label: 'Perspectiva' },
                                { value: 'orthographic', label: 'Ortográfica' }
                            ],
                            default: 'perspective'
                        },
                        fov: { type: 'range', label: 'FOV', min: 10, max: 120, default: 75 },
                        positionX: { type: 'number', label: 'Posición X', default: 0 },
                        positionY: { type: 'number', label: 'Posición Y', default: 0 },
                        positionZ: { type: 'number', label: 'Posición Z', default: 5 }
                    }
                },
                controls: {
                    type: 'select',
                    label: 'Controles',
                    options: [
                        { value: 'orbit', label: 'Órbita' },
                        { value: 'fly', label: 'Vuelo' },
                        { value: 'none', label: 'Sin controles' }
                    ],
                    default: 'orbit'
                },
                autoRotate: { type: 'toggle', label: 'Auto-rotar', default: false },
                autoRotateSpeed: { type: 'range', label: 'Velocidad rotación', min: 0.5, max: 10, step: 0.5, default: 2 },
                background: {
                    type: 'group',
                    label: 'Fondo',
                    props: {
                        type: {
                            type: 'select',
                            label: 'Tipo',
                            options: [
                                { value: 'solid', label: 'Color sólido' },
                                { value: 'gradient', label: 'Gradiente' },
                                { value: 'transparent', label: 'Transparente' }
                            ],
                            default: 'solid'
                        },
                        color1: { type: 'color', label: 'Color 1', default: '#000000' },
                        color2: { type: 'color', label: 'Color 2', default: '#333333' }
                    }
                },
                shadows: { type: 'toggle', label: 'Sombras', default: false },
                antialiasing: { type: 'toggle', label: 'Antialiasing', default: true },
                enableAR: {
                    type: 'toggle',
                    label: 'Habilitar AR',
                    default: false,
                    description: 'Mostrar botón "Ver en AR" para dispositivos compatibles'
                },
                enableVR: {
                    type: 'toggle',
                    label: 'Habilitar VR',
                    default: false,
                    description: 'Mostrar botón "Ver en VR" para cascos compatibles'
                }
            }
        },

        /**
         * Objeto 3D - Primitivas geométricas
         */
        '3d-object': {
            type: '3d-object',
            name: 'Objeto 3D',
            category: '3d',
            icon: '🔷',
            description: 'Primitiva geométrica 3D (cubo, esfera, etc.)',
            parentRequired: '3d-scene',
            defaultData: {
                primitive: 'box',
                material: {
                    type: 'standard',
                    color: '#6366f1',
                    metalness: 0.3,
                    roughness: 0.7
                }
            },
            props: {
                primitive: {
                    type: 'select',
                    label: 'Forma',
                    options: [
                        { value: 'box', label: 'Cubo' },
                        { value: 'sphere', label: 'Esfera' },
                        { value: 'cylinder', label: 'Cilindro' },
                        { value: 'cone', label: 'Cono' },
                        { value: 'torus', label: 'Toro' },
                        { value: 'plane', label: 'Plano' },
                        { value: 'dodecahedron', label: 'Dodecaedro' },
                        { value: 'icosahedron', label: 'Icosaedro' },
                        { value: 'octahedron', label: 'Octaedro' },
                        { value: 'tetrahedron', label: 'Tetraedro' },
                        { value: 'ring', label: 'Anillo' },
                        { value: 'capsule', label: 'Cápsula' }
                    ],
                    default: 'box'
                },
                position: {
                    type: 'vector3',
                    label: 'Posición',
                    default: { x: 0, y: 0, z: 0 }
                },
                rotation: {
                    type: 'vector3',
                    label: 'Rotación',
                    default: { x: 0, y: 0, z: 0 },
                    unit: '°'
                },
                scale: {
                    type: 'vector3',
                    label: 'Escala',
                    default: { x: 1, y: 1, z: 1 },
                    uniformOption: true
                },
                material: {
                    type: 'group',
                    label: 'Material',
                    props: {
                        type: {
                            type: 'select',
                            label: 'Tipo',
                            options: [
                                { value: 'standard', label: 'Estándar' },
                                { value: 'basic', label: 'Básico' },
                                { value: 'phong', label: 'Phong' },
                                { value: 'lambert', label: 'Lambert' },
                                { value: 'physical', label: 'Físico' },
                                { value: 'toon', label: 'Cartoon' }
                            ],
                            default: 'standard'
                        },
                        color: { type: 'color', label: 'Color', default: '#6366f1' },
                        metalness: { type: 'range', label: 'Metalicidad', min: 0, max: 1, step: 0.01, default: 0.3 },
                        roughness: { type: 'range', label: 'Rugosidad', min: 0, max: 1, step: 0.01, default: 0.7 },
                        opacity: { type: 'range', label: 'Opacidad', min: 0, max: 1, step: 0.01, default: 1 },
                        wireframe: { type: 'toggle', label: 'Wireframe', default: false }
                    }
                },
                castShadow: { type: 'toggle', label: 'Proyectar sombra', default: true },
                receiveShadow: { type: 'toggle', label: 'Recibir sombra', default: true },
                onClick: {
                    type: 'select',
                    label: 'Al hacer clic',
                    options: [
                        { value: '', label: 'Ninguna acción' },
                        { value: 'rotate', label: 'Rotar' },
                        { value: 'scale', label: 'Escalar' },
                        { value: 'highlight', label: 'Resaltar' }
                    ],
                    default: ''
                }
            }
        },

        /**
         * Modelo 3D - Importar archivos externos
         */
        '3d-model': {
            type: '3d-model',
            name: 'Modelo 3D',
            category: '3d',
            icon: '📦',
            description: 'Importar modelo 3D externo (GLB, GLTF)',
            parentRequired: '3d-scene',
            defaultData: {
                src: '',
                scale: 1,
                autoPlay: true
            },
            props: {
                src: {
                    type: 'media',
                    label: 'Archivo 3D',
                    accept: '.glb,.gltf',
                    description: 'Formatos soportados: GLB, GLTF'
                },
                position: {
                    type: 'vector3',
                    label: 'Posición',
                    default: { x: 0, y: 0, z: 0 }
                },
                rotation: {
                    type: 'vector3',
                    label: 'Rotación',
                    default: { x: 0, y: 0, z: 0 },
                    unit: '°'
                },
                scale: {
                    type: 'number',
                    label: 'Escala',
                    min: 0.01,
                    max: 100,
                    step: 0.1,
                    default: 1
                },
                autoPlay: { type: 'toggle', label: 'Reproducir animaciones', default: true },
                autoRotate: { type: 'toggle', label: 'Auto-rotar', default: false },
                autoRotateSpeed: { type: 'range', label: 'Velocidad', min: 0.1, max: 5, step: 0.1, default: 1 }
            }
        },

        /**
         * Texto 3D
         */
        '3d-text': {
            type: '3d-text',
            name: 'Texto 3D',
            category: '3d',
            icon: '🔤',
            description: 'Texto tridimensional extruido',
            parentRequired: '3d-scene',
            defaultData: {
                text: 'Hello 3D',
                size: 1,
                depth: 0.2,
                material: {
                    color: '#ffffff',
                    metalness: 0.5,
                    roughness: 0.3
                }
            },
            props: {
                text: { type: 'text', label: 'Texto', default: 'Hello 3D' },
                font: {
                    type: 'select',
                    label: 'Fuente',
                    options: [
                        { value: 'helvetiker', label: 'Helvetiker' },
                        { value: 'optimer', label: 'Optimer' },
                        { value: 'gentilis', label: 'Gentilis' },
                        { value: 'droid_sans', label: 'Droid Sans' },
                        { value: 'droid_serif', label: 'Droid Serif' }
                    ],
                    default: 'helvetiker'
                },
                size: { type: 'range', label: 'Tamaño', min: 0.1, max: 10, step: 0.1, default: 1 },
                depth: { type: 'range', label: 'Profundidad', min: 0.01, max: 2, step: 0.01, default: 0.2 },
                bevel: { type: 'toggle', label: 'Bisel', default: true },
                bevelThickness: { type: 'range', label: 'Grosor bisel', min: 0.01, max: 0.2, step: 0.01, default: 0.03 },
                position: {
                    type: 'vector3',
                    label: 'Posición',
                    default: { x: 0, y: 0, z: 0 }
                },
                rotation: {
                    type: 'vector3',
                    label: 'Rotación',
                    default: { x: 0, y: 0, z: 0 },
                    unit: '°'
                },
                material: {
                    type: 'group',
                    label: 'Material',
                    props: {
                        color: { type: 'color', label: 'Color', default: '#ffffff' },
                        metalness: { type: 'range', label: 'Metalicidad', min: 0, max: 1, step: 0.01, default: 0.5 },
                        roughness: { type: 'range', label: 'Rugosidad', min: 0, max: 1, step: 0.01, default: 0.3 }
                    }
                }
            }
        },

        /**
         * Partículas 3D
         */
        '3d-particles': {
            type: '3d-particles',
            name: 'Partículas 3D',
            category: '3d',
            icon: '✨',
            description: 'Sistema de partículas animadas',
            parentRequired: '3d-scene',
            defaultData: {
                count: 1000,
                size: 0.02,
                color: '#ffffff',
                spread: 10,
                movement: 'float'
            },
            props: {
                count: { type: 'range', label: 'Cantidad', min: 100, max: 10000, step: 100, default: 1000 },
                size: { type: 'range', label: 'Tamaño', min: 0.001, max: 0.1, step: 0.001, default: 0.02 },
                color: { type: 'color', label: 'Color', default: '#ffffff' },
                spread: { type: 'range', label: 'Dispersión', min: 1, max: 50, step: 1, default: 10 },
                opacity: { type: 'range', label: 'Opacidad', min: 0, max: 1, step: 0.1, default: 0.8 },
                movement: {
                    type: 'select',
                    label: 'Movimiento',
                    options: [
                        { value: 'float', label: 'Flotante' },
                        { value: 'rise', label: 'Ascendente' },
                        { value: 'fall', label: 'Descendente' },
                        { value: 'orbit', label: 'Orbital' },
                        { value: 'static', label: 'Estático' }
                    ],
                    default: 'float'
                },
                speed: { type: 'range', label: 'Velocidad', min: 0.1, max: 5, step: 0.1, default: 1 }
            }
        },

        /**
         * Luz 3D
         */
        '3d-light': {
            type: '3d-light',
            name: 'Luz 3D',
            category: '3d',
            icon: '💡',
            description: 'Fuente de luz para la escena',
            parentRequired: '3d-scene',
            defaultData: {
                lightType: 'directional',
                color: '#ffffff',
                intensity: 1
            },
            props: {
                lightType: {
                    type: 'select',
                    label: 'Tipo de luz',
                    options: [
                        { value: 'ambient', label: 'Ambiental' },
                        { value: 'directional', label: 'Direccional' },
                        { value: 'point', label: 'Puntual' },
                        { value: 'spot', label: 'Foco' },
                        { value: 'hemisphere', label: 'Hemisférica' }
                    ],
                    default: 'directional'
                },
                color: { type: 'color', label: 'Color', default: '#ffffff' },
                intensity: { type: 'range', label: 'Intensidad', min: 0, max: 5, step: 0.1, default: 1 },
                position: {
                    type: 'vector3',
                    label: 'Posición',
                    default: { x: 1, y: 1, z: 1 },
                    condition: { lightType: ['directional', 'point', 'spot'] }
                },
                distance: {
                    type: 'range',
                    label: 'Distancia',
                    min: 0,
                    max: 100,
                    default: 0,
                    condition: { lightType: ['point', 'spot'] }
                },
                angle: {
                    type: 'range',
                    label: 'Ángulo',
                    min: 0,
                    max: 90,
                    default: 60,
                    unit: '°',
                    condition: { lightType: 'spot' }
                },
                penumbra: {
                    type: 'range',
                    label: 'Penumbra',
                    min: 0,
                    max: 1,
                    step: 0.1,
                    default: 0.1,
                    condition: { lightType: 'spot' }
                },
                castShadow: {
                    type: 'toggle',
                    label: 'Proyectar sombras',
                    default: false,
                    condition: { lightType: ['directional', 'point', 'spot'] }
                },
                skyColor: {
                    type: 'color',
                    label: 'Color cielo',
                    default: '#87ceeb',
                    condition: { lightType: 'hemisphere' }
                },
                groundColor: {
                    type: 'color',
                    label: 'Color suelo',
                    default: '#444444',
                    condition: { lightType: 'hemisphere' }
                }
            }
        },

        /**
         * Grupo 3D - Contenedor de objetos
         */
        '3d-group': {
            type: '3d-group',
            name: 'Grupo 3D',
            category: '3d',
            icon: '📁',
            description: 'Agrupar múltiples objetos 3D',
            parentRequired: '3d-scene',
            isContainer: true,
            allowedChildren: ['3d-object', '3d-model', '3d-text', '3d-particles', '3d-light'],
            defaultData: {},
            props: {
                position: {
                    type: 'vector3',
                    label: 'Posición del grupo',
                    default: { x: 0, y: 0, z: 0 }
                },
                rotation: {
                    type: 'vector3',
                    label: 'Rotación del grupo',
                    default: { x: 0, y: 0, z: 0 },
                    unit: '°'
                },
                scale: {
                    type: 'number',
                    label: 'Escala del grupo',
                    min: 0.01,
                    max: 10,
                    step: 0.1,
                    default: 1
                }
            }
        },

        /**
         * Animación 3D
         */
        '3d-animation': {
            type: '3d-animation',
            name: 'Animación 3D',
            category: '3d',
            icon: '🎞️',
            description: 'Animar propiedades de objetos 3D',
            defaultData: {
                target: '',
                property: 'rotation',
                duration: 2,
                loop: true
            },
            props: {
                target: {
                    type: 'objectSelector',
                    label: 'Objeto objetivo',
                    filter: ['3d-object', '3d-model', '3d-text', '3d-group']
                },
                property: {
                    type: 'select',
                    label: 'Propiedad',
                    options: [
                        { value: 'position', label: 'Posición' },
                        { value: 'rotation', label: 'Rotación' },
                        { value: 'scale', label: 'Escala' },
                        { value: 'opacity', label: 'Opacidad' },
                        { value: 'color', label: 'Color' }
                    ],
                    default: 'rotation'
                },
                preset: {
                    type: 'select',
                    label: 'Preset',
                    options: [
                        { value: 'rotate-y', label: 'Rotar Y' },
                        { value: 'rotate-x', label: 'Rotar X' },
                        { value: 'float', label: 'Flotar' },
                        { value: 'bounce', label: 'Rebotar' },
                        { value: 'pulse', label: 'Pulsar' },
                        { value: 'swing', label: 'Balancear' },
                        { value: 'custom', label: 'Personalizado' }
                    ],
                    default: 'rotate-y'
                },
                duration: { type: 'range', label: 'Duración', min: 0.1, max: 30, step: 0.1, default: 2, unit: 's' },
                delay: { type: 'range', label: 'Retraso', min: 0, max: 10, step: 0.1, default: 0, unit: 's' },
                loop: { type: 'toggle', label: 'Repetir', default: true },
                easing: {
                    type: 'select',
                    label: 'Easing',
                    options: [
                        { value: 'linear', label: 'Lineal' },
                        { value: 'ease', label: 'Ease' },
                        { value: 'ease-in', label: 'Ease In' },
                        { value: 'ease-out', label: 'Ease Out' },
                        { value: 'ease-in-out', label: 'Ease In Out' },
                        { value: 'bounce', label: 'Rebote' },
                        { value: 'elastic', label: 'Elástico' }
                    ],
                    default: 'linear'
                },
                trigger: {
                    type: 'select',
                    label: 'Disparador',
                    options: [
                        { value: 'load', label: 'Al cargar' },
                        { value: 'scroll', label: 'Al hacer scroll' },
                        { value: 'hover', label: 'Al pasar el cursor' },
                        { value: 'click', label: 'Al hacer clic' }
                    ],
                    default: 'load'
                }
            }
        }
    };

    /**
     * Presets de materiales
     */
    var MATERIAL_PRESETS = {
        'gold': {
            name: 'Oro',
            color: '#ffd700',
            metalness: 1,
            roughness: 0.2
        },
        'silver': {
            name: 'Plata',
            color: '#c0c0c0',
            metalness: 1,
            roughness: 0.3
        },
        'bronze': {
            name: 'Bronce',
            color: '#cd7f32',
            metalness: 0.8,
            roughness: 0.4
        },
        'copper': {
            name: 'Cobre',
            color: '#b87333',
            metalness: 0.9,
            roughness: 0.3
        },
        'plastic-red': {
            name: 'Plástico Rojo',
            color: '#e63946',
            metalness: 0,
            roughness: 0.5
        },
        'plastic-blue': {
            name: 'Plástico Azul',
            color: '#457b9d',
            metalness: 0,
            roughness: 0.5
        },
        'plastic-green': {
            name: 'Plástico Verde',
            color: '#2a9d8f',
            metalness: 0,
            roughness: 0.5
        },
        'glass': {
            name: 'Cristal',
            color: '#ffffff',
            metalness: 0,
            roughness: 0,
            opacity: 0.3
        },
        'glass-tinted': {
            name: 'Cristal Tintado',
            color: '#4a90d9',
            metalness: 0,
            roughness: 0,
            opacity: 0.5
        },
        'rubber': {
            name: 'Goma',
            color: '#2d3436',
            metalness: 0,
            roughness: 0.9
        },
        'wood': {
            name: 'Madera',
            color: '#8b4513',
            metalness: 0,
            roughness: 0.8
        },
        'marble': {
            name: 'Mármol',
            color: '#f5f5f5',
            metalness: 0.1,
            roughness: 0.3
        },
        'concrete': {
            name: 'Hormigón',
            color: '#808080',
            metalness: 0,
            roughness: 0.9
        },
        'neon-green': {
            name: 'Neón Verde',
            color: '#00ff88',
            metalness: 0,
            roughness: 0.5,
            emissive: '#00ff88',
            emissiveIntensity: 0.5
        },
        'neon-pink': {
            name: 'Neón Rosa',
            color: '#ff00ff',
            metalness: 0,
            roughness: 0.5,
            emissive: '#ff00ff',
            emissiveIntensity: 0.5
        },
        'neon-blue': {
            name: 'Neón Azul',
            color: '#00ffff',
            metalness: 0,
            roughness: 0.5,
            emissive: '#00ffff',
            emissiveIntensity: 0.5
        }
    };

    /**
     * Presets de animación 3D
     */
    var ANIMATION_PRESETS_3D = {
        'rotate-y': {
            name: 'Rotar Y',
            keyframes: [
                { time: 0, rotation: { y: 0 } },
                { time: 1, rotation: { y: 360 } }
            ],
            loop: true
        },
        'rotate-x': {
            name: 'Rotar X',
            keyframes: [
                { time: 0, rotation: { x: 0 } },
                { time: 1, rotation: { x: 360 } }
            ],
            loop: true
        },
        'float': {
            name: 'Flotar',
            keyframes: [
                { time: 0, position: { y: 0 } },
                { time: 0.5, position: { y: 0.3 } },
                { time: 1, position: { y: 0 } }
            ],
            loop: true,
            easing: 'ease-in-out'
        },
        'bounce': {
            name: 'Rebotar',
            keyframes: [
                { time: 0, position: { y: 0 }, scale: 1 },
                { time: 0.3, position: { y: 0.5 }, scale: 0.9 },
                { time: 0.5, position: { y: 0 }, scale: 1.1 },
                { time: 0.7, position: { y: 0.2 }, scale: 0.95 },
                { time: 1, position: { y: 0 }, scale: 1 }
            ],
            loop: true,
            easing: 'ease-out'
        },
        'pulse': {
            name: 'Pulsar',
            keyframes: [
                { time: 0, scale: 1 },
                { time: 0.5, scale: 1.1 },
                { time: 1, scale: 1 }
            ],
            loop: true,
            easing: 'ease-in-out'
        },
        'swing': {
            name: 'Balancear',
            keyframes: [
                { time: 0, rotation: { z: 0 } },
                { time: 0.25, rotation: { z: 15 } },
                { time: 0.5, rotation: { z: 0 } },
                { time: 0.75, rotation: { z: -15 } },
                { time: 1, rotation: { z: 0 } }
            ],
            loop: true,
            easing: 'ease-in-out'
        },
        'orbit': {
            name: 'Orbitar',
            keyframes: [
                { time: 0, position: { x: 2, z: 0 } },
                { time: 0.25, position: { x: 0, z: 2 } },
                { time: 0.5, position: { x: -2, z: 0 } },
                { time: 0.75, position: { x: 0, z: -2 } },
                { time: 1, position: { x: 2, z: 0 } }
            ],
            loop: true,
            easing: 'linear'
        },
        'fade-in': {
            name: 'Aparecer',
            keyframes: [
                { time: 0, opacity: 0, scale: 0.8 },
                { time: 1, opacity: 1, scale: 1 }
            ],
            loop: false,
            easing: 'ease-out'
        },
        'fade-out': {
            name: 'Desaparecer',
            keyframes: [
                { time: 0, opacity: 1, scale: 1 },
                { time: 1, opacity: 0, scale: 0.8 }
            ],
            loop: false,
            easing: 'ease-in'
        }
    };

    /**
     * Registrar bloques en el catálogo de VBP
     */
    function registerBlocks() {
        if (window.VBPStoreCatalog) {
            // Extender el catálogo existente
            var catalog = window.VBPStoreCatalog;

            // Agregar nombres de bloques 3D
            var originalGetDefaultName = catalog.getDefaultName;
            catalog.getDefaultName = function(type) {
                if (BLOCKS_3D[type]) {
                    return BLOCKS_3D[type].name;
                }
                return originalGetDefaultName.call(this, type);
            };

            // Agregar datos por defecto de bloques 3D
            var originalGetDefaultData = catalog.getDefaultData;
            catalog.getDefaultData = function(type) {
                if (BLOCKS_3D[type]) {
                    return BLOCKS_3D[type].defaultData;
                }
                return originalGetDefaultData.call(this, type);
            };

            console.log('[VBP] Bloques 3D registrados en el catálogo');
        }

        // Registrar categoría 3D
        if (window.VBPBlockCategories) {
            window.VBPBlockCategories.push(CATEGORY_3D);
        } else {
            window.VBPBlockCategories = [CATEGORY_3D];
        }
    }

    // Registrar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', registerBlocks);
    } else {
        registerBlocks();
    }

    /**
     * API pública
     */
    window.VBP3DBlocks = {
        BLOCKS: BLOCKS_3D,
        CATEGORY: CATEGORY_3D,
        MATERIAL_PRESETS: MATERIAL_PRESETS,
        ANIMATION_PRESETS: ANIMATION_PRESETS_3D,

        /**
         * Obtener definición de bloque
         * @param {string} blockType - Tipo de bloque
         * @returns {Object|null}
         */
        getBlock: function(blockType) {
            return BLOCKS_3D[blockType] || null;
        },

        /**
         * Obtener todos los bloques de la categoría 3D
         * @returns {Array}
         */
        getAllBlocks: function() {
            return Object.keys(BLOCKS_3D).map(function(key) {
                return BLOCKS_3D[key];
            });
        },

        /**
         * Obtener preset de material
         * @param {string} presetName - Nombre del preset
         * @returns {Object|null}
         */
        getMaterialPreset: function(presetName) {
            return MATERIAL_PRESETS[presetName] || null;
        },

        /**
         * Obtener preset de animación
         * @param {string} presetName - Nombre del preset
         * @returns {Object|null}
         */
        getAnimationPreset: function(presetName) {
            return ANIMATION_PRESETS_3D[presetName] || null;
        },

        /**
         * Listar presets de material
         * @returns {Array}
         */
        listMaterialPresets: function() {
            return Object.keys(MATERIAL_PRESETS).map(function(key) {
                return {
                    id: key,
                    name: MATERIAL_PRESETS[key].name,
                    preview: MATERIAL_PRESETS[key].color
                };
            });
        },

        /**
         * Listar presets de animación
         * @returns {Array}
         */
        listAnimationPresets: function() {
            return Object.keys(ANIMATION_PRESETS_3D).map(function(key) {
                return {
                    id: key,
                    name: ANIMATION_PRESETS_3D[key].name
                };
            });
        }
    };

})();
