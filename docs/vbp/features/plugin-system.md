# Sistema de Plugins

Arquitectura extensible que permite agregar funcionalidades al editor VBP mediante plugins.

## Descripcion

El Sistema de Plugins de VBP permite extender las capacidades del editor sin modificar el codigo core. Los plugins pueden agregar bloques, paneles, herramientas, comandos y mas, manteniendo compatibilidad con actualizaciones.

## Como Acceder

- Menu: Plugins > Gestionar Plugins
- Paleta de comandos: "plugins"
- URL: `/wp-admin/admin.php?page=vbp-plugins`

## Tipos de Extensiones

### 1. Bloques Personalizados

```javascript
VBPPlugins.registerBlock({
    id: 'mi-plugin/mi-bloque',
    name: 'Mi Bloque',
    category: 'custom',
    icon: '<svg>...</svg>',
    defaultProps: {
        text: 'Hola mundo'
    },
    fields: [
        { name: 'text', type: 'text', label: 'Texto' }
    ],
    render: (props) => `<div class="mi-bloque">${props.text}</div>`
});
```

### 2. Paneles

```javascript
VBPPlugins.registerPanel({
    id: 'mi-plugin/mi-panel',
    name: 'Mi Panel',
    icon: '<svg>...</svg>',
    position: 'left',           // 'left', 'right', 'bottom'
    component: MiPanelComponent  // Alpine.js component
});
```

### 3. Comandos

```javascript
VBPPlugins.registerCommand({
    id: 'mi-plugin/mi-comando',
    name: 'Mi Comando',
    shortcut: 'Ctrl+Alt+M',
    category: 'custom',
    execute: () => {
        console.log('Comando ejecutado');
    }
});
```

### 4. Herramientas

```javascript
VBPPlugins.registerTool({
    id: 'mi-plugin/mi-herramienta',
    name: 'Mi Herramienta',
    icon: '<svg>...</svg>',
    cursor: 'crosshair',
    onActivate: () => { /* ... */ },
    onDeactivate: () => { /* ... */ },
    onMouseDown: (e) => { /* ... */ },
    onMouseMove: (e) => { /* ... */ },
    onMouseUp: (e) => { /* ... */ }
});
```

### 5. Inspectores

```javascript
VBPPlugins.registerInspector({
    id: 'mi-plugin/mi-inspector',
    name: 'Mi Inspector',
    blockTypes: ['mi-plugin/mi-bloque'],  // Para que bloques
    sections: [
        {
            name: 'custom',
            label: 'Configuracion Custom',
            fields: [/* ... */]
        }
    ]
});
```

## Estructura de un Plugin

### Archivos Requeridos

```
mi-plugin/
├── plugin.json         # Metadatos del plugin
├── index.js            # Punto de entrada
├── blocks/             # Bloques personalizados
│   └── mi-bloque.js
├── panels/             # Paneles
│   └── mi-panel.js
├── styles/             # Estilos CSS
│   └── styles.css
└── assets/             # Imagenes, iconos
    └── icon.svg
```

### plugin.json

```json
{
    "id": "mi-plugin",
    "name": "Mi Plugin VBP",
    "version": "1.0.0",
    "description": "Descripcion del plugin",
    "author": "Tu Nombre",
    "minVBPVersion": "2.3.0",
    "main": "index.js",
    "styles": ["styles/styles.css"],
    "dependencies": [],
    "settings": {
        "apiKey": {
            "type": "string",
            "label": "API Key",
            "default": ""
        }
    }
}
```

### index.js

```javascript
(function() {
    'use strict';

    const plugin = VBPPlugins.register({
        id: 'mi-plugin',

        onLoad: function(api) {
            // Registrar bloques
            this.registerBlocks(api);

            // Registrar paneles
            this.registerPanels(api);

            // Registrar comandos
            this.registerCommands(api);
        },

        onUnload: function() {
            // Limpieza
        },

        registerBlocks: function(api) {
            api.registerBlock({
                id: 'mi-plugin/bloque',
                name: 'Mi Bloque',
                // ...
            });
        },

        registerPanels: function(api) {
            // ...
        },

        registerCommands: function(api) {
            // ...
        }
    });

})();
```

## API de Plugins

### VBPPlugins

```javascript
// Registrar plugin
const plugin = VBPPlugins.register({
    id: 'mi-plugin',
    onLoad: (api) => { /* ... */ },
    onUnload: () => { /* ... */ }
});

// Obtener plugin
const plugin = VBPPlugins.get('mi-plugin');

// Listar plugins
const all = VBPPlugins.list();
const active = VBPPlugins.listActive();

// Activar/Desactivar
VBPPlugins.activate('mi-plugin');
VBPPlugins.deactivate('mi-plugin');

// Configuracion
const settings = VBPPlugins.getSettings('mi-plugin');
VBPPlugins.setSettings('mi-plugin', { apiKey: 'xxx' });
```

### API del Plugin

```javascript
// Dentro de onLoad, el api proporciona:

api.registerBlock(config);         // Registrar bloque
api.registerPanel(config);         // Registrar panel
api.registerCommand(config);       // Registrar comando
api.registerTool(config);          // Registrar herramienta
api.registerInspector(config);     // Registrar inspector

api.getStore();                    // Acceder al store de VBP
api.getSelection();                // Obtener elementos seleccionados
api.addElement(element);           // Agregar elemento
api.updateElement(id, props);      // Actualizar elemento
api.removeElement(id);             // Eliminar elemento

api.showNotification(message, type);  // Mostrar notificacion
api.showModal(config);                // Mostrar modal
api.confirm(message);                 // Dialogo de confirmacion

api.fetch(url, options);           // Fetch con auth
api.saveData(key, data);           // Guardar datos persistentes
api.loadData(key);                 // Cargar datos

api.on(event, callback);           // Escuchar evento
api.off(event, callback);          // Dejar de escuchar
api.emit(event, data);             // Emitir evento
```

## Hooks Disponibles

### Documento

```javascript
api.on('vbp:document:loaded', (data) => { /* ... */ });
api.on('vbp:document:saved', (data) => { /* ... */ });
api.on('vbp:document:dirty', (data) => { /* ... */ });
```

### Elementos

```javascript
api.on('vbp:element:added', (data) => { /* ... */ });
api.on('vbp:element:updated', (data) => { /* ... */ });
api.on('vbp:element:removed', (data) => { /* ... */ });
api.on('vbp:element:selected', (data) => { /* ... */ });
```

### Editor

```javascript
api.on('vbp:editor:ready', () => { /* ... */ });
api.on('vbp:editor:resize', (size) => { /* ... */ });
api.on('vbp:breakpoint:changed', (breakpoint) => { /* ... */ });
api.on('vbp:zoom:changed', (zoom) => { /* ... */ });
```

## Panel de Gestion

El panel de gestion de plugins permite:

- Ver plugins instalados
- Activar/Desactivar plugins
- Configurar ajustes
- Ver informacion de dependencias
- Instalar desde URL/ZIP

### Interfaz

```
PLUGINS INSTALADOS
├── [ON]  Mi Plugin (v1.0.0)
│         Descripcion del plugin
│         [Configurar] [Desactivar]
├── [OFF] Otro Plugin (v2.1.0)
│         [Activar] [Eliminar]
└── [!]   Plugin Incompatible (v0.5.0)
          Requiere VBP 3.0.0+

[+ Instalar Plugin]
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `Ctrl+Shift+P` | Abrir panel de plugins |

## Distribucion de Plugins

### Publicar Plugin

1. Crea el archivo ZIP con la estructura correcta
2. Incluye `plugin.json` con metadatos completos
3. Documenta requisitos y uso
4. Sube a tu repositorio o marketplace

### Instalar Plugin

1. Descarga el ZIP
2. Ve a Plugins > Instalar
3. Sube el archivo o pega URL
4. Activa el plugin

## Consideraciones de Seguridad

### Buenas Practicas

- Sanitiza todas las entradas
- Usa nonces para peticiones AJAX
- No exponer datos sensibles
- Valida permisos de usuario

### Sandbox

Los plugins se ejecutan en un contexto controlado:

- No pueden acceder a otros plugins directamente
- Las APIs estan filtradas
- Los errores se contienen

## Debugging

```javascript
// Activar modo debug para plugin
VBPPlugins.setDebug('mi-plugin', true);

// Ver logs del plugin
console.log(VBPPlugins.getLogs('mi-plugin'));

// Inspeccionar estado
console.log(VBPPlugins.inspect('mi-plugin'));
```

## Solucionar Problemas

### Plugin no carga

1. Verifica que `plugin.json` es valido
2. Comprueba la version minima de VBP
3. Revisa errores en consola
4. Verifica dependencias

### Conflictos entre plugins

1. Desactiva plugins uno por uno
2. Verifica IDs unicos
3. Revisa conflictos de atajos de teclado
4. Comprueba orden de carga

### Error en bloque personalizado

1. Verifica la funcion render
2. Comprueba props requeridos
3. Revisa HTML generado
4. Valida campos del inspector
