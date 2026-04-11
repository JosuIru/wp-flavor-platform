# Guia de Contribucion - Flavor Platform

Gracias por tu interes en contribuir a Flavor Platform. Esta guia te ayudara a entender nuestro proceso de desarrollo y como hacer contribuciones efectivas.

---

## Tabla de Contenidos

1. [Codigo de Conducta](#codigo-de-conducta)
2. [Como Empezar](#como-empezar)
3. [Conventional Commits](#conventional-commits)
4. [Flujo de Trabajo Git](#flujo-de-trabajo-git)
5. [Estandares de Codigo](#estandares-de-codigo)
6. [Proceso de Pull Request](#proceso-de-pull-request)
7. [Reporte de Bugs](#reporte-de-bugs)
8. [Solicitud de Features](#solicitud-de-features)

---

## Codigo de Conducta

Este proyecto adhiere a un codigo de conducta. Al participar, se espera que mantengas un ambiente respetuoso y constructivo.

- Se respetuoso con otros contribuidores
- Acepta criticas constructivas
- Enfocate en lo mejor para la comunidad
- Muestra empatia hacia otros

---

## Como Empezar

### Requisitos Previos

- PHP 7.4+
- Node.js 18+
- WordPress 6.0+
- Composer
- Git

### Configuracion del Entorno

```bash
# 1. Clonar repositorio
git clone https://github.com/flavor/flavor-platform.git
cd flavor-platform

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias Node
npm install

# 4. Configurar husky para pre-commit hooks
npm run prepare

# 5. Ejecutar tests para verificar
npm test
```

### Estructura del Proyecto

```
flavor-platform/
|-- addons/              # Addons independientes
|-- admin/               # Panel de administracion
|-- assets/              # CSS, JS, imagenes
|   |-- vbp/             # Assets de Visual Builder Pro
|-- docs/                # Documentacion
|-- includes/            # Clases PHP principales
|   |-- modules/         # Modulos del plugin
|   |-- visual-builder-pro/  # VBP core
|-- languages/           # Traducciones
|-- mobile-apps/         # Apps Flutter
|-- scripts/             # Scripts de build
|-- tests/               # Tests PHPUnit y Jest
|-- tools/               # Herramientas CLI
```

---

## Conventional Commits

Usamos [Conventional Commits](https://www.conventionalcommits.org/) para mantener un historial de commits limpio y generar changelogs automaticamente.

### Formato

```
<tipo>[scope opcional]: <descripcion>

[cuerpo opcional]

[footer(s) opcional(es)]
```

### Tipos de Commit

| Tipo | Descripcion | Ejemplo |
|------|-------------|---------|
| `feat` | Nueva funcionalidad | `feat(vbp): add symbol variants support` |
| `fix` | Correccion de bug | `fix(canvas): prevent drag outside viewport` |
| `docs` | Solo documentacion | `docs: update API reference for symbols` |
| `style` | Formato, sin cambios de codigo | `style: fix indentation in vbp-store.js` |
| `refactor` | Refactorizacion sin cambiar funcionalidad | `refactor(symbols): extract validation logic` |
| `perf` | Mejora de rendimiento | `perf(canvas): optimize render loop` |
| `test` | Agregar o modificar tests | `test(api): add symbol creation tests` |
| `build` | Cambios en build system | `build: update webpack config` |
| `ci` | Cambios en CI/CD | `ci: add PHP 8.2 to test matrix` |
| `chore` | Mantenimiento general | `chore: update dependencies` |
| `revert` | Revertir commit anterior | `revert: feat(vbp): add symbol variants` |

### Scopes Comunes

| Scope | Descripcion |
|-------|-------------|
| `vbp` | Visual Builder Pro |
| `canvas` | Canvas del editor |
| `symbols` | Sistema de simbolos |
| `api` | REST API |
| `admin` | Panel de administracion |
| `modules` | Sistema de modulos |
| `mobile` | Apps Flutter |
| `i18n` | Internacionalizacion |
| `a11y` | Accesibilidad |
| `deps` | Dependencias |

### Ejemplos de Buenos Commits

```bash
# Nueva funcionalidad
feat(vbp): add real-time collaboration with WebSocket

Implement WebSocket server for real-time synchronization between editors.
Falls back to WordPress Heartbeat if WebSocket unavailable.

Closes #123

# Correccion de bug
fix(canvas): prevent elements from being dragged outside viewport

Elements could be accidentally dragged off-screen, making them
inaccessible. Now constrains drag within canvas boundaries.

Fixes #456

# Breaking change
feat(api)!: change symbols endpoint from /v1/symbol to /v1/symbols

BREAKING CHANGE: Symbol endpoints now use plural naming.
Update API calls accordingly.

# Refactorizacion
refactor(symbols): extract symbol validation to separate class

Move validation logic from Symbol class to new SymbolValidator.
No functional changes, improves testability.

# Documentacion
docs(vbp): add keyboard shortcuts reference

Add comprehensive keyboard shortcuts documentation including
all new shortcuts for symbols and collaboration.

# Mantenimiento
chore(deps): update alpine.js to 3.13.0
```

### Commits que NO Usar

```bash
# Muy vago
fixed stuff

# Sin tipo
update code

# Tipo incorrecto (feat para un fix)
feat: fix button alignment

# Descripcion muy larga en primera linea
feat(vbp): add a new feature that allows users to create symbols from selection and then insert instances that are automatically synchronized when the master is updated

# Uso de primera persona
feat: I added symbol support
```

### Breaking Changes

Para cambios que rompen compatibilidad:

```bash
# Opcion 1: ! despues del tipo
feat(api)!: rename endpoints for consistency

# Opcion 2: Footer BREAKING CHANGE
feat(api): rename endpoints for consistency

BREAKING CHANGE: All API endpoints now use kebab-case.
/getSymbols -> /get-symbols
```

---

## Flujo de Trabajo Git

### Branches

| Branch | Proposito |
|--------|-----------|
| `main` / `master` | Codigo estable, produccion |
| `develop` | Integracion de features |
| `feature/*` | Nuevas funcionalidades |
| `fix/*` | Correcciones de bugs |
| `hotfix/*` | Correcciones urgentes para produccion |
| `release/*` | Preparacion de releases |

### Crear una Feature

```bash
# 1. Actualizar main
git checkout main
git pull origin main

# 2. Crear branch de feature
git checkout -b feature/add-symbol-variants

# 3. Desarrollar y commitear
git add .
git commit -m "feat(symbols): add variant support"

# 4. Push y crear PR
git push -u origin feature/add-symbol-variants
```

### Convenciones de Nombres de Branch

```
feature/short-description
fix/issue-number-description
hotfix/critical-bug-name
release/v3.6.0
```

---

## Estandares de Codigo

### PHP

Seguimos [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/):

```bash
# Verificar
composer lint

# Corregir automaticamente
composer lint:fix
```

**Reglas principales:**
- Indentacion con tabs
- Llaves en nueva linea para funciones y clases
- Espacios alrededor de operadores
- Prefijo `flavor_` para funciones globales

### JavaScript

Seguimos ESLint con config de WordPress:

```bash
# Verificar
npm run lint:js

# Corregir
npm run lint:js:fix
```

**Reglas principales:**
- Indentacion con tabs
- Comillas simples
- Sin punto y coma al final (excepto cuando necesario)
- Camel case para variables y funciones

### CSS

Seguimos Stylelint con config standard:

```bash
# Verificar
npm run lint:css

# Corregir
npm run lint:css:fix
```

**Reglas principales:**
- BEM para nombres de clases
- Variables CSS para colores y espaciado
- Mobile-first para media queries

---

## Proceso de Pull Request

### Antes de Crear un PR

1. **Tests pasan:** `npm test`
2. **Lint pasa:** `npm run lint`
3. **Commits siguen Conventional Commits**
4. **Branch actualizado con main**
5. **Documentacion actualizada si aplica**

### Plantilla de PR

```markdown
## Descripcion

[Breve descripcion del cambio]

## Tipo de Cambio

- [ ] Bug fix (cambio que corrige un issue)
- [ ] Nueva funcionalidad (cambio que agrega funcionalidad)
- [ ] Breaking change (cambio que rompe compatibilidad)
- [ ] Documentacion

## Como se ha Probado

[Describe como probaste los cambios]

## Checklist

- [ ] Mi codigo sigue los estandares del proyecto
- [ ] He realizado una auto-revision de mi codigo
- [ ] He comentado mi codigo donde es dificil de entender
- [ ] He actualizado la documentacion correspondiente
- [ ] Mis cambios no generan nuevos warnings
- [ ] He agregado tests que prueban que mi fix/feature funciona
- [ ] Tests nuevos y existentes pasan localmente
- [ ] Cambios dependientes han sido mergeados y publicados

## Screenshots (si aplica)

[Capturas de pantalla o GIFs]

## Issues Relacionados

Closes #[numero]
```

### Proceso de Review

1. Al menos 1 aprobacion requerida
2. CI debe pasar (tests, lint)
3. Conflictos resueltos
4. Squash merge preferido

---

## Reporte de Bugs

### Antes de Reportar

1. Busca si ya existe un issue similar
2. Reproduce el bug en la ultima version
3. Identifica pasos minimos para reproducir

### Plantilla de Bug Report

```markdown
## Descripcion del Bug

[Descripcion clara y concisa]

## Pasos para Reproducir

1. Ir a '...'
2. Click en '...'
3. Scroll hasta '...'
4. Ver error

## Comportamiento Esperado

[Que esperabas que pasara]

## Comportamiento Actual

[Que paso realmente]

## Screenshots

[Si aplica]

## Entorno

- WordPress: [version]
- PHP: [version]
- Plugin: [version]
- Navegador: [nombre y version]
- Sistema Operativo: [nombre y version]

## Logs de Error

```
[Logs de consola o PHP]
```

## Contexto Adicional

[Cualquier otra informacion relevante]
```

---

## Solicitud de Features

### Plantilla de Feature Request

```markdown
## Descripcion de la Feature

[Descripcion clara de la funcionalidad solicitada]

## Problema que Resuelve

[Que problema o necesidad atiende esta feature]

## Solucion Propuesta

[Tu idea de como deberia funcionar]

## Alternativas Consideradas

[Otras soluciones que consideraste]

## Contexto Adicional

[Mockups, ejemplos de otros productos, etc.]
```

---

## Scripts Utiles

```bash
# Desarrollo
npm run watch          # Watch mode para assets
npm run build:dev      # Build de desarrollo
npm run build:prod     # Build de produccion

# Testing
npm test               # Todos los tests
npm run test:php       # Solo tests PHP
npm run test:js        # Solo tests JS
npm run test:coverage  # Con cobertura

# Linting
npm run lint           # Todo
npm run lint:fix       # Todo con autofix

# Release
npm run release:patch  # 3.5.0 -> 3.5.1
npm run release:minor  # 3.5.0 -> 3.6.0
npm run release:major  # 3.5.0 -> 4.0.0
```

---

## Recursos

- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [Keep a Changelog](https://keepachangelog.com/)
- [Semantic Versioning](https://semver.org/)

---

## Contacto

- **Issues:** GitHub Issues
- **Discusiones:** GitHub Discussions
- **Email:** contribuir@gailu.net

---

*Gracias por contribuir a Flavor Platform!*
