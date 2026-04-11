# 3D y WebGL

Sistema de creacion y manipulacion de contenido 3D usando Three.js integrado en el editor VBP.

## Descripcion

El modulo 3D de VBP permite agregar elementos tridimensionales a los disenos: modelos 3D, escenas interactivas, texto 3D, particulas y efectos visuales. Usa Three.js internamente y se integra con el flujo de trabajo del editor.

## Como Acceder

- Panel de Bloques > Categoria "3D"
- Menu: Insert > 3D Element
- Paleta de comandos: "3D"

## Bloques 3D Disponibles

### 3D Scene

Contenedor principal para contenido 3D.

```javascript
{
    type: '3d-scene',
    props: {
        width: '100%',
        height: '400px',
        background: 'transparent',  // o color hex
        camera: {
            type: 'perspective',    // 'perspective', 'orthographic'
            position: [0, 2, 5],
            fov: 75
        },
        controls: {
            type: 'orbit',          // 'orbit', 'fly', 'drag', 'none'
            enableZoom: true,
            enablePan: true,
            autoRotate: false
        },
        lighting: 'studio'          // 'studio', 'outdoor', 'dramatic', 'custom'
    }
}
```

### 3D Model

Importa modelos 3D (GLTF, GLB, OBJ).

```javascript
{
    type: '3d-model',
    props: {
        src: '/path/to/model.glb',
        scale: 1,
        position: [0, 0, 0],
        rotation: [0, 0, 0],
        animation: 'idle',          // Nombre de animacion del modelo
        castShadow: true,
        receiveShadow: true
    }
}
```

### 3D Text

Texto extruido en 3D.

```javascript
{
    type: '3d-text',
    props: {
        text: 'Hello 3D',
        font: 'helvetiker',
        size: 1,
        height: 0.2,               // Extrusion depth
        curveSegments: 12,
        bevelEnabled: true,
        bevelThickness: 0.03,
        bevelSize: 0.02,
        material: {
            type: 'standard',      // 'standard', 'phong', 'toon', 'glass'
            color: '#ffffff',
            metalness: 0.5,
            roughness: 0.3
        }
    }
}
```

### 3D Shape

Formas geometricas primitivas.

```javascript
{
    type: '3d-shape',
    props: {
        geometry: 'box',           // 'box', 'sphere', 'cylinder', 'cone', 'torus', 'plane'
        size: [1, 1, 1],           // Dimensiones
        segments: 32,              // Segmentos para formas curvas
        material: {
            type: 'standard',
            color: '#3b82f6',
            wireframe: false
        }
    }
}
```

### 3D Particles

Sistema de particulas.

```javascript
{
    type: '3d-particles',
    props: {
        count: 1000,
        size: 0.05,
        color: '#ffffff',
        spread: [10, 10, 10],
        velocity: [0, 0.1, 0],
        lifetime: 2,
        texture: '/path/to/sprite.png',
        blending: 'additive'
    }
}
```

## Inspector 3D

Cuando seleccionas un elemento 3D, el inspector muestra:

### Transform

| Propiedad | Descripcion |
|-----------|-------------|
| Position X/Y/Z | Posicion en espacio 3D |
| Rotation X/Y/Z | Rotacion en grados |
| Scale X/Y/Z | Escala |

### Material

| Propiedad | Descripcion |
|-----------|-------------|
| Type | standard, phong, toon, glass, custom |
| Color | Color base |
| Metalness | 0-1, aspecto metalico |
| Roughness | 0-1, aspecto rugoso |
| Emissive | Color de emision |
| Map | Textura de color |
| Normal Map | Mapa de normales |
| Env Map | Mapa de entorno |

### Lighting

| Tipo de Luz | Descripcion |
|-------------|-------------|
| Ambient | Luz ambiental global |
| Directional | Luz direccional (sol) |
| Point | Luz puntual (bombilla) |
| Spot | Luz focal (foco) |
| Hemisphere | Luz de cielo/suelo |

### Animation

| Propiedad | Descripcion |
|-----------|-------------|
| Clip | Animacion del modelo |
| Speed | Velocidad de reproduccion |
| Loop | Repetir animacion |
| Auto Play | Reproducir al cargar |

## API JavaScript

### VBP3DScene

```javascript
const scene = window.VBP3DScene;

// Obtener escena por elemento
const sceneInstance = scene.getScene(elementId);

// Agregar objeto
scene.addObject(sceneId, {
    type: 'box',
    position: [0, 1, 0],
    material: { color: '#ff0000' }
});

// Actualizar objeto
scene.updateObject(objectId, {
    position: [1, 1, 0],
    rotation: [0, 45, 0]
});

// Remover objeto
scene.removeObject(objectId);

// Camara
scene.setCameraPosition([0, 5, 10]);
scene.setCameraTarget([0, 0, 0]);
scene.setCameraFOV(60);

// Controles
scene.setControls('orbit');
scene.enableControls(true);
scene.setAutoRotate(true);

// Iluminacion
scene.setLighting('dramatic');
scene.addLight({ type: 'point', position: [0, 5, 0], intensity: 1 });

// Screenshot
const dataUrl = scene.takeScreenshot();

// Exportar
const gltf = scene.exportGLTF();
```

### VBP3DStore

```javascript
const store = Alpine.store('vbp3d');

// Estado global 3D
store.scenes;              // Escenas activas
store.selectedObject;      // Objeto 3D seleccionado
store.transformMode;       // 'translate', 'rotate', 'scale'

// Cambiar modo de transformacion
store.setTransformMode('rotate');

// Helpers visuales
store.showGrid = true;
store.showAxes = true;
store.showBoundingBox = true;
```

### Cargar Modelos

```javascript
// Cargar GLTF/GLB
const model = await scene.loadModel('/models/character.glb', {
    scale: 1,
    position: [0, 0, 0]
});

// Listar animaciones del modelo
const clips = model.getAnimationClips();

// Reproducir animacion
model.playAnimation('walk');
model.stopAnimation();
model.setAnimationSpeed(1.5);
```

## Eventos

```javascript
// Modelo cargado
document.addEventListener('vbp:3d:model:loaded', (e) => {
    console.log('Modelo:', e.detail.modelId);
    console.log('Animaciones:', e.detail.animations);
});

// Objeto seleccionado
document.addEventListener('vbp:3d:object:selected', (e) => {
    console.log('Objeto:', e.detail.objectId);
});

// Transformacion aplicada
document.addEventListener('vbp:3d:transform:changed', (e) => {
    console.log('Objeto:', e.detail.objectId);
    console.log('Transform:', e.detail.transform);
});

// Click en objeto 3D
document.addEventListener('vbp:3d:click', (e) => {
    console.log('Clicked:', e.detail.objectId);
    console.log('Point:', e.detail.point);
});
```

## Atajos de Teclado

| Atajo | Accion |
|-------|--------|
| `G` | Modo translate |
| `R` | Modo rotate |
| `S` | Modo scale |
| `X` | Restringir a eje X |
| `Y` | Restringir a eje Y |
| `Z` | Restringir a eje Z |
| `Shift+G` | Toggle grid |
| `Shift+X` | Toggle ejes |
| `F` | Focus en seleccion |

## Formatos Soportados

### Modelos 3D

| Formato | Extension | Notas |
|---------|-----------|-------|
| GLTF | .gltf, .glb | Recomendado, soporta animaciones |
| OBJ | .obj | Solo geometria |
| FBX | .fbx | Con convertidor |
| STL | .stl | Solo geometria |

### Texturas

| Formato | Uso |
|---------|-----|
| PNG/JPG | Texturas de color |
| EXR/HDR | Environment maps |
| Normal maps | Detalle de superficie |

## Presets de Iluminacion

| Preset | Descripcion |
|--------|-------------|
| `studio` | Iluminacion de estudio, neutral |
| `outdoor` | Luz natural, cielo azul |
| `dramatic` | Alto contraste, sombras fuertes |
| `soft` | Luz suave difusa |
| `neon` | Colores vibrantes |
| `sunset` | Tonos calidos |
| `night` | Oscuro con acentos |

```javascript
scene.setLighting('dramatic');

// O personalizado
scene.setLighting({
    ambient: { color: '#404040', intensity: 0.5 },
    directional: { color: '#ffffff', intensity: 1, position: [5, 10, 5] },
    hemisphere: { skyColor: '#87ceeb', groundColor: '#444444', intensity: 0.3 }
});
```

## Consideraciones de Performance

### Optimizacion de Modelos

1. **Polygon count**: Mantener < 100k triangulos
2. **Texturas**: Usar power-of-2 sizes (512, 1024, 2048)
3. **LOD**: Usar niveles de detalle si es posible
4. **Instancing**: Reusar geometrias

### Configuracion de Renderer

```javascript
scene.setRendererConfig({
    antialias: true,
    shadows: true,
    pixelRatio: window.devicePixelRatio,
    maxLights: 4
});

// En dispositivos moviles
if (isMobile) {
    scene.setRendererConfig({
        antialias: false,
        shadows: false,
        pixelRatio: 1
    });
}
```

### Lazy Loading

```javascript
// Cargar modelo solo cuando este visible
scene.loadModel('/model.glb', {
    lazyLoad: true,
    threshold: 0.1  // 10% visible
});
```

## Exportacion

### Como GLTF

```javascript
const gltf = await scene.exportGLTF({
    binary: true,           // GLB
    includeAnimations: true,
    includeCameras: false
});
```

### Como Screenshot

```javascript
const dataUrl = scene.takeScreenshot({
    width: 1920,
    height: 1080,
    transparent: true
});
```

## Solucionar Problemas

### El modelo no se ve

1. Verifica que el path es correcto
2. Comprueba la escala (puede ser muy grande o muy pequeno)
3. Revisa la posicion de la camara
4. Verifica que el formato es soportado

### Performance lenta

1. Reduce el polygon count del modelo
2. Baja la resolucion de texturas
3. Desactiva sombras en movil
4. Usa menos luces

### Las animaciones no funcionan

1. Verifica que el modelo tiene animaciones (GLTF)
2. Comprueba el nombre del clip
3. Revisa que el modelo esta correctamente exportado

### Errores de WebGL

1. Actualiza los drivers de video
2. Verifica soporte de WebGL en el navegador
3. Prueba en modo incognito
4. Reduce la complejidad de la escena
