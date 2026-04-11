# Visual Builder Pro - Modo Offline

## Resumen

El modo offline permite que el editor VBP funcione sin conexion a internet, guardando cambios localmente y sincronizandolos cuando se recupera la conexion.

## Componentes

### 1. Service Worker (`assets/vbp/js/vbp-service-worker.js`)

Intercepta requests y aplica estrategias de cache:

- **Stale-While-Revalidate**: Para assets estaticos (CSS, JS, fonts)
- **Network-First**: Para API calls con fallback a cache
- **Cache-First con Expiracion**: Para imagenes de usuario

**Funcionalidades:**
- Pre-cache de assets criticos durante instalacion
- Limpieza automatica de caches antiguos
- Background sync para cambios pendientes
- Comunicacion bidireccional con el cliente

### 2. IndexedDB (`assets/vbp/js/vbp-indexed-db.js`)

Almacenamiento local persistente con los siguientes stores:

| Store | Descripcion |
|-------|-------------|
| `pages` | Paginas en edicion con datos del builder |
| `pending` | Cola de cambios pendientes de sincronizar |
| `assets` | Imagenes cacheadas como blobs |
| `settings` | Configuraciones del editor |
| `versions` | Historial local de versiones |

**API:**
```javascript
// Guardar pagina localmente
await VBP_DB.savePage(postId, pageData, { title, status });

// Obtener pagina local
const page = await VBP_DB.getPage(postId);

// Agregar cambio pendiente
await VBP_DB.addPendingChange('save', postId, data, priority);

// Obtener cambios pendientes
const pending = await VBP_DB.getPendingChanges();

// Estadisticas de almacenamiento
const stats = await VBP_DB.getStorageStats();
```

### 3. Sincronizacion Offline (`assets/vbp/js/vbp-offline-sync.js`)

Alpine.js store que gestiona:

- Deteccion de estado de conexion
- Cola de sincronizacion con prioridades
- Resolucion de conflictos
- Notificaciones de estado

**Store Alpine:**
```javascript
Alpine.store('vbpOffline', {
    isOnline: true,
    isSyncing: false,
    pendingCount: 0,
    hasConflicts: false,

    // Metodos
    async saveLocal(postId, data, options) {},
    async syncPendingChanges() {},
    async resolveConflict(conflictId, resolution) {},
});
```

### 4. CSS (`assets/vbp/css/offline.css`)

Estilos para:

- Indicador de conexion en toolbar
- Badge de cambios pendientes
- Overlay cuando offline
- Barra de sincronizacion
- Modal de resolucion de conflictos

## Integracion

Los archivos se cargan automaticamente en el editor VBP:

```php
// En class-vbp-editor.php
$archivos_css = array(
    // ...
    'offline' => 'offline.css',
);

$archivos_js = array(
    // ...
    'indexed-db'   => array( 'vbp-indexed-db.js', array() ),
    'offline-sync' => array( 'vbp-offline-sync.js', array( 'vbp-indexed-db', 'vbp-toast' ) ),
);
```

El Service Worker se registra automaticamente:

```php
wp_add_inline_script( 'vbp-offline-sync', $service_worker_registration, 'after' );
```

## Configuracion

Disponible en `VBP_Config.offline`:

```javascript
{
    enabled: true,
    serviceWorkerUrl: '/wp-content/plugins/flavor-platform/assets/vbp/js/vbp-service-worker.js',
    config: {
        connectionCheckInterval: 30000,  // ms
        syncCooldown: 5000,              // ms
        maxSyncAttempts: 5,
        offlineDebounce: 2000,           // ms
    },
    strings: {
        offline: 'Sin conexion',
        online: 'Conectado',
        syncing: 'Sincronizando...',
        // ...
    }
}
```

## Prioridades de Sincronizacion

| Accion | Prioridad |
|--------|-----------|
| Publicar | 10 |
| Guardar | 8 |
| Eliminar | 6 |
| Autosave | 4 |
| Settings | 2 |

## Testing

### 1. Preparacion

```bash
# Abrir el editor VBP
# Ir a: /wp-admin/admin.php?page=vbp-editor&post_id=123
```

### 2. Verificar Service Worker

```javascript
// En DevTools > Console
navigator.serviceWorker.ready.then(reg => console.log('SW ready:', reg));
```

### 3. Simular Offline

```
DevTools > Network > Offline (checkbox)
```

### 4. Realizar cambios

- Editar elementos
- Agregar bloques
- Cambiar estilos

### 5. Verificar guardado local

```javascript
// En Console
await VBP_DB.getStorageStats();
// { pages: 1, pending: 2, assets: 0, versions: 1 }

Alpine.store('vbpOffline').pendingCount;
// 2
```

### 6. Reconectar

```
DevTools > Network > desmarcar Offline
```

### 7. Verificar sincronizacion

- Debe aparecer notificacion de "Conexion restaurada"
- Los cambios pendientes deben sincronizarse
- El badge de pendientes debe llegar a 0

## Manejo de Conflictos

Cuando hay cambios locales y remotos en la misma pagina:

1. Se detecta el conflicto (HTTP 409)
2. Se muestra modal de resolucion
3. Usuario elige: "Mantener local" o "Usar servidor"
4. Se aplica la resolucion

```javascript
// Resolver conflicto programaticamente
Alpine.store('vbpOffline').resolveConflict('conflict_123', 'local');
// o
Alpine.store('vbpOffline').resolveConflict('conflict_123', 'server');
```

## API Publica

```javascript
// Guardar cambios localmente
await VBP_Offline.saveLocal(postId, pageData, { action: 'save' });

// Forzar sincronizacion
await VBP_Offline.sync();

// Verificar estado
VBP_Offline.isOnline();  // true/false

// Obtener pendientes
VBP_Offline.getPendingCount();  // number
```

## Eventos

```javascript
// Cambio de conexion
window.addEventListener('vbp:connection-change', (e) => {
    console.log('Online:', e.detail.isOnline);
});

// Actualizacion de Service Worker disponible
window.addEventListener('vbp:sw-update-available', () => {
    console.log('Nueva version disponible');
});

// Recargar pagina (despues de resolver conflicto con version servidor)
window.addEventListener('vbp:reload-page', (e) => {
    console.log('Recargar post:', e.detail.postId);
});
```

## Degradacion Graceful

Si el navegador no soporta Service Workers o IndexedDB:

- El editor funciona normalmente
- Los cambios se guardan directamente en el servidor
- No hay funcionalidad offline

```javascript
// Verificar soporte
VBP_DB.isSupported;  // true/false
Alpine.store('vbpOffline')?.serviceWorkerReady;  // true/false
```

## Limitaciones

- El Service Worker solo funciona en HTTPS (o localhost)
- El scope esta limitado a `/wp-admin/`
- IndexedDB tiene limite de almacenamiento (~50MB por defecto)
- Las imagenes grandes pueden agotar el espacio disponible

## Troubleshooting

### El Service Worker no se registra

1. Verificar HTTPS o localhost
2. Revisar Console para errores
3. Verificar que el archivo existe: `/assets/vbp/js/vbp-service-worker.js`

### Los cambios no se sincronizan

1. Verificar conexion real (no solo `navigator.onLine`)
2. Revisar `VBP_DB.getPendingChanges()` para ver la cola
3. Verificar que el endpoint API responde

### Conflictos frecuentes

1. Evitar edicion simultanea en multiples pestanas
2. Guardar frecuentemente cuando hay conexion
3. Usar el sistema de colaboracion en tiempo real si esta disponible

## Archivos Relacionados

- `/assets/vbp/js/vbp-service-worker.js` - Service Worker
- `/assets/vbp/js/vbp-indexed-db.js` - Modulo IndexedDB
- `/assets/vbp/js/vbp-offline-sync.js` - Sincronizacion y Alpine store
- `/assets/vbp/css/offline.css` - Estilos de UI offline
- `/assets/vbp/images/icons/offline.svg` - Icono de estado offline
- `/includes/visual-builder-pro/class-vbp-editor.php` - Integracion PHP
