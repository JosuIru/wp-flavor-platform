# WebXR / Realidad Aumentada

Visualizar objetos 3D en el mundo real usando la cámara del dispositivo.

## Qué es

WebXR permite que los usuarios vean los modelos 3D en su entorno físico:
- Producto en su sala de estar
- Mueble en su habitación
- Objeto a escala real

## Requisitos

| Requisito | Detalle |
|-----------|---------|
| Navegador | Chrome 79+ en Android |
| Dispositivo | Android con ARCore (la mayoría desde 2018) |
| Conexión | HTTPS obligatorio (o localhost) |
| Permisos | Acceso a cámara |

## Cómo activar

1. Añadir bloque **3D Scene**
2. En Inspector → activar **"Habilitar AR"**
3. Añadir objetos 3D a la escena
4. Publicar página

## Experiencia del usuario

```
Usuario visita la página
    ↓
Ve el modelo 3D en el canvas
    ↓
Pulsa botón "Ver en AR"
    ↓
Navegador pide permiso de cámara
    ↓
Modelo aparece en su entorno real
    ↓
Puede mover/rotar/escalar con gestos
    ↓
Pulsa "Salir de AR" para volver
```

## Botón AR

El botón aparece automáticamente si:
- `enableAR: true` en la escena
- El dispositivo soporta WebXR
- El navegador es compatible

Estilos del botón:
- Gradiente morado/azul
- Posición: centrado abajo
- Icono AR + texto "Ver en AR"

## API JavaScript

### Verificar soporte
```javascript
const scene = VBP3D.getScene('mi-contenedor');
const supported = await scene.checkARSupport();
if (supported) {
    console.log('AR disponible');
}
```

### Iniciar AR
```javascript
await scene.startAR();
```

### Detener AR
```javascript
scene.stopAR();
```

### Eventos
```javascript
// Cuando inicia sesión AR
scene.onARStart = () => {
    console.log('AR iniciado');
};

// Cuando termina sesión AR
scene.onAREnd = () => {
    console.log('AR terminado');
};
```

## Dispositivos compatibles

### Android (ARCore)
- Samsung Galaxy S8 y posteriores
- Google Pixel 2 y posteriores
- OnePlus 6 y posteriores
- Xiaomi Mi 8 y posteriores
- Huawei P20 y posteriores
- [Lista completa ARCore](https://developers.google.com/ar/devices)

### iOS
Safari no soporta WebXR nativamente. Alternativas:
- **Quick Look**: Usar archivos .usdz
- **WebXR Viewer**: App de Mozilla
- **8th Wall**: Solución comercial

## Limitaciones

| Limitación | Detalle |
|------------|---------|
| Solo Android Chrome | iOS no soporta WebXR nativo |
| HTTPS requerido | No funciona en HTTP |
| Modelos ligeros | Recomendado < 5MB |
| Sin oclusión | Objetos no se ocultan tras muebles reales |

## Optimización para AR

### Tamaño de modelos
- Máximo recomendado: 5MB
- Polígonos: < 50,000
- Texturas: 1024x1024

### Escala
Configurar escala real del objeto:
```
1 unidad en Three.js = 1 metro en AR
```

### Iluminación
AR usa iluminación estimada del entorno real.

## Casos de uso

| Sector | Aplicación |
|--------|------------|
| E-commerce | Ver producto en casa antes de comprar |
| Muebles | Probar sofá en el salón |
| Inmobiliaria | Visualizar reforma |
| Educación | Modelos 3D interactivos |
| Arte | Esculturas virtuales |

## Troubleshooting

### "AR no disponible"
- Verificar que es Android con Chrome
- Verificar que tiene ARCore instalado
- Verificar que la página usa HTTPS

### Modelo no aparece
- Verificar escala (puede ser muy pequeño/grande)
- Verificar que el modelo .glb es válido
- Apuntar a superficie plana

### Rendimiento bajo
- Reducir polígonos del modelo
- Reducir tamaño de texturas
- Desactivar sombras en AR
