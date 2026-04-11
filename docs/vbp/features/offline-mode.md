# Modo Offline

Sistema que permite trabajar en el editor VBP sin conexion a internet.

## Descripcion

El Modo Offline de VBP permite continuar editando disenos incluso sin conexion a internet. Usa Service Workers, IndexedDB y sincronizacion inteligente para guardar cambios localmente y sincronizarlos cuando se restablece la conexion.

## Como Funciona

### Arquitectura

```
+------------------+     +------------------+
|    EDITOR VBP    | --> | SERVICE WORKER   |
+------------------+     +------------------+
        |                        |
        v                        v
+------------------+     +------------------+
|   INDEXED DB     |     |   CACHE API      |
|  (Documentos)    |     |   (Assets)       |
+------------------+     +------------------+
        |                        |
        +------------------------+
                   |
                   v
        +------------------+
        |  SYNC MANAGER    |
        |  (Background)    |
        +------------------+
                   |
                   v
        +------------------+
        |    SERVIDOR      |
        +------------------+
```

### Flujo de Trabajo

1. **Online**: Cambios se guardan en servidor y localmente
2. **Offline**: Cambios se guardan solo localmente
3. **Reconexion**: Cambios locales se sincronizan con servidor

## Activacion

El modo offline se activa automaticamente si:

- El navegador soporta Service Workers
- El usuario ha visitado el editor al menos una vez
- Los assets estan cacheados

### Verificar Soporte

```javascript
const offline = window.VBPOfflineMode;

// Verificar si offline esta disponible
if (offline.isAvailable()) {
    console.log('Modo offline disponible');
}

// Verificar estado actual
if (offline.isOffline()) {
    console.log('Actualmente offline');
}
```

## Indicadores Visuales

### Barra de Estado

La barra de estado muestra:

- **Verde**: Conectado, cambios sincronizados
- **Amarillo**: Offline, cambios pendientes
- **Rojo**: Error de sincronizacion

### Icono de Conexion

```
[WiFi] Online - Todo sincronizado
[!] Offline - 3 cambios pendientes
[X] Error - Fallo de sincronizacion
```

## API JavaScript

### VBPOfflineMode

```javascript
const offline = window.VBPOfflineMode;

// Estado
offline.isAvailable();             // Soporte offline disponible
offline.isOffline();               // Actualmente offline
offline.isOnline();                // Actualmente online

// Sync
offline.sync();                    // Forzar sincronizacion
offline.getPendingChanges();       // Cambios pendientes
offline.getPendingCount();         // Numero de cambios pendientes
offline.clearPendingChanges();     // Limpiar pendientes

// Cache
offline.cacheDocument(postId);     // Cachear documento
offline.getCachedDocuments();      // Listar documentos cacheados
offline.removeCachedDocument(postId);  // Eliminar del cache
offline.clearCache();              // Limpiar todo el cache

// Espacio
offline.getStorageUsage();         // Uso de almacenamiento
offline.getStorageQuota();         // Cuota disponible

// Configuracion
offline.setAutoSync(boolean);      // Sincronizar automaticamente
offline.setSyncInterval(ms);       // Intervalo de sincronizacion
```

### VBPIndexedDB

```javascript
const db = window.VBPIndexedDB;

// Documentos
await db.saveDocument(postId, data);    // Guardar documento
await db.loadDocument(postId);           // Cargar documento
await db.deleteDocument(postId);         // Eliminar documento
await db.listDocuments();                // Listar documentos

// Cambios pendientes
await db.addPendingChange(change);       // Agregar cambio
await db.getPendingChanges();            // Obtener pendientes
await db.clearPendingChanges();          // Limpiar pendientes

// Assets
await db.cacheAsset(url, data);          // Cachear asset
await db.getAsset(url);                  // Obtener asset
await db.listAssets();                   // Listar assets
```

## Sincronizacion

### Automatica

Por defecto, VBP sincroniza automaticamente cuando:

- Se restablece la conexion
- Se abre un documento cacheado
- Cada 30 segundos si hay cambios pendientes

### Manual

```javascript
// Forzar sincronizacion
await offline.sync();

// Con callback
offline.sync().then(() => {
    console.log('Sincronizacion completada');
}).catch((error) => {
    console.error('Error de sincronizacion:', error);
});
```

### Conflictos

Cuando hay conflictos entre version local y servidor:

1. Se notifica al usuario
2. Se muestran ambas versiones
3. Usuario decide cual mantener

```javascript
// Escuchar conflictos
document.addEventListener('vbp:sync:conflict', (e) => {
    console.log('Conflicto en:', e.detail.postId);
    console.log('Version local:', e.detail.local);
    console.log('Version servidor:', e.detail.server);

    // Resolver
    offline.resolveConflict(e.detail.postId, 'local');  // o 'server'
});
```

## Eventos

```javascript
// Cambio de estado de conexion
document.addEventListener('vbp:offline:status:changed', (e) => {
    console.log('Ahora:', e.detail.online ? 'online' : 'offline');
});

// Sincronizacion iniciada
document.addEventListener('vbp:sync:started', () => {
    console.log('Sincronizando...');
});

// Sincronizacion completada
document.addEventListener('vbp:sync:completed', (e) => {
    console.log('Sincronizados:', e.detail.count, 'cambios');
});

// Error de sincronizacion
document.addEventListener('vbp:sync:error', (e) => {
    console.error('Error:', e.detail.error);
});

// Cambio pendiente agregado
document.addEventListener('vbp:offline:change:pending', (e) => {
    console.log('Cambio pendiente:', e.detail.change);
});

// Conflicto detectado
document.addEventListener('vbp:sync:conflict', (e) => {
    console.log('Conflicto:', e.detail);
});
```

## Configuracion

### WordPress Config

```php
// wp-config.php

// Activar modo offline
define('VBP_OFFLINE_ENABLED', true);

// Tiempo de expiracion de cache (segundos)
define('VBP_CACHE_TTL', 86400);  // 24 horas

// Tamaño maximo de cache (bytes)
define('VBP_CACHE_MAX_SIZE', 52428800);  // 50MB
```

### JavaScript Config

```javascript
// Configuracion del cliente
offline.configure({
    autoSync: true,
    syncInterval: 30000,        // 30 segundos
    maxPendingChanges: 100,
    cacheAssets: true,
    cacheFonts: true
});
```

## Service Worker

### Registro

El Service Worker se registra automaticamente al cargar el editor:

```javascript
// Verificar registro
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.ready.then((registration) => {
        console.log('SW registrado:', registration.scope);
    });
}
```

### Estrategias de Cache

| Recurso | Estrategia |
|---------|------------|
| Editor JS/CSS | Cache First |
| Fonts | Cache First |
| Imagenes | Stale While Revalidate |
| API Requests | Network First |
| Documentos | Network First, Fallback to Cache |

### Actualizar Service Worker

```javascript
// Forzar actualizacion
offline.updateServiceWorker();

// Escuchar actualizaciones
document.addEventListener('vbp:sw:update:available', () => {
    if (confirm('Nueva version disponible. ¿Actualizar?')) {
        offline.applyUpdate();
    }
});
```

## Gestion de Almacenamiento

### Ver Uso

```javascript
const usage = await offline.getStorageUsage();
console.log('Usado:', usage.used);
console.log('Disponible:', usage.quota);
console.log('Porcentaje:', usage.percentage);
```

### Limpiar Espacio

```javascript
// Limpiar assets antiguos
await offline.cleanupCache({ olderThan: 7 * 24 * 60 * 60 * 1000 }); // 7 dias

// Limpiar todo
await offline.clearCache();
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Shift+O` | Toggle panel offline |
| `Ctrl+Shift+S` | Forzar sincronizacion |

## Limitaciones

### Funciones No Disponibles Offline

- Colaboracion en tiempo real
- Subida de archivos
- AI Layout Assistant
- Busqueda en biblioteca de imagenes (Unsplash)

### Tamaño de Almacenamiento

- Depende del navegador y dispositivo
- Tipicamente 50-100MB disponibles
- Documentos grandes pueden requerir limpieza

### Compatibilidad

| Navegador | Soporte |
|-----------|---------|
| Chrome | Completo |
| Firefox | Completo |
| Safari | Parcial* |
| Edge | Completo |

*Safari tiene limitaciones en almacenamiento persistente

## Solucionar Problemas

### El modo offline no funciona

1. Verifica que Service Workers estan habilitados
2. Comprueba que el sitio usa HTTPS
3. Limpia cache del navegador y recarga
4. Verifica espacio de almacenamiento

### Los cambios no se sincronizan

1. Verifica la conexion a internet
2. Comprueba errores en la consola
3. Intenta sincronizacion manual
4. Verifica que no hay conflictos

### El almacenamiento esta lleno

1. Limpia documentos no necesarios
2. Ejecuta limpieza de cache
3. Reduce assets cacheados
4. Considera aumentar cuota si es posible

### Conflictos frecuentes

1. Sincroniza mas frecuentemente
2. Evita editar el mismo documento en multiples dispositivos offline
3. Considera usar branching para cambios grandes
