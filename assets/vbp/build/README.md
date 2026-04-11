# VBP Build System

Sistema de bundling, minificacion y lazy loading para Visual Builder Pro.

## Resumen

El sistema de build optimiza los assets de VBP mediante:

1. **Bundling**: Agrupa archivos relacionados en bundles logicos
2. **Minificacion**: Comprime JS con Terser y CSS con cssnano
3. **Lazy Loading**: Carga bundles bajo demanda segun uso
4. **Code Splitting**: Separa codigo critico del opcional

## Estadisticas Actuales

### Antes de Bundling (archivos individuales)
- **Archivos JS**: 93
- **Archivos CSS**: 47
- **Total archivos**: 140
- **Tamano JS (sin min)**: 2,380 KB
- **Tamano CSS (sin min)**: 1,041 KB
- **Total sin minificar**: 3,421 KB
- **Total minificado**: 1,099 KB

### Despues de Bundling (estimado)
- **Bundles JS**: 13
- **Bundles CSS**: 12
- **Total bundles**: 25
- **Bundles preload (criticos)**: 6
- **Bundles lazy (bajo demanda)**: 19
- **Reduccion de requests**: ~82%

## Bundles

### JS Bundles

| Bundle | Prioridad | Lazy | Descripcion |
|--------|-----------|------|-------------|
| `vbp-core` | critical | No | Estado, API, utilidades (~80KB) |
| `vbp-editor` | high | No | Canvas, inspector, capas (~120KB) |
| `vbp-keyboard` | high | No | Atajos y command palette (~45KB) |
| `vbp-app` | high | No | Modulos de aplicacion (~90KB) |
| `vbp-symbols` | normal | Si | Sistema de simbolos (~65KB) |
| `vbp-animation` | low | Si | Constructor de animaciones (~35KB) |
| `vbp-responsive` | normal | Si | Variantes responsive (~40KB) |
| `vbp-prototype` | low | Si | Modo prototipado (~55KB) |
| `vbp-collab` | low | Si | Colaboracion realtime (~45KB) |
| `vbp-ai` | low | Si | Asistente IA (~45KB) |
| `vbp-branching` | low | Si | Ramas de diseno (~30KB) |
| `vbp-advanced` | low | Si | Funciones avanzadas (~70KB) |
| `vbp-export` | low | Si | Exportacion de codigo (~35KB) |
| `vbp-admin` | low | Si | Audit log, workflows (~35KB) |

### CSS Bundles

| Bundle | Prioridad | Lazy | Descripcion |
|--------|-----------|------|-------------|
| `vbp-core` | critical | No | Estilos base del editor |
| `vbp-editor` | high | No | Canvas, paneles, toolbar |
| `vbp-symbols` | normal | Si | Panel de simbolos |
| `vbp-animation` | low | Si | Builder de animaciones |
| `vbp-responsive` | normal | Si | Indicadores responsive |
| `vbp-prototype` | low | Si | Modo prototipo |
| `vbp-collab` | low | Si | Colaboracion |
| `vbp-ai` | low | Si | Panel de IA |
| `vbp-branching` | low | Si | Panel de ramas |
| `vbp-advanced` | low | Si | Funciones avanzadas |
| `vbp-admin` | low | Si | Admin features |
| `vbp-frontend` | high | No | Renderizado frontend |

## Uso

### Build de Produccion

```bash
# Via npm
npm run build:vbp

# Via script
./scripts/build-vbp.sh
```

### Build de Desarrollo

```bash
# Via npm (con sourcemaps)
npm run build:vbp:dev

# Via script
./scripts/build-vbp.sh dev
```

### Analizar Tamanos

```bash
npm run build:vbp:analyze

# o
./scripts/build-vbp.sh analyze
```

### Build de un Bundle Especifico

```bash
node assets/vbp/build/vbp-build.js --bundle=vbp-core
```

## Lazy Loading

Los bundles marcados como `lazy: true` se cargan bajo demanda:

### Por Trigger

```javascript
// Cuando se abre el panel de simbolos
VBPLoader.loadByTrigger('symbols-panel-open');

// Cuando se activa modo responsive
VBPLoader.loadByTrigger('responsive-mode-active');
```

### Por Feature Flag

```javascript
// Cargar bundles de colaboracion si esta habilitada
VBPLoader.loadByFeatureFlag('collaboration');
```

### Triggers Disponibles

| Trigger | Bundles |
|---------|---------|
| `symbols-panel-open` | vbp-symbols |
| `animation-panel-open` | vbp-animation |
| `responsive-mode-active` | vbp-responsive |
| `prototype-mode-enabled` | vbp-prototype |
| `collaboration-enabled` | vbp-collab |
| `ai-panel-open` | vbp-ai |
| `branching-panel-open` | vbp-branching |
| `advanced-features-used` | vbp-advanced |
| `export-panel-open` | vbp-export |
| `admin-features-enabled` | vbp-admin |

## Archivos Generados

Despues del build, en `assets/vbp/dist/`:

```
dist/
  vbp-core.bundle.js
  vbp-core.bundle.css
  vbp-editor.bundle.js
  vbp-editor.bundle.css
  vbp-keyboard.bundle.js
  ...
  vbp-loader.min.js     # Cargador lazy
  manifest.json         # Manifiesto de bundles
```

## Integracion PHP

El `class-vbp-asset-loader.php` maneja la carga:

```php
// Obtener instancia
$loader = Flavor_VBP_Asset_Loader::get_instance();

// Establecer feature flags
$loader->establecer_feature_flags($features);

// Encolar assets core (siempre necesarios)
$loader->encolar_assets_core();

// Encolar assets del editor
$loader->encolar_assets_editor();

// Encolar lazy loader
$loader->encolar_lazy_loader();
```

## Configuracion

Editar `build.config.js` para:

- Agregar/quitar archivos de bundles
- Cambiar prioridades
- Configurar triggers de lazy loading
- Ajustar opciones de Terser/cssnano

## Modo de Carga

El sistema detecta automaticamente:

1. **Modo bundled**: Usa bundles compilados de `dist/`
2. **Modo individual**: Carga archivos individuales (SCRIPT_DEBUG)

Para forzar modo individual en desarrollo:

```php
define('SCRIPT_DEBUG', true);
```

## Mantenimiento

### Agregar nuevo archivo a un bundle

1. Editar `build.config.js`
2. Agregar ruta en el array `files` del bundle
3. Ejecutar `npm run build:vbp`

### Crear nuevo bundle

1. Agregar entrada en `bundles` o `cssBundles` en `build.config.js`
2. Definir `files`, `dependencies`, `priority`, `lazy`, `trigger`
3. Actualizar `manifest.json` si es necesario
4. Ejecutar build

### Agregar nuevo trigger

1. Definir trigger en `lazyLoad.triggers` del manifiesto
2. Asociar bundles al trigger
3. En JS, llamar `VBPLoader.loadByTrigger('nuevo-trigger')`

## Rendimiento

### Carga Inicial (bundles preload)
- `vbp-core`: ~80 KB
- `vbp-editor`: ~120 KB
- `vbp-keyboard`: ~45 KB
- `vbp-app`: ~90 KB
- **Total inicial**: ~335 KB (gzipped: ~100 KB)

### Carga Diferida (lazy)
- Simbolos: ~65 KB (cuando se usa)
- IA: ~45 KB (cuando se abre panel)
- Colaboracion: ~45 KB (si hay otros usuarios)
- etc.

### Mejoras de Rendimiento
- Reduccion de requests HTTP: 140 -> 25 (~82%)
- Carga inicial mas rapida
- Recursos bajo demanda
- Mejor cache del navegador
