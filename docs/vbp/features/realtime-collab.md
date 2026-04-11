# Realtime Collaboration (Colaboracion en Tiempo Real)

Sistema que permite a multiples usuarios editar el mismo documento simultaneamente, con sincronizacion de cursores, selecciones y cambios.

## Descripcion

La colaboracion en tiempo real utiliza WordPress Heartbeat API como mecanismo principal de sincronizacion, con fallback a long-polling. Muestra cursores de otros usuarios, bloquea elementos siendo editados y sincroniza cambios automaticamente.

## Caracteristicas

- **Cursores en tiempo real**: Ve donde estan trabajando otros usuarios
- **Selecciones visibles**: Ve que elementos tienen seleccionados otros usuarios
- **Bloqueo de elementos**: Evita conflictos al editar el mismo elemento
- **Sincronizacion de cambios**: Los cambios se propagan a todos los usuarios
- **Indicador de usuarios activos**: Lista de colaboradores conectados

## Activar Colaboracion

La colaboracion se activa automaticamente cuando:

1. Abres un documento en el editor
2. Otro usuario tiene el mismo documento abierto

### Verificar Estado

```javascript
const store = Alpine.store('vbpRealtime');

// Estado de conexion
console.log(store.connected);      // true/false
console.log(store.hasCollaborators); // true/false
console.log(store.activeUsers);    // Array de usuarios
```

## Interfaz

### Indicador de Usuarios

En la toolbar aparece:
- Avatares de usuarios conectados
- Numero total de colaboradores
- Click para ver lista detallada

### Cursores

Los cursores de otros usuarios muestran:
- Posicion en tiempo real
- Nombre del usuario
- Color asignado

### Selecciones

Cuando otro usuario selecciona un elemento:
- El elemento se resalta con el color del usuario
- Aparece el nombre del usuario junto al elemento

### Bloqueos

Cuando otro usuario esta editando un elemento:
- Aparece un icono de candado
- No puedes editar ese elemento
- Tooltip muestra quien lo esta editando

## Colores de Usuario

Cada usuario conectado tiene un color unico asignado:

| Color | Hex |
|-------|-----|
| Blue | #3b82f6 |
| Red | #ef4444 |
| Green | #10b981 |
| Amber | #f59e0b |
| Violet | #8b5cf6 |
| Pink | #ec4899 |
| Cyan | #06b6d4 |
| Orange | #f97316 |
| Teal | #14b8a6 |
| Indigo | #6366f1 |

## API JavaScript

### Acceder al Store

```javascript
const realtime = Alpine.store('vbpRealtime');
```

### Propiedades

```javascript
realtime.enabled         // Colaboracion habilitada
realtime.connected       // Estado de conexion
realtime.connecting      // Conectando...
realtime.users           // Array de usuarios conectados
realtime.locks           // Elementos bloqueados
realtime.pendingChanges  // Cambios pendientes de sincronizar
realtime.ownColor        // Tu color asignado
realtime.lastError       // Ultimo error (si hay)
```

### Getters

```javascript
realtime.activeUsers     // Usuarios activos (excluyendote)
realtime.hasCollaborators // true si hay otros usuarios
realtime.lockedElements  // IDs de elementos bloqueados por otros
```

### Metodos

```javascript
// Conectar a sesion
realtime.connect(postId);

// Desconectar
realtime.disconnect();

// Broadcast cursor
realtime.broadcastCursor({ x: 100, y: 200 });

// Broadcast seleccion
realtime.broadcastSelection(['el_123', 'el_456']);

// Solicitar bloqueo
realtime.requestLock('el_789').then(success => {
    if (success) {
        // Puedes editar el elemento
    }
});

// Liberar bloqueo
realtime.releaseLock('el_789');

// Sincronizar cambios
realtime.syncChanges([
    { type: 'update', elementId: 'el_123', data: {...} }
]);

// Verificar si elemento esta bloqueado
if (realtime.isElementLocked('el_789')) {
    console.log('Bloqueado por:', realtime.getLockOwner('el_789'));
}

// Verificar si puedes editar
if (realtime.canEditElement('el_789')) {
    // Proceder con edicion
}
```

## Eventos

```javascript
// Conexion establecida
document.addEventListener('vbp:realtime:connected', (e) => {
    console.log('Conectado a sesion');
});

// Usuario se unio
document.addEventListener('vbp:realtime:user:joined', (e) => {
    console.log('Nuevo usuario:', e.detail.user);
});

// Usuario salio
document.addEventListener('vbp:realtime:user:left', (e) => {
    console.log('Usuario salio:', e.detail.userId);
});

// Cursor actualizado
document.addEventListener('vbp:realtime:cursor:updated', (e) => {
    console.log('Cursor de', e.detail.userId, 'en', e.detail.position);
});

// Elemento bloqueado
document.addEventListener('vbp:realtime:element:locked', (e) => {
    console.log(e.detail.elementId, 'bloqueado por', e.detail.userId);
});

// Cambios recibidos
document.addEventListener('vbp:realtime:changes:received', (e) => {
    console.log('Cambios:', e.detail.changes);
});
```

## API REST

### Estado de Sesion

```http
GET /wp-json/flavor-vbp/v1/realtime/status/{post_id}
```

### Unirse a Sesion

```http
POST /wp-json/flavor-vbp/v1/realtime/join
Content-Type: application/json

{
    "post_id": 123
}
```

### Salir de Sesion

```http
POST /wp-json/flavor-vbp/v1/realtime/leave
Content-Type: application/json

{
    "post_id": 123
}
```

### Solicitar Bloqueo

```http
POST /wp-json/flavor-vbp/v1/realtime/lock
Content-Type: application/json

{
    "post_id": 123,
    "element_id": "el_789"
}
```

### Liberar Bloqueo

```http
POST /wp-json/flavor-vbp/v1/realtime/unlock
Content-Type: application/json

{
    "post_id": 123,
    "element_id": "el_789"
}
```

### Sincronizar Cambios

```http
POST /wp-json/flavor-vbp/v1/realtime/sync
Content-Type: application/json

{
    "post_id": 123,
    "changes": [...]
}
```

## Configuracion

### Intervalos

```php
// wp-config.php o plugin

// Intervalo de heartbeat (segundos)
define('VBP_REALTIME_HEARTBEAT', 5);

// Tiempo de expiracion de lock (segundos)
define('VBP_REALTIME_LOCK_TIMEOUT', 30);
```

### JavaScript Config

```javascript
// Configuracion del cliente
const config = {
    heartbeatInterval: 5000,      // 5 segundos
    cursorThrottle: 50,           // Actualizar cursor cada 50ms max
    lockTimeout: 30000,           // 30 segundos de lock
    lockRenewInterval: 20000,     // Renovar lock cada 20 segundos
    reconnectDelay: 3000,         // Reintentar conexion cada 3 segundos
    maxReconnectAttempts: 10,     // Maximo de intentos
    syncDebounce: 500             // Debounce de sincronizacion
};
```

## Mecanismo de Sincronizacion

### Heartbeat

VBP usa WordPress Heartbeat API:

1. Cliente envia datos en intervalo regular
2. Servidor procesa y responde con actualizaciones
3. Cliente aplica cambios recibidos

### Bloqueo Optimista

1. Usuario intenta editar elemento
2. Se solicita bloqueo al servidor
3. Si exito: procede con edicion
4. Si falla: muestra mensaje de que otro usuario lo tiene

### Resolucion de Conflictos

Cuando hay conflictos:
1. Ultimo en guardar gana (LWW - Last Write Wins)
2. Cambios se registran en historial
3. Usuario puede ver/revertir cambios anteriores

## Consideraciones

- Heartbeat tiene overhead en servidor
- Bloqueos expiran automaticamente si usuario se desconecta
- Cambios grandes pueden tomar mas tiempo en sincronizar
- Funciona mejor con conexiones estables

## Limitaciones

- No soporta edicion simultanea del mismo campo de texto
- Latencia depende del intervalo de heartbeat
- Servidor puede limitar conexiones simultaneas
- No hay resolucion de conflictos CRDT (aun)

## Solucionar Problemas

### No se conecta

1. Verifica que Heartbeat esta habilitado
2. Comprueba permisos del usuario
3. Revisa la consola por errores

### Cursores no aparecen

1. Verifica que `connected` es true
2. Comprueba que hay otros usuarios activos
3. Revisa que el canvas tiene el listener correcto

### Bloqueos no funcionan

1. Verifica que el endpoint de lock responde
2. Comprueba que el timeout no es muy corto
3. Revisa que los bloqueos se renuevan correctamente

### Cambios no se sincronizan

1. Verifica la conexion a internet
2. Comprueba que `syncChanges` se llama
3. Revisa el intervalo de debounce
