#!/usr/bin/env node
/* eslint-env node */
/**
 * Build Script para Flavor Platform
 *
 * Minifica y procesa CSS/JS con soporte para:
 * - Modo desarrollo (sourcemaps, sin minificacion)
 * - Modo produccion (minificado, sin sourcemaps)
 * - Watch mode para desarrollo
 *
 * @package FlavorPlatform
 * @since 3.3.0
 */

const fs = require('fs');
const path = require('path');
const { minify } = require('terser');
const postcss = require('postcss');
const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');
const postcssImport = require('postcss-import');
const { glob } = require('glob');

// Colores para la consola
const colors = {
    reset: '\x1b[0m',
    bright: '\x1b[1m',
    green: '\x1b[32m',
    yellow: '\x1b[33m',
    red: '\x1b[31m',
    cyan: '\x1b[36m',
    blue: '\x1b[34m'
};

// Parsear argumentos de linea de comandos
const argumentos = process.argv.slice(2);
const configuracion = {
    modo: 'production',
    tipo: 'all',
    watch: false
};

argumentos.forEach(argumento => {
    if (argumento.startsWith('--mode=')) {
        configuracion.modo = argumento.split('=')[1];
    } else if (argumento.startsWith('--type=')) {
        configuracion.tipo = argumento.split('=')[1];
    } else if (argumento === '--watch') {
        configuracion.watch = true;
    }
});

const esProduccion = configuracion.modo === 'production';
const directorioPlugin = path.resolve(__dirname, '..');

// Estadisticas de build
const estadisticas = {
    archivosJsProcesados: 0,
    archivosCssProcesados: 0,
    errores: [],
    tiempoInicio: Date.now()
};

/**
 * Imprime un mensaje con formato
 */
function imprimirMensaje(tipo, mensaje) {
    const iconos = {
        info: `${colors.cyan}[INFO]${colors.reset}`,
        success: `${colors.green}[OK]${colors.reset}`,
        error: `${colors.red}[ERROR]${colors.reset}`,
        warning: `${colors.yellow}[WARN]${colors.reset}`,
        build: `${colors.blue}[BUILD]${colors.reset}`
    };
    console.log(`${iconos[tipo] || '[LOG]'} ${mensaje}`);
}

/**
 * Imprime el encabezado del build
 */
function imprimirEncabezado() {
    console.log('');
    console.log(`${colors.bright}========================================${colors.reset}`);
    console.log(`${colors.bright}  Flavor Platform - Build System${colors.reset}`);
    console.log(`${colors.bright}========================================${colors.reset}`);
    console.log(`  Modo: ${esProduccion ? colors.green + 'PRODUCCION' : colors.yellow + 'DESARROLLO'}${colors.reset}`);
    console.log(`  Tipo: ${configuracion.tipo.toUpperCase()}`);
    console.log(`  Watch: ${configuracion.watch ? 'SI' : 'NO'}`);
    console.log(`${colors.bright}========================================${colors.reset}`);
    console.log('');
}

/**
 * Busca archivos segun patron glob
 */
async function buscarArchivos(patrones) {
    const archivos = [];
    for (const patron of patrones) {
        const encontrados = await glob(patron, {
            cwd: directorioPlugin,
            ignore: ['**/node_modules/**', '**/vendor/**', '**/*.min.*']
        });
        archivos.push(...encontrados.map(archivo => path.join(directorioPlugin, archivo)));
    }
    return archivos;
}

/**
 * Minifica un archivo JavaScript
 */
async function minificarArchivoJs(rutaArchivo) {
    try {
        const nombreArchivo = path.basename(rutaArchivo);

        // Ignorar archivos ya minificados
        if (nombreArchivo.endsWith('.min.js')) {
            return;
        }

        const contenidoOriginal = fs.readFileSync(rutaArchivo, 'utf8');
        const rutaSalida = rutaArchivo.replace('.js', '.min.js');
        const rutaSourcemap = rutaSalida + '.map';

        const opcionesTerser = {
            compress: esProduccion ? {
                drop_console: false,
                drop_debugger: true,
                pure_funcs: ['console.debug']
            } : false,
            mangle: esProduccion,
            format: {
                comments: esProduccion ? false : 'some'
            },
            sourceMap: !esProduccion ? {
                filename: path.basename(rutaSalida),
                url: path.basename(rutaSourcemap)
            } : false
        };

        const resultado = await minify(contenidoOriginal, opcionesTerser);

        if (resultado.code) {
            fs.writeFileSync(rutaSalida, resultado.code);

            if (resultado.map && !esProduccion) {
                fs.writeFileSync(rutaSourcemap, resultado.map);
            }

            const rutaRelativa = path.relative(directorioPlugin, rutaArchivo);
            const tamanoOriginal = Buffer.byteLength(contenidoOriginal, 'utf8');
            const tamanoMinificado = Buffer.byteLength(resultado.code, 'utf8');
            const porcentajeReduccion = ((1 - tamanoMinificado / tamanoOriginal) * 100).toFixed(1);

            imprimirMensaje('success', `JS: ${rutaRelativa} (${porcentajeReduccion}% reduccion)`);
            estadisticas.archivosJsProcesados++;
        }
    } catch (error) {
        const rutaRelativa = path.relative(directorioPlugin, rutaArchivo);
        imprimirMensaje('error', `JS: ${rutaRelativa} - ${error.message}`);
        estadisticas.errores.push({ archivo: rutaArchivo, error: error.message });
    }
}

/**
 * Procesa un archivo CSS con PostCSS
 */
async function procesarArchivoCss(rutaArchivo) {
    try {
        const nombreArchivo = path.basename(rutaArchivo);

        // Ignorar archivos ya minificados
        if (nombreArchivo.endsWith('.min.css')) {
            return;
        }

        const contenidoOriginal = fs.readFileSync(rutaArchivo, 'utf8');
        const rutaSalida = rutaArchivo.replace('.css', '.min.css');
        const rutaSourcemap = rutaSalida + '.map';

        const pluginsPostcss = [
            postcssImport(),
            autoprefixer({
                overrideBrowserslist: ['> 1%', 'last 2 versions', 'not dead']
            })
        ];

        if (esProduccion) {
            pluginsPostcss.push(cssnano({
                preset: ['default', {
                    discardComments: { removeAll: true },
                    normalizeWhitespace: true,
                    minifyFontValues: true,
                    minifyGradients: true
                }]
            }));
        }

        const opcionesPostcss = {
            from: rutaArchivo,
            to: rutaSalida,
            map: !esProduccion ? {
                inline: false,
                annotation: path.basename(rutaSourcemap)
            } : false
        };

        const resultado = await postcss(pluginsPostcss).process(contenidoOriginal, opcionesPostcss);

        fs.writeFileSync(rutaSalida, resultado.css);

        if (resultado.map && !esProduccion) {
            fs.writeFileSync(rutaSourcemap, resultado.map.toString());
        }

        const rutaRelativa = path.relative(directorioPlugin, rutaArchivo);
        const tamanoOriginal = Buffer.byteLength(contenidoOriginal, 'utf8');
        const tamanoMinificado = Buffer.byteLength(resultado.css, 'utf8');
        const porcentajeReduccion = ((1 - tamanoMinificado / tamanoOriginal) * 100).toFixed(1);

        imprimirMensaje('success', `CSS: ${rutaRelativa} (${porcentajeReduccion}% reduccion)`);
        estadisticas.archivosCssProcesados++;

    } catch (error) {
        const rutaRelativa = path.relative(directorioPlugin, rutaArchivo);
        imprimirMensaje('error', `CSS: ${rutaRelativa} - ${error.message}`);
        estadisticas.errores.push({ archivo: rutaArchivo, error: error.message });
    }
}

/**
 * Procesa todos los archivos JavaScript
 */
async function procesarJavascript() {
    imprimirMensaje('build', 'Procesando archivos JavaScript...');

    const patronesJs = [
        'assets/js/**/*.js',
        'assets/vbp/js/**/*.js',
        'admin/js/**/*.js',
        'addons/*/assets/js/**/*.js'
    ];

    const archivosJs = await buscarArchivos(patronesJs);

    for (const archivo of archivosJs) {
        await minificarArchivoJs(archivo);
    }
}

/**
 * Procesa todos los archivos CSS
 */
async function procesarCss() {
    imprimirMensaje('build', 'Procesando archivos CSS...');

    const patronesCss = [
        'assets/css/**/*.css',
        'assets/vbp/css/**/*.css',
        'admin/css/**/*.css',
        'addons/*/assets/css/**/*.css'
    ];

    const archivosCss = await buscarArchivos(patronesCss);

    for (const archivo of archivosCss) {
        await procesarArchivoCss(archivo);
    }
}

/**
 * Copia assets estaticos (imagenes, fuentes, etc.)
 */
async function copiarAssets() {
    imprimirMensaje('build', 'Copiando assets estaticos...');

    const directoriosDist = [
        path.join(directorioPlugin, 'assets/css/dist'),
        path.join(directorioPlugin, 'assets/js/dist')
    ];

    for (const directorio of directoriosDist) {
        if (!fs.existsSync(directorio)) {
            fs.mkdirSync(directorio, { recursive: true });
            imprimirMensaje('info', `Creado directorio: ${path.relative(directorioPlugin, directorio)}`);
        }
    }
}

/**
 * Imprime el resumen final del build
 */
function imprimirResumen() {
    const tiempoTotal = ((Date.now() - estadisticas.tiempoInicio) / 1000).toFixed(2);

    console.log('');
    console.log(`${colors.bright}========================================${colors.reset}`);
    console.log(`${colors.bright}  Resumen del Build${colors.reset}`);
    console.log(`${colors.bright}========================================${colors.reset}`);
    console.log(`  Archivos JS procesados: ${estadisticas.archivosJsProcesados}`);
    console.log(`  Archivos CSS procesados: ${estadisticas.archivosCssProcesados}`);
    console.log(`  Tiempo total: ${tiempoTotal}s`);

    if (estadisticas.errores.length > 0) {
        console.log(`  ${colors.red}Errores: ${estadisticas.errores.length}${colors.reset}`);
        estadisticas.errores.forEach(errorItem => {
            console.log(`    - ${path.relative(directorioPlugin, errorItem.archivo)}`);
        });
    } else {
        console.log(`  ${colors.green}Estado: Sin errores${colors.reset}`);
    }

    console.log(`${colors.bright}========================================${colors.reset}`);
    console.log('');
}

/**
 * Inicia el modo watch para desarrollo
 */
async function iniciarModoWatch() {
    const chokidar = require('chokidar');

    imprimirMensaje('info', 'Iniciando modo watch...');
    imprimirMensaje('info', 'Presiona Ctrl+C para detener');
    console.log('');

    const patronesWatch = [];

    if (configuracion.tipo === 'all' || configuracion.tipo === 'js') {
        patronesWatch.push(
            path.join(directorioPlugin, 'assets/js/**/*.js'),
            path.join(directorioPlugin, 'admin/js/**/*.js'),
            path.join(directorioPlugin, 'addons/*/assets/js/**/*.js')
        );
    }

    if (configuracion.tipo === 'all' || configuracion.tipo === 'css') {
        patronesWatch.push(
            path.join(directorioPlugin, 'assets/css/**/*.css'),
            path.join(directorioPlugin, 'admin/css/**/*.css'),
            path.join(directorioPlugin, 'addons/*/assets/css/**/*.css')
        );
    }

    const observador = chokidar.watch(patronesWatch, {
        ignored: [
            /(^|[\/\\])\../,
            /\.min\.(js|css)$/,
            /\.map$/,
            /node_modules/
        ],
        persistent: true,
        awaitWriteFinish: {
            stabilityThreshold: 300,
            pollInterval: 100
        }
    });

    observador.on('change', async (rutaArchivo) => {
        const extension = path.extname(rutaArchivo);
        const rutaRelativa = path.relative(directorioPlugin, rutaArchivo);

        imprimirMensaje('info', `Cambio detectado: ${rutaRelativa}`);

        if (extension === '.js') {
            await minificarArchivoJs(rutaArchivo);
        } else if (extension === '.css') {
            await procesarArchivoCss(rutaArchivo);
        }
    });

    observador.on('ready', () => {
        imprimirMensaje('success', 'Watch iniciado. Esperando cambios...');
    });
}

/**
 * Funcion principal del build
 */
async function ejecutarBuild() {
    imprimirEncabezado();

    try {
        await copiarAssets();

        if (configuracion.tipo === 'all' || configuracion.tipo === 'js') {
            await procesarJavascript();
        }

        if (configuracion.tipo === 'all' || configuracion.tipo === 'css') {
            await procesarCss();
        }

        imprimirResumen();

        if (configuracion.watch) {
            await iniciarModoWatch();
        } else {
            process.exit(estadisticas.errores.length > 0 ? 1 : 0);
        }

    } catch (error) {
        imprimirMensaje('error', `Build fallido: ${error.message}`);
        console.error(error);
        process.exit(1);
    }
}

// Ejecutar build
ejecutarBuild();
