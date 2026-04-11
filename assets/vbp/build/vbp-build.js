#!/usr/bin/env node
/**
 * VBP Build Script
 *
 * Script especializado para bundling y minificacion de Visual Builder Pro.
 * Genera bundles optimizados, manifiesto de assets y soporte para lazy loading.
 *
 * Uso:
 *   node vbp-build.js                    # Build completo de produccion
 *   node vbp-build.js --mode=development # Build de desarrollo con sourcemaps
 *   node vbp-build.js --bundle=vbp-core  # Build de un bundle especifico
 *   node vbp-build.js --analyze          # Analizar tamanos de bundles
 *   node vbp-build.js --manifest-only    # Solo generar manifiesto
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 3.5.0
 */

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');

// Cargar configuracion
const buildConfig = require('./build.config.js');

// Colores para consola
const colores = {
    reset: '\x1b[0m',
    bold: '\x1b[1m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    cyan: '\x1b[36m',
    blue: '\x1b[34m',
    magenta: '\x1b[35m',
    gray: '\x1b[90m'
};

// Parsear argumentos
const argumentos = process.argv.slice(2);
const configuracion = {
    modo: 'production',
    bundle: null,
    analyze: false,
    manifestOnly: false,
    verbose: false
};

argumentos.forEach(argumento => {
    if (argumento.startsWith('--mode=')) {
        configuracion.modo = argumento.split('=')[1];
    } else if (argumento.startsWith('--bundle=')) {
        configuracion.bundle = argumento.split('=')[1];
    } else if (argumento === '--analyze') {
        configuracion.analyze = true;
    } else if (argumento === '--manifest-only') {
        configuracion.manifestOnly = true;
    } else if (argumento === '--verbose' || argumento === '-v') {
        configuracion.verbose = true;
    }
});

const esProduccion = configuracion.modo === 'production';
const directorioBase = path.resolve(__dirname, '..');
const directorioSalida = path.resolve(directorioBase, 'dist');

// Estadisticas
const estadisticas = {
    bundlesJsProcesados: 0,
    bundlesCssProcesados: 0,
    tamanoTotalOriginal: 0,
    tamanoTotalMinificado: 0,
    errores: [],
    tiempoInicio: Date.now()
};

/**
 * Imprime mensaje con formato
 */
function log(tipo, mensaje) {
    const iconos = {
        info: `${colores.cyan}[INFO]${colores.reset}`,
        success: `${colores.green}[OK]${colores.reset}`,
        error: `${colores.red}[ERROR]${colores.reset}`,
        warning: `${colores.yellow}[WARN]${colores.reset}`,
        build: `${colores.blue}[BUILD]${colores.reset}`,
        bundle: `${colores.magenta}[BUNDLE]${colores.reset}`
    };
    console.log(`${iconos[tipo] || '[LOG]'} ${mensaje}`);
}

/**
 * Imprime encabezado
 */
function imprimirEncabezado() {
    console.log('');
    console.log(`${colores.bold}================================================${colores.reset}`);
    console.log(`${colores.bold}  VBP Build System v${buildConfig.version}${colores.reset}`);
    console.log(`${colores.bold}================================================${colores.reset}`);
    console.log(`  Modo: ${esProduccion ? colores.green + 'PRODUCCION' : colores.yellow + 'DESARROLLO'}${colores.reset}`);
    if (configuracion.bundle) {
        console.log(`  Bundle: ${configuracion.bundle}`);
    }
    console.log(`${colores.bold}================================================${colores.reset}`);
    console.log('');
}

/**
 * Asegura que el directorio existe
 */
function asegurarDirectorio(directorio) {
    if (!fs.existsSync(directorio)) {
        fs.mkdirSync(directorio, { recursive: true });
    }
}

/**
 * Lee y concatena archivos
 */
function leerArchivos(archivos) {
    let contenidoCombinado = '';
    let tamanoTotal = 0;

    for (const archivo of archivos) {
        const rutaCompleta = path.join(directorioBase, archivo);

        if (fs.existsSync(rutaCompleta)) {
            const contenido = fs.readFileSync(rutaCompleta, 'utf8');
            contenidoCombinado += `\n/* === ${archivo} === */\n${contenido}\n`;
            tamanoTotal += Buffer.byteLength(contenido, 'utf8');
        } else {
            log('warning', `Archivo no encontrado: ${archivo}`);
        }
    }

    return { contenido: contenidoCombinado, tamano: tamanoTotal };
}

/**
 * Minifica bundle JavaScript
 */
async function minificarBundleJs(nombreBundle, archivos) {
    try {
        const { contenido, tamano } = leerArchivos(archivos);
        estadisticas.tamanoTotalOriginal += tamano;

        // Agregar wrapper IIFE para encapsulamiento
        const contenidoEnvuelto = `(function() {\n'use strict';\n${contenido}\n})();`;

        const opcionesTerser = esProduccion
            ? buildConfig.terserOptions.production
            : buildConfig.terserOptions.development;

        const resultado = await minify(contenidoEnvuelto, opcionesTerser);

        if (resultado.code) {
            const rutaSalida = path.join(directorioSalida, `${nombreBundle}.bundle.js`);
            fs.writeFileSync(rutaSalida, resultado.code);

            if (resultado.map && !esProduccion) {
                const rutaSourcemap = rutaSalida + '.map';
                fs.writeFileSync(rutaSourcemap, resultado.map);
            }

            const tamanoMinificado = Buffer.byteLength(resultado.code, 'utf8');
            estadisticas.tamanoTotalMinificado += tamanoMinificado;
            estadisticas.bundlesJsProcesados++;

            const reduccion = ((1 - tamanoMinificado / tamano) * 100).toFixed(1);
            const tamanoKb = (tamanoMinificado / 1024).toFixed(1);

            log('bundle', `JS: ${nombreBundle}.bundle.js (${tamanoKb}KB, -${reduccion}%)`);

            return {
                nombre: nombreBundle,
                archivo: `${nombreBundle}.bundle.js`,
                tamanoOriginal: tamano,
                tamanoMinificado: tamanoMinificado,
                archivos: archivos.length
            };
        }
    } catch (error) {
        log('error', `Error en bundle ${nombreBundle}: ${error.message}`);
        estadisticas.errores.push({ bundle: nombreBundle, error: error.message });
        return null;
    }
}

/**
 * Minifica bundle CSS
 */
async function minificarBundleCss(nombreBundle, archivos) {
    try {
        const { contenido, tamano } = leerArchivos(archivos);
        estadisticas.tamanoTotalOriginal += tamano;

        const pluginsPostcss = [
            autoprefixer({
                overrideBrowserslist: ['> 1%', 'last 2 versions', 'not dead']
            })
        ];

        if (esProduccion) {
            pluginsPostcss.push(cssnano(buildConfig.cssnanoOptions.production));
        }

        const rutaSalida = path.join(directorioSalida, `${nombreBundle}.bundle.css`);

        const resultado = await postcss(pluginsPostcss).process(contenido, {
            from: undefined,
            to: rutaSalida,
            map: !esProduccion ? { inline: false } : false
        });

        fs.writeFileSync(rutaSalida, resultado.css);

        if (resultado.map && !esProduccion) {
            fs.writeFileSync(rutaSalida + '.map', resultado.map.toString());
        }

        const tamanoMinificado = Buffer.byteLength(resultado.css, 'utf8');
        estadisticas.tamanoTotalMinificado += tamanoMinificado;
        estadisticas.bundlesCssProcesados++;

        const reduccion = ((1 - tamanoMinificado / tamano) * 100).toFixed(1);
        const tamanoKb = (tamanoMinificado / 1024).toFixed(1);

        log('bundle', `CSS: ${nombreBundle}.bundle.css (${tamanoKb}KB, -${reduccion}%)`);

        return {
            nombre: nombreBundle,
            archivo: `${nombreBundle}.bundle.css`,
            tamanoOriginal: tamano,
            tamanoMinificado: tamanoMinificado,
            archivos: archivos.length
        };
    } catch (error) {
        log('error', `Error en bundle CSS ${nombreBundle}: ${error.message}`);
        estadisticas.errores.push({ bundle: nombreBundle, error: error.message });
        return null;
    }
}

/**
 * Genera manifiesto de assets
 */
function generarManifiesto(bundlesJs, bundlesCss) {
    const manifiesto = {
        version: buildConfig.version,
        generated: new Date().toISOString(),
        mode: configuracion.modo,
        bundles: {
            js: {},
            css: {}
        },
        lazyLoad: {
            triggers: {},
            featureFlags: {}
        },
        preload: [],
        stats: {
            totalBundles: bundlesJs.length + bundlesCss.length,
            totalSizeOriginal: estadisticas.tamanoTotalOriginal,
            totalSizeMinified: estadisticas.tamanoTotalMinificado,
            reduction: ((1 - estadisticas.tamanoTotalMinificado / estadisticas.tamanoTotalOriginal) * 100).toFixed(1) + '%'
        }
    };

    // Procesar bundles JS
    for (const bundle of bundlesJs) {
        if (!bundle) continue;

        const configBundle = buildConfig.bundles[bundle.nombre];
        manifiesto.bundles.js[bundle.nombre] = {
            file: bundle.archivo,
            size: bundle.tamanoMinificado,
            files: bundle.archivos,
            dependencies: configBundle?.dependencies || [],
            priority: configBundle?.priority || 'normal',
            lazy: configBundle?.lazy || false
        };

        if (configBundle?.preload) {
            manifiesto.preload.push({
                type: 'script',
                bundle: bundle.nombre,
                file: bundle.archivo
            });
        }

        if (configBundle?.lazy && configBundle?.trigger) {
            if (!manifiesto.lazyLoad.triggers[configBundle.trigger]) {
                manifiesto.lazyLoad.triggers[configBundle.trigger] = [];
            }
            manifiesto.lazyLoad.triggers[configBundle.trigger].push({
                type: 'js',
                bundle: bundle.nombre
            });
        }

        if (configBundle?.featureFlag) {
            if (!manifiesto.lazyLoad.featureFlags[configBundle.featureFlag]) {
                manifiesto.lazyLoad.featureFlags[configBundle.featureFlag] = [];
            }
            manifiesto.lazyLoad.featureFlags[configBundle.featureFlag].push({
                type: 'js',
                bundle: bundle.nombre
            });
        }
    }

    // Procesar bundles CSS
    for (const bundle of bundlesCss) {
        if (!bundle) continue;

        const configBundle = buildConfig.cssBundles[bundle.nombre];
        manifiesto.bundles.css[bundle.nombre] = {
            file: bundle.archivo,
            size: bundle.tamanoMinificado,
            files: bundle.archivos,
            dependencies: configBundle?.dependencies || [],
            priority: configBundle?.priority || 'normal',
            lazy: configBundle?.lazy || false
        };

        if (configBundle?.preload) {
            manifiesto.preload.push({
                type: 'style',
                bundle: bundle.nombre,
                file: bundle.archivo
            });
        }

        if (configBundle?.lazy && configBundle?.trigger) {
            if (!manifiesto.lazyLoad.triggers[configBundle.trigger]) {
                manifiesto.lazyLoad.triggers[configBundle.trigger] = [];
            }
            manifiesto.lazyLoad.triggers[configBundle.trigger].push({
                type: 'css',
                bundle: bundle.nombre
            });
        }

        if (configBundle?.featureFlag) {
            if (!manifiesto.lazyLoad.featureFlags[configBundle.featureFlag]) {
                manifiesto.lazyLoad.featureFlags[configBundle.featureFlag] = [];
            }
            manifiesto.lazyLoad.featureFlags[configBundle.featureFlag].push({
                type: 'css',
                bundle: bundle.nombre
            });
        }
    }

    // Escribir manifiesto
    const rutaManifiesto = path.join(directorioSalida, 'manifest.json');
    fs.writeFileSync(rutaManifiesto, JSON.stringify(manifiesto, null, 2));

    log('success', `Manifiesto generado: manifest.json`);

    return manifiesto;
}

/**
 * Genera archivo de cargador lazy
 */
function generarLazyLoader(manifiesto) {
    const loaderCode = `/**
 * VBP Lazy Loader
 *
 * Carga bundles bajo demanda basado en triggers y feature flags.
 * Generado automaticamente - NO EDITAR
 *
 * @generated ${new Date().toISOString()}
 */
(function() {
    'use strict';

    const VBP_MANIFEST = ${JSON.stringify(manifiesto, null, 2)};

    const loadedBundles = new Set();
    const loadingBundles = new Map();

    /**
     * Carga un bundle de JavaScript
     */
    function loadJsBundle(bundleName) {
        if (loadedBundles.has('js:' + bundleName)) {
            return Promise.resolve();
        }

        if (loadingBundles.has('js:' + bundleName)) {
            return loadingBundles.get('js:' + bundleName);
        }

        const bundleInfo = VBP_MANIFEST.bundles.js[bundleName];
        if (!bundleInfo) {
            console.warn('[VBP] Bundle no encontrado:', bundleName);
            return Promise.reject(new Error('Bundle not found: ' + bundleName));
        }

        // Cargar dependencias primero
        const loadDeps = bundleInfo.dependencies.map(dep => loadJsBundle(dep));

        const loadPromise = Promise.all(loadDeps).then(() => {
            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = VBP_Config.assetsUrl + 'dist/' + bundleInfo.file;
                script.async = true;

                script.onload = () => {
                    loadedBundles.add('js:' + bundleName);
                    loadingBundles.delete('js:' + bundleName);
                    console.debug('[VBP] Bundle cargado:', bundleName);
                    resolve();
                };

                script.onerror = () => {
                    loadingBundles.delete('js:' + bundleName);
                    reject(new Error('Failed to load: ' + bundleName));
                };

                document.head.appendChild(script);
            });
        });

        loadingBundles.set('js:' + bundleName, loadPromise);
        return loadPromise;
    }

    /**
     * Carga un bundle de CSS
     */
    function loadCssBundle(bundleName) {
        if (loadedBundles.has('css:' + bundleName)) {
            return Promise.resolve();
        }

        const bundleInfo = VBP_MANIFEST.bundles.css[bundleName];
        if (!bundleInfo) {
            console.warn('[VBP] CSS Bundle no encontrado:', bundleName);
            return Promise.reject(new Error('CSS Bundle not found: ' + bundleName));
        }

        // Cargar dependencias CSS primero
        const loadDeps = (bundleInfo.dependencies || []).map(dep => loadCssBundle(dep));

        return Promise.all(loadDeps).then(() => {
            return new Promise((resolve) => {
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = VBP_Config.assetsUrl + 'dist/' + bundleInfo.file;

                link.onload = () => {
                    loadedBundles.add('css:' + bundleName);
                    console.debug('[VBP] CSS Bundle cargado:', bundleName);
                    resolve();
                };

                link.onerror = () => {
                    console.warn('[VBP] Error cargando CSS:', bundleName);
                    resolve(); // No bloquear por CSS faltante
                };

                document.head.appendChild(link);
            });
        });
    }

    /**
     * Carga bundles por trigger
     */
    function loadByTrigger(triggerName) {
        const bundles = VBP_MANIFEST.lazyLoad.triggers[triggerName] || [];
        const promises = [];

        for (const bundle of bundles) {
            if (bundle.type === 'js') {
                promises.push(loadJsBundle(bundle.bundle));
            } else if (bundle.type === 'css') {
                promises.push(loadCssBundle(bundle.bundle));
            }
        }

        return Promise.all(promises);
    }

    /**
     * Carga bundles por feature flag
     */
    function loadByFeatureFlag(flagName) {
        const bundles = VBP_MANIFEST.lazyLoad.featureFlags[flagName] || [];
        const promises = [];

        for (const bundle of bundles) {
            if (bundle.type === 'js') {
                promises.push(loadJsBundle(bundle.bundle));
            } else if (bundle.type === 'css') {
                promises.push(loadCssBundle(bundle.bundle));
            }
        }

        return Promise.all(promises);
    }

    /**
     * Precarga bundles criticos
     */
    function preloadCritical() {
        for (const item of VBP_MANIFEST.preload) {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.href = VBP_Config.assetsUrl + 'dist/' + item.file;
            link.as = item.type === 'script' ? 'script' : 'style';
            document.head.appendChild(link);
        }
    }

    // Exponer API global
    window.VBPLoader = {
        loadJs: loadJsBundle,
        loadCss: loadCssBundle,
        loadByTrigger: loadByTrigger,
        loadByFeatureFlag: loadByFeatureFlag,
        preloadCritical: preloadCritical,
        isLoaded: (type, name) => loadedBundles.has(type + ':' + name),
        manifest: VBP_MANIFEST
    };

    // Auto-precargar si el documento esta listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', preloadCritical);
    } else {
        preloadCritical();
    }

})();
`;

    const rutaLoader = path.join(directorioSalida, 'vbp-loader.js');
    fs.writeFileSync(rutaLoader, loaderCode);

    // Minificar loader en produccion
    if (esProduccion) {
        minify(loaderCode, buildConfig.terserOptions.production).then(resultado => {
            if (resultado.code) {
                fs.writeFileSync(path.join(directorioSalida, 'vbp-loader.min.js'), resultado.code);
            }
        });
    }

    log('success', `Lazy loader generado: vbp-loader.js`);
}

/**
 * Analiza tamanos de bundles
 */
function analizarBundles(bundlesJs, bundlesCss) {
    console.log('');
    console.log(`${colores.bold}Analisis de Bundles${colores.reset}`);
    console.log('─'.repeat(60));

    const todosLosBundles = [
        ...bundlesJs.filter(b => b).map(b => ({ ...b, type: 'JS' })),
        ...bundlesCss.filter(b => b).map(b => ({ ...b, type: 'CSS' }))
    ].sort((a, b) => b.tamanoMinificado - a.tamanoMinificado);

    for (const bundle of todosLosBundles) {
        const tamanoKb = (bundle.tamanoMinificado / 1024).toFixed(1);
        const barra = '█'.repeat(Math.min(Math.ceil(bundle.tamanoMinificado / 5000), 40));
        const color = bundle.tamanoMinificado > 100000 ? colores.red :
                      bundle.tamanoMinificado > 50000 ? colores.yellow : colores.green;

        console.log(`${bundle.type.padEnd(4)} ${bundle.nombre.padEnd(20)} ${tamanoKb.padStart(7)}KB ${color}${barra}${colores.reset}`);
    }

    console.log('─'.repeat(60));

    const totalOriginalKb = (estadisticas.tamanoTotalOriginal / 1024).toFixed(1);
    const totalMinKb = (estadisticas.tamanoTotalMinificado / 1024).toFixed(1);
    const reduccionTotal = ((1 - estadisticas.tamanoTotalMinificado / estadisticas.tamanoTotalOriginal) * 100).toFixed(1);

    console.log(`Total Original:   ${totalOriginalKb}KB`);
    console.log(`Total Minificado: ${totalMinKb}KB`);
    console.log(`Reduccion:        ${reduccionTotal}%`);
    console.log('');
}

/**
 * Imprime resumen final
 */
function imprimirResumen() {
    const tiempoTotal = ((Date.now() - estadisticas.tiempoInicio) / 1000).toFixed(2);

    console.log('');
    console.log(`${colores.bold}================================================${colores.reset}`);
    console.log(`${colores.bold}  Resumen del Build${colores.reset}`);
    console.log(`${colores.bold}================================================${colores.reset}`);
    console.log(`  Bundles JS:  ${estadisticas.bundlesJsProcesados}`);
    console.log(`  Bundles CSS: ${estadisticas.bundlesCssProcesados}`);
    console.log(`  Tiempo:      ${tiempoTotal}s`);

    if (estadisticas.errores.length > 0) {
        console.log(`  ${colores.red}Errores: ${estadisticas.errores.length}${colores.reset}`);
        estadisticas.errores.forEach(err => {
            console.log(`    - ${err.bundle}: ${err.error}`);
        });
    } else {
        console.log(`  ${colores.green}Estado: Sin errores${colores.reset}`);
    }

    console.log(`${colores.bold}================================================${colores.reset}`);
    console.log('');
}

/**
 * Funcion principal
 */
async function ejecutarBuild() {
    imprimirEncabezado();

    // Asegurar directorio de salida
    asegurarDirectorio(directorioSalida);

    const bundlesJs = [];
    const bundlesCss = [];

    if (!configuracion.manifestOnly) {
        // Procesar bundles JS
        log('build', 'Procesando bundles JavaScript...');
        for (const [nombre, config] of Object.entries(buildConfig.bundles)) {
            if (configuracion.bundle && configuracion.bundle !== nombre) {
                continue;
            }
            const resultado = await minificarBundleJs(nombre, config.files);
            bundlesJs.push(resultado);
        }

        // Procesar bundles CSS
        log('build', 'Procesando bundles CSS...');
        for (const [nombre, config] of Object.entries(buildConfig.cssBundles)) {
            if (configuracion.bundle && configuracion.bundle !== nombre) {
                continue;
            }
            const resultado = await minificarBundleCss(nombre, config.files);
            bundlesCss.push(resultado);
        }
    }

    // Generar manifiesto
    log('build', 'Generando manifiesto de assets...');
    const manifiesto = generarManifiesto(bundlesJs, bundlesCss);

    // Generar lazy loader
    log('build', 'Generando lazy loader...');
    generarLazyLoader(manifiesto);

    // Analizar si se solicito
    if (configuracion.analyze) {
        analizarBundles(bundlesJs, bundlesCss);
    }

    imprimirResumen();

    process.exit(estadisticas.errores.length > 0 ? 1 : 0);
}

// Ejecutar
ejecutarBuild().catch(error => {
    log('error', `Build fallido: ${error.message}`);
    console.error(error);
    process.exit(1);
});
