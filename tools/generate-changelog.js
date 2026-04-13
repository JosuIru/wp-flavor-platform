#!/usr/bin/env node
/**
 * generate-changelog.js
 *
 * Genera CHANGELOG.md automaticamente desde commits que siguen Conventional Commits.
 *
 * Uso:
 *   node scripts/generate-changelog.js                    # Desde ultimo tag
 *   node scripts/generate-changelog.js --from=v3.5.0      # Desde tag especifico
 *   node scripts/generate-changelog.js --version=3.6.0    # Para version especifica
 *   node scripts/generate-changelog.js --dry-run          # Solo mostrar, no escribir
 *
 * @package FlavorPlatform
 * @since 3.5.0
 */

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// Configuracion
const CONFIGURACION = {
    archivoChangelog: path.join(__dirname, '..', 'CHANGELOG.md'),
    tiposDeCommit: {
        feat: { titulo: 'Anadido', emoji: '', orden: 1 },
        fix: { titulo: 'Corregido', emoji: '', orden: 2 },
        perf: { titulo: 'Rendimiento', emoji: '', orden: 3 },
        refactor: { titulo: 'Refactorizado', emoji: '', orden: 4 },
        docs: { titulo: 'Documentacion', emoji: '', orden: 5 },
        style: { titulo: 'Estilo', emoji: '', orden: 6 },
        test: { titulo: 'Testing', emoji: '', orden: 7 },
        build: { titulo: 'Build', emoji: '', orden: 8 },
        ci: { titulo: 'CI/CD', emoji: '', orden: 9 },
        chore: { titulo: 'Mantenimiento', emoji: '', orden: 10 },
        security: { titulo: 'Seguridad', emoji: '', orden: 0 },
    },
    scopesIgnorados: ['deps', 'release'],
    maxLineasDescripcion: 100,
};

/**
 * Parsea argumentos de linea de comandos
 */
function parsearArgumentos() {
    const argumentos = {
        desde: null,
        version: null,
        soloMostrar: false,
        ayuda: false,
    };

    process.argv.slice(2).forEach(argumento => {
        if (argumento.startsWith('--from=')) {
            argumentos.desde = argumento.replace('--from=', '');
        } else if (argumento.startsWith('--version=')) {
            argumentos.version = argumento.replace('--version=', '');
        } else if (argumento === '--dry-run') {
            argumentos.soloMostrar = true;
        } else if (argumento === '--help' || argumento === '-h') {
            argumentos.ayuda = true;
        }
    });

    return argumentos;
}

/**
 * Muestra ayuda
 */
function mostrarAyuda() {
    console.log(`
Generador de Changelog para Flavor Platform

Uso:
  node scripts/generate-changelog.js [opciones]

Opciones:
  --from=TAG       Generar desde tag especifico (ej: --from=v3.5.0)
  --version=X.Y.Z  Version a generar (ej: --version=3.6.0)
  --dry-run        Solo mostrar, no escribir archivo
  -h, --help       Mostrar esta ayuda

Ejemplos:
  node scripts/generate-changelog.js
  node scripts/generate-changelog.js --from=v3.5.0 --version=3.6.0
  node scripts/generate-changelog.js --dry-run
`);
}

/**
 * Ejecuta comando git y retorna output
 */
function ejecutarGit(comando) {
    try {
        return execSync(`git ${comando}`, { encoding: 'utf-8' }).trim();
    } catch (error) {
        console.error(`Error ejecutando git ${comando}:`, error.message);
        return '';
    }
}

/**
 * Obtiene el ultimo tag
 */
function obtenerUltimoTag() {
    const tags = ejecutarGit('tag --sort=-v:refname');
    if (!tags) {
        return null;
    }
    return tags.split('\n')[0];
}

/**
 * Obtiene commits desde un punto
 */
function obtenerCommits(desde) {
    const rango = desde ? `${desde}..HEAD` : 'HEAD~50..HEAD';
    const formatoLog = '%H|%s|%b|---COMMIT_END---';

    const logOutput = ejecutarGit(`log ${rango} --pretty=format:"${formatoLog}"`);
    if (!logOutput) {
        return [];
    }

    const commitsRaw = logOutput.split('---COMMIT_END---').filter(Boolean);
    const commits = [];

    for (const commitRaw of commitsRaw) {
        const lineas = commitRaw.trim().split('|');
        if (lineas.length < 2) continue;

        const hash = lineas[0];
        const asunto = lineas[1];
        const cuerpo = lineas.slice(2).join('|');

        const commitParseado = parsearCommitConvencional(asunto, cuerpo, hash);
        if (commitParseado) {
            commits.push(commitParseado);
        }
    }

    return commits;
}

/**
 * Parsea un commit que sigue Conventional Commits
 */
function parsearCommitConvencional(asunto, cuerpo, hash) {
    // Patron: tipo(scope)!: descripcion o tipo!: descripcion o tipo: descripcion
    const patronCommit = /^(\w+)(?:\(([^)]+)\))?(!)?:\s*(.+)$/;
    const coincidencia = asunto.match(patronCommit);

    if (!coincidencia) {
        return null;
    }

    const [, tipo, scope, esBreaking, descripcion] = coincidencia;

    // Ignorar tipos desconocidos
    if (!CONFIGURACION.tiposDeCommit[tipo]) {
        return null;
    }

    // Ignorar scopes ignorados
    if (scope && CONFIGURACION.scopesIgnorados.includes(scope)) {
        return null;
    }

    // Detectar breaking change en cuerpo
    const tieneBreakingEnCuerpo = cuerpo && cuerpo.includes('BREAKING CHANGE:');

    return {
        hash: hash.substring(0, 7),
        tipo,
        scope: scope || null,
        descripcion: descripcion.trim(),
        esBreaking: esBreaking === '!' || tieneBreakingEnCuerpo,
        cuerpo: cuerpo ? cuerpo.trim() : null,
    };
}

/**
 * Agrupa commits por tipo
 */
function agruparCommitsPorTipo(commits) {
    const grupos = {};

    for (const commit of commits) {
        if (!grupos[commit.tipo]) {
            grupos[commit.tipo] = [];
        }
        grupos[commit.tipo].push(commit);
    }

    // Ordenar grupos segun configuracion
    const gruposOrdenados = {};
    const tiposOrdenados = Object.entries(CONFIGURACION.tiposDeCommit)
        .sort((a, b) => a[1].orden - b[1].orden)
        .map(([tipo]) => tipo);

    for (const tipo of tiposOrdenados) {
        if (grupos[tipo]) {
            gruposOrdenados[tipo] = grupos[tipo];
        }
    }

    return gruposOrdenados;
}

/**
 * Genera markdown para un commit
 */
function generarMarkdownCommit(commit) {
    let linea = '- ';

    if (commit.scope) {
        linea += `**${commit.scope}:** `;
    }

    linea += commit.descripcion;

    if (commit.esBreaking) {
        linea += ' **[BREAKING]**';
    }

    return linea;
}

/**
 * Genera seccion de changelog
 */
function generarSeccionChangelog(version, commits) {
    const fecha = new Date().toISOString().split('T')[0];
    const gruposDeCommits = agruparCommitsPorTipo(commits);

    let seccion = `## [${version}] - ${fecha}\n\n`;

    // Breaking changes primero
    const breakingChanges = commits.filter(c => c.esBreaking);
    if (breakingChanges.length > 0) {
        seccion += '### BREAKING CHANGES\n\n';
        for (const commit of breakingChanges) {
            seccion += generarMarkdownCommit(commit) + '\n';
        }
        seccion += '\n';
    }

    // Resto de grupos
    for (const [tipo, commitsDelTipo] of Object.entries(gruposDeCommits)) {
        const configuracionTipo = CONFIGURACION.tiposDeCommit[tipo];
        if (!configuracionTipo) continue;

        // Filtrar breaking changes que ya se mostraron
        const commitsNoBreaking = commitsDelTipo.filter(c => !c.esBreaking);
        if (commitsNoBreaking.length === 0) continue;

        seccion += `### ${configuracionTipo.titulo}\n\n`;
        for (const commit of commitsNoBreaking) {
            seccion += generarMarkdownCommit(commit) + '\n';
        }
        seccion += '\n';
    }

    return seccion;
}

/**
 * Inserta seccion en CHANGELOG.md existente
 */
function insertarEnChangelog(nuevaSeccion) {
    const archivoPath = CONFIGURACION.archivoChangelog;

    if (!fs.existsSync(archivoPath)) {
        // Crear nuevo changelog
        const contenido = `# Changelog

Todos los cambios notables de este proyecto se documentan en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

---

${nuevaSeccion}`;
        fs.writeFileSync(archivoPath, contenido);
        return;
    }

    // Insertar despues de [Unreleased] o al inicio de versiones
    let contenidoActual = fs.readFileSync(archivoPath, 'utf-8');

    // Buscar donde insertar
    const marcadorUnreleased = '## [Unreleased]';
    const marcadorPrimeraVersion = /## \[\d+\.\d+\.\d+\]/;

    if (contenidoActual.includes(marcadorUnreleased)) {
        // Insertar despues de la seccion Unreleased
        const indiceUnreleased = contenidoActual.indexOf(marcadorUnreleased);
        const siguienteSeccion = contenidoActual.slice(indiceUnreleased).search(marcadorPrimeraVersion);

        if (siguienteSeccion > 0) {
            const posicionInsercion = indiceUnreleased + siguienteSeccion;
            contenidoActual = contenidoActual.slice(0, posicionInsercion) +
                '\n' + nuevaSeccion +
                contenidoActual.slice(posicionInsercion);
        } else {
            // No hay versiones anteriores
            contenidoActual += '\n' + nuevaSeccion;
        }
    } else {
        // Buscar primera version e insertar antes
        const primeraVersionMatch = contenidoActual.match(marcadorPrimeraVersion);
        if (primeraVersionMatch) {
            const indiceVersion = contenidoActual.indexOf(primeraVersionMatch[0]);
            contenidoActual = contenidoActual.slice(0, indiceVersion) +
                nuevaSeccion +
                contenidoActual.slice(indiceVersion);
        } else {
            // No hay versiones, agregar al final
            contenidoActual += '\n---\n\n' + nuevaSeccion;
        }
    }

    fs.writeFileSync(archivoPath, contenidoActual);
}

/**
 * Obtiene version actual del package.json
 */
function obtenerVersionActual() {
    const packagePath = path.join(__dirname, '..', 'package.json');
    if (fs.existsSync(packagePath)) {
        const packageJson = JSON.parse(fs.readFileSync(packagePath, 'utf-8'));
        return packageJson.version;
    }
    return '0.0.0';
}

/**
 * Incrementa version segun tipo de cambios
 */
function incrementarVersion(versionActual, commits) {
    const [major, minor, patch] = versionActual.split('.').map(Number);

    const tieneBreaking = commits.some(c => c.esBreaking);
    const tieneFeature = commits.some(c => c.tipo === 'feat');

    if (tieneBreaking) {
        return `${major + 1}.0.0`;
    } else if (tieneFeature) {
        return `${major}.${minor + 1}.0`;
    } else {
        return `${major}.${minor}.${patch + 1}`;
    }
}

/**
 * Funcion principal
 */
function main() {
    const argumentos = parsearArgumentos();

    if (argumentos.ayuda) {
        mostrarAyuda();
        process.exit(0);
    }

    console.log('Generando changelog...\n');

    // Determinar punto de inicio
    const desde = argumentos.desde || obtenerUltimoTag();
    if (desde) {
        console.log(`Analizando commits desde: ${desde}`);
    } else {
        console.log('No se encontro tag anterior, analizando ultimos 50 commits');
    }

    // Obtener commits
    const commits = obtenerCommits(desde);
    if (commits.length === 0) {
        console.log('No se encontraron commits nuevos siguiendo Conventional Commits');
        process.exit(0);
    }

    console.log(`Encontrados ${commits.length} commits validos\n`);

    // Determinar version
    const versionActual = obtenerVersionActual();
    const nuevaVersion = argumentos.version || incrementarVersion(versionActual, commits);
    console.log(`Version actual: ${versionActual}`);
    console.log(`Nueva version: ${nuevaVersion}\n`);

    // Generar seccion
    const seccion = generarSeccionChangelog(nuevaVersion, commits);

    console.log('--- Changelog generado ---\n');
    console.log(seccion);

    if (argumentos.soloMostrar) {
        console.log('--- Modo dry-run: no se escribio el archivo ---');
    } else {
        insertarEnChangelog(seccion);
        console.log(`Changelog actualizado: ${CONFIGURACION.archivoChangelog}`);
    }

    // Resumen
    console.log('\nResumen:');
    const gruposDeCommits = agruparCommitsPorTipo(commits);
    for (const [tipo, commitsDelTipo] of Object.entries(gruposDeCommits)) {
        const configuracionTipo = CONFIGURACION.tiposDeCommit[tipo];
        if (configuracionTipo) {
            console.log(`  ${configuracionTipo.titulo}: ${commitsDelTipo.length}`);
        }
    }
}

// Ejecutar
main();
