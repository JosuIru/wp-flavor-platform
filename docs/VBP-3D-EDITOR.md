# VBP Editor 3D - Guía Completa

## ¿Qué es el Editor 3D?

El Editor 3D de Visual Builder Pro permite crear escenas 3D interactivas directamente en tus páginas web. Los visitantes pueden rotar, hacer zoom y explorar los objetos 3D.

## Casos de Uso

| Sector | Aplicación |
|--------|------------|
| **E-commerce** | Visualización de productos 360° (muebles, electrónica, moda) |
| **Inmobiliaria** | Maquetas de edificios, planos interactivos |
| **Educación** | Modelos anatómicos, moléculas, geografía |
| **Industria** | Catálogos de piezas, maquinaria |
| **Arte/Diseño** | Portfolios 3D, esculturas digitales |
| **Gaming** | Landing pages con elementos interactivos |

---

## Bloques Disponibles

### 1. 3D Scene (Escena 3D)

El contenedor principal. Todo objeto 3D debe estar dentro de una escena.

**Propiedades:**
| Propiedad | Descripción | Valores |
|-----------|-------------|---------|
| `preset` | Preset de escena | minimal, studio, outdoor, dark |
| `background` | Color de fondo | Color hex o 'transparent' |
| `enableControls` | Permitir interacción | true/false |
| `autoRotate` | Rotación automática | true/false |
| `autoRotateSpeed` | Velocidad de rotación | 0.5 - 10 |
| `cameraPosition` | Posición inicial cámara | [x, y, z] |
| `enableShadows` | Sombras | true/false |

**Ejemplo de uso:**
```
1. Añadir bloque "3D Scene"
2. Configurar preset (ej: "studio" para fondo neutro)
3. Activar "Auto-rotar" si quieres rotación automática
4. Añadir objetos dentro de la escena
```

---

### 2. 3D Object (Objeto Primitivo)

Formas geométricas básicas.

**Tipos disponibles:**
- `box` - Cubo/caja
- `sphere` - Esfera
- `cylinder` - Cilindro
- `cone` - Cono
- `torus` - Toroide (donut)
- `plane` - Plano

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `type` | Tipo de geometría |
| `width/height/depth` | Dimensiones |
| `color` | Color del material |
| `metalness` | Efecto metálico (0-1) |
| `roughness` | Rugosidad (0-1) |
| `position` | Posición [x, y, z] |
| `rotation` | Rotación [x, y, z] en grados |
| `scale` | Escala [x, y, z] |

---

### 3. 3D Model (Modelo Externo)

Importa modelos 3D desde archivos GLTF/GLB.

**Formatos soportados:**
- `.gltf` - Formato texto (con archivos separados)
- `.glb` - Formato binario (todo en uno, recomendado)

**Cómo obtener modelos:**
1. **Blender** → Exportar como GLTF/GLB
2. **SketchUp** → Plugin de exportación GLTF
3. **Sketchfab** → Descargar modelos gratuitos en GLTF
4. **Poly Pizza** → Modelos low-poly gratuitos
5. **Turbosquid** → Modelos profesionales

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `url` | URL del archivo .glb/.gltf |
| `scale` | Escala del modelo |
| `position` | Posición en la escena |
| `rotation` | Rotación inicial |
| `castShadow` | Proyecta sombras |
| `receiveShadow` | Recibe sombras |

**Ejemplo:**
```
1. Subir archivo .glb a la biblioteca de medios
2. Añadir bloque "3D Model"
3. Seleccionar el archivo
4. Ajustar escala y posición
```

---

### 4. 3D Text (Texto 3D)

Texto extruido con profundidad.

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `text` | El texto a mostrar |
| `font` | Fuente (helvetiker, optimer, gentilis) |
| `size` | Tamaño del texto |
| `height` | Profundidad de extrusión |
| `color` | Color del material |
| `bevelEnabled` | Bordes biselados |

**Uso típico:** Logos 3D, títulos destacados, carteles.

---

### 5. 3D Particles (Partículas)

Sistemas de partículas para efectos visuales.

**Presets:**
| Preset | Efecto |
|--------|--------|
| `snow` | Nieve cayendo |
| `rain` | Lluvia |
| `stars` | Estrellas/puntos brillantes |
| `fire` | Fuego (partículas naranjas) |
| `smoke` | Humo |
| `sparkles` | Destellos |

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `preset` | Tipo de partículas |
| `count` | Cantidad de partículas |
| `size` | Tamaño de cada partícula |
| `speed` | Velocidad de movimiento |
| `color` | Color de las partículas |
| `opacity` | Transparencia |

---

### 6. 3D Light (Luz)

Iluminación de la escena.

**Tipos:**
| Tipo | Descripción |
|------|-------------|
| `ambient` | Luz ambiental (ilumina todo por igual) |
| `directional` | Luz direccional (como el sol) |
| `point` | Luz puntual (bombilla) |
| `spot` | Foco (cono de luz) |
| `hemisphere` | Cielo/suelo (dos colores) |

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `type` | Tipo de luz |
| `color` | Color de la luz |
| `intensity` | Intensidad (0-10) |
| `position` | Posición [x, y, z] |
| `castShadow` | Proyecta sombras |

**Recomendación:** Siempre incluir al menos una luz `ambient` + una `directional`.

---

### 7. 3D Group (Grupo)

Agrupa objetos para moverlos/rotarlos juntos.

**Uso:**
1. Crear grupo
2. Arrastrar objetos al grupo (desde Inspector 3D)
3. Transformar el grupo afecta a todos los hijos

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `position` | Posición del grupo |
| `rotation` | Rotación del grupo |
| `scale` | Escala del grupo |
| `visible` | Mostrar/ocultar todo el grupo |

---

### 8. 3D Animation (Animación)

Añade movimiento a objetos.

**Presets de animación:**
| Preset | Efecto |
|--------|--------|
| `rotate-y` | Rotación continua eje Y |
| `rotate-x` | Rotación continua eje X |
| `float` | Flotación arriba/abajo |
| `pulse` | Escala pulsante |
| `bounce` | Rebote |
| `swing` | Balanceo |

**Propiedades:**
| Propiedad | Descripción |
|-----------|-------------|
| `preset` | Tipo de animación |
| `speed` | Velocidad |
| `amplitude` | Amplitud del movimiento |
| `loop` | Repetir infinitamente |
| `autoPlay` | Iniciar automáticamente |

---

## Flujo de Trabajo Recomendado

### Crear una escena de producto:

```
1. Añadir "3D Scene" con preset "studio"
2. Añadir "3D Light" tipo "ambient" (intensidad 0.5)
3. Añadir "3D Light" tipo "directional" (intensidad 1, posición [5,5,5])
4. Añadir "3D Model" con tu producto .glb
5. Ajustar posición y escala del modelo
6. Activar "Auto-rotar" en la escena
7. Publicar
```

### Crear un logo 3D animado:

```
1. Añadir "3D Scene" con fondo transparente
2. Añadir "3D Text" con tu texto
3. Configurar color y material metálico
4. Añadir "3D Animation" preset "rotate-y"
5. Vincular la animación al texto
```

### Crear escena con partículas:

```
1. Añadir "3D Scene" con preset "dark"
2. Añadir tus objetos 3D
3. Añadir "3D Particles" preset "sparkles"
4. Ajustar cantidad y tamaño
```

---

## Inspector 3D Avanzado

El panel lateral para gestión avanzada de la escena.

### Funciones:

| Función | Cómo |
|---------|------|
| **Ver jerarquía** | Panel "Objetos en Escena" |
| **Seleccionar** | Clic en objeto del árbol |
| **Reordenar** | Arrastrar dentro del mismo nivel |
| **Mover entre grupos** | Arrastrar a otro grupo |
| **Sacar de grupo** | Arrastrar al nivel raíz |
| **Duplicar** | Botón duplicar o Ctrl+D |
| **Eliminar** | Botón eliminar o Supr |

### Atajos de teclado:

| Atajo | Acción |
|-------|--------|
| `G` | Mover (Grab) |
| `R` | Rotar |
| `S` | Escalar |
| `X/Y/Z` | Restringir a eje |
| `Ctrl+D` | Duplicar |
| `Supr` | Eliminar |
| `Ctrl+G` | Agrupar selección |
| `Ctrl+Z` | Deshacer |

---

## Optimización y Rendimiento

### Recomendaciones:

1. **Modelos ligeros** - Usar modelos < 5MB, < 50k polígonos
2. **Texturas optimizadas** - JPG para fotos, PNG solo si necesitas transparencia
3. **Pocas luces** - Máximo 3-4 luces por escena
4. **Sombras selectivas** - Solo en objetos principales
5. **LOD** - Usar modelos simplificados para móvil

### Tamaños recomendados:

| Dispositivo | Polígonos | Texturas |
|-------------|-----------|----------|
| Desktop | < 100k | 2048x2048 |
| Tablet | < 50k | 1024x1024 |
| Móvil | < 25k | 512x512 |

---

## Presets de Escena

| Preset | Descripción | Ideal para |
|--------|-------------|------------|
| `minimal` | Fondo blanco, luz suave | Productos, minimalismo |
| `studio` | Fondo gris, iluminación de estudio | E-commerce, catálogos |
| `outdoor` | Cielo HDR, luz natural | Arquitectura, exteriores |
| `dark` | Fondo oscuro, luces dramáticas | Gaming, tecnología |
| `gallery` | Galería blanca, spotlights | Arte, esculturas |
| `showroom` | Suelo reflectante | Vehículos, maquinaria |

---

## Presets de Materiales

| Preset | Descripción |
|--------|-------------|
| `plastic` | Plástico mate |
| `metal` | Metal pulido |
| `glass` | Cristal transparente |
| `wood` | Madera |
| `concrete` | Hormigón |
| `gold` | Oro |
| `chrome` | Cromado |
| `matte` | Mate sin reflejos |

---

## Solución de Problemas

### El modelo no aparece:
- Verificar que el archivo .glb es válido
- Ajustar la escala (puede ser muy grande o pequeño)
- Revisar la posición (puede estar fuera de cámara)

### La escena va lenta:
- Reducir número de partículas
- Desactivar sombras
- Usar modelos más ligeros
- Reducir calidad de texturas

### Los colores se ven mal:
- Añadir luz ambiental
- Verificar que hay luz direccional
- Revisar el preset de escena

### No puedo interactuar:
- Verificar que `enableControls` está activado
- La escena debe tener foco (clic primero)

---

## Recursos

### Dónde conseguir modelos 3D gratuitos:

- [Sketchfab](https://sketchfab.com) - Miles de modelos GLTF
- [Poly Pizza](https://poly.pizza) - Low-poly gratuitos
- [Kenney](https://kenney.nl) - Assets para juegos
- [Three.js Examples](https://threejs.org/examples/) - Modelos de ejemplo

### Herramientas para crear modelos:

- **Blender** (gratis) - Profesional, exporta GLTF
- **SketchUp** (freemium) - Fácil para arquitectura
- **Tinkercad** (gratis) - Muy fácil, básico
- **Spline** (gratis) - Web-based, diseño 3D

---

---

## Realidad Aumentada (AR)

### ¿Qué es?

Permite a los usuarios ver los objetos 3D en su entorno real usando la cámara del móvil.

### Requisitos

| Requisito | Detalle |
|-----------|---------|
| Navegador | Chrome 79+ en Android |
| Dispositivo | Android con ARCore |
| HTTPS | Obligatorio (o localhost) |

### Cómo activar

1. Seleccionar bloque **3D Scene**
2. En el inspector, activar **"Habilitar AR"**
3. Aparecerá botón "Ver en AR" en la escena
4. El usuario pulsa → se abre cámara → objeto aparece en su espacio

### Cómo funciona

```
Usuario pulsa "Ver en AR"
    ↓
Navegador pide permiso de cámara
    ↓
Se inicia sesión WebXR immersive-ar
    ↓
Objeto 3D se renderiza sobre cámara real
    ↓
Usuario puede mover/rotar con gestos
    ↓
Pulsa "Salir de AR" para volver
```

### Dispositivos compatibles

- **Android**: Samsung Galaxy S8+, Pixel 2+, OnePlus 6+, Xiaomi Mi 8+
- **iOS**: Safari no soporta WebXR nativamente (usar app Quick Look)

### Código ejemplo

```javascript
// Verificar soporte AR
const scene = VBP3D.getScene('mi-contenedor');
scene.checkARSupport().then(supported => {
    if (supported) {
        scene.startAR();
    } else {
        alert('AR no disponible');
    }
});
```

---

## Próximas Funcionalidades
- [ ] **Configurador de productos** - Cambiar colores/partes
- [ ] **Hotspots** - Puntos de información interactivos
- [ ] **Animaciones por scroll** - Animar al hacer scroll
- [ ] **Physics** - Física básica (gravedad, colisiones)
