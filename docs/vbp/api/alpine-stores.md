# Alpine Stores de Visual Builder Pro

Documentacion de los stores de Alpine.js disponibles en VBP.

## Acceso a Stores

```javascript
// Acceder a un store
const store = Alpine.store('vbp');

// Ejemplo con propiedades reactivas
Alpine.effect(() => {
    console.log('Zoom cambiado:', Alpine.store('vbp').zoom);
});
```

---

## Store: vbp (Principal)

El store principal que gestiona el estado del editor.

### Propiedades del Documento

```javascript
{
    postId: 0,              // ID del post/pagina
    postTitle: '',          // Titulo
    isDirty: false,         // Cambios sin guardar
    elements: [],           // Array de elementos
    settings: {             // Configuracion de pagina
        pageWidth: '1200px',
        backgroundColor: '#ffffff',
        customCss: ''
    }
}
```

### Estado de UI

```javascript
{
    zoom: 100,                    // Nivel de zoom (10-200)
    devicePreview: 'desktop',     // 'desktop', 'tablet', 'mobile'
    panels: {
        blocks: true,             // Panel de bloques visible
        inspector: true,          // Inspector visible
        layers: false             // Panel de capas visible
    },
    isLoading: false,
    isSaving: false
}
```

### Seleccion

```javascript
{
    selection: {
        elementIds: [],           // IDs de elementos seleccionados
        multiSelect: false        // Seleccion multiple activa
    }
}
```

### Historial

```javascript
{
    history: {
        past: [],                 // Estados anteriores (para undo)
        future: []                // Estados futuros (para redo)
    }
}
```

### Metodos Principales

```javascript
// Documento
store.loadDocument(postId)        // Cargar documento
store.saveDocument()              // Guardar
store.setDirty(boolean)           // Marcar como modificado

// Elementos
store.addElement(element, parentId, index)
store.updateElement(id, changes)
store.removeElement(id)
store.duplicateElement(id)
store.moveElement(id, newParentId, newIndex)

// Seleccion
store.select(elementId, addToSelection)
store.selectMultiple(elementIds)
store.deselectAll()
store.getSelectedElements()

// Zoom
store.setZoom(level)
store.zoomIn()
store.zoomOut()
store.zoomToFit()
store.zoomToSelection()

// Paneles
store.togglePanel(panelName)
store.showPanel(panelName)
store.hidePanel(panelName)

// Historial
store.undo()
store.redo()
store.pushHistory(snapshot)

// Utilidades
store.findElement(id)
store.getElementById(id)
store.getElementPath(id)
```

---

## Store: vbpRealtime

Gestion de colaboracion en tiempo real.

### Propiedades

```javascript
{
    enabled: false,           // Colaboracion habilitada
    connected: false,         // Estado de conexion
    connecting: false,        // Conectando...
    users: [],                // Usuarios conectados
    locks: {},                // Elementos bloqueados
    pendingChanges: [],       // Cambios pendientes
    ownColor: '#3b82f6',      // Color propio
    lastError: null           // Ultimo error
}
```

### Getters

```javascript
store.activeUsers          // Usuarios sin incluirte
store.hasCollaborators     // true si hay otros
store.lockedElements       // IDs bloqueados por otros
```

### Metodos

```javascript
store.connect(postId)              // Conectar a sesion
store.disconnect()                 // Desconectar
store.broadcastCursor(position)    // Enviar posicion cursor
store.broadcastSelection(ids)      // Enviar seleccion
store.requestLock(elementId)       // Solicitar bloqueo
store.releaseLock(elementId)       // Liberar bloqueo
store.syncChanges(changes)         // Sincronizar cambios
store.isElementLocked(id)          // Verificar bloqueo
store.getLockOwner(id)             // Obtener quien bloquea
store.canEditElement(id)           // Puedo editar?
```

---

## Store: vbpAILayout

Asistente de diseno con IA.

### Propiedades

```javascript
{
    isOpen: false,            // Panel abierto
    activeTab: 'generate',    // Tab activo
    loading: false,           // Cargando
    error: null,              // Error actual
    prompt: '',               // Input del usuario

    // Resultados
    generatedBlocks: [],      // Bloques generados
    spacingSuggestions: [],   // Sugerencias de spacing
    colorPalette: [],         // Paleta de colores
    variants: [],             // Variantes generadas
    analysisResult: null,     // Resultado de analisis

    // Configuracion
    gridBase: 8,              // Base del grid
    colorScheme: 'complementary',

    // Estado de IA
    aiAvailable: false,
    fallbackEnabled: true
}
```

### Metodos

```javascript
store.open(tab)                    // Abrir panel
store.close()                      // Cerrar
store.toggle()                     // Toggle
store.setTab(tabName)              // Cambiar tab

store.generateLayout(prompt)       // Generar layout
store.applyAutoSpacing(elements)   // Auto-espaciado
store.suggestColors(baseColor, scheme)  // Colores
store.generateVariants(elementId)  // Variantes
store.analyzeDesign()              // Analizar
```

---

## Store: vbp.symbols

Extension del store principal para simbolos.

### Propiedades

```javascript
{
    symbols: [],              // Array de simbolos
    symbolInstances: {},      // Mapa element_id -> symbol_id
    symbolsLoading: false     // Cargando simbolos
}
```

### Metodos

```javascript
store.createSymbolFromSelection(name, exposedProps)
store.insertSymbolInstance(symbolId, parentId, position)
store.detachInstance(elementId)
store.goToMaster(symbolId)
store.updateSymbolMaster(symbolId, changes)
store.createVariant(symbolId, variantName, props)
store.applyVariant(instanceId, variantName)
store.getSymbol(symbolId)
store.getSymbolInstances(symbolId)
store.syncInstances(symbolId)
```

---

## Store: vbp.responsive

Extension para variantes responsive.

### Propiedades

```javascript
{
    currentBreakpoint: 'desktop',
    breakpoints: {...},
    cssOrderMode: 'desktop-first'
}
```

### Metodos

```javascript
store.setBreakpoint(breakpoint)
store.getBreakpoint()
store.getOverrides(elementId)
store.setOverride(elementId, breakpoint, overrides)
store.removeOverride(elementId, breakpoint, property)
store.copyOverrides(elementId, from, to)
store.getComputedValue(elementId, property, breakpoint)
```

---

## Store: vbp.prototype

Extension para modo prototipado.

### Propiedades

```javascript
{
    enabled: false,           // Modo activo
    interactions: [],         // Interacciones definidas
    previewMode: false,       // En preview
    currentFrame: null        // Frame actual en preview
}
```

### Metodos

```javascript
store.enablePrototypeMode()
store.disablePrototypeMode()
store.createInteraction(config)
store.updateInteraction(id, config)
store.deleteInteraction(id)
store.startPreview()
store.stopPreview()
store.executeAction(action)
```

---

## Store: vbp.branching

Extension para sistema de ramas.

### Propiedades

```javascript
{
    branches: [],             // Lista de ramas
    currentBranch: null,      // Rama actual
    isLoadingBranches: false,
    isMerging: false,
    mergeConflicts: []
}
```

### Metodos

```javascript
store.loadBranches()
store.createBranch(name, description)
store.checkoutBranch(branchId)
store.mergeBranch(source, target)
store.deleteBranch(branchId)
store.getDiff(branchA, branchB)
store.resolveConflict(elementId, resolution)
```

---

## Objetos Globales

### VBPSymbols

```javascript
window.VBPSymbols.init()
window.VBPSymbols.cargarSimbolos()
window.VBPSymbols.crearSimboloDesdeSeleccion(nombre, props)
window.VBPSymbols.insertarInstancia(symbolId, parentId)
window.VBPSymbols.actualizarMaster(symbolId, cambios)
```

### VBPResponsiveVariants

```javascript
window.VBPResponsiveVariants.init()
window.VBPResponsiveVariants.setBreakpoint(bp)
window.VBPResponsiveVariants.getOverrides(elementId)
window.VBPResponsiveVariants.applyOverride(elementId, bp, prop, value)
```

### VBPConstraints

```javascript
window.VBPConstraints.init()
window.VBPConstraints.getConstraints(elementId)
window.VBPConstraints.setConstraints(elementId, constraints)
window.VBPConstraints.applyPreset(elementId, presetName)
```

### VBPAnimationBuilder

```javascript
window.VBPAnimationBuilder.init()
window.VBPAnimationBuilder.createAnimation(elementId, config)
window.VBPAnimationBuilder.applyPreset(elementId, presetName)
window.VBPAnimationBuilder.preview(elementId)
window.VBPAnimationBuilder.exportCSS(animationId)
```

### VBPGlobalStyles

```javascript
window.VBPGlobalStyles.getAll()
window.VBPGlobalStyles.get(styleId)
window.VBPGlobalStyles.create(styleData)
window.VBPGlobalStyles.update(styleId, styleData)
window.VBPGlobalStyles.delete(styleId)
window.VBPGlobalStyles.applyToElement(elementId, styleId)
window.VBPGlobalStyles.refreshCSS()
```

### VBPAssetManager

```javascript
window.VBPAssetManager.open(options)
window.VBPAssetManager.close()
window.VBPAssetManager.loadAssets()
window.VBPAssetManager.uploadFile(file)
window.VBPAssetManager.toggleFavorite(assetId)
window.VBPAssetManager.searchUnsplash()
```

### VBPSmartGuides

```javascript
window.VBPSmartGuides.enable()
window.VBPSmartGuides.disable()
window.VBPSmartGuides.toggle()
window.VBPSmartGuides.clearGuides()
```

### VBPSpacingIndicators

```javascript
window.VBPSpacingIndicators.init()
window.VBPSpacingIndicators.showSpacing(fromId, toId)
window.VBPSpacingIndicators.clearIndicators()
```

### VBPBulkEdit

```javascript
window.VBPBulkEdit.init()
window.VBPBulkEdit.isActive
window.VBPBulkEdit.selectedIds
window.VBPBulkEdit.getCommonValues()
window.VBPBulkEdit.applyToAll(property, value)
```

### VBPAppBranching

```javascript
window.VBPAppBranching.init()
window.VBPAppBranching.loadBranches()
window.VBPAppBranching.createBranch()
window.VBPAppBranching.checkoutBranch(branchId)
window.VBPAppBranching.startMerge()
```

---

## Eventos Globales

### Documento

```javascript
document.addEventListener('vbp:document:loaded', handler)
document.addEventListener('vbp:document:saved', handler)
document.addEventListener('vbp:document:dirty', handler)
```

### Elementos

```javascript
document.addEventListener('vbp:element:added', handler)
document.addEventListener('vbp:element:updated', handler)
document.addEventListener('vbp:element:removed', handler)
document.addEventListener('vbp:element:moved', handler)
```

### Seleccion

```javascript
document.addEventListener('vbp:selection:changed', handler)
```

### Zoom

```javascript
document.addEventListener('vbp:zoom:changed', handler)
```

### Breakpoint

```javascript
document.addEventListener('vbp:breakpoint:changed', handler)
```

### Simbolos

```javascript
document.addEventListener('vbp:symbols:loaded', handler)
document.addEventListener('vbp:symbol:created', handler)
document.addEventListener('vbp:symbol:updated', handler)
```

### Colaboracion

```javascript
document.addEventListener('vbp:realtime:connected', handler)
document.addEventListener('vbp:realtime:user:joined', handler)
document.addEventListener('vbp:realtime:user:left', handler)
```

---

## Store: vbp3d

Store para escenas y objetos 3D/WebGL.

### Propiedades

```javascript
{
    scenes: {},               // Escenas 3D activas
    selectedObject: null,     // Objeto 3D seleccionado
    transformMode: 'translate', // 'translate', 'rotate', 'scale'
    showGrid: true,           // Mostrar grid 3D
    showAxes: true,           // Mostrar ejes
    showBoundingBox: true,    // Mostrar caja delimitadora
    isLoading: false          // Cargando modelo
}
```

### Metodos

```javascript
store.createScene(elementId, config)
store.deleteScene(sceneId)
store.addObject(sceneId, objectConfig)
store.updateObject(objectId, changes)
store.removeObject(objectId)
store.selectObject(objectId)
store.setTransformMode(mode)
store.setCameraPosition(sceneId, position)
store.setLighting(sceneId, preset)
store.loadModel(sceneId, url, config)
store.takeScreenshot(sceneId)
store.exportGLTF(sceneId)
```

---

## Store: vbpAnimations

Store para animaciones avanzadas.

### Propiedades

```javascript
{
    animations: {},           // Animaciones por elemento
    scrollAnimations: {},     // Animaciones de scroll
    activeTimelines: [],      // Timelines activos
    previewActive: false,     // Preview activo
    markers: false            // Markers de debug visibles
}
```

### Metodos

```javascript
// Animaciones basicas
store.create(elementId, config)
store.update(animationId, changes)
store.delete(animationId)
store.play(animationId)
store.pause(animationId)
store.stop(animationId)

// Scroll animations
store.createScrollAnimation(elementId, config)
store.updateScrollAnimation(animationId, changes)
store.deleteScrollAnimation(animationId)

// Advanced (stagger, spring, motion path)
store.createStagger(parentId, config)
store.createSpring(elementId, config)
store.createMotionPath(elementId, config)

// Timeline
store.createTimeline(config)
store.addToTimeline(timelineId, elementId, animation, position)

// Preview
store.startPreview()
store.stopPreview()
store.toggleMarkers()
```

---

## Store: vbpVariables

Store para variables y logica.

### Propiedades

```javascript
{
    variables: {},            // Variables por nombre
    collections: {},          // Colecciones de variables
    currentModes: {},         // Modos activos por coleccion
    bindings: {},             // Bindings element -> variable
    watchers: {}              // Observadores de cambios
}
```

### Metodos

```javascript
// Variables
store.get(variablePath)
store.set(variablePath, value)
store.create(config)
store.delete(name)
store.list()
store.listByType(type)

// Colecciones
store.createCollection(config)
store.getCollection(name)
store.setMode(collection, mode)
store.getMode(collection)

// Expresiones
store.evaluate(expression)
store.registerFunction(name, fn)

// Bindings
store.bind(elementId, property, variablePath)
store.unbind(elementId, property)
store.getBindings(elementId)

// Observadores
store.watch(variablePath, callback)
store.unwatch(variablePath, callback)
```

---

## Store: vbpLogic

Store para logica condicional.

### Propiedades

```javascript
{
    conditions: {},           // Condiciones por elemento
    states: {},               // Estados por elemento
    currentStates: {}         // Estado actual de cada elemento
}
```

### Metodos

```javascript
store.createCondition(elementId, config)
store.updateCondition(conditionId, changes)
store.deleteCondition(conditionId)
store.evaluateCondition(conditionId)

store.createState(elementId, config)
store.updateState(stateId, changes)
store.deleteState(stateId)
store.setState(elementId, stateName)
store.getState(elementId)
```

---

## Store: vbpOffline

Store para modo offline y sincronizacion.

### Propiedades

```javascript
{
    isOnline: true,           // Estado de conexion
    pendingChanges: [],       // Cambios pendientes
    cachedDocuments: [],      // Documentos en cache
    lastSync: null,           // Ultima sincronizacion
    syncInProgress: false,    // Sincronizando
    conflicts: []             // Conflictos de sincronizacion
}
```

### Metodos

```javascript
store.sync()
store.getPendingCount()
store.clearPendingChanges()
store.cacheDocument(postId)
store.getCachedDocuments()
store.removeCachedDocument(postId)
store.getStorageUsage()
store.resolveConflict(postId, resolution)
```

---

## Store: vbpPerformance

Store para monitor de rendimiento.

### Propiedades

```javascript
{
    enabled: false,           // Monitor activo
    metrics: {
        fps: 0,
        memory: 0,
        domNodes: 0,
        renderTime: 0
    },
    history: [],              // Historial de metricas
    issues: []                // Problemas detectados
}
```

### Metodos

```javascript
store.start()
store.stop()
store.getMetrics()
store.getHistory(minutes)
store.clearHistory()
store.mark(name)
store.measure(name, startMark, endMark)
store.getMeasures()
store.generateReport()
```

---

## Store: vbpTokens

Store para design tokens.

### Propiedades

```javascript
{
    tokens: {},               // Tokens por nombre
    types: ['color', 'spacing', 'typography', 'border', 'shadow'],
    modes: ['light', 'dark'],
    currentMode: 'light'
}
```

### Metodos

```javascript
store.get(tokenName)
store.resolve(tokenName)      // Resuelve referencias
store.set(tokenName, value)
store.create(config)
store.delete(tokenName)
store.list()
store.listByType(type)
store.setMode(mode)
store.getMode()
store.toCSS()
store.importFromFigma(data)
store.exportForFigma()
```

---

## Nuevos Objetos Globales

### VBPScrollAnimations

```javascript
window.VBPScrollAnimations.create(elementId, config)
window.VBPScrollAnimations.createParallax(elementId, config)
window.VBPScrollAnimations.createPin(elementId, config)
window.VBPScrollAnimations.createProgress(elementId, config)
window.VBPScrollAnimations.getAnimations(elementId)
window.VBPScrollAnimations.refresh()
window.VBPScrollAnimations.enableMarkers(boolean)
```

### VBPAdvancedAnimations

```javascript
window.VBPAdvancedAnimations.createStagger(parentId, config)
window.VBPAdvancedAnimations.createMotionPath(elementId, config)
window.VBPAdvancedAnimations.createSpring(elementId, config)
window.VBPAdvancedAnimations.createGesture(elementId, config)
window.VBPAdvancedAnimations.createTimeline(config)
```

### VBP3DScene

```javascript
window.VBP3DScene.getScene(elementId)
window.VBP3DScene.addObject(sceneId, config)
window.VBP3DScene.updateObject(objectId, changes)
window.VBP3DScene.removeObject(objectId)
window.VBP3DScene.loadModel(sceneId, url, config)
window.VBP3DScene.setCameraPosition(sceneId, position)
window.VBP3DScene.setLighting(sceneId, preset)
window.VBP3DScene.takeScreenshot(sceneId)
```

### VBPVariables

```javascript
window.VBPVariables.get(path)
window.VBPVariables.set(path, value)
window.VBPVariables.create(config)
window.VBPVariables.delete(name)
window.VBPVariables.watch(path, callback)
window.VBPVariables.unwatch(path, callback)
window.VBPVariables.evaluate(expression)
window.VBPVariables.setMode(collection, mode)
```

### VBPLogic

```javascript
window.VBPLogic.createCondition(elementId, config)
window.VBPLogic.createState(elementId, config)
window.VBPLogic.bind(elementId, property, variable)
window.VBPLogic.unbind(elementId, property)
```

### VBPDesignTokens

```javascript
window.VBPDesignTokens.get(name)
window.VBPDesignTokens.resolve(name)
window.VBPDesignTokens.set(name, value)
window.VBPDesignTokens.create(config)
window.VBPDesignTokens.delete(name)
window.VBPDesignTokens.toCSS()
window.VBPDesignTokens.importFromFigma(data)
window.VBPDesignTokens.exportForFigma()
```

### VBPOfflineMode

```javascript
window.VBPOfflineMode.isAvailable()
window.VBPOfflineMode.isOffline()
window.VBPOfflineMode.sync()
window.VBPOfflineMode.getPendingChanges()
window.VBPOfflineMode.cacheDocument(postId)
window.VBPOfflineMode.getCachedDocuments()
window.VBPOfflineMode.getStorageUsage()
```

### VBPPerformanceMonitor

```javascript
window.VBPPerformanceMonitor.start()
window.VBPPerformanceMonitor.stop()
window.VBPPerformanceMonitor.getFPS()
window.VBPPerformanceMonitor.getMemory()
window.VBPPerformanceMonitor.getMetrics()
window.VBPPerformanceMonitor.generateReport()
```

### VBPAccessibility

```javascript
window.VBPAccessibility.audit()
window.VBPAccessibility.auditElement(elementId)
window.VBPAccessibility.checkContrast(fg, bg)
window.VBPAccessibility.suggestColor(fg, bg, target)
window.VBPAccessibility.checkHeadingStructure()
window.VBPAccessibility.announce(message)
```

### VBPEditorThemes

```javascript
window.VBPEditorThemes.setTheme(themeId)
window.VBPEditorThemes.getTheme()
window.VBPEditorThemes.toggleDarkMode()
window.VBPEditorThemes.registerTheme(config)
window.VBPEditorThemes.export(themeId)
window.VBPEditorThemes.import(json)
```

### VBPPlugins

```javascript
window.VBPPlugins.register(config)
window.VBPPlugins.get(pluginId)
window.VBPPlugins.list()
window.VBPPlugins.activate(pluginId)
window.VBPPlugins.deactivate(pluginId)
window.VBPPlugins.getSettings(pluginId)
window.VBPPlugins.setSettings(pluginId, settings)
```

---

## Nuevos Eventos Globales

### Animaciones

```javascript
document.addEventListener('vbp:animation:start', handler)
document.addEventListener('vbp:animation:complete', handler)
document.addEventListener('vbp:scroll:animation:start', handler)
document.addEventListener('vbp:scroll:progress', handler)
```

### 3D

```javascript
document.addEventListener('vbp:3d:model:loaded', handler)
document.addEventListener('vbp:3d:object:selected', handler)
document.addEventListener('vbp:3d:transform:changed', handler)
```

### Variables

```javascript
document.addEventListener('vbp:variable:changed', handler)
document.addEventListener('vbp:mode:changed', handler)
document.addEventListener('vbp:condition:evaluated', handler)
```

### Tokens

```javascript
document.addEventListener('vbp:token:changed', handler)
document.addEventListener('vbp:tokens:mode:changed', handler)
document.addEventListener('vbp:tokens:imported', handler)
```

### Offline

```javascript
document.addEventListener('vbp:offline:status:changed', handler)
document.addEventListener('vbp:sync:started', handler)
document.addEventListener('vbp:sync:completed', handler)
document.addEventListener('vbp:sync:conflict', handler)
```

### Performance

```javascript
document.addEventListener('vbp:perf:fps:low', handler)
document.addEventListener('vbp:perf:memory:high', handler)
document.addEventListener('vbp:perf:issue', handler)
```

### Tema

```javascript
document.addEventListener('vbp:theme:changed', handler)
```

### Accesibilidad

```javascript
document.addEventListener('vbp:a11y:audit:complete', handler)
document.addEventListener('vbp:a11y:issue:detected', handler)
```
