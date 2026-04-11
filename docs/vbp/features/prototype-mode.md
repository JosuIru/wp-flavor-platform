# Prototype Mode (Modo Prototipado)

Sistema de prototipado interactivo que permite definir interacciones entre elementos, crear flujos de navegacion y previsualizar prototipos.

## Descripcion

El Prototype Mode permite simular el comportamiento de la interfaz final sin necesidad de escribir codigo. Define triggers (click, hover, scroll) y acciones (navegar, mostrar overlay, animar) para crear experiencias interactivas.

## Activar Prototype Mode

- Atajo: `P`
- Toolbar: Click en icono de Prototype
- Paleta de comandos: "Toggle Prototype Mode"

## Interfaz

### Modo Prototipado

Cuando el modo esta activo:

1. Los elementos muestran puntos de conexion
2. Puedes arrastrar flechas entre elementos
3. El Inspector muestra opciones de interaccion
4. El canvas cambia a modo de conexiones

### Panel de Prototype

Abre con `Shift+P`:

- Lista de interacciones definidas
- Configuracion de triggers y acciones
- Vista previa de flujo

## Triggers (Disparadores)

### Tipos de Trigger

| Trigger | Icono | Descripcion |
|---------|-------|-------------|
| **click** | clck | Al hacer clic en el elemento |
| **hover** | mouse | Al pasar el cursor sobre el elemento |
| **scroll** | scroll | Al entrar en el viewport |
| **load** | lightning | Al cargar la pagina/frame |
| **drag** | hand | Al arrastrar el elemento |

### Configuracion de Triggers

**Click**
- Sin opciones adicionales

**Hover**
- Delay: Tiempo antes de activar (ms)
- On Leave: Accion al salir

**Scroll**
- Offset: Porcentaje del viewport
- Once: Ejecutar solo una vez

**Load**
- Delay: Tiempo despues de cargar

**Drag**
- Direction: Direccion del arrastre

## Actions (Acciones)

### Tipos de Accion

| Accion | Icono | Descripcion |
|--------|-------|-------------|
| **navigate** | arrow-right | Navegar a otro frame/pagina |
| **overlay** | phone | Mostrar elemento como overlay/modal |
| **animate** | film | Ejecutar animacion |
| **set_variable** | note | Cambiar valor de variable |
| **open_url** | link | Abrir URL externa |
| **close_overlay** | x | Cerrar overlay actual |
| **back** | arrow-left | Volver al frame anterior |

### Configuracion de Acciones

**Navigate**
- Target: Frame/pagina destino
- Transition: Tipo de transicion
- Duration: Duracion de transicion

**Overlay**
- Target: Elemento a mostrar
- Position: Posicion del overlay
- Close on click outside: Cerrar al hacer clic fuera

**Animate**
- Animation: Animacion del Animation Builder
- O propiedades inline

**Set Variable**
- Variable: Nombre de la variable
- Value: Nuevo valor

## Transiciones

### Tipos de Transicion

| Transicion | Descripcion | Duracion Default |
|------------|-------------|------------------|
| **instant** | Sin animacion | 0ms |
| **dissolve** | Fundido cruzado | 300ms |
| **slide-left** | Deslizar izquierda | 300ms |
| **slide-right** | Deslizar derecha | 300ms |
| **slide-up** | Deslizar arriba | 300ms |
| **slide-down** | Deslizar abajo | 300ms |
| **push-left** | Empujar izquierda | 300ms |
| **push-right** | Empujar derecha | 300ms |
| **flip** | Voltear | 500ms |
| **zoom-in** | Acercar | 300ms |
| **zoom-out** | Alejar | 300ms |
| **smart-animate** | Animacion inteligente | 300ms |

### Smart Animate

Smart Animate detecta automaticamente cambios entre estados y los anima:

- Posicion (translate)
- Tamano (scale)
- Opacidad
- Colores
- Bordes

## Preview del Prototipo

### Iniciar Preview

- Atajo: `Ctrl+Shift+Enter`
- Toolbar: Boton Play
- Menu: View > Preview Prototype

### Durante Preview

- Interactua como usuario final
- Usa Back para volver
- Presiona `Escape` para salir

### Opciones de Preview

- **Full Screen**: Pantalla completa
- **Device Frame**: Mostrar marco de dispositivo
- **Show Hotspots**: Resaltar areas interactivas
- **Show Flow**: Mostrar flujo de navegacion

## Estructura de Interaccion

```json
{
    "id": "int_abc123",
    "elementId": "el_button",
    "trigger": {
        "type": "click",
        "config": {}
    },
    "action": {
        "type": "navigate",
        "config": {
            "targetId": "frame_signup",
            "transition": "slide-left",
            "duration": 300
        }
    }
}
```

## API JavaScript

### Acceder al Sistema

```javascript
const prototype = window.VBPPrototypeMode;
```

### Activar/Desactivar

```javascript
prototype.enable();
prototype.disable();
prototype.toggle();
```

### Obtener Interacciones

```javascript
// Todas las interacciones del documento
const interactions = prototype.getInteractions();

// Interacciones de un elemento
const elementInteractions = prototype.getInteractionsForElement('el_button');
```

### Crear Interaccion

```javascript
prototype.createInteraction({
    elementId: 'el_button',
    trigger: { type: 'click' },
    action: {
        type: 'navigate',
        config: {
            targetId: 'frame_2',
            transition: 'slide-left'
        }
    }
});
```

### Editar Interaccion

```javascript
prototype.updateInteraction('int_abc123', {
    action: {
        type: 'overlay',
        config: {
            targetId: 'el_modal'
        }
    }
});
```

### Eliminar Interaccion

```javascript
prototype.deleteInteraction('int_abc123');
```

### Preview

```javascript
// Iniciar preview
prototype.startPreview();

// Detener preview
prototype.stopPreview();

// Preview de elemento especifico
prototype.previewElement('el_button');
```

### Conectar Elementos

```javascript
// Crear conexion visual (arrastrando)
prototype.startConnection('el_source');
prototype.endConnection('el_target');
```

## Eventos

```javascript
// Modo cambiado
document.addEventListener('vbp:prototype:mode-changed', (e) => {
    console.log('Activo:', e.detail.enabled);
});

// Interaccion creada
document.addEventListener('vbp:prototype:interaction-created', (e) => {
    console.log('Nueva interaccion:', e.detail.interaction);
});

// Preview iniciado
document.addEventListener('vbp:prototype:preview-started', () => {
    console.log('Preview activo');
});

// Accion ejecutada (en preview)
document.addEventListener('vbp:prototype:action-executed', (e) => {
    console.log('Accion:', e.detail.action);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `P` | Toggle Prototype Mode |
| `Shift+P` | Abrir panel de Prototype |
| `Ctrl+Shift+Enter` | Iniciar preview |
| `Escape` | Salir de preview |
| `Delete` | Eliminar interaccion seleccionada |

## Exportar Prototipo

### Como HTML

```javascript
prototype.exportHTML().then(html => {
    // HTML interactivo standalone
});
```

### Como JSON

```javascript
const data = prototype.exportJSON();
// Incluye todas las interacciones y assets
```

## Variables de Estado

Puedes usar variables para estados dinamicos:

```javascript
// Definir variable
prototype.setVariable('isLoggedIn', false);

// Condicion en interaccion
{
    trigger: { type: 'click' },
    condition: 'isLoggedIn === true',
    action: { type: 'navigate', config: { targetId: 'frame_dashboard' } }
}

// Cambiar variable con accion
{
    action: {
        type: 'set_variable',
        config: { variable: 'isLoggedIn', value: true }
    }
}
```

## Integracion con Animation Builder

Las animaciones creadas en Animation Builder estan disponibles en Prototype Mode:

1. Crea animacion en Animation Builder
2. En Prototype, elige accion "Animate"
3. Selecciona la animacion del dropdown

## Consideraciones

- Las interacciones se guardan con el documento VBP
- El preview funciona en navegadores modernos
- Smart Animate funciona mejor con elementos similares
- Los overlays soportan multiples capas

## Casos de Uso

### Flujo de Login

1. Frame Login con form
2. Click en "Submit" -> navega a Dashboard
3. Click en "Signup" -> navega a Registro

### Modal de Confirmacion

1. Click en "Delete" -> muestra overlay
2. Click en "Confirm" -> cierra overlay + accion
3. Click fuera -> cierra overlay

### Carrusel

1. Click en flecha derecha -> slide siguiente
2. Click en flecha izquierda -> slide anterior
3. Auto-play con trigger load + delay

## Solucionar Problemas

### Las interacciones no funcionan

1. Verifica que el modo Prototype esta activo
2. Comprueba que el trigger esta configurado
3. Revisa que el target existe

### Smart Animate no anima

1. Verifica que ambos estados tienen el mismo ID
2. Comprueba que las propiedades son animables
3. Revisa la duracion de la transicion

### El preview se ve mal

1. Verifica dimensiones del canvas
2. Comprueba responsive styles
3. Revisa que los assets cargan
